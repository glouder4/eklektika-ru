# S3: Unit-волна `eklektika.sync`

- Главная задача: `../README.md`
- Статус: `done`

## Результат

- Добавлены тесты `modules/eklektika.sync/tests/Unit/CrmInboundUfMapTest.php` для `OnlineService\Sync\FromCrm\CrmInboundUfMap`.
- В `composer.json` добавлены `psr-4` для `OnlineService\Sync\FromCrm\` и `Eklektika\Tests\EklektikaSync\`.
- В `phpunit.xml` добавлен suite `eklektika.sync` и включение `lib/from-crm` в coverage source.
