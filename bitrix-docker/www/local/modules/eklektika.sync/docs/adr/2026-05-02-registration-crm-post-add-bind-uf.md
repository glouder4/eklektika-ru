# ADR: привязка контакта к компании и UF элемента сайта после crm.company.add

## Статус

Принято (2026-05-02).

## Контекст

После успешного `crm.company.add` / `crm.contact.add` в CRM не обновлялся UF `UF_CRM_1774915439581` (связь с элементом ИБ) и/или не отображалась привязка контакта к компании, хотя сущности в портале существовали.

## Решение

1. **Нормализовать** ответ `crm.company.get`: при отсутствии `ID` в payload использовать идентификатор, уже известный из `crm.company.add`, чтобы `COMPANY_ID` в `crm.contact.add` и `ENTITY_ID` в `crm.requisite.*` не терялись.
2. **Проверять** результат `crm.contact.company.add`; при структуре ошибки — **retry** через `crm.company.contact.add` (тот же смысл связи, иной entry REST).
3. **Зафиксировать** флаг `IS_PRIMARY = Y` в полях привязки (рекомендация REST Bitrix24).
4. **Исправить** ветку ИНН: если после проверки requisite компания в CRM не подтверждена вторым вебхуком, но кандидат по ИНН был > 0 — не выполнять автоматический `crm.company.add` (избежать дубликата); вернуть понятную ошибку.

## Дополнение (2026‑05‑02, формат ответа contact‑webhook)

Ответ `crm-contact-add-v1` иногда приходит как `[{"success":1,"result":107}]`. Без распаковки массива‑обёртки `normalizeCrmAddResult` возвращал ошибку, цепочка обрывалась до REST‑прокси. Исправление: `unwrapRegistrationWebhookSingleElementEnvelope()` в `unwrapRegistrationWebhookResult()`.

## Ссылки

- `modules/eklektika.b24.registration/lib/CrmRegistrationOrchestrator.php`
- `docs/business-processes/registration/verification-checklist.md`
- `docs/reference/registration-n8n-webhooks.md`
