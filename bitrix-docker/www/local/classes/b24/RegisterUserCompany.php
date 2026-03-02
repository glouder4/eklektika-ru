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

    private function createB24Company($arFields){
        global $APPLICATION;

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
                'NAME' => $arFields['NAME'],
                'SECOND_NAME' => $arFields['SECOND_NAME'],
                'LAST_NAME' => $arFields['LAST_NAME'],
                'POST' => $arFields['WORK_POSITION'],
                'BIRTHDATE' => $arFields['PERSONAL_BIRTHDAY'],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => 3036,
                'UF_CRM_3804624445810' => $arFields['UF_CITY'],
                'PHONE' => [[
                    "VALUE" => $arFields['PERSONAL_PHONE'],
                    "VALUE_TYPE" => "WORK"
                ]],
                'EMAIL' => [ [
                    "VALUE" => $arFields['EMAIL'],
                    "VALUE_TYPE" => "WORK"
                ]]
            ],
            'params' => []
        ];

        // если это компания или рекламынй агент
        if ($arFields['UF_TYPE'] == '5' || $arFields['UF_TYPE'] == '6') {
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
                $dataRequisite = sendRequestB24("crm.requisite.list", $dataRequisite,false);

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
                                'VALUE' => $arFields['PERSONAL_PHONE'],
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

                    $companyId = sendRequestB24("crm.company.add", $qrCompanyInfo);
					
                    if (!empty($companyId)) {
                        $qrCompany['id'] = $companyId;
                        $dataCompany = sendRequestB24("crm.company.get", $qrCompany);

                        /*Добавление реквизита к компании*/
                        $qrRequisite = [
                            'fields' => [
                                'ENTITY_ID' => $dataCompany['ID'],
                                'ENTITY_TYPE_ID' => '4',
                                'NAME' => 'Реквизит с формы сайта',
                                'PRESET_ID' => 1
                            ]
                        ];
                        $requisiteId = sendRequestB24("crm.requisite.add", $qrRequisite);

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
                        sendRequestB24("crm.requisite.update", $qrRequisites);

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

        $contactId = sendRequestB24("crm.contact.add", $dataContact);

        if (!empty($companyId) && !empty($contactId)) {
            // добавить контакт в компанию
            $qrCompanyAddContact = [
                'fields' => ['COMPANY_ID' => $companyId],
                'id' => $contactId
            ];
            sendRequestB24("crm.contact.company.add", $qrCompanyAddContact);
        }

        return true;
    } 


    public function OnBeforeUserRegisterHandler(&$arFields) {
        global $APPLICATION;

        $arFields['ACTIVE'] = 'N';

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
                $APPLICATION->ThrowException('Пользователь с указанными почтой или телефоном уже существует в системе. Вы можете <a href="/personal/profile/">авторизоваться</a> или <a href="/personal/profile/?forgot_password=yes">восстановить пароль</a>','already_registered');
            } else {
                $APPLICATION->ThrowException('Что-то пошло не так.','already_registered');
            }

            return false;
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

                // Обновляем пользователя, записываем $contactId в UF_B24_USER_ID
                $user = new \CUser;
                $user->Update($arFields["USER_ID"], ["ACTIVE" => "N","UF_B24_USER_ID" => $contactId]);

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
        $arResult = $this->sendB24Request("crm.contact.list", $qrList);

        if ($arResult['ID']) {
            // убрать рекламную агентность		
            sendRequestB24("crm.contact.update", [
                "id" => $arResult['ID'],
                "fields" => [
                    'UF_CRM_1698752707853' => ''
                ]
            ]);
            intec\eklectika\advertising_agent\Client::eraseStatusRA($arUser["ID"], $idCompanySite);

            // уволить его!		
            sendRequestB24("crm.contact.company.delete", [
                'id' => $arResult['ID'],
                'fields' => array('COMPANY_ID' => $companyId),
            ]);
            // прощай сотрудник, ты больше нам не нужен =(
        }
    }
}