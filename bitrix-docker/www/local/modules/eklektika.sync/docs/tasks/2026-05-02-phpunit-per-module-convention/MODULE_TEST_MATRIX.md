# Матрица: модули `local/modules/*` ↔ PHPUnit

Легенда: **есть unit-тесты** | **запланировано** | **нет тестов / только интеграции позже**

Перечень текущих кейсов и смысл «28 tests»: `../../../../../docs/reference/phpunit-test-inventory.md` (от корня `local/`).

| Module ID | Каталог тестов | Статус | Примечание |
|-----------|----------------|--------|------------|
| `eklektika.b24.registration` | `modules/eklektika.b24.registration/tests/Unit` + `tests/WebhookFixtures` | есть | Unit + JSON-фикстуры n8n (`local/tests/fixtures/n8n-webhooks/`); см. wave2 ADR, fixtures ADR |
| `eklektika.sync` | `modules/eklektika.sync/tests/Unit` | есть | `CrmInboundUfMap` и др. pure-классы из `lib/` |
| `eklektika.b24.usersync` | `modules/eklektika.b24.usersync/tests/Unit` | запланировано | тяжёлый Bitrix; выделять pure-хелперы |
| `eklektika.b24.rest` | `modules/eklektika.b24.rest/tests/Unit` | есть | ранние выходы `N8nCrmGateway`, сборка URL `RestTransportConfig`; см. ADR wave3 |
| `eklektika.site` | `modules/eklektika.site/tests/Unit` | запланировано | — |
| `eklektika.company` | `modules/eklektika.company/tests/Unit` | запланировано | — |
| `eklektika.orders.applications` | `modules/eklektika.orders.applications/tests/Unit` | запланировано | — |
| `eklektika.catalog.pricing` | `modules/eklektika.catalog.pricing/tests/Unit` | запланировано | — |
| `eklektika.catalog.import` | `modules/eklektika.catalog.import/tests/Unit` | запланировано | — |

## Как добавить модуль в раннер

1. Создать `modules/<id>/tests/Unit/`.
2. Добавить `psr-4` в `local/composer.json` (`Eklektika\Tests\<ModuleCamel>\` → путь выше).
3. Добавить отдельный `<testsuite name="<module.id>">` с одним `<directory>` в `local/phpunit.xml` (не дублировать каталог в нескольких suite).
4. При необходимости добавить `psr-4`/`classmap` для **кода** модуля, если ещё не покрыто автозагрузкой тестов.
