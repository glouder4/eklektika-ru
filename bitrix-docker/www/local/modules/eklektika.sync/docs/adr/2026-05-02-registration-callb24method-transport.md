# ADR: имя `callB24Method` и фактический транспорт (n8n REST-прокси)

## Статус

Принято (2026-05-02). Документ фиксирует разъяснение для разработчиков: метод не является «легаси прямого REST к порталу».

## Контекст

В `CrmRegistrationOrchestrator` присутствует приватный метод `callB24Method`, через который вызываются `crm.requisite.*`, `crm.company.get`, `crm.contact.*` и др. Название метода создаёт впечатление устаревшего прямого обращения к Bitrix24.

## Решение

- Транспорт: при непустом `registration_crm_rest_proxy_webhook_url` все вызовы идут в **n8n** через `N8nCrmGateway::callRestMethodWithWebhookUrl` (контракт METHOD/PARAMS). Прямого исходящего REST с PHP на облако портала нет.
- Параллельно существуют **именованные** вебхуки регистрации (`registration_webhook_*`) для узких операций (precheck, `crm.company.add`, `crm.contact.add` и т.п.) — см. `docs/reference/registration-n8n-webhooks.md`.
- Универсальный прокси оставлен для «длинного хвоста» методов без отдельного n8n entrypoint на каждый вызов.

## Последствия

- Переименование метода (например, в `callCrmRestViaRegistrationProxy`) возможно как косметический рефакторинг; функционально менять нечего, если задан прокси-URL.

## Ссылки

- `docs/reference/registration-n8n-webhooks.md` — ключ `registration_crm_rest_proxy_webhook_url`
- `modules/eklektika.b24.rest/lib/N8nCrmGateway.php`
