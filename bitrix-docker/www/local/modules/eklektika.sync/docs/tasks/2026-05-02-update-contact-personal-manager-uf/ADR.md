# ADR: UPDATE_CONTACT — UF_PERSONAL_MANAGER_* → элемент ИБ по BITRIX24_ID

## Статус

Принято (реализация в `eklektika.b24.usersync`).

## Контекст

Входящий JSON от n8n/CRM для `ACTION=UPDATE_CONTACT` содержит поля пользователя сайта **`UF_PERSONAL_MANAGER_1`** и **`UF_PERSONAL_MANAGER_2`**. Значения приходят как **идентификаторы из CRM**, совпадающие со значением свойства **`BITRIX24_ID`** элементов инфоблока **24** (карточки менеджера). Без преобразования в локальный **ID элемента ИБ** привязка на сайте будет неверной.

Отдельно в цепочках выгрузки контакт может передаваться как **`CONTACT_ID`**, а поле **`ID`** — служебный ключ строки; для резолва пользователя сайта нужен именно CRM contact id.

## Решение

1. Константы ИБ и свойства: `UserSyncConfig::MANAGER_CARD_IBLOCK_ID`, `MANAGER_CARD_BITRIX24_PROPERTY_CODE`, коды UF персональных менеджеров.
2. После `CrmInboundUfMap::prepareUserUpdatePayload`: замена значений `UF_PERSONAL_MANAGER_1`/`_2` на ID элемента ИБ через `CIBlockElement::GetList` по `PROPERTY_BITRIX24_ID`.
3. Если элемент не найден — ключ не передаётся в `CUser::Update`; при включённом SyncTrace — событие `personal_manager_card_not_found`.
4. Приоритет **`CONTACT_ID`** над **`ID`** для заполнения `B24_ID` до поиска пользователя; **`CONTACT_ID`** удаляется перед обновлением.

## Последствия

- При смене номера ИБ или кода свойства править только `UserSyncConfig`.
- Для смоука нужны элементы ИБ 24 с заполненным `BITRIX24_ID`, соответствующие тестовым значениям из CRM.
