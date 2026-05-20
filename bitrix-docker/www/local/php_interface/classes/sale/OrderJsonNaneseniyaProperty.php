<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsTable;

/**
 * Увеличивает MAXLENGTH свойства заказа json_naneseniya (по умолчанию Bitrix — 500 для STRING).
 */
final class OrderJsonNaneseniyaProperty
{
    private const OPTION_KEY = 'json_naneseniya_prop_maxlength_v1';
    private const TARGET_MAXLENGTH = 8000;

    public static function ensureMaxLength(): void
    {
        if (Option::get('main', self::OPTION_KEY, 'N') === 'Y') {
            return;
        }

        if (!Loader::includeModule('sale')) {
            return;
        }

        $rs = OrderPropsTable::getList([
            'filter' => ['=CODE' => 'json_naneseniya'],
            'select' => ['ID', 'SETTINGS', 'TYPE'],
        ]);

        while ($prop = $rs->fetch()) {
            if (($prop['TYPE'] ?? '') !== 'STRING') {
                continue;
            }

            $settings = is_array($prop['SETTINGS']) ? $prop['SETTINGS'] : [];
            $settings['MAXLENGTH'] = self::TARGET_MAXLENGTH;
            if (empty($settings['SIZE'])) {
                $settings['SIZE'] = 80;
            }

            OrderPropsTable::update((int)$prop['ID'], ['SETTINGS' => $settings]);
        }

        Option::set('main', self::OPTION_KEY, 'Y');
    }
}
