# S4: Graphify wave note для следующего покрытия

- Главная задача: `../README.md`
- Статус: `done`

## Graph context (MCP `graphify-eklektika-ru-site`, BFS depth 3)

Стартовые узлы precheck/n8n связали кластер регистрации:

- **Ядро разбора ответов:** `CrmRegistrationN8nPrecheckResponse` — методы `registrationPrecheckResponseIndicatesSuccess`, `unwrapWebhookResult`, `formatCrmPrecheckRejectionMessage`, `isProbableN8nErrorResponseBody` (первая волна unit уже покрыта).
- **Транспорт URL/payload:** `CrmRegistrationN8nTransport` — `enrichRegistrationWebhookPayload`, `resolveN8nOutboundB24RestPrefix`, `resolveRegistrationWebhookUrl`, `resolveAsyncPostRegisterWebhookUrl`, `formatRegistrationWebhookFailureMessage`, `post` (следующая волна: выделить pure-хелперы и мокать HTTP).
- **Связка с пайплайном:** `AjaxRegisterCrmContactPrecheck` ↔ `AjaxRegisterActionService::registerStepCrmPrecheckContactUniqueViaN8n`.
- **Смежный модуль:** `RegisterUserCompany` (usersync) — webhooks `postRegistrationWebhook`, async post-register; интеграционные тесты только со второй фазы.

## Очередность покрытия (roadmap)

1. **Unit:** чистые функции в `CrmRegistrationN8nTransport` без вызова Bitrix Option (через извлечение конфигурации или тестовые doubles).
2. **Unit:** полевая логика в `AjaxRegisterCrmContactPrecheck`, где можно подставить fake transport.
3. **Integration (отдельный suite):** Bitrix bootstrap + orchestrator — после явного `tests/bootstrap-bitrix.php` и политики запуска только в подготовленном окружении.

## Блокеры integration-фазы

- Единый Bitrix prolog для CLI и загрузка `eklektika.b24.registration`.
- Тестовые дублёры n8n (mock server или fixture JSON).
- Разделение suite в `phpunit.xml` (`unit` vs `integration`, группы `@group`).

## Чеклист проверки результата

- [x] Указаны конкретные классы/узлы из registration cluster.
- [x] Приоритизация следующей волны обоснована связностью графа.
- [x] Перечислены блокеры integration suite.
