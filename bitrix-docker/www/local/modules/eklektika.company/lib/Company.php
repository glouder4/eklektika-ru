<?php
    namespace OnlineService\Site;

    use OnlineService\B24\RestClient;
    use OnlineService\B24\User;
    use OnlineService\Site\Config\CompanyB24Config;
    use OnlineService\Site\Config\CompanyModuleConfig;

    class Company{
        private static $codeProps = [
            "OS_COMPANY_IS_HEAD_OF_HOLDING",
            "OS_COMPANY_BOSS",
            "OS_HEAD_COMPANY_B24_ID",
            "OS_HOLDING_OF",
            "OS_COMPANY_INN",
            "OS_COMPANY_WEB_SITE",
            "OS_COMPANY_USERS",
            "OS_COMPANY_NAME",
            "OS_COMPANY_PHONE",
            "OS_COMPANY_EMAIL",
            "OS_COMPANY_B24_ID",
            'OS_COMPANY_CITY',
            'OS_IS_MARKETING_AGENT',
            "OS_IS_COMPANY_DISABLED",
            "OS_COMPANY_DISCOUNT_VALUE",
            'OS_REQUSITES_FILE',
            /** Приходят из CRM в payload UPDATE_COMPANY (зеркало в LEGAN_ENTITY_*) */
            'OS_COMPANY_JUR_ADDRESS',
            'OS_COMPANY_ACTIVITY',
        ];

        /**
         * Дублирование «служебных» свойств OS_* в пользовательские LEGAN_ENTITY_* (ИБ 23 / витрина).
         * Значения берутся уже после слияния с текущими свойствами элемента.
         *
         * @param array<string, mixed> $props
         */
        private static function mirrorOsCompanyFieldsToLeganEntity(array &$props): void
        {
            $map = [
                'OS_COMPANY_NAME' => 'LEGAN_ENTITY_NAME',
                'OS_COMPANY_PHONE' => 'LEGAN_ENTITY_PHONE',
                'OS_COMPANY_EMAIL' => 'LEGAN_ENTITY_EMAIL',
                'OS_COMPANY_WEB_SITE' => 'LEGAN_ENTITY_WWW',
                'OS_COMPANY_INN' => 'LEGAN_ENTITY_INN',
                'OS_COMPANY_CITY' => 'LEGAN_ENTITY_CITY',
                'OS_COMPANY_USERS' => 'LEGAN_ENTITY_USERS',
                'OS_COMPANY_BOSS' => 'LEGAN_ENTITY_BOSS',
                'OS_COMPANY_IS_HEAD_OF_HOLDING' => 'LEGAN_ENTITY_IS_HEAD_COMPANY',
                'OS_HOLDING_OF' => 'LEGAN_ENTITY_ID_OF_HEAD_COMPANY',
                'OS_REQUSITES_FILE' => 'LEGAN_ENTITY_FILE',
                'OS_COMPANY_JUR_ADDRESS' => 'LEGAN_ENTITY_ADRESS',
                'OS_COMPANY_ACTIVITY' => 'LEGAN_ENTITY_ACTIVITY',
            ];

            foreach ($map as $os => $legan) {
                if (!\array_key_exists($os, $props)) {
                    continue;
                }
                $v = $props[$os];
                if ($v === null || $v === '') {
                    continue;
                }
                if (\is_array($v) && $v === []) {
                    continue;
                }
                $props[$legan] = $v;
            }
        }

        /**
         * После публикации компании (ACTIVE=Y) включаем учётки сотрудников из списка сайтовых ID.
         *
         * @param list<int>|array<int|string> $siteUserIds
         */
        private static function activateCompanyStaffSiteUsers(array $siteUserIds): void
        {
            foreach ($siteUserIds as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue;
                }
                $user = new \CUser();
                $user->Update($uid, ['ACTIVE' => 'Y']);
            }
        }

        /**
         * @return list<int>
         */
        private static function normalizeCompanyUserIdsList(mixed $raw): array
        {
            if ($raw === null || $raw === '' || $raw === false) {
                return [];
            }
            if (!\is_array($raw)) {
                $one = (int)$raw;

                return $one > 0 ? [$one] : [];
            }
            $out = [];
            foreach ($raw as $v) {
                $iv = (int)$v;
                if ($iv > 0) {
                    $out[] = $iv;
                }
            }

            return $out;
        }

        /**
         * ID компании в B24 из входящего payload (без обращения к несуществующим ключам).
         */
        private static function normalizeIncomingCompanyB24Id(mixed $raw): string
        {
            if (!\is_scalar($raw)) {
                return '';
            }

            return \trim((string)$raw);
        }

        /**
         * @return array<int|string, mixed>
         */
        private static function contactIdsMapFromCompanyParams(array $params): array
        {
            if (!isset($params['CONTACT_IDS']) || !\is_array($params['CONTACT_IDS'])) {
                return [];
            }

            return $params['CONTACT_IDS'];
        }

        /**
         * @return string|false безопасное имя файла (basename), без path traversal
         */
        private static function sanitizeRequisitesOriginalFileName(mixed $name)
        {
            if (!\is_string($name)) {
                return false;
            }
            $base = \basename(\str_replace('\\', '/', $name));
            if ($base === '' || $base === '.' || $base === '..') {
                return false;
            }
            if (\str_contains($base, '..')) {
                return false;
            }

            return $base;
        }

        /**
         * Части пути к файлу на портале B24 (SUBDIR / FILE_NAME) — без «..».
         */
        private static function isSafeB24RequisiteUrlPart(mixed $subdir, mixed $fileNameInPath): bool
        {
            if (!\is_string($subdir) || !\is_string($fileNameInPath)) {
                return false;
            }
            if ($subdir === '' || $fileNameInPath === '') {
                return false;
            }
            if (\str_contains($subdir, '..') || \str_contains($fileNameInPath, '..')) {
                return false;
            }

            return true;
        }

        /**
         * Максимальный процент скидки по группам компании (пользователь в одной из b_group из маппинга статуса).
         *
         * @param array<int|string> $userGroupIds
         */
        public static function getMaxCompanyDiscountPercentForUserGroups(array $userGroupIds): float
        {
            if ($userGroupIds === []) {
                return 0.0;
            }
            $set = [];
            foreach ($userGroupIds as $g) {
                $ig = (int)$g;
                if ($ig > 0) {
                    $set[$ig] = true;
                }
            }

            $max = 0.0;
            foreach (CompanyModuleConfig::getCompanyDiscountPercentByAssignedGroupId() as $gid => $pct) {
                if (isset($set[(int)$gid])) {
                    $max = \max($max, (float)$pct);
                }
            }

            return $max;
        }

        /**
         * @param int|string|null $groupId ID группы после разрешения через searchGroup
         * @return int|string|null
         */
        private static function mapCompanyStatusGroupId($groupId)
        {
            if ($groupId === null || $groupId === '' || $groupId === false) {
                return $groupId;
            }
            $id = (int)$groupId;

            $statusGroupIdMap = CompanyModuleConfig::getCompanyStatusGroupIdMap();

            return $statusGroupIdMap[$id] ?? $groupId;
        }

        /**
         * ID групп на сайте, соответствующие скидке компании (взаимоисключающие — не более одной на пользователя).
         *
         * @return list<int>
         */
        private static function getCompanyDiscountAssignedGroupIds(): array
        {
            return array_values(array_unique(array_map('intval', array_values(CompanyModuleConfig::getCompanyStatusGroupIdMap()))));
        }

        /**
         * Синхронизация групп пользователя из UPDATE_COMPANY: при наличии в payload ключа
         * `OS_COMPANY_DISCOUNT_VALUE` — снять все скидочные группы компании,
         * затем выставить маркетинг (если есть в params) и не более одной скидочной ($discountMappedGroupId).
         * Если ключа скидки в payload нет — скидочные группы пользователя не меняются (частичные апдейты).
         * Администраторы и прочие группы не трогаем (кроме скидочных из маппинга).
         */
        private static function applyB24CompanyGroupsToUser(User $user, int $userId, array $params, ?int $discountMappedGroupId): void
        {
            $userId = (int)$userId;
            if ($userId <= 0) {
                return;
            }

            // Скидочные группы трогаем только если в payload явно передан OS_COMPANY_DISCOUNT_VALUE
            // (частичный UPDATE_COMPANY / цепочка после UPDATE_CONTACT не должна сбрасывать скидку).
            $touchDiscountGroups = array_key_exists('OS_COMPANY_DISCOUNT_VALUE', $params);

            if ($touchDiscountGroups) {
                $user->removeUserFromGroupsByIds($userId, self::getCompanyDiscountAssignedGroupIds());
            }

            $groups = [];
            if (!empty($params['OS_IS_MARKETING_AGENT']['VALUE'])) {
                $groups[] = $user->getMarketingGroupId();
            }
            if ($touchDiscountGroups && $discountMappedGroupId !== null && $discountMappedGroupId > 0) {
                $groups[] = $discountMappedGroupId;
            }

            if ($groups !== []) {
                $user->addUserToGroups($userId, $groups);
            }
        }

        /**
         * Руководитель (UF_IS_DIRECTOR) должен наследовать группу скидки только от головной компании холдинга,
         * а не от последней обновлённой дочерней (несколько компаний у одного пользователя).
         *
         * @param array<string, mixed> $companyUpdateParams параметры UPDATE_COMPANY / updateCompanyElement
         */
        private static function shouldApplyCompanyDiscountGroupForUser(int $userId, array $companyUpdateParams): bool
        {
            if (!self::isSiteUserDirector($userId)) {
                return true;
            }

            return self::isHeadOfHoldingFromCompanyParams($companyUpdateParams);
        }

        private static function isSiteUserDirector(int $userId): bool
        {
            if ($userId <= 0) {
                return false;
            }
            $rs = \CUser::GetByID($userId);
            if (!$u = $rs->Fetch()) {
                return false;
            }
            $v = $u['UF_IS_DIRECTOR'] ?? null;
            if ($v === null || $v === '' || $v === false) {
                return false;
            }
            if ($v === true || $v === 1 || $v === '1') {
                return true;
            }
            if (\is_string($v)) {
                $s = \strtoupper(\trim($v));

                return \in_array($s, ['Y', 'YES', 'TRUE', '1'], true);
            }

            return (bool)(int)$v;
        }

        /**
         * @param array<string, mixed> $companyUpdateParams
         */
        private static function isHeadOfHoldingFromCompanyParams(array $companyUpdateParams): bool
        {
            $v = $companyUpdateParams['OS_COMPANY_IS_HEAD_OF_HOLDING'] ?? null;
            if ($v === null || $v === '' || $v === false) {
                return false;
            }
            if (\is_array($v)) {
                $v = $v['VALUE'] ?? $v['~VALUE'] ?? null;
            }
            if ($v === null || $v === '' || $v === false) {
                return false;
            }
            if ($v === true || $v === 1 || $v === '1') {
                return true;
            }
            if (\is_string($v)) {
                $s = \strtoupper(\trim($v));

                return \in_array($s, ['Y', 'YES', 'TRUE', '1', 31520,'31520'], true);
            }

            return (bool)(int)$v;
        }

        /**
         * Получить ID инфоблока компаний
         * @return int
         */
        public function getIblockId(): int {
            return CompanyModuleConfig::COMPANY_IBLOCK_ID;
        }

        private static function callB24Method(string $method, array $params, bool $debug = false)
        {
            return RestClient::callRestMethod($method, $params, $debug);
        }

        public function createCompanyElement($params){
            /*$params = [
                'OS_COMPANY_INN'
                'OS_COMPANY_WEB_SITE'
                'OS_COMPANY_NAME'
                'OS_COMPANY_EMAIL'
                'OS_COMPANY_PHONE'
                'OS_COMPANY_B24_ID' - ID уже существующей компании
                'OS_COMPANY_CITY'
                'OS_REQUSITES_FILE',
                'USER_ID'
            ]; */


            if (!empty($params['OS_COMPANY_INN']) && empty($params['LEGAN_ENTITY_INN'])) {
                $params['LEGAN_ENTITY_INN'] = (string)$params['OS_COMPANY_INN'];
            }

            $b24CompanyId = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24CompanyId === '') {
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24CompanyId;

            // Ищем существующую компанию по OS_COMPANY_B24_ID
            $existingCompany = $this->getCompanyByB24ID($b24CompanyId);
            
            if ($existingCompany && !empty($existingCompany['ID'])) {
                // Компания найдена - дописываем пользователя в OS_COMPANY_USERS
                $companyId = $existingCompany['ID'];
                $currentUsers = $existingCompany['OS_COMPANY_USERS'] ?? [];
                
                // Если это массив, добавляем новый ID, иначе создаем массив
                if (is_array($currentUsers)) {
                    if (!in_array($params['USER_ID'], $currentUsers)) {
                        $currentUsers[] = $params['USER_ID'];
                    }
                } else {
                    $currentUsers = [$currentUsers, $params['USER_ID']];
                }
                
                // Обновляем свойство OS_COMPANY_USERS и зеркало LEGAN_ENTITY_USERS
                \CIBlockElement::SetPropertyValues(
                    $companyId,
                    CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    $currentUsers,
                    'OS_COMPANY_USERS'
                );
                \CIBlockElement::SetPropertyValues(
                    $companyId,
                    CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    $currentUsers,
                    'LEGAN_ENTITY_USERS'
                );

                return $companyId;
            } else {
                // Компания не найдена - создаем новую
                $el = new \CIBlockElement;

                // Устанавливаем пользователя в OS_COMPANY_USERS для новой компании
                $params['OS_COMPANY_USERS'] = [$params['USER_ID']];

                $propBag = $params;
                self::mirrorOsCompanyFieldsToLeganEntity($propBag);

                $arLoadProductArray = [
                    "IBLOCK_SECTION_ID" => false,
                    "IBLOCK_TYPE" => 'personal',
                    "IBLOCK_ID" => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    "PROPERTY_VALUES" => $propBag,
                    "NAME" => $params["OS_COMPANY_NAME"],
                    "ACTIVE" => "N",
                    "CODE" => $b24CompanyId
                ];

                if ($companyId = $el->Add($arLoadProductArray)) {
                    return $companyId;
                }
                
                return false;
            }
        }

        /**
         * Обновляет элемент компании в инфоблоке по B24_ID.
         *
         * @param array $params Массив параметров компании:
         *   - OS_COMPANY_B24_ID (string|int) — ID компании в B24 (обязательный)
         *   - OS_COMPANY_NAME (string) — Название компании
         *   - OS_COMPANY_IS_HEAD_OF_HOLDING (boolean) — Головная компания
         *   - OS_COMPANY_DISCOUNT_VALUE (string|int) — Скидка компании
         *   - OS_COMPANY_USERS (array|int) — ID связанных контактов
         *   - OS_COMPANY_INN (string) — ИНН компании
         *   - OS_COMPANY_CITY (string) — Город компании
         *   - OS_COMPANY_WEB_SITE (string) — Сайт компании
         *   - OS_COMPANY_PHONE (string) — Телефон компании
         *   - OS_COMPANY_EMAIL (string) — Email компании
         *   и другие свойства, поддерживаемые инфоблоком.
         *
         * @return int|false ID обновлённой компании или false в случае ошибки
         */
        public function updateCompanyElement($params){
            if (!empty($params['OS_COMPANY_INN']) && empty($params['LEGAN_ENTITY_INN'])) {
                $params['LEGAN_ENTITY_INN'] = (string)$params['OS_COMPANY_INN'];
            }

            $b24_id = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24_id === '') {
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24_id;

            $contactIdsMap = self::contactIdsMapFromCompanyParams($params);

            // Находим компанию по B24_ID
            $company = $this->getCompanyByB24ID($b24_id);

            if ($company && !empty($company['ID'])) {
                // Компания найдена - обновляем
                $companyId = $company['ID'];
                
                /*if (!empty($params['OS_COMPANY_DISCOUNT_VALUE'])) {
                    $params['OS_COMPANY_DISCOUNT_VALUE'] = (new UserGroups([]))->searchGroup($params['OS_COMPANY_DISCOUNT_VALUE'])['ID'];
                }*/

                if (!empty($params['OS_COMPANY_USERS']) && \is_array($params['OS_COMPANY_USERS'])) {
                    foreach ($params['OS_COMPANY_USERS'] as $key => $b24_id){
                        $user = new User();
                        $userId = $user->getUserIDByB24ID($b24_id);
                        if (!$userId && \array_key_exists($key, $contactIdsMap)) {
                            $altId = $contactIdsMap[$key];
                            if ($altId !== null && $altId !== '') {
                                $userId = $user->getUserIDByB24ID($altId);
                            }
                        }

                        if( $userId ){
                            $params['OS_COMPANY_USERS'][$key] =  $userId;

                            $discountMapped = null;
                            if (!empty($params['OS_COMPANY_DISCOUNT_VALUE'])
                                && self::shouldApplyCompanyDiscountGroupForUser((int)$userId, $params)
                            ) {
                                $mapped = self::mapCompanyStatusGroupId($params['OS_COMPANY_DISCOUNT_VALUE']);
                                $mappedInt = (int)$mapped;
                                if ($mappedInt > 0) {
                                    $discountMapped = $mappedInt;
                                }
                            }

                            self::applyB24CompanyGroupsToUser($user, (int)$userId, $params, $discountMapped);
                        }
                    }
                }

                if (!empty($params['OS_REQUSITES_FILE'])) {
                    $fileId = $this->processRequisitesFile($params['OS_REQUSITES_FILE']);
                    if ($fileId) {
                        $params['OS_REQUSITES_FILE'] = $fileId;
                    }
                }

                if (!empty($params['OS_HOLDING_OF'])) {
                    $holdingRef = $params['OS_HOLDING_OF'];
                    if (\is_array($holdingRef)) {
                        $params['OS_HOLDING_OF'] = !empty($holdingRef['ID']) ? (int)$holdingRef['ID'] : $holdingRef;
                    } else {
                        $holdingCompany = $this->getCompanyByB24ID($holdingRef);
                        if (!empty($holdingCompany['ID'])) {
                            $params['OS_HOLDING_OF'] = (int)$holdingCompany['ID'];
                        }
                    }
                }

                // Получаем текущие значения всех свойств компании
                $currentProps = [];
                foreach (self::$codeProps as $code) {
                    $propertyValues = \CIBlockElement::GetProperty(
                        CompanyModuleConfig::COMPANY_IBLOCK_ID,
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

                if (!empty($company['CODE'])) {
                    $params['OS_COMPANY_B24_ID'] = $company['CODE'];
                }

                self::mirrorOsCompanyFieldsToLeganEntity($arProps);

                $elRow = \CIBlockElement::GetByID($companyId)->GetNext() ?: [];
                $elementName = $params['OS_COMPANY_NAME'] ?? $arProps['OS_COMPANY_NAME'] ?? ($elRow['NAME'] ?? '');
                $activeVal = $params['ACTIVE'] ?? ($elRow['ACTIVE'] ?? 'N');
                if ($elementName === '' || $elementName === null) {
                    $elementName = (string)($elRow['NAME'] ?? '');
                }

                $arUpdateArray = [
                    'PROPERTY_VALUES' => $arProps,
                    'NAME' => $elementName,
                    'ACTIVE' => $activeVal,
                ];

                $el = new \CIBlockElement;
                if ($el->Update($companyId, $arUpdateArray)) {
                    if ($activeVal === 'Y') {
                        self::activateCompanyStaffSiteUsers(self::normalizeCompanyUserIdsList($arProps['OS_COMPANY_USERS'] ?? []));
                    }

                    return $companyId;
                }

                return false;
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

            $b24NewId = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24NewId === '') {
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24NewId;

            if (isset($params['OS_COMPANY_USERS']) && !\is_array($params['OS_COMPANY_USERS'])) {
                $params['OS_COMPANY_USERS'] = [$params['OS_COMPANY_USERS']];
            }

            $el = new \CIBlockElement;
            
            // Обрабатываем пользователей
            if (!empty($params['OS_COMPANY_USERS']) && \is_array($params['OS_COMPANY_USERS'])) {
                foreach ($params['OS_COMPANY_USERS'] as $key => $b24_id) {
                    $user = new User();
                    $userId = $user->getUserIDByB24ID($b24_id);
                    
                    if ($userId) {
                        $params['OS_COMPANY_USERS'][$key] = $userId;

                        $discountMapped = null;
                        if (!empty($params['OS_COMPANY_DISCOUNT_VALUE'])
                            && self::shouldApplyCompanyDiscountGroupForUser((int)$userId, $params)
                        ) {
                            $statusId = (new UserGroups([]))->searchGroup($params['OS_COMPANY_DISCOUNT_VALUE'])['ID'] ?? null;
                            if ($statusId) {
                                $mapped = self::mapCompanyStatusGroupId($statusId);
                                $mappedInt = (int)$mapped;
                                if ($mappedInt > 0) {
                                    $discountMapped = $mappedInt;
                                }
                            }
                        }

                        self::applyB24CompanyGroupsToUser($user, (int)$userId, $params, $discountMapped);
                    }
                }
            }
            
            // Обрабатываем файл реквизитов
            if (!empty($params['OS_REQUSITES_FILE'])) {
                $fileId = $this->processRequisitesFile($params['OS_REQUSITES_FILE']);
                if ($fileId) {
                    $params['OS_REQUSITES_FILE'] = $fileId;
                }
            }
            
            // Обрабатываем связь с холдингом
            if (!empty($params['OS_HOLDING_OF'])) {
                $holdingRef = $params['OS_HOLDING_OF'];
                if (\is_array($holdingRef)) {
                    $params['OS_HOLDING_OF'] = !empty($holdingRef['ID']) ? (int)$holdingRef['ID'] : $holdingRef;
                } else {
                    $holdingCompany = $this->getCompanyByB24ID($holdingRef);
                    if (!empty($holdingCompany['ID'])) {
                        $params['OS_HOLDING_OF'] = (int)$holdingCompany['ID'];
                    }
                }
            }
            
            // Формируем массив свойств
            $arProps = [];
            foreach (self::$codeProps as $code) {
                if (isset($params[$code])) {
                    $arProps[$code] = $params[$code];
                }
            }

            self::mirrorOsCompanyFieldsToLeganEntity($arProps);
            
            $arFields = [
                'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                'IBLOCK_TYPE' => 'personal',
                'NAME' => $params['OS_COMPANY_NAME'] ?? 'Новая компания',
                'CODE' => $b24NewId,
                'ACTIVE' => $params['ACTIVE'] ?? 'N',
                'PROPERTY_VALUES' => $arProps
            ];
            
            $companyId = $el->Add($arFields);
            
            if ($companyId) {
                if (($arFields['ACTIVE'] ?? '') === 'Y') {
                    self::activateCompanyStaffSiteUsers(self::normalizeCompanyUserIdsList($arProps['OS_COMPANY_USERS'] ?? []));
                }

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
            if (empty($fileData) || !\is_array($fileData)) {
                return false;
            }

            $subdir = $fileData['SUBDIR'] ?? null;
            $fileNameInUrl = $fileData['FILE_NAME'] ?? null;
            if (!self::isSafeB24RequisiteUrlPart($subdir, $fileNameInUrl)) {
                return false;
            }
            $subdir = (string)$subdir;
            $fileNameInUrl = (string)$fileNameInUrl;

            $safeOriginal = self::sanitizeRequisitesOriginalFileName($fileData['ORIGINAL_NAME'] ?? null);
            if ($safeOriginal === false) {
                return false;
            }
            
            try {
                $downloadableUrl = URL_B24 . $subdir . '/' . urlencode($fileNameInUrl);
                
                // Куда сохранить
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/os_requisites/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filePath = $uploadDir . $safeOriginal;
                
                // Скачиваем файл
                $fileContent = file_get_contents($downloadableUrl);
                
                if ($fileContent === false) {
                    return false;
                }
                
                // Сохраняем на сервер
                if (file_put_contents($filePath, $fileContent)) {
                    // Загружаем файл в Битрикс
                    $fileArray = \CFile::MakeFileArray($filePath, false, $safeOriginal);
                    
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
            $b24_id = self::normalizeIncomingCompanyB24Id($params['ID'] ?? $params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24_id === '') {
                return true;
            }
            $company = $this->getCompanyByB24ID($b24_id);
            if (!$company || empty($company['ID'])) {
                // Идемпотентность: если карточки уже нет на сайте, не блокируем удаление в CRM.
                return true;
            }

            if (\CIBlockElement::Delete($company['ID'])) {
                return true;
            }

            return false;
        }

        public function getCompany($id){
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

            foreach (CompanyModuleConfig::ORDER_CUSTOM_FIELD_IDS as $id => $fieldName){
                $response[$id] = $company[$fieldName];
            }
            $response[12] = $user['NAME'].' '.$user['LAST_NAME'];

            return $response;
        }

        public function getCompanyByB24ID($b24_id){
            $b24_id = \trim((string)$b24_id);
            if ($b24_id === '') {
                return false;
            }

            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $rsCompany = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    '=CODE' => $b24_id,
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'NAME', 'PROPERTY_OS_COMPANY_B24_ID', 'CODE', 'XML_ID']
            );

            if (!($ob = $rsCompany->GetNextElement())) {
                $rsCompany = \CIBlockElement::GetList(
                    ['ID' => 'ASC'],
                    [
                        'IBLOCK_ID' => $iblockId,
                        'PROPERTY_OS_COMPANY_B24_ID' => $b24_id,
                    ],
                    false,
                    ['nTopCount' => 1],
                    ['ID', 'NAME', 'PROPERTY_OS_COMPANY_B24_ID', 'CODE', 'XML_ID']
                );
                $ob = $rsCompany->GetNextElement();
            }

            if (!$ob) {
                return false;
            }

            $arFields = $ob->GetFields();
            $arCompany = ['ID' => $arFields['ID']];

            foreach (self::$codeProps as $code) {
                $propertyValues = \CIBlockElement::GetProperty(
                    $iblockId,
                    $arFields['ID'],
                    [],
                    ['CODE' => $code]
                );

                $values = [];
                $isMultiple = false;
                while ($prop = $propertyValues->GetNext()) {
                    $values[] = $prop['VALUE'];
                    if ($prop['MULTIPLE'] === 'Y') {
                        $isMultiple = true;
                    }
                }

                if ($isMultiple) {
                    $arCompany[$code] = $values;
                } else {
                    $arCompany[$code] = \count($values) > 0 ? $values[0] : null;
                }
            }

            return $arCompany;
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

            // Обработка ошибок CURL
            if ($curlErrno) {
                return [
                    'success' => 0,
                    'error' => 'CURL Error: ' . $curlError,
                    'errno' => $curlErrno
                ];
            }

            // Обработка HTTP ошибок
            if ($httpCode !== 200) {
                return [
                    'success' => 0,
                    'error' => 'HTTP Error: ' . $httpCode,
                    'response' => $result
                ];
            }

            // Парсим JSON ответ
            $decodedResult = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => 0,
                    'error' => 'JSON Parse Error: ' . json_last_error_msg(),
                    'raw_response' => $result
                ];
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
                $isHeadOfHolding = $headCompany['OS_COMPANY_IS_HEAD_OF_HOLDING_XML_ID'] ?? $headCompany['OS_COMPANY_IS_HEAD_OF_HOLDING'] ?? '';
                if (!$headCompany || !in_array($isHeadOfHolding, ['Y', 'YES', '1', true])) {
                    return json_encode(['success' => false, 'error' => 'Компания не является головной. Значение: ' . $isHeadOfHolding]);
                }

                // Получаем всех руководителей головной компании
                $headCompanyManagers = $headCompany['OS_COMPANY_BOSS'] ?? [];
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
                    $childManagers = $childCompanyData['OS_COMPANY_BOSS'] ?? [];
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

            // Ищем все компании, у которых OS_HOLDING_OF указывает на головную компанию (по ID элемента)
            $rsCompanies = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    'PROPERTY_OS_HOLDING_OF' => $headCompanyId,
                    'ACTIVE' => 'Y'
                ],
                false,
                false,
                ['ID', 'NAME', 'CODE', 'PROPERTY_OS_HOLDING_OF']
            );

            $childCompanies = [];
            while ($ob = $rsCompanies->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $childCompanies[] = [
                    'ID' => $arFields['ID'],
                    'NAME' => $arFields['NAME'],
                    'CODE' => $arFields['CODE'],
                    'OS_HOLDING_OF' => $arProps['OS_HOLDING_OF']['VALUE'] ?? null
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

                // Обновляем свойство OS_COMPANY_BOSS
                \CIBlockElement::SetPropertyValues($companyId, CompanyModuleConfig::COMPANY_IBLOCK_ID, $managers, 'OS_COMPANY_BOSS');

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
         *   - OS_COMPANY_NAME (string) - название компании
         *   - OS_COMPANY_INN (string) - ИНН
         *   - OS_COMPANY_CITY (string) - город
         *   - OS_COMPANY_PHONE (string) - телефон
         *   - OS_COMPANY_EMAIL (string) - email
         *   - OS_COMPANY_WEB_SITE (string) - сайт
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
                'OS_COMPANY_NAME' => 'Название компании',
                'OS_COMPANY_INN' => 'ИНН',
                'OS_COMPANY_CITY' => 'Город',
                'OS_COMPANY_WEB_SITE' => 'Сайт'
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
            if (!empty($data['OS_COMPANY_EMAIL'])) {
                if (!filter_var($data['OS_COMPANY_EMAIL'], FILTER_VALIDATE_EMAIL)) {
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
            if ($deleteRequisites && !empty($company['OS_REQUSITES_FILE'])) {
                \CFile::Delete($company['OS_REQUSITES_FILE']);
                $data['OS_REQUSITES_FILE'] = '';
            } elseif ($fileId) {
                // Удаляем старый файл только если новый успешно загружен
                if (!empty($company['OS_REQUSITES_FILE'])) {
                    \CFile::Delete($company['OS_REQUSITES_FILE']);
                }
                $data['OS_REQUSITES_FILE'] = $fileId;
            }

            // Начинаем обновление
            $el = new \CIBlockElement();

            // Обновляем название элемента
            $arUpdateFields = [
                'NAME' => $data['OS_COMPANY_NAME']
            ];

            if (!$el->Update($companyId, $arUpdateFields)) {
                return [
                    'success' => false,
                    'message' => 'Ошибка обновления компании: ' . $el->LAST_ERROR
                ];
            }

            // Обновляем свойства
            $fieldsToUpdate = [
                'OS_COMPANY_NAME',
                'OS_COMPANY_INN',
                'OS_COMPANY_CITY',
                'OS_COMPANY_PHONE',
                'OS_COMPANY_EMAIL',
                'OS_COMPANY_WEB_SITE'
            ];

            foreach ($fieldsToUpdate as $field) {
                if (isset($data[$field])) {
                    \CIBlockElement::SetPropertyValueCode($companyId, $field, $data[$field]);
                }
            }

            // Обновляем файл реквизитов, если был изменен
            if (isset($data['OS_REQUSITES_FILE'])) {
                \CIBlockElement::SetPropertyValueCode($companyId, 'OS_REQUSITES_FILE', $data['OS_REQUSITES_FILE']);
            }

            // Получаем обновленные данные для ответа
            $rsElement = \CIBlockElement::GetByID($companyId);
            $companyCode = $companyId;
            if ($arElement = $rsElement->Fetch()) {
                $companyCode = $arElement['CODE'] ?? $companyId;
            }

            // Синхронизируем данные с Bitrix24
            $b24SyncSuccess = false;
            if (!empty($company['OS_COMPANY_B24_ID'])) {
                // Если файл не был изменен, но существует - добавляем его в данные для синхронизации
                if (!isset($data['OS_REQUSITES_FILE']) && !empty($company['OS_REQUSITES_FILE'])) {
                    $data['OS_REQUSITES_FILE'] = $company['OS_REQUSITES_FILE'];
                }
                
                $b24Result = $this->sendToBitrix24($company['OS_COMPANY_B24_ID'], $data);
                $b24SyncSuccess = !empty($b24Result);
            } 

            return [
                'success' => true,
                'message' => 'Данные компании успешно обновлены',
                'data' => [
                    'company_id' => $companyId,
                    'company_code' => $companyCode,
                    'b24_synced' => $b24SyncSuccess
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
            $bosses = $company['OS_COMPANY_BOSS'] ?? [];
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
         *   - OS_COMPANY_NAME (string) - название компании
         *   - OS_COMPANY_INN (string) - ИНН
         *   - OS_COMPANY_CITY (string) - город
         *   - OS_COMPANY_PHONE (string) - телефон
         *   - OS_COMPANY_EMAIL (string) - email
         *   - OS_COMPANY_WEB_SITE (string) - сайт
         *   - OS_REQUSITES_FILE (int) - ID файла реквизитов в Bitrix
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
            if (!empty($data['OS_COMPANY_NAME'])) {
                $b24Fields['TITLE'] = $data['OS_COMPANY_NAME'];
            }
            
            // ИНН (UF_CRM_1669208589 - пример, может отличаться)
            if (!empty($data['OS_COMPANY_INN'])) {
                $b24Fields[CompanyB24Config::COMPANY_INN_FIELD] = $data['OS_COMPANY_INN'];
            }
            
            // Город/Адрес
            if (!empty($data['OS_COMPANY_CITY'])) {
                $b24Fields[CompanyB24Config::COMPANY_CITY_FIELD] = $data['OS_COMPANY_CITY']; // Адрес
            }
            
            // Телефон
            if (!empty($data['OS_COMPANY_PHONE'])) {
                $b24Fields['PHONE'] = [
                    [
                        'VALUE' => $data['OS_COMPANY_PHONE'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }
            
            // Email
            if (!empty($data['OS_COMPANY_EMAIL'])) {
                $b24Fields['EMAIL'] = [
                    [
                        'VALUE' => $data['OS_COMPANY_EMAIL'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }
            
            // Сайт
            if (!empty($data['OS_COMPANY_WEB_SITE'])) {
                $b24Fields['WEB'] = [
                    [
                        'VALUE' => $data['OS_COMPANY_WEB_SITE'],
                        'VALUE_TYPE' => 'WORK'
                    ]
                ];
            }

            // Файл реквизитов (как в RegisterUserCompany.php)
            if (!empty($data['OS_REQUSITES_FILE'])) {
                $fileId = $data['OS_REQUSITES_FILE'];
                
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
                            $b24Fields[CompanyB24Config::REQUISITES_FILE_FIELD] = [
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
                $result = self::callB24Method('crm.company.update', [
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
            
            $existingRequisite = self::callB24Method('crm.requisite.list', $dataRequisite, false);
            
            if (!empty($existingRequisite)) {
                return [
                    'success' => false,
                    'message' => 'Компания с указанным ИНН уже существует в системе'
                ];
            }

            // Получаем B24 ID головной компании из поля OS_HEAD_COMPANY_B24_ID
            $headCompanyB24Id = $headCompany['OS_HEAD_COMPANY_B24_ID'] ?? '';
            
            // Если поле пустое - это критическая ошибка синхронизации
            if (empty($headCompanyB24Id)) {
                error_log('ERROR: OS_HEAD_COMPANY_B24_ID головной компании пустое! Head company ID: ' . $headCompanyId);
                return [
                    'success' => false,
                    'message' => 'Ошибка синхронизации с Bitrix24. Головная компания не имеет связи с CRM системой. Пожалуйста, обратитесь к персональному менеджеру для исправления данной ошибки.'
                ];
            }
            
            // Логируем успешное получение
            error_log('INFO: B24 ID головной компании для ' . CompanyB24Config::HEAD_COMPANY_B24_LINK_FIELD . ': ' . $headCompanyB24Id);
            
            // Создаем компанию в Bitrix24
            $b24CompanyFields = [
                'TITLE' => $data['UF_NAME_COMPANY'],
                'WEB' => [[
                    'VALUE' => $data['UF_SITE'] ?? '',
                    'VALUE_TYPE' => 'WORK'
                ]],
                CompanyB24Config::BRANCH_CITY_FIELD => $data['UF_CITY'] ?? '',
                CompanyB24Config::HEAD_COMPANY_B24_LINK_FIELD => $headCompanyB24Id, // ID головной компании в B24
                'COMPANY_TYPE' => 'CUSTOMER',
                'ASSIGNED_BY_ID' => CompanyB24Config::ASSIGNED_BY_ID,
            ];

            // Логируем данные отправки в B24 для отладки
            error_log('Creating branch company in B24. Parent B24 ID: ' . $headCompanyB24Id);

            // Добавляем файл реквизитов если есть
            if ($fileDataB24) {
                $b24CompanyFields[CompanyB24Config::REQUISITES_FILE_FIELD] = $fileDataB24;
            }

            // Создаем компанию в B24
            $companyB24Id = self::callB24Method('crm.company.add', ['fields' => $b24CompanyFields]);
            
            if (empty($companyB24Id)) {
                return [
                    'success' => false,
                    'message' => 'Ошибка создания компании в Bitrix24'
                ];
            }

            // Получаем данные созданной компании из B24
            $dataCompany = self::callB24Method('crm.company.get', ['id' => $companyB24Id]);

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
                self::callB24Method('crm.contact.company.add', $qrCompanyAddContact);
                
                error_log('INFO: Контакт руководителя привязан к новой компании. Contact ID: ' . $contactId . ', Company ID: ' . $dataCompany['ID']);
            } else {
                error_log('WARNING: У пользователя нет UF_B24_USER_ID, контакт не привязан к компании');
            }

            // Добавляем реквизит к компании в B24
            $requisiteId = self::callB24Method('crm.requisite.add', [
                'fields' => [
                    'ENTITY_ID' => $dataCompany['ID'],
                    'ENTITY_TYPE_ID' => '4',
                    'NAME' => 'Реквизит с формы сайта',
                    'PRESET_ID' => 1
                ]
            ]);

            // Обновляем реквизиты компании
            if ($requisiteId) {
                self::callB24Method('crm.requisite.update', [
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
                'OS_COMPANY_INN' => $data['UF_INN'],
                'OS_COMPANY_WEB_SITE' => $data['UF_SITE'] ?? '',
                'OS_COMPANY_NAME' => $data['UF_NAME_COMPANY'],
                'OS_COMPANY_B24_ID' => $dataCompany['ID'],
                'OS_COMPANY_CITY' => $data['UF_CITY'] ?? '',
                'OS_REQUSITES_FILE' => $fileDataB24 ?? ''
            ];

            $newCompanyId = $this->createCompanyElement($companyElementParams);
            
            if (!$newCompanyId) {
                return [
                    'success' => false,
                    'message' => 'Ошибка создания компании на сайте'
                ];
            }

            // Синхронизируем руководителей головной компании с дочерней
            $headCompanyManagers = $headCompany['OS_COMPANY_BOSS'] ?? [];
            if (!is_array($headCompanyManagers)) {
                $headCompanyManagers = $headCompanyManagers ? [$headCompanyManagers] : [];
            }

            // Применяем руководителей к дочерней компании
            if (!empty($headCompanyManagers)) {
                \CIBlockElement::SetPropertyValues(
                    $newCompanyId, 
                    CompanyModuleConfig::COMPANY_IBLOCK_ID, 
                    $headCompanyManagers, 
                    'OS_COMPANY_BOSS'
                );
            }

            // Устанавливаем связь с головной компанией (ID элемента инфоблока)
            \CIBlockElement::SetPropertyValueCode($newCompanyId, 'OS_HOLDING_OF', $headCompanyId);

            // Устанавливаем B24 ID головной компании (значение уже проверено выше)
            \CIBlockElement::SetPropertyValueCode($newCompanyId, 'OS_HEAD_COMPANY_B24_ID', $headCompanyB24Id);
            error_log('INFO: Установлено OS_HEAD_COMPANY_B24_ID для дочерней компании ID=' . $newCompanyId . ': ' . $headCompanyB24Id);

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
            $bosses = $headCompany['OS_COMPANY_BOSS'] ?? [];
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