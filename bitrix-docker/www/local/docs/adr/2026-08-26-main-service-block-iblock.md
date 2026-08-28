# ADR: Блок услуг на главной из инфоблока (IBLOCK_ID=27)

## Статус

**Accepted** (2026-08-26). Реализация открыта (этот цикл).

## Контекст

На главной (`bitrix-docker/www/index.php`, строки 417–426) блок услуг выводится через `bitrix:main.include` → `/include/page_index/block_service.php`: 3 ряда × 2 карточки, захардкоженные href/картинки/тексты.

Владелец: заменить на компонент инфоблока **IBLOCK_ID=27**, «то же самое» что цикл ИБ 26 (popular block).

Маппинг владельца:

| Слот | Источник |
|------|----------|
| Фото | `PREVIEW_PICTURE` |
| Название | `NAME` |
| Ссылка | `/{CODE}/` |

Факты вёрстки include (не менять визуальный контракт):

- Классы: `.row.no-gutters.flex-wrapper.main-items-anons` > `.item-anons` > `a.item`.
- В карточке: `<img>`, `.item-title .h3.strong`, `.item-description`.
- Фон карточки — фиксированный `linear-gradient(99.89deg, #F4F4F4 1.16%, #F6FAFF 101.52%)`, не картинка.
- CSS уже в `custom.css` (~1329+): `.item-anons` width 49%. **Не менять.**

Не путать инфоблоки:

| ID | Роль | Публичный URL |
|----|------|----------------|
| 25 | кастомные страницы (`pages`) | `/pages/#CODE#/` (ADR 2026-08-18) |
| 26 | popular-блок на главной | карточки `/{CODE}/` |
| 27 | **этот** блок услуг на главной | карточки `/{CODE}/` |

ИБ 27 — источник карточек на главной, не движок лендингов. Корневые `/{CODE}/` уже живут как отдельные страницы сайта.

Константу в `SiteModuleConfig` для 27 **не вводить** (как для 26). Graphify в этом цикле не вызывать (`index.php` вне `local`, шаблон в `templates`).

## Решение

Канон: `bitrix:news.list`, как ИБ 26 (`main-popular-block`) и слайдеры 17/18.

1. **Шаблон** `news.list/main-service-block` (не переиспользовать `main-popular-block`: там фон = `background-image` из картинки, без `<img>`/текста).
2. **href** собирать из `CODE`: `/{CODE}/`. Пустой CODE — `continue` (не `DETAIL_PAGE_URL`).
3. **Картинка:** `<img src>` из `PREVIEW_PICTURE.SRC`. Нет картинки — тег `<img>` не рендерить. Не фон, не `DETAIL_PICTURE`. Lazy/`data-src` не обязательны.
4. **NAME** в `.item-title` → `<p class="h3 strong">`.
5. **PREVIEW_TEXT** в `.item-description` (иначе карточка потеряет абзацы текущего include). Пустой текст — блок описания не рендерить. Не оборачивать второй раз в `<p>`, если поле уже HTML.
6. **Ряды:** чанки по 2 элемента после skip CODE — три (или меньше) соседних `.row.no-gutters.flex-wrapper.main-items-anons`. Нечётный остаток — один `.item-anons` в последнем ряду (width 49% это допускает). Пустой результат — ничего не выводить.
7. **Фон `a.item`:** тот же hardcoded gradient, что в include.
8. **Вызов на главной** вместо `main.include` block_service:

| Параметр | Значение |
|----------|----------|
| Компонент / шаблон | `bitrix:news.list` / `main-service-block` |
| `IBLOCK_ID` | `27` (литерал, без константы модуля) |
| `IBLOCK_TYPE` | `content` (числовой ID тип не фильтрует) |
| `SORT_BY1` / `SORT_ORDER1` | `SORT` / `ASC` |
| `SORT_BY2` / `SORT_ORDER2` | `ID` / `ASC` (как popular) |
| `NEWS_COUNT` | `6` (текущая сетка) |
| pager / `SET_TITLE` / meta | `N` |
| кэш | как слайдеры/popular: `CACHE_TYPE=A`, `CACHE_TIME=36000000`, `CACHE_GROUPS=N` |
| `FIELD_CODE` | `CODE`, `PREVIEW_PICTURE` |
| `DISPLAY_PICTURE` | `Y` |
| `DISPLAY_PREVIEW_TEXT` | `Y` |
| `PREVIEW_TRUNCATE_LEN` | пусто |

9. Файл include **не удалять**. `custom.css` **не трогать**.

## Consequences

- Редактор меняет карточки услуг элементами ИБ 27 (SORT, NAME, CODE, PREVIEW_PICTURE, PREVIEW_TEXT).
- Карточка без CODE на витрине не появится; без картинки — без круглого фото (правый padding карточки останется).
- Существующие лендинги `/{CODE}/` этим циклом не создаются и не переезжают в ИБ 25.

## Out of scope

- Правки `custom.css` и сетки `.item-anons`.
- Константа `SiteModuleConfig` для ИБ 27.
- Удаление `/include/page_index/block_service.php`.
- Корневые ЧПУ / миграция лендингов / ИБ 25.
- Свойства ИБ кроме стандартных полей элемента.

## Acceptance

| AC | Критерий |
|----|----------|
| AC1 | На `/` вызывается `news.list` ИБ 27 вместо include `block_service` |
| AC2 | Классы `.main-items-anons`, `.item-anons`, `.item` как в текущем include |
| AC3 | `href = /{CODE}/` |
| AC4 | `img` = `PREVIEW_PICTURE`, title = `NAME` |
| AC5 | Сортировка `SORT ASC` |
| AC6 | `custom.css` не менялся |

## Source of truth

- Этот ADR.
- Инициатива: `local/docs/tasks/2026-08-26-main-service-block/README.md`.
- Оркестрация: `tasks/subtasks/{adr-index,progress-checklist,next-steps}.md`.
