# Инициатива: `/registration/crm-company-update-v1` — восстановить ИБ 23 и base64 файла реквизитов

## Статус

**частично выполнено** (2026-05-06).

- **Outbound (S3)**: фикс в коде есть и считается завершённым по ревью.
- **Inbound (S1/S2)**: верификация по стендам/логам не выполнена в рамках этого task‑пакета; закрывать как «E2E done» нельзя без фактов.

## Цель

1) Восстановить/подтвердить, что inbound из CRM (`ACTION=UPDATE_COMPANY`) снова **обновляет элемент компании в ИБ 23**.  
2) Восстановить/подтвердить, что outbound `crm.company.update` снова **включает base64 файл реквизитов** (UF `LEGAN_ENTITY_FILE` / `REQUISITES_FILE_FIELD`) при наличии файла на сайте.

Важно: это два разных контура (см. ADR ниже) и должны тестироваться раздельно.

## Source of truth

- ADR: `modules/eklektika.sync/docs/adr/2026-05-06-registration-crm-company-update-v1-regression.md`
- Регистрация и named webhooks: `local/docs/business-processes/registration/README.md`
- Контракты registration webhooks: `local/docs/reference/registration-n8n-webhooks.md`
- Контракт inbound UPDATE_COMPANY: `local/docs/reference/n8n-inbound-update-company-from-b24.md`

## Критерии готовности (Acceptance Criteria)

- **AC1 (inbound / ИБ 23)**: POST на `local/modules/eklektika.sync/public/inbound_crm.php` с `ACTION=UPDATE_COMPANY` и валидным `OS_COMPANY_B24_ID` возвращает `{"success":1,"data":{"company_id":<int>}}`, и в ИБ 23 обновляются ожидаемые свойства (минимум `OS_COMPANY_NAME`/`LEGAN_ENTITY_NAME`, `OS_COMPANY_INN`/`LEGAN_ENTITY_INN`).
- **AC2 (inbound / файл)**: при `LEGAN_ENTITY_FILE="/upload/..."` (или URL того же хоста `URL_B24`) сайт скачивает файл, сохраняет в `b_file`, и в ИБ 23 в свойствах `OS_REQUSITES_FILE` и `LEGAN_ENTITY_FILE` стоит **ID файла > 0**.
- **AC3 (outbound / base64)**: при наличии fileId в `OS_REQUSITES_FILE` (или fallback `LEGAN_ENTITY_FILE`) вызов `crm.company.update` формируется с UF файла в формате `fileData: [filename, base64]` (см. `Company::buildCrmFileFieldFileDataFromBitrixFileId`), а в `SyncTrace` виден шаг `company.b24.requisites_file.trace` с `included=true`.
- **AC4 (контракты n8n)**: `registration/crm-company-update-v1` отвечает JSON envelope `{ success: 1, result: ... }` (или single-element array envelope), не ломая PHP‑транспорт.

## Реализация (что сделано в коде)

- `local/modules/eklektika.company/lib/Company.php`
  - вход `OS_REQUSITES_FILE` в формате B24 `fileData` (base64) сохраняется в `b_file`, дальше в ИБ 23 хранится как **fileId** (устраняет падение/невалидное значение при записи в ИБ).
  - исходящий `crm.company.update`: если `OS_REQUSITES_FILE` уже `fileData`, передаём как есть; иначе `fileId` извлекается устойчиво (int/string/`VALUE`/массив с `ID`) и конвертируется в `fileData + base64`.

Примечание: это описание подтверждено **по текущему коду**; результаты фактических прогонов (n8n execution / обновления ИБ 23 на стенде) должны быть приложены отдельно в S1/S2.

## Тест‑план (что проверять и где искать логи)

### 1) Inbound UPDATE_COMPANY (CRM → n8n → site)

- **n8n**:
  - В execution inbound‑workflow проверить: URL запроса на сайт, `Content-Type`, headers (`X-Sync-Token` если используется), body.
  - Body минимум содержит: `ACTION="UPDATE_COMPANY"`, `OS_COMPANY_B24_ID`, и (для файла) `LEGAN_ENTITY_FILE` как `/upload/...` или URL.
- **Сайт**:
  - HTTP ответ должен быть `200` и `success=1`.
  - При отладке включить `sync_debug=true` и смотреть `debug_trace` в ответе (его добавляет `InboundGateway`).
  - При ошибке `403` ожидать `{"success":0,"error":"sync_forbidden"}` (секрет).

### 2) Outbound crm.company.update (site → n8n → CRM)

- **Сайт**:
  - До вызова проверить, что в ИБ 23 у компании fileId > 0 в `OS_REQUSITES_FILE` (или `LEGAN_ENTITY_FILE`).
  - В `SyncTrace` искать `company.b24.requisites_file.trace` (причины `included`/`missing_file_id`).
- **n8n**:
  - В execution webhook `registration/crm-company-update-v1` убедиться, что в `PARAMS` пришёл UF файла с `fileData + base64`.
  - Проверить, что `Respond to Webhook` возвращает `{ success, result }` и не теряет тело/тип.

## Субзадачи

| Файл | Содержание |
|------|------------|
| [subtasks/01-evidence-collection.md](subtasks/01-evidence-collection.md) | Какие факты собрать из n8n/сайта, чтобы сузить причину |
| [subtasks/02-inbound-iblock23-restore.md](subtasks/02-inbound-iblock23-restore.md) | Восстановить/подтвердить inbound UPDATE_COMPANY и запись в ИБ 23 |
| [subtasks/03-outbound-base64-file-restore.md](subtasks/03-outbound-base64-file-restore.md) | Восстановить/подтвердить base64 файл в `crm.company.update` |

## Риски / follow-ups

- **E2E‑неопределённость inbound (ИБ 23)**: без фактов из n8n execution нельзя утверждать, что `UPDATE_COMPANY` снова обновляет ИБ 23.
- **Хранилище файлов**: `buildCrmFileFieldFileDataFromBitrixFileId()` возвращает `null`, если файл физически не читается по пути (расхождение `b_file` ↔ FS, docroot, права, монтирование).
- **Контракты webhooks n8n**: если ответ `registration/crm-company-update-v1` не в envelope `{success,result}` (или array‑envelope), транспорт может трактовать это как ошибку.
- **Tooling**: Graphify preflight сейчас блокирован ограничением MCP args; аудит артефактов через Graphify отложен.

## Next steps for Team Lead

- Собрать факты по S1: execution inbound workflow и `registration/crm-company-update-v1` (request/response).
- Выполнить S2 smoke‑проверку inbound `UPDATE_COMPANY` и убедиться, что ИБ 23 обновляется и файл сохраняется как fileId.
- Для S3: подтвердить на реальном прогоне `company.b24.requisites_file.trace` с `included=true` и наличие UF файла в `PARAMS` вебхука.

