# Task: registration CRM webhooks refactor

## Бизнес-цель

Ускорить и стабилизировать пользовательскую регистрацию за счет отделения CRM-вызовов в отдельные webhook-операции n8n и перевода записи `company_id/contact_id` в асинхронную фазу после успешной локальной регистрации.

## Связанные артефакты

- ADR: `docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- Текущий task первой волны: `docs/tasks/2026-04-30-n8n-crm-bridge-registration/task.md`
- Runbook: `docs/runbook-registration-crm-webhooks.md`

## План внедрения (milestones)

1. **M1 — Контракты и маршруты webhook зафиксированы**
   - Результат: согласованные `v2` контракты для 5 операций (`unique email/phone`, `inn`, `company.add`, `contact.add`, `async bind ids`).
   - Gate: Team Lead утверждает schema + error model + idempotency policy.
   - **Статус post-rework:** async consumer-контракт и политика idempotency на сайте зафиксированы в runbook и коде; **полная матрица пяти n8n entrypoints — вне PHP, ожидает согласования с n8n** (см. ADR «Сверка с реализацией»).
2. **M2 — Pre-check и core registration разделены**
   - Результат: синхронно выполняются только проверки уникальности, локальная регистрация не ждет CRM ID.
   - Gate: регрессионный прогон регистрации и негативных кейсов pre-check.
   - **Статус post-rework:** `runSyncPreCheck` в `OnBeforeUserRegisterHandler`; при `sync_legacy=true` CRM create остаётся синхронным в `OnAfter` (как и раньше). Целевое «не ждать CRM ID» достигается при выключенном legacy + полагается на async/n8n — **требует интеграционной проверки перед prod**.
3. **M3 — Async enrichment включен под feature toggle**
   - Результат: фоновая дозапись / уведомление n8n через webhook с retry, dedupe и метриками.
   - Gate: canary успешен по SLA и fail-rate.
   - **Статус post-rework:** код и runbook готовы; **canary на целевом окружении не зафиксирован в этом пакете**.
4. **M4 — Cutover + rollback readiness**
   - Результат: основной трафик на новом пути, rollback-процедура протестирована.
   - Gate: Team Lead audit закрыт, Tech Lead принял итоги документов.
   - **Статус post-rework:** **Tech Lead принял пакет документации и сверку с ADR 2026-04-30**; повторный **Team Lead audit** и production cutover — открыты.

## Декомпозиция на подзадачи

- [x] `S1` Контракты и n8n routes — `subtasks/01-contracts-and-webhooks.md` *(частично: сайт + runbook; пять HTTP-маршрутов n8n — вне этого PR)*
- [x] `S2` PHP pre-check слой регистрации — `subtasks/02-php-precheck-registration-flow.md`
- [x] `S3` Async enrichment trigger — `subtasks/03-async-enrichment-and-local-bind.md`
- [x] `S4` Идемпотентность, retry, observability — `subtasks/04-idempotency-retry-observability.md`
- [x] `S5` Миграция, canary, rollback rehearsal — `subtasks/05-migration-rollout-rollback.md` *(процедуры в runbook; фактический rehearsal на стенде — отметить владельцу инфры)*
- [x] `S6` QA и release audit пакет — `subtasks/06-qa-and-release-audit.md` *(матрица и sign-off в runbook; исполнение тестов — до prod)*

## Общие критерии готовности (Definition of Done)

1. Pre-check операции (Email/Телефон, базовые проверки ЮЛ) блокируют регистрацию только по бизнес-ошибкам.
2. Включены переключатели `sync_legacy` / `async_post_register`; async путь отправляет webhook с идемпотентностью, retry и dead-letter (см. runbook).
3. Описаны canary, rollback, метрики и QA-матрица; секреты не коммитятся в git.
4. ADR синхронизирован с фактической реализацией и зафиксированы открытые зазоры (n8n routing / второй audit).

Исходный п.2 DoD («локальная регистрация без ожидания CRM ID») выполняется **только в сочетании** `sync_legacy=false` и рабочего n8n downstream — трактовать как **остаточный интеграционный gate перед prod**.

## Риски и зависимости

- **Зависимости:** URL async webhook в конфиге, права записи на `local/logs`, n8n workflow.
- **Критический риск:** рассинхрон ADR («5 webhook-операций») и факт (REST + один async URL) без явного appendix.
- **Снятие риска:** повторный Team Lead audit, canary по runbook, подписание n8n-контракта.

## Статус и прогресс

- Статус инициативы (документы Tech Lead): **`delivery_chain_closed`** (план, ADR-сверка, подзадачи S1–S6 обновлены).
- Статус внедрения в production: **`pending`** — открыты cutover, повторный audit, исполнение QA/sign-off на окружении.
- Прогресс подзадач: **6/6** закрыты с оговорками в файлах S1/S5/S6.
- Последнее обновление: `2026-04-30` (post-rework).

### История волн

| Волна | Итог |
|-------|------|
| Initial dev-wave | Team Lead audit: **FAIL** |
| Developer-squad rework | Security, idempotency, retry, observability, S5–S6 docs, runbook, `RegisterUserCompany` / `config.local.php` |
| Tech Lead (финал) | ADR + task/subtask чеклисты + статус (этот коммит) |

## Next steps for Team Lead

1. Провести **повторный audit** кода после rework (фокус: сочетания флагов, отсутствие секретов в git, обработка `contact_id=0` в async payload).
2. Согласовать с n8n владельцем маршрутизацию: 5 операций vs упрощённая схема; при необходимости — appendix к ADR.
3. Исполнить canary и отметить даты/metric baseline в задаче или release notes.
4. Закрыть production Go/No-Go по §6 runbook.
