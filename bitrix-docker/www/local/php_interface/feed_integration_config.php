<?php

/**
 * Пример конфигурации фида. Скопируйте в feed_integration_config.php и задайте значения.
 *
 * @return array{regenerate_token?: string, site_base_url?: string}
 */
return [
    // Секрет для HTTP-регенерации: /local/modules/eklektika.feed/public/regenerate_yandex_yml.php?token=...
    'regenerate_token' => '10cb7238dd2a07616bb5c3a895eb4d507f0921db5ec7309aabe60764cccd7b57',

    // Базовый URL в абсолютных ссылках фида при запуске из CLI/cron
    'site_base_url' => 'https://new.eklektika.ru',
];
