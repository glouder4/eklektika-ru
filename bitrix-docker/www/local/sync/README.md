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

## Обновление статуса: 2026-04-24

- `local/classes/ajax.php` приведён к роли тонкого входящего фасада: bootstrap + проверка `InboundSecurity` + делегирование в `InboundGateway`.
- Маршрутизация входящих `ACTION` закреплена в `local/sync/from-crm/InboundGateway.php`, включая JSON-ответы для `UPDATE_COMPANY` / `DELETE_COMPANY`.
- Бизнес-классы перенесены из `local/classes/*` в `local/modules/eklektika.*`; в `local/classes/requires.php` добавлена единая цепочка `include.php` модулей.
- На стороне B24 (`eklektika-ru-b24`) события компании и контактов переведены на `local/sync/to-site/*`.
- Фикс удаления компании: `OnBeforeCrmCompanyDelete` отправляет `DELETE_COMPANY`, сайт удаляет элемент компании в ИБ `23`, успех/ошибка возвращается синхронно в JSON.

## Активные task-артефакты

- INN-first registration rewrite + hard gates:
  - `local/sync/docs/tasks/2026-04-24/inn-first-registration-hard-gates/task.md`
  - текущий статус: `at risk`

## Roadmap/статус (INN-first rewrite, 2026-04-24)

- Этап 1 (контракт и документация): выполнен частично, ключевые правила INN-first/B24-by-ID зафиксированы.
- Этап 2 (hard-gates remediation): в работе, QA/Release/SRE/final go-teamlead вернули `changes requested`.
- Этап 3 (release readiness): не начат, переход только после закрытия замечаний этапа 2.
- Блокеры: незакрытые замечания QA и эксплуатационно-релизные риски (Release/SRE).
- Следующий шаг: единый remediation-пакет по замечаниям QA+Release+SRE с прогоном `docs/registration-checklist.md`.
