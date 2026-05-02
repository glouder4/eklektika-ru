# 01 — Каркас модуля и bootstrap

## Цель

Появился модуль `local/modules/eklektika.b24.registration/` с `include.php`, README и подключением из `php_interface/eklektika_requires.php` **до** переноса кода.

## Критерии готовности

- [x] Каталог модуля создан, нет фатальных ошибок при загрузке сайта.
- [x] В `include.php` зафиксирован namespace-план (комментарий) и пустой или минимальный `Loader::registerAutoLoadClasses`.
- [x] В `eklektika_requires.php` добавлен `requireEklektikaModuleInclude('eklektika.b24.registration')` после `eklektika.b24.usersync`.

## Зависимости

`eklektika.b24.rest`, `eklektika.sync` (конфиг).
