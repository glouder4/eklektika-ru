<?php
namespace OnlineService\Sync\FromCrm;

use OnlineService\B24\User;
use OnlineService\Site\UserGroups;
use OnlineService\Site\Company;
use OnlineService\Site\Manager;
use OnlineService\Sync\InboundRequestParser;
use OnlineService\Sync\SyncInboundLog;
use OnlineService\Sync\SyncPrimitiveBreakpoint;
use OnlineService\Sync\SyncTrace;

// На части стендов bootstrap без автозагрузки — подстраховка перед dispatch().
if (!\class_exists(SyncTrace::class, false)) {
    require_once __DIR__ . '/../SyncTrace.php';
}
if (!\class_exists(SyncInboundLog::class, false)) {
    require_once __DIR__ . '/../SyncInboundLog.php';
}
if (!\class_exists(SyncPrimitiveBreakpoint::class, false)) {
    require_once __DIR__ . '/../SyncPrimitiveBreakpoint.php';
}

/**
 * Тонкий фасад: маршрутизация ACTION → классы канала from-crm.
 */
class InboundGateway
{
    public static function dispatch(array $request): void
    {
        SyncTrace::reset();
        SyncTrace::add('request', SyncTrace::summarizeRequest($request));

        try {
            self::dispatchInternal($request);
        } catch (\Throwable $e) {
            SyncTrace::add('dispatch_exception', [
                'class' => \get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . (string)$e->getLine(),
            ]);
            SyncInboundLog::line(
                '[inbound] dispatch_failed '
                . \get_class($e) . ': '
                . $e->getMessage()
                . ' @' . $e->getFile() . ':' . (string)$e->getLine()
            );
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            $payload = [
                'success' => 0,
                'error' => 'dispatch_failed',
                'message' => 'Internal error',
                'data' => [],
            ];
            $trace = SyncTrace::flushLines();
            if ($trace !== null) {
                $payload['debug_trace'] = $trace;
            }
            echo \json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function withDebugTrace(array $payload): array
    {
        $trace = SyncTrace::flushLines();
        if ($trace !== null) {
            $payload['debug_trace'] = $trace;
        }

        return $payload;
    }

    /**
     * Один элемент-массив из JSON `[{ ... }]` → объект.
     * Конверт `{ "ACTION": "...", "FIELDS": { ... } }` → плоский массив (ключи конверта не перекрывают верхний `ACTION`).
     *
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     */
    private static function isListArrayWithSingleElement(array $request): bool
    {
        return $request !== []
            && \array_keys($request) === \range(0, \count($request) - 1)
            && \count($request) === 1
            && isset($request[0])
            && \is_array($request[0]);
    }

    private static function normalizeInboundEnvelope(array $request): array
    {
        for ($depth = 0; $depth < 4; $depth++) {
            if (self::isListArrayWithSingleElement($request)) {
                $request = $request[0];
                continue;
            }
            break;
        }

        foreach (['body', 'data', 'json', 'payload'] as $wrapKey) {
            if (isset($request[$wrapKey]) && \is_array($request[$wrapKey])) {
                $inner = $request[$wrapKey];
                unset($request[$wrapKey]);
                if (self::isListArrayWithSingleElement($inner)) {
                    $inner = $inner[0];
                }
                $request = \array_merge($request, $inner);
            }
        }

        unset($request['headers'], $request['params'], $request['query'], $request['webhookUrl'], $request['executionMode']);

        foreach (['FIELDS', 'fields'] as $fieldsKey) {
            if (!isset($request[$fieldsKey])) {
                continue;
            }
            $fields = $request[$fieldsKey];
            unset($request[$fieldsKey]);
            if (\is_string($fields)) {
                $decodedFields = \json_decode($fields, true);
                if (\json_last_error() === \JSON_ERROR_NONE && \is_array($decodedFields)) {
                    $fields = $decodedFields;
                } else {
                    continue;
                }
            }
            if (!\is_array($fields)) {
                continue;
            }
            $action = \trim((string) ($request['ACTION'] ?? $fields['ACTION'] ?? ''));
            $request = \array_merge($fields, $request);
            if ($action !== '') {
                $request['ACTION'] = $action;
            }
            break;
        }

        if (!isset($request['ACTION']) || \trim((string) $request['ACTION']) === '') {
            $lower = \trim((string) ($request['action'] ?? ''));
            if ($lower !== '') {
                $request['ACTION'] = $lower;
            }
        }

        // CRM outbound: COMPANY_ID → OS_COMPANY_B24_ID (поиск элемента ИБ 23 по CODE)
        if (
            (!isset($request['OS_COMPANY_B24_ID']) || \trim((string) $request['OS_COMPANY_B24_ID']) === '')
            && isset($request['COMPANY_ID'])
            && \is_scalar($request['COMPANY_ID'])
            && \trim((string) $request['COMPANY_ID']) !== ''
        ) {
            $request['OS_COMPANY_B24_ID'] = \trim((string) $request['COMPANY_ID']);
        }

        // UF CRM: ID элемента каталога компаний на сайте (ИБ 23)
        $siteUf = \OnlineService\Sync\FromCrm\CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF;
        if (
            (!isset($request['SITE_IBLOCK_ELEMENT_ID']) || (int) $request['SITE_IBLOCK_ELEMENT_ID'] <= 0)
            && isset($request[$siteUf])
            && \is_scalar($request[$siteUf])
            && (int) $request[$siteUf] > 0
        ) {
            $request['SITE_IBLOCK_ELEMENT_ID'] = (int) $request[$siteUf];
        }

        // n8n: FIELDS.ID = ID элемента ИБ 23 (173813), OS_COMPANY_B24_ID / COMPANY_ID = CRM (126)
        if (
            (!isset($request['SITE_IBLOCK_ELEMENT_ID']) || (int) $request['SITE_IBLOCK_ELEMENT_ID'] <= 0)
            && isset($request['ID'])
            && \is_scalar($request['ID'])
        ) {
            $elementId = (int) \trim((string) $request['ID']);
            $crmId = (int) \trim((string) ($request['OS_COMPANY_B24_ID'] ?? $request['COMPANY_ID'] ?? '0'));
            if ($elementId > 0 && ($crmId <= 0 || $elementId !== $crmId)) {
                $request['SITE_IBLOCK_ELEMENT_ID'] = $elementId;
            }
        }

        return self::inferInboundActionIfMissing($request);
    }

    /**
     * n8n иногда шлёт только FIELDS без верхнего ACTION (тело = item.json без обёртки).
     *
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     */
    private static function inferInboundActionIfMissing(array $request): array
    {
        if (isset($request['ACTION']) && \trim((string) $request['ACTION']) !== '') {
            return $request;
        }

        if (
            isset($request['LEGAN_ENTITY_INN'])
            || isset($request['LEGAN_MAIN_PHONE'])
            || isset($request['LEGAN_MOBILE_PHONE'])
            || isset($request['OS_COMPANY_B24_ID'])
            || isset($request['COMPANY_ID'])
            || isset($request['LEGAN_ENTITY_NAME'])
            || isset($request['CRM_MULTIFIELDS'])
            || isset($request['TITLE'])
        ) {
            $request['ACTION'] = 'UPDATE_COMPANY';
        } elseif (
            isset($request['CONTACT_ID'])
            || (isset($request['NAME']) && isset($request['LAST_NAME']))
            || isset($request['PERSONAL_PHONE'])
            || isset($request['WORK_PHONE'])
            || isset($request['EMAIL'])
        ) {
            $request['ACTION'] = 'UPDATE_CONTACT';
        }

        return $request;
    }

    private static function dispatchInternal(array $request): void
    {
        $request = self::normalizeInboundEnvelope($request);
        $action = $request['ACTION'] ?? '';

        if ($action === 'UPDATE_GROUP') {
            $group = new UserGroups($request);
            echo $group->getGroupId();
            return;
        }

        if ($action === 'UPDATE_CONTACT' || $action === 'UPDATE_BATCH_USERS') {
            self::requireIfExists('/local/modules/eklektika.b24.usersync/lib/User.php');
            if (class_exists(User::class)) {
                $user = new User();
                header('Content-Type: application/json; charset=UTF-8');
                if ($action === 'UPDATE_BATCH_USERS') {
                    $ok = (bool)$user->updateBatch($request);
                    echo \json_encode(self::withDebugTrace([
                        'success' => $ok ? 1 : 0,
                        'data' => ['batch' => $ok],
                    ]), JSON_UNESCAPED_UNICODE);
                    return;
                }
                $ok = (bool)$user->update($request);
                $data = ['updated' => $ok];
                if (!$ok) {
                    $rc = $user->getLastUpdateFailReason();
                    if ($rc !== null && $rc !== '') {
                        $data['reason_code'] = $rc;
                    }
                }
                echo \json_encode(self::withDebugTrace([
                    'success' => $ok ? 1 : 0,
                    'data' => $data,
                ]), JSON_UNESCAPED_UNICODE);
                return;
            }

            // Fallback: фасад в модуле usersync (единый путь, без дублирования регистро-чувствительных каталогов).
            self::requireIfExists('/local/modules/eklektika.b24.usersync/lib/ContactAjaxFacade.php');
            if (class_exists('\OnlineService\B24\UserSync\ContactAjaxFacade')) {
                $facade = '\OnlineService\B24\UserSync\ContactAjaxFacade';
                header('Content-Type: application/json; charset=UTF-8');
                if ($action === 'UPDATE_BATCH_USERS') {
                    $ok = (bool)$facade::updateBatchUsers($request);
                    echo \json_encode(self::withDebugTrace([
                        'success' => $ok ? 1 : 0,
                        'data' => ['batch' => $ok],
                    ]), JSON_UNESCAPED_UNICODE);
                } else {
                    $ok = (bool)$facade::updateContact($request);
                    echo \json_encode(self::withDebugTrace([
                        'success' => $ok ? 1 : 0,
                        'data' => ['updated' => $ok],
                    ]), JSON_UNESCAPED_UNICODE);
                }
                return;
            }

            throw new \RuntimeException('No contact sync handler class found');
        }

        if ($action === 'DELETE_CONTACT') {
            self::requireIfExists('/local/modules/eklektika.b24.usersync/lib/User.php');
            if (class_exists(User::class)) {
                $user = new User();
                $ok = (bool)$user->delete($request);
                header('Content-Type: application/json; charset=UTF-8');
                echo \json_encode(self::withDebugTrace([
                    'success' => $ok ? 1 : 0,
                    'data' => ['deleted' => $ok],
                ]), JSON_UNESCAPED_UNICODE);
                return;
            }
            self::requireIfExists('/local/modules/eklektika.b24.usersync/lib/ContactAjaxFacade.php');
            if (class_exists('\OnlineService\B24\UserSync\ContactAjaxFacade')) {
                $facade = '\OnlineService\B24\UserSync\ContactAjaxFacade';
                $ok = (bool)$facade::deleteContact($request);
                header('Content-Type: application/json; charset=UTF-8');
                echo \json_encode(self::withDebugTrace([
                    'success' => $ok ? 1 : 0,
                    'data' => ['deleted' => $ok],
                ]), JSON_UNESCAPED_UNICODE);
                return;
            }
            throw new \RuntimeException('No contact delete handler class found');
        }

        if ($action === 'DELETE_COMPANY' || $action === 'UPDATE_COMPANY' || $action === 'SYNC_COMPANY_CONTACTS') {
            self::requireIfExists('/local/modules/eklektika.company/lib/Company.php');
            if (!class_exists(Company::class)) {
                $companyModule = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.company/include.php';
                if (is_file($companyModule)) {
                    require_once $companyModule;
                }
            }
            if (!class_exists(Company::class)) {
                throw new \RuntimeException('No company handler class found');
            }
            $company = new Company();
            if ($action === 'DELETE_COMPANY') {
                SyncTrace::add('DELETE_COMPANY start', []);
                $result = $company->deleteCompanyElement($request);
                SyncTrace::add('DELETE_COMPANY end', ['ok' => (bool)$result]);
                header('Content-Type: application/json; charset=UTF-8');
                echo \json_encode(self::withDebugTrace([
                    'success' => $result ? 1 : 0,
                    'data' => ['deleted' => (bool)$result],
                ]), JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($action === 'UPDATE_COMPANY') {
                SyncTrace::add('UPDATE_COMPANY start', []);
                SyncPrimitiveBreakpoint::hit('sync_bp_inbound_before_update_company', [
                    'ACTION' => $action,
                    'summary' => SyncTrace::summarizeRequest($request),
                ]);
                $result = $company->updateCompanyElement($request);
                SyncPrimitiveBreakpoint::hit('sync_bp_inbound_after_update_company', [
                    'result' => $result === false ? 'false' : (string)(int)$result,
                ]);
                SyncTrace::add('UPDATE_COMPANY end', [
                    'result' => $result === false ? 'false' : (string)(int)$result,
                ]);
                header('Content-Type: application/json; charset=UTF-8');
                echo \json_encode(self::withDebugTrace([
                    'success' => $result ? 1 : 0,
                    'data' => ['company_id' => (int)$result],
                ]), JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $company->syncCompanyContacts($request);
            return;
        }

        if ($action === 'UPDATE_MANAGER') {
            self::requireIfExists('/local/modules/eklektika.company/lib/Manager.php');
            if (!class_exists(Manager::class)) {
                throw new \RuntimeException('No manager handler class found');
            }
            $manager = new Manager();
            $ok = (bool)$manager->update($request);
            header('Content-Type: application/json; charset=UTF-8');
            echo \json_encode(self::withDebugTrace([
                'success' => $ok ? 1 : 0,
                'data' => ['updated' => $ok],
            ]), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($action !== '') {
            SyncTrace::add('unknown_action', ['action' => $action]);
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo \json_encode(self::withDebugTrace([
                'success' => 0,
                'error' => 'unknown_action',
                'action' => $action,
                'data' => [],
            ]), JSON_UNESCAPED_UNICODE);
            return;
        }

        // Пустой ACTION: n8n не передал JSON body или запрос пришёл GET без тела.
        $receiveMeta = InboundRequestParser::getLastMeta();
        SyncTrace::add('missing_action', $receiveMeta);

        if (InboundRequestParser::isEmptyBodyGetRequest()) {
            http_response_code(405);
            header('Content-Type: application/json; charset=UTF-8');
            echo \json_encode(self::withDebugTrace([
                'success' => 0,
                'error' => 'wrong_http_method',
                'message' => 'Empty GET: no JSON body and no query parameters with ACTION/company fields. In n8n either use POST with JSON body, or GET with query parameters (n8n often maps fields to URL on GET).',
                'data' => [],
                'received' => $receiveMeta,
                'payload_keys' => \array_slice(\array_keys($request), 0, 40),
            ]), JSON_UNESCAPED_UNICODE);

            return;
        }

        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo \json_encode(self::withDebugTrace([
            'success' => 0,
            'error' => 'missing_action',
            'message' => 'ACTION is required',
            'data' => [],
            'received' => $receiveMeta,
            'payload_keys' => \array_slice(\array_keys($request), 0, 40),
        ]), JSON_UNESCAPED_UNICODE);
    }

    private static function requireIfExists(string $relativePath): void
    {
        $absPath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;
        if (is_file($absPath)) {
            require_once $absPath;
        }
    }
}
