<?php

namespace OnlineService\Sale;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;

/**
 * Запись json_naneseniya в свойство заказа (D7 + legacy fallback).
 */
final class JsonNaneseniyaPersister
{
    private const PROPERTY_CODE = 'json_naneseniya';

    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPropertyDefinition(int $personTypeId = 1): ?array
    {
        if (!Loader::includeModule('sale')) {
            self::$lastError = 'sale module not loaded';
            return null;
        }

        $row = OrderPropsTable::getList([
            'filter' => [
                '=CODE' => self::PROPERTY_CODE,
                'PERSON_TYPE_ID' => $personTypeId > 0 ? $personTypeId : 1,
            ],
            'limit' => 1,
        ])->fetch();

        if (is_array($row)) {
            return $row;
        }

        return OrderPropsTable::getList([
            'filter' => ['=CODE' => self::PROPERTY_CODE],
            'limit' => 1,
        ])->fetch() ?: null;
    }

    public static function readValue(int $orderId): ?string
    {
        if ($orderId <= 0 || !Loader::includeModule('sale')) {
            return null;
        }

        $row = OrderPropsValueTable::getList([
            'filter' => ['=ORDER_ID' => $orderId, '=CODE' => self::PROPERTY_CODE],
            'select' => ['VALUE'],
            'limit' => 1,
        ])->fetch();

        if (!is_array($row)) {
            $legacy = \CSaleOrderPropsValue::GetList([], [
                'ORDER_ID' => $orderId,
                'CODE' => self::PROPERTY_CODE,
            ])->Fetch();

            if (!is_array($legacy)) {
                return null;
            }

            return (string)($legacy['VALUE'] ?? '');
        }

        return (string)($row['VALUE'] ?? '');
    }

    public static function persist(int $orderId, int $personTypeId, string $jsonValue): bool
    {
        self::$lastError = '';

        if ($orderId <= 0) {
            self::$lastError = 'orderId is empty';
            return false;
        }
        if ($jsonValue === '') {
            self::$lastError = 'jsonValue is empty';
            return false;
        }
        if (!Loader::includeModule('sale')) {
            self::$lastError = 'sale module not loaded';
            return false;
        }

        OrderJsonNaneseniyaProperty::ensureMaxLength();
        OrderPropsValueStorage::ensureValueColumn();

        $propDef = self::findPropertyDefinition($personTypeId);
        if (!$propDef) {
            self::$lastError = 'property json_naneseniya not found in OrderPropsTable';
            return false;
        }

        $propId = (int)$propDef['ID'];
        if ($propId <= 0) {
            self::$lastError = 'property ID is empty';
            return false;
        }

        if (self::persistViaD7($orderId, $propId, $propDef, $jsonValue)) {
            return true;
        }

        if (self::persistViaLegacy($orderId, $propId, $propDef, $jsonValue)) {
            return true;
        }

        if (self::persistViaRawSql($orderId, $propId, $jsonValue)) {
            self::$lastError = '';
            return true;
        }

        if (self::$lastError === '') {
            self::$lastError = 'unknown persist failure';
        }

        return false;
    }

    private static function persistViaRawSql(int $orderId, int $propId, string $jsonValue): bool
    {
        $existing = OrderPropsValueTable::getList([
            'filter' => [
                '=ORDER_ID' => $orderId,
                '=ORDER_PROPS_ID' => $propId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if (!is_array($existing) || (int)($existing['ID'] ?? 0) <= 0) {
            $existing = OrderPropsValueTable::getList([
                'filter' => [
                    '=ORDER_ID' => $orderId,
                    '=CODE' => self::PROPERTY_CODE,
                ],
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();
        }

        $valueId = is_array($existing) ? (int)($existing['ID'] ?? 0) : 0;
        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();

        try {
            if ($valueId > 0) {
                $connection->queryExecute(
                    'UPDATE b_sale_order_props_value SET VALUE = \'' . $helper->forSql($jsonValue)
                    . '\' WHERE ID = ' . $valueId
                );
                return true;
            }

            $propDef = OrderPropsTable::getList([
                'filter' => ['=ID' => $propId],
                'select' => ['NAME'],
                'limit' => 1,
            ])->fetch();

            $connection->queryExecute(
                'INSERT INTO b_sale_order_props_value (ORDER_ID, ORDER_PROPS_ID, NAME, CODE, VALUE) VALUES ('
                . $orderId . ', ' . $propId . ', \''
                . $helper->forSql((string)($propDef['NAME'] ?? self::PROPERTY_CODE)) . '\', \''
                . $helper->forSql(self::PROPERTY_CODE) . '\', \''
                . $helper->forSql($jsonValue) . '\')'
            );
            return true;
        } catch (\Throwable $e) {
            self::$lastError = 'Raw SQL: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * @param array<string, mixed> $propDef
     */
    private static function persistViaD7(int $orderId, int $propId, array $propDef, string $jsonValue): bool
    {
        $existing = OrderPropsValueTable::getList([
            'filter' => [
                '=ORDER_ID' => $orderId,
                '=ORDER_PROPS_ID' => $propId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if (is_array($existing) && (int)($existing['ID'] ?? 0) > 0) {
            $result = OrderPropsValueTable::update((int)$existing['ID'], [
                'VALUE' => $jsonValue,
            ]);
            if ($result->isSuccess()) {
                return true;
            }
            self::$lastError = 'D7 update: ' . implode('; ', $result->getErrorMessages());
            return false;
        }

        $existingByCode = OrderPropsValueTable::getList([
            'filter' => [
                '=ORDER_ID' => $orderId,
                '=CODE' => self::PROPERTY_CODE,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if (is_array($existingByCode) && (int)($existingByCode['ID'] ?? 0) > 0) {
            $result = OrderPropsValueTable::update((int)$existingByCode['ID'], [
                'VALUE' => $jsonValue,
                'ORDER_PROPS_ID' => $propId,
            ]);
            if ($result->isSuccess()) {
                return true;
            }
            self::$lastError = 'D7 update by code: ' . implode('; ', $result->getErrorMessages());
            return false;
        }

        $result = OrderPropsValueTable::add([
            'ORDER_ID' => $orderId,
            'ORDER_PROPS_ID' => $propId,
            'NAME' => (string)($propDef['NAME'] ?? self::PROPERTY_CODE),
            'CODE' => self::PROPERTY_CODE,
            'VALUE' => $jsonValue,
        ]);

        if ($result->isSuccess()) {
            return true;
        }

        self::$lastError = 'D7 add: ' . implode('; ', $result->getErrorMessages());
        return false;
    }

    /**
     * @param array<string, mixed> $propDef
     */
    private static function persistViaLegacy(int $orderId, int $propId, array $propDef, string $jsonValue): bool
    {
        global $APPLICATION;

        $existing = \CSaleOrderPropsValue::GetList([], [
            'ORDER_ID' => $orderId,
            'ORDER_PROPS_ID' => $propId,
        ])->Fetch();

        if (!$existing) {
            $existing = \CSaleOrderPropsValue::GetList([], [
                'ORDER_ID' => $orderId,
                'CODE' => self::PROPERTY_CODE,
            ])->Fetch();
        }

        if ($existing) {
            $ok = (bool)\CSaleOrderPropsValue::Update((int)$existing['ID'], [
                'VALUE' => $jsonValue,
            ]);
            if (!$ok) {
                $ex = $APPLICATION->GetException();
                $legacyError = 'Legacy update: ' . ($ex ? $ex->GetString() : 'failed');
                self::$lastError = self::$lastError !== '' ? self::$lastError . ' | ' . $legacyError : $legacyError;
            }
            return $ok;
        }

        $newId = (int)\CSaleOrderPropsValue::Add([
            'ORDER_ID' => $orderId,
            'ORDER_PROPS_ID' => $propId,
            'NAME' => (string)($propDef['NAME'] ?? self::PROPERTY_CODE),
            'CODE' => self::PROPERTY_CODE,
            'VALUE' => $jsonValue,
        ]);

        if ($newId > 0) {
            return true;
        }

        $ex = $APPLICATION->GetException();
        self::$lastError = 'Legacy add: ' . ($ex ? $ex->GetString() : 'failed');
        return false;
    }
}
