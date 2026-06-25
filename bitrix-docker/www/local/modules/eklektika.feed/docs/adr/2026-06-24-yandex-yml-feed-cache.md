# ADR-2026-06-24: Предгенерация YML-фида

## Status

accepted

## Context

On-the-fly генерация `/feed/yandex.yml` занимала ~30 минут — неприемлемо для публичного URL.

## Decision

1. Генератор пишет XML атомарно в `local/cache/eklektika.feed/yandex.yml`.
2. Публичный endpoint отдаёт файл через `readfile` (без генерации).
3. Обновление:
   - CLI: `tools/regenerate_yandex_yml.php` (cron)
   - HTTP: `public/regenerate_yandex_yml.php?token=...` (опционально)
4. Конфиг: `local/php_interface/feed_integration_config.php` (`site_base_url`, `regenerate_token`).

## Consequences

- До первого cron-прогона URL вернёт 503.
- Актуальность фида = расписание cron.
- Оптимизация скорости генерации — отдельная задача.

## Links

- `docs/cron.md`
- `tasks/subtasks/next-steps.md`
