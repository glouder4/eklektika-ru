# 01: Инвентарь `callB24Method` и черновик ключей конфига

Источник: `CrmRegistrationOrchestrator.php` (поиск `callB24Method`).

| Метод B24 | Назначение в регистрации | Черновик ключа конфига (подлежит утверждению) | Заметки |
|-----------|--------------------------|-----------------------------------------------|---------|
| `crm.contact.company.add` | привязка контакта к компании | `registration_webhook_crm_contact_company_add_url` | альтернатива имени: path `registration/crm-contact-company-add-v1` |
| `crm.company.contact.add` | fallback привязки | `registration_webhook_crm_company_contact_add_url` | |
| `crm.company.update` | UF элемента сайта на компании | `registration_webhook_crm_company_update_url` | **реализовано** в `registrationCrmRestWebhookConfigKey` |
| `crm.requisite.list` | список реквизитов по компании / по ИНН | `registration_webhook_crm_requisite_list_url` | только `callB24Method` (дубликат `postRegistrationWebhook` убран) |
| `crm.requisite.update` | дописать ИНН в реквизит | `registration_webhook_crm_requisite_update_url` | |
| `crm.company.get` | получить компанию по id | `registration_webhook_crm_company_get_url` | несколько вызовов в коде |
| `crm.requisite.add` | создать реквизит | `registration_webhook_crm_requisite_add_url` | |
| `crm.contact.list` | поиск контакта (ветка в конце файла) | `registration_webhook_crm_contact_list_url` | |
| `crm.contact.update` | обновление контакта | `registration_webhook_crm_contact_update_url` | |
| ~~`crm.contact.company.delete`~~ | — | ~~`registration_webhook_crm_contact_company_delete_url`~~ | **Исключено:** со стороны сайта удаления не допускаются; метод и ключ убраны из кода. |

Имена ключей — предложение; финал согласовать с единым префиксом `registration_webhook_*` в проекте.
