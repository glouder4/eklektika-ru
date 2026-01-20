<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content flex-wrapper';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Корзина");
$APPLICATION->AddChainItem("Корзина", "");
$APPLICATION->SetPageProperty("title", "Корзина");

?>
<div id="cart-page">
	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.basket.basket",
		"main-basket",
		[
			"COMPONENT_TEMPLATE" => ".default",
			"DEFERRED_REFRESH" => "N",
			"USE_DYNAMIC_SCROLL" => "Y",
			"SHOW_FILTER" => "Y",
			"SHOW_RESTORE" => "Y",
			"COLUMNS_LIST_EXT" => [
				0 => "PREVIEW_PICTURE",
				1 => "PROPERTY_ARTICLE",
				2 => "NAME",
				3 => "PRICE",
				4 => "DISCOUNT",
				5 => "QUANTITY",
				6 => "PROPERTY_METOD_NANESENIYA",
				7 => "DELETE",
				8 => "SUM",
			],
			"COLUMNS_LIST_MOBILE" => [
			],
			"TEMPLATE_THEME" => "blue",
			"TOTAL_BLOCK_DISPLAY" => [
				0 => "top",
			],
			"DISPLAY_MODE" => "extended",
			"PRICE_DISPLAY_MODE" => "Y",
			"SHOW_DISCOUNT_PERCENT" => "Y",
			"DISCOUNT_PERCENT_POSITION" => "bottom-right",
			"PRODUCT_BLOCKS_ORDER" => "props,sku,columns",
			"USE_PRICE_ANIMATION" => "Y",
			"LABEL_PROP" => [
			],
			"PATH_TO_ORDER" => "/personal/order/make/",
			"HIDE_COUPON" => "N",
			"PRICE_VAT_SHOW_VALUE" => "N",
			"USE_PREPAYMENT" => "N",
			"QUANTITY_FLOAT" => "Y",
			"CORRECT_RATIO" => "Y",
			"AUTO_CALCULATION" => "Y",
			"SET_TITLE" => "Y",
			"ACTION_VARIABLE" => "basketAction",
			"COMPATIBLE_MODE" => "Y",
			"EMPTY_BASKET_HINT_PATH" => "/",
			"ADDITIONAL_PICT_PROP_13" => "-",
			"ADDITIONAL_PICT_PROP_14" => "-",
			"BASKET_IMAGES_SCALING" => "adaptive",
			"USE_GIFTS" => "N",
			"GIFTS_PLACE" => "BOTTOM",
			"GIFTS_BLOCK_TITLE" => "Выберите один из подарков",
			"GIFTS_HIDE_BLOCK_TITLE" => "N",
			"GIFTS_TEXT_LABEL_GIFT" => "Подарок",
			"GIFTS_PRODUCT_QUANTITY_VARIABLE" => "quantity",
			"GIFTS_PRODUCT_PROPS_VARIABLE" => "prop",
			"GIFTS_SHOW_OLD_PRICE" => "N",
			"GIFTS_SHOW_DISCOUNT_PERCENT" => "Y",
			"GIFTS_MESS_BTN_BUY" => "Выбрать",
			"GIFTS_MESS_BTN_DETAIL" => "Подробнее",
			"GIFTS_PAGE_ELEMENT_COUNT" => "4",
			"GIFTS_CONVERT_CURRENCY" => "N",
			"GIFTS_HIDE_NOT_AVAILABLE" => "N",
			"USE_ENHANCED_ECOMMERCE" => "N"
		],
		false
	);?>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
