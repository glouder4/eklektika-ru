# S4: Идемпотентность, retry и observability

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done`

## Tech Lead (delivery-chain)

- Документы и сверка с реализацией закрыты: **2026-04-30** (post-rework).

## Цель

Обеспечить устойчивость async-фазы: защита от дублей, управляемые ретраи и прозрачный мониторинг ошибок.

## Входы

- Контракт async операции из `S1`.
- Async поток из `S3`.

## Выходы

- Политика idempotency key.
- Политика retry/backoff/dead-letter.
- Набор метрик и алертов по SLA.

## Зависимости

- `S1`, `S3`.

## Риски

- Дубликаты contact/company при повторной доставке без строгой проверки ключа.

## Индивидуальные критерии готовности

1. Каждая async операция имеет idempotency key и проверку повторов.
2. Retry ограничен по попыткам и времени, dead-letter сценарий документирован.
3. Метрики и алерты покрывают fail-rate, lag и объем очереди.

## Чеклист проверки результата

- [x] Описана таблица retry-политик для всех webhook-операций.
- [x] Настроены алерты на рост fail-rate и queue lag.
- [x] Есть runbook для ручной reconciliation проблемных регистраций.

## Артефакты

- `modules/eklektika.b24.usersync/lib/RegisterUserCompany.php`
- `modules/eklektika.sync/docs/runbook-registration-crm-webhooks.md`
