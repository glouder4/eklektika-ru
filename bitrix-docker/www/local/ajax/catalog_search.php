<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
CModule::IncludeModule("sale");

$IBLOCK_ID = 13; // ID инфоблока каталога
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
$arFilterProducts = array(
    'IBLOCK_ID' => $IBLOCK_ID,
    'ACTIVE' => 'Y',
    'ACTIVE_DATE' => 'Y'
);

// Фильтр по названию (только если есть текстовый запрос)
if ($hasSearchTerm) {
    $arFilterProducts['%NAME'] = $searchTerm;
}

// Дополнительные фильтры по цене и остатку
$arFilterProducts['CATALOG_AVAILABLE'] = 'Y';

$arSelectProducts = array('ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'IBLOCK_ID');
$rsProducts = CIBlockElement::GetList(
    array('SORT' => 'ASC', 'NAME' => 'ASC'),
    $arFilterProducts,
    false,
    array('nTopCount' => $LIMIT_PRODUCTS * 2), // Берем больше для фильтрации
    $arSelectProducts
);

// Получаем информацию об инфоблоке для формирования URL
$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);

$productsList = array();
while ($arProduct = $rsProducts->GetNext()) {
    $productId = $arProduct['ID'];
    
    // Получаем цену товара
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
        continue;
    }
    if ($priceTo > 0 && $productPrice > $priceTo) {
        continue;
    }
    
    // Получаем остаток товара
    $arProductCatalog = CCatalogProduct::GetByID($productId);
    $productQuantity = 0;
    if ($arProductCatalog) {
        $productQuantity = floatval($arProductCatalog['QUANTITY'] ?? 0);
    }
    
    // Фильтр по остатку (минимальный остаток - "от" переданного значения)
    // Показываем только товары с остатком >= переданного значения
    if ($quantity > 0 && $productQuantity < $quantity) {
        continue;
    }
    
    // Формируем URL товара через Bitrix API
    $productUrl = '';
    
    // Получаем информацию о разделе товара
    $sectionId = $arProduct['IBLOCK_SECTION_ID'] ?? 0;
    $sectionCodePath = '';
    
    if ($sectionId > 0) {
        // Получаем путь к разделу через GetNavChain
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
        // Используем DETAIL_PAGE_URL, который уже сформирован Bitrix
        $productUrl = $arProduct['DETAIL_PAGE_URL'];
        
        // Для товаров без родительской категории исправляем URL
        if (empty($sectionCodePath)) {
            // Убираем двойные слеши, если они есть
            $productUrl = preg_replace('#(/catalog)/+/#', '$1/', $productUrl);
        }
    } elseif ($arIBlock && !empty($arIBlock['DETAIL_PAGE_URL'])) {
        // Если товар без родительской категории, используем альтернативный формат URL
        if (empty($sectionCodePath)) {
            // Формируем URL в формате /catalog/element-code/ (без SECTION_CODE_PATH)
            $elementCode = $arProduct['CODE'] ?? '';
            if (!empty($elementCode)) {
                $productUrl = '/catalog/' . $elementCode . '/';
            }
        } else {
            // Для товаров с разделом используем стандартный формат
            $productUrl = CIBlock::ReplaceDetailUrl($arIBlock['DETAIL_PAGE_URL'], $arProduct, false, 'E');
        }
    }
    
    // Декодируем HTML-сущности в названии товара
    $productName = html_entity_decode($arProduct['NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    $productsList[] = array(
        'name' => $productName,
        'url' => $productUrl,
        'price' => $productPrice,
        'quantity' => $productQuantity
    );
    
    // Ограничиваем количество результатов
    if (count($productsList) >= $LIMIT_PRODUCTS) {
        break;
    }
}

// Формируем HTML для товаров
if (!empty($productsList)) {
    $productsHtml = '<div class="catalo-cats"><ul>';
    foreach ($productsList as $product) {
        // Название уже декодировано при добавлении в массив
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
