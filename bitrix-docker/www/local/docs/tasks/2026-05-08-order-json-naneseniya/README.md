# Инициатива: заполнять `json_naneseniya` из корзины при оформлении заказа

## Статус

**in progress** (2026-05-08): код внедрён, ожидается ручная проверка по тест-плану.

## Цель

При создании заказа формировать JSON по товарам из текущей корзины в формате `{id: PRODUCT_ID, NANESENIE: [{name, price}, ...]}` и записывать в order property с кодом `json_naneseniya`.

## Scope / Out of scope

- **Scope**: только компонент оформления заказа `local/components/online-service/order.form/class.php` (серверная логика формирования значения свойства заказа).
- **Out of scope**: изменения интеграций (n8n webhooks/CRM), изменения схемы инфоблоков, изменения структуры корзины/добавления свойств.

## Source of truth

- ADR: `local/docs/adr/2026-05-08-order-json-naneseniya.md`
- Код компонента: `local/components/online-service/order.form/class.php`

## Критерии готовности (Acceptance Criteria)

- **AC1 (источник данных)**: значение `json_naneseniya` формируется сервером из basket item properties `NANESENIE`, а не из поля формы.
- **AC2 (формат)**: в заказ сохраняется валидный JSON-массив объектов `{id: <int>, NANESENIE: <{name: string, price: float}[]>}`.
- **AC3 (агрегация)**: одинаковые `PRODUCT_ID` агрегируются в одну запись; `NANESENIE` мерджится и дедуплицируется.
- **AC4 (пустые значения)**: если для товара нет нанесения, `NANESENIE` — пустой массив; если нанесений нет везде — `"[]"`.
- **AC5 (удаление дебага)**: в `handleAjaxRequest` отсутствуют `pre(...)`/`die()` для `json_naneseniya`.

## Тест-план (ручной)

См. чеклисты в подзадачах, итоговый прогон — в `subtasks/03-manual-test-plan.md`.

## Субзадачи

| Файл | Содержание |
|------|------------|
| [subtasks/01-build-json-from-basket.md](subtasks/01-build-json-from-basket.md) | Спецификация и реализация сборки JSON из корзины |
| [subtasks/02-wire-into-order-property.md](subtasks/02-wire-into-order-property.md) | Подстановка значения в `json_naneseniya` при создании заказа |
| [subtasks/03-manual-test-plan.md](subtasks/03-manual-test-plan.md) | Ручной тест-план для проверки в UI/админке |

## Риски / Notes

- В `handleAjaxRequest` и `processForm` сейчас есть дублирование логики создания заказа; нужно обеспечить одинаковое поведение для `json_naneseniya` в обоих путях (либо явно зафиксировать, что используется только один путь).

## Next steps for Team Lead

См. блок в конце этого чата (в сообщении техлида): dev wave + аудит.

