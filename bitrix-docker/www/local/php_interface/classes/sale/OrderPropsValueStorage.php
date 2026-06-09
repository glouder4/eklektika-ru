<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Расширяет колонку VALUE в b_sale_order_props_value для json_naneseniya.
 */
final class OrderPropsValueStorage
{
    private const OPTION_KEY = 'order_props_value_text_v1';

    public static function ensureValueColumn(): void
    {
        if (Option::get('main', self::OPTION_KEY, 'N') === 'Y') {
            return;
        }

        if (!Loader::includeModule('sale')) {
            return;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        if (!$connection->isTableExists('b_sale_order_props_value')) {
            return;
        }

        $helper = $connection->getSqlHelper();
        $table = $helper->quote('b_sale_order_props_value');
        $column = $helper->quote('VALUE');

        $connection->queryExecute("ALTER TABLE {$table} MODIFY {$column} TEXT");

        Option::set('main', self::OPTION_KEY, 'Y');
    }
}
