<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use Bitrix\Main\Request;

/**
 * Нормализация и разбор полей формы публичной регистрации.
 */
final class AjaxRegisterPostParser
{
    /**
     * @return array<string, string>
     */
    public static function parse(Request $request): array
    {
        $mobilephone = \trim((string) $request->getPost('mobilephone'));
        if ($mobilephone === '') {
            $mobilephone = \trim((string) $request->getPost('mobile-phone'));
        }
        if ($mobilephone === '') {
            $mobilephone = \trim((string) $request->getPost('mobile_phone'));
        }

        return [
            'name' => \trim((string) $request->getPost('name')),
            'lastname' => \trim((string) $request->getPost('lastname')),
            'mobilephone' => $mobilephone,
            'phone' => \trim((string) $request->getPost('main-phone')),
            'address' => \trim((string) $request->getPost('address')),
            'inn' => self::normalizeInn(\trim((string) $request->getPost('inn'))),
            'activities' => \trim((string) $request->getPost('activities')),
            'name_company' => \trim((string) $request->getPost('name_company')),
            'sait' => \trim((string) $request->getPost('sait')),
            'email' => \trim((string) $request->getPost('email')),
            'password' => (string) $request->getPost('password'),
            'password_confirm' => (string) $request->getPost('password_confirm'),
        ];
    }

    /**
     * @param array<string, string> $post
     *
     * @return list<string>
     */
    public static function collectMissingRequiredFields(array $post): array
    {
        $required = [
            'name' => 'Имя',
            'lastname' => 'Фамилия',
            'phone' => 'Телефон',
            'email' => 'E-mail',
            'name_company' => 'Название юридического лица',
            'inn' => 'ИНН организации',
            'password' => 'Пароль',
        ];
        $missing = [];
        foreach ($required as $postKey => $label) {
            if ($post[$postKey] === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public static function normalizeInn(string $inn): string
    {
        return (string) \preg_replace('/\D+/', '', $inn);
    }
}

