# Инициатива: страница кейсов `/o-kompanii/clients/` из ИБ 29

## Статус

**In progress** (2026-08-27): ADR Accepted. Код CASE-01 сделан и прошёл аудит (AC1–AC6 по коду PASS). Live-smoke CASE-02 **blocked** (`127.0.0.1:8588` refused; `new.eklektika.ru` невалиден). Цикл не закрыт.

## Цель

Заменить захардкоженную сетку `.clients` в `bitrix-docker/www/o-kompanii/clients/index.php` на `bitrix:news.list` по инфоблоку **IBLOCK_ID=29**, сохранив текущую вёрстку карточек и intro/SEO страницы.

Параллельно владелец подтвердил сортировку **SORT ASC** для отзывов ИБ 28 и кейсов ИБ 29. Для ИБ 28 это уже в коде (`otzyvy/index.php`); отдельный PHP-трек отзывов **не нужен**.

## Scope / Out of scope

**Scope:**

- Шаблон `news.list/clients` (+ lang/ru)
- Вызов компонента на `/o-kompanii/clients/` вместо хардкод-сетки (блок BEGIN/END team)
- Маппинг: `PREVIEW_PICTURE` → `<img>`, `CODE` → `href = /{CODE}/`, `NAME` → `alt`
- Сортировка вызова: `SORT ASC`, `ID ASC`

**Out of scope (этот цикл):**

- Любые правки `style.css` / `custom.css`
- `detail.php`, ИБ 9, urlrewrite `/o-kompanii/clients/` и `/clients/`
- Шаблон `news.list/our-clients` на главной
- Константа в `SiteModuleConfig` для 29
- Пагинация
- Graphify (`o-kompanii/clients/` вне `local`, шаблон в `templates`)
- SEO / header / footer / intro `<p>` страницы
- Миграция 15 хардкод-карточек в ИБ кодом (owner)
- PHP-правки `/otzyvy/` (сортировка ИБ 28 уже в коде)

## Source of truth

- ADR: `local/docs/adr/2026-08-27-clients-cases-iblock.md`
- Оркестрация: `local/docs/tasks/subtasks/next-steps.md`

## Критерии готовности

- **AC1**: на `/o-kompanii/clients/` — `news.list` ИБ 29, шаблон `clients`, хардкод-сетки нет — код PASS / live blocked
- **AC2**: классы `.clients`, `.row`, `.col-6.col-md-4.col-xl1-3`, `a.client[target=_blank]`, `img` — код PASS / live blocked
- **AC3**: href = `/{CODE}/` — код PASS / live blocked
- **AC4**: img = `PREVIEW_PICTURE`, alt = `NAME`; skip пустого CODE и пустой картинки; пустой список — без `.clients` — код PASS / live blocked
- **AC5**: `SORT ASC`, `ID ASC` — код PASS / live blocked
- **AC6**: `style.css` не менялся; intro и SEO на месте — код PASS / live blocked

## Субзадачи

| Task ID | Содержание | Owner | Priority | Status |
|---------|------------|-------|----------|--------|
| CASE-01 | Шаблон `news.list/clients` + замена сетки в `o-kompanii/clients/index.php` | dev | P0 | DONE (аудит 2026-08-27, AC1–AC6 по коду PASS) |
| CASE-02 | Smoke AC1–AC6 на `/o-kompanii/clients/` | QA после CASE-01 | P1 | BLOCKED (локальный Bitrix docker не запущен: канон `127.0.0.1:8588` connection refused, оркестратор перепроверил curl; контейнеров eklektika/bitrix нет. `new.eklektika.ru` невалиден для локальных файлов) |

Контент ИБ 29 (активные элементы с `CODE` и `PREVIEW_PICTURE`) — owner, не Task ID цикла.

## Риски

- Пустой / неактивный ИБ 29 или элементы без CODE/картинки → страница только с intro.
- Не смешать ИБ 9 (detail) и ИБ 29 (сетка index); не взять шаблон `our-clients`.
- `SET_TITLE=Y` сломает заголовок «Кейсы» — в вызове все title/meta = `N`.
- Локальный smoke без стенда на **8588** невалиден; `new.eklektika.ru` не доказательство этого кода.

## Graphify

Не вызывать: страница вне `local`, шаблон в `templates`.
