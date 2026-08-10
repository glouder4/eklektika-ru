<?php

namespace OnlineService\Catalog;

use Bitrix\Main\Loader;

/**
 * Список методов нанесения для селектора — все значения enum свойства
 * VIDY_NANESENIY_KLASSIFIKATOR из настроек инфоблока (не значения элемента).
 */
final class NanesenieOptionsResolver
{
    public const PROPERTY_CODE = 'VIDY_NANESENIY_KLASSIFIKATOR';
    public const DEFAULT_OPTION = 'Без нанесения';
    public const OFFERS_IBLOCK_ID = 14;
    public const CATALOG_IBLOCK_ID = 13;

    /** @var array<int, string[]> */
    private static array $optionsCache = [];

    /** @var array<int, array<int, array{label: string, xml_id: string}>> */
    private static array $enumEntriesCache = [];

    /**
     * @return array<int, array{label: string, xml_id: string}>
     */
    private static function getEnumEntries(?int $iblockId = null): array
    {
        $cacheKey = $iblockId ?? 0;
        if (isset(self::$enumEntriesCache[$cacheKey])) {
            return self::$enumEntriesCache[$cacheKey];
        }

        if (!Loader::includeModule('iblock')) {
            return self::$enumEntriesCache[$cacheKey] = [];
        }

        $propertyId = self::resolvePropertyId($iblockId);
        if ($propertyId === null) {
            return self::$enumEntriesCache[$cacheKey] = [];
        }

        $entries = [];
        $rsEnum = \CIBlockPropertyEnum::GetList(
            ['SORT' => 'ASC', 'VALUE' => 'ASC'],
            ['PROPERTY_ID' => $propertyId]
        );
        while ($enum = $rsEnum->Fetch()) {
            $label = trim((string)($enum['VALUE'] ?? ''));
            if ($label === '') {
                continue;
            }

            $entries[] = [
                'label' => $label,
                'xml_id' => trim((string)($enum['XML_ID'] ?? '')),
            ];
        }

        return self::$enumEntriesCache[$cacheKey] = $entries;
    }

    public static function resolveXmlIdByLabel(string $label, ?int $iblockId = null): string
    {
        $label = trim($label);
        if ($label === '' || self::isDefaultOption($label)) {
            return '';
        }

        foreach (self::getEnumEntries($iblockId) as $entry) {
            if ($entry['label'] === $label) {
                return $entry['xml_id'];
            }
        }

        foreach (self::getEnumEntries($iblockId) as $entry) {
            if (mb_strtolower($entry['label']) === mb_strtolower($label)) {
                return $entry['xml_id'];
            }
        }

        return '';
    }

    public static function resolveLabelByXmlId(string $xmlId, ?int $iblockId = null): string
    {
        $xmlId = trim($xmlId);
        if ($xmlId === '') {
            return '';
        }

        foreach (self::getEnumEntries($iblockId) as $entry) {
            if ($entry['xml_id'] !== '' && $entry['xml_id'] === $xmlId) {
                return $entry['label'];
            }
        }

        foreach (self::getEnumEntries($iblockId) as $entry) {
            if ($entry['xml_id'] !== '' && mb_strtolower($entry['xml_id']) === mb_strtolower($xmlId)) {
                return $entry['label'];
            }
        }

        return '';
    }

    /**
     * Человекочитаемое значение свойства корзины/заказа для писем и UI.
     */
    public static function formatPropertyValueForDisplay(mixed $raw): string
    {
        $names = [];
        foreach (self::parseNaneseniyaRawValueForExport($raw) as $item) {
            $name = trim((string)($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $names[$name] = $name;
        }

        if ($names === []) {
            $rawStr = trim((string)$raw);
            if ($rawStr !== '' && ($rawStr[0] === '{' || $rawStr[0] === '[')) {
                $decoded = json_decode($rawStr, true);
                if (is_array($decoded)) {
                    $list = array_is_list($decoded) ? $decoded : [$decoded];
                    foreach ($list as $entry) {
                        if (!is_array($entry)) {
                            continue;
                        }
                        $id = trim((string)($entry['id'] ?? $entry['ID'] ?? ''));
                        if ($id === '') {
                            continue;
                        }
                        $label = self::resolveLabelByXmlId($id);
                        if ($label !== '') {
                            $names[$label] = $label;
                        }
                    }
                }
            }
        }

        return implode(', ', array_values($names));
    }

    /**
     * В #ORDER_LIST# подменяет JSON нанесения на названия.
     */
    public static function sanitizeOrderListTextForEmail(string $text): string
    {
        if ($text === '' || (strpos($text, '{') === false && strpos($text, '[') === false)) {
            return $text;
        }

        $labelPattern = '(?:Варианты\s+нанесения|Метод(?:ы)?\s+нанесения|Нанесение)';

        $replaced = preg_replace_callback(
            '/\[\s*(' . $labelPattern . ')\s*:\s*(\{.*?\}|\[.*?\])\s*\]/su',
            static function (array $matches): string {
                $display = self::formatPropertyValueForDisplay($matches[2]);
                if ($display === '') {
                    return '';
                }

                return '[Варианты нанесения: ' . $display . ']';
            },
            $text
        );

        if (!is_string($replaced)) {
            $replaced = $text;
        }

        $replaced = preg_replace_callback(
            '/(^|[>\s])(' . $labelPattern . ')\s*:\s*(\{.*?\}|\[.*?\])/su',
            static function (array $matches): string {
                $display = self::formatPropertyValueForDisplay($matches[3]);
                if ($display === '') {
                    return $matches[1];
                }

                return $matches[1] . 'Варианты нанесения: ' . $display;
            },
            $replaced
        );

        return is_string($replaced) ? $replaced : $text;
    }

    /**
     * @return array{name: string, price: float, id?: string}
     */
    public static function buildNaneseniyaItem(string $label, float $price = 0.0, ?string $xmlId = null): array
    {
        $label = trim($label);
        if ($label === '' || self::isDefaultOption($label)) {
            return [
                'name' => $label,
                'price' => round($price, 2),
            ];
        }

        $xmlId = trim((string)($xmlId ?? ''));
        if ($xmlId === '') {
            $xmlId = self::resolveXmlIdByLabel($label);
        }

        $item = [
            'name' => $label,
            'price' => round($price, 2),
        ];
        if ($xmlId !== '') {
            $item = ['id' => $xmlId] + $item;
        }

        return $item;
    }

    /**
     * Все доступные варианты нанесения из справочника свойства инфоблока.
     *
     * @return string[]
     */
    public static function getAllOptions(?int $iblockId = null): array
    {
        $cacheKey = $iblockId ?? 0;
        if (isset(self::$optionsCache[$cacheKey])) {
            return self::$optionsCache[$cacheKey];
        }

        if (!Loader::includeModule('iblock')) {
            return self::$optionsCache[$cacheKey] = [self::DEFAULT_OPTION];
        }

        $propertyId = self::resolvePropertyId($iblockId);
        if ($propertyId === null) {
            return self::$optionsCache[$cacheKey] = [self::DEFAULT_OPTION];
        }

        $labels = array_column(self::getEnumEntries($iblockId), 'label');

        self::$optionsCache[$cacheKey] = self::buildOptionsList($labels);

        return self::$optionsCache[$cacheKey];
    }

    private static function resolvePropertyId(?int $iblockId): ?int
    {
        if ($iblockId !== null && $iblockId > 0) {
            return self::findPropertyIdInIblock($iblockId);
        }

        foreach ([self::OFFERS_IBLOCK_ID, self::CATALOG_IBLOCK_ID] as $candidateIblockId) {
            $propertyId = self::findPropertyIdInIblock($candidateIblockId);
            if ($propertyId !== null) {
                return $propertyId;
            }
        }

        return null;
    }

    private static function findPropertyIdInIblock(int $iblockId): ?int
    {
        $property = \CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => self::PROPERTY_CODE,
            ]
        )->Fetch();

        if (!is_array($property) || empty($property['ID'])) {
            return null;
        }

        return (int)$property['ID'];
    }

    /**
     * @param string[] $labels
     * @return string[]
     */
    private static function buildOptionsList(array $labels): array
    {
        $labels = array_values(array_unique($labels));
        if ($labels === []) {
            return [self::DEFAULT_OPTION];
        }

        $otherLabels = array_values(array_filter(
            $labels,
            static fn(string $label): bool => $label !== self::DEFAULT_OPTION
        ));

        if (in_array(self::DEFAULT_OPTION, $labels, true)) {
            return array_merge([self::DEFAULT_OPTION], $otherLabels);
        }

        return array_merge([self::DEFAULT_OPTION], $labels);
    }

    /**
     * @param mixed $raw POST nanesenie или nanesenie[]
     * @return string[]
     */
    public static function normalizeSubmittedValues($raw): array
    {
        $values = [];
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $label = trim((string)$item);
                if ($label !== '') {
                    $values[] = $label;
                }
            }
        } else {
            $label = trim((string)$raw);
            if ($label !== '') {
                $values[] = $label;
            }
        }

        $values = array_values(array_unique($values));
        if ($values === []) {
            return [self::DEFAULT_OPTION];
        }

        $withoutDefault = array_values(array_filter(
            $values,
            static fn(string $value): bool => mb_strtolower($value) !== mb_strtolower(self::DEFAULT_OPTION)
        ));

        if ($withoutDefault === []) {
            return [self::DEFAULT_OPTION];
        }

        return $withoutDefault;
    }

    /**
     * @param array<int, array<string, mixed>> $props PROPS / PROPS_ALL строки корзины
     * @return string[]
     */
    public static function extractSelectedFromItemProps(array $props): array
    {
        $values = [];
        foreach ($props as $prop) {
            $propCode = mb_strtoupper((string)($prop['CODE'] ?? ''));
            $propName = (string)($prop['NAME'] ?? '');
            $normalizedPropName = function_exists('mb_strtolower') ? mb_strtolower($propName) : strtolower($propName);
            if (
                $propCode !== 'NANESENIE'
                && $normalizedPropName !== 'нанесение'
                && $normalizedPropName !== 'варианты нанесения'
                && $normalizedPropName !== 'метод нанесения'
                && $normalizedPropName !== 'методы нанесения'
            ) {
                continue;
            }

            $rawValue = trim((string)($prop['VALUE'] ?? ''));
            if ($rawValue === '') {
                continue;
            }

            foreach (self::parseNaneseniyaRawValue($rawValue) as $item) {
                $name = trim((string)($item['name'] ?? ''));
                if ($name !== '') {
                    $values[] = $name;
                }
            }
        }

        return self::normalizeSubmittedValues($values);
    }

    /**
     * @return array<int, array{name: string, price: float, id?: string}>
     */
    public static function parseNaneseniyaRawValue(mixed $raw): array
    {
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $rawStr = trim((string)$raw);
            if ($rawStr === '') {
                return [];
            }

            $decoded = null;
            if ($rawStr[0] === '[' || $rawStr[0] === '{') {
                $decoded = json_decode($rawStr, true);
            }

            if (!is_array($decoded)) {
                if ($rawStr[0] === '[' || $rawStr[0] === '{') {
                    $recovered = self::recoverTruncatedJsonItems($rawStr);
                    if ($recovered !== []) {
                        return $recovered;
                    }
                    return [];
                }
                return [self::buildNaneseniyaItem($rawStr)];
            }
        }

        $result = [];
        $list = array_is_list($decoded) ? $decoded : [$decoded];
        foreach ($list as $item) {
            if (is_string($item) && trim($item) !== '') {
                $result = array_merge($result, self::parseNaneseniyaRawValue($item));
                continue;
            }
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? $item['NAME'] ?? ''));
            $xmlId = trim((string)($item['id'] ?? $item['ID'] ?? ''));
            if ($name !== '' && ($name[0] === '[' || $name[0] === '{')) {
                $nested = json_decode($name, true);
                if (is_array($nested)) {
                    $result = array_merge($result, self::parseNaneseniyaRawValue($nested));
                    continue;
                }
            }

            if ($name === '' && $xmlId !== '') {
                $name = self::resolveLabelByXmlId($xmlId);
            }

            if ($name === '' || self::isDefaultOption($name)) {
                continue;
            }

            $priceRaw = $item['price'] ?? $item['PRICE'] ?? 0.0;
            $price = (float)(is_string($priceRaw) ? str_replace(',', '.', $priceRaw) : $priceRaw);
            $result[] = self::buildNaneseniyaItem(
                $name,
                $price,
                $xmlId
            );
        }

        return $result;
    }

    /**
     * Парсинг для json_naneseniya: включает «Без нанесения».
     *
     * @return array<int, array{name: string, price: float, id?: string}>
     */
    public static function parseNaneseniyaRawValueForExport(mixed $raw): array
    {
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $rawStr = trim((string)$raw);
            if ($rawStr === '') {
                return [];
            }

            if (!($rawStr[0] === '[' || $rawStr[0] === '{')) {
                return [self::buildNaneseniyaItem($rawStr)];
            }

            $decoded = json_decode($rawStr, true);
            if (!is_array($decoded)) {
                $recovered = self::recoverTruncatedJsonItems($rawStr);
                return $recovered !== [] ? $recovered : [];
            }
        }

        $result = [];
        $list = array_is_list($decoded) ? $decoded : [$decoded];
        foreach ($list as $item) {
            if (is_string($item) && trim($item) !== '') {
                $result = array_merge($result, self::parseNaneseniyaRawValueForExport($item));
                continue;
            }
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? $item['NAME'] ?? ''));
            $xmlId = trim((string)($item['id'] ?? $item['ID'] ?? ''));
            if ($name !== '' && ($name[0] === '[' || $name[0] === '{')) {
                $nested = json_decode($name, true);
                if (is_array($nested)) {
                    $result = array_merge($result, self::parseNaneseniyaRawValueForExport($nested));
                    continue;
                }
            }

            if ($name === '' && $xmlId !== '') {
                $name = self::resolveLabelByXmlId($xmlId);
            }

            if ($name === '') {
                continue;
            }

            $priceRaw = $item['price'] ?? $item['PRICE'] ?? 0.0;
            $price = (float)(is_string($priceRaw) ? str_replace(',', '.', $priceRaw) : $priceRaw);
            $result[] = self::buildNaneseniyaItem(
                $name,
                $price,
                $xmlId
            );
        }

        return $result;
    }

    /**
     * @return array{name: string, price: float}
     */
    public static function buildDefaultNaneseniyaExportItem(): array
    {
        return [
            'name' => self::DEFAULT_OPTION,
            'price' => 0.0,
        ];
    }

    /**
     * Извлекает полные JSON-объекты из обрезанной строки массива.
     *
     * @return array<int, array{name: string, price: float, id?: string}>
     */
    private static function recoverTruncatedJsonItems(string $rawStr): array
    {
        $result = [];
        $patterns = [
            '/\{"id":"[^"]+","name":"[^"]*","price":[\d.]+\}/u',
            '/\{"name":"[^"]*","price":[\d.]+,"id":"[^"]+"\}/u',
            '/\{"name":"[^"]*","price":[\d.]+\}/u',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $rawStr, $matches)) {
                continue;
            }
            foreach ($matches[0] as $jsonObject) {
                $item = json_decode($jsonObject, true);
                if (!is_array($item)) {
                    continue;
                }
                $parsed = self::parseNaneseniyaRawValue($item);
                foreach ($parsed as $entry) {
                    $dedupKey = trim((string)($entry['id'] ?? '')) ?: trim((string)($entry['name'] ?? ''));
                    if ($dedupKey !== '') {
                        $result[$dedupKey] = $entry;
                    }
                }
            }
            if ($result !== []) {
                break;
            }
        }

        return array_values($result);
    }

    /**
     * @param string[] $values
     * @return array<int, array<string, mixed>>
     */
    public static function buildBasketPropertyRows(array $values): array
    {
        $values = self::normalizeSubmittedValues($values);
        $propName = 'Варианты нанесения';

        // В VALUE — человекочитаемые названия (для SALE_NEW_ORDER / ORDER_LIST).
        // JSON id/name/price собирается отдельно в свойстве заказа json_naneseniya.
        if (count($values) === 1 && self::isDefaultOption($values[0])) {
            return [[
                'NAME' => $propName,
                'CODE' => 'NANESENIE',
                'VALUE' => $values[0],
                'SORT' => 100,
            ]];
        }

        $rows = [];
        foreach ($values as $value) {
            $label = trim((string)$value);
            if ($label === '' || self::isDefaultOption($label)) {
                continue;
            }
            $rows[] = [
                'NAME' => $propName,
                'CODE' => 'NANESENIE',
                'VALUE' => $label,
                'SORT' => 100,
            ];
        }

        if ($rows === []) {
            return [[
                'NAME' => $propName,
                'CODE' => 'NANESENIE',
                'VALUE' => self::DEFAULT_OPTION,
                'SORT' => 100,
            ]];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $post
     * @return string[]
     */
    public static function collectSubmittedValuesFromRequest(array $post): array
    {
        if (array_key_exists('nanesenie', $post)) {
            return self::normalizeSubmittedValues($post['nanesenie']);
        }

        $values = [];
        foreach ($post as $key => $value) {
            if ($key === 'nanesenie[]' || preg_match('/^nanesenie\[\d*]$/', (string)$key)) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $values[] = $item;
                    }
                } else {
                    $values[] = $value;
                }
            }
        }

        return self::normalizeSubmittedValues($values);
    }

    /**
     * @param \Bitrix\Sale\BasketPropertyCollection|null $propertyCollection
     * @return string[]
     */
    public static function extractSelectedFromPropertyCollection($propertyCollection): array
    {
        if (!$propertyCollection) {
            return [self::DEFAULT_OPTION];
        }

        $props = [];
        foreach ($propertyCollection as $propertyItem) {
            $props[] = [
                'CODE' => $propertyItem->getField('CODE'),
                'NAME' => $propertyItem->getField('NAME'),
                'VALUE' => $propertyItem->getField('VALUE'),
            ];
        }

        return self::extractSelectedFromItemProps($props);
    }

    /**
     * @param \Bitrix\Sale\BasketPropertyCollection|null $propertyCollection
     * @param string[] $values
     */
    public static function applyBasketPropertyCollection($propertyCollection, array $values): void
    {
        if (!$propertyCollection) {
            return;
        }

        $toDelete = [];
        foreach ($propertyCollection as $propertyItem) {
            $code = mb_strtoupper((string)$propertyItem->getField('CODE'));
            if ($code === 'NANESENIE') {
                $toDelete[] = $propertyItem;
            }
        }
        foreach ($toDelete as $propertyItem) {
            $propertyItem->delete();
        }

        foreach (self::buildBasketPropertyRows($values) as $row) {
            $propertyItem = $propertyCollection->createItem();
            $propertyItem->setFields([
                'NAME' => (string)$row['NAME'],
                'CODE' => (string)$row['CODE'],
                'VALUE' => (string)$row['VALUE'],
                'SORT' => (int)$row['SORT'],
            ]);
        }
    }

    /**
     * Разбивает монолитный JSON-массив NANESENIE на отдельные строки корзины.
     *
     * @param \Bitrix\Sale\BasketPropertyCollection|null $propertyCollection
     */
    public static function repackMonolithicNaneseniyaProps($propertyCollection): bool
    {
        if (!$propertyCollection) {
            return false;
        }

        $rawValues = [];
        foreach ($propertyCollection as $propertyItem) {
            if (mb_strtoupper((string)$propertyItem->getField('CODE')) !== 'NANESENIE') {
                continue;
            }
            $rawValues[] = (string)$propertyItem->getField('VALUE');
        }

        if ($rawValues === []) {
            return false;
        }

        $needsRepack = false;
        foreach ($rawValues as $raw) {
            $trim = trim($raw);
            if ($trim !== '' && $trim[0] === '[') {
                $needsRepack = true;
                break;
            }
        }

        if (!$needsRepack && count($rawValues) > 1) {
            return false;
        }

        if (!$needsRepack && count($rawValues) === 1) {
            $trim = trim($rawValues[0]);
            if ($trim !== '' && $trim[0] === '{' && json_decode($trim, true) !== null) {
                return false;
            }
            if ($trim !== '' && $trim[0] !== '{' && $trim[0] !== '[') {
                return false;
            }
        }

        $items = [];
        foreach ($rawValues as $raw) {
            $items = array_merge($items, self::parseNaneseniyaRawValue($raw));
        }
        if ($items === []) {
            return false;
        }

        $toDelete = [];
        foreach ($propertyCollection as $propertyItem) {
            if (mb_strtoupper((string)$propertyItem->getField('CODE')) === 'NANESENIE') {
                $toDelete[] = $propertyItem;
            }
        }
        foreach ($toDelete as $propertyItem) {
            $propertyItem->delete();
        }

        foreach ($items as $item) {
            $name = trim((string)($item['name'] ?? ''));
            if ($name === '' && !empty($item['id'])) {
                $name = self::resolveLabelByXmlId((string)$item['id']);
            }
            if ($name === '') {
                continue;
            }
            $propertyItem = $propertyCollection->createItem();
            $propertyItem->setFields([
                'NAME' => 'Варианты нанесения',
                'CODE' => 'NANESENIE',
                'VALUE' => $name,
                'SORT' => 100,
            ]);
        }

        return true;
    }

    public static function isDefaultOption(string $value): bool
    {
        return mb_strtolower(trim($value)) === mb_strtolower(self::DEFAULT_OPTION);
    }
}
