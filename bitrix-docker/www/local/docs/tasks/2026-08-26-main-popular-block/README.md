# Инициатива: блок популярных категорий на главной из ИБ 26

## Статус

**In progress** (2026-08-26): код POP-01/POP-02 прошёл аудит тимлида. POP-03 OWNER (контент ИБ 26). POP-04 smoke **blocked** (локальный docker недоступен; `new.eklektika.ru` ещё со старым include). Закрытие цикла (Lessons Learned) — после live-smoke.

## Цель

Заменить `bitrix:main.include` блока популярных категорий на главной на `bitrix:news.list` по элементам **IBLOCK_ID=26**, сохранив визуал.

## Scope / Out of scope

**Scope:**

- Шаблон `news.list/main-popular-block`
- Замена вызова в `index.php` (строки 150–159)
- Данные: CODE → href `/{CODE}/`, PREVIEW_PICTURE → фон, SORT ASC

**Out of scope (этот цикл):**

- CSS (`custom.css` и style.css шаблона)
- Удаление `/include/page_index/main-popular-block.php`
- ИБ 25 (`PAGES_IBLOCK_ID`), ЧПУ `/pages/`
- Константа в `SiteModuleConfig` для ID 26
- Graphify (файлы вне разрешённого scope)

## Source of truth

- ADR: `local/docs/adr/2026-08-26-main-popular-block-iblock.md`
- Оркестрация: `tasks/subtasks/next-steps.md`

## Критерии готовности

- **AC1**: на `/` рендерится news.list ИБ 26
- **AC2**: классы `.main-popular-block`, `.popular-cat-item`, `.p-cat-item-4`
- **AC3**: href = `/{CODE}/`
- **AC4**: фон = PREVIEW_PICTURE
- **AC5**: порядок SORT ASC
- **AC6**: custom.css без изменений

## Субзадачи

| Task ID | Содержание | Priority | Status |
|---------|------------|----------|--------|
| POP-01 | Шаблон `news.list/main-popular-block` | P0 | DONE |
| POP-02 | Замена include в `index.php` | P0 | DONE |
| POP-03 | 4 элемента ИБ 26 (админка) | P0 | OWNER |
| POP-04 | Smoke главной | P1 | BLOCKED |
