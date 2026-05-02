# Задача: модуль регистрации и компании вместо смешения в usersync

**Инициатива:** вынести регистрацию юрлица и проверку/создание компании (CRM + n8n + ИБ сайта) из `eklektika.b24.usersync` в `eklektika.b24.registration`.  
**ADR:** `docs/adr/2026-05-01-b24-usersync-registration-module-extraction.md`

## Критерии готовности инициативы

- [x] Новый модуль подключён в prolog (**волна 1:** только каркас `include.php`; автозагрузка регистрации — волна 2).
- [x] **Wave 2:** `CrmRegistrationN8nTransport` + DTO + helper перенесены в `eklektika.b24.registration` (новые FQCN), **все вызовы переведены** на новые FQCN, **shims не используются и удалены**.
- [ ] Код регистрации и `RegisterUserCompany` перенесены или обёрнуты без поломки `ajax-register-action` и событий `main`.
- [ ] В `usersync` не остаётся бизнес-логики «компания + n8n registration webhooks» кроме тонких делегатов.
- [ ] Документация и ADR обновлены; graphify-скоуп для `local/modules/eklektika.b24.registration` отражён.

## Subtasks

| ID | Файл | Суть |
|----|------|------|
| 01 | [subtasks/01-module-skeleton-and-bootstrap.md](subtasks/01-module-skeleton-and-bootstrap.md) | Каркас `eklektika.b24.registration`, include, require в prolog |
| 02 | [subtasks/02-move-ajax-register-pipeline.md](subtasks/02-move-ajax-register-pipeline.md) | Перенос `AjaxRegister*` и точки входа |
| 03 | [subtasks/03-extract-company-crm-service.md](subtasks/03-extract-company-crm-service.md) | Выделение сервиса компании из `RegisterUserCompany` |
| 04 | [subtasks/04-config-and-n8n-transport-boundary.md](subtasks/04-config-and-n8n-transport-boundary.md) | **Wave 2:** перенос `CrmRegistrationN8nTransport` + DTO + helper; граница конфига |
| 05 | [subtasks/05-events-and-backward-compat.md](subtasks/05-events-and-backward-compat.md) | События main, aliases, deprecation |
| 06 | [subtasks/06-qa-rollout.md](subtasks/06-qa-rollout.md) | Регресс, выкат, откат |

---

## Next steps for Team Lead

### Wave 2 (small & safe) — parallel work packages

- **Package A — Code move + BC shims (dev)**: вынести 3 класса (transport + DTO + helper) в `eklektika.b24.registration` и добавить guarded `class_alias` со старых FQCN на новые.
- **Package B — Grep inventory (dev/tech writer)**: собрать все usages старых FQCN (`CrmRegistrationN8nTransport`, `CrmRegistrationN8nPrecheckResponse`, `AjaxRegisterCrmContactPrecheck`) и внешние точки входа (ajax, события) — список в подзадачу 04/05.
- **Package C — QA smoke (QA)**: проверить, что ранний CRM-precheck (unique contact) и ИНН-precheck не изменились по ответам/ошибкам; убедиться, что старые FQCN работают при отключённом usersync (если такое допустимо) и при обычной цепочке require.

1. Распараллелить **01** (скелет) и **04** (контракт конфига/транспорта) — низкая связность.  
2. После merge **01**: **02** и **03** вести в отдельных ветках с ежедневным rebase на `main`.  
3. **05** только после прохождения интеграционных тестов **02+03**.  
4. Перед production — обязательно **06** и согласование с владельцем n8n workflow.

---

## Audit (Team Lead, волна 1)

- **ОК:** Каркас не регистрирует классы — дубликатов автозагрузки нет; порядок `usersync` → `registration` корректен, пока код живёт в usersync.
- **Замечание:** После переноса классов из **02** проверить, что не остаётся двух записей одного FQCN в разных `include.php`.
- **Замечание:** Зафиксировать список grep по `RegisterUserCompany` и `AjaxRegister` перед **03–05** для чеклиста BC.
