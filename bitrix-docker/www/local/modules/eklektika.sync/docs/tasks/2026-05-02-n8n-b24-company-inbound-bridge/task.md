# Task: n8n — мост событий Bitrix24 → входящий UPDATE_COMPANY

## Цель

Подготовить workflow n8n, который принимает событие/данные из Bitrix24 и вызывает сайт с корректным телом для `ACTION=UPDATE_COMPANY`.

## Критерии готовности

- [x] Конверт `ACTION` + `FIELDS` и JSON body на стороне сайта (`InboundGateway`, `inbound_crm.php`).
- [x] Алиас `TITLE` → `OS_COMPANY_NAME` в `Company::updateCompanyElement`.
- [ ] Узел n8n: при необходимости дублирование `LEGAN_ENTITY_USERS` → `OS_COMPANY_USERS`, флаги холдинга в `OS_*`.
- [ ] HTTP POST на `{origin}/local/modules/eklektika.sync/public/inbound_crm.php` с секретом входа.
- [ ] Проверено на стенде: ответ `success: 1`, элемент ИБ 23 обновляется.

## Subtasks

- [ ] **S1.** Черновик workflow (trigger из B24 / ручной webhook) + таблица соответствий полей по `docs/reference/n8n-inbound-update-company-from-b24.md`.
- [x] **S2.** JSON POST: парсинг в `inbound_crm.php`; конверт `[item]` и `{ FIELDS }` в gateway.
- [ ] **S3.** При отклонении смоука — расширение merge LEGAN→OS в `Company` (отдельная задача).

## Audit

_(после смоука)_

## Next steps for Tech Lead

- Закрыть ADR после успешного смоука или зафиксировать необходимость правки PHP.
