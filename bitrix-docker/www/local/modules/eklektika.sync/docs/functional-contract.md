# Функциональный контракт входящего sync (сайт)

Краткая замена удалённого `local/sync/docs/functional-contract.md`. Детали кода — в `InboundGateway`, `InboundSecurity`, `lib/from-crm/*`.

## HTTP

- **Канонический входящий канал CRM → сайт:** `POST` на путь `OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH` → скрипт `local/modules/eklektika.sync/public/inbound_crm.php` (body/query с полем `ACTION` и полезной нагрузкой).
- **Legacy:** `POST /local/classes/ajax.php` — тонкий `require` того же скрипта (совместимость со старыми закладками и интеграциями).
- **Исходящие вызовы с портала на сайт** (в т.ч. `CRM_METHOD` через `RestClient::postSiteRequestsHandler`) — тот же канонический URL; см. `RestTransportConfig::SITE_AJAX_PROXY_PATH` и `SITE_REQUESTS_HANDLER_PATH`.

## Модули

- Реализация: `local/modules/eklektika.sync/`.
- Регистрация и bootstrap: `local/php_interface/eklektika_requires.php`.

## Пользовательские поля CRM

- Соответствия UF: класс `CrmInboundUfMap` (этот файл — обзор; константы в коде).

## Документация задач

- `docs/tasks/2026-04-30-workspace-refactor-to-modules/task.md`

При необходимости полного архива runbook/inventory восстановите из истории git коммитов до удаления `local/sync/docs/`.
