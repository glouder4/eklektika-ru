# Реестр unit-тестов `local/` (PHPUnit)

**Текущий ориентир:** **46 tests** (полный прогон всех suite в `phpunit.xml`). Число **assertions** см. в выводе `composer test` — оно растёт с каждым новым кейсом.

**Как читать вывод**

- **N tests** — отдельных **запусков** тест-кейса. Один метод с `#[DataProvider]` считается за **несколько** тестов (по одному на набор данных). Ниже это отмечено как «4× DataProvider».
- **Покрытие — unit без полного Bitrix / без реальных HTTP к n8n.** Дополнительно: тесты **WebhookFixtures** используют сохранённые JSON-файлы (обезличенные снимки контракта), но по-прежнему **не** дергают сеть. Это **не** end-to-end регистрация и **не** `CrmRegistrationOrchestrator` целиком.

См. также: `local/modules/eklektika.sync/docs/tasks/2026-05-02-phpunit-per-module-convention/MODULE_TEST_MATRIX.md`.

---

## Модуль `eklektika.b24.registration` (suite `eklektika.b24.registration`)

### Класс `CrmRegistrationN8nPrecheckResponse`

**Файл:** `modules/eklektika.b24.registration/tests/Unit/CrmRegistrationN8nPrecheckResponseTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 1 | `testRegistrationPrecheckResponseIndicatesSuccess_topLevelSuccess` | Успех precheck по `data['success'] === 1` |
| 2 | `testRegistrationPrecheckResponseIndicatesSuccess_nestedResultSuccess` | Успех по `result['success']` при `data['success'] === 0` |
| 3 | `testRegistrationPrecheckResponseIndicatesSuccess_falseWhenNoSuccessFlags` | Нет успеха, если оба флага 0 |
| 4 | `testUnwrapWebhookResult_nonArrayReturnsNull` | Не-массив → `null` |
| 5 | `testUnwrapWebhookResult_successZeroReturnsArray` | `success === 0` — возвращается envelope |
| 6 | `testUnwrapWebhookResult_unwrapsResultKey` | Достаётся `result` |
| 7 | `testUnwrapWebhookResult_noResultKeyReturnsArray` | Нет `result` — возвращается весь массив |
| 8–11 | `testFormatCrmPrecheckRejectionMessage_prefersFirstNonEmptyKey` (**DataProvider**, 4 кейса) | Порядок текста ошибки: `error_description`, `error`, `message`, `hint` |
| 12 | `testFormatCrmPrecheckRejectionMessage_fallback` | Пустой массив → запасной текст |
| 13 | `testIsProbableN8nErrorResponseBody_emptyOrNonArray` | Пустой / не массив |
| 14 | `testIsProbableN8nErrorResponseBody_contractShapeRejected` | Контрактный envelope не «сырой n8n» |
| 15 | `testIsProbableN8nErrorResponseBody_n8nStyleWithCode` | `message` + `code` |
| 16 | `testIsProbableN8nErrorResponseBody_hintOrStacktrace` | `hint` / `stacktrace` |

**Подитого: 16 тестов.**

### Класс `AjaxRegisterPostParser`

**Файл:** `modules/eklektika.b24.registration/tests/Unit/AjaxRegisterPostParserTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 17 | `testNormalizeInn_stripsNonDigits` | ИНН: только цифры |
| 18 | `testNormalizeInn_emptyAfterStrip` | Пустая строка после очистки |
| 19 | `testCollectMissingRequiredFields_noneMissing` | Все обязательные поля заполнены |
| 20 | `testCollectMissingRequiredFields_allRequiredEmpty` | Все обязательные пусты (7 подписей) |
| 21 | `testCollectMissingRequiredFields_partial` | Частично не заполнено |

**Подитого: 5 тестов.** Метод `parse(Bitrix\Request)` **не** покрыт (нужен double Request).

### Класс `CrmRegistrationN8nTransport` (только формат ошибки)

**Файл:** `modules/eklektika.b24.registration/tests/Unit/CrmRegistrationN8nTransportFormatTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 22 | `testFormatRegistrationWebhookFailureMessage_httpStatusOnly` | Строка с HTTP-кодом |
| 23 | `testFormatRegistrationWebhookFailureMessage_errorField` | Поле `error` |
| 24 | `testFormatRegistrationWebhookFailureMessage_dataMessage` | `data.message` |
| 25 | `testFormatRegistrationWebhookFailureMessage_messageAndHint` | HTTP + message + hint |
| 26 | `testFormatRegistrationWebhookFailureMessage_rawPreviewTruncated` | `raw_preview` до 200 символов |
| 27 | `testFormatRegistrationWebhookFailureMessage_fallbackWhenEmpty` | Пустой вход → дефолтное сообщение |

**Подитого: 6 тестов.** Методы `post`, разрешение URL с config — **не** здесь.

**Итого по каталогу Unit модуля registration: 27 тестов.**

### Фикстуры JSON (`WebhookFixtures`)

**Файл:** `modules/eklektika.b24.registration/tests/WebhookFixtures/RegistrationWebhookSampleRegressionTest.php`  
Данные: `local/tests/fixtures/n8n-webhooks/samples/*.anon.json`

| Метод | Фикстура | Смысл |
|-------|----------|-------|
| `testPrecheckNoDuplicate_sampleMatchesSuccessContract` | `precheck-no-duplicate.anon.json` | Успех, пустой `result` |
| `testPrecheckDuplicateHit_sampleStillIndicatesSuccess` | `precheck-duplicate-hit.anon.json` | Успех, непустой `result` |
| `testPrecheckCrmReject_sampleFormatsRejection` | `precheck-crm-reject.anon.json` | Отказ CRM, текст ошибки |

**+3 теста.** Всего по suite `eklektika.b24.registration`: **30**.

---

## Модуль `eklektika.sync` (suite `eklektika.sync`)

**SUT:** `OnlineService\Sync\FromCrm\CrmInboundUfMap`  
**Файл:** `modules/eklektika.sync/tests/Unit/CrmInboundUfMapTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 28 | `testMarketingInboundSignalAbsent` | Пустой маркетинг-сигнал vs `0` |
| 29 | `testMarketingInboundSignalTrue` | Нормализованное «да» |
| 30 | `testMarketingInboundSignalFalse` | Нормализованное «нет» / граница |
| 31 | `testPeekMarketingAgentRawValue_prefersIsMarketingAgent` | Приоритет `IS_MARKETING_AGENT` |
| 32 | `testPeekMarketingAgentRawValue_isMarketingAgentAbsentReturnsNull` | null/'' в агенте |
| 33 | `testPeekMarketingAgentRawValue_contactAdvertisingUf` | UF рекламы контакта |
| 34 | `testPeekMarketingAgentRawValue_legacyUFAdvertstering` | Легаси-ключ |
| 35 | `testPeekMarketingAgentRawValue_noKeys` | Пустой массив |
| 36 | `testUserDirectorUfToCrmInt` | Директор → 0/1 |
| 37 | `testPrepareUserUpdatePayload_mapsAndStripsCrmUf` | Маппинг и очистка `UF_CRM_*` |
| 38 | `testPrepareUserUpdatePayload_absentMarketingLeavesUnset` | Пустой UF маркетинга |
| 39 | `testPrepareUserUpdatePayload_absentDirectorLeavesUnset` | null у директора |

**Итого по модулю sync: 12 тестов.**

---

## Модуль `eklektika.b24.rest` (suite `eklektika.b24.rest`)

### Класс `N8nCrmGateway` (ранние выходы без curl)

**Файл:** `modules/eklektika.b24.rest/tests/Unit/N8nCrmGatewayEarlyExitTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 40 | `testCallRestMethodWithWebhookUrl_emptyUrl` | Пустой URL → структурированная ошибка до HTTP |
| 41 | `testCallRestMethodWithWebhookUrl_jsonEncodeFails` | Несериализуемый `PARAMS` → `json_encode_failed` до curl |

### Класс `RestTransportConfig`

**Файл:** `modules/eklektika.b24.rest/tests/Unit/RestTransportConfigTest.php`

| # | Метод теста | Смысл |
|---|-------------|-------|
| 42 | `testBuildMainWebhookMethodUrl` | Шаблон URL `buildMainWebhookMethodUrl` (константы из `setUpBeforeClass`) |
| 43 | `testBuildKitWebhookPrefix` | Префикс kit-вебхука `buildKitWebhookPrefix` |

**Итого по модулю rest: 4 теста.** Реальный HTTP/curl в этих кейсах не вызывается.

---

## Сводка

| Модуль | Тестов |
|--------|--------|
| `eklektika.b24.registration` | 30 |
| `eklektika.b24.rest` | 4 |
| `eklektika.sync` | 12 |
| **Всего** | **46** |

---

## Что намеренно не покрыто

- `CrmRegistrationOrchestrator`, реальный HTTP/`HttpClient`, webhooks, сессии Bitrix, `Option`, БД.
- `AjaxRegisterPostParser::parse(Request)`.
- Остальные модули матрицы до появления у них `tests/Unit`.
