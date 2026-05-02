# CRM registration refactor: wave 1

## Scope (S1-S6, minimum viable wave)

- Split registration flow into:
  - sync pre-check (`runSyncPreCheck`)
  - core CRM registration (`registerCoreInCrm`)
- Dedicated webhook operations in `RegisterUserCompany`:
  - unique email/phone check (`crmCheckUniqueEmailPhone`)
  - INN check (`crmCheckInnUniqueness`)
  - company.add (`crmAddCompany`)
  - contact.add (`crmAddContact`)
- Async post-register stage:
  - optional by toggle `async_post_register`
  - emits webhook payload with `contact_id`/`company_id` and `idempotency_key`
  - duplicate guard via lock file in `local/logs`

## Feature toggles

Defined in `local/modules/eklektika.sync/config.local.php`:

- `sync_legacy` (default `true`)
  - keeps legacy synchronous CRM registration path in `OnAfterUserRegisterHandler`.
- `async_post_register` (default `false`)
  - enables async webhook dispatch after successful local registration.
- `async_post_register_webhook_url` (default empty)
  - target n8n webhook URL.

## Contract clarifications for Team Lead

- Async webhook currently sends payload:
  - `event`, `site_user_id`, `email`, `phone`, `contact_id`, `company_id`, `inn`, `idempotency_key`.
- If `async_post_register=true` but URL is empty, dispatch is skipped with debug log.
- Duplicate protection is local-file based; distributed idempotency (shared store) is out of this wave.

## Backward compatibility

- Existing legacy sync flow remains active by default (`sync_legacy=true`).
- No changes to existing CRM UF mappings and no removal of legacy user UF contact field updates.
