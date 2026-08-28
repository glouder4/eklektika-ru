<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("og:title", "Подарки корпоративные в Москве купить - сувенирная продукция с логотипом на заказ");
$APPLICATION->SetPageProperty("title", "Подарки корпоративные в Москве купить - сувенирная продукция с логотипом на заказ");
$APPLICATION->SetPageProperty("description", "Подарки корпоративные купить или заказать с доставкой по Москве и России. Сувенирная продукция с логотипом на заказ. Брендированная продукция с логотипом компании. Гибкий ценовой подход. Изготовление на заказ. Срочно и качественно.");
 

?>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
 
<?if ($APPLICATION->GetCurPage() === '/') {
    $APPLICATION->SetTitle("Эклектика");
?>

<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"mainpage-slider", 
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "N",
		"DISPLAY_PICTURE" => "N",
		"DISPLAY_PREVIEW_TEXT" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "DETAIL_PICTURE",
			1 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "17",
		"IBLOCK_TYPE" => "sliders",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "TARGET",
			1 => "LINK",
			2 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "ACTIVE_FROM",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "mainpage-slider"
	],
	false
);?>

    <?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"marquee-slider", 
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "N",
		"DISPLAY_PICTURE" => "N",
		"DISPLAY_PREVIEW_TEXT" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "DETAIL_PICTURE",
			1 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "18",
		"IBLOCK_TYPE" => "sliders",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "TARGET",
			1 => "LINK",
			2 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "ACTIVE_FROM",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "marquee-slider"
	],
	false
);?>


    <?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"main-popular-block",
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "/#ELEMENT_CODE#/",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "N",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "CODE",
			1 => "PREVIEW_PICTURE",
			2 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "26",
		"IBLOCK_TYPE" => "content",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "4",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "ID",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "main-popular-block"
	],
	false
);?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        Array(
            "AREA_FILE_SHOW" => "file",
            "AREA_FILE_SUFFIX" => "inc",
            "EDIT_TEMPLATE" => "",
            "PATH" => "/include/page_index/titles/main-title.php"
        )
    );?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        [
            "AREA_FILE_SHOW" => "file",
            "AREA_FILE_SUFFIX" => "inc",
            "EDIT_TEMPLATE" => "",
            "PATH" => "/include/page_index/catalog-sections.php"
        ],
        false
    );?>


    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        Array(
            "AREA_FILE_SHOW" => "file",
            "AREA_FILE_SUFFIX" => "inc",
            "EDIT_TEMPLATE" => "",
            "PATH" => "/include/page_index/titles/free-services.php"
        )
    );?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        Array(
            "AREA_FILE_SHOW" => "file",
            "AREA_FILE_SUFFIX" => "inc",
            "EDIT_TEMPLATE" => "",
            "PATH" => "/include/page_index/free-services.php"
        )
    );?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        Array(
            "AREA_FILE_SHOW" => "file",
            "AREA_FILE_SUFFIX" => "inc",
            "EDIT_TEMPLATE" => "",
            "PATH" => "/include/page_index/mainpage-description.php"
        )
    );?>

    <p>&nbsp;</p>
    <p><!-- Клиенты -->

    <div class="related-list portfolio">
        <h2>Наши клиенты</h2>
        <?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"our-clients", 
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "N",
		"DISPLAY_PICTURE" => "N",
		"DISPLAY_PREVIEW_TEXT" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "DETAIL_PICTURE",
			1 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "19",
		"IBLOCK_TYPE" => "sliders",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "LINK",
			1 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "ID",
		"SORT_BY2" => "ACTIVE_FROM",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "our-clients"
	],
	false
);?>
    </div>

    <div class="related-list">
        <h2>Бренды</h2>
        <?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"our-brands", 
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "N",
		"DISPLAY_PICTURE" => "N",
		"DISPLAY_PREVIEW_TEXT" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "DETAIL_PICTURE",
			1 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "20",
		"IBLOCK_TYPE" => "sliders",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "LINK",
			1 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "ACTIVE_FROM",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "our-brands"
	],
	false
);?>
    </div>
        <br><br>

    </p>

	<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"main-service-block",
	[
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "/#ELEMENT_CODE#/",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "N",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => [
			0 => "CODE",
			1 => "PREVIEW_PICTURE",
			2 => "PREVIEW_TEXT",
			3 => "",
		],
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "27",
		"IBLOCK_TYPE" => "content",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "N",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "6",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => [
			0 => "",
		],
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "ID",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "main-service-block"
	],
	false
);?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "file",
			"AREA_FILE_SUFFIX" => "inc",
			"EDIT_TEMPLATE" => "",
			"PATH" => "/include/page_index/info_eklektika.php"
		)
	);?>
<? } else { ?>

	<!-- Стандартный вывод для других страниц (например, через news.list и т.п.) -->
<? } ?>

<?php if ($APPLICATION->GetCurPage() === '/'): ?>
<style>
.reg-pending-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10050;display:none;align-items:center;justify-content:center;padding:16px;}
.reg-pending-overlay.is-visible{display:flex;}
.reg-pending-modal{max-width:420px;width:100%;background:#fff;border-radius:10px;padding:24px 28px;box-shadow:0 12px 40px rgba(0,0,0,.2);position:relative;}
.reg-pending-modal__close{position:absolute;top:10px;right:12px;border:0;background:transparent;font-size:26px;line-height:1;cursor:pointer;color:#666;padding:4px;}
.reg-pending-modal__close:hover{color:#111;}
.reg-pending-modal h3{margin:0 0 12px;font-size:18px;font-weight:600;color:#222;}
.reg-pending-modal p{margin:0 0 20px;font-size:15px;line-height:1.45;color:#444;}
.reg-pending-modal .btn{margin-top:4px;}
</style>
<div id="reg-pending-overlay" class="reg-pending-overlay" role="presentation" aria-hidden="true">
	<div class="reg-pending-modal" role="dialog" aria-modal="true" aria-labelledby="reg-pending-title">
		<button type="button" class="reg-pending-modal__close" id="reg-pending-close-x" aria-label="Закрыть">&times;</button>
		<h3 id="reg-pending-title">Регистрация успешна</h3>
		<p>Вы вошли на сайт. Учётная запись проходит модерацию; после её завершения вам будет доступен полный функционал.</p>
		<button type="button" class="btn btn-round btn-shadow btn-blue" id="reg-pending-ok">Понятно</button>
	</div>
</div>
<script>
(function() {
	function stripRegPendingFromUrl() {
		try {
			var u = new URL(window.location.href);
			if (u.searchParams.get('reg_pending') !== '1') {
				return;
			}
			u.searchParams.delete('reg_pending');
			var next = u.pathname + (u.search ? u.search : '') + (u.hash || '');
			window.history.replaceState({}, document.title, next);
		} catch (e) {}
	}
	function hideOverlay() {
		var o = document.getElementById('reg-pending-overlay');
		if (!o) return;
		o.classList.remove('is-visible');
		o.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}
	function showOverlay() {
		var o = document.getElementById('reg-pending-overlay');
		if (!o) return;
		o.classList.add('is-visible');
		o.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}
	try {
		var sp = new URLSearchParams(window.location.search || '');
		if (sp.get('reg_pending') === '1') {
			var open = function() {
				stripRegPendingFromUrl();
				showOverlay();
			};
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', open);
			} else {
				open();
			}
		}
	} catch (e) {}
	document.addEventListener('click', function(ev) {
		if (ev.target && ev.target.id === 'reg-pending-ok') {
			hideOverlay();
			stripRegPendingFromUrl();
		}
		if (ev.target && ev.target.id === 'reg-pending-close-x') {
			hideOverlay();
			stripRegPendingFromUrl();
		}
		var o = document.getElementById('reg-pending-overlay');
		if (ev.target === o) {
			hideOverlay();
			stripRegPendingFromUrl();
		}
	});
	document.addEventListener('keydown', function(ev) {
		if (ev.key === 'Escape') {
			var o = document.getElementById('reg-pending-overlay');
			if (o && o.classList.contains('is-visible')) {
				hideOverlay();
				stripRegPendingFromUrl();
			}
		}
	});
})();
</script>
<?php endif; ?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>