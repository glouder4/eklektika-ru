# ADR: конвенция PHPUnit — отдельный комплект тестов у каждого модуля в `local/modules/*`

## Статус

Accepted (2026-05-02).

## Контекст

Первый шаг PHPUnit жил в `local/tests/Unit` и не делал явной связи «модуль → тесты». В `local/modules/` несколько самостоятельных модулей с разным доменом (registration, sync, usersync, REST, каталог и т.д.). Для надёжности и прозрачности CI нужна **одинаковая структура**: у каждого модуля свой каталог тестов рядом с кодом.

## Решение

1. **Расположение:** `local/modules/<module.id>/tests/Unit/` — только unit (без Bitrix bootstrap). Интеграционные тесты позже: отдельный bootstrap и suite.
2. **Пространства имён тестов:** `Eklektika\Tests\<ModuleCamel>\`, где `<ModuleCamel>` — идентификатор модуля без точек в стиле PascalCase, например:
   - `eklektika.b24.registration` → `Eklektika\Tests\EklektikaB24Registration\`
   - `eklektika.sync` → `Eklektika\Tests\EklektikaSync\`
3. **Регистрация в Composer:** в `local/composer.json` для каждого модуля с тестами добавляется запись `autoload-dev` `psr-4` на `modules/<id>/tests/Unit`.
4. **Корневой `phpunit.xml`:** для каждого модуля с тестами — **отдельный** `<testsuite name="<module.id>">`; один и тот же каталог **не** включается во второй suite (иначе PHPUnit 11 дублирует файлы и даёт предупреждения при `failOnWarning`). Полный прогон: `phpunit` без `--testsuite` (выполняются все перечисленные suite). Выборочно: `--testsuite eklektika.sync`.
5. **Учёт покрытия:** блок `<source><include>` расширяется по мере появления тестируемых путей в модулях (без обязательного покрытия всего `lib/` на старте).

## Последствия

- Видно из дерева файлов, какой модуль уже защищён тестами.
- Появление нового модуля = добавить строку в `composer.json`, каталог `tests/Unit` и (при необходимости) `<testsuite>` в `phpunit.xml`.
- Если namespace модуля является **префиксом** другого модуля (пример: `OnlineService\B24\` у `eklektika.b24.rest` vs `OnlineService\B24\Registration` у регистрации), для автозагрузки использовать **`classmap` по каталогу `lib`**, а не широкий PSR-4 — см. ADR `2026-05-02-phpunit-wave3-b24-rest.md`.
- Дублирование конфигурации контролируется матрицей `MODULE_TEST_MATRIX.md` в задаче инициативы.

## Связанные артефакты

- Предыдущий фундамент: `../adr/2026-05-02-phpunit-local-reliability.md`
- Матрица модулей: `../tasks/2026-05-02-phpunit-per-module-convention/MODULE_TEST_MATRIX.md`
- Расшифровка текущих тестов (что входит в «N tests»): `../../../../docs/reference/phpunit-test-inventory.md`
- Задача: `../tasks/2026-05-02-phpunit-per-module-convention/README.md`
