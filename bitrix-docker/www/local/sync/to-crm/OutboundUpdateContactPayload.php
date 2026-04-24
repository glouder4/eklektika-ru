<?php

declare(strict_types=1);

namespace OnlineService\Sync\ToCrm;

use OnlineService\Sync\FromCrm\CrmInboundUfMap;

/**
 * Сборка полей POST для ACTION=UPDATE_CONTACT при отправке с портала B24 на сайт.
 *
 * Канонический файл — в репозитории сайта (`local/sync/to-crm/`). На портале зеркало:
 * `eklektika-ru-b24/local/sync/to-site/OutboundContactMarketingForSite.php` (без зависимости от
 * классов сайта). Менять правила absent/true/false — в обоих местах согласованно.
 */
final class OutboundUpdateContactPayload
{
    /**
     * По данным контакта из CRM (например ответ crm.contact.get с полями в $crmContactRow)
     * дополняет $post для сайта: рекламный агент передаётся только при явном да/нет;
     * пустые и неоднозначные значения не кладём (сайт не трактует как «выключить агента»).
     *
     * @param array<string, mixed> $post        Тело запроса на сайт (изменяется по ссылке).
     * @param array<string, mixed> $crmContactRow Поля контакта CRM (скаляры или вложенные VALUE из REST).
     */
    public static function mergeAdvertisingMarketingFromCrmContact(array &$post, array $crmContactRow): void
    {
        $ufKey = CrmInboundUfMap::CONTACT_ADVERTISING_AGENT_UF;
        $raw = self::extractCrmFieldScalar($crmContactRow, $ufKey);

        if (CrmInboundUfMap::marketingInboundSignalAbsent($raw)) {
            unset($post[$ufKey], $post['IS_MARKETING_AGENT']);

            return;
        }

        if (CrmInboundUfMap::marketingInboundSignalTrue($raw)) {
            $post['IS_MARKETING_AGENT'] = 'Y';
            $post[$ufKey] = 'Y';

            return;
        }

        if (CrmInboundUfMap::marketingInboundSignalFalse($raw)) {
            $post['IS_MARKETING_AGENT'] = 'N';
            $post[$ufKey] = 'N';

            return;
        }

        unset($post[$ufKey], $post['IS_MARKETING_AGENT']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function extractCrmFieldScalar(array $row, string $key): mixed
    {
        if (!\array_key_exists($key, $row)) {
            return null;
        }

        $v = $row[$key];
        if (\is_array($v)) {
            if (\array_key_exists('VALUE', $v)) {
                return $v['VALUE'];
            }
            $first = \reset($v);
            if (\is_array($first) && \array_key_exists('VALUE', $first)) {
                return $first['VALUE'];
            }
            if (\is_scalar($first)) {
                return $first;
            }

            return null;
        }

        return $v;
    }
}
