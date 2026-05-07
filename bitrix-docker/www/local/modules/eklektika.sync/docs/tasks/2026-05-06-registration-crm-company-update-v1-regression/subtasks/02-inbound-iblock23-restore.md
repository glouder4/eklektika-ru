# S2 — Inbound: восстановить `UPDATE_COMPANY` и запись в ИБ 23

Родительская задача: `../README.md`

## Статус

todo

## Объём работ

Обеспечить, что inbound‑цепочка CRM→site реально отрабатывает:

`B24 event (company) → n8n workflow → POST inbound_crm.php → InboundGateway → Company::updateCompanyElement → ИБ 23`.

## Риски / типовые причины

- **Секрет**: `InboundSecurity` отдаёт 403, если `inbound_secret` задан и токен не передан/не совпадает.
- **Content-Type**: если n8n шлёт JSON, но `Content-Type` не содержит `application/json`, `inbound_crm.php` не парсит body и payload развалится.
- **Envelope**: `ACTION` отсутствует или лежит не там; `FIELDS` не объект.
- **Ключ ID компании**: `OS_COMPANY_B24_ID` отсутствует (или не скаляр) → `updateCompanyElement` возвращает `false`.
- **Файл**: `LEGAN_ENTITY_FILE` приходит как base64/объект — код ожидает строку пути `/upload/...` или URL того же хоста, иначе игнорирует.

## Acceptance criteria (для S2)

- [ ] Inbound запрос с `ACTION=UPDATE_COMPANY` и `OS_COMPANY_B24_ID` приводит к `success=1` и `data.company_id>0`.
- [ ] В ИБ 23 свойства обновились (минимум `OS_COMPANY_NAME`/`LEGAN_ENTITY_NAME`, `OS_COMPANY_INN`/`LEGAN_ENTITY_INN`).
- [ ] Для файла: при `LEGAN_ENTITY_FILE="/upload/..."` в результате в ИБ 23 сохраняется fileId в `OS_REQUSITES_FILE` и зеркале `LEGAN_ENTITY_FILE`.

## Чеклист тестирования

### Smoke (ручной)

- [ ] Отправить тестовый POST на `.../local/modules/eklektika.sync/public/inbound_crm.php`:
  - `Content-Type: application/json`
  - header `X-Sync-Token: <inbound_secret>` (если секрет задан)
  - body (примерно):
    - `ACTION`: `UPDATE_COMPANY`
    - `OS_COMPANY_B24_ID`: `"70"` (пример)
    - `TITLE` или `OS_COMPANY_NAME`
    - `LEGAN_ENTITY_FILE`: `"/upload/..."` (для проверки скачивания файла)
- [ ] Проверить JSON ответ (200 + `success=1`).
- [ ] Проверить изменение свойств элемента ИБ 23.

### Диагностика (если не проходит)

- [ ] Если 403: сверить `inbound_secret` и передачу `X-Sync-Token`.
- [ ] Если `unknown_action`: проверить `ACTION` и envelope `{ACTION,FIELDS}`.
- [ ] Если `company_id=0`: проверить `OS_COMPANY_B24_ID`, и есть ли в ИБ 23 элемент с таким B24 ID.
- [ ] Если файл не сохранился: проверить `URL_B24` (нужен для скачивания) и форму `LEGAN_ENTITY_FILE` (должна быть строка `/upload/...` либо URL того же хоста).

