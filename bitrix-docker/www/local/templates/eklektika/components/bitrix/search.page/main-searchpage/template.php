<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
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
?>
<div class="search-page">
    <form action="" method="get">
        <?php if ($arParams['USE_SUGGEST'] === 'Y'):
            if (mb_strlen($arResult['REQUEST']['~QUERY']) && is_object($arResult['NAV_RESULT']))
            {
                $arResult['FILTER_MD5'] = $arResult['NAV_RESULT']->GetFilterMD5();
                $obSearchSuggest = new CSearchSuggest($arResult['FILTER_MD5'], $arResult['REQUEST']['~QUERY']);
                $obSearchSuggest->SetResultCount($arResult['NAV_RESULT']->NavRecordCount);
            }
            ?>
            <?php $APPLICATION->IncludeComponent(
            'bitrix:search.suggest.input',
            '',
            [
                'NAME' => 'q',
                'VALUE' => $arResult['REQUEST']['~QUERY'],
                'INPUT_SIZE' => 40,
                'DROPDOWN_SIZE' => 10,
                'FILTER_MD5' => $arResult['FILTER_MD5'],
            ],
            $component, ['HIDE_ICONS' => 'Y']
        );?>
        <?php else:?>
            <input type="text" name="q" value="<?=$arResult['REQUEST']['QUERY']?>" size="40" />
        <?php endif;?>
        <?php if ($arParams['SHOW_WHERE']):?>
            &nbsp;<select name="where">
                <option value=""><?=GetMessage('SEARCH_ALL')?></option>
                <?php foreach ($arResult['DROPDOWN'] as $key => $value):?>
                    <option value="<?=$key?>"<?php echo ($arResult['REQUEST']['WHERE'] == $key) ? ' selected' : '';?>><?=$value?></option>
                <?php endforeach?>
            </select>
        <?php endif;?>
        &nbsp;<input type="submit" value="<?=GetMessage('SEARCH_GO')?>" />
        <input type="hidden" name="how" value="<?php echo $arResult['REQUEST']['HOW'] == 'd' ? 'd' : 'r'?>" />
        <?php if ($arParams['SHOW_WHEN']):?>
            <script>
                var switch_search_params = function()
                {
                    var sp = document.getElementById('search_params');
                    var flag;
                    var i;

                    if(sp.style.display == 'none')
                    {
                        flag = false;
                        sp.style.display = 'block'
                    }
                    else
                    {
                        flag = true;
                        sp.style.display = 'none';
                    }

                    var from = document.getElementsByName('from');
                    for(i = 0; i < from.length; i++)
                        if(from[i].type.toLowerCase() == 'text')
                            from[i].disabled = flag;

                    var to = document.getElementsByName('to');
                    for(i = 0; i < to.length; i++)
                        if(to[i].type.toLowerCase() == 'text')
                            to[i].disabled = flag;

                    return false;
                }
            </script>
            <br /><a class="search-page-params" href="#" onclick="return switch_search_params()"><?php echo GetMessage('CT_BSP_ADDITIONAL_PARAMS')?></a>
            <div id="search_params" class="search-page-params" style="display:<?php echo $arResult['REQUEST']['FROM'] || $arResult['REQUEST']['TO'] ? 'block' : 'none'?>">
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:main.calendar',
                    '',
                    [
                        'SHOW_INPUT' => 'Y',
                        'INPUT_NAME' => 'from',
                        'INPUT_VALUE' => $arResult['REQUEST']['~FROM'],
                        'INPUT_NAME_FINISH' => 'to',
                        'INPUT_VALUE_FINISH' => $arResult['REQUEST']['~TO'],
                        'INPUT_ADDITIONAL_ATTR' => 'size="10"',
                    ],
                    null,
                    ['HIDE_ICONS' => 'Y']
                );?>
            </div>
        <?php endif?>
    </form><br />

    <?php if (isset($arResult['REQUEST']['ORIGINAL_QUERY'])):
        ?>
        <div class="search-language-guess">
            <?php echo GetMessage('CT_BSP_KEYBOARD_WARNING', ['#query#' => '<a href="' . $arResult['ORIGINAL_QUERY_URL'] . '">' . $arResult['REQUEST']['ORIGINAL_QUERY'] . '</a>'])?>
        </div><br /><?php
    endif;?>

    <?php
    // Добавляем смартфильтр для каталога на странице поиска
    if (CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog")):
        // Параметры каталога
        $catalogIblockType = "catalog";
        $catalogIblockId = 13;
        $filterName = "arrFilter";

        // Базовые параметры для смартфильтра
        $priceCode = array("BASE");
        $cacheType = "A";
        $cacheTime = 3600;
        $cacheGroups = "Y";

        // Обрабатываем параметры фильтра из URL (аналогично каталогу)
        if (isset($_GET) && is_array($_GET))
        {
            $filterArray = array();
            $hasFilterParams = false;

            // Обрабатываем стандартные параметры фильтра
            foreach ($_GET as $key => $value)
            {
                if (strpos($key, $filterName) === 0)
                {
                    $filterArray[$key] = $value;
                    $hasFilterParams = true;
                }
            }

            // Обрабатываем фильтр по остаткам
            $stockFilterName = $filterName . "_stock";
            if (isset($_GET[$stockFilterName]) && $_GET[$stockFilterName] !== '')
            {
                $stockValue = intval($_GET[$stockFilterName]);
                if ($stockValue > 0)
                {
                    $filterArray[">=CATALOG_QUANTITY"] = $stockValue;
                    $hasFilterParams = true;
                }
            }

            // Обрабатываем фильтр по цене (формат: minmax~min,max)
            if (isset($_GET['f8']) && $_GET['f8'] !== '')
            {
                $priceFilter = $_GET['f8'];
                if (preg_match('/^minmax~(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/', $priceFilter, $matches))
                {
                    $minPrice = floatval($matches[1]);
                    $maxPrice = floatval($matches[2]);

                    // Получаем ID типа цены
                    $priceTypeId = null;
                    if (is_numeric($priceCode[0]))
                    {
                        $priceTypeId = intval($priceCode[0]);
                    }
                    else
                    {
                        $dbPriceType = CCatalogGroup::GetList(array(), array("NAME" => $priceCode[0]));
                        if ($arPriceType = $dbPriceType->Fetch())
                        {
                            $priceTypeId = $arPriceType["ID"];
                        }
                    }

                    if ($priceTypeId)
                    {
                        $filterArray[">=CATALOG_PRICE_" . $priceTypeId] = $minPrice;
                        $filterArray["<=CATALOG_PRICE_" . $priceTypeId] = $maxPrice;
                        $hasFilterParams = true;
                    }
                }
            }

            // Если есть параметры фильтра, но нет set_filter=y, добавляем его
            if ($hasFilterParams && !isset($_GET['set_filter']))
            {
                $_GET['set_filter'] = 'y';
                $_REQUEST['set_filter'] = 'y';
            }

            // Создаем глобальную переменную с именем фильтра
            if (!empty($filterArray))
            {
                $GLOBALS[$filterName] = $filterArray;
            }
        }

        // Показываем смартфильтр только если есть результаты поиска или есть запрос
        if ($arResult['REQUEST']['QUERY'] !== false || !empty($_GET['set_filter'])):
            ?>
            <div class="search-smart-filter">
                <?php $APPLICATION->IncludeComponent(
                    "bitrix:catalog.smart.filter",
                    "search-smartfilter-template",
                    array(
                        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
                        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
                        "FILTER_NAME" => $arParams["FILTER_NAME"],
                        "PRICE_CODE" => $arParams["~PRICE_CODE"],
                        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                        "CACHE_TIME" => $arParams["CACHE_TIME"],
                        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                        "SAVE_IN_SESSION" => "N",
                        "FILTER_VIEW_MODE" => $arParams["FILTER_VIEW_MODE"],
                        "XML_EXPORT" => "N",
                        "SECTION_TITLE" => "NAME",
                        "SECTION_DESCRIPTION" => "DESCRIPTION",
                        'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                        "TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
                        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
                        'CURRENCY_ID' => $arParams['CURRENCY_ID'],
                        "SEF_MODE" => $arParams['SEF_MODE'], // Отключаем SEF для фильтра, чтобы работали GET параметры
                        "SEF_RULE" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["smart_filter"],
                        "SMART_FILTER_PATH" => $arResult["VARIABLES"]["SMART_FILTER_PATH"],
                        "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
                        "INSTANT_RELOAD" => $arParams["INSTANT_RELOAD"],
                    ),
                    $component,
                    array('HIDE_ICONS' => 'Y')
                );?>
            </div>
            <br />
        <?php
        endif;
    endif;
    ?>

    <?php if ($arResult['REQUEST']['QUERY'] === false && $arResult['REQUEST']['TAGS'] === false):?>
    <?php elseif ($arResult['ERROR_CODE'] != 0):?>
        <p><?=GetMessage('SEARCH_ERROR')?></p>
        <?php ShowError($arResult['ERROR_TEXT']);?>
        <p><?=GetMessage('SEARCH_CORRECT_AND_CONTINUE')?></p>
        <br /><br />
        <p><?=GetMessage('SEARCH_SINTAX')?><br /><b><?=GetMessage('SEARCH_LOGIC')?></b></p>
        <table border="0" cellpadding="5">
            <tr>
                <td align="center" valign="top"><?=GetMessage('SEARCH_OPERATOR')?></td><td valign="top"><?=GetMessage('SEARCH_SYNONIM')?></td>
                <td><?=GetMessage('SEARCH_DESCRIPTION')?></td>
            </tr>
            <tr>
                <td align="center" valign="top"><?=GetMessage('SEARCH_AND')?></td><td valign="top">and, &amp;, +</td>
                <td><?=GetMessage('SEARCH_AND_ALT')?></td>
            </tr>
            <tr>
                <td align="center" valign="top"><?=GetMessage('SEARCH_OR')?></td><td valign="top">or, |</td>
                <td><?=GetMessage('SEARCH_OR_ALT')?></td>
            </tr>
            <tr>
                <td align="center" valign="top"><?=GetMessage('SEARCH_NOT')?></td><td valign="top">not, ~</td>
                <td><?=GetMessage('SEARCH_NOT_ALT')?></td>
            </tr>
            <tr>
                <td align="center" valign="top">( )</td>
                <td valign="top">&nbsp;</td>
                <td><?=GetMessage('SEARCH_BRACKETS_ALT')?></td>
            </tr>
        </table>
    <?php
    elseif (count($arResult['SEARCH']) > 0):?>
        <div class="row product-list" id="container-with-small-cards">

            <?php foreach ($arResult['SEARCH'] as $arItem):?>
                <div class="col-sm-6 col-lg-4 col-xl1-3 " style="min-height: 619px;">
                    <div itemscope="" itemtype="http://schema.org/Product" class="product-item is-action" style="min-height: 619px;">
                        <div class="label label-action">НОВИНКА</div>
                        <div class="product-item_images">
                            <div class="product-item_img">
                                <a class="changed-url" href="<?php echo $arItem['URL']?>"> <img itemprop="image" data-src="foto-tovara2/1/3/0/1300266_1.jpg" src="foto-tovara2/1/3/0/1300266_1.jpg" class="lazy-loaded"> </a>
                            </div>
                        </div>
                        <div class="infos" data-cacheid="analogs30d9dcdd-f50f-4cfa-b81f-0e822be3c75b1300266">
                            <div class="info-in-card  loaded " data-id="0"> <a href="<?php echo $arItem['URL']?>" class="product-item_title" style="height: 84px;"><span itemprop="name"><?php echo $arItem['TITLE_FORMATED']?></span></a>
                                <div itemprop="description" class="product-item_fields" style="height: 158px;">
                                    <table>
                                        <tbody>
                                        <tr class="tr-price">
                                            <td>Цена</td>
                                            <td>
                                                <div class="price-big "><span itemprop="offers" itemscope="" itemtype="http://schema.org/Offer"><span itemprop="price"> 360.<sub>00</sub>
                            </span><span itemprop="priceCurrency" style="font-size: 14px;" content="RUB">р.</span></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Артикул:</td>
                                            <td>1300266</td>
                                        </tr>
                                        <tr>
                                            <td>В наличии:</td>
                                            <td>515 шт.</td>
                                        </tr>
                                        <tr>
                                            <td>Материал:</td>
                                            <td>МГК (микрогофрокартон) 1 мм</td>
                                        </tr>
                                        <tr>
                                            <td>Цвет:</td>
                                            <td>Коричневый</td>
                                        </tr>
                                        <tr>
                                            <td>Бренд:</td>
                                            <td>Yoliba</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="product-item_buttons">
                                    <div class="button-cart">
                                        <button class="ubtn blue-border-ubtn btn-to-cart-small" type="submit"> Заказать </button>
                                        <form method="post" class="count-block product-item_tooltip">
                                            <div class="quantity-title"> Укажите необходимый тираж <span>(cвободно на складе)</span> </div>
                                            <div class="pit-fields quantity-block evoShop_shelfItem">
                                                <div style="display:none;">
                                                    <span class="item_url"><?php echo $arItem['URL']?></span>
                                                    <span class="item_image">foto-tovara2/1/3/0/1300266_1.jpg</span>
                                                    <span class="item_name"><?php echo $arItem['TITLE_FORMATED']?></span>
                                                    <span class="item_price">360</span>
                                                    <span class="item_pricedefault">360</span>
                                                    <span class="item_pricera">305</span>
                                                    <span class="item_artikul">1300266</span>
                                                    <span class="item_inventory">515</span>
                                                    <span class="item_diffprices"></span>
                                                    <span class="item_priceconst">360</span>
                                                    <span class="inventory-kolvo">515</span>
                                                    <span class="item_ves"> 95</span>
                                                    <span class="item_obem"> </span>
                                                    <input style="" type="button" class="item_add item-add-btn" value="Положить в корзину"> </div>
                                                <input type="text" name="count" placeholder="515" class="item_quantity input-number input-count" required=""> </div>
                                            <hr>
                                            <div class="pit-btn ">
                                                <button type="submit" class="global-add btn btn-cart btn-gray btn-round" itemtype="http://schema.org/BuyAction" disabled=""> Отложить </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach;?>
            <?php echo ($arParams['DISPLAY_BOTTOM_PAGER'] != 'N') ? $arResult['NAV_STRING'] : '';?>
        </div>
    <?php else:?>
        <?php ShowNote(GetMessage('SEARCH_NOTHING_TO_FOUND'));?>
    <?php endif;?>
</div>