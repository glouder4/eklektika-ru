<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<?if ($APPLICATION->GetCurPage() === '/') {
    $APPLICATION->SetTitle("Мебельная компания");
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>