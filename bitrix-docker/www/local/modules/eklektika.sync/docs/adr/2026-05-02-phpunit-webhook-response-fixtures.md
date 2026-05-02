# ADR: регрессионные тесты по JSON-фикстурам ответов n8n webhooks

## Статус

Accepted (2026-05-02).

## Контекст

Юнит-тесты на «ручных» массивах не подключают реальные ответы стенда. Нужен воспроизводимый контур: сохранить ответ webhook после реального вызова, обезличить, закоммитить как `.anon.json`, прогонять те же проверки разбора, что использует PHP-код (`CrmRegistrationN8nPrecheckResponse` и далее по пайплайну).

## Решение

1. Каталог данных: `local/tests/fixtures/n8n-webhooks/samples/` — только обезличенные образцы в Git.
2. Локальные сырые ответы: `local/tests/fixtures/n8n-webhooks/captured/` — в `.gitignore`, пользователь чистит сам.
3. Тесты в `modules/eklektika.b24.registration/tests/WebhookFixtures/` читают JSON с диска и проверяют контракт через существующие статические методы (без HTTP и без Bitrix).
4. Расширение: после каждого нового реального прогона добавлять файл в `samples/` и метод/данные в тестах.

## Риски

- Фикстуры устаревают, если n8n меняет форму тела — тесты красные как сигнал рассинхрона с продом.
- Случайная утечка секретов при копировании в `samples/` — обязательна ручная редактура и чеклист в README фикстур.

## Связанные артефакты

- Playbook: `local/tests/fixtures/n8n-webhooks/README.md`
- Контракт: `local/docs/reference/registration-n8n-webhooks.md`
- Задача: `modules/eklektika.sync/docs/tasks/2026-05-02-phpunit-webhook-response-fixtures/README.md`
