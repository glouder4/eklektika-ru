<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Облако подразделов — выводим сразу под <h1> через after-title-description.
 *
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

ob_start();
?>
<!-- BEGIN cats-menu-filter -->
<div class="cats-menu-filter">
    <?php
    $iblockId = (int)$arParams["IBLOCK_ID"];
    $sectionId = (int)($arResult["VARIABLES"]["SECTION_ID"] ?? 0);
    $sectionCode = (string)($arResult["VARIABLES"]["SECTION_CODE"] ?? '');
    $folder = $arResult["FOLDER"] ?? '/catalog/';
    $urlTemplate = $arResult["URL_TEMPLATES"]["section"] ?? '#SECTION_CODE_PATH#/';
    require $_SERVER["DOCUMENT_ROOT"] . "/include/catalog/section-subsections.php";
    ?>
</div>
<?php
$APPLICATION->AddViewContent('after-title-description', ob_get_clean());
