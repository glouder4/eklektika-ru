# Task: строгие контракты ответов registration webhooks

## Инициатива

Регистрация не завершается успешно при нарушении контракта любого задействованного вебхука или при ошибке REST‑прокси на обязательных шагах.

## Subtasks

- [x] **S1.** `CrmRegistrationOrchestrator`: `assertProbeWebhookContract`, `assertMutationAddWebhookContract`, `assertProbeListWebhookContract`, `assertRegistrationRestProxyOk`, интеграция в precheck / add / requisite list / company_updates / реквизиты / привязка / UF.
- [x] **S2.** `docs/reference/registration-n8n-webhooks.md` — раздел «Обязательный контракт ответов» + примеры JSON.
- [x] **S3.** ADR `modules/eklektika.sync/docs/adr/2026-05-02-registration-webhook-response-contracts.md`.

## Audit (team lead), 2026-05-02

- Согласовано с политикой «CRM через n8n»; ломающее изменение: n8n должен отдавать envelope с `success` и `result`.

## Next steps for Tech Lead

- Прогнать регрессию на staging: все webhooks возвращают канонический JSON.
- При необходимости выровнять workflow n8n под примеры из справочника.
