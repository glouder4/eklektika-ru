# 02: Рефакторинг PHP — разрешение URL по методу

## Статус реализации

**Сделано:** статическая карта «метод → ключ конфига» в `CrmRegistrationOrchestrator::registrationCrmRestWebhookConfigKey()`, резолв URL через `CrmRegistrationN8nTransport::resolveRegistrationWebhookUrl()`, вызов `N8nCrmGateway::callRestMethodWithWebhookUrl` с телом **METHOD + PARAMS** (контракт единый для всех именованных вебхуков). Это соответствует духу варианта A без отдельного массива в `config.local.php` — ключи плоские, как у остальных `registration_webhook_*`.

---

## Вариант A (минимальный)

В конфиге завести ассоциативный массив, например `registration_crm_method_webhook_urls`:

```php
'registration_crm_method_webhook_urls' => [
    'crm.company.get' => 'https://n8n/.../crm-company-get-v1',
    // ...
],
```

В `callB24Method` сначала искать URL по ключу `$method`; если нет — **не** падать на универсальный прокси (после миграции), а явная ошибка конфигурации.

## Вариант B (явные методы-обёртки)

Убрать универсальный `callB24Method` и заменить на `callRegistrationWebhookForCompanyGet`, `...RequisiteAdd`, и т.д., каждый читает свой ключ — максимально явно, больше кода.

## Рекомендация

Начать с **варианта A** + постепенный переход на B для горячих путей.

## Контракт тела запроса к n8n

Сейчас универсальный прокси принимает `{ METHOD, PARAMS }`. Отдельные вебхуки обычно принимают **только `PARAMS`** (метод зашит в workflow). При миграции унифицировать на стороне n8n или оборачивать в PHP один раз.
