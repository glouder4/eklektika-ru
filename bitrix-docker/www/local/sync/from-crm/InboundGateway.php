<?php
namespace OnlineService\Sync\FromCrm;

use OnlineService\B24\User;
use OnlineService\Site\UserGroups;
use OnlineService\Site\Company;
use OnlineService\Site\Manager;

/**
 * Тонкий фасад: маршрутизация ACTION → классы канала from-crm.
 */
class InboundGateway
{
    public static function dispatch(array $request): void
    {
        try {
            self::dispatchInternal($request);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => 0,
                'error' => 'dispatch_failed',
                'message' => 'Internal error',
            ], JSON_UNESCAPED_UNICODE);
        }
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
                $user = new User($request);
                echo $action === 'UPDATE_BATCH_USERS'
                    ? $user->updateBatch($request)
                    : $user->update($request);
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
                $user = new User($request);
                echo $user->delete($request);
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
                $result = $company->deleteCompanyElement($request);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => $result ? 1 : 0,
                    'data' => ['deleted' => (bool)$result],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($action === 'UPDATE_COMPANY') {
                $result = $company->updateCompanyElement($request);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => $result ? 1 : 0,
                    'data' => ['company_id' => (int)$result],
                ], JSON_UNESCAPED_UNICODE);
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
            echo $manager->update($request);
            return;
        }

        if ($action !== '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => 0, 'error' => 'unknown_action', 'action' => $action], JSON_UNESCAPED_UNICODE);
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
