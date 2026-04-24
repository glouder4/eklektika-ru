<?php
namespace OnlineService\Sync\FromCrm;

use OnlineService\B24\User;
use OnlineService\Site\UserGroups;
use OnlineService\Site\Company;
use OnlineService\Site\Manager;
use OnlineService\Sync\SyncInboundLog;
use OnlineService\Sync\SyncPrimitiveBreakpoint;
use OnlineService\Sync\SyncTrace;

// На части стендов bootstrap.php старый и не подключает SyncTrace — иначе fatal на первой строке dispatch().
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

    private static function dispatchInternal(array $request): void
    {
        $action = $request['ACTION'] ?? '';

        if ($action === 'UPDATE_GROUP') {
            $group = new UserGroups($request);
            echo $group->getGroupId();
            return;
        }

        if ($action === 'UPDATE_CONTACT' || $action === 'UPDATE_BATCH_USERS') {
            self::requireIfExists('/local/classes/b24/User.php');
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

            // Legacy fallback, if usersync facade exists in this environment.
            self::requireIfExists('/local/classes/b24/UserSync/ContactAjaxFacade.php');
            self::requireIfExists('/local/classes/b24/usersync/ContactAjaxFacade.php');
            if (class_exists('\OnlineService\B24\UserSync\ContactAjaxFacade')) { 
                $facade = '\OnlineService\B24\UserSync\ContactAjaxFacade';
                if ($action === 'UPDATE_BATCH_USERS') {
                    echo $facade::updateBatchUsers($request);
                } else {
                    echo $facade::updateContact($request);
                }
                return;
            }

            throw new \RuntimeException('No contact sync handler class found');
        }

        if ($action === 'DELETE_CONTACT') {
            self::requireIfExists('/local/classes/b24/User.php');
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
            self::requireIfExists('/local/classes/b24/UserSync/ContactAjaxFacade.php');
            self::requireIfExists('/local/classes/b24/usersync/ContactAjaxFacade.php');
            if (class_exists('\OnlineService\B24\UserSync\ContactAjaxFacade')) {
                $facade = '\OnlineService\B24\UserSync\ContactAjaxFacade';
                echo $facade::deleteContact($request);
                return;
            }
            throw new \RuntimeException('No contact delete handler class found');
        }

        if ($action === 'DELETE_COMPANY' || $action === 'UPDATE_COMPANY' || $action === 'SYNC_COMPANY_CONTACTS') {
            self::requireIfExists('/local/classes/site/Company.php');
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
            self::requireIfExists('/local/classes/site/Manager.php');
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
            ]), JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    private static function requireIfExists(string $relativePath): void
    {
        $absPath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;
        if (is_file($absPath)) {
            require_once $absPath;
        }
    }
}
