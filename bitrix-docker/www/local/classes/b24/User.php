<?php
    namespace OnlineService\B24;
    use OnlineService\B24\Request;
    class User extends Request{
        private static bool $isSyncingAfterUpdate = false;
        private array $lastB24LookupDebug = [];
        public ?int $contactId = null;

        public int $userId = 0;
        
        // Константы для ID групп
        public int $MARKETING_AGENT_GROUP_ID = 7;
        public int $DIRECTOR_GROUP_ID = 8;
        public function __construct()
        {
        }

        public function getContactID($arFields,$returnAll = false,$debug = false){
            $arFields = array_merge($arFields,[
                "ACTION" => 'GET_CONTACT_ID',
                "SORT" => 'ID',
                "ORDER" => 'asc',
            ]);

            // найти пользователя в б24 по EMAIL
            $response = $this->sendRequest($arFields,$debug);

            if( $response['success'] == 1 ){
                return ($returnAll) ? $response['data'] : $response['data']['ID'];
            }

            return [];
        }

        private function deleteContact($contactId){
            $arFields = [
                'ACTION' => "DELETE_CONTACT",
                'ID' => $contactId
            ];


            // найти пользователя в б24 по EMAIL
            $response = $this->sendRequest($arFields,false);

            if( !$response['success'] ){
                global $APPLICATION;
                $APPLICATION->ThrowException($response);

                return false;
            }
        }


        public function OnBeforeUserDeleteHandler($userId){
            $userObject = $this->getUserObject($userId);

            if( $userObject )
                $this->deleteContact($userObject['CONTACT_ID']);
        }

        public function isUserRegistered($arFields,$debug){
            return $this->getContactID([
                'EMAIL' => $arFields['EMAIL'],
                'PHONE' => $arFields['PERSONAL_PHONE']
            ],true,$debug);
        }

        /**
         * Получить ID пользователя на сайте по ID контакта в B24
         * 
         * @param int $b24ContactId ID контакта в B24
         * @return int|false ID пользователя на сайте или false если не найден
         */
        public function getUserIDByB24ID($b24ContactId){
            if (empty($b24ContactId)) {
                pre("Error: B24 Contact ID is required");
                return false;
            }

            // Ищем пользователя по UF_BITRIX24_ID, затем fallback на UF_B24_USER_ID
            $rsUser = \CUser::GetList(
                array(), 
                $order = "asc", 
                array('UF_BITRIX24_ID' => $b24ContactId),
                array('SELECT' => array('ID', 'UF_B24_USER_ID', 'UF_BITRIX24_ID'))
            );

            if ($userObject = $rsUser->Fetch()) {
                return $userObject['ID'];
            }

            $rsUser = \CUser::GetList(
                array(),
                $order = "asc",
                array('UF_B24_USER_ID' => $b24ContactId),
                array('SELECT' => array('ID', 'UF_B24_USER_ID', 'UF_BITRIX24_ID'))
            );
            if ($userObject = $rsUser->Fetch()) {
                return $userObject['ID'];
            }

            return false;
        }

        private function findUserIdByEmailAndPhone(string $email, string $phone): int
        {
            $email = trim($email);
            $phoneKey = $this->normalizePhoneDigitsForCompare($phone);
            if ($email === '' && $phoneKey === '') {
                return 0;
            }

            if ($email !== '') {
                $rs = \CUser::GetList(
                    ['ID' => 'ASC'],
                    '',
                    ['=EMAIL' => $email],
                    ['FIELDS' => ['ID', 'PERSONAL_PHONE', 'WORK_PHONE', 'PERSONAL_MOBILE'], 'NAV_PARAMS' => ['nTopCount' => 50]]
                );
                while ($row = $rs->Fetch()) {
                    if ($phoneKey === '') {
                        return (int)$row['ID'];
                    }
                    foreach (['PERSONAL_PHONE', 'WORK_PHONE', 'PERSONAL_MOBILE'] as $field) {
                        if ($this->normalizePhoneDigitsForCompare((string)($row[$field] ?? '')) === $phoneKey) {
                            return (int)$row['ID'];
                        }
                    }
                }
            }

            return 0;
        }

        private function normalizePhoneDigitsForCompare(string $phone): string
        {
            $d = preg_replace('/\D+/', '', $phone);
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
        public function getUserObject($userId){

            $rsUser = \CUser::GetList(
                array(), 
                $order = "asc", 
                array('ID' => $userId),
                array('SELECT' => array('UF_B24_USER_ID'))
            );

            if( $userObject = $rsUser->Fetch() ){
                $this->userId = $userObject['ID'];
                $ID = $userObject['ID'];
                $email = $userObject['EMAIL'];
                $phone = $userObject['PERSONAL_PHONE'];
                $b24UserId = $userObject['UF_B24_USER_ID'];

                // Если у пользователя уже есть UF_B24_USER_ID, используем его
                if (!empty($b24UserId)) {
                    $userObject['CONTACT_ID'] = $b24UserId;
                    return $userObject;
                }

                // Иначе ищем контакт в B24 по email/телефону
                $contactId = $this->getContactID([
                    'ID' => $ID,
                    'EMAIL' => $email,
                    'PHONE' => $phone
                ]);

                $userObject['CONTACT_ID'] = $contactId;

                return $userObject;
            }

            return false;
        }
        /**
         * Обновление контакта в B24 по ID контакта
         * 
         * @param int $contactId ID контакта в B24
         * @return array|false Результат обновления или false при ошибке
         */
        public function updateContact($contactId){
            if (empty($contactId)) {
                pre("Error: Contact ID is required");
                return false;
            }

            // Получаем данные пользователя из B24 для обновления
            $arFields = [
                'ACTION' => 'UPDATE_CONTACT',
                'ID' => $contactId
            ];

            /*$response = $this->sendRequest($arFields);

            if ($response['success'] == 1) {
                pre("Contact data retrieved from B24: " . print_r($response['data'], true));
                return $response['data'];
            } else {
                pre("Error getting contact data from B24: " . print_r($response, true));
                return false;
            }*/
        }

        private function getSiteUserForSync(int $userId): ?array
        {
            $rsUser = \CUser::GetList(
                ['ID' => 'ASC'],
                '',
                ['ID' => $userId],
                ['SELECT' => ['ID', 'EMAIL', 'PERSONAL_PHONE', 'UF_B24_USER_ID', 'UF_BITRIX24_ID'], 'NAV_PARAMS' => ['nTopCount' => 1]]
            );

            $userObject = $rsUser->Fetch();
            if (!$userObject) {
                return null;
            }

            return $userObject;
        }

        private function ensureBitrix24IdSynced(int $userId, array $userObject): int
        {
            $this->lastB24LookupDebug = [
                'source' => [],
                'selected' => null,
            ];

            $contactId = (int)($userObject['UF_B24_USER_ID'] ?? 0);
            if ($contactId <= 0) {
                $contactId = (int)($userObject['UF_BITRIX24_ID'] ?? 0);
            }
            if ($contactId <= 0) {
                $bySiteUf = $this->restRequestCompat('crm.contact.list', [
                    'select' => ['ID', 'UF_CRM_1776075126830'],
                    'order' => ['ID' => 'ASC'],
                    'filter' => ['=UF_CRM_1776075126830' => $userId],
                ]);
                $this->lastB24LookupDebug['source']['by_site_user_uf'] = $bySiteUf;
                if (is_array($bySiteUf) && !empty($bySiteUf[0]['ID'])) {
                    $contactId = (int)$bySiteUf[0]['ID'];
                    $this->lastB24LookupDebug['selected'] = 'by_site_user_uf';
                }
            }
            if ($contactId <= 0) {
                // Основной путь: штатная механика поиска дубля контакта в B24.
                $duplicateContact = $this->isUserRegistered([
                    'EMAIL' => (string)($userObject['EMAIL'] ?? ''),
                    'PERSONAL_PHONE' => (string)($userObject['PERSONAL_PHONE'] ?? ''),
                ], false);
                $this->lastB24LookupDebug['source']['isUserRegistered'] = $duplicateContact;
                if (is_array($duplicateContact) && !empty($duplicateContact['ID'])) {
                    $contactId = (int)$duplicateContact['ID'];
                    $this->lastB24LookupDebug['selected'] = 'isUserRegistered';
                }
            }
            if ($contactId <= 0) {
                // Резервный путь через legacy ACTION=GET_CONTACT_ID + сырой ответ.
                $legacyParams = [
                    'ID' => $userId,
                    'EMAIL' => (string)($userObject['EMAIL'] ?? ''),
                    'PHONE' => (string)($userObject['PERSONAL_PHONE'] ?? ''),
                    'ACTION' => 'GET_CONTACT_ID',
                    'SORT' => 'ID',
                    'ORDER' => 'asc',
                ];
                $legacyResponse = $this->sendRequest($legacyParams, false);
                $this->lastB24LookupDebug['source']['legacy_sendRequest'] = $legacyResponse;
                if (is_array($legacyResponse) && (int)($legacyResponse['success'] ?? 0) === 1) {
                    $contactId = (int)($legacyResponse['data']['ID'] ?? 0);
                    $this->lastB24LookupDebug['selected'] = 'legacy_sendRequest';
                }
            }
            if ($contactId <= 0) {
                $contactId = (int)$this->findContactIdInB24ByEmailOrPhone(
                    (string)($userObject['EMAIL'] ?? ''),
                    (string)($userObject['PERSONAL_PHONE'] ?? '')
                );
                if ($contactId > 0) {
                    $this->lastB24LookupDebug['selected'] = 'rest_fallback';
                }
            }

            if ($contactId > 0) {
                $currentB24 = (int)($userObject['UF_B24_USER_ID'] ?? 0);
                $currentBitrix24 = (int)($userObject['UF_BITRIX24_ID'] ?? 0);
                if ($currentB24 !== $contactId || $currentBitrix24 !== $contactId) {
                    self::$isSyncingAfterUpdate = true;
                    try {
                        (new \CUser)->Update($userId, [
                            'UF_B24_USER_ID' => $contactId,
                            'UF_BITRIX24_ID' => $contactId,
                        ]);
                    } finally {
                        self::$isSyncingAfterUpdate = false;
                    }
                }
            }

            return $contactId;
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

        private function findContactIdInB24ByEmailOrPhone(string $email, string $phone): int
        {
            $email = trim($email);
            $normalizedPhone = $this->normalizePhoneForB24($phone);
            $phoneDigits = preg_replace('/\D+/', '', $normalizedPhone);
            $this->lastB24LookupDebug = array_merge($this->lastB24LookupDebug, [
                'email' => $email,
                'phone_raw' => $phone,
                'phone_normalized' => $normalizedPhone,
                'phone_digits' => $phoneDigits,
                'email_rows' => null,
                'phone_rows' => null,
                'validated_candidates' => [],
            ]);

            if ($email !== '') {
                $rows = $this->restRequestCompat('crm.contact.list', [
                    'select' => ['ID'],
                    'order' => ['ID' => 'ASC'],
                    'filter' => ['=EMAIL' => $email],
                ]);
                $this->lastB24LookupDebug['email_rows'] = $rows;
                $id = $this->resolveContactIdFromCandidates($rows, $email, $phoneDigits);
                if ($id > 0) {
                    return $id;
                }
            }

            if ($normalizedPhone !== '') {
                $rows = $this->restRequestCompat('crm.contact.list', [
                    'select' => ['ID'],
                    'order' => ['ID' => 'ASC'],
                    'filter' => ['=PHONE' => $normalizedPhone],
                ]);
                $this->lastB24LookupDebug['phone_rows'] = $rows;
                $id = $this->resolveContactIdFromCandidates($rows, $email, $phoneDigits);
                if ($id > 0) {
                    return $id;
                }
            }

            return 0;
        }

        private function resolveContactIdFromCandidates($rows, string $email, string $phoneDigits): int
        {
            if (!is_array($rows)) {
                return 0;
            }

            $candidateIds = [];
            foreach ($rows as $row) {
                $id = (int)($row['ID'] ?? 0);
                if ($id > 0) {
                    $candidateIds[] = $id;
                }
            }
            $candidateIds = array_values(array_unique($candidateIds));
            if (count($candidateIds) === 0) {
                return 0;
            }
            if (count($candidateIds) === 1) {
                return (int)$candidateIds[0];
            }

            // Если кандидатов несколько, подтверждаем точным сравнением через crm.contact.get.
            $matchedBoth = [];
            $matchedPhoneOnly = [];
            $matchedEmailOnly = [];
            foreach ($candidateIds as $candidateId) {
                $contact = $this->restRequestCompat('crm.contact.get', ['id' => (int)$candidateId]);
                $contactEmail = strtolower(trim((string)($contact['EMAIL'][0]['VALUE'] ?? '')));
                $contactPhoneDigits = preg_replace(
                    '/\D+/',
                    '',
                    (string)($contact['PHONE'][0]['VALUE'] ?? '')
                );
                $emailMatches = ($email !== '' && strtolower($email) === $contactEmail);
                $phoneMatches = ($phoneDigits !== '' && $contactPhoneDigits !== '' && str_ends_with($contactPhoneDigits, substr($phoneDigits, -10)));

                $this->lastB24LookupDebug['validated_candidates'][] = [
                    'id' => $candidateId,
                    'email' => $contactEmail,
                    'phone_digits' => $contactPhoneDigits,
                    'email_match' => $emailMatches,
                    'phone_match' => $phoneMatches,
                ];

                if ($emailMatches && $phoneMatches) {
                    $matchedBoth[] = (int)$candidateId;
                } elseif ($phoneMatches) {
                    $matchedPhoneOnly[] = (int)$candidateId;
                } elseif ($emailMatches) {
                    $matchedEmailOnly[] = (int)$candidateId;
                }
            }

            $matchedBoth = array_values(array_unique($matchedBoth));
            $matchedPhoneOnly = array_values(array_unique($matchedPhoneOnly));
            $matchedEmailOnly = array_values(array_unique($matchedEmailOnly));

            $this->lastB24LookupDebug['match_groups'] = [
                'both' => $matchedBoth,
                'phone_only' => $matchedPhoneOnly,
                'email_only' => $matchedEmailOnly,
            ];

            if (count($matchedBoth) === 1) {
                return (int)$matchedBoth[0];
            }
            if (count($matchedBoth) > 1) {
                return 0;
            }
            if (count($matchedPhoneOnly) === 1) {
                return (int)$matchedPhoneOnly[0];
            }
            if (count($matchedPhoneOnly) > 1) {
                return 0;
            }
            if (count($matchedEmailOnly) === 1) {
                return (int)$matchedEmailOnly[0];
            }

            return 0;
        }

        private function restRequestCompat(string $method, array $params, bool $debug = false)
        {
            if (\method_exists('\OnlineService\B24\Request', 'restRequest')) {
                return \OnlineService\B24\Request::restRequest($method, $params, $debug);
            }
            if (\function_exists('sendRequestB24')) {
                return \sendRequestB24($method, $params, $debug);
            }
            return null;
        }

        private function buildB24ContactFieldsFromUserUpdate(array $arFields): array
        {
            $fields = [];
            $map = [
                'NAME' => 'NAME',
                'LAST_NAME' => 'LAST_NAME',
                'SECOND_NAME' => 'SECOND_NAME',
                'WORK_POSITION' => 'POST',
                'PERSONAL_BIRTHDAY' => 'BIRTHDATE',
            ];

            foreach ($map as $siteField => $b24Field) {
                if (array_key_exists($siteField, $arFields)) {
                    $fields[$b24Field] = (string)$arFields[$siteField];
                }
            }

            if (array_key_exists('EMAIL', $arFields)) {
                $fields['EMAIL'] = [[
                    'VALUE' => (string)$arFields['EMAIL'],
                    'VALUE_TYPE' => 'WORK',
                ]];
            }
            $phoneForB24 = null;
            if (array_key_exists('PERSONAL_PHONE', $arFields)) {
                $phoneForB24 = (string)$arFields['PERSONAL_PHONE'];
            } elseif (array_key_exists('WORK_PHONE', $arFields)) {
                // Форма ЛК редактирует "Телефон" через WORK_PHONE, синхронизируем его в B24 как основной PHONE.
                $phoneForB24 = (string)$arFields['WORK_PHONE'];
            }

            if ($phoneForB24 !== null) {
                $normalizedPhone = $this->normalizePhoneForB24($phoneForB24);
                $fields['PHONE'] = [[
                    'VALUE' => ($normalizedPhone !== '' ? $normalizedPhone : $phoneForB24),
                    'VALUE_TYPE' => 'WORK',
                ]];
            }

            return $fields;
        }

        public function OnAfterUserUpdateHandler($arFields){
            if (self::$isSyncingAfterUpdate) {
                return true;
            }

            $userId = (int)($arFields['ID'] ?? 0);
            if ($userId <= 0) {
                return true;
            }

            $userObject = $this->getSiteUserForSync($userId);
            if (array_key_exists('UF_ADVERSTERING_AGENT', $arFields)) {
                $marketingStatus = $arFields['UF_ADVERSTERING_AGENT'];
                if (
                    $marketingStatus === 'Y' || $marketingStatus === 'N' ||
                    $marketingStatus === '1' || $marketingStatus === '0' ||
                    $marketingStatus === 1 || $marketingStatus === 0 ||
                    $marketingStatus === true || $marketingStatus === false
                ) {
                    $this->updateMarketingAgentPriceType($marketingStatus, $userId);
                }
            }

            if (!$userObject) {
                return true;
            }

            $contactId = $this->ensureBitrix24IdSynced($userId, $userObject);
            if ($contactId <= 0) {
                return true;
            }

            $contactFields = $this->buildB24ContactFieldsFromUserUpdate($arFields);
            $contactFields['UF_CRM_1776075126830'] = $userId;
            if (!empty($contactFields)) {
                $this->restRequestCompat('crm.contact.update', [
                    'id' => $contactId,
                    'fields' => $contactFields,
                ]);
            }

            return true;
        }

        /**
         * Получить список ID пользователей в группе
         * @param int $groupId ID группы
         * @return array Массив ID пользователей
         */
        public function getUsersInGroup($groupId){
            $userIds = array();
            
            // Получаем список пользователей в группе
            $rsUsers = \CUser::GetList(
                array('ID' => 'ASC'),
                array('ASC'),
                array('GROUPS_ID' => $groupId),
                array('SELECT' => array('ID'))
            );
            
            while ($user = $rsUsers->Fetch()) {
                $userIds[] = $user['ID'];
            }
            
            return $userIds;
        }

        /**
         * Получить список групп пользователя
         * @param int $userId ID пользователя
         * @return array Массив ID групп пользователя
         */
        public function getUserGroups($userId){
            $groupIds = array();
            
            // Получаем данные пользователя
            $rsUser = \CUser::GetByID($userId);
            $userData = $rsUser->Fetch();
            
            if ($userData && !empty($userData['GROUPS_ID'])) {
                $groupIds = $userData['GROUPS_ID'];
                if (!is_array($groupIds)) {
                    $groupIds = array($groupIds);
                }
            }
            
            return $groupIds;
        }

        /**
         * Добавить пользователя в группу
         * @param int $userId ID пользователя
         * @param int $groupId ID группы
         * @return bool Результат операции
         */
        public function addUserToGroup($userId, $groupId){
            $user = (new \CUser);
            // Получаем текущие группы пользователя
            $userGroups = \CUser::GetUserGroup((int)$userId);
            
            // Проверяем, не добавлен ли пользователь уже в эту группу
            if (in_array($groupId, $userGroups)) {
                pre("Пользователь ID " . $userId . " уже находится в группе " . $groupId);
                return true;
            }
            
            // Добавляем новую группу к существующим группам
            $userGroups[] = $groupId;
            
            $arFields = array(
                'GROUP_ID' => $userGroups,
                'UF_ADVERSTERING_AGENT' => 1
            );
            
            $result = (new \CUser)->Update($userId, $arFields);
            
            if ($result) {
                return true;
            } else {
                return false;
            }
        }
        public function addUserToGroups($userId, $groupIds, $userObj = null){
            // Получаем текущие группы пользователя и добавляем новые, не теряя существующие.
            $currentGroups = \CUser::GetUserGroup((int)$userId);
            if (!is_array($currentGroups)) {
                $currentGroups = [];
            }
            if (!is_array($groupIds)) {
                $groupIds = [$groupIds];
            }
            $groupIds = array_values(array_filter(array_map('intval', $groupIds), static function ($id) {
                return $id > 0;
            }));
            $userGroups = array_values(array_unique(array_merge($currentGroups, $groupIds)));

            $arFields = array(
                'GROUP_ID' => $userGroups
            );
            
            if( in_array($this->MARKETING_AGENT_GROUP_ID,$userGroups) ){
                $arFields['UF_ADVERSTERING_AGENT'] = 1;
            }

            $result = (new \CUser)->Update($userId, $arFields);
            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Удалить пользователя из группы
         * @param int $userId ID пользователя
         * @param int $groupId ID группы
         * @return bool Результат операции
         */
        public function removeUserFromGroup($userId, $groupId){
            $user = new \CUser();
            $userGroups = \CUser::GetUserGroup((int)$userId);
            if (!is_array($userGroups)) {
                $userGroups = array();
            }
            $userGroups = array_values(array_diff($userGroups, array((int)$groupId)));
            
            $arFields = array(
                'GROUP_ID' => $userGroups,
                'UF_ADVERSTERING_AGENT' => 0
            );
            
            $result = $user->Update($userId, $arFields);
            
            if ($result) {
                //pre("Пользователь ID " . $userId . " удален из группы " . $groupId);
                return true;
            } else {
                //pre("Ошибка удаления пользователя ID " . $userId . " из группы " . $groupId . ": " . $user->LAST_ERROR);
                return false;
            }
        }

        private function updateMarketingAgentPriceType($status, $userId = null){
            // Получаем информацию о группе рекламных агентов
            $rsGroup = \CGroup::GetByID($this->MARKETING_AGENT_GROUP_ID);
            $groupData = $rsGroup->Fetch();

            if( is_null($userId) ){
                $userId = $this->userId;
            }
            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            
            if (!$groupData) {
                pre("Ошибка: группа рекламных агентов не найдена");
                return false;
            }
            
            // Получаем текущий список пользователей в группе
            $currentUserIds = $this->getUsersInGroup($this->MARKETING_AGENT_GROUP_ID);
            
            // Определяем, нужно ли добавить или удалить пользователя из группы
            $isUserInGroup = in_array($userId, $currentUserIds);
            $shouldBeInGroup = ($status === 'Y' || $status === true || $status === 1 || $status === "1");
            
            if ($shouldBeInGroup && !$isUserInGroup) {
                // Добавляем пользователя в группу
                return $this->addUserToGroup($userId, $this->MARKETING_AGENT_GROUP_ID);
                
            } elseif (!$shouldBeInGroup && $isUserInGroup) {
                // Удаляем пользователя из группы
                return $this->removeUserFromGroup($userId, $this->MARKETING_AGENT_GROUP_ID);
                
            } else {
                return true;
            }
        }

        private function getManagerID($manager_xml_id){
            // Ищем элемент по внешнему коду (XML_ID)
            $arFilter = [
                'IBLOCK_ID' => 53,
                'XML_ID' => $manager_xml_id
            ];

            $rsElement = \CIBlockElement::GetList(
                ['SORT' => 'ASC'],
                $arFilter,
                false,
                false,
                ['ID', 'NAME', 'XML_ID', 'IBLOCK_ID']
            );

            if ($managerElement = $rsElement->GetNext()) {
                return $managerElement['ID'];
            }

            return false;
        }


        /**
         * Обновление пользователя на сайте по ID контакта в B24
         * 
         * @param array $fields Поля для обновления:
         * - 'ID' => ID контакта в B24 (обязательно)
         * - 'NAME' => Имя
         * - 'LAST_NAME' => Фамилия  
         * - 'SECOND_NAME' => Отчество
         * - 'EMAIL' => Email
         * - 'PERSONAL_PHONE' => Телефон
         * - 'WORK_POSITION' => Должность
         * - 'PERSONAL_BIRTHDAY' => Дата рождения
         * 
         * @return bool Результат обновления
         */
        public function update($fields){
            $siteUserIdFromB24Uf = (int)($fields['UF_CRM_1776075126830'] ?? 0);
            $b24ID = (int)($fields['B24_ID'] ?? 0);
            unset($fields['B24_ID']);

            if ($siteUserIdFromB24Uf > 0) {
                $this->userId = $siteUserIdFromB24Uf;
            } elseif ($b24ID > 0) {
                $this->userId = $this->getUserIDByB24ID($b24ID);
                if (!$this->userId) {
                    $this->userId = $this->findUserIdByEmailAndPhone(
                        (string)($fields['EMAIL'] ?? ''),
                        (string)($fields['PERSONAL_PHONE'] ?? '')
                    );
                }
            } else {
                pre("Error: neither B24_ID nor UF_CRM_1776075126830 provided");
                return false;
            }

            $fields['UF_MANAGER'] = !empty($fields['ASSIGNED_MANAGER'])
                ? $this->getManagerID((string)$fields['ASSIGNED_MANAGER'])
                : false;
            $fields['UF_MANAGER2'] = !empty($fields['SECOND_MANAGER'])
                ? $this->getManagerID((string)$fields['SECOND_MANAGER'])
                : false;
            unset($fields['ASSIGNED_MANAGER'], $fields['SECOND_MANAGER']);
            
            if (!$this->userId) {
                pre("Error: User not found. B24_ID=" . $b24ID . " UF_CRM_1776075126830=" . $siteUserIdFromB24Uf);
                return false;
            }

            if ($b24ID > 0) {
                $fields['UF_B24_USER_ID'] = $b24ID;
                $fields['UF_BITRIX24_ID'] = $b24ID;
            }

            $isDirectorFlag = $fields['UF_IS_DIRECTOR'] ?? null;
            $incomingAction = (string)($fields['ACTION'] ?? '');

            if (array_key_exists('UF_CRM_1775034008956', $fields)) {
                $activeFlag = $fields['UF_CRM_1775034008956'];
                if ($activeFlag === 'Y' || $activeFlag === '1' || $activeFlag === 1 || $activeFlag === true) {
                    $fields['ACTIVE'] = 'Y';
                } elseif ($activeFlag === 'N' || $activeFlag === '0' || $activeFlag === 0 || $activeFlag === false) {
                    $fields['ACTIVE'] = 'N';
                }
            }

            // Для входящего UPDATE_CONTACT с B24 обновляем рабочий телефон тем же значением,
            // если отдельный WORK_PHONE не передан.
            if (!empty($fields['PERSONAL_PHONE']) && empty($fields['WORK_PHONE'])) {
                $fields['WORK_PHONE'] = (string)$fields['PERSONAL_PHONE'];
            }

            // Технические поля входящего канала не должны уходить в CUser->Update.
            unset(
                $fields['ACTION'],
                $fields['UF_CRM_1776075126830'],
                $fields['UF_IS_DIRECTOR'],
                $fields['UF_CRM_1775034008956'],
                $fields['CONTACT_IDS'],
                $fields['BITRIX24_ID'],
                $fields['B24_ID']
            );

            if (array_key_exists('EMAIL', $fields) && trim((string)$fields['EMAIL']) === '') {
                unset($fields['EMAIL']);
            }

            // Обновляем пользователя на сайте
            $user = new \CUser();

            if ($isDirectorFlag && $incomingAction === "UPDATE_CONTACT") {
                // Получаем компанию пользователя
                $rsCompany = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => 57,
                        'PROPERTY_OS_COMPANY_USERS' => $this->userId,
                        'ACTIVE' => 'Y'
                    ],
                    false,
                    false,
                    ['ID', 'PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING', 'PROPERTY_OS_HOLDING_OF']
                );

                $userCompany = $rsCompany->GetNext();
                $companyIds = [];

                if ($userCompany) {
                    // Проверяем, является ли компания головной холдинга
                    if (!empty($userCompany['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE']) &&
                        ($userCompany['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE'] === 'Y' ||
                            $userCompany['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE'] === 'Да')) {

                        // Сценарий 1: Головная компания - получаем все компании холдинга
                        $rsHoldingCompanies = \CIBlockElement::GetList(
                            [],
                            [
                                'IBLOCK_ID' => 57,
                                'PROPERTY_OS_HOLDING_OF' => $userCompany['ID'],
                                'ACTIVE' => 'Y'
                            ],
                            false,
                            false,
                            ['ID']
                        );

                        while ($holdingCompany = $rsHoldingCompanies->GetNext()) {
                            $companyIds[] = $holdingCompany['ID'];
                        }

                        // Добавляем саму головную компанию
                        $companyIds[] = $userCompany['ID'];

                    } else if (!empty($userCompany['PROPERTY_OS_HOLDING_OF_VALUE'])) {

                        // Сценарий 2: Обычная компания - получаем все компании того же холдинга
                        $holdingId = $userCompany['PROPERTY_OS_HOLDING_OF_VALUE'];

                        // Получаем все компании этого холдинга
                        $rsHoldingCompanies = \CIBlockElement::GetList(
                            [],
                            [
                                'IBLOCK_ID' => 57,
                                'PROPERTY_OS_HOLDING_OF' => $holdingId,
                                'ACTIVE' => 'Y'
                            ],
                            false,
                            false,
                            ['ID']
                        );

                        while ($holdingCompany = $rsHoldingCompanies->GetNext()) {
                            $companyIds[] = $holdingCompany['ID'];
                        }

                        // Добавляем головную компанию холдинга
                        $companyIds[] = $holdingId;

                    } else {
                        // Если нет связей с холдингом - только своя компания
                        $companyIds[] = $userCompany['ID'];
                    }
                }

                if( $companyIds ){
                    // Обновляем руководителя у всех
                    foreach ($companyIds as $companyId){
                        $el = new \CIBlockElement;
                        $companyUpdated = $el->SetPropertyValues($companyId, 57,[$this->userId],"OS_COMPANY_BOSS");
                    }
                }
                
                // Добавляем пользователя в группу руководителей (ID: 432)
                $userGroups = \CUser::GetUserGroup($this->userId);
                if (!in_array($this->DIRECTOR_GROUP_ID, $userGroups)) {
                    $userGroups[] = $this->DIRECTOR_GROUP_ID;
                    \CUser::SetUserGroup($this->userId, $userGroups);
                    pre("User ID " . $this->userId . " added to Directors group (ID: " . $this->DIRECTOR_GROUP_ID . ")");
                }
            } else if (($isDirectorFlag === 'N' || $isDirectorFlag === '0' || $isDirectorFlag === 0 || $isDirectorFlag === false || $isDirectorFlag === null || $isDirectorFlag === '') && $incomingAction === "UPDATE_CONTACT") {
                // Убираем пользователя из группы руководителей при снятии галочки
                $userGroups = \CUser::GetUserGroup($this->userId);
                if (($key = array_search($this->DIRECTOR_GROUP_ID, $userGroups)) !== false) {
                    unset($userGroups[$key]);
                    \CUser::SetUserGroup($this->userId, $userGroups);
                    pre("User ID " . $this->userId . " removed from Directors group (ID: " . $this->DIRECTOR_GROUP_ID . ")");
                }
            }

            self::$isSyncingAfterUpdate = true;
            try {
                $result = $user->Update($this->userId, $fields);
            } finally {
                self::$isSyncingAfterUpdate = false;
            }

            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        public function updateBatch($fields){
            // Проверяем обязательные поля
            if (empty($fields['CONTACT_IDS'])) {
                pre("Error: CONTACT_IDS is required for user update");
                return false;
            }

            foreach ($fields['CONTACT_IDS'] as $b24Id){
                $userId = $this->getUserIDByB24ID($b24Id);

                if( $userId )
                    $this->updateMarketingAgentPriceType($fields['IS_MARKETING_AGENT'],$userId);
            }
        }

        public function delete($fields){
            $this->userId = $this->getUserIDByB24ID($fields['ID']);

            if( $this->userId )
                return \CUser::Delete($this->userId);
            else return false;
        }

        public function getMarketingGroupId(){
            return $this->MARKETING_AGENT_GROUP_ID;
        }

        /**
         * Получить головную компанию холдинга, где пользователь является руководителем
         * 
         * @param int|null $userId ID пользователя (если не указан, используется текущий)
         * @return array|false Данные головной компании или false если не найдена
         */
        public function getHeadCompany($userId = null) {
            if ($userId === null) {
                $userId = $this->userId;
            }

            if (empty($userId)) {
                pre("Error: User ID is required");
                return false;
            }

            // Ищем головную компанию холдинга, где пользователь является руководителем
            $filter = [
                'IBLOCK_ID' => 57,
                'PROPERTY_OS_COMPANY_BOSS' => $userId,
                'PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING' => 31520, // Константа для головной компании холдинга
                'ACTIVE' => 'Y'
            ];

            // Получаем головную компанию холдинга пользователя
            $rsCompany = \CIBlockElement::GetList(
                [],
                $filter,
                false,
                false,
                [
                    'ID', 
                    'NAME',
                    'PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING', 
                    'PROPERTY_OS_HOLDING_OF',
                    'PROPERTY_OS_COMPANY_B24_ID',
                    'PROPERTY_OS_HEAD_COMPANY_B24_ID'
                ]
            );

            if ($company = $rsCompany->GetNext()) {
                return $company;
            }

            return false;
        }

        /**
         * Получить любую компанию пользователя (руководитель или сотрудник)
         * 
         * @param int|null $userId ID пользователя (если не указан, используется текущий)
         * @param string $userRole Роль пользователя: 'boss' - руководитель, 'user' - обычный пользователь
         * @return array|false Данные компании или false если не найдена
         */
        public function getUserCompany($userId = null, $userRole = 'boss', $companyId = null) {
            if ($userId === null) {
                $userId = $this->userId;
            }

            if (empty($userId)) {
                pre("Error: User ID is required");
                return false;
            }

            // Определяем фильтр в зависимости от роли
            $filter = [
                'IBLOCK_ID' => 57,
                'ACTIVE' => 'Y'
            ];

            if ($userRole === 'boss') {
                $filter['PROPERTY_OS_COMPANY_BOSS'] = $userId;
            } else {
                $filter['PROPERTY_OS_COMPANY_USERS'] = $userId;
            }

            if (!is_null($companyId)) {
                $companyId = (int)$companyId;

                if ($companyId <= 0) {
                    return false;
                }

                $filter['ID'] = $companyId;
            }

            // Получаем компанию пользователя
            $rsCompany = \CIBlockElement::GetList(
                [],
                $filter,
                false,
                false,
                [
                    'ID', 
                    'NAME',
                    'PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING', 
                    'PROPERTY_OS_HOLDING_OF',
                    'PROPERTY_OS_COMPANY_B24_ID',
                    'PROPERTY_OS_HEAD_COMPANY_B24_ID'
                ]
            );

            if ($company = $rsCompany->GetNext()) {
                return $company;
            }

            return false;
        }

        /**
         * Проверить, является ли пользователь руководителем головной компании холдинга
         * 
         * @param int|null $userId ID пользователя (если не указан, используется текущий)
         * @return bool true если пользователь руководитель головной компании холдинга
         */
        public function isCompanyBoss($userId = null) {
            $company = $this->getHeadCompany($userId);
            return $company !== false;
        }

        /**
         * Получить ID головной компании холдинга для пользователя
         * 
         * @param int|null $userId ID пользователя (если не указан, используется текущий)
         * @return int|false ID головной компании холдинга или false если не найдена
         */
        public function getHeadCompanyId($userId = null) {
            $company = $this->getHeadCompany($userId);
            
            if (!$company) {
                return false;
            }

            // Если это головная компания холдинга
            if (!empty($company['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE']) && 
                ($company['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE'] === 'Y' || 
                 $company['PROPERTY_OS_COMPANY_IS_HEAD_OF_HOLDING_VALUE'] === 'Да')) {
                
                return $company['PROPERTY_OS_HEAD_COMPANY_B24_ID_VALUE'] ?: $company['PROPERTY_OS_COMPANY_B24_ID_VALUE'];
            }
            
            // Если это дочерняя компания в холдинге
            if (!empty($company['PROPERTY_OS_HOLDING_OF_VALUE'])) {
                return $company['PROPERTY_OS_HOLDING_OF_VALUE'];
            }

            // Если нет связей с холдингом - возвращаем ID самой компании
            return $company['PROPERTY_OS_COMPANY_B24_ID_VALUE'];
        }
    }