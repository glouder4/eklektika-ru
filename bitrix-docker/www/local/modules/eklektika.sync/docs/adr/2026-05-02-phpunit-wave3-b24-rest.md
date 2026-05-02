# ADR: PHPUnit wave 3 — `eklektika.b24.rest` (ранние выходы N8n + URL вебхуков)

## Статус

Accepted (2026-05-02).

## Контекст

По матрице модулей `eklektika.b24.rest` был без `tests/Unit`. Прямой PSR-4 `OnlineService\B24\` → `rest/lib` **запрещён**: пересекается с namespace модуля регистрации (`OnlineService\B24\Registration\...`), Composer резолвил бы классы регистрации в неверный путь.

## Решение

1. Подключить код модуля в dev-autoload через **`classmap` на `modules/eklektika.b24.rest/lib`**, PSR-4 оставить для более длинных префиксов (`Registration\AjaxRegister`, тесты).
2. Тесты:
   - `N8nCrmGateway::callRestMethodWithWebhookUrl` — пустой URL; падение `json_encode` **до** `curl` (resource в params).
   - `RestTransportConfig::buildMainWebhookMethodUrl` / `buildKitWebhookPrefix` — константы `URL_B24` / `B24_REST_WEBHOOK_*` задаются в `setUpBeforeClass` теста (как в рантайме Bitrix).

## Риски

- Константы, определённые в тесте, не отражают реальный портал — проверяется только **шаблон** сборки URL.
- Полный путь `callRestMethodWithWebhookUrl` с успешным curl в unit не входит (интеграция).

## Связанные артефакты

- Задача: `../tasks/2026-05-02-phpunit-wave3-b24-rest/README.md`
- Реестр: `../../../../docs/reference/phpunit-test-inventory.md`
