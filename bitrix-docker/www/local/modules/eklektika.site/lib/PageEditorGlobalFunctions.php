<?php

use OnlineService\Site\PageSettings;

if (!function_exists('getPageEditorSettings')) {
    function getPageEditorSettings($ID, $IBLOCK_ID)
    {
        $pageSettings = PageSettings::getInstance((int) $ID, (int) $IBLOCK_ID);

        if (!$pageSettings instanceof PageSettings || !$pageSettings->isLoaded()) {
            return false;
        }

        return $pageSettings;
    }
}

if (!function_exists('getPageSettingValue')) {
    function getPageSettingValue($code, $elementId, $default = null, $iblockId = 60)
    {
        $pageSettings = getPageEditorSettings($elementId, $iblockId);

        if (!$pageSettings instanceof PageSettings) {
            return $default;
        }

        return $pageSettings->getValue($code, $default);
    }
}
