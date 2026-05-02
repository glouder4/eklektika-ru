# Task: UPDATE_CONTACT — резолв UF_PERSONAL_MANAGER в элемент ИБ 24

## Цель

Корректно обрабатывать входящие поля **`UF_PERSONAL_MANAGER_1`** / **`UF_PERSONAL_MANAGER_2`** и опционально **`CONTACT_ID`** в канале CRM → сайт (`InboundGateway` → `User::update`).

## Критерии готовности

- [x] Константы ИБ 24 и свойства `BITRIX24_ID` в `UserSyncConfig`.
- [x] Резолв CRM-значения → `ID` элемента ИБ перед `CUser::Update`.
- [x] `CONTACT_ID` как источник `crm.contact` id при поиске пользователя.
- [x] Справочник `docs/reference/crm-uf-site-mapping.md` обновлён.

## Subtasks

- [x] **S1.** Реализация в `modules/eklektika.b24.usersync/lib/User.php` и конфиг.
- [x] **S2.** Документация контракта (таблица + ADR).

## Audit

Реализация соответствует ADR в этом каталоге; смоук на стенде — вне этого PR.

## Next steps for Team Lead

- Смоук: POST на `inbound_crm.php` с примером payload (см. пользовательский JSON), проверить `b_user` и trace при отсутствии карточки менеджера.

## Next steps for Tech Lead

- После смоука при необходимости скорректировать номер ИБ/код свойства в конфиге под боевой портал.
- По регламенту проекта: при необходимости запустить `/graphify-local-wave1` для актуализации графа знаний по затронутым классам.
