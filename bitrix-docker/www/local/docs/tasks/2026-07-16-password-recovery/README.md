# Инициатива: восстановление пароля end-to-end

## Статус

**Accepted / partial impl** (cycle close 2026-07-16): код Wave A + hotfixes в репо; **не Done**.  
PWD-04R blocked (MESSAGE); PWD-05 done with blockers (live E2E). AC4 + AC6 закрыты.

## Цель

Реализовать отсутствующую страницу `/personal/vosstanovlenie-parolya.php` и полный поток: запрос письма → ссылка с CHECKWORD → смена пароля → вход, в стиле существующего кастомного auth UI.

## Scope / Out of scope

**Scope:**

- Страница request/change UI (`personal/vosstanovlenie-parolya.php`)
- AJAX endpoint request/change (`personal/ajax/ajax-password-reset-action.php`)
- Rate-limit (отдельный namespace от login bruteforce; hotfix: 15/15, `attempts_v2.json`)
- Штатная почта Bitrix: `CUser::SendUserInfo(..., false, 'USER_PASS_REQUEST')` (очередь) + `CUser::ChangePassword`
- Аудит/правка URL в почтовом шаблоне (ops на live)
- Фикс битых forgot-password ссылок в продуктовом коде
- Документация

**Out of scope:**

- PHPMailer напрямую
- Автологин после смены пароля
- Ядро `bitrix/` и штатные компоненты auth как основной UI
- Изменения CRM registration, кроме текста ссылки

## Source of truth

- ADR: `local/docs/adr/2026-07-16-password-recovery.md`
- Оркестрация: `tasks/subtasks/next-steps.md`
- Эталон UI/AJAX: `personal/vhod.php`, `personal/ajax/ajax-entry-action.php`

## Критерии готовности (Acceptance Criteria)

- **AC1 (страница)**: `GET /personal/vosstanovlenie-parolya.php` → 200; UI в стиле `auth-block` / `btn-round` / `form-control`; авторизованный → ЛК. — *код ready; live open*
- **AC2 (request)**: POST AJAX с sessid ставит `USER_PASS_REQUEST` в очередь (`SendUserInfo`, не SendImmediate); без sessid — ошибка; rate-limit 15/15; anti-enumeration. — *код ready; runtime open*
- **AC3 (change)**: ссылка из письма → смена пароля; валидный CHECKWORD → успех; вход на `/personal/vhod.php`. — *код ready; E2E open*
- **AC4 (ссылки)**: нет user-facing `/personal/profile/?forgot_password=yes`; precheck → новая страница. — **закрыт**
- **AC5 (почта)**: шаблон `USER_PASS_REQUEST` → `vosstanovlenie-parolya.php` с `#URL_LOGIN#` + `#CHECKWORD#`. — **открыт (ops)**
- **AC6 (docs)**: ADR и task-пакет актуальны; progress/next-steps обновлены. — **закрыт**

## Субзадачи

| Файл | Task ID | Содержание |
|------|---------|------------|
| [subtasks/01-page-ui-request-change.md](subtasks/01-page-ui-request-change.md) | PWD-01 | Страница dual-mode UI |
| [subtasks/02-ajax-reset-endpoints.md](subtasks/02-ajax-reset-endpoints.md) | PWD-02 | AJAX request/change + rate-limit |
| [subtasks/03-fix-forgot-links.md](subtasks/03-fix-forgot-links.md) | PWD-03 | Правка битых ссылок |
| [subtasks/04-mail-template-audit.md](subtasks/04-mail-template-audit.md) | PWD-04 | Аудит шаблона USER_PASS_REQUEST |
| [subtasks/05-manual-test-plan.md](subtasks/05-manual-test-plan.md) | PWD-05 | Ручной тест-план / docs closure |

## Риски / Notes

- Graphify MCP на цикле **unavailable** — не блокер; scoped только `bitrix-docker/www/local` без `templates`.
- Страницы в `www/personal` (вне graphify scope) — ожидаемо.
- Login может отличаться от email — резолв по EMAIL обязателен.
- Не смешивать bruteforce login и password-reset.
- Hotfixes: SendUserInfo queue; attempts_v2 15/15.

## Next steps (ops)

См. `tasks/subtasks/next-steps.md` — P0 MESSAGE на live + деплой + live E2E. Новый code-wave не нужен.
