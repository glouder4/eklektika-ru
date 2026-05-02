# ADR: один метод Bitrix24 CRM — отдельный вебхук n8n (без «универсального» маршрутизатора)

## Статус

Принято (2026-05-05). **Реализовано в коде:** `CrmRegistrationOrchestrator::registrationCrmRestWebhookConfigKey` + удалён универсальный `registration_crm_rest_proxy_webhook_url`.

## Контекст

Ранее допускался `registration_crm_rest_proxy_webhook_url`: один URL на все `METHOD` + `PARAMS`. По политике проекта универсальный вход **не используется**: каждый вызываемый метод REST имеет **свой** конфиг-ключ и вебхук n8n.

## Решение

1. **Целевая модель:** для каждого используемого в регистрации вызова `crm.*` задаётся **отдельный** конфиг-ключ с полным URL вебхука n8n (как уже сделано для `registration_webhook_unique_url`, `registration_webhook_inn_url`, `crm-company-add-v1`, и т.д.).
2. **`registration_crm_rest_proxy_webhook_url`:** **удалён** из конфигурации и кода регистрации.
3. **Код:** `callB24Method` разрешает URL по статической карте «метод → ключ конфига» (`registrationCrmRestWebhookConfigKey`).
4. **Документация:** в `docs/reference/registration-n8n-webhooks.md` закрепить инвариант «один метод — один вебхук».

## Текущие вызовы через `callB24Method` (инвентарь для миграции)

Методы в `CrmRegistrationOrchestrator`:  
`crm.contact.company.add`, `crm.company.contact.add`, `crm.company.update`, `crm.requisite.list`, `crm.requisite.update`, `crm.company.get`, `crm.requisite.add`, `crm.contact.list`, `crm.contact.update`, `crm.contact.company.delete`.

`crm.requisite.list` вызывается только через `callB24Method` → `registration_webhook_crm_requisite_list_url` (единый канал).

## Последствия

- n8n: для каждого метода — отдельный workflow или webhook path (без одного mega-router на все `crm.*`).
- PHP: зависимость от универсального ключа снята.
- Риск: увеличение числа URL в конфиге и workflow; зато явные контракты и изоляция изменений.

## Ссылки

- Задача миграции: `docs/tasks/2026-05-05-registration-one-method-one-webhook/`
- Код: `CrmRegistrationOrchestrator::callB24Method`, `N8nCrmGateway::callRestMethodWithWebhookUrl`
