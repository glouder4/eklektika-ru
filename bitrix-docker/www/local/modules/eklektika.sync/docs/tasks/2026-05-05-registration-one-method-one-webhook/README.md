# Задача: отказ от универсального `registration_crm_rest_proxy_webhook_url` в пользу одного вебхука на метод

## Статус

**Код и документация — готово.** `callB24Method` маршрутизирует по `registration_webhook_crm_*_url`, универсальный `registration_crm_rest_proxy_webhook_url` удалён (ADR `2026-05-05-one-crm-method-one-n8n-webhook.md`).

**Операционно:** на стенде должны быть задеплоены n8n webhooks по всем path из `config.local.php` / `registration_webhook_path_suffixes` (см. риски ниже).

## Цель

Привести код и конфиги в соответствие с правилом: **уникальный REST-метод CRM → отдельный URL вебхука n8n**, без единого маршрутизатора `METHOD` + `PARAMS` на один URL.

## Критерии готовности

- [x] Для каждого вызова из инвентаря `callB24Method` задан ключ конфига в `registrationCrmRestWebhookConfigKey`; универсальный прокси удалён.
- [x] Документация и пример `config.local.php` обновлены (без `registration_crm_rest_proxy_webhook_url`).
- [ ] Тесты/регрессия регистрации на стенде (вручную / CI по политике команды).

## Субзадачи

| Файл | Содержание |
|------|------------|
| [subtasks/01-inventory-methods-and-config-keys.md](subtasks/01-inventory-methods-and-config-keys.md) | Таблица метод → предлагаемый ключ → приоритет миграции |
| [subtasks/02-php-resolve-url-by-method.md](subtasks/02-php-resolve-url-by-method.md) | Подход к рефакторингу `callB24Method` |

## Next steps for Team Lead

- **Синхронизировать n8n** с владельцем интеграций: один production webhook/path на каждый ключ `registration_webhook_crm_*_url` из инвентаря (`subtasks/01`).
- **QA:** закрыть пункт чеклиста «Тесты/регрессия регистрации на стенде» по матрице из `docs/business-processes/registration/verification-checklist.md`.

## Аудит (Team Lead)

- Карта `registrationCrmRestWebhookConfigKey` покрывает все вызовы `callB24Method` в `CrmRegistrationOrchestrator`.
- Дублирование `crm.requisite.list` (POST с `crmMethod` vs Gateway) снято — один транспорт.
- Универсальный ключ регистрации из PHP удалён; пример `config.local.php` содержит полный набор URL.

## Tech Lead — закрытие инициативы

- ADR обновлены: `2026-05-05-one-crm-method-one-n8n-webhook.md`, `2026-05-02-registration-callb24method-transport.md`, см. также кросс‑ссылки в `docs/reference/registration-n8n-webhooks.md`.
- **Graphify (политика local/):** после выкладки выполнить `/graphify-local-wave1` для актуализации графа знаний (сервер MCP `graphify-eklektika-ru-site`, исключения по `local-graphify-guard`).
