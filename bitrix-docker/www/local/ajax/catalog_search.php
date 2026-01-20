<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
CModule::IncludeModule("sale");

$IBLOCK_ID = 13; // ID инфоблока каталога
$OFFER_IBLOCK_ID = 14; // ID инфоблока товарных предложений
$LIMIT_SECTIONS = 10; // Лимит разделов
$LIMIT_PRODUCTS = 20; // Лимит товаров

// Получаем параметры поиска
$searchTerm = trim($_POST['term'] ?? $_GET['term'] ?? '');
$priceFrom = floatval($_POST['s_price_from'] ?? $_GET['s_price_from'] ?? 0);
$priceTo = floatval($_POST['s_price_to'] ?? $_GET['s_price_to'] ?? 0);
$quantity = intval($_POST['kolvo'] ?? $_GET['kolvo'] ?? 0);

$result = array(
    'categories' => '',
    'products' => ''
);

// Проверяем, есть ли хотя бы один параметр для поиска
$hasSearchTerm = strlen($searchTerm) > 0;
$hasFilters = ($priceFrom > 0 || $priceTo > 0 || $quantity > 0);

if (!$hasSearchTerm && !$hasFilters) {
    echo json_encode($result);
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
    exit;
}

// Получаем информацию о связи товарных предложений с товарами
$arSKU = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
$hasOffers = !empty($arSKU) && $arSKU['IBLOCK_ID'] == $OFFER_IBLOCK_ID;

// Поиск разделов (только если есть текстовый запрос)
$sectionsHtml = '';
if ($hasSearchTerm) {
    $arFilterSections = array(
        'IBLOCK_ID' => $IBLOCK_ID,
        'ACTIVE' => 'Y',
        '%NAME' => $searchTerm
    );

    $arSelectSections = array('ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID');
    $rsSections = CIBlockSection::GetList(
        array('SORT' => 'ASC', 'NAME' => 'ASC'),
        $arFilterSections,
        false,
        $arSelectSections,
        array('nTopCount' => $LIMIT_SECTIONS)
    );
} else {
    $rsSections = false;
}

$sectionsList = array();
if ($rsSections) {
    while ($arSection = $rsSections->GetNext()) {
        // Получаем полную информацию о разделе для формирования URL
        $rsSectionFull = CIBlockSection::GetByID($arSection['ID']);
        $arSectionFull = $rsSectionFull->GetNext();

        // Формируем URL раздела
        $sectionUrl = '/';
        if (!empty($arSectionFull['SECTION_PAGE_URL'])) {
            $sectionUrl = $arSectionFull['SECTION_PAGE_URL'];
        } elseif (!empty($arSection['CODE'])) {
            $sectionUrl = '/' . $arSection['CODE'] . '/';
        } elseif (!empty($arSectionFull['CODE'])) {
            $sectionUrl = '/' . $arSectionFull['CODE'] . '/';
        }

        // Декодируем HTML-сущности в названии раздела
        $sectionName = html_entity_decode($arSection['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $sectionsList[] = array(
            'name' => $sectionName,
            'url' => $sectionUrl
        );
    }
}

// Формируем HTML для разделов
if (!empty($sectionsList)) {
    $sectionsHtml = '<div class="catalo-cats"><ul>';
    foreach ($sectionsList as $section) {
        $sectionsHtml .= '<li><a href="' . htmlspecialchars($section['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    $sectionsHtml .= '</ul></div>';
}

// Поиск товаров
$productsHtml = '';
$productsList = array();
$foundProductIds = array(); // ID товаров, которые уже добавлены (чтобы избежать дублей)
$addedUrls = array(); // Массив для отслеживания уже добавленных URL (чтобы избежать дублей)

// Получаем информацию об инфоблоке для формирования URL
$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);

// Функция для получения URL товара
function getProductUrl($arProduct, $arIBlock, $IBLOCK_ID) {
    $productUrl = '';
    $sectionId = $arProduct['IBLOCK_SECTION_ID'] ?? 0;
    $sectionCodePath = '';

    if ($sectionId > 0) {
        $arSectionPath = array();
        $rsSection = CIBlockSection::GetNavChain($IBLOCK_ID, $sectionId, array('ID', 'CODE', 'IBLOCK_SECTION_ID'));
        while ($arSection = $rsSection->GetNext()) {
            if (!empty($arSection['CODE'])) {
                $arSectionPath[] = $arSection['CODE'];
            }
        }
        if (!empty($arSectionPath)) {
            $sectionCodePath = implode('/', $arSectionPath);
        }
    }

    if (!empty($arProduct['DETAIL_PAGE_URL'])) {
        $productUrl = $arProduct['DETAIL_PAGE_URL'];
        if (empty($sectionCodePath)) {
            $productUrl = preg_replace('#(/catalog)/+/#', '$1/', $productUrl);
        }
    } elseif ($arIBlock && !empty($arIBlock['DETAIL_PAGE_URL'])) {
        if (empty($sectionCodePath)) {
            $elementCode = $arProduct['CODE'] ?? '';
            if (!empty($elementCode)) {
                $productUrl = '/catalog/' . $elementCode . '/';
            }
        } else {
            $productUrl = CIBlock::ReplaceDetailUrl($arIBlock['DETAIL_PAGE_URL'], $arProduct, false, 'E');
        }
    }

    return $productUrl;
}

// Функция для проверки товара по фильтрам
function checkProductFilters($productId, $priceFrom, $priceTo, $quantity) {
    // Получаем цену
    $arPrice = CCatalogProduct::GetOptimalPrice(
        $productId,
        1,
        array(),
        'N',
        array(),
        SITE_ID,
        array()
    );

    $productPrice = 0;
    if (isset($arPrice["PRICE"]["PRICE"])) {
        $productPrice = floatval($arPrice["PRICE"]["PRICE"]);
    }

    // Фильтр по цене
    if ($priceFrom > 0 && $productPrice < $priceFrom) {
        return false;
    }
    if ($priceTo > 0 && $productPrice > $priceTo) {
        return false;
    }

    // Получаем остаток
    $arProductCatalog = CCatalogProduct::GetByID($productId);
    $productQuantity = 0;
    if ($arProductCatalog) {
        $productQuantity = floatval($arProductCatalog['QUANTITY'] ?? 0);
    }

    // Фильтр по остатку
    if ($quantity > 0 && $productQuantity < $quantity) {
        return false;
    }

    return array('price' => $productPrice, 'quantity' => $productQuantity);
}

// 1. Ищем в товарных предложениях (если есть текстовый запрос)
$productsFromOffers = array(); // Массив товаров, найденных через предложения
if ($hasSearchTerm && $hasOffers) {
    $arFilterOffers = array(
        'IBLOCK_ID' => $OFFER_IBLOCK_ID,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        '%NAME' => $searchTerm
    );

    $arSelectOffers = array('ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'IBLOCK_ID');
    if ($arSKU['SKU_PROPERTY_ID'] > 0) {
        $arSelectOffers[] = 'PROPERTY_' . $arSKU['SKU_PROPERTY_ID'];
    }

    $rsOffers = CIBlockElement::GetList(
        array('SORT' => 'ASC', 'NAME' => 'ASC'),
        $arFilterOffers,
        false,
        array('nTopCount' => $LIMIT_PRODUCTS * 3),
        $arSelectOffers
    );

    while ($arOffer = $rsOffers->GetNext()) {
        $offerId = $arOffer['ID'];
        $linkedProductId = 0;

        // Получаем ID связанного товара из свойства
        if ($arSKU['SKU_PROPERTY_ID'] > 0) {
            $propKey = 'PROPERTY_' . $arSKU['SKU_PROPERTY_ID'] . '_VALUE';
            if (isset($arOffer[$propKey])) {
                $linkedProductId = intval($arOffer[$propKey]);
            }
        }

        if ($linkedProductId > 0 && !in_array($linkedProductId, $productsFromOffers)) {
            $productsFromOffers[] = $linkedProductId;
        }
    }
}

// 2. Ищем товары в основном инфоблоке
$arFilterProducts = array(
    'IBLOCK_ID' => $IBLOCK_ID,
    'ACTIVE' => 'Y',
    'ACTIVE_DATE' => 'Y'
);

if ($hasSearchTerm) {
    $arFilterProducts['%NAME'] = $searchTerm;
}

$arFilterProducts['CATALOG_AVAILABLE'] = 'Y';

$arSelectProducts = array('ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'IBLOCK_ID');
$rsProducts = CIBlockElement::GetList(
    array('SORT' => 'ASC', 'NAME' => 'ASC'),
    $arFilterProducts,
    false,
    array('nTopCount' => $LIMIT_PRODUCTS * 3),
    $arSelectProducts
);

// Обрабатываем товары
while ($arProduct = $rsProducts->GetNext()) {
    $productId = $arProduct['ID'];

    // Проверяем, есть ли у товара предложения
    $hasProductOffers = false;
    if ($hasOffers) {
        $arOffersList = CCatalogSKU::GetOffersList(
            array($productId),
            $IBLOCK_ID,
            array(),
            array('ID', 'NAME', 'CODE', 'ACTIVE')
        );
        $hasProductOffers = !empty($arOffersList[$productId]);
    }

    // Если у товара есть предложения, пропускаем его (будем выводить предложения)
    if ($hasProductOffers) {
        if (!in_array($productId, $foundProductIds)) {
            $foundProductIds[] = $productId;
        }
        continue;
    }

    // Проверяем товар по фильтрам
    $productData = checkProductFilters($productId, $priceFrom, $priceTo, $quantity);
    if ($productData === false) {
        continue;
    }

    // Получаем URL товара
    $productUrl = getProductUrl($arProduct, $arIBlock, $IBLOCK_ID);

    // Проверяем на дубликаты по URL
    if (in_array($productUrl, $addedUrls)) {
        continue;
    }

    // Декодируем название
    $productName = html_entity_decode($arProduct['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $productsList[] = array(
        'name' => $productName,
        'url' => $productUrl,
        'price' => $productData['price'],
        'quantity' => $productData['quantity']
    );

    $addedUrls[] = $productUrl;
    $foundProductIds[] = $productId;

    if (count($productsList) >= $LIMIT_PRODUCTS) {
        break;
    }
}

// 3. Обрабатываем товары с предложениями (найденные через поиск по предложениям и в основном инфоблоке)
$allProductsWithOffers = array_unique(array_merge($productsFromOffers, $foundProductIds));

if ($hasOffers && !empty($allProductsWithOffers)) {
    // Получаем информацию о товарах с предложениями
    $arFilterProductsWithOffers = array(
        'IBLOCK_ID' => $IBLOCK_ID,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        'ID' => $allProductsWithOffers
    );

    // Не добавляем фильтр по названию товара, так как мы ищем по предложениям
    // Товары из $productsFromOffers уже найдены через поиск по предложениям
    // А товары из $foundProductIds - через поиск в основном инфоблоке (уже проверены)

    $rsProductsWithOffers = CIBlockElement::GetList(
        array('SORT' => 'ASC', 'NAME' => 'ASC'),
        $arFilterProductsWithOffers,
        false,
        false,
        $arSelectProducts
    );

    while ($arProduct = $rsProductsWithOffers->GetNext()) {
        if (count($productsList) >= $LIMIT_PRODUCTS) {
            break;
        }

        $productId = $arProduct['ID'];

        // Получаем предложения товара
        $arOffersList = CCatalogSKU::GetOffersList(
            array($productId),
            $IBLOCK_ID,
            array(),
            array('ID', 'NAME', 'CODE', 'ACTIVE', 'IBLOCK_SECTION_ID')
        );

        if (!empty($arOffersList[$productId])) {
            // У товара есть предложения - добавляем первое подходящее предложение
            foreach ($arOffersList[$productId] as $offerData) {
                $offerId = $offerData['ID'];

                // Если есть поисковый запрос, проверяем совпадение в названии предложения
                if ($hasSearchTerm) {
                    $offerName = html_entity_decode($offerData['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (stripos($offerName, $searchTerm) === false && stripos($arProduct['NAME'], $searchTerm) === false) {
                        // Если поисковый запрос не найден ни в названии предложения, ни в названии товара - пропускаем
                        continue;
                    }
                }

                // Проверяем предложение по фильтрам
                $offerData_result = checkProductFilters($offerId, $priceFrom, $priceTo, $quantity);
                if ($offerData_result === false) {
                    continue;
                }

                // Получаем URL товара (используем URL основного товара)
                $productUrl = getProductUrl($arProduct, $arIBlock, $IBLOCK_ID);

                // Проверяем на дубликаты по URL
                if (in_array($productUrl, $addedUrls)) {
                    continue;
                }

                // Используем название предложения
                $productName = html_entity_decode($offerData['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $productsList[] = array(
                    'name' => $productName,
                    'url' => $productUrl,
                    'price' => $offerData_result['price'],
                    'quantity' => $offerData_result['quantity']
                );

                $addedUrls[] = $productUrl;

                // Добавляем только одно предложение на товар для поиска
                break;
            }
        } else {
            // У товара нет предложений - добавляем сам товар
            $productData = checkProductFilters($productId, $priceFrom, $priceTo, $quantity);
            if ($productData !== false) {
                $productUrl = getProductUrl($arProduct, $arIBlock, $IBLOCK_ID);

                // Проверяем на дубликаты по URL
                if (in_array($productUrl, $addedUrls)) {
                    continue;
                }

                $productName = html_entity_decode($arProduct['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $productsList[] = array(
                    'name' => $productName,
                    'url' => $productUrl,
                    'price' => $productData['price'],
                    'quantity' => $productData['quantity']
                );

                $addedUrls[] = $productUrl;
            }
        }
    }
}

// Формируем HTML для товаров
if (!empty($productsList)) {
    $productsHtml = '<div class="catalo-cats"><ul>';
    foreach ($productsList as $product) {
        $productsHtml .= '<li><a href="' . htmlspecialchars($product['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    $productsHtml .= '</ul></div>';
}

$result = array(
    $sectionsHtml,
    $productsHtml
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>