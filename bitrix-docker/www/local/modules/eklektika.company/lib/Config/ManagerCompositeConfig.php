<?php

namespace OnlineService\Site\Config;

/**
 * Композитные свойства карточки менеджера (ИБ 24, группа menedzhery).
 */
final class ManagerCompositeConfig
{
    public const GROUP_KEY = 'menedzhery';

    /** Повторитель внутри группы menedzhery. */
    public const SOCIAL_REPEATER_CODE = 'sotsialnaya_set';

    public const INNER_TYPE_CODE = 'tip_sotsseti';

    public const INNER_LINK_CODE = 'ssylka';

    /**
     * Поля входящего UPDATE_MANAGER → значение tip_sotsseti (TELEGRAM / MAX).
     *
     * @var array<string, string>
     */
    public const PAYLOAD_LINK_MAP = [
        'TELEGRAM_LINK' => 'TELEGRAM',
        'MAX_LINK' => 'MAX',
    ];

    /**
     * @return array<string, string>
     */
    public static function getPayloadLinkMap(): array
    {
        return self::PAYLOAD_LINK_MAP;
    }

    /**
     * @return list<string>
     */
    public static function getManagedSocialTypes(): array
    {
        return array_values(self::PAYLOAD_LINK_MAP);
    }
}
