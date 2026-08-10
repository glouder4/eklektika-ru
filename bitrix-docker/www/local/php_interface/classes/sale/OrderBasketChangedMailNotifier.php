<?php

namespace OnlineService\Sale;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use OnlineService\Catalog\NanesenieOptionsResolver;

/**
 * Письмо покупателю при изменении состава/количества/нанесения заказа.
 * Штатного SALE-события на это нет — свой тип SALE_ORDER_BASKET_CHANGED. 
 */
final class OrderBasketChangedMailNotifier
{
    public const EVENT_NAME = 'SALE_ORDER_BASKET_CHANGED';

    /** @var array<int, array{changes: list<string>, order_list: string, price: string}> */
    private static array $pending = [];

    /** @var array<int, string> снимок json_naneseniya до save */
    private static array $nanesenieSnapshot = [];

    /**
     * sale:OnSaleOrderBeforeSaved — пока у позиций ещё есть originalValues.
     */
    public static function onOrderBeforeSaved(Event $event): void
    {
        try {
            $order = $event->getParameter('ENTITY');
            if (!$order instanceof Order || $order->isNew()) {
                return;
            }

            $orderId = (int)$order->getId();
            self::$nanesenieSnapshot[$orderId] = self::readJsonNaneseniyaRaw($order, true);

            $diff = self::buildBasketDiff($order);
            if ($diff === null) {
                return;
            }

            self::$pending[$orderId] = $diff;
        } catch (\Throwable $e) {
            // не ломаем сохранение заказа
        }
    }

    /**
     * sale:OnSaleOrderSaved — отправляем одно письмо на заказ.
     */
    public static function onOrderSaved(Event $event): void
    {
        try {
            $isNew = (bool)$event->getParameter('IS_NEW');
            if ($isNew) {
                return;
            }

            $order = $event->getParameter('ENTITY');
            if (!$order instanceof Order) {
                return;
            }

            $orderId = (int)$order->getId();
            if ($orderId <= 0) {
                return;
            }

            $diff = self::$pending[$orderId] ?? null;
            unset(self::$pending[$orderId]);

            // Если qty не менялся, но поменялось нанесение/цены в json_naneseniya
            $oldJson = self::$nanesenieSnapshot[$orderId] ?? null;
            unset(self::$nanesenieSnapshot[$orderId]);
            $newJson = self::readJsonNaneseniyaRaw($order, false);
            if ($oldJson !== null && $oldJson !== $newJson) {
                $nanesenieChanges = self::diffNaneseniyaJson($oldJson, $newJson, $order->getCurrency());
                if ($nanesenieChanges !== []) {
                    if ($diff === null) {
                        $diff = [
                            'changes' => [],
                            'order_list' => self::buildCurrentOrderList($order),
                            'price' => self::formatMoney((float)$order->getPrice(), $order->getCurrency()),
                        ];
                    }
                    $diff['changes'] = array_merge($diff['changes'], $nanesenieChanges);
                    $diff['order_list'] = self::buildCurrentOrderList($order);
                    $diff['price'] = self::formatMoney((float)$order->getPrice(), $order->getCurrency());
                }
            }

            if ($diff === null || $diff['changes'] === []) {
                return;
            }

            // Актуальный состав после save — с нанесениями и ценами из json_naneseniya.
            $diff['order_list'] = self::buildCurrentOrderList($order);
            $diff['price'] = self::formatMoney((float)$order->getPrice(), $order->getCurrency());

            self::sendMail($order, $diff);
        } catch (\Throwable $e) {
            // не ломаем сохранение
        }
    }

    /**
     * @return array{changes: list<string>, order_list: string, price: string}|null
     */
    private static function buildBasketDiff(Order $order): ?array
    {
        $basket = $order->getBasket();
        if ($basket === null) {
            return null;
        }

        $changes = [];
        $lines = [];
        $currency = (string)$order->getCurrency();

        /** @var BasketItem $item */
        foreach ($basket as $item) {
            $name = trim((string)$item->getField('NAME'));
            if ($name === '') {
                $name = 'Товар #' . (int)$item->getProductId();
            }

            $qty = (float)$item->getQuantity();
            $price = (float)$item->getPrice();
            $measure = (string)($item->getField('MEASURE_NAME') ?: 'шт');
            $nanesenieNow = self::getItemNanesenieLabel($item, true, $currency);

            if (method_exists($item, 'isDeleted') && $item->isDeleted()) {
                $changes[] = 'Удалён: ' . $name;
                continue;
            }

            $isNewItem = ((int)$item->getId() <= 0) || (method_exists($item, 'isNew') && $item->isNew());
            if ($isNewItem) {
                $line = 'Добавлен: ' . $name . ' × ' . self::formatQty($qty) . ' ' . $measure;
                if ($nanesenieNow !== '') {
                    $line .= ' (нанесение: ' . $nanesenieNow . ')';
                }
                $changes[] = $line;
                $lines[] = self::formatLine($name, $qty, $measure, $price, $currency, $nanesenieNow);
                continue;
            }

            $original = self::getItemOriginalValues($item);

            if (array_key_exists('QUANTITY', $original)) {
                $oldQty = (float)$original['QUANTITY'];
                if (abs($oldQty - $qty) > 0.00001) {
                    $changes[] = $name . ': количество '
                        . self::formatQty($oldQty) . ' → ' . self::formatQty($qty) . ' ' . $measure;
                }
            }

            if (array_key_exists('PRICE', $original)) {
                $oldPrice = (float)$original['PRICE'];
                if (abs($oldPrice - $price) > 0.00001) {
                    $changes[] = $name . ': цена '
                        . self::formatMoney($oldPrice, $currency)
                        . ' → '
                        . self::formatMoney($price, $currency);
                }
            }

            $nanesenieOld = self::getItemNanesenieLabel($item, false, $currency);
            if ($nanesenieOld !== $nanesenieNow) {
                $changes[] = $name . ': нанесение '
                    . ($nanesenieOld !== '' ? $nanesenieOld : '—')
                    . ' → '
                    . ($nanesenieNow !== '' ? $nanesenieNow : '—');
            }

            $lines[] = self::formatLine($name, $qty, $measure, $price, $currency, $nanesenieNow);
        }

        if ($changes === []) {
            return null;
        }

        return [
            'changes' => $changes,
            'order_list' => implode('<br />', $lines),
            'price' => self::formatMoney((float)$order->getPrice(), $currency),
        ];
    }

    private static function buildCurrentOrderList(Order $order): string
    {
        $basket = $order->getBasket();
        if ($basket === null) {
            return '—';
        }

        $currency = (string)$order->getCurrency();
        $jsonMap = self::nanesenieByProductKeyFromJson(
            self::readJsonNaneseniyaRaw($order, false),
            $currency
        );

        $lines = [];
        /** @var BasketItem $item */
        foreach ($basket as $item) {
            if (method_exists($item, 'isDeleted') && $item->isDeleted()) {
                continue;
            }
            $name = trim((string)$item->getField('NAME'));
            if ($name === '') {
                $name = 'Товар #' . (int)$item->getProductId();
            }

            $nanesenie = self::getItemNanesenieLabel($item, true, $currency);
            if ($nanesenie === '' || !self::labelHasPrices($nanesenie)) {
                $fromJson = self::lookupNanesenieForBasketItem($item, $jsonMap);
                if ($fromJson !== '') {
                    $nanesenie = $fromJson;
                }
            }

            $lines[] = self::formatLine(
                $name,
                (float)$item->getQuantity(),
                (string)($item->getField('MEASURE_NAME') ?: 'шт'),
                (float)$item->getPrice(),
                $currency,
                $nanesenie
            );
        }

        return $lines !== [] ? implode('<br />', $lines) : '—';
    }

    /** @return array<string, mixed> */
    private static function getItemOriginalValues(BasketItem $item): array
    {
        if (!method_exists($item, 'getFields')) {
            return [];
        }
        $fields = $item->getFields();
        if (!is_object($fields) || !method_exists($fields, 'getOriginalValues')) {
            return [];
        }
        $original = $fields->getOriginalValues();

        return is_array($original) ? $original : [];
    }

    /**
     * @param bool $current true = текущее VALUE, false = original VALUE свойства NANESENIE
     */
    private static function getItemNanesenieLabel(BasketItem $item, bool $current, string $currency = 'RUB'): string
    {
        $props = $item->getPropertyCollection();
        if (!$props) {
            return '';
        }

        $labels = [];
        foreach ($props as $propItem) {
            $code = mb_strtoupper(trim((string)$propItem->getField('CODE')));
            $propName = function_exists('mb_strtolower')
                ? mb_strtolower(trim((string)$propItem->getField('NAME')))
                : strtolower(trim((string)$propItem->getField('NAME')));
            $isNanesenie = $code === 'NANESENIE'
                || in_array($propName, [
                    'нанесение',
                    'варианты нанесения',
                    'метод нанесения',
                    'методы нанесения',
                ], true);
            if (!$isNanesenie) {
                continue;
            }

            $raw = $propItem->getField('VALUE');
            if (!$current && method_exists($propItem, 'getFields')) {
                $pf = $propItem->getFields();
                if (is_object($pf) && method_exists($pf, 'getOriginalValues')) {
                    $orig = $pf->getOriginalValues();
                    if (is_array($orig) && array_key_exists('VALUE', $orig)) {
                        $raw = $orig['VALUE'];
                    }
                }
            }

            $chunk = self::formatNanesenieRaw($raw, $currency);
            if ($chunk !== '') {
                $labels[] = $chunk;
            }
        }

        return implode('; ', $labels);
    }

    private static function formatNanesenieRaw(mixed $raw, string $currency): string
    {
        $priced = [];
        foreach (NanesenieOptionsResolver::parseNaneseniyaRawValueForExport($raw) as $row) {
            $n = trim((string)($row['name'] ?? ''));
            if ($n === '' || NanesenieOptionsResolver::isDefaultOption($n)) {
                continue;
            }
            $p = (float)($row['price'] ?? 0);
            $priced[] = $p > 0.00001
                ? $n . ' (' . self::formatMoney($p, $currency) . ')'
                : $n;
        }

        if ($priced !== []) {
            return implode(', ', $priced);
        }

        $display = NanesenieOptionsResolver::formatPropertyValueForDisplay($raw);
        if ($display !== '' && !NanesenieOptionsResolver::isDefaultOption($display)) {
            return $display;
        }

        $fallback = trim((string)$raw);
        if ($fallback === '' || NanesenieOptionsResolver::isDefaultOption($fallback)) {
            return '';
        }
        if ($fallback[0] === '{' || $fallback[0] === '[') {
            return '';
        }

        return $fallback;
    }

    private static function labelHasPrices(string $label): bool
    {
        return str_contains($label, '(') && (str_contains($label, '₽') || str_contains($label, 'RUB'));
    }

    /**
     * @return array<string, string> product/xml key => "name (price), ..."
     */
    private static function nanesenieByProductKeyFromJson(string $json, string $currency): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = $decoded;
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $rows = $decoded['items'];
        } elseif (isset($decoded['NANESENIE']) && is_array($decoded['NANESENIE'])) {
            $rows = [$decoded];
        }

        $map = [];
        foreach ($rows as $block) {
            if (!is_array($block)) {
                continue;
            }
            $keys = [];
            foreach (['id', 'offer_id', 'product_id', 'PRODUCT_ID'] as $k) {
                $v = trim((string)($block[$k] ?? ''));
                if ($v !== '') {
                    $keys[] = $v;
                }
            }
            $nanesenie = $block['NANESENIE'] ?? $block['nanesenie'] ?? null;
            if (!is_array($nanesenie)) {
                continue;
            }
            $label = self::formatNanesenieRaw($nanesenie, $currency);
            if ($label === '' || $keys === []) {
                continue;
            }
            foreach ($keys as $key) {
                $map[$key] = $label;
            }
        }

        return $map;
    }

    /**
     * @param array<string, string> $jsonMap
     */
    private static function lookupNanesenieForBasketItem(BasketItem $item, array $jsonMap): string
    {
        if ($jsonMap === []) {
            return '';
        }

        $candidates = [(string)(int)$item->getProductId()];
        $xmlId = trim((string)$item->getField('PRODUCT_XML_ID'));
        if ($xmlId !== '') {
            $candidates[] = $xmlId;
            // Иногда в json id — XML_ID родителя до #
            if (str_contains($xmlId, '#')) {
                $candidates[] = explode('#', $xmlId, 2)[0];
            }
        }

        foreach ($candidates as $key) {
            if ($key !== '' && isset($jsonMap[$key]) && $jsonMap[$key] !== '') {
                return $jsonMap[$key];
            }
        }

        // Если в заказе один блок нанесений — берём его.
        if (count($jsonMap) === 1) {
            return (string)reset($jsonMap);
        }

        return '';
    }

    private static function readJsonNaneseniyaRaw(Order $order, bool $preferOriginal): string
    {
        $orderId = (int)$order->getId();
        $collection = $order->getPropertyCollection();
        if ($collection) {
            foreach ($collection as $prop) {
                if ((string)$prop->getField('CODE') !== 'json_naneseniya') {
                    continue;
                }
                $raw = (string)$prop->getField('VALUE');
                if ($preferOriginal && method_exists($prop, 'getFields')) {
                    $fields = $prop->getFields();
                    if (is_object($fields) && method_exists($fields, 'getOriginalValues')) {
                        $orig = $fields->getOriginalValues();
                        if (is_array($orig) && array_key_exists('VALUE', $orig)) {
                            return (string)$orig['VALUE'];
                        }
                    }
                    // originalValues нет — до save в БД ещё старое значение
                    $fromDb = JsonNaneseniyaPersister::readValue($orderId);

                    return is_string($fromDb) ? $fromDb : $raw;
                }

                if ($raw !== '') {
                    return $raw;
                }
            }
        }

        $fromDb = JsonNaneseniyaPersister::readValue($orderId);

        return is_string($fromDb) ? $fromDb : '';
    }

    /**
     * @return list<string>
     */
    private static function diffNaneseniyaJson(string $oldJson, string $newJson, string $currency): array
    {
        $oldMap = self::flattenNaneseniyaJson($oldJson, $currency);
        $newMap = self::flattenNaneseniyaJson($newJson, $currency);
        $changes = [];

        $keys = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
        sort($keys);
        foreach ($keys as $key) {
            $old = $oldMap[$key] ?? null;
            $new = $newMap[$key] ?? null;
            if ($old === $new) {
                continue;
            }
            if ($old === null) {
                $changes[] = 'Нанесение добавлено: ' . $new;
                continue;
            }
            if ($new === null) {
                $changes[] = 'Нанесение убрано: ' . $old;
                continue;
            }
            $changes[] = 'Нанесение изменено: ' . $old . ' → ' . $new;
        }

        if ($changes === [] && $oldJson !== $newJson) {
            $changes[] = 'Обновлены варианты/цены нанесения';
        }

        return $changes;
    }

    /**
     * @return array<string, string> key => "offer: name (price)"
     */
    private static function flattenNaneseniyaJson(string $json, string $currency = 'RUB'): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $label = self::formatNanesenieRaw($json, $currency);

            return $label !== '' ? ['global' => $label] : [];
        }

        $rows = $decoded;
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $rows = $decoded['items'];
        }

        $map = [];
        $list = array_is_list($rows) ? $rows : [$rows];
        foreach ($list as $block) {
            if (!is_array($block)) {
                continue;
            }
            $offer = trim((string)($block['offer_id'] ?? $block['id'] ?? 'item'));
            $nanesenieRows = $block['NANESENIE'] ?? $block['nanesenie'] ?? null;
            if ($nanesenieRows === null) {
                $parsed = NanesenieOptionsResolver::parseNaneseniyaRawValueForExport($block);
                foreach ($parsed as $row) {
                    $n = trim((string)($row['name'] ?? ''));
                    if ($n === '' || NanesenieOptionsResolver::isDefaultOption($n)) {
                        continue;
                    }
                    $p = (float)($row['price'] ?? 0);
                    $key = $offer . '|' . ($row['id'] ?? $n);
                    $map[$key] = $offer . ': ' . $n . ($p > 0.00001 ? ' (' . self::formatMoney($p, $currency) . ')' : '');
                }
                continue;
            }
            if (!is_array($nanesenieRows)) {
                continue;
            }
            foreach (NanesenieOptionsResolver::parseNaneseniyaRawValueForExport($nanesenieRows) as $row) {
                $n = trim((string)($row['name'] ?? ''));
                if ($n === '' || NanesenieOptionsResolver::isDefaultOption($n)) {
                    continue;
                }
                $p = (float)($row['price'] ?? 0);
                $key = $offer . '|' . ($row['id'] ?? $n);
                $map[$key] = $offer . ': ' . $n . ($p > 0.00001 ? ' (' . self::formatMoney($p, $currency) . ')' : '');
            }
        }

        return $map;
    }

    /**
     * @param array{changes: list<string>, order_list: string, price: string} $diff
     */
    private static function sendMail(Order $order, array $diff): void
    {
        if (!Loader::includeModule('sale') || !Loader::includeModule('main')) {
            return;
        }

        $email = '';
        $propertyCollection = $order->getPropertyCollection();
        if ($propertyCollection) {
            $emailProp = $propertyCollection->getUserEmail();
            if ($emailProp) {
                $email = trim((string)$emailProp->getValue());
            }
        }
        if ($email === '') {
            return;
        }

        $siteId = $order->getSiteId() ?: 's1';
        $saleEmail = (string)Option::get('sale', 'order_email', '', $siteId);
        if ($saleEmail === '') {
            $saleEmail = (string)Option::get('sale', 'order_email', '');
        }
        if ($saleEmail === '') {
            $saleEmail = 'order@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        }

        $accountNumber = (string)($order->getField('ACCOUNT_NUMBER') ?: $order->getId());
        $dateInsert = $order->getField('DATE_INSERT');
        $orderDate = is_object($dateInsert) && method_exists($dateInsert, 'toString')
            ? $dateInsert->toString()
            : (string)$dateInsert;

        $changeHtml = implode('<br />', array_map(
            static fn(string $line): string => htmlspecialcharsbx($line),
            $diff['changes']
        ));

        $fields = [
            'ORDER_ID' => $accountNumber,
            'ORDER_REAL_ID' => $order->getId(),
            'ORDER_DATE' => $orderDate,
            'EMAIL' => $email,
            'SALE_EMAIL' => $saleEmail,
            'PRICE' => $diff['price'],
            'ORDER_LIST' => $diff['order_list'],
            'ORDER_CHANGE_LIST' => $changeHtml,
            'TEXT' => $changeHtml,
            'HTML_ORDER_LIST' => $diff['order_list'],
            'HTML_ORDER_CHANGE_LIST' => $changeHtml,
            'HTML_PRICE' => $diff['price'],
        ];

        \CEvent::Send(self::EVENT_NAME, $siteId, $fields, 'Y');
    }

    private static function formatLine(
        string $name,
        float $qty,
        string $measure,
        float $price,
        string $currency,
        string $nanesenie = ''
    ): string {
        $line = htmlspecialcharsbx($name)
            . ' - '
            . self::formatQty($qty)
            . ' '
            . htmlspecialcharsbx($measure)
            . ' × '
            . self::formatMoney($price, $currency);

        if ($nanesenie !== '') {
            $line .= '<br />&nbsp;&nbsp;Варианты нанесения: ' . htmlspecialcharsbx($nanesenie);
        }

        return $line;
    }

    private static function formatQty(float $qty): string
    {
        if (abs($qty - round($qty)) < 0.00001) {
            return (string)(int)round($qty);
        }

        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }

    private static function formatMoney(float $amount, string $currency): string
    {
        if (function_exists('SaleFormatCurrency')) {
            return (string)SaleFormatCurrency($amount, $currency);
        }

        return number_format($amount, 2, '.', ' ') . ' ' . $currency;
    }

    /**
     * Создаёт тип события и шаблон, если ещё нет.
     */
    public static function ensureMailEvent(): array
    {
        $result = ['type' => false, 'message' => false];

        $description = "#ORDER_ID# - код заказа\n"
            . "#ORDER_DATE# - дата заказа\n"
            . "#EMAIL# - E-Mail покупателя\n"
            . "#SALE_EMAIL# - E-Mail отдела продаж\n"
            . "#PRICE# - сумма заказа\n"
            . "#ORDER_LIST# - актуальный состав заказа\n"
            . "#ORDER_CHANGE_LIST# - что изменилось\n"
            . "#TEXT# - текст изменений\n"
            . "#HTML_ORDER_LIST# - состав заказа (HTML)\n"
            . "#HTML_ORDER_CHANGE_LIST# - что изменилось (HTML)\n"
            . "#HTML_PRICE# - сумма (HTML)\n"
            . "#SITE_NAME# - название сайта\n"
            . "#SERVER_NAME# - URL сервера\n"
            . "#DEFAULT_EMAIL_FROM# - E-Mail по умолчанию\n";

        $exists = false;
        $rs = \CEventType::GetList(['EVENT_NAME' => self::EVENT_NAME, 'LID' => 'ru']);
        if ($rs->Fetch()) {
            $exists = true;
            $result['type'] = 'exists';
        }

        if (!$exists) {
            $et = new \CEventType();
            $id = $et->Add([
                'LID' => 'ru',
                'EVENT_NAME' => self::EVENT_NAME,
                'NAME' => 'Изменение состава заказа',
                'DESCRIPTION' => $description,
            ]);
            $result['type'] = $id ? 'created' : 'failed';
        }

        $msgExists = false;
        $rsMsg = \CEventMessage::GetList($by = 'id', $order = 'asc', [
            'TYPE_ID' => self::EVENT_NAME,
            'ACTIVE' => 'Y',
        ]);
        if ($rsMsg->Fetch()) {
            $msgExists = true;
            $result['message'] = 'exists';
        }

        if (!$msgExists) {
            $templatePath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/local/docs/mail/SALE_ORDER_BASKET_CHANGED.html';
            $body = is_file($templatePath)
                ? (string)file_get_contents($templatePath)
                : self::defaultTemplateBody();

            $em = new \CEventMessage();
            $mid = $em->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => self::EVENT_NAME,
                'LID' => ['s1'],
                'EMAIL_FROM' => '#SALE_EMAIL#',
                'EMAIL_TO' => '#EMAIL#',
                'SUBJECT' => '#SERVER_NAME#: Изменение состава заказа N#ORDER_ID#',
                'BODY_TYPE' => 'html',
                'MESSAGE' => $body,
            ]);
            $result['message'] = $mid ? 'created' : 'failed';
        }

        return $result;
    }

    private static function defaultTemplateBody(): string
    {
        return '<p>Информационное сообщение сайта #SITE_NAME#</p>'
            . '<p>В заказе N#ORDER_ID# от #ORDER_DATE# изменился состав.</p>'
            . '<p><b>Что изменилось</b><br />#ORDER_CHANGE_LIST#</p>'
            . '<p><b>Актуальный состав</b><br />#ORDER_LIST#</p>'
            . '<p>Сумма заказа: #PRICE#</p>'
            . '<p>Подробнее: http://#SERVER_NAME#/personal/order/#ORDER_ID#/</p>';
    }
}
