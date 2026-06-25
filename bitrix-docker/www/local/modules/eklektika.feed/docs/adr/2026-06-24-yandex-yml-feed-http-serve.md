# ADR-2026-06-24: HTTP-отдача YML без prolog_before

## Status

accepted

## Context

CLI-регенерация создаёт `local/cache/eklektika.feed/yandex.yml` (~117 MB, десятки тысяч офферов), но публичный URL `/feed/yandex.yml/` возвращал пустой body.

Причина: `yandex-yml.php` подключал `prolog_before.php`, а при ЧПУ через `urlrewrite.php` Bitrix уже инициализирует OB до включения entrypoint. Модуль `sproduction.integration` и стандартный Bitrix output buffering перехватывают вывод `readfile()` — заголовки `Content-Type`/`Content-Length` уходят, тело пустое.

## Decision

1. Публичная отдача **не** вызывает `prolog_before.php`.
2. Для `YandexYmlFeedStorage` достаточно `include.php` с `B_PROLOG_INCLUDED` (без полного kernel).
3. `include.php` при отсутствии `Bitrix\Main\Loader` регистрирует классы через прямые `require_once` (lite-режим для serve).
4. `FeedHttpServe` очищает все OB-уровни перед заголовками/`readfile`, после успешной отдачи — `exit`.
5. При отсутствии файла — 503 + `Retry-After` (без изменений).

## Consequences

- `/feed/yandex.yml` отдаёт бинарно идентичный кэш без повторной генерации.
- `exit` после `readfile` предотвращает epilog/дополнительный вывод Bitrix.
- Генерация и HTTP-regenerate продолжают использовать `FeedCliBootstrap` (полный kernel).

## Links

- `docs/adr/2026-06-24-yandex-yml-feed-cache.md`
- `lib/Http/FeedHttpServe.php`
- `public/yandex-yml.php`
