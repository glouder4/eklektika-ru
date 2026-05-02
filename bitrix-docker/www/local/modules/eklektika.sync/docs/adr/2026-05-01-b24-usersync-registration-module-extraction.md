# ADR: вынос регистрации и проверки компании из `eklektika.b24.usersync`

- **Статус:** принято к реализации поэтапно  
- **Дата:** 2026-05-01  
- **Контекст Graphify:** обновлён запросом `query_graph` (сервер `graphify-eklektika-ru-site`), фокус: `RegisterUserCompany`, `AjaxRegisterActionService`, связи с `eklektika.company`, события `main`.

## Проблема

Модуль `eklektika.b24.usersync` по смыслу — синхронизация **пользователя сайта ↔ контакт B24** (`User`, `SyncEventHandlers`, профиль, группы). В нём же сосредоточены **регистрация юрлица**, **n8n-пречеки**, **создание/поиск компании в CRM** (`RegisterUserCompany`, весь конвейер `AjaxRegister*`). Это нарушает единую ответственность и усложняет сопровождение.

## Решение

1. Ввести отдельный модуль **`eklektika.b24.registration`** (рабочее имя; финально согласовать с владельцем репозитория).
2. **Перенести** в него поэтапно:
   - публичный вход ajax-регистрации (`AjaxRegisterActionService` и каталог `UserSync/AjaxRegister/` за исключением того, что останется в usersync);
   - сервис компании/ИНН в CRM и n8n для сценария регистрации (ядро нынешнего `RegisterUserCompany`, переименованное по домену, например `CompanyRegistrationService` / `CrmCompanyRegistrationGateway`);
   - конфиг, специфичный только для регистрации (`RegisterUserCompanyConfig` → модуль registration или общий sync-config с префиксом ключей).
3. **Оставить** в `eklektika.b24.usersync`:
   - `User`, `ContactAjaxFacade`, обработчики `OnBeforeUserAdd/AfterUserAdd/...`, не регистрационный CRM-поток;
   - тонкие вызовы в новый модуль при необходимости (фасад или событие).

## Milestones

### Волна 1 (bootstrap, без переноса кода)

- Добавить каркас модуля `eklektika.b24.registration` и подключение в `php_interface` после `usersync`.
- Никаких перемещений классов; только подготовка структуры и задач.

### Волна 2 (маленькая и безопасная)

**Цель:** вынести “transport + DTO + helper” для ранних n8n-пречеков регистрации из `usersync` в `registration`, а затем перевести все вызовы на новые FQCN и удалить переходные слои.

**Переносим (точный список классов):**

- `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport`
- `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse`
- `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck`

**Новое пространство имён (план):**

- Новые FQCN в модуле `eklektika.b24.registration`:
  - `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport`
  - `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse`
  - `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck`

**Обратная совместимость (BC):** не используем. После перевода всех вызовов на новые FQCN удаляем старые классы и их маппинги автозагрузки.

### Волна 4 (вынос company-registration из монолита)

**Цель:** убрать прямую зависимость `AjaxRegisterActionService` от монолита `\OnlineService\B24\RegisterUserCompany` и начать выделение домена регистрации компании в модуль `eklektika.b24.registration`.

**Сделано в рамках волны:** добавлен `\OnlineService\B24\Registration\CompanyRegistrationService` как точка входа для:

- precheck ИНН (ajax),
- `OnBeforeUserRegisterHandler` (CRM precheck),
- `syncFromSiteRegistration` (CRM + компания + контакт после создания пользователя).

Дальнейший шаг — перенос/распил внутренностей `RegisterUserCompany` в отдельные сервисы registration-модуля и устранение зависимости usersync → registration.

**Сделано (2026-05-01):** автозагрузка `\OnlineService\B24\RegisterUserCompany` перенесена под `eklektika.b24.registration`, чтобы usersync не владел регистрационным сервисом на уровне bootstrap.

### Волна 3 (миграция оставшегося `AjaxRegister`-пайплайна)

**Цель:** перенести оставшиеся классы конвейера `AjaxRegister` из `eklektika.b24.usersync` в `eklektika.b24.registration`, сменив FQCN с `\OnlineService\B24\UserSync\AjaxRegister\...` на `\OnlineService\B24\Registration\AjaxRegister\...`, и обновить `AjaxRegisterActionService` на новые namespace.

**Переносим (точный список классов):**

- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterExecutionContext` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterExecutionContext`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterDuplicateGuard` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterDuplicateGuard`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterBitrixApplication` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterBitrixApplication`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterSiteCompanyResolver` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterSiteCompanyResolver`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterUserPayloadBuilder` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterUserPayloadBuilder`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterPostParser` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser`
- `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterResponse` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterResponse`

**BC:** не используем (как в волне 2). После обновления всех use/import и точек входа — удаляем старые классы из `usersync`.

## Совместимость

- Фаза 1: новый модуль подключается из `php_interface`, **классы остаются на старых путях**, добавляются только типовые `class_alias` или обёртки с `@deprecated` (на усмотрение волны 2).
- Публичные точки (`ajax-register-action.php`, имена классов в интеграциях) не ломать без мажорной версии; предпочтительно сохранить `OnlineService\B24\RegisterUserCompany` как deprecated alias к новому FQCN.

## Риски

- Циклические зависимости registration ↔ usersync ↔ rest — граница через интерфейсы/DTO в `eklektika.b24.registration` и минимальный контракт.
- Двойная загрузка конфига n8n — ключи остаются в `eklektika.sync` до выделения `registration`-специфичного конфига.
- Регрессия событий `main` — чеклист регресса на staging обязателен.

## Прогресс (tech-lead)

- **2026-05-01 — волна 1:** добавлен модуль `eklektika.b24.registration` (каркас `include.php`, README), подключение в `php_interface/eklektika_requires.php`; дерево задач и subtasks в `eklektika.sync/docs/tasks/...`.
- **2026-05-01 — волна 2 (внедрено):** канонические классы перенесены в `eklektika.b24.registration` (новые FQCN), все вызовы переведены на новые FQCN, переходные shim/alias удалены из `usersync`.
- **2026-05-01 — волна 3 (внедрено):** все `AjaxRegister*` классы перенесены в `eklektika.b24.registration` (namespace `OnlineService\B24\Registration\AjaxRegister`), `usersync` больше не содержит `UserSync\AjaxRegister` домена.

## Ссылки

- Задачи: `modules/eklektika.sync/docs/tasks/2026-05-01-b24-usersync-registration-module-split/`
- Связанный ADR: `2026-04-30-site-crm-via-n8n-registration.md`
