<?php
    namespace OnlineService\B24;
    use OnlineService\B24\UserSync\Config\UserSyncConfig;
    use OnlineService\B24\Request;
    use OnlineService\Site\Config\CompanyModuleConfig;
    use OnlineService\Sync\FromCrm\CrmInboundUfMap;

    class User extends Request{ 
        public ?int $contactId = null;

        public int $userId;
        
        // Константы для ID групп
        /** Администраторы сайта — при любом обновлении групп сохраняем членство, если оно было */
        public int $ADMINISTRATORS_GROUP_ID = UserSyncConfig::ADMINISTRATORS_GROUP_ID;
        public int $MARKETING_AGENT_GROUP_ID = UserSyncConfig::MARKETING_AGENT_GROUP_ID;
        public int $DIRECTOR_GROUP_ID = UserSyncConfig::DIRECTOR_GROUP_ID;
        /** @var list<string> */
        private const INBOUND_PROTECTED_USER_FIELDS = [
            'BLOCKED',
            'LOGIN_ATTEMPTS',
            'PASSWORD',
            'CONFIRM_PASSWORD',
            'CHECKWORD',
            '~CHECKWORD_TIME',
            'LOGIN',
            'LID',
            'GROUP_ID',
            'GROUPS_ID',
            'ADMIN_NOTES',
        ];

        private function agentDebugLog(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void
        {
            try {
                if (function_exists('eklektikaWriteDebugA19051')) {
                    eklektikaWriteDebugA19051($runId, $hypothesisId, $location, $message, $data);
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        public function __construct()
        {
        }

        public function getContactID($arFields,$returnAll = false,$debug = false){
            $filter = [];
            if (!empty($arFields['EMAIL'])) {
                $filter['=EMAIL'] = (string)$arFields['EMAIL'];
            }
            if (!empty($arFields['PHONE'])) {
                $filter['=PHONE'] = (string)$arFields['PHONE'];
            }
            if ($filter === []) {
                return [];
            }

            $result = \OnlineService\B24\RestClient::callRestMethod('crm.contact.list', [
                'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PHONE'],
                'order' => ['ID' => 'ASC'],
                'filter' => $filter,
            ], (bool)$debug);

            if (is_array($result) && isset($result['success']) && (int)$result['success'] === 0) {
                return [];
            }
            if (!is_array($result) || empty($result[0]['ID'])) {
                return [];
            }

            return $returnAll ? $result[0] : (int)$result[0]['ID'];
        }

        private function deleteContact($contactId){
            $contactId = (int)$contactId;
            if ($contactId <= 0) {
                return false;
            }
            $response = \OnlineService\B24\RestClient::callRestMethod('crm.contact.delete', [
                'id' => $contactId
            ], false);

            if (is_array($response) && isset($response['success']) && (int)$response['success'] === 0) {
                global $APPLICATION;
                $APPLICATION->ThrowException((string)($response['error'] ?? 'CRM delete contact failed'));
                return false;
            }

            if ($response === false || $response === null) {
                global $APPLICATION;
                $APPLICATION->ThrowException('CRM delete contact failed');
                return false;
            }

            return true;
        }

        public function OnBeforeUserDeleteHandler($userId){
            $userObject = $this->getUserObject($userId);
            if ($userObject && !empty($userObject['CONTACT_ID'])) {
                $this->deleteContact((int)$userObject['CONTACT_ID']);
            }
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
            $b24ContactId = (int) $b24ContactId;
            if ($b24ContactId <= 0) {
                return false;
            }

            $matches = [];
            $usedField = '';
            foreach ([UserSyncConfig::USER_UF_CONTACT_B24_ID, UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] as $ufField) {
                $matches = $this->collectUserIdsByContactUfField($b24ContactId, $ufField);
                if ($matches !== []) {
                    $usedField = $ufField;
                    break;
                }
            }

            if ($matches === []) {
                return false;
            }
            if (\count($matches) > 1) {
                return false;
            }

            return $matches[0];
        }

        /**
         * @return list<int>
         */
        private function collectUserIdsByContactUfField(int $b24ContactId, string $ufField): array
        {
            $rsUser = \CUser::GetList(
                ['ID' => 'ASC'],
                'id',
                [$ufField => $b24ContactId],
                ['SELECT' => ['ID', $ufField]]
            );

            $out = [];
            while ($userObject = $rsUser->Fetch()) {
                $uid = (int) ($userObject['ID'] ?? 0);
                $raw = $userObject[$ufField] ?? null;
                $ufVal = $raw !== null && $raw !== ''
                    ? (int) $raw
                    : 0;
                if ($uid > 0 && $ufVal === $b24ContactId) {
                    $out[] = $uid;
                }
            }

            return $out;
        }

        public function getUserObject($userId){

            $rsUser = \CUser::GetList(
                array(), 
                $order = "asc", 
                array('ID' => $userId),
                ['SELECT' => [UserSyncConfig::USER_UF_CONTACT_B24_ID, UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY]]
            );

            if( $userObject = $rsUser->Fetch() ){
                $this->userId = $userObject['ID'];
                $ID = $userObject['ID'];
                $email = $userObject['EMAIL'];
                $phone = $userObject['PERSONAL_PHONE'];
                $b24UserId = $userObject[UserSyncConfig::USER_UF_CONTACT_B24_ID]
                    ?? $userObject[UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY]
                    ?? null;

                // Если уже сохранён ID контакта B24 — используем его
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
                return false;
            }

            // Получаем данные пользователя из B24 для обновления
            $arFields = [
                'ACTION' => 'UPDATE_CONTACT',
                'ID' => $contactId
            ];

            /*$response = $this->sendRequest($arFields);

            if ($response['success'] == 1) {
                return $response['data'];
            } else {
                return false;
            }*/
        }

        public function OnAfterUserUpdateHandler($arFields){
            $userId = (int)($arFields['ID'] ?? 0);
            if ($userId <= 1) {
                return true;
            }
            $userObject = $this->getUserObject($userId);
            if( isset($arFields['UF_ADVERSTERING_AGENT']) ) {
                $this->updateMarketingAgentPriceType($arFields['UF_ADVERSTERING_AGENT'], $userId);
            }

            //if( $userObject )
                //$this->updateContact($userObject['CONTACT_ID']);

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
            $userId = (int)$userId;
            if ($userId <= 0) {
                return [];
            }

            // Важно: поле GROUPS_ID из CUser::GetByID не гарантирует полный список членства;
            // источник истины — CUser::GetUserGroup (как в addUserToGroups / removeUserFromGroupsByIds).
            $ids = \CUser::GetUserGroup($userId);
            if (!is_array($ids)) {
                $ids = $ids !== null && $ids !== '' && $ids !== false
                    ? [(int)$ids]
                    : [];
            } else {
                $ids = array_map('intval', $ids);
            }

            return $this->normalizeUserGroupIds($ids);
        }

        /**
         * Добавить пользователя в группу
         * @param int $userId ID пользователя
         * @param int $groupId ID группы
         * @return bool Результат операции
         */
        public function addUserToGroup($userId, $groupId){
            $userId = (int)$userId;
            $groupId = (int)$groupId;
            if ($userId <= 0 || $groupId <= 0) {
                return false;
            }
            if ($userId <= 1) {
                return true;
            }

            $cur = $this->normalizeUserGroupIds(\CUser::GetUserGroup($userId));
            if (in_array($groupId, $cur, true)) {
                return (bool)(new \CUser())->Update($userId, [
                    'UF_ADVERSTERING_AGENT' => 1,
                    'ACTIVE' => 'Y',
                ]);
            }

            $hadAdministratorsGroup = in_array($this->ADMINISTRATORS_GROUP_ID, $cur, true);
            $new = $this->normalizeUserGroupIds(array_merge($cur, [$groupId]));
            if ($hadAdministratorsGroup && !in_array($this->ADMINISTRATORS_GROUP_ID, $new, true)) {
                $new[] = $this->ADMINISTRATORS_GROUP_ID;
            }

            \CUser::SetUserGroup($userId, $new);

            return (bool)(new \CUser())->Update($userId, [
                'UF_ADVERSTERING_AGENT' => 1,
                'ACTIVE' => 'Y',
            ]);
        }
        public function addUserToGroups($userId, $groupIds, $userObj = null){
            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            if ($userId <= 1) {
                return true;
            }

            $currentGroups = \CUser::GetUserGroup($userId);
            if (!is_array($currentGroups)) {
                $currentGroups = $currentGroups !== null && $currentGroups !== '' && $currentGroups !== false
                    ? [(int)$currentGroups]
                    : [];
            } else {
                $currentGroups = array_map('intval', $currentGroups);
            }

            $hadAdministratorsGroup = in_array($this->ADMINISTRATORS_GROUP_ID, $currentGroups, true);

            $toAdd = [];
            foreach ((array)$groupIds as $gid) {
                $gid = (int)$gid;
                if ($gid > 0) {
                    $toAdd[] = $gid;
                }
            }

            if ($toAdd === []) {
                return true;
            }

            $userGroups = array_values(array_unique(array_merge($currentGroups, $toAdd)));
            if ($hadAdministratorsGroup && !in_array($this->ADMINISTRATORS_GROUP_ID, $userGroups, true)) {
                $userGroups[] = $this->ADMINISTRATORS_GROUP_ID;
            }

            $arFields = array(
                'GROUP_ID' => $userGroups
            );

            if (in_array($this->MARKETING_AGENT_GROUP_ID, $userGroups, true)) {
                $arFields['UF_ADVERSTERING_AGENT'] = 1;
                $arFields['ACTIVE'] = 'Y';
            }

            $result = (new \CUser)->Update($userId, $arFields);
            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Убрать пользователя из перечисленных групп (только GROUP_ID), без изменения UF/ACTIVE.
         * Для снятия скидочных групп компании; не путать с {@see removeUserFromGroup} (там побочные поля).
         *
         * @param list<int|mixed> $groupIdsToRemove
         */
        public function removeUserFromGroupsByIds(int $userId, array $groupIdsToRemove): bool
        {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            if ($userId <= 1) {
                return true;
            }

            $remove = [];
            foreach ($groupIdsToRemove as $g) {
                $g = (int)$g;
                if ($g > 0) {
                    $remove[$g] = true;
                }
            }
            unset($remove[$this->ADMINISTRATORS_GROUP_ID]);
            if ($remove === []) {
                return true;
            }

            $current = \CUser::GetUserGroup($userId);
            if (!is_array($current)) {
                $current = $current !== null && $current !== '' && $current !== false
                    ? [(int)$current]
                    : [];
            } else {
                $current = array_map('intval', $current);
            }

            $hadAdministratorsGroup = in_array($this->ADMINISTRATORS_GROUP_ID, $current, true);

            $new = [];
            foreach ($current as $g) {
                if (!isset($remove[$g])) {
                    $new[] = $g;
                }
            }

            if ($hadAdministratorsGroup && !in_array($this->ADMINISTRATORS_GROUP_ID, $new, true)) {
                $new[] = $this->ADMINISTRATORS_GROUP_ID;
            }

            if ($new === $current) {
                return true;
            }

            \CUser::SetUserGroup($userId, $new);

            return true;
        }

        /**
         * Удалить пользователя из группы
         * @param int $userId ID пользователя
         * @param int $groupId ID группы
         * @return bool Результат операции
         */
        public function removeUserFromGroup($userId, $groupId){
            if ((int)$userId <= 1) {
                // #region agent log
                $this->agentDebugLog('user_sync_' . date('Ymd_His'), 'H23', 'User.php:removeUserFromGroup', 'Skip protected user deactivation in removeUserFromGroup', [
                    'user_id' => (int)$userId,
                    'group_id' => (int)$groupId,
                ]);
                // #endregion
                return true;
            }
            if ((int)$groupId === $this->ADMINISTRATORS_GROUP_ID) {
                return true;
            }

            $user = new \CUser();
            
            // Получаем текущие группы пользователя
            $rsUser = \CUser::GetByID($userId);
            $userData = $rsUser->Fetch();
            
            if (!$userData) {
                return false;
            }
            
            // Удаляем группу из списка групп пользователя
            $userGroups = $userData['GROUPS_ID'];
            if (is_array($userGroups)) {
                $userGroups = array_diff($userGroups, array($groupId));
            } else {
                $userGroups = array();
            }
            
            $arFields = array(
                'GROUP_ID' => $userGroups,
                'UF_ADVERSTERING_AGENT' => 0,
                'ACTIVE' => 'N'
            );
            
            $result = $user->Update($userId, $arFields);
            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        private function updateMarketingAgentPriceType($status, int $userId){
            // Получаем информацию о группе рекламных агентов
            $rsGroup = \CGroup::GetByID($this->MARKETING_AGENT_GROUP_ID);
            $groupData = $rsGroup->Fetch();

            $userId = (int)$userId;
            if ($userId <= 0) {
                return false;
            }
            
            if (!$groupData) {
                return false;
            }
            
            // Получаем текущий список пользователей в группе
            $currentUserIds = $this->getUsersInGroup($this->MARKETING_AGENT_GROUP_ID);
            
            // Определяем, нужно ли добавить или удалить пользователя из группы
            $isUserInGroup = in_array($userId, $currentUserIds);
            $shouldBeInGroup = ($status === 'Y' || $status === true || $status === 1 || $status === "1"
                || $status === 'y' || $status === 'Да' || $status === 'да');

            $out = true;
            if ($shouldBeInGroup && !$isUserInGroup) {
                $out = $this->addUserToGroup($userId, $this->MARKETING_AGENT_GROUP_ID);
            } elseif (!$shouldBeInGroup && $isUserInGroup) {
                if (!$this->removeUserFromGroupsByIds((int) $userId, [$this->MARKETING_AGENT_GROUP_ID])) {
                    $out = false;
                } else {
                    if ((int)$userId <= 1) {
                        // #region agent log
                        $this->agentDebugLog('user_sync_' . date('Ymd_His'), 'H23', 'User.php:updateMarketingAgentPriceType', 'Skip protected user deactivation in marketing sync', [
                            'user_id' => (int)$userId,
                            'status' => (string)$status,
                        ]);
                        // #endregion
                        $out = true;
                    } else {
                        $u = new \CUser();
                        $out = (bool) $u->Update((int) $userId, [
                            'ACTIVE' => 'N',
                            'UF_ADVERSTERING_AGENT' => 0,
                        ]);
                    }
                }
            }

            return $out;
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
            // Проверяем обязательные поля
            if (empty($fields['B24_ID'])) {
                return false;
            }

            $b24ID = $fields['B24_ID'];
            // Убираем ID из полей для обновления
            unset($fields['B24_ID']);

            $marketingSyncRaw = CrmInboundUfMap::peekMarketingAgentRawValue($fields);

            $this->userId = $this->getUserIDByB24ID($b24ID);
            $fields['UF_MANAGER'] = $this->getManagerID($fields['ASSIGNED_MANAGER']);
            $fields['UF_MANAGER2'] = $this->getManagerID($fields['SECOND_MANAGER']);
            
            if (!$this->userId) {
                return false;
            }

            CrmInboundUfMap::prepareUserUpdatePayload($fields);

            $fields = $this->sanitizeInboundUserFields((array)$fields);
            // Обновляем пользователя на сайте
            $user = new \CUser();

            if (($fields['ACTION'] ?? '') === 'UPDATE_CONTACT' && array_key_exists('UF_IS_DIRECTOR', $fields)) {
                if ($this->isCrmDirectorFlagOn($fields['UF_IS_DIRECTOR'])) {
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
                $cur = $this->normalizeUserGroupIds(\CUser::GetUserGroup($this->userId));
                if (!in_array($this->DIRECTOR_GROUP_ID, $cur, true)) {
                    \CUser::SetUserGroup($this->userId, $this->normalizeUserGroupIds(array_merge($cur, [$this->DIRECTOR_GROUP_ID])));
                }
                } else {
                // Убираем из группы руководителей только если CRM явно прислала UF_IS_DIRECTOR (частичный payload без ключа не трогает 432).
                $cur = $this->normalizeUserGroupIds(\CUser::GetUserGroup($this->userId));
                if (in_array($this->DIRECTOR_GROUP_ID, $cur, true)) {
                    $new = array_values(array_diff($cur, [$this->DIRECTOR_GROUP_ID]));
                    $new = $this->ensureCompanyDiscountGroupsPreserved($cur, $new);
                    \CUser::SetUserGroup($this->userId, $new);
                }
                }
            }

            // Внешний payload (B24 → ajax) не должен перезаписывать членство в группах напрямую.
            unset($fields['GROUP_ID'], $fields['GROUPS_ID'], $fields['IS_MARKETING_AGENT']);

            // Синхронизация контакта из CRM: включить учётную запись, если CRM явно не передала ACTIVE.
            if (($fields['ACTION'] ?? '') === 'UPDATE_CONTACT' && !\array_key_exists('ACTIVE', $fields)) {
                $fields['ACTIVE'] = 'Y';
            }

            $result = $user->Update($this->userId, $fields);
            if ($result) {
                if ($marketingSyncRaw !== null) {
                    $this->updateMarketingAgentPriceType($marketingSyncRaw, $this->userId);
                }
                return true;
            } else {
                return false;
            }
        }

        public function updateBatch($fields){
            // Проверяем обязательные поля
            if (empty($fields['CONTACT_IDS'])) {
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

            if ((int)$this->userId <= 1) {
                return true;
            }

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

        /**
         * Трактовка UF_IS_DIRECTOR из CRM (как у рекламного агента): не использовать «PHP truthy» для строк вроде 'N'.
         */
        private function isCrmDirectorFlagOn(mixed $v): bool
        {
            return $v === true || $v === 1 || $v === '1' || $v === 'Y' || $v === 'y' || $v === 'Да';
        }

        /**
         * @param array<int|string|mixed> $ids
         * @return list<int>
         */
        private function normalizeUserGroupIds(array $ids): array
        {
            $out = [];
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    $out[$id] = $id;
                }
            }

            return array_values($out);
        }

        /**
         * Не терять скидочные группы компании (ID из CompanyModuleConfig) при пересборке списка для SetUserGroup.
         *
         * @param list<int> $before
         * @param list<int> $after
         * @return list<int>
         */
        private function ensureCompanyDiscountGroupsPreserved(array $before, array $after): array
        {
            $discountIds = array_keys(CompanyModuleConfig::getCompanyDiscountPercentByAssignedGroupId());
            if ($discountIds === []) {
                return $this->normalizeUserGroupIds($after);
            }

            $afterMap = array_fill_keys($this->normalizeUserGroupIds($after), true);
            foreach ($this->normalizeUserGroupIds($before) as $gid) {
                if (!in_array($gid, $discountIds, true)) {
                    continue;
                }
                if (!isset($afterMap[$gid])) {
                    $after[] = $gid;
                    $afterMap[$gid] = true;
                }
            }

            return $this->normalizeUserGroupIds($after);
        }

        /**
         * Контракт inbound-синхронизации: auth/security поля из CRM не должны мутировать сайтового пользователя.
         *
         * @param array<string,mixed> $fields
         * @return array<string,mixed>
         */
        private function sanitizeInboundUserFields(array $fields): array
        {
            foreach (self::INBOUND_PROTECTED_USER_FIELDS as $key) {
                unset($fields[$key]);
            }

            return $fields;
        }
    }