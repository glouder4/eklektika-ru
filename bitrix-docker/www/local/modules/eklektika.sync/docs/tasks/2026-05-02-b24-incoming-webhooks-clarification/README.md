# Задача: пояснить границу «входящие вебхуки B24» vs named n8n webhooks

## Цель

Ответить на вопрос оператора: сколько **входящих вебхуков** создавать в Bitrix24 при текущей архитектуре (сайт → n8n → B24 REST).

## Результат

- ADR: `modules/eklektika.sync/docs/adr/2026-05-02-b24-incoming-webhooks-single-prefix.md`
- Справочник: раздел в `docs/reference/registration-n8n-webhooks.md`

## Критерии готовности

- [x] Зафиксировано: по умолчанию один B24 incoming webhook + `EKLEKTIKA_B24_REST_PREFIX` в n8n
- [x] Зафиксировано: множественные ключи конфига — про n8n, не про количество webhooks в B24
- [x] Описаны случаи для >1 входящего вебхука B24

## Подзадачи

| ID | Файл | Статус |
|----|------|--------|
| 01 | `subtasks/01-adr-b24-single-prefix.md` | done |
| 02 | `subtasks/02-reference-cross-link.md` | done |
