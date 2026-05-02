# ADR: PHPUnit wave 2 — хелперы регистрации без Bitrix Request / HTTP

## Статус

Accepted (2026-05-02).

## Контекст

После wave 1 оставалась непрозрачная зона: парсер POST и текст ошибок n8n-транспорта не были покрыты unit-тестами, хотя содержат проверяемую логику на массивах и строках.

## Решение

1. Покрыть `AjaxRegisterPostParser::normalizeInn` и `collectMissingRequiredFields` (без `parse(Request)` — нужен тестовый double Bitrix Request).
2. Покрыть публичный статический `CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage` (без `post()` и HttpClient).

## Риски

- `parse()` остаётся без unit-тестов до появления фейкового `Request`.
- Формат сообщения при изменении `formatRegistrationWebhookFailureMessage` потребует обновления тестов (ожидаемо).

## Связанные артефакты

- Задача: `../tasks/2026-05-02-phpunit-wave2-registration-helpers/README.md`
- Реестр тестов: `../../../../docs/reference/phpunit-test-inventory.md`
