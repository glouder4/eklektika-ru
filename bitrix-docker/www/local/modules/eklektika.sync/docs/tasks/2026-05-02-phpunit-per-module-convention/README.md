# Task: PHPUnit — конвенция «тесты внутри каждого модуля»

## Инициатива

Сделать явную и масштабируемую привязку **один модуль = свой каталог unit-тестов** в `local/modules/<id>/tests/Unit`, с регистрацией в `composer.json` и отдельными testsuite в `phpunit.xml`.

## Связанные артефакты

- ADR: `../../adr/2026-05-02-phpunit-per-module-convention.md`
- Матрица: `MODULE_TEST_MATRIX.md`
- Базовый ADR PHPUnit: `../../adr/2026-05-02-phpunit-local-reliability.md`

## Subtasks

- `S1` Инвентарь и матрица — `subtasks/01-inventory-matrix.md`
- `S2` Миграция тестов registration + обновление bootstrap/phpunit — `subtasks/02-migrate-registration-and-config.md`
- `S3` Первая волна по второму модулю (`eklektika.sync`) — `subtasks/03-sync-unit-wave.md`

## Checklist

- [x] Матрица модулей и конвенция имён задокументированы
- [x] Тесты `eklektika.b24.registration` перенесены в модуль
- [x] Добавлены unit-тесты для `CrmInboundUfMap` (`eklektika.sync`)
- [x] `composer.json` / `phpunit.xml` отражают несколько модулей и отдельные suite

## Статус

- Статус: `done`
- Прогресс: `3/3 subtasks`
- Последнее обновление: `2026-05-02`

## Next steps for Team Lead

- По матрице последовательно подключать остальные модули (usersync/rest/site/…) — сначала вынос pure-логики или тестовые дублёры Bitrix.
- Рассмотреть CI matrix job с `--testsuite <module>` для параллели.

## Audit (team lead), 2026-05-02

- Конвенция соблюдена: тесты лежат рядом с модулями, suite именованы по module id.
- Остаточный риск: модули без каталога `tests/Unit` не попадают в прогон — это ожидаемо до появления тестов.
