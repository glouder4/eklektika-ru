# Cron: YML-фид Яндекс Директ

## CLI (рекомендуется)

Из корня сайта (`www`):

```bash
php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php
```

С явным доменом для ссылок в фиде:

```bash
php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php --site-base-url=https://new.eklektika.ru
```

Прогресс пишется в stderr (чтобы stdout оставался чистым JSON). Без прогресса: `--quiet`.

Bootstrap для CLI использует `bitrix/modules/main/start.php` + только модуль `iblock` (без `catalog`, чтобы не подтягивать `sproduction.integration`). Цены и наличие читаются SQL-запросами к `b_catalog_price` / `b_catalog_product`.

Legacy-глобали (`USER_FIELD_MANAGER`, `APPLICATION`) инициализируются в `FeedCliBootstrap` — без полного `include.php` / `init.php`.

Конфиг `site_base_url` можно задать в `local/php_interface/feed_integration_config.php` (см. `feed_integration_config.example.php`).

## Crontab

Пример — каждые 6 часов:

```cron
0 */6 * * * cd /var/www/eklektika/www && /usr/bin/php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php >> /var/log/eklektika-yml-feed.log 2>&1
```

Первый запуск после деплоя — вручную (генерация может занять десятки минут).

## HTTP (альтернатива cron через curl)

1. Скопировать `local/php_interface/feed_integration_config.example.php` → `feed_integration_config.php`
2. Задать `regenerate_token`
3. Вызов:

```bash
curl -sS "https://new.eklektika.ru/local/modules/eklektika.feed/public/regenerate_yandex_yml.php?token=YOUR_TOKEN"
```

## Публичная отдача

`/feed/yandex.yml` — только чтение готового файла:

`local/cache/eklektika.feed/yandex.yml`

Если файла нет — HTTP 503 и подсказка запустить CLI.
