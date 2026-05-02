# Registration CRM Webhooks Runbook

## 1) Security and secret rotation

- Source of truth for inbound secret: env `EKLEKTIKA_SYNC_INBOUND_SECRET`.
- `local/modules/eklektika.sync/config.local.php` must not contain real secret values.
- Rotation steps:
  1. Generate a new random value (minimum 32 bytes).
  2. Put the new value into environment as `EKLEKTIKA_SYNC_INBOUND_SECRET`.
  3. Restart PHP-FPM/web worker on target environment.
  4. Update sender side (`X-Sync-Token` / `sync_token`) in CRM/n8n.
  5. Validate with one signed inbound test request.
  6. Revoke old value and record rotation timestamp in release notes.

## 2) Async post-register reliability model

- Dedupe key: `idempotency_key` in payload and `X-Idempotency-Key` header.
- Bounded retries: max 3 attempts with exponential backoff (`2s`, `4s`, `8s` bounded by code constants).
- State persistence:
  - `local/logs/async-register-state-<idempotency_key>.json`
  - `local/logs/async-register-lock-<idempotency_key>.lock`
- Dead-letter sink:
  - `local/logs/async-register-dead-letter.log` (JSON lines).

## 3) Observability and alert thresholds

Metrics stream (JSON lines):
- `local/logs/async-register-metrics.log`
- Prefix: `registration.async_post_register.*`

Key events:
- `delivered`
- `retry_scheduled`
- `dead_letter_delivery_failed`
- `dead_letter_max_attempts`
- `dedupe_success_skip`
- `dedupe_inflight_skip`
- `lock_open_failed`
- `skipped_empty_url`

Alert thresholds for canary/release:
- **Critical:** any `dead_letter_*` event in 5 minutes -> stop canary, switch `async_post_register=false`, investigate.
- **Warning:** retry ratio > 5% over 15 minutes (`retry_scheduled / delivered`) -> hold rollout step.
- **Warning:** > 3 `lock_open_failed` in 10 minutes -> check filesystem permissions/capacity.

## 4) Canary protocol and rollback

Feature toggles:
- `sync_legacy` (default `true`)
- `async_post_register` (default `false`)

Canary steps:
1. Baseline: `sync_legacy=true`, `async_post_register=false`.
2. Canary: enable `async_post_register=true` for limited traffic window.
3. Monitor metrics for 30 minutes against thresholds above.
4. Expand only if no critical alerts and retry ratio <= 5%.

Rollback:
1. Set `async_post_register=false` immediately.
2. Keep `sync_legacy=true` to preserve current production behavior.
3. Export and reconcile records from `async-register-dead-letter.log`.
4. Re-run enrichment manually via n8n for unresolved ids.

## 5) QA matrix (minimum for release)

- **Q1 Happy path legal entity:** local registration creates/links company and contact in CRM.
- **Q2 Happy path individual:** contact sync works without company branch regressions.
- **Q3 Duplicate email/phone pre-check:** registration rejected once, no duplicate CRM writes.
- **Q4 Async webhook transient fail:** retries happen, final success recorded as `delivered`.
- **Q5 Async webhook hard fail:** bounded attempts reached, dead-letter entry created.
- **Q6 Toggle compatibility:** `sync_legacy` and `async_post_register` combinations behave as expected.

## 6) Go/No-Go signoff artifact

Release signoff checklist:
- [ ] Security: secret removed from repo and env configured.
- [ ] Reliability: retry/dedupe/dead-letter verified on staging.
- [ ] Observability: metric log and alert rules validated.
- [ ] Canary: completed without critical alerts.
- [ ] Rollback rehearsal: executed and documented.
- [ ] QA matrix: all tests passed or accepted with explicit risk.

Decision block:
- **Go:** all checklist items passed, no open critical risk.
- **No-Go:** any critical risk, unresolved dead-letter spike, or failed rollback rehearsal.
