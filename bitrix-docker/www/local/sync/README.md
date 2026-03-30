# Синхронизация сайт ↔ Bitrix24

Единая зона интеграции в репозитории сайта. Новую логику обмена с CRM размещать **здесь**, а не размазывать по разным каталогам в `local/`.

Подробные правила для Cursor и команды: [`.cursor/rules/eklektika-rules.mdc`](../../../../.cursor/rules/eklektika-rules.mdc) (от корня монорепозитория `eklektika-ru`).

## Содержимое

| Путь | Назначение |
|------|------------|
| [`docs/channels.md`](docs/channels.md) | Каналы, базовый транспорт CRM ↔ сайт |
| [`docs/inventory.md`](docs/inventory.md) | Инвентаризация `ACTION`, событий и вызовов |
| [`docs/functional-contract.md`](docs/functional-contract.md) | **Предметный контракт:** связи сущностей, жизненный цикл, to-crm, `ajax`↔`from-crm`, сбои доставки, приём на B24 |
| [`docs/MIGRATION.md`](docs/MIGRATION.md), [`docs/regression-checklist.md`](docs/regression-checklist.md) | Миграция и регрессия |
| [`docs/runbook.md`](docs/runbook.md) | *Опционально:* шпаргалка «обмен встал» — не мониторинг ради отчётов; конвейер — в `functional-contract.md` |
| *(код по мере рефакторинга)* | Классы в `to-crm/`, `from-crm/`, общий bootstrap — по мере переноса из `local/classes/*` |

## Каналы (логически)

- **Сайт → CRM** — исходящий обмен на портал (исторически часть кода в `local/classes/b24/`, `local/crm/`).
- **CRM → Сайт** — входящий обмен с портала (исторически часть в `local/classes/site/`, приём через `local/classes/ajax.php`).

Имена папок внутри `sync/` при переносе кода лучше брать **по направлению**, например `to-crm/` и `from-crm/`, чтобы не путать с «весь проект — сайт».

## Транспорт CRM → сайт

- С портала: `Updater` и др. → HTTP POST.
- На сайте: endpoint `/local/classes/ajax.php`, поле `ACTION`.

Детали и нефункциональные требования — в [`docs/channels.md`](docs/channels.md).

## Текущее состояние (наследие)

До завершения рефакторинга значимая часть кода остаётся в:

- `local/classes/b24/`, `local/classes/site/`, `local/crm/`, `local/classes/ajax.php`, `local/events/events.php`

Перенос в `local/sync/` — по плану рефакторинга, с временными алиасами и без дублирования обработчиков.
