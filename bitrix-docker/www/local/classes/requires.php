<?php
    require_once __DIR__.'/b24/Request.php';
    require_once __DIR__.'/b24/RegisterUserCompany.php';
    require_once __DIR__.'/b24/User.php';
    require_once __DIR__.'/site/UserGroups.php';
    require_once __DIR__.'/site/Company.php';
    require_once __DIR__.'/site/Manager.php';
    //require_once __DIR__.'/events.php';

// Модификация индексирования
\Bitrix\Main\EventManager::getInstance()->addEventHandler('search', 'BeforeIndex',
    array('\OnlineService\Classes\Handlers\Search\Stemming', 'BeforeIndexHandler')
);

\Bitrix\Main\Loader::registerAutoLoadClasses(null, array(
    '\OnlineService\Classes\Handlers\Search\Stemming' => '/local/php_interface/classes/handlers/search/stemming.php',
));