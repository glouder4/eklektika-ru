# Задача: дедупликация транспорта и пречека unique-contact

## Статус

Закрыта (реализация в коде + ADR).

## Цель

Убрать двойную реализацию POST на webhooks регистрации и разбор `crm-check-unique-contact-v1`; выровнять контракт `assertProbeWebhookContract` с отказом CRM (`success=0`).

## Критерии готовности

- [x] `postRegistrationWebhook` делегирует в `CrmRegistrationN8nTransport::post`.
- [x] `crmCheckUniqueContact` делегирует в `AjaxRegisterCrmContactPrecheck::checkUniqueContactInCrm`.
- [x] `assertProbe*`: не валить контрактом тело с `success=0` при HTTP 2xx.
- [x] ADR: `docs/adr/2026-05-04-registration-precheck-transport-dedup.md`

## Субзадачи

1. [x] Рефакторинг `CrmRegistrationOrchestrator` (транспорт, delegate, assert, INN/add вспомогательные вызовы).
2. [x] Документация: ADR, обновление комментария в `AjaxRegisterCrmContactPrecheck`.

## Next steps for Team Lead

- Прогнать регрессию регистрации на стенде (AJAX + legacy `OnBeforeUserRegister` без `OS_REGISTER_USER_PRECHECK_DONE`).
- При появлении отдельного n8n-логгера — сверить payload (наличие `B24_REST_PREFIX` на INN/unique webhooks).

## Аудит

- Код-ревью: единая точка POST; отсутствие дублирующих private-хелперов пречека в оркестраторе.
- Риск: регресс на стороне n8n, если сценарий опирался на «старый» payload без `B24_REST_PREFIX` (теперь enrich везде).
