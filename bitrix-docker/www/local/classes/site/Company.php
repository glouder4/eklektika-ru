<?php
    namespace OnlineService\Site;

    use OnlineService\B24\User;

    class Company{
        private int $iblock_id = 23;
        private static $codeProps = [
            "LEGAN_ENTITY_NAME",
            "LEGAN_ENTITY_PHONE",
            "LEGAN_ENTITY_ADRESS",
            "LEGAN_ENTITY_ACTIVITY",
            "LEGAN_ENTITY_INN",
            "LEGAN_ENTITY_USERS",
            "LEGAN_ENTITY_IS_HEAD_COMPANY",
            "LEGAN_ENTITY_ID_OF_HEAD_COMPANY",
            "LEGAN_ENTITY_BOSS",
            "LEGAN_ENTITY_WWW",
            "OS_IS_MARKETING_AGENT",
            "LEGAN_ENTITY_CITY",
            "LEGAN_ENTITY_EMAIL",
            "LEGAN_ENTITY_FILE"
        ];

        /**
         * Получить ID инфоблока компаний
         * @return int
         */
        public function getIblockId(): int {
            return $this->iblock_id;
        }

        public function createCompanyElement($params){
            /*$params = [
                'LEGAN_ENTITY_INN'
                'LEGAN_ENTITY_WWW'
                'LEGAN_ENTITY_NAME'
                'LEGAN_ENTITY_EMAIL'
                'LEGAN_ENTITY_PHONE'
                'OS_COMPANY_B24_ID' - ID уже существующей компании
                'LEGAN_ENTITY_CITY'
                'LEGAN_ENTITY_FILE',
                'USER_ID'
            ]; */


            // Ищем существующую компанию по OS_COMPANY_B24_ID
            $existingCompany = $this->getCompanyByB24ID($params['OS_COMPANY_B24_ID']);
            
            if ($existingCompany && !empty($existingCompany['ID'])) {
                // Компания найдена - дописываем пользователя в LEGAN_ENTITY_USERS
                $companyId = $existingCompany['ID'];
                $currentUsers = $existingCompany['LEGAN_ENTITY_USERS'] ?? [];
                
                // Если это массив, добавляем новый ID, иначе создаем массив
                if (is_array($currentUsers)) {
                    if (!in_array($params['USER_ID'], $currentUsers)) {
                        $currentUsers[] = $params['USER_ID'];
                    }
                } else {
                    $currentUsers = [$currentUsers, $params['USER_ID']];
                }
                
                // Обновляем свойство LEGAN_ENTITY_USERS
                \CIBlockElement::SetPropertyValues(
                    $companyId,
                    $this->iblock_id,
                    $currentUsers,
                    'LEGAN_ENTITY_USERS'
                );
                
                return $companyId;
            } else {
                // Компания не найдена - создаем новую
                $el = new \CIBlockElement;

                // Устанавливаем пользователя в LEGAN_ENTITY_USERS для новой компании
                $params['LEGAN_ENTITY_USERS'] = [$params['USER_ID']];

                $arLoadProductArray = [
                    "IBLOCK_SECTION_ID" => false,
                    "IBLOCK_TYPE" => 'personal',
                    "IBLOCK_ID" => $this->iblock_id,
                    "PROPERTY_VALUES" => $params,
                    "NAME" => $params["LEGAN_ENTITY_NAME"],
                    "ACTIVE" => "N",
                    "CODE" => $params["OS_COMPANY_B24_ID"]
                ];

                if ($companyId = $el->Add($arLoadProductArray)) {
                    return $companyId;
                }
                
                return false;
            }
        }

        protected array $orderCustomFieldIds = [8 => "LEGAN_ENTITY_NAME",10 => "LEGAN_ENTITY_INN",12 => "USER_NAME__USER_LASTNAME",13 => "LEGAN_ENTITY_EMAIL",14 => "LEGAN_ENTITY_PHONE"];

        /**
         * Обновляет элемент компании в инфоблоке по B24_ID.
         *
         * @param array $params Массив параметров компании:
         *   - OS_COMPANY_B24_ID (string|int) — ID компании в B24 (обязательный)
         *   - LEGAN_ENTITY_NAME (string) — Название компании
         *   - LEGAN_ENTITY_IS_HEAD_COMPANY (boolean) — Головная компания
         *   - OS_COMPANY_STATUS (string|int) — Статус компании
         *   - LEGAN_ENTITY_USERS (array|int) — ID связанных контактов
         *   - LEGAN_ENTITY_INN (string) — ИНН компании
         *   - LEGAN_ENTITY_CITY (string) — Город компании
         *   - LEGAN_ENTITY_WWW (string) — Сайт компании
         *   - LEGAN_ENTITY_PHONE (string) — Телефон компании
         *   - LEGAN_ENTITY_EMAIL (string) — Email компании
         *   и другие свойства, поддерживаемые инфоблоком.
         *
         * @return int|false ID обновлённой компании или false в случае ошибки
         */
        public function updateCompanyElement($params){
            // Находим компанию по B24_ID
            $b24_id = $params['OS_COMPANY_B24_ID'];
            $company = $this->getCompanyByB24ID($b24_id);

            if ($company && !empty($company['ID'])) {
                // Компания найдена - обновляем
                $companyId = $company['ID'];
                
                if (!empty($params['OS_COMPANY_STATUS'])) {
                    $params['OS_COMPANY_STATUS'] = (new UserGroups([]))->searchGroup($params['OS_COMPANY_STATUS'])['ID'];
                }

                if( $params['LEGAN_ENTITY_USERS'] ){
                    foreach ($params['LEGAN_ENTITY_USERS'] as $key => $b24_id){
                        $user = new User();
                        $userId = $user->getUserIDByB24ID($b24_id);

                        if( $userId ){
                            $params['LEGAN_ENTITY_USERS'][$key] =  $userId;

                            $groups = [];
                            if( $params['OS_IS_MARKETING_AGENT']['VALUE'] ){
                                $groups[] = $user->getMarketingGroupId();
                            }
                            if ($params['OS_COMPANY_STATUS']){
                                $groups[] = $params['OS_COMPANY_STATUS'];
                            }

                            $user->addUserToGroups($userId,$groups);
                        }
                    }
                }

                if (!empty($params['LEGAN_ENTITY_FILE'])) {
                    $fileId = $this->processRequisitesFile($params['LEGAN_ENTITY_FILE']);
                    if ($fileId) {
                        $params['LEGAN_ENTITY_FILE'] = $fileId;
                    }
                }

                if( !empty($params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY']) && $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] ){
                    $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] = $this->getCompanyByB24ID($params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY']);
                }

                // Получаем текущие значения всех свойств компании
                $currentProps = [];
                foreach (self::$codeProps as $code) {
                    $propertyValues = \CIBlockElement::GetProperty(
                        $this->iblock_id,
                        $companyId,
                        [],
                        ["CODE" => $code]
                    );
                    
                    $values = [];
                    $isMultiple = false;
                    while ($prop = $propertyValues->GetNext()) {
                        $values[] = $prop["VALUE"];
                        if ($prop["MULTIPLE"] === "Y") {
                            $isMultiple = true;
                        }
                    }
                    
                    if ($isMultiple) {
                        $currentProps[$code] = $values;
                    } else {
                        $currentProps[$code] = count($values) > 0 ? $values[0] : null;
                    }
                }

                // Формируем массив свойств для обновления - объединяем текущие и новые значения
                $arProps = $currentProps; // Начинаем с текущих значений
                foreach (self::$codeProps as $code) {
                    if (isset($params[$code])) {
                        $arProps[$code] = $params[$code]; // Перезаписываем только переданные значения
                    }
                }

                $params['OS_COMPANY_B24_ID'] = $company['CODE'];

                $arUpdateArray = [
                    "PROPERTY_VALUES" => $arProps,
                    "NAME" => $params["LEGAN_ENTITY_NAME"],
                    "ACTIVE" => $params['ACTIVE'],
                ];

                $el = new \CIBlockElement;
                if ($el->Update($companyId, $arUpdateArray)) {
                    return $companyId;
                } else {
                    return false;
                }
            } else {
                // Компания не найдена - создаем новую
                $companyId = $this->createCompanyFromUpdate($params);
                
                if (!$companyId) {
                    return false;
                }
                
                // После создания компания уже содержит все данные
                return $companyId;
            }
        }

        /**
         * Создает новую компанию на основе данных из updateCompanyElement
         * @param array $params - параметры компании
         * @return int|false - ID созданной компании или false
         */
        private function createCompanyFromUpdate($params){
            if (!\CModule::IncludeModule('iblock')) {
                return false;
            }

            $el = new \CIBlockElement;
            
            // Обрабатываем пользователей
            if (!empty($params['LEGAN_ENTITY_USERS'])) {
                foreach ($params['LEGAN_ENTITY_USERS'] as $key => $b24_id) {
                    $user = new User();
                    $userId = $user->getUserIDByB24ID($b24_id);
                    
                    if ($userId) {
                        $params['LEGAN_ENTITY_USERS'][$key] = $userId;
                        
                        $groups = [];
                        if (!empty($params['OS_IS_MARKETING_AGENT']['VALUE'])) {
                            $groups[] = $user->getMarketingGroupId();
                        }
                        if (!empty($params['OS_COMPANY_STATUS'])) {
                            $statusId = (new UserGroups([]))->searchGroup($params['OS_COMPANY_STATUS'])['ID'];
                            if ($statusId) {
                                $groups[] = $statusId;
                            }
                        }
                        
                        if (!empty($groups)) {
                            $user->addUserToGroups($userId, $groups);
                        }
                    }
                }
            }
            
            // Обрабатываем файл реквизитов
            if (!empty($params['LEGAN_ENTITY_FILE'])) {
                $fileId = $this->processRequisitesFile($params['LEGAN_ENTITY_FILE']);
                if ($fileId) {
                    $params['LEGAN_ENTITY_FILE'] = $fileId;
                }
            }
            
            // Обрабатываем связь с холдингом
            if (!empty($params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'])) {
                $holdingCompany = $this->getCompanyByB24ID($params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY']);
                if ($holdingCompany) {
                    $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] = $holdingCompany['ID'];
                }
            }
            
            // Формируем массив свойств
            $arProps = [];
            foreach (self::$codeProps as $code) {
                if (isset($params[$code])) {
                    $arProps[$code] = $params[$code];
                }
            }
            
            $arFields = [
                'IBLOCK_ID' => $this->iblock_id,
                'IBLOCK_TYPE' => 'personal',
                'NAME' => $params['LEGAN_ENTITY_NAME'] ?? 'Новая компания',
                'CODE' => $params['OS_COMPANY_B24_ID'],
                'ACTIVE' => $params['ACTIVE'] ?? 'N',
                'PROPERTY_VALUES' => $arProps
            ];
            
            $companyId = $el->Add($arFields);
            
            if ($companyId) {
                return $companyId;
            }
            
            return false;
        }

        /**
         * Обрабатывает файл реквизитов - скачивает и сохраняет в Bitrix
         * @param array $fileData - данные файла из B24
         * @return int|false - ID сохраненного файла или false
         */
        private function processRequisitesFile($fileData){
            if (empty($fileData)) {
                return false;
            }
            
            try {
                $downloadableUrl = URL_B24 . $fileData['SUBDIR'] . '/' . urlencode($fileData['FILE_NAME']);
                
                // Куда сохранить
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/os_requisites/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $originalName = $fileData['ORIGINAL_NAME'];
                $filePath = $uploadDir . $originalName;
                
                // Скачиваем файл
                $fileContent = file_get_contents($downloadableUrl);
                
                if ($fileContent === false) {
                    return false;
                }
                
                // Сохраняем на сервер
                if (file_put_contents($filePath, $fileContent)) {
                    // Загружаем файл в Битрикс
                    $fileArray = \CFile::MakeFileArray($filePath, false, $originalName);
                    
                    if ($fileArray && !isset($fileArray['error'])) {
                        // Сохраняем в систему Битрикс
                        $savedFileId = \CFile::SaveFile($fileArray, 'os_requisites');
                        
                        // Удаляем временный файл
                        unlink($filePath);
                        
                        if ($savedFileId) {
                            return $savedFileId;
                        }
                    }
                    
                    // Удаляем временный файл в случае ошибки
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            } catch (\Exception $e) {
                // Ошибка обработки файла
            }
            
            return false;
        }

        public function deleteCompanyElement($params){
            $b24_id = $params['ID'];
            $company = $this->getCompanyByB24ID($b24_id);
            if ($company && !empty($company['ID'])) {
                if (\CIBlockElement::Delete($company['ID'])) {
                    return true;
                }
            }
            return false;
        }

        public function getCompany($id){
            \Bitrix\Main\Loader::includeModule('iblock');
            $rsCompany = \CIBlockElement::GetById($id);
            if($ob = $rsCompany->GetNextElement()) {
                $arProps = $ob->GetProperties();
                $arFields = $ob->GetFields();
                $arCompany["ID"] = $arFields["ID"];
                foreach (self::$codeProps as $code) {
                    $arCompany[$code] = $arProps[$code]["VALUE"];
                    // Для свойств типа "Список" также сохраняем VALUE_XML_ID
                    if (isset($arProps[$code]["VALUE_XML_ID"])) {
                        $arCompany[$code . "_XML_ID"] = $arProps[$code]["VALUE_XML_ID"];
                    }
                }

                return $arCompany;
            }
            return [];
        }

        public function getProfileValues($id){
            global $USER;
            $company = $this->getCompany($id);
            $user = \CUser::GetByID($USER->GetID())->Fetch();

            $response = [];

            foreach ($this->orderCustomFieldIds as $id => $fieldName){
                $response[$id] = $company[$fieldName];
            }
            $response[12] = $user['NAME'].' '.$user['LAST_NAME'];

            return $response;
        }

        public function getCompanyByB24ID($b24_id){
            $rsCompany = \CIBlockElement::GetList(
                [],
                ['CODE' => $b24_id],
                false,
                false,
                ['ID', 'NAME', 'PROPERTY_OS_COMPANY_B24_ID','CODE','XML_ID']
            );  
            
            if ($ob = $rsCompany->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arCompany["ID"] = $arFields["ID"];
                
                // Загружаем свойства через GetPropertyValues для каждого свойства отдельно
                foreach (self::$codeProps as $code) {
                    $propertyValues = \CIBlockElement::GetProperty(
                        $this->iblock_id,
                        $arFields["ID"],
                        [],
                        ["CODE" => $code]
                    );
                    
                    $values = [];
                    $isMultiple = false;
                    while ($prop = $propertyValues->GetNext()) {
                        $values[] = $prop["VALUE"];
                        // Проверяем, является ли свойство множественным
                        if ($prop["MULTIPLE"] === "Y") {
                            $isMultiple = true;
                        }
                    }
                    
                    // Для множественных свойств всегда возвращаем массив
                    if ($isMultiple) {
                        $arCompany[$code] = $values; // Всегда массив для множественных свойств
                    } else {
                        // Для обычных свойств возвращаем первое значение или null
                        $arCompany[$code] = count($values) > 0 ? $values[0] : null;
                    }
                }
                
                return $arCompany;
            }
            
            return false;
        }

        public static function query($url,$params,$debug = false){
            $queryUrl = $url;

            $curl = curl_init();
            $queryData  = http_build_query($params);

            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ));

            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            $curlErrno = curl_errno($curl);

            curl_close($curl);

            if( $debug ){
                // Логируем детали запроса
                pre("=== CURL Request Details ===");
                pre("URL: " . $queryUrl);
                pre("Params: " . print_r($params, true));
                pre("HTTP Code: " . $httpCode);
                pre("CURL Error: " . $curlError);
                pre("CURL Errno: " . $curlErrno);
                pre("Raw Response: " . $result);
            }

            // Обработка ошибок CURL
            if ($curlErrno) {
                pre("CURL Error occurred: " . $curlError);
                return [
                    'success' => 0,
                    'error' => 'CURL Error: ' . $curlError,
                    'errno' => $curlErrno
                ];
            }

            // Обработка HTTP ошибок
            if ($httpCode !== 200) {
                if( $debug )
                    pre("HTTP Error: " . $httpCode);

                return [
                    'success' => 0,
                    'error' => 'HTTP Error: ' . $httpCode,
                    'response' => $result
                ];
            }

            // Парсим JSON ответ
            $decodedResult = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                if( $debug ) {
                    pre("JSON Parse Error: " . json_last_error_msg());
                    pre("Raw response that failed to parse: " . $result);
                }
                return [
                    'success' => 0,
                    'error' => 'JSON Parse Error: ' . json_last_error_msg(),
                    'raw_response' => $result
                ];
            }
            if( $debug ) {
                pre("=== Parsed Response ===");
                pre($decodedResult);
            }

            return $decodedResult;
        }

        /**
         * Синхронизация всех контактов (руководители + сотрудники) между головной компанией и всеми дочерними
         */
        public function syncCompanyContacts($params) {
            try {
                $headCompanyId = $params['COMPANY_ID'] ?? null;
                
                if (!$headCompanyId) {
                    return json_encode(['success' => false, 'error' => 'Не указан ID головной компании']);
                }

                // Получаем данные головной компании
                $headCompany = $this->getCompany($headCompanyId);
                
                // Проверяем, является ли компания головной (используем VALUE_XML_ID как в шаблоне)
                $isHeadOfHolding = $headCompany['LEGAN_ENTITY_IS_HEAD_COMPANY_XML_ID'] ?? $headCompany['LEGAN_ENTITY_IS_HEAD_COMPANY'] ?? '';
                if (!$headCompany || !in_array($isHeadOfHolding, ['Y', 'YES', '1', true])) {
                    return json_encode(['success' => false, 'error' => 'Компания не является головной. Значение: ' . $isHeadOfHolding]);
                }

                // Получаем всех руководителей головной компании
                $headCompanyManagers = $headCompany['LEGAN_ENTITY_BOSS'] ?? [];
                if (!is_array($headCompanyManagers)) {
                    $headCompanyManagers = $headCompanyManagers ? [$headCompanyManagers] : [];
                }

                // Получаем все дочерние компании
                $childCompanies = $this->getChildCompanies($headCompanyId);
                
                // Собираем всех уникальных руководителей из ВСЕХ компаний холдинга
                $allManagers = $headCompanyManagers;
                
                foreach ($childCompanies as $childCompany) {
                    $childCompanyData = $this->getCompany($childCompany['ID']);
                    
                    // Собираем руководителей дочерней компании
                    $childManagers = $childCompanyData['LEGAN_ENTITY_BOSS'] ?? [];
                    if (!is_array($childManagers)) {
                        $childManagers = $childManagers ? [$childManagers] : [];
                    }
                    
                    // Добавляем в общий список (с проверкой на уникальность)
                    foreach ($childManagers as $manager) {
                        if (!empty($manager) && !in_array($manager, $allManagers)) {
                            $allManagers[] = $manager;
                        }
                    }
                }
                
                $updatedCompanies = 0;
                $errors = [];
                $debugInfo = [];

                $debugInfo[] = "Головная компания ID: {$headCompanyId}";
                $debugInfo[] = "Найдено дочерних компаний: " . count($childCompanies);
                $debugInfo[] = "ИТОГО уникальных руководителей: " . count($allManagers);

                // Обновляем руководителей во всех дочерних компаниях (общим списком!)
                foreach ($childCompanies as $childCompany) {
                    $debugInfo[] = "Обновляем компанию: {$childCompany['NAME']} (ID: {$childCompany['ID']})";
                    $result = $this->updateCompanyManagers($childCompany['ID'], $allManagers);
                    if ($result) {
                        $updatedCompanies++;
                        $debugInfo[] = "✓ Компания {$childCompany['NAME']} обновлена успешно";
                    } else {
                        $errors[] = "Ошибка обновления компании {$childCompany['NAME']} (ID: {$childCompany['ID']})";
                        $debugInfo[] = "✗ Ошибка обновления компании {$childCompany['NAME']}";
                    }
                }

                // Также обновляем саму головную компанию (общим списком!)
                $this->updateCompanyManagers($headCompanyId, $allManagers);
                $updatedCompanies++;

                return json_encode([
                    'success' => true,
                    'message' => "Синхронизация завершена. Обновлено компаний: {$updatedCompanies}",
                    'updated_companies' => $updatedCompanies,
                    'errors' => $errors,
                    'managers_count' => count($allManagers),
                    'debug_info' => $debugInfo
                ]);

            } catch (Exception $e) {
                return json_encode(['success' => false, 'error' => 'Ошибка синхронизации: ' . $e->getMessage()]);
            }
        }

        /**
         * Получить все дочерние компании холдинга
         */
        private function getChildCompanies($headCompanyId) {
            $headCompany = $this->getCompany($headCompanyId);
            if (!$headCompany) {
                return [];
            }

            // Ищем все компании, у которых LEGAN_ENTITY_ID_OF_HEAD_COMPANY указывает на головную компанию (по ID элемента)
            $rsCompanies = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $this->iblock_id,
                    'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $headCompanyId,
                    'ACTIVE' => 'Y'
                ],
                false,
                false,
                ['ID', 'NAME', 'CODE', 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY']
            );

            $childCompanies = [];
            while ($ob = $rsCompanies->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $childCompanies[] = [
                    'ID' => $arFields['ID'],
                    'NAME' => $arFields['NAME'],
                    'CODE' => $arFields['CODE'],
                    'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $arProps['LEGAN_ENTITY_ID_OF_HEAD_COMPANY']['VALUE'] ?? null
                ];
            }

            return $childCompanies;
        }

        /**
         * Обновить руководителей компании
         */
        private function updateCompanyManagers($companyId, $managers) {
            try {
                // Убираем пустые значения
                $managers = array_filter($managers, function($manager) {
                    return !empty($manager);
                });

                // Обновляем свойство LEGAN_ENTITY_BOSS
                \CIBlockElement::SetPropertyValues($companyId, $this->iblock_id, $managers, 'LEGAN_ENTITY_BOSS');

                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         * Обновить профиль компании через веб-интерфейс
         * 
         * @param int $companyId - ID компании
         * @param array $data - данные для обновления:
         *   - LEGAN_ENTITY_NAME (string) - название компании
         *   - LEGAN_ENTITY_INN (string) - ИНН
         *   - LEGAN_ENTITY_CITY (string) - город
         *   - LEGAN_ENTITY_PHONE (string) - телефон
         *   - LEGAN_ENTITY_EMAIL (string) - email
         *   - LEGAN_ENTITY_WWW (string) - сайт
         * @param array|null $uploadedFile - данные загруженного файла из $_FILES
         * @param bool $deleteRequisites - флаг удаления файла реквизитов
         * 
         * @return array - результат операции ['success' => bool, 'message' => string, 'data' => array]
         */
        public function updateCompanyProfile($companyId, $data, $uploadedFile = null, $deleteRequisites = false) {
            if (!\CModule::IncludeModule('iblock')) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подключения модуля инфоблоков'
                ];
            }

            // Проверяем существование компании
            $company = $this->getCompany($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Компания не найдена'
                ];
            }

            // Валидация обязательных полей
            $requiredFields = [
                'LEGAN_ENTITY_NAME' => 'Название компании',
                'LEGAN_ENTITY_INN' => 'ИНН',
                'LEGAN_ENTITY_CITY' => 'Город',
                'LEGAN_ENTITY_WWW' => 'Сайт'
            ];

            $errors = [];
            foreach ($requiredFields as $field => $fieldName) {
                if (empty($data[$field])) {
                    $errors[] = $fieldName;
                }
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Не заполнены обязательные поля: ' . implode(', ', $errors)
                ];
            }

            // Валидация email
            if (!empty($data['LEGAN_ENTITY_EMAIL'])) {
                if (!filter_var($data['LEGAN_ENTITY_EMAIL'], FILTER_VALIDATE_EMAIL)) {
                    return [
                        'success' => false,
                        'message' => 'Некорректный формат email'
                    ];
                }
            }

            // Обработка файла реквизитов
            $fileId = null;
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                $fileResult = $this->processUploadedRequisitesFile($uploadedFile);
                if (!$fileResult['success']) {
                    return $fileResult;
                }
                $fileId = $fileResult['file_id'];
            }

            // Обработка удаления файла
            if ($deleteRequisites && !empty($company['LEGAN_ENTITY_FILE'])) {
                \CFile::Delete($company['LEGAN_ENTITY_FILE']);
                $data['LEGAN_ENTITY_FILE'] = '';
            } elseif ($fileId) {
                // Удаляем старый файл только если новый успешно загружен
                if (!empty($company['LEGAN_ENTITY_FILE'])) {
                    \CFile::Delete($company['LEGAN_ENTITY_FILE']);
                }
                $data['LEGAN_ENTITY_FILE'] = $fileId;
            }

            // Начинаем обновление
            $el = new \CIBlockElement();

            // Обновляем название элемента
            $arUpdateFields = [
                'NAME' => $data['LEGAN_ENTITY_NAME']
            ];

            if (!$el->Update($companyId, $arUpdateFields)) {
                return [
                    'success' => false,
                    'message' => 'Ошибка обновления компании: ' . $el->LAST_ERROR
                ];
            }

            // Обновляем свойства
            $fieldsToUpdate = [
                'LEGAN_ENTITY_NAME',
                'LEGAN_ENTITY_INN',
                'LEGAN_ENTITY_CITY',
                'LEGAN_ENTITY_PHONE',
                'LEGAN_ENTITY_EMAIL',
                'LEGAN_ENTITY_WWW'
            ];

            foreach ($fieldsToUpdate as $field) {
                if (isset($data[$field])) {
                    \CIBlockElement::SetPropertyValueCode($companyId, $field, $data[$field]);
                }
            }

            // Обновляем файл реквизитов, если был изменен
            if (isset($data['LEGAN_ENTITY_FILE'])) {
                \CIBlockElement::SetPropertyValueCode($companyId, 'LEGAN_ENTITY_FILE', $data['LEGAN_ENTITY_FILE']);
            }

            // Получаем обновленные данные для ответа
            $rsElement = \CIBlockElement::GetByID($companyId);
            $companyCode = $companyId;
            if ($arElement = $rsElement->Fetch()) {
                $companyCode = $arElement['CODE'] ?? $companyId;
            }

            // Синхронизируем данные с Bitrix24
            /*$b24SyncSuccess = false;
            if (!empty($company['OS_COMPANY_B24_ID'])) {
                // Если файл не был изменен, но существует - добавляем его в данные для синхронизации
                if (!isset($data['LEGAN_ENTITY_FILE']) && !empty($company['LEGAN_ENTITY_FILE'])) {
                    $data['LEGAN_ENTITY_FILE'] = $company['LEGAN_ENTITY_FILE'];
                }
                
                $b24Result = $this->sendToBitrix24($company['OS_COMPANY_B24_ID'], $data);
                $b24SyncSuccess = !empty($b24Result);
            } */

            return [
                'success' => true,
                'message' => 'Данные компании успешно обновлены',
                'data' => [
                    'company_id' => $companyId,
                    'company_code' => $companyCode,
                    //'b24_synced' => $b24SyncSuccess
                ]
            ];
        }

        /**
         * Обработать загруженный файл реквизитов
         * 
         * @param array $uploadedFile - данные из $_FILES
         * @return array - ['success' => bool, 'message' => string, 'file_id' => int|null]
         */
        private function processUploadedRequisitesFile($uploadedFile) {
            // Проверка размера файла (10 МБ)
            $maxFileSize = 10 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'Размер файла превышает 10 МБ'
                ];
            }

            // Проверка расширения файла
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return [
                    'success' => false,
                    'message' => 'Недопустимый формат файла. Разрешены: ' . implode(', ', $allowedExtensions)
                ];
            }

            // Подготавливаем файл для загрузки
            $arFile = $uploadedFile;
            $arFile['MODULE_ID'] = 'iblock';
            
            $fileId = \CFile::SaveFile($arFile, 'company_requisites');
            
            if (!$fileId) {
                return [
                    'success' => false,
                    'message' => 'Ошибка сохранения файла'
                ];
            }

            return [
                'success' => true,
                'file_id' => $fileId
            ];
        }

        /**
         * Проверить права пользователя на редактирование компании
         * 
         * @param int $companyId - ID компании
         * @param int $userId - ID пользователя
         * @return array - ['has_access' => bool, 'message' => string]
         */
        public function checkEditPermission($companyId, $userId) {
            global $USER;

            // Админы могут редактировать любую компанию
            if ($USER->IsAdmin()) {
                return [
                    'has_access' => true
                ];
            }

            // Получаем данные компании
            $company = $this->getCompany($companyId);
            if (!$company) {
                return [
                    'has_access' => false,
                    'message' => 'Компания не найдена'
                ];
            }

            // Проверяем, является ли пользователь руководителем компании
            $bosses = $company['LEGAN_ENTITY_BOSS'] ?? [];
            if (!is_array($bosses)) {
                $bosses = $bosses ? [$bosses] : [];
            }

            if (in_array($userId, $bosses)) {
                return [
                    'has_access' => true
                ];
            }

            return [
                'has_access' => false,
                'message' => 'У вас нет прав для редактирования этой компании'
            ];
        }

        /**
         * Отправить обновленные данные компании в Bitrix24
         *
         * @param int $companyId - ID компании в Bitrix (из CODE элемента)
         * @param array $data - данные компании для отправки:
         *   - LEGAN_ENTITY_NAME (string) - название компании
         *   - LEGAN_ENTITY_INN (string) - ИНН
         *   - LEGAN_ENTITY_CITY (string) - город
         *   - LEGAN_ENTITY_PHONE (string) - телефон
         *   - LEGAN_ENTITY_EMAIL (string) - email
         *   - LEGAN_ENTITY_WWW (string) - сайт
         *   - LEGAN_ENTITY_FILE (int) - ID файла реквизитов в Bitrix
         * @param bool $debug - режим отладки
         * 
         * @return array|false - результат отправки или false при ошибке
         */
        private function sendToBitrix24($companyId, $data, $debug = false) {
            if (empty($companyId)) {
                return false;
            }

            // Маппинг полей сайта на поля Bitrix24
            $b24Fields = [];
            
            // Название компании
            if (!empty($data['LEGAN_ENTITY_NAME'])) {
                $b24Fields['TITLE'] = $data['LEGAN_ENTITY_NAME'];
            }
            
            // ИНН (UF_CRM_1669208589 - пример, может отличаться)
            if (!empty($data['LEGAN_ENTITY_INN'])) {
                $b24Fields['UF_CRM_INN'] = $data['LEGAN_ENTITY_INN'];
            }
            
            // Город/Адрес
            if (!empty($data['LEGAN_ENTITY_CITY'])) {
                $b24Fields['UF_CRM_1669208295583'] = $data['LEGAN_ENTITY_CITY']; // Адрес
            }
            
            // Телефон
            if (!empty($data['LEGAN_ENTITY_PHONE'])) {
                $b24Fields['PHONE'] = [
                    [
                        'VALUE' => $data['LEGAN_ENTITY_PHONE'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }
            
            // Email
            if (!empty($data['LEGAN_ENTITY_EMAIL'])) {
                $b24Fields['EMAIL'] = [
                    [
                        'VALUE' => $data['LEGAN_ENTITY_EMAIL'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }
            
            // Сайт
            if (!empty($data['LEGAN_ENTITY_WWW'])) {
                $b24Fields['WEB'] = [
                    [
                        'VALUE' => $data['LEGAN_ENTITY_WWW'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }

            // Файл реквизитов (как в RegisterUserCompany.php)
            if (!empty($data['LEGAN_ENTITY_FILE'])) {
                $fileId = $data['LEGAN_ENTITY_FILE'];
                
                // Получаем информацию о файле из Bitrix
                $fileInfo = \CFile::GetFileArray($fileId);
                
                if ($fileInfo && !empty($fileInfo['SRC'])) {
                    $filePath = $_SERVER['DOCUMENT_ROOT'] . $fileInfo['SRC'];
                    
                    // Проверяем существование файла
                    if (file_exists($filePath)) {
                        // Читаем содержимое файла
                        $fileContent = file_get_contents($filePath);
                        
                        if ($fileContent !== false) {
                            // Кодируем в base64 и передаем в B24 (как в RegisterUserCompany.php)
                            $b24Fields['UF_CRM_1755643990423'] = [
                                'fileData' => [
                                    $fileInfo['ORIGINAL_NAME'],
                                    base64_encode($fileContent)
                                ]
                            ];
                        }
                    }
                }
            }

            // Отправляем запрос в Bitrix24
            try {
                $result = sendRequestB24('crm.company.update', [
                    'id'     => $companyId,
                    'fields' => $b24Fields,
                ], $debug);

                return $result;
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем процесс
                error_log('Bitrix24 company update error: ' . $e->getMessage());
                return false;
            }
        }

        /**
         * Создать дочернюю компанию (филиал) в холдинге
         * 
         * @param array $data - данные для создания:
         *   - UF_NAME_COMPANY (string) - название компании
         *   - UF_INN (string) - ИНН
         *   - UF_CITY (string) - город
         *   - UF_SITE (string) - сайт
         *   - head_company_element_id (int) - ID головной компании (элемент инфоблока)
         *   - UF_TYPE (string) - тип компании ('5' = юр.лицо, '6' = рекламный агент)
         * @param array|null $uploadedFile - данные загруженного файла из $_FILES['UF_REQ']
         * 
         * @return array - результат операции ['success' => bool, 'message' => string, 'data' => array]
         */
        public function createBranchCompany($data, $uploadedFile = null) {
            if (!\CModule::IncludeModule('iblock')) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подключения модуля инфоблоков'
                ];
            }

            // Валидация обязательных полей
            if (empty($data['UF_NAME_COMPANY']) || empty($data['UF_INN'])) {
                return [
                    'success' => false,
                    'message' => 'Поля "Название компании" и "ИНН организации" обязательны для заполнения'
                ];
            }

            // Проверяем существование головной компании
            $headCompanyId = intval($data['head_company_element_id'] ?? 0);
            if (empty($headCompanyId)) {
                return [
                    'success' => false,
                    'message' => 'Не указана головная компания'
                ];
            }

            $headCompany = $this->getCompany($headCompanyId);
            if (!$headCompany) {
                return [
                    'success' => false,
                    'message' => 'Головная компания не найдена'
                ];
            }

            // Обработка файла реквизитов (как в RegisterUserCompany.php)
            $fileDataB24 = null;
            $savedFileId = null;
            
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                // Сохраняем файл локально
                $savedFileId = \CFile::SaveFile($uploadedFile, 'os_requisites');
                
                if ($savedFileId) {
                    // Подготавливаем для отправки в B24
                    $fileName = $uploadedFile['name'];
                    $filePath = $uploadedFile['tmp_name'];
                    $fileContent = file_get_contents($filePath);
                    
                    if ($fileContent !== false) {
                        // Кодируем в base64 (как в RegisterUserCompany.php)
                        $fileDataB24 = [
                            'fileData' => [
                                $fileName,
                                base64_encode($fileContent)
                            ]
                        ];
                    }
                }
            }

            // Проверяем существование компании с таким ИНН в B24
            $dataRequisite = [
                'fields' => [],
                'params' => [],
                'select' => ['ID', 'RQ_INN', 'ENTITY_ID'],
                'filter' => ['RQ_INN' => $data['UF_INN']]
            ];
            
            $existingRequisite = sendRequestB24("crm.requisite.list", $dataRequisite, false);
            
            if (!empty($existingRequisite)) {
                return [
                    'success' => false,
                    'message' => 'Компания с указанным ИНН уже существует в системе'
                ];
            }

            // Получаем B24 ID головной компании из поля OS_HEAD_COMPANY_B24_ID
            /*$headCompanyB24Id = $headCompany['OS_HEAD_COMPANY_B24_ID'] ?? '';
            
            // Если поле пустое - это критическая ошибка синхронизации
            if (empty($headCompanyB24Id)) {
                error_log('ERROR: OS_HEAD_COMPANY_B24_ID головной компании пустое! Head company ID: ' . $headCompanyId);
                return [
                    'success' => false,
                    'message' => 'Ошибка синхронизации с Bitrix24. Головная компания не имеет связи с CRM системой. Пожалуйста, обратитесь к персональному менеджеру для исправления данной ошибки.'
                ];
            }*/
            
            // Логируем успешное получение
            //error_log('INFO: B24 ID головной компании для UF_CRM_1758028816: ' . $headCompanyB24Id);
            
            // Создаем компанию в Bitrix24
            $b24CompanyFields = [
                'TITLE' => $data['UF_NAME_COMPANY'],
                'WEB' => [[
                    'VALUE' => $data['UF_SITE'] ?? '',
                    'VALUE_TYPE' => 'WORK'
                ]],
                'UF_CRM_1618551330657' => $data['UF_CITY'] ?? '',
                'UF_CRM_1758028816' => $headCompanyB24Id, // ID головной компании в B24
                'COMPANY_TYPE' => 'CUSTOMER',
                'ASSIGNED_BY_ID' => 3036,
            ];

            // Логируем данные отправки в B24 для отладки
            error_log('Creating branch company in B24. Parent B24 ID: ' . $headCompanyB24Id);

            // Добавляем файл реквизитов если есть
            if ($fileDataB24) {
                $b24CompanyFields['UF_CRM_1755643990423'] = $fileDataB24;
            }

            // Создаем компанию в B24
            $companyB24Id = sendRequestB24("crm.company.add", ['fields' => $b24CompanyFields]);
            
            if (empty($companyB24Id)) {
                return [
                    'success' => false,
                    'message' => 'Ошибка создания компании в Bitrix24'
                ];
            }

            // Получаем данные созданной компании из B24
            $dataCompany = sendRequestB24("crm.company.get", ['id' => $companyB24Id]);

            // Привязываем текущего пользователя (руководителя) к созданной компании в B24
            global $USER;
            $currentUser = \CUser::GetByID($USER->GetID())->Fetch();
            
            if ($currentUser && !empty($currentUser['UF_B24_USER_ID'])) {
                $contactId = $currentUser['UF_B24_USER_ID'];
                
                // Добавляем контакт в компанию (как в RegisterUserCompany.php)
                $qrCompanyAddContact = [
                    'fields' => ['COMPANY_ID' => $dataCompany['ID']],
                    'id' => $contactId
                ];
                sendRequestB24("crm.contact.company.add", $qrCompanyAddContact);
                
                error_log('INFO: Контакт руководителя привязан к новой компании. Contact ID: ' . $contactId . ', Company ID: ' . $dataCompany['ID']);
            } else {
                error_log('WARNING: У пользователя нет UF_B24_USER_ID, контакт не привязан к компании');
            }

            // Добавляем реквизит к компании в B24
            $requisiteId = sendRequestB24("crm.requisite.add", [
                'fields' => [
                    'ENTITY_ID' => $dataCompany['ID'],
                    'ENTITY_TYPE_ID' => '4',
                    'NAME' => 'Реквизит с формы сайта',
                    'PRESET_ID' => 1
                ]
            ]);

            // Обновляем реквизиты компании
            if ($requisiteId) {
                sendRequestB24("crm.requisite.update", [
                    'id' => $requisiteId,
                    'fields' => [
                        'ENTITY_ID' => $dataCompany['ENTITY_ID'],
                        'ENTITY_TYPE_ID' => '4',
                        'RQ_INN' => $data['UF_INN'],
                        'RQ_COMPANY_FULL_NAME' => $data['UF_NAME_COMPANY']
                    ]
                ]);
            }

            // Создаем элемент компании на сайте
            $companyElementParams = [
                'LEGAN_ENTITY_INN' => $data['UF_INN'],
                'LEGAN_ENTITY_WWW' => $data['UF_SITE'] ?? '',
                'LEGAN_ENTITY_NAME' => $data['UF_NAME_COMPANY'],
                //'OS_COMPANY_B24_ID' => $dataCompany['ID'],
                'LEGAN_ENTITY_CITY' => $data['UF_CITY'] ?? '',
                'LEGAN_ENTITY_FILE' => $fileDataB24 ?? ''
            ];

            $newCompanyId = $this->createCompanyElement($companyElementParams);
            
            if (!$newCompanyId) {
                return [
                    'success' => false,
                    'message' => 'Ошибка создания компании на сайте'
                ];
            }

            // Синхронизируем руководителей головной компании с дочерней
            $headCompanyManagers = $headCompany['LEGAN_ENTITY_BOSS'] ?? [];
            if (!is_array($headCompanyManagers)) {
                $headCompanyManagers = $headCompanyManagers ? [$headCompanyManagers] : [];
            }

            // Применяем руководителей к дочерней компании
            if (!empty($headCompanyManagers)) {
                \CIBlockElement::SetPropertyValues(
                    $newCompanyId, 
                    $this->iblock_id, 
                    $headCompanyManagers, 
                    'LEGAN_ENTITY_BOSS'
                );
            }

            // Устанавливаем связь с головной компанией (ID элемента инфоблока)
            \CIBlockElement::SetPropertyValueCode($newCompanyId, 'LEGAN_ENTITY_ID_OF_HEAD_COMPANY', $headCompanyId);

            // Устанавливаем B24 ID головной компании (значение уже проверено выше)
            /*\CIBlockElement::SetPropertyValueCode($newCompanyId, 'OS_HEAD_COMPANY_B24_ID', $headCompanyB24Id);
            error_log('INFO: Установлено OS_HEAD_COMPANY_B24_ID для дочерней компании ID=' . $newCompanyId . ': ' . $headCompanyB24Id);*/

            return [
                'success' => true,
                'message' => 'Дочерняя компания успешно создана',
                'data' => [
                    'company_id' => $newCompanyId,
                    'company_b24_id' => $dataCompany['ID'],
                    'company_name' => $data['UF_NAME_COMPANY']
                ]
            ];
        }

        /**
         * Проверить права пользователя на создание дочерней компании
         * 
         * @param int $headCompanyId - ID головной компании
         * @param int $userId - ID пользователя
         * @return array - ['has_access' => bool, 'message' => string]
         */
        public function checkBranchCreatePermission($headCompanyId, $userId) {
            global $USER;

            // Админы могут создавать дочерние компании
            if ($USER->IsAdmin()) {
                return [
                    'has_access' => true
                ];
            }

            // Получаем данные головной компании
            $headCompany = $this->getCompany($headCompanyId);
            if (!$headCompany) {
                return [
                    'has_access' => false,
                    'message' => 'Головная компания не найдена'
                ];
            }

            // Проверяем, является ли пользователь руководителем головной компании
            $bosses = $headCompany['LEGAN_ENTITY_BOSS'] ?? [];
            if (!is_array($bosses)) {
                $bosses = $bosses ? [$bosses] : [];
            }

            if (in_array($userId, $bosses)) {
                return [
                    'has_access' => true
                ];
            }

            return [
                'has_access' => false,
                'message' => 'Вы не являетесь руководителем головной компании'
            ];
        }
    }