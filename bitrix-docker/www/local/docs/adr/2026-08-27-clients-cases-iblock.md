# ADR: Страница кейсов `/o-kompanii/clients/` из инфоблока (IBLOCK_ID=29)

## Статус

**Accepted** (2026-08-27). Код реализован (CASE-01, аудит AC1–AC6 по коду PASS). Residual: **CASE-02 live-smoke**. Цикл не закрыт; статус Implemented не ставить до smoke. Lessons Learned — после CASE-02.

## Контекст

Страница `bitrix-docker/www/o-kompanii/clients/index.php` рендерит захардкоженную сетку логотипов/кейсов (~15 карточек) внутри `.clients` > `.row` > `.col-6.col-md-4.col-xl1-3` > `a.client[target=_blank]` > `img`. Ссылки в основном корневые `/{slug}/`; две — с `SITE_URL` и `.php`. Картинки из `/assets/images/...`.

Владелец: выводить сетку из инфоблока **IBLOCK_ID=29**, по тому же канону, что отзывы (ИБ 28).

Маппинг владельца:

| Слот | Источник |
|------|----------|
| Картинка | `PREVIEW_PICTURE` (картинка анонса) |
| Ссылка | символьный код `CODE` → `/{CODE}/` |

SEO / header / title «Кейсы» и intro `<p>Помогать другим...</p>` — **оставить** в `index.php`.

CSS уже есть в `local/templates/eklektika/assets/css/style.css` (~7582+): `.clients`, `.clients a`, `.clients a img`. **Не менять.**

`detail.php` той же папки — `bitrix:news.detail`, **IBLOCK_ID=9**, шаблон `portfolio-default-template`. urlrewrite `#^/o-kompanii/clients/([^/?]+)/?#` и `#^/clients/([^/?]+)/?#` ведут на этот detail. Это **другой** инфоблок и другой контур. **Не трогать** `detail.php`, urlrewrite, ИБ 9.

На главной уже есть `news.list` шаблон `our-clients` (слайдер логотипов, свойство LINK). **Не переиспользовать.** Новый шаблон: `news.list/clients`.

Не путать инфоблоки:

| ID | Роль | Публичный URL |
|----|------|---------------|
| 9 | деталь портфолио (`clients/detail.php`) | `/o-kompanii/clients/{code}/`, `/clients/{code}/` |
| 25 | кастомные страницы (`pages`) | `/pages/#CODE#/` |
| 26 | popular-блок на главной | карточки `/{CODE}/` |
| 27 | блок услуг на главной | карточки `/{CODE}/` |
| 28 | список отзывов | `/otzyvy/` |
| 29 | **этот** список кейсов | страница `/o-kompanii/clients/` (сетка на index) |

Канон href проекта (ИБ 26/27, отзывы 28): `'/' . CODE . '/'`. Не `DETAIL_PAGE_URL`. Пустой CODE — skip.

### Сортировка ИБ 28 и ИБ 29

Владелец уточнил: **в обоих списках** (отзывы 28 и кейсы 29) сортировка по полю сортировки, по возрастанию.

- ИБ 28: уже в коде `otzyvy/index.php` — `SORT_BY1=SORT` / `SORT_ORDER1=ASC`, `SORT_BY2=ID` / `SORT_ORDER2=ASC`. Отдельный PHP-трек **не нужен**.
- ИБ 29: то же в вызове этого цикла.

Канон проекта (ADR 2026-08-26-main-popular-block-iblock, 2026-08-26-main-service-block-iblock, 2026-08-27-reviews-iblock): `bitrix:news.list` + шаблон в `local/templates/eklektika/components/bitrix/news.list/<name>/`.

Константу в `SiteModuleConfig` для 29 **не вводить** (один call-site, страница вне модуля). Graphify в этом цикле не вызывать (`o-kompanii/clients/index.php` вне `local`, шаблон в `templates`).

## Решение

Канон: `bitrix:news.list`, как ИБ 26/27/28. Шаблон **не** переиспользовать `our-clients` (слайдер + LINK) и **не** `reviews` (другой DOM).

1. **Шаблон** `news.list/clients`:
   - путь: `local/templates/eklektika/components/bitrix/news.list/clients/template.php`
   - lang: `lang/ru/template.php` с `CT_BNL_ELEMENT_DELETE_CONFIRM` (как у `reviews` / `main-service-block`)
   - свой `style.css` у шаблона **не** создавать
   - `setFrameMode(true)`
2. **Обёртка.** Шаблон владеет `.clients` > `.row` и карточками. В `o-kompanii/clients/index.php` заменить только блок `<!-- BEGIN team block -->` … `<!-- END team-block -->` одним `IncludeComponent`. Intro `<p>`, SEO / `$GLOBALS['OG_TAGS']` / `SetPageProperty` / `SetTitle` / `AddChainItem` / header / footer / обёртка `.content` **не трогать**.
3. **Пустой результат.** Сначала отфильтровать элементы без CODE и без картинки анонса. Если после skip список пуст — ничего не выводить (без пустого `.clients`).
4. **Skip.**
   - пустой `CODE` (после `trim`) — элемент не рендерить;
   - пустой `PREVIEW_PICTURE` (нет `SRC`) — элемент не рендерить (карточка = картинка).
5. **Карточка** — тот же DOM, что сейчас:

| Узел | Значение |
|------|----------|
| колонка | `.col-6.col-md-4.col-xl1-3` |
| ссылка | `a.client`, `target="_blank"`, `href = /{CODE}/` |
| картинка | `<img src=PREVIEW_PICTURE.SRC alt=NAME>` (`htmlspecialcharsbx` на src/alt/href) |

`alt=NAME` лучше пустого `alt=""` хардкода; визуал тот же.

6. **href** собирать из `CODE`: `'/' . $code . '/'`. Не `DETAIL_PAGE_URL`, не `SITE_URL`, не `.php`. Два текущих хардкод-href с `SITE_URL`/`.php` после перехода станут каноном `/{CODE}/` — CODE в ИБ задаёт редактор.
7. **Edit-area** на колонке: `AddEditAction` / `AddDeleteAction` / `GetEditAreaId` как у `reviews`.
8. **Вызов** на `/o-kompanii/clients/` вместо хардкод-сетки:

| Параметр | Значение |
|----------|----------|
| Компонент / шаблон | `bitrix:news.list` / `clients` |
| `IBLOCK_ID` | `29` (литерал, без константы модуля) |
| `IBLOCK_TYPE` | `content` |
| `SORT_BY1` / `SORT_ORDER1` | `SORT` / `ASC` |
| `SORT_BY2` / `SORT_ORDER2` | `ID` / `ASC` (tie-breaker; первичный ключ — SORT) |
| `NEWS_COUNT` | `100` |
| pager / `SET_TITLE` / `SET_BROWSER_TITLE` / meta | `N` |
| кэш | `CACHE_TYPE=A`, `CACHE_TIME=36000000`, `CACHE_GROUPS=N` |
| `FIELD_CODE` | `CODE`, `PREVIEW_PICTURE`, `NAME` |
| `DISPLAY_PICTURE` | `Y` |
| `DISPLAY_NAME` | `N` (NAME только в `alt`) |
| `DISPLAY_PREVIEW_TEXT` | `N` |
| `PROPERTY_CODE` | пусто (LINK и прочие свойства не запрашивать) |

9. Фоллбэк на хардкод-карточки **не** оставлять.

## Consequences

- Редактор меняет сетку кейсов элементами ИБ 29 (SORT, CODE, PREVIEW_PICTURE, NAME), без правки PHP страницы.
- Элемент без CODE или без картинки анонса на витрине не появится.
- Пустой / неактивный ИБ 29 → на `/o-kompanii/clients/` останется intro, сетки не будет.
- `NEWS_COUNT=100` скрывает 101-й и далее; расширение — отдельный цикл (пагинация не в scope).
- Деталь `/o-kompanii/clients/{code}/` по-прежнему ИБ 9; сетка index на ИБ 29 на неё не завязана.

## Out of scope

- Правки `style.css` / `custom.css` и любой CSS шаблона.
- `o-kompanii/clients/detail.php`, ИБ 9, urlrewrite `#^/o-kompanii/clients/` и `#^/clients/`.
- Шаблон `news.list/our-clients` на главной.
- Константа `SiteModuleConfig` для ИБ 29.
- Пагинация.
- Graphify.
- Миграция 15 хардкод-карточек в ИБ кодом (контент — ответственность редактора/owner).
- PHP-правки страницы отзывов (сортировка ИБ 28 уже в коде).

## Риски

| Риск | Митигация |
|------|-----------|
| ИБ 29 пуст / элементы неактивны / без CODE или PREVIEW_PICTURE → пустая сетка | Owner проверяет контент до выкладки; шаблон не рисует пустую обёртку; intro остаётся |
| Путаница с ИБ 9 (`detail.php`) | Явный запрет трогать detail/urlrewrite/ИБ 9; сетка index = только ИБ 29 |
| Переиспользование `our-clients` (слайдер + LINK) | Новый шаблон `clients`; PROPERTY_CODE пустой |
| `SET_TITLE=Y` перезапишет «Кейсы» | Все title/meta компонента = `N` |
| Старые href с `SITE_URL`/`.php` | Канон `/{CODE}/`; CODE в элементе = живой корневой URL |
| Путаница ID 25/26/27/28/29 | Явный ID 29, страница `/o-kompanii/clients/` |

## Acceptance

| AC | Критерий |
|----|----------|
| AC1 | На `/o-kompanii/clients/` вызывается `news.list` ИБ 29, шаблон `clients`, вместо хардкод-сетки |
| AC2 | Классы `.clients`, `.row`, `.col-6.col-md-4.col-xl1-3`, `a.client[target=_blank]`, `img` как сейчас |
| AC3 | `href` карточки = `/{CODE}/` (не `DETAIL_PAGE_URL`) |
| AC4 | `img src` = `PREVIEW_PICTURE.SRC`, `alt` = `NAME`; пустой CODE или пустая картинка — карточка не рендерится |
| AC5 | Сортировка `SORT ASC`, затем `ID ASC` |
| AC6 | `style.css` этим циклом не менялся; intro `<p>` и SEO страницы на месте |

## Реализация (код, 2026-08-27)

- Шаблон: `local/templates/eklektika/components/bitrix/news.list/clients/template.php` + `lang/ru/template.php`.
- Вызов: `o-kompanii/clients/index.php` — `bitrix:news.list`, `IBLOCK_ID=29`, шаблон `clients` (хардкод-сетка снята; intro/SEO на месте). Сортировка `SORT ASC` + `ID ASC`. PHP отзывов не трогали.
- Live-smoke не выполнен: оркестратор перепроверил curl — `127.0.0.1:8588` connection refused; контейнеров eklektika/bitrix нет. `new.eklektika.ru` невалиден для локального кода.

## Source of truth

- Этот ADR.
- Инициатива: `local/docs/tasks/2026-08-27-clients-cases-iblock/README.md`.
- Оркестрация: `local/docs/tasks/subtasks/{adr-index,progress-checklist,next-steps}.md`.
- Сортировка отзывов: ADR `2026-08-27-reviews-iblock.md` (код ИБ 28 уже `SORT ASC`; PHP не трогать).
