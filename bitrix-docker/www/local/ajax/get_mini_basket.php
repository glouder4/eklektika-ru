<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('sale') || !Loader::includeModule('catalog') || !Loader::includeModule('iblock')) {
    echo json_encode(['success' => false, 'error' => 'required_modules_not_loaded'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);
$basketItems = $basket->getBasketItems();
$lastOfferId = (int)($_SESSION['MINI_CART_LAST_OFFER_ID'] ?? 0);

$items = [];
$total = 0.0;
$count = 0;

foreach ($basketItems as $basketItem) {
    $isDelay = method_exists($basketItem, 'isDelay') ? (bool)$basketItem->isDelay() : false;
    $isSubscribe = method_exists($basketItem, 'isSubscribe') ? (bool)$basketItem->isSubscribe() : false;
    if ($isDelay || $isSubscribe) {
        continue;
    }

    $quantity = (float)$basketItem->getQuantity();
    if ($quantity <= 0) {
        continue;
    }

    $productId = (int)$basketItem->getProductId();
    $name = (string)$basketItem->getField('NAME');
    $price = (float)$basketItem->getPrice();
    $itemTotal = $price * $quantity;

    $detailUrl = '';
    $imageSrc = '';
    $article = '';

    $parentProductId = $productId;
    if (\CCatalogSku::IsExistOffers($productId)) {
        $parentProductId = $productId;
    } else {
        $skuInfo = \CCatalogSku::GetProductInfo($productId);
        if (is_array($skuInfo) && !empty($skuInfo['ID'])) {
            $parentProductId = (int)$skuInfo['ID'];
        }
    }

    $elementRes = \CIBlockElement::GetList(
        [],
        ['ID' => $parentProductId, 'ACTIVE' => 'Y'],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
    );
    if ($element = $elementRes->GetNext()) {
        $detailUrl = (string)($element['DETAIL_PAGE_URL'] ?? '');
        $pictureId = (int)($element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE']);
        if ($pictureId > 0) {
            $file = \CFile::GetFileArray($pictureId);
            if (is_array($file) && !empty($file['SRC'])) {
                $imageSrc = (string)$file['SRC'];
            }
        }
    }

    // Fallback 1: берем первое фото из свойства MORE_PHOTO у родительского товара.
    if ($imageSrc === '') {
        $morePhotoRes = \CIBlockElement::GetProperty(
            13,
            $parentProductId,
            ['sort' => 'asc'],
            ['CODE' => 'MORE_PHOTO']
        );
        if ($morePhoto = $morePhotoRes->Fetch()) {
            $morePhotoId = (int)($morePhoto['VALUE'] ?? 0);
            if ($morePhotoId > 0) {
                $file = \CFile::GetFileArray($morePhotoId);
                if (is_array($file) && !empty($file['SRC'])) {
                    $imageSrc = (string)$file['SRC'];
                }
            }
        }
    }

    // Fallback 2: если у родителя нет картинки — пробуем у самого оффера.
    if ($imageSrc === '') {
        $offerRes = \CIBlockElement::GetList(
            [],
            ['ID' => $productId, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
        );
        if ($offer = $offerRes->GetNext()) {
            $offerPictureId = (int)($offer['PREVIEW_PICTURE'] ?: $offer['DETAIL_PICTURE']);
            if ($offerPictureId > 0) {
                $file = \CFile::GetFileArray($offerPictureId);
                if (is_array($file) && !empty($file['SRC'])) {
                    $imageSrc = (string)$file['SRC'];
                }
            }
        }
    }

    if ($imageSrc === '') {
        $imageSrc = SITE_TEMPLATE_PATH . '/assets/images/no-image.png';
    }

    $articlePropRes = \CIBlockElement::GetProperty(
        14,
        $productId,
        ['sort' => 'asc'],
        ['CODE' => 'CML2_ARTICLE']
    );
    if ($articleProp = $articlePropRes->Fetch()) {
        $article = (string)($articleProp['VALUE'] ?? '');
    }

    $items[] = [
        'offer_id' => $productId,
        'name' => $name,
        'url' => $detailUrl,
        'image' => $imageSrc,
        'article' => $article,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $itemTotal,
    ];

    $total += $itemTotal;
    $count += (int)$quantity;
}

if (!empty($items) && $lastOfferId > 0) {
    $selected = null;
    foreach ($items as $row) {
        if ((int)$row['offer_id'] === $lastOfferId) {
            $selected = $row;
            break;
        }
    }
    if ($selected !== null) {
        $items = [$selected];
        $total = (float)$selected['total'];
        $count = (int)$selected['quantity'];
    }
}

$html = '';
foreach ($items as $item) {
    $html .= '<div class="product-mini">';
    $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="product-mini_img"><img src="' . htmlspecialchars($item['image']) . '" alt=""></a>';
    $html .= '<div class="product-mini_fields">';
    $html .= '<p><span>' . htmlspecialchars($item['name']) . '</span></p>';
    $html .= '<p><span>Артикул:</span> ' . htmlspecialchars($item['article']) . '</p>';
    $html .= '<p><span>Тираж:</span> ' . number_format($item['quantity'], 0, '.', '') . ' шт.</p>';
    $html .= '<p><span>Цена:</span> ' . number_format($item['price'], 2, '.', ' ') . '</p>';
    $html .= '</div>';
    $html .= '<div class="product-mini_price">' . number_format($item['total'], 2, '.', ' ') . '<sub></sub></div>';
    $html .= '</div>';
}

if (!empty($items)) {
    $html .= '<span class="icon-cart"></span>';
    $html .= '<span><span style="font-weiht:bold;">' . number_format($total, 2, '.', ' ') . '</span>руб.</span>';
    $html .= '<div class="cart-side-buttons"><a href="/cart.php" class="btn btn-blue btn-round">Купить</a></div>';
}

echo json_encode([
    'success' => true,
    'count' => $count,
    'total' => $total,
    'html' => $html,
], JSON_UNESCAPED_UNICODE);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");

