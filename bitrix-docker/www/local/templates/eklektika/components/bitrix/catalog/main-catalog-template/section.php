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
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$this->setFrameMode(true);

?>
<div class="category">
<?
    if( $arParams['IS_SEARCH_PAGE'] == "Y" )
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/search.php");
    else
        include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_main_catalog_template-view.php");
    //include($_SERVER["DOCUMENT_ROOT"]."/".$this->GetFolder()."/section_horizontal.php");
?>
</div>
