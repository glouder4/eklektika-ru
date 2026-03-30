# Шаблон: новая синхронизируемая сущность

1. Строка в [functional-contract.md](functional-contract.md) §1 (ключи B24 ↔ сайт).
2. Политика в §2: удаление / отвязка, если применимо.
3. Если трафик CRM → сайт: новый `ACTION` в `from-crm/InboundGateway.php` + обработчик в `local/classes/site/` или `b24/`.
4. Если трафик сайт → CRM: сценарий в `b24/` и обработчик на портале при необходимости.
5. Строка в [inventory.md](inventory.md) и пункт в [regression-checklist.md](regression-checklist.md).
