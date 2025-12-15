<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
    <ul class=" menu " itemscope itemtype="http://www.schema.org/SiteNavigationElement">
        <?
        $previousLevel = 0;
        $items = array_values($arResult);
        $itemsCount = count($items);
        
        for($i = 0; $i < $itemsCount; $i++):
            $arItem = $items[$i];
            
            if($arItem["PERMISSION"] <= "D") {
                continue;
            }
            
            // Проверяем, есть ли дочерние элементы
            $hasChildren = false;
            if ($i + 1 < $itemsCount && $items[$i + 1]["DEPTH_LEVEL"] > $arItem["DEPTH_LEVEL"]) {
                $hasChildren = true;
            }
            
            // Закрываем теги при переходе на более высокий уровень
            if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel) {
                // Закрываем все вложенные уровни
                for ($closeLevel = $previousLevel; $closeLevel > $arItem["DEPTH_LEVEL"]; $closeLevel--) {
                    echo "</ul>";
                    // Если закрываем второй уровень, нужно закрыть div.sub-menu
                    if ($closeLevel == 2) {
                        echo "</div>";
                    }
                    echo "</li>";
                }
            }
            
            // Выводим пункт меню
            if ($arItem["DEPTH_LEVEL"] == 1): ?>
                <li itemprop="name"><a itemprop="url" href="<?=htmlspecialchars($arItem["LINK"])?>"><?=htmlspecialchars($arItem["TEXT"])?></a>
                    <?if ($hasChildren): ?>
                        <div class="sub-menu justify-content-between">
                            <ul>
                    <?else: ?>
                        </li>
                    <?endif?>
            <?else: ?>
                <li><a href="<?=htmlspecialchars($arItem["LINK"])?>"><?=htmlspecialchars($arItem["TEXT"])?></a>
                    <?if ($hasChildren): ?>
                        <ul>
                    <?else: ?>
                        </li>
                    <?endif?>
            <?endif?>
            
            <?
            $previousLevel = $arItem["DEPTH_LEVEL"];
        endfor;
        
        // Закрываем все оставшиеся открытые теги
        if ($previousLevel > 1) {
            for ($closeLevel = $previousLevel; $closeLevel >= 2; $closeLevel--) {
                echo "</ul>";
                if ($closeLevel == 2) {
                    echo "</div>";
                }
                echo "</li>";
            }
        }
        ?>
    </ul>
<?endif?>
