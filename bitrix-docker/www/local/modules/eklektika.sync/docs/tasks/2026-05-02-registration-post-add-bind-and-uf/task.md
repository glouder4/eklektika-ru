# Task: привязка контакт↔компания и UF сайта после регистрации

## Инициатива

Устранить расхождение: компания и контакт создаются в B24, но UF карточки сайта на компании не заполняется и связь контакта с компанией отсутствует.

## Ключевые находки

1. **`crm.contact.company.add`** вызывался без проверки ответа REST/n8n — сбой молчал.
2. **`crm.company.get`** после `crm.company.add`: при «обрезанном» ответе прокси терялся `ID` → `COMPANY_ID` у контакта и `ENTITY_ID` у реквизита могли быть некорректны (контакт без компании).
3. **Ветвление по ИНН**: при непустом `dataRequisite` и `candidateCompanyExists === false` ветка `crm.company.add` раньше не выполнялась; исправлено отдельным `if (companyId<=0)` + защита от дубликата при «двусмысленном» ИНН (кандидат > 0, вебхук не подтвердил — отказ, не создаём вторую компанию).

## Subtasks

- [x] **S1.** `CrmRegistrationOrchestrator`: нормализация `crm.company.get`, `bindContactToCompany` (retry `crm.company.contact.add`), структура `if` для `crm.company.add`, PHPDoc-логика.
- [x] **S2.** `docs/business-processes/registration/verification-checklist.md` — уточнение цепочки привязки.
- [x] **S3.** Распаковка ответа `[{"success":1,"result":ID}]` от `crm-contact-add-v1` / `unwrapRegistrationWebhookResult`; fallback в `normalizeCrmAddResult`; справка в `docs/reference/registration-n8n-webhooks.md`.

## Audit (team lead), 2026-05-02

- Соответствует `docs/reference/registration-n8n-webhooks.md` (REST-прокси + именованные вебхуки).
- Риск: при обоих сбоях привязки в лог пишется `H_bind`, в CRM остаётся «сиротский» контакт — требуется мониторинг / ручной fix.

## Next steps for Tech Lead

- Смоук на стенде: сценарии 1 и 3 из `verification-checklist.md`.
- При необходимости — донастройка n8n `crm-registration-rest-v1` (корректный JSON `result` для `crm.contact.company.add` / `crm.company.update`).

## Audit (2026-05-02, доп.)

- **ThrowException не останавливал поток:** после сбоя привязки регистрация всё равно возвращала успех — исправлено `bool` + `return false` из `createB24Company`; см. ADR `modules/eklektika.sync/docs/adr/2026-05-02-registration-crm-bind-throwexception-not-halting.md`.
- **`crm.company.update`:** проверка только `success===0` заменена на `isB24RestFailure`.
