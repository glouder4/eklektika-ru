<?php
namespace OnlineService\OrderForm;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Sale;
use Bitrix\Main\ErrorCollection;
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
        $fuserId = Sale\Fuser::getId();
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

    protected function processForm($request): void
    {
        // === 1. Проверка сессии (обязательно!) ===
        if (!check_bitrix_sessid()) {
            $this->sendJsonError('Неверная сессия. Обновите страницу.', $request);
            return;
        }

        $errors = new \Bitrix\Main\ErrorCollection();
        $fields = [];

        // === 2. Валидация свойств заказа ===
        foreach ($this->arResult['ORDER_PROPERTIES'] as $code => $prop) {
            $value = trim((string)$request->getPost($code));
            if ($prop['REQUIED'] === 'Y' && empty($value)) {
                $label = $prop['NAME'] ?: $code;
                $errors->setError(new \Bitrix\Main\Error("Поле «{$label}» обязательно"));
            }
            $fields[$code] = $value;
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
        foreach ($this->arResult['ORDER_PROPERTIES_FILES'] as $code => $prop) {
            if (!empty($_FILES[$code]['tmp_name']) && $_FILES[$code]['error'] === UPLOAD_ERR_OK) {
                $fileId = \CFile::SaveFile($_FILES[$code], 'order_files');
                if ($fileId) {
                    $uploadedFileIds[$code] = $fileId;
                } else {
                    $errors->setError(new \Bitrix\Main\Error("Ошибка загрузки файла: {$prop['NAME']}"));
                }
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
            $order = \Bitrix\Sale\Order::create(SITE_ID, $this->arResult['_FUSER_ID']);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', 'RUB');
            $order->setBasket($this->arResult['_BASKET']);

            // Первое сохранение — чтобы создать запись
            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            // Заполняем свойства
            $propertyCollection = $order->getPropertyCollection();

            foreach ($fields as $code => $value) {
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

            // Финальное сохранение
            $order->save();

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
        // Загружаем корзину и свойства (как в execute())
        $this->initResult();
        $this->loadOrderProperties();
        $this->loadBasket();

        // Валидация и создание заказа
        $errors = new \Bitrix\Main\ErrorCollection();
        $fields = [];

        // --- Валидация (повтори логику из processForm) ---
        foreach ($this->arResult['ORDER_PROPERTIES'] as $code => $prop) {
            $value = trim((string)$request->getPost($code));
            if ($prop['REQUIED'] === 'Y' && empty($value)) {
                $label = $prop['NAME'] ?: $code;
                $errors->setError(new \Bitrix\Main\Error("Поле «{$label}» обязательно"));
            }
            $fields[$code] = $value;
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
        foreach ($this->arResult['ORDER_PROPERTIES_FILES'] as $code => $prop) {
            if (!empty($_FILES[$code]['tmp_name']) && $_FILES[$code]['error'] === UPLOAD_ERR_OK) {
                $fileId = \CFile::SaveFile($_FILES[$code], 'order_files');
                if ($fileId) {
                    $uploadedFileIds[$code] = $fileId;
                } else {
                    $errors->setError(new \Bitrix\Main\Error("Ошибка загрузки файла: {$prop['NAME']}"));
                }
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
            $order = \Bitrix\Sale\Order::create(SITE_ID, $this->arResult['_FUSER_ID']);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', 'RUB');
            $order->setBasket($this->arResult['_BASKET']);

            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode('; ', $result->getErrorMessages()));
            }

            $propertyCollection = $order->getPropertyCollection();
            foreach ($fields as $code => $value) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) $propItem->setValue($value);
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
                if ($propItem) $propItem->setValue($fileId);
            }
            if ($comment = $request->getPost('COMMENT')) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                if ($propItem) $propItem->setValue($comment);
            }
            $order->save();

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
            $this->arResult['FIELDS']['NAME'] = $USER->GetFullName() ?: $USER->GetLogin();
            $this->arResult['FIELDS']['EMAIL'] = $USER->GetEmail();
        }
    }

    protected function showError(string $message): void
    {
        ShowError($message);
    }
}