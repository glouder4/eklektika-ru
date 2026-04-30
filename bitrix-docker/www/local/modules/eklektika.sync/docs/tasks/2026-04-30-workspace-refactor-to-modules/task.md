# Задача: сегментация рабочей области (local) — модули вместо разрозненных папок

## ADR (кратко)

- **Контекст:** В `local/` исторически накопились `sync/`, `events/`, `crm/`, лишние файлы в `classes/` с пересекающейся ответственностью.
- **Решение:** Бизнес-логика в `local/modules/eklektika.*`. Bootstrap модулей — `local/php_interface/eklektika_requires.php`. Входящий CRM → сайт: канонически **`eklektika.sync/public/inbound_crm.php`** (`CrmInboundEndpoint::HTTP_PATH`); **`/local/classes/ajax.php`** — совместимый шим.
- **n8n:** целевой обмен через n8n; PHP endpoint менять только по согласованию.

## Сделано (волна 3 — удаление «мусорных» директорий)

- Удалены каталоги **`local/sync/`**, **`local/events/`**, **`local/crm/`** (файлы и обёртки).
- Удалены **`local/classes/requires.php`**, мёртвый код **`classes/site/StatusDiscounter.php`**, заготовки **`classes/b24/`**.
- Добавлен **`local/php_interface/eklektika_requires.php`**; **`local/php_interface/init.php`** подключает его вместо `classes/requires.php`.
- Канонический inbound — **`eklektika.sync/public/inbound_crm.php`**; **`local/classes/ajax.php`** — шим. Папки **`local/sync/`** нет.
- Конфиг sync только **`local/modules/eklektika.sync/config.local.php`** (fallback `local/sync/config.local.php` снят).

## Риски

- Расширенные runbook/inventory из бывшего `local/sync/docs/` удалены вместе с папкой; краткий контракт переложен в `modules/eklektika.sync/docs/functional-contract.md`. Остальное — из git-истории при необходимости.
- Внешние интеграции, жёстко зашившие URL `/local/sync/from-site/site_requests_handler.php`, обновлены в коде на канонический путь (`RestTransportConfig` = `CrmInboundEndpoint`); старый `/local/classes/ajax.php` по-прежнему работает как шим.

## Критерии готовности

- Нет каталогов `local/sync`, `local/events`, `local/crm` в рабочей копии.
- Входящий канал: канонический скрипт модуля + опционально `local/classes/ajax.php` как шим.
