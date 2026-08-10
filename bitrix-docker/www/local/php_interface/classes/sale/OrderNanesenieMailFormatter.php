<?php

namespace OnlineService\Sale;

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;
use OnlineService\Catalog\NanesenieOptionsResolver;

/**
 * Обогащение полей почтовых событий sale (состав заказа / причина смены статуса).
 * Не должно влиять на факт отправки: исключения глотаются, false не возвращаем.
 */
final class OrderNanesenieMailFormatter
{
    private const FIELD_KEYS = [
        'ORDER_LIST',
        'ORDER_LIST_HTML',
        'ORDER_TABLE_ITEMS',
        'BASKET_LIST',
        'START_ITEMS',
        'PRODUCTS_LIST',
    ];

    /**
     * Точка входа из sale:OnOrderStatusSendEmail.
     *
     * @param array<string, mixed> $fields
     */
    public static function enrichStatusChangedMail($orderId, $eventName, array &$fields, $statusId = null): void
    {
        $name = is_string($eventName) ? $eventName : '';
        if ($name === '' || strncmp($name, 'SALE_STATUS_CHANGED_', 20) !== 0) {
            return;
        }

        if (!isset($fields['ORDER_REAL_ID']) && (int)$orderId > 0) {
            $fields['ORDER_REAL_ID'] = (int)$orderId;
        }

        self::enrichStatusChangedFields($fields);
        self::sanitizeMailFields($fields);
    }

    /**
     * @deprecated оставить совместимость со старой регистрацией AddEventHandler на метод класса
     * @param array<string, mixed>|mixed $fields
     */
    public static function onOrderStatusSendEmail($orderId, &$eventName, &$fields, $statusId = null)
    {
        try {
            if (is_array($fields)) {
                self::enrichStatusChangedMail($orderId, $eventName, $fields, $statusId);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // не возвращаем false
    }

    /**
     * @deprecated больше не регистрируем на OnBeforeEventAdd (могло рвать отправку)
     * @param array<string, mixed>|mixed $fields
     */
    public static function onBeforeEventAdd(&$event, &$lid, &$fields, &$messageId = null, &$files = null, &$languageId = null)
    {
        // no-op: не трогаем цепочку CEvent::Send
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function sanitizeMailFields(array &$fields): void
    {
        foreach (self::FIELD_KEYS as $key) {
            if (!isset($fields[$key]) || !is_string($fields[$key]) || $fields[$key] === '') {
                continue;
            }
            $fields[$key] = NanesenieOptionsResolver::sanitizeOrderListTextForEmail($fields[$key]);
        }
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function enrichStatusChangedFields(array &$fields): void
    {
        $fields['ORDER_CHANGE_REASON'] = self::buildChangeReasonHtml($fields);

        $existingList = isset($fields['ORDER_LIST']) && is_string($fields['ORDER_LIST'])
            ? trim($fields['ORDER_LIST'])
            : '';
        if ($existingList === '') {
            $orderList = self::buildOrderListHtml($fields);
            $fields['ORDER_LIST'] = $orderList !== '' ? $orderList : '—';
        }

        if (!isset($fields['PRICE']) || trim((string)$fields['PRICE']) === '') {
            $price = self::buildOrderPriceFormatted($fields);
            $fields['PRICE'] = $price !== '' ? $price : '—';
        }

        $text = isset($fields['TEXT']) ? trim((string)$fields['TEXT']) : '';
        if ($text === '' && !empty($fields['ORDER_CHANGE_REASON'])) {
            $fields['TEXT'] = (string)$fields['ORDER_CHANGE_REASON'];
        }

        foreach (['ORDER_LIST', 'ORDER_CHANGE_REASON', 'PRICE', 'TEXT'] as $key) {
            if (!isset($fields[$key]) || !is_string($fields[$key]) || $fields[$key] === '') {
                continue;
            }
            $htmlKey = 'HTML_' . $key;
            if (!isset($fields[$htmlKey]) || trim((string)$fields[$htmlKey]) === '') {
                $fields[$htmlKey] = $fields[$key];
            }
        }
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function buildChangeReasonHtml(array $fields): string
    {
        $parts = [];

        $status = trim((string)($fields['ORDER_STATUS'] ?? ''));
        if ($status !== '') {
            $safe = function_exists('htmlspecialcharsbx') ? htmlspecialcharsbx($status) : htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $parts[] = 'Новый статус: <b>' . $safe . '</b>';
        }

        $description = trim((string)($fields['ORDER_DESCRIPTION'] ?? ''));
        if ($description !== '') {
            $parts[] = function_exists('htmlspecialcharsbx')
                ? htmlspecialcharsbx($description)
                : htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $text = trim((string)($fields['TEXT'] ?? ''));
        if ($text !== '') {
            $parts[] = 'Комментарий: ' . $text;
        }

        if ($parts === []) {
            return 'Изменён статус заказа.';
        }

        return implode('<br />', $parts);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function buildOrderListHtml(array $fields): string
    {
        $order = self::resolveOrder($fields);
        if ($order === null) {
            return '';
        }

        $basket = $order->getBasket();
        if ($basket === null) {
            return '';
        }

        $lines = $basket->getListOfFormatText();
        if (!is_array($lines) || $lines === []) {
            return '';
        }

        return NanesenieOptionsResolver::sanitizeOrderListTextForEmail(implode('<br />', $lines));
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function buildOrderPriceFormatted(array $fields): string
    {
        $order = self::resolveOrder($fields);
        if ($order === null || !function_exists('SaleFormatCurrency')) {
            return '';
        }

        return (string)SaleFormatCurrency($order->getPrice(), $order->getCurrency());
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function resolveOrder(array $fields): ?Order
    {
        if (!Loader::includeModule('sale')) {
            return null;
        }

        $realId = (int)($fields['ORDER_REAL_ID'] ?? 0);
        if ($realId > 0) {
            $order = Order::load($realId);
            if ($order) {
                return $order;
            }
        }

        $accountNumber = trim((string)($fields['ORDER_ID'] ?? ''));
        if ($accountNumber === '') {
            return null;
        }

        if (method_exists(Order::class, 'loadByAccountNumber')) {
            $order = Order::loadByAccountNumber($accountNumber);
            if ($order) {
                return $order;
            }
        }

        if (ctype_digit($accountNumber)) {
            $order = Order::load((int)$accountNumber);
            if ($order) {
                return $order;
            }
        }

        return null;
    }
}
