# ADR: UPDATE_MANAGER — ИБ 24, BITRIX24_ID, IS_PERSONAL_MANAGER

## Статус

Принято (реализация в `eklektika.company` / `Manager.php`, согласование `getManagerID` в `eklektika.b24.usersync`).

## Решение

- Инфоблок **24**, поиск и сохранение внешнего id в свойстве **`BITRIX24_ID`** (fallback **`XML_ID`** для старых элементов).
- Превью: **`URL_B24` + PERSONAL_PHOTO** (или полный URL как есть); без константы — база **`https://bitrix.eklektika.ru`**.
- **`IS_PERSONAL_MANAGER` = false**: не создавать элемент; при наличии — обновить поля и **`ACTIVE=N`**.

## Риски

Старые карточки только в ИБ **53** по **XML_ID** не находятся в новом поиске — при необходимости миграция данных.
