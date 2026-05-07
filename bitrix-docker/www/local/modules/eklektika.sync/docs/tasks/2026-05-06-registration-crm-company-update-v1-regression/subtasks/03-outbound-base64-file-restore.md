# S3 — Outbound: восстановить base64 файл в `crm.company.update`

Родительская задача: `../README.md`

## Статус

done

## Результат (факт выполнения)

- **Код**: реализовано в `local/modules/eklektika.company/lib/Company.php` (нормализация `OS_REQUSITES_FILE` и устойчивое формирование UF файла для `crm.company.update`).
- **E2E / стенды**: не подтверждено в этом документе (нужно приложить execution n8n и/или `SyncTrace` из реального прогона).

## Объём работ

Обеспечить, что при обновлении компании в CRM через named webhook `registration/crm-company-update-v1` сайт передаёт UF файла реквизитов в формате `fileData + Base64`, а n8n возвращает корректный envelope.

Ключевая деталь: сайт **не умеет** отправлять URL файла в `crm.company.update` как замену base64. UF типа «файл» требует `fileData` + base64.

## Зависимости

- На сайте должен быть сохранён файл как `b_file` и в ИБ 23 должен быть **fileId > 0** в `OS_REQUSITES_FILE` (или fallback `LEGAN_ENTITY_FILE`).

## Acceptance criteria (для S3)

- [ ] В вызове `crm.company.update` в `PARAMS` присутствует поле UF файла (контракт `CompanyB24Config::REQUISITES_FILE_FIELD`) со структурой:
  - `{ fileData: [ "<имя>", "<base64>" ] }`
- [ ] В `SyncTrace` есть запись `company.b24.requisites_file.trace` с `included=true` и `reason` одним из:
  - `passthrough_fileData` (если UF уже был в `fileData`),
  - `fileId_to_fileData_base64` (если UF восстановлен из fileId).
- [ ] n8n `Respond to Webhook` возвращает `{ "success": 1, "result": ... }` (или single-element array envelope), и PHP‑сторона не трактует это как ошибку.

## Тест‑план

### 1) Подготовка данных

- [ ] Убедиться, что в ИБ 23 у тестовой компании заполнено `OS_REQUSITES_FILE` (int > 0) или `LEGAN_ENTITY_FILE` (int > 0).

### 2) Проверка запроса в n8n

- [ ] Открыть execution webhook `registration/crm-company-update-v1`.
- [ ] Проверить входной JSON:
  - `METHOD == "crm.company.update"`
  - `PARAMS` содержит UF файла с `fileData` и base64 строкой (без полного дампа, достаточно длины/первых символов и имени файла).

### 3) Проверка ответа

- [ ] `Respond to Webhook` отдаёт envelope (см. `local/docs/reference/registration-n8n-webhooks.md`).
- [ ] Если ответ “сырой” Bitrix REST, убедиться, что он завернут в `{success,result}`.

## Диагностика (если base64 отсутствует)

- Если UF файла отсутствует:
  - [ ] Проверить, что fileId действительно >0 (не пустая строка / не массив / не `0`).
  - [ ] Проверить, что файл физически читается на диске (иначе `buildCrmFileFieldFileDataFromBitrixFileId` вернёт `null`).
- Если UF файла есть, но CRM возвращает ошибку:
  - [ ] Проверить, что это именно `fileData` и base64 корректный (не URL).
  - [ ] Проверить права входящего вебхука B24 (префикс `b24_rest_prefix`) на обновление UF поля.

