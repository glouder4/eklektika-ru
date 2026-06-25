# ADR-2026-06-24: Оптимизация скорости генерации YML

## Status

accepted

## Context

CLI `regenerate_yandex_yml.php` падал на чанке #1 с `Call to a member function getRequest() on null`.
Причина: урезанный bootstrap без `initializeExtendedKernel()` — `Context::getCurrent()` возвращал null,
а `CIBlockElement::GetNext()` требует HTTP Context.

Дополнительно: `CFile::GetPath()` в цикле по тысячам file ID давал N+1.

## Decision

1. `FeedCliBootstrap::ensureHttpContext()` — `HttpApplication::initializeExtendedKernel()` без `start()` (обход sproduction.integration).
2. В batch-loader: `Fetch()` вместо `GetNext()`; URL товаров — через `resolveDetailPageUrl()`.
3. `resolveFileSrcMap`: пакетный SELECT из `b_file`, fallback на `CFile::GetPath`.

## Consequences

- CLI и HTTP-regenerate используют один bootstrap-путь для context.
- Пути картинок совпадают с `CFile` для стандартных upload-файлов.
- Полный замер на проде — после деплоя.

## Links

- `tasks/subtasks/progress-checklist.md`
- `lib/Bootstrap/FeedCliBootstrap.php`
- `lib/Yml/FeedCatalogBatchLoader.php`
