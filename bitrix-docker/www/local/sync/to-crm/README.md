# Канал to-crm (сайт → Bitrix24)

Исходящая логика с сайта на портал: обёртки в `local/classes/b24/`, `Request::sendRequest` → `URL_B24` + `local/classes/site_requests_handler.php` на стороне CRM.

По мере рефакторинга новые классы размещать здесь и подключать из `classes/requires.php` или автозагрузки.
