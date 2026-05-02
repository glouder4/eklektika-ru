# S1: Контракты и n8n routes

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done` *(scoped: потребление async webhook со стороны сайта и операционное описание; пять именованных n8n HTTP entrypoints из ADR — на стороне workflow / отдельное согласование)*

## Цель

Зафиксировать единый `v2` контракт и маршрутизацию webhook-операций внутри того же n8n workflow для pre-check и CRM create шагов.

## Входы

- ADR: `docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- Текущий workflow `Site to CRM` и существующий webhook-конфиг.

## Выходы

- Документированный контракт для 5 операций.
- Схема маршрутизации ошибок и response envelope.
- Согласованные обязательные поля correlation/idempotency.

## Зависимости

- Доступ в n8n.
- Подтверждение от Team Lead по naming и версиям endpoint.

## Риски

- Разнобой форматов payload между PHP и n8n.

## Индивидуальные критерии готовности

1. На сайте описан потребляемый async payload и заголовок `X-Idempotency-Key` (runbook §2).
2. Зафиксирован зазор: синхронные вызовы pre-check/create в PHP пока через REST, не через пять отдельных n8n URLs — требует ADR-appendix или доработки n8n.
3. Повторный Team Lead review контрактов перед production.

## Чеклист проверки результата

- [x] Описана async-операция post-register (`event`, `site_user_id`, UF/идентификаторы, `idempotency_key`) — реализация + runbook.
- [ ] Явно развернуты в n8n и подписаны `crm-check-unique-email-phone`, `crm-check-company-inn`, `crm-company-add`, `crm-contact-add`, отдельно от монолитного REST — **вне текущего PHP-пакета**.
- [x] Добавлены пороги таймаутов HTTP клиента async вызова (код `HttpClient`) и политика idempotency (runbook §2).
- [x] ADR содержит таблицу «Сверка с реализацией».

## Артефакты

- `modules/eklektika.sync/docs/runbook-registration-crm-webhooks.md`
- `modules/eklektika.sync/docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- `modules/eklektika.b24.usersync/lib/RegisterUserCompany.php` (`buildAsyncPostRegisterWebhook`, заголовки)

## Tech Lead

- Цепочка документирования подзадачи закрыта: **2026-04-30** (остаток — n8n маршрутизация и подпись контракта).
