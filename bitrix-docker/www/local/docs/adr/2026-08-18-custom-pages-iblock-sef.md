# ADR: Кастомные страницы сайта из инфоблока `pages` (ЧПУ)

## Статус

Принято (2026-08-18). Wave A (ЧПУ + скелет шаблона) реализован в коде. Визуал шаблона — следующая итерация.

## Контекст

Новый инфоблок **pages**, `IBLOCK_ID=25`. Элементы создаются в админке и должны открываться как страницы сайта.

Исходные шаблоны ИБ (query-string):

- список: `#SITE_DIR#/pages/index.php?ID=#IBLOCK_ID#`
- раздел: `#SITE_DIR#/pages/list.php?SECTION_ID=#SECTION_ID#`
- деталь: `#SITE_DIR#/pages/detail.php?ID=#ELEMENT_ID#`

Так `GetDetailPageUrl()` отдаёт `/pages/detail.php?ID=123`, а не человеческий URL.

На сайте уже есть рабочие ЧПУ по `ELEMENT_CODE`: `/novosti/{code}/`, `/o-kompanii/clients/{code}/`. Корневые URL лендингов живут как сотни жёстких правил в `urlrewrite.php` — этот подход для нового ИБ не копируем.

`PAGE_SETTINGS_DEFAULT_IBLOCK_ID=60` — другой инфоблок (настройки карточки товара), не путать с pages.

## Решение

1. **Публичный URL v1:** `/pages/#ELEMENT_CODE#/` (не ID, не корень сайта).
2. **Шаблоны ИБ менять сразу** (иначе ссылки из компонентов останутся с `?ID=`):
   - страница ИБ: `#SITE_DIR#pages/`
   - раздел: `#SITE_DIR#pages/#SECTION_CODE#/`
   - деталь: `#SITE_DIR#pages/#ELEMENT_CODE#/`
3. **Правило rewrite** вынести в `local/php_interface/pages_urlrewrite.php` и подключать из `urlrewrite.php` (как brand/yml).
4. **Физическая страница:** `/pages/detail.php` → `bitrix:news.detail` / `custom-page`, `IBLOCK_ID` из `SiteModuleConfig::PAGES_IBLOCK_ID`.
5. **Canonical:** `$GLOBALS['CANONICAL_URL']` до header, потому что `REAL_FILE_PATH` указывает на `detail.php`.
6. **Корневые URL** `/#ELEMENT_CODE#/` — фаза 2 (коллизии с каталогом, брендами и старыми rewrite).

## Consequences

- Новый элемент с заполненным символьным кодом `dostavka` открывается как `/pages/dostavka/`.
- Без символьного кода ЧПУ не соберётся.
- `/pages/` — заглушка раздела, не публичный каталог страниц.
- Разделы ИБ как публичные листинги — не в этом wave.

## Out of scope

- Финальная вёрстка `custom-page`.
- Корневые алиасы и миграция старых лендингов `/landing/*.php`.
- Свойства ИБ кроме стандартных полей элемента.

## Acceptance

| AC | Критерий |
|----|----------|
| AC1 | `/pages/{code}/` отдаёт элемент ИБ 25, title/H1 из имени элемента |
| AC2 | Несуществующий code → 404 |
| AC3 | `/pages/detail.php` без code → 301 на `/pages/` |
| AC4 | `DETAIL_PAGE_URL` после смены шаблонов ИБ = `/pages/{code}/` |
| AC5 | Не ломает `/catalog/`, `/novosti/`, brand rewrite |
