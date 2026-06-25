<?php

declare(strict_types=1);

namespace OnlineService\Feed\Bootstrap;

use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;

/**
 * CLI-bootstrap без полного prolog_before (обход конфликта ob + sproduction.integration).
 */
final class FeedCliBootstrap
{
    public static function init(string $documentRoot): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

        if (!defined('B_PROLOG_INCLUDED')) {
            define('B_PROLOG_INCLUDED', true);
        }

        define('NO_KEEP_STATISTIC', true);
        define('NOT_CHECK_PERMISSIONS', true);
        define('BX_NO_ACCELERATOR_RESET', true);
        define('BX_CRONTAB', true);
        define('BX_CRONTAB_SUPPORT', true);
        define('BX_WITH_ON_AFTER_EPILOG', false);
        define('BX_SKIP_SESSION_EXPAND', true);
        define('NO_AGENT_CHECK', true);
        define('DisableEventsCheck', true);
        define('CHK_EVENT', false);
        define('STOP_STATISTICS', true);

        if (PHP_SAPI === 'cli') {
            ini_set('display_errors', '1');
            ini_set('html_errors', '0');
            ini_set('output_buffering', '0');
            ini_set('implicit_flush', '1');
            @ini_set('memory_limit', '512M');
            self::clearOutputBuffers();
        }

        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/local/modules/eklektika.feed/tools/regenerate_yandex_yml.php';
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        require_once $documentRoot . '/bitrix/modules/main/start.php';

        self::ensureSiteContext();
        self::ensureHttpContext();
        self::ensureLegacyKernelGlobals();

        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Cannot load required Bitrix module: iblock');
        }

        require_once $documentRoot . '/local/modules/eklektika.feed/include.php';

        if (PHP_SAPI === 'cli') {
            self::clearOutputBuffers();
        }
    }

    private static function ensureSiteContext(): void
    {
        if (defined('SITE_ID') && defined('SITE_DIR')) {
            return;
        }

        $site = SiteTable::getList([
            'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
            'limit' => 1,
            'select' => ['LID', 'DIR', 'SERVER_NAME', 'LANGUAGE_ID'],
        ])->fetch();

        if (!$site) {
            $site = SiteTable::getList([
                'filter' => ['=ACTIVE' => 'Y'],
                'limit' => 1,
                'select' => ['LID', 'DIR', 'SERVER_NAME', 'LANGUAGE_ID'],
                'order' => ['SORT' => 'ASC'],
            ])->fetch();
        }

        if (!defined('SITE_ID')) {
            define('SITE_ID', (string)($site['LID'] ?? 's1'));
        }
        if (!defined('SITE_DIR')) {
            define('SITE_DIR', (string)($site['DIR'] ?? '/'));
        }
        if (!defined('SITE_SERVER_NAME')) {
            define('SITE_SERVER_NAME', (string)($site['SERVER_NAME'] ?? 'localhost'));
        }
        if (!defined('LANGUAGE_ID')) {
            define('LANGUAGE_ID', (string)($site['LANGUAGE_ID'] ?? 'ru'));
        }
        if (!defined('LANG')) {
            define('LANG', SITE_ID);
        }
    }

    /**
     * D7 Context + HttpRequest для CIBlockElement::GetNext() и прочих API без полного prolog/start().
     */
    private static function ensureHttpContext(): void
    {
        $application = HttpApplication::getInstance();
        if ($application->getContext() !== null) {
            return;
        }

        $application->initializeExtendedKernel([
            'get' => $_GET ?? [],
            'post' => $_POST ?? [],
            'files' => $_FILES ?? [],
            'cookie' => $_COOKIE ?? [],
            'server' => $_SERVER,
            'env' => $_ENV ?? [],
        ]);
    }

    private static function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Часть legacy-окружения из bitrix/modules/main/include.php без init.php и application->start().
     */
    private static function ensureLegacyKernelGlobals(): void
    {
        if (!isset($GLOBALS['MESS']) || !is_array($GLOBALS['MESS'])) {
            $GLOBALS['MESS'] = [];
        }
        if (!isset($GLOBALS['ALL_LANG_FILES']) || !is_array($GLOBALS['ALL_LANG_FILES'])) {
            $GLOBALS['ALL_LANG_FILES'] = [];
        }
        if (!isset($GLOBALS['arCustomTemplateEngines'])) {
            $GLOBALS['arCustomTemplateEngines'] = [];
        }

        $filterToolsPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/filter_tools.php';
        if (is_file($filterToolsPath)) {
            require_once $filterToolsPath;
        }

        if (!isset($GLOBALS['USER_FIELD_MANAGER']) || !is_object($GLOBALS['USER_FIELD_MANAGER'])) {
            $GLOBALS['USER_FIELD_MANAGER'] = new \CUserTypeManager();
        }

        if (class_exists(\CMenuCustom::class, false) && !isset($GLOBALS['BX_MENU_CUSTOM'])) {
            $GLOBALS['BX_MENU_CUSTOM'] = \CMenuCustom::getInstance();
        }

        if (!isset($GLOBALS['APPLICATION']) || !($GLOBALS['APPLICATION'] instanceof \CMain)) {
            $GLOBALS['APPLICATION'] = new \CMain();
        }

        if (!defined('SITE_CHARSET')) {
            define('SITE_CHARSET', 'UTF-8');
        }
        if (!defined('LANG_CHARSET')) {
            define('LANG_CHARSET', SITE_CHARSET);
        }
        if (!defined('FORMAT_DATE')) {
            define('FORMAT_DATE', 'DD.MM.YYYY');
        }
        if (!defined('FORMAT_DATETIME')) {
            define('FORMAT_DATETIME', 'DD.MM.YYYY HH:MI:SS');
        }
        if (!defined('LANG_DIR')) {
            define('LANG_DIR', defined('SITE_DIR') ? SITE_DIR : '/');
        }
        if (!defined('BX_UTF')) {
            define('BX_UTF', true);
        }
    }
}
