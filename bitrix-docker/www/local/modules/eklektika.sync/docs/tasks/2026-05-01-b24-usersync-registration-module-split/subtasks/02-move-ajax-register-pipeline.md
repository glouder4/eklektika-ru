# 02 — Перенос ajax-конвейера регистрации

## Цель

Классы `AjaxRegisterActionService`, `UserSync/AjaxRegister/*` (кроме явно usersync-специфичных) физически лежат в `eklektika.b24.registration`; namespaces обновлены или сохранены через alias.

## Критерии

- [x] `personal/ajax/ajax-register-action.php` указывает на новый FQCN без изменения контракта JSON.
- [x] `AjaxRegisterActionService` физически в `eklektika.b24.registration`, шымы/алиасы удалены (Wave 2).
- [x] Весь `AjaxRegister` пайплайн лежит в `eklektika.b24.registration` и использует `\OnlineService\B24\Registration\AjaxRegister\...` (Wave 3).
- [x] Автозагрузка: классы `Registration\AjaxRegister\*` грузятся из registration-модуля; старые классы удалены из `usersync`.

## Wave 3 — scope и чеклист

**Перенос классов (точный список):**

- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterExecutionContext` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterExecutionContext`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterDuplicateGuard` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterDuplicateGuard`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterBitrixApplication` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterBitrixApplication`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterSiteCompanyResolver` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterSiteCompanyResolver`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterUserPayloadBuilder` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterUserPayloadBuilder`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterPostParser` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser`
- [x] `\OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterResponse` → `\OnlineService\B24\Registration\AjaxRegister\AjaxRegisterResponse`

**Перевод использования:**

- [x] `AjaxRegisterActionService` использует `\OnlineService\B24\Registration\AjaxRegister\...` (use/import + FQCN).
- [x] Поиск по репо: нет ссылок на `\OnlineService\B24\UserSync\AjaxRegister\` (кроме истории/доков).

## Риски

Пропуск класса в Loader — смоук-тест регистрации на staging.
