# Task: PHPUnit wave 3 — `eklektika.b24.rest`

## Инициатива

Добавить первые unit-тесты для модуля REST/n8n-транспорта без пересечения PSR-4 с `OnlineService\B24\Registration`.

## Связанные артефакты

- ADR: `../../adr/2026-05-02-phpunit-wave3-b24-rest.md`
- Реестр: `../../../../../docs/reference/phpunit-test-inventory.md`

## Subtasks

- [x] `S1` `composer.json`: `classmap` для `modules/eklektika.b24.rest/lib`
- [x] `S2` Тесты `N8nCrmGatewayEarlyExitTest`, `RestTransportConfigTest`
- [x] `S3` `phpunit.xml` suite `eklektika.b24.rest`, coverage path

## Статус

- Статус: `done`
- Последнее обновление: `2026-05-02`

## Next steps for Team Lead

- Интеграционные сценарии `callRestMethodWithWebhookUrl` с mock HTTP вне scope текущего unit-контура.
- Следующий модуль по матрице: `eklektika.b24.usersync` (выделение pure-хелперов).

## Audit (team lead), 2026-05-02

- Автозагрузка через classmap исключает коллизию с registration; тесты не трогают сеть при двух ранних выходах.
