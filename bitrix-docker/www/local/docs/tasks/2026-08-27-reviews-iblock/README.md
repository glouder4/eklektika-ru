# Инициатива: страница отзывов `/otzyvy/` из ИБ 28

## Статус

**In progress** (2026-08-27): ADR Accepted. Код REV-01 сделан и прошёл аудит (AC1–AC6 по коду PASS). Live-smoke REV-02 **blocked**. Цикл не закрыт.

## Цель

Заменить захардкоженные HTML-карточки в `bitrix-docker/www/otzyvy/index.php` на `bitrix:news.list` по инфоблоку **IBLOCK_ID=28**, сохранив текущую вёрстку `.testimonials` / schema.org `Review`.

## Scope / Out of scope

**Scope:**

- Шаблон `news.list/reviews` (+ lang/ru)
- Вызов компонента на `/otzyvy/` вместо обёртки+хардкод-карточек
- Маппинг: `NAME` → заголовок, `PREVIEW_TEXT` → текст

**Out of scope (этот цикл):**

- Любые правки `style.css` / `custom.css`
- Картинки отзывов
- Свойства ИБ и заполнение `.testimonial-company`
- Константа в `SiteModuleConfig` для 28
- Пагинация, форма добавления отзыва
- Graphify (`otzyvy/` вне `local`, шаблон в `templates`)
- SEO / header / footer страницы
- Заведение элементов ИБ 28 (owner)

## Source of truth

- ADR: `local/docs/adr/2026-08-27-reviews-iblock.md`
- Оркестрация: `local/docs/tasks/subtasks/next-steps.md`

## Критерии готовности

- **AC1**: на `/otzyvy/` — `news.list` ИБ 28, шаблон `reviews`, хардкод-карточек нет — код PASS / live blocked
- **AC2**: классы `.testimonials`, `.testimonial-item`, `.testimonial-title`, `.testimonial-company`, `.testimonial-txt` + schema.org `Review` — код PASS / live blocked
- **AC3**: `NAME` → `.testimonial-title` (`itemprop=author`) — код PASS / live blocked
- **AC4**: `PREVIEW_TEXT` → `.testimonial-txt` (`itemprop=reviewBody`); пустой текст skip — код PASS / live blocked
- **AC5**: `SORT ASC`, `ID ASC` — код PASS / live blocked
- **AC6**: `style.css` не менялся этим циклом — код PASS / live blocked

## Субзадачи

| Task ID | Содержание | Owner | Priority | Status |
|---------|------------|-------|----------|--------|
| REV-01 | Шаблон `news.list/reviews` + замена блока в `otzyvy/index.php` | dev | P0 | DONE (аудит 2026-08-27, AC1–AC6 по коду PASS) |
| REV-02 | Smoke AC1–AC6 на `/otzyvy/` | QA после REV-01 | P1 | BLOCKED (локальный Bitrix docker не запущен: канон README `127.0.0.1:8588` connection refused; контейнеров eklektika/bitrix нет. Порт `8590` — ошибочный из прошлых циклов, тоже refused. `https://new.eklektika.ru/otzyvy/` 200 невалиден: удалённый стенд без локальных незакоммиченных файлов) |

## Риски

- Пустой / неактивный ИБ 28 или элементы без `PREVIEW_TEXT` → страница без карточек.
- Не смешать ИБ 25/26/27/28.
- `SET_TITLE=Y` сломает заголовок страницы — в вызове все title/meta = `N`.
- HTML в анонсе: в body echo; в `meta name` — `strip_tags` + escape.

## Graphify

Не вызывать: страница вне `local`, шаблон в `templates`.
