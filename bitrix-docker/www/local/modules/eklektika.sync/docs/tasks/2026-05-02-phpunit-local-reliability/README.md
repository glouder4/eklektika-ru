# Task: PHPUnit для reliability в `local/` (WSL PHP 8.2)

## Инициатива

Ввести минимально достаточный и воспроизводимый тестовый контур на PHPUnit в `local/`, чтобы снизить риск регрессий в регистрационном n8n/CRM потоке и ускорить проверку изменений до интеграционного уровня.

## Контекст / область

- Зона внедрения: `local/` (без выхода за пределы рабочего scope).
- Фокус домена: registration n8n cluster (`CrmRegistrationN8nPrecheckResponse`, `CrmRegistrationN8nTransport`, `AjaxRegisterCrmContactPrecheck`).
- Базовая среда запуска: WSL + PHP 8.2.
- Инфраструктура: `local/composer.json` + `composer.lock`, suite в `phpunit.xml`.

## Связанные артефакты

- ADR решения: `../adr/2026-05-02-phpunit-local-reliability.md`
- Process source of truth: `../../../../docs/business-processes/registration/README.md`
- Reference source of truth: `../../../../docs/reference/registration-n8n-webhooks.md`

## План внедрения (milestones)

1. **M1 — Bootstrap тестовой инфраструктуры**
   - Результат: в `local/` появились `composer.json`, `phpunit.xml`, базовая структура `tests/`.
2. **M2 — Первая unit-волна по registration precheck**
   - Результат: есть первые unit-тесты для `CrmRegistrationN8nPrecheckResponse` и зеленый локальный прогон.
3. **M3 — Стандартизованный запуск в WSL/CI**
   - Результат: зафиксированы инструкции запуска и минимальные CI-ожидания.
4. **M4 — Graphify wave + backlog integration phase**
   - Результат: зафиксирована graph-backed карта следующей волны (transport/precheck связки + Bitrix bootstrap для integration).

## Декомпозиция на подзадачи

- `S1` Bootstrap PHPUnit в `local/` — `subtasks/01-bootstrap-phpunit-in-local.md`
- `S2` Первые unit-тесты `CrmRegistrationN8nPrecheckResponse` — `subtasks/02-first-unit-tests-precheck-response.md`
- `S3` WSL/CI run-инструкции — `subtasks/03-wsl-ci-run-instructions.md`
- `S4` Graphify wave note (следующая волна покрытия) — `subtasks/04-graphify-wave-note.md`

## Checklist subtasks

- [x] `S1` Выполнен bootstrap тестовой инфраструктуры в `local/`
- [x] `S2` Добавлены первые unit-тесты `CrmRegistrationN8nPrecheckResponse` (**верифицировано:** 16/16, PHPUnit 11.5.55, PHP 8.2.30)
- [x] `S3` Зафиксированы run-команды — см. `subtasks/03-wsl-ci-run-instructions.md`
- [x] `S4` Graphify wave — см. `subtasks/04-graphify-wave-note.md`

## Общие критерии готовности (Definition of Done)

1. В `local/` есть рабочая PHPUnit-конфигурация, запускаемая в WSL PHP 8.2.
2. Первая unit-волна покрывает минимум `CrmRegistrationN8nPrecheckResponse` и воспроизводимо проходит локально.
3. Команда имеет единый runbook запуска (локально + CI) без неявных шагов.
4. План integration-фазы (с Bitrix bootstrap) зафиксирован отдельно и не блокирует unit feedback loop.

## Статус и прогресс

- Статус: `closed` (верификация WSL + lock в репозитории)
- Прогресс: `4/4 subtasks`
- Последнее обновление: `2026-05-02`

## Audit (team lead), 2026-05-02 (повторная верификация)

- **S1/S2:** зелёный прогон подтверждён (16/16, PHPUnit 11.5.55, PHP 8.2.30, конфиг `local/phpunit.xml`). Блокер по `composer.lock` снят — файл присутствует в `local/`.
- **S3:** runbook актуален; следующий шаг — при необходимости подключить тот же сценарий в CI.
- **S4:** roadmap второй волны без изменений до старта покрытия transport/orchestrator.

## Next steps for Tech Lead

- Опционально: добавить job в CI (`composer install` + `composer test` из `local/`).
- Следующая инициатива покрытия: unit-тесты для pure/near-pure логики `CrmRegistrationN8nTransport` и контрактные кейсы по `docs/reference/registration-n8n-webhooks.md` (см. S4).
