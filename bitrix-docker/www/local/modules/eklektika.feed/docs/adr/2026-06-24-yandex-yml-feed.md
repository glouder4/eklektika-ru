# ADR-2026-06-24: YML-фид каталога для Яндекс Директ

## Status

accepted

## Context

Нужен YML-фид товарной базы для загрузки в Яндекс Директ по [требованиям YML](https://yandex.ru/support/direct/ru/feeds/requirements-yml). Пользователь открывает прямую ссылку и получает актуальный XML.

Каталог: IBLOCK 13 (товары), IBLOCK 14 (ТП). Цены на витрине — рекламная (тип 3) и оптовая база (тип 2) через `getCatalogPriceDiscount`.

## Decision

1. Новый модуль `eklektika.feed` в `local/modules` (scope graphify: только `local`, без `templates`).
2. Генератор `YandexYmlFeedGenerator` формирует упрощённые `<offer>` с обязательными полями Директа.
3. Публичный endpoint:
   - ЧПУ: `/feed/yandex.yml` → отдача предсгенерированного файла
   - Кэш: `local/cache/eklektika.feed/yandex.yml`
   - Регенерация: CLI `tools/regenerate_yandex_yml.php` + cron (`docs/cron.md`)
4. `YmlXml` — чистый класс для экранирования и рендера оферов; покрыт unit-тестами без Bitrix.

## Consequences

- Фид генерируется on-the-fly при каждом запросе (без кэша в MVP).
- Офферы без цены, картинки или категории пропускаются.
- `oldprice` выводится только при скидке ≥ 5% относительно оптовой базы.

## Links

- `tasks/subtasks/next-steps.md`
- `FeedConfig::PUBLIC_PATH`
