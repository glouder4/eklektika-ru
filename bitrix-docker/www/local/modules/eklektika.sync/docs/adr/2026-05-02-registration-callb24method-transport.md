# ADR: имя `callB24Method` и фактический транспорт (n8n REST-прокси)

## Статус

Принято (2026-05-02). **Дополнено (2026-05-05):** один URL на все `crm.*` не используется. **Реализация:** `callB24Method` маршрутизирует по карте «метод → `registration_webhook_crm_*_url`»; см. ADR `2026-05-05-one-crm-method-one-n8n-webhook.md`.

## Контекст

В `CrmRegistrationOrchestrator` присутствует приватный метод `callB24Method`, через который вызываются `crm.requisite.*`, `crm.company.get`, `crm.contact.*` и др. Название метода создаёт впечатление прямого обращения к Bitrix24. Фактически исходящий HTTP — на **именованные** вебхуки n8n; путаница с «proxy» снята отказом от единого `registration_crm_rest_proxy_webhook_url` в пользу явных URL на метод.

## Решение

- **Инвариант:** PHP на сайте **не** вызывает REST API облака Bitrix24 напрямую. Исходящий HTTP всегда на **вебхук n8n**.
- Транспорт: для каждого метода — свой ключ `registration_webhook_crm_*_url` (или base + `registration_webhook_path_suffixes`); вызов через `N8nCrmGateway::callRestMethodWithWebhookUrl` (контракт METHOD/PARAMS); исполнение REST на портале — в workflow n8n.
- Рядом остаются другие **именованные** вебхуки регистрации (`registration_webhook_unique_url`, `registration_webhook_company_add_url`, …) — отдельные сценарии precheck и мутаций.

## Последствия

- Переименование PHP-метода или конфиг-ключа (например, более говорящее имя вместо «proxy») возможно как косметический рефакторинг; инвариант «только n8n» от этого не меняется.

## Ссылки

- `docs/reference/registration-n8n-webhooks.md` — раздел `callB24Method` и ключи `registration_webhook_crm_*_url`
- `modules/eklektika.b24.rest/lib/N8nCrmGateway.php`
