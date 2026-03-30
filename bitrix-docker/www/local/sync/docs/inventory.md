# Инвентаризация обмена (снимок для рефакторинга)

Автоматически не исчерпывает компоненты и прямые вызовы API; дополнять по мере обхода кода.

## CRM → сайт (`POST /local/classes/ajax.php`)

| ACTION | Обработчик (класс) | Файл |
| ------ | ------------------ | ---- |
| UPDATE_GROUP | `OnlineService\Site\UserGroups` | `local/classes/site/UserGroups.php` |
| UPDATE_CONTACT | `OnlineService\B24\User` | `local/classes/b24/User.php` |
| UPDATE_BATCH_USERS | `OnlineService\B24\User` | `local/classes/b24/User.php` |
| DELETE_CONTACT | `OnlineService\B24\User` | `local/classes/b24/User.php` |
| DELETE_COMPANY | `OnlineService\Site\Company` | `local/classes/site/Company.php` |
| UPDATE_COMPANY | `OnlineService\Site\Company` | `local/classes/site/Company.php` |
| UPDATE_MANAGER | `OnlineService\Site\Manager` | `local/classes/site/Manager.php` |
| SYNC_COMPANY_CONTACTS | `OnlineService\Site\Company` | `local/classes/site/Company.php` |

Инициатор на B24: `Updater::sendRequest` → URL сайта из `EKLEKTIKA_SITE_URL` + `/local/classes/ajax.php` (см. `eklektika-ru-b24/local/classes/Updater.php`). Пример сценария: `ManagerUpdater` → `ACTION=UPDATE_MANAGER`.

## Сайт → CRM (исходящие с сайта)

| Транспорт | Endpoint на стороне B24 | Класс |
| --------- | ------------------------- | ----- |
| `Request::sendRequest` | `URL_B24` + `local/classes/site_requests_handler.php` | `local/classes/b24/Request.php` |

Другие вызовы: поиск по `sendRequest` и `URL_B24` в `local/classes/`.

## События Bitrix (сайт)

| Событие | Обработчик | Где регистрируется |
| ------- | ---------- | ------------------ |
| main:OnBeforeUserDelete | `SyncEventHandlers::onBeforeUserDelete` → `OnlineService\B24\User` | `local/events/events.php`, `local/events/SyncEventHandlers.php` |
| main:OnBeforeUserAdd | `SyncEventHandlers::onBeforeUserAdd` → `RegisterUserCompany::OnBeforeUserRegisterHandler` | `local/events/events.php`, `local/events/SyncEventHandlers.php` |
| main:OnAfterUserAdd | `SyncEventHandlers::onAfterUserAdd` → `RegisterUserCompany::OnAfterUserRegisterHandler` | `local/events/events.php`, `local/events/SyncEventHandlers.php` |
| main:OnAfterUserUpdate | `SyncEventHandlers::onAfterUserUpdate` → `OnlineService\B24\User` | `local/events/events.php`, `local/events/SyncEventHandlers.php` |
| search:BeforeIndex | `OnlineService\Classes\Handlers\Search\Stemming` | `local/events/events.php` |

`RegisterUserCompany::OnBeforeUserRegisterHandler`: **1)** дубликат по `EMAIL` и телефону в `b_user`; **2)** дубликат контакта в B24 (`isUserRegistered`). `CUser::Add()` не шлёт `OnBeforeUserRegister` — используются события **Add**.

## События Bitrix (B24)

| Событие | Обработчик |
| ------- | ---------- |
| main:OnAfterUserUpdate | `OnlineService\Manager\ManagerUpdater::OnAfterUserManagerUpdate` |

Регистрация: `eklektika-ru-b24/local/events/events.php`.
