# Бизнес‑процесс: регистрация (сайт → CRM через n8n)

## Назначение

Публичная регистрация юрлица/рекламного агента на сайте создаёт Bitrix‑пользователя и **синхронизирует** сущности в CRM (B24) **через n8n webhooks** (не прямыми REST вызовами из сайта).

## Точки входа (канонические)

- **AJAX‑регистрация (основной конвейер)**: `/personal/ajax/ajax-register-action.php` → `\OnlineService\B24\Registration\AjaxRegisterActionService::run()`.
- **Обработчики main** (`OnBeforeUserRegister`, `OnAfterUserRegister`, `OnBeforeUserAdd`, `OnAfterUserAdd`): делегируют в `\OnlineService\B24\Registration\CrmRegistrationOrchestrator` через `\OnlineService\B24\UserSync\UserSyncBootstrap` и `\OnlineService\Events\SyncEventHandlers` (модуль `eklektika.b24.registration` должен подключаться раньше `eklektika.b24.usersync`, см. `php_interface/eklektika_requires.php`).

## Ключевые файлы / классы / методы (source of truth)

- `local/modules/eklektika.b24.registration/lib/Registration/AjaxRegisterActionService.php`
  - `run()` — запускает конвейер.
  - `registerSegmentCompanyPrechecks()` / `registerSegmentUserPrechecksAndCreate()` / `registerStepSyncCrmAndFinalize()` — сегменты процесса.
- `local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/*`
  - `AjaxRegisterPostParser::parse()` — нормализация/парсинг POST формы.
  - `AjaxRegisterSiteCompanyResolver::resolveByInn()` — поиск компании в ИБ по ИНН (и “ambiguous” логика).
  - `AjaxRegisterDuplicateGuard::checkEmailPhoneDuplicates()` — локальные дубли в `b_user`.
  - `AjaxRegisterCrmContactPrecheck::*` — CRM precheck уникальности контакта (n8n).
- `local/modules/eklektika.b24.registration/lib/CrmRegistrationOrchestrator.php`
  - `runSyncPreCheck()` — precheck’и перед регистрацией в обработчиках `main`.
  - `syncFromSiteRegistration()` — безопасный sync для ajax‑цепочки после `CUser::Add`.
  - `createB24Company()` / `crmAddCompany()` / `crmAddContact()` — создание/поиск сущностей.
  - `maybeMergeCompanyUpdatesFromN8n()` — опционально: webhook `check-crm-company-updates` (ключ `registration_webhook_company_updates_url`).
  - `callB24Method()` — вызовы `crm.*` через **именованные** вебхуки n8n (`registration_webhook_crm_*_url`, по одному на метод; тело `{ METHOD, PARAMS }`), без прямого REST с сайта на портал B24.
- `local/modules/eklektika.b24.registration/lib/Registration/CompanyRegistrationService.php` — фасад для `AjaxRegisterActionService` и точек интеграции.

## Конвейер (порядок шагов)

Ниже порядок шагов **как должно выполняться** в текущей реализации.

### Сегмент 1. Компания (prechecks)

Источник: `\OnlineService\B24\Registration\AjaxRegisterActionService`.

1) **Проверка обязательных полей формы**  
   `registerStepValidateRequiredFields()`

2) **Валидация длины ИНН (локально)**: 10 или 12 цифр  
   `registerStepValidateInnLength()`

3) **CRM precheck ИНН через n8n** (`crm-check-inn-v1`)  
   `registerStepCrmPrecheckCompanyInnViaN8n()` → `CompanyRegistrationService::runInnPrecheck()` → `CrmRegistrationOrchestrator::runAjaxCompanyInnPrecheck()` → `crmCheckInnUniqueness()`.  
   Результат сохраняется в `ctx->crmInnPrecheckPayload` и влияет на режим `COMPANY_MODE` (см. «Режимы»).

4) **Поиск компании на сайте по ИНН (ИБ)**  
   `registerStepResolveSiteCompany()` → `AjaxRegisterSiteCompanyResolver::resolveByInn()`
   - если найдено **несколько** компаний: статус `ambiguous` → **отказ** с пользовательским сообщением.
   - если найдена одна: подтягиваются поля (название/адрес/деятельность/сайт).

### Сегмент 2. Пользователь (prechecks + создание)

1) **CRM precheck уникальности контакта** (email/телефон) через n8n (`crm-check-unique-contact-v1`)  
   `registerStepCrmPrecheckContactUniqueViaN8n()` → `AjaxRegisterCrmContactPrecheck::runFromRegistrationPost()`

2) **Локальные проверки пароля и e-mail**  
   `registerStepValidatePasswordAndEmail()`

3) **Локальная проверка дублей в b_user** (email/телефон)  
   `registerStepRejectLocalDuplicates()` → `AjaxRegisterDuplicateGuard::checkEmailPhoneDuplicates()`

4) **Сбор полей пользователя**  
   `registerStepBuildUserFields()` → `AjaxRegisterUserPayloadBuilder::*`

5) **Пометить, что CRM precheck уже выполнен** (чтобы не дублировать в обработчике)  
   `registerStepMarkCrmPrecheckDone()` выставляет:
   - `$GLOBALS['OS_REGISTER_USER_PRECHECK_DONE'] = true`
   - `OS_SKIP_USERSYNC_AFTER_USER_ADD = true` (define + global)

6) **Создание Bitrix‑пользователя**  
   `registerStepCreateBitrixUser()` → `CUser::Add()`

### Сегмент 3. CRM sync + финализация (safe path)

1) **Синхронизация с CRM** (создание/поиск company/contact, привязки, requisite и т.п.)  
   `registerStepSyncCrmAndFinalize()`:
   - собирает `syncFields` через `AjaxRegisterUserPayloadBuilder::buildSyncFields()`
   - вызывает `CompanyRegistrationService::syncFromSiteRegistration($syncFields)` → `CrmRegistrationOrchestrator::syncFromSiteRegistration()`
   - при существующей компании в CRM по ИНН: перед `crm.contact.add` может вызываться опциональный webhook **check-crm-company-updates** (`registration_webhook_company_updates_url`), см. `docs/reference/registration-n8n-webhooks.md`.
   - **UF `UF_CRM_1774915439581` (связь с элементом каталога компаний на сайте):** в `crm.company.add` **не передаётся** — ID элемента ИБ на сайте появляется только после шагов CRM. Сайт создаёт элемент инфоблока и затем вызывает **`crm.company.update`** через вебхук n8n `registration_webhook_crm_company_update_url` (см. `CrmRegistrationOrchestrator::upsertSiteCompanyLinkByB24Id`, ADR `modules/eklektika.sync/docs/adr/2026-05-03-registration-uf-site-iblock-order.md`).

2) **Если CRM sync не удался** → откат  
   - удаление созданного пользователя: `(new CUser())->Delete($ctx->newUserId)`
   - возврат fail‑ответа

3) **Если CRM sync успешен** → деактивация пользователя  
   - после sync принудительно ставится `ACTIVE = 'N'` (если ещё не N)

## Режимы и ветвления

- **Ambiguous company by INN**: найдено несколько компаний в ИБ → регистрация отклоняется (чтобы не привязать пользователя «не к той» компании).
- **Duplicate contact (CRM)**: если `crm-check-unique-contact-v1` считает контакт дублем → регистрация отклоняется на этапе precheck.
- **Duplicate contact (site)**: локальные дубли (email/телефон) → регистрация отклоняется до `CUser::Add`.
- **Компания уже есть в CRM по ИНН**: используется ENTITY_ID из пречека / локальной привязки; опционально подтягиваются поля через `registration_webhook_company_updates_url`; локальный ИБ дополняется в `upsertSiteCompanyLinkByB24Id`.
- **Пустой ИНН после нормализации**:
  - в **ajax‑конвейере** ИНН ожидается заполненным и валидным (10/12 цифр) — иначе отказ на сегменте 1;
  - в обработчиках **без ajax** precheck ИНН в CRM может быть пропущен, если ИНН пуст после нормализации (`runAjaxCompanyInnPrecheck()` возвращает ok без HTTP вызова).

## Интеграция с n8n / CRM: конфиги и вебхуки

Конфиги читаются из `$GLOBALS['EKLEKTIKA_SYNC_CONFIG']` или из файла `local/modules/eklektika.sync/config.local.php`.

Ключи (основные):

- `n8n_registration_http_base`: базовый URL до `/registration/...` вебхуков (если не заданы полные URL).
- `registration_webhook_unique_url`: `crm-check-unique-contact-v1` (precheck уникальности контакта).
- `registration_webhook_inn_url`: `crm-check-inn-v1` (precheck ИНН).
- `registration_webhook_company_updates_url`: опционально `check-crm-company-updates` (сверка полей компании из CRM).
- `registration_webhook_crm_*_url`: **по одному URL** на каждый `crm.*`, вызываемый из `callB24Method` (см. `docs/reference/registration-n8n-webhooks.md`, ADR `2026-05-05-one-crm-method-one-n8n-webhook.md`).
- `registration_webhook_company_add_url`: `crm-company-add-v1` (создание компании, если используется отдельный webhook).
- `registration_webhook_contact_add_url`: `crm-contact-add-v1` (создание контакта, если используется отдельный webhook).
- `inbound_secret`: общий секрет для inbound запросов (используется в заголовке `X-Sync-Token` при запросах к n8n/CRM‑мосту).
- `sync_legacy`: синхронизация в `CrmRegistrationOrchestrator::OnAfterUserRegisterHandler`.
- `async_post_register`, `async_post_register_webhook_url`: дополнительный «тихий» webhook после регистрации.

Полный справочник ключей и назначений:

- `docs/reference/registration-n8n-webhooks.md`

## Канонические файлы (source of truth)

- `local/modules/eklektika.b24.registration/lib/Registration/AjaxRegisterActionService.php`
- `local/modules/eklektika.b24.registration/lib/Registration/CompanyRegistrationService.php`
- `local/modules/eklektika.b24.registration/lib/CrmRegistrationOrchestrator.php`
- `local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/*` (парсер, payload builder, duplicate guard, resolver и т.п.)
- `local/modules/eklektika.sync/config.local.php` (пример конфигурации)

## Верификация (E2E чеклист)

- `docs/business-processes/registration/verification-checklist.md`

## Правило изменения процесса

Если меняется:
- порядок шагов,
- условия отказа/ветвления,
- используемые вебхуки/URL/ключи конфига,
- формат payload/ответа n8n,

то изменения считаются **незавершёнными**, пока не обновлён этот документ (и при необходимости `docs/README.md` / оглавление).
