<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();

class DSAuth {
    private $constructor;
    private $login;
    private $password;
    public $change = false;
 
    function __construct($constructor)
    {
        $this->constructor = $constructor;
        $config = include DS_FORM_ROOT . '/constructor/config.php';
        $config = unserialize($config);
        $this->login = $config['login'];
        $this->password = $config['password'];
        $this->change = $config['change'];
    }

    public function isAuth() {
        if (isset($this->constructor->session->data["is_auth"])) {
            return $this->constructor->session->data["is_auth"];
        }
        else return false;
    }

    public function authorization($login, $passwors) {
        if ($login == $this->login && md5($passwors) == $this->password) {
            $this->constructor->session->data["is_auth"] = true;
            $this->constructor->session->data["login"] = $login;
            return true;
        } else {
            $this->constructor->session->data["is_auth"] = false;
            return false; 
        }
    }
     
    public function getLogin() {
        if ($this->isAuth()) {
            return $this->constructor->session->data["login"];
        }
    }

    public function logout() {
        $this->constructor->session->data = array();
        $this->constructor->session->destroy();
    }

    public function changePassword($login, $oldPassword, $newPassword) {
        if($login == $this->login && md5($oldPassword) == $this->password) {
            try {
                $this->writeConfig($login, $newPassword);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $this->logout();
            return 1;
        } else {
            return 6;
        }
    }

    private function writeConfig($login, $password, $change = false){
        $path = DS_FORM_ROOT . '/constructor/config.php';
        $arrConfig = array('login' => $login, 'password' => md5($password), 'change' => $change);
        $data = "<?php\nreturn '". $this->constructor->addCSlashes(serialize($arrConfig)) . "';";
        $this->constructor->writeInFile($path, $data, 'Невозможно сохранить настройки!');
    }

}