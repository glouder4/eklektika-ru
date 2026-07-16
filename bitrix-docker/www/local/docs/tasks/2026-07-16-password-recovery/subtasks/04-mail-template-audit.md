# Subtask PWD-04: аудит почтового шаблона USER_PASS_REQUEST

Связано с: `local/docs/tasks/2026-07-16-password-recovery/README.md`  
ADR: `local/docs/adr/2026-07-16-password-recovery.md`

## Статус

**audit done / admin fix pending** (2026-07-16, Dev C)

## Цель

Гарантировать, что письмо восстановления ведёт на кастомную страницу смены пароля, а не на `/personal/profile/` / штатный Bitrix URL.

## Compat (2026-07-16 hotfix)

Дефолтный шаблон ссылается на `/auth/index.php?...` — папки не было → **404**.  
Добавлен `www/auth/index.php`: редирект `change_password` → `/personal/vosstanovlenie-parolya.php` (старые письма продолжают работать).  
Всё равно обновить MESSAGE на прямой URL (см. ниже).

## Scope

- Админка Bitrix: Настройки → Настройки продукта → Почтовые события → `USER_PASS_REQUEST` (и связанные шаблоны сайта `s1`).
- При наличии экспорта/фикстур шаблонов в репозитории — обновить и их (если есть; иначе только runtime/admin + зафиксировать в task notes фактические макросы).
- **Не** править ядро `bitrix/modules/main`.

## Результат аудита (репо)

| Источник | Найден? | Вывод |
|----------|---------|--------|
| Product code / seeds вне `bitrix/` | **Нет** MESSAGE | Править нельзя из репо |
| Ядро: `main/lang/ru/install/index.php` → `MAIN_USER_PASS_REQUEST_EVENT_DESC` | Да (только install default) | URL = `/auth/index.php?...` |
| Live `b_event_message` | **Не прочитан** (`ru_mysql` down) | Admin-only до старта стека |

### Фактические макросы

Событие `USER_PASS_REQUEST` заполняется через `CUser::SendUserInfo` полями:

- `#USER_ID#`, `#STATUS#`, `#MESSAGE#`
- `#LOGIN#`, `#URL_LOGIN#` (urlencoded login — для URL)
- `#CHECKWORD#`
- `#NAME#`, `#LAST_NAME#`, `#EMAIL#`

Сайтные: `#SITE_NAME#`, `#SERVER_NAME#`, `#DEFAULT_EMAIL_FROM#`.  
`#SITE_URL#` в дефолтном шаблоне **не используется**.

### Целевая ссылка в письме

```
http://#SERVER_NAME#/personal/vosstanovlenie-parolya.php?change_password=yes&USER_LOGIN=#URL_LOGIN#&USER_CHECKWORD=#CHECKWORD#
```

ADR допускал `#LOGIN#`; для query string предпочтителен `#URL_LOGIN#` (как в дефолте Bitrix).

## Инструкция для админки

1. Админка → **Настройки** → **Настройки продукта** → **Почтовые и SMS-события** → типы/шаблоны.
2. Найти шаблон(ы) с `EVENT_NAME = USER_PASS_REQUEST` для сайта `s1` (и др. при наличии).
3. В теле письма заменить ссылку смены пароля на целевую (см. выше).
4. Убедиться, что шаблон **активен**.
5. Отправить тестовое письмо (после PWD-02) и проверить, что ссылка открывает change-режим на `vosstanovlenie-parolya.php`.

## SQL (после поднятия `ru_mysql`; без секретов)

Сначала **только SELECT** — тело на проде могло быть кастомизировано:

```sql
SELECT ID, EVENT_NAME, LID, ACTIVE, SUBJECT, MESSAGE
FROM b_event_message
WHERE EVENT_NAME = 'USER_PASS_REQUEST';
```

Если MESSAGE всё ещё со ссылкой на `/auth/index.php` (типичный дефолт):

```sql
UPDATE b_event_message
SET MESSAGE = REPLACE(
  MESSAGE,
  'http://#SERVER_NAME#/auth/index.php?change_password=yes&lang=ru&USER_CHECKWORD=#CHECKWORD#&USER_LOGIN=#URL_LOGIN#',
  'http://#SERVER_NAME#/personal/vosstanovlenie-parolya.php?change_password=yes&USER_CHECKWORD=#CHECKWORD#&USER_LOGIN=#URL_LOGIN#'
)
WHERE EVENT_NAME = 'USER_PASS_REQUEST'
  AND MESSAGE LIKE '%/auth/index.php?change_password=%';
```

После UPDATE — повторный SELECT и тестовое письмо. Не коммитить дампы с паролями/PII.

## Definition of Done

- [x] Целевой URL и макросы зафиксированы в `tasks/subtasks/progress-checklist.md`.
- [x] Зафиксировано: шаблон **admin-only / DB-only**; в product-репо править нечего.
- [ ] Шаблон на live обновлён (админка или SQL при поднятом MySQL).
- [ ] Тестовое письмо содержит рабочую ссылку на `vosstanovlenie-parolya.php` (PWD-05 / после PWD-02).
- [ ] CHECKWORD из письма принимается `CUser::ChangePassword` (PWD-05).

## Зависимости

- Может стартовать параллельно с PWD-01/03.
- Полная E2E-проверка — после PWD-02.
