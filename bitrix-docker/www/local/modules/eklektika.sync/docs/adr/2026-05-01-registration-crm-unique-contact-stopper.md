# ADR: инцидент регистрации — выполнение останавливалось после unique-contact webhook

- **Статус:** принято (фикс внедрён; требуется верификация на окружении)
- **Дата:** 2026-05-01
- **Контекст:** eklektika-ru (1C‑Bitrix). Интеграция с CRM через n8n webhooks. Фокус — registration pipeline в `eklektika.b24.usersync` (`RegisterUserCompany`) и pre-check webhook `registration_webhook_unique_url` (`/registration/crm-check-unique-contact-v1`).

## Инцидент (что произошло)

Сценарий регистрации с включённым CRM pre-check выполнял webhook проверки уникальности контакта (`crm-check-unique-contact-v1`), после чего **цепочка регистрации прекращалась**, и **последующие CRM webhooks/операции не выполнялись** (в т.ч. следующие проверки/создания сущностей, которые запускаются в процессе регистрации).

## Влияние (impact)

- **Пользовательский эффект:** регистрация “ломалась” после успешного unique-contact pre-check: дальнейшие шаги регистрации/синхронизации с CRM не происходили.
- **Интеграционный эффект:** нагрузка/логи n8n показывали успешный вызов unique-contact, но downstream действия из сайта отсутствовали.
- **Операционный эффект:** диагностика осложнялась тем, что pre-check webhook был успешным, а сбой происходил “после”, в PHP-процессе.

## Root Cause (первопричина)

В `local/modules/eklektika.b24.usersync/lib/RegisterUserCompany.php` внутри `private function crmAddContact(array $payload)` присутствовал **безусловный debug‑stop**:

- `pre($payload);`
- `die();`

Это приводило к немедленному завершению выполнения PHP (halt), из‑за чего любые дальнейшие шаги после вызова `crmAddContact()` не выполнялись.

## Исправление (fix)

- Удалены строки `pre($payload); die();` из `RegisterUserCompany::crmAddContact()`.

## Что остаётся (environment verification)

1) **Подтвердить downstream после unique-contact** на целевом окружении:
- по execution traces в n8n (есть ли вызовы после `/registration/crm-check-unique-contact-v1`);
- по артефактам сайта в `local/logs/async-register-*.log` (и связанным логам/метрикам, если включены).

2) **Зафиксировать один успешный коррелированный прогон** (ID/временное окно/ключ корреляции), чтобы можно было повторно проверить регрессию.

## Почему это проявлялось именно “после” `crm-check-unique-contact-v1`

Unique-contact pre-check (`registration_webhook_unique_url`) выполняется **раньше** в регистрационном конвейере. После успешного pre-check поток продолжал выполнение до участка, где вызывался `crmAddContact()`, и там **останавливался** из‑за debug‑stop.

## Prevent / Follow-ups (как предотвратить повтор)

### 1) Аудит и запрет безусловных debug-stop в прод‑коде

Подлежит аудиту и/или автоматизации (линтер/CI guard):

- `pre(...)` без feature‑flag / env‑guard
- `die(...)`, `exit`, `dd(...)`, `var_dump(...)`, `print_r(...)` в коде, который может выполняться в runtime
- “брейкпоинты” в стиле `SyncPrimitiveBreakpoint` — только за явным флагом/окружением и с безопасным поведением по умолчанию

Статус desk-аудита по `die()`:
- оставшиеся использования `die()` относятся к include-guard (стандартные для Bitrix) **или** ограждены debug-флагами (`sync_debug` / breakpoint step) и не являются безусловным halt в registration runtime.

### 2) Наблюдаемость для “mid-flight” остановок

- В регистрационном пайплайне (особенно вокруг CRM create/bind участков) важно иметь корреляционный id и финальную “точку завершения” шага (например, структурированный лог/метрика уровня “registration.step.completed”), чтобы детектировать остановку внутри PHP без явной ошибки n8n.

## Связанные документы

- ADR: `modules/eklektika.sync/docs/adr/2026-04-30-site-crm-via-n8n-registration.md`
- ADR: `modules/eklektika.sync/docs/adr/2026-05-01-b24-usersync-registration-module-extraction.md`
- Runbook: `modules/eklektika.sync/docs/runbook-registration-crm-webhooks.md`

