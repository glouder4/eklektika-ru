# Subtask PWD-03: правка битых forgot-password ссылок

Связано с: `local/docs/tasks/2026-07-16-password-recovery/README.md`

## Статус

**done** (2026-07-16, Dev C)

## Цель

Все пользовательские ссылки «восстановить пароль» ведут на `/personal/vosstanovlenie-parolya.php`.

## Scope файлов

- **edit:** `bitrix-docker/www/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterCrmContactPrecheck.php` (строка ~114)
- **audit grep:** `forgot_password`, `vosstanovlenie-parolya`, `восстановить пароль` в продуктовом коде (не ядро `bitrix/`)

## Требования

- Заменить `/personal/profile/?forgot_password=yes` → `/personal/vosstanovlenie-parolya.php`.
- Ссылку «авторизоваться» желательно вести на `/personal/vhod.php` (сейчас `/personal/profile/` — поправить в том же сообщении, если profile не является кастомным входом).
- Не менять логику CRM precheck, только текст/URL исключения.

## Definition of Done

- [x] Precheck показывает новую ссылку (`/personal/vosstanovlenie-parolya.php` + login `/personal/vhod.php`).
- [x] Grep по `local` + `personal`: нет `forgot_password` / `profile/?forgot` user-facing URL (ядро и strip в `oformlenie-zakaza.php` вне scope).
- [ ] Регистрационный сценарий «уже зарегистрирован» кликабелен до рабочей страницы (страница — PWD-01; E2E — PWD-05).

## Зависимости

- Независимо от PWD-01/02 (можно параллелить сразу).
- Страница может ещё 404 до PWD-01 — ссылка всё равно должна быть правильной.
