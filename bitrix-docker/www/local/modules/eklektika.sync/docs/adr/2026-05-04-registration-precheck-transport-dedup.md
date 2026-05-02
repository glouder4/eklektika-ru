# ADR: единый транспорт и пречек уникальности контакта (без дублирования)

## Статус

Принято (2026-05-04).

## Контекст

Дублировались: HTTP POST на named webhooks регистрации (`CrmRegistrationOrchestrator::postRegistrationWebhook` vs `CrmRegistrationN8nTransport::post` без `B24_REST_PREFIX` в payload), разбор ответа `crm-check-unique-contact-v1` в оркестраторе и в `AjaxRegisterCrmContactPrecheck`, копия `haltIfDuplicateContactFromCrmCheck`.  
`assertProbeWebhookContract` требовал `success=1` до обработки отказа CRM при HTTP 200 с `success=0`.

## Решение

1. **Транспорт:** `postRegistrationWebhook` → делегирование в `CrmRegistrationN8nTransport::post` (enrich + единый `HttpClient`).
2. **Пречек unique:** `CrmRegistrationOrchestrator::crmCheckUniqueContact` → `AjaxRegisterCrmContactPrecheck::checkUniqueContactInCrm`.
3. **Дубликат контакта:** вызовы `AjaxRegisterCrmContactPrecheck::haltIfDuplicateContactFromCrmCheck` вместо копии в оркестраторе.
4. **Контракт probe:** в `assertProbeWebhookContract` и `assertProbeListWebhookContract` при `success=0` в теле — выход без `throw` (отказ CRM обрабатывается ниже).
5. **URL из конфига** для проверки `company_updates`: `CrmRegistrationN8nTransport::resolveRegistrationWebhookUrl`; дублирующие `resolveRegistrationWebhookPath` в оркестраторе удалены.
6. **ИНН / add company|contact:** `isProbableN8nErrorResponseBody` и тексты отказа пречека — через `CrmRegistrationN8nPrecheckResponse` (без копий в оркестраторе).

## Последствия

- Все named registration webhooks из PHP получают тот же payload enrich, что и AJAX-пречек.
- Поведение на `success:0` согласовано с `AjaxRegisterCrmContactPrecheck`.
- Меньше расхождения при правках контракта n8n.

## Ссылки

- `CrmRegistrationOrchestrator.php`
- `CrmRegistrationN8nTransport.php`
- `AjaxRegisterCrmContactPrecheck.php`
- `CrmRegistrationN8nPrecheckResponse.php`
