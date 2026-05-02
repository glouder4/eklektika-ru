# ADR: контракты ответов registration webhooks и жёсткий отказ регистрации

## Статус

Принято (2026-05-02).

## Контекст

Регистрация могла считаться успешной при частичных сбоях n8n/REST (неожиданное тело, отсутствие полей, молчаливые ошибки прокси).

## Решение

1. **Именованные webhooks**: после HTTP 2xx тело проверяется по классу операции:
   - пречек / поиск — обязательны `success` и ключ `result` (`result` может быть пустым массивом);
   - создание компании/контакта — `success` и числовой ID в `result` (или объект с `ID`);
   - `crm.requisite.list` — `result` должен быть массивом.
2. **`registration_webhook_company_updates_url`**: если URL задан, ошибка HTTP или нарушение envelope прерывает регистрацию (раньше ошибки часто игнорировались).
3. **REST‑прокси**: на критичных шагах вызывается `assertRegistrationRestProxyOk` (реквизиты, при необходимости список реквизитов, привязка контакта, UF компании, создание элемента ИБ).

## Последствия

- Старые ответы без полей `success`/`result` нужно привести к контракту в n8n (см. `docs/reference/registration-n8n-webhooks.md`).

## Ссылки

- `docs/reference/registration-n8n-webhooks.md`
- `modules/eklektika.b24.registration/lib/CrmRegistrationOrchestrator.php`
