# ADR: PHPUnit в `local/` для повышения reliability

## Статус

Accepted (2026-05-02) — **закрыто по верификации:** в `local/` зафиксирован `composer.lock` (PHPUnit **11.5.55**), WSL-прогон `phpunit.xml` на **PHP 8.2.30** — **16/16** тестов, время порядка **0.005 s**, память **8 MB** (локальный regression-контур подтверждён).

## Контекст

- В рабочей области `local/` добавлены `composer.json`, `composer.lock` и стандартизованный unit-bootstrap (`tests/bootstrap.php`) для WSL PHP 8.2 без загрузки Bitrix.
- Регистрационный контур (site -> n8n -> CRM) содержит чистые PHP-классы, которые можно покрывать unit-тестами без поднятия полного Bitrix runtime.
- По Graphify preflight (`graphify-eklektika-ru-site`) registration n8n кластер связывает:
  - `CrmRegistrationN8nPrecheckResponse`
  - `CrmRegistrationN8nTransport`
  - `AjaxRegisterCrmContactPrecheck`
  Эти узлы находятся в общем community регистрации и образуют естественную первую волну тестового покрытия.

## Решение

1. Добавить `composer.json` в корень `local/` как тестовый dependency entrypoint (без изменения продуктового runtime-контракта модулей).
2. Добавить `phpunit.xml` в `local/` как единый профиль запуска тестов под WSL PHP 8.2.
3. Создать структуру `local/tests/` и начать с **unit-first** подхода для чистых классов:
   - приоритетно: `CrmRegistrationN8nPrecheckResponse`;
   - затем связанные pure/near-pure преобразователи и response parser'ы.
4. Интеграционные тесты вынести во вторую фазу:
   - отдельный bootstrap Bitrix;
   - явная сегрегация от unit-тестов по suite/group;
   - запуск только в подготовленном окружении.

## Последствия

- Появится быстрый regression-контур для ключевой логики регистрации/n8n без тяжелого окружения.
- Уменьшится риск скрытых поломок контрактов ответа (`success/result`) в precheck-ветке.
- Команда получит воспроизводимый WSL-раннер (`composer` + `phpunit.xml`) для локальной и CI-проверки.

## Риски

- Сильная связность с Bitrix API может затруднить изоляцию некоторых классов и привести к "ложным unit" тестам.
- Возможен рост техдолга bootstrap-слоя, если не удерживать четкую границу unit vs integration.
- Различия окружений (WSL/CI/production PHP extensions, include-path, кодировка) могут вызвать флаки при первом запуске.

## Ограничения и меры

- Не смешивать Bitrix bootstrap в первом unit-контуре.
- Для integration suite сразу определить отдельный launcher и правила запуска (не блокировать ими быстрый feedback-loop).
- Зафиксировать run-инструкции в task-документе и использовать единый путь запуска под WSL.

## Связанные артефакты

- Конвенция тестов по модулям (co-located): `../adr/2026-05-02-phpunit-per-module-convention.md`
- Главная задача: `../tasks/2026-05-02-phpunit-local-reliability/README.md`
- Канонический процесс регистрации: `../../../../docs/business-processes/registration/README.md`
- Справочник webhook-контрактов: `../../../../docs/reference/registration-n8n-webhooks.md`
