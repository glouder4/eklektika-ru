<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Подключаем модули
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog'); // нужен для CCatalogSku

$arIBlocks = [];
$dbIBlock = \CIBlock::GetList(
    ["SORT" => "ASC"],
    ["ACTIVE" => "Y"]
);
while ($arIBlock = $dbIBlock->Fetch()) {
    $arIBlocks[$arIBlock["ID"]] = "[" . $arIBlock["ID"] . "] " . $arIBlock["NAME"];
}

$arOfferProperties = [];
$arOfferPropertyValues = [];

if (!empty($arCurrentValues["IBLOCK_ID"]) && ctype_digit((string)$arCurrentValues["IBLOCK_ID"])) {
    $iblockId = (int)$arCurrentValues["IBLOCK_ID"];

    // === Определяем IBLOCK_ID предложений через старый API ===
    $offersIblockId = null;
    if (class_exists('CCatalogSku')) {
        $skuInfo = \CCatalogSku::GetInfoByProductIBlock($iblockId);
        if ($skuInfo && !empty($skuInfo['IBLOCK_ID'])) {
            $offersIblockId = (int)$skuInfo['IBLOCK_ID'];
        }
    }

    if ($offersIblockId) {
        // Список свойств инфоблока предложений
        $dbProps = \CIBlockProperty::GetList(
            ["SORT" => "ASC", "NAME" => "ASC"],
            ["IBLOCK_ID" => $offersIblockId, "ACTIVE" => "Y"]
        );
        while ($arProp = $dbProps->Fetch()) {
            if (!empty($arProp["CODE"])) {
                $arOfferProperties[$arProp["CODE"]] = "[" . $arProp["CODE"] . "] " . $arProp["NAME"];
            }
        }

        // Значения выбранного свойства (если это список)
        if (!empty($arCurrentValues["FILTER_OFFER_PROPERTY"])) {
            $propCode = $arCurrentValues["FILTER_OFFER_PROPERTY"];
            $dbProp = \CIBlockProperty::GetList([], [
                "IBLOCK_ID" => $offersIblockId,
                "CODE" => $propCode
            ])->Fetch();

            if ($dbProp && $dbProp["PROPERTY_TYPE"] === "L") {
                $arOfferPropertyValues = ["*" => "— Любое значение —"];
                $dbEnum = \CIBlockPropertyEnum::GetList([], ["PROPERTY_ID" => $dbProp["ID"]]);
                while ($arEnum = $dbEnum->Fetch()) {
                    $arOfferPropertyValues[$arEnum["ID"]] = $arEnum["VALUE"];
                }
            } elseif ($dbProp) {
                // Для строк/чисел — оставим текстовое поле (ничего не делаем)
                $arOfferPropertyValues = null;
            }
        }
    }
}

$arComponentParameters = [
    "GROUPS" => [
        "DATA_SOURCE" => ["NAME" => "Источник данных"],
        "FILTER" => ["NAME" => "Фильтр по предложениям"],
        "VISUAL" => ["NAME" => "Внешний вид"],
    ],
    "PARAMETERS" => [
        "IBLOCK_ID" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Инфоблок товаров",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks ?: ["" => "— Выберите —"],
            "ADDITIONAL_VALUES" => "N",
            "REFRESH" => "Y",
        ],

        "FILTER_OFFER_PROPERTY" => [
            "PARENT" => "FILTER",
            "NAME" => "Свойство предложения для фильтра",
            "TYPE" => "LIST",
            "VALUES" => $arOfferProperties ?: ["" => "— Сначала выберите инфоблок —"],
            "ADDITIONAL_VALUES" => "N",
            "REFRESH" => "Y",
        ],

        "FILTER_OFFER_VALUE" => [
            "PARENT" => "FILTER",
            "NAME" => "Значение свойства",
            "TYPE" => isset($arOfferPropertyValues) ? "LIST" : "STRING",
            "VALUES" => $arOfferPropertyValues ?? [],
            "ADDITIONAL_VALUES" => "Y",
            "DEFAULT" => "",
        ],

        "PROPERTY_CODE" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Свойства товара для отображения",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => [],
            "ADDITIONAL_VALUES" => "Y",
        ],

        "ELEMENT_COUNT" => [
            "PARENT" => "VISUAL",
            "NAME" => "Количество элементов",
            "TYPE" => "STRING",
            "DEFAULT" => "10"
        ],
        "OFFER_BASE_FIELDS" => [
            "PARENT" => "FILTER",
            "NAME" => "Базовые поля предложений",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => [
                "ID" => "ID",
                "NAME" => "Название",
                "PREVIEW_PICTURE" => "Картинка анонса",
                "DETAIL_PICTURE" => "Детальная картинка",
                "PREVIEW_TEXT" => "Текст анонса",
                "DETAIL_TEXT" => "Детальный текст",
                "SORT" => "Сортировка",
                "ACTIVE" => "Активность"
            ],
            "ADDITIONAL_VALUES" => "Y",
            "DEFAULT" => ["ID", "NAME", "PREVIEW_PICTURE"]
        ],
        "OFFER_PROPERTY_CODE" => [
            "PARENT" => "FILTER",
            "NAME" => "Свойства предложений для отображения",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arOfferProperties ?: ["" => "— Сначала выберите инфоблок —"],
            "ADDITIONAL_VALUES" => "Y",
            "COLS" => 25
        ],
        "OFFER_GET_PRICES" => [
            "PARENT" => "FILTER",
            "NAME" => "Загружать цены предложений",
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N"
        ],
        "PRICE_AD_GROUP_ID" => [
            "PARENT" => "FILTER",
            "NAME" => "ID типа цены «Рекламная» (для FINAL_PRICE)",
            "TYPE" => "STRING",
            "DEFAULT" => "3"
        ],
        "SECTION_ID" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => "Раздел (категория)",
            "TYPE" => "STRING",
            "DEFAULT" => ""
        ],
        "CACHE_TIME" => ["DEFAULT" => 3600]
    ]
];

// Заполняем PROPERTY_CODE для основного инфоблока
if (!empty($arCurrentValues["IBLOCK_ID"])) {
    $propertyList = [];
    $dbProps = \CIBlockProperty::GetList(
        ["SORT" => "ASC"],
        ["IBLOCK_ID" => (int)$arCurrentValues["IBLOCK_ID"], "ACTIVE" => "Y"]
    );
    while ($arProp = $dbProps->Fetch()) {
        if (!empty($arProp["CODE"])) {
            $propertyList[$arProp["CODE"]] = "[" . $arProp["CODE"] . "] " . $arProp["NAME"];
        }
    }
    $arComponentParameters["PARAMETERS"]["PROPERTY_CODE"]["VALUES"] = $propertyList;
}