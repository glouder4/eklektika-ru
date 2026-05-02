# S3: Async enrichment и тихая дозапись CRM ID

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done`

## Цель

Реализовать асинхронный post-registration вызов webhook и тихую локальную дозапись `company_id/contact_id` после успешной регистрации.

## Входы

- Контракты `S1`.
- Новый поток регистрации из `S2`.

## Выходы

- Механизм публикации async задачи после local success.
- Обработчик результатов `company.add` и `contact.add` с локальным bind.

## Зависимости

- `S1`, `S2`.

## Риски

- Потеря async событий при transient ошибках транспорта.

## Индивидуальные критерии готовности

1. После апдейта UF контакта при `async_post_register=true` вызывается `runAsyncPostRegisterWebhook` (`OnAfterUserRegisterHandler`).
2. Ошибки доставки не откатывают локальную регистрацию; состояние и retry — в файловых логах (runbook §2).
3. Payload содержит `idempotency_key` и поля корреляции (`site_user_id`, email/phone, ids).

## Чеклист проверки результата

- [x] Correlation: `site_user_id` + стабильный `idempotency_key` из полей пользователя/contact.
- [x] Контракт payload задокументирован в коде (`buildAsyncPostRegisterPayload`) и runbook.
- [x] Сценарий деградации n8n: retry/dead-letter (runbook + реализация).

## Артефакты

- `modules/eklektika.b24.usersync/lib/RegisterUserCompany.php` (`buildAsyncPostRegisterPayload`, `runAsyncPostRegisterWebhook`, `OnAfterUserRegisterHandler`)
- `modules/eklektika.sync/config.local.php` (`async_post_register_webhook_url`)

## Tech Lead

- Подзадача закрыта по коду rework: **2026-04-30**.
