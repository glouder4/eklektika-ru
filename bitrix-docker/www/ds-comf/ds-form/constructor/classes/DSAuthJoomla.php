<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();

define('_JEXEC', 1);

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/defines.php'))
{
    include_once $_SERVER['DOCUMENT_ROOT'] . '/defines.php';
}

if (!defined('_JDEFINES'))
{
    define('JPATH_BASE', $_SERVER['DOCUMENT_ROOT']);
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

session_destroy();

//echo JVersion::RELEASE;

class DSAuthJoomla {

	private $app;
	private $user;
    public $change = false;
 
    function __construct()
    {
       $this->app  = JFactory::getApplication('administrator');
       $this->user = JFactory::getUser();
    }

    public function isAuth() {
    	return (!$this->user->id)? false : true;
    }

    public function authorization($login, $password) {
		$credentials = array('username' => $login, 'password' => $password);
		$options     = array('remember' => false);
    	if($this->app->login($credentials, $options)){
    	    return true;
    	} else {
            return false; 
        }
    }
     
    public function getLogin() {
        return $this->user->username;
    }

    public function logout() {
    	$this->app->logout($this->user->id);
    }

}