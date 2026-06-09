<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsTable;

/**
 * Настройка свойства заказа json_naneseniya: TYPE=STRING, MAXLENGTH=8000, MULTILINE.
 * (TYPE=TEXT в Bitrix Sale недопустим — ломает сохранение заказа.)
 */
final class OrderJsonNaneseniyaProperty
{
    private const OPTION_KEY = 'json_naneseniya_prop_string_v4';
    private const TARGET_MAXLENGTH = 8000;

    /** D7-валидация STRING всё равно режет на ~500 — длинные значения пишет JsonNaneseniyaPersister. */
    public const D7_SET_VALUE_SAFE_LENGTH = 500;

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
            $settings = is_array($prop['SETTINGS']) ? $prop['SETTINGS'] : [];
            $settings['MAXLENGTH'] = self::TARGET_MAXLENGTH;
            $settings['MULTILINE'] = 'Y';
            if (empty($settings['SIZE'])) {
                $settings['SIZE'] = 80;
            }

            OrderPropsTable::update((int)$prop['ID'], [
                'TYPE' => 'STRING',
                'SETTINGS' => $settings,
            ]);
        }

        Option::set('main', self::OPTION_KEY, 'Y');
    }
}
