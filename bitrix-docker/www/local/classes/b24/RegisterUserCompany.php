<?php
namespace OnlineService\B24;
use intec\eklectika\advertising_agent\Company;
use OnlineService\B24\User;
use OnlineService\B24\Request;
class RegisterUserCompany extends Request{
    public function __construct()
    {
    }

    public function isUserRegistered($arFields,$debug = false){
        // найти пользователя в б24 по EMAIL
        $b24User = new \OnlineService\B24\User();

        $userObject = $b24User->isUserRegistered($arFields,$debug);

        // если такой пользователь есть, то вывести предупреждение
        if ($userObject && !empty($userObject)) {
            return $userObject;
        }

        return false;
    }

    private function createCompanyElement($params){
        $company = new \OnlineService\Site\Company();
        $company->createCompanyElement($params);
    }

    private function normalizePhoneForB24(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }
        return '+' . $digits;
    }

    /**
     * Сохраняем ID контакта Bitrix24 в пользовательские UF-поля сайта.
     * Оставляем старое поле для обратной совместимости.
     */
    private function saveBitrix24UserId(int $userId, $contactId): void
    {
        $contactId = (int) $contactId;
        if ($userId <= 0 || $contactId <= 0) {
            return;
        }

        $user = new \CUser;
        $user->Update($userId, [
            "ACTIVE" => "N",
            "UF_B24_USER_ID" => $contactId,
            "UF_BITRIX24_ID" => $contactId,
        ]);
    }

    private function createB24Company(&$arFields)
    {
        global $APPLICATION;

        $this->normalizeCompanyRegistrationFields($arFields);
        $normalizedPhone = $this->normalizePhoneForB24((string)($arFields['PERSONAL_PHONE'] ?? ''));

        $companyId = false;
        $reqFile = [];
        $file = [];
        if( !empty($arFields['UF_REQ']) && !empty($arFields['UF_REQ']['name']) ){
            $file = $arFields['UF_REQ'];

            // Сохраняем в систему Битрикс
            $savedFileId = \CFile::SaveFile($file, 'os_requisites');
            $fileInfo = \CFile::GetFileArray($savedFileId);

            if ($file['error'] === 0) {
                $fileName = $file['name'];
                $filePath = $file['tmp_name'];

                // Читаем содержимое файла
                $fileContent = file_get_contents($filePath);

                if ($fileContent !== false) {
                    // Кодируем в base64
                    $fileData = [
                        $fileName,
                        base64_encode($fileContent),
                    ];

                    // Передаём в поле Bitrix24
                    $arFields['UF_CRM_1755643990423'] = [
                        'fileData' => $fileData
                    ];
                }
            }
			else{
                // Вывести подробную ошибку
                $errorMessage = 'Ошибка загрузки файла реквизитов: ';
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMessage .= 'Размер файла превышает максимально допустимый размер, указанный в php.ini.';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= 'Размер файла превышает максимально допустимый размер, указанный в форме.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= 'Файл был загружен только частично.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= 'Файл не был загружен.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= 'Отсутствует временная папка для загрузки файла.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= 'Не удалось записать файл на диск.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= 'Загрузка файла была остановлена расширением PHP.';
                        break;
                    default:
                        $errorMessage .= 'Неизвестная ошибка (код: ' . $file['error'] . ').';
                        break;
                }
                $APPLICATION->ThrowException($errorMessage);
                return false;
            }
        }

        // данные для контакта
        $dataContact = [
            'fields' => [
                'NAME' => $arFields['NAME'] ?? '',
                'SECOND_NAME' => $arFields['SECOND_NAME'] ?? '',
                'LAST_NAME' => $arFields['LAST_NAME'] ?? '',
                'POST' => $arFields['WORK_POSITION'] ?? '',
                'BIRTHDATE' => $arFields['PERSONAL_BIRTHDAY'] ?? '',
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => 3036,
                'UF_CRM_3804624445810' => $arFields['UF_CITY'] ?? '',
                'PHONE' => [[
                    "VALUE" => ($normalizedPhone !== '' ? $normalizedPhone : ($arFields['PERSONAL_PHONE'] ?? '')),
                    "VALUE_TYPE" => "WORK"
                ]],
                'EMAIL' => [ [
                    "VALUE" => $arFields['EMAIL'],
                    "VALUE_TYPE" => "WORK"
                ]]
            ],
            'params' => []
        ];

        // если это компания или рекламный агент (ajax-регистрация: UF_TYPE=5 задаётся вместе с ИНН)
        $ufType = (string) ($arFields['UF_TYPE'] ?? '');
        if ($ufType === '5' || $ufType === '6') {
            // проверить заполненность ИНН и названия компании
            if (empty($arFields['UF_INN']) && empty($arFields['UF_NAME_COMPANY'])) {
                $APPLICATION->ThrowException('Вы регистрируйтесь как рекламный агент или юридическое лицо. Поля "Название компании", "ИНН организации" обязательно для заполнения!');
                return false;
            } else {
                // если это рекламный агент
                if ($arFields['UF_ADVERSTERING_AGENT'] == 'on') {
                    $dataContact['fields']['UF_CRM_1701839165901'] = "Пользователь зарегистрировался как рекламный агент";
                }
                $dataRequisite = [
                    'fields' => [],
                    'params' => [],
                    'select' => [
                        'ID',
                        'RQ_INN',
                        'ENTITY_ID'
                    ],
                    'filter' => [
                        'RQ_INN' => $arFields['UF_INN']
                    ]
                ];
                // найти реквизит по ИНН
                $dataRequisite = self::restRequest("crm.requisite.list", $dataRequisite, false);

                if (!empty($dataRequisite)) {			
					//pre($dataRequisite);
                    $dataContact['fields']['COMPANY_ID'] = $dataRequisite[0]['ENTITY_ID'];
                    $companyId = $dataRequisite[0]['ENTITY_ID'];

                    $companyElementParamss = [
                        'OS_COMPANY_INN' => $arFields['UF_INN'],
                        'OS_COMPANY_WEB_SITE' => $arFields['UF_SITE'],
                        'OS_COMPANY_NAME' => $arFields['UF_NAME_COMPANY'],
                        'OS_COMPANY_EMAIL' => $arFields['EMAIL'],
                        'OS_COMPANY_PHONE' => $arFields['PERSONAL_PHONE'],
                        'OS_COMPANY_B24_ID' => $companyId,
                        'OS_COMPANY_CITY' => $arFields['UF_CITY'],
                        'LEGAN_ENTITY_FILE' => $arFields['UF_CRM_1755643990423']
                    ];
                    if( isset($arFields['USER_ID']) ){
                        $companyElementParamss['USER_ID'] = $arFields['USER_ID'];
                        $dataContact['fields']['UF_CRM_3804624445748'] = $arFields['USER_ID'];
                    }

                    $this->createCompanyElement($companyElementParamss);
                } else {
                    /*Создание компании*/
                    $qrCompanyInfo = [
                        'fields' => [
                            'TITLE' => $arFields['UF_NAME_COMPANY'],
                            'PHONE' => [[
                                'VALUE' => ($normalizedPhone !== '' ? $normalizedPhone : ($arFields['PERSONAL_PHONE'] ?? '')),
                                'VALUE_TYPE' => "WORK"
                            ]],
                            'EMAIL' => [[
                                'VALUE' => $arFields['EMAIL'],
                                'VALUE_TYPE' => "WORK"
                            ]],
                            'WEB' => [[
                                'VALUE' => $arFields['UF_SITE'],
                                "VALUE_TYPE" => "WORK"
                            ]],
                            'UF_CRM_1669208000616' => $arFields['UF_SPERE'],
                            'UF_CRM_1669208295583' => $arFields['UF_JUR_ADDRESS'],
                            'UF_CRM_1618551330657' => $arFields['UF_CITY'],
                            'UF_CRM_1755643990423' => $arFields['UF_CRM_1755643990423'],
                            'COMPANY_TYPE' => 'CUSTOMER',
                            'ASSIGNED_BY_ID' => 3036,
                        ]
                    ];

                    $companyId = self::restRequest("crm.company.add", $qrCompanyInfo);
					
                    if (!empty($companyId)) {
                        $qrCompany['id'] = $companyId;
                        $dataCompany = self::restRequest("crm.company.get", $qrCompany);

                        /*Добавление реквизита к компании*/
                        $qrRequisite = [
                            'fields' => [
                                'ENTITY_ID' => $dataCompany['ID'],
                                'ENTITY_TYPE_ID' => '4',
                                'NAME' => 'Реквизит с формы сайта',
                                'PRESET_ID' => 1
                            ]
                        ];
                        $requisiteId = self::restRequest("crm.requisite.add", $qrRequisite);

                        /*Обновление реквизитов у компании*/
                        $qrRequisites = array(
                            'id' => $requisiteId,
                            'fields' => [
                                'ENTITY_ID' => $dataCompany['ENTITY_ID'],
                                'ENTITY_TYPE_ID' => '4',
                                'RQ_INN' => $arFields['UF_INN'],
                                'RQ_KPP' => $arFields['UF_KPP'],
                                'RQ_COMPANY_FULL_NAME' => $arFields['UF_NAME_COMPANY']
                            ]
                        );
                        self::restRequest("crm.requisite.update", $qrRequisites);

                        $companyElementParamss = [
                            'OS_COMPANY_INN' => $arFields['UF_INN'],
                            'OS_COMPANY_WEB_SITE' => $arFields['UF_SITE'],
                            'OS_COMPANY_NAME' => $arFields['UF_NAME_COMPANY'],
                            'OS_COMPANY_EMAIL' => $arFields['EMAIL'],
                            'OS_COMPANY_PHONE' => $arFields['PERSONAL_PHONE'],
                            'OS_COMPANY_B24_ID' => $dataCompany['ID'],
                            'OS_COMPANY_CITY' => $arFields['UF_CITY'],
                            'LEGAN_ENTITY_FILE' => $arFields['UF_CRM_1755643990423']
                        ];
                        if( isset($arFields['USER_ID']) ){
                            $companyElementParamss['USER_ID'] = $arFields['USER_ID'];
                            $dataContact['fields']['UF_CRM_3804624445748'] = $arFields['USER_ID'];
                        }
                        $dataContact['fields']['COMPANY_ID'] = $dataCompany['ID'];

                        $this->createCompanyElement($companyElementParamss);


                        /*\OnlineService\Site\Company::updateB24Company([
                            'ID' => $companyId,
                            'UF_CRM_1755643990423' => $reqFile
                        ]);*/
                    }
                }
            }
        }

        if (!empty($arFields['USER_ID'])) {
            $dataContact['fields']['UF_CRM_1776075126830'] = (int)$arFields['USER_ID'];
        }

        $contactId = self::restRequest("crm.contact.add", $dataContact);

        if (!empty($companyId) && !empty($contactId)) {
            // добавить контакт в компанию
            $qrCompanyAddContact = [
                'fields' => ['COMPANY_ID' => $companyId],
                'id' => $contactId
            ];
            self::restRequest("crm.contact.company.add", $qrCompanyAddContact);
        }

        return true;
    } 


    public function OnBeforeUserRegisterHandler(&$arFields) {
        global $APPLICATION;

        $arFields['ACTIVE'] = 'N';

        // Этап 1: дубликаты среди пользователей сайта (b_user), без обращения к CRM
        $siteDupId = $this->findDuplicateSiteUserId($arFields);
        if ($siteDupId !== null) {
            $APPLICATION->ThrowException(
                'Пользователь с таким e-mail или телефоном уже зарегистрирован на сайте. Вы можете <a href="/personal/profile/">авторизоваться</a> или <a href="/personal/profile/?forgot_password=yes">восстановить пароль</a>.',
                'already_registered'
            );
            return false;
        }

        // Этап 2: контакт в Bitrix24 (как раньше)
        $response = $this->isUserRegistered($arFields);

        if( !$response ){
            if ($arFields['PASSWORD'] == $arFields['CONFIRM_PASSWORD']) {

                //$createResult = $this->createB24Company($arFields);
                /*if ($createResult === false) {
                    // Если createB24Company вернул false, значит была ошибка
                    // Исключение уже было выброшено в createB24Company
                    return false;
                }*/
                $arFields['UF_ADVERSTERING_AGENT'] = "";
                return $arFields;
            }
        }
        else{
            // Определяем какое поле использовать для сообщения об ошибке
            if (isset($response['PHONE']) && !empty($response['PHONE']) || isset($response['EMAIL']) && !empty($response['EMAIL'])) {
                $APPLICATION->ThrowException('Пользователь с указанными почтой или телефоном уже есть в CRM. Вы можете <a href="/personal/profile/">авторизоваться</a> или <a href="/personal/profile/?forgot_password=yes">восстановить пароль</a>','already_registered');
            } else {
                $APPLICATION->ThrowException('Что-то пошло не так.','already_registered');
            }

            return false;
        }
    }

    /**
     * Поиск существующего пользователя на сайте по e-mail или телефону (нормализация цифр).
     */
    private function findDuplicateSiteUserId(array $arFields): ?int
    {
        $email = trim((string)($arFields['EMAIL'] ?? ''));
        if ($email !== '' && stripos($email, '@temp.eklektika.local') === false) {
            // CUser::GetList($by, $order, $arFilter, $arParams) — фильтр третьим аргументом, не вторым
            $rs = \CUser::GetList(
                ['ID' => 'ASC'],
                '',
                ['=EMAIL' => $email],
                ['FIELDS' => ['ID'], 'NAV_PARAMS' => ['nTopCount' => 1]]
            );
            if ($row = $rs->Fetch()) {
                return (int)$row['ID'];
            }
        }

        foreach ($this->collectNormalizedPhoneKeysFromArFields($arFields) as $key) {
            if ($key === '') {
                continue;
            }
            $id = $this->findSiteUserIdByPhoneKey($key);
            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function collectNormalizedPhoneKeysFromArFields(array $arFields): array
    {
        $keys = [];
        foreach (['PERSONAL_PHONE', 'WORK_PHONE', 'PERSONAL_MOBILE'] as $field) {
            if (empty($arFields[$field])) {
                continue;
            }
            $k = $this->normalizePhoneDigitsForCompare((string)$arFields[$field]);
            if ($k !== '') {
                $keys[] = $k;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Последние 10 цифр национального номера (РФ: 8/7 + 10 цифр → сравниваем по 10).
     */
    private function normalizePhoneDigitsForCompare(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);
        if ($d === '') {
            return '';
        }
        if (strlen($d) === 11 && ($d[0] === '8' || $d[0] === '7')) {
            $d = '7' . substr($d, 1);
        }
        if (strlen($d) >= 10) {
            return substr($d, -10);
        }

        return $d;
    }

    private function findSiteUserIdByPhoneKey(string $key): ?int
    {
        if (strlen($key) < 10) {
            return null;
        }
        global $DB;
        $sub = $DB->ForSql($key);
        $sql = "
            SELECT ID, PERSONAL_PHONE, WORK_PHONE, PERSONAL_MOBILE
            FROM b_user
            WHERE ID > 0
              AND (
                (PERSONAL_PHONE IS NOT NULL AND PERSONAL_PHONE != '' AND PERSONAL_PHONE LIKE '%" . $sub . "%')
                OR (WORK_PHONE IS NOT NULL AND WORK_PHONE != '' AND WORK_PHONE LIKE '%" . $sub . "%')
                OR (PERSONAL_MOBILE IS NOT NULL AND PERSONAL_MOBILE != '' AND PERSONAL_MOBILE LIKE '%" . $sub . "%')
              )
            LIMIT 50
        ";
        $rs = $DB->Query($sql);
        while ($row = $rs->Fetch()) {
            foreach (['PERSONAL_PHONE', 'WORK_PHONE', 'PERSONAL_MOBILE'] as $field) {
                if (empty($row[$field])) {
                    continue;
                }
                if ($this->normalizePhoneDigitsForCompare((string)$row[$field]) === $key) {
                    return (int)$row['ID'];
                }
            }
        }

        return null;
    }

    /**
     * Поля формы ajax и старой регистрации: WORK_COMPANY / WORK_WWW / PERSONAL_STREET / UF_WORK_PROFILE
     * приводим к UF_NAME_COMPANY, UF_SITE, UF_JUR_ADDRESS, UF_SPERE; при ИНН без UF_TYPE — юр.лицо (5).
     */
    private function normalizeCompanyRegistrationFields(array &$arFields): void
    {
        if (empty($arFields['UF_NAME_COMPANY']) && !empty($arFields['WORK_COMPANY'])) {
            $arFields['UF_NAME_COMPANY'] = $arFields['WORK_COMPANY'];
        }
        if (empty($arFields['UF_SITE']) && !empty($arFields['WORK_WWW'])) {
            $arFields['UF_SITE'] = $arFields['WORK_WWW'];
        }
        if (empty($arFields['UF_SPERE']) && !empty($arFields['UF_WORK_PROFILE'])) {
            $arFields['UF_SPERE'] = $arFields['UF_WORK_PROFILE'];
        }
        if (empty($arFields['UF_JUR_ADDRESS']) && !empty($arFields['PERSONAL_STREET'])) {
            $arFields['UF_JUR_ADDRESS'] = $arFields['PERSONAL_STREET'];
        }
        $ufType = (string) ($arFields['UF_TYPE'] ?? '');
        if ($ufType === '' && !empty($arFields['UF_INN'])
            && (!empty($arFields['UF_NAME_COMPANY']) || !empty($arFields['WORK_COMPANY']))
        ) {
            $arFields['UF_TYPE'] = '5';
        }
    }

    public function OnAfterUserRegisterHandler(&$arFields) {
        // если регистрация успешна то
        if($arFields["USER_ID"]>0)
        {
            $response = $this->isUserRegistered($arFields,false);

            if( !$response ){
                $createResult = $this->createB24Company($arFields);

                $response = $this->isUserRegistered($arFields,false);
            }

            if( $response ){
                $contactId = $response['ID'];
                $userId = (int)($arFields["USER_ID"] ?? 0);

                // Обновляем пользователя, записываем $contactId в UF_B24_USER_ID и UF_BITRIX24_ID
                $this->saveBitrix24UserId($userId, $contactId);

                if ($userId > 0 && (int)$contactId > 0) {
                    self::restRequest("crm.contact.update", [
                        'id' => (int)$contactId,
                        'fields' => [
                            'UF_CRM_1776075126830' => $userId,
                        ],
                    ]);
                }

                /*$event = new \CEvent;
                $event->SendImmediate("NEW_USER", SITE_ID, $arFields);*/

                unset($arFields["PASSWORD"]);
                unset($arFields["CONFIRM_PASSWORD"]);

                \Bitrix\Main\Mail\Event::send([
                    'EVENT_NAME' => 'NEW_USER_CONFIRM',
                    'LID' => 's1', // ID вашего сайта
                    'C_FIELDS' => $arFields,
                ]);
            }
        }
    }
    
    private function deleteStaffB24($arUser, $companyId, $idCompanySite) {
        $qrList = [
            'fields' => [],
            'params' => [],
            'select' => [],
            'filter' => ["EMAIL" => $arUser["EMAIL"]]
        ];
        $arResult = self::restRequest("crm.contact.list", $qrList);

        if ($arResult['ID']) {
            // убрать рекламную агентность		
            self::restRequest("crm.contact.update", [
                "id" => $arResult['ID'],
                "fields" => [
                    'UF_CRM_1698752707853' => ''
                ]
            ]);
            intec\eklectika\advertising_agent\Client::eraseStatusRA($arUser["ID"], $idCompanySite);

            // уволить его!		
            self::restRequest("crm.contact.company.delete", [
                'id' => $arResult['ID'],
                'fields' => array('COMPANY_ID' => $companyId),
            ]);
            // прощай сотрудник, ты больше нам не нужен =(
        }
    }
}