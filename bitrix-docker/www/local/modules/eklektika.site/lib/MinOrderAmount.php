<?php

namespace OnlineService\Site;

use Bitrix\Main\Config\Option;
use OnlineService\Site\Config\SiteModuleConfig;

/**
 * Минимальная сумма заказа из настроек сайта (Option модуля eklektika.site).
 */
final class MinOrderAmount
{
    public static function getSum(?string $siteId = null): int
    {
        $raw = Option::get(
            SiteModuleConfig::MODULE_ID,
            SiteModuleConfig::MIN_ORDER_SUM_OPTION,
            (string)SiteModuleConfig::MIN_ORDER_SUM_DEFAULT
        );

        $sum = (int)preg_replace('/\D+/', '', (string)$raw);
        if ($sum <= 0) {
            $sum = SiteModuleConfig::MIN_ORDER_SUM_DEFAULT;
        }

        return $sum;
    }

    /** Формат для шапки: «50 000» */
    public static function formatSum(?int $sum = null): string
    {
        $sum = $sum ?? self::getSum();

        return number_format($sum, 0, '', ' ');
    }

    /** Текст шапки: «Мин. заказ 50 000 р.» */
    public static function formatHeaderLabel(?int $sum = null): string
    {
        return 'Мин. заказ ' . self::formatSum($sum) . ' р.';
    }
}
