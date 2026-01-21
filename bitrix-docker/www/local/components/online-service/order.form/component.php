<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Sale;
use Bitrix\Main\IO\File;
use Bitrix\Main\ErrorCollection;

// Подключаем модули
if (!Loader::includeModule('sale') || !Loader::includeModule('main')) {
    ShowError('Требуемые модули не подключены');
    return;
}


// Инициализация результата
$arResult = [
    'ERRORS' => [],
    'FIELDS' => [],
    'ORDER_PROPERTIES' => [],
    'ORDER_PROPERTIES_FILES' => [],
    'countItems' => 0,
    'totalQuantity' => 0,
    'totalWeight' => 0,
    'integerPart' => 0,
    'fractionPart' => '00',
];

// === ЗАГРУЖАЕМ СВОЙСТВА ЗАКАЗА ДЛЯ ТИПА ПЛАТЕЛЬЩИКА ===
$personTypeId = 1; // или получи из настроек / сессии
$propertyCollection = \Bitrix\Sale\Internals\OrderPropsTable::getList([
    'select' => ['*'],
    'filter' => [
        'PERSON_TYPE_ID' => $personTypeId,
        'ACTIVE' => 'Y'
    ],
    'order' => ['SORT' => 'ASC', 'ID' => 'ASC']
]);

$arResult['ORDER_PROPERTIES'] = [];
while ($prop = $propertyCollection->fetch()) {
    // Пропускаем служебные свойства (например, LOCATION)
    if (in_array($prop['TYPE'], ['LOCATION', 'MULTISELECT'])) {
        continue;
    }

    if( $prop['TYPE'] === "FILE" )
        $arResult['ORDER_PROPERTIES_FILES'][$prop['CODE']] = $prop;
    else
        $arResult['ORDER_PROPERTIES'][$prop['CODE']] = $prop;
}

// === 1. ЗАГРУЖАЕМ ДАННЫЕ КОРЗИНЫ ===
$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

// Считаем итоги
$totalPrice = 0;
foreach ($basket as $item) {
    $arResult['countItems']++;
    $arResult['totalQuantity'] += $item->getQuantity();
    $totalPrice += $item->getFinalPrice(); // в копейках
}


[$arResult['integerPart'], $arResult['fractionPart']] = explode('.', number_format($totalPrice, 2, '.', ''));
$arResult['totalWeight'] = '0'; // можно рассчитать из свойств товара

// === 2. ОБРАБОТКА ОТПРАВКИ ФОРМЫ ===
$request = Application::getInstance()->getContext()->getRequest();

if ($request->isPost() && $request->getPost('formid') === 'performOrder') {
    $errors = new ErrorCollection();

    // Обязательные поля
    $requiredFields = ['name', 'company', 'phone', 'email'];
    $fields = [];

    foreach ($requiredFields as $field) {
        $value = trim($request->getPost($field));
        if (empty($value)) {
            $errors->setError(new \Bitrix\Main\Error("Поле «" . ucfirst($field) . "» обязательно"));
        }
        $fields[$field] = $value;
    }

    // Валидация email
    if (!empty($fields['email']) && !check_email($fields['email'])) {
        $errors->setError(new \Bitrix\Main\Error('Некорректный email'));
    }

    // Валидация телефона (простая)
    if (!empty($fields['phone']) && !preg_match('/^\+7\s?\(\d{3}\)\s?\d{3}-?\d{2}-?\d{2}$/', $fields['phone'])) {
        // Можно разрешить любые цифры, если маска гибкая
        // Здесь просто проверим, что есть хотя бы 10 цифр
        if (strlen(preg_replace('/\D/', '', $fields['phone'])) < 10) {
            $errors->setError(new \Bitrix\Main\Error('Некорректный телефон'));
        }
    }

    // Проверка согласия
    if (!$request->getPost('agree2')) {
        $errors->setError(new \Bitrix\Main\Error('Требуется согласие на обработку персональных данных'));
    }

    // === 3. ОБРАБОТКА ФАЙЛОВ ===
    $uploadedFiles = [];
    $fileFields = ['requisites', 'requisites1', 'requisites2'];

    foreach ($fileFields as $fileKey) {
        if ($_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/upload/order_files/';
            CheckDirPath($_SERVER['DOCUMENT_ROOT'] . $uploadDir);

            $fileName = $_FILES[$fileKey]['name'];
            $fileTmp = $_FILES[$fileKey]['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Безопасное имя файла
            $safeName = md5(uniqid()) . '.' . $fileExt;
            $filePath = $uploadDir . $safeName;

            if (move_uploaded_file($fileTmp, $_SERVER['DOCUMENT_ROOT'] . $filePath)) {
                $uploadedFiles[$fileKey] = $filePath;
            } else {
                $errors->setError(new \Bitrix\Main\Error("Не удалось загрузить файл: $fileKey"));
            }
        }
    }

    // === 4. ЕСЛИ НЕТ ОШИБОК — СОХРАНЯЕМ ЗАКАЗ ===
    if ($errors->isEmpty()) {
        try {
            // Создаём заказ
            $order = Sale\Order::create(SITE_ID, $fuserId);

            // Устанавливаем свойства
            $order->setPersonTypeId(1); // тип плательщика (юр. лицо / физ. лицо)
            $order->setField('CURRENCY', 'RUB');

            // Добавляем корзину
            $order->setBasket($basket);

            // Сохраняем заказ
            $result = $order->save();

            if (!$result->isSuccess()) {
                foreach ($result->getErrors() as $error) {
                    $errors->setError($error);
                }
            } else {
                // === 5. СОХРАНЯЕМ ДОП. ДАННЫЕ В СВОЙСТВА ЗАКАЗА ===
                $propertyCollection = $order->getPropertyCollection();

                // Пример: сохраняем комментарий
                $commentProp = $propertyCollection->getItemByOrderPropertyCode('COMMENT');
                if ($commentProp) {
                    $commentProp->setValue($request->getPost('comment'));
                }

                // Сохраняем телефон, email и т.д. как свойства
                $propsMap = [
                    'PHONE' => $fields['phone'],
                    'EMAIL' => $fields['email'],
                    'COMPANY' => $fields['company'],
                    'NAME' => $fields['name'],
                    'PAY_SYSTEM_ID' => (int)$request->getPost('pay') ?: 1,
                ];

                foreach ($propsMap as $code => $value) {
                    $prop = $propertyCollection->getItemByOrderPropertyCode($code);
                    if ($prop) {
                        $prop->setValue($value);
                    }
                }

                // Сохраняем файлы как свойства (если нужно)
                foreach ($uploadedFiles as $fileKey => $filePath) {
                    $prop = $propertyCollection->getItemByOrderPropertyCode(strtoupper($fileKey));
                    if ($prop) {
                        $prop->setValue($filePath);
                    }
                }

                $order->save();

                // === 6. РЕДИРЕКТ НА СТРАНИЦУ УСПЕХА ===
                LocalRedirect('/personal/order/success/?ORDER_ID=' . $order->getId());
                exit;
            }
        } catch (Exception $e) {
            $errors->setError(new \Bitrix\Main\Error('Ошибка создания заказа: ' . $e->getMessage()));
        }
    }

    // Если были ошибки — передаём их в шаблон
    $arResult['ERRORS'] = $errors->toArray();
    $arResult['FIELDS'] = $fields;
} else {
    // Предзаполняем поля, если пользователь авторизован
    global $USER;
    if ($USER->IsAuthorized()) {
        $arResult['FIELDS']['name'] = $USER->GetFullName() ?: $USER->GetLogin();
        $arResult['FIELDS']['email'] = $USER->GetEmail();
        $arResult['FIELDS']['phone'] = ''; // можно взять из профиля
        $arResult['FIELDS']['company'] = ''; // или из свойств пользователя
    }
}

// Передаём результат в шаблон
$this->IncludeComponentTemplate();