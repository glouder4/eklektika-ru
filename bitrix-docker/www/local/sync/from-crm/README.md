# Канал from-crm (CRM → сайт)

- **`InboundGateway.php`** — маршрутизация `ACTION` → `OnlineService\Site\*` / `OnlineService\B24\User`.
- Точка входа HTTP: `/local/classes/ajax.php` (подключает `../sync/bootstrap.php`).
