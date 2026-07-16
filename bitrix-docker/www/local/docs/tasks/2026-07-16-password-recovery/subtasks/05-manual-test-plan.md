# Subtask PWD-05: ручной тест-план и закрытие документации

Связано с: `local/docs/tasks/2026-07-16-password-recovery/README.md`

## Статус

**done with blockers** (2026-07-16)

## Цель

Прогнать E2E и закрыть techlead-артефакты после реализации.

## Чеклист ручных тестов

### Request flow

1. ~~Открыть `/personal/vhod.php` → «Забыли пароль?» → страница без 404.~~ → **blocked** (live HTTP 500 DB) / **pass** (static link + файл страницы)
2. ~~Ввести несуществующий email → нейтральный success…~~ → **blocked** (runtime DB)
3. ~~Ввести существующий email… + письмо~~ → **blocked** (DB + PWD-04R)
4. ~~Повторить request > N раз → rate-limit~~ → **blocked** (live) / **pass** (code: 5/30мин)
5. ~~Запрос без/с битым sessid → отказ~~ → **blocked** (live) / **pass** (code)

### Change flow

6. ~~Перейти по ссылке из письма~~ → **blocked** (PWD-04R / нет письма)
7. ~~Несовпадение паролей~~ → **blocked**
8. ~~Слабый пароль~~ → **blocked**
9. ~~Валидный пароль → success + redirect~~ → **blocked**
10. ~~Вход новым паролем на `vhod.php`~~ → **blocked**
11. ~~Повтор CHECKWORD → отказ~~ → **blocked**

### Links / regress

12. ~~already_registered → новая страница~~ → **pass** (`AjaxRegisterCrmContactPrecheck.php`)
13. ~~Авторизованный → redirect ЛК~~ → **blocked** (live) / **pass** (code)

Полная таблица + причины: `tasks/subtasks/progress-checklist.md` → секция **PWD-05 note**.

## Definition of Done

- [x] Все пункты 1–13 пройдены или зафиксированы исключения.
- [x] Обновлены `tasks/subtasks/progress-checklist.md` и `adr-index.md` (ADR: Wave A code done; E2E blocked — не Done).
- [x] Lessons Learned кратко в progress-checklist.
- [ ] Полный green live E2E — после ops (DB host + dump) + PWD-04R.

## Зависимости

- После PWD-01…PWD-04.
- Live E2E: рабочая MySQL Bitrix + PWD-04R для mail.
