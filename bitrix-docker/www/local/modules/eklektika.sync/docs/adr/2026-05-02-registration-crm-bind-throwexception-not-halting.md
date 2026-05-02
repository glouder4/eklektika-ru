# ADR: Ошибка CRM при привязке контакта не останавливала регистрацию

## Статус

Принято (исправление).

## Проблема

`CAllMain::ThrowException()` **не** прерывает выполнение PHP. После сбоя `crm.contact.company.add` / `crm.company.contact.add` вызывался `ThrowException`, но `createB24Company` продолжал работу, вызывал `upsertSiteCompanyLinkByB24Id` и возвращал `true` — в JSON регистрация выглядела успешной, хотя n8n-вебхуки завершались с ошибкой.

Дополнительно проверка `crm.company.update` смотрела только на `success === 0`, а не на полный контракт `isB24RestFailure` (в т.ч. поле `error` без `success`).

## Решение

- `bindContactToCompany` и `upsertSiteCompanyLinkByB24Id` возвращают `bool`; при сбое — `return false` после `ThrowException`.
- `createB24Company` после привязки и UF-update проверяет `false` и **возвращает `false`**, чтобы `syncFromSiteRegistration` и ajax-конвейер откатили пользователя.
- `crm.company.update`: ошибка — если `isB24RestFailure($result)`.

## Связанные документы

- `CrmRegistrationOrchestrator::createB24Company`
