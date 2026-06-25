# ADR-2026-06-24: Оптимизация генерации YML-фида

## Status

accepted

## Context

Предгенерация фида занимала ~30 минут из-за N+1: на каждый оффер отдельно вызывались `CCatalogSku::GetProductInfo`, `getCatalogPriceDiscount`, `CIBlockElement::GetProperty`, `CCatalogProduct::GetByID`, `CFile::GetFileArray`.

## Decision

1. `FeedCatalogBatchLoader`:
   - один `GetList` офферов с `PROPERTY_CML2_LINK` и текстовыми свойствами;
   - `PriceTable::getList` чанками по всем offer/parent IDs;
   - `ProductTable::getList` для наличия;
   - `ElementPropertyTable` для файловых свойств MORE_PHOTO/PHOTOS;
   - `CFile::GetList` батчами для SRC картинок.
2. `FeedOfferPriceResolver` — расчёт MAIN/OLD из предзагруженных цен (CLI без скидки компании).
3. Товары: один проход `GetList` + батч файловых свойств.

## Consequences

- Логика цен для cron совпадает с анонимной витриной (без company tier).
- Публичный URL по-прежнему отдаёт только кэш-файл.

## Links

- `docs/adr/2026-06-24-yandex-yml-feed-cache.md`
