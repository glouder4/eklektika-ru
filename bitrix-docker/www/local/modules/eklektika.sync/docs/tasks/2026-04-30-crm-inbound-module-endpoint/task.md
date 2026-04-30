# Задача: перенос входящего CRM endpoint в модуль eklektika.sync

## ADR

- **Контекст:** Обработчик канала CRM → сайт жил в `local/classes/ajax.php`, вне модуля с доменной логикой sync.
- **Решение:** Каноническая реализация — `local/modules/eklektika.sync/public/inbound_crm.php`; единый путь в коде — `OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH`. Константы транспорта B24 дублируют строку пути в `RestTransportConfig` (порядок `include` до автозагрузки sync).
- **Совместимость:** `local/classes/ajax.php` оставлен как однострочный шим на модульный скрипт.

## Сделано

- Публичный endpoint модуля, класс `CrmInboundEndpoint`, автозагрузка в `include.php`.
- Обновлены `RestTransportConfig`, `RestClient` (комментарий), шаблон `os-personal-profile`, `InboundSecurity`, `config.local.php` (комментарий), `functional-contract.md`.

## Риски

- Внешние системы с захардкоженным `/local/classes/ajax.php` продолжат работать (шим); при жёсткой привязке к длине URL или проверкам пути — проверить.
- Веб-сервер должен отдавать PHP из `local/modules/eklektika.sync/public/` (стандартно для Bitrix).

## Критерии готовности

- Прямой вызов канонического URL и вызов через шим дают одинаковое поведение.
