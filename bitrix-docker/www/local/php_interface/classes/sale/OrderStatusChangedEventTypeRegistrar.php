<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Регистрирует доп. макросы в DESCRIPTION типа события SALE_STATUS_CHANGED_*
 * (блок «Доступные поля» в админке почтовых шаблонов).
 *
 * Сами значения заполняет {@see OrderNanesenieMailFormatter}.
 */
final class OrderStatusChangedEventTypeRegistrar
{
    private const OPTION_MODULE = 'main';
    private const OPTION_NAME = 'eklektika_status_mail_fields_v1';

    private const EXTRA_FIELDS_RU = [
        'ORDER_LIST' => 'состав заказа',
        'ORDER_CHANGE_REASON' => 'что изменилось (статус, описание, комментарий)',
        'PRICE' => 'сумма заказа',
        'HTML_ORDER_LIST' => 'состав заказа (HTML)',
        'HTML_ORDER_CHANGE_REASON' => 'что изменилось (HTML)',
        'HTML_PRICE' => 'сумма заказа (HTML)',
    ];

    private const EXTRA_FIELDS_EN = [
        'ORDER_LIST' => 'order items',
        'ORDER_CHANGE_REASON' => 'what changed (status, description, comment)',
        'PRICE' => 'order total',
        'HTML_ORDER_LIST' => 'order items (HTML)',
        'HTML_ORDER_CHANGE_REASON' => 'what changed (HTML)',
        'HTML_PRICE' => 'order total (HTML)',
    ];

    /**
     * Однократная регистрация при старте (флаг в b_option).
     */
    public static function ensureOnce(): void
    {
        try {
            if (Option::get(self::OPTION_MODULE, self::OPTION_NAME, '') === 'Y') {
                return;
            }

            $result = self::ensureExtraFields('N');
            if ($result['updated'] !== [] || $result['skipped'] !== []) {
                Option::set(self::OPTION_MODULE, self::OPTION_NAME, 'Y');
            }
        } catch (\Throwable $e) {
            // не должна ломать сайт / почту
        }
    }

    /**
     * @param string|null $statusId null = все статусы заказов, 'N' = только SALE_STATUS_CHANGED_N
     * @return array{updated: list<array{event:string,lid:string}>, skipped: list<string>, events: list<string>}
     */
    public static function ensureExtraFields(?string $statusId = 'N'): array
    {
        $updated = [];
        $skipped = [];
        $events = self::resolveEventNames($statusId);

        foreach ($events as $eventName) {
            $rs = \CEventType::GetList(['EVENT_NAME' => $eventName]);
            $found = false;
            while ($row = $rs->Fetch()) {
                $found = true;
                $lid = (string)($row['LID'] ?? 'ru');
                $description = (string)($row['DESCRIPTION'] ?? '');
                $extra = (stripos($lid, 'en') === 0) ? self::EXTRA_FIELDS_EN : self::EXTRA_FIELDS_RU;
                $newDescription = self::mergeDescription($description, $extra);

                if ($newDescription === $description) {
                    $skipped[] = $eventName . ':' . $lid . ':already';
                    continue;
                }

                $result = \CEventType::Update(
                    [
                        'EVENT_NAME' => $eventName,
                        'LID' => $lid,
                    ],
                    [
                        'DESCRIPTION' => $newDescription,
                    ]
                );

                if ($result) {
                    $updated[] = ['event' => $eventName, 'lid' => $lid];
                } else {
                    $skipped[] = $eventName . ':' . $lid . ':update_failed';
                }
            }

            if (!$found) {
                $skipped[] = $eventName . ':not_found';
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'events' => $events,
        ];
    }

    /**
     * @return list<string>
     */
    private static function resolveEventNames(?string $statusId): array
    {
        if ($statusId !== null && $statusId !== '') {
            return ['SALE_STATUS_CHANGED_' . $statusId];
        }

        $events = [];
        if (Loader::includeModule('sale')) {
            $rs = \CSaleStatus::GetList(['SORT' => 'ASC'], [], false, false, ['ID']);
            while ($row = $rs->Fetch()) {
                $id = (string)($row['ID'] ?? '');
                if ($id !== '') {
                    $events[] = 'SALE_STATUS_CHANGED_' . $id;
                }
            }
        }

        if ($events === []) {
            $events[] = 'SALE_STATUS_CHANGED_N';
        }

        return array_values(array_unique($events));
    }

    /**
     * @param array<string, string> $extraFields
     */
    private static function mergeDescription(string $description, array $extraFields): string
    {
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        $description = rtrim($description);

        foreach ($extraFields as $code => $label) {
            $token = '#' . $code . '#';
            if (strpos($description, $token) !== false) {
                continue;
            }
            $description .= "\n" . $token . ' - ' . $label;
        }

        return $description . "\n";
    }
}
