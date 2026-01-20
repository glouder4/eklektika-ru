<?php
    if (!empty($arResult['ITEM']['OFFERS'])) {
        foreach ($arResult['ITEM']['OFFERS'] as $key => $offer) {
            $arResult['ITEM'][$key]['DISPLAY_PROPERTIES'] = [];

            foreach ($arParams['DISPLAY_PROPERTIES'] as $propCode) {
                if (!isset($offer['PROPERTIES'][$propCode]) || empty($offer['PROPERTIES'][$propCode]['VALUE'])) {
                    continue;
                }

                $prop = $offer['PROPERTIES'][$propCode];
                $arResult['ITEM']['OFFERS'][$key]['DISPLAY_PROPERTIES'][$propCode] = [
                    'NAME' => $prop['NAME'],
                    'VALUE' => $prop['VALUE'],
                    'DISPLAY_VALUE' => CIBlockFormatProperties::GetDisplayValue(
                        $offer,
                        $prop,
                        'catalog_out'
                    ),
                ];
            }
        }
    }