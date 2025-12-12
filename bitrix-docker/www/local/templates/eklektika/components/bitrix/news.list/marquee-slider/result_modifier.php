<?php
// $arResult["ITEMS"] - минимум 44 элемента

if (!empty($arResult["ITEMS"]) && count($arResult["ITEMS"]) < 44) {
    $minItems = 44;
    $currentCount = count($arResult["ITEMS"]);
    $originalItems = $arResult["ITEMS"];
    
    // Дублируем элементы до достижения минимума в 44 элемента
    while (count($arResult["ITEMS"]) < $minItems) {
        foreach ($originalItems as $item) {
            if (count($arResult["ITEMS"]) >= $minItems) {
                break;
            }
            $arResult["ITEMS"][] = $item;
        }
    }
}