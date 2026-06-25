<?php

/**
 * Сохранение результатов кастомных HTML-форм в модуль form (CFormResult).
 */
final class WebFormSubmissionHandler 
{
    /**
     * @param array<string, mixed> $input
     * @return array{status: string, message: string, result_id?: int, web_form?: int}
     */
    public static function submit(int $webFormKey, array $input): array
    {
        $formMap = WebFormRegistry::getFormIdMap();
        if (!isset($formMap[$webFormKey])) {
            return [
                'status' => 'error',
                'message' => 'Неизвестный WEB_FORM',
            ];
        }

        $webFormId = (int)$formMap[$webFormKey];
        $parsed = self::parseInput($input);
        $errors = self::validate($webFormKey, $parsed);
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => implode(', ', $errors),
            ];
        }

        if (!CModule::IncludeModule('form')) {
            return [
                'status' => 'error',
                'message' => 'Модуль form не подключен',
            ];
        }

        $arForm = [];
        $arQuestions = [];
        $arAnswers = [];
        $arDropDown = [];
        $arMultiSelect = [];
        CForm::GetDataByID($webFormId, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect);

        $fieldMap = WebFormRegistry::getFieldMap($webFormKey);
        if ($fieldMap !== null) {
            $arValues = self::buildExplicitValues($fieldMap, $parsed, $arQuestions, $arAnswers);
        } else {
            $arValues = self::buildDynamicValues($parsed, $arQuestions, $arAnswers);
        }

        $resultId = (int)CFormResult::Add($webFormId, $arValues, 'N');
        if ($resultId > 0) {
            self::finalizeResult($webFormId, $resultId);

            return [
                'status' => 'success',
                'message' => 'Форма успешно отправлена',
                'result_id' => $resultId,
                'web_form' => $webFormKey,
            ];
        }

        $errorMessage = 'Ошибка сохранения формы';
        if (!empty($GLOBALS['strError'])) {
            $errorMessage = (string)$GLOBALS['strError'];
        } elseif (isset($GLOBALS['APPLICATION']) && $GLOBALS['APPLICATION']->GetException()) {
            $ex = $GLOBALS['APPLICATION']->GetException();
            $errorMessage = $ex->GetString();
        }

        return [
            'status' => 'error',
            'message' => $errorMessage,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{name: string, email: string, phone: string, message: string, is_personal_data: bool, is_mailing: bool}
     */
    private static function parseInput(array $input): array
    {
        $personalData = $input['personal_data'] ?? $input['agree2'] ?? null;
        $mailing = $input['mailing'] ?? $input['agree1'] ?? null;

        return [
            'name' => trim((string)($input['name'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'phone' => trim((string)($input['phone'] ?? '')),
            'message' => trim((string)($input['message'] ?? '')),
            'is_personal_data' => !empty($personalData),
            'is_mailing' => !empty($mailing),
        ];
    }

    /**
     * @param array{name: string, email: string, phone: string, message: string, is_personal_data: bool, is_mailing: bool} $parsed
     * @return list<string>
     */
    private static function validate(int $webFormKey, array $parsed): array
    {
        $rules = WebFormRegistry::getValidationRules($webFormKey);
        $fieldMap = WebFormRegistry::getFieldMap($webFormKey);
        $errors = [];

        if ($parsed['name'] === '') {
            $errors[] = 'Не указано имя';
        }
        if (!empty($rules['email_required']) && ($parsed['email'] === '' || !filter_var($parsed['email'], FILTER_VALIDATE_EMAIL))) {
            $errors[] = 'Не указан или некорректный email';
        }
        if ($parsed['phone'] === '') {
            $errors[] = 'Не указан телефон';
        }
        if (!empty($rules['message_required']) && $parsed['message'] === '') {
            $errors[] = 'Не указано сообщение';
        }

        $personalRequired = true;
        if ($fieldMap !== null && isset($fieldMap['personal_data']['required'])) {
            $personalRequired = (bool)$fieldMap['personal_data']['required'];
        }
        if ($personalRequired && !$parsed['is_personal_data']) {
            $errors[] = 'Необходимо согласие на обработку персональных данных';
        }

        return $errors;
    }

    /**
     * @param array<string, array{key: string, type?: string, question_sid?: string, answer_id?: string, required?: bool}> $fieldMap
     * @param array{name: string, email: string, phone: string, message: string, is_personal_data: bool, is_mailing: bool} $parsed
     * @param array<int, array<string, mixed>> $arQuestions
     * @param array<int, array<int, array<string, mixed>>> $arAnswers
     * @return array<string, mixed>
     */
    private static function buildExplicitValues(array $fieldMap, array $parsed, array $arQuestions, array $arAnswers): array
    {
        $arValues = [];

        foreach ($fieldMap as $inputKey => $config) {
            $bitrixKey = (string)$config['key'];
            $type = (string)($config['type'] ?? 'text');

            if ($type === 'checkbox') {
                $isChecked = $inputKey === 'mailing' ? $parsed['is_mailing'] : $parsed['is_personal_data'];
                $answerId = isset($config['answer_id']) && $config['answer_id'] !== ''
                    ? (string)$config['answer_id']
                    : self::resolveCheckboxAnswerId((string)($config['question_sid'] ?? ''), $arQuestions, $arAnswers);
                $answerId = $answerId !== null ? (string)$answerId : '';
                $arValues[$bitrixKey] = ($isChecked && $answerId !== '') ? [$answerId] : [];
                continue;
            }

            $arValues[$bitrixKey] = $parsed[$inputKey] ?? '';
        }

        return $arValues;
    }

    /**
     * @param array{name: string, email: string, phone: string, message: string, is_personal_data: bool, is_mailing: bool} $parsed
     * @param array<int, array<string, mixed>> $arQuestions
     * @param array<int, array<int, array<string, mixed>>> $arAnswers
     * @return array<string, mixed>
     */
    private static function buildDynamicValues(array $parsed, array $arQuestions, array $arAnswers): array
    {
        $normalize = static function ($value) {
            $value = (string)$value;
            return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        };

        $arValues = [];

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
                $value = $parsed['name'];
            } elseif (strpos($lowerSid, 'email') !== false || strpos($lowerTitle, 'email') !== false || strpos($lowerTitle, 'e-mail') !== false) {
                $value = $parsed['email'];
            } elseif (strpos($lowerSid, 'phone') !== false || strpos($lowerTitle, 'телефон') !== false) {
                $value = $parsed['phone'];
            } elseif (strpos($lowerSid, 'message') !== false || strpos($lowerTitle, 'сообщение') !== false) {
                $value = $parsed['message'];
            } elseif (
                strpos($lowerSid, 'personal') !== false
                || strpos($lowerSid, 'agree2') !== false
                || strpos($lowerTitle, 'политики конфиденциальности') !== false
            ) {
                $value = self::checkboxValue($parsed['is_personal_data'], $fieldType, $questionId, $arAnswers, $normalize);
            } elseif (
                strpos($lowerSid, 'mailing') !== false
                || strpos($lowerSid, 'agree1') !== false
                || strpos($lowerTitle, 'рассыл') !== false
            ) {
                $value = self::checkboxValue($parsed['is_mailing'], $fieldType, $questionId, $arAnswers, $normalize);
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

        return $arValues;
    }

    /**
     * @param array<int, array<string, mixed>> $arQuestions
     * @param array<int, array<int, array<string, mixed>>> $arAnswers
     */
    private static function resolveCheckboxAnswerId(string $questionSid, array $arQuestions, array $arAnswers): ?string
    {
        $normalize = static function ($value) {
            $value = (string)$value;
            return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        };

        $targetSid = $normalize($questionSid);

        foreach ($arQuestions as $questionId => $question) {
            if (!is_array($question)) {
                continue;
            }
            $sid = $normalize((string)($question['SID'] ?? ''));
            if ($sid !== $targetSid) {
                continue;
            }

            return self::pickCheckboxAnswerId((int)$questionId, $arAnswers, $normalize);
        }

        return null;
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $arAnswers
     * @param callable $normalize
     * @return array<int, string>|string
     */
    private static function checkboxValue(bool $isChecked, string $fieldType, int $questionId, array $arAnswers, callable $normalize)
    {
        if ($fieldType === 'checkbox') {
            $answerId = self::pickCheckboxAnswerId($questionId, $arAnswers, $normalize);
            return ($isChecked && $answerId !== null) ? [$answerId] : [];
        }

        return $isChecked ? 'Y' : '';
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $arAnswers
     * @param callable $normalize
     */
    private static function pickCheckboxAnswerId(int $questionId, array $arAnswers, callable $normalize): ?string
    {
        if (empty($arAnswers[$questionId]) || !is_array($arAnswers[$questionId])) {
            return null;
        }

        foreach ($arAnswers[$questionId] as $answer) {
            $answerText = $normalize((string)($answer['MESSAGE'] ?? ''));
            if (strpos($answerText, 'да') !== false || strpos($answerText, 'соглас') !== false) {
                return (string)$answer['ID'];
            }
        }

        $first = reset($arAnswers[$questionId]);
        if (is_array($first) && isset($first['ID'])) {
            return (string)$first['ID'];
        }

        return null;
    }

    /**
     * Пост-обработка после CFormResult::Add (как при стандартной отправке form.result.new).
     * Add сам не шлёт в CRM и не создаёт событие статистики — только сохраняет запись.
     */
    private static function finalizeResult(int $webFormId, int $resultId): void
    {
        if ($resultId <= 0) {
            return;
        }

        if (class_exists('CFormCRM') && is_callable(['CFormCRM', 'onResultAdded'])) {
            CFormCRM::onResultAdded($webFormId, $resultId);
        } elseif (class_exists('CFormCrm') && is_callable(['CFormCrm', 'AddLead'])) {
            CFormCrm::AddLead($webFormId, $resultId);
        }

        if (is_callable(['CFormResult', 'SetEvent'])) {
            CFormResult::SetEvent($resultId);
        }

        CFormResult::Mail($resultId);
    }
}
