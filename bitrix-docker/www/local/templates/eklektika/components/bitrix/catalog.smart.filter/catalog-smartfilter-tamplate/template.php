<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Iblock\SectionPropertyTable;

$this->setFrameMode(true);

$this->addExternalCss("/assets/snippets/eFilter/html/css/eFilter.css");
$this->addExternalCss("/assets/snippets/eFilter/html/css/slider.css");

/*$this->addExternalJs("/assets/snippets/eFilter/html/js/eFilter.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/evoSortBlock.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/jquery-ui.min.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/jquery.ui.touch-punch.min.js");*/

$this->addExternalJs("/local/templates/eklektika/components/bitrix/catalog.smart.filter/catalog-smartfilter-tamplate/external.js");
?>

<?php
$useSef = isset($arResult["SEF_SET_FILTER_URL"]) && !empty($arResult["SEF_SET_FILTER_URL"]);
// Проверяем наличие доступных фильтров
$hasAvailableFilters = isset($arResult["HAS_AVAILABLE_FILTERS"]) && $arResult["HAS_AVAILABLE_FILTERS"];

?>

<?if ($hasAvailableFilters):?>
<form id="eFiltr" name="<?echo $arResult["FILTER_NAME"]."_form"?>" action="<?echo $arResult["FORM_ACTION"]?>" method="get" class="smartfilter eFiltr eFiltr_form filters">
    <?foreach($arResult["HIDDEN"] as $arItem):?>
        <input type="hidden" name="<?echo $arItem["CONTROL_NAME"]?>" id="<?echo $arItem["CONTROL_ID"]?>" value="<?echo $arItem["HTML_VALUE"]?>" />
    <?endforeach;?>
    <div class="row">
        <?foreach($arResult["ITEMS"] as $key=>$arItem)//prices
        {
            $key = $arItem["ENCODED_ID"];
            if(isset($arItem["PRICE"])):
                if ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] <= 0)
                    continue;

                $precision = 0;
                $step_num = 4;
                $step = ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"]) / $step_num;
                $prices = array();
                if (Bitrix\Main\Loader::includeModule("currency"))
                {
                    for ($i = 0; $i < $step_num; $i++)
                    {
                        $prices[$i] = CCurrencyLang::CurrencyFormat($arItem["VALUES"]["MIN"]["VALUE"] + $step*$i, $arItem["VALUES"]["MIN"]["CURRENCY"], false);
                    }
                    $prices[$step_num] = CCurrencyLang::CurrencyFormat($arItem["VALUES"]["MAX"]["VALUE"], $arItem["VALUES"]["MAX"]["CURRENCY"], false);
                }
                else
                {
                    $precision = $arItem["DECIMALS"]? $arItem["DECIMALS"]: 0;
                    for ($i = 0; $i < $step_num; $i++)
                    {
                        $prices[$i] = number_format($arItem["VALUES"]["MIN"]["VALUE"] + $step*$i, $precision, ".", "");
                    }
                    $prices[$step_num] = number_format($arItem["VALUES"]["MAX"]["VALUE"], $precision, ".", "");
                }
                ?>

                <div class="col select-drop side-filter" id="price_filter_block">
                    <div class="select-drop_title" onclick="smartFilter.hideFilterProps(this)">Цена</div>
                    <div class=" select-drop_list side-trigger__content" data-role="bx_filter_block" style="display:none;">
                        <div class="side-filter__slider bx-filter-parameters-box-container">
                            <div class="side-filter__slider-inputs">
                                <label> <span class="side-filter__dash">от</span>
                                    <input
                                            class="min-price side-filter__input-lower input-number"
                                            type="text"
                                            name="<?echo $arItem["VALUES"]["MIN"]["CONTROL_NAME"]?>"
                                            id="<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"
                                            value="<?echo intval($arItem["VALUES"]["MIN"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MIN"]["FILTERED_VALUE"] : $arItem["VALUES"]["MIN"]["VALUE"]?>"
                                            data-min-val="<?echo intval($arItem["VALUES"]["MIN"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MIN"]["FILTERED_VALUE"] : $arItem["VALUES"]["MIN"]["VALUE"]?>"
                                            size="5"
                                            onkeyup="smartFilter.keyup(this)"
                                    />
                                    <br>
                                </label>
                                <label> <span class="side-filter__dash">до</span>
                                    <input
                                            class="max-price side-filter__input-upper input-number"
                                            type="text"
                                            name="<?echo $arItem["VALUES"]["MAX"]["CONTROL_NAME"]?>"
                                            id="<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>"
                                            value="<?echo intval($arItem["VALUES"]["MAX"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MAX"]["FILTERED_VALUE"] : $arItem["VALUES"]["MAX"]["VALUE"]?>"
                                            data-max-val="<?echo intval($arItem["VALUES"]["MAX"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MAX"]["FILTERED_VALUE"] : $arItem["VALUES"]["MAX"]["VALUE"]?>"
                                            size="5"
                                            onkeyup="smartFilter.keyup(this)"
                                    />
                                </label>
                                <input type="text" id="resultsPrice8" name="f8" value="minmax~," style="display:none">
                            </div>

                            <div class="col-xs-10 col-xs-offset-1 bx-ui-slider-track-container" style="display:none;">
                                <div class="bx-ui-slider-track" id="drag_track_<?=$key?>">
                                    <?for($i = 0; $i <= $step_num; $i++):?>
                                        <div class="bx-ui-slider-part p<?=$i+1?>"><span><?=$prices[$i]?></span></div>
                                    <?endfor;?>

                                    <div class="bx-ui-slider-pricebar-vd" style="left: 0;right: 0;" id="colorUnavailableActive_<?=$key?>"></div>
                                    <div class="bx-ui-slider-pricebar-vn" style="left: 0;right: 0;" id="colorAvailableInactive_<?=$key?>"></div>
                                    <div class="bx-ui-slider-pricebar-v"  style="left: 0;right: 0;" id="colorAvailableActive_<?=$key?>"></div>
                                    <div class="bx-ui-slider-range" id="drag_tracker_<?=$key?>"  style="left: 0%; right: 0%;">
                                        <a class="bx-ui-slider-handle left"  style="left:0;" href="javascript:void(0)" id="left_slider_<?=$key?>"></a>
                                        <a class="bx-ui-slider-handle right" style="right:0;" href="javascript:void(0)" id="right_slider_<?=$key?>"></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?
            $arJsParams = array(
                "leftSlider" => 'left_slider_'.$key,
                "rightSlider" => 'right_slider_'.$key,
                "tracker" => "drag_tracker_".$key,
                "trackerWrap" => "drag_track_".$key,
                "minInputId" => $arItem["VALUES"]["MIN"]["CONTROL_ID"],
                "maxInputId" => $arItem["VALUES"]["MAX"]["CONTROL_ID"],
                "minPrice" => $arItem["VALUES"]["MIN"]["VALUE"],
                "maxPrice" => $arItem["VALUES"]["MAX"]["VALUE"],
                "curMinPrice" => $arItem["VALUES"]["MIN"]["HTML_VALUE"],
                "curMaxPrice" => $arItem["VALUES"]["MAX"]["HTML_VALUE"],
                "fltMinPrice" => intval($arItem["VALUES"]["MIN"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MIN"]["FILTERED_VALUE"] : $arItem["VALUES"]["MIN"]["VALUE"] ,
                "fltMaxPrice" => intval($arItem["VALUES"]["MAX"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MAX"]["FILTERED_VALUE"] : $arItem["VALUES"]["MAX"]["VALUE"],
                "precision" => $precision,
                "colorUnavailableActive" => 'colorUnavailableActive_'.$key,
                "colorAvailableActive" => 'colorAvailableActive_'.$key,
                "colorAvailableInactive" => 'colorAvailableInactive_'.$key,
            );
            ?>
                <script>
                    BX.ready(function(){
                        window['trackBar<?=$key?>'] = new BX.Iblock.SmartFilter(<?=CUtil::PhpToJSObject($arJsParams)?>);
                    });
                </script>
            <?endif;
        }

        //not prices
        foreach($arResult["ITEMS"] as $key=>$arItem)
        {
            if(
                empty($arItem["VALUES"])
                || isset($arItem["PRICE"])
            )
                continue;

            if (
                $arItem["DISPLAY_TYPE"] === SectionPropertyTable::NUMBERS_WITH_SLIDER
                && ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] <= 0)
            )
                continue;
            ?>
                <?
                    $arCur = current($arItem["VALUES"]);
                    // Ищем элемент, у которого CHECKED установлен
                    $selectedItem = array_filter($arItem["VALUES"], function($ar) { return !empty($ar["CHECKED"]); });
                    $selectedItem = $selectedItem ? reset($selectedItem) : null;

                    $haveAvailableElements = (bool)array_filter($arItem["VALUES"], function($ar) { 
                        return $ar["DISABLED"] != 1; 
                    });

                    if( !$haveAvailableElements )
                        continue;
                ?>
                <div class="col">
                    <div class="select-ul">
                        <?php if (!is_null($selectedItem)): ?>
                            <button type="button" class="select-ul-btn is-selected">
                                <?=$selectedItem['VALUE'];?> 
                                <a rel="nofollow" href="#" onclick="
                                    var allRadio = document.getElementById('<?=htmlspecialcharsbx('all_'.$arCur["CONTROL_ID"])?>');
                                    if (allRadio) {
                                        allRadio.checked = true;
                                        smartFilter.click(allRadio);
                                    }
                                    return false;
                                "></a>
                            </button>
                        <?php else: ?>
                            <button type="button" class="select-ul-btn" onclick="smartFilter.hideFilterProps(this)">
                                <?=$arItem["NAME"]?>
                                <?if ($arItem["FILTER_HINT"] <> ""):?>
                                    <i id="item_title_hint_<?echo $arItem["ID"]?>" class="fa fa-question-circle"></i>
                                    <script>
                                        new top.BX.CHint({
                                            parent: top.BX("item_title_hint_<?echo $arItem["ID"]?>"),
                                            show_timeout: 10,
                                            hide_timeout: 200,
                                            dx: 2,
                                            preventHide: true,
                                            min_width: 250,
                                            hint: '<?= CUtil::JSEscape($arItem["FILTER_HINT"])?>'
                                        });
                                    </script>
                                <?endif?>
                                <i data-role="prop_angle" class="fa fa-angle-<?if ($arItem["DISPLAY_EXPANDED"]== "Y"):?>up<?else:?>down<?endif?>"></i>
                            </button>
                        <?php endif; ?>

                        <ul data-role="bx_filter_block">
                            <?
                            switch ($arItem["DISPLAY_TYPE"])
                            {
                                case SectionPropertyTable::NUMBERS_WITH_SLIDER://NUMBERS_WITH_SLIDER
                                    ?>
                                    <div class="col-xs-6 bx-filter-parameters-box-container-block bx-left">
                                        <i class="bx-ft-sub"><?=GetMessage("CT_BCSF_FILTER_FROM")?></i>
                                        <div class="bx-filter-input-container">
                                            <input
                                                    class="min-price"
                                                    type="text"
                                                    name="<?echo $arItem["VALUES"]["MIN"]["CONTROL_NAME"]?>"
                                                    id="<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"
                                                    value="<?echo $arItem["VALUES"]["MIN"]["HTML_VALUE"]?>"
                                                    size="5"
                                                    onkeyup="smartFilter.keyup(this)"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 bx-filter-parameters-box-container-block bx-right">
                                        <i class="bx-ft-sub"><?=GetMessage("CT_BCSF_FILTER_TO")?></i>
                                        <div class="bx-filter-input-container">
                                            <input
                                                    class="max-price"
                                                    type="text"
                                                    name="<?echo $arItem["VALUES"]["MAX"]["CONTROL_NAME"]?>"
                                                    id="<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>"
                                                    value="<?echo $arItem["VALUES"]["MAX"]["HTML_VALUE"]?>"
                                                    size="5"
                                                    onkeyup="smartFilter.keyup(this)"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-xs-10 col-xs-offset-1 bx-ui-slider-track-container">
                                        <div class="bx-ui-slider-track" id="drag_track_<?=$key?>">
                                            <?
                                            $precision = $arItem["DECIMALS"]? $arItem["DECIMALS"]: 0;
                                            $step = ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"]) / 4;
                                            $value1 = number_format($arItem["VALUES"]["MIN"]["VALUE"], $precision, ".", "");
                                            $value2 = number_format($arItem["VALUES"]["MIN"]["VALUE"] + $step, $precision, ".", "");
                                            $value3 = number_format($arItem["VALUES"]["MIN"]["VALUE"] + $step * 2, $precision, ".", "");
                                            $value4 = number_format($arItem["VALUES"]["MIN"]["VALUE"] + $step * 3, $precision, ".", "");
                                            $value5 = number_format($arItem["VALUES"]["MAX"]["VALUE"], $precision, ".", "");
                                            ?>
                                            <div class="bx-ui-slider-part p1"><span><?=$value1?></span></div>
                                            <div class="bx-ui-slider-part p2"><span><?=$value2?></span></div>
                                            <div class="bx-ui-slider-part p3"><span><?=$value3?></span></div>
                                            <div class="bx-ui-slider-part p4"><span><?=$value4?></span></div>
                                            <div class="bx-ui-slider-part p5"><span><?=$value5?></span></div>

                                            <div class="bx-ui-slider-pricebar-vd" style="left: 0;right: 0;" id="colorUnavailableActive_<?=$key?>"></div>
                                            <div class="bx-ui-slider-pricebar-vn" style="left: 0;right: 0;" id="colorAvailableInactive_<?=$key?>"></div>
                                            <div class="bx-ui-slider-pricebar-v"  style="left: 0;right: 0;" id="colorAvailableActive_<?=$key?>"></div>
                                            <div class="bx-ui-slider-range" 	id="drag_tracker_<?=$key?>"  style="left: 0;right: 0;">
                                                <a class="bx-ui-slider-handle left"  style="left:0;" href="javascript:void(0)" id="left_slider_<?=$key?>"></a>
                                                <a class="bx-ui-slider-handle right" style="right:0;" href="javascript:void(0)" id="right_slider_<?=$key?>"></a>
                                            </div>
                                        </div>
                                    </div>
                                <?
                                $arJsParams = array(
                                    "leftSlider" => 'left_slider_'.$key,
                                    "rightSlider" => 'right_slider_'.$key,
                                    "tracker" => "drag_tracker_".$key,
                                    "trackerWrap" => "drag_track_".$key,
                                    "minInputId" => $arItem["VALUES"]["MIN"]["CONTROL_ID"],
                                    "maxInputId" => $arItem["VALUES"]["MAX"]["CONTROL_ID"],
                                    "minPrice" => $arItem["VALUES"]["MIN"]["VALUE"],
                                    "maxPrice" => $arItem["VALUES"]["MAX"]["VALUE"],
                                    "curMinPrice" => $arItem["VALUES"]["MIN"]["HTML_VALUE"],
                                    "curMaxPrice" => $arItem["VALUES"]["MAX"]["HTML_VALUE"],
                                    "fltMinPrice" => intval($arItem["VALUES"]["MIN"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MIN"]["FILTERED_VALUE"] : $arItem["VALUES"]["MIN"]["VALUE"] ,
                                    "fltMaxPrice" => intval($arItem["VALUES"]["MAX"]["FILTERED_VALUE"]) ? $arItem["VALUES"]["MAX"]["FILTERED_VALUE"] : $arItem["VALUES"]["MAX"]["VALUE"],
                                    "precision" => $arItem["DECIMALS"]? $arItem["DECIMALS"]: 0,
                                    "colorUnavailableActive" => 'colorUnavailableActive_'.$key,
                                    "colorAvailableActive" => 'colorAvailableActive_'.$key,
                                    "colorAvailableInactive" => 'colorAvailableInactive_'.$key,
                                );
                                ?>
                                    <script>
                                        BX.ready(function(){
                                            window['trackBar<?=$key?>'] = new BX.Iblock.SmartFilter(<?=CUtil::PhpToJSObject($arJsParams)?>);
                                        });
                                    </script>
                                <?
                                break;
                                case SectionPropertyTable::NUMBERS://NUMBERS
                                ?>
                                    <div class="col-xs-6 bx-filter-parameters-box-container-block bx-left">
                                        <i class="bx-ft-sub"><?=GetMessage("CT_BCSF_FILTER_FROM")?></i>
                                        <div class="bx-filter-input-container">
                                            <input
                                                    class="min-price"
                                                    type="text"
                                                    name="<?echo $arItem["VALUES"]["MIN"]["CONTROL_NAME"]?>"
                                                    id="<?echo $arItem["VALUES"]["MIN"]["CONTROL_ID"]?>"
                                                    value="<?echo $arItem["VALUES"]["MIN"]["HTML_VALUE"]?>"
                                                    size="5"
                                                    onkeyup="smartFilter.keyup(this)"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 bx-filter-parameters-box-container-block bx-right">
                                        <i class="bx-ft-sub"><?=GetMessage("CT_BCSF_FILTER_TO")?></i>
                                        <div class="bx-filter-input-container">
                                            <input
                                                    class="max-price"
                                                    type="text"
                                                    name="<?echo $arItem["VALUES"]["MAX"]["CONTROL_NAME"]?>"
                                                    id="<?echo $arItem["VALUES"]["MAX"]["CONTROL_ID"]?>"
                                                    value="<?echo $arItem["VALUES"]["MAX"]["HTML_VALUE"]?>"
                                                    size="5"
                                                    onkeyup="smartFilter.keyup(this)"
                                            />
                                        </div>
                                    </div>
                                <?
                                break;
                                case SectionPropertyTable::CHECKBOXES_WITH_PICTURES://CHECKBOXES_WITH_PICTURES
                                ?>
                                    <div class="col-xs-12">
                                        <div class="bx-filter-param-btn-inline">
                                            <?foreach ($arItem["VALUES"] as $val => $ar):?>
                                                <input
                                                        style="display: none"
                                                        type="checkbox"
                                                        name="<?=$ar["CONTROL_NAME"]?>"
                                                        id="<?=$ar["CONTROL_ID"]?>"
                                                        value="<?=$ar["HTML_VALUE"]?>"
                                                    <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                />
                                                <?
                                                $class = "";
                                                if ($ar["CHECKED"])
                                                    $class.= " bx-active";
                                                if ($ar["DISABLED"])
                                                    $class.= " disabled";
                                                ?>
                                                <label for="<?=$ar["CONTROL_ID"]?>" data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label <?=$class?>" onclick="smartFilter.keyup(BX('<?=CUtil::JSEscape($ar["CONTROL_ID"])?>')); BX.toggleClass(this, 'bx-active');">
                                                                <span class="bx-filter-param-btn bx-color-sl">
                                                                    <?if (isset($ar["FILE"]) && !empty($ar["FILE"]["SRC"])):?>
                                                                        <span class="bx-filter-btn-color-icon" style="background-image:url('<?=$ar["FILE"]["SRC"]?>');"></span>
                                                                    <?endif?>
                                                                </span>
                                                </label>
                                            <?endforeach?>
                                        </div>
                                    </div>
                                <?
                                break;
                                case SectionPropertyTable::CHECKBOXES_WITH_PICTURES_AND_LABELS://CHECKBOXES_WITH_PICTURES_AND_LABELS
                                ?>
                                    <div class="col-xs-12">
                                        <div class="bx-filter-param-btn-block">
                                            <?foreach ($arItem["VALUES"] as $val => $ar):?>
                                                <input
                                                        style="display: none"
                                                        type="checkbox"
                                                        name="<?=$ar["CONTROL_NAME"]?>"
                                                        id="<?=$ar["CONTROL_ID"]?>"
                                                        value="<?=$ar["HTML_VALUE"]?>"
                                                    <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                />
                                                <?
                                                $class = "";
                                                if ($ar["CHECKED"])
                                                    $class.= " bx-active";
                                                if ($ar["DISABLED"])
                                                    $class.= " disabled";
                                                ?>
                                                <label for="<?=$ar["CONTROL_ID"]?>" data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label<?=$class?>" onclick="smartFilter.keyup(BX('<?=CUtil::JSEscape($ar["CONTROL_ID"])?>')); BX.toggleClass(this, 'bx-active');">
                                                                <span class="bx-filter-param-btn bx-color-sl">
                                                                    <?if (isset($ar["FILE"]) && !empty($ar["FILE"]["SRC"])):?>
                                                                        <span class="bx-filter-btn-color-icon" style="background-image:url('<?=$ar["FILE"]["SRC"]?>');"></span>
                                                                    <?endif?>
                                                                </span>
                                                    <span class="bx-filter-param-text" title="<?=$ar["VALUE"];?>"><?=$ar["VALUE"];?><?
                                                        if ($arParams["DISPLAY_ELEMENT_COUNT"] !== "N" && isset($ar["ELEMENT_COUNT"])):
                                                            ?> (<span data-role="count_<?=$ar["CONTROL_ID"]?>"><? echo $ar["ELEMENT_COUNT"]; ?></span>)<?
                                                        endif;?></span>
                                                </label>
                                            <?endforeach?>
                                        </div>
                                    </div>
                                <?
                                break;
                                case SectionPropertyTable::DROPDOWN://DROPDOWN
                                $checkedItemExist = false;
                                ?>
                                    <div class="col-xs-12">
                                        <div class="bx-filter-select-container">
                                            <div class="bx-filter-select-block" onclick="smartFilter.showDropDownPopup(this, '<?=CUtil::JSEscape($key)?>')">
                                                <div class="bx-filter-select-text" data-role="currentOption">
                                                    <?
                                                    foreach ($arItem["VALUES"] as $val => $ar)
                                                    {
                                                        if ($ar["CHECKED"])
                                                        {
                                                            echo $ar["VALUE"];
                                                            $checkedItemExist = true;
                                                        }
                                                    }
                                                    if (!$checkedItemExist)
                                                    {
                                                        echo GetMessage("CT_BCSF_FILTER_ALL");
                                                    }
                                                    ?>
                                                </div>
                                                <div class="bx-filter-select-arrow"></div>
                                                <input
                                                        style="display: none"
                                                        type="radio"
                                                        name="<?=$arCur["CONTROL_NAME_ALT"]?>"
                                                        id="<? echo "all_".$arCur["CONTROL_ID"] ?>"
                                                        value=""
                                                />
                                                <?foreach ($arItem["VALUES"] as $val => $ar):?>
                                                    <input
                                                            style="display: none"
                                                            type="radio"
                                                            name="<?=$ar["CONTROL_NAME_ALT"]?>"
                                                            id="<?=$ar["CONTROL_ID"]?>"
                                                            value="<? echo $ar["HTML_VALUE_ALT"] ?>"
                                                        <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                    />
                                                <?endforeach?>
                                                <div class="bx-filter-select-popup" data-role="dropdownContent" style="display: none;">
                                                    <ul>
                                                        <li>
                                                            <label for="<?="all_".$arCur["CONTROL_ID"]?>" class="bx-filter-param-label" data-role="label_<?="all_".$arCur["CONTROL_ID"]?>" onclick="smartFilter.selectDropDownItem(this, '<?=CUtil::JSEscape("all_".$arCur["CONTROL_ID"])?>')">
                                                                <? echo GetMessage("CT_BCSF_FILTER_ALL"); ?>
                                                            </label>
                                                        </li>
                                                        <?
                                                        foreach ($arItem["VALUES"] as $val => $ar):
                                                            $class = "";
                                                            if ($ar["CHECKED"])
                                                                $class.= " selected";
                                                            if ($ar["DISABLED"])
                                                                $class.= " disabled";
                                                            ?>
                                                            <li>
                                                                <label for="<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label<?=$class?>" data-role="label_<?=$ar["CONTROL_ID"]?>" onclick="smartFilter.selectDropDownItem(this, '<?=CUtil::JSEscape($ar["CONTROL_ID"])?>')"><?=$ar["VALUE"]?></label>
                                                            </li>
                                                        <?endforeach?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?
                                break;
                                case SectionPropertyTable::DROPDOWN_WITH_PICTURES_AND_LABELS://DROPDOWN_WITH_PICTURES_AND_LABELS
                                ?>
                                    <div class="col-xs-12">
                                        <div class="bx-filter-select-container">
                                            <div class="bx-filter-select-block" onclick="smartFilter.showDropDownPopup(this, '<?=CUtil::JSEscape($key)?>')">
                                                <div class="bx-filter-select-text fix" data-role="currentOption">
                                                    <?
                                                    $checkedItemExist = false;
                                                    foreach ($arItem["VALUES"] as $val => $ar):
                                                        if ($ar["CHECKED"])
                                                        {
                                                            ?>
                                                            <?if (isset($ar["FILE"]) && !empty($ar["FILE"]["SRC"])):?>
                                                            <span class="bx-filter-btn-color-icon" style="background-image:url('<?=$ar["FILE"]["SRC"]?>');"></span>
                                                        <?endif?>
                                                            <span class="bx-filter-param-text">
                                                                                <?=$ar["VALUE"]?>
                                                                            </span>
                                                            <?
                                                            $checkedItemExist = true;
                                                        }
                                                    endforeach;
                                                    if (!$checkedItemExist)
                                                    {
                                                        ?><span class="bx-filter-btn-color-icon all"></span> <?
                                                        echo GetMessage("CT_BCSF_FILTER_ALL");
                                                    }
                                                    ?>
                                                </div>
                                                <div class="bx-filter-select-arrow"></div>
                                                <input
                                                        style="display: none"
                                                        type="radio"
                                                        name="<?=$arCur["CONTROL_NAME_ALT"]?>"
                                                        id="<? echo "all_".$arCur["CONTROL_ID"] ?>"
                                                        value=""
                                                />
                                                <?foreach ($arItem["VALUES"] as $val => $ar):?>
                                                    <input
                                                            style="display: none"
                                                            type="radio"
                                                            name="<?=$ar["CONTROL_NAME_ALT"]?>"
                                                            id="<?=$ar["CONTROL_ID"]?>"
                                                            value="<?=$ar["HTML_VALUE_ALT"]?>"
                                                        <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                    />
                                                <?endforeach?>
                                                <div class="bx-filter-select-popup" data-role="dropdownContent" style="display: none">
                                                    <ul>
                                                        <li style="border-bottom: 1px solid #e5e5e5;padding-bottom: 5px;margin-bottom: 5px;">
                                                            <label for="<?="all_".$arCur["CONTROL_ID"]?>" class="bx-filter-param-label" data-role="label_<?="all_".$arCur["CONTROL_ID"]?>" onclick="smartFilter.selectDropDownItem(this, '<?=CUtil::JSEscape("all_".$arCur["CONTROL_ID"])?>')">
                                                                <span class="bx-filter-btn-color-icon all"></span>
                                                                <? echo GetMessage("CT_BCSF_FILTER_ALL"); ?>
                                                            </label>
                                                        </li>
                                                        <?
                                                        foreach ($arItem["VALUES"] as $val => $ar):
                                                            $class = "";
                                                            if ($ar["CHECKED"])
                                                                $class.= " selected";
                                                            if ($ar["DISABLED"])
                                                                $class.= " disabled";
                                                            ?>
                                                            <li>
                                                                <label for="<?=$ar["CONTROL_ID"]?>" data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label<?=$class?>" onclick="smartFilter.selectDropDownItem(this, '<?=CUtil::JSEscape($ar["CONTROL_ID"])?>')">
                                                                    <?if (isset($ar["FILE"]) && !empty($ar["FILE"]["SRC"])):?>
                                                                        <span class="bx-filter-btn-color-icon" style="background-image:url('<?=$ar["FILE"]["SRC"]?>');"></span>
                                                                    <?endif?>
                                                                    <span class="bx-filter-param-text">
                                                                                    <?=$ar["VALUE"]?>
                                                                                </span>
                                                                </label>
                                                            </li>
                                                        <?endforeach?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?
                                break;
                                case SectionPropertyTable::RADIO_BUTTONS://RADIO_BUTTONS
                                ?>
                                <li style="display:none;" data-role="label_<?=$arCur["CONTROL_ID"]?>" class="bx-filter-param-label" for="<? echo $arCur["CONTROL_ID"] ?>">
                                    <span class="bx-filter-input-checkbox <? echo $arCur["DISABLED"] ? 'disabled': '' ?>">
                                        <input
                                                type="radio"
                                                value=""
                                                name="<? echo $arCur["CONTROL_NAME_ALT"] ?>"
                                                id="<? echo "all_".$arCur["CONTROL_ID"] ?>"
                                                onclick="smartFilter.click(this)"
                                        />
													<span class="bx-filter-param-text"><? echo GetMessage("CT_BCSF_FILTER_ALL"); ?></span>
                                    </span>
                                </li>

                                    <?foreach($arItem["VALUES"] as $val => $ar):
                                        /*if( $ar['DISABLED'] )
                                            continue;*/
                                    ?>
                                        <li style="<?=$ar['DISABLED'] ? 'display:none;' : null;?>" data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label" for="<? echo $ar["CONTROL_ID"] ?>">
                                            <span class="bx-filter-input-checkbox <? echo $ar["DISABLED"] ? 'disabled': '' ?>">
                                                <input
                                                        type="radio"
                                                        value="<? echo $ar["HTML_VALUE_ALT"] ?>"
                                                        name="<? echo $ar["CONTROL_NAME_ALT"] ?>"
                                                        id="<? echo $ar["CONTROL_ID"] ?>"
                                                    <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                    onclick="smartFilter.click(this)"
                                                />
                                                <span class="bx-filter-param-text" title="<?=$ar["VALUE"];?>"><?=$ar["VALUE"];?><?
                                                    if ($arParams["DISPLAY_ELEMENT_COUNT"] !== "N" && isset($ar["ELEMENT_COUNT"])):
                                                        ?>&nbsp;(<span data-role="count_<?=$ar["CONTROL_ID"]?>"><? echo $ar["ELEMENT_COUNT"]; ?></span>)<?
                                                    endif;?></span>
                                            </span>
                                        </li>
                                    <?endforeach;?>
                                <?
                                break;
                                case SectionPropertyTable::CALENDAR://CALENDAR
                                ?>
                                    <div class="col-xs-12">
                                        <div class="bx-filter-parameters-box-container-block"><div class="bx-filter-input-container bx-filter-calendar-container">
                                                <?$APPLICATION->IncludeComponent(
                                                    'bitrix:main.calendar',
                                                    '',
                                                    array(
                                                        'FORM_NAME' => $arResult["FILTER_NAME"]."_form",
                                                        'SHOW_INPUT' => 'Y',
                                                        'INPUT_ADDITIONAL_ATTR' => 'class="calendar" placeholder="'.FormatDate("SHORT", $arItem["VALUES"]["MIN"]["VALUE"]).'" onkeyup="smartFilter.keyup(this)" onchange="smartFilter.keyup(this)"',
                                                        'INPUT_NAME' => $arItem["VALUES"]["MIN"]["CONTROL_NAME"],
                                                        'INPUT_VALUE' => $arItem["VALUES"]["MIN"]["HTML_VALUE"],
                                                        'SHOW_TIME' => 'N',
                                                        'HIDE_TIMEBAR' => 'Y',
                                                    ),
                                                    null,
                                                    array('HIDE_ICONS' => 'Y')
                                                );?>
                                            </div></div>
                                        <div class="bx-filter-parameters-box-container-block"><div class="bx-filter-input-container bx-filter-calendar-container">
                                                <?$APPLICATION->IncludeComponent(
                                                    'bitrix:main.calendar',
                                                    '',
                                                    array(
                                                        'FORM_NAME' => $arResult["FILTER_NAME"]."_form",
                                                        'SHOW_INPUT' => 'Y',
                                                        'INPUT_ADDITIONAL_ATTR' => 'class="calendar" placeholder="'.FormatDate("SHORT", $arItem["VALUES"]["MAX"]["VALUE"]).'" onkeyup="smartFilter.keyup(this)" onchange="smartFilter.keyup(this)"',
                                                        'INPUT_NAME' => $arItem["VALUES"]["MAX"]["CONTROL_NAME"],
                                                        'INPUT_VALUE' => $arItem["VALUES"]["MAX"]["HTML_VALUE"],
                                                        'SHOW_TIME' => 'N',
                                                        'HIDE_TIMEBAR' => 'Y',
                                                    ),
                                                    null,
                                                    array('HIDE_ICONS' => 'Y')
                                                );?>
                                            </div></div>
                                    </div>
                                <?
                                break;
                                default://CHECKBOXES
                            ?>
                                <div class="col-xs-12">
                                    <?foreach($arItem["VALUES"] as $val => $ar):?>
                                        <div class="checkbox">
                                            <label data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label <? echo $ar["DISABLED"] ? 'disabled': '' ?>" for="<? echo $ar["CONTROL_ID"] ?>">
                                                                <span class="bx-filter-input-checkbox">
                                                                    <input
                                                                            type="checkbox"
                                                                            value="<? echo $ar["HTML_VALUE"] ?>"
                                                                            name="<? echo $ar["CONTROL_NAME"] ?>"
                                                                            id="<? echo $ar["CONTROL_ID"] ?>"
                                                                        <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                                        onclick="smartFilter.click(this)"
                                                                    />
                                                                    <span class="bx-filter-param-text" title="<?=$ar["VALUE"];?>"><?=$ar["VALUE"];?><?
                                                                        if ($arParams["DISPLAY_ELEMENT_COUNT"] !== "N" && isset($ar["ELEMENT_COUNT"])):
                                                                            ?>&nbsp;(<span data-role="count_<?=$ar["CONTROL_ID"]?>"><? echo $ar["ELEMENT_COUNT"]; ?></span>)<?
                                                                        endif;?></span>
                                                                </span>
                                            </label>
                                        </div>
                                    <?endforeach;?>
                                </div>
                            <?
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            <?
        }
        ?>

        <input
                class="btn btn-themes"
                type="submit"
                id="set_filter"
                name="set_filter"
                <?=($arParams['INSTANT_RELOAD'] == "Y") ? 'style="display:none;"' : null;?>
                value="<?=GetMessage("CT_BCSF_SET_FILTER")?>"
        />
        <input
                class="btn btn-reset"
                type="submit"
                id="del_filter"
                name="del_filter"
                value="<?=GetMessage("CT_BCSF_DEL_FILTER")?>"
        />
    </div><!--//row-->



    <div class="bx-filter-popup-result eFiltr_form_result bx-filter-container-modef" id="modef" <?if(!isset($arResult["ELEMENT_COUNT"])) echo 'style="display:none"';?> style="display: inline-block;">
        <?echo GetMessage("CT_BCSF_FILTER_COUNT", array("#ELEMENT_COUNT#" => '<span id="modef_num">'.(int)($arResult["ELEMENT_COUNT"] ?? 0).'</span>'));?>
        <span class="arrow"></span>
        <br/>
        <a href="<?echo $arResult["FILTER_URL"]?>" target=""><?echo GetMessage("CT_BCSF_FILTER_SHOW")?></a>
    </div>
</form>
<script>
    var smartFilter = new JCSmartFilter('<?echo CUtil::JSEscape($arResult["FORM_ACTION"])?>', '<?=CUtil::JSEscape($arParams["FILTER_VIEW_MODE"])?>', <?=CUtil::PhpToJSObject($arResult["JS_FILTER_PARAMS"])?>);

    // Переинициализация после AJAX обновления
    BX.addCustomEvent('onAjaxSuccess', function() {
        setTimeout(function() {
            // Переинициализируем ваш external.js
            if (typeof window.reinitFilterJS === 'function') {
                window.reinitFilterJS();
            }
        }, 100);
    });
</script>
<?endif;?>