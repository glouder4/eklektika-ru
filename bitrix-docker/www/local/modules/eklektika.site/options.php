<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Localization\Loc;
use OnlineService\Site\Config\SiteModuleConfig;

defined('B_PROLOG_INCLUDED') || die();

/** @global CMain $APPLICATION */
/** @global CUser $USER */

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

// Модуль грузится через local include (как eklektika_requires), без ModuleManager::isModuleInstalled.
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.site/include.php';
Loc::loadMessages(__FILE__);

$moduleId = SiteModuleConfig::MODULE_ID;
$request = HttpApplication::getInstance()->getContext()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    $sum = (int)preg_replace('/\D+/', '', (string)$request->getPost(SiteModuleConfig::MIN_ORDER_SUM_OPTION));
    if ($sum <= 0) {
        $sum = SiteModuleConfig::MIN_ORDER_SUM_DEFAULT;
    }
    Option::set($moduleId, SiteModuleConfig::MIN_ORDER_SUM_OPTION, (string)$sum);
    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($moduleId) . '&lang=' . LANGUAGE_ID . '&mid_menu=1');
}

$currentSum = (int)Option::get(
    $moduleId,
    SiteModuleConfig::MIN_ORDER_SUM_OPTION,
    (string)SiteModuleConfig::MIN_ORDER_SUM_DEFAULT
);

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('EKLEKTIKA_SITE_OPT_TAB'),
        'TITLE' => Loc::getMessage('EKLEKTIKA_SITE_OPT_TAB_TITLE'),
    ],
];
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= urlencode($moduleId) ?>&amp;lang=<?= LANGUAGE_ID ?>&amp;mid_menu=1">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%">
            <label for="<?= htmlspecialcharsbx(SiteModuleConfig::MIN_ORDER_SUM_OPTION) ?>">
                <?= Loc::getMessage('EKLEKTIKA_SITE_OPT_MIN_ORDER_SUM') ?>
            </label>
        </td>
        <td width="60%">
            <input
                type="text"
                id="<?= htmlspecialcharsbx(SiteModuleConfig::MIN_ORDER_SUM_OPTION) ?>"
                name="<?= htmlspecialcharsbx(SiteModuleConfig::MIN_ORDER_SUM_OPTION) ?>"
                value="<?= (int)$currentSum ?>"
                size="12"
            >
            <?= Loc::getMessage('EKLEKTIKA_SITE_OPT_MIN_ORDER_SUM_HINT') ?>
        </td>
    </tr>
    <?php $tabControl->Buttons(['btnApply' => true, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>
    <?php $tabControl->End(); ?>
</form>
