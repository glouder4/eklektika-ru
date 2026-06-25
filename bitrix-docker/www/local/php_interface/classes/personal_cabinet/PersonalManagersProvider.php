<?php

use Bitrix\Main\Loader;
use CompositeProperties\Iblock\IblockEntityValueRepository;
use OnlineService\B24\UserSync\Config\UserSyncConfig;
use OnlineService\Site\Config\ManagerCompositeConfig;
use OnlineService\Site\ManagerCompositeFieldResolver;

/**
 * Карточки персональных менеджеров для личного кабинета.
 */
final class PersonalManagersProvider
{
    /**
     * @return list<array{
     *   SLOT: int,
     *   ID: int,
     *   NAME: string,
     *   PREVIEW_SRC: string,
     *   WORK_POSITION: string,
     *   PHONE: string,
     *   EMAIL: string,
     *   SOCIAL_LINKS: list<array{TYPE: string, URL: string}>
     * }>
     */
    public static function loadForUserId(int $userId): array
    {
        if ($userId <= 0 || !Loader::includeModule('iblock')) {
            return [];
        }

        $managerRefs = self::loadUserManagerReferences($userId);
        $slots = [];

        foreach ([1 => UserSyncConfig::USER_UF_PERSONAL_MANAGER_1, 2 => UserSyncConfig::USER_UF_PERSONAL_MANAGER_2] as $slotIndex => $ufKey) {
            $reference = (int)($managerRefs[$ufKey] ?? 0);
            if ($reference <= 0) {
                continue;
            }

            $elementId = self::resolveManagerElementId($reference);
            if ($elementId <= 0) {
                continue;
            }

            $card = self::loadManagerCard($elementId, $slotIndex);
            if ($card !== null) {
                $slots[] = $card;
            }
        }

        return $slots;
    }

    /**
     * @return array<string, int> ufKey => raw reference (element ID or CRM BITRIX24_ID)
     */
    private static function loadUserManagerReferences(int $userId): array
    {
        $ufKeys = [
            UserSyncConfig::USER_UF_PERSONAL_MANAGER_1,
            UserSyncConfig::USER_UF_PERSONAL_MANAGER_2,
        ];
        $result = array_fill_keys($ufKeys, 0);

        global $USER_FIELD_MANAGER;
        if (\is_object($USER_FIELD_MANAGER)) {
            $lang = \defined('LANGUAGE_ID') ? (string)LANGUAGE_ID : 'ru';
            $userFields = $USER_FIELD_MANAGER->GetUserFields('USER', $userId, $lang);
            foreach ($ufKeys as $ufKey) {
                $raw = $userFields[$ufKey]['VALUE'] ?? null;
                $result[$ufKey] = self::unwrapElementId($raw);
            }

            return $result;
        }

        $userRow = \CUser::GetList(
            'ID',
            'ASC',
            ['ID' => $userId],
            ['FIELDS' => ['ID'], 'SELECT' => $ufKeys]
        )->Fetch();
        if (!\is_array($userRow)) {
            return $result;
        }

        foreach ($ufKeys as $ufKey) {
            $result[$ufKey] = self::unwrapElementId($userRow[$ufKey] ?? null);
        }

        return $result;
    }

    /**
     * UF может хранить ID элемента ИБ или CRM-референс (BITRIX24_ID свойства карточки).
     */
    private static function resolveManagerElementId(int $reference): int
    {
        if ($reference <= 0) {
            return 0;
        }

        $iblockId = UserSyncConfig::MANAGER_CARD_IBLOCK_ID;

        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $reference],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($row = $rs->Fetch()) {
            $id = (int)($row['ID'] ?? 0);

            return $id > 0 ? $id : 0;
        }

        $prop = UserSyncConfig::MANAGER_CARD_BITRIX24_PROPERTY_CODE;
        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'PROPERTY_' . $prop => $reference],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($row = $rs->Fetch()) {
            $id = (int)($row['ID'] ?? 0);

            return $id > 0 ? $id : 0;
        }

        return 0;
    }

    /**
     * @return array{
     *   SLOT: int,
     *   ID: int,
     *   NAME: string,
     *   PREVIEW_SRC: string,
     *   WORK_POSITION: string,
     *   PHONE: string,
     *   EMAIL: string,
     *   SOCIAL_LINKS: list<array{TYPE: string, URL: string}>
     * }|null
     */
    private static function loadManagerCard(int $elementId, int $slot): ?array
    {
        $iblockId = UserSyncConfig::MANAGER_CARD_IBLOCK_ID;

        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $elementId],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'PREVIEW_PICTURE']
        );
        $element = $rs->GetNextElement();
        if ($element === false) {
            return null;
        }

        $fields = $element->GetFields();
        $props = $element->GetProperties();
        if (!\is_array($fields)) {
            return null;
        }

        $previewSrc = '';
        if (!empty($fields['PREVIEW_PICTURE'])) {
            $previewSrc = (string)\CFile::GetPath($fields['PREVIEW_PICTURE']);
        }

        return [
            'SLOT' => $slot,
            'ID' => (int)($fields['ID'] ?? 0),
            'NAME' => \trim((string)($fields['NAME'] ?? '')),
            'PREVIEW_SRC' => $previewSrc,
            'WORK_POSITION' => self::readPropertyValue($elementId, $iblockId, $props, ['WORK_POSITION', 'WORKPOSITION']),
            'PHONE' => self::readPropertyValue($elementId, $iblockId, $props, ['PHONE']),
            'EMAIL' => self::readPropertyValue($elementId, $iblockId, $props, ['EMAIL']),
            'SOCIAL_LINKS' => self::loadCompositeSocialLinks($elementId, $iblockId),
        ];
    }

    /**
     * @param array<string, mixed> $props результат {@see \CIBlockElement::GetProperties()}
     * @param list<string> $codes
     */
    private static function readPropertyValue(int $elementId, int $iblockId, array $props, array $codes): string
    {
        foreach ($codes as $code) {
            $scalar = self::readSinglePropertyValue($elementId, $iblockId, $props, $code);
            if ($scalar !== '') {
                return $scalar;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $props
     */
    private static function readSinglePropertyValue(int $elementId, int $iblockId, array $props, string $code): string
    {
        if (isset($props[$code])) {
            $entry = $props[$code];
            if (\is_array($entry)) {
                $value = $entry['VALUE'] ?? '';
                if (\is_array($value)) {
                    $value = \reset($value);
                }
                $scalar = \is_scalar($value) ? \trim((string)$value) : '';
                if ($scalar !== '') {
                    return $scalar;
                }
            } elseif (\is_scalar($entry)) {
                $scalar = \trim((string)$entry);
                if ($scalar !== '') {
                    return $scalar;
                }
            }
        }

        return self::readPropertyValueViaGetProperty($iblockId, $elementId, $code);
    }

    private static function readPropertyValueViaGetProperty(int $iblockId, int $elementId, string $code): string
    {
        if ($elementId <= 0 || $iblockId <= 0 || $code === '') {
            return '';
        }

        $rs = \CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['CODE' => $code]);
        if (!\is_object($rs)) {
            return '';
        }

        while ($row = $rs->Fetch()) {
            if (!\is_array($row)) {
                continue;
            }
            $value = $row['VALUE'] ?? '';
            if (\is_array($value)) {
                $value = \reset($value);
            }
            $scalar = \is_scalar($value) ? \trim((string)$value) : '';
            if ($scalar !== '') {
                return $scalar;
            }
        }

        return '';
    }

    /**
     * @return list<array{TYPE: string, URL: string}>
     */
    private static function loadCompositeSocialLinks(int $elementId, int $iblockId): array
    {
        if (!Loader::includeModule('amiiyaproduction.compositeproperties')) {
            return [];
        }

        Loader::includeModule('eklektika.company');

        $resolver = new ManagerCompositeFieldResolver();
        $meta = $resolver->resolveSocialRepeaterMeta();
        if ($meta === null) {
            return [];
        }

        $repo = new IblockEntityValueRepository();
        $fieldId = (int)$meta['field_id'];
        $values = $repo->loadValuesMap(
            $iblockId,
            IblockEntityValueRepository::ENTITY_ELEMENT,
            $elementId,
            [$fieldId]
        );

        $json = \trim((string)($values[$fieldId] ?? ''));
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!\is_array($decoded) || !isset($decoded['items']) || !\is_array($decoded['items'])) {
            return [];
        }

        $typeCode = ManagerCompositeConfig::INNER_TYPE_CODE;
        $linkCode = ManagerCompositeConfig::INNER_LINK_CODE;
        $links = [];

        foreach ($decoded['items'] as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $type = \strtoupper(\trim((string)($row[$typeCode] ?? '')));
            $url = \trim((string)($row[$linkCode] ?? ''));
            if ($type === '' || $url === '') {
                continue;
            }
            $links[] = ['TYPE' => $type, 'URL' => $url];
        }

        return $links;
    }

    private static function unwrapElementId(mixed $raw): int
    {
        if (\is_array($raw)) {
            if (isset($raw['VALUE'])) {
                $raw = $raw['VALUE'];
            } else {
                $raw = \reset($raw);
            }
        }
        if (\is_array($raw)) {
            $raw = \reset($raw);
        }

        return (int)(string)$raw;
    }
}
