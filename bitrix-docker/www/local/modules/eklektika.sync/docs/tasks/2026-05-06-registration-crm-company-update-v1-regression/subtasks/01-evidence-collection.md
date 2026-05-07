# S1 — Evidence collection: где именно сломалось

Родительская задача: `../README.md`

## Статус

todo

## Цель

Собрать минимальный набор фактов, который однозначно разделит проблему на:

- inbound CRM→site (ИБ 23 не обновляется), и/или
- outbound site→CRM (base64 файла не уходит).

## Что собрать (обязательно)

### A) Inbound UPDATE_COMPANY (CRM → n8n → site)

- **URL запроса** на сайт (должен быть `.../local/modules/eklektika.sync/public/inbound_crm.php`).
- **HTTP code** ответа сайта и тело ответа.
- **Headers**:
  - `Content-Type` (ожидается `application/json` если body JSON),
  - `X-Sync-Token` (если на сайте задан `inbound_secret`; иначе 403).
- **Body** (как отправлено n8n):
  - `ACTION` = `UPDATE_COMPANY`,
  - `OS_COMPANY_B24_ID` — тип и значение,
  - `FIELDS` envelope (если используется) и его тип,
  - для файла: `LEGAN_ENTITY_FILE` (строка) или `OS_REQUSITES_FILE` (объект/ID) — типы и значения (без утечки содержимого; достаточно длины/формы).

### B) Outbound `registration/crm-company-update-v1` (site → n8n → CRM)

- **Вход webhook n8n**: JSON, который пришёл в `registration/crm-company-update-v1`:
  - `METHOD` должен быть `crm.company.update`,
  - `PARAMS` → наличие UF файла и его форма.
- **Ответ webhook n8n**: body из `Respond to Webhook` (должно быть `{ success, result }`).

### C) Состояние на сайте (ИБ 23 и файл)

Для конкретной тестовой компании:

- Значение свойств ИБ 23:
  - `OS_COMPANY_B24_ID`,
  - `OS_REQUSITES_FILE` (ожидается int fileId),
  - `LEGAN_ENTITY_FILE` (ожидается int fileId).
- Если base64 не уходит: подтвердить, что fileId существует в `b_file` и физический файл читается (проблемы docroot/FS проявляются как невозможность сформировать base64).

## Ожидаемые диагнозы по признакам

- **Inbound 403 + `sync_forbidden`** → секрет `inbound_secret` не совпадает / не передан `X-Sync-Token`.
- **Inbound 200, но `success=0` и `unknown_action`** → не пришёл `ACTION` / envelope неверный.
- **Inbound 200, `success=0` и `company_id=0`** → `OS_COMPANY_B24_ID` пуст/не распарсился, или компания не найдена/не создана (смотреть `SyncTrace`).
- **Outbound webhook вызывается, но UF файла нет** → на сайте `OS_REQUSITES_FILE`/`LEGAN_ENTITY_FILE` пусты или не ID.
- **UF файла есть, но CRM игнорит/ошибка** → n8n не прокидывает `fileData+base64` корректно, или ответ не по envelope.

## Чеклист проверки результата

- [ ] Есть execution‑пруф inbound с request+response.
- [ ] Есть execution‑пруф `crm-company-update-v1` с request+response.
- [ ] Для тестовой компании известны значения `OS_REQUSITES_FILE`/`LEGAN_ENTITY_FILE` (ID/0).

