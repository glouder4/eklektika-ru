<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Loader;

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogElementComponent $component
 */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

if( !isset($arParams['SELECTED_OFFER_CODE']) ){
    header("Location: /");
    exit();
}

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
    return ['error' => 'Модули не подключены'];
}

$offerCode = trim($arParams['SELECTED_OFFER_CODE'] ?? '');
$offersIblockId = 14; // ваш ID инфоблока предложений

if (!$offerCode) {
    return ['error' => 'Не указан код предложения'];
}

// === Шаг 1: Находим ID предложения по CODE ===
$offerElement = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $offersIblockId,
        'CODE'      => $offerCode,
        'ACTIVE'    => 'Y'
    ],
    false,
    ['nTopCount' => 1],
    [
        'ID',
        'NAME',
        'IBLOCK_ID',
        'ACTIVE',
        'DATE_ACTIVE_FROM',
        'DATE_ACTIVE_TO',
        'SORT',
        'PREVIEW_TEXT',
        'PREVIEW_PICTURE',
        'DETAIL_TEXT',
        'DETAIL_PICTURE',
        'TAGS',
        'SHOW_COUNTER',
        'CODE'
    ]
)->GetNext();

if (!$offerElement) {
    return ['error' => 'Предложение не найдено'];
}

$offerId = (int)$offerElement['ID'];

// === Шаг 2: Получаем ВСЕ свойства предложения ===
$properties = [];
$rsProps = \CIBlockElement::GetProperty(
    $offersIblockId,
    $offerId
);
while ($prop = $rsProps->Fetch()) {
    if (!isset($properties[$prop['CODE']])) {
        $properties[$prop['CODE']] = [];
    }
    $properties[$prop['CODE']][] = [
        'VALUE'      => $prop['VALUE'],
        'DESCRIPTION'=> $prop['DESCRIPTION'],
        'ID'         => $prop['ID'],
        'PROPERTY_TYPE' => $prop['PROPERTY_TYPE'],
    ];
}

// Упрощаем одиночные значения
foreach ($properties as $code => $vals) {
    if (count($vals) == 1) {
        $properties[$code] = $vals[0]['VALUE'];
    }
}

// === Шаг 3: Получаем цены предложения ===
$prices = [];
$rsPrices = \CPrice::GetList([], ['PRODUCT_ID' => $offerId]);
while ($price = $rsPrices->Fetch()) {
    $prices[] = [
        'ID'              => $price['ID'],
        'PRICE'           => (float)$price['PRICE'],
        'CURRENCY'        => $price['CURRENCY'],
        'PRICE_TYPE_ID'   => (int)$price['PRICE_TYPE_ID'],
        'CATALOG_GROUP_NAME' => '',
    ];
}

// Добавляем названия типов цен
if (!empty($prices)) {
    $priceTypeIds = array_unique(array_column($prices, 'PRICE_TYPE_ID'));
    $priceTypes = [];
    $rsGroups = \CCatalogGroup::GetList([], ['ID' => $priceTypeIds]);
    while ($group = $rsGroups->Fetch()) {
        $priceTypes[$group['ID']] = $group['NAME'];
    }
    foreach ($prices as &$p) {
        $p['CATALOG_GROUP_NAME'] = $priceTypes[$p['PRICE_TYPE_ID']] ?? '';
    }
    unset($p);
}

// === Шаг 4: Получаем связь с родительским товаром ===
$parentProductId = null;
$parentProductData = [];

// Связь хранится в свойстве CML2_LINK (или другом, если вы меняли)
$linkPropertyCode = 'CML2_LINK'; // ← замените, если у вас другой код связи

$rsLink = \CIBlockElement::GetProperty(
    $offersIblockId,
    $offerId,
    ['sort' => 'asc'],
    ['CODE' => $linkPropertyCode]
);
if ($linkProp = $rsLink->Fetch()) {
    $parentProductId = (int)$linkProp['VALUE'];

    // Получаем данные родителя
    $parentEl = \CIBlockElement::GetList(
        [],
        ['ID' => $parentProductId, 'ACTIVE' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL']
    )->GetNext();

    if ($parentEl) {
        $parentProductData = [
            'ID'       => $parentEl['ID'],
            'NAME'     => $parentEl['NAME'],
            'CODE'     => $parentEl['CODE'],
            'URL'      => $parentEl['DETAIL_PAGE_URL'] ?? ''
        ];
    }
}
$previewPictureUrl = '';
$detailPictureUrl = '';

if (!empty($offerElement['PREVIEW_PICTURE'])) {
    $previewPictureUrl = \CFile::GetPath($offerElement['PREVIEW_PICTURE']);
}
else{
    $previewPictureUrl = "/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png";
}
if (!empty($offerElement['DETAIL_PICTURE'])) {
    $detailPictureUrl = \CFile::GetPath($offerElement['DETAIL_PICTURE']);
}
else{
    $detailPictureUrl = "/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png";
}

// === Шаг 5: Формируем итоговый массив ===
$offerData = [
    'ID'                => $offerId,
    'NAME'              => $offerElement['NAME'],
    'CODE'              => $offerCode,
    'IBLOCK_ID'         => $offersIblockId,
    'PARENT_PRODUCT'    => $parentProductData,
    'PROPERTIES'        => $properties,
    'PRICES'            => $prices,
    'HAS_PRICE'         => !empty($prices),
    'ACTIVE'            => $offerElement['ACTIVE'] === 'Y',
    'PREVIEW_PICTURE'   => $previewPictureUrl,
    'DETAIL_PICTURE'    => $detailPictureUrl,
    'PREVIEW_TEXT'      => $offerElement['PREVIEW_TEXT'] ?? '',
    'DETAIL_TEXT'       => $offerElement['DETAIL_TEXT'] ?? '',
    'DATE_ACTIVE_FROM'  => $offerElement['DATE_ACTIVE_FROM'] ?? '',
    'TAGS'              => $offerElement['TAGS'] ?? '',
];

$arResult['OFFER_DATA'] = $offerData;