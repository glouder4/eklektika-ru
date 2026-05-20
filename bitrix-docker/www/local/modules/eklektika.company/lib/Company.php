<?php
    namespace OnlineService\Site;

    use OnlineService\B24\N8nCrmGateway;
    use OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport;
    use OnlineService\B24\RestClient;
    use OnlineService\B24\User;
    use OnlineService\B24\UserSync\Config\UserSyncConfig;
    use OnlineService\Site\Config\CompanyB24Config;
    use OnlineService\Site\Config\CompanyModuleConfig;
    use OnlineService\Sync\FromCrm\CrmInboundUfMap;
    use OnlineService\Sync\SyncTrace;
    use Bitrix\Main\Loader;

    class Company{
        /** @var array<string, array{id: int, multiple: bool, type: string}|null> */
        private static array $companyIblockPropertyMetaCache = [];

        private static ?bool $loggedDuplicateLeganPhonePropertyIds = null;

        private static $codeProps = [
            "OS_COMPANY_IS_HEAD_OF_HOLDING",
            /** Зеркало OS; участвует в определении головной компании для наследования скидки на дочерние (`OS_HOLDING_OF`). */
            "LEGAN_ENTITY_IS_HEAD_COMPANY",
            "OS_COMPANY_BOSS",
            /** Витрина/«зеркало»; для ACL нельзя опираться только на OS_ — бывает пусто при заполненном LEGAN. */
            "LEGAN_ENTITY_BOSS",
            "OS_HEAD_COMPANY_B24_ID",
            "OS_HOLDING_OF",
            "OS_COMPANY_INN",
            "OS_COMPANY_WEB_SITE",
            "OS_COMPANY_USERS",
            /** Витрина; без ключа в merge inbound‑апдейт LEGAN_* терялся при mirror OS→LEGAN из текущего ИБ */
            "LEGAN_ENTITY_USERS",
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
            'LEGAL_ENTITY_INN',
            'LEGAN_ENTITY_CITY',
            /** Витрина; без ключа inbound‑поле не попадало в merge (раньше только OS_COMPANY_JUR_ADDRESS). */
            'LEGAN_ENTITY_ADRESS',
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
         * Трассировка входящего sync при sync_debug (модуль `eklektika.sync` / `SyncTrace`).
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
         * Жёсткая отладочная остановка (только при подключённом `eklektika.sync` и флаге в конфиге).
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
            foreach (['OS_COMPANY_INN' => 'os_inn', 'LEGAN_ENTITY_INN' => 'legan_inn', 'LEGAL_ENTITY_INN' => 'legal_inn'] as $code => $label) {
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

        /**
         * @return array<string, string>
         */
        private static function osToLeganMirrorFieldMap(bool $includePhone): array
        {
            $map = [
                'OS_COMPANY_NAME' => 'LEGAN_ENTITY_NAME',
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
            if ($includePhone) {
                $map['OS_COMPANY_PHONE'] = 'LEGAN_ENTITY_PHONE';
            }

            return $map;
        }

        private static function mirrorOsCompanyFieldsToLeganEntity(array &$props): void
        {
            foreach (self::osToLeganMirrorFieldMap(true) as $os => $legan) {
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
            self::mirrorInnToLegalEntityProperty($props);
        }

        /**
         * Зеркало OS → LEGAN без телефона (телефоны задаются отдельно через LEGAN_MAIN / LEGAN_MOBILE).
         *
         * @param array<string, mixed> $props
         */
        private static function mirrorOsCompanyFieldsToLeganEntityExcludingPhones(array &$props): void
        {
            foreach (self::osToLeganMirrorFieldMap(false) as $os => $legan) {
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
            self::mirrorInnToLegalEntityProperty($props);
        }

        /**
         * Канонический OS/LEGAN_ENTITY_PHONE из витринных LEGAN_MAIN / LEGAN_MOBILE (форма и inbound UF).
         *
         * @param array<string, mixed> $props
         */
        private static function syncOsPhoneFromLeganProfileFields(array &$props): void
        {
            $hasMain = \array_key_exists('LEGAN_MAIN_PHONE', $props);
            $hasMobile = \array_key_exists('LEGAN_MOBILE_PHONE', $props);
            if (!$hasMain && !$hasMobile) {
                return;
            }
            $main = $hasMain ? \trim((string) $props['LEGAN_MAIN_PHONE']) : '';
            $mobile = $hasMobile ? \trim((string) $props['LEGAN_MOBILE_PHONE']) : '';
            $osPhone = $main !== '' ? $main : $mobile;
            $props['OS_COMPANY_PHONE'] = $osPhone;
            $props['LEGAN_ENTITY_PHONE'] = $osPhone;
        }

        /**
         * @return list<string>
         */
        private static function companyProfilePhonePropertyCodes(): array
        {
            return [
                'OS_COMPANY_PHONE',
                'LEGAN_ENTITY_PHONE',
                'LEGAN_MAIN_PHONE',
                'LEGAN_MOBILE_PHONE',
            ];
        }

        /**
         * @return list<string>
         */
        private static function companyProfileRequisitesFilePropertyCodes(): array
        {
            return ['OS_REQUSITES_FILE', 'LEGAN_ENTITY_FILE'];
        }

        /**
         * @param array<string, mixed> $bag
         */
        private static function mirrorInnToLegalEntityProperty(array &$bag): void
        {
            if (!self::isCompanyFieldEmptyForRead($bag['LEGAL_ENTITY_INN'] ?? null, 'LEGAL_ENTITY_INN', true)) {
                return;
            }
            if (!self::isCompanyFieldEmptyForRead($bag['LEGAN_ENTITY_INN'] ?? null, 'LEGAN_ENTITY_INN', true)) {
                $bag['LEGAL_ENTITY_INN'] = $bag['LEGAN_ENTITY_INN'];
                return;
            }
            if (!self::isCompanyFieldEmptyForRead($bag['OS_COMPANY_INN'] ?? null, 'OS_COMPANY_INN', false)) {
                $bag['LEGAL_ENTITY_INN'] = $bag['OS_COMPANY_INN'];
            }
        }

        /**
         * @return array<string, mixed>
         */
        private static function loadCompanyElementProperties(int $companyId): array
        {
            $currentProps = [];
            foreach (self::$codeProps as $code) {
                $propertyValues = \CIBlockElement::GetProperty(
                    CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    $companyId,
                    [],
                    ['CODE' => $code]
                );

                $values = [];
                $isMultiple = false;
                while ($prop = $propertyValues->GetNext()) {
                    $values[] = $prop['VALUE'];
                    if (($prop['MULTIPLE'] ?? 'N') === 'Y') {
                        $isMultiple = true;
                    }
                }

                if ($isMultiple) {
                    $currentProps[$code] = $values;
                } else {
                    $currentProps[$code] = \count($values) > 0 ? $values[0] : null;
                }
            }

            return $currentProps;
        }

        /**
         * Метаданные свойства ИБ компаний (кэш на запрос).
         *
         * @return array{id: int, multiple: bool, type: string}|null
         */
        private static function getCompanyIblockPropertyMeta(string $code): ?array
        {
            if (\array_key_exists($code, self::$companyIblockPropertyMetaCache)) {
                return self::$companyIblockPropertyMetaCache[$code];
            }
            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $res = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
            if (!$row = $res->Fetch()) {
                self::$companyIblockPropertyMetaCache[$code] = null;

                return null;
            }
            $meta = [
                'id' => (int) $row['ID'],
                'multiple' => ($row['MULTIPLE'] ?? 'N') === 'Y',
                'type' => (string) ($row['PROPERTY_TYPE'] ?? 'S'),
            ];
            self::$companyIblockPropertyMetaCache[$code] = $meta;

            return $meta;
        }

        private static function traceDuplicateLeganPhonePropertyIdsIfAny(): void
        {
            if (self::$loggedDuplicateLeganPhonePropertyIds !== null) {
                return;
            }
            self::$loggedDuplicateLeganPhonePropertyIds = true;
            $mainMeta = self::getCompanyIblockPropertyMeta('LEGAN_MAIN_PHONE');
            $mobileMeta = self::getCompanyIblockPropertyMeta('LEGAN_MOBILE_PHONE');
            if ($mainMeta === null || $mobileMeta === null) {
                return;
            }
            if ($mainMeta['id'] === $mobileMeta['id']) {
                self::syncTrace('company.iblock.duplicate_legan_phone_property_ids', [
                    'property_id' => $mainMeta['id'],
                    'codes' => ['LEGAN_MAIN_PHONE', 'LEGAN_MOBILE_PHONE'],
                ]);
            }
        }

        /**
         * Чтение scalar string-свойства элемента (последнее непустое значение при MULTIPLE=Y).
         */
        private static function readCompanyIblockScalarProperty(int $elementId, string $code): string
        {
            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $rs = \CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => $code]);
            $lastNonEmpty = '';
            while ($row = $rs->GetNext()) {
                $raw = $row['VALUE'] ?? '';
                if (\is_array($raw)) {
                    if (\array_key_exists('TEXT', $raw)) {
                        $raw = $raw['TEXT'];
                    } elseif ($raw !== []) {
                        $raw = \reset($raw);
                    } else {
                        $raw = '';
                    }
                }
                $v = \trim((string) $raw);
                if ($v !== '') {
                    $lastNonEmpty = $v;
                }
            }

            return $lastNonEmpty;
        }

        /**
         * Телефоны ИБ 23: {@see \CIBlockElement::SetPropertyValues} по CODE (на стенде надёжнее, чем SetPropertyValuesEx по ID).
         */
        private static function writeCompanyIblockPhoneProperty(int $elementId, string $code, string $value): bool
        {
            if ($code === 'LEGAN_MAIN_PHONE' || $code === 'LEGAN_MOBILE_PHONE') {
                self::traceDuplicateLeganPhonePropertyIdsIfAny();
            }
            $meta = self::getCompanyIblockPropertyMeta($code);
            if ($meta === null) {
                self::syncTrace('company.iblock.write_property_missing', ['code' => $code, 'element_id' => $elementId]);

                return false;
            }
            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            \CIBlockElement::SetPropertyValues(
                $elementId,
                $iblockId,
                $value !== '' ? $value : false,
                $code
            );
            $readBack = self::readCompanyIblockScalarProperty($elementId, $code);

            return $readBack === $value;
        }

        /**
         * Надёжная запись string-свойства: сброс + {@see \CIBlockElement::SetPropertyValuesEx}.
         */
        private static function writeCompanyIblockScalarProperty(int $elementId, string $code, string $value): void
        {
            if (\in_array($code, self::companyProfilePhonePropertyCodes(), true)) {
                self::writeCompanyIblockPhoneProperty($elementId, $code, $value);

                return;
            }
            $meta = self::getCompanyIblockPropertyMeta($code);
            if ($meta === null) {
                self::syncTrace('company.iblock.write_property_missing', ['code' => $code, 'element_id' => $elementId]);

                return;
            }
            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $propKey = $meta['id'] > 0 ? $meta['id'] : $code;
            \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [$propKey => false]);
            if ($value !== '') {
                \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [$propKey => $value]);
            }
        }

        /**
         * @return array{LEGAN_MAIN_PHONE?: string, LEGAN_MOBILE_PHONE?: string}
         */
        private static function collectInboundLeganPhoneFields(array $params): array
        {
            $out = [];
            foreach (['LEGAN_MAIN_PHONE', 'LEGAN_MOBILE_PHONE'] as $code) {
                if (!\array_key_exists($code, $params)) {
                    continue;
                }
                $out[$code] = \trim((string) $params[$code]);
            }

            return $out;
        }

        /**
         * Inbound UPDATE_COMPANY: запись LEGAN_MAIN / LEGAN_MOBILE в элемент ИБ 23 (до bulk Update).
         *
         * @param array{LEGAN_MAIN_PHONE?: string, LEGAN_MOBILE_PHONE?: string} $phones
         */
        private static function persistInboundCompanyPhonesToElement(int $elementId, array $phones): void
        {
            if ($elementId <= 0 || $phones === [] || !\CModule::IncludeModule('iblock')) {
                return;
            }

            $trace = ['element_id' => $elementId];
            foreach (['LEGAN_MAIN_PHONE', 'LEGAN_MOBILE_PHONE'] as $code) {
                if (!\array_key_exists($code, $phones)) {
                    continue;
                }
                $wanted = \trim((string) $phones[$code]);
                $ok = self::writeCompanyIblockPhoneProperty($elementId, $code, $wanted);
                $trace[$code] = [
                    'wanted_len' => \strlen($wanted),
                    'ok' => $ok,
                    'read_back' => \substr(self::readCompanyIblockScalarProperty($elementId, $code), 0, 32),
                ];
            }

            $work = \trim((string) ($phones['LEGAN_MAIN_PHONE'] ?? ''));
            $mobile = \trim((string) ($phones['LEGAN_MOBILE_PHONE'] ?? ''));
            $os = $work !== '' ? $work : $mobile;
            if ($os !== '') {
                self::writeCompanyIblockPhoneProperty($elementId, 'OS_COMPANY_PHONE', $os);
                self::writeCompanyIblockPhoneProperty($elementId, 'LEGAN_ENTITY_PHONE', $os);
            }

            self::syncTrace('Company::persistInboundCompanyPhonesToElement', $trace);
        }

        /**
         * LEGAN_MAIN_PHONE + LEGAN_MOBILE_PHONE через {@see persistInboundCompanyPhonesToElement}.
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyLeganMainAndMobilePhonesToElement(int $elementId, array $arProps): void
        {
            $phones = self::collectInboundLeganPhoneFields($arProps);
            if ($phones !== []) {
                self::persistInboundCompanyPhonesToElement($elementId, $phones);
            }
        }

        /**
         * Запись телефонов профиля: MAIN/MOBILE независимо, затем OS_* / LEGAN_ENTITY_PHONE.
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyCompanyPhonePropertiesToElement(int $elementId, array $arProps): void
        {
            self::syncOsPhoneFromLeganProfileFields($arProps);
            self::applyLeganMainAndMobilePhonesToElement($elementId, $arProps);
            $written = [];
            foreach (['OS_COMPANY_PHONE', 'LEGAN_ENTITY_PHONE'] as $code) {
                if (!\array_key_exists($code, $arProps)) {
                    continue;
                }
                $v = $arProps[$code];
                if ($v === null) {
                    continue;
                }
                $str = \trim((string) $v);
                self::writeCompanyIblockScalarProperty($elementId, $code, $str);
                $written[$code] = \strlen($str);
            }
            if ($written !== [] || \array_key_exists('LEGAN_MAIN_PHONE', $arProps) || \array_key_exists('LEGAN_MOBILE_PHONE', $arProps)) {
                self::syncTrace('Company::applyCompanyPhonePropertiesToElement', [
                    'element_id' => $elementId,
                    'legan_main_preview' => \substr(\trim((string) ($arProps['LEGAN_MAIN_PHONE'] ?? '')), 0, 24),
                    'legan_mobile_preview' => \substr(\trim((string) ($arProps['LEGAN_MOBILE_PHONE'] ?? '')), 0, 24),
                    'os_lens' => $written,
                ]);
            }
        }

        /**
         * Исключаем телефоны из {@see \CIBlockElement::Update}(PROPERTY_VALUES) — на ИБ 23 bulk-update затирает LEGAN_MAIN.
         *
         * @param array<string, mixed> $arProps
         */
        private static function stripCompanyPhoneKeysFromPropertyBag(array &$arProps): void
        {
            foreach (self::companyProfilePhonePropertyCodes() as $code) {
                unset($arProps[$code]);
            }
        }

        /**
         * Файл реквизитов не обновляем через bulk {@see \CIBlockElement::Update}(PROPERTY_VALUES).
         *
         * @param array<string, mixed> $arProps
         */
        private static function stripCompanyRequisitesFileKeysFromPropertyBag(array &$arProps): void
        {
            foreach (self::companyProfileRequisitesFilePropertyCodes() as $code) {
                unset($arProps[$code]);
            }
        }

        /**
         * Явная запись fileId в OS_REQUSITES_FILE и LEGAN_ENTITY_FILE (тип «Файл» в ИБ 23).
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyCompanyRequisitesFilePropertiesToElement(int $elementId, array $arProps): void
        {
            $iblockId = CompanyModuleConfig::COMPANY_IBLOCK_ID;
            $hasOs = \array_key_exists('OS_REQUSITES_FILE', $arProps);
            $hasLegan = \array_key_exists('LEGAN_ENTITY_FILE', $arProps);

            if ($hasOs && ($arProps['OS_REQUSITES_FILE'] === '' || $arProps['OS_REQUSITES_FILE'] === null)) {
                \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
                    'OS_REQUSITES_FILE' => false,
                    'LEGAN_ENTITY_FILE' => false,
                ]);

                return;
            }

            $fileId = 0;
            if ($hasOs) {
                $norm = self::normalizeOsRequisitesFileInputToStoredFileId($arProps['OS_REQUSITES_FILE']);
                if ($norm !== null) {
                    $fileId = $norm;
                }
            }
            if ($fileId <= 0 && $hasLegan) {
                $norm = self::normalizeOsRequisitesFileInputToStoredFileId($arProps['LEGAN_ENTITY_FILE']);
                if ($norm !== null) {
                    $fileId = $norm;
                }
            }
            if ($fileId <= 0) {
                return;
            }

            foreach (self::companyProfileRequisitesFilePropertyCodes() as $code) {
                \CIBlockElement::SetPropertyValues($elementId, $iblockId, $fileId, $code);
            }
        }

        /**
         * Поля формы /company/profile/edit/ → PROPERTY_VALUES (только переданные ключи).
         *
         * @param array<string, mixed> $arProps
         * @param array<string, mixed> $data
         */
        /**
         * Коды свойств ИБ 23, редактируемых формой /company/profile/edit/.
         *
         * @return list<string>
         */
        private static function companyProfilePropertyCodes(): array
        {
            return [
                'OS_COMPANY_NAME',
                'OS_COMPANY_INN',
                'OS_COMPANY_CITY',
                'OS_COMPANY_PHONE',
                'OS_COMPANY_EMAIL',
                'OS_COMPANY_WEB_SITE',
                'OS_REQUSITES_FILE',
                'LEGAN_ENTITY_NAME',
                'LEGAN_ENTITY_INN',
                'LEGAL_ENTITY_INN',
                'LEGAN_ENTITY_CITY',
                'LEGAN_ENTITY_WWW',
                'LEGAN_ENTITY_EMAIL',
                'LEGAN_ENTITY_PHONE',
                'LEGAN_MAIN_PHONE',
                'LEGAN_MOBILE_PHONE',
                'LEGAN_ENTITY_FILE',
            ];
        }

        private static function mergeCompanyProfileFormIntoPropertyBag(array &$arProps, array $data): void
        {
            foreach (self::companyProfilePropertyCodes() as $code) {
                if (\array_key_exists($code, $data)) {
                    $arProps[$code] = $data[$code];
                }
            }
        }

        /**
         * Телефоны формы профиля: LEGAN_MAIN / LEGAN_MOBILE независимо; OS_* — рабочий (main, иначе mobile).
         *
         * @param array<string, mixed> $bag
         * @param array<string, mixed> $data
         */
        private static function applyProfilePhonesToPropertyBag(array &$bag, array $data): void
        {
            if (!\array_key_exists('LEGAN_MAIN_PHONE', $data) && !\array_key_exists('LEGAN_MOBILE_PHONE', $data)) {
                return;
            }
            $main = \array_key_exists('LEGAN_MAIN_PHONE', $data)
                ? \trim((string) $data['LEGAN_MAIN_PHONE'])
                : '';
            $mobile = \array_key_exists('LEGAN_MOBILE_PHONE', $data)
                ? \trim((string) $data['LEGAN_MOBILE_PHONE'])
                : '';
            if (\array_key_exists('LEGAN_MAIN_PHONE', $data)) {
                $bag['LEGAN_MAIN_PHONE'] = $main;
            }
            if (\array_key_exists('LEGAN_MOBILE_PHONE', $data)) {
                $bag['LEGAN_MOBILE_PHONE'] = $mobile;
            }
            $osPhone = $main !== '' ? $main : $mobile;
            $bag['OS_COMPANY_PHONE'] = $osPhone;
            $bag['LEGAN_ENTITY_PHONE'] = $osPhone;
        }

        /**
         * Запись профиля в ИБ 23 из данных формы (после {@see mapCompanyEditFormLeganToOs}, обычно — после CRM).
         * На стенде {@see \CIBlockElement::Update} с PROPERTY_VALUES для string/телефонов ненадёжен —
         * пишем через {@see writeCompanyIblockScalarProperty} / {@see applyCompanyPhonePropertiesToElement}.
         *
         * @param array<string, mixed> $data
         * @param list<int> $deleteRequisitesFileIdsAfterSuccess ID b_file для удаления после успешной записи в ИБ
         *
         * @return string|null текст ошибки или null при успехе
         */
        public function persistCompanyProfileFormDataToIblock(
            int $companyId,
            array $data,
            array $deleteRequisitesFileIdsAfterSuccess = []
        ): ?string {
            $bag = [];
            self::mergeCompanyProfileFormIntoPropertyBag($bag, $data);
            self::syncCanonicalInnAcrossPropertyBag($bag, $data);
            $this->hydrateOsRequisitesFileInPropertyBag($bag);
            self::mirrorOsCompanyFieldsToLeganEntityExcludingPhones($bag);
            self::syncCanonicalInnAcrossPropertyBag($bag, $data);

            $phoneBag = [];
            self::applyProfilePhonesToPropertyBag($phoneBag, $data);

            $phoneCodes = self::companyProfilePhonePropertyCodes();
            $fileCodes = self::companyProfileRequisitesFilePropertyCodes();
            foreach (self::companyProfilePropertyCodes() as $code) {
                if (\in_array($code, $phoneCodes, true) || \in_array($code, $fileCodes, true)) {
                    continue;
                }
                if (!\array_key_exists($code, $bag)) {
                    continue;
                }
                $v = $bag[$code];
                if ($v === null) {
                    continue;
                }
                if (\is_string($v)) {
                    self::writeCompanyIblockScalarProperty($companyId, $code, \trim($v));
                } else {
                    \CIBlockElement::SetPropertyValues(
                        $companyId,
                        CompanyModuleConfig::COMPANY_IBLOCK_ID,
                        $v,
                        $code
                    );
                }
            }

            self::applyCompanyPhonePropertiesToElement($companyId, $phoneBag);
            self::applyCompanyRequisitesFilePropertiesToElement($companyId, $bag);

            $name = \trim((string) ($bag['OS_COMPANY_NAME'] ?? ''));
            if ($name === '') {
                $name = \trim((string) ($bag['LEGAN_ENTITY_NAME'] ?? ''));
            }
            if ($name !== '') {
                $el = new \CIBlockElement();
                if (!$el->Update($companyId, ['NAME' => $name])) {
                    return (string) $el->LAST_ERROR;
                }
            }

            self::applyLeganInnPropertyValuesToElement($companyId, $bag);

            foreach (\array_unique(\array_map('intval', $deleteRequisitesFileIdsAfterSuccess)) as $delFid) {
                if ($delFid > 0) {
                    \CFile::Delete($delFid);
                }
            }

            return null;
        }

        /**
         * Канонический ИНН из данных формы (после {@see mapCompanyEditFormLeganToOs}).
         *
         * @param array<string, mixed> $data
         */
        private static function resolveCanonicalInnFromProfileData(array $data): string
        {
            if (\array_key_exists('OS_COMPANY_INN', $data)) {
                return \trim((string) $data['OS_COMPANY_INN']);
            }
            if (\array_key_exists('LEGAN_ENTITY_INN', $data)) {
                return \trim((string) $data['LEGAN_ENTITY_INN']);
            }
            if (\array_key_exists('LEGAL_ENTITY_INN', $data)) {
                return \trim((string) $data['LEGAL_ENTITY_INN']);
            }

            return '';
        }

        /**
         * Синхронизирует все коды ИНН в bag одним значением из формы.
         *
         * @param array<string, mixed> $bag
         * @param array<string, mixed> $data
         */
        private static function syncCanonicalInnAcrossPropertyBag(array &$bag, array $data): void
        {
            $inn = self::resolveCanonicalInnFromProfileData($data);
            if ($inn === '') {
                return;
            }
            $bag['OS_COMPANY_INN'] = $inn;
            $bag['LEGAN_ENTITY_INN'] = $inn;
            $bag['LEGAL_ENTITY_INN'] = $inn;
        }

        /**
         * Post-pass: ИНН и телефоны через {@see \CIBlockElement::SetPropertyValues} по одному коду (IBLOCK_ID).
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyCompanyProfileCriticalPropertiesToElement(int $elementId, array $arProps): void
        {
            $inn = self::resolveCanonicalInnFromProfileData($arProps);
            if ($inn === '' && \array_key_exists('LEGAN_ENTITY_INN', $arProps)) {
                $inn = \trim((string) $arProps['LEGAN_ENTITY_INN']);
            }
            if ($inn === '' && \array_key_exists('OS_COMPANY_INN', $arProps)) {
                $inn = \trim((string) $arProps['OS_COMPANY_INN']);
            }
            if ($inn !== '') {
                foreach (['OS_COMPANY_INN', 'LEGAN_ENTITY_INN', 'LEGAL_ENTITY_INN'] as $code) {
                    self::writeCompanyIblockScalarProperty($elementId, $code, $inn);
                }
            }

            self::applyCompanyPhonePropertiesToElement($elementId, $arProps);
        }

        /**
         * На чтении: если OS_COMPANY_INN и LEGAN_ENTITY_INN расходятся — для витрины берём OS (как в CRM).
         *
         * @param array<string, mixed> $arCompany
         */
        private static function reconcileInnFieldsOnRead(array &$arCompany): void
        {
            $osInn = \trim((string) ($arCompany['OS_COMPANY_INN'] ?? ''));
            $leganInn = \trim((string) ($arCompany['LEGAN_ENTITY_INN'] ?? ''));
            $legalInn = \trim((string) ($arCompany['LEGAL_ENTITY_INN'] ?? ''));

            $canonical = $osInn !== '' ? $osInn : ($leganInn !== '' ? $leganInn : $legalInn);
            if ($canonical === '') {
                return;
            }

            if ($osInn !== '' && $leganInn !== '' && $osInn !== $leganInn) {
                $arCompany['LEGAN_ENTITY_INN'] = $osInn;
            }
            if ($canonical !== $legalInn) {
                $arCompany['LEGAL_ENTITY_INN'] = $canonical;
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
            self::mirrorInnToLegalEntityProperty($arCompany);
            self::reconcileInnFieldsOnRead($arCompany);
            self::reconcilePhonesOnRead($arCompany);
        }

        /**
         * На чтении: LEGAN_MAIN_PHONE не должен дублировать мобильный, если OS_COMPANY_PHONE — рабочий.
         *
         * @param array<string, mixed> $arCompany
         */
        private static function reconcilePhonesOnRead(array &$arCompany): void
        {
            $main = \trim((string) ($arCompany['LEGAN_MAIN_PHONE'] ?? ''));
            $mobile = \trim((string) ($arCompany['LEGAN_MOBILE_PHONE'] ?? ''));
            $os = \trim((string) ($arCompany['OS_COMPANY_PHONE'] ?? ''));
            $legacy = \trim((string) ($arCompany['LEGAN_ENTITY_PHONE'] ?? ''));

            if ($main === '' && $mobile !== '') {
                if ($os !== '' && $os !== $mobile) {
                    $arCompany['LEGAN_MAIN_PHONE'] = $os;
                } elseif ($legacy !== '' && $legacy !== $mobile) {
                    $arCompany['LEGAN_MAIN_PHONE'] = $legacy;
                }
            }

            if ($main !== '' && $mobile !== '' && $main === $mobile) {
                if ($os !== '' && $os !== $mobile) {
                    $arCompany['LEGAN_MAIN_PHONE'] = $os;
                } elseif ($legacy !== '' && $legacy !== $mobile) {
                    $arCompany['LEGAN_MAIN_PHONE'] = $legacy;
                }
            }
        }

        /**
         * @param mixed $propRow элемент из {@see \CIBlockElement::GetProperties()}
         */
        private static function extractScalarFromIblockPropertyRow(mixed $propRow): mixed
        {
            if (!\is_array($propRow)) {
                return $propRow;
            }
            if (!\array_key_exists('VALUE', $propRow)) {
                return $propRow;
            }
            $v = $propRow['VALUE'];
            if (\is_array($v)) {
                return \count($v) > 0 ? $v[0] : null;
            }

            return $v;
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
         * При снятии публикации компании (в т.ч. через OS_IS_MARKETING_AGENT → ACTIVE=N) выключаем учётки из списков компании.
         *
         * @param list<int>|array<int|string> $siteUserIds
         */
        private static function deactivateCompanyStaffSiteUsers(array $siteUserIds): void
        {
            foreach ($siteUserIds as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue;
                }
                $user = new \CUser();
                $user->Update($uid, ['ACTIVE' => 'N']);
            }
        }

        /**
         * Входит ли значение OS_IS_MARKETING_AGENT в «да» для групп / ACTIVE (enum ИБ, boolean, CRM‑«да»).
         */
        private static function inboundMarketingAgentPayloadIsYes(mixed $raw): bool
        {
            if ($raw === null) {
                return false;
            }
            if (\is_bool($raw)) {
                return $raw;
            }
            $yesEnum = CompanyModuleConfig::COMPANY_IBLOCK_LIST_MARKETING_AGENT_YES_ENUM_ID;
            $u = self::unwrapCrmScalarForGroupId(\is_array($raw) ? $raw : $raw);
            if ($u !== null && $u !== '' && \is_scalar($u) && (int)(string)$u === $yesEnum) {
                return true;
            }

            return CrmInboundUfMap::marketingInboundSignalTrue($raw);
        }

        /**
         * ACTIVE элемента ИБ: явный `ACTIVE` в payload; иначе при наличии `OS_IS_MARKETING_AGENT` — Y/N по признаку рекламного агента.
         *
         * @param array<string, mixed> $params
         */
        private static function resolveCompanyElementActiveForInbound(array $params, string $fallbackActive): string
        {
            if (\array_key_exists('ACTIVE', $params)) {
                $a = $params['ACTIVE'];
                if (\is_bool($a)) {
                    return $a ? 'Y' : 'N';
                }
                $s = \is_scalar($a) ? \strtoupper(\trim((string)$a)) : '';

                return ($s === 'Y' || $s === '1' || $s === 'TRUE') ? 'Y' : 'N';
            }
            if (!\array_key_exists('OS_IS_MARKETING_AGENT', $params)) {
                return $fallbackActive;
            }
            $raw = $params['OS_IS_MARKETING_AGENT'];
            if (self::inboundMarketingAgentPayloadIsYes($raw)) {
                return 'Y';
            }
            if (\is_bool($raw) && !$raw) {
                return 'N';
            }
            if (CrmInboundUfMap::marketingInboundSignalFalse($raw)) {
                return 'N';
            }
            $u = self::unwrapCrmScalarForGroupId(\is_array($raw) ? $raw : $raw);
            if ($u !== null && $u !== '' && \is_scalar($u)) {
                $iv = (int)(string)$u;
                $yesEnum = CompanyModuleConfig::COMPANY_IBLOCK_LIST_MARKETING_AGENT_YES_ENUM_ID;
                if ($iv === $yesEnum) {
                    return 'Y';
                }
                if ($iv > 0) {
                    return 'N';
                }
            }

            return $fallbackActive;
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

            if ($out === []) {
                return [];
            }
            $set = [];
            foreach ($out as $id) {
                if ($id > 0) {
                    $set[(int) $id] = true;
                }
            }

            return \array_map('intval', \array_keys($set));
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
         * Чтение мультисвойства пользователей из ИБ напрямую (для scope-пересчёта скидок head+child).
         *
         * @return list<int>
         */
        private static function loadCompanyUserIdsFromIblock(int $companyElementId, string $propertyCode): array
        {
            if ($companyElementId <= 0) {
                return [];
            }
            $out = [];
            $rs = \CIBlockElement::GetProperty(
                CompanyModuleConfig::COMPANY_IBLOCK_ID,
                $companyElementId,
                [],
                ['CODE' => $propertyCode]
            );
            while ($row = $rs->GetNext()) {
                $v = $row['VALUE'] ?? null;
                if ($v === null || $v === '' || $v === false) {
                    continue;
                }
                $id = (int)$v;
                if ($id > 0) {
                    $out[] = $id;
                }
            }

            return self::normalizeCompanyUserIdsList($out);
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
         * Inbound UPDATE_COMPANY: COMPANY_ID / ID → OS_COMPANY_B24_ID; вложенный body из n8n.
         *
         * @param array<string, mixed> $params
         */
        private static function aliasInboundCompanyUpdateRequest(array &$params): void
        {
            if (
                !isset($params['ACTION'])
                && isset($params['body'])
                && \is_array($params['body'])
            ) {
                $inner = $params['body'];
                unset($params['body'], $params['headers'], $params['params'], $params['query'], $params['webhookUrl'], $params['executionMode']);
                foreach ($inner as $k => $v) {
                    $params[$k] = $v;
                }
            }

            $b24 = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24 !== '') {
                $params['OS_COMPANY_B24_ID'] = $b24;

                return;
            }
            foreach (['COMPANY_ID', 'ENTITY_ID'] as $aliasKey) {
                $candidate = self::normalizeIncomingCompanyB24Id($params[$aliasKey] ?? null);
                if ($candidate !== '') {
                    $params['OS_COMPANY_B24_ID'] = $candidate;
                    self::syncTrace('Company::aliasInboundCompanyUpdateRequest', [
                        'from' => $aliasKey,
                        'os_company_b24_id' => $candidate,
                    ]);

                    return;
                }
            }
        }

        /**
         * ID элемента компании в ИБ 23 из inbound: `SITE_IBLOCK_ELEMENT_ID` или UF `UF_CRM_1774915439581`.
         *
         * @param array<string, mixed> $params
         */
        private static function extractInboundSiteIblockElementIdFromParams(array &$params): int
        {
            if (\array_key_exists('SITE_IBLOCK_ELEMENT_ID', $params)) {
                $id = (int) self::extractCrmInboundScalarString($params['SITE_IBLOCK_ELEMENT_ID'])
                    ?: (int) $params['SITE_IBLOCK_ELEMENT_ID'];
                if ($id > 0) {
                    $params['SITE_IBLOCK_ELEMENT_ID'] = $id;

                    return $id;
                }
            }

            $uf = CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF;
            if (!\array_key_exists($uf, $params)) {
                return 0;
            }

            $raw = $params[$uf];
            unset($params[$uf]);
            $id = (int) (self::extractCrmInboundScalarString($raw) ?? '0');
            if ($id <= 0 && \is_scalar($raw)) {
                $id = (int) \trim((string) $raw);
            }
            if ($id <= 0) {
                return 0;
            }

            $params['SITE_IBLOCK_ELEMENT_ID'] = $id;
            self::syncTrace('Company::extractInboundSiteIblockElementIdFromParams', [
                'site_iblock_element_id' => $id,
            ]);

            return $id;
        }

        /**
         * @return array<string, mixed>|false
         */
        private function loadInboundCompanyRecord(int $siteElementId, string $b24Id)
        {
            if ($siteElementId > 0) {
                if (!\CModule::IncludeModule('iblock')) {
                    return false;
                }
                $row = \CIBlockElement::GetByID($siteElementId)->Fetch();
                if (
                    \is_array($row)
                    && (int) ($row['IBLOCK_ID'] ?? 0) === CompanyModuleConfig::COMPANY_IBLOCK_ID
                ) {
                    $loaded = $this->getCompany($siteElementId);
                    if (!empty($loaded['ID'])) {
                        self::syncTrace('Company::loadInboundCompanyRecord by SITE_IBLOCK_ELEMENT_ID', [
                            'element_id' => $siteElementId,
                        ]);

                        return $loaded;
                    }
                }
            }

            if ($b24Id !== '') {
                return $this->getCompanyByB24ID($b24Id);
            }

            return false;
        }

        /**
         * ID компании в CRM для исходящих вызовов API: в ИБ в свойстве `OS_COMPANY_B24_ID` хранится локальный ID элемента,
         * поэтому берём числовой **`CODE`** элемента (= внешний id); если кода нет — легаси: свойство, если оно не равно локальному ID.
         *
         * @param array<string, mixed> $company результат {@see getCompany()}
         */
        private static function resolveOutboundBitrix24CompanyId(int $elementId, array $company): int
        {
            $rs = \CIBlockElement::GetByID($elementId);
            $row = $rs ? $rs->Fetch() : null;
            $code = \trim((string) ($row['CODE'] ?? ''));
            if ($code !== '' && \ctype_digit($code)) {
                return (int) $code;
            }
            $prop = (int) ($company['OS_COMPANY_B24_ID'] ?? 0);
            if ($prop > 0 && $prop !== $elementId) {
                return $prop;
            }

            return 0;
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
         * Дополняет OS_COMPANY_USERS/LEGAN_ENTITY_USERS пользователями сайта,
         * найденными по CONTACT_IDS (ID контактов B24) через маппинг contact->user.
         *
         * @param array<string, mixed> $params
         * @param array<int|string, mixed> $contactIdsByIndex
         */
        private static function mergeCompanyUsersFromContactIdsMap(array &$params, array $contactIdsByIndex): void
        {
            if ($contactIdsByIndex === []) {
                return;
            }
            if (!isset($params['OS_COMPANY_USERS']) || !\is_array($params['OS_COMPANY_USERS'])) {
                $params['OS_COMPANY_USERS'] = self::normalizeCompanyUserIdsList($params['OS_COMPANY_USERS'] ?? []);
            }
            if (!isset($params['LEGAN_ENTITY_USERS']) || !\is_array($params['LEGAN_ENTITY_USERS'])) {
                $params['LEGAN_ENTITY_USERS'] = self::normalizeCompanyUserIdsList($params['LEGAN_ENTITY_USERS'] ?? []);
            }

            $set = [];
            foreach (self::normalizeCompanyUserIdsList($params['OS_COMPANY_USERS']) as $id) {
                $set[$id] = true;
            }
            foreach (self::normalizeCompanyUserIdsList($params['LEGAN_ENTITY_USERS']) as $id) {
                $set[$id] = true;
            }

            $user = new User();
            foreach ($contactIdsByIndex as $key => $rawContactId) {
                $siteUserId = self::resolveSiteUserIdForUpdateCompany($user, $rawContactId, $key, $contactIdsByIndex);
                if ($siteUserId > 0) {
                    $set[$siteUserId] = true;
                }
            }
            if ($set === []) {
                return;
            }

            $mergedSiteUserIds = \array_map('intval', \array_keys($set));
            $params['OS_COMPANY_USERS'] = $mergedSiteUserIds;
            $params['LEGAN_ENTITY_USERS'] = $mergedSiteUserIds;
        }

        /**
         * Входящий payload может содержать только OS_COMPANY_USERS или только LEGAN_ENTITY_USERS.
         * После разрешения CRM‑ID → b_user.ID нужно заполнить вторую ветку; иначе
         * {@see self::mirrorOsCompanyFieldsToLeganEntity()} перезапишет LEGAN значениями OS из merge с текущим ИБ.
         *
         * @param array<string, mixed> $params
         */
        private static function syncOsAndLeganCompanyUsersParamsWhenSingleSidePresent(array &$params): void
        {
            $hasOs = \array_key_exists('OS_COMPANY_USERS', $params);
            $hasLegan = \array_key_exists('LEGAN_ENTITY_USERS', $params);
            if ($hasOs && !$hasLegan) {
                $params['LEGAN_ENTITY_USERS'] = self::normalizeCompanyUserIdsList($params['OS_COMPANY_USERS'] ?? []);

                return;
            }
            if ($hasLegan && !$hasOs) {
                $params['OS_COMPANY_USERS'] = self::normalizeCompanyUserIdsList($params['LEGAN_ENTITY_USERS'] ?? []);
            }
        }

        /**
         * Пары OS_* / LEGAN_* из {@see self::mirrorOsCompanyFieldsToLeganEntity()}: если в payload только одна сторона,
         * дублируем до merge, иначе «текущее OS» из ИБ перезапишет пришедший LEGAN при mirror.
         *
         * @param array<string, mixed> $params
         */
        private static function syncOsAndLeganMirrorableCompanyFieldsParamsWhenSingleSidePresent(array &$params): void
        {
            $pairs = [
                ['OS_COMPANY_JUR_ADDRESS', 'LEGAN_ENTITY_ADRESS'],
                ['OS_COMPANY_ACTIVITY', 'LEGAN_ENTITY_ACTIVITY'],
                ['OS_COMPANY_IS_HEAD_OF_HOLDING', 'LEGAN_ENTITY_IS_HEAD_COMPANY'],
                ['OS_COMPANY_BOSS', 'LEGAN_ENTITY_BOSS'],
            ];
            foreach ($pairs as [$osKey, $leganKey]) {
                $hasOs = \array_key_exists($osKey, $params);
                $hasLegan = \array_key_exists($leganKey, $params);
                if ($hasOs && !$hasLegan) {
                    $params[$leganKey] = $params[$osKey];
                } elseif ($hasLegan && !$hasOs) {
                    $params[$osKey] = $params[$leganKey];
                }
            }
        }

        /**
         * Списки руководителей: ID контакта CRM → `b_user.ID` по {@see User::getUserIDByB24ID()} (`UF_BITRIX24_ID`).
         *
         * @param array<string, mixed> $params
         * @param array<int|string, mixed> $contactIdsMap
         */
        private static function resolveInboundCompanyBossListsFromCrmContactIds(array &$params, array $contactIdsMap): void
        {
            foreach (['OS_COMPANY_BOSS', 'LEGAN_ENTITY_BOSS'] as $code) {
                if (!isset($params[$code])) {
                    continue;
                }
                if (!\is_array($params[$code])) {
                    $params[$code] = [$params[$code]];
                }
                $user = new User();
                $resolved = [];
                foreach ($params[$code] as $key => $raw) {
                    $uid = self::resolveSiteUserIdForUpdateCompany($user, $raw, $key, $contactIdsMap);
                    if ($uid > 0) {
                        $resolved[] = $uid;
                    }
                }
                $params[$code] = self::normalizeCompanyUserIdsList($resolved);
            }
        }

        /**
         * Входящий LEGAN_ENTITY_FILE как путь `/upload/...` на портале CRM → данные для скачивания через OS_REQUSITES_FILE
         * и {@see URL_B24} (без записи сырой строки в свойство типа «Файл»).
         *
         * @param array<string, mixed> $params
         */
        private static function convertInboundLeganEntityFilePublicPathToOsRequisitesPayload(array &$params): void
        {
            self::applyRequisitesFileAliasForInput($params);
            if (!\array_key_exists('LEGAN_ENTITY_FILE', $params)) {
                return;
            }
            $raw = $params['LEGAN_ENTITY_FILE'];
            if (!\is_string($raw)) {
                return;
            }
            $srcPath = self::inboundLeganEntityFileRawToSafeUploadSrcPath($raw);
            if ($srcPath === null || $srcPath === '') {
                return;
            }
            $osVal = \array_key_exists('OS_REQUSITES_FILE', $params) ? $params['OS_REQUSITES_FILE'] : null;
            if ($osVal !== null && $osVal !== '') {
                return;
            }
            $params['OS_REQUSITES_FILE'] = ['SRC' => $srcPath];
            unset($params['LEGAN_ENTITY_FILE']);
            self::syncTrace('company.requisites_file.legan_public_path_routed_to_os', [
                'path_len' => \strlen($srcPath),
            ]);
        }

        /**
         * Строка вида `/upload/...` или полный URL того же хоста, что и {@see URL_B24}, с тем же путём.
         */
        private static function inboundLeganEntityFileRawToSafeUploadSrcPath(string $raw): ?string
        {
            $t = \trim($raw);
            if ($t === '') {
                return null;
            }
            if (self::isSafeCrmPublicUploadSrc($t)) {
                return $t;
            }
            if (!\preg_match('#^https?://#i', $t)) {
                return null;
            }
            $parts = \parse_url($t);
            if ($parts === false || empty($parts['path']) || !\is_string($parts['path'])) {
                return null;
            }
            $path = $parts['path'];
            if (!self::isSafeCrmPublicUploadSrc($path)) {
                return null;
            }
            if (\defined('URL_B24')) {
                $wantBase = \rtrim((string)\constant('URL_B24'), '/');
                $wantHost = \parse_url($wantBase !== '' ? $wantBase : '', PHP_URL_HOST);
                $gotHost = $parts['host'] ?? '';
                if (\is_string($wantHost) && $wantHost !== '' && \is_string($gotHost) && $gotHost !== ''
                    && \strcasecmp($wantHost, $gotHost) !== 0) {
                    return null;
                }
            }

            return $path;
        }

        /**
         * Безопасная сборка URL сегментов пути (кириллица и пробелы в имени файла).
         */
        private static function buildPortalUploadFileDownloadUrl(string $base, string $srcPath): string
        {
            $base = \rtrim($base, '/');
            $srcPath = \str_replace('\\', '/', $srcPath);
            if ($srcPath === '') {
                return $base;
            }
            if (!\str_starts_with($srcPath, '/')) {
                $srcPath = '/' . $srcPath;
            }
            $trimmed = \trim($srcPath, '/');
            if ($trimmed === '') {
                return $base;
            }
            $segments = \explode('/', $trimmed);
            $encoded = [];
            foreach ($segments as $seg) {
                if ($seg === '') {
                    continue;
                }
                $encoded[] = \rawurlencode($seg);
            }

            return $base . '/' . \implode('/', $encoded);
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
         * Входящие `true`/CRM‑«да» для свойств типа «список» (чекбокс) → `{VALUE: enum_id}` для ИБ.
         *
         * @param array<string, mixed> $arProps
         */
        private static function normalizeInboundCrmCheckboxBooleansToListEnums(array &$arProps): void
        {
            $yesMarketing = CompanyModuleConfig::COMPANY_IBLOCK_LIST_MARKETING_AGENT_YES_ENUM_ID;
            $yesHead = CompanyModuleConfig::COMPANY_IBLOCK_LIST_HEAD_COMPANY_YES_ENUM_ID;

            if (\array_key_exists('OS_IS_MARKETING_AGENT', $arProps)) {
                $v = $arProps['OS_IS_MARKETING_AGENT'];
                if ($v === true || CrmInboundUfMap::marketingInboundSignalTrue($v)) {
                    $arProps['OS_IS_MARKETING_AGENT'] = ['VALUE' => $yesMarketing];
                }
            }
            foreach (['OS_COMPANY_IS_HEAD_OF_HOLDING', 'LEGAN_ENTITY_IS_HEAD_COMPANY'] as $code) {
                if (!\array_key_exists($code, $arProps)) {
                    continue;
                }
                $v = $arProps[$code];
                if ($v === true || CrmInboundUfMap::marketingInboundSignalTrue($v)) {
                    $arProps[$code] = ['VALUE' => $yesHead];
                }
            }
        }

        /**
         * Консистентность "голова холдинга" ↔ "дочерняя" для inbound UPDATE_COMPANY.
         *
         * Инварианты:
         * - Компания не может быть одновременно "головной" (OS_COMPANY_IS_HEAD_OF_HOLDING / LEGAN_ENTITY_IS_HEAD_COMPANY)
         *   и иметь ссылку на голову (OS_HOLDING_OF / LEGAN_ENTITY_ID_OF_HEAD_COMPANY).
         * - Явный `false` из CRM должен очищать галочку, а не оставлять старое значение из ИБ.
         *
         * @param array<string, mixed> $params
         */
        private static function enforceInboundHoldingHeadConsistency(array &$params): void
        {
            $incomingOsHoldingOf = $params['OS_HOLDING_OF'] ?? null;
            $incomingLeganHeadId = $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] ?? null;

            $hasHolding = !empty($incomingOsHoldingOf) || !empty($incomingLeganHeadId);

            $rawHead = null;
            $hasExplicitHeadSignal = false;
            if (\array_key_exists('OS_COMPANY_IS_HEAD_OF_HOLDING', $params)) {
                $rawHead = $params['OS_COMPANY_IS_HEAD_OF_HOLDING'];
                $hasExplicitHeadSignal = true;
            } elseif (\array_key_exists('LEGAN_ENTITY_IS_HEAD_COMPANY', $params)) {
                $rawHead = $params['LEGAN_ENTITY_IS_HEAD_COMPANY'];
                $hasExplicitHeadSignal = true;
            }

            $isHeadTrue = ($rawHead === true || CrmInboundUfMap::marketingInboundSignalTrue($rawHead));
            $isHeadFalse = ($rawHead === false || $rawHead === 0 || $rawHead === '0' || $rawHead === '' || $rawHead === null);

            // Если задана ссылка на голову — принудительно снимаем "голову"
            if ($hasHolding) {
                $params['OS_COMPANY_IS_HEAD_OF_HOLDING'] = '';
                $params['LEGAN_ENTITY_IS_HEAD_COMPANY'] = '';
                self::syncTrace('company.inbound.holding_head.consistency', [
                    'rule' => 'has_holding_clears_head',
                    'incoming' => [
                        'OS_HOLDING_OF' => $incomingOsHoldingOf,
                        'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $incomingLeganHeadId,
                        'head_signal_raw' => $rawHead,
                    ],
                    'final' => [
                        'OS_COMPANY_IS_HEAD_OF_HOLDING' => $params['OS_COMPANY_IS_HEAD_OF_HOLDING'],
                        'LEGAN_ENTITY_IS_HEAD_COMPANY' => $params['LEGAN_ENTITY_IS_HEAD_COMPANY'],
                    ],
                ]);
                return;
            }

            // Если пришёл явный false — тоже чистим
            if ($hasExplicitHeadSignal && $isHeadFalse && !$isHeadTrue) {
                $params['OS_COMPANY_IS_HEAD_OF_HOLDING'] = '';
                $params['LEGAN_ENTITY_IS_HEAD_COMPANY'] = '';
                self::syncTrace('company.inbound.holding_head.consistency', [
                    'rule' => 'explicit_false_clears_head',
                    'incoming' => [
                        'OS_HOLDING_OF' => $incomingOsHoldingOf,
                        'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $incomingLeganHeadId,
                        'head_signal_raw' => $rawHead,
                    ],
                    'final' => [
                        'OS_COMPANY_IS_HEAD_OF_HOLDING' => $params['OS_COMPANY_IS_HEAD_OF_HOLDING'],
                        'LEGAN_ENTITY_IS_HEAD_COMPANY' => $params['LEGAN_ENTITY_IS_HEAD_COMPANY'],
                    ],
                ]);
                return;
            }

            // Если пришёл "true" — очищаем ссылку на голову (если вдруг притащили в payload)
            if ($hasExplicitHeadSignal && $isHeadTrue) {
                if (\array_key_exists('OS_HOLDING_OF', $params)) {
                    $params['OS_HOLDING_OF'] = '';
                }
                if (\array_key_exists('LEGAN_ENTITY_ID_OF_HEAD_COMPANY', $params)) {
                    $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] = '';
                }
                self::syncTrace('company.inbound.holding_head.consistency', [
                    'rule' => 'explicit_true_clears_holding',
                    'incoming' => [
                        'OS_HOLDING_OF' => $incomingOsHoldingOf,
                        'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $incomingLeganHeadId,
                        'head_signal_raw' => $rawHead,
                    ],
                    'final' => [
                        'OS_HOLDING_OF' => $params['OS_HOLDING_OF'] ?? null,
                        'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $params['LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] ?? null,
                    ],
                ]);
            }
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
         * Пустое значение (в т.ч. после `DISCOUNT_GROUP: null` в JSON → `''` в params) — только снятие скидочных групп.
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
            $removeResult = null;

            if ($touchDiscountGroups) {
                $removeResult = $user->removeUserFromGroupsByIds($userId, self::getCompanyDiscountAssignedGroupIds());
            }

            $groups = [];
            if (self::inboundMarketingAgentPayloadIsYes($params['OS_IS_MARKETING_AGENT'] ?? null)) {
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
         * Одно значение признака «головная компания холдинга» (после снятия одной оболочки VALUE у свойства ИБ).
         */
        private static function isTruthyHeadOfHoldingLeafValue(mixed $v): bool
        {
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
         * Головная компания холдинга: {@see OS_COMPANY_IS_HEAD_OF_HOLDING} или зеркало {@see LEGAN_ENTITY_IS_HEAD_COMPANY}.
         *
         * @param array<string, mixed> $companyUpdateParams
         */
        private static function isHeadOfHoldingFromCompanyParams(array $companyUpdateParams): bool
        {
            foreach (['OS_COMPANY_IS_HEAD_OF_HOLDING', 'LEGAN_ENTITY_IS_HEAD_COMPANY'] as $key) {
                $v = $companyUpdateParams[$key] ?? null;
                if (\is_array($v)) {
                    $v = $v['VALUE'] ?? $v['~VALUE'] ?? null;
                }
                if (self::isTruthyHeadOfHoldingLeafValue($v)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Распространение скидки головы на дочерние (`OS_HOLDING_OF` / `LEGAN_ENTITY_ID_OF_HEAD_COMPANY`) — только если текущая карточка — голова холдинга.
         *
         * @param array<string, mixed> $existingCompany карточка из ИБ до merge payload (поля из {@see getCompany} / {@see getCompanyByB24ID})
         */
        private static function isHeadCompanyForDiscountSync(array $params, array $existingCompany): bool
        {
            if (self::isHeadOfHoldingFromCompanyParams($params)) {
                return true;
            }

            $fromIblock = [];
            foreach (['OS_COMPANY_IS_HEAD_OF_HOLDING', 'LEGAN_ENTITY_IS_HEAD_COMPANY'] as $k) {
                if (\array_key_exists($k, $existingCompany)) {
                    $fromIblock[$k] = $existingCompany[$k];
                }
            }

            return self::isHeadOfHoldingFromCompanyParams($fromIblock);
        }

        /**
         * B24-контакт, «альтернативный» контакт из CONTACT_IDS по индексу, все CONTACT_IDS, либо уже **b_user.ID**
         * (если в запросе нет CONTACT_IDS и число совпадает с существующей учёткой — типично для LEGAN_ENTITY_USERS из UF сайта в CRM).
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
            $existsInContactIds = false;
            foreach ($contactIdsByIndex as $cRaw) {
                if (self::normalizeIncomingContactB24Id($cRaw) === $b24) {
                    $existsInContactIds = true;
                    break;
                }
            }
            if (!$existsInContactIds) {
                $uRow = \CUser::GetByID($b24)->Fetch();
                if (\is_array($uRow) && (int) ($uRow['ID'] ?? 0) === $b24) {
                    $expectedContactB24Id = 0;
                    if (\array_key_exists($key, $contactIdsByIndex)) {
                        $expectedContactB24Id = self::normalizeIncomingContactB24Id($contactIdsByIndex[$key]);
                    } elseif (\array_key_exists(0, $contactIdsByIndex)) {
                        $expectedContactB24Id = self::normalizeIncomingContactB24Id($contactIdsByIndex[0]);
                    }
                    if ($expectedContactB24Id > 0) {
                        if (self::doesUserHaveExpectedB24ContactId($uRow, $expectedContactB24Id)) {
                            return $b24;
                        }
                    } elseif ($contactIdsByIndex === []) {
                        return (int) $b24;
                    }
                }
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
            return 0;
        }

        /**
         * @param array<string, mixed> $userRow
         */
        private static function doesUserHaveExpectedB24ContactId(array $userRow, int $expectedContactB24Id): bool
        {
            if ($expectedContactB24Id <= 0) {
                return false;
            }
            foreach ([UserSyncConfig::USER_UF_CONTACT_B24_ID, UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY] as $ufField) {
                if (!\array_key_exists($ufField, $userRow)) {
                    continue;
                }
                $raw = $userRow[$ufField];
                if (\is_array($raw)) {
                    $raw = $raw['VALUE'] ?? $raw['~VALUE'] ?? null;
                }
                if ($raw === null || $raw === '' || $raw === false) {
                    continue;
                }
                if ((int) (string) $raw === $expectedContactB24Id) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Пустое значение скидки во входящем payload (в т.ч. `DISCOUNT_GROUP: null` → `''` в `OS_COMPANY_DISCOUNT_VALUE`).
         */
        private static function isCompanyDiscountRawSemanticallyEmpty(mixed $raw): bool
        {
            if ($raw === null || $raw === '' || $raw === false) {
                return true;
            }
            $u = self::unwrapCrmScalarForGroupId($raw);

            return $u === null || $u === '' || $u === false;
        }

        /**
         * Явный сброс скидки компании из CRM: в payload передано пустое значение скидки (ключ есть).
         *
         * @param array<string, mixed> $params
         */
        private static function isExplicitCompanyDiscountClear(array $params): bool
        {
            if (!\array_key_exists('OS_COMPANY_DISCOUNT_VALUE', $params)) {
                return false;
            }

            return self::isCompanyDiscountRawSemanticallyEmpty($params['OS_COMPANY_DISCOUNT_VALUE']);
        }

        /**
         * Все `b_user.ID` из свойств `OS_COMPANY_USERS` и `LEGAN_ENTITY_USERS` элемента компании.
         *
         * @return list<int>
         */
        private static function collectSiteUserIdsAttachedToCompanyElement(int $companyElementId): array
        {
            $acc = [];
            foreach (['OS_COMPANY_USERS', 'LEGAN_ENTITY_USERS'] as $code) {
                foreach (self::loadCompanyUserIdsFromIblock($companyElementId, $code) as $uid) {
                    $uid = (int) $uid;
                    if ($uid > 0) {
                        $acc[$uid] = true;
                    }
                }
            }

            return \array_map('intval', \array_keys($acc));
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
            if (self::isCompanyDiscountRawSemanticallyEmpty($raw)) {
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

        /**
         * Ключи `registration_webhook_*` в `eklektika.sync/config.local.php` (и `EKLEKTIKA_SYNC_CONFIG`) —
         * тот же словарь, что у {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator}.
         *
         * @return string пустая строка, если метод не мапится на именованный n8n-вебхук (тогда только {@see RestClient}).
         */
        private static function registrationCrmRestWebhookConfigKeyForOutbound(string $method): string
        {
            static $map = [
                'crm.company.get' => 'registration_webhook_crm_company_get_url',
                'crm.company.update' => 'registration_webhook_crm_company_update_url',
                'crm.contact.company.add' => 'registration_webhook_crm_contact_company_add_url',
                'crm.company.contact.add' => 'registration_webhook_crm_company_contact_add_url',
                'crm.requisite.list' => 'registration_webhook_crm_requisite_list_url',
                'crm.requisite.update' => 'registration_webhook_crm_requisite_update_url',
                'crm.requisite.add' => 'registration_webhook_crm_requisite_add_url',
                'crm.contact.list' => 'registration_webhook_crm_contact_list_url',
                'crm.contact.get' => 'registration_webhook_crm_contact_get_url',
                'crm.contact.update' => 'registration_webhook_crm_contact_update_url',
                'crm.company.add' => 'registration_webhook_company_add_url',
                'crm.contact.add' => 'registration_webhook_contact_add_url',
            ];

            return $map[$method] ?? '';
        }

        /**
         * Исходящий `crm.*` через явный ключ `registration_webhook_*` в sync-конфиге (без маппинга по имени метода).
         *
         * @param string $registrationConfigKey например `registration_webhook_crm_requisite_list_url`
         * @param string $crmMethod тело JSON: METHOD / PARAMS (как в n8n)
         */
        private static function callB24RegistrationWebhook(string $registrationConfigKey, string $crmMethod, array $params, bool $debug = false)
        {
            $configKey = \trim($registrationConfigKey);
            if ($configKey !== '' && \class_exists(CrmRegistrationN8nTransport::class)) {
                $webhookUrl = \trim((string) CrmRegistrationN8nTransport::resolveRegistrationWebhookUrl($configKey));
                if ($webhookUrl !== '') {
                    $result = N8nCrmGateway::callRestMethodWithWebhookUrl(
                        $webhookUrl,
                        $crmMethod,
                        $params,
                        $debug,
                        CrmRegistrationN8nTransport::resolveRegistrationWebhookB24Prefix($configKey)
                    );
                    if (
                        \is_array($result)
                        && \array_key_exists('success', $result)
                        && (int) $result['success'] === 0
                        && \preg_match('/^HTTP Error:\s*404\b/', \trim((string) ($result['error'] ?? '')))
                    ) {
                        self::syncTrace('company.b24.registration_webhook_404_fallback', [
                            'method' => $crmMethod,
                            'config_key' => $configKey,
                            'webhook_url' => $webhookUrl,
                        ]);

                        return RestClient::callRestMethod($crmMethod, $params, $debug);
                    }

                    return $result;
                }
            }

            return RestClient::callRestMethod($crmMethod, $params, $debug);
        }

        /**
         * Исходящий `crm.*`: n8n по маппингу метода → ключ `registration_webhook_*` ({@see registrationCrmRestWebhookConfigKeyForOutbound}).
         * Для явного ключа конфига используйте {@see callB24RegistrationWebhook}.
         */
        private static function callB24Method(string $method, array $params, bool $debug = false)
        {
            $configKey = self::registrationCrmRestWebhookConfigKeyForOutbound($method);
            if ($configKey === '') {
                return RestClient::callRestMethod($method, $params, $debug);
            }

            return self::callB24RegistrationWebhook($configKey, $method, $params, $debug);
        }

        /**
         * Значение UF типа «файл» для crm.company.update / crm.company.add: {@see https://github.com/bitrix-tools/b24-rest-docs/blob/main/api-reference/files/how-to-upload-files.md}
         *
         * @return array{fileData: list{0: string, 1: string}}|null
         */
        private static function buildCrmFileFieldFileDataFromBitrixFileId(int $fileId): ?array
        {
            if ($fileId <= 0) {
                return null;
            }
            $fileInfo = \CFile::GetFileArray($fileId);
            if (!$fileInfo || !\is_array($fileInfo)) {

                return null;
            }
            $docRoot = \rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
            if ($docRoot === '' && \class_exists(\Bitrix\Main\Application::class)) {
                $docRoot = \rtrim((string) \Bitrix\Main\Application::getInstance()->getDocumentRoot(), '/\\');
            }
            $path = '';
            $relPath = \CFile::GetPath($fileId);
            if (\is_string($relPath) && $relPath !== '') {
                $path = $docRoot . '/' . \ltrim($relPath, '/');
            }
            if ($path === '' || !\is_readable($path)) {
                $subdir = \trim((string) ($fileInfo['SUBDIR'] ?? ''), '/');
                $fname = (string) ($fileInfo['FILE_NAME'] ?? '');
                if ($subdir !== '' && $fname !== '' && $docRoot !== '') {
                    $path = $docRoot . '/' . $subdir . '/' . $fname;
                }
            }
            if ($path === '' || !\is_readable($path)) {

                return null;
            }
            $binary = @\file_get_contents($path);
            if ($binary === false || $binary === '') {

                return null;
            }
            $rawName = (string) ($fileInfo['ORIGINAL_NAME'] ?? $fileInfo['FILE_NAME'] ?? '');
            $sanitized = self::sanitizeRequisitesOriginalFileName($rawName);
            $origName = \is_string($sanitized) ? $sanitized : '';
            if ($origName === '') {
                $origName = 'document.pdf';
            }

            return [
                'fileData' => [
                    $origName,
                    \base64_encode($binary),
                ],
            ];
        }

        /**
         * «Сырой» result после n8n / {@see N8nCrmGateway::callRestMethodWithWebhookUrl} для crm.requisite.list —
         * либо список строк, либо обёртка Bitrix `{ result: [...] }`.
         *
         * @param mixed $raw
         *
         * @return list<array<string, mixed>>
         */
        private static function normalizeCrmRequisiteListRowsFromTransport($raw): array
        {
            if (!\is_array($raw)) {
                return [];
            }
            // Полный ответ Bitrix: { result: [...], total, time } — иногда приходит как value result у n8n.
            while (
                isset($raw['result'])
                && \is_array($raw['result'])
                && !isset($raw[0])
                && isset($raw['result']['result'])
                && \is_array($raw['result']['result'])
            ) {
                $raw = $raw['result'];
            }
            if (isset($raw['result']) && \is_array($raw['result'])) {
                $rows = $raw['result'];
                if ($rows === []) {
                    return [];
                }
                if (isset($rows[0]) && \is_array($rows[0])) {
                    return \array_values($rows);
                }
                if (isset($rows['ID']) || isset($rows['ENTITY_ID'])) {
                    return [$rows];
                }

                return \array_values(\array_filter($rows, static function ($row) {
                    return \is_array($row);
                }));
            }
            if ($raw === []) {
                return [];
            }
            $keys = \array_keys($raw);
            $isList = $keys === [] || $keys === \range(0, \count($raw) - 1);
            if ($isList) {
                return \array_values(\array_filter($raw, static function ($row) {
                    return \is_array($row);
                }));
            }
            if (isset($raw['ID']) || isset($raw['ENTITY_ID'])) {
                return [$raw];
            }

            return [];
        }

        /**
         * ID реквизита для обновления ИНН/названия: только привязка к компании в CRM, без сопоставления с новым ИНН из формы
         * (в CRM до update ещё старый RQ_INN).
         *
         * @param list<array<string, mixed>> $rows
         */
        private static function resolvePrimaryCompanyRequisiteId(array $rows, int $companyB24Id): int
        {
            if ($companyB24Id <= 0) {
                return 0;
            }
            $candidates = [];
            foreach ($rows as $row) {
                if (!\is_array($row)) {
                    continue;
                }
                $eid = (int) ($row['ENTITY_ID'] ?? 0);
                $etype = (int) ($row['ENTITY_TYPE_ID'] ?? 0);
                if ((string) ($row['ENTITY_TYPE_ID'] ?? '') === '4') {
                    $etype = 4;
                }
                // Список уже с filter ENTITY_TYPE_ID=4 & ENTITY_ID=company; в select иногда не приходит ENTITY_TYPE_ID — не отбрасывать строку из‑за 0.
                $typeOk = ($etype === 4 || $etype === 0 || (string) ($row['ENTITY_TYPE_ID'] ?? '') === '4');
                if ($eid !== $companyB24Id || !$typeOk) {
                    continue;
                }
                $id = (int) ($row['ID'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $candidates[] = [
                    'id' => $id,
                    'preset' => (int) ($row['PRESET_ID'] ?? 0),
                ];
            }
            if ($candidates === []) {
                return 0;
            }
            foreach ($candidates as $c) {
                if ($c['preset'] === 1) {
                    return $c['id'];
                }
            }
            \usort($candidates, static function (array $a, array $b): int {
                return $a['id'] <=> $b['id'];
            });

            return (int) ($candidates[0]['id'] ?? 0);
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

            self::aliasInboundCompanyUpdateRequest($params);
            self::mapCrmCompanyPayloadUfToSiteProperties($params);
            self::syncOsAndLeganMirrorableCompanyFieldsParamsWhenSingleSidePresent($params);
            // До записи: устраняем противоречия "голова" vs "дочерняя" и фиксируем явный false.
            self::enforceInboundHoldingHeadConsistency($params);

            $b24CompanyId = self::normalizeIncomingCompanyB24Id($params['OS_COMPANY_B24_ID'] ?? null);
            if ($b24CompanyId === '') {
                return false;
            }
            $params['OS_COMPANY_B24_ID'] = $b24CompanyId;

            self::convertInboundLeganEntityFilePublicPathToOsRequisitesPayload($params);
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
                $set = [];
                foreach (self::normalizeCompanyUserIdsList($existingCompany['OS_COMPANY_USERS'] ?? []) as $uid) {
                    $set[$uid] = true;
                }
                $set[$addUserId] = true;
                $currentUsers = \array_map('intval', \array_keys($set));

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
                self::normalizeInboundCrmCheckboxBooleansToListEnums($propBag);
                self::normalizeInboundCrmListPropertyValuesForIblock($propBag);

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
         * Явная запись витринных телефонов LEGAN через {@see \CIBlockElement::SetPropertyValues} (IBLOCK_ID).
         * Вызывается после inbound {@see \CIBlockElement::Update}(PROPERTY_VALUES) и в конце {@see Company::updateCompanyProfile}.
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyLeganPhonePropertyValuesToElement(int $elementId, array $arProps): void
        {
            self::applyCompanyPhonePropertiesToElement($elementId, $arProps);
        }

        /**
         * Явная запись ИНН (OS + витрина LEGAN / LEGAL) через {@see \CIBlockElement::SetPropertyValues}.
         * На части стендов {@see \CIBlockElement::SetPropertyValueCode} не сохраняет string-свойства ИБ 23.
         *
         * @param array<string, mixed> $arProps
         */
        private static function applyLeganInnPropertyValuesToElement(int $elementId, array $arProps): void
        {
            $inn = null;
            if (\array_key_exists('LEGAN_ENTITY_INN', $arProps)) {
                $inn = \trim((string) $arProps['LEGAN_ENTITY_INN']);
            } elseif (\array_key_exists('OS_COMPANY_INN', $arProps)) {
                $inn = \trim((string) $arProps['OS_COMPANY_INN']);
            } elseif (\array_key_exists('LEGAL_ENTITY_INN', $arProps)) {
                $inn = \trim((string) $arProps['LEGAL_ENTITY_INN']);
            }
            if ($inn === null) {
                return;
            }

            foreach (['OS_COMPANY_INN', 'LEGAN_ENTITY_INN', 'LEGAL_ENTITY_INN'] as $code) {
                self::writeCompanyIblockScalarProperty($elementId, $code, $inn);
            }
        }

        /**
         * n8n: `CRM_MULTIFIELDS` → плоские `PHONE` / `EMAIL` / `WEB` (как в REST crm.company).
         *
         * @param array<string, mixed> $params
         */
        private static function expandInboundCrmMultifieldsEnvelope(array &$params): void
        {
            if (!isset($params['CRM_MULTIFIELDS']) || !\is_array($params['CRM_MULTIFIELDS'])) {
                return;
            }
            $envelope = $params['CRM_MULTIFIELDS'];
            unset($params['CRM_MULTIFIELDS']);
            foreach (['PHONE', 'EMAIL', 'WEB'] as $mfKey) {
                if (!isset($envelope[$mfKey]) || isset($params[$mfKey])) {
                    continue;
                }
                $params[$mfKey] = self::normalizeInboundCrmMultifieldRows($envelope[$mfKey]);
            }
        }

        /**
         * Один элемент multifield или список → list<array{VALUE:mixed,VALUE_TYPE?:string,...}>.
         *
         * @return list<array<string, mixed>>
         */
        private static function normalizeInboundCrmMultifieldRows(mixed $raw): array
        {
            if (!\is_array($raw)) {
                return [];
            }
            if (isset($raw['VALUE']) || isset($raw['value'])) {
                return [$raw];
            }
            $out = [];
            foreach ($raw as $row) {
                if (\is_array($row) && (isset($row['VALUE']) || isset($row['value']))) {
                    $out[] = $row;
                }
            }

            return $out;
        }

        /**
         * Дополняет `PHONE[]` рабочим/мобильным из UF (`UF_CRM_1777069666894` / `UF_CRM_1777069676348`),
         * если n8n положил в `CRM_MULTIFIELDS.PHONE` только одну запись.
         *
         * @param array<string, mixed> $params
         */
        private static function mergeInboundCompanyPhoneUfsIntoPhoneMultifield(array &$params): void
        {
            $rows = isset($params['PHONE']) ? self::normalizeInboundCrmMultifieldRows($params['PHONE']) : [];
            $workUf = \trim((string) ($params[CrmInboundUfMap::COMPANY_CRM_MAIN_PHONE_UF] ?? ''));
            $mobileUf = \trim((string) ($params[CrmInboundUfMap::COMPANY_CRM_MOBILE_PHONE_UF] ?? ''));
            $hasWork = false;
            $hasMobile = false;
            foreach ($rows as $row) {
                $type = \strtoupper(\trim((string) ($row['VALUE_TYPE'] ?? $row['value_type'] ?? 'WORK')));
                if ($type === 'MOBILE') {
                    $hasMobile = true;
                } else {
                    $hasWork = true;
                }
            }
            if ($workUf !== '' && !$hasWork) {
                $rows[] = ['VALUE' => $workUf, 'VALUE_TYPE' => 'WORK'];
            }
            if ($mobileUf !== '' && !$hasMobile) {
                $rows[] = ['VALUE' => $mobileUf, 'VALUE_TYPE' => 'MOBILE'];
            }
            if ($rows !== []) {
                $params['PHONE'] = $rows;
            }
        }

        /**
         * Inbound `UPDATE_COMPANY` (Bitrix24): `UF_CRM_*` в карточке crm.company → поля элемента ИБ 23.
         * `EMAIL` из payload (как в REST) → `OS_COMPANY_EMAIL` + `LEGAN_ENTITY_EMAIL`.
         *
         * @param array<string, mixed> $params
         */
        private static function mapInboundCrmPhoneMultifieldToSiteProperties(array &$params): void
        {
            $phoneRows = isset($params['PHONE']) ? self::normalizeInboundCrmMultifieldRows($params['PHONE']) : [];
            if ($phoneRows === []) {
                return;
            }
            $work = '';
            $mobile = '';
            foreach ($phoneRows as $row) {
                if (!\is_array($row)) {
                    continue;
                }
                if (!empty($row['DELETE']) || !empty($row['delete'])) {
                    continue;
                }
                $v = \trim((string) ($row['VALUE'] ?? ''));
                if ($v === '') {
                    continue;
                }
                $type = \strtoupper(\trim((string) ($row['VALUE_TYPE'] ?? $row['value_type'] ?? 'WORK')));
                if ($type === 'MOBILE') {
                    if ($mobile === '') {
                        $mobile = $v;
                    }
                } elseif ($work === '') {
                    $work = $v;
                }
            }
            unset($params['PHONE']);
            if ($work !== '') {
                $params['LEGAN_MAIN_PHONE'] = $work;
            }
            if ($mobile !== '') {
                $params['LEGAN_MOBILE_PHONE'] = $mobile;
            }
            if ($work !== '') {
                $params['OS_COMPANY_PHONE'] = $work;
            }
        }

        /**
         * UF телефонов — только если в payload нет CRM_MULTIFIELDS.PHONE[].
         *
         * @param array<string, mixed> $params
         */
        private static function mapInboundCrmPhoneUfFallbackOnly(array &$params): void
        {
            $pairs = [
                CrmInboundUfMap::COMPANY_CRM_MAIN_PHONE_UF => 'LEGAN_MAIN_PHONE',
                CrmInboundUfMap::COMPANY_CRM_MOBILE_PHONE_UF => 'LEGAN_MOBILE_PHONE',
            ];
            foreach ($pairs as $ufK => $siteK) {
                if (!\array_key_exists($ufK, $params)) {
                    continue;
                }
                $raw = $params[$ufK];
                unset($params[$ufK]);
                if (\array_key_exists($siteK, $params) && \trim((string) $params[$siteK]) !== '') {
                    continue;
                }
                $str = self::extractCrmInboundScalarString($raw);
                if ($str !== null) {
                    $params[$siteK] = $str;
                }
            }
        }

        private static function mapCrmCompanyPayloadUfToSiteProperties(array &$params): void
        {
            self::extractInboundSiteIblockElementIdFromParams($params);
            self::expandInboundCrmMultifieldsEnvelope($params);
            self::mergeInboundCompanyPhoneUfsIntoPhoneMultifield($params);

            $discountClearedByInboundDiscountGroupAlias = false;

            if (\array_key_exists(CrmInboundUfMap::COMPANY_IS_HEAD_OF_HOLDING_UF, $params)) {
                $raw = $params[CrmInboundUfMap::COMPANY_IS_HEAD_OF_HOLDING_UF];
                unset($params[CrmInboundUfMap::COMPANY_IS_HEAD_OF_HOLDING_UF]);
                if (\is_bool($raw)) {
                    $params['OS_COMPANY_IS_HEAD_OF_HOLDING'] = $raw;
                } else {
                    $str = self::extractCrmInboundScalarString($raw);
                    if ($str !== null) {
                        $params['OS_COMPANY_IS_HEAD_OF_HOLDING'] = $str;
                    }
                }
            }

            if (\array_key_exists(CrmInboundUfMap::COMPANY_IS_ADVERTISING_AGENT_UF, $params)) {
                $raw = $params[CrmInboundUfMap::COMPANY_IS_ADVERTISING_AGENT_UF];
                unset($params[CrmInboundUfMap::COMPANY_IS_ADVERTISING_AGENT_UF]);
                if (\is_bool($raw)) {
                    $params['OS_IS_MARKETING_AGENT'] = $raw;
                } elseif (\is_int($raw) || \is_float($raw)) {
                    $params['OS_IS_MARKETING_AGENT'] = $raw;
                } else {
                    $str = self::extractCrmInboundScalarString($raw);
                    if ($str !== null) {
                        $params['OS_IS_MARKETING_AGENT'] = $str;
                    }
                }
            }

            if (\array_key_exists(CrmInboundUfMap::COMPANY_INBOUND_DISCOUNT_GROUP_ALIAS, $params)) {
                $rawDg = $params[CrmInboundUfMap::COMPANY_INBOUND_DISCOUNT_GROUP_ALIAS];
                $dg = self::unwrapCrmScalarForGroupId($rawDg);
                unset($params[CrmInboundUfMap::COMPANY_INBOUND_DISCOUNT_GROUP_ALIAS]);
                if (!\array_key_exists('OS_COMPANY_DISCOUNT_VALUE', $params)) {
                    // null в inbound (например из JSON `DISCOUNT_GROUP: null`) трактуем как «поля нет»
                    // и **не трогаем** скидочные группы сотрудников (частичный UPDATE_COMPANY).
                    // Явный сброс скидки должен приходить как пустая строка/0 или через `OS_COMPANY_DISCOUNT_VALUE`.
                    $rawNullLike = ($rawDg === null)
                        || (\is_array($rawDg) && \array_key_exists('VALUE', $rawDg) && $rawDg['VALUE'] === null)
                        || (\is_array($rawDg) && !\array_key_exists('VALUE', $rawDg) && \reset($rawDg) === null);
                    if ($rawNullLike) {
                        // nothing
                    } elseif ($dg !== null && $dg !== '') {
                        $params['OS_COMPANY_DISCOUNT_VALUE'] = $dg;
                    } else {
                        // Явный сброс скидки в CRM (`DISCOUNT_GROUP: null` / пусто) — триггер для снятия скидочных групп у сотрудников
                        $params['OS_COMPANY_DISCOUNT_VALUE'] = '';
                        $discountClearedByInboundDiscountGroupAlias = true;
                    }
                }
            }

            $m = [
                CrmInboundUfMap::COMPANY_CRM_CITY_UF => 'OS_COMPANY_CITY',
                CrmInboundUfMap::COMPANY_CRM_WEB_SITE_UF => 'OS_COMPANY_WEB_SITE',
                CrmInboundUfMap::COMPANY_CRM_ACTIVITY_UF => 'OS_COMPANY_ACTIVITY',
                CrmInboundUfMap::COMPANY_CRM_JUR_ADDRESS_UF => 'OS_COMPANY_JUR_ADDRESS',
                CrmInboundUfMap::COMPANY_DISCOUNT_UF => 'OS_COMPANY_DISCOUNT_VALUE',
            ];
            foreach ($m as $ufK => $siteK) {
                if (!\array_key_exists($ufK, $params)) {
                    continue;
                }
                $raw = $params[$ufK];
                unset($params[$ufK]);
                if ($siteK === 'OS_COMPANY_DISCOUNT_VALUE' && $discountClearedByInboundDiscountGroupAlias) {
                    // `DISCOUNT_GROUP: null` уже задал сброс — не перезаписывать списковым UF `UF_CRM_1777030197` из полной выгрузки CRM
                    continue;
                }
                $str = self::extractCrmInboundScalarString($raw);
                if ($str !== null) {
                    $params[$siteK] = $str;
                }
            }

            // Источник истины: CRM_MULTIFIELDS.PHONE[] (WORK/MOBILE), UF — только fallback.
            self::mapInboundCrmPhoneMultifieldToSiteProperties($params);
            self::mapInboundCrmPhoneUfFallbackOnly($params);

            if (
                \array_key_exists('LEGAN_MAIN_PHONE', $params)
                || \array_key_exists('LEGAN_MOBILE_PHONE', $params)
            ) {
                self::syncTrace('Company::mapCrmCompanyPayloadUfToSiteProperties phones', [
                    'legan_main_preview' => \substr(\trim((string) ($params['LEGAN_MAIN_PHONE'] ?? '')), 0, 32),
                    'legan_mobile_preview' => \substr(\trim((string) ($params['LEGAN_MOBILE_PHONE'] ?? '')), 0, 32),
                ]);
            }

            if (\array_key_exists('EMAIL', $params)) {
                $emRaw = $params['EMAIL'];
                unset($params['EMAIL']);
                $em = self::extractCrmInboundScalarString($emRaw);
                if ($em === null && \is_array($emRaw)) {
                    foreach (self::normalizeInboundCrmMultifieldRows($emRaw) as $row) {
                        $em = self::extractCrmInboundScalarString($row['VALUE'] ?? null);
                        if ($em !== null) {
                            break;
                        }
                    }
                }
                if ($em !== null && $em !== '') {
                    $params['OS_COMPANY_EMAIL'] = $em;
                    $params['LEGAN_ENTITY_EMAIL'] = $em;
                }
            }
        }

        /**
         * Обновляет элемент компании в инфоблоке по B24_ID.
         *
         * @param array $params Массив параметров компании:
         *   - OS_COMPANY_B24_ID (string|int) — ID компании в CRM для поиска элемента; в свойстве ИБ сохраняется **локальный ID** элемента
         *   - OS_HEAD_COMPANY_B24_ID — остаётся значением из CRM (без подмены на ID элемента)
         *   - OS_HOLDING_OF — при значении как у `OS_COMPANY_B24_ID` головной компании резолвится в **локальный ID** элемента-привязки
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

            if (
                (!isset($params['OS_COMPANY_NAME']) || \trim((string) $params['OS_COMPANY_NAME']) === '')
                && isset($params['TITLE'])
            ) {
                $t = \trim((string) $params['TITLE']);
                if ($t !== '') {
                    $params['OS_COMPANY_NAME'] = $t;
                }
            }
            unset($params['TITLE']);

            if (!empty($params['OS_COMPANY_INN']) && empty($params['LEGAN_ENTITY_INN'])) {
                $params['LEGAN_ENTITY_INN'] = (string)$params['OS_COMPANY_INN'];
            }

            self::aliasInboundCompanyUpdateRequest($params);
            self::mapCrmCompanyPayloadUfToSiteProperties($params);
            self::syncOsAndLeganMirrorableCompanyFieldsParamsWhenSingleSidePresent($params);
            // До merge с ИБ: устраняем противоречия "голова" vs "дочерняя" и фиксируем явный false.
            self::enforceInboundHoldingHeadConsistency($params);

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
            if (isset($params['OS_COMPANY_BOSS']) && !\is_array($params['OS_COMPANY_BOSS'])) {
                $params['OS_COMPANY_BOSS'] = [$params['OS_COMPANY_BOSS']];
            }
            if (isset($params['LEGAN_ENTITY_BOSS']) && !\is_array($params['LEGAN_ENTITY_BOSS'])) {
                $params['LEGAN_ENTITY_BOSS'] = [$params['LEGAN_ENTITY_BOSS']];
            }

            $contactIdsMap = self::contactIdsMapFromCompanyParams($params);
            self::mergeCompanyUsersFromContactIdsMap($params, $contactIdsMap);
            self::resolveInboundCompanyBossListsFromCrmContactIds($params, $contactIdsMap);
            // UF с ID пользователей сайта должен участвовать в снятии/назначении скидочных групп до финального merge свойств ИБ
            self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($params, $params);
            self::syncOsAndLeganCompanyUsersParamsWhenSingleSidePresent($params);

            $siteElementId = (int) ($params['SITE_IBLOCK_ELEMENT_ID'] ?? 0);
            $company = $this->loadInboundCompanyRecord($siteElementId, $b24_id);

            self::syncPrimitiveBreakpoint('sync_bp_company_update_entry', [
                'b24_id' => $b24_id,
                'site_iblock_element_id' => $siteElementId > 0 ? $siteElementId : null,
                'found_element_id' => !empty($company['ID']) ? (int)$company['ID'] : null,
            ]);

            if ($company && !empty($company['ID'])) {
                // Компания найдена - обновляем
                $companyId = $company['ID'];
                self::syncTrace('Company::updateCompanyElement company found', [
                    'element_id' => (int)$companyId,
                    'element_code' => (string)($company['CODE'] ?? ''),
                ]);

                $inboundPhones = self::collectInboundLeganPhoneFields($params);
                if ($inboundPhones !== []) {
                    self::persistInboundCompanyPhonesToElement((int) $companyId, $inboundPhones);
                }

                $discountBase = self::resolveUpdatedCompanyDiscountTargetGroupId($params);
                $discountDecisionParams = $params;
                if (!\array_key_exists('OS_COMPANY_IS_HEAD_OF_HOLDING', $discountDecisionParams)
                    && \array_key_exists('OS_COMPANY_IS_HEAD_OF_HOLDING', (array)$company)
                ) {
                    $discountDecisionParams['OS_COMPANY_IS_HEAD_OF_HOLDING'] = $company['OS_COMPANY_IS_HEAD_OF_HOLDING'];
                }
                if (!\array_key_exists('LEGAN_ENTITY_IS_HEAD_COMPANY', $discountDecisionParams)
                    && \array_key_exists('LEGAN_ENTITY_IS_HEAD_COMPANY', (array)$company)
                ) {
                    $discountDecisionParams['LEGAN_ENTITY_IS_HEAD_COMPANY'] = $company['LEGAN_ENTITY_IS_HEAD_COMPANY'];
                }
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
                $seenUserIds = [];
                if ($memberRaws !== []) {
                    $user = new User();
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
                        $applyParams = $params;
                        if ($discountBase !== null
                            && $discountBase > 0
                        ) {
                            if (self::shouldApplyCompanyDiscountGroupForUser($userId, $discountDecisionParams)) {
                                $discountMapped = $discountBase;
                            } else {
                                // Директор не должен терять head-скидку при обновлении дочерней: не трогаем скидочные группы.
                                unset($applyParams['OS_COMPANY_DISCOUNT_VALUE']);
                            }
                        }
                        self::applyB24CompanyGroupsToUser($user, $userId, $applyParams, $discountMapped);
                    }
                }
                if (self::isExplicitCompanyDiscountClear($params)) {
                    $user = isset($user) ? $user : new User();
                    foreach (self::collectSiteUserIdsAttachedToCompanyElement((int) $companyId) as $orphanUserId) {
                        if ($orphanUserId <= 0 || isset($seenUserIds[$orphanUserId])) {
                            continue;
                        }
                        $seenUserIds[$orphanUserId] = true;
                        self::applyB24CompanyGroupsToUser($user, $orphanUserId, $params, null);
                    }
                }
                self::syncOsAndLeganCompanyUsersParamsWhenSingleSidePresent($params);

                // Головная компания холдинга (OS или LEGAN признак): скидка наследуется — обрабатываем пользователей головы и дочерних (`OS_HOLDING_OF` / LEGAN_ENTITY_ID_OF_HEAD_COMPANY → {@see getChildCompanyElementIdsByOsHoldingOf}), по OS_HOLDING_OF; на каждой карточке OS_COMPANY_USERS и LEGAN_ENTITY_USERS из ИБ.
                if ($discountBase !== null && $discountBase > 0 && self::isHeadCompanyForDiscountSync($params, (array)$company)) {
                    $user = isset($user) ? $user : new User();
                    $scopeCompanyIds = [(int)$companyId];
                    foreach ($this->getChildCompanyElementIdsByOsHoldingOf((int)$companyId) as $cid) {
                        if ($cid > 0) {
                            $scopeCompanyIds[] = $cid;
                        }
                    }
                    $scopeCompanyIds = \array_values(\array_unique(\array_map('intval', $scopeCompanyIds)));
                    foreach ($scopeCompanyIds as $scopeCompanyId) {
                        $scopeCompanyId = (int) $scopeCompanyId;
                        $isChildInHeadDiscountScope = ($scopeCompanyId !== (int) $companyId);
                        foreach (['OS_COMPANY_USERS', 'LEGAN_ENTITY_USERS'] as $usersPropCode) {
                            $normalizedScopeUsers = self::loadCompanyUserIdsFromIblock($scopeCompanyId, (string)$usersPropCode);
                            foreach ($normalizedScopeUsers as $scopeUserId) {
                                if ($scopeUserId <= 0 || isset($seenUserIds[$scopeUserId])) {
                                    continue;
                                }
                                $seenUserIds[$scopeUserId] = true;
                                // Дочерние карточки наследуют скидку головы для всех привязанных пользователей (OS + LEGAN), без ворот «директор / не голова».
                                $allowedByDirectorGate = $isChildInHeadDiscountScope
                                    || self::shouldApplyCompanyDiscountGroupForUser($scopeUserId, $discountDecisionParams);
                                if (!$allowedByDirectorGate) {
                                    continue;
                                }
                                $discountMapped = $discountBase;
                                $discountOnlyParams = ['OS_COMPANY_DISCOUNT_VALUE' => $params['OS_COMPANY_DISCOUNT_VALUE']];
                                self::applyB24CompanyGroupsToUser($user, $scopeUserId, $discountOnlyParams, $discountMapped);
                            }
                        }
                    }
                }

                if (self::isExplicitCompanyDiscountClear($params) && self::isHeadCompanyForDiscountSync($params, (array)$company)) {
                    $user = isset($user) ? $user : new User();
                    $scopeCompanyIds = [(int)$companyId];
                    foreach ($this->getChildCompanyElementIdsByOsHoldingOf((int)$companyId) as $cid) {
                        if ($cid > 0) {
                            $scopeCompanyIds[] = $cid;
                        }
                    }
                    $scopeCompanyIds = \array_values(\array_unique(\array_map('intval', $scopeCompanyIds)));
                    foreach ($scopeCompanyIds as $scopeCompanyId) {
                        foreach (['OS_COMPANY_USERS', 'LEGAN_ENTITY_USERS'] as $usersPropCode) {
                            foreach (self::loadCompanyUserIdsFromIblock((int)$scopeCompanyId, (string)$usersPropCode) as $scopeUserId) {
                                if ($scopeUserId <= 0 || isset($seenUserIds[$scopeUserId])) {
                                    continue;
                                }
                                $seenUserIds[$scopeUserId] = true;
                                $discountOnlyParams = ['OS_COMPANY_DISCOUNT_VALUE' => ''];
                                self::applyB24CompanyGroupsToUser($user, $scopeUserId, $discountOnlyParams, null);
                            }
                        }
                    }
                }

                self::convertInboundLeganEntityFilePublicPathToOsRequisitesPayload($params);
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

                // Свойство OS_COMPANY_B24_ID в ИБ — локальный ID элемента (в payload приходит ID компании CRM для поиска строки).
                $params['OS_COMPANY_B24_ID'] = (int) $companyId;

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
                    // Важно: `isset('')` = false, но пустая строка для list/checkbox — это явный сброс, его нужно передать в Update.
                    if (\array_key_exists($code, $params)) {
                        $arProps[$code] = $params[$code]; // Перезаписываем только переданные значения
                    }
                }

                $this->hydrateOsRequisitesFileInPropertyBag($arProps);
                self::syncOsPhoneFromLeganProfileFields($arProps);
                self::mirrorOsCompanyFieldsToLeganEntityExcludingPhones($arProps);
                self::syncOsPhoneFromLeganProfileFields($arProps);
                self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($arProps, $params);
                self::normalizeInboundCrmCheckboxBooleansToListEnums($arProps);
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
                $activeVal = self::resolveCompanyElementActiveForInbound($params, (string)($elRow['ACTIVE'] ?? 'N'));
                if ($elementName === '' || $elementName === null) {
                    $elementName = (string)($elRow['NAME'] ?? '');
                }

                $arUpdateArray = [
                    'PROPERTY_VALUES' => $arProps,
                    'NAME' => $elementName,
                    'ACTIVE' => $activeVal,
                ];
                if (\trim((string) ($elRow['CODE'] ?? '')) === '' && $b24_id !== '') {
                    $arUpdateArray['CODE'] = $b24_id;
                }

                self::syncPrimitiveBreakpoint('sync_bp_company_before_ciupdate', [
                    'element_id' => (int)$companyId,
                    'ACTIVE' => $activeVal,
                    'NAME_preview' => \is_string($elementName)
                        ? (\strlen($elementName) > 160 ? 'string(len=' . (string)\strlen($elementName) . ')' : $elementName)
                        : (string)$elementName,
                    'property_codes' => \array_keys($arProps),
                ]);

                $phonePropsForPostPass = [];
                self::applyProfilePhonesToPropertyBag($phonePropsForPostPass, $params);
                foreach (self::companyProfilePhonePropertyCodes() as $phoneCode) {
                    if (
                        !\array_key_exists($phoneCode, $phonePropsForPostPass)
                        && \array_key_exists($phoneCode, $arProps)
                    ) {
                        $phonePropsForPostPass[$phoneCode] = $arProps[$phoneCode];
                    }
                }
                $requisitesFilePropsForPostPass = $arProps;
                self::stripCompanyPhoneKeysFromPropertyBag($arUpdateArray['PROPERTY_VALUES']);
                self::stripCompanyRequisitesFileKeysFromPropertyBag($arUpdateArray['PROPERTY_VALUES']);

                $el = new \CIBlockElement;
                $updateOk = (bool) $el->Update($companyId, $arUpdateArray);
                if ($phonePropsForPostPass !== []) {
                    self::applyCompanyPhonePropertiesToElement((int) $companyId, $phonePropsForPostPass);
                }
                if ($updateOk) {
                    self::applyCompanyRequisitesFilePropertiesToElement((int) $companyId, $requisitesFilePropsForPostPass);
                    self::syncTrace('Company::updateCompanyElement CIBlockElement::Update ok', [
                        'element_id' => (int)$companyId,
                    ]);
                    $staffIds = self::siteUserIdsForCompanyActivation($arProps);
                    if ($activeVal === 'Y') {
                        self::activateCompanyStaffSiteUsers($staffIds);
                    } elseif (\array_key_exists('ACTIVE', $params) || \array_key_exists('OS_IS_MARKETING_AGENT', $params)) {
                        self::deactivateCompanyStaffSiteUsers($staffIds);
                    }

                    return $companyId;
                }

                self::syncTrace('Company::updateCompanyElement CIBlockElement::Update failed', [
                    'element_id' => (int)$companyId,
                    'last_error' => (string)($el->LAST_ERROR ?? ''),
                    'phones_postpass' => $phonePropsForPostPass !== [],
                ]);

                return $phonePropsForPostPass !== [] ? $companyId : false;
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

            if (
                (!isset($params['OS_COMPANY_NAME']) || \trim((string) $params['OS_COMPANY_NAME']) === '')
                && isset($params['TITLE'])
            ) {
                $t = \trim((string) $params['TITLE']);
                if ($t !== '') {
                    $params['OS_COMPANY_NAME'] = $t;
                }
            }
            unset($params['TITLE']);

            if (!empty($params['OS_COMPANY_INN']) && empty($params['LEGAN_ENTITY_INN'])) {
                $params['LEGAN_ENTITY_INN'] = (string)$params['OS_COMPANY_INN'];
            }

            self::aliasInboundCompanyUpdateRequest($params);
            self::mapCrmCompanyPayloadUfToSiteProperties($params);
            self::syncOsAndLeganMirrorableCompanyFieldsParamsWhenSingleSidePresent($params);
            // До записи: устраняем противоречия "голова" vs "дочерняя" и фиксируем явный false.
            self::enforceInboundHoldingHeadConsistency($params);

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
            if (isset($params['OS_COMPANY_BOSS']) && !\is_array($params['OS_COMPANY_BOSS'])) {
                $params['OS_COMPANY_BOSS'] = [$params['OS_COMPANY_BOSS']];
            }
            if (isset($params['LEGAN_ENTITY_BOSS']) && !\is_array($params['LEGAN_ENTITY_BOSS'])) {
                $params['LEGAN_ENTITY_BOSS'] = [$params['LEGAN_ENTITY_BOSS']];
            }

            $el = new \CIBlockElement;
            $contactIdsMap = self::contactIdsMapFromCompanyParams($params);
            self::mergeCompanyUsersFromContactIdsMap($params, $contactIdsMap);
            self::resolveInboundCompanyBossListsFromCrmContactIds($params, $contactIdsMap);
            self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($params, $params);
            self::syncOsAndLeganCompanyUsersParamsWhenSingleSidePresent($params);
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
                    $applyParams = $params;
                    if ($discountBase !== null
                        && $discountBase > 0
                    ) {
                        if (self::shouldApplyCompanyDiscountGroupForUser($userId, $params)) {
                            $discountMapped = $discountBase;
                        } else {
                            // Директор не должен терять head-скидку при обработке дочерней.
                            unset($applyParams['OS_COMPANY_DISCOUNT_VALUE']);
                        }
                    }
                    self::applyB24CompanyGroupsToUser($user, $userId, $applyParams, $discountMapped);
                }
            }
            self::syncOsAndLeganCompanyUsersParamsWhenSingleSidePresent($params);

            self::convertInboundLeganEntityFilePublicPathToOsRequisitesPayload($params);
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
                // Важно: `isset('')` = false, но пустая строка для list/checkbox — это явный сброс, его нужно передать в Update/Add.
                if (\array_key_exists($code, $params)) {
                    $arProps[$code] = $params[$code];
                }
            }
            // В CRM-пейлоаде OS_COMPANY_B24_ID — внешний id для поиска/кода; в свойстве ИБ храним локальный ID элемента после Add.
            unset($arProps['OS_COMPANY_B24_ID']);

            $this->hydrateOsRequisitesFileInPropertyBag($arProps);
            self::mirrorOsCompanyFieldsToLeganEntity($arProps);
            self::mergeLeganEntityUsersFromCrmSiteUserUfPayload($arProps, $params);
            self::normalizeInboundCrmCheckboxBooleansToListEnums($arProps);
            self::normalizeInboundCrmListPropertyValuesForIblock($arProps);
            self::syncTrace('Company::createCompanyFromUpdate merged PROPERTY_VALUES', [
                'inn_arProps' => self::syncInnFieldLengths($arProps),
            ]);
            
            $activeCreate = self::resolveCompanyElementActiveForInbound($params, 'N');
            $phonePropsForPostPass = [];
            self::applyProfilePhonesToPropertyBag($phonePropsForPostPass, $params);
            foreach (self::companyProfilePhonePropertyCodes() as $phoneCode) {
                if (
                    !\array_key_exists($phoneCode, $phonePropsForPostPass)
                    && \array_key_exists($phoneCode, $arProps)
                ) {
                    $phonePropsForPostPass[$phoneCode] = $arProps[$phoneCode];
                }
            }
            $requisitesFilePropsForPostPass = $arProps;
            self::stripCompanyPhoneKeysFromPropertyBag($arProps);
            self::stripCompanyRequisitesFileKeysFromPropertyBag($arProps);
            $arFields = [
                'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                'IBLOCK_TYPE' => 'personal',
                'NAME' => $params['OS_COMPANY_NAME'] ?? 'Новая компания',
                'CODE' => $b24NewId,
                'ACTIVE' => $activeCreate,
                'PROPERTY_VALUES' => $arProps
            ];
            
            $companyId = $el->Add($arFields);
            
            if ($companyId) {
                \CIBlockElement::SetPropertyValues(
                    (int) $companyId,
                    CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    (int) $companyId,
                    'OS_COMPANY_B24_ID'
                );
                self::applyCompanyPhonePropertiesToElement((int) $companyId, $phonePropsForPostPass);
                self::applyCompanyRequisitesFilePropertiesToElement((int) $companyId, $requisitesFilePropsForPostPass);
                self::syncTrace('Company::createCompanyFromUpdate CIBlockElement::Add ok', [
                    'element_id' => (int)$companyId,
                ]);
                $staffIds = self::siteUserIdsForCompanyActivation($arProps);
                if (($arFields['ACTIVE'] ?? '') === 'Y') {
                    self::activateCompanyStaffSiteUsers($staffIds);
                } elseif (\array_key_exists('ACTIVE', $params) || \array_key_exists('OS_IS_MARKETING_AGENT', $params)) {
                    self::deactivateCompanyStaffSiteUsers($staffIds);
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
         * Нормализует payload `fileData` (Bitrix24 file UF) в ID файла сайта через base64 decode + CFile::SaveFile.
         *
         * Поддержка:
         * - ['fileData' => [<name>, <base64>]]  (типичный формат B24)
         * - ['fileData' => ['name' => <name>, 'base64' => <base64>]] (вариант)
         *
         * @return int|null ID файла или null
         */
        private static function trySaveOsRequisitesFileFromB24FileDataPayload(mixed $raw): ?int
        {
            if (!\is_array($raw) || !\array_key_exists('fileData', $raw)) {
                return null;
            }
            $fd = $raw['fileData'];
            $name = '';
            $base64 = '';

            if (\is_array($fd)) {
                // fileData: [name, base64]
                if (isset($fd[0], $fd[1]) && \is_scalar($fd[0]) && \is_scalar($fd[1])) {
                    $name = (string) $fd[0];
                    $base64 = (string) $fd[1];
                } elseif (isset($fd['name'], $fd['base64']) && \is_scalar($fd['name']) && \is_scalar($fd['base64'])) {
                    // fileData: { name, base64 }
                    $name = (string) $fd['name'];
                    $base64 = (string) $fd['base64'];
                }
            }

            $name = \trim($name);
            $base64 = \trim($base64);
            if ($name === '' || $base64 === '') {
                return null;
            }

            // Allow "data:...;base64,..." inputs
            if (\str_contains($base64, 'base64,')) {
                $parts = \explode('base64,', $base64, 2);
                $base64 = isset($parts[1]) ? \trim((string) $parts[1]) : $base64;
            }

            $bin = \base64_decode($base64, true);
            if ($bin === false || $bin === '') {
                return null;
            }

            $tmpPath = \tempnam(\sys_get_temp_dir(), 'osreq_');
            if (!\is_string($tmpPath) || $tmpPath === '') {
                return null;
            }

            try {
                $written = @\file_put_contents($tmpPath, $bin);
                if ($written === false) {
                    return null;
                }

                $fileArray = \CFile::MakeFileArray($tmpPath, false, $name);
                if (!\is_array($fileArray) || isset($fileArray['error'])) {
                    return null;
                }

                $savedFileId = (int) \CFile::SaveFile($fileArray, 'os_requisites');
                if ($savedFileId > 0) {
                    return $savedFileId;
                }
            } catch (\Throwable $e) {
                return null;
            } finally {
                if (\file_exists($tmpPath)) {
                    @\unlink($tmpPath);
                }
            }

            return null;
        }

        /**
         * Нормализует "битриксовый" массив файла (например $_FILES['LEGAN_ENTITY_FILE']) в ID файла сайта.
         *
         * @return int|null
         */
        private static function trySaveOsRequisitesFileFromBitrixFileArray(mixed $raw): ?int
        {
            if (!\is_array($raw)) {
                return null;
            }
            if (!isset($raw['tmp_name'], $raw['name'])) {
                return null;
            }
            if (!\is_string($raw['tmp_name']) || $raw['tmp_name'] === '') {
                return null;
            }
            if (!\is_scalar($raw['name']) || \trim((string) $raw['name']) === '') {
                return null;
            }
            $err = isset($raw['error']) ? (int) $raw['error'] : 0;
            if ($err !== 0) {
                return null;
            }

            try {
                $fileArray = $raw;
                $fileArray['name'] = (string) $fileArray['name'];
                $savedFileId = (int) \CFile::SaveFile($fileArray, 'os_requisites');
                return $savedFileId > 0 ? $savedFileId : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        /**
         * Backward-compatible alias: OS_REQUISITES_FILE -> OS_REQUSITES_FILE.
         *
         * @param array<string, mixed> $params
         */
        private static function applyRequisitesFileAliasForInput(array &$params): void
        {
            if (\array_key_exists('OS_REQUSITES_FILE', $params)) {
                return;
            }
            if (\array_key_exists('OS_REQUISITES_FILE', $params)) {
                $params['OS_REQUSITES_FILE'] = $params['OS_REQUISITES_FILE'];
                self::syncTrace('company.requisites_file.alias.applied', [
                    'from' => 'OS_REQUISITES_FILE',
                    'to' => 'OS_REQUSITES_FILE',
                    'value_type' => \gettype($params['OS_REQUISITES_FILE']),
                ]);
            }
        }

        /**
         * После слияния с текущими свойствами: скачать с CRM при необходимости, иначе int для зеркала LEGAN_ENTITY_FILE.
         */
        private function hydrateOsRequisitesFileInPropertyBag(array &$props): void
        {
            self::applyRequisitesFileAliasForInput($props);
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
            self::applyRequisitesFileAliasForInput($params);
            if (!\array_key_exists('OS_REQUSITES_FILE', $params)) {
                return;
            }
            $raw = $params['OS_REQUSITES_FILE'];
            if ($raw === null || $raw === '') {
                return;
            }

            self::syncTrace('company.requisites_file.normalize.enter', [
                'value_type' => \gettype($raw),
                'is_array' => \is_array($raw),
                'array_keys' => \is_array($raw) ? \array_slice(\array_keys($raw), 0, 15) : null,
                'has_fileData' => \is_array($raw) ? \array_key_exists('fileData', $raw) : false,
                'has_tmp_name' => \is_array($raw) ? \array_key_exists('tmp_name', $raw) : false,
            ]);

            // 0) Прямой fileData payload (B24 UF) -> сохраняем на сайт и храним как fileId
            $fileIdFromFileData = self::trySaveOsRequisitesFileFromB24FileDataPayload($raw);
            if ($fileIdFromFileData !== null) {
                $params['OS_REQUSITES_FILE'] = $fileIdFromFileData;
                self::syncTrace('company.requisites_file.normalize.applied', [
                    'method' => 'fileData_base64_to_fileId',
                    'file_id' => $fileIdFromFileData,
                ]);

                return;
            }

            // 0.1) Битриксовый массив файла (например $_FILES) -> сохранить
            $fileIdFromBitrixFileArray = self::trySaveOsRequisitesFileFromBitrixFileArray($raw);
            if ($fileIdFromBitrixFileArray !== null) {
                $params['OS_REQUSITES_FILE'] = $fileIdFromBitrixFileArray;
                self::syncTrace('company.requisites_file.normalize.applied', [
                    'method' => 'bitrix_file_array_to_fileId',
                    'file_id' => $fileIdFromBitrixFileArray,
                ]);

                return;
            }

            if (\is_array($raw) && self::isOsRequisitesFileCrmDownloadPayload($raw)) {
                $fileId = $this->processRequisitesFile($raw);
                if ($fileId) {
                    $params['OS_REQUSITES_FILE'] = $fileId;

                    self::syncTrace('company.requisites_file.normalize.applied', [
                        'method' => 'crm_download_payload_to_fileId',
                        'file_id' => (int) $fileId,
                    ]);

                    return;
                }
            }
            $norm = self::normalizeOsRequisitesFileInputToStoredFileId($raw);
            if ($norm !== null) {
                $params['OS_REQUSITES_FILE'] = $norm;

                self::syncTrace('company.requisites_file.normalize.applied', [
                    'method' => 'scalar_or_existing_site_fileId',
                    'file_id' => $norm,
                ]);

                return;
            }
            if (\is_array($raw)) {
                $fileId = $this->processRequisitesFile($raw);
                if ($fileId) {
                    $params['OS_REQUSITES_FILE'] = $fileId;

                    self::syncTrace('company.requisites_file.normalize.applied', [
                        'method' => 'processRequisitesFile_fallback',
                        'file_id' => (int) $fileId,
                    ]);
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
                $downloadableUrl = self::buildPortalUploadFileDownloadUrl($base, $src);
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
            if (!Loader::includeModule('iblock')) {
                throw new \Exception('Модуль iblock не установлен');
            }
            $rsCompany = \CIBlockElement::GetById($id);
            if($ob = $rsCompany->GetNextElement()) {
                $arProps = $ob->GetProperties();
                $arFields = $ob->GetFields();
                $arCompany["ID"] = $arFields["ID"];
                $arCompany['NAME'] = $arFields['NAME'] ?? '';
                $phoneCodes = self::companyProfilePhonePropertyCodes();
                foreach (self::$codeProps as $code) {
                    if (\in_array($code, $phoneCodes, true)) {
                        $arCompany[$code] = self::readCompanyIblockScalarProperty((int) $id, $code);
                        continue;
                    }
                    $p = $arProps[$code] ?? null;
                    $arCompany[$code] = self::extractScalarFromIblockPropertyRow($p);
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
         * ID элементов ИБ (всех статусов ACTIVE), у которых {@see OS_HOLDING_OF} = ID элемента головной компании.
         * Используется для наследования скидки головы: строго по OS, без {@see LEGAN_ENTITY_ID_OF_HEAD_COMPANY} и без фильтра ACTIVE=Y
         * (иначе неактивные дочерние карточки не получали бы пересчёт групп).
         *
         * @return list<int>
         */
        private function getChildCompanyElementIdsByOsHoldingOf(int $headCompanyElementId): array
        {
            if ($headCompanyElementId <= 0) {
                return [];
            }
            $seen = [];
            $rs = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                    'PROPERTY_OS_HOLDING_OF' => $headCompanyElementId,
                ],
                false,
                false,
                ['ID']
            );
            while ($row = $rs->GetNext()) {
                $id = (int) ($row['ID'] ?? 0);
                if ($id > 0 && $id !== $headCompanyElementId) {
                    $seen[$id] = true;
                }
            }

            return \array_map('intval', \array_keys($seen));
        }

        /**
         * Получить все дочерние компании холдинга
         */
        private function getChildCompanies($headCompanyId) {
            $headCompany = $this->getCompany($headCompanyId);
            if (!$headCompany) {
                return [];
            }

            $childCompanies = [];
            $seen = [];
            foreach (['OS_HOLDING_OF', 'LEGAN_ENTITY_ID_OF_HEAD_COMPANY'] as $headLinkCode) {
                $rsCompanies = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => CompanyModuleConfig::COMPANY_IBLOCK_ID,
                        'ACTIVE' => 'Y',
                        'PROPERTY_' . $headLinkCode => (int)$headCompanyId,
                    ],
                    false,
                    false,
                    ['ID', 'NAME', 'CODE', 'PROPERTY_OS_HOLDING_OF', 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY']
                );
                while ($ob = $rsCompanies->GetNextElement()) {
                    $arFields = $ob->GetFields();
                    $arProps = $ob->GetProperties();
                    $cid = (int)($arFields['ID'] ?? 0);
                    if ($cid <= 0 || $cid === (int)$headCompanyId || isset($seen[$cid])) {
                        continue;
                    }
                    $seen[$cid] = true;
                    $childCompanies[] = [
                        'ID' => $cid,
                        'NAME' => $arFields['NAME'],
                        'CODE' => $arFields['CODE'],
                        'OS_HOLDING_OF' => $arProps['OS_HOLDING_OF']['VALUE'] ?? null,
                        'LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $arProps['LEGAN_ENTITY_ID_OF_HEAD_COMPANY']['VALUE'] ?? null,
                    ];
                }
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

        public function updateCompanyProfile($companyId, array &$data, $uploadedFile = null, $deleteRequisites = false) {
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
            $requisitesFilesToDeleteAfterPersist = [];
            $uploadErr = \is_array($uploadedFile) ? (int) ($uploadedFile['error'] ?? -1) : -1;
            if ($uploadedFile && $uploadErr === UPLOAD_ERR_OK) {
                $fileResult = $this->processUploadedRequisitesFile($uploadedFile);
                self::syncTrace('company.profile.requisites.upload.result', [
                    'company_id' => (int)$companyId,
                    'success' => !empty($fileResult['success']),
                    'file_id' => (int)($fileResult['file_id'] ?? 0),
                    'message' => (string)($fileResult['message'] ?? ''),
                    'uploaded_name' => (string)($uploadedFile['name'] ?? ''),
                    'uploaded_size' => (int)($uploadedFile['size'] ?? 0),
                    'uploaded_error' => (int)($uploadedFile['error'] ?? -1),
                ]);
                if (!$fileResult['success']) {
                    return $fileResult;
                }
                $fileId = $fileResult['file_id'];
            }

            // Обработка удаления файла
            if ($deleteRequisites) {
                foreach (\array_unique(\array_filter([
                    (int) ($company['OS_REQUSITES_FILE'] ?? 0),
                    (int) ($company['LEGAN_ENTITY_FILE'] ?? 0),
                ])) as $delFid) {
                    if ($delFid > 0) {
                        \CFile::Delete($delFid);
                    }
                }
                $data['OS_REQUSITES_FILE'] = '';
                // Явно очищаем и витринное поле, т.к. mirror пропускает пустые значения.
                $data['LEGAN_ENTITY_FILE'] = '';
            } elseif ($fileId) {
                $newFid = (int) $fileId;
                foreach (\array_unique(\array_filter([
                    (int) ($company['OS_REQUSITES_FILE'] ?? 0),
                    (int) ($company['LEGAN_ENTITY_FILE'] ?? 0),
                ])) as $oldFid) {
                    if ($oldFid > 0 && $oldFid !== $newFid) {
                        $requisitesFilesToDeleteAfterPersist[] = $oldFid;
                    }
                }
                $data['OS_REQUSITES_FILE'] = $fileId;
                $data['LEGAN_ENTITY_FILE'] = $fileId;
            } elseif (!array_key_exists('OS_REQUSITES_FILE', $data)) {
                $existingRequisitesFileId = (int)($company['OS_REQUSITES_FILE'] ?? 0);
                if ($existingRequisitesFileId <= 0) {
                    // Fallback для старых карточек, где файл остался только в витринном LEGAN поле.
                    $existingRequisitesFileId = (int)($company['LEGAN_ENTITY_FILE'] ?? 0);
                }
                if ($existingRequisitesFileId > 0) {
                    $data['OS_REQUSITES_FILE'] = $existingRequisitesFileId;
                    $data['LEGAN_ENTITY_FILE'] = $existingRequisitesFileId;
                    self::syncTrace('company.profile.requisites.fallback_existing_file', [
                        'company_id' => (int)$companyId,
                        'file_id' => $existingRequisitesFileId,
                        'source' => !empty($company['OS_REQUSITES_FILE']) ? 'OS_REQUSITES_FILE' : 'LEGAN_ENTITY_FILE',
                    ]);
                }
            }

            self::syncTrace('company.profile.requisites.pre_sync', [
                'company_id' => (int)$companyId,
                'delete_requisites' => (bool)$deleteRequisites,
                'has_uploaded_file' => is_array($uploadedFile),
                'has_data_os_requisites_file' => array_key_exists('OS_REQUSITES_FILE', $data),
                'data_os_requisites_file_type' => array_key_exists('OS_REQUSITES_FILE', $data) ? gettype($data['OS_REQUSITES_FILE']) : null,
                'data_os_requisites_file_value' => array_key_exists('OS_REQUSITES_FILE', $data) && is_scalar($data['OS_REQUSITES_FILE']) ? (string)$data['OS_REQUSITES_FILE'] : null,
            ]);

            $payload = $this->buildProfileOutboundBitrixPayload((int) $companyId, $data);
            if (isset($payload['error'])) {
                return [
                    'success' => false,
                    'message' => (string) $payload['error'],
                ];
            }

            $preservedFileFields = [];
            foreach (self::companyProfileRequisitesFilePropertyCodes() as $fileCode) {
                if (\array_key_exists($fileCode, $data)) {
                    $preservedFileFields[$fileCode] = $data[$fileCode];
                }
            }
            self::mergeCompanyProfileFormIntoPropertyBag($data, $payload['merged']);
            foreach ($preservedFileFields as $fileCode => $fileValue) {
                $data[$fileCode] = $fileValue;
            }

            // Локальный ИБ 23: {@see persistCompanyProfileFormDataToIblock} после CRM (company.profile.edit/ajax.php).

            // Получаем обновленные данные для ответа
            $rsElement = \CIBlockElement::GetByID($companyId);
            $companyCode = $companyId;
            if ($arElement = $rsElement->Fetch()) {
                $companyCode = $arElement['CODE'] ?? $companyId;
            }

            // CRM и запись в ИБ: company.profile.edit/ajax.php — сначала {@see syncCompanyProfileCompanyCardToBitrix24}, затем {@see persistCompanyProfileFormDataToIblock}.
            $b24CompanyId = self::resolveOutboundBitrix24CompanyId((int) $companyId, $company);

            return [
                'success' => true,
                'message' => 'Данные компании успешно обновлены',
                'data' => [
                    'company_id' => $companyId,
                    'company_code' => $companyCode,
                    'b24_synced' => false,
                    'b24_company_id' => $b24CompanyId,
                    'b24_error' => '',
                    'b24_result' => null,
                    'requisites_files_to_delete_after_persist' => $requisitesFilesToDeleteAfterPersist,
                ],
            ];
        }

        /**
         * Подготовка payload для исходящей синхронизации профиля в B24 (как после {@see updateCompanyProfile}).
         *
         * @param array<string, mixed> $updateData поля формы (LEGAN_* / OS_*), будут смёржены с карточкой ИБ
         *
         * @return array{b24_id: int, merged: array<string, mixed>}|array{error: string}
         */
        public function buildProfileOutboundBitrixPayload(int $siteCompanyElementId, array $updateData): array
        {
            $companyRow = $this->getCompany($siteCompanyElementId);
            if ($companyRow === [] || empty($companyRow['ID'])) {
                return ['error' => 'Компания не найдена'];
            }
            $merged = \array_merge($companyRow, $updateData);
            self::mapCompanyEditFormLeganToOs($merged);
            if (!isset($merged['OS_REQUSITES_FILE']) && !empty($companyRow['OS_REQUSITES_FILE'])) {
                $merged['OS_REQUSITES_FILE'] = $companyRow['OS_REQUSITES_FILE'];
            }
            $merged['SITE_IBLOCK_ELEMENT_ID'] = $siteCompanyElementId;
            $b24Id = self::resolveOutboundBitrix24CompanyId($siteCompanyElementId, $companyRow);
            if ($b24Id <= 0) {
                return ['error' => 'Не задана связь компании с CRM (OS_COMPANY_B24_ID)'];
            }

            return ['b24_id' => $b24Id, 'merged' => $merged];
        }

        /**
         * @return array{success: bool, error: string, raw: mixed}
         */
        private static function normalizeOutboundB24MethodResult($result): array
        {
            if (\is_array($result) && \array_key_exists('success', $result) && (int) $result['success'] === 0) {
                return [
                    'success' => false,
                    'error' => (string) ($result['error'] ?? 'Ошибка CRM'),
                    'raw' => $result,
                ];
            }
            if ($result === false || $result === null || $result === '') {
                return [
                    'success' => false,
                    'error' => 'Пустой ответ CRM',
                    'raw' => $result,
                ];
            }

            return ['success' => true, 'error' => '', 'raw' => $result];
        }

        /** Мультиполя карточки компании B24: без удаления по ID новые VALUE дописываются к старым. */
        private const B24_COMPANY_PROFILE_REPLACE_MULTIFIELDS = ['PHONE', 'EMAIL', 'WEB'];

        /**
         * Нормализация результата crm.company.get после транспорта n8n / {@see RestClient}.
         *
         * @param mixed $result
         * @return array<string, mixed>|null
         */
        private static function normalizeCrmCompanyGetRow($result): ?array
        {
            if (!\is_array($result)) {
                return null;
            }
            if (isset($result['success']) && (int) $result['success'] === 0) {
                return null;
            }
            $err = $result['error'] ?? null;
            if ($err !== null && $err !== '') {
                return null;
            }
            $id = (int) ($result['ID'] ?? $result['id'] ?? 0);

            return $id > 0 ? $result : null;
        }

        /**
         * Перед crm.company.update: помечаем существующие элементы PHONE/EMAIL/WEB на удаление (Bitrix crm_multifield).
         *
         * @param array<string, mixed> $existingCompany строка из crm.company.get
         * @param array<string, mixed> $desiredFields поля для update (из {@see buildBitrix24CompanyFieldsFromSiteData})
         * @param list<string> $multifieldKeys
         * @return array<string, mixed>
         */
        private static function mergeCompanyProfileMultifieldsReplacingExisting(
            array $existingCompany,
            array $desiredFields,
            array $multifieldKeys
        ): array {
            $out = $desiredFields;
            foreach ($multifieldKeys as $mf) {
                if (!\array_key_exists($mf, $desiredFields)) {
                    continue;
                }
                $deletes = [];
                $existingList = $existingCompany[$mf] ?? [];
                if (\is_array($existingList)) {
                    foreach ($existingList as $row) {
                        if (!\is_array($row)) {
                            continue;
                        }
                        $rowId = (int) ($row['ID'] ?? $row['id'] ?? 0);
                        if ($rowId > 0) {
                            $deletes[] = ['ID' => $rowId, 'DELETE' => 'Y'];
                        }
                    }
                }
                $desiredList = $desiredFields[$mf];
                $newItems = [];
                if (\is_array($desiredList)) {
                    foreach ($desiredList as $row) {
                        if (!\is_array($row)) {
                            continue;
                        }
                        $value = isset($row['VALUE']) ? \trim((string) $row['VALUE']) : '';
                        if ($value === '') {
                            continue;
                        }
                        $newItems[] = [
                            'VALUE' => $value,
                            'VALUE_TYPE' => \trim((string) ($row['VALUE_TYPE'] ?? $row['value_type'] ?? 'WORK')) ?: 'WORK',
                        ];
                    }
                }
                $out[$mf] = \array_merge($deletes, $newItems);
            }

            return $out;
        }

        /**
         * @return array<string, mixed>|null
         */
        private static function fetchCrmCompanyRowForProfileSync(int $b24CompanyId, bool $debug): ?array
        {
            if ($b24CompanyId <= 0) {
                return null;
            }
            $result = self::callB24Method('crm.company.get', ['id' => $b24CompanyId], $debug);

            return self::normalizeCrmCompanyGetRow($result);
        }

        /**
         * Исходящий вызов: только карточка компании в B24 (`crm.company.update`).
         *
         * @param array<string, mixed> $updateData как в {@see updateCompanyProfile} (до merge)
         *
         * @return array{success: bool, error: string, raw: mixed}
         */
        public function syncCompanyProfileCompanyCardToBitrix24(int $siteCompanyElementId, array $updateData, bool $debug = false): array
        {
            $payload = $this->buildProfileOutboundBitrixPayload($siteCompanyElementId, $updateData);
            if (isset($payload['error'])) {
                return ['success' => false, 'error' => (string) $payload['error'], 'raw' => null];
            }
            $b24Id = (int) $payload['b24_id'];
            $extract = self::buildBitrix24CompanyFieldsFromSiteData($payload['merged']);
            $b24Fields = $extract['b24Fields'];

            try {
                $needsMultifieldMerge = false;
                foreach (self::B24_COMPANY_PROFILE_REPLACE_MULTIFIELDS as $mf) {
                    if (\array_key_exists($mf, $b24Fields)) {
                        $needsMultifieldMerge = true;
                        break;
                    }
                }
                if ($needsMultifieldMerge) {
                    $existingCompany = self::fetchCrmCompanyRowForProfileSync($b24Id, $debug);
                    if ($existingCompany === null) {
                        return [
                            'success' => false,
                            'error' => 'crm_company_get_failed',
                            'raw' => null,
                        ];
                    }
                    $b24Fields = self::mergeCompanyProfileMultifieldsReplacingExisting(
                        $existingCompany,
                        $b24Fields,
                        self::B24_COMPANY_PROFILE_REPLACE_MULTIFIELDS
                    );
                }

                $result = self::callB24Method('crm.company.update', [
                    'id' => $b24Id,
                    'fields' => $b24Fields,
                ], $debug);

                $norm = self::normalizeOutboundB24MethodResult($result);

                return $norm;
            } catch (\Exception $e) {

                return ['success' => false, 'error' => $e->getMessage(), 'raw' => null];
            }
        }

        /**
         * Исходящий вызов: только реквизит компании в B24 (`crm.requisite.list` + `crm.requisite.update`).
         *
         * @param array<string, mixed> $updateData как в {@see updateCompanyProfile}
         *
         * @return array{success: bool, error: string, raw: mixed}
         */
        public function syncCompanyProfileRequisiteToBitrix24(int $siteCompanyElementId, array $updateData, bool $debug = false): array
        {
            $payload = $this->buildProfileOutboundBitrixPayload($siteCompanyElementId, $updateData);
            if (isset($payload['error'])) {
                return ['success' => false, 'error' => (string) $payload['error'], 'raw' => null];
            }
            $b24Id = (int) $payload['b24_id'];
            $extract = self::buildBitrix24CompanyFieldsFromSiteData($payload['merged']);
            $rqInn = $extract['rqInn'];
            $rqFullName = $extract['rqFullName'];
            $b24Fields = $extract['b24Fields'];
            $rqFullNameForRequisite = $rqFullName !== ''
                ? $rqFullName
                : \trim((string) ($b24Fields['TITLE'] ?? ''));
            $needRequisitePush = ($rqInn !== '' || $rqFullName !== '');

            try {
                $requisiteListRaw = self::callB24RegistrationWebhook(
                    'registration_webhook_crm_requisite_list_url',
                    'crm.requisite.list',
                    [
                        'select' => ['ID', 'ENTITY_ID', 'ENTITY_TYPE_ID', 'PRESET_ID', 'RQ_INN'],
                        'filter' => [
                            'ENTITY_TYPE_ID' => 4,
                            'ENTITY_ID' => $b24Id,
                        ],
                    ],
                    $debug
                );
                $requisiteRows = self::normalizeCrmRequisiteListRowsFromTransport($requisiteListRaw);
                $requisiteId = self::resolvePrimaryCompanyRequisiteId($requisiteRows, $b24Id);


                $doRequisiteCrmUpdate = $requisiteId > 0
                    && ($rqInn !== '' || $rqFullNameForRequisite !== '');
                if (!$doRequisiteCrmUpdate) {
                    $reason = $requisiteId <= 0 ? 'no_requisite_id' : 'no_rq_fields';

                    if ($requisiteId <= 0 && $needRequisitePush) {
                        return [
                            'success' => false,
                            'error' => 'В CRM не найден реквизит компании для обновления',
                            'raw' => null,
                        ];
                    }

                    return ['success' => true, 'error' => '', 'raw' => null];
                }

                $requisiteFields = [
                    'ENTITY_ID' => $b24Id,
                    'ENTITY_TYPE_ID' => 4,
                ];
                if ($rqInn !== '') {
                    $requisiteFields['RQ_INN'] = $rqInn;
                }
                if ($rqFullNameForRequisite !== '') {
                    $requisiteFields['RQ_COMPANY_FULL_NAME'] = $rqFullNameForRequisite;
                }
                $reqResult = self::callB24RegistrationWebhook(
                    'registration_webhook_crm_requisite_update_url',
                    'crm.requisite.update',
                    [
                        'id' => $requisiteId,
                        'fields' => $requisiteFields,
                    ],
                    $debug
                );
                $norm = self::normalizeOutboundB24MethodResult($reqResult);

                return $norm;
            } catch (\Exception $e) {
                self::syncTrace('company.profile.b24.requisite.exception', [
                    'b24_company_id' => $b24Id,
                    'exception_class' => \get_class($e),
                ]);

                return ['success' => false, 'error' => $e->getMessage(), 'raw' => null];
            }
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
         * Сбор полей для `crm.company.update` и значений RQ_* из данных сайта (merged ИБ + форма).
         *
         * @param array<string, mixed> $data
         *
         * @return array{b24Fields: array<string, mixed>, rqInn: string, rqFullName: string}
         */
        private static function buildBitrix24CompanyFieldsFromSiteData(array $data): array
        {
            $b24Fields = [];

            if (!empty($data['OS_COMPANY_NAME'])) {
                $b24Fields['TITLE'] = $data['OS_COMPANY_NAME'];
            }

            $rqInn = isset($data['OS_COMPANY_INN']) ? \trim((string) $data['OS_COMPANY_INN']) : '';
            if ($rqInn === '' && \array_key_exists('LEGAN_ENTITY_INN', $data)) {
                $rqInn = \trim((string) $data['LEGAN_ENTITY_INN']);
            }
            $rqFullName = isset($data['OS_COMPANY_NAME']) ? \trim((string) $data['OS_COMPANY_NAME']) : '';
            if ($rqFullName === '' && \array_key_exists('LEGAN_ENTITY_NAME', $data)) {
                $rqFullName = \trim((string) $data['LEGAN_ENTITY_NAME']);
            }

            // Город в синхронизированный UF (совместим с inbound map)
            if (!empty($data['OS_COMPANY_CITY'])) {
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_CITY_UF] = $data['OS_COMPANY_CITY'];
            }

            // Телефоны: multifield PHONE + UF (симметрия с inbound CrmInboundUfMap)
            $phoneRows = [];
            $pushPhone = static function (string $value, string $type) use (&$phoneRows): void {
                $v = \trim($value);
                if ($v === '') {
                    return;
                }
                foreach ($phoneRows as $existing) {
                    if ((string) ($existing['VALUE'] ?? '') === $v && (string) ($existing['VALUE_TYPE'] ?? '') === $type) {
                        return;
                    }
                }
                $phoneRows[] = ['VALUE' => $v, 'VALUE_TYPE' => $type];
            };
            if (!empty($data['OS_COMPANY_PHONE'])) {
                $pushPhone((string) $data['OS_COMPANY_PHONE'], 'WORK');
            }
            if (!empty($data['LEGAN_MAIN_PHONE'])) {
                $pushPhone((string) $data['LEGAN_MAIN_PHONE'], 'WORK');
            }
            if (!empty($data['LEGAN_MOBILE_PHONE'])) {
                $pushPhone((string) $data['LEGAN_MOBILE_PHONE'], 'MOBILE');
            }
            if ($phoneRows !== []) {
                $b24Fields['PHONE'] = $phoneRows;
            }

            $mainPhoneUf = '';
            if (!empty($data['LEGAN_MAIN_PHONE'])) {
                $mainPhoneUf = \trim((string) $data['LEGAN_MAIN_PHONE']);
            } elseif (!empty($data['OS_COMPANY_PHONE'])) {
                $mainPhoneUf = \trim((string) $data['OS_COMPANY_PHONE']);
            }
            if ($mainPhoneUf !== '') {
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_MAIN_PHONE_UF] = $mainPhoneUf;
            }
            if (\array_key_exists('LEGAN_MOBILE_PHONE', $data)) {
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_MOBILE_PHONE_UF] = \trim((string) $data['LEGAN_MOBILE_PHONE']);
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
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_WEB_SITE_UF] = $data['OS_COMPANY_WEB_SITE'];
            }

            // Activity / юр. адрес в CRM UF (если заполнены)
            if (!empty($data['OS_COMPANY_ACTIVITY'])) {
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_ACTIVITY_UF] = $data['OS_COMPANY_ACTIVITY'];
            }
            if (!empty($data['OS_COMPANY_JUR_ADDRESS'])) {
                $b24Fields[CrmInboundUfMap::COMPANY_CRM_JUR_ADDRESS_UF] = $data['OS_COMPANY_JUR_ADDRESS'];
            }

            // Связь crm.company ↔ ID элемента ИБ 23 на сайте (иначе B24 `CompanySync` не шлёт `UPDATE_COMPANY`)
            if (!empty($data['SITE_IBLOCK_ELEMENT_ID']) && (int) $data['SITE_IBLOCK_ELEMENT_ID'] > 0) {
                $b24Fields[CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF] = (string) (int) $data['SITE_IBLOCK_ELEMENT_ID'];
            }

            // Файл реквизитов: UF типа «файл» — crm.company.update ожидает fileData + Base64 (URL не подставляется)
            // 0) Если уже пришёл fileData payload (например из syncFields регистрации) — передаём как есть.
            $rawOs = $data['OS_REQUSITES_FILE'] ?? null;
            if (\is_array($rawOs) && \array_key_exists('fileData', $rawOs)) {
                $b24Fields[CompanyB24Config::REQUISITES_FILE_FIELD] = $rawOs;
                self::syncTrace('company.b24.requisites_file.trace', [
                    'included' => true,
                    'reason' => 'passthrough_fileData',
                    'value_keys' => \array_slice(\array_keys($rawOs), 0, 15),
                ]);
            } else {
                $fileId = 0;
                if (\array_key_exists('OS_REQUSITES_FILE', $data)) {
                    $fid = self::normalizeOsRequisitesFileInputToStoredFileId($data['OS_REQUSITES_FILE']);
                    if ($fid !== null) {
                        $fileId = $fid;
                    }
                }
                if ($fileId <= 0 && \array_key_exists('LEGAN_ENTITY_FILE', $data)) {
                    $fid = self::normalizeOsRequisitesFileInputToStoredFileId($data['LEGAN_ENTITY_FILE']);
                    if ($fid !== null) {
                        $fileId = $fid;
                    }
                }
                if ($fileId > 0) {
                    $filePayload = self::buildCrmFileFieldFileDataFromBitrixFileId($fileId);
                    if ($filePayload !== null) {
                        $b24Fields[CompanyB24Config::REQUISITES_FILE_FIELD] = $filePayload;
                        self::syncTrace('company.b24.requisites_file.trace', [
                            'included' => true,
                            'reason' => 'fileId_to_fileData_base64',
                            'file_id' => $fileId,
                        ]);
                    }
                } elseif (\array_key_exists('OS_REQUSITES_FILE', $data) && (int) $data['OS_REQUSITES_FILE'] === 0) {
                    self::syncTrace('company.b24.requisites_file.trace', [
                        'included' => false,
                        'reason' => 'missing_file_id',
                        'source_key' => 'OS_REQUSITES_FILE',
                    ]);
                } elseif (\array_key_exists('OS_REQUISITES_FILE', $data)) {
                    self::syncTrace('company.b24.requisites_file.trace', [
                        'included' => false,
                        'reason' => 'missing_file_id',
                        'source_key' => 'OS_REQUISITES_FILE',
                        'source_key_type' => \gettype($data['OS_REQUISITES_FILE']),
                    ]);
                }
            }

            return [
                'b24Fields' => $b24Fields,
                'rqInn' => $rqInn,
                'rqFullName' => $rqFullName,
            ];
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
                self::syncTrace('company.branch.head_b24_id.missing', [
                    'head_company_id' => (int) $headCompanyId,
                ]);
                return [
                    'success' => false,
                    'message' => 'Ошибка синхронизации с Bitrix24. Головная компания не имеет связи с CRM системой. Пожалуйста, обратитесь к персональному менеджеру для исправления данной ошибки.'
                ];
            }
            
            self::syncTrace('company.branch.head_b24_id.loaded', [
                'head_company_id' => (int) $headCompanyId,
                'field' => (string) CompanyB24Config::HEAD_COMPANY_B24_LINK_FIELD,
                'has_b24_id' => true,
            ]);
            
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

            self::syncTrace('company.branch.b24.create.start', [
                'head_company_id' => (int) $headCompanyId,
            ]);

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
                
                self::syncTrace('company.branch.contact.bound', [
                    'contact_id' => (int) $contactId,
                    'company_b24_id' => (int) ($dataCompany['ID'] ?? 0),
                ]);
            } else {
                self::syncTrace('company.branch.contact.skip_no_b24_user');
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
            self::syncTrace('company.branch.head_b24_id.assigned', [
                'new_company_id' => (int) $newCompanyId,
            ]);

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