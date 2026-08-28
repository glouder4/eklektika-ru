# ADR: Color + Size selectors on product card

## Status
**Accepted / implemented** (2026-08-08)

Teamlead audit: PCS-01/02/03 PASS, AC1–AC6 PASS (code), disk verified. Commit не выполнялся (по запросу владельца).

## Context
На карточке товара (`catalog.element` / `main-product-page`) блок `.color-menu` рендерит все `RELATED_OFFERS` родителя. Для одежды с заполненным `RAZMER_ODEZHDY` один и тот же `TSVET` порождает визуальные дубликаты (цвет × размер).

Пример: `/catalog/promo_odezhda/futbolki/futbolka_start/offer/88298/`

## Decision
1. **Data layer** (`result_modifier.php`): при сборке related offers читать `TSVET` и `RAZMER_ODEZHDY`.
2. **Color menu**: показывать уникальные цвета (`TSVET`). Для каждого цвета — один representative offer; при переходе предпочитать текущий размер, иначе первый доступный offer цвета.
3. **Size menu**: если у текущего цвета есть офферы с непустым `RAZMER_ODEZHDY` — квадратный переключатель размеров (ссылки на `DETAIL_URL` офферов текущего цвета).
4. **UI placement**: size-switcher рядом с color-menu в buy-form (и корректно в верхнем color-menu, если он остаётся).
5. Без SPA: навигация через `DETAIL_URL`, как у текущего color-menu.

## Consequences
- Дубли цветов исчезают на размерных товарах.
- На товарах без `RAZMER_ODEZHDY` size-menu скрыт; color-menu без дублей по `TSVET`.
- Graphify policy: UI в `local/templates` (вне graphify-scope) — осознанное исключение для шаблона карточки.

## Out of scope
- Список каталога / line swatches.
- AJAX-переключение оффера без reload.

## Lessons Learned
- Нормализация свойств оффера: display `VALUE` предпочтительнее сырого `ENUM_ID`; top-level ключи `TSVET` / `RAZMER_ODEZHDY` упрощают UI.
- Dual include (`template.php` + `buy-form.php`) для color/size — обязательный AC, иначе меню расходятся.
- Code-audit + disk verify достаточно для закрытия цикла; browser smoke и `php -l` остаются residual QA.

## Residual (не блокируют Accepted)
- Browser smoke на `/catalog/promo_odezhda/futbolki/futbolka_start/offer/...`
- `php -l` в docker на `result_modifier.php` + includes
- Commit по явному запросу владельца
