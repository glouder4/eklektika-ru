<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

class DSAuthBX {

    private $bxCUser;
    public $change = false;
 
    function __construct()
    {
        $this->bxCUser = (isset($USER) && is_object($USER))? $USER : new CUser;
    }

    public function isAuth() {
        return $this->bxCUser->IsAuthorized();
    }

    public function authorization($login, $password) {
        if (!is_array($this->bxCUser->Login($login, $password))) {
            return true;
        } else {
            return false; 
        }
    }
     
    public function getLogin() {
        if ($this->bxCUser->IsAuthorized()) {
            return $this->bxCUser->GetLogin();
        }
    }

    public function logout() {
        $this->bxCUser->Logout();
    }

}