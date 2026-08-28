# Progress checklist

## Цикл 2026-08-27: кейсы `/o-kompanii/clients/` из ИБ 29

**Цель:** хардкод-сетка на `/o-kompanii/clients/` → `bitrix:news.list` / `clients`, `IBLOCK_ID=29`. Сортировка `SORT ASC` / `ID ASC` (как у отзывов ИБ 28).

**Критерии готовности:** AC1–AC6 (см. ADR). Код PASS. Live — после стенда.

**Зависимости:** активные элементы ИБ 29 с `CODE` и `PREVIEW_PICTURE` (owner). Локальный Bitrix docker на каноническом порту **8588**.

**Риски:** пустой ИБ → страница только с intro; путаница с ИБ 9 (`detail.php`) и шаблоном `our-clients`; smoke на `new.eklektika.ru` не покрывает локальный код.

| ID | Шаг | Статус |
|----|-----|--------|
| — | ADR Accepted | DONE (2026-08-27) |
| — | Инициатива README + оркестрация | DONE (2026-08-27) |
| CASE-01 | Шаблон `news.list/clients` + вызов в `o-kompanii/clients/index.php` | DONE (аудит 2026-08-27, AC1–AC6 по коду PASS) |
| CASE-02 | Smoke AC1–AC6 на `/o-kompanii/clients/` | BLOCKED: docker eklektika/bitrix не запущен; `127.0.0.1:8588` refused (оркестратор перепроверил curl); `new.eklektika.ru` невалиден для локальных файлов |
| — | Закрытие цикла / Lessons Learned | OPEN (после CASE-02; не закрывать сейчас) |

**Next action:** owner — контент ИБ 29 (активные элементы с `CODE` и `PREVIEW_PICTURE`); затем поднять стенд на 8588 и CASE-02. PHP/шаблоны кейсов не переписывать. Graphify не вызывать.

## Residual 2026-08-27: отзывы `/otzyvy/` из ИБ 28

**Цель (уже в коде):** хардкод-карточки на `/otzyvy/` → `bitrix:news.list` / `reviews`, `IBLOCK_ID=28`. Сортировка `SORT ASC` / `ID ASC` — **уже в вызове**; PHP не трогать.

**Критерии готовности:** AC1–AC6 (см. ADR отзывов). Код PASS. Live — после стенда.

**Зависимости:** активные элементы ИБ 28 с `PREVIEW_TEXT` (owner). Стенд **8588**.

| ID | Шаг | Статус |
|----|-----|--------|
| — | ADR Accepted | DONE (2026-08-27) |
| — | Инициатива README + оркестрация | DONE (2026-08-27) |
| REV-01 | Шаблон `news.list/reviews` + вызов в `otzyvy/index.php` | DONE (аудит 2026-08-27, AC1–AC6 по коду PASS) |
| REV-02 | Smoke AC1–AC6 на `/otzyvy/` | BLOCKED: docker eklektika/bitrix не запущен; `127.0.0.1:8588` refused; `8590` — ошибочный порт; `new.eklektika.ru/otzyvy/` 200 невалиден для локальных файлов |
| — | Закрытие цикла / Lessons Learned | OPEN (после REV-02; не закрывать сейчас) |

**Next action (residual):** owner — контент ИБ 28; стенд 8588; затем REV-02. PHP/шаблоны отзывов не переписывать.

**Graphify:** не вызывать.

## Другие открытые циклы сайта (не этот пакет)

| Цикл | Блокер |
|------|--------|
| 2026-08-26 popular (ИБ 26) | POP-03 owner; live-smoke blocked |
| 2026-08-26 service (ИБ 27) | POP-03/контент 27 owner; SVC-02 live-smoke blocked |
