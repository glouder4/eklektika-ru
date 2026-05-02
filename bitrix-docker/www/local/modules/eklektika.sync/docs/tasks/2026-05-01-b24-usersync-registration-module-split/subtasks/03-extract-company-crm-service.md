# 03 — Сервис компании и CRM из RegisterUserCompany

## Цель

Логика `createB24Company`, пречеки ИНН, webhooks company/contact/add, `syncFromSiteRegistration` инкапсулирована в классах нового модуля; `RegisterUserCompany` удалён или превращён в тонкий фасад `@deprecated`.

## Критерии

- [ ] Нет дублирования вызовов n8n для одного шага регистрации.
- [ ] Интеграционный сценарий: регистрация → пользователь ACTIVE=N → контакт/компания в CRM.
