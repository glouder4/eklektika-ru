# Subtask: wire `json_naneseniya` into order property

Связано с задачей: `local/docs/tasks/2026-05-08-order-json-naneseniya/README.md`

## Статус

**done**

## Цель

Убрать debug-блок в `handleAjaxRequest` и обеспечить установку order property `json_naneseniya` значением, сформированным из корзины.

## Объём работ

- `local/components/online-service/order.form/class.php`
  - `handleAjaxRequest($request)`:
    - удалить блок `if ($code == "json_naneseniya") { pre(...); die(); }`
    - не читать `json_naneseniya` из `$request->getPost(...)` как пользовательское поле
    - до заполнения `$propertyCollection` сформировать серверное значение JSON
    - установить `$propItem->setValue($json)`
  - `processForm($request)`:
    - обеспечить идентичное поведение (если `processForm` используется в не-AJAX flow)

## Критерии готовности

- [ ] `json_naneseniya` больше не зависит от поля формы.
- [ ] `json_naneseniya` заполняется и в AJAX, и в не-AJAX сценарии (или явно зафиксировано/доказано, что один из путей не используется).
- [ ] В коде отсутствуют отладочные `pre()/die()` связанные с `json_naneseniya`.

## Чеклист проверки результата

- [ ] В админке заказа свойство `json_naneseniya` содержит валидный JSON.
- [ ] При одинаковых `PRODUCT_ID` данные агрегированы.
- [ ] Ошибки оформления заказа не возникают при пустом `NANESENIE`.

## Риски

- Дублирование логики создания заказа (`processForm` vs `handleAjaxRequest`) может привести к рассинхрону поведения; желательно выделить общий приватный метод для установки вычисляемых свойств.

