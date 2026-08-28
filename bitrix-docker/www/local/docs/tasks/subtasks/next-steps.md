# Next Steps — кейсы `/o-kompanii/clients/` из ИБ 29 (residual) + residual отзывов

Пакет для Team Lead. Дата: 2026-08-27 (фиксация после аудита CASE-01).  
ADR кейсов: `local/docs/adr/2026-08-27-clients-cases-iblock.md`  
Инициатива кейсов: `local/docs/tasks/2026-08-27-clients-cases-iblock/README.md`

CASE-01 **DONE**. PHP/шаблоны кейсов и отзывов не переписывать. Цикл кейсов **не** закрывать. ADR не ставить в Implemented. Graphify **не** вызывать.

Сортировка: ИБ 28 и ИБ 29 — `SORT ASC`, затем `ID ASC`. Для обоих уже в коде.

---

## Residual A (текущий цикл): CASE-02

### 1. Контекст

Код соответствует ADR: `news.list` / `clients`, `IBLOCK_ID=29` на `/o-kompanii/clients/`. Аудит AC1–AC6 по коду PASS. Intro/SEO на месте. Хардкод-сетка снята.

Live-smoke не сделан:

- канон: `http://127.0.0.1:8588` — connection refused (оркестратор перепроверил curl);
- контейнеров eklektika/bitrix нет;
- `new.eklektika.ru` невалиден для локальной реализации.

### 2. Треки (остаток)

| Трек | Task ID | Priority | Зависит от | Owner |
|------|---------|----------|------------|-------|
| A. Контент ИБ 29 | — (не Task ID цикла) | P0 | — | owner |
| B. Поднять локальный стенд | — | P1 | — | infra / dev |
| C. Live-smoke | CASE-02 | P1 | A + B | QA / dev |

Параллелить можно A и B. CASE-02 только после обоих.

### Owner (P0)

В ИБ 29 — активные элементы с непустым `CODE` и `PREVIEW_PICTURE` (`NAME` желателен для alt). Иначе после деплоя `/o-kompanii/clients/` будет только intro (шаблон skip’ает пустой CODE/картинку).

### Стенд (P1)

Поднять Bitrix docker этого репозитория. HTTP: **8588**. Не использовать 8590. Не валидировать на `new.eklektika.ru`.

### CASE-02 (P1)

На локальном `/o-kompanii/clients/` (через 8588) пройти AC1–AC6: карточки из ИБ 29, вёрстка `.clients` / `a.client[target=_blank]`, intro и title «Кейсы».

### 3. Критерии приемки (live, CASE-02)

| AC | Критерий |
|----|----------|
| AC1 | На `/o-kompanii/clients/` рендерится `news.list` ИБ 29 / `clients` |
| AC2 | Классы `.clients`, `.row`, `.col-6.col-md-4.col-xl1-3`, `a.client[target=_blank]`, `img` |
| AC3 | `href = /{CODE}/` |
| AC4 | `img` = `PREVIEW_PICTURE`, `alt` = `NAME`; пустой CODE/картинка не на витрине |
| AC5 | Порядок как SORT/ID в админке ИБ 29 |
| AC6 | Intro/SEO на месте; визуал без регресса CSS |

### 4. Что НЕ делать

- Не править PHP/шаблоны CASE-01 без нового дефекта.
- Не закрывать цикл и не писать Lessons Learned до CASE-02.
- Не ставить ADR в Implemented.
- Не валидировать live на `new.eklektika.ru`.
- Не вызывать Graphify.
- Не менять CSS, не трогать `detail.php` / ИБ 9 / urlrewrite / `our-clients`.
- Не править PHP отзывов.

### 5. Аудит после CASE-02

- Скрин/факт локального URL на порту **8588**.
- Карточки совпадают с активными элементами ИБ 29 (CODE + PREVIEW_PICTURE).
- Пустой CODE/картинка не на витрине.
- После PASS — techlead закрывает цикл и пишет Lessons Learned.

---

## Residual B (соседний открытый): REV-02

ADR: `local/docs/adr/2026-08-27-reviews-iblock.md`  
Инициатива: `local/docs/tasks/2026-08-27-reviews-iblock/README.md`

REV-01 **DONE** (аудит AC1–AC6 по коду PASS). PHP/шаблоны отзывов **не** переписывать. Цикл отзывов **не** закрывать. ADR не ставить в Implemented.

Сортировка `SORT ASC` для ИБ 28 уже в `otzyvy/index.php`. Отдельный PHP-трек **не нужен**.

Live-smoke REV-02 по-прежнему **BLOCKED** (тот же стенд 8588 refused; `new.eklektika.ru/otzyvy/` невалиден).

| Трек | Task ID | Priority | Owner |
|------|---------|----------|-------|
| Контент ИБ 28 (PREVIEW_TEXT) | — | P0 | owner |
| Стенд 8588 | — | P1 | infra / dev |
| Live-smoke `/otzyvy/` | REV-02 | P1 | QA / dev |

REV-02 только после контента и стенда. Не смотреть `new.eklektika.ru` как доказательство.
