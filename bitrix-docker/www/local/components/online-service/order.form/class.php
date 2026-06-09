<?php
namespace OnlineService\OrderForm;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Sale;
use Bitrix\Main\ErrorCollection;
use OnlineService\Catalog\NanesenieOptionsResolver;
use OnlineService\Sale\BasketNaneseniyaStorage;
use OnlineService\Sale\OrderJsonNaneseniyaProperty;
use OnlineService\Sale\JsonNaneseniyaPersister;
use CFile;

class OrderFormComponent
{
    /** @var \CBitrixComponent */
    protected $component;

    /** @var array */
    protected $arParams;

    /** @var array */
    protected $arResult;

    public function __construct(\CBitrixComponent $component)
    {
        $this->component = $component;
        $this->arParams = $component->arParams;
        $this->arResult = [];
    }

    public function execute(): void
    {
        if (!Loader::includeModule('sale')) {
            $this->showError('Модуль sale не подключен');
            return;
        }

        $this->initResult();
        $this->loadOrderProperties();
        $this->loadBasket();

        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->isPost() && $request->getPost('formid') === 'performOrder') {
            $this->processForm($request);
        } else {
            $this->prefillFields();
        }

        $this->component->arResult = $this->arResult;
        $this->component->IncludeComponentTemplate();
    }

    protected function initResult(): void
    {
        $this->arResult = [
            'ERRORS' => [],
            'FIELDS' => [],
            'ORDER_PROPERTIES' => [],
            'ORDER_PROPERTIES_FILES' => [],
            'CHECKBOX_PROPERTIES' => [],
            'countItems' => 0,
            'totalQuantity' => 0,
            'totalWeight' => 0,
            'integerPart' => 0,
            'fractionPart' => '00',
        ];
    }

    protected function loadOrderProperties(): void
    {
        $personTypeId = 1;
        $props = Sale\Internals\OrderPropsTable::getList([
            'select' => ['*'],
            'filter' => [
                'PERSON_TYPE_ID' => $personTypeId,
                'ACTIVE' => 'Y'
            ],
            'order' => ['SORT' => 'ASC']
        ]);

        while ($prop = $props->fetch()) {
            if (in_array($prop['TYPE'], ['LOCATION', 'MULTISELECT'])) continue;

            $code = $prop['CODE'];
            if ($prop['TYPE'] === 'FILE') {
                $this->arResult['ORDER_PROPERTIES_FILES'][$code] = $prop;
            }
            else if($prop['TYPE'] === "Y/N"){
                $this->arResult['CHECKBOX_PROPERTIES'][$code] = $prop;
            }
            else {
                $this->arResult['ORDER_PROPERTIES'][$code] = $prop;
            }
        }
    }

    protected function loadBasket(): void
    {
        global $USER;
        // В AJAX/нестандартных точках входа Fuser::getId() может вернуть "гостевой" FUSER даже для авторизованного.
        // Для корректной привязки заказа к покупателю используем getId(true) при наличии авторизации.
        $fuserId = ($USER && $USER->IsAuthorized()) ? Sale\Fuser::getId(true) : Sale\Fuser::getId();
        $basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

        $totalPrice = 0;
        foreach ($basket as $item) {
            $this->arResult['countItems']++;
            $this->arResult['totalQuantity'] += $item->getQuantity();
            $totalPrice += $item->getFinalPrice();
        }

        [$int, $frac] = explode('.', number_format($totalPrice, 2, '.', ''));
        $this->arResult['integerPart'] = $int;
        $this->arResult['fractionPart'] = $frac;
        $this->arResult['totalWeight'] = '0';

        // Сохраняем корзину в arResult для использования в processForm
        $this->arResult['_BASKET'] = $basket;
        $this->arResult['_FUSER_ID'] = $fuserId;
    }

    /**
     * @param int[] $productIds
     * @return array<int, string> productId => XML_ID товара (для SKU — родительский товар)
     */
    protected function resolveXmlIdsForProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $elementIdByProductId = [];
        if (Loader::includeModule('catalog')) {
            $rs = \CCatalogProduct::GetList([], ['ID' => $productIds], false, false, ['ID', 'TYPE', 'PARENT_PRODUCT_ID']);
            $catalogById = [];
            while ($row = $rs->Fetch()) {
                $catalogById[(int)$row['ID']] = $row;
            }
            foreach ($productIds as $productId) {
                $elementId = $productId;
                $cat = $catalogById[$productId] ?? null;
                if ($cat && (int)$cat['TYPE'] === \CCatalogProduct::TYPE_OFFER) {
                    $parentId = (int)($cat['PARENT_PRODUCT_ID'] ?? 0);
                    if ($parentId > 0) {
                        $elementId = $parentId;
                    }
                }
                $elementIdByProductId[$productId] = $elementId;
            }
        } else {
            foreach ($productIds as $productId) {
                $elementIdByProductId[$productId] = $productId;
            }
        }

        $xmlByElementId = [];
        if (Loader::includeModule('iblock')) {
            $elementIds = array_values(array_unique($elementIdByProductId));
            $rs = \CIBlockElement::GetList([], ['ID' => $elementIds], false, false, ['ID', 'XML_ID']);
            while ($row = $rs->Fetch()) {
                $xmlByElementId[(int)$row['ID']] = trim((string)($row['XML_ID'] ?? ''));
            }
        }

        $map = [];
        foreach ($productIds as $productId) {
            $elementId = $elementIdByProductId[$productId] ?? $productId;
            $xmlId = $xmlByElementId[$elementId] ?? '';
            $map[$productId] = $xmlId !== '' ? $xmlId : (string)$productId;
        }

        return $map;
    }

    protected function encodeNaneseniyaJson(array $result): string
    {
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return '[]';
        }

        return (string)preg_replace_callback(
            '/"price":(-?\d+(?:\.\d+)?)/',
            static function (array $m): string {
                $formatted = number_format((float)$m[1], 2, '.', '');
                return '"price":' . $formatted;
            },
            $json
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{id: string, items: array<int, array<string, mixed>>}
     */
    protected function wrapNaneseniyaPayload(array $items, string $orderXmlId): array
    {
        return [
            'id' => $orderXmlId,
            'items' => array_values($items),
        ];
    }

    protected function reloadOrder(int $orderId): ?Sale\Order
    {
        if ($orderId <= 0) {
            return null;
        }

        return Sale\Order::load($orderId);
    }

    protected function snapshotNaneseniyaItemsForOrder(): void
    {
        $basket = $this->arResult['_BASKET'] ?? null;
        if (!$basket instanceof Sale\Basket) {
            $this->arResult['_NANESENIYA_ITEMS'] = [];
            return;
        }

        $this->arResult['_NANESENIYA_ITEMS'] = $this->buildNaneseniyaItemsFromBasket($basket);
    }

    protected function normalizeBasketNaneseniyaBeforeOrder(): void
    {
        $basket = $this->arResult['_BASKET'] ?? null;
        if (!$basket instanceof Sale\Basket) {
            return;
        }

        BasketNaneseniyaStorage::ensureValueColumn();

        $changed = false;
        foreach ($basket as $item) {
            $props = $item->getPropertyCollection();
            if (NanesenieOptionsResolver::repackMonolithicNaneseniyaProps($props)) {
                $changed = true;
            }
        }

        if ($changed) {
            $basket->save();
        }
    }

    protected function resolveOrderXmlIdForJson(Sale\Order $order): string
    {
        $xmlId = trim((string)$order->getField('XML_ID'));
        if ($xmlId !== '') {
            return $xmlId;
        }

        $orderId = (int)$order->getId();
        if ($orderId <= 0) {
            return '';
        }

        if (class_exists(\Bitrix\Main\Security\Random::class)) {
            $xmlId = (string)\Bitrix\Main\Security\Random::getUuid();
        } else {
            $xmlId = sprintf(
                '%s-%s',
                date('YmdHis'),
                substr(md5(uniqid((string)$orderId, true)), 0, 12)
            );
        }

        \CSaleOrder::Update($orderId, ['XML_ID' => $xmlId]);
        $order->setField('XML_ID', $xmlId);

        return $xmlId;
    }

    protected function buildJsonForOrder(Sale\Order $order): string
    {
        $basket = $order->getBasket();
        $items = $this->buildNaneseniyaItemsFromBasket($basket instanceof Sale\Basket ? $basket : null);

        if ($this->isCorruptedNaneseniyaItems($items)) {
            $items = [];
        }

        if ($items === []) {
            $snapshot = $this->arResult['_NANESENIYA_ITEMS'] ?? null;
            if (is_array($snapshot) && $snapshot !== [] && !$this->isCorruptedNaneseniyaItems($snapshot)) {
                $items = $snapshot;
            } elseif ((int)$order->getId() <= 0) {
                $items = $this->buildNaneseniyaItemsFromBasket($this->arResult['_BASKET'] ?? null);
            }
        }

        $orderXmlId = $this->resolveOrderXmlIdForJson($order);
        $wrapped = $this->wrapNaneseniyaPayload($items, $orderXmlId);
        if (($wrapped['items'] ?? []) === []) {
            return '';
        }

        return $this->encodeNaneseniyaJson($wrapped);
    }

    /**
     * @param array<int, array{id: string, NANESENIE: array<int, array<string, mixed>>}> $items
     */
    protected function isCorruptedNaneseniyaItems(array $items): bool
    {
        foreach ($items as $item) {
            $nanesenie = $item['NANESENIE'] ?? [];
            if (!is_array($nanesenie)) {
                continue;
            }
            foreach ($nanesenie as $n) {
                if (!is_array($n)) {
                    continue;
                }
                $name = trim((string)($n['name'] ?? ''));
                if ($name !== '' && ($name[0] === '[' || $name[0] === '{')) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function writeJsonNaneseniyaForOrder(Sale\Order $order, bool $alsoPersistDirect = false): void
    {
        OrderJsonNaneseniyaProperty::ensureMaxLength();

        $orderId = (int)$order->getId();
        if ($orderId <= 0) {
            return;
        }

        $jsonValue = $this->buildJsonForOrder($order);
        if ($jsonValue === '') {
            return;
        }

        $canSetValueViaCollection = strlen($jsonValue) <= OrderJsonNaneseniyaProperty::D7_SET_VALUE_SAFE_LENGTH;
        if ($canSetValueViaCollection) {
            $propertyCollection = $order->getPropertyCollection();
            if ($propertyCollection) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('json_naneseniya');
                if ($propItem) {
                    $propItem->setValue($jsonValue);
                }
            }
        }

        if ($alsoPersistDirect || !$canSetValueViaCollection) {
            $personTypeId = (int)$order->getPersonTypeId();
            if ($personTypeId <= 0) {
                $personTypeId = 1;
            }
            JsonNaneseniyaPersister::persist($orderId, $personTypeId, $jsonValue);
        }
    }

    protected function finalizeJsonNaneseniya(Sale\Order $order): void
    {
        $this->writeJsonNaneseniyaForOrder($order, true);
    }

    protected function applyJsonNaneseniyaProperty(Sale\Order $order): void
    {
        $this->writeJsonNaneseniyaForOrder($order, true);
    }

    /**
     * @return array<int, array{id: string, NANESENIE: array<int, array<string, mixed>>}>
     */
    protected function buildNaneseniyaItemsFromBasket(?Sale\Basket $basket = null): array
    {
        if ($basket === null) {
            $basket = $this->arResult['_BASKET'] ?? null;
        }
        if (!$basket instanceof Sale\Basket) {
            return [];
        }

        $byProductId = [];

        /** @var Sale\BasketItem $item */
        foreach ($basket as $item) {
            $productId = (int)$item->getProductId();
            if ($productId <= 0) {
                continue;
            }

            $byProductId[$productId] ??= [];

            $values = [];
            $props = $item->getPropertyCollection();
            if ($props) {
                foreach ($props as $propItem) {
                    $code = (string)($propItem->getField('CODE') ?? '');
                    if (mb_strtoupper($code) !== 'NANESENIE') {
                        continue;
                    }

                    $raw = $propItem->getField('VALUE');
                    $values = array_merge(
                        $values,
                        NanesenieOptionsResolver::parseNaneseniyaRawValueForExport($raw)
                    );
                }
            }

            if ($values) {
                $byProductId[$productId] = array_merge($byProductId[$productId], $values);
            }
        }

        $xmlIdMap = $this->resolveXmlIdsForProductIds(array_keys($byProductId));
        $byXmlId = [];
        $xmlIdOrder = [];

        foreach ($byProductId as $productId => $values) {
            $xmlId = $xmlIdMap[$productId] ?? (string)$productId;
            if (!isset($xmlIdOrder[$xmlId])) {
                $xmlIdOrder[$xmlId] = true;
            }
            if ($values) {
                $byXmlId[$xmlId] = array_merge($byXmlId[$xmlId] ?? [], $values);
            } else {
                $byXmlId[$xmlId] ??= [];
            }
        }

        $result = [];
        foreach (array_keys($xmlIdOrder) as $xmlId) {
            $values = $byXmlId[$xmlId] ?? [];
            $norm = [];
            foreach ($values as $v) {
                if (!is_array($v)) {
                    continue;
                }
                $name = trim((string)($v['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                if (NanesenieOptionsResolver::isDefaultOption($name)) {
                    $norm['__default__'] = NanesenieOptionsResolver::buildDefaultNaneseniyaExportItem();
                    continue;
                }
                $dedupKey = trim((string)($v['id'] ?? ''));
                if ($dedupKey === '') {
                    $dedupKey = $name;
                }
                $item = [
                    'name' => $name,
                    'price' => round((float)($v['price'] ?? 0), 2),
                ];
                $enumXmlId = trim((string)($v['id'] ?? ''));
                if ($enumXmlId !== '') {
                    $item = ['id' => $enumXmlId] + $item;
                }
                $norm[$dedupKey] = $item;
            }
            if ($norm === []) {
                continue;
            }
            $result[] = [
                'id' => $xmlId,
                'NANESENIE' => array_values($norm),
            ];
        }

        return $result;
    }

    protected function buildNaneseniyaJsonFromBasket(?string $orderXmlId = null, ?Sale\Basket $basket = null): string
    {
        $items = $this->buildNaneseniyaItemsFromBasket($basket);
        if ($orderXmlId === null || $orderXmlId === '') {
            return $this->encodeNaneseniyaJson($items);
        }

        return $this->encodeNaneseniyaJson($this->wrapNaneseniyaPayload($items, $orderXmlId));
    }

    protected function processForm($request): void
    {
        OrderJsonNaneseniyaProperty::ensureMaxLength();

        // === 1. Проверка сессии (обязательно!) ===
        if (!check_bitrix_sessid()) {
            $this->sendJsonError('Неверная сессия. Обновите страницу.', $request);
            return;
        }

        $errors = new \Bitrix\Main\ErrorCollection();
        $fields = [];

        // === 2. Валидация свойств заказа ===
        foreach ($this->arResult['ORDER_PROPERTIES'] as $code => $prop) {
            if ($code === 'json_naneseniya') {
                continue;
            }
            $value = trim((string)$request->getPost($code));
            if ($prop['REQUIED'] === 'Y' && empty($value)) {
                $label = $prop['NAME'] ?: $code;
                $errors->setError(new \Bitrix\Main\Error("Поле «{$label}» обязательно"));
            }
            $fields[$code] = $value;
        }

        $orderCompanyId = trim((string)$request->getPost('order_company'));
        if ($orderCompanyId) {
            $fields['ORDER_COMPANY'] = $orderCompanyId;
        }

        // === 3. Дополнительная валидация ===
        if (!empty($fields['EMAIL']) && !check_email($fields['EMAIL'])) {
            $errors->setError(new \Bitrix\Main\Error('Некорректный email'));
        }

        if (!empty($fields['PHONE'])) {
            $digits = preg_replace('/\D/', '', $fields['PHONE']);
            if (strlen($digits) < 10) {
                $errors->setError(new \Bitrix\Main\Error('Некорректный телефон'));
            }
        }

        // === 4. Обработка файлов ===
        $uploadedFileIds = [];
        $companyRequisitesFileId = (int)($request->getPost('order_company_requisites_file_id') ?? 0);
        foreach ($this->arResult['ORDER_PROPERTIES_FILES'] as $code => $prop) {
            if (!empty($_FILES[$code]['tmp_name']) && $_FILES[$code]['error'] === UPLOAD_ERR_OK) {
                $fileId = \CFile::SaveFile($_FILES[$code], 'order_files');
                if ($fileId) {
                    $uploadedFileIds[$code] = $fileId;
                } else {
                    $errors->setError(new \Bitrix\Main\Error("Ошибка загрузки файла: {$prop['NAME']}"));
                }
            } elseif ($companyRequisitesFileId > 0 && stripos($code, 'requisites') !== false) {
                $uploadedFileIds[$code] = $companyRequisitesFileId;
            }
        }

        // === 5. Если есть ошибки — возвращаем их ===
        if (!$errors->isEmpty()) {
            if ($request->isAjaxRequest()) {
                $this->sendJsonError(
                    implode("\n", array_map(fn($e) => $e->getMessage(), $errors->toArray())),
                    $request
                );
            } else {
                $this->arResult['ERRORS'] = $errors->toArray();
                $this->arResult['FIELDS'] = $fields;
            }
            return;
        }

        // === 6. Создаём заказ (только если ошибок нет) ===
        try {
            $this->normalizeBasketNaneseniyaBeforeOrder();
            $this->snapshotNaneseniyaItemsForOrder();

            global $USER;
            $siteUserId = ($USER && $USER->IsAuthorized()) ? (int) $USER->GetID() : 0;
            if ($siteUserId <= 0 && \class_exists(\CSaleUser::class)) {
                // Для гостевого заказа Bitrix ожидает anonymous USER_ID, а не FUSER_ID.
                $siteUserId = (int) \CSaleUser::GetAnonymousUserID();
            }

            $order = \Bitrix\Sale\Order::create(SITE_ID, $siteUserId);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', 'RUB');
            // Явно фиксируем покупателя: в некоторых конфигурациях USER_ID не попадает в админку при ручной установке FUSER_ID.
            if ($siteUserId > 0) {
                $order->setField('USER_ID', $siteUserId);
            }
            // FUSER_ID привязывается корзиной; ручная установка может падать на валидации.
            $order->setBasket($this->arResult['_BASKET']);

            // Первое сохранение — чтобы создать запись
            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            $orderId = (int)$order->getId();
            $order = $this->reloadOrder($orderId);
            if (!$order) {
                throw new \Exception('Не удалось загрузить созданный заказ');
            }

            // Заполняем свойства
            $propertyCollection = $order->getPropertyCollection();

            foreach ($fields as $code => $value) {
                if ($code === 'json_naneseniya') {
                    continue;
                }
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($value);
                }
            }

            foreach ($uploadedFileIds as $code => $fileId) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($fileId);
                }
            }

            if ($comment = $request->getPost('COMMENT')) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                if ($propItem) {
                    $propItem->setValue($comment);
                }
            }

            if ($orderCompanyId && (int)$orderCompanyId > 0) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('ORDER_COMPANY');
                if ($propItem) {
                    $propItem->setValue($orderCompanyId);
                } else {
                    $commentItem = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                    if ($commentItem) {
                        $currentComment = (string)$commentItem->getValue();
                        $commentItem->setValue($currentComment . ($currentComment ? "\n" : '') . 'Заказ от компании ID: ' . $orderCompanyId);
                    }
                }
            }

            $this->writeJsonNaneseniyaForOrder($order, false);

            // Финальное сохранение
            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            $this->writeJsonNaneseniyaForOrder($order, true);

            // === 7. Отправляем ответ ===
            $redirectUrl = '/personal/order/success/?ORDER_ID=' . $order->getId();

            if ($request->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                exit;
            } else {
                LocalRedirect($redirectUrl);
                exit;
            }

        } catch (\Exception $e) {
            $this->sendJsonError('Ошибка создания заказа: ' . $e->getMessage(), $request);
        }
    }

// Вспомогательный метод для отправки JSON-ошибки
    protected function sendJsonError(string $message, $request): void
    {
        if ($request->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        } else {
            $this->arResult['ERRORS'][] = new \Bitrix\Main\Error($message);
        }
    }

    public function handleAjaxRequest($request)
    {
        OrderJsonNaneseniyaProperty::ensureMaxLength();

        // Загружаем корзину и свойства (как в execute())
        $this->initResult();
        $this->loadOrderProperties();
        $this->loadBasket();

        // Валидация и создание заказа
        $errors = new \Bitrix\Main\ErrorCollection();
        $fields = [];

        // --- Валидация (повтори логику из processForm) ---
        foreach ($this->arResult['ORDER_PROPERTIES'] as $code => $prop) {

            if ($code === 'json_naneseniya') {
                continue;
            }
            $value = trim((string)$request->getPost($code));
            if ($prop['REQUIED'] === 'Y' && empty($value)) {
                $label = $prop['NAME'] ?: $code;
                $errors->setError(new \Bitrix\Main\Error("Поле «{$label}» обязательно"));
            }

            $fields[$code] = $value;
        }

        $orderCompanyId = trim((string)$request->getPost('order_company'));
        if ($orderCompanyId) {
            $fields['ORDER_COMPANY'] = $orderCompanyId;
        }

        if (!empty($fields['EMAIL']) && !check_email($fields['EMAIL'])) {
            $errors->setError(new \Bitrix\Main\Error('Некорректный email'));
        }

        if (!empty($fields['PHONE'])) {
            $digits = preg_replace('/\D/', '', $fields['PHONE']);
            if (strlen($digits) < 10) {
                $errors->setError(new \Bitrix\Main\Error('Некорректный телефон'));
            }
        }

        foreach ($this->arResult['CHECKBOX_PROPERTIES'] as $code => $prop) {
            if ($prop['REQUIRED'] === 'Y') {
                if ($request->getPost($code) !== 'Y') {
                    $label = $prop['NAME'] ?: $code;
                    $errors->setError(new \Bitrix\Main\Error("Требуется согласие: {$label}"));
                }
            }
        }

        // Файлы
        $uploadedFileIds = [];
        $companyRequisitesFileId = (int)($request->getPost('order_company_requisites_file_id') ?? 0);
        foreach ($this->arResult['ORDER_PROPERTIES_FILES'] as $code => $prop) {
            if (!empty($_FILES[$code]['tmp_name']) && $_FILES[$code]['error'] === UPLOAD_ERR_OK) {
                $fileId = \CFile::SaveFile($_FILES[$code], 'order_files');
                if ($fileId) {
                    $uploadedFileIds[$code] = $fileId;
                } else {
                    $errors->setError(new \Bitrix\Main\Error("Ошибка загрузки файла: {$prop['NAME']}"));
                }
            } elseif ($companyRequisitesFileId > 0 && stripos($code, 'requisites') !== false) {
                $uploadedFileIds[$code] = $companyRequisitesFileId;
            }
        }

        if (!$errors->isEmpty()) {
            return [
                'success' => false,
                'message' => implode("\n", array_map(fn($e) => $e->getMessage(), $errors->toArray()))
            ];
        }

        // Создаём заказ
        try {
            $this->normalizeBasketNaneseniyaBeforeOrder();
            $this->snapshotNaneseniyaItemsForOrder();

            global $USER;
            $siteUserId = ($USER && $USER->IsAuthorized()) ? (int) $USER->GetID() : 0;
            if ($siteUserId <= 0 && \class_exists(\CSaleUser::class)) {
                $siteUserId = (int) \CSaleUser::GetAnonymousUserID();
            }

            $order = \Bitrix\Sale\Order::create(SITE_ID, $siteUserId);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', 'RUB');
            if ($siteUserId > 0) {
                $order->setField('USER_ID', $siteUserId);
            }
            $order->setBasket($this->arResult['_BASKET']);

            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            $orderId = (int)$order->getId();
            $order = $this->reloadOrder($orderId);
            if (!$order) {
                throw new \Exception('Не удалось загрузить созданный заказ');
            }

            $propertyCollection = $order->getPropertyCollection();
            foreach ($fields as $code => $value) {
                if ($code === 'json_naneseniya') {
                    continue;
                }
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($value);
                }
            }
            foreach ($this->arResult['CHECKBOX_PROPERTIES'] as $code => $prop) {
                $isChecked = ($request->getPost($code) === 'Y');
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($isChecked ? 'Y' : '');
                }
            }
            foreach ($uploadedFileIds as $code => $fileId) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($fileId);
                }
            }
            if ($comment = $request->getPost('COMMENT')) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                if ($propItem) {
                    $propItem->setValue($comment);
                }
            }
            if ($orderCompanyId && (int)$orderCompanyId > 0) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('ORDER_COMPANY');
                if ($propItem) {
                    $propItem->setValue($orderCompanyId);
                } else {
                    $commentItem = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                    if ($commentItem) {
                        $currentComment = (string)$commentItem->getValue();
                        $commentItem->setValue($currentComment . ($currentComment ? "\n" : '') . 'Заказ от компании ID: ' . $orderCompanyId);
                    }
                }
            }
            $this->writeJsonNaneseniyaForOrder($order, false);

            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            $this->writeJsonNaneseniyaForOrder($order, true);

            return [
                'success' => true,
                'redirect' => '/personal/order/success/?ORDER_ID=' . $order->getId()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка создания заказа: ' . $e->getMessage()
            ];
        }
    }

    protected function prefillFields(): void
    {
        global $USER;
        if ($USER->IsAuthorized()) {
            // Не склеиваем имя+фамилия в одно поле: заполняем раздельно, если такие поля есть в форме/свойствах заказа.
            $name = trim((string)$USER->GetFirstName());
            $lastName = trim((string)$USER->GetLastName());

            if ($name !== '') {
                $this->arResult['FIELDS']['NAME'] = $name;
            } else {
                $this->arResult['FIELDS']['NAME'] = $USER->GetLogin();
            }

            if ($lastName !== '') {
                $this->arResult['FIELDS']['LASTNAME'] = $lastName;
                $this->arResult['FIELDS']['LAST_NAME'] = $lastName;
            }
            $this->arResult['FIELDS']['EMAIL'] = $USER->GetEmail();
        }
    }

    protected function showError(string $message): void
    {
        ShowError($message);
    }
}