<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

define('DS_FORM_LOAD', true);
define('DS_FORM_ROOT', dirname(__FILE__));

/*
'BX'     - for CMS BITRIX
'MODx'   - for CMS MODx Revolution
'Joomla' - for CMS Joomla!
'WP'     - for CMS WordPress
'OC'     - for CMS OpenCart
false    - not popular CMS
*/
define('AUTH_CMS', false);

function autoloadForm($className) {
	if(file_exists(DS_FORM_ROOT . '/classes/'.$className .'.php')) {
		include_once DS_FORM_ROOT . '/classes/'.$className .'.php';
	}
}
function autoloadConstructor($className) {
    if(file_exists(DS_FORM_ROOT . '/constructor/classes/'.$className .'.php')) {
        include_once DS_FORM_ROOT . '/constructor/classes/'.$className .'.php';
    }
}

spl_autoload_register('autoloadForm');
spl_autoload_register('autoloadConstructor');

if(!DSMain::routing()) {
	include_once DS_FORM_ROOT . '/constructor/index.php';
}

?>
