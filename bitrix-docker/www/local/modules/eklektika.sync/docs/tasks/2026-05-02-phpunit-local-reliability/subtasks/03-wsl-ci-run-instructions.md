# S3: Инструкции запуска в WSL и CI

- Главная задача: `../README.md`
- Статус: `done`

## Предусловия

- PHP 8.2+ и Composer в PATH (рекомендуется нативный путь в WSL к репозиторию, не UNC как cwd для `cmd`).
- Рабочая директория: каталог `local/` внутри клона (`bitrix-docker/www/local`).

## WSL (разработчик)

```bash
cd /path/to/bitrix-docker/www/local
composer install --no-interaction
# после правок composer.json:
composer dump-autoload
composer test
# эквивалентно:
# ./vendor/bin/phpunit -c phpunit.xml
# только один модуль (см. phpunit.xml <testsuite name="...">):
# ./vendor/bin/phpunit -c phpunit.xml --testsuite eklektika.sync
# ./vendor/bin/phpunit -c phpunit.xml --testsuite eklektika.b24.registration
```

После первого успешного `composer install` **закоммитить** `composer.lock`, чтобы зафиксировать версии PHPUnit и зависимостей.

## Docker (опционально)

Контейнер `php` монтирует сайт в `/opt/www`. Если в образе есть Composer:

```bash
docker compose exec php sh -lc 'cd /opt/www/local && composer install --no-interaction && composer test'
```

## Минимальный CI job

```yaml
# пример: job только unit-suite
- run: cd bitrix-docker/www/local && composer install --no-interaction --prefer-dist
- run: cd bitrix-docker/www/local && composer test
```

Требования к runner: PHP 8.2+, расширения по умолчанию достаточны для текущих unit-тестов (без Bitrix).

## Если тесты падают

1. Убедиться, что `vendor/autoload.php` существует (`composer install`).
2. Запускать команды **из** `local/`, иначе относительные пути `phpunit.xml` сломаются.
3. Сообщение `Missing vendor/` в bootstrap — снова `composer install`.
4. `Class ... not found` для классов из `modules/*/lib` после правки `composer.json` — выполнить `composer dump-autoload` (bootstrap также подгружает `CrmInboundUfMap` как запасной вариант).
5. Ошибка PHPUnit `Comment must not contain '--'` — в XML-комментариях `phpunit.xml` нельзя использовать двойной дефис (`--`); переформулировать комментарий.
6. Composer ругается на Git «dubious ownership» — один раз: `git config --global --add safe.directory /path/to/eklektika-ru` (путь как у вас в WSL), либо выровнять владельца каталога репозитория.
7. Предупреждение «Do not run Composer as root» — по возможности запускать Composer от обычного пользователя WSL, не от root.

## Чеклист проверки результата

- [x] Документированы команды install/test.
- [x] Есть секция предусловий окружения.
- [x] Есть секция диагностики типовых ошибок.
