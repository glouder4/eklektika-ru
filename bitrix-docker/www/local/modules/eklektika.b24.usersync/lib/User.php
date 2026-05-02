<?php
    namespace OnlineService\B24;
    use Bitrix\Main\Event;
    use OnlineService\B24\Config\RestTransportConfig;
    use OnlineService\B24\Registration\Config\RegisterUserCompanyConfig;
    use OnlineService\B24\UserSync\Config\UserSyncConfig;
    use OnlineService\B24\Request;
    use OnlineService\Site\Config\CompanyModuleConfig;
    use OnlineService\Sync\FromCrm\CrmInboundUfMap;
    use OnlineService\Sync\SyncTrace;

    class User extends Request{ 
        public ?int $contactId = null;

        public int $userId;

        /** Код последней неудачи {@see User::update()} для ответа inbound JSON (без PII). */
        private ?string $lastUpdateFailReason = null;
        
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

        public function getLastUpdateFailReason(): ?string
        {
            return $this->lastUpdateFailReason;
        }

        /**
         * Почему нельзя однозначно сопоставить контакт B24 с одним пользователем сайта.
         */
        private function classifyB24ContactMappingFailure(int $b24ContactId): string
        {
            $uniq = [];
            foreach ([UserSyncConfig::USER_UF_CONTACT_B24_ID, UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] as $ufField) {
                foreach ($this->collectUserIdsByContactUfField($b24ContactId, $ufField) as $uid) {
                    $uniq[(int)$uid] = true;
                }
            }
            $n = \count($uniq);

            return $n > 1 ? 'site_user_ambiguous_b24_contact' : 'site_user_not_found';
        }

        /**
         * CRM передаёт ID пользователя сайта в {@see RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD},
         * если по UF контакта B24 пользователь ещё не найден.
         *
         * @param array<string, mixed> $fields
         */
        private function tryResolveSiteUserIdFromCrmPayload(array $fields, int $b24ContactId): int|false
        {
            $key = RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD;
            if (!\array_key_exists($key, $fields)) {
                return false;
            }
            $raw = $this->unwrapInboundCrmScalar($fields[$key]);
            if ($raw === null || $raw === '' || $raw === false) {
                return false;
            }
            if (!\is_scalar($raw)) {
                $this->lastUpdateFailReason = 'crm_site_user_id_invalid';

                return false;
            }
            $siteUid = (int)(string)$raw;
            if ($siteUid <= 0) {
                $this->lastUpdateFailReason = 'crm_site_user_id_invalid';

                return false;
            }

            $rsUser = \CUser::GetList(
                ['ID' => 'ASC'],
                'id',
                ['ID' => $siteUid],
                ['SELECT' => ['ID', UserSyncConfig::USER_UF_CONTACT_B24_ID, UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY]]
            );
            $row = $rsUser->Fetch();
            if (!$row) {
                $this->lastUpdateFailReason = 'site_user_from_crm_field_not_found';

                return false;
            }

            $main = $this->intFromUserUf($row[UserSyncConfig::USER_UF_CONTACT_B24_ID] ?? null);
            $leg = $this->intFromUserUf($row[UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] ?? null);
            if (($main > 0 && $main !== $b24ContactId) || ($leg > 0 && $leg !== $b24ContactId)) {
                $this->lastUpdateFailReason = 'site_user_crm_site_id_b24_mismatch';

                return false;
            }

            return $siteUid;
        }

        private function intFromUserUf(mixed $raw): int
        {
            if ($raw === null || $raw === '' || $raw === false) {
                return 0;
            }
            if (\is_array($raw)) {
                $raw = $raw['VALUE'] ?? $raw['~VALUE'] ?? null;
            }
            if ($raw === null || $raw === '' || !\is_scalar($raw)) {
                return 0;
            }

            return (int)(string)$raw;
        }

        private function unwrapInboundCrmScalar(mixed $v): mixed
        {
            for ($i = 0; $i < 6 && \is_array($v); $i++) {
                if (\array_key_exists('VALUE', $v)) {
                    $v = $v['VALUE'];

                    continue;
                }
                $first = \reset($v);
                $v = $first === false ? null : $first;
            }

            return $v;
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
            if( isset($arFields['UF_ADVERSTERING_AGENT']) ) {
                $this->updateMarketingAgentPriceType($arFields['UF_ADVERSTERING_AGENT'], $userId);
            }

            if ($this->shouldPushLocalProfileToB24Crm($arFields) && !isset(self::$b24CrmProfilePushCoalesced[$userId])) {
                self::$b24CrmProfilePushCoalesced[$userId] = true;
                $this->pushLocalUserProfileToB24Crm($userId);
            }

            return true;
        }

        /** @var array<int, true> одно пуш-обновление на пользователя за HTTP-запрос (см. дубли {@see \OnlineService\B24\UserSync\UserSyncBootstrap} + {@see \OnlineService\Events\SyncEventHandlers}) */
        private static array $b24CrmProfilePushCoalesced = [];

        /** @var list<string> */
        private const LOCAL_TO_CRM_PROFILE_FIELD_KEYS = [
            'NAME',
            'LAST_NAME',
            'SECOND_NAME',
            'EMAIL',
            'PERSONAL_PHONE',
            'WORK_PHONE',
        ];

        private function shouldPushLocalProfileToB24Crm(array $arFields): bool
        {
            if (!empty($GLOBALS['OS_SKIP_USERSYNC_EVENTS']) || (defined('OS_SKIP_USERSYNC_EVENTS') && \OS_SKIP_USERSYNC_EVENTS === true)) {
                return false;
            }
            if (defined('ADMIN_SECTION') && \ADMIN_SECTION === true) {
                return false;
            }
            foreach (self::LOCAL_TO_CRM_PROFILE_FIELD_KEYS as $k) {
                if (\array_key_exists($k, $arFields)) {
                    return true;
                }
            }

            return false;
        }

        private function getB24ContactIdForSiteUser(int $userId): int
        {
            if ($userId <= 0) {
                return 0;
            }
            $rs = \CUser::GetByID($userId);
            $row = $rs ? $rs->Fetch() : null;
            if (!\is_array($row)) {
                return 0;
            }
            $main = (int) $this->intFromUserUf($row[UserSyncConfig::USER_UF_CONTACT_B24_ID] ?? null);
            if ($main > 0) {
                return $main;
            }

            return (int) $this->intFromUserUf($row[UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] ?? null);
        }

        /**
         * crm.contact.update: данные с сайта совпадают с форматом полей телефона/email, собираемых в CRM‑ветке регистрации ({@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator}).
         *
         * @param array<string, mixed> $u строка b_user
         * @return array<string, mixed>
         */
        private function buildCrmContactFieldsFromUserRowForPush(array $u): array
        {
            $fields = [
                'NAME' => \trim((string)($u['NAME'] ?? '')),
                'LAST_NAME' => \trim((string)($u['LAST_NAME'] ?? '')),
                'SECOND_NAME' => \trim((string)($u['SECOND_NAME'] ?? '')),
            ];
            $work = \trim((string)($u['WORK_PHONE'] ?? ''));
            $mobile = \trim((string)($u['PERSONAL_PHONE'] ?? ''));
            $phones = [];
            if ($work !== '') {
                $phones[] = ['VALUE' => $work, 'VALUE_TYPE' => 'WORK'];
            }
            if ($mobile !== '') {
                $phones[] = ['VALUE' => $mobile, 'VALUE_TYPE' => 'MOBILE'];
            }
            if ($phones !== []) {
                $fields['PHONE'] = $phones;
            }
            $email = \trim((string)($u['EMAIL'] ?? ''));
            if ($email !== '') {
                $fields['EMAIL'] = [
                    ['VALUE' => $email, 'VALUE_TYPE' => 'WORK'],
                ];
            }
            $siteUid = (int)($u['ID'] ?? 0);
            if ($siteUid > 1) {
                $fields[RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD] = $siteUid;
            }
            $fields[RegisterUserCompanyConfig::CRM_CONTACT_IS_DIRECTOR_FIELD] = CrmInboundUfMap::userDirectorUfToCrmInt($u['UF_IS_DIRECTOR'] ?? null);

            return $fields;
        }

        /**
         * DEBUG (sync_debug): вызов **после** `crm.contact.update`, чтобы в B24 ушла реальная запись; затем pre()+die (ломает JSON аякса, только кратковременная отладка).
         *
         * @param array<string, mixed> $data
         */
        private static function debugLkB24ProfilePreDumpAfterB24(array $data): void
        {
            if (\function_exists('pre')) {
                pre($data);
            } else {
                $json = \json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
                echo $json === false
                    ? '<pre>' . \print_r($data, true) . '</pre>'
                    : '<pre>' . \htmlspecialchars($json) . '</pre>';
            }
            die();
        }

        /**
         * Проталкивает в CRM контакт, привязанный по UF, после изменения ФИО/тел/почты на сайте (ЛК и т.д.).
         */
        private function pushLocalUserProfileToB24Crm(int $userId): void
        {
            $syncDbg = \class_exists(SyncTrace::class, false) && SyncTrace::isDebugModeEnabled();
            $contactId = $this->getB24ContactIdForSiteUser($userId);
            if ($contactId <= 0) {
                if ($syncDbg && \class_exists(SyncTrace::class, false)) {
                    $q = (defined('URL_B24') ? (string) \URL_B24 : 'URL_B24?') . \ltrim(RestTransportConfig::SITE_REQUESTS_HANDLER_PATH, '/');
                    SyncTrace::add('User::pushLocalUserProfileToB24Crm no_crm_uf', [
                        'user_id' => $userId,
                        'url_post' => $q,
                    ]);
                }
                $this->fireUserProfileB24SyncEvent($userId, 0, false, 'no_crm_contact_in_uf');

                return;
            }
            $rs = \CUser::GetByID($userId);
            $u = $rs ? $rs->Fetch() : null;
            if (!\is_array($u)) {
                if ($syncDbg) {
                    SyncTrace::add('User::pushLocalUserProfileToB24Crm no_site_user_row', [
                        'user_id' => $userId,
                        'b24_contact_id' => $contactId,
                    ]);
                }
                $this->fireUserProfileB24SyncEvent($userId, $contactId, false, 'site_user_not_found');

                return;
            }
            $crmFields = $this->buildCrmContactFieldsFromUserRowForPush($u);
            $postUrl = (defined('URL_B24') ? (string) \URL_B24 : 'URL_B24?')
                . \ltrim(RestTransportConfig::SITE_REQUESTS_HANDLER_PATH, '/');
            $postBody = [
                'ACTION' => 'CRM_METHOD',
                'METHOD' => 'crm.contact.update',
                'PARAMS' => [
                    'id' => $contactId,
                    'fields' => $crmFields,
                ],
            ];
            if ($syncDbg) {
                SyncTrace::add('User::pushLocalUserProfileToB24Crm before_rest', [
                    'user_id' => $userId,
                    'contact_id' => $contactId,
                    'url_post' => $postUrl,
                ]);
            }
            $result = \OnlineService\B24\RestClient::callRestMethod('crm.contact.update', [
                'id' => $contactId,
                'fields' => $crmFields,
            ], false);
            $ok = $result === true
                || $result === 1
                || $result === '1'
                || (is_array($result) && (isset($result['ID']) || isset($result['id'])));
            if (! $ok) {
                $err = 'crm_contact_update_failed';
                if (\is_array($result) && (isset($result['error']) || (isset($result['success']) && (int) $result['success'] === 0))) {
                    $err = 'rest_error';
                }
                if (\class_exists(SyncTrace::class, false) && SyncTrace::enabled()) {
                    SyncTrace::add('User::pushLocalUserProfileToB24Crm', [
                        'user_id' => $userId,
                        'contact_id' => $contactId,
                        'ok' => false,
                        'result_type' => \is_object($result) ? 'object' : \gettype($result),
                    ]);
                }
                $this->fireUserProfileB24SyncEvent($userId, $contactId, false, $err, $result);
                if ($syncDbg) {
                    self::debugLkB24ProfilePreDumpAfterB24([
                        'ПРИМЕЧАНИЕ' => 'Запрос в B24 УЖЕ ушёл; смотри ОТВЕТ_ИЗ_CRM, ниже событиe тоже отправлен (SUCCESS=false).',
                        'КУДА' => $postUrl,
                        'ЗАПРОС' => $postBody,
                        'ОТВЕТ_ИЗ_CRM' => $result,
                        'успех' => false,
                    ]);
                }

                return;
            }
            if (\class_exists(SyncTrace::class, false) && SyncTrace::enabled()) {
                SyncTrace::add('User::pushLocalUserProfileToB24Crm', [
                    'user_id' => $userId,
                    'contact_id' => $contactId,
                    'ok' => true,
                ]);
            }
            $this->fireUserProfileB24SyncEvent($userId, $contactId, true, null, null);
            if ($syncDbg) {
                self::debugLkB24ProfilePreDumpAfterB24([
                    'ПРИМЕЧАНИЕ' => 'Запрос в B24 УЖЕ ушёл; EklektikaOnAfterUserProfileB24Sync вызван; ниже сырые данные.',
                    'КУДА' => $postUrl,
                    'ЗАПРОС' => $postBody,
                    'ОТВЕТ_ИЗ_CRM' => $result,
                    'успех' => true,
                ]);
            }
        }

        /**
         * Слушатели: подписка на main / EklektikaOnAfterUserProfileB24Sync.
         *
         * @param mixed $rawResult
         */
        private function fireUserProfileB24SyncEvent(int $userId, int $contactId, bool $success, ?string $reasonCode, $rawResult = null): void
        {
            if (!\class_exists(\Bitrix\Main\Event::class, false)) {
                return;
            }
            $params = [
                'USER_ID' => $userId,
                'CRM_CONTACT_ID' => $contactId,
                'SUCCESS' => $success,
            ];
            if ($reasonCode !== null && $reasonCode !== '') {
                $params['REASON'] = $reasonCode;
            }
            if ($rawResult !== null && \is_array($rawResult) && (isset($rawResult['error']) || isset($rawResult['error_description']))) {
                $params['REST_ERROR'] = $rawResult;
            }
            $ev = new Event('main', 'EklektikaOnAfterUserProfileB24Sync', $params);
            $ev->send();
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
         * - 'B24_ID' или 'ID' => ID контакта в B24; сохраняется в {@see UserSyncConfig::USER_UF_CONTACT_B24_ID} (и legacy) при {@see CUser::Update}
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
            $this->lastUpdateFailReason = null;

            $inboundAction = isset($fields['ACTION']) && \is_scalar($fields['ACTION'])
                ? (string)$fields['ACTION']
                : '';

            // Вход с портала B24: ID контакта в CRM часто приходит как ID (как в DELETE_CONTACT), B24_ID — опционально.
            if (empty($fields['B24_ID'])) {
                $idAlt = $fields['ID'] ?? null;
                if (\is_scalar($idAlt) && (string)$idAlt !== '') {
                    $fields['B24_ID'] = $idAlt;
                }
            }

            if (empty($fields['B24_ID'])) {
                $this->lastUpdateFailReason = 'missing_b24_contact_id';

                return false;
            }

            $b24ID = $fields['B24_ID'];
            unset($fields['B24_ID']);

            $marketingSyncRaw = CrmInboundUfMap::peekMarketingAgentRawValue($fields);

            $resolvedUserId = $this->getUserIDByB24ID($b24ID);
            $resolvedVia = 'b24_uf';
            if ($resolvedUserId === false || $resolvedUserId <= 0) {
                $this->lastUpdateFailReason = null;
                $fallback = $this->tryResolveSiteUserIdFromCrmPayload($fields, (int)$b24ID);
                if ($fallback !== false && $fallback > 0) {
                    $resolvedUserId = $fallback;
                    $resolvedVia = 'crm_site_user_id_field';
                }
            }

            if ($resolvedUserId === false || $resolvedUserId <= 0) {
                if ($this->lastUpdateFailReason === null || $this->lastUpdateFailReason === '') {
                    $this->lastUpdateFailReason = $this->classifyB24ContactMappingFailure((int)$b24ID);
                }
                if (\class_exists(\OnlineService\Sync\SyncTrace::class, false) && \OnlineService\Sync\SyncTrace::enabled()) {
                    \OnlineService\Sync\SyncTrace::add('User::update no_site_user', [
                        'reason_code' => $this->lastUpdateFailReason,
                        'b24_contact_id' => (int)$b24ID,
                    ]);
                }

                return false;
            }
            $this->userId = $resolvedUserId;

            if (\class_exists(\OnlineService\Sync\SyncTrace::class, false) && \OnlineService\Sync\SyncTrace::enabled()) {
                \OnlineService\Sync\SyncTrace::add('User::update site_user_resolved', [
                    'site_user_id' => $this->userId,
                    'b24_contact_id' => (int)$b24ID,
                    'via' => $resolvedVia,
                ]);
            }

            $fields['UF_MANAGER'] = $this->getManagerID($fields['ASSIGNED_MANAGER']);
            $fields['UF_MANAGER2'] = $this->getManagerID($fields['SECOND_MANAGER']);

            /** @see CrmInboundUfMap::COMPANY_SITE_USER_IDS_UF / RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD — снимается в prepare. */
            $inboundCrmContactSiteUserId = 0;
            $crmSiteUserUfKey = RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD;
            if (\array_key_exists($crmSiteUserUfKey, $fields)) {
                $vRawUf = $fields[$crmSiteUserUfKey];
                if ($vRawUf !== null && (string) $vRawUf !== '' && (string) $vRawUf !== '0') {
                    $inboundCrmContactSiteUserId = (int) $vRawUf;
                }
            }

            CrmInboundUfMap::prepareUserUpdatePayload($fields);

            $fields = $this->sanitizeInboundUserFields((array)$fields);
            $crmContactIdForUf = (int) (\is_scalar($b24ID) ? (string) $b24ID : '0');
            if ($crmContactIdForUf > 0) {
                $fields[UserSyncConfig::USER_UF_CONTACT_B24_ID] = $crmContactIdForUf;
                $fields[UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] = $crmContactIdForUf;
            }
            // Обновляем пользователя на сайте
            $user = new \CUser();

            $isInboundContactUpdate = ($inboundAction === 'UPDATE_CONTACT' || $inboundAction === 'CONTACT_UPDATE');
            $skipSyncEventsWasSet = \array_key_exists('OS_SKIP_USERSYNC_EVENTS', $GLOBALS);
            $skipSyncEventsPrev = $skipSyncEventsWasSet ? $GLOBALS['OS_SKIP_USERSYNC_EVENTS'] : null;
            if ($isInboundContactUpdate) {
                // Inbound CRM updates must not emit outbound contact updates back to B24.
                $GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = true;
            }

            try {
                if (($fields['ACTION'] ?? '') === 'UPDATE_CONTACT' && array_key_exists('UF_IS_DIRECTOR', $fields)) {
                    $inboundIsDirector = $this->isCrmDirectorFlagOn($fields['UF_IS_DIRECTOR']);
                    $bossListUserId = $inboundCrmContactSiteUserId > 0 ? $inboundCrmContactSiteUserId : (int) $this->userId;
                    if ($inboundIsDirector) {
                        if (!\CModule::IncludeModule('iblock')) {
                            $this->lastUpdateFailReason = 'iblock_not_loaded';

                            return false;
                        }
                        $this->syncLeganAndOsCompanyBossForEmployeeFromCrm((int) $this->userId, $bossListUserId, true);
                    } else {
                        if (\CModule::IncludeModule('iblock')) {
                            $this->syncLeganAndOsCompanyBossForEmployeeFromCrm((int) $this->userId, $bossListUserId, false);
                        }
                    }
                    if ($inboundIsDirector) {
                        // Добавляем пользователя в группу руководителей (ID: 432)
                        $cur = $this->normalizeUserGroupIds(\CUser::GetUserGroup($this->userId));
                        if (!in_array($this->DIRECTOR_GROUP_ID, $cur, true)) {
                            \CUser::SetUserGroup(
                                $this->userId,
                                $this->normalizeUserGroupIds(\array_merge($cur, [$this->DIRECTOR_GROUP_ID]))
                            );
                        }
                    } else {
                        // Убираем из группы руководителей только если CRM явно прислала UF_IS_DIRECTOR (частичный payload без ключа не трогает 432).
                        $cur = $this->normalizeUserGroupIds(\CUser::GetUserGroup($this->userId));
                        if (in_array($this->DIRECTOR_GROUP_ID, $cur, true)) {
                            $new = \array_values(\array_diff($cur, [$this->DIRECTOR_GROUP_ID]));
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

                unset($fields['ID'], $fields['ACTION'], $fields['sync_token']);

                $result = $user->Update($this->userId, $fields);
                if ($result) {
                    if ($marketingSyncRaw !== null) {
                        $this->updateMarketingAgentPriceType($marketingSyncRaw, $this->userId);
                    }
                    if (\class_exists(\OnlineService\Sync\SyncTrace::class, false) && \OnlineService\Sync\SyncTrace::enabled()) {
                        \OnlineService\Sync\SyncTrace::add('User::update CUser::Update ok', ['site_user_id' => $this->userId]);
                    }

                    if ($inboundAction === 'UPDATE_CONTACT' && \CModule::IncludeModule('iblock')) {
                        $crmContactId = (int)(\is_scalar($b24ID) ? (string)$b24ID : '0');
                        if ($crmContactId > 0 && $this->userId > 0) {
                            $this->repairCompanyUserListsAfterContactSiteIdSync($this->userId, $crmContactId);
                        }
                    }

                    return true;
                }

                $this->lastUpdateFailReason = 'cuser_update_rejected';
                if (\class_exists(\OnlineService\Sync\SyncTrace::class, false) && \OnlineService\Sync\SyncTrace::enabled()) {
                    $le = (string)($user->LAST_ERROR ?? '');
                    $le = \preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}/i', '<email>', $le) ?? $le;
                    if (\strlen($le) > 240) {
                        $le = \substr($le, 0, 240) . '…';
                    }
                    \OnlineService\Sync\SyncTrace::add('User::update CUser::Update failed', [
                        'site_user_id' => $this->userId,
                        'last_error' => $le,
                    ]);
                }

                return false;
            } finally {
                if ($isInboundContactUpdate) {
                    if ($skipSyncEventsWasSet) {
                        $GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = $skipSyncEventsPrev;
                    } else {
                        unset($GLOBALS['OS_SKIP_USERSYNC_EVENTS']);
                    }
                }
            }
        }

        /**
         * В OS_COMPANY_USERS / LEGAN_ENTITY_USERS иногда остаётся ID контакта CRM вместо ID пользователя сайта
         * (до корректного UPDATE_COMPANY). После успешного UPDATE_CONTACT с известным соответствием контакт → user
         * заменяем «чужой» ID контакта на {@see $siteUserId} в обоих свойствах.
         */
        private function repairCompanyUserListsAfterContactSiteIdSync(int $siteUserId, int $crmContactId): void
        {
            if ($crmContactId <= 0 || $siteUserId <= 0 || $crmContactId === $siteUserId) {
                return;
            }

            $companyIds = $this->findCompanyElementIdsForB24ContactInUserLists($crmContactId);
            if ($companyIds === []) {
                return;
            }

            $iblockId = (int)CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $norm = static function (array $a): array {
                $m = [];
                foreach ($a as $x) {
                    $i = (int)$x;
                    if ($i > 0) {
                        $m[$i] = true;
                    }
                }
                $k = \array_keys($m);
                \sort($k);

                return $k;
            };

            foreach ($companyIds as $companyId) {
                $os = $this->readCompanyMultiIntProperty($companyId, 'OS_COMPANY_USERS');
                $legan = $this->readCompanyMultiIntProperty($companyId, 'LEGAN_ENTITY_USERS');

                $newOs = $this->replaceB24ContactIdWithSiteUserInIdList($os, $crmContactId, $siteUserId);
                $newLegan = $this->replaceB24ContactIdWithSiteUserInIdList($legan, $crmContactId, $siteUserId);

                if ($norm($newOs) === $norm($os) && $norm($newLegan) === $norm($legan)) {
                    continue;
                }

                $el = new \CIBlockElement();
                if ($norm($newOs) !== $norm($os)) {
                    $el->SetPropertyValues($companyId, $iblockId, $newOs, 'OS_COMPANY_USERS');
                }
                if ($norm($newLegan) !== $norm($legan)) {
                    $el->SetPropertyValues($companyId, $iblockId, $newLegan, 'LEGAN_ENTITY_USERS');
                }

                if (\class_exists(\OnlineService\Sync\SyncTrace::class, false) && \OnlineService\Sync\SyncTrace::enabled()) {
                    \OnlineService\Sync\SyncTrace::add('User::update company_user_lists_repaired', [
                        'company_element_id' => $companyId,
                        'crm_contact_id' => $crmContactId,
                        'site_user_id' => $siteUserId,
                    ]);
                }
            }
        }

        /**
         * @return list<int>
         */
        private function findCompanyElementIdsForB24ContactInUserLists(int $crmContactId): array
        {
            $seen = [];
            foreach (['OS_COMPANY_USERS', 'LEGAN_ENTITY_USERS'] as $code) {
                $rs = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                        'PROPERTY_' . $code => $crmContactId,
                    ],
                    false,
                    false,
                    ['ID']
                );
                while ($row = $rs->Fetch()) {
                    $seen[(int)$row['ID']] = true;
                }
            }

            return \array_map('intval', \array_keys($seen));
        }

        /**
         * @return list<int>
         */
        private function readCompanyMultiIntProperty(int $companyElementId, string $propertyCode): array
        {
            $out = [];
            $rs = \CIBlockElement::GetProperty(
                CompanyModuleConfig::COMPANY_IBLOCK_ID,
                $companyElementId,
                [],
                ['CODE' => $propertyCode]
            );
            while ($row = $rs->Fetch()) {
                $v = $row['VALUE'] ?? null;
                if ($v === null || $v === '' || $v === false) {
                    continue;
                }
                if (\is_scalar($v)) {
                    $out[] = (int)$v;
                }
            }

            return $out;
        }

        /**
         * @param list<int>|array<int|string> $ids
         *
         * @return list<int>
         */
        private function replaceB24ContactIdWithSiteUserInIdList(array $ids, int $crmContactId, int $siteUserId): array
        {
            $set = [];
            foreach ($ids as $raw) {
                $id = (int)$raw;
                if ($id <= 0) {
                    continue;
                }
                if ($id === $crmContactId) {
                    $set[$siteUserId] = true;
                } else {
                    $set[$id] = true;
                }
            }

            $out = \array_map('intval', \array_keys($set));
            \sort($out);

            return $out;
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

            return true;
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
                'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
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
                'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
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
         * Элементы ИБ 23, где $employeeUserId в LEGAN_ENTITY_USERS (приоритет) или в OS_COMPANY_USERS.
         *
         * @return list<int>
         */
        private function collectCompanyElementIdsWhereUserIsEmployee(int $employeeUserId): array
        {
            if ($employeeUserId <= 0) {
                return [];
            }
            $ib = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $out = [];
            $props = ['LEGAN_ENTITY_USERS', 'OS_COMPANY_USERS'];
            foreach ($props as $p) {
                $rs = \CIBlockElement::GetList(
                    ['ID' => 'ASC'],
                    [
                        'IBLOCK_ID' => $ib,
                        'ACTIVE' => 'Y',
                        "PROPERTY_{$p}" => $employeeUserId,
                    ],
                    false,
                    false,
                    ['ID']
                );
                if (!$rs) {
                    continue;
                }
                while ($row = $rs->GetNext()) {
                    $eid = (int)($row['ID'] ?? 0);
                    if ($eid > 0) {
                        $out[$eid] = true;
                    }
                }
            }

            return $this->normalizeUserGroupIds(\array_map('intval', \array_keys($out)));
        }

        /**
         * @return list<int>
         */
        private function fetchIblockUserIdListByPropertyCode(int $iblockId, int $elementId, string $code): array
        {
            $out = [];
            $propertyValues = \CIBlockElement::GetProperty(
                $iblockId,
                $elementId,
                [],
                ['CODE' => $code]
            );
            if ($propertyValues) {
                while ($prop = $propertyValues->GetNext()) {
                    if (isset($prop['VALUE']) && (string) $prop['VALUE'] !== '') {
                        $out[] = (int) $prop['VALUE'];
                    }
                }
            }

            return $this->normalizeUserGroupIds($out);
        }

        /**
         * CRM: номер в списке руководителей = {@see CrmInboundUfMap::COMPANY_SITE_USER_IDS_UF} (сайт user id, обычно = контакт);
         * добавляем/убираем id в зеркалах OS/LEGAN (см. `OnlineService\Site\Company`).
         */
        private function syncLeganAndOsCompanyBossForEmployeeFromCrm(
            int $employeeSiteUserId,
            int $bossListUserId,
            bool $addToBossList
        ): void {
            if ($employeeSiteUserId <= 0 || $bossListUserId <= 0) {
                return;
            }
            $ib = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            foreach ($this->collectCompanyElementIdsWhereUserIsEmployee($employeeSiteUserId) as $eid) {
                $mapOs = [];
                $mapLg = [];
                foreach ($this->fetchIblockUserIdListByPropertyCode($ib, $eid, 'OS_COMPANY_BOSS') as $i) {
                    if ($i > 0) {
                        $mapOs[$i] = true;
                    }
                }
                foreach ($this->fetchIblockUserIdListByPropertyCode($ib, $eid, 'LEGAN_ENTITY_BOSS') as $i) {
                    if ($i > 0) {
                        $mapLg[$i] = true;
                    }
                }
                if ($addToBossList) {
                    $mapOs[$bossListUserId] = true;
                    $mapLg[$bossListUserId] = true;
                } else {
                    unset($mapOs[$bossListUserId], $mapLg[$bossListUserId]);
                }
                $newOs = $this->normalizeUserGroupIds(\array_map('intval', \array_keys($mapOs)));
                $newLg = $this->normalizeUserGroupIds(\array_map('intval', \array_keys($mapLg)));
                $el = new \CIBlockElement();
                $el->SetPropertyValues($eid, $ib, $newOs, 'OS_COMPANY_BOSS');
                $el->SetPropertyValues($eid, $ib, $newLg, 'LEGAN_ENTITY_BOSS');
            }
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