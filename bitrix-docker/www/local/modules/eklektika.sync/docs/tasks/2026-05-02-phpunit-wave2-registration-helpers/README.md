# Task: PHPUnit wave 2 — registration helpers (parser + webhook message format)

## Инициатива

Расширить unit-покрытие модуля `eklektika.b24.registration` без поднятия Bitrix: статические хелперы парсера формы и форматирование ошибки n8n-транспорта.

## Связанные артефакты

- ADR: `../../adr/2026-05-02-phpunit-wave2-registration-helpers.md`
- Per-module convention: `../../adr/2026-05-02-phpunit-per-module-convention.md`
- Реестр: `../../../../../docs/reference/phpunit-test-inventory.md`

## Subtasks

- [x] `S1` `AjaxRegisterPostParser` — `normalizeInn`, `collectMissingRequiredFields`
- [x] `S2` `CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage`

## Статус

- Статус: `done`
- Последнее обновление: `2026-05-02`

## Next steps for Team Lead

- По желанию: фейк `Bitrix\Main\Request` или вынос логики из `parse()` в pure-функцию для тестов.
- Следующая волна по матрице: `eklektika.b24.rest` / `usersync` (выделение pure-хелперов).

## Audit (team lead), 2026-05-02

- Добавлены 11 новых тестов в suite `eklektika.b24.registration`; HTTP/post по-прежнему вне scope.

Wave 2 по helper-покрытию регистрации закрыт: решения и границы зафиксированы в ADR `../../adr/2026-05-02-phpunit-wave2-registration-helpers.md`, а общий инвентарь текущего unit-контура закреплен в реестре `../../../../../docs/reference/phpunit-test-inventory.md` и составляет **39 tests** (27 `eklektika.b24.registration` + 12 `eklektika.sync`).
