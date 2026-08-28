# Инициатива: кастомные страницы из инфоблока pages + ЧПУ

## Статус

**In progress** (2026-08-18): Wave A (ЧПУ + скелет) в коде. Нужна правка шаблонов URL в админке ИБ 25 и тестовый элемент.

## Цель

Создавать страницы сайта элементами инфоблока `pages` (ID=25) по одному шаблону, с ЧПУ `/pages/#ELEMENT_CODE#/`.

## Scope / Out of scope

**Scope:**

- Шаблоны URL инфоблока (админка)
- urlrewrite + `/pages/detail.php`
- Скелет шаблона `news.detail/custom-page`
- Константа `SiteModuleConfig::PAGES_IBLOCK_ID`

**Out of scope (этот цикл):**

- Финальная вёрстка шаблона
- Корневые URL без префикса `/pages/`
- Перенос старых лендингов

## Source of truth

- ADR: `local/docs/adr/2026-08-18-custom-pages-iblock-sef.md`
- Оркестрация: `tasks/subtasks/next-steps.md`

## Критерии готовности

- **AC1**: `/pages/{code}/` открывает элемент ИБ 25
- **AC2**: неизвестный code → 404
- **AC3**: голый `detail.php` → 301 `/pages/`
- **AC4**: шаблоны URL ИБ в админке — ЧПУ по CODE
- **AC5**: нет регрессии каталога/новостей

## Субзадачи

| Task ID | Содержание | Status |
|---------|------------|--------|
| PAGES-01 | ЧПУ urlrewrite + detail.php + конфиг | DONE (code) |
| PAGES-02 | Шаблоны URL ИБ 25 в админке | OWNER (админка) |
| PAGES-03 | Тестовый элемент + smoke | OPEN |
| PAGES-04 | Визуал шаблона `custom-page` | OPEN (вместе) |
