<?php

declare(strict_types=1);

/**
 * Unit tests: no Bitrix prolog. Composer autoload only.
 */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Missing vendor/. Run: composer install (from local/)\n");
    exit(1);
}

require $autoload;

// После изменений в composer.json: `composer dump-autoload`. Fallback только если autoload не поднял класс.
if (!class_exists(\OnlineService\Sync\FromCrm\CrmInboundUfMap::class, true)) {
    $ufMap = dirname(__DIR__) . '/modules/eklektika.sync/lib/from-crm/CrmInboundUfMap.php';
    if (is_file($ufMap)) {
        require_once $ufMap;
    }
}
