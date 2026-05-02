# Фикстуры ответов n8n / CRM для регрессионных тестов

## Зачем

Фикстуры — это **снимки JSON-тел ответов** вебхуков после реальных прогонов на стенде. Тесты PHPUnit читают файлы из `samples/` (редакция, можно коммитить) или из `captured/` (локально, не коммитить секреты).

## Как снять ответ с реального webhook

1. Вызовите webhook тем же методом, что и прод (обычно **POST**, `Content-Type: application/json`). Пример каркаса:

```bash
curl -sS -X POST "$WEBHOOK_URL" \
  -H 'Content-Type: application/json; charset=UTF-8' \
  -d '{"METHOD":"crm.contact.list","PARAMS":{}}' \
  | tee /tmp/webhook-response.json
```

Для registration-веток тело возьмите из справочника `docs/reference/registration-n8n-webhooks.md` и из логов вашего сценария.

2. Проверьте HTTP **200** и валидный JSON.

3. **Обезличьте** перед сохранением в репозиторий:
   - URL/домены n8n, токены, пароли, полные email/телефоны реальных людей;
   - замените ID сущностей CRM на вымышленные (например `107` → `1001`), если это не ломает смысл контракта.

4. Сохраните:
   - для общего репозитория — скопируйте в `samples/` с суффиксом **`.anon.json`**;
   - черновики «как есть» — только в `captured/` (каталог по умолчанию в `.gitignore`).

## Именование `samples/*.anon.json`

Рекомендуется:

`{pipeline}-{outcome}.anon.json`

Примеры: `precheck-unique-empty-result.anon.json`, `precheck-inn-reject.anon.json`.

## Связь с тестами

Классы в `modules/eklektika.b24.registration/tests/WebhookFixtures/` загружают JSON и проверяют разбор через `CrmRegistrationN8nPrecheckResponse` и дальше по мере расширения.

Контракт ответов: `docs/reference/registration-n8n-webhooks.md`.
