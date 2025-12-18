<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();


require_once $_SERVER['DOCUMENT_ROOT'].'/manager/config.core.php'; 
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php'; 
require_once MODX_CORE_PATH.'model/modx/modx.class.php'; 

class DSAuthMODx {

        private $modx;
        public $change = false;
     
        function __construct()
        {
            $this->modx = new modX();
            $this->modx->initialize('web');
        }

        public function isAuth() {
            return $this->modx->user->isAuthenticated('web');
        }

        public function authorization($login, $password) {
            $logindata = array(
                'username'   => $login,
                'password'   => $password,
                'rememberme' => false
            );
            $response = $this->modx->runProcessor('/security/login', $logindata);
            if ($response->isError()) {
                return false; 
            } else {
                return true;
            }
        }
         
        public function getLogin() {
            if ($this->modx->user->isAuthenticated('web')) {
                return $this->modx->user->get('username');
            }
        }

        public function logout() {
            $this->modx->runProcessor('/security/logout');
        }

}