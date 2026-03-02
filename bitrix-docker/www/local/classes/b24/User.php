<?php
    namespace OnlineService\B24;
    use OnlineService\B24\Request;
    class User extends Request{
        public ?int $contactId = null;

        public int $userId;
        
        // Константы для ID групп
        public int $MARKETING_AGENT_GROUP_ID = 12;
        public int $DIRECTOR_GROUP_ID = 432;
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

            // Ищем пользователя по полю UF_B24_USER_ID
            $rsUser = \CUser::GetList(
                array(), 
                $order = "asc", 
                array('UF_B24_USER_ID' => $b24ContactId),
                array('SELECT' => array('ID', 'UF_B24_USER_ID'))
            );

            if ($userObject = $rsUser->Fetch()) {
                return $userObject['ID'];
            } else {
                //pre("User not found for B24 contact ID: " . $b24ContactId);
                return false;
            }
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

        public function OnAfterUserUpdateHandler($arFields){
            $userObject = $this->getUserObject($arFields['ID']);
            if( isset($arFields['UF_ADVERSTERING_AGENT']) )
                $this->updateMarketingAgentPriceType($arFields['UF_ADVERSTERING_AGENT']);

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
            $userGroups = $this->getUserGroups($userId);
            
            // Проверяем, не добавлен ли пользователь уже в эту группу
            if (in_array($groupId, $userGroups)) {
                pre("Пользователь ID " . $userId . " уже находится в группе " . $groupId);
                return true;
            }
            
            // Добавляем новую группу к существующим группам
            $userGroups[] = $groupId;
            
            $arFields = array(
                'GROUP_ID' => $userGroups,
                'UF_ADVERSTERING_AGENT' => 1,
                'ACTIVE' => 'Y'
            );
            
            $result = (new \CUser)->Update($userId, $arFields);
            
            if ($result) {
                return true;
            } else {
                return false;
            }
        }
        public function addUserToGroups($userId, $groupIds, $userObj = null){
            $user = (new \CUser);

            // Получаем текущие группы пользователя
            $userGroups = $groupIds;

            $arFields = array(
                'GROUP_ID' => $userGroups
            );
            
            if( in_array($this->MARKETING_AGENT_GROUP_ID,$userGroups) ){
                $arFields['UF_ADVERSTERING_AGENT'] = 1;
                $arFields['ACTIVE'] = "Y";
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
            
            // Получаем текущие группы пользователя
            $rsUser = \CUser::GetByID($userId);
            $userData = $rsUser->Fetch();
            
            if (!$userData) {
                pre("Пользователь ID " . $userId . " не найден");
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
            // Проверяем обязательные поля
            if (empty($fields['B24_ID'])) {
                pre("Error: B24 Contact ID is required for user update");
                return false;
            }

            $b24ID = $fields['B24_ID'];
            // Убираем ID из полей для обновления
            unset($fields['B24_ID']);

            $this->userId = $this->getUserIDByB24ID($b24ID);

            $fields['UF_MANAGER'] = $this->getManagerID($fields['ASSIGNED_MANAGER']);
            $fields['UF_MANAGER2'] = $this->getManagerID($fields['SECOND_MANAGER']);
            
            if (!$this->userId) {
                pre("Error: User not found for B24 contact ID: " . $b24ID);
                return false;
            }

            // Обновляем пользователя на сайте
            $user = new \CUser();

            if( $fields['UF_IS_DIRECTOR'] && $fields['ACTION'] == "UPDATE_CONTACT" ){
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
            } else if (!$fields['UF_IS_DIRECTOR'] && $fields['ACTION'] == "UPDATE_CONTACT") {
                // Убираем пользователя из группы руководителей при снятии галочки
                $userGroups = \CUser::GetUserGroup($this->userId);
                if (($key = array_search($this->DIRECTOR_GROUP_ID, $userGroups)) !== false) {
                    unset($userGroups[$key]);
                    \CUser::SetUserGroup($this->userId, $userGroups);
                    pre("User ID " . $this->userId . " removed from Directors group (ID: " . $this->DIRECTOR_GROUP_ID . ")");
                }
            }

            $result = $user->Update($this->userId, $fields);

            if ($result) {
                pre("User updated successfully on site");
                return true;
            } else {
                pre("Error updating user on site: " . $user->LAST_ERROR);
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