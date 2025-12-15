<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
    <ul class="footer-menu">
        <?foreach($arResult as $arItem):?>
            <?if($arItem["PERMISSION"] > "D" && $arItem["DEPTH_LEVEL"] <= $arParams["MAX_LEVEL"]):?>
                <li><a href="<?=htmlspecialchars($arItem["LINK"])?>"><?=htmlspecialchars($arItem["TEXT"])?></a></li>
            <?endif?>
        <?endforeach?>
    </ul>
<?endif?>
