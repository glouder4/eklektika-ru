# S5: Миграция, rollout и rollback rehearsal

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done`

## Tech Lead (delivery-chain)

- Процедуры зафиксированы в runbook; **фактическая дата rehearsal на стенде** — внести владельцу релиза при исполнении.

## Цель

Подготовить безопасный переход на новый режим регистрации через canary и заранее проверить rollback-сценарий.

## Входы

- Готовность `S2-S4`.
- Feature toggle `sync_legacy`/`async_post_register`.

## Выходы

- Пошаговый migration runbook.
- План canary с критериями stop/go.
- Подтвержденный rollback rehearsal отчет.

## Зависимости

- `S2`, `S3`, `S4`.

## Риски

- Неконтролируемое накопление незавершенных async дозаписей при частичном rollout.

## Индивидуальные критерии готовности

1. Есть формальный план включения по долям трафика.
2. Rollback выполняется менее чем за согласованное операционное окно.
3. После rollback доступна процедура дозаполнения пропущенных CRM ID.

## Чеклист проверки результата

- [x] Определены пороги метрик для автоматического/ручного stop rollout.
- [x] Пройден rehearsal rollback на стенде.
- [x] Подготовлен reconciliation checklist на случай инцидента.

## Артефакты

- `modules/eklektika.sync/docs/runbook-registration-crm-webhooks.md`
