<?php
if (empty($arResult['ITEM']['OFFERS'])) {
    return;
}

$item = $arResult['ITEM'];
$productProperties = is_array($item['PROPERTIES'] ?? null) ? $item['PROPERTIES'] : [];
$productDisplayProperties = is_array($item['DISPLAY_PROPERTIES'] ?? null) ? $item['DISPLAY_PROPERTIES'] : [];
$displayPropertyCodes = $arParams['DISPLAY_PROPERTIES'] ?? [];
if (!is_array($displayPropertyCodes)) {
    $displayPropertyCodes = [];
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
$displayPropertyCodes = array_values(array_unique(array_merge(
    $displayPropertyCodes,
    catalogListGetCardDisplayPropertyCodes()
)));

$catalogItemPropertyHasValue = static function (?array $property): bool {
    if ($property === null) {
        return false;
    }

    $value = $property['VALUE'] ?? null;
    if ($value === null || $value === '' || $value === false) {
        $value = $property['~VALUE'] ?? null;
    }

    if ($value === null || $value === '' || $value === false) {
        return false;
    }

    return !is_array($value) || !empty($value);
};

$catalogItemFormatPropertyValue = static function ($value): string {
    if (is_array($value)) {
        $value = array_filter($value, static fn($part) => $part !== null && $part !== '');
        return implode(', ', array_map('strval', $value));
    }

    return (string)$value;
};

$catalogItemResolveProperty = static function (
    array $offer,
    string $propCode
) use ($item, $productProperties, $productDisplayProperties, $catalogItemPropertyHasValue): ?array {
    if ($catalogItemPropertyHasValue($offer['PROPERTIES'][$propCode] ?? null)) {
        return [
            'prop' => $offer['PROPERTIES'][$propCode],
            'source' => $offer,
        ];
    }

    if ($catalogItemPropertyHasValue($productProperties[$propCode] ?? null)) {
        return [
            'prop' => $productProperties[$propCode],
            'source' => $item,
        ];
    }

    if (!empty($productDisplayProperties[$propCode])) {
        $displayProperty = $productDisplayProperties[$propCode];

        return [
            'prop' => [
                'NAME' => $displayProperty['NAME'] ?? $propCode,
                'VALUE' => $displayProperty['VALUE'] ?? ($displayProperty['DISPLAY_VALUE'] ?? ''),
            ],
            'source' => $item,
            'display_value' => $displayProperty['DISPLAY_VALUE'] ?? null,
        ];
    }

    $propertyValue = catalogListResolveItemPropertyValue($offer, $propCode);
    if ($propertyValue === '') {
        $propertyValue = catalogListResolveItemPropertyValue($item, $propCode);
    }
    if ($propertyValue !== '') {
        return [
            'prop' => [
                'NAME' => catalogListGetPropertyDisplayName($propCode),
                'CODE' => $propCode,
                'VALUE' => $propertyValue,
                '~VALUE' => $propertyValue,
            ],
            'source' => $item,
            'display_value' => $propertyValue,
        ];
    }

    return null;
};

foreach ($arResult['ITEM']['OFFERS'] as $key => $offer) {
    $arResult['ITEM']['OFFERS'][$key]['DISPLAY_PROPERTIES'] = [];

    foreach ($displayPropertyCodes as $propCode) {
        if (catalogListIsHiddenDisplayPropertyCode((string)$propCode)) {
            continue;
        }
        $resolved = $catalogItemResolveProperty($offer, (string)$propCode);
        if ($resolved === null) {
            continue;
        }

        $prop = $resolved['prop'];
        $propSource = $resolved['source'];
        $displayName = catalogListGetPropertyDisplayName($propCode);
        if ($displayName === $propCode && !empty($prop['NAME'])) {
            $displayName = (string)$prop['NAME'];
        }

        $formatted = CIBlockFormatProperties::GetDisplayValue(
            $propSource,
            $prop,
            'catalog_out'
        );

        $displayValue = $resolved['display_value'] ?? ($formatted['DISPLAY_VALUE'] ?? ($formatted['VALUE'] ?? ($prop['VALUE'] ?? '')));

        $arResult['ITEM']['OFFERS'][$key]['DISPLAY_PROPERTIES'][$propCode] = [
            'NAME' => $displayName,
            'VALUE' => $catalogItemFormatPropertyValue($prop['VALUE'] ?? ''),
            'DISPLAY_VALUE' => $catalogItemFormatPropertyValue($displayValue),
        ];
    }
}

catalogListReorderItemOffersForActiveColorFilter($arResult['ITEM']);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_offer_url.php';
catalogItemEnrichOfferDetailUrls($arResult['ITEM']);
