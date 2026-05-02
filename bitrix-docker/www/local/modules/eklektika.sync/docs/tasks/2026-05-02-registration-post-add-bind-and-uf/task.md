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

- ~~Смоук на стенде~~ — **выполнено** (2026-05-02): см. «Закрытие E2E / вебхуки» ниже и ADR `2026-05-02-registration-e2e-verified.md`.

## Audit (2026-05-02, доп.)

- **ThrowException не останавливал поток:** после сбоя привязки регистрация всё равно возвращала успех — исправлено `bool` + `return false` из `createB24Company`; см. ADR `modules/eklektika.sync/docs/adr/2026-05-02-registration-crm-bind-throwexception-not-halting.md`.
- **`crm.company.update`:** проверка только `success===0` заменена на `isB24RestFailure`.

## Закрытие E2E / вебхуки (2026-05-02)

- Подтверждено: **регистрация проходит успешно**; цепочка **именованных webhooks** для регистрации на стенде **отлажена** (пречеки + `callB24Method`). Зафиксировано в ADR `modules/eklektika.sync/docs/adr/2026-05-02-registration-e2e-verified.md` и в `docs/business-processes/registration/verification-checklist.md` (блок «Статус»).

## Next steps for Team Lead

- Разработка по «запуску» регистрации **не требуется**. Дальше — только мониторинг редких сбоев привязки (см. аудит выше) и отдельные фичи по бэклогу.
