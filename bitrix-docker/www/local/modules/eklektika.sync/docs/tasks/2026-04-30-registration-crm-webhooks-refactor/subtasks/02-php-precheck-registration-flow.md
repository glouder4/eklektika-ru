# S2: PHP pre-check слой регистрации

- Главная задача: `docs/tasks/2026-04-30-registration-crm-webhooks-refactor/README.md`
- Статус: `done`

## Цель

Отделить синхронные проверки уникальности от этапа локальной регистрации и убрать зависимость успеха регистрации от немедленного получения CRM ID.

## Входы

- Контракты webhook из `S1`.
- Текущие обработчики `OnBeforeUserRegisterHandler`/`OnAfterUserRegisterHandler`.

## Выходы

- Обновленный поток регистрации: sync pre-check -> local success.
- Явная обработка бизнес-ошибок pre-check.

## Зависимости

- Завершение `S1` *(достигнут минимально необходимый уровень для PHP; см. S1 caveat)*.

## Риски

- Регрессии в пользовательских ошибках валидации при регистрации.

## Индивидуальные критерии готовности

1. Синхронно в `OnBeforeUserRegisterHandler` вызывается `runSyncPreCheck` (email/телефон в CRM, пароль, ЮЛ поля).
2. Поведение `sync_legacy` сохранено для обратной совместимости.
3. Интеграционные регрессии — в зоне ответственности QA перед prod (матрица runbook §5).

## Чеклист проверки результата

- [x] Обновлена последовательность: pre-check изолирован в `OnBeforeUserRegisterHandler` (`RegisterUserCompany.php`).
- [x] Сохранён feature toggle `sync_legacy` через `config.local.php`.
- [ ] Отдельный sequence diagram в репозитории — опционально; актуальный источник правды — ADR «Сверка с реализацией» + runbook.

## Артефакты

- `modules/eklektika.b24.usersync/lib/RegisterUserCompany.php` (`runSyncPreCheck`, `OnBeforeUserRegisterHandler`)
- `modules/eklektika.sync/config.local.php` *(пример ключей toggle)*

## Tech Lead

- Подзадача закрыта по коду rework: **2026-04-30**.
