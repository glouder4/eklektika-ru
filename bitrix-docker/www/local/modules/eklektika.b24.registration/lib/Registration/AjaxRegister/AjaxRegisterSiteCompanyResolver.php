<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use CIBlockElement;

/**
 * Сопоставление ИНН с карточкой компании в ИБ сайта (и уточнение через CRM при неоднозначности).
 */
final class AjaxRegisterSiteCompanyResolver
{
    private const COMPANY_IBLOCK_ID = 23;

    /**
     * @return array{status: 'none'|'exact'|'ambiguous', company: array<string, mixed>|null}
     */
    public static function resolveByInn(string $inn): array
    {
        $inn = AjaxRegisterPostParser::normalizeInn($inn);
        if ($inn === '' || (\strlen($inn) !== 10 && \strlen($inn) !== 12)) {
            return ['status' => 'none', 'company' => null];
        }

        $requestInnHash = self::safeInnHash((string) $inn);
        $iblockId = self::COMPANY_IBLOCK_ID;
        $filters = [
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAN_ENTITY_INN' => $inn],
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAL_ENTITY_INN' => $inn],
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_OS_COMPANY_INN' => $inn],
        ];
        $maxCandidateIdsForExact = 200;
        $candidateOverflow = false;
        $candidateIds = [];
        foreach ($filters as $filter) {
            $rs = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                $filter,
                false,
                false,
                ['ID']
            );
            while ($row = $rs->Fetch()) {
                $id = (int) ($row['ID'] ?? 0);
                if ($id > 0) {
                    $candidateIds[$id] = true;
                    if (\count($candidateIds) > $maxCandidateIdsForExact) {
                        $candidateOverflow = true;
                        break;
                    }
                }
            }
            if ($candidateOverflow) {
                break;
            }
        }
        if ($candidateOverflow) {
            self::logRegisterResolution('resolve_site_company_by_inn_ambiguous_overflow', [
                'inn_hash' => $requestInnHash,
                'prefilter_candidate_count' => \count($candidateIds),
                'exact_candidate_count' => 0,
                'candidate_limit' => $maxCandidateIdsForExact,
                'decision' => 'ambiguous',
                'reason_code' => 'prefilter_overflow_limit',
            ]);

            return ['status' => 'ambiguous', 'company' => null];
        }
        if ($candidateIds === []) {
            self::logRegisterResolution('resolve_site_company_by_inn_none', [
                'inn_hash' => $requestInnHash,
            ]);

            return ['status' => 'none', 'company' => null];
        }

        $candidates = [];
        foreach (\array_keys($candidateIds) as $candidateId) {
            $candidate = self::loadRegistrationInnCandidate((int) $candidateId, $iblockId, $inn);
            if ($candidate !== null && (bool) $candidate['is_exact']) {
                $candidates[] = $candidate;
            }
        }
        if ($candidates === []) {
            self::logRegisterResolution('resolve_site_company_by_inn_none_after_props', [
                'inn_hash' => $requestInnHash,
                'prefilter_candidate_count' => \count($candidateIds),
                'exact_candidate_count' => 0,
                'decision' => 'none',
                'reason_code' => 'none_after_props_no_exact',
                'company_ids' => \array_values(\array_map('intval', \array_keys($candidateIds))),
            ]);

            return ['status' => 'none', 'company' => null];
        }
        if (\count($candidates) === 1) {
            $resolved = $candidates[0];
            self::logRegisterResolution('resolve_site_company_by_inn_exact_single', [
                'inn_hash' => $requestInnHash,
                'company_id' => (int) $resolved['company_id'],
                'has_b24_id' => (int) ($resolved['b24_company_id'] ?? 0) > 0,
            ]);

            return ['status' => 'exact', 'company' => (array) $resolved['company']];
        }

        $withB24Id = \array_values(\array_filter($candidates, static function (array $candidate): bool {
            return (int) ($candidate['b24_company_id'] ?? 0) > 0;
        }));

        $crmTransportAvailable = \class_exists(\OnlineService\B24\RestClient::class);
        $verifiedInCrm = [];
        $crmValidationAttempted = false;
        if ($crmTransportAvailable) {
            foreach ($withB24Id as $candidate) {
                $b24Id = (int) ($candidate['b24_company_id'] ?? 0);
                if ($b24Id <= 0) {
                    continue;
                }
                $crmValidationAttempted = true;
                try {
                    $crmCompany = \OnlineService\B24\RestClient::callRestMethod('crm.company.get', ['id' => $b24Id], false);
                    if (\is_array($crmCompany) && (int) ($crmCompany['ID'] ?? 0) === $b24Id) {
                        $verifiedInCrm[] = $candidate;
                    }
                } catch (\Throwable $e) {
                    self::logRegisterResolution('resolve_site_company_by_inn_crm_get_error', [
                        'inn_hash' => $requestInnHash,
                        'b24_id' => $b24Id,
                        'error' => self::buildSafeCrmErrorContext($e),
                    ]);
                }
            }
        }

        $priorityPool = $verifiedInCrm;
        if (!$crmValidationAttempted && \count($withB24Id) === 1) {
            $priorityPool = $withB24Id;
        }
        if (\count($priorityPool) === 1) {
            $resolved = $priorityPool[0];
            self::logRegisterResolution('resolve_site_company_by_inn_exact_b24_priority', [
                'inn_hash' => $requestInnHash,
                'company_id' => (int) $resolved['company_id'],
                'b24_id' => (int) $resolved['b24_company_id'],
                'crm_validation_attempted' => $crmValidationAttempted,
            ]);

            return ['status' => 'exact', 'company' => (array) $resolved['company']];
        }

        if (\count($priorityPool) > 1 && $crmTransportAvailable) {
            $narrowedByRequisite = [];
            foreach ($priorityPool as $candidate) {
                $b24Id = (int) ($candidate['b24_company_id'] ?? 0);
                if ($b24Id <= 0) {
                    continue;
                }
                try {
                    $requisites = \OnlineService\B24\RestClient::callRestMethod('crm.requisite.list', [
                        'select' => ['ID', 'ENTITY_ID', 'RQ_INN'],
                        'filter' => [
                            'ENTITY_TYPE_ID' => 4,
                            'RQ_INN' => $inn,
                            'ENTITY_ID' => $b24Id,
                        ],
                    ], false);
                    if (\is_array($requisites) && !empty($requisites)) {
                        $narrowedByRequisite[] = $candidate;
                    }
                } catch (\Throwable $e) {
                    self::logRegisterResolution('resolve_site_company_by_inn_requisite_error', [
                        'inn_hash' => $requestInnHash,
                        'b24_id' => $b24Id,
                        'error' => self::buildSafeCrmErrorContext($e),
                    ]);
                }
            }
            if (\count($narrowedByRequisite) === 1) {
                $resolved = $narrowedByRequisite[0];
                self::logRegisterResolution('resolve_site_company_by_inn_exact_requisite', [
                    'inn_hash' => $requestInnHash,
                    'company_id' => (int) $resolved['company_id'],
                    'b24_id' => (int) $resolved['b24_company_id'],
                ]);

                return ['status' => 'exact', 'company' => (array) $resolved['company']];
            }
            if (\count($narrowedByRequisite) > 1) {
                self::logRegisterResolution('resolve_site_company_by_inn_ambiguous_requisite', [
                    'inn_hash' => $requestInnHash,
                    'prefilter_candidate_count' => \count($candidateIds),
                    'exact_candidate_count' => \count($candidates),
                    'requisite_candidate_count' => \count($narrowedByRequisite),
                    'decision' => 'ambiguous',
                    'reason_code' => 'ambiguous_after_requisite',
                    'company_ids' => \array_values(\array_map(static function (array $candidate): int {
                        return (int) ($candidate['company_id'] ?? 0);
                    }, $narrowedByRequisite)),
                    'b24_ids' => \array_values(\array_map(static function (array $candidate): int {
                        return (int) ($candidate['b24_company_id'] ?? 0);
                    }, $narrowedByRequisite)),
                ]);

                return ['status' => 'ambiguous', 'company' => null];
            }
        }

        self::logRegisterResolution('resolve_site_company_by_inn_ambiguous', [
            'inn_hash' => $requestInnHash,
            'prefilter_candidate_count' => \count($candidateIds),
            'exact_candidate_count' => \count($candidates),
            'decision' => 'ambiguous',
            'reason_code' => 'ambiguous_exact_candidates',
            'candidates_with_b24' => \count($withB24Id),
            'candidates_verified_in_crm' => \count($verifiedInCrm),
            'company_ids' => \array_values(\array_map(static function (array $candidate): int {
                return (int) ($candidate['company_id'] ?? 0);
            }, $candidates)),
        ]);

        return ['status' => 'ambiguous', 'company' => null];
    }

    private static function logRegisterResolution(string $event, array $context = []): void
    {
        $logPath = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/local/logs/ajax-register-action.log';
        if ($logPath === '/local/logs/ajax-register-action.log') {
            return;
        }

        $payload = [
            'ts' => \date('c'),
            'event' => $event,
            'context' => $context,
        ];
        @\file_put_contents($logPath, \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    private static function safeInnHash(string $value): string
    {
        return \substr(\sha1($value), 0, 8);
    }

    /**
     * @return array{error_type: string, error_code: string, error_fingerprint: string}
     */
    private static function buildSafeCrmErrorContext(\Throwable $e): array
    {
        return [
            'error_type' => \get_class($e),
            'error_code' => \is_scalar($e->getCode()) ? (string) $e->getCode() : '0',
            'error_fingerprint' => \substr(\sha1(\get_class($e) . '|' . (string) $e->getCode()), 0, 12),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadRegistrationInnCandidate(int $companyId, int $iblockId, string $inn): ?array
    {
        $rsEl = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'ID' => $companyId], false, ['nTopCount' => 1], ['ID', 'NAME']);
        $el = $rsEl->Fetch();
        if (!$el) {
            return null;
        }

        $company = [
            'id' => $companyId,
            'inn' => $inn,
            'name' => \trim((string) ($el['NAME'] ?? '')),
            'address' => '',
            'activity' => '',
            'site' => '',
        ];
        $resolvedInnHashes = [];
        $b24CompanyId = 0;
        $dbProps = CIBlockElement::GetProperty($iblockId, $companyId, ['sort' => 'asc']);
        while ($prop = $dbProps->Fetch()) {
            $code = (string) ($prop['CODE'] ?? '');
            $val = $prop['VALUE'] ?? '';
            $val = \is_array($val) ? \trim((string) ($val[0] ?? '')) : \trim((string) $val);
            if ($code === 'LEGAN_ENTITY_NAME' || $code === 'LEGAL_ENTITY_NAME' || $code === 'OS_COMPANY_NAME') {
                if ($val !== '') {
                    $company['name'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_ADRESS' || $code === 'LEGAL_ENTITY_ADRESS' || $code === 'OS_COMPANY_JUR_ADDRESS') {
                if ($val !== '') {
                    $company['address'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_ACTIVITY' || $code === 'LEGAL_ENTITY_ACTIVITY' || $code === 'OS_COMPANY_ACTIVITY') {
                if ($val !== '') {
                    $company['activity'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_WWW' || $code === 'LEGAL_ENTITY_WWW' || $code === 'OS_COMPANY_WEB_SITE') {
                if ($val !== '') {
                    $company['site'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_INN' || $code === 'LEGAL_ENTITY_INN' || $code === 'OS_COMPANY_INN') {
                $normalizedPropInn = AjaxRegisterPostParser::normalizeInn($val);
                if ($normalizedPropInn !== '') {
                    $resolvedInnHashes[] = self::safeInnHash((string) $normalizedPropInn);
                }
            } elseif ($code === 'OS_COMPANY_B24_ID') {
                $b24CompanyId = (int) $val;
            }
        }

        $resolvedInnHashes = \array_values(\array_unique($resolvedInnHashes));
        $requestInnHash = self::safeInnHash((string) $inn);
        $isExactByProps = \in_array($requestInnHash, $resolvedInnHashes, true);

        return [
            'company' => $company,
            'is_exact' => $isExactByProps,
            'b24_company_id' => $b24CompanyId,
            'company_id' => $companyId,
            'resolved_inn_hashes' => $resolvedInnHashes,
            'request_inn_hash' => $requestInnHash,
        ];
    }
}

