# Subtask: build JSON from basket (`NANESENIE`)

Связано с задачей: `local/docs/tasks/2026-05-08-order-json-naneseniya/README.md`

## Статус

**done**

## Цель

Определить и реализовать способ сборки JSON из корзины по каждому `PRODUCT_ID`, извлекая множественное свойство корзины `NANESENIE`.

## Входы

- Корзина заказа: `$this->arResult['_BASKET']` (экземпляр `\Bitrix\Sale\Basket`)
- Basket item properties: `\Bitrix\Sale\BasketItem::getPropertyCollection()`

## Выходы

- Строка JSON для order property `json_naneseniya` (см. ADR).

## Требования (Definition)

- **Формат**: JSON-массив объектов `{id: int, NANESENIE: {name: string, price: float}[]}`.
- **Агрегация**: по ключу `PRODUCT_ID`, мердж по всем строкам корзины.
- **Нормализация**: `name` trim/filter empty; `price` → float; дедуп по `name` (коллизии: последняя цена побеждает); порядок стабильный по первому появлению `name`.
- **Пустые значения**: `NANESENIE: []`, общий результат при отсутствии данных — `"[]"`.
- **JSON encode**: `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.

## Чеклист проверки результата

- [ ] При 1 товаре с 2 нанесениями JSON содержит 1 объект и 2 элемента в `NANESENIE` (каждый — `{name, price}`).
- [ ] При 2 строках корзины с одинаковым `PRODUCT_ID` значения `NANESENIE` объединяются и не дублируются.
- [ ] Пустые/пробельные значения не попадают в массив.

## Риски

- Возможные вариации представления `NANESENIE` в property values (скаляр/массив/структура) — нужно устойчивое извлечение к строкам.

