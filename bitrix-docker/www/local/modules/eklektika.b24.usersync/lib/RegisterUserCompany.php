<?php
namespace OnlineService\B24;
use intec\eklectika\advertising_agent\Company;
use OnlineService\B24\UserSync\Config\RegisterUserCompanyConfig;
use OnlineService\B24\UserSync\Config\UserSyncConfig;
use OnlineService\B24\User;
use OnlineService\B24\Request;
use OnlineService\Sync\FromCrm\CrmInboundUfMap;
class RegisterUserCompany extends Request{ 
    private int $lastSyncedCompanyB24Id = 0;
    private int $lastSyncedContactB24Id = 0;
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

    private function createCompanyElement($params)
    {
        $company = new \OnlineService\Site\Company();

        return $company->createCompanyElement($params);
    }

    /**
     * Единственная точка записи локальной связи компании с B24 в сценарии регистрации.
     * Создаёт/обновляет карточку в ИБ 23 только при уже существующей компании в B24 (companyId из CRM, этот хук после contact.add).
     * После успешного createCompanyElement пишет в crm.company UF с ID элемента на сайте (без догадок по getCompanyByB24ID).
     */
    private function upsertSiteCompanyLinkByB24Id(int $companyId, array $arFields, array &$dataContact): void
    {
        if ($companyId <= 0) {
            return;
        }

        $companyElementParams = [
            'OS_COMPANY_INN' => $arFields['UF_INN'],
            'OS_COMPANY_WEB_SITE' => $arFields['UF_SITE'],
            'OS_COMPANY_NAME' => $arFields['UF_NAME_COMPANY'],
            'OS_COMPANY_EMAIL' => $arFields['EMAIL'],
            'OS_COMPANY_PHONE' => $arFields['PERSONAL_PHONE'],
            'OS_COMPANY_B24_ID' => $companyId,
            'OS_COMPANY_CITY' => $arFields['UF_CITY'],
            'OS_COMPANY_ACTIVITY' => $arFields['UF_SPERE'] ?? '',
            'OS_COMPANY_JUR_ADDRESS' => $arFields['UF_JUR_ADDRESS'] ?? '',
            'OS_REQUSITES_FILE' => $this->getConfiguredFieldValue($arFields, RegisterUserCompanyConfig::getRequisitesFileField()),
            'LEGAN_MAIN_PHONE' => (string)($arFields['UF_MAIN_PHONE'] ?? ($arFields['WORK_PHONE'] ?? '')),
            'LEGAN_MOBILE_PHONE' => (string)($arFields['UF_MOBILE_PHONE'] ?? ($arFields['PERSONAL_PHONE'] ?? '')),
        ];

        if (isset($arFields['USER_ID'])) {
            $companyElementParams['USER_ID'] = $arFields['USER_ID'];
        }

        $iblockElementId = $this->createCompanyElement($companyElementParams);
        if ($iblockElementId === false || (int) $iblockElementId <= 0) {
            $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H_uf_link', 'RegisterUserCompany::upsertSiteCompanyLinkByB24Id', 'createCompanyElement failed, skip crm UF + check USER_ID and B24 id', [
                'b24_company_id' => $companyId,
            ]);

            return;
        }
        $result = $this->callB24Method('crm.company.update', [
            'id' => $companyId,
            'fields' => [
                CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF => (string) (int) $iblockElementId,
            ],
        ], false);
        if (\is_array($result) && \array_key_exists('success', $result) && (int) $result['success'] === 0) {
            $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H_uf_link', 'RegisterUserCompany::upsertSiteCompanyLinkByB24Id', 'crm.company.update company site id UF failed', [
                'b24_company_id' => $companyId,
                'site_element_id' => (int) $iblockElementId,
                'error' => $result['error'] ?? '',
            ]);
        }
    }

    private function callB24Method($method, array $params, $debug = false)
    {
        return \OnlineService\B24\RestClient::callRestMethod($method, $params, (bool) $debug);
    }

    private function getConfiguredFieldValue(array $arFields, $fieldName)
    {
        return $arFields[$fieldName] ?? null;
    }

    /**
     * Мультиполя PHONE / EMAIL для crm.*.add: тип WORK, пустые VALUE не передаём (B24 иначе может не записать).
     *
     * @return array{PHONE: list<array{VALUE: string, VALUE_TYPE: string}>, EMAIL: list<array{VALUE: string, VALUE_TYPE: string}>}
     */
    private function buildB24CrmWorkPhoneAndEmailFields(array $arFields): array
    {
        $phone = \trim((string)($arFields['PERSONAL_PHONE'] ?? ''));
        $email = \trim((string)($arFields['EMAIL'] ?? ''));
        $out = [
            'PHONE' => [],
            'EMAIL' => [],
        ];
        if ($phone !== '') {
            $out['PHONE'][] = ['VALUE' => $phone, 'VALUE_TYPE' => 'WORK'];
        }
        if ($email !== '') {
            $out['EMAIL'][] = ['VALUE' => $email, 'VALUE_TYPE' => 'WORK'];
        }

        return $out;
    }

    private function findSiteCompanyByInn(string $inn): array
    {
        $inn = self::normalizeInnValue($inn);
        if ($inn === '') {
            return [];
        }

        $iblockId = 23;
        $baseSelect = ['ID', 'NAME', 'CODE', 'XML_ID', 'PROPERTY_OS_COMPANY_B24_ID'];
        $filters = [
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAN_ENTITY_INN' => $inn],
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_OS_COMPANY_INN' => $inn],
        ];

        foreach ($filters as $filter) {
            $rs = \CIBlockElement::GetList(['ID' => 'ASC'], $filter, false, ['nTopCount' => 1], $baseSelect);
            if ($row = $rs->Fetch()) {
                $resolvedInnHashes = [];
                $dbProps = \CIBlockElement::GetProperty($iblockId, (int)($row['ID'] ?? 0), ['sort' => 'asc']);
                while ($prop = $dbProps->Fetch()) {
                    $code = (string)($prop['CODE'] ?? '');
                    if ($code !== 'LEGAN_ENTITY_INN' && $code !== 'LEGAL_ENTITY_INN' && $code !== 'OS_COMPANY_INN') {
                        continue;
                    }
                    $propInn = self::normalizeInnValue((string)($prop['VALUE'] ?? ''));
                    if ($propInn !== '') {
                        $resolvedInnHashes[] = \substr(\sha1($propInn), 0, 8);
                    }
                }
                $resolvedInnHashes = \array_values(\array_unique($resolvedInnHashes));
                $innHash = \substr(\sha1($inn), 0, 8);
                $isExactByProps = \in_array($innHash, $resolvedInnHashes, true);
                if (!$isExactByProps) {
                    continue;
                }
                return [
                    'ID' => (int)($row['ID'] ?? 0),
                    'CODE' => (string)($row['CODE'] ?? ''),
                    'XML_ID' => (string)($row['XML_ID'] ?? ''),
                    'OS_COMPANY_B24_ID' => (string)($row['PROPERTY_OS_COMPANY_B24_ID_VALUE'] ?? ''),
                ];
            }
        }

        return [];
    }

    private static function normalizeInnValue($inn): string
    {
        return (string)\preg_replace('/\D+/', '', (string)$inn);
    }

    private function resolveExactCompanyIdByInnFromRequisites($requisites, string $targetInn): int
    {
        if (!\is_array($requisites)) {
            return 0;
        }
        $normalizedTargetInn = self::normalizeInnValue($targetInn);
        if ($normalizedTargetInn === '') {
            return 0;
        }
        foreach ($requisites as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $entityId = (int)($row['ENTITY_ID'] ?? 0);
            if ($entityId <= 0) {
                continue;
            }
            $rowInn = self::normalizeInnValue($row['RQ_INN'] ?? '');
            if ($rowInn === '' || $rowInn !== $normalizedTargetInn) {
                continue;
            }

            return $entityId;
        }

        return 0;
    }

    private function enforceCompanyInnInRequisites(int $companyId, array $arFields, string $hypothesisId = 'H19'): void
    {
        if ($companyId <= 0) {
            return;
        }

        $companyRequisites = $this->callB24Method('crm.requisite.list', [
            'fields' => [],
            'params' => [],
            'select' => ['ID', 'RQ_INN', 'ENTITY_ID', 'ENTITY_TYPE_ID', 'NAME'],
            'filter' => [
                'ENTITY_TYPE_ID' => 4,
                'ENTITY_ID' => $companyId,
            ],
        ], false);
        // #region agent log
        $this->agentDebugLog('company_sync_' . date('Ymd_His'), $hypothesisId, 'RegisterUserCompany.php:enforceCompanyInnInRequisites', 'company requisites before enforce RQ_INN', [
            'company_id' => $companyId,
            'requisites_count' => is_array($companyRequisites) ? count($companyRequisites) : -1,
            'first_req_id' => is_array($companyRequisites) ? (int)($companyRequisites[0]['ID'] ?? 0) : 0,
            'first_req_rq_inn_len' => is_array($companyRequisites) ? strlen((string)($companyRequisites[0]['RQ_INN'] ?? '')) : 0,
        ]);
        // #endregion
        if (!is_array($companyRequisites)) {
            return;
        }

        foreach ($companyRequisites as $requisiteRow) {
            $requisiteId = (int)($requisiteRow['ID'] ?? 0);
            $requisiteInn = (string)($requisiteRow['RQ_INN'] ?? '');
            if ($requisiteId <= 0 || $requisiteInn !== '') {
                continue;
            }

            $forceUpdateResult = $this->callB24Method('crm.requisite.update', [
                'id' => $requisiteId,
                'fields' => [
                    'ENTITY_ID' => $companyId,
                    'ENTITY_TYPE_ID' => 4,
                    'RQ_INN' => (string)$arFields['UF_INN'],
                    'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY'],
                ],
            ], false);
            // #region agent log
            $this->agentDebugLog('company_sync_' . date('Ymd_His'), $hypothesisId, 'RegisterUserCompany.php:enforceCompanyInnInRequisites', 'forced RQ_INN update for empty requisite', [
                'company_id' => $companyId,
                'requisite_id' => $requisiteId,
                'rq_inn_len' => strlen((string)$arFields['UF_INN']),
                'update_result_type' => gettype($forceUpdateResult),
                'update_result_scalar' => is_scalar($forceUpdateResult) ? (string)$forceUpdateResult : '[non-scalar]',
            ]);
            // #endregion
        }
    }

    private function createB24Company($arFields){
        global $APPLICATION;
        $this->lastSyncedCompanyB24Id = 0;
        $this->lastSyncedContactB24Id = 0;
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
                    $arFields[RegisterUserCompanyConfig::getRequisitesFileField()] = [
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

        $phoneEmailForCrm = $this->buildB24CrmWorkPhoneAndEmailFields($arFields);

        // данные для контакта
        $dataContact = [
            'fields' => [
                'NAME' => $arFields['NAME'],
                'SECOND_NAME' => $arFields['SECOND_NAME'],
                'LAST_NAME' => $arFields['LAST_NAME'],
                'POST' => $arFields['WORK_POSITION'],
                'BIRTHDATE' => $arFields['PERSONAL_BIRTHDAY'],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => RegisterUserCompanyConfig::ASSIGNED_BY_ID,
                RegisterUserCompanyConfig::CRM_CONTACT_CITY_FIELD => $arFields['UF_CITY'],
            ],
            'params' => []
        ];
        if (!empty($phoneEmailForCrm['PHONE'])) {
            $dataContact['fields']['PHONE'] = $phoneEmailForCrm['PHONE'];
        }
        if (!empty($phoneEmailForCrm['EMAIL'])) {
            $dataContact['fields']['EMAIL'] = $phoneEmailForCrm['EMAIL'];
        }

        // если это компания или рекламынй агент
        if ($arFields['UF_TYPE'] == '5' || $arFields['UF_TYPE'] == '6') {
            // проверить заполненность ИНН и названия компании
            if (empty($arFields['UF_INN']) && empty($arFields['UF_NAME_COMPANY'])) {
                $APPLICATION->ThrowException('Вы регистрируйтесь как рекламный агент или юридическое лицо. Поля "Название компании", "ИНН организации" обязательно для заполнения!');
                return false;
            } else {
                // если это рекламный агент
                if ($arFields['UF_ADVERSTERING_AGENT'] == 'on') {
                    $dataContact['fields'][RegisterUserCompanyConfig::CRM_CONTACT_NOTE_FIELD] = RegisterUserCompanyConfig::REGISTRATION_NOTE_AD_AGENT;
                }
                $dataRequisite = [];
                $localCompany = $this->findSiteCompanyByInn((string)$arFields['UF_INN']);
                $localB24Id = (int)($localCompany['OS_COMPANY_B24_ID'] ?? 0);
                $resolvedByLocalB24Id = false;
                $localBindingExists = !empty($localCompany) && $localB24Id > 0;
                // #region agent log
                $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H20', 'RegisterUserCompany.php:createB24Company', 'local company lookup by INN', [
                    'inn_len' => strlen((string)($arFields['UF_INN'] ?? '')),
                    'local_company_found' => !empty($localCompany),
                    'local_company_id' => (int)($localCompany['ID'] ?? 0),
                    'local_b24_id' => $localB24Id,
                ]);
                // #endregion
                if ($localBindingExists) {
                    $companyById = $this->callB24Method('crm.company.get', ['id' => $localB24Id], false);
                    $resolvedByLocalB24Id = is_array($companyById) && (int)($companyById['ID'] ?? 0) > 0;
                    // #region agent log
                    $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H20', 'RegisterUserCompany.php:createB24Company', 'B24 company lookup by local OS_COMPANY_B24_ID', [
                        'local_b24_id' => $localB24Id,
                        'resolved' => $resolvedByLocalB24Id,
                    ]);
                    // #endregion
                    if ($resolvedByLocalB24Id) {
                        $dataRequisite = [[
                            'ID' => 0,
                            'RQ_INN' => '',
                            'ENTITY_ID' => $localB24Id,
                        ]];
                        $companyId = $localB24Id;
                        $dataContact['fields']['COMPANY_ID'] = $localB24Id;
                        $this->enforceCompanyInnInRequisites($localB24Id, $arFields, 'H19');
                    } else {
                        $APPLICATION->ThrowException('Не удалось подтвердить связь компании с CRM по сохраненному B24 ID.');
                        return false;
                    }
                }

                if (!$resolvedByLocalB24Id && !$localBindingExists) {
                    $requisiteByInnQuery = [
                        'fields' => [],
                        'params' => [],
                        'select' => [
                            'ID',
                            'RQ_INN',
                            'ENTITY_TYPE_ID',
                            'ENTITY_ID'
                        ],
                        'filter' => [
                            'ENTITY_TYPE_ID' => 4,
                            'RQ_INN' => $arFields['UF_INN']
                        ]
                    ];
                    // найти реквизит по ИНН только если нет валидной локальной связки B24_ID
                    $dataRequisite = $this->callB24Method("crm.requisite.list", $requisiteByInnQuery, false);
                }
                // #region agent log
                $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H17', 'RegisterUserCompany.php:createB24Company', 'crm.requisite.list result', [
                    'inn_len' => strlen((string)($arFields['UF_INN'] ?? '')),
                    'result_type' => gettype($dataRequisite),
                    'result_count' => is_array($dataRequisite) ? count($dataRequisite) : -1,
                    'first_entity_id' => is_array($dataRequisite) ? (int)($dataRequisite[0]['ENTITY_ID'] ?? 0) : 0,
                    'first_rq_inn_len' => is_array($dataRequisite) ? strlen((string)($dataRequisite[0]['RQ_INN'] ?? '')) : 0,
                ]);
                // #endregion

                if (!empty($dataRequisite)) {
                    $candidateCompanyId = $this->resolveExactCompanyIdByInnFromRequisites($dataRequisite, (string)$arFields['UF_INN']);
                    $candidateCompanyGet = $candidateCompanyId > 0
                        ? $this->callB24Method('crm.company.get', ['id' => $candidateCompanyId], false)
                        : null;
                    $candidateCompanyExists = is_array($candidateCompanyGet) && (int)($candidateCompanyGet['ID'] ?? 0) > 0;
                    // #region agent log
                    $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H28', 'RegisterUserCompany.php:createB24Company', 'Validate ENTITY_ID from crm.requisite.list via crm.company.get', [
                        'candidate_company_id' => $candidateCompanyId,
                        'candidate_company_exists' => $candidateCompanyExists,
                        'candidate_company_get_type' => gettype($candidateCompanyGet),
                    ]);
                    // #endregion

                    if (!$candidateCompanyExists) {
                        // #region agent log
                        $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H28', 'RegisterUserCompany.php:createB24Company', 'Phantom company id from requisite list ignored, fallback to company creation', [
                            'candidate_company_id' => $candidateCompanyId,
                        ]);
                        // #endregion
                        $dataRequisite = [];
                    } else {
                        $dataContact['fields']['COMPANY_ID'] = $candidateCompanyId;
                        $companyId = $candidateCompanyId;
                        $this->enforceCompanyInnInRequisites((int)$companyId, $arFields, 'H19');
                    }
                } else {
                    $crmCompanyWebField = 'UF_CRM_1777119084064';
                    $crmCompanyMainPhoneField = 'UF_CRM_1777069666894';
                    $crmCompanyMobilePhoneField = 'UF_CRM_1777069676348';
                    $crmCompanySphereField = 'UF_CRM_1777119807943';
                    $crmCompanyJurAddressField = 'UF_CRM_1777120939583';
                    $crmCompanyCityField = RegisterUserCompanyConfig::CRM_COMPANY_CITY_FIELD;
                    /*Создание компании*/
                    $peCompany = $this->buildB24CrmWorkPhoneAndEmailFields($arFields);
                    $qrCompanyInfo = [
                        'fields' => [
                            'TITLE' => $arFields['UF_NAME_COMPANY'],
                            'WEB' => [[
                                'VALUE' => $arFields['UF_SITE'],
                                "VALUE_TYPE" => "WORK"
                            ]],
                            $crmCompanyWebField => $arFields['UF_SITE'],
                            $crmCompanySphereField => $arFields['UF_SPERE'],
                            $crmCompanyJurAddressField => $arFields['UF_JUR_ADDRESS'],
                            $crmCompanyCityField => $arFields['UF_CITY'],
                            $crmCompanyMainPhoneField => (string)($arFields['UF_MAIN_PHONE'] ?? ($arFields['WORK_PHONE'] ?? '')),
                            $crmCompanyMobilePhoneField => (string)($arFields['UF_MOBILE_PHONE'] ?? ($arFields['PERSONAL_PHONE'] ?? '')),
                            RegisterUserCompanyConfig::CRM_REQUISITES_FILE_FIELD => $this->getConfiguredFieldValue($arFields, RegisterUserCompanyConfig::getRequisitesFileField()),
                            'COMPANY_TYPE' => 'CUSTOMER',
                            'ASSIGNED_BY_ID' => RegisterUserCompanyConfig::ASSIGNED_BY_ID,
                        ]
                    ];
                    if (!empty($peCompany['PHONE'])) {
                        $qrCompanyInfo['fields']['PHONE'] = $peCompany['PHONE'];
                    }
                    if (!empty($peCompany['EMAIL'])) {
                        $qrCompanyInfo['fields']['EMAIL'] = $peCompany['EMAIL'];
                    }

                    $companyId = $this->callB24Method("crm.company.add", $qrCompanyInfo);
                    // #region agent log
                    $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H17', 'RegisterUserCompany.php:createB24Company', 'crm.company.add result', [
                        'result_type' => gettype($companyId),
                        'result_value' => is_scalar($companyId) ? (string)$companyId : '[non-scalar]',
                    ]);
                    // #endregion
					
                    if (!empty($companyId)) {
                        $qrCompany['id'] = $companyId;
                        $dataCompany = $this->callB24Method("crm.company.get", $qrCompany);
                        // #region agent log
                        $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H17', 'RegisterUserCompany.php:createB24Company', 'crm.company.get result', [
                            'company_id' => (int)$companyId,
                            'has_id' => (int)($dataCompany['ID'] ?? 0) > 0,
                            'has_entity_id' => array_key_exists('ENTITY_ID', (array)$dataCompany),
                        ]);
                        // #endregion

                        /*Добавление реквизита к компании*/
                        $qrRequisite = [
                            'fields' => [
                                'ENTITY_ID' => $dataCompany['ID'],
                                'ENTITY_TYPE_ID' => 4,
                                'NAME' => 'Реквизит с формы сайта',
                                'PRESET_ID' => 1,
                                'ACTIVE' => 'Y',
                                'RQ_INN' => (string)$arFields['UF_INN'],
                                'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY'],
                            ]
                        ];
                        $requisiteId = $this->callB24Method("crm.requisite.add", $qrRequisite);
                        // #region agent log
                        $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H17', 'RegisterUserCompany.php:createB24Company', 'crm.requisite.add result', [
                            'result_type' => gettype($requisiteId),
                            'result_value' => is_scalar($requisiteId) ? (string)$requisiteId : '[non-scalar]',
                            'company_id_for_requisite' => (int)($dataCompany['ID'] ?? 0),
                        ]);
                        // #endregion

                        /*Обновление реквизитов у компании*/
                        $requisiteUpdateResult = null;
                        if (!empty($requisiteId)) {
                            $qrRequisites = array(
                                'id' => $requisiteId,
                                'fields' => [
                                    'ENTITY_ID' => (int)$dataCompany['ID'],
                                    'ENTITY_TYPE_ID' => 4,
                                    'RQ_INN' => (string)$arFields['UF_INN'],
                                    'RQ_KPP' => (string)$arFields['UF_KPP'],
                                    'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY']
                                ]
                            );
                            $requisiteUpdateResult = $this->callB24Method("crm.requisite.update", $qrRequisites);
                        }
                        // #region agent log
                        $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H17', 'RegisterUserCompany.php:createB24Company', 'crm.requisite.update result', [
                            'update_result_type' => gettype($requisiteUpdateResult),
                            'update_result_scalar' => is_scalar($requisiteUpdateResult) ? (string)$requisiteUpdateResult : '[non-scalar]',
                            'update_payload_entity_id' => isset($qrRequisites) ? (string)($qrRequisites['fields']['ENTITY_ID'] ?? '') : '',
                            'update_payload_rq_inn_len' => isset($qrRequisites) ? strlen((string)($qrRequisites['fields']['RQ_INN'] ?? '')) : 0,
                            'requisite_id_present' => !empty($requisiteId),
                        ]);
                        // #endregion

                        $dataContact['fields']['COMPANY_ID'] = $dataCompany['ID'];


                        /*\OnlineService\Site\Company::updateB24Company([
                            'ID' => $companyId,
                            'UF_CRM_1755643990423' => $reqFile
                        ]);*/
                    }
                }
            }
        }

        $siteUserId = (int)($arFields['USER_ID'] ?? 0);
        if ($siteUserId > 1) {
            $dataContact['fields'][RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD] = $siteUserId;
        }

        $contactId = $this->callB24Method("crm.contact.add", $dataContact);
        // #region agent log
        $this->agentDebugLog('company_sync_' . date('Ymd_His'), 'H28', 'RegisterUserCompany.php:createB24Company', 'crm.contact.add result', [
            'result_type' => gettype($contactId),
            'result_value' => is_scalar($contactId) ? (string)$contactId : '[non-scalar]',
        ]);
        // #endregion

        if (!empty($companyId) && !empty($contactId)) {
            // добавить контакт в компанию
            $qrCompanyAddContact = [
                'fields' => ['COMPANY_ID' => $companyId],
                'id' => $contactId
            ];
            $this->callB24Method("crm.contact.company.add", $qrCompanyAddContact);
            // Локальные сущности создаём только после успешного обмена с B24.
            $this->upsertSiteCompanyLinkByB24Id((int)$companyId, $arFields, $dataContact);
            $this->lastSyncedCompanyB24Id = (int)$companyId;
            $this->lastSyncedContactB24Id = (int)$contactId;
            return true;
        }
        $APPLICATION->ThrowException('Не удалось завершить регистрацию в CRM: отсутствует ID компании или контакта.');
        return false;
    } 


    public function OnBeforeUserRegisterHandler(&$arFields) {
        global $APPLICATION;

        $debugRunId = 'beforeReg_' . date('Ymd_His') . '_' . substr(md5((string)($arFields['EMAIL'] ?? '') . (string)($arFields['PERSONAL_PHONE'] ?? '')), 0, 8);
        // #region agent log
        $this->agentDebugLog($debugRunId, 'H12', 'RegisterUserCompany.php:OnBeforeUserRegisterHandler', 'OnBeforeUserRegisterHandler entered', [
            'has_email' => !empty($arFields['EMAIL']),
            'phone' => (string)($arFields['PERSONAL_PHONE'] ?? ''),
        ]);
        // #endregion
        $arFields['ACTIVE'] = 'N';

        $response = $this->isUserRegistered($arFields);
        // #region agent log
        $this->agentDebugLog($debugRunId, 'H12', 'RegisterUserCompany.php:isUserRegistered.before', 'isUserRegistered in OnBeforeUserRegisterHandler', [
            'response_empty' => empty($response),
            'response_type' => gettype($response),
        ]);
        // #endregion

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
            $APPLICATION->ThrowException('Указанные пароли не совпадают.');
            return false;
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
        $debugRunId = 'afterReg_' . date('Ymd_His') . '_' . substr(md5((string)($arFields['EMAIL'] ?? '') . (string)($arFields['USER_ID'] ?? 0)), 0, 8);
        // #region agent log
        $this->agentDebugLog($debugRunId, 'H4', 'RegisterUserCompany.php:OnAfterUserRegisterHandler', 'OnAfterUserRegisterHandler entered', [
            'user_id' => (int)($arFields['USER_ID'] ?? 0),
            'has_email' => !empty($arFields['EMAIL']),
        ]);
        // #endregion
        // если регистрация успешна то
        if($arFields["USER_ID"]>0)
        {
            $response = $this->isUserRegistered($arFields,false);
            // #region agent log
            $this->agentDebugLog($debugRunId, 'H4', 'RegisterUserCompany.php:isUserRegistered', 'isUserRegistered before createB24Company', [
                'response_type' => gettype($response),
                'response_empty' => empty($response),
            ]);
            // #endregion

            if( !$response ){
                $createResult = $this->createB24Company($arFields);
                // #region agent log
                $this->agentDebugLog($debugRunId, 'H5', 'RegisterUserCompany.php:createB24Company', 'createB24Company finished', [
                    'create_result' => $createResult === false ? 'false' : 'true',
                ]);
                // #endregion

                $response = $this->isUserRegistered($arFields,false);
            }

            if( $response ){
                $contactId = $response['ID'];

                // Обновляем пользователя: ID контакта B24 в каноническом UF и в легаси-поле
                $targetUserId = (int)($arFields["USER_ID"] ?? 0);
                $targetUserBefore = $targetUserId > 0 ? \CUser::GetByID($targetUserId)->Fetch() : [];
                // #region agent log
                $this->agentDebugLog($debugRunId, 'H21', 'RegisterUserCompany.php:OnAfterUserRegisterHandler.beforeUpdate', 'About to update user ACTIVE/contact binding', [
                    'target_user_id' => $targetUserId,
                    'target_login' => (string)($targetUserBefore['LOGIN'] ?? ''),
                    'target_active_before' => (string)($targetUserBefore['ACTIVE'] ?? ''),
                    'contact_id' => (int)$contactId,
                ]);
                // #endregion
                if ($targetUserId <= 1) {
                    // #region agent log
                    $this->agentDebugLog($debugRunId, 'H21', 'RegisterUserCompany.php:OnAfterUserRegisterHandler.beforeUpdate', 'Skip protected user update in registration handler', [
                        'target_user_id' => $targetUserId,
                    ]);
                    // #endregion
                    return;
                }
                $user = new \CUser;
                $updated = $user->Update($targetUserId, [
                    'ACTIVE' => 'N',
                    UserSyncConfig::USER_UF_CONTACT_B24_ID => $contactId,
                    UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY => $contactId,
                ]);
                $targetUserAfter = $targetUserId > 0 ? \CUser::GetByID($targetUserId)->Fetch() : [];
                // #region agent log
                $this->agentDebugLog($debugRunId, 'H21', 'RegisterUserCompany.php:OnAfterUserRegisterHandler.afterUpdate', 'User update ACTIVE/contact binding result', [
                    'target_user_id' => $targetUserId,
                    'update_ok' => (bool)$updated,
                    'update_error' => $updated ? '' : (string)($user->LAST_ERROR ?? ''),
                    'target_active_after' => (string)($targetUserAfter['ACTIVE'] ?? ''),
                ]);
                // #endregion

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

    /**
     * Безопасный путь синхронизации из ajax-register-action:
     * создаёт/находит компанию и контакт в B24, но не трогает ACTIVE у пользователей сайта.
     */
    public function syncFromSiteRegistration(array $arFields): bool
    {
        $debugRunId = 'safeSync_' . date('Ymd_His') . '_' . substr(md5((string)($arFields['EMAIL'] ?? '') . (string)($arFields['USER_ID'] ?? 0)), 0, 8);
        // #region agent log
        $this->agentDebugLog($debugRunId, 'H27', 'RegisterUserCompany.php:syncFromSiteRegistration', 'Safe sync entered', [
            'user_id' => (int)($arFields['USER_ID'] ?? 0),
            'has_inn' => !empty($arFields['UF_INN']),
            'has_company_name' => !empty($arFields['UF_NAME_COMPANY']),
        ]);
        // #endregion
        $result = $this->createB24Company($arFields);
        if ($result !== false) {
            $targetUserId = (int)($arFields['USER_ID'] ?? 0);
            if ($targetUserId > 1 && $this->lastSyncedContactB24Id > 0) {
                $user = new \CUser();
                $user->Update($targetUserId, [
                    UserSyncConfig::USER_UF_CONTACT_B24_ID => $this->lastSyncedContactB24Id,
                    UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY => $this->lastSyncedContactB24Id,
                ]);
            }
        }
        // #region agent log
        $this->agentDebugLog($debugRunId, 'H27', 'RegisterUserCompany.php:syncFromSiteRegistration', 'Safe sync finished', [
            'result' => $result === false ? 'false' : 'true',
        ]);
        // #endregion
        return $result !== false;
    }
    
    private function deleteStaffB24($arUser, $companyId, $idCompanySite) {
        $qrList = [
            'fields' => [],
            'params' => [],
            'select' => [],
            'filter' => ["EMAIL" => $arUser["EMAIL"]]
        ];
        $arResult = $this->callB24Method("crm.contact.list", $qrList);

        if ($arResult['ID']) {
            // убрать рекламную агентность		
            $this->callB24Method("crm.contact.update", [
                "id" => $arResult['ID'],
                "fields" => [
                    RegisterUserCompanyConfig::CRM_CONTACT_AD_AGENT_FIELD => ''
                ]
            ]);
            intec\eklectika\advertising_agent\Client::eraseStatusRA($arUser["ID"], $idCompanySite);

            // уволить его!		
            $this->callB24Method("crm.contact.company.delete", [
                'id' => $arResult['ID'],
                'fields' => array('COMPANY_ID' => $companyId),
            ]);
            // прощай сотрудник, ты больше нам не нужен =(
        }
    }
}