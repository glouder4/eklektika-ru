# ADR: Mobile UX размерной карточки в каталоге

## Status
**Accepted / implemented** (2026-08-10)

Teamlead audit: MSC-01…04 DONE (code), AC PASS (code review). Rework не требуется. Commit не создавался.  
**Residual:** MSC-05 visual smoke — BLOCKED (стенд без деплоя, локальная БД пустая).

## Context
На `/catalog/promo_odezhda/` мобильная карточка размерного товара в блоке `.infos` плохо читается:

1. Таблица «Арт. / Раз. / Остаток» слишком узкая, колонки слипаются.
2. Nested scroll: `.product-item_articles-scroll` с `max-height: 112px` + `overflow-y: auto` внутри страничного скролла.
3. Конфликт данных: в свойствах «Размеры: L» (`RAZMERY`), в матрице — S/M/XL/XXL (`RAZMER_ODEZHDY`).
4. Зачёркнутая цена через `.price-throug::after` (диагональ 2px / −18°) выглядит грубо.
5. Скученность блока `.infos` на узком экране.

Источник UI: `catalog.item` / `main-catalog-item` (`card/template.php` → `include/card-offer-panel.php`).  
Логика матрицы: `local/php_interface/catalog_list_item_properties.php` → `catalogListBuildColorSizeArticleRows()`.

Предыдущий цикл `product-color-size-selector` (2026-08-08) — **карточка товара** (`main-product-page`), не список каталога. Не смешивать.

### Graphify
- Policy: только `bitrix-docker/www/local`, **исключая** `local/templates`.
- UI в templates — осознанное исключение (как в ADR 2026-08-08).
- Non-templates слой: `php_interface/catalog_list_item_properties.php`.

## Decision
1. **Presentation-first**: не менять серверную модель остатков/офферов; правим отображение и (точечно) фильтрацию display-properties.
2. **Единый источник размера на карточке**: при наличии size-matrix скрывать конфликтующие props (`RAZMERY` и аналоги) — матрица «Арт./Раз./Остаток» остаётся каноном.
3. **Mobile scroll**: на ≤767px убрать nested scroll у `.product-item_articles-scroll`; скролл только страницы.
4. **Таблица**: на mobile — читаемые колонки без слипания Арт./Раз./Остаток.
5. **Strike**: в catalog card — `text-decoration: line-through`, `::after` отключён (scoped `.product-item`).
6. **Плотность**: снизить скученность `.infos` на mobile для `has-size-matrix`.

## Consequences
- Мобильная карточка размерного товара читается без scroll-within-scroll и без противоречивых размеров (по code).
- Desktop/tablet: внутренний скролл матрицы сохранён ≥768px.
- Visual confirmation на live/стенде — residual до деплоя.

## Out of scope
- PDP size/color menu (`main-product-page`) — ADR 2026-08-08.
- Изменение расчёта остатков / цен / add2basket.
- Редизайн всей сетки каталога.

## Acceptance (cycle-level)

| AC | Критерий | Status |
|----|----------|--------|
| AC1 | Mobile: таблица читаема | **PASS (code)** — visual pending MSC-05 |
| AC2 | Нет scroll-within-scroll на mobile | **PASS (code)** — visual pending MSC-05 |
| AC3 | Нет конфликтующей строки «Размеры: …» при matrix | **PASS (code)** |
| AC4 | Strike аккуратный в catalog card | **PASS (code)** — visual pending MSC-05 |
| AC5 | Desktop ≥768px без регрессии | **PASS (code)** — visual pending MSC-05 |
| AC6 | Неразмерные карточки без негатива | **PASS (code)** — visual pending MSC-05 |

## Implemented files
1. `bitrix-docker/www/local/php_interface/catalog_list_item_properties.php`
2. `.../main-catalog-item/include/card-offer-panel.php`
3. `.../main-catalog-item/include/line-offer-panel.php`
4. `bitrix-docker/www/local/templates/eklektika/assets/css/custom.css`

## Lessons Learned
- Скрытие size-like props только при непустой matrix — безопаснее глобального hide `RAZMERY`.
- Nested scroll убивать строго в `@media (max-width: 767px)`, desktop hover-скролл оставлять.
- Strike лучше scoped `line-through` + `::after: none` в `.product-item`, не трогая глобальный `.price-throug`.
- Visual smoke нельзя закрыть на пустой локальной БД — цикл закрываем по code audit, smoke → residual owner.

## Residual (не блокируют Accepted)
- Деплой на стенд с данными каталога.
- Browser smoke `/catalog/promo_odezhda/` (mobile + desktop, размерная + неразмерная).
- Commit по явному запросу владельца.
