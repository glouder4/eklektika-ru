<?php

/**
 * Публичный entrypoint YML-фида: /feed/yandex.yml
 * (физический путь + правило в urlrewrite.php)
 */

require $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.feed/public/yandex-yml.php';
