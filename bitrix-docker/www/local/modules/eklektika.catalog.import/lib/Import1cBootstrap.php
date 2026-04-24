<?php

namespace OnlineService\Catalog\Import1c;

/**
 * Регистрация обработчиков постобработки каталога после импорта 1С.
 */
final class Import1cBootstrap
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        if (!\function_exists('AddEventHandler')) {
            return;
        }

        \AddEventHandler(
            'catalog',
            'OnSuccessCatalogImport1C',
            [PostImportHandler::class, 'onSuccessCatalogImport']
        );
    }
}
