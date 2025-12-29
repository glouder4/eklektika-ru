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

$this->addExternalJs("/assets/snippets/eFilter/html/js/eFilter.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/evoSortBlock.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/jquery-ui.min.js");
$this->addExternalJs("/assets/snippets/eFilter/html/js/jquery.ui.touch-punch.min.js");

pre($arResult);
?>

<form id="eFiltr" class="eFiltr eFiltr_form filters smartfilter" name="<?echo $arResult["FILTER_NAME"]."_form"?>" action="<?echo $arResult["FORM_ACTION"]?>" method="get">
    <div style="display:none;" id="eFiltr_info"><span id="eFiltr_info_cnt">0</span><span id="eFiltr_info_cnt_ending"></span></div>

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
                <div class="<?if ($arParams["FILTER_VIEW_MODE"] == "HORIZONTAL"):?>col-sm-6 col-md-4<?else:?>col-lg-12<?endif?> bx-filter-parameters-box bx-active">
                    <span class="bx-filter-container-modef"></span>
                    <div class="bx-filter-parameters-box-title" onclick="smartFilter.hideFilterProps(this)"><span><?=$arItem["NAME"]?> <i data-role="prop_angle" class="fa fa-angle-<?if (isset($arItem["DISPLAY_EXPANDED"]) && $arItem["DISPLAY_EXPANDED"] == "Y"):?>up<?else:?>down<?endif?>"></i></span></div>
                    <div class="bx-filter-block" data-role="bx_filter_block">
                        <div class="row bx-filter-parameters-box-container">
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
            <div class="<?if ($arParams["FILTER_VIEW_MODE"] == "HORIZONTAL"):?>col-sm-6 col-md-4<?else:?>col-lg-12<?endif?> bx-filter-parameters-box <?if ($arItem["DISPLAY_EXPANDED"]== "Y"):?>bx-active<?endif?>">
                <span class="bx-filter-container-modef"></span>
                <div class="bx-filter-parameters-box-title" onclick="smartFilter.hideFilterProps(this)">
							<span class="bx-filter-parameters-box-hint"><?=$arItem["NAME"]?>
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
							</span>
                </div>

                <div class="bx-filter-block" data-role="bx_filter_block">
                    <div class="row bx-filter-parameters-box-container">
                        <?
                        $arCur = current($arItem["VALUES"]);
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
                            <div class="col-xs-12">
                                <div class="radio">
                                    <label class="bx-filter-param-label" for="<? echo "all_".$arCur["CONTROL_ID"] ?>">
												<span class="bx-filter-input-checkbox">
													<input
                                                            type="radio"
                                                            value=""
                                                            name="<? echo $arCur["CONTROL_NAME_ALT"] ?>"
                                                            id="<? echo "all_".$arCur["CONTROL_ID"] ?>"
                                                            onclick="smartFilter.click(this)"
                                                    />
													<span class="bx-filter-param-text"><? echo GetMessage("CT_BCSF_FILTER_ALL"); ?></span>
												</span>
                                    </label>
                                </div>
                                <?foreach($arItem["VALUES"] as $val => $ar):?>
                                    <div class="radio">
                                        <label data-role="label_<?=$ar["CONTROL_ID"]?>" class="bx-filter-param-label" for="<? echo $ar["CONTROL_ID"] ?>">
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
                                        </label>
                                    </div>
                                <?endforeach;?>
                            </div>
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
                    </div>
                    <div style="clear: both"></div>
                </div>
            </div>
            <?
        }
        ?>




        <div class="col fltr_block129 ">
            <div class="select-ul">

                <button type="button" class="select-ul-btn   ">
                    Цвет <a rel="nofollow" href=" novii_god_i_rojdestvo/ "></a>
                </button>
                <ul>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-&lt;&gt;-f/" value="color-&lt;&gt;-f">
                            &lt;&gt;</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-circle-f/" value="color-circle-f">
                            CIRCLE</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-square-f/" value="color-square-f">
                            SQUARE</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-triangle-f/" value="color-triangle-f">
                            TRIANGLE</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-bejevii-f/" value="color-bejevii-f">
                            Бежевый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-belii-f/" value="color-belii-f">
                            Белый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-belii-matovii-f/" value="color-belii-matovii-f">
                            Белый матовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-belii-s-zolotistim-f/" value="color-belii-s-zolotistim-f">
                            Белый с золотистым</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-biruzovii-f/" value="color-biruzovii-f">
                            Бирюзовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-bordovii-f/" value="color-bordovii-f">
                            Бордовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-bronzovii-f/" value="color-bronzovii-f">
                            Бронзовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-byrgyndi-f/" value="color-byrgyndi-f">
                            Бургунди</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-vinnii-f/" value="color-vinnii-f">
                            Винный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-golyboi-f/" value="color-golyboi-f">
                            Голубой</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-jyoltii-f/" value="color-jyoltii-f">
                            Жёлтый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-zelyonoe-yabloko-f/" value="color-zelyonoe-yabloko-f">
                            Зелёное-яблоко</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-zelyonii-f/" value="color-zelyonii-f">
                            Зелёный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-zolotistii-f/" value="color-zolotistii-f">
                            Золотистый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-zolotoi-f/" value="color-zolotoi-f">
                            Золотой</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-kombinirovannii-f/" value="color-kombinirovannii-f">
                            Комбинированный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-kombinirovannii-2-f/" value="color-kombinirovannii-2-f">
                            Комбинированный 2</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-korichnevii-f/" value="color-korichnevii-f">
                            Коричневый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-korichnevii--bejevii-f/" value="color-korichnevii--bejevii-f">
                            Коричневый, бежевый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-krasnaya-f/" value="color-krasnaya-f">
                            Красная</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-krasnii-f/" value="color-krasnii-f">
                            Красный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-krasnii-prozrachnii-f/" value="color-krasnii-prozrachnii-f">
                            Красный прозрачный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-madjenta-f/" value="color-madjenta-f">
                            Маджента</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-molochno-belii-f/" value="color-molochno-belii-f">
                            Молочно-белый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-molochnii-f/" value="color-molochnii-f">
                            Молочный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-myltikolor-f/" value="color-myltikolor-f">
                            Мультиколор</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-natyralnii-f/" value="color-natyralnii-f">
                            Натуральный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-oranjevii-f/" value="color-oranjevii-f">
                            Оранжевый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-prozrachnii-f/" value="color-prozrachnii-f">
                            Прозрачный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-prozrachnii--krasnii-f/" value="color-prozrachnii--krasnii-f">
                            Прозрачный, Красный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-prozrachnii--sinii-f/" value="color-prozrachnii--sinii-f">
                            Прозрачный, Синий</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-prozrachnii-krasnii-f/" value="color-prozrachnii-krasnii-f">
                            Прозрачный,Красный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-raznocvetnii-f/" value="color-raznocvetnii-f">
                            Разноцветный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-rozovii-f/" value="color-rozovii-f">
                            Розовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-svetlo-zelyonii-f/" value="color-svetlo-zelyonii-f">
                            Светло-Зелёный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-svetlo-korichnevii-f/" value="color-svetlo-korichnevii-f">
                            Светло-коричневый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-svetlo-serii-f/" value="color-svetlo-serii-f">
                            Светло-серый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serebristii-f/" value="color-serebristii-f">
                            Серебристый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serebro-metallik-f/" value="color-serebro-metallik-f">
                            Серебро металлик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serebryanii-f/" value="color-serebryanii-f">
                            Серебряный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serenevii-f/" value="color-serenevii-f">
                            Сереневый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serii-f/" value="color-serii-f">
                            Серый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-serii-melanj-f/" value="color-serii-melanj-f">
                            Серый меланж</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-sinii-f/" value="color-sinii-f">
                            Синий</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-sinii-prozrachnii-f/" value="color-sinii-prozrachnii-f">
                            Синий прозрачный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-sinim-f/" value="color-sinim-f">
                            Синим</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-tyomno-serii-f/" value="color-tyomno-serii-f">
                            Тёмно-Серый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-tyomno-sinii-f/" value="color-tyomno-sinii-f">
                            Тёмно-Синий</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-fioletovii-f/" value="color-fioletovii-f">
                            Фиолетовый</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-chernii-f/" value="color-chernii-f">
                            Черный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-chernii-metallik-f/" value="color-chernii-metallik-f">
                            Черный металлик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-shokoladnii-f/" value="color-shokoladnii-f">
                            Шоколадный</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/color-yarko-sinii-f/" value="color-yarko-sinii-f">
                            Ярко-синий</a></li>

                </ul>
            </div>
        </div>

        <div class="col fltr_block14 ">
            <div class="select-ul">

                <button type="button" class="select-ul-btn   ">
                    Материал <a rel="nofollow" href=" novii_god_i_rojdestvo/ "></a>
                </button>
                <ul>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-50-sherst--50-akril-f/" value="material-50-sherst--50-akril-f">
                            50% шерсть, 50% акрил</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-abs-plastik-f/" value="material-abs-plastik-f">
                            АБС-пластик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-as-plastik-bez-bfa-f/" value="material-as-plastik-bez-bfa-f">
                            АС пластик без БФА</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-akril-f/" value="material-akril-f">
                            Акрил</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-bambyk-f/" value="material-bambyk-f">
                            Бамбук</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-bereza-f/" value="material-bereza-f">
                            Береза</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-bereza--akril-f/" value="material-bereza--akril-f">
                            Береза, акрил</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-beresta-f/" value="material-beresta-f">
                            Береста</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-bymaga-f/" value="material-bymaga-f">
                            Бумага</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-velur-f/" value="material-velur-f">
                            Велюр</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-vosk-f/" value="material-vosk-f">
                            Воск</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-gips-f/" value="material-gips-f">
                            Гипс</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-girlyanda---pvh--plastik-f/" value="material-girlyanda---pvh--plastik-f">
                            Гирлянда - ПВХ, пластик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-girlyanda---plastik--metall-f/" value="material-girlyanda---plastik--metall-f">
                            Гирлянда - пластик, металл</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-derevo-f/" value="material-derevo-f">
                            Дерево</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-djyt-f/" value="material-djyt-f">
                            Джут</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-ejednevnik---iskysstvennaya-ko-f/" value="material-ejednevnik---iskysstvennaya-ko-f">
                            Ежедневник - искусственная ко</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-iskysstvennaya-koja-f/" value="material-iskysstvennaya-koja-f">
                            Искусственная кожа</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-karandash---derevo-f/" value="material-karandash---derevo-f">
                            Карандаш - дерево</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-karton-f/" value="material-karton-f">
                            Картон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-kaychyk-f/" value="material-kaychyk-f">
                            Каучук</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-keramika-f/" value="material-keramika-f">
                            Керамика</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-kryjka---kostyanoi-farfor-f/" value="material-kryjka---kostyanoi-farfor-f">
                            Кружка - костяной фарфор</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-len-f/" value="material-len-f">
                            Лен</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-mdf-v-plenke-f/" value="material-mdf-v-plenke-f">
                            МДФ в пленке</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-med--pvh--svinec-f/" value="material-med--pvh--svinec-f">
                            Медь, ПВХ, свинец</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-med--pvh--smola-f/" value="material-med--pvh--smola-f">
                            Медь, ПВХ, смола</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-metall-f/" value="material-metall-f">
                            Металл</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-metallicheskoe-volokno-f/" value="material-metallicheskoe-volokno-f">
                            Металлическое волокно</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-mikrogofrokarton-f/" value="material-mikrogofrokarton-f">
                            Микрогофрокартон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-natyralnaya-koja-f/" value="material-natyralnaya-koja-f">
                            Натуральная кожа</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-neilon-f/" value="material-neilon-f">
                            Нейлон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-nerjaveushaya-stal-f/" value="material-nerjaveushaya-stal-f">
                            Нержавеюшая сталь</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-pvh-f/" value="material-pvh-f">
                            ПВХ</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-pp-plastik-f/" value="material-pp-plastik-f">
                            ПП пластик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-palochki---derevo-f/" value="material-palochki---derevo-f">
                            Палочки - дерево</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-parafin-f/" value="material-parafin-f">
                            Парафин</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-perepletnii-karton-f/" value="material-perepletnii-karton-f">
                            Переплетный картон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-plastik-f/" value="material-plastik-f">
                            Пластик</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-pled---poliester-100-f/" value="material-pled---poliester-100-f">
                            Плед - полиэстер 100%</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-pokritie-soft-touch-f/" value="material-pokritie-soft-touch-f">
                            Покрытие SOFT-TOUCH</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-poliamid-f/" value="material-poliamid-f">
                            Полиамид</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-polistirol-f/" value="material-polistirol-f">
                            Полистирол</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-poliyretan-f/" value="material-poliyretan-f">
                            Полиуретан</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-poliester-f/" value="material-poliester-f">
                            Полиэстер</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-poliester.-f/" value="material-poliester.-f">
                            Полиэстер.</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-polietilen-f/" value="material-polietilen-f">
                            Полиэтилен</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-polysherst-f/" value="material-polysherst-f">
                            Полушерсть</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-probka-f/" value="material-probka-f">
                            Пробка</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-probkovoe-derevo-f/" value="material-probkovoe-derevo-f">
                            Пробковое дерево</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-svecha---parafin-f/" value="material-svecha---parafin-f">
                            Свеча - парафин</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-svechi---parafin--steklo-f/" value="material-svechi---parafin--steklo-f">
                            Свечи - парафин, стекло</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-silikon-f/" value="material-silikon-f">
                            Силикон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-sketchbyk---iskysstvennaya-koja-f/" value="material-sketchbyk---iskysstvennaya-koja-f">
                            Скетчбук - искусственная кожа</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-smola-f/" value="material-smola-f">
                            Смола</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-spandeks-f/" value="material-spandeks-f">
                            Спандекс</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-stal-f/" value="material-stal-f">
                            Сталь</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-steklo-f/" value="material-steklo-f">
                            Стекло</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-steklo-lampochka--abs-plastik-osnovanie-f/" value="material-steklo-lampochka--abs-plastik-osnovanie-f">
                            Стекло (лампочка), ABS-пластик (основание)</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-steklo--metall-f/" value="material-steklo--metall-f">
                            Стекло, металл</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-tkan-f/" value="material-tkan-f">
                            Ткань</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-tkan-sinteticheskaya-f/" value="material-tkan-sinteticheskaya-f">
                            Ткань синтетическая</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-fanera-f/" value="material-fanera-f">
                            Фанера</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-farfor-f/" value="material-farfor-f">
                            Фарфор</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-fayans-f/" value="material-fayans-f">
                            Фаянс</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-fetr-f/" value="material-fetr-f">
                            Фетр</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-flakon---steklo-f/" value="material-flakon---steklo-f">
                            Флакон - стекло</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-flis-f/" value="material-flis-f">
                            Флис</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-hlopok-f/" value="material-hlopok-f">
                            Хлопок</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-sharf-f/" value="material-sharf-f">
                            Шарф</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-sherst-f/" value="material-sherst-f">
                            Шерсть</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/material-shpon-dereva-f/" value="material-shpon-dereva-f">
                            Шпон дерева</a></li>

                </ul>
            </div>
        </div>
        <div class="col fltr_block21 ">
            <div class="select-ul">

                <button type="button" class="select-ul-btn   ">
                    Метод нанесения  <a rel="nofollow" href=" novii_god_i_rojdestvo/ "></a>
                </button>
                <ul>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-vishivka-f/" value="mn-vishivka-f">
                            Вышивка</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-dtf-f/" value="mn-dtf-f">
                            ДТФ</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-dekolirovanie-f/" value="mn-dekolirovanie-f">
                            Деколирование</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-zalivka-polimernoi-smoloi-f/" value="mn-zalivka-polimernoi-smoloi-f">
                            Заливка полимерной смолой</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-lazernaya-gravirovka-f/" value="mn-lazernaya-gravirovka-f">
                            Лазерная гравировка</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-pryamaya-cifrovaya-pechat-f/" value="mn-pryamaya-cifrovaya-pechat-f">
                            Прямая цифровая печать</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-syblimacionnaya-pechat-f/" value="mn-syblimacionnaya-pechat-f">
                            Сублимационная печать</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-tampopechat-f/" value="mn-tampopechat-f">
                            Тампопечать</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-termotransfer-f/" value="mn-termotransfer-f">
                            Термотрансфер</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-tisnenie-f/" value="mn-tisnenie-f">
                            Тиснение</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-yf-dtf-f/" value="mn-yf-dtf-f">
                            УФ-ДТФ</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-yf-pechat-f/" value="mn-yf-pechat-f">
                            УФ-печать</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-fleksografiya-f/" value="mn-fleksografiya-f">
                            Флексография</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-shevron-f/" value="mn-shevron-f">
                            Шеврон</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-shelkografiya-f/" value="mn-shelkografiya-f">
                            Шелкография</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/mn-shildi-i-nakleiki-f/" value="mn-shildi-i-nakleiki-f">
                            Шильды и наклейки</a></li>

                </ul>
            </div>
        </div>

        <div class="col fltr_block77 ">
            <div class="select-ul">

                <button type="button" class="select-ul-btn   ">
                    Бренд <a rel="nofollow" href=" novii_god_i_rojdestvo/ "></a>
                </button>
                <ul>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/brend-happy-gifts-extra-f/" value="brend-happy-gifts-extra-f">
                            Happy Gifts Extra</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/brend-pulltex-f/" value="brend-pulltex-f">
                            Pulltex</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/brend-teplo-f/" value="brend-teplo-f">
                            Teplo</a></li>

                    <li><a rel="nofollow" href="novii_god_i_rojdestvo/brend-yoliba-f/" value="brend-yoliba-f">
                            Yoliba</a></li>

                </ul>
            </div>
        </div>


        <div class="col select-drop side-filter">
            <div class="select-drop_title">Остаток</div>

            <div class=" select-drop_list side-trigger__content" style="display: none;">
                <div class="side-filter__slider">

                    <div class="side-filter__slider-inputs">
                        <label>
                            <span class="side-filter__dash">от</span>
                            <input class="side-filter__input-lower input-number" type="text" id="minCostInp11" value="" data-min-val="1"><br>
                            <input style="display:none" class="side-filter__input-upper input-number" type="text" id="maxCostInp11" value="" data-max-val="21082">

                        </label>
                    </div>
                </div>
            </div>

        </div>

        <script type="text/javascript">
            $(document).ready(function(){
                var kolvo = "";
                console.log(kolvo)
                //	    $("#minCostInp11").data("min-val",kolvo);
                $(document).on("change", "#minCostInp11", function(){
                    var val = $(this).val();
                    if(parseInt($(this).val()) > parseInt($("#maxCostInp11").data("max-val"))){

                        $(this).val($("#maxCostInp11").data("max-val"))
                        val = $("#maxCostInp11").data("max-val");
                    }
                    var url_string = window.location.href; //window.location.href
                    var url = new URL(url_string);
                    var url2 = url;

                    url2.searchParams.delete("kolvo");
                    url2.searchParams.append("kolvo",val);
                    window.location.href = url2;

                    //            $("form#eFiltr").BForms("onsubmit");
                });
            });
        </script>

        <div class="col select-drop side-filter" id="price_filter_block">
            <div class="select-drop_title">Цена</div>

            <div class=" select-drop_list side-trigger__content" style="display: none;">
                <div class="side-filter__slider">

                    <div class="side-filter__slider-inputs">
                        <label>
                            <span class="side-filter__dash">от</span>
                            <input class="side-filter__input-lower input-number" type="text" id="minCostInp8" value="" data-min-val="29"><br>
                        </label>
                        <label>
                            <span class="side-filter__dash">до</span>
                            <input class="side-filter__input-upper input-number" type="text" id="maxCostInp8" value="" data-max-val="26400">
                        </label>
                        <input type="text" id="resultsPrice8" name="f8" value="minmax~," style="display:none">
                    </div>
                    <div id="slider8" style="margin-top:15px; display:none;" class="ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all"><div class="ui-slider-range ui-widget-header ui-corner-all" style="left: 0%; width: 100%;"></div><span class="ui-slider-handle ui-state-default ui-corner-all" tabindex="0" style="left: 0%;"></span><span class="ui-slider-handle ui-state-default ui-corner-all" tabindex="0" style="left: 100%;"></span></div>
                </div>
            </div>

        </div>




        <script type="text/javascript">
            $(document).ready(function(){
                var change_price = false;
                $(document).on("change", "#minCostInp8", function(){
                    if($(this).val() > $(this).data("min-val")){
                        $("#slider8").slider("values", 0, $(this).val());
                        $("#minCost8").text(minCostCurr);
                        $("#maxCost8").text(maxCostCurr);

                        $("#resultsPrice8").val("minmax~" +  $("#slider8").slider("values",0) + "," + $("#slider8").slider("values",1));
                        change_price=true;
                        //				$("form#eFiltr").BForms("onsubmit");
                        //				$("form#eFiltr").css("pointer-events","none");
                    }
                    else{
                        $(this).val($(this).data("min-val"))
                    }
                });

                $(document).on("change", "#maxCostInp8", function(){
                    if($(this).val() < $(this).data("max-val")){
                        $("#slider8").slider("values", 1, $(this).val());
                        $("#minCost8").text($("#slider8").slider("values",0));
                        $("#maxCost8").text($("#slider8").slider("values",1));

                        $("#resultsPrice8").val("minmax~" +  $("#slider8").slider("values",0) + "," + $("#slider8").slider("values",1));
                        change_price=true;
                        //				$("form#eFiltr").BForms("onsubmit");
                        //				$("form#eFiltr").css("pointer-events","none");
                    }
                    else{
                        $(this).val($(this).data("max-val"))
                    }
                });


                $(document).on("keyup", "#maxCostInp8", function(e){
                    var enterKey = 13;
                    if (e.which == enterKey){
                        $("form#eFiltr").BForms("onsubmit");
                        $("form#eFiltr").css("pointer-events","none");
                    }
                });
                $(document).on("keyup", "#minCostInp8", function(e){
                    var enterKey = 13;
                    if (e.which == enterKey){
                        $("form#eFiltr").BForms("onsubmit");
                        $("form#eFiltr").css("pointer-events","none");
                    }
                });

                $(document).mouseup(function (e){
                    //		    console.log($("#minCostInp8").val())
                    //		    console.log($("#minCostInp8").data("min-val"))
                    //		    console.log($("#maxCostInp8").val())
                    //		    console.log($("#maxCostInp8").data("max-val"))

                    var div = $("#price_filter_block");
                    if (!div.is(e.target)
                        && div.has(e.target).length === 0) {

                        //               if($("#maxCostInp8").val()!=$("#maxCostInp8").data("max-val")
                        //               || $("#minCostInp8").val()!=$("#minCostInp8").data("min-val") )

                        if(change_price) {
                            //console.log("123123");
                            $("form#eFiltr").BForms("onsubmit");
                            $("form#eFiltr").css("pointer-events","none");
                        }
                    }

                });


                var minCost = 0;
                var maxCost = 0;
                var minCostCurr = 0;
                var maxCostCurr = 0;
                if ($("#minCostInp8").val() != "") {
                    minCostCurr = $("#minCostInp8").val();
                } else {
                    minCostCurr = $("#minCostInp8").data("minVal");
                }
                if ($("#maxCostInp8").val() != "") {
                    maxCostCurr = $("#maxCostInp8").val();
                } else {
                    maxCostCurr = $("#maxCostInp8").data("maxVal");
                }
                minCost = $("#minCostInp8").data("minVal");
                maxCost = $("#maxCostInp8").data("maxVal");

                $("#minCostInp8").val(minCostCurr);
                $("#maxCostInp8").val(maxCostCurr);

                $("#slider8").slider({
                    min: minCost,
                    max: maxCost,
                    values: [minCostCurr,maxCostCurr],

                    range: true,
                    stop: function(event, ui) {
                        $("input#minCostInp8").val($("#slider8").slider("values",0));
                        $("input#maxCostInp8").val($("#slider8").slider("values",1));
                        $("#minCost8").text($("#slider8").slider("values",0));
                        $("#maxCost8").text($("#slider8").slider("values",1));

                        $("#resultsPrice8").val("minmax~" +  $("#slider8").slider("values",0) + "," + $("#slider8").slider("values",1));
                        $("input#minCostInp8").change();
                        //				$("form#eFiltr").BForms("onsubmit");
                    },
                    slide: function(event, ui){
                        $("input#minCostInp8").val($("#slider8").slider("values",0));
                        $("input#maxCostInp8").val($("#slider8").slider("values",1));
                        $("#minCost8").text(jQuery("#slider8").slider("values",0));
                        $("#maxCost8").text(jQuery("#slider8").slider("values",1));

                        $("#resultsPrice8").val( "minmax~" +  $("#slider8").slider("values",0) + "," + $("#slider8").slider("values",1));
                    }
                });
            });
        </script>




        <input
                class="btn btn-themes"
                type="submit"
                id="set_filter"
                name="set_filter"
                value="<?=GetMessage("CT_BCSF_SET_FILTER")?>"
        />
        <input
                class="btn btn-reset"
                type="submit"
                id="del_filter"
                name="del_filter"
                value="<?=GetMessage("CT_BCSF_DEL_FILTER")?>"
                disabled
        />
        <div class="bx-filter-popup-result <?if ($arParams["FILTER_VIEW_MODE"] == "VERTICAL") echo $arParams["POPUP_POSITION"]?>" id="modef" <?if(!isset($arResult["ELEMENT_COUNT"])) echo 'style="display:none"';?> style="display: inline-block;">
            <?echo GetMessage("CT_BCSF_FILTER_COUNT", array("#ELEMENT_COUNT#" => '<span id="modef_num">'.(int)($arResult["ELEMENT_COUNT"] ?? 0).'</span>'));?>
            <span class="arrow"></span>
            <br/>
            <a href="<?echo $arResult["FILTER_URL"]?>" target=""><?echo GetMessage("CT_BCSF_FILTER_SHOW")?></a>
        </div>
    </div>
    <div class="eFiltr_form_result" style="display:none;">Найдено: 0 </div>
</form>
<script>
    var smartFilter = new JCSmartFilter('<?echo CUtil::JSEscape($arResult["FORM_ACTION"])?>', '<?=CUtil::JSEscape($arParams["FILTER_VIEW_MODE"])?>', <?=CUtil::PhpToJSObject($arResult["JS_FILTER_PARAMS"])?>);
</script>