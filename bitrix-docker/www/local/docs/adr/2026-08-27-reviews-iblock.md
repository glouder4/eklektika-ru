# ADR: Страница отзывов `/otzyvy/` из инфоблока (IBLOCK_ID=28)

## Статус

**Accepted** (2026-08-27). Код реализован (REV-01, аудит AC1–AC6 по коду PASS). Residual: **REV-02 live-smoke**. Цикл не закрыт; статус Implemented не ставить до smoke. Lessons Learned — после REV-02.

## Контекст

Страница `bitrix-docker/www/otzyvy/index.php` рендерит захардкоженные HTML-карточки отзывов (сейчас 10 штук) внутри `.testimonials`.

Владелец: выводить отзывы из инфоблока **IBLOCK_ID=28**.

Маппинг владельца:

| Слот | Источник |
|------|----------|
| Заголовок / автор | `NAME` |
| Текст отзыва | `PREVIEW_TEXT` |

Факты вёрстки (не менять визуальный контракт):

- Контейнер `.testimonials`.
- Карточка `.testimonial-item` + schema.org `Review` (`itemprop="review"`, `itemscope`, `itemtype="http://schema.org/Review"`).
- `.testimonial-title` + `itemprop="author"` — имя автора.
- `.testimonial-company` всегда пустой.
- `.testimonial-txt` + `itemprop="reviewBody"` — текст.
- `<meta itemprop="name" content="...">` дублирует текст отзыва.
- Колонка `.col-sm-3` под картинку закомментирована / пустая; сетка `col-sm-3` + `col-sm-9` сохраняется.

CSS уже есть в `local/templates/eklektika/assets/css/style.css` (~7933+): `.testimonials`, `.testimonial-item`, `.testimonial-title`, `.testimonial-company`. **Не менять.**

Не путать инфоблоки:

| ID | Роль | Публичный URL |
|----|------|----------------|
| 25 | кастомные страницы (`pages`) | `/pages/#CODE#/` (ADR 2026-08-18) |
| 26 | popular-блок на главной | карточки `/{CODE}/` |
| 27 | блок услуг на главной | карточки `/{CODE}/` |
| 28 | **этот** список отзывов | страница `/otzyvy/` |

Уточнение владельца 2026-08-27: списки ИБ 28 и ИБ 29 сортируются по полю сортировки по возрастанию (`SORT ASC`, tie-breaker `ID ASC`). В вызове `/otzyvy/` это уже так; отдельный PHP-трек не открывать. Сетка кейсов — ADR `2026-08-27-clients-cases-iblock.md`.

Канон проекта (ADR 2026-08-26-main-popular-block-iblock, 2026-08-26-main-service-block-iblock): `bitrix:news.list` + шаблон в `local/templates/eklektika/components/bitrix/news.list/<name>/`.

Константу в `SiteModuleConfig` для 28 **не вводить** (один call-site, страница вне модуля). Graphify в этом цикле не вызывать (`otzyvy/index.php` вне `local`, шаблон в `templates`).

## Решение

Канон: `bitrix:news.list`, как ИБ 26/27. Шаблон **не** переиспользовать `main-service-block` / `main-popular-block` (другой визуальный контракт).

1. **Шаблон** `news.list/reviews`:
   - путь: `local/templates/eklektika/components/bitrix/news.list/reviews/template.php`
   - lang: `lang/ru/template.php` с `CT_BNL_ELEMENT_DELETE_CONFIRM` (как у `main-service-block`)
   - свой `style.css` у шаблона **не** создавать
   - `setFrameMode(true)`
2. **Обёртка.** Шаблон владеет `.testimonials` и карточками. В `otzyvy/index.php` заменить весь блок (обёртка + хардкод-карточки) одним `IncludeComponent`. SEO / `$GLOBALS['OG_TAGS']` / `SetPageProperty` / `SetTitle` / header / footer **не трогать**.
3. **Пустой результат.** Сначала отфильтровать элементы без текста. Если после skip список пуст — ничего не выводить (без пустого `.testimonials`).
4. **Skip.** Пустой `PREVIEW_TEXT` (после `trim`) — элемент не рендерить. Пустой `NAME` при непустом тексте — карточку рендерить (пустой `.testimonial-title` допустим).
5. **Карточка** — тот же DOM, что сейчас:

| Узел | Значение |
|------|----------|
| `.testimonial-title` + `itemprop="author"` | `NAME`, `htmlspecialcharsbx` |
| `.testimonial-company` | пустой (свойства ИБ не запрашивать) |
| `.testimonial-txt` + `itemprop="reviewBody"` | `PREVIEW_TEXT`: если `PREVIEW_TEXT_TYPE=html` — echo как есть; иначе `htmlspecialcharsbx` |
| `<meta itemprop="name">` | текст отзыва для атрибута: `htmlspecialcharsbx`; если тип html — сначала `strip_tags` |
| `.col-sm-3` | пустая колонка, без `<img>` |

6. **Картинки не выводить** (в текущей вёрстке закомментированы; владелец не просил).
7. **Edit-area** на карточке: `AddEditAction` / `AddDeleteAction` / `GetEditAreaId` как у `main-service-block`.
8. **Вызов** на `/otzyvy/` вместо хардкода:

| Параметр | Значение |
|----------|----------|
| Компонент / шаблон | `bitrix:news.list` / `reviews` |
| `IBLOCK_ID` | `28` (литерал, без константы модуля) |
| `IBLOCK_TYPE` | `content` (как ИБ 26/27) |
| `SORT_BY1` / `SORT_ORDER1` | `SORT` / `ASC` (владелец 2026-08-27: то же для списка кейсов ИБ 29; в этом вызове уже так — PHP не трогать) |
| `SORT_BY2` / `SORT_ORDER2` | `ID` / `ASC` |
| `NEWS_COUNT` | `100` (все отзывы; пагинацию владелец не просил) |
| pager / `SET_TITLE` / `SET_BROWSER_TITLE` / meta | `N` |
| кэш | `CACHE_TYPE=A`, `CACHE_TIME=36000000`, `CACHE_GROUPS=N` |
| `FIELD_CODE` | `NAME`, `PREVIEW_TEXT` |
| `DISPLAY_PREVIEW_TEXT` | `Y` |
| `PREVIEW_TRUNCATE_LEN` | пусто |
| `DISPLAY_PICTURE` | `N` |

9. Фоллбэк на хардкод-карточки **не** оставлять.

## Consequences

- Редактор меняет отзывы элементами ИБ 28 (SORT, NAME, PREVIEW_TEXT), без правки PHP страницы.
- Элемент без текста анонса на витрине не появится.
- Пустой / неактивный ИБ 28 → на `/otzyvy/` не будет карточек (заголовок страницы останется).
- `NEWS_COUNT=100` скрывает 101-й и далее; расширение — отдельный цикл (пагинация не в scope).

## Out of scope

- Правки `style.css` / `custom.css` и любой CSS шаблона.
- Картинки отзывов (`PREVIEW_PICTURE` / `DETAIL_PICTURE`).
- Свойства ИБ (в т.ч. компания для `.testimonial-company`).
- Константа `SiteModuleConfig` для ИБ 28.
- Пагинация.
- Форма добавления отзыва.
- Graphify.
- Миграция 10 хардкод-текстов в ИБ (контент — ответственность редактора/owner).

## Риски

| Риск | Митигация |
|------|-----------|
| ИБ 28 пуст / элементы неактивны / без `PREVIEW_TEXT` → пустая страница | Owner проверяет контент до выкладки; шаблон не рисует пустую обёртку |
| Неверный `IBLOCK_TYPE` | Литерал `content`, как у ИБ 26/27 |
| HTML в `PREVIEW_TEXT` ломает `meta content` | `strip_tags` + `htmlspecialcharsbx` только для meta |
| Путаница с ИБ 25/26/27 | Явный ID 28, страница `/otzyvy/` |
| `SET_TITLE=Y` перезапишет «Отзывы» | Все title/meta компонента = `N` |

## Acceptance

| AC | Критерий |
|----|----------|
| AC1 | На `/otzyvy/` вызывается `news.list` ИБ 28, шаблон `reviews`, вместо хардкод-карточек |
| AC2 | Классы `.testimonials`, `.testimonial-item`, `.testimonial-title`, `.testimonial-company`, `.testimonial-txt` и schema.org `Review` как в текущей разметке |
| AC3 | `NAME` → `.testimonial-title` (`itemprop=author`) |
| AC4 | `PREVIEW_TEXT` → `.testimonial-txt` (`itemprop=reviewBody`); пустой текст — карточка не рендерится |
| AC5 | Сортировка `SORT ASC`, затем `ID ASC` |
| AC6 | `style.css` этим циклом не менялся |

## Реализация (код, 2026-08-27)

- Шаблон: `local/templates/eklektika/components/bitrix/news.list/reviews/template.php` + `lang/ru/template.php`.
- Вызов: `otzyvy/index.php` — `bitrix:news.list`, `IBLOCK_ID=28`, шаблон `reviews` (хардкод карточек снят).
- Live-smoke не выполнен: локальный Bitrix docker не запущен (`127.0.0.1:8588` refused; контейнеров eklektika/bitrix нет). `https://new.eklektika.ru/otzyvy/` невалиден для незакоммиченного локального кода.

## Source of truth

- Этот ADR.
- Инициатива: `local/docs/tasks/2026-08-27-reviews-iblock/README.md`.
- Оркестрация: `local/docs/tasks/subtasks/{adr-index,progress-checklist,next-steps}.md`.
