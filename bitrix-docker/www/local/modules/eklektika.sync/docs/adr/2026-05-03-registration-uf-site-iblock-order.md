# ADR: порядок заполнения UF элемента сайта на компании CRM (`UF_CRM_1774915439581`)

## Статус

Принято (2026-05-03).

## Контекст

Поле `UF_CRM_1774915439581` (константа `CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF`) хранит **ID элемента инфоблока «компания на сайте»**. Возник вопрос: передавать ли его в `crm.company.add` и нужен ли **отдельный** вебхук только под `crm.company.update`.

## Решение (как в коде)

1. **Сначала** в сценарии регистрации существует компания в B24: создана через именованный вебхук n8n `crm-company-add-v1` (`registration_webhook_company_add_url`) или **найдена** по ИНН/привязке.
2. **Затем** контакт, привязка контакта к компании, реквизиты — по ветке `CrmRegistrationOrchestrator::createB24Company` / `registerCoreInCrm`.
3. **Локальный** элемент инфоблока на сайте **создаётся после** этого обмена: метод `upsertSiteCompanyLinkByB24Id()` сначала вызывает `createCompanyElement`, получает `$iblockElementId`, **потом** через **вебхук n8n** (`callB24Method('crm.company.update', …)` → `registration_webhook_crm_company_update_url`) вызывается метод B24 [`crm.company.update`](https://apidocs.bitrix24.ru/api-reference/crm/companies/crm-company-update.html) с полем `UF_CRM_1774915439581` = ID элемента (**не** прямой REST с PHP на портал).
4. Передавать `UF_CRM_1774915439581` в **`crm.company.add` нельзя** в типичном сценарии: **ID элемента каталога на сайте ещё не существует** в момент первого создания компании в CRM.
5. Для `crm.company.update` задан **отдельный** конфиг-ключ `registration_webhook_crm_company_update_url` (отдельный path/workflow n8n для этого метода).

## Последствия

- Если UF не заполняется, диагностировать: доходит ли выполнение до `upsertSiteCompanyLinkByB24Id`, успешен ли `createCompanyElement`, не режет ли n8n прокси вызов `crm.company.update`.
- Документ процесса: `docs/business-processes/registration/README.md` (сегмент 3).

## Ссылки

- `CrmRegistrationOrchestrator::upsertSiteCompanyLinkByB24Id`
- `CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF`
- `docs/business-processes/registration/verification-checklist.md` (шаги с `crm.company.update`)
