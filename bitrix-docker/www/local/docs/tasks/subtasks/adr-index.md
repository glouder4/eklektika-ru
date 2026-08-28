# ADR Index (сайт `local/docs`)

Корень оркестрации: `bitrix-docker/www/local/docs/tasks/subtasks/`.  
Корень ADR сайта: `bitrix-docker/www/local/docs/adr/`.

Индекс создан 2026-08-27 (ранее файлы `tasks/subtasks/*` в репозитории отсутствовали). Модульные ADR (`eklektika.sync`, `eklektika.feed`) сюда не входят.

| Дата | ADR | Статус | Цикл |
|------|-----|--------|------|
| 2026-08-27 | [clients-cases-iblock](../../adr/2026-08-27-clients-cases-iblock.md) | **Accepted**, код реализован (CASE-01); residual CASE-02 live-smoke | **текущий** — `/o-kompanii/clients/` из ИБ 29; цикл не закрыт |
| 2026-08-27 | [reviews-iblock](../../adr/2026-08-27-reviews-iblock.md) | **Accepted**, код реализован (REV-01); residual REV-02 live-smoke | открыт residual — `/otzyvy/` из ИБ 28; PHP не трогать (SORT ASC уже в коде) |
| 2026-08-26 | [main-service-block-iblock](../../adr/2026-08-26-main-service-block-iblock.md) | Accepted; код SVC-01 DONE; live-smoke blocked | открыт (контент owner / стенд) |
| 2026-08-26 | [main-popular-block-iblock](../../adr/2026-08-26-main-popular-block-iblock.md) | Accepted; код POP-01/02 DONE; live-smoke blocked | открыт (контент owner / стенд) |
| 2026-08-18 | [custom-pages-iblock-sef](../../adr/2026-08-18-custom-pages-iblock-sef.md) | Accepted; Wave A в коде | визуал шаблона — следующая итерация |
| 2026-08-10 | [mobile-size-card-ux](../../adr/2026-08-10-mobile-size-card-ux.md) | см. файл | — |
| 2026-08-08 | [product-color-size-selector](../../adr/2026-08-08-product-color-size-selector.md) | см. файл | — |
| 2026-07-16 | [password-recovery](../../adr/2026-07-16-password-recovery.md) | см. файл | — |
| 2026-05-08 | [order-json-naneseniya](../../adr/2026-05-08-order-json-naneseniya.md) | см. файл | — |

## Текущий цикл

`2026-08-27-clients-cases-iblock`: страница кейсов из ИБ 29. Код CASE-01 DONE (аудит AC1–AC6 по коду PASS). Residual: owner-контент ИБ 29 + CASE-02 после подъёма стенда (`127.0.0.1:8588`). Source of truth — ADR + `tasks/2026-08-27-clients-cases-iblock/README.md` + `next-steps.md`.

Сортировка списков: ИБ 28 и ИБ 29 — `SORT ASC` (затем `ID ASC`). Для ИБ 28 уже в коде; PHP отзывов не открывать.

## Residual (не закрывать)

`2026-08-27-reviews-iblock`: REV-01 DONE. Residual REV-02 live-smoke после стенда `127.0.0.1:8588`. Цикл отзывов не закрывать; ADR не ставить в Implemented.
