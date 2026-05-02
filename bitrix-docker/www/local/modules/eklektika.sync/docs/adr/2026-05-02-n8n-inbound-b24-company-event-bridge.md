# ADR: мост n8n для событий B24 → входящий `UPDATE_COMPANY` на сайте

## Статус

Принято (2026-05-02). Документирует контракт и риски без обязательного изменения PHP в первой волне.

## Контекст

Bitrix24 генерирует события по компании. Целевой канал на сайте — **один HTTP endpoint** (`inbound_crm.php`) с полем `ACTION` и полезной нагрузкой, далее `Company::updateCompanyElement`.

Сырой payload CRM часто содержит `TITLE` и смесь **витринных** полей (`LEGAN_*`), тогда как код сайта в первую очередь ожидает **`OS_*`** для части флагов и имени элемента (`OS_COMPANY_NAME`).

## Решение

1. **PHP:** `inbound_crm.php` при `Content-Type: application/json` читает тело и передаёт в `InboundGateway` декод + merge с `$_GET` (токен из URL).
2. **PHP:** `InboundGateway::normalizeInboundEnvelope` — поддержка **`[{ … }]`** и конверта **`{ ACTION, FIELDS }`** → плоский массив перед маршрутизацией.
3. **PHP:** `Company::updateCompanyElement` — если нет `OS_COMPANY_NAME`, подставляется **`TITLE`** (типичное поле названия в CRM).
4. В n8n по-прежнему желательно дублировать **`LEGAN_ENTITY_USERS` → `OS_COMPANY_USERS`** и флаги холдинга в `OS_*`, если приходят только витринные ключи (см. `$codeProps`).

## Риски

- **JSON vs form**: если входящий скрипт не наполняет `$_REQUEST` из JSON, workflow будет «молча» неправильным; проверить на стенде.
- **Только LEGAN без OS** для пользователей компании: возможны пробелы в слиянии свойств; дублировать `OS_COMPANY_USERS`.
- Секрет входа (`X-Sync-Token` / `sync_token`): должен совпадать с политикой `InboundSecurity`.

## Ссылки

- Справочник n8n: `docs/reference/n8n-inbound-update-company-from-b24.md`
- Задача: `modules/eklektika.sync/docs/tasks/2026-05-02-n8n-b24-company-inbound-bridge/task.md`

## Next steps for Team Lead

1. Смоук: событие B24 → n8n → POST на staging inbound → проверка элемента ИБ 23 и ответа JSON.
2. При необходимости — доработка PHP: парсинг JSON body в `inbound_crm.php` или расширение `$codeProps` / обратное зеркалирование LEGAN→OS (отдельная задача разработки).
