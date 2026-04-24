<?php
    namespace OnlineService\Site;

    use OnlineService\B24\RestClient;
    use OnlineService\B24\User;
    use OnlineService\Site\Config\CompanyB24Config;
    use OnlineService\Site\Config\CompanyModuleConfig;
    use OnlineService\Sync\FromCrm\CrmInboundUfMap;
    use OnlineService\Sync\SyncTrace;

    class Company{
        private static $codeProps = [
            "OS_COMPANY_IS_HEAD_OF_HOLDING",
            "OS_COMPANY_BOSS",
            /** Витрина/«зеркало»; для ACL нельзя опираться только на OS_ — бывает пусто при заполненном LEGAN. */
            "LEGAN_ENTITY_BOSS",
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
            // Витринные поля (ИБ 23) — шаблоны /company/profile/ и т.д.; при пустом LEGAN ниже дозаполняем из OS_*
            'LEGAN_ENTITY_NAME',
            'LEGAN_ENTITY_INN',
            'LEGAN_ENTITY_CITY',
            'LEGAN_ENTITY_WWW',
            'LEGAN_ENTITY_PHONE',
            'LEGAN_ENTITY_EMAIL',
            'LEGAN_ENTITY_FILE',
            'LEGAN_MAIN_PHONE',
            'LEGAN_MOBILE_PHONE',
        ];

        /**
         * Дублирование «служебных» свойств OS_* в пользовательские LEGAN_ENTITY_* (ИБ 23 / витрина).
         * Значения берутся уже после слияния с текущими свойствами элемента.
         *
         * @param array<string, mixed> $props
         */
        /**
         * Трассировка входящего sync при sync_debug (класс подключается из local/sync/bootstrap.php).
         *
         * @param array<string, mixed> $context
         */
        private static function syncTrace(string $step, array $context = []): void
        {
            if (!\class_exists(SyncTrace::class, false)) {
                return;
            }
            SyncTrace::add($step, $context);
        }

        /**
         * Жёсткая отладочная остановка (только если подключён local/sync/bootstrap и включён флаг в конфиге).
         *
         * @param array<string, mixed> $payload
         */
        private static function syncPrimitiveBreakpoint(string $stepId, array $payload = []): void
        {
            if (!\class_exists(\OnlineService\Sync\SyncPrimitiveBreakpoint::class, false)) {
                return;
            }
            \OnlineService\Sync\SyncPrimitiveBreakpoint::hit($stepId, $payload);
        }

        /**
         * Длины ИНН для лога без утечки значения.
         *
         * @param array<string, mixed> $bag
         * @return array<string, int>
         */
        private static function syncInnFieldLengths(array $bag): array
        {
            $out = [];
            foreach (['OS_COMPANY_INN' => 'os_inn', 'LEGAN_ENTITY_INN' => 'legan_inn'] as $code => $label) {
                $v = $bag[$code] ?? null;
                if ($v === null || $v === '') {
                    $out[$label] = 0;
                } elseif (\is_string($v)) {
                    $out[$label] = \strlen($v);
                } else {
                    $out[$label] = 1;
                }
            }

            return $out;
        }

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
         * Чтение с ИБ: если в CRM/импорте заполнен только OS_*, в форме (LEGAN_*) тогда пусто — копируем.
         * Карта согласована с {@see self::mirrorOsCompanyFieldsToLeganEntity()}.
         *
         * @param array<string, mixed> $arCompany
         */
        private static function enrichLeganFromOsOnRead(array &$arCompany): void
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
                if (self::isCompanyFieldEmptyForRead($arCompany[$legan] ?? null, $legan, true)) {
                    $osV = $arCompany[$os] ?? null;
                    if (!self::isCompanyFieldEmptyForRead($osV, $os, false)) {
                        $arCompany[$legan] = $osV;
                    }
                }
            }
        }

        private static function isCompanyFieldEmptyForRead(mixed $v, string $code, bool $isLeganSide): bool
        {
            if ($v === null || $v === false) {
                return true;
            }
            if ($v === '') {
                return true;
            }
            if (\is_array($v) && $v === []) {
                return true;
            }
            if ($code === 'LEGAN_ENTITY_FILE' && $isLeganSide) {
                return (int) $v === 0;
            }
            if ($code === 'OS_REQUSITES_FILE' && ! $isLeganSide) {
                return (int) $v === 0;
            }

            return false;
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
                $u = self::unwrapCrmScalarForGroupId($raw);
                if ($u === null || $u === false || $u === '' || !\is_scalar($u)) {
                    return [];
                }
                $one = (int)(string)$u;

                return $one > 0 ? [$one] : [];
            }
            $out = [];
            foreach ($raw as $v) {
                if (\is_array($v) || \is_object($v)) {
                    $u = self::unwrapCrmScalarForGroupId($v);
                    if ($u === null || $u === false || $u === '' || !\is_scalar($u)) {
                        continue;
                    }
                    $iv = (int)(string)$u;
                } else {
                    $iv = (int)(string)$v;
                }
                if ($iv > 0) {
                    $out[] = $iv;
                }
            }

            return $out;
        }

        /**
         * Скаляры из UF CRM (в т.ч. ['VALUE'=>…], вложенные списки) → положительные int (ID пользователей сайта).
         *
         * @return list<int>
         */
        private static function normalizeCrmSiteUserIdsUfValue(mixed $raw): array
        {
            $set = [];
            self::collectPositiveIntsFromCrmUfTree($raw, $set, 0);

            return \array_map('intval', \array_keys($set));
        }

        /**
         * @param array<int, true> $set
         */
        private static function collectPositiveIntsFromCrmUfTree(mixed $raw, array &$set, int $depth): void
        {
            if ($depth > 12) {
                return;
            }
            if ($raw === null || $raw === '' || $raw === false) {
                return;
            }
            if (\is_int($raw) || \is_float($raw)) {
                $id = (int)$raw;
                if ($id > 0) {
                    $set[$id] = true;
                }

                return;
            }
            if (\is_string($raw)) {
                if (\trim($raw) === '') {
                    return;
                }
                $id = (int)$raw;
                if ($id > 0) {
                    $set[$id] = true;
                }

                return;
            }
            if (!\is_array($raw)) {
                return;
            }
            if (\array_key_exists('VALUE', $raw)) {
                self::collectPositiveIntsFromCrmUfTree($raw['VALUE'], $set, $depth + 1);

                return;
            }
            foreach ($raw as $v) {
                self::collectPositiveIntsFromCrmUfTree($v, $set, $depth + 1);
            }
        }

        /**
         * Дополняет LEGAN_ENTITY_USERS значениями UF_CRM_* с ID пользователей сайта из CRM (UPDATE_COMPANY).
         */
        private static function mergeLeganEntityUsersFromCrmSiteUserUfPayload(array &$arProps, array $params): void
        {
            $ufKey = CrmInboundUfMap::COMPANY_SITE_USER_IDS_UF;
            if (!\array_key_exists($ufKey, $params)) {
                return;
            }
            $fromUf = self::normalizeCrmSiteUserIdsUfValue($params[$ufKey]);
            if ($fromUf === []) {
                return;
            }
            $set = [];
            foreach (self::normalizeCompanyUserIdsList($arProps['LEGAN_ENTITY_USERS'] ?? []) as $id) {
                $set[$id] = true;
            }
            foreach ($fromUf as $id) {
                $set[$id] = true;
            }
            $arProps['LEGAN_ENTITY_USERS'] = \array_map('intval', \array_keys($set));
        }

        /**
         * @return list<int>
         */
        private static function siteUserIdsForCompanyActivation(array $arProps): array
        {
            $set = [];
            foreach (self::normalizeCompanyUserIdsList($arProps['OS_COMPANY_USERS'] ?? []) as $id) {
                $set[$id] = true;
            }
            foreach (self::normalizeCompanyUserIdsList($arProps['LEGAN_ENTITY_USERS'] ?? []) as $id) {
                $set[$id] = true;
            }

            return \array_map('intval', \array_keys($set));
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

        /** Публичный путь вида /upload/... на портале CRM (без «..»). */
        private static function isSafeCrmPublicUploadSrc(mixed $src): bool
        {
            if (!\is_string($src) || $src === '') {
                return false;
            }
            if (!\str_starts_with($src, '/upload/')) {
                return false;
            }
            if (\str_contains($src, '..')) {
                return false;
            }

            return true;
        }

        /**
         * В payload из CRM можно скачать файл реквизитов с {@see URL_B24} (не подставлять чужой b_file.ID на сайт).
         */
        private static function isOsRequisitesFileCrmDownloadPayload(array $fileData): bool
        {
            $src = isset($fileData['SRC']) && \is_string($fileData['SRC']) ? \trim($fileData['SRC']) : '';
            if (self::isSafeCrmPublicUploadSrc($src)) {
                return true;
            }

            return self::isSafeB24RequisiteUrlPart($fileData['SUBDIR'] ?? null, $fileData['FILE_NAME'] ?? null);
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
         * CRM/POST часто отдаёт UF как массив / ['VALUE' => …]; (int)array в PHP 8 даёт TypeError.
         *
         * @return mixed
         */
        private static function unwrapCrmScalarForGroupId(mixed $raw)
        {
            $v = $raw;
            for ($i = 0; $i < 8 && \is_array($v); $i++) {
                if (\array_key_exists('VALUE', $v)) {
                    $v = $v['VALUE'];
                    continue;
                }
                $first = \reset($v);
                $v = $first === false ? null : $first;
            }
            if ($v === null || $v === '' || $v === false) {
                return null;
            }
            if (!\is_scalar($v)) {
                return null;
            }

            return $v;
        }

        /**
         * Inbound `UPDATE_COMPANY` с B24: списки ИБ ждут ID/скаляр; из CRM нередко приходит `['VALUE' => id]`
         * (как в REST), иначе {@see \CIBlockElement::Update} может пасть или оставлять внутри обёртку.
         *
         * @param array<string, mixed> $arProps
         */
        private static function normalizeInboundCrmListPropertyValuesForIblock(array &$arProps): void
        {
            $codes = [
                'OS_COMPANY_IS_HEAD_OF_HOLDING',
                'OS_IS_MARKETING_AGENT',
                'OS_IS_COMPANY_DISABLED',
                'OS_COMPANY_DISCOUNT_VALUE',
                'LEGAN_ENTITY_IS_HEAD_COMPANY',
            ];
            foreach ($codes as $code) {
                if (!\array_key_exists($code, $arProps)) {
                    continue;
                }
                $raw = $arProps[$code];
                if (!\is_array($raw)) {
                    continue;
                }
                if ($raw === []) {
                    continue;
                }
                if (self::isFileLikePropertyValueArray($raw) || self::isOsRequisitesFileCrmDownloadPayload($raw)) {
                    continue;
                }
                $u = self::unwrapCrmScalarForGroupId($raw);
                if ($u === null || $u === false || $u === '' || !\is_scalar($u)) {
                    continue;
                }
                if (\is_string($u) && \trim($u) === '') {
                    continue;
                }
                $arProps[$code] = $u;
            }
        }

        /**
         * Отличие от «значения списка с CRM»: вложенный b_file-описатель с SUBDIR, MODULE_ID, …
         */
        private static function isFileLikePropertyValueArray(array $raw): bool
        {
            if (\array_key_exists('ID', $raw) && \array_key_exists('SRC', $raw)) {
                return true;
            }
            if (\array_key_exists('name', $raw) && \array_key_exists('size', $raw)) {
                return true;
            }

            return false;
        }

        /**
         * ID контакта B24 из payload (скаляр / ['VALUE'=>…]); иначе (int)массива в PHP 8 — TypeError.
         */
        private static function normalizeIncomingContactB24Id(mixed $raw): int
        {
            $v = self::unwrapCrmScalarForGroupId($raw);
            if ($v === null || $v === '' || $v === false) {
                return 0;
            }
            if (!\is_scalar($v)) {
                return 0;
            }
            $i = (int)(string)$v;

            return $i > 0 ? $i : 0;
        }

        /**
         * @param int|string|null $groupId ID группы после разрешения через searchGroup
         * @return int|string|null
         */
        private static function mapCompanyStatusGroupId($groupId)
        {
            $groupId = self::unwrapCrmScalarForGroupId($groupId);
            if ($groupId === null || $groupId === '' || $groupId === false) {
                return $groupId;
            }
            $id = (int)(string)$groupId;

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
            // POST/CRM может отдать UF как скаляр, как ['VALUE'=>…] или ключ может отсутствовать — без проверки PHP 8 даёт TypeError.
            if (!empty(self::unwrapCrmScalarForGroupId($params['OS_IS_MARKETING_AGENT'] ?? null))) {
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
            if ($v === true) {
                return true;
            }
            if ($v === 1 || $v === '1') {
                return true;
            }
            if (\is_string($v)) {
                $s = \strtoupper(\trim($v));
                if (\in_array($s, ['N', 'NO', 'FALSE', '0', 'НЕТ'], true)) {
                    return false;
                }
                if (\in_array($s, ['Y', 'YES', 'TRUE', '1', 'ДА', '31520'], true)) {
                    return true;
                }
            }
            if (!\is_scalar($v)) {
                return false;
            }
            $i = (int)(string)$v;
            if ($i === 0) {
                return false;
            }
            if (\in_array($i, CompanyModuleConfig::getHeadOfHoldingCrmListYesValueIds(), true)) {
                return true;
            }

            return false;
        }

        /**
         * B24-контакт, «альтернативный» контакт из CONTACT_IDS по индексу, все CONTACT_IDS, либо уже b_user.ID.
         *
         * @param array<int|string, mixed> $contactIdsByIndex
         */
        private static function resolveSiteUserIdForUpdateCompany(User $user, mixed $contactB24Raw, $key, array $contactIdsByIndex): int
        {
            $b24 = self::normalizeIncomingContactB24Id($contactB24Raw);
            if ($b24 <= 0) {
                return 0;
            }
            $byB24 = $user->getUserIDByB24ID($b24) ?: 0;
            if ($byB24 > 0) {
                return (int) $byB24;
            }
            foreach ([$key, 0] as $k) {
                if (!\array_key_exists($k, $contactIdsByIndex)) {
                    continue;
                }
                $alt = self::normalizeIncomingContactB24Id($contactIdsByIndex[$k]);
                if ($alt <= 0) {
                    continue;
                }
                if ($alt === $b24) {
                    continue;
                }
                $u2 = $user->getUserIDByB24ID($alt) ?: 0;
                if ($u2 > 0) {
                    return (int) $u2;
                }
            }
            $seenC = [];
            foreach ($contactIdsByIndex as $cRaw) {
                $c = self::normalizeIncomingContactB24Id($cRaw);
                if ($c <= 0 || $c === $b24) {
                    continue;
                }
                if (isset($seenC[$c])) {
                    continue;
                }
                $seenC[$c] = true;
                $u2 = $user->getUserIDByB24ID($c) ?: 0;
                if ($u2 > 0) {
                    return (int) $u2;
                }
            }
            $uRow = \CUser::GetByID($b24)->Fetch();
            if (\is_array($uRow) && (int) ($uRow['ID'] ?? 0) === $b24) {
                return $b24;
            }

            return 0;
        }

        /**
         * Целевая группа скидки из payload (как в createCompanyFromUpdate: map + searchGroup).
         * Без ключа `OS_COMPANY_DISCOUNT_VALUE` в payload — null (см. {@see applyB24CompanyGroupsToUser}).
         *
         * @param array<string, mixed> $params
         */
        private static function resolveUpdatedCompanyDiscountTargetGroupId(array $params): ?int
        {
            if (!\array_key_exists('OS_COMPANY_DISCOUNT_VALUE', $params)) {
                return null;
            }
            $raw = $params['OS_COMPANY_DISCOUNT_VALUE'];
            if ($raw === null || $raw === '' || $raw === false) {
                return null;
            }
            $u = self::unwrapCrmScalarForGroupId($raw);
            if ($u === null || $u === '' || $u === false) {
                return null;
            }
            $candidates = (int) (string) $u;
            if ($candidates <= 0) {
                return null;
            }
            $allowed = self::getCompanyDiscountAssignedGroupIds();
            $fromMap = (int) (string) self::mapCompanyStatusGroupId($candidates);
            if ($fromMap > 0 && \in_array($fromMap, $allowed, true)) {
                return $fromMap;
            }
            $statusRow = (new UserGroups([]))->searchGroup($candidates);
            if (\is_array($statusRow) && !empty($statusRow['ID'])) {
                $g = (int) (string) $statusRow['ID'];
                if ($g > 0) {
                    $m2 = (int) (string) self::mapCompanyStatusGroupId($g);
                    if ($m2 > 0 && \in_array($m2, $allowed, true)) {
                        return $m2;
                    }
                    if (\in_array($g, $allowed, true)) {
                        return $g;
                    }
                }
            }
            if ($fromMap > 0) {
                return $fromMap;
            }

            return null;
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

            $this->resolveOsRequisitesFileParamForUpdate($params);

            // Ищем существующую компанию по OS_COMPANY_B24_ID
            $existingCompany = $this->getCompanyByB24ID($b24CompanyId);
            
            if ($existingCompany && !empty($existingCompany['ID'])) {
                $companyId = (int) $existingCompany['ID'];
                if (!\array_key_exists('USER_ID', $params) || (int) $params['USER_ID'] <= 1) {
                    return $companyId;
                }
                $addUserId = (int) $params['USER_ID'];
                if ($addUserId <= 1) {
                    return $companyId;
                }
                $currentUsers = $existingCompany['OS_COMPANY_USERS'] ?? [];

                if (\is_array($currentUsers)) {
                    if (!\in_array($addUserId, $currentUsers, true) && !\in_array((string) $addUserId, $currentUsers, true)) {
                        $currentUsers[] = $addUserId;
                    }
                } else {
                    $one = (int) $currentUsers;
                    $currentUsers = $one > 0 ? [$one, $addUserId] : [$addUserId];
                }

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
            }
            if (!\array_key_exists('USER_ID', $params) || (int) $params['USER_ID'] <= 1) {
                self::syncTrace('Company::createCompanyElement refuse new element without real site user', [
                    'b24' => (string) $b24CompanyId,
                ]);

                return false;
            } else {
                $regUserId = (int) $params['USER_ID'];
                $el = new \CIBlockElement;

                $params['OS_COMPANY_USERS'] = [$regUserId];

                $propBag = $params;
                $this->hydrateOsRequisitesFileInPropertyBag($propBag);
                self::mirrorOsCompanyFieldsToLeganEntity($propBag);
                self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($propBag, $params);

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
         * Скаляр из входящего UF CRM (строка/число или оболочка {@see self::unwrapCrmScalarForGroupId()} — ['VALUE' => ...], первый элемент мультителефона).
         */
        private static function extractCrmInboundScalarString(mixed $raw): ?string
        {
            if ($raw === null) {
                return null;
            }
            if (\is_string($raw)) {
                $t = \trim($raw);

                return $t === '' ? null : $t;
            }
            if (\is_int($raw) || \is_float($raw)) {
                $t = \trim((string) $raw);

                return $t === '' ? null : $t;
            }
            if (\is_object($raw)) {
                return null;
            }
            if (!\is_array($raw)) {
                return null;
            }
            if (\array_key_exists('VALUE', $raw)) {
                return self::extractCrmInboundScalarString($raw['VALUE']);
            }
            if ($raw !== [] && \array_key_exists(0, $raw) && \is_array($raw[0])) {
                return self::extractCrmInboundScalarString($raw[0]);
            }

            return null;
        }

        /**
         * Явная запись телефонов LEGAN: тот же приём, что в {@see Company::updateCompanyProfile} через SetPropertyValueCode
         * (массовый CIBlockElement::Update(PROPERTY_VALUES) на части стендов не пишет отдельные string-свойства).
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyLeganPhonePropertyValuesToElement(int $elementId, array $arProps): void
        {
            foreach (['LEGAN_MAIN_PHONE', 'LEGAN_MOBILE_PHONE'] as $code) {
                if (!\array_key_exists($code, $arProps)) {
                    continue;
                }
                $v = $arProps[$code];
                if ($v === null) {
                    continue;
                }
                if (\is_string($v)) {
                    $v = \trim($v);
                }
                \CIBlockElement::SetPropertyValueCode($elementId, $code, $v);
            }
        }

        /**
         * Inbound `UPDATE_COMPANY` (Bitrix24): `UF_CRM_*` в карточке crm.company → поля элемента ИБ 23.
         * `EMAIL` из payload (как в REST) → `OS_COMPANY_EMAIL` + `LEGAN_ENTITY_EMAIL`.
         *
         * @param array<string, mixed> $params
         */
        private static function mapCrmCompanyPayloadUfToSiteProperties(array &$params): void
        {
            $m = [
                CrmInboundUfMap::COMPANY_CRM_MAIN_PHONE_UF => 'LEGAN_MAIN_PHONE',
                CrmInboundUfMap::COMPANY_CRM_MOBILE_PHONE_UF => 'LEGAN_MOBILE_PHONE',
            ];
            foreach ($m as $ufK => $siteK) {
                if (!\array_key_exists($ufK, $params)) {
                    continue;
                }
                $raw = $params[$ufK];
                unset($params[$ufK]);
                $str = self::extractCrmInboundScalarString($raw);
                if ($str !== null) {
                    $params[$siteK] = $str;
                }
            }

            if (!\array_key_exists('EMAIL', $params)) {
                return;
            }
            $em = \trim((string)($params['EMAIL'] ?? ''));
            unset($params['EMAIL']);
            if ($em === '') {
                return;
            }
            $params['OS_COMPANY_EMAIL'] = $em;
            $params['LEGAN_ENTITY_EMAIL'] = $em;
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
            if (!\is_array($params)) {
                return false;
            }

            if (!empty($params['OS_COMPANY_INN']) && empty($params['LEGAN_ENTITY_INN'])) {
                $params['LEGAN_ENTITY_INN'] = (string)$params['OS_COMPANY_INN'];
            }

            self::mapCrmCompanyPayloadUfToSiteProperties($params);

            self::syncTrace('Company::updateCompanyElement enter', [
                'inn_params' => self::syncInnFieldLengths($params),
            ]);

            $b24_id = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24_id === '') {
                self::syncTrace('Company::updateCompanyElement reject empty OS_COMPANY_B24_ID', []);
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24_id;

            if (isset($params['OS_COMPANY_USERS']) && !\is_array($params['OS_COMPANY_USERS'])) {
                $params['OS_COMPANY_USERS'] = [$params['OS_COMPANY_USERS']];
            }
            if (isset($params['LEGAN_ENTITY_USERS']) && !\is_array($params['LEGAN_ENTITY_USERS'])) {
                $params['LEGAN_ENTITY_USERS'] = [$params['LEGAN_ENTITY_USERS']];
            }

            $contactIdsMap = self::contactIdsMapFromCompanyParams($params);

            // Находим компанию по B24_ID
            $company = $this->getCompanyByB24ID($b24_id);

            self::syncPrimitiveBreakpoint('sync_bp_company_update_entry', [
                'b24_id' => $b24_id,
                'found_element_id' => !empty($company['ID']) ? (int)$company['ID'] : null,
            ]);

            if ($company && !empty($company['ID'])) {
                // Компания найдена - обновляем
                $companyId = $company['ID'];
                self::syncTrace('Company::updateCompanyElement company found', [
                    'element_id' => (int)$companyId,
                    'element_code' => (string)($company['CODE'] ?? ''),
                ]);
                
                $discountBase = self::resolveUpdatedCompanyDiscountTargetGroupId($params);
                $memberRaws = [];
                if (!empty($params['OS_COMPANY_USERS']) && \is_array($params['OS_COMPANY_USERS'])) {
                    foreach ($params['OS_COMPANY_USERS'] as $key => $raw) {
                        $memberRaws[] = ['key' => $key, 'raw' => $raw, 'target' => 'os'];
                    }
                }
                if (!empty($params['LEGAN_ENTITY_USERS']) && \is_array($params['LEGAN_ENTITY_USERS'])) {
                    foreach ($params['LEGAN_ENTITY_USERS'] as $k => $raw) {
                        $memberRaws[] = ['key' => $k, 'raw' => $raw, 'target' => 'legan'];
                    }
                }
                if ($memberRaws !== []) {
                    $user = new User();
                    $seenUserIds = [];
                    foreach ($memberRaws as $m) {
                        $userId = self::resolveSiteUserIdForUpdateCompany($user, $m['raw'], $m['key'], $contactIdsMap);
                        if ($userId <= 0) {
                            continue;
                        }
                        if ($m['target'] === 'os') {
                            $params['OS_COMPANY_USERS'][$m['key']] = $userId;
                        } else {
                            $params['LEGAN_ENTITY_USERS'][$m['key']] = $userId;
                        }
                        if (isset($seenUserIds[$userId])) {
                            continue;
                        }
                        $seenUserIds[$userId] = true;
                        $discountMapped = null;
                        if ($discountBase !== null
                            && $discountBase > 0
                            && self::shouldApplyCompanyDiscountGroupForUser($userId, $params)
                        ) {
                            $discountMapped = $discountBase;
                        }
                        self::applyB24CompanyGroupsToUser($user, $userId, $params, $discountMapped);
                    }
                }

                $this->resolveOsRequisitesFileParamForUpdate($params);

                if (!empty($params['OS_HOLDING_OF'])) {
                    $holdingRef = $params['OS_HOLDING_OF'];
                    if (\is_array($holdingRef)) {
                        if (!empty($holdingRef['ID']) && \is_scalar($holdingRef['ID'])) {
                            $params['OS_HOLDING_OF'] = (int)$holdingRef['ID'];
                        } else {
                            $scalar = self::unwrapCrmScalarForGroupId($holdingRef);
                            if ($scalar !== null && $scalar !== '' && \is_scalar($scalar)) {
                                $holdingCompany = $this->getCompanyByB24ID(\trim((string)$scalar));
                                if (!empty($holdingCompany['ID'])) {
                                    $params['OS_HOLDING_OF'] = (int)$holdingCompany['ID'];
                                } else {
                                    unset($params['OS_HOLDING_OF']);
                                }
                            } else {
                                unset($params['OS_HOLDING_OF']);
                            }
                        }
                    } else {
                        $holdingCompany = $this->getCompanyByB24ID(\trim((string)$holdingRef));
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

                $this->hydrateOsRequisitesFileInPropertyBag($arProps);
                self::mirrorOsCompanyFieldsToLeganEntity($arProps);
                self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($arProps, $params);
                self::normalizeInboundCrmListPropertyValuesForIblock($arProps);
                self::syncTrace('Company::updateCompanyElement merged PROPERTY_VALUES', [
                    'inn_arProps' => self::syncInnFieldLengths($arProps),
                ]);

                self::syncPrimitiveBreakpoint('sync_bp_company_after_merge_property_values', [
                    'element_id' => (int)$companyId,
                    'prop_keys' => \array_keys($arProps),
                    'inn_arProps' => self::syncInnFieldLengths($arProps),
                ]);

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

                self::syncPrimitiveBreakpoint('sync_bp_company_before_ciupdate', [
                    'element_id' => (int)$companyId,
                    'ACTIVE' => $activeVal,
                    'NAME_preview' => \is_string($elementName)
                        ? (\strlen($elementName) > 160 ? 'string(len=' . (string)\strlen($elementName) . ')' : $elementName)
                        : (string)$elementName,
                    'property_codes' => \array_keys($arProps),
                ]);

                $el = new \CIBlockElement;
                if ($el->Update($companyId, $arUpdateArray)) {
                    self::applyLeganPhonePropertyValuesToElement((int) $companyId, $arProps);
                    self::syncTrace('Company::updateCompanyElement CIBlockElement::Update ok', [
                        'element_id' => (int)$companyId,
                    ]);
                    if ($activeVal === 'Y') {
                        self::activateCompanyStaffSiteUsers(self::siteUserIdsForCompanyActivation($arProps));
                    }

                    return $companyId;
                }

                self::syncTrace('Company::updateCompanyElement CIBlockElement::Update failed', [
                    'element_id' => (int)$companyId,
                    'last_error' => (string)($el->LAST_ERROR ?? ''),
                ]);

                return false;
            } else {
                // Компания не найдена - создаем новую
                self::syncTrace('Company::updateCompanyElement company not found, create', [
                    'b24_id' => $b24_id,
                ]);
                $companyId = $this->createCompanyFromUpdate($params);
                
                if (!$companyId) {
                    self::syncTrace('Company::updateCompanyElement createCompanyFromUpdate failed', []);
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
                self::syncTrace('Company::createCompanyFromUpdate iblock not loaded', []);
                return false;
            }

            $b24NewId = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24NewId === '') {
                self::syncTrace('Company::createCompanyFromUpdate empty b24 id', []);
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24NewId;

            if (isset($params['OS_COMPANY_USERS']) && !\is_array($params['OS_COMPANY_USERS'])) {
                $params['OS_COMPANY_USERS'] = [$params['OS_COMPANY_USERS']];
            }
            if (isset($params['LEGAN_ENTITY_USERS']) && !\is_array($params['LEGAN_ENTITY_USERS'])) {
                $params['LEGAN_ENTITY_USERS'] = [$params['LEGAN_ENTITY_USERS']];
            }

            $el = new \CIBlockElement;
            $contactIdsMap = self::contactIdsMapFromCompanyParams($params);
            $discountBase = self::resolveUpdatedCompanyDiscountTargetGroupId($params);
            $memberRaws = [];
            if (!empty($params['OS_COMPANY_USERS']) && \is_array($params['OS_COMPANY_USERS'])) {
                foreach ($params['OS_COMPANY_USERS'] as $key => $raw) {
                    $memberRaws[] = ['key' => $key, 'raw' => $raw, 'target' => 'os'];
                }
            }
            if (!empty($params['LEGAN_ENTITY_USERS']) && \is_array($params['LEGAN_ENTITY_USERS'])) {
                foreach ($params['LEGAN_ENTITY_USERS'] as $k => $raw) {
                    $memberRaws[] = ['key' => $k, 'raw' => $raw, 'target' => 'legan'];
                }
            }
            if ($memberRaws !== []) {
                $user = new User();
                $seenUserIds = [];
                foreach ($memberRaws as $m) {
                    $userId = self::resolveSiteUserIdForUpdateCompany($user, $m['raw'], $m['key'], $contactIdsMap);
                    if ($userId <= 0) {
                        continue;
                    }
                    if ($m['target'] === 'os') {
                        $params['OS_COMPANY_USERS'][$m['key']] = $userId;
                    } else {
                        $params['LEGAN_ENTITY_USERS'][$m['key']] = $userId;
                    }
                    if (isset($seenUserIds[$userId])) {
                        continue;
                    }
                    $seenUserIds[$userId] = true;
                    $discountMapped = null;
                    if ($discountBase !== null
                        && $discountBase > 0
                        && self::shouldApplyCompanyDiscountGroupForUser($userId, $params)
                    ) {
                        $discountMapped = $discountBase;
                    }
                    self::applyB24CompanyGroupsToUser($user, $userId, $params, $discountMapped);
                }
            }

            $this->resolveOsRequisitesFileParamForUpdate($params);
            
            // Обрабатываем связь с холдингом
            if (!empty($params['OS_HOLDING_OF'])) {
                $holdingRef = $params['OS_HOLDING_OF'];
                if (\is_array($holdingRef)) {
                    if (!empty($holdingRef['ID']) && \is_scalar($holdingRef['ID'])) {
                        $params['OS_HOLDING_OF'] = (int)$holdingRef['ID'];
                    } else {
                        $scalar = self::unwrapCrmScalarForGroupId($holdingRef);
                        if ($scalar !== null && $scalar !== '' && \is_scalar($scalar)) {
                            $holdingCompany = $this->getCompanyByB24ID(\trim((string)$scalar));
                            if (!empty($holdingCompany['ID'])) {
                                $params['OS_HOLDING_OF'] = (int)$holdingCompany['ID'];
                            } else {
                                unset($params['OS_HOLDING_OF']);
                            }
                        } else {
                            unset($params['OS_HOLDING_OF']);
                        }
                    }
                } else {
                    $holdingCompany = $this->getCompanyByB24ID(\trim((string)$holdingRef));
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

            $this->hydrateOsRequisitesFileInPropertyBag($arProps);
            self::mirrorOsCompanyFieldsToLeganEntity($arProps);
            self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($arProps, $params);
            self::normalizeInboundCrmListPropertyValuesForIblock($arProps);
            self::syncTrace('Company::createCompanyFromUpdate merged PROPERTY_VALUES', [
                'inn_arProps' => self::syncInnFieldLengths($arProps),
            ]);
            
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
                self::applyLeganPhonePropertyValuesToElement((int) $companyId, $arProps);
                self::syncTrace('Company::createCompanyFromUpdate CIBlockElement::Add ok', [
                    'element_id' => (int)$companyId,
                ]);
                if (($arFields['ACTIVE'] ?? '') === 'Y') {
                    self::activateCompanyStaffSiteUsers(self::siteUserIdsForCompanyActivation($arProps));
                }

                return $companyId;
            }
            
            self::syncTrace('Company::createCompanyFromUpdate CIBlockElement::Add failed', [
                'last_error' => (string)($el->LAST_ERROR ?? ''),
            ]);

            return false;
        }

        /**
         * OS_REQUSITES_FILE: уже файл в b_file этого сайта — не качаем с B24 по SUBDIR/FILE_NAME.
         * Иначе null и дальше {@see processRequisitesFile()}.
         */
        private static function tryResolveOsRequisitesFileAsExistingSiteFileId(mixed $raw): ?int
        {
            if (!\is_array($raw)) {
                return null;
            }
            $id = isset($raw['ID']) ? (int)$raw['ID'] : 0;
            if ($id <= 0) {
                return null;
            }
            $moduleId = isset($raw['MODULE_ID']) && \is_scalar($raw['MODULE_ID'])
                ? (string)$raw['MODULE_ID']
                : '';
            if ($moduleId !== '' && \in_array($moduleId, ['main', 'iblock'], true)) {
                return $id;
            }
            $src = isset($raw['SRC']) && \is_scalar($raw['SRC']) ? (string)$raw['SRC'] : '';
            if ($src !== '' && \str_starts_with($src, '/upload/')) {
                return $id;
            }

            return null;
        }

        /**
         * Приводит вход OS_REQUSITES_FILE к ID файла в b_file этого сайта (без скачивания с B24), если возможно.
         */
        private static function normalizeOsRequisitesFileInputToStoredFileId(mixed $raw): ?int
        {
            if ($raw === null || $raw === '') {
                return null;
            }
            if (\is_int($raw) || \is_float($raw)) {
                $id = (int)$raw;

                return $id > 0 ? $id : null;
            }
            if (\is_string($raw)) {
                $t = \trim($raw);
                if ($t !== '' && \ctype_digit($t)) {
                    $id = (int)$t;

                    return $id > 0 ? $id : null;
                }

                return null;
            }
            if (!\is_array($raw)) {
                return null;
            }
            if (\array_key_exists('VALUE', $raw)) {
                return self::normalizeOsRequisitesFileInputToStoredFileId($raw['VALUE']);
            }

            if (self::isOsRequisitesFileCrmDownloadPayload($raw)) {
                return null;
            }

            return self::tryResolveOsRequisitesFileAsExistingSiteFileId($raw);
        }

        /**
         * После слияния с текущими свойствами: скачать с CRM при необходимости, иначе int для зеркала LEGAN_ENTITY_FILE.
         */
        private function hydrateOsRequisitesFileInPropertyBag(array &$props): void
        {
            if (!\array_key_exists('OS_REQUSITES_FILE', $props)) {
                return;
            }
            $tmp = ['OS_REQUSITES_FILE' => $props['OS_REQUSITES_FILE']];
            $this->resolveOsRequisitesFileParamForUpdate($tmp);
            if (\array_key_exists('OS_REQUSITES_FILE', $tmp)) {
                $props['OS_REQUSITES_FILE'] = $tmp['OS_REQUSITES_FILE'];
            }
        }

        /**
         * OS_REQUSITES_FILE из CRM: сначала скачивание на сайт через {@see processRequisitesFile()},
         * затем при отсутствии данных для скачивания — только локальный int / «свой» b_file без SRC/SUBDIR с CRM.
         */
        private function resolveOsRequisitesFileParamForUpdate(array &$params): void
        {
            if (!\array_key_exists('OS_REQUSITES_FILE', $params)) {
                return;
            }
            $raw = $params['OS_REQUSITES_FILE'];
            if ($raw === null || $raw === '') {
                return;
            }
            if (\is_array($raw) && self::isOsRequisitesFileCrmDownloadPayload($raw)) {
                $fileId = $this->processRequisitesFile($raw);
                if ($fileId) {
                    $params['OS_REQUSITES_FILE'] = $fileId;

                    return;
                }
            }
            $norm = self::normalizeOsRequisitesFileInputToStoredFileId($raw);
            if ($norm !== null) {
                $params['OS_REQUSITES_FILE'] = $norm;

                return;
            }
            if (\is_array($raw)) {
                $fileId = $this->processRequisitesFile($raw);
                if ($fileId) {
                    $params['OS_REQUSITES_FILE'] = $fileId;
                }
            }
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
            if (!\defined('URL_B24')) {
                return false;
            }

            $base = \rtrim((string)\constant('URL_B24'), '/');
            if ($base === '') {
                return false;
            }

            $src = isset($fileData['SRC']) && \is_string($fileData['SRC']) ? \trim($fileData['SRC']) : '';
            $downloadableUrl = null;
            if (self::isSafeCrmPublicUploadSrc($src)) {
                $downloadableUrl = $base . $src;
            } else {
                $subdir = $fileData['SUBDIR'] ?? null;
                $fileNameInUrl = $fileData['FILE_NAME'] ?? null;
                if (!self::isSafeB24RequisiteUrlPart($subdir, $fileNameInUrl)) {
                    return false;
                }
                $subdir = \ltrim((string)$subdir, '/');
                $fileNameInUrl = (string)$fileNameInUrl;
                $downloadableUrl = $base . '/' . $subdir . '/' . \rawurlencode($fileNameInUrl);
            }

            $safeOriginal = self::sanitizeRequisitesOriginalFileName($fileData['ORIGINAL_NAME'] ?? null);
            if ($safeOriginal === false && $src !== '') {
                $pathOnly = $src;
                if (\str_contains($pathOnly, '?')) {
                    $pathOnly = (string)\strstr($pathOnly, '?', true);
                }
                $safeOriginal = self::sanitizeRequisitesOriginalFileName(\basename(\str_replace('\\', '/', $pathOnly)));
            }
            if ($safeOriginal === false) {
                return false;
            }
            
            try {
                
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
            } catch (\Throwable $e) {
                // Ошибка обработки файла (в т.ч. \Error при неверных данных/окружении)
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
                $arCompany['NAME'] = $arFields['NAME'] ?? '';
                foreach (self::$codeProps as $code) {
                    $p = $arProps[$code] ?? null;
                    $arCompany[$code] = \is_array($p) ? ($p['VALUE'] ?? null) : null;
                    // Для свойств типа "Список" также сохраняем VALUE_XML_ID
                    if (\is_array($p) && isset($p['VALUE_XML_ID'])) {
                        $arCompany[$code . "_XML_ID"] = $p['VALUE_XML_ID'];
                    }
                }
                self::enrichLeganFromOsOnRead($arCompany);

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

            // Входящий ajax (CRM → сайт) не тянет полный prolog с автоподключением iblock — без этого PHP 8: Class "CIBlockElement" not found.
            if (!\CModule::IncludeModule('iblock')) {
                self::syncTrace('Company::getCompanyByB24ID iblock_not_loaded', ['b24_id' => $b24_id]);

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
        private static function mapCompanyEditFormLeganToOs(array &$data): void
        {
            $map = [
                'LEGAN_ENTITY_NAME' => 'OS_COMPANY_NAME',
                'LEGAN_ENTITY_INN' => 'OS_COMPANY_INN',
                'LEGAN_ENTITY_CITY' => 'OS_COMPANY_CITY',
                'LEGAN_ENTITY_WWW' => 'OS_COMPANY_WEB_SITE',
                'LEGAN_ENTITY_EMAIL' => 'OS_COMPANY_EMAIL',
            ];
            foreach ($map as $leg => $os) {
                if (!\array_key_exists($leg, $data)) {
                    continue;
                }
                $data[$os] = \trim((string) ($data[$leg] ?? ''));
            }
            if (\array_key_exists('LEGAN_MAIN_PHONE', $data) || \array_key_exists('LEGAN_MOBILE_PHONE', $data)) {
                $m = \array_key_exists('LEGAN_MAIN_PHONE', $data) ? \trim((string) $data['LEGAN_MAIN_PHONE']) : '';
                $mb = \array_key_exists('LEGAN_MOBILE_PHONE', $data) ? \trim((string) $data['LEGAN_MOBILE_PHONE']) : '';
                $data['OS_COMPANY_PHONE'] = $m !== '' ? $m : $mb;
                $data['LEGAN_ENTITY_PHONE'] = $data['OS_COMPANY_PHONE'];
            } elseif (\array_key_exists('LEGAN_ENTITY_PHONE', $data)) {
                $data['OS_COMPANY_PHONE'] = \trim((string) ($data['LEGAN_ENTITY_PHONE'] ?? ''));
            }
        }

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

            self::mapCompanyEditFormLeganToOs($data);

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

            // Обновляем свойства (OS_ + витрина, зеркало, отдельные LEGAN_MAIN/LEGAN_MOBILE)
            $propBag = [];
            foreach (['OS_COMPANY_NAME', 'OS_COMPANY_INN', 'OS_COMPANY_CITY', 'OS_COMPANY_PHONE', 'OS_COMPANY_EMAIL', 'OS_COMPANY_WEB_SITE', 'OS_REQUSITES_FILE'] as $c) {
                if (\array_key_exists($c, $data)) {
                    $propBag[$c] = $data[$c];
                }
            }
            if (\array_key_exists('LEGAN_MAIN_PHONE', $data)) {
                $propBag['LEGAN_MAIN_PHONE'] = \trim((string) $data['LEGAN_MAIN_PHONE']);
            }
            if (\array_key_exists('LEGAN_MOBILE_PHONE', $data)) {
                $propBag['LEGAN_MOBILE_PHONE'] = \trim((string) $data['LEGAN_MOBILE_PHONE']);
            }
            self::mirrorOsCompanyFieldsToLeganEntity($propBag);
            foreach ($propBag as $code => $val) {
                \CIBlockElement::SetPropertyValueCode($companyId, (string) $code, $val);
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
                $data['SITE_IBLOCK_ELEMENT_ID'] = $companyId;
                
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
         * ID руководителей из OS_COMPANY_BOSS и LEGAN_ENTITY_BOSS (объединение, без дублей).
         * Не использовать `??` между OS и LEGAN: пустой [] в OS не даёт прочитать заполненный LEGAN.
         *
         * @return list<int>
         */
        private function mergeCompanyBossIdLists(mixed $osRaw, mixed $leganRaw): array
        {
            $set = [];
            foreach ([$osRaw, $leganRaw] as $raw) {
                if ($raw === null || $raw === '' || $raw === false) {
                    continue;
                }
                if (!\is_array($raw)) {
                    $raw = [$raw];
                }
                foreach ($raw as $id) {
                    $i = (int) $id;
                    if ($i > 0) {
                        $set[$i] = $i;
                    }
                }
            }

            return \array_values($set);
        }

        /**
         * Публичная обёртка для проверок ACL по строке {@see getCompany} (страница / компонент).
         *
         * @param array<string, mixed> $companyData
         * @return list<int>
         */
        public function getMergedBossUserIdsFromCompanyRow(array $companyData): array
        {
            return $this->mergeCompanyBossIdLists(
                $companyData['OS_COMPANY_BOSS'] ?? null,
                $companyData['LEGAN_ENTITY_BOSS'] ?? null
            );
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

            $bosses = $this->getMergedBossUserIdsFromCompanyRow($company);
            if (\in_array((int) $userId, $bosses, true)) {
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

            // Связь crm.company ↔ ID элемента ИБ 23 на сайте (иначе B24 `CompanySync` не шлёт `UPDATE_COMPANY`)
            if (!empty($data['SITE_IBLOCK_ELEMENT_ID']) && (int) $data['SITE_IBLOCK_ELEMENT_ID'] > 0) {
                $b24Fields[CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF] = (string) (int) $data['SITE_IBLOCK_ELEMENT_ID'];
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