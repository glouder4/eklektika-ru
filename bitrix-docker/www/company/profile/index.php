<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/**
 * @global $APPLICATION
 */

$APPLICATION->SetTitle("Компания");

// Получаем ID элемента из URL
$elementId = $_REQUEST['ELEMENT_ID'] ?? '';
?>
    <div class="container personal-profile-wrapper">
        <?$APPLICATION->IncludeComponent(
	"bitrix:news.detail", 
	"os-personal-profile", 
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_ELEMENT_CHAIN" => "Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BROWSER_TITLE" => "-",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "0",
		"CACHE_TYPE" => "N",
		"CHECK_DATES" => "N",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"ELEMENT_CODE" => "",
		"ELEMENT_ID" => $elementId,
		"FIELD_CODE" => [
			0 => "",
			1 => "",
		],
		"IBLOCK_ID" => "23",
		"IBLOCK_TYPE" => "crm_connector",
		"IBLOCK_URL" => "",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"MESSAGE_404" => "",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Страница",
		"PROPERTY_CODE" => [
			0 => "LEGAN_ENTITY_ID_OF_HEAD_COMPANY",
			1 => "LEGAN_ENTITY_BOSS",
			2 => "LEGAN_ENTITY_WWW",
			3 => "LEGAN_ENTITY_INN",
			4 => "OS_IS_MARKETING_AGENT",
			5 => "LEGAN_ENTITY_USERS",
			6 => "LEGAN_ENTITY_PHONE",
			7 => "",
		],
		"SET_BROWSER_TITLE" => "Y",
		"SET_CANONICAL_URL" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "Y",
		"SHOW_404" => "N",
		"STRICT_SECTION_CHECK" => "N",
		"USE_PERMISSIONS" => "N",
		"USE_SHARE" => "N",
		"COMPONENT_TEMPLATE" => "os-personal-profile"
	],
	false
);?>
    </div>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php") ?>