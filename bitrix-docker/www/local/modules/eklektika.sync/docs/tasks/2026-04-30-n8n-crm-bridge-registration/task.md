# Task: n8n CRM bridge + регистрация

## Инициатива

Сайт → n8n → Bitrix24; первая волна — транспорт для сценариев, использующих `RestClient::callRestMethod` (в т.ч. `RegisterUserCompany`).

## Subtasks

- [x] **S1.** ADR `docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- [x] **S2.** PHP: `N8nCrmGateway`, переключение в `RestClient::callRestMethod` по `EKLEKTIKA_N8N_CRM_WEBHOOK_URL`
- [x] **S3.** n8n workflow `gGtsrfCPP9t3OyLj`: множественные Webhook-триггеры в том же процессе (см. `docs/n8n/workflow-site-to-crm-multihook.sdk.ts` и однострочный `docs/n8n/pack_update.json`). Пути production (базово `POST https://<n8n-host>/webhook/...`; на стенде иногда без `/webhook` — смотреть вкладку вебхука в n8n):
  - `9cdcd623-305c-4da3-877d-3c9b7a05bd0a` — legacy proxy (`METHOD` + `PARAMS`, как у `EKLEKTIKA_N8N_CRM_WEBHOOK_URL`), Header Auth
  - `registration/crm-check-unique-contact-v1` → `crm.contact.list` по email/телефону, Header Auth (ранний ajax-precheck и `runSyncPreCheck`)
  - `registration/crm-check-inn-v1` → `crm.requisite.list`, Header Auth (ранний ajax-precheck при непустом ИНН и `runSyncPreCheck` для типов 5/6)
  - `registration/crm-company-add-v1` → `crm.company.add`, Header Auth (`PARAMS.fields` совместимо с текущим PHP)
  - `registration/crm-contact-add-v1` → `crm.contact.add`, Header Auth (`PARAMS.fields` совместимо с текущим PHP)
  - `registration/crm-register-post-sync-v1` — ACK для пост-регистрации (**без** Header Auth, как отправляет сайт через `async_post_register_webhook_url`)
- [ ] **S4.** DevOps: задать env на PHP и на n8n (`EKLEKTIKA_B24_REST_PREFIX`, URL вебхука prod); в n8n на Webhook включить **Header Auth** (`X-Sync-Token` = тот же секрет, что `inbound_secret` / credential из SDK)
- [ ] **S5.** QA: смоук регистрации на стенде

## Критерии приёмки

- При заданном `EKLEKTIKA_N8N_CRM_WEBHOOK_URL` сайт не вызывает `postSiteRequestsHandler` для `crm.*`.
- Ответ вебхука позволяет `RegisterUserCompany` получать те же типы результатов (`result` или структура ошибки с `success: 0`).

## Audit (team lead)

- Код PHP согласован с контрактом JSON n8n; риски: время ответа, лимиты тела запроса (файл реквизитов base64).
- **2026-05-01:** В ADR зафиксирована полная цепочка `ajax-register-action.php` → `AjaxRegisterActionService` → `RegisterUserCompany` (webhooks уникальности / ИНН + REST proxy регистрации). Расхождение ожиданий QA («не должно проходить») при `success: true` — проверять ответы n8n и конфиг URL, а не предполагать отсутствие проверок на сервере.

## Follow-up wave

- Новая инициатива рефакторинга регистрации (отдельные webhook-операции + async дозапись CRM ID) декомпозирована в:
  - `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
