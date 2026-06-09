<?php
if (!empty($arResult['ITEM']['OFFERS'])) {
    $productProperties = $arResult['ITEM']['PROPERTIES'] ?? [];
    $brandPropertyCode = 'BRENDY_DLYA_WEB';

    foreach ($arResult['ITEM']['OFFERS'] as $key => $offer) {
        $arResult['ITEM']['OFFERS'][$key]['DISPLAY_PROPERTIES'] = [];

        foreach ($arParams['DISPLAY_PROPERTIES'] as $propCode) {
            $prop = null;
            $propSource = null;

            if (!empty($offer['PROPERTIES'][$propCode]['VALUE'])) {
                $prop = $offer['PROPERTIES'][$propCode];
                $propSource = $offer;
            } elseif (
                $propCode === $brandPropertyCode
                && !empty($productProperties[$propCode]['VALUE'])
            ) {
                $prop = $productProperties[$propCode];
                $propSource = $arResult['ITEM'];
            }

            if ($prop === null) {
                continue;
            }

            $displayName = $propCode === $brandPropertyCode
                ? 'Бренд'
                : ($prop['NAME'] ?? $propCode);

            $arResult['ITEM']['OFFERS'][$key]['DISPLAY_PROPERTIES'][$propCode] = [
                'NAME' => $displayName,
                'VALUE' => $prop['VALUE'],
                'DISPLAY_VALUE' => CIBlockFormatProperties::GetDisplayValue(
                    $propSource,
                    $prop,
                    'catalog_out'
                ),
            ];
        }
    }
}
