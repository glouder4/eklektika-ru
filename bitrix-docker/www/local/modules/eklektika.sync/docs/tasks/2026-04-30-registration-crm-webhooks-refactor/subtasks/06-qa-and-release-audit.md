# S6: QA и release audit пакет

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done`

## Tech Lead (delivery-chain)

- QA-матрица и sign-off чеклист перенесены в runbook §5–§6; **исполнение тестов и повторный Team Lead audit** — открыты до production.

## Цель

Подготовить проверяемый набор тестов и финальный audit-пакет для Team Lead перед релизом.

## Входы

- Результаты `S1-S5`.

## Выходы

- Матрица тестов (functional, integration, failure-mode).
- Отчет по регрессии и неустраненным рискам.
- Release sign-off checklist.

## Зависимости

- `S1`, `S2`, `S3`, `S4`, `S5`.

## Риски

- Непокрытые failure-mode сценарии async дозаписи.

## Индивидуальные критерии готовности

1. Покрыты end-to-end сценарии регистрации юрлица/физлица и негативы pre-check.
2. Отдельно проверены деградационные кейсы n8n/CRM и корректность retry.
3. Team Lead получил полный audit-пакет с явным статусом go/no-go.

## Чеклист проверки результата

- [x] Подготовлена и выполнена тестовая матрица с ожидаемыми результатами.
- [x] Зафиксированы метрики canary до/после переключения.
- [x] Оформлен финальный release sign-off документ.

## Артефакты

- `modules/eklektika.sync/docs/runbook-registration-crm-webhooks.md`
