# ADR: Нормализация ответа `crm.requisite.list` при подтверждении ИНН

## Статус

Принято (исправление регистрации).

## Контекст

После успешного precheck ИНН (`crm-check-inn-v1`, массив реквизитов в `result`) регистрация вызывает второй запрос `crm.requisite.list` через `callB24Method` → вебхук `registration_webhook_crm_requisite_list_url`. Транспорт `N8nCrmGateway::callRestMethodWithWebhookUrl` возвращает только поле **`result`** из envelope ответа n8n.

Если workflow **`crm-requisite-list-v1`** кладёт в `result` **сырой JSON Bitrix** (`{ result: [...], total, time }`), PHP получает не список строк, а обёртку. Функция `resolveExactCompanyIdByInnFromRequisites` не извлекает `ENTITY_ID`, сравнение с кандидатом из precheck не выполняется → флаг `crm_inn_company_ambiguous` и текст про «подтверждение через n8n».

Precheck не страдает: там используется `unwrapRegistrationWebhookResult`, который отдаёт уже внутренний массив строк.

## Решение

В `CrmRegistrationOrchestrator` добавлена нормализация **`normalizeCrmRequisiteListRows`**: поддерживаются и плоский список реквизитов, и тело ответа Bitrix с ключом `result`.

Использование:

- подтверждение компании по ИНН (`createB24Company`);
- `enforceCompanyInnInRequisites` (перечень реквизитов компании).

Дополнения (если ошибка «подтверждение через n8n» сохранялась):

1. **`N8nCrmGateway::peelN8nSingleItemJsonEnvelope`** — тот же контракт, что у precheck: ответ `[{"success":1,"result":...}]` распаковывается до объекта до чтения `result`.
2. **Повторный `crm.requisite.list` пустой при успешном транспорте** — если precheck уже вычислил `candidateCompanyId > 0`, повторная проверка не блокирует регистрацию (доверие раннему успешному precheck).

**Обновление:** второй вызов `crm.requisite.list` только для подтверждения компании по ИНН в `createB24Company` **убран** — истина одна: успешный precheck `crm-check-inn-v1`. Отдельный URL `registration_webhook_crm_requisite_list_url` используется там, где нужен список реквизитов по компании (например `enforceCompanyInnInRequisites`).

## Альтернативы

- Привести все n8n-ветки к одному контракту (в `result` всегда массив строк). Полезно для ясности, но PHP-нормализация устойчивее к расхождению версий workflow.

## Связанные документы

- `docs/reference/registration-n8n-webhooks.md`
- `modules/eklektika.b24.rest/lib/N8nCrmGateway.php`
