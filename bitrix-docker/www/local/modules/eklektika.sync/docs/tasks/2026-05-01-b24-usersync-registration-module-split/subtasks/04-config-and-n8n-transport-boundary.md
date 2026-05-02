# 04 — Конфиг и транспорт n8n

## Цель

`CrmRegistrationN8nTransport` и ключи `registration_webhook_*` логически принадлежат модулю registration; при необходимости — thin wrapper над `EKLEKTIKA_SYNC_CONFIG` без дублирования файлов конфига.

## Критерии

- [ ] Один источник правды для URL вебхуков регистрации.
- [ ] Документировано в ADR, какие ключи читает registration vs sync vs usersync.

## Wave 2 checklist (BC + loader)

- [ ] Перенести классы в `eklektika.b24.registration` с новым namespace:
  - `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport`
  - `\OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse`
  - `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck`
- [ ] Проверить автозагрузку (Bitrix Loader class map):
  - добавить новые FQCN → новые пути в `eklektika.b24.registration/include.php`;
- [ ] Перевести все вызовы на новые FQCN и удалить старые классы (без shims):
  - обновить все usages `\OnlineService\B24\UserSync\AjaxRegister\...` → `\OnlineService\B24\Registration\AjaxRegister\...`;
  - удалить маппинг старых FQCN из `eklektika.b24.usersync/include.php`;
  - удалить старые файлы классов из `eklektika.b24.usersync/lib/UserSync/AjaxRegister/`.
- [ ] Проверить цепочку подключения в `local/php_interface/eklektika_requires.php`:
  - `usersync` подключается **до** `registration` (как сейчас), чтобы BC работала в обе стороны и не возникали циклы.
