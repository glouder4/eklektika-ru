# S3: Verification plan (E2E + failure modes)

- Главная задача: `docs/tasks/2026-05-01-registration-crm-unique-contact-stopper/README.md`
- Статус: `todo` (ожидается выполнение на окружении)

## Цель

Подтвердить, что после исправления регистрация продолжает выполнение после unique-contact pre-check и “доживает” до downstream шагов, не ломая существующие сценарии.

## Входы

- Исправление инцидента (удаление debug‑stop).
- Runbook: `docs/runbook-registration-crm-webhooks.md`

## Выходы

- Чеклист проверок на окружении (staging/production canary).
- Подтверждение факта downstream выполнения (по логам n8n + логам/метрикам сайта).

## План верификации

### A) Happy path

- Регистрация, где выполняется `crm-check-unique-contact-v1` и далее требуется CRM‑ветка (контакт/компания/биндинги по текущей реализации).
- Ожидание: после unique-contact наблюдается запуск downstream операций:
  - по execution traces в n8n,
  - и по артефактам сайта в `local/logs/async-register-*.log` (если задействован async_post_register).

### B) Negative path (pre-check reject)

- Дубликат email/phone: unique-contact возвращает бизнес-отказ.
- Ожидание: регистрация корректно отклоняется, downstream шаги не выполняются (и это объяснимо), без PHP halt.

### C) Degradation / failure modes (минимум)

- Временный сбой downstream webhook (если есть): фиксируется retry/ошибка по runbook, но **нет** “молчаливой” остановки PHP.

## Индивидуальные критерии готовности

1. Happy path подтвержден на целевом окружении (или максимально приближенном).
2. Negative path подтвержден.
3. Есть артефакты проверки: ссылки/выжимки логов n8n и/или метрики из `async-register-metrics.log`, если задействовано.

## Чеклист проверки результата

- [ ] Зафиксирована дата/окно прогона и используемые флаги.
- [ ] Приложены ID/корреляция хотя бы для одного успешного прогона.
- [ ] Нет `dead_letter_*` событий в окне canary (или есть объяснение/rollback).

