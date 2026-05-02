# S2: Миграция тестов registration + конфиг PHPUnit/Composer

- Главная задача: `../README.md`
- Статус: `done`

## Результат

- Тесты перенесены: `modules/eklektika.b24.registration/tests/Unit/CrmRegistrationN8nPrecheckResponseTest.php`
- NS тестов: `Eklektika\Tests\EklektikaB24Registration\`
- `local/composer.json`: `psr-4` для модуля и SUT `AjaxRegister`
- `local/phpunit.xml`: отдельные suite по модулю (`eklektika.b24.registration`, …), без дублирования каталогов; `source` для покрытия AjaxRegister / from-crm
