<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$isAjaxRequest = isset($arParams['AJAX_REQUEST']) && $arParams['AJAX_REQUEST'] == 'Y';

if($isAjaxRequest){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'cart_html' => renderBasketHtml($arResult,true),
        'totals_html' => renderTotalInfoHtml($arResult,true)
    ], JSON_UNESCAPED_UNICODE);

    return 1;
    exit;
}

$this->SetViewTarget('after-title-description');
?>
    <p style="color: red; font-size: 16px;">Внимание! Стоимость нанесения рассчитывается менеджером после оформления заказа.</p>
<?php
$this->EndViewTarget();

/**
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 * @var CBitrixComponentTemplate $this
 * @var array $giftParameters
 */

$documentRoot = Main\Application::getDocumentRoot();

\CJSCore::Init(array('fx', 'popup', 'ajax'));
Main\UI\Extension::load(['ui.mustache']);

//$this->addExternalCss('/bitrix/css/main/bootstrap.css');
//$this->addExternalCss($templateFolder.'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css');

//$this->addExternalJs($templateFolder.'/js/action-pool.js');
//$this->addExternalJs($templateFolder.'/js/filter.js');
//$this->addExternalJs($templateFolder.'/js/component.js');
$this->addExternalJs($templateFolder.'/js/main-basket.js');

function renderBasketHtml(&$arResult,$isAjax=false){
    ob_start();
    foreach ($arResult['GRID']['ROWS'] as $key => $arItem){
        include __DIR__ . '/item_row.php';
    }
    $cartHtml = ob_get_clean();
    if($isAjax)
        ob_clean();
    return $cartHtml;
}

function renderTotalInfoHtml(&$arResult,$isAjax=false)
{
    ob_start();
    include __DIR__ . '/total_info.php';
    $totalsHtml = ob_get_clean();

    if($isAjax)
        ob_clean();

    return $totalsHtml;
}

if (empty($arResult['ERROR_MESSAGE']))
{

	if ($arResult['BASKET_ITEM_MAX_COUNT_EXCEEDED'])
	{
		?>
		<div id="basket-item-message">
			<?=Loc::getMessage('SBB_BASKET_ITEM_MAX_COUNT_EXCEEDED', array('#PATH#' => $arParams['PATH_TO_BASKET']))?>
		</div>
		<?
	}
	?>

    <div class="cart-product">
        <div class="cart-product-head">
            <div class="row">
                <div class="cart-col cart-col1"></div>
                <div class="cart-col cart-col2">Артикул</div>
                <div class="cart-col cart-col3">Цена за шт.</div>
                <div class="cart-col cart-col4">Тираж</div>
                <div class="cart-col cart-col5">Нанесение</div>
                <div class="cart-col cart-col5">Сумма</div>
                <div class="cart-col cart-col6"></div>
            </div>
        </div>
        <div id="my_cart">
            <?php
                echo renderBasketHtml($arResult);
            ?>
        </div>
        <!-- end cart-product-row -->
        <!-- //////////////////// -->
    </div>

    <div id="cart-totals">
        <?php
            echo renderTotalInfoHtml($arResult);
        ?>
    </div>

	<?

	if (!empty($arResult['CURRENCIES']) && Main\Loader::includeModule('currency'))
	{
		CJSCore::Init('currency');

		?>
		<script>
			BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true)?>);
		</script>
		<?
	}

	$signer = new \Bitrix\Main\Security\Sign\Signer;
	$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
	$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
	$messages = Loc::loadLanguageFile(__FILE__);
	?>
	<script>
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);
		/*BX.Sale.BasketComponent.init({
			result: <?=CUtil::PhpToJSObject($arResult, false, false, true)?>,
			params: <?=CUtil::PhpToJSObject($arParams)?>,
			template: '<?=CUtil::JSEscape($signedTemplate)?>',
			signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
			siteId: '<?=CUtil::JSEscape($component->getSiteId())?>',
			siteTemplateId: '<?=CUtil::JSEscape($component->getSiteTemplateId())?>',
			templateFolder: '<?=CUtil::JSEscape($templateFolder)?>'
		});*/
	</script>
	<?
}
elseif ($arResult['EMPTY_BASKET'])
{
	include(Main\Application::getDocumentRoot().$templateFolder.'/empty.php');
}
else
{
	ShowError($arResult['ERROR_MESSAGE']);
}

?>

