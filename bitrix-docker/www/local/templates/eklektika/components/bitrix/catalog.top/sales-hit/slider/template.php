<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Catalog\ProductTable;

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
/** @var array $skuTemplate */
/** @var array $templateData */
$this->setFrameMode(true);

if (!empty($arResult['ITEMS']))
{
	$arResult['SKU_PROPS'] = reset($arResult['SKU_PROPS']);
	$skuTemplate = array();
	if (!empty($arResult['SKU_PROPS']))
	{
		foreach ($arResult['SKU_PROPS'] as $arProp)
		{
			$propId = $arProp['ID'];
			$skuTemplate[$propId] = array(
				'SCROLL' => array(
					'START' => '',
					'FINISH' => '',
				),
				'FULL' => array(
					'START' => '',
					'FINISH' => '',
				),
				'ITEMS' => array()
			);
			$templateRow = '';
			if ('TEXT' == $arProp['SHOW_MODE'])
			{
				$skuTemplate[$propId]['SCROLL']['START'] = '<div class="bx_item_detail_size full" id="#ITEM#_prop_'.$propId.'_cont">'.
					'<span class="bx_item_section_name_gray">'.htmlspecialcharsbx($arProp['NAME']).'</span>'.
					'<div class="bx_size_scroller_container"><div class="bx_size"><ul id="#ITEM#_prop_'.$propId.'_list" style="width: #WIDTH#;">';;
				$skuTemplate[$propId]['SCROLL']['FINISH'] = '</ul></div>'.
					'<div class="bx_slide_left" id="#ITEM#_prop_'.$propId.'_left" data-treevalue="'.$propId.'" style=""></div>'.
					'<div class="bx_slide_right" id="#ITEM#_prop_'.$propId.'_right" data-treevalue="'.$propId.'" style=""></div>'.
					'</div></div>';

				$skuTemplate[$propId]['FULL']['START'] = '<div class="bx_item_detail_size" id="#ITEM#_prop_'.$propId.'_cont">'.
					'<span class="bx_item_section_name_gray">'.htmlspecialcharsbx($arProp['NAME']).'</span>'.
					'<div class="bx_size_scroller_container"><div class="bx_size"><ul id="#ITEM#_prop_'.$propId.'_list" style="width: #WIDTH#;">';;
				$skuTemplate[$propId]['FULL']['FINISH'] = '</ul></div>'.
					'<div class="bx_slide_left" id="#ITEM#_prop_'.$propId.'_left" data-treevalue="'.$propId.'" style="display: none;"></div>'.
					'<div class="bx_slide_right" id="#ITEM#_prop_'.$propId.'_right" data-treevalue="'.$propId.'" style="display: none;"></div>'.
					'</div></div>';
				foreach ($arProp['VALUES'] as $value)
				{
					$value['NAME'] = htmlspecialcharsbx($value['NAME']);
					$skuTemplate[$propId]['ITEMS'][$value['ID']] = '<li data-treevalue="'.$propId.'_'.$value['ID'].
						'" data-onevalue="'.$value['ID'].'" style="width: #WIDTH#;" title="'.$value['NAME'].'"><i></i><span class="cnt">'.$value['NAME'].'</span></li>';
				}
				unset($value);
			}
			elseif ('PICT' == $arProp['SHOW_MODE'])
			{
				$skuTemplate[$propId]['SCROLL']['START'] = '<div class="bx_item_detail_scu full" id="#ITEM#_prop_'.$propId.'_cont">'.
					'<span class="bx_item_section_name_gray">'.htmlspecialcharsbx($arProp['NAME']).'</span>'.
					'<div class="bx_scu_scroller_container"><div class="bx_scu"><ul id="#ITEM#_prop_'.$propId.'_list" style="width: #WIDTH#;">';
				$skuTemplate[$propId]['SCROLL']['FINISH'] = '</ul></div>'.
					'<div class="bx_slide_left" id="#ITEM#_prop_'.$propId.'_left" data-treevalue="'.$propId.'" style=""></div>'.
					'<div class="bx_slide_right" id="#ITEM#_prop_'.$propId.'_right" data-treevalue="'.$propId.'" style=""></div>'.
					'</div></div>';

				$skuTemplate[$propId]['FULL']['START'] = '<div class="bx_item_detail_scu" id="#ITEM#_prop_'.$propId.'_cont">'.
					'<span class="bx_item_section_name_gray">'.htmlspecialcharsbx($arProp['NAME']).'</span>'.
					'<div class="bx_scu_scroller_container"><div class="bx_scu"><ul id="#ITEM#_prop_'.$propId.'_list" style="width: #WIDTH#;">';
				$skuTemplate[$propId]['FULL']['FINISH'] = '</ul></div>'.
					'<div class="bx_slide_left" id="#ITEM#_prop_'.$propId.'_left" data-treevalue="'.$propId.'" style="display: none;"></div>'.
					'<div class="bx_slide_right" id="#ITEM#_prop_'.$propId.'_right" data-treevalue="'.$propId.'" style="display: none;"></div>'.
					'</div></div>';
				foreach ($arProp['VALUES'] as $value)
				{
					$value['NAME'] = htmlspecialcharsbx($value['NAME']);
					$skuTemplate[$propId]['ITEMS'][$value['ID']] = '<li data-treevalue="'.$propId.'_'.$value['ID'].
						'" data-onevalue="'.$value['ID'].'" style="width: #WIDTH#; padding-top: #WIDTH#;"><i title="'.$value['NAME'].'"></i>'.
						'<span class="cnt"><span class="cnt_item" style="background-image:url(\''.$value['PICT']['SRC'].'\');" title="'.$value['NAME'].'"></span></span></li>';
				}
				unset($value);
			}
		}
		unset($templateRow, $arProp);
	}
}

$intRowsCount = count($arResult['ITEMS']);
$strRand = $this->randString();
$strContID = 'cat_top_cont_'.$strRand;
?>

<div class="related-products" id="<? echo $strContID; ?>">
    <div class="middle-content">
        <h3>Хит продаж</h3>
    </div>
    <div class="related-list">
        <div class="swiper-container related-slider">
            <div class="swiper-wrapper">
                <?php
                foreach ($arResult['ITEMS'] as $key => $arItem){
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], $strElementDelete, CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"));
                    ?>
                        <div class="swiper-slide">
                            <div class="product-item">
                                <div class="product-item_images">
                                    <div class="product-item_img">
                                        <a class="changed-url" href="<?=$arItem['DETAIL_PAGE_URL'];?>">
                                            <img class="swiper-lazy" data-src="<?=$arItem['DETAIL_PAGE_URL'];?>"
                                                 alt="Линейка с игрой &quot;Пятнашки&quot;, белая"
                                                 title="Линейка с игрой &quot;Пятнашки&quot;, белая">
                                        </a>
                                    </div>
                                    <ul class="product-item_gallery">
                                        <li>
                                            <a class="change-image-url" data-id="0"  data-tovar="2390026" data-tid="1270924"   data-link="/katalog/lineika_s_igroi_pyatnashki_sinyaya_2390026.php" href="foto-tovara2/2/3/9/2390026_1.jpg">
                                                <img data-src="foto-tovara2/2/3/9/2390026_1.jpg">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="change-image-url" data-id="1"  data-tovar="2390027" data-tid="1269295"   data-link="/katalog/lineika_s_igroi_pyatnashki_belaya_2390027.php" href="foto-tovara2/2/3/9/2390027_1.jpg">
                                                <img data-src="foto-tovara2/2/3/9/2390027_1.jpg">
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="infos" data-cacheid="">
                                    <div class="info-in-card" data-id="0" style="display:none">
                                        <a href="/katalog/lineika_s_igroi_pyatnashki_sinyaya_2390026.php" class="product-item_title">
                                            Линейка с игрой &quot;Пятнашки&quot;, синяя
                                        </a>
                                    </div>
                                    <div class="info-in-card loaded" data-id="1">
                                        <a href="/katalog/lineika_s_igroi_pyatnashki_belaya_2390027.php" class="product-item_title">
                                            Линейка с игрой &quot;Пятнашки&quot;, белая
                                        </a>
                                        <div itemprop="description" class="product-item_fields">
                                            <table>
                                                <tr class="tr-price">
                                                    <td>Цена</td>
                                                    <td>
                                                        <div class="price-big ">
                                                                <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                                                    <span itemprop="price"> 171.<sub>25</sub></span>
                                                                    <span itemprop="priceCurrency" style="font-size: 14px;" content="RUB">р.</span>
                                                                </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Артикул:</td>
                                                    <td>2390027</td>
                                                </tr>
                                                <tr>
                                                    <td>В наличии:</td>
                                                    <td>6191 шт.</td>
                                                </tr>
                                                <tr>
                                                    <td>Материал:</td>
                                                    <td>Пластик</td>
                                                </tr>
                                                <tr>
                                                    <td>Цвет:</td>
                                                    <td>Белый</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="product-item_buttons">
                                            <div class="button-cart">
                                                <button class="ubtn blue-border-ubtn btn-to-cart-small" type="submit">
                                                    Заказать
                                                </button>
                                                <form method="post" class="count-block product-item_tooltip">
                                                    <div class="quantity-title">Укажите необходимый тираж <span>(cвободно на складе)</span></div>
                                                    <div class="pit-fields quantity-block evoShop_shelfItem">
                                                        <div style="display:none;">
                                                            <span class="item_url">/katalog/lineika_s_igroi_pyatnashki_belaya_2390027.php</span>
                                                            <span class="item_image">foto-tovara2/2/3/9/2390027_1.jpg</span>
                                                            <span class="item_name">Линейка с игрой &quot;Пятнашки&quot;, белая</span>
                                                            <span class="item_price">171.25</span>
                                                            <span class="item_pricedefault">171.25</span>
                                                            <span class="item_pricera">159.26</span>
                                                            <span class="item_artikul">2390027</span>
                                                            <span class="item_inventory">6191</span>
                                                            <span class="item_diffprices"></span>
                                                            <span class="item_priceconst">171.25</span>
                                                            <span class="inventory-kolvo">6191</span>
                                                            <span class="item_ves"> 38</span>
                                                            <span class="item_obem"> </span>
                                                            <input style="" type="button"
                                                                   class="item_add item-add-btn"
                                                                   value="Положить в корзину">
                                                        </div>
                                                        <input type="text" name="count" placeholder="6191"
                                                               class="item_quantity input-number input-count"
                                                               required="">
                                                    </div>
                                                    <hr>
                                                    <div class="pit-btn ">
                                                        <button type="submit"
                                                                class="global-add btn btn-cart btn-gray btn-round" itemtype="http://schema.org/BuyAction"
                                                                disabled>
                                                            Отложить
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="swiper-nav cp-nav">
            <div class="cp-button-prev">
                <i class="icon-arrow"></i>
            </div>
            <div class="cp-button-next">
                <i class="icon-arrow"></i>
            </div>
        </div>
    </div>
</div>
