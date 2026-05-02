# Задача: ответ `crm.requisite.list` и подтверждение ИНН

## Цель

Устранить ложное срабатывание «Компания по ИНН найдена в CRM, но подтверждение через n8n не удалось» при успешных ответах precheck и пустом/корректном unique-contact.

## Результат

- [x] ADR: `modules/eklektika.sync/docs/adr/2026-05-02-registration-requisite-list-response-shape.md`
- [x] PHP: `CrmRegistrationOrchestrator::normalizeCrmRequisiteListRows` — используется в `enforceCompanyInnInRequisites`
- [x] `N8nCrmGateway::peelN8nSingleItemJsonEnvelope` — ответ n8n `[{"success":1,"result":...}]` для `callB24Method`
- [x] Убрано дублирующее подтверждение ИНН вторым `crm.requisite.list` в `createB24Company`: достаточно precheck `crm-check-inn-v1`

## Подзадачи

| ID | Статус | Описание |
|----|--------|----------|
| S1 | done | Анализ ветки `crm_inn_company_ambiguous` и транспорта `N8nCrmGateway` |
| S2 | done | Реализация нормализации списка реквизитов |
| S3 | optional | Выравнивание n8n `crm-requisite-list-v1` Out: отдавать массив строк как у `crm-check-inn-v1` |

## Риски

- Другие вызывающие `crm.requisite.list` вне оркестратора могут по-прежнему ожидать «сырой» Bitrix JSON — не затрагивалось.

## Next steps for Team Lead

- Различать **precheck** (`crm-check-inn-v1` / `crm-check-unique-contact-v1`) и **sync после `CUser::Add`** (`crm-requisite-list-v1` и др. через `callB24Method`). См. раздел «Хронология вызовов» в `docs/reference/registration-n8n-webhooks.md`.
- При похожих симптомах проверить execution n8n для `registration/crm-requisite-list-v1`: поле `result` в Respond — массив строк или обёртка Bitrix.
- При желании унифицировать workflow по контракту из `registration-n8n-webhooks.md`.
