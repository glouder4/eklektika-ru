# ADR: Блок популярных категорий на главной из инфоблока (IBLOCK_ID=26)

## Статус

Принято (2026-08-26). Код ещё не реализован.

## Контекст

На главной (`/`, `bitrix-docker/www/index.php`, строки 150–159) блок популярных категорий подключается через `bitrix:main.include` → `/include/page_index/main-popular-block.php`. Ссылки и картинки зашиты в PHP.

Владелец: визуал тот же, данные — из **IBLOCK_ID=26**.

На той же главной уже стоят `bitrix:news.list` с шаблонами `mainpage-slider` (ИБ 17) и `marquee-slider` (ИБ 18) — это канонический паттерн.

Инфоблок кастомных страниц `pages` — **IBLOCK_ID=25** (`SiteModuleConfig::PAGES_IBLOCK_ID`), URL `/pages/#ELEMENT_CODE#/`. Это **другой** инфоблок. Не путать с 26. Корневые href `/{CODE}/` блока популярного — это не DETAIL_PAGE_URL ИБ 25 и не фаза 2 ADR `2026-08-18-custom-pages-iblock-sef`.

CSS карточек уже есть в `local/templates/eklektika/assets/css/custom.css` (`.main-popular-block`, `.popular-cat-item`, `.p-cat-item-4`). Стили не меняем.

## Решение

1. **Компонент:** `bitrix:news.list`, шаблон `main-popular-block`.
2. **Шаблон:** `local/templates/eklektika/components/bitrix/news.list/main-popular-block/template.php`.
   - Обёртка `.main-popular-block.flex-wrapper`.
   - Карточка: `<a class="popular-cat-item p-cat-item-4">` (класс всегда `p-cat-item-4`, не 1/2/3).
   - Цикл по `$arResult["ITEMS"]`.
   - Свой `style.css` у шаблона **не** создавать.
3. **href:** `'/' . $arItem['CODE'] . '/'`. Не `DETAIL_PAGE_URL`. Пустой CODE — элемент не рендерить.
4. **Фон:** `background-image` из `$arItem['PREVIEW_PICTURE']['SRC']`. В вызове `FIELD_CODE` = `PREVIEW_PICTURE`. Нет картинки — карточка с CODE всё равно рендерится, без `background-image`.
5. **Вызов в `index.php`:** заменить только include `main-popular-block.php`. Параметры:
   - `IBLOCK_ID` = `26`
   - `IBLOCK_TYPE` — фактический тип ИБ 26 из админки (не копировать `sliders` вслепую)
   - `SORT_BY1` = `SORT`, `SORT_ORDER1` = `ASC`
   - `SET_TITLE` / `SET_BROWSER_TITLE` = `N`
   - кэш как у соседних слайдеров (`CACHE_TYPE=A`, `CACHE_TIME=36000000`, `CACHE_GROUPS=N`)
   - `NEWS_COUNT` = `4` (сетка `p-cat-item-4`)
   - `DISPLAY_BOTTOM_PAGER` / `DISPLAY_TOP_PAGER` = `N`
6. **Старый include-файл** `/include/page_index/main-popular-block.php` **не удалять** (rollback: вернуть `bitrix:main.include`).
7. **Константа `SiteModuleConfig` для ИБ 26 — не добавлять** в этом цикле. Слайдеры 17/18 на той же странице уже захардкожены; один call-site; `index.php` вне модуля. ИБ 25 остаётся в конфиге, потому что это раутинг. Вынести `26` в константу — когда появится второй вызов.

## Consequences

- Редактор меняет карточки главной элементами ИБ 26 (SORT, CODE, анонс-картинка), без правки PHP include.
- CODE должен совпадать с уже существующими корневыми URL (`/catalog/`, `/eklektika_primo/`, …). Этот цикл **не** создаёт urlrewrite и **не** мигрирует лендинги.
- Пятый и далее элементы ИБ 26 на главной не попадут в разметку (`NEWS_COUNT=4`). Расширение сетки — отдельный цикл (потребуется CSS).

## Out of scope

- Правки `custom.css` и любых стилей шаблона.
- Удаление `/include/page_index/main-popular-block.php`.
- ИБ 25 / `/pages/#CODE#/` / корневые алиасы ЧПУ.
- Константа и опция модуля для ID 26.
- Graphify: `index.php` вне `local`; шаблон в `templates` — исключён. Индексация не требуется.

## Риски

| Риск | Митигация |
|------|-----------|
| ИБ 26 пуст / элементы неактивны → пустой блок на главной | POP-03: 4 активных элемента до выкладки |
| Неверный `IBLOCK_TYPE` | Взять тип из админки ИБ 26 |
| CODE не совпадает с живым URL → 404 | CODE как у текущих href include-файла |
| Пустой CODE | skip в шаблоне |
| Путаница с ИБ 25 | Явный запрет `PAGES_IBLOCK_ID` и `/pages/` |

## Acceptance

| AC | Критерий |
|----|----------|
| AC1 | На `/` вместо include рендерится `bitrix:news.list`, `IBLOCK_ID=26`, шаблон `main-popular-block` |
| AC2 | Классы карточек те же: `.main-popular-block`, `.popular-cat-item`, `.p-cat-item-4` |
| AC3 | `href` карточки = `/{CODE}/` |
| AC4 | `background-image` = `PREVIEW_PICTURE` |
| AC5 | Порядок = `SORT ASC` |
| AC6 | `custom.css` не менялся |
