# Subtask PWD-01: страница request/change UI

Связано с: `local/docs/tasks/2026-07-16-password-recovery/README.md`  
ADR: `local/docs/adr/2026-07-16-password-recovery.md`

## Статус

**todo**

## Цель

Создать `/personal/vosstanovlenie-parolya.php` с двумя режимами UI в стиле `vhod.php`.

## Scope файлов

- **create:** `bitrix-docker/www/personal/vosstanovlenie-parolya.php`
- **reference only:** `bitrix-docker/www/personal/vhod.php`

## Требования

- Режим **request**: email + кнопка отправки; `bitrix_sessid_post()`; блок `.errors`.
- Режим **change**: если в query есть `change_password=yes`, `USER_LOGIN`, `USER_CHECKWORD` — форма пароль/подтверждение; hidden fields с login/checkword; sessid.
- Авторизованный → redirect `/personal/lichnyj-kabinet.php`.
- Классы: `auth-block`, `btn-round`, `form-control`, `btn-bluelight` / `btn-blue-border` по образцу входа.
- JS: AJAX на `/personal/ajax/ajax-password-reset-action.php` (контракт согласовать с PWD-02).
- Ссылка «Войти» обратно на `/personal/vhod.php`.

## Definition of Done

- [ ] Страница открывается без 404.
- [ ] Оба режима рендерятся корректно.
- [ ] Визуально согласовано с `vhod.php`.
- [ ] Нет утечки checkword в client-side console/logs.

## Зависимости

- Контракт AJAX описан в ADR (можно параллелить с PWD-02).
- Не зависит от PWD-03/PWD-04.
