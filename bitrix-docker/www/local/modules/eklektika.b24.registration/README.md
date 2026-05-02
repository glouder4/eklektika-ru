# eklektika.b24.registration

Модуль сценария **регистрации юридического лица на сайте** и связки с **компанией в Bitrix24** (включая вызовы n8n registration webhooks).

## Граница с `eklektika.b24.usersync`

| Модуль | Ответственность |
|--------|-----------------|
| **registration** | Конвейер ajax-регистрации (`AjaxRegisterActionService`), фасад `CompanyRegistrationService`, CRM‑оркестратор `CrmRegistrationOrchestrator` (webhooks, `syncFromSiteRegistration`, обработчики main делегируют сюда) |
| **usersync** | Пользователь сайта ↔ контакт B24 вне сценария регистрации, профиль, группы, удаление; bootstrap событий main вызывает классы из **registration** |

История переноса: `eklektika.sync/docs/tasks/2026-05-01-b24-usersync-registration-module-split/`.

## Ключевые классы

- `lib/CrmRegistrationOrchestrator.php` — единая реализация CRM‑ветки регистрации (ранее `usersync/RegisterUserCompany.php`, класс удалён).
- `lib/Registration/AjaxRegisterActionService.php` — сегментированный конвейер формы.
- `docs/reference/registration-n8n-webhooks.md` — конфиг‑ключи и контракты n8n.
