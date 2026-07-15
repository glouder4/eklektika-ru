<?php

/**
 * Реестр конфигураций веб-форм: маппинг UI-полей → ключи CFormResult::Add.
 */
final class WebFormRegistry
{
    /**
     * @return array<int, int> WEB_FORM key → Bitrix web form ID
     */
    public static function getFormIdMap(): array
    {
        return [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ];
    }

    /**
     * Явный маппинг полей для форм с известной схемой.
     *
     * @return array<string, array{key: string, type?: string, question_sid?: string, answer_id?: string, required?: bool}>|null
     */
    public static function getFieldMap(int $webFormKey): ?array
    {
        $maps = [
            1 => [
                'name' => ['key' => 'form_text_1'],
                'email' => ['key' => 'form_text_2'],
                'phone' => ['key' => 'form_text_3'],
                'message' => ['key' => 'form_textarea_4'],
                'personal_data' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_128',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_128',
                    'answer_id' => '5',
                    'required' => true,
                ],
                'mailing' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_635',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_635',
                    'answer_id' => '6',
                ],
            ],
            2 => [
                'name' => ['key' => 'form_text_7'],
                'email' => ['key' => 'form_text_8'],
                'phone' => ['key' => 'form_text_9'],
                'message' => ['key' => 'form_textarea_10'],
                'mailing' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_631',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_631',
                    'answer_id' => '11',
                ],
                'personal_data' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_706',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_706',
                    'answer_id' => '12',
                    'required' => true,
                ],
            ],
            3 => [
                'name' => ['key' => 'form_text_13'],
                'phone' => ['key' => 'form_text_14'],
                'mailing' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_182',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_182',
                    'answer_id' => '15',
                ],
                'personal_data' => [
                    'key' => 'form_checkbox_SIMPLE_QUESTION_240',
                    'type' => 'checkbox',
                    'question_sid' => 'SIMPLE_QUESTION_240',
                    'answer_id' => '16',
                    'required' => true,
                ], 
            ],
            4 => [
                'company_name' => [
                    'key' => 'form_textarea_17',
                    'required' => true,
                ],
                'company_site' => [
                    'key' => 'form_textarea_18',
                ],
                'target_audience' => [
                    'key' => 'form_dropdown_19',
                    'type' => 'dropdown',
                ],
                'competitors' => [
                    'key' => 'form_textarea_20',
                ],
                'business_sphere' => [
                    'key' => 'form_dropdown_21',
                    'type' => 'dropdown',
                ],
                'brandbook' => [
                    'key' => 'form_textarea_22',
                ],
                'document' => [
                    'key' => 'form_file_23',
                    'type' => 'file',
                ],
                'layout_specs' => [
                    'key' => 'form_textarea_24',
                ],
                'event_type' => [
                    'key' => 'form_dropdown_25',
                    'type' => 'dropdown',
                ],
                'layout_info' => [
                    'key' => 'form_textarea_26',
                ],
                'color_solution' => [
                    'key' => 'form_textarea_27',
                ],
                'style' => [  
                    'key' => 'form_checkbox_28', 
                    'type' => 'checkbox_multiple',
                    'required' => false,
                ],
                'style_comment' => [
                    'key' => 'form_textarea_29',
                ],
                'design_likes' => [
                    'key' => 'form_textarea_30',
                ],
                'design_dislikes' => [
                    'key' => 'form_textarea_31',
                ],
                'additional_requirements' => [
                    'key' => 'form_textarea_32',
                ],
                'phone' => [
                    'key' => 'form_text_33',
                ],
                'name' => [
                    'key' => 'form_text_34',
                    'required' => true,
                ],
                'email' => [
                    'key' => 'form_email_35',
                    'required' => true,
                ],
            ],
        ];

        return $maps[$webFormKey] ?? null;
    }

    /**
     * @return array{email_required?: bool, message_required?: bool}
     */
    public static function getValidationRules(int $webFormKey): array
    {
        $rules = [
            1 => ['email_required' => true,  'message_required' => false],
            2 => ['email_required' => true,  'message_required' => true],
            3 => ['email_required' => false, 'message_required' => false],
            4 => ['email_required' => true,  'message_required' => false,],
        ];

        return $rules[$webFormKey] ?? ['email_required' => true, 'message_required' => false];
    }
}
