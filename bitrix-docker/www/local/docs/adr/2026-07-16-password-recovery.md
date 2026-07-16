# ADR: End-to-end восстановление пароля (`/personal/vosstanovlenie-parolya.php`)

## Статус

**Accepted / partial implementation** (2026-07-16).

- Код Wave A (PWD-01…04) + post-audit hotfixes — в репозитории.
- Live: шаблон `USER_PASS_REQUEST` в БД **не обновлён**; полный mail/E2E — **не закрыт** (PWD-04R blocked; PWD-05 done with blockers).
- Цикл **не** считается Done до ops MESSAGE + live E2E.

## Контекст

- На `/personal/vhod.php` уже есть ссылка «Забыли пароль?» → `/personal/vosstanovlenie-parolya.php`; страница реализована в Wave A.
- Вход реализован кастомно: AJAX `/personal/ajax/ajax-entry-action.php` + `check_bitrix_sessid()` + `bruteforce-protection.php`.
- Почтовая система сайта работает; для reset — штатные механизмы Bitrix (`USER_PASS_REQUEST`, `CUser::ChangePassword` с `CHECKWORD`), не PHPMailer.
- В `AjaxRegisterCrmContactPrecheck.php` была устаревшая ссылка `/personal/profile/?forgot_password=yes` — исправлена (PWD-03 / AC4).

## Решение

### 1) Одна страница — два режима UI

Файл: `bitrix-docker/www/personal/vosstanovlenie-parolya.php`.

| Режим | Условие | UI |
|-------|---------|-----|
| **request** | нет валидных `change_password` + checkword | форма email + кнопка «Отправить» |
| **change** | `change_password=yes` и присутствуют `USER_LOGIN` + `USER_CHECKWORD` (из письма) | форма нового пароля + подтверждение |

Стиль: `auth-block`, `btn-round`, `form-control` — как на `vhod.php`.  
Если пользователь уже авторизован — редирект в ЛК (`/personal/lichnyj-kabinet.php`).

### 2) AJAX endpoint

Файл: `bitrix-docker/www/personal/ajax/ajax-password-reset-action.php`.

Обязательно:

- `check_bitrix_sessid()`;
- rate-limit по IP — `bruteforce-password-reset.php`, storage `upload/bruteforce_password_reset/` (**не** смешивать с login);
- JSON-ответы в стиле login-endpoint.

Действия (`action` / `formid`):

| Action | Поведение |
|--------|-----------|
| `request` | Резолв пользователя по EMAIL. **Hotfix:** вместо `CUser::SendPassword` → `CUser::SendUserInfo($userId, $siteId, …, false, 'USER_PASS_REQUEST')` (`bImmediate=false` → `CEvent::Send`, очередь `b_event`, не `SendImmediate`). Анти-enumeration: нейтральный success вне зависимости от исхода. |
| `change` | `CUser::ChangePassword($login, $checkword, $password, $confirm)`. Ошибки checkword/пароля — явные. Успех → redirect на `/personal/vhod.php` (без автологина). |

#### Rate-limit (hotfix)

| Параметр | Значение |
|----------|----------|
| Лимит | **15** попыток / **15** мин |
| Storage | `upload/bruteforce_password_reset/attempts_v2.json` |
| Зачем v2 | сброс старых банов от прежнего лимита 5/30 (`attempts.json`) без ручной чистки на проде |

Счётчик инкрементируется на **каждый** request (включая успешный) — anti-flood писем.

### 3) Почтовое событие и ссылка в письме

- Событие: `USER_PASS_REQUEST` (через очередь после hotfix SendUserInfo).
- В шаблоне письма (админка / `b_event_message`) ссылка смены пароля должна вести на:

```
http://#SERVER_NAME#/personal/vosstanovlenie-parolya.php?change_password=yes&USER_LOGIN=#URL_LOGIN#&USER_CHECKWORD=#CHECKWORD#
```

(предпочтительно `#URL_LOGIN#`, не `#LOGIN#`).

- **Ops residual:** MESSAGE на live **ещё не обновлён** (PWD-04R blocked — нет доступа к рабочей `b_event_message` / пустой local MySQL).
- Не вызывать PHPMailer напрямую для reset.

### 4) Правка битых ссылок

Заменено `/personal/profile/?forgot_password=yes` → `/personal/vosstanovlenie-parolya.php` в:

- `local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterCrmContactPrecheck.php`

Grep по product scope: старых user-facing forgot URL нет. Ядро `bitrix/` не трогали. **AC4 закрыт.**

### 5) Документация

- Этот ADR + task-пакет `local/docs/tasks/2026-07-16-password-recovery/`.
- Оркестрация цикла: `tasks/subtasks/{adr-index,progress-checklist,next-steps}.md`.

## Out of scope

- Компоненты ядра `system.auth.forgotpasswd` / `system.auth.changepasswd` как основной UI (допускается только как reference).
- Прямая отправка через PHPMailer.
- Изменение CRM registration pipeline, кроме текста ссылки forgot password.
- Автологин после успешной смены пароля (осознанно отложено).
- Graphify-анализ путей вне `bitrix-docker/www/local` (и вне `local/templates`).

## Последствия

- Пользовательский цикл «забыл пароль → письмо → смена → вход» **готов в коде**; на live зависит от деплоя файлов + MESSAGE + почтовой очереди.
- Безопасность: sessid, rate-limit 15/15, anti-enumeration, отдельный bruteforce namespace.
- Риск: прод-MESSAGE со старым URL — **открыт** до ручного ops.
- Риск: login ≠ email — резолв по EMAIL в AJAX обязателен.
- Graphify MCP на цикле **unavailable** — не блокер.

## Ссылки

- UI-эталон: `personal/vhod.php`
- Login AJAX: `personal/ajax/ajax-entry-action.php`
- Reset AJAX: `personal/ajax/ajax-password-reset-action.php`
- Rate-limit reset: `personal/ajax/bruteforce-password-reset.php`
- Precheck: `local/modules/eklektika.b24.registration/.../AjaxRegisterCrmContactPrecheck.php`
- Task-пакет: `local/docs/tasks/2026-07-16-password-recovery/`
