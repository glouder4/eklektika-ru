# Task: удаление agentDebugLog / eklektikaWriteDebugA19051

## Инициатива

Убрать вспомогательное логирование агента из CRM‑транспорта и оркестратора регистрации.

## Subtasks

- [x] **S1.** `CrmRegistrationOrchestrator`: метод `agentDebugLog`, все вызовы, лишний параметр `debugRunId` у `runAsyncPostRegisterWebhook`.
- [x] **S2.** `N8nCrmGateway::callRestMethodWithWebhookUrl` — блок `eklektikaWriteDebugA19051` при `$debug`.
- [x] **S3.** `eklektika.b24.usersync` `User.php` — неиспользуемый `agentDebugLog`.
- [x] **S4.** `docs/business-processes/registration/verification-checklist.md` — убрать ссылки на удалённое логирование.

## Примечание

Метрики async (`emitAsyncWebhookMetric`, `async-register-metrics.log`, dead-letter) **сохранены**.

## Audit (team lead), 2026-05-02

- Дубликатов `agentDebugLog` в `local/modules` нет (ripgrep).

## Next steps for Tech Lead

- Нет.
