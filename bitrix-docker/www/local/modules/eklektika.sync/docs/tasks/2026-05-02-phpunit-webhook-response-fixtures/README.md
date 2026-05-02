# Task: JSON-фикстуры n8n и регрессионные mock-тесты

## Инициатива

Подготовить устойчивый контур: реальные ответы webhooks → обезличенные файлы → PHPUnit регрессия разбора без сети.

## Связанные артефакты

- ADR: `../../adr/2026-05-02-phpunit-webhook-response-fixtures.md`
- Playbook захвата: `../../../../../tests/fixtures/n8n-webhooks/README.md`
- Реестр тестов: `../../../../../docs/reference/phpunit-test-inventory.md`

## Subtasks

- [x] `S1` Структура `local/tests/fixtures/n8n-webhooks`, `.gitignore`, committed samples
- [x] `S2` Класс `RegistrationWebhookSampleRegressionTest` + подключение в suite registration

## Статус

- Статус: `done` (базовые образцы; новые прогоны добавляют файлы и кейсы по мере необходимости)
- Последнее обновление: `2026-05-02`

## Next steps for Team Lead

- После реальных прогонов: добавить файлы в `samples/*.anon.json` и расширить тесты (или DataProvider по каталогу).
- Дальше: фикстуры для тел после unwrap на уровне orchestrator потребуют отдельного bootstrap или выделения pure-функций.

## Audit (team lead), 2026-05-02

- Нет дублирования каталогов в нескольких testsuite; fixture-тесты входят только в `eklektika.b24.registration`.
