<?php

use Bitrix\Main\ModuleManager;

class eklektika_site extends CModule
{
    public $MODULE_ID = 'eklektika.site';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME = 'Eklektika Site';
    public $MODULE_DESCRIPTION = 'Настройки сайта Эклектика (мин. заказ и др.)';
    public $PARTNER_NAME = 'Eklektika';
    public $PARTNER_URI = 'https://eklektika.ru';

    public function __construct()
    {
        $version = include __DIR__ . '/version.php';
        if (is_array($version)) {
            $this->MODULE_VERSION = $version['VERSION'] ?? '1.0.0';
            $this->MODULE_VERSION_DATE = $version['VERSION_DATE'] ?? '';
        }
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
