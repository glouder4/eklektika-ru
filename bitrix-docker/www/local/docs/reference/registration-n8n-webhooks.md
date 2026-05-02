# Регистрация: n8n webhooks и конфиг‑ключи

Документ описывает **какие конфиг‑ключи используются** при регистрации юрлица/рекламного агента и **за что отвечает каждый webhook**.

## Где лежит конфигурация

Конфиги читаются из:

- `$GLOBALS['EKLEKTIKA_SYNC_CONFIG']` (например, задаётся на стенде окружением/инициализацией)
- или `local/modules/eklektika.sync/config.local.php` (локальный файл; секреты не коммитить)

## Обязательный контракт ответов (JSON)

Регистрация **не завершается успешно**, если любой вызванный вебхук вернул неожиданное тело при HTTP 2xx, невалидный JSON, HTTP ≠ 2xx, либо если REST‑прокси (`registration_crm_rest_proxy_webhook_url`) вернул распознанную ошибку на обязательном шаге (реквизиты, привязка контакт↔компания, UF сайта на компании и т.д.).

### Общий envelope

- Тело ответа — **JSON‑объект** с полями **`success`** и **`result`**. Допускается ошибочная обёртка n8n в виде **одного** элемента: `[{ "success": 1, "result": ... }]` — PHP распакует.
- Успех: `success` ∈ `{ 1, true, "1", "true", "yes" }` (регистронезависимо для строк).
- Поле **`result` обязательно** при успешном ответе (даже если «пустой» смысл — см. ниже).

### Типы вебхуков

| Класс | Ключи конфига | Поле `result` при успехе |
|--------|----------------|---------------------------|
| **Поиск / пречек** | `registration_webhook_unique_url`, `registration_webhook_inn_url` | Массив `[]` (нет данных), список найденных записей или объект — как договорено с CRM; главное — **явный** `success` и наличие ключа `result`. |
| **Создание сущности** | `registration_webhook_company_add_url`, `registration_webhook_contact_add_url` | Числовой ID **или** объект с полем `ID`. |
| **Список (проверка)** | `registration_webhook_crm_requisite_list_url` | Массив элементов (может быть `[]`). |
| **Доп. данные компании** | `registration_webhook_company_updates_url` | Объект (может быть `{}`); webhook вызывается только если URL **задан** — тогда ответ должен соответствовать envelope (ошибка HTTP или контракта прерывает регистрацию). |

### Примеры для n8n «Respond to Webhook»

Уникальность / ИНН — нет совпадений:

```json
{ "success": 1, "result": [] }
```

Создание компании / контакта:

```json
{ "success": 1, "result": 107 }
```

Список реквизитов:

```json
{ "success": 1, "result": [ { "ID": 1, "ENTITY_ID": 70, "RQ_INN": "7707083893" } ] }
```

Отказ CRM:

```json
{ "success": 0, "error": "...", "error_description": "..." }
```

## Ключи конфигурации (обязательные/критичные)

### `n8n_registration_http_base`

- **Назначение**: базовый URL до webhook‑хостинга n8n для регистрации (например `http://localhost:5678/webhook` или `/webhook-test`).
- **Как используется**: если конкретный `registration_webhook_*_url` пустой, URL собирается как `base + '/' + <path>`.
- **Важно**: для сборки через base нужен также mapping относительных путей в `registration_webhook_path_suffixes` (см. реализацию в `CrmRegistrationOrchestrator::registrationWebhookRelativePath()` / `CrmRegistrationN8nTransport::registrationWebhookRelativePath()`); на стендах обычно задаются полные URL.

### `inbound_secret`

- **Назначение**: общий секрет для доверенных HTTP вызовов между сайтом ↔ n8n/CRM‑мостом.
- **Как передаётся**: заголовок `X-Sync-Token` (см. `CrmRegistrationOrchestrator::postRegistrationWebhook()` и `CrmRegistrationN8nTransport::post()`).

### `registration_webhook_unique_url`

- **Назначение**: ранний precheck уникальности контакта в CRM (email/телефон).
- **Операция n8n**: `crm-check-unique-contact-v1`.
- **Кто вызывает**:
  - `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck`
  - `\OnlineService\B24\Registration\CrmRegistrationOrchestrator::crmCheckUniqueContact()`
- **Ожидаемый контракт ответа**: см. раздел «Обязательный контракт ответов»; для «нет дубликата» используйте `{ "success": 1, "result": [] }`. Непустой `result` → найден контакт; `success=0`/ошибка → отказ регистрации.

### `registration_webhook_inn_url`

- **Назначение**: ранний precheck ИНН организации в CRM.
- **Операция n8n**: `crm-check-inn-v1`.
- **Кто вызывает**:
  - `CrmRegistrationOrchestrator::crmCheckInnUniqueness()` (на вход получает `UF_INN`, нормализует до цифр)
  - `CrmRegistrationOrchestrator::runAjaxCompanyInnPrecheck()` (если ИНН пуст после нормализации — HTTP не вызывается)
- **Примечание**: payload результата используется дальше для выбора режима `COMPANY_MODE` (см. `AjaxRegisterActionService::isExistingCompanyRegistrationMode()`).

### `registration_webhook_company_updates_url`

- **Назначение**: опциональная сверка/подтягивание актуальных данных компании из CRM перед `crm.contact.add`, когда компания в CRM уже найдена по ИНН (локальная привязка или requisite → `crm.company.get`).
- **Операция n8n (канонический path)**: `registration/check-crm-company-updates-v1` (полный URL можно задать в ключе или через `n8n_registration_http_base` + суффикс в `registration_webhook_path_suffixes['registration_webhook_company_updates_url']`).
- **Кто вызывает**: `CrmRegistrationOrchestrator::maybeMergeCompanyUpdatesFromN8n()` — только если URL непустой и HTTP успешен.
- **Запрос (JSON)**:
  - `COMPANY_ID` — ID компании в CRM (Bitrix24 company id).
  - Важно: **ИНН не передаётся**. Если на стороне n8n нужно получить `COMPANY_ID` по ИНН — используйте отдельный исходящий webhook `registration_webhook_crm_requisite_list_url` (crm.requisite.list) или precheck `registration_webhook_inn_url` (crm-check-inn-v1).
- **Ответ (ожидаемый envelope, как у других registration webhooks)**:
  - `{ "success": 1, "result": { ... } }`
  - Внутри `result` сайт читает первый непустой из ключей:
    - `registration_fields` — ассоциативный массив полей для слияния в массив пользователя/регистрации (`UF_*`, `UF_NAME_COMPANY`, …);
    - или `site_user_fields`;
    - или `merge_fields`.
  - Только непустые скалярные значения мержатся в `$arFields` перед дальнейшим созданием контакта.
- **Ошибки**: при пустом URL, HTTP‑ошибке или отсутствии `result` шаг **пропускается** (не блокирует регистрацию).

#### Карта свойств элемента компании (ИБ) для возврата из webhook

Webhook должен возвращать **полный набор пользовательских свойств** элемента компании (ИБ) в виде словаря (ключ → значение), чтобы сайт мог создать/обновить локальную карточку синхронно с CRM.

Рекомендуемый ключ в `result`:

- `iblock_company_properties`: `array<string, mixed>`

Ожидаемые коды свойств (ключи словаря) и смысл:

- `LEGAN_ENTITY_NAME`: Название юридического лица
- `LEGAN_ENTITY_ADRESS`: Юридический адрес
- `LEGAN_ENTITY_ACTIVITY`: Сфера деятельности
- `LEGAN_ENTITY_INN`: ИНН организации (CRM: `RQ_INN`)
- `LEGAN_ENTITY_USERS`: Сотрудники
- `LEGAN_ENTITY_IS_HEAD_COMPANY`: Является головной компанией
- `LEGAN_ENTITY_ID_OF_HEAD_COMPANY`: ID головной компании
- `LEGAN_ENTITY_BOSS`: Руководители компании
- `LEGAN_ENTITY_WWW`: Веб-сайт компании
- `OS_IS_MARKETING_AGENT`: Рекламный агент
- `LEGAN_ENTITY_CITY`: Город
- `LEGAN_ENTITY_EMAIL`: Email
- `LEGAN_ENTITY_FILE`: Файл с реквизитами
- `OS_COMPANY_B24_ID`: Id из Bitrix24
- `OS_HEAD_COMPANY_B24_ID`: ID головной компании в B24
- `OS_HOLDING_OF`: Холдинг
- `LEGAN_MAIN_PHONE`: Телефон основной
- `LEGAN_MOBILE_PHONE`: Телефон мобильный

Пример `result`:

```json
{
  "success": 1,
  "result": {
    "registration_fields": { "UF_NAME_COMPANY": "..." },
    "iblock_company_properties": {
      "LEGAN_ENTITY_INN": "111111111111",
      "OS_COMPANY_B24_ID": "70",
      "LEGAN_ENTITY_NAME": "ООО Ромашка"
    }
  }
}
```

### `registration_crm_rest_proxy_webhook_url`

- **Назначение**: прокси‑вебхук n8n для выполнения CRM REST методов `crm.*` в рамках регистрации.
- **Операция n8n**: `registration/crm-registration-rest-v1`.
- **Кто вызывает**: `CrmRegistrationOrchestrator::callB24Method()` → `\OnlineService\B24\N8nCrmGateway::callRestMethodWithWebhookUrl(...)`.
- **Типовые методы**: `crm.company.get/update/add`, `crm.requisite.*`, `crm.contact.*`, `crm.contact.company.add` и др.
- **Пояснение**: имя `callB24Method` историческое; транспорт — вебхук n8n (не прямой REST с сайта на портал). Подробнее: `modules/eklektika.sync/docs/adr/2026-05-02-registration-callb24method-transport.md`.

### `registration_webhook_company_add_url`

- **Назначение**: прямой вебхук для создания компании (когда используется не REST‑прокси).
- **Операция n8n**: `crm-company-add-v1`.
- **Кто вызывает**: `CrmRegistrationOrchestrator::crmAddCompany()`.

### `registration_webhook_contact_add_url`

- **Назначение**: прямой вебхук для создания контакта (когда используется не REST‑прокси).
- **Операция n8n**: `crm-contact-add-v1`.
- **Кто вызывает**: `CrmRegistrationOrchestrator::crmAddContact()`.
- **Ответ (envelope)**: канонически объект `{ "success": 1, "result": <id контакта> }`. Если узел n8n возвращает один объект в JSON‑массиве — `[{ "success": 1, "result": 107 }]`, сайт распаковывает это в тот же контракт (`unwrapRegistrationWebhookSingleElementEnvelope`), иначе ID контакта не извлекается и **не вызываются** последующие шаги через REST‑прокси (`crm.contact.company.add`, `crm.company.update` и т.д.).

### `registration_webhook_crm_requisite_list_url`

- **Назначение**: отдельный webhook-адаптер для вызова B24 REST `crm.requisite.list` (используется точечно в регистрации для проверки/валидации компании по ИНН, без REST‑прокси).
- **Операция n8n (канонический path)**: `registration/crm-requisite-list-v1`
- **Кто вызывает**: `CrmRegistrationOrchestrator` в ветке проверки компании по результату `crm-check-inn-v1`/requisites.
- **Запрос (JSON)**:
  - `{ "crmMethod": "crm.requisite.list", "crmParams": { "select": [...], "filter": { "ENTITY_TYPE_ID": 4, "RQ_INN": "<inn>" } } }`
- **Ответ (envelope)**:
  - success=1: `result` должен содержать список реквизитов (массив), где `ENTITY_ID` соответствует компании по ИНН
  - success=0: отказ/ошибка (сайт считает, что объект не существует)

## Ключи поведения процесса (feature toggles)

### `sync_legacy`

- **Назначение**: включает синхронную CRM‑синхронизацию в `CrmRegistrationOrchestrator::OnAfterUserRegisterHandler()` (после классической регистрации через события main).
- **Смысл**: исторический путь, когда после регистрации пользователя сразу выполняется CRM‑часть.

### `async_post_register` и `async_post_register_webhook_url` (и `async_post_register*`)

- **Назначение**: включает дополнительный “тихий” webhook после регистрации для дозаписи `contact_id/company_id` и связанных полей.
- **Кто вызывает**:
  - `CrmRegistrationOrchestrator::OnAfterUserRegisterHandler()` (когда известен `contact_id`)
  - и fallback‑ветка (когда `contact_id` не найден, отправляется payload с `contact_id = 0`)
- **Механика**: идемпотентность через `X-Idempotency-Key`, локальные lock/state файлы, ретраи и dead‑letter (см. `CrmRegistrationOrchestrator::runAsyncPostRegisterWebhook()`).

## Связанный бизнес‑процесс

Каноническое описание регистрации (шаги, порядок вызовов, ветвления, откаты):

- `docs/business-processes/registration/README.md`
