# Task: registration flow breaks after unique-contact webhook

## Бизнес-цель

Восстановить корректное выполнение registration pipeline после CRM pre-check `registration_webhook_unique_url` (`/registration/crm-check-unique-contact-v1`) и исключить повтор инцидентов из‑за безусловных debug-stop в прод‑коде.

## Контекст / область

- Сайт: eklektika-ru (1C‑Bitrix), основная зона работ `local/`.
- CRM интеграция: через n8n webhooks (не прямые вызовы).
- Фокус: `eklektika.b24.usersync` registration pipeline (`RegisterUserCompany`, `AjaxRegisterActionService`), этапы после unique-contact pre-check.

## Симптом

Unique-contact webhook выполняется успешно, но **последующие registration webhooks/операции не запускаются** (цепочка останавливается в PHP).

## Root cause / Fix (кратко)

- Причина: безусловный debug‑stop `pre($payload); die();` внутри `RegisterUserCompany::crmAddContact()`.
- Fix: удаление этих двух строк.

## Связанные артефакты

- ADR инцидента: `docs/adr/2026-05-01-registration-crm-unique-contact-stopper.md`
- ADR (контекст регистрации): `docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- Runbook: `docs/runbook-registration-crm-webhooks.md`

## План внедрения (milestones)

1. **M1 — Воспроизведение и локализация остановки**
   - Результат: подтверждено, что остановка происходит в PHP после unique-contact pre-check; собраны входные данные (payload, user/company тип, момент остановки).
2. **M2 — Фикс внедрён и подтвержден**
   - Результат: `crmAddContact()` больше не содержит безусловных остановок выполнения; регистрация проходит все последующие шаги.
3. **M3 — Верификация на окружении + минимальная наблюдаемость**
   - Результат: есть проверяемый план тестов; подтверждено, что downstream шаги исполняются; мониторинг по runbook не деградировал.
4. **M4 — Rollout & risk control**
   - Результат: выкатка безопасна (canary/rollback по runbook), риск повторного debug‑stop снижен аудитом/правилом.

## Декомпозиция на подзадачи

- `S1` Reproduce steps + evidence — `subtasks/01-reproduce-and-evidence.md`
- `S2` Fix & guardrails against debug-stop — `subtasks/02-fix-and-guardrails.md`
- `S3` Verification plan (E2E + failure modes) — `subtasks/03-verification-plan.md`
- `S4` Rollout risk & rollback plan — `subtasks/04-rollout-risk.md`

## Общие критерии готовности (Definition of Done)

1. Регистрация после unique-contact pre-check продолжает выполнение и доходит до последующих шагов (по логам/метрикам/факту вызовов).
2. В `eklektika.b24.usersync` нет безусловных debug-stop в registration pipeline (или они ограждены недоступным по умолчанию флагом/окружением).
3. Есть воспроизведение, верификация, и план безопасной выкатки/отката (с привязкой к runbook).

## Статус и прогресс

- Статус: `in_progress` (ожидается environment verification)
- Прогресс: 3/4 subtasks
- Последнее обновление: `2026-05-01` (post-fix + desk-audit)

## Next steps for Team Lead

1. **Подтвердить фиксацию в нужной ветке/релизе:** убедиться, что удаление `pre()/die()` попало в целевую поставку и не “перетёрто” рефакторингом registration‑модуля.
2. **Сделать быстрый аудит на подобные стопперы:** `pre()/die()/exit/var_dump/print_r` и `SyncPrimitiveBreakpoint` в `local/modules/eklektika.b24.usersync` и соседних модулях, которые исполняются в runtime регистрации.
3. **Верифицировать цепочку на окружении:** пройти чеклист `S3` и убедиться, что после unique-contact реально происходят ожидаемые downstream вызовы/шаги (n8n executions + `local/logs/async-register-*.log`).
4. **Rollout:** применять canary/rollback протокол из runbook, зафиксировать окно выкатки и метрики.

