# ADR: E2E верификация регистрации и именованных webhooks n8n (подтверждено)

## Статус

Принято и **зафиксировано по факту стенда** (2026-05-02).

## Контекст

Цепочка публичной регистрации юрлица (`AjaxRegisterActionService` → `CompanyRegistrationService` → `CrmRegistrationOrchestrator::syncFromSiteRegistration` / `callB24Method`) опирается на **именованные вебхуки n8n** (`registration_webhook_*`, тело `{ METHOD, PARAMS, … }`). Ранее выполнялись исправления привязки контакт↔компания, контрактов ответов и запрета delete с сайта.

## Решение / факт

На отладочном стенде подтверждено:

1. **Регистрация завершается успешно** (пользователь создан, CRM синхронизирован по задуманному контуру).
2. **Вебхуки для этой части отлажены**: сценарии n8n для пречеков и для `callB24Method` дают ожидаемый envelope (`success` / `result`), корректно обрабатываются `N8nCrmGateway` и оркестратором.

## Последствия

- Дальнейшие изменения в этой зоне — только осознанные продуктовые или безопасность; регресс проверять по `docs/business-processes/registration/verification-checklist.md`.
- Известный операционный риск (сиротский контакт при полном отказе обеих попыток привязки) остаётся; см. аудит в `modules/eklektika.sync/docs/tasks/2026-05-02-registration-post-add-bind-and-uf/task.md`.

## Ссылки

- `docs/business-processes/registration/verification-checklist.md`
- `docs/reference/registration-n8n-webhooks.md`
- Graphify (контекст модулей регистрации / transport): community вокруг `CrmRegistrationN8nTransport`.

## Next steps for Team Lead

- Отдельная разработка по «довести регистрацию» **не требуется**.
- Имеет смысл поддерживать мониторинг редких сбоев привязки и при необходимости завести отдельную мини-инициативу по метрикам/alerts.
