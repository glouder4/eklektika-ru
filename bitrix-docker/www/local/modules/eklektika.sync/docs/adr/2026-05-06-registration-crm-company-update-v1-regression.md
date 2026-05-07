# ADR: регресс `/registration/crm-company-update-v1` — перестали обновляться ИБ 23 и base64 `LEGAN_ENTITY_FILE`

## Статус

Решено (2026-05-06). Регресс устранён на стороне сайта; для полной E2E верификации по стендам по-прежнему нужны факты из execution n8n (см. тест-план в task).

## Контекст / симптомы

Наблюдается регресс в инициативе `/registration/crm-company-update-v1`:

- **Сайт перестал обновлять элемент компании в ИБ 23** по входящему апдейту из CRM (`ACTION=UPDATE_COMPANY` → `Company::updateCompanyElement`).
- **Сайт перестал отправлять base64 файл реквизитов в CRM** в поле UF `LEGAN_ENTITY_FILE` (в текущем коде исходящее имя ключа — `CompanyB24Config::REQUISITES_FILE_FIELD`, см. сборку `fileData + Base64`).

В проекте есть два разных контура, которые часто путают как «одну интеграцию»:

1) **Inbound CRM → site**: `local/modules/eklektika.sync/public/inbound_crm.php` → `InboundGateway::dispatch()` → `Company::updateCompanyElement()` → запись в **ИБ 23**.  
   Документ: `local/docs/reference/n8n-inbound-update-company-from-b24.md`.

2) **Outbound site → n8n → CRM**: регистрационный оркестратор / gateway вызывает `crm.company.update` через **named webhook** `registration_webhook_crm_company_update_url` (path `registration/crm-company-update-v1`).  
   Документ: `local/docs/reference/registration-n8n-webhooks.md`.

Симптомы «ИБ не обновляется» и «base64 не уходит» могут происходить независимо (разные входы, разные контракты).

## Стабильные точки в коде (что “должно быть”)

### Inbound UPDATE_COMPANY → ИБ 23

- Точка входа: `local/modules/eklektika.sync/public/inbound_crm.php`
  - при `Content-Type: application/json` читает `php://input` и делает merge с `$_GET` (query-параметры перекрывают JSON).
  - вызывает `InboundSecurity::assertInboundAllowed()` (секрет `inbound_secret`).
- Роутинг: `local/modules/eklektika.sync/lib/from-crm/InboundGateway.php`
  - поддерживает envelopes `[{...}]` и `{ ACTION, FIELDS }`.
  - для `ACTION=UPDATE_COMPANY` вызывает `Company::updateCompanyElement($request)`.
- `Company::updateCompanyElement` откажет без **`OS_COMPANY_B24_ID`** (возвращает `false`), затем:
  - ищет элемент компании по B24 ID (`getCompanyByB24ID`) и обновляет свойства, включая файл реквизитов через `OS_REQUSITES_FILE`/зеркало `LEGAN_ENTITY_FILE`;
  - принимает вход `LEGAN_ENTITY_FILE` как **путь `/upload/...`** или URL того же хоста, что `URL_B24`, и “перенаправляет” его в `OS_REQUSITES_FILE=['SRC'=>...]` для скачивания.

### Outbound crm.company.update → base64 файла

Для исходящего `crm.company.update` (через n8n) base64 формируется только если на сайте есть ID файла в `b_file`:

- Источник файла: `OS_REQUSITES_FILE` (приоритет) или `LEGAN_ENTITY_FILE` (fallback), оба ожидаются как **ID файла сайта**.
- Payload для CRM: `buildCrmFileFieldFileDataFromBitrixFileId($fileId)`:
  - читает файл с диска по пути из `CFile::GetPath()` и делает `base64_encode(binary)`;
  - если файл не найден/не читается/пустой — возвращает `null`, и поле **не включается** в `crm.company.update`.

## Гипотезы причин (с приоритетами)

### H1. Перепутаны контуры: ожидали inbound‑обновление ИБ от webhook `/registration/crm-company-update-v1`

Вероятность: высокая.  
Пояснение: `crm-company-update-v1` — это outbound named webhook (site→n8n) для `crm.company.update`. Он **не** пишет в ИБ 23.  
Если “стоп обновления ИБ” наблюдается одновременно со “стоп base64”, это может быть просто совпадение двух регрессий или неправильная точка наблюдения.

**Что проверить**:
- Есть ли в n8n workflow Bitrix24‑событие “company updated” → POST на `inbound_crm.php` с `ACTION=UPDATE_COMPANY`.
- Есть ли последние вызовы `inbound_crm.php` в web‑логах/SyncTrace, а не только `registration/crm-company-update-v1`.

### H2. Inbound перестал попадать в `updateCompanyElement`: секрет/заголовок/контент‑тайп/конверт

Вероятность: высокая.

Триггеры:
- `inbound_secret` поменялся на сайте или в n8n, и `InboundSecurity` начинает отдавать 403.
- n8n начал отправлять JSON, но не `application/json` (например `text/plain`) → `inbound_crm.php` не парсит body и остаётся `$_REQUEST`, а `ACTION`/`FIELDS` могут не прийти.
- Изменили envelope: `ACTION` переехал внутрь `FIELDS` (или наоборот), или `FIELDS` перестал быть объектом.

**Доказательства собрать**:
- Ответ сайта на inbound (HTTP code + body), особенно при ошибке: `{"success":0,"error":"sync_forbidden"}` или `unknown_action`.
- Лог n8n “last execution” для inbound: что реально отправлено (headers + body).
- Включить `sync_debug=true` на стенде и посмотреть `debug_trace` в ответе inbound (он добавляется `InboundGateway::withDebugTrace`).

### H3. `OS_COMPANY_B24_ID` отсутствует/пустой/не в том ключе

Вероятность: высокая.

`Company::updateCompanyElement` возвращает `false`, если `OS_COMPANY_B24_ID` после нормализации пуст. Это “молчаливый” стоппер обновления ИБ.

Причины:
- n8n перестал пробрасывать `OS_COMPANY_B24_ID` в тело запроса (пришёл только `ID`/`COMPANY_ID`/`ENTITY_ID`).
- Пришло значение в виде массива/объекта не того вида, и нормализация отбрасывает.

**Доказательства**:
- payload inbound на сайт: наличие `OS_COMPANY_B24_ID`, его тип и пример значения.
- `SyncTrace` шаг `Company::updateCompanyElement reject empty OS_COMPANY_B24_ID`.

### H4. Base64 перестал уходить, потому что на сайте нет fileId (или файл не читается)

Вероятность: высокая.

Причины:
- На сайте перестали сохранять `OS_REQUSITES_FILE` (или сохраняют только “витринный” `LEGAN_ENTITY_FILE` как не‑ID).
- Приходит `OS_REQUISITES_FILE` вместо `OS_REQUSITES_FILE` — алиас есть, но если тип/форма поменялись (например base64 строка вместо id), `normalizeOsRequisitesFileInputToStoredFileId` вернёт `null`, и файл не попадёт в CRM.
- Права/путь: `CFile::GetPath($fileId)` возвращает путь, но файл физически отсутствует (удалён, не смонтирован, проблемы docroot) → `buildCrmFileFieldFileDataFromBitrixFileId` возвращает `null`.

**Доказательства**:
- На карточке компании в ИБ 23: текущее значение `OS_REQUSITES_FILE` и `LEGAN_ENTITY_FILE` (должны быть числа >0).
- `SyncTrace` шаг `company.b24.requisites_file.trace` (в коде есть причины `included` / `missing_file_id`).
- Ошибка чтения файла: косвенно — `included=false` и `file_id`=0 или `buildCrmFileField...` вернул `null`.

## Итог: подтверждённая причина и фикс

### RC1 (подтверждено кодом): `OS_REQUSITES_FILE` приходил как B24 `fileData` (base64), а ИБ 23 ожидал fileId

- В ветках регистрации/синка `OS_REQUSITES_FILE` мог приходить как массив `['fileData' => [name, base64]]`.
- До фикса нормализация `OS_REQUSITES_FILE` не умела сохранять `fileData` в `b_file`, поэтому значение оставалось массивом и могло ломать запись в ИБ 23 (и/или приводить к отсутствию fileId для последующей отправки base64).
- Фикс: `Company::resolveOsRequisitesFileParamForUpdate()` теперь умеет:
  - `fileData (base64) → fileId` через `base64_decode` + `CFile::SaveFile`,
  - `$_FILES`-массив → `fileId`,
  - логировать выбор ветки через `syncTrace`.

### RC2 (подтверждено в профиле): при outbound сборке файла каст `array → int` давал `1`

- В профиле компании `getCompany()` мог возвращать `OS_REQUSITES_FILE`/`LEGAN_ENTITY_FILE` как массив с `ID/SRC/...`.
- Прежний код делал `(int)$array` и получал `1`, из-за чего base64 не собирался.
- Фикс: для исходящего `crm.company.update` извлечение fileId сделано через `normalizeOsRequisitesFileInputToStoredFileId()`.

### H5. Контракт n8n “crm-company-update-v1” изменился и сайт не делает `crm.company.update` (или считает шаг неуспешным)

Вероятность: средняя.

Причины:
- Ответ `Respond to Webhook` в n8n перестал содержать envelope `{ success, result }` и `N8nCrmGateway` / транспорт воспринимают это как ошибку на критичном шаге.
- Поменялись параметры для `crm.company.update`: UF файла требует строго `fileData + base64` (URL не работает), но workflow пытается подставлять URL.

**Доказательства**:
- Лог последнего execution в n8n для `registration/crm-company-update-v1`: входной JSON `{ METHOD:"crm.company.update", PARAMS:{...} }` и выходной JSON `{ success, result }`.
- Ошибки в `SyncInboundLog`/логах регистрации на сайте (если sync path).

## Предлагаемая стратегия фикса (без догадок)

1) **Развести наблюдение на 2 контура**: отдельно проверить inbound `UPDATE_COMPANY` (ИБ 23), отдельно — outbound `crm.company.update` (base64).
2) **Собрать факты**:
   - для inbound: HTTP код/ответ/headers, наличие `ACTION`, наличие `OS_COMPANY_B24_ID`, наличие `LEGAN_ENTITY_FILE` или `OS_REQUSITES_FILE`, `Content-Type`;
   - для outbound: наличие на сайте fileId в `OS_REQUSITES_FILE`, успешность `buildCrmFileFieldFileDataFromBitrixFileId`, что реально ушло в n8n в `PARAMS`.
3) **Правка по результату**:
   - если inbound не доходит — исправить n8n (headers/envelope/secret), либо расширить PHP парсер (accept `text/plain` JSON) и добавить явную диагностику `missing_action`/`missing_b24_id`;
   - если fileId отсутствует — восстановить сохранение файла в ИБ 23 (inbound) или маппинг из регистрации/UF, затем проверить, что outbound собирает base64;
   - если файл читается плохо — проверить docroot/права/пути `CFile::GetPath`, устранить удаление/несоответствие storage.

## Ограничения / блокеры

- **Graphify preflight блокирован (tooling issue)**: интерфейс MCP Graphify требует аргументы (например `question`), но текущая связка `CallMcpTool` не позволяет передать args в нужном формате; Graphify‑аудит по scope `.graphify-scope-local/` временно недоступен. Это не блокер прод‑фикса, но блокирует аудит/кластеризацию артефактов через Graphify.

## Итог: подтверждённая причина и исправление

### Root cause (подтверждено по коду)

Регресс «base64 файл реквизитов не уходит в `crm.company.update`» происходил из‑за того, что на выходном контуре `site → n8n → CRM` поле файла реквизитов приходило **не как чистый site fileId (int)**, а в одной из «не‑интовых» форм:

- `OS_REQUSITES_FILE` как массив (например Bitrix‑массив файла / UF дерево / `['VALUE'=>…]`);
- `OS_REQUSITES_FILE` как payload Bitrix24 UF файла `fileData` (`{ fileData: [name, base64] }`).

При таких формах прежняя логика извлечения `fileId` легко давала `0` и, как следствие, `buildCrmFileFieldFileDataFromBitrixFileId($fileId)` возвращал `null`, а UF файла **не включался** в `crm.company.update`.

Inbound‑контур «CRM → site → ИБ 23» в рамках этой инициативы **не подтверждён как причина** и требует отдельной проверки по фактическим входящим payload/логам.

### Fix summary (подтверждено по коду)

Исправление реализовано в `local/modules/eklektika.company/lib/Company.php`:

- **Нормализация входного `OS_REQUSITES_FILE` в fileId сайта**: `resolveOsRequisitesFileParamForUpdate()` теперь умеет:
  - сохранять `fileData` (base64) в `b_file` и подменять на `fileId` (см. `trySaveOsRequisitesFileFromB24FileDataPayload()`),
  - принимать «битриксовый» file array (например `$_FILES`) и сохранять в `b_file` (см. `trySaveOsRequisitesFileFromBitrixFileArray()`),
  - принимать «download payload» для скачивания с портала CRM (SRC/SUBDIR/FILE_NAME) и конвертировать в `fileId`,
  - принимать скаляры/строки‑цифры/обёртки `['VALUE'=>…]` и устойчиво извлекать `fileId` (см. `normalizeOsRequisitesFileInputToStoredFileId()`).
- **Outbound `crm.company.update`: устойчивое извлечение `fileId` и сбор UF файла** в `buildBitrix24CompanyFieldsFromSiteData()`:
  - если `OS_REQUSITES_FILE` уже в формате `fileData` — **passthrough** без конвертации,
  - иначе `fileId` извлекается через `normalizeOsRequisitesFileInputToStoredFileId()` (с fallback на `LEGAN_ENTITY_FILE`) и конвертируется в `fileData + base64` через `buildCrmFileFieldFileDataFromBitrixFileId()`.

## Что ещё проверить (для фактического закрытия по стендам)

- **Outbound**:
  - В `SyncTrace` есть `company.b24.requisites_file.trace` с `included=true` и причиной `passthrough_fileData` либо `fileId_to_fileData_base64`.
  - В execution `registration/crm-company-update-v1` видно UF файла в `PARAMS` в формате `fileData`.
- **Inbound** (если симптом «ИБ 23 не обновляется» актуален):
  - Реальный запрос в `local/modules/eklektika.sync/public/inbound_crm.php` с `ACTION=UPDATE_COMPANY` и валидным `OS_COMPANY_B24_ID` приводит к обновлению ИБ 23.

## Связанные документы

- `local/docs/business-processes/registration/README.md`
- `local/docs/reference/registration-n8n-webhooks.md`
- `local/docs/reference/n8n-inbound-update-company-from-b24.md`
- ADR: `modules/eklektika.sync/docs/adr/2026-05-02-n8n-inbound-b24-company-event-bridge.md`
- ADR: `modules/eklektika.sync/docs/adr/2026-05-02-registration-webhook-response-contracts.md`

