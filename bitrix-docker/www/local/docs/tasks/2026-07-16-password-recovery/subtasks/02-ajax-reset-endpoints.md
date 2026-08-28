# Subtask PWD-02: AJAX request/change + rate-limit

Связано с: `local/docs/tasks/2026-07-16-password-recovery/README.md`  
ADR: `local/docs/adr/2026-07-16-password-recovery.md`

## Статус

**todo**

## Цель

Реализовать безопасный AJAX endpoint для запроса письма и смены пароля по CHECKWORD.

## Scope файлов

- **create:** `bitrix-docker/www/personal/ajax/ajax-password-reset-action.php`
- **extend or fork pattern:** `bitrix-docker/www/personal/ajax/bruteforce-protection.php`  
  (предпочтительно: отдельные функции/storage path `upload/bruteforce_password_reset`, не ломая login-счётчик)
- **reference:** `bitrix-docker/www/personal/ajax/ajax-entry-action.php`

## Контракт API (черновой)

### `action=request`

POST: `sessid`, `email` (или `username`)

Response success (всегда нейтральный при валидном формате email):

```json
{"success": true, "message": "Если аккаунт с таким e-mail существует, мы отправили инструкции по восстановлению пароля."}
```

Внутри: резолв LOGIN по EMAIL → `CUser::SendPassword($login, $email)` (или эквивалент API сайта).  
Несуществующий email → тот же success. Ошибки отправки — логировать серверно, клиенту нейтральный/общий fail только при инфраструктурных сбоях по решению разработчика (предпочитать нейтральный success).

### `action=change`

POST: `sessid`, `USER_LOGIN`, `USER_CHECKWORD`, `PASSWORD`, `CONFIRM_PASSWORD`

Success:

```json
{"success": true, "redirect": "/personal/vhod.php", "message": "Пароль изменён. Войдите с новым паролем."}
```

Failure: `{"success": false, "error": "..."}` (истёкший checkword, несовпадение паролей, политика пароля Bitrix).

## Обязательные проверки

- `check_bitrix_sessid()`
- rate-limit по IP (лимиты можно взять как у login: 5 / 30 мин, либо чуть мягче для request — зафиксировать в коде константами)
- валидация email / длины пароля
- JSON `JSON_UNESCAPED_UNICODE`

## Definition of Done

- [ ] Без sessid → отказ.
- [ ] Anti-enumeration на request соблюдён.
- [ ] ChangePassword работает с валидным CHECKWORD.
- [ ] Login bruteforce storage не затрагивается.
- [ ] Письмо уходит через штатный Bitrix mail path (не PHPMailer).

## Зависимости

- Параллельно с PWD-01 по контракту ADR.
- PWD-04 проверяет, что письмо содержит правильный URL.
