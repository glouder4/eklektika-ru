<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

$request = Application::getInstance()->getContext()->getRequest();
$webFormId = 1; // ID вебформы

// Получаем данные из запроса (проверяем оба варианта имен)
$name = trim($request->getPost('form_text_1') ?: $request->getPost('name') ?: '');
$email = trim($request->getPost('form_text_2') ?: $request->getPost('email') ?: '');
$phone = trim($request->getPost('form_text_3') ?: $request->getPost('phone') ?: '');
$message = trim($request->getPost('form_textarea_4') ?: $request->getPost('message') ?: '');

// Для чекбоксов проверяем массив и обычное значение
$personalDataPost = $request->getPost('form_checkbox_5');
if (is_array($personalDataPost) && in_array('Y', $personalDataPost)) {
    $personalData = 'Y';
} else {
    $personalData = $request->getPost('personal_data') ?: '';
}

$mailingPost = $request->getPost('form_checkbox_6');
if (is_array($mailingPost) && in_array('Y', $mailingPost)) {
    $mailing = 'Y';
} else {
    $mailing = $request->getPost('mailing') ?: '';
}

// Валидация
$errors = array();

if (empty($name)) {
    $errors[] = 'Не указано имя';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Не указан или некорректный email';
}

if (empty($phone)) {
    $errors[] = 'Не указан телефон';
}

if (empty($personalData) || $personalData !== 'Y') {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

if (!empty($errors)) {
    echo json_encode(array(
        'status' => 'error',
        'message' => implode(', ', $errors)
    ));
    exit;
}

// Подключаем модуль вебформ
CModule::IncludeModule("form");

// Получаем данные вебформы для определения реальных имен полей и ID вариантов ответов
$arForm = array();
$arQuestions = array();
$arAnswers = array();
$arDropDown = array();
$arMultiSelect = array();

CForm::GetDataByID($webFormId, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect);

// Определяем реальные имена полей и ID вариантов ответов
$checkbox5FieldName = null; // Реальное имя поля для чекбокса 5
$checkbox6FieldName = null; // Реальное имя поля для чекбокса 6
$checkbox5AnswerId = null; // ID варианта ответа "Да" для поля 5
$checkbox6AnswerId = null; // ID варианта ответа "Да" для поля 6

// Проходим по всем вопросам, чтобы найти вопросы с ID 5 и 6
foreach ($arQuestions as $questionId => $question) {
    if (!is_array($question)) continue;
    
    // Ищем вопрос с ID = 5
    if (isset($question['ID']) && $question['ID'] == 5) {
        // Используем SID вопроса для формирования имени поля
        $sid = isset($question['SID']) ? $question['SID'] : '5';
        $checkbox5FieldName = 'form_checkbox_' . $sid;
        
        // Ищем варианты ответов для этого вопроса
        if (isset($arAnswers[$questionId]) && is_array($arAnswers[$questionId])) {
            foreach ($arAnswers[$questionId] as $answer) {
                // Ищем вариант "Да" - проверяем по ID или по тексту
                if (isset($answer['ID']) && $answer['ID'] == 5) {
                    $checkbox5AnswerId = $answer['ID'];
                    break;
                } elseif (isset($answer['MESSAGE']) && stripos($answer['MESSAGE'], 'да') !== false) {
                    $checkbox5AnswerId = $answer['ID'];
                    break;
                }
            }
            // Если не нашли, берем первый вариант
            if (!$checkbox5AnswerId && !empty($arAnswers[$questionId])) {
                $checkbox5AnswerId = $arAnswers[$questionId][0]['ID'];
            }
        }
    }
    
    // Ищем вопрос с ID = 6
    if (isset($question['ID']) && $question['ID'] == 6) {
        // Используем SID вопроса для формирования имени поля
        $sid = isset($question['SID']) ? $question['SID'] : '6';
        $checkbox6FieldName = 'form_checkbox_' . $sid;
        
        // Ищем варианты ответов для этого вопроса
        if (isset($arAnswers[$questionId]) && is_array($arAnswers[$questionId])) {
            foreach ($arAnswers[$questionId] as $answer) {
                // Ищем вариант "Да" - проверяем по ID или по тексту
                if (isset($answer['ID']) && $answer['ID'] == 6) {
                    $checkbox6AnswerId = $answer['ID'];
                    break;
                } elseif (isset($answer['MESSAGE']) && stripos($answer['MESSAGE'], 'да') !== false) {
                    $checkbox6AnswerId = $answer['ID'];
                    break;
                }
            }
            // Если не нашли, берем первый вариант
            if (!$checkbox6AnswerId && !empty($arAnswers[$questionId])) {
                $checkbox6AnswerId = $arAnswers[$questionId][0]['ID'];
            }
        }
    }
}

// Если не удалось определить через GetDataByID, используем значения по умолчанию
if (!$checkbox5FieldName) $checkbox5FieldName = 'form_checkbox_5';
if (!$checkbox6FieldName) $checkbox6FieldName = 'form_checkbox_6';
if (!$checkbox5AnswerId) $checkbox5AnswerId = 5;
if (!$checkbox6AnswerId) $checkbox6AnswerId = 6;

// Подготавливаем данные для сохранения
$arValues = array();

// Получаем данные из POST
$arValues['form_text_1'] = $name;        // Поле ID = 1 (Имя) - тип: text
$arValues['form_text_2'] = $email;       // Поле ID = 2 (E-mail) - тип: text
$arValues['form_text_3'] = $phone;       // Поле ID = 3 (Телефон) - тип: text
$arValues['form_textarea_4'] = $message; // Поле ID = 4 (Сообщение) - тип: textarea

// Для чекбоксов - используем реальные имена полей и ID вариантов ответов
// В Bitrix для чекбоксов нужно использовать строковые значения ID вариантов ответов
$personalDataValue = $request->getPost('form_checkbox_5');
if (is_array($personalDataValue)) {
    // Проверяем наличие ID варианта ответа в массиве (пробуем и строку, и число)
    $found = in_array((string)$checkbox5AnswerId, $personalDataValue) || in_array((int)$checkbox5AnswerId, $personalDataValue);
    if ($found) {
        // Используем строковое значение ID (как в примерах Bitrix)
        $arValues[$checkbox5FieldName] = array((string)$checkbox5AnswerId);
        // Также пробуем стандартное имя на случай, если SID не работает
        $arValues['form_checkbox_5'] = array((string)$checkbox5AnswerId);
    } else {
        $arValues[$checkbox5FieldName] = array();
        $arValues['form_checkbox_5'] = array();
    }
} else {
    // Если пришло не массивом, проверяем через personal_data
    if ($personalData === 'Y') {
        $arValues[$checkbox5FieldName] = array((string)$checkbox5AnswerId);
        $arValues['form_checkbox_5'] = array((string)$checkbox5AnswerId);
    } else {
        $arValues[$checkbox5FieldName] = array();
        $arValues['form_checkbox_5'] = array();
    }
}

$mailingValue = $request->getPost('form_checkbox_6');
if (is_array($mailingValue)) {
    // Проверяем наличие ID варианта ответа в массиве (пробуем и строку, и число)
    $found = in_array((string)$checkbox6AnswerId, $mailingValue) || in_array((int)$checkbox6AnswerId, $mailingValue);
    if ($found) {
        // Используем строковое значение ID (как в примерах Bitrix)
        $arValues[$checkbox6FieldName] = array((string)$checkbox6AnswerId);
        // Также пробуем стандартное имя на случай, если SID не работает
        $arValues['form_checkbox_6'] = array((string)$checkbox6AnswerId);
    } else {
        $arValues[$checkbox6FieldName] = array();
        $arValues['form_checkbox_6'] = array();
    }
} else {
    // Если пришло не массивом, проверяем через mailing
    if ($mailing === 'Y') {
        $arValues[$checkbox6FieldName] = array((string)$checkbox6AnswerId);
        $arValues['form_checkbox_6'] = array((string)$checkbox6AnswerId);
    } else {
        $arValues[$checkbox6FieldName] = array();
        $arValues['form_checkbox_6'] = array();
    }
}

// Сохраняем результат
$RESULT_ID = CFormResult::Add($webFormId, $arValues);

if ($RESULT_ID) {
    // Отправляем уведомления, если настроены
    CFormResult::Mail($RESULT_ID);
    
    echo json_encode(array(
        'status' => 'success',
        'message' => 'Форма успешно отправлена',
        'result_id' => $RESULT_ID
    ));
} else {
    $errorMessage = 'Ошибка сохранения формы';
    
    // Получаем детальную информацию об ошибке
    if (isset($GLOBALS['strError']) && !empty($GLOBALS['strError'])) {
        $errorMessage = $GLOBALS['strError'];
    } elseif (isset($GLOBALS['APPLICATION']) && $GLOBALS['APPLICATION']->GetException()) {
        $ex = $GLOBALS['APPLICATION']->GetException();
        $errorMessage = $ex->GetString();
    }
    
    echo json_encode(array(
        'status' => 'error',
        'message' => $errorMessage
    ));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>

