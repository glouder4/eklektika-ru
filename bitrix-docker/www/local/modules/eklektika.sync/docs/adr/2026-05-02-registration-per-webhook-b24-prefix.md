# ADR: Свой `b24_rest_prefix` на каждый `registration_webhook_*`

## Статус

Принято.

## Контекст

Часть инсталляций не использует один универсальный REST-транспорт в n8n: для каждого именованного webhook в Bitrix24 создаётся **отдельный** входящий вебхук (свой секрет в URL). Тогда переменная окружения `EKLEKTIKA_B24_REST_PREFIX` в n8n не подходит; префикс должен передаваться **вместе с запросом** и подставляться в Expression как `{{ $json.B24_REST_PREFIX }}`, а не собираться только из `$env`.

## Решение

1. В `config.local.php` значение ключа `registration_webhook_*` может быть **массивом** `url` + `b24_rest_prefix` + **`crm_method`** (строковые ключи также поддерживаются: `n8n_url`, `webhook_url`).
2. **`CrmRegistrationN8nTransport::resolveRegistrationWebhookB24Prefix($configKey)`** — отдаёт префикс из массива или глобальный fallback `RestTransportConfig::buildKitWebhookPrefix()`.
3. **`CrmRegistrationN8nTransport::resolveRegistrationWebhookCrmMethod($configKey)`** — каноническое `crm.*` по ключу или из массива; в JSON уходит как **`CRM_METHOD`**.
4. **`CrmRegistrationN8nTransport::post`** и **`N8nCrmGateway::callRestMethodWithWebhookUrl`** добавляют **`B24_REST_PREFIX`** (если непусто) и **`CRM_METHOD`**.

## Последствия

- n8n workflow может убрать зависимость от `$env.EKLEKTIKA_B24_REST_PREFIX` там, где сайт присылает префикс в теле.
- Обратная совместимость: строковое значение ключа (только URL n8n) сохраняется; префикс тогда только из глобального KIT, как раньше.

## Связанные документы

- `docs/reference/registration-n8n-webhooks.md`
