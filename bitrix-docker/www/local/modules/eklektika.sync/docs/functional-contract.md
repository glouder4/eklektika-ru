# Функциональный контракт входящего sync (сайт)

Краткая замена удалённого `local/sync/docs/functional-contract.md`. Детали кода — в `InboundGateway`, `InboundSecurity`, `lib/from-crm/*`.

## HTTP

- **Канонический входящий канал CRM → сайт:** `POST` на путь `OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH` → скрипт `local/modules/eklektika.sync/public/inbound_crm.php` (body/query с полем `ACTION` и полезной нагрузкой).
- Мост **Bitrix24 → n8n → сайт** для `UPDATE_COMPANY`: [`docs/reference/n8n-inbound-update-company-from-b24.md`](../../../docs/reference/n8n-inbound-update-company-from-b24.md).
- **Legacy:** `POST /local/classes/ajax.php` — тонкий `require` того же скрипта (совместимость со старыми закладками и интеграциями).
- **Исходящие вызовы с портала на сайт** (в т.ч. `CRM_METHOD` через `RestClient::postSiteRequestsHandler`) — тот же канонический URL; см. `RestTransportConfig::SITE_AJAX_PROXY_PATH` и `SITE_REQUESTS_HANDLER_PATH`.

## Модули

- Реализация: `local/modules/eklektika.sync/`.
- Регистрация и bootstrap: `local/php_interface/eklektika_requires.php`.

## Пользовательские поля CRM

- Соответствия UF: класс `CrmInboundUfMap` (этот файл — обзор; константы в коде).
- Полная карта UF CRM ↔ сайт (контакт/компания/`b_user`, регистрация): [`docs/reference/crm-uf-site-mapping.md`](../../../docs/reference/crm-uf-site-mapping.md).
- Входящий **`UPDATE_MANAGER`** (`InboundGateway` → `OnlineService\Site\Manager::update`, ИБ 24, свойство **`BITRIX24_ID`**): плоский payload с `BITRIX24_ID` / `ID`, `NAME`+`LAST_NAME` → имя элемента, `PERSONAL_PHOTO` (относительный путь) скачивается с базы CRM (**`URL_B24`**, напр. [`https://bitrix.eklektika.ru/`](https://bitrix.eklektika.ru/)) в **`PREVIEW_PICTURE`**. **`IS_PERSONAL_MANAGER`**: при `false` новый элемент не создаётся; существующий обновляется и переводится в неактивный (`ACTIVE=N`). Без ключа — поведение как «активный менеджер» (как раньше).

- Входящий **`UPDATE_CONTACT`** (`InboundGateway` → `OnlineService\B24\User::update`): поля **`UF_PERSONAL_MANAGER_1`** / **`UF_PERSONAL_MANAGER_2`** резолвятся в ID элементов инфоблока карточек менеджера по свойству **`BITRIX24_ID`** (см. раздел в карте UF и `UserSyncConfig::MANAGER_CARD_*`). При **`ASSOCIATED_WITH_ENTITY`** при отсутствии привязки добавляется сотрудник в **`LEGAN_ENTITY_USERS`** (по **`OS_COMPANY_B24_ID`**); при уже существующей привязке запись в ИБ не дублируется; снятие директора не откатывает **`LEGAN_ENTITY_USERS`** — только BOSS-списки (см. карту UF).

## Документация задач

- `docs/tasks/2026-04-30-workspace-refactor-to-modules/task.md`

При необходимости полного архива runbook/inventory восстановите из истории git коммитов до удаления `local/sync/docs/`.
