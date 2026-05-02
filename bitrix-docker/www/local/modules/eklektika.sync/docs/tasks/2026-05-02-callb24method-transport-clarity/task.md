# Task: ясность транспорта `callB24Method` (n8n, не прямой B24)

## Инициатива

Устранить недопонимание: `CrmRegistrationOrchestrator::callB24Method` — обёртка над **именованными** вебхуками n8n (`registration_webhook_crm_*_url`, JSON METHOD+PARAMS), а не прямой вызов CRM с сайта.

## Subtasks

- [x] **S1.** ADR `modules/eklektika.sync/docs/adr/2026-05-02-registration-callb24method-transport.md`
- [x] **S2.** PHPDoc на `callB24Method` в `CrmRegistrationOrchestrator.php` (ссылки на `N8nCrmGateway`, именованные вебхуки)

## Критерии приёмки

- По коду и ADR однозначно видно: транспорт = вебхуки n8n (один URL на метод).

## Audit (team lead), 2026-05-02

- Согласовано с `docs/reference/registration-n8n-webhooks.md` (раздел `callB24Method` / `registration_webhook_crm_*_url`).
- Доработок по коду после PHPDoc не требуется.

## Next steps for Tech Lead

- При желании — опциональный рефакторинг переименования метода в отдельной задаче (широкий поиск по репозиторию).
