<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

$request = Application::getInstance()->getContext()->getRequest();
$webFormKey = (int)($request->getPost('WEB_FORM') ?: $request->getPost('WEB_FORM_ID') ?: 1);
$formMap = array(
    1 => 1, // footer
    2 => 2, // сообщение
    3 => 3, // заказать звонок
);

if (!isset($formMap[$webFormKey])) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Неизвестный WEB_FORM'
    ));
    exit;
}

$webFormId = (int)$formMap[$webFormKey];
$name = trim((string)$request->getPost('name'));
$email = trim((string)$request->getPost('email'));
$phone = trim((string)$request->getPost('phone'));
$message = trim((string)$request->getPost('message'));
$personalData = $request->getPost('personal_data') ?: $request->getPost('agree2');
$mailing = $request->getPost('mailing') ?: $request->getPost('agree1');
$isPersonalDataAccepted = !empty($personalData);
$isMailingAccepted = !empty($mailing);

$errors = array();
if ($name === '') {
    $errors[] = 'Не указано имя';
}
if (in_array($webFormKey, array(1, 2), true) && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    $errors[] = 'Не указан или некорректный email';
}
if ($phone === '') {
    $errors[] = 'Не указан телефон';
}
if ($webFormKey === 2 && $message === '') {
    $errors[] = 'Не указано сообщение';
}
if (!$isPersonalDataAccepted) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

if (!empty($errors)) {
    echo json_encode(array(
        'status' => 'error',
        'message' => implode(', ', $errors)
    ));
    exit;
}

if (!CModule::IncludeModule("form")) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Модуль form не подключен'
    ));
    exit;
}

$arForm = array();
$arQuestions = array();
$arAnswers = array();
$arDropDown = array();
$arMultiSelect = array();
CForm::GetDataByID($webFormId, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect);

$normalize = function($value) {
    $value = (string)$value;
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
};

$arValues = array(
    'form_text_1' => $name,
    'form_text_2' => $email,
    'form_text_3' => $phone,
    'form_textarea_4' => $message,
    'personal_data' => $isPersonalDataAccepted ? 'Y' : '',
    'mailing' => $isMailingAccepted ? 'Y' : '',
);

foreach ($arQuestions as $questionId => $question) {
    if (!is_array($question)) {
        continue;
    }

    $sid = (string)($question['SID'] ?? $question['ID'] ?? '');
    if ($sid === '') {
        continue;
    }

    $lowerSid = $normalize($sid);
    $title = (string)($question['TITLE'] ?? '');
    $lowerTitle = $normalize($title);
    $fieldType = $normalize((string)($question['FIELD_TYPE'] ?? 'text'));

    $value = null;
    if (strpos($lowerSid, 'name') !== false || strpos($lowerTitle, 'имя') !== false || strpos($lowerTitle, 'кого спросить') !== false) {
        $value = $name;
    } elseif (strpos($lowerSid, 'email') !== false || strpos($lowerTitle, 'email') !== false || strpos($lowerTitle, 'e-mail') !== false) {
        $value = $email;
    } elseif (strpos($lowerSid, 'phone') !== false || strpos($lowerTitle, 'телефон') !== false) {
        $value = $phone;
    } elseif (strpos($lowerSid, 'message') !== false || strpos($lowerTitle, 'сообщение') !== false) {
        $value = $message;
    } elseif (
        strpos($lowerSid, 'personal') !== false ||
        strpos($lowerSid, 'agree2') !== false ||
        strpos($lowerTitle, 'политики конфиденциальности') !== false
    ) {
        if ($fieldType === 'checkbox') {
            $answerId = null;
            if (!empty($arAnswers[$questionId]) && is_array($arAnswers[$questionId])) {
                foreach ($arAnswers[$questionId] as $answer) {
                    $answerText = $normalize((string)($answer['MESSAGE'] ?? ''));
                    if (strpos($answerText, 'да') !== false || strpos($answerText, 'соглас') !== false) {
                        $answerId = (string)$answer['ID'];
                        break;
                    }
                }
                if ($answerId === null) {
                    $first = reset($arAnswers[$questionId]);
                    if (is_array($first) && isset($first['ID'])) {
                        $answerId = (string)$first['ID'];
                    }
                }
            }
            $value = $isPersonalDataAccepted && $answerId !== null ? array($answerId) : array();
        } else {
            $value = $isPersonalDataAccepted ? 'Y' : '';
        }
    } elseif (
        strpos($lowerSid, 'mailing') !== false ||
        strpos($lowerSid, 'agree1') !== false ||
        strpos($lowerTitle, 'рассыл') !== false
    ) {
        if ($fieldType === 'checkbox') {
            $answerId = null;
            if (!empty($arAnswers[$questionId]) && is_array($arAnswers[$questionId])) {
                foreach ($arAnswers[$questionId] as $answer) {
                    $answerText = $normalize((string)($answer['MESSAGE'] ?? ''));
                    if (strpos($answerText, 'да') !== false || strpos($answerText, 'соглас') !== false) {
                        $answerId = (string)$answer['ID'];
                        break;
                    }
                }
                if ($answerId === null) {
                    $first = reset($arAnswers[$questionId]);
                    if (is_array($first) && isset($first['ID'])) {
                        $answerId = (string)$first['ID'];
                    }
                }
            }
            $value = $isMailingAccepted && $answerId !== null ? array($answerId) : array();
        } else {
            $value = $isMailingAccepted ? 'Y' : '';
        }
    }

    if ($value === null) {
        continue;
    }

    $fieldPrefix = 'text';
    if ($fieldType === 'textarea') {
        $fieldPrefix = 'textarea';
    } elseif ($fieldType === 'checkbox') {
        $fieldPrefix = 'checkbox';
    } elseif ($fieldType === 'email') {
        $fieldPrefix = 'email';
    }

    $arValues['form_' . $fieldPrefix . '_' . $sid] = $value;
}

// Сохраняем результат
$RESULT_ID = CFormResult::Add($webFormId, $arValues);

if ($RESULT_ID) {
    // Отправляем уведомления, если настроены
    CFormResult::Mail($RESULT_ID);
    
    echo json_encode(array(
        'status' => 'success',
        'message' => 'Форма успешно отправлена',
        'result_id' => $RESULT_ID,
        'web_form' => $webFormKey
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

