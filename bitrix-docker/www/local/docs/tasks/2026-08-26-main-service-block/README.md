# Инициатива: блок услуг на главной из ИБ 27

## Статус

**In progress** (2026-08-26): ADR принят. Код SVC-01 сделан и прошёл аудит (AC1–AC6 по коду). Контент POP-03 — owner. Live-smoke SVC-02 blocked (нет локального стенда, на `new.eklektika.ru` ещё старый include).

## Цель

Заменить `bitrix:main.include` → `/include/page_index/block_service.php` на `bitrix:news.list` по инфоблоку **IBLOCK_ID=27**, сохранив текущую сетку и классы карточек.

## Scope / Out of scope

**Scope:**

- Шаблон `news.list/main-service-block`
- Вызов компонента на `/` вместо include
- Контент ИБ 27: 6 элементов с CODE из текущего include (owner)

**Out of scope (этот цикл):**

- Любые правки `custom.css`
- Константа в `SiteModuleConfig` для 27
- Удаление include-файла
- ИБ 25 (`/pages/#CODE#/`) и ИБ 26 (popular)
- Создание/миграция лендингов `/{CODE}/`

## Source of truth

- ADR: `local/docs/adr/2026-08-26-main-service-block-iblock.md`
- Оркестрация: `tasks/subtasks/next-steps.md`

## Критерии готовности

- **AC1**: на `/` — `news.list` ИБ 27 вместо include `block_service` — код PASS / live blocked
- **AC2**: классы `.main-items-anons`, `.item-anons`, `.item` как сейчас — код PASS / live blocked
- **AC3**: `href = /{CODE}/` — код PASS / live blocked
- **AC4**: img = `PREVIEW_PICTURE`, title = `NAME` — код PASS / live blocked
- **AC5**: `SORT ASC` — код PASS / live blocked
- **AC6**: `custom.css` не менялся этим циклом — код PASS (dirty hunks в файле — каталог/корзина) / live blocked

## Субзадачи

| Task ID | Содержание | Owner | Status |
|---------|------------|-------|--------|
| SVC-01 | Шаблон `main-service-block` + замена вызова в `index.php` | dev | DONE (аудит 2026-08-26) |
| POP-03 | 6 элементов ИБ 27 (CODE/NAME/картинка/текст) | owner | OWNER (блокер live-smoke) |
| SVC-02 | Smoke AC1–AC6 на `/` | QA после SVC-01+POP-03 | BLOCKED (`127.0.0.1:8590` connection refused; стенд со старым include; стек не поднимали) |

## Риски

- Без CODE карточка скипается; без `PREVIEW_PICTURE` нет `<img>` — сетка живая, визуал беднее.
- Пустой ИБ 27 → на главной блок исчезнет, пока owner не заведёт элементы.
- `/{CODE}/` должны совпасть с уже существующими лендингами (underscore у `sublimacionnaya_pechat`).
- Не смешать ИБ 25/26/27.

## Graphify

Не вызывать: `index.php` вне `local`, шаблон в `templates`.
