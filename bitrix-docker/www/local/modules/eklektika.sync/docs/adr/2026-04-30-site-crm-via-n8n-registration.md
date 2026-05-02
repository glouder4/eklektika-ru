# ADR: рефакторинг регистрации через n8n webhook-операции (волна 2)

## Статус

Принято. **Tech Lead delivery-chain по артефактам закрыт 2026-04-30** (post-rework): обновлены PHP (`RegisterUserCompany`), конфиг `eklektika.sync`, runbook и подзадачи S4–S6. Целевое разбиение **всех** pre-check / CRM.create операций на отдельные n8n webhook-ветки (**§ Решение п.1**) остаётся дорожной картой: в текущей реализации сайта синхронные проверки и создание сущностей по-прежнему идут через существующий REST-транспорт (`callB24Method` / см. блок «Сверка с реализацией» ниже).

## Контекст

Сайт eklektika-ru синхронизирует регистрацию пользователей и компаний с Bitrix24 через модуль `eklektika.b24.usersync` (`RegisterUserCompany` и события `OnBefore/AfterUserRegister`) и транспорт `RestClient::callRestMethod` через n8n.

Бизнес-требование инициативы:
- выделить в отдельные webhook-операции проверки и создание CRM-сущностей:
  - проверка Email/Телефон на уникальность в CRM;
  - проверка ИНН организации;
  - `company.add`;
  - `contact.add`;
- изменить процесс регистрации: локальная регистрация завершается без ожидания `company_id/contact_id` из CRM, затем выполняется асинхронная "тихая дозапись" CRM ID.

Graphify preflight (сервер `graphify-eklektika-ru-site`) подтверждает опорные точки внедрения:
- `RegisterUserCompany::OnBeforeUserRegisterHandler` и `UserSyncBootstrap::handleBeforeUserRegister` — участок pre-check валидации;
- `RegisterUserCompany::OnAfterUserRegisterHandler`, `createB24Company` и `syncFromSiteRegistration` — участок post-register синхронизации;
- `RegisterUserCompany::callB24Method` — единый шлюз вызовов CRM-методов.

## Сверка с реализацией (rework, 2026-04-30; уточнение ajax-цепочки и ИНН — 2026-05-01)

**Точка входа публичной регистрации (ajax):** `www/personal/ajax/ajax-register-action.php` → после `check_bitrix_sessid()` и `Loader::includeModule('iblock')` вызывается **`AjaxRegisterActionService::run`** (`eklektika.b24.usersync`): локально обязательные поля и длина ИНН → **ранний n8n precheck** через **те же** webhooks, что и в `runSyncPreCheck`: `registration_webhook_unique_url` (`crm-check-unique-contact-v1`) и при непустом ИНН — `registration_webhook_inn_url` (`crm-check-inn-v1`), **без** отдельного композитного URL (`crm-registration-precheck-v1` не используется) → далее ИБ/локальные дубликаты → сбор полей → **`RegisterUserCompany::OnBeforeUserRegisterHandler`** → создание пользователя → **`syncFromSiteRegistration`** (REST-прокси регистрации).

Фактически внедрено на стороне сайта:

| Область | Реализация |
|--------|-------------|
| Feature toggle | `sync_legacy`, `async_post_register`, URL `async_post_register_webhook_url` в `config.local.php` / `GLOBALS['EKLEKTIKA_SYNC_CONFIG']`. |
| Pre-check перед регистрацией | `OnBeforeUserRegisterHandler` → `runSyncPreCheck`: уникальность email/телефон через n8n (`registration_webhook_unique_url` / base); для типов 5/6 при **непустом ИНН** — обязательный вызов **`crmCheckInnUniqueness`** (вебхук `registration_webhook_inn_url` / `crm-check-inn-v1`). Далее валидация пароля/полей ЮЛ. |
| Синхронизация после создания пользователя (ajax safe path) | `syncFromSiteRegistration` → `createB24Company`: при непустом ИНН **`crmCheckInnUniqueness` вызывается до** опоры на локальную связку `OS_COMPANY_B24_ID`; при расхождении CRM vs локальный B24 ID — отказ. Остальные `crm.*` — через **`callB24Method`** → именованные `registration_webhook_crm_*_url` / `N8nCrmGateway`. |
| Legacy post-register | При `sync_legacy=true` сохраняется синхронное `registerCoreInCrm` → `createB24Company`. |
| Async пост-регистрация | При `async_post_register=true` после локального апдейта UF контакта вызывается `runAsyncPostRegisterWebhook`: JSON payload, заголовок `X-Idempotency-Key`, файловые lock/state в `local/logs`, до 3 попыток, backoff, dead-letter и метрики в `async-register-metrics.log`. |
| Безопасность секретов | Runbook: inbound secret только из `EKLEKTIKA_SYNC_INBOUND_SECRET`, не хранить в репозитории. |
| Наблюдаемость / операции | `runbook-registration-crm-webhooks.md`: метрики, пороги canary, rollback, QA-матрица, Go/No-Go. |

Открытый архитектурный зазор относительно исходного ADR: **пять именованных n8n операций** (`crm-check-unique-email-phone`, …) как отдельные HTTP entrypoints должны быть подтверждены и задеплоены в workflow n8n; PHP сейчас готов потреблять **один** async webhook URL и не заменяет синхронный CRM create на пять отдельных вызовов n8n.

## Решение

1. **Архитектурное разбиение workflow "Site to CRM"**
   - В рамках того же n8n workflow вводятся отдельные webhook entrypoints (или явные route-ветки единого entrypoint c отдельными path/operation-id) для операций:
     - `crm-check-unique-email-phone`;
     - `crm-check-company-inn`;
     - `crm-company-add`;
     - `crm-contact-add`;
     - `crm-bind-registration-ids-async` (новый async endpoint для тихой дозаписи локальных `company_id/contact_id`).
   - Каждый endpoint имеет отдельный контракт запроса/ответа, отдельный маршрут ошибок и отдельные метрики.

2. **Новый целевой регистрационный сценарий**
   - **Этап A (sync pre-check):** до локальной регистрации выполняются только проверки уникальности (Email/Телефон, ИНН) через соответствующие webhook-операции n8n.
   - **Этап B (core registration):** сайт завершает основной сценарий регистрации и подтверждает локальный success без ожидания CRM ID.
   - **Этап C (async enrichment):** после локального success публикуется асинхронный вызов в n8n для последовательности `company.add`/`contact.add` (или идемпотентного upsert в рамках workflow) с последующей тихой дозаписью CRM ID в локальный профиль.

3. **Контракты и идемпотентность**
   - Для async enrichment обязателен корреляционный ключ (`registration_uuid`/`site_user_id`) и идемпотентный ключ операции (`idempotency_key`) для защиты от дублей ретраев.
   - Ошибки async-фазы не блокируют факт локальной регистрации, но пишутся в наблюдаемое хранилище (журнал sync/error queue) и уходят в механизм повторов.
   - Для `company.add`/`contact.add` вводится политика повторного выполнения с backoff и лимитом попыток.

4. **Совместимость и feature toggle**
   - Вводится флаг режима регистрации CRM-sync: `sync_legacy` (текущий синхронный) / `async_post_register` (новый целевой).
   - Переключение на `async_post_register` выполняется поэтапно (canary rollout), с возможностью мгновенного rollback на `sync_legacy`.

## Последствия

- Регистрация перестает быть жестко связанной с синхронным созданием CRM сущностей, уменьшается latency пользовательского потока.
- Появляется eventual consistency между локальным профилем и CRM ID (требует мониторинга и операционных retry-процедур).
- Требуется расширенный операционный контур: idempotency, retry, DLQ/ошибочный журнал и алерты на отставание async-пайплайна.

## Риски

1. **Функциональные**
   - Расхождение данных при падении async-фазы после успешной локальной регистрации.
   - Дубликаты company/contact при повторной доставке без корректного idempotency-key.
   - Остановка PHP-потока регистрации из‑за безусловных debug-stop (`pre()/die()/exit`) в runtime‑коде. Инцидент и фикс: `docs/adr/2026-05-01-registration-crm-unique-contact-stopper.md`.

2. **Интеграционные**
   - Несогласованность контрактов между PHP и n8n по полям pre-check/async payload.
   - Изменения структуры `RegisterUserCompany` могут затронуть существующие хуки `OnBefore/OnAfter`.

3. **Операционные**
   - Очередь async-задач может накапливаться при деградации n8n/CRM.
   - Недостаточная наблюдаемость усложнит восстановление "тихих" ошибок дозаписи.

## Миграционный план

1. Подготовить новые webhook-операции в том же workflow n8n с версионированными контрактами (`v2`).
2. Реализовать в PHP split-сервис: pre-check (sync) + async enrichment trigger.
3. Включить dual-write observability (логирование старого и нового пути без отключения legacy).
4. Запустить canary на ограниченной доле регистраций.
5. При стабильных метриках перевести 100% трафика в `async_post_register`.
6. После стабилизации удалить/заморозить legacy-синхронную ветку.

## Rollback план

1. Мгновенно переключить feature toggle на `sync_legacy`.
2. Остановить публикацию новых async enrichment задач.
3. Обработать хвост уже созданных async задач в режиме safe-retry или пометить как cancelled по регламенту инцидента.
4. Сверить данные по окну инцидента: локальные регистрации vs CRM ID и выполнить reconciliation-скрипт.
5. Зафиксировать RCA и корректировки контрактов перед повторным rollout.

## Контрольные метрики (обязательно)

- P95 времени pre-check (Email/Телефон, ИНН).
- % регистраций без `company_id/contact_id` старше SLA-окна.
- Retry rate и конечный fail rate async enrichment.
- Количество detected duplicate-attempts по idempotency-key.

## Next steps для Team Lead (после rework)

- Зафиксировать в материалах для поддержки: **`success: true` из ajax-register-action означает прохождение всей цепочки (включая n8n pre-check и CRM proxy)** на данном стенде; продуктовый отказ для «мусорных» ИНН — политика ответов workflow n8n и/или доп. валидация на сайте (например контрольные суммы ИНН), если требуется.
- Повторный **code / security audit** перед production (первая волна — FAIL; rework закрыл блокеры по idempotency, retry, наблюдаемости и runbook).
- Согласовать с владельцем n8n **фактическую матрицу маршрутов**: либо довести workflow до пяти операций как в § Решение, либо оформить ADR-appendix «упрощённая фаза: один async webhook + legacy REST pre-check/create».
- Выполнить canary по runbook на staging/production с включением `async_post_register` и проверкой метрик/dead-letter.
- Зафиксировать production sign-off по чеклисту §6 runbook после успешной репетиции rollback.
