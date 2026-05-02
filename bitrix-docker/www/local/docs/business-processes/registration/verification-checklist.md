# Регистрация: чеклист верификации (E2E)

Используйте на staging перед выкладкой изменений в CRM‑ветке регистрации.

## Предусловия

- Заполнены `registration_crm_rest_proxy_webhook_url`, `registration_webhook_unique_url`, `registration_webhook_inn_url` (и при необходимости `registration_webhook_company_updates_url`).
- В n8n включены соответствующие workflow; есть доступ к executions.

## Сценарии

### 1. ИНН есть в CRM, локальной карточки на сайте нет

- Ожидание: цепочка находит компанию по requisite / `crm.company.get`, не создаёт дубликат компании в CRM; создаётся контакт и связь; при необходимости создаётся элемент ИБ (`upsertSiteCompanyLinkByB24Id`).
- Проверка: executions `crm-check-inn-v1` → REST `crm.company.get` / при необходимости опционально `check-crm-company-updates` → `crm.contact.add` → привязка `crm.contact.company.add` (при ошибке ответа — повтор `crm.company.contact.add`) → `crm.company.update` (UF `UF_CRM_1774915439581` — ID элемента каталога на сайте).

### 2. ИНН есть в CRM и есть локальная привязка (ИБ + OS_COMPANY_B24_ID)

- Ожидание: используется локальный B24 id при валидном `crm.company.get`; при расхождении ИНН между сайтом и CRM — отказ с сообщением о менеджере.
- Проверка: n8n executions без лишнего `crm.company.add`.

### 3. ИНН нет в CRM

- Ожидание: создание компании (webhook `crm-company-add-v1` или REST‑прокси), requisite, контакт, привязка.

### 4. Duplicate contact (`crm-check-unique-contact-v1`)

- Ожидание: отказ до `CUser::Add`, пользователь в `b_user` не создаётся.

### 5. Ошибка CRM sync после создания пользователя (ajax)

- Ожидание: откат — `(new CUser())->Delete($newUserId)` в `AjaxRegisterActionService::registerStepSyncCrmAndFinalize()`.

## Артефакты

- Ответ AJAX `/personal/ajax/ajax-register-action.php` (`success` / текст ошибки).
- n8n executions по correlation (время регистрации, email в payload пречека).
- При `async_post_register`: `local/logs/async-register-metrics.log`, при ошибках — `async-register-dead-letter.log`.
