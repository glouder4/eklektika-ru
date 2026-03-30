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
        $action = $request['ACTION'] ?? '';

        if ($action === 'UPDATE_GROUP') {
            $group = new UserGroups($request);
            echo $group->getGroupId();
            return;
        }

        if ($action === 'UPDATE_CONTACT' || $action === 'UPDATE_BATCH_USERS') {
            $user = new User($request);
            echo $action === 'UPDATE_BATCH_USERS'
                ? $user->updateBatch($request)
                : $user->update($request);
            return;
        }

        if ($action === 'DELETE_CONTACT') {
            $user = new User($request);
            echo $user->delete($request);
            return;
        }

        if ($action === 'DELETE_COMPANY') {
            $company = new Company();
            echo $company->deleteCompanyElement($request);
            return;
        }

        if ($action === 'UPDATE_COMPANY') {
            $company = new Company();
            echo $company->updateCompanyElement($request);
            return;
        }

        if ($action === 'UPDATE_MANAGER') {
            $manager = new Manager();
            echo $manager->update($request);
            return;
        }

        if ($action === 'SYNC_COMPANY_CONTACTS') {
            $company = new Company();
            echo $company->syncCompanyContacts($request);
            return;
        }

        if ($action !== '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => 0, 'error' => 'unknown_action', 'action' => $action], JSON_UNESCAPED_UNICODE);
            return;
        }
    }
}
