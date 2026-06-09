<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Расширяет колонку VALUE в b_sale_basket_props для длинного JSON NANESENIE.
 */
final class BasketNaneseniyaStorage
{
    private const OPTION_KEY = 'basket_naneseniya_value_text_v1';

    public static function ensureValueColumn(): void
    {
        if (Option::get('main', self::OPTION_KEY, 'N') === 'Y') {
            return;
        }

        if (!Loader::includeModule('sale')) {
            return;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        if (!$connection->isTableExists('b_sale_basket_props')) {
            return;
        }

        $helper = $connection->getSqlHelper();
        $table = $helper->quote('b_sale_basket_props');
        $column = $helper->quote('VALUE');

        $connection->queryExecute("ALTER TABLE {$table} MODIFY {$column} TEXT");

        Option::set('main', self::OPTION_KEY, 'Y');
    }
}
