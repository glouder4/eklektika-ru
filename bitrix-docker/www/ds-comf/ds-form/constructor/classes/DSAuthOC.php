<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();


require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

// Autoloader
function autoloadOCClasses($class) {
    $file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';
    
    if (is_file($file)) {
        include_once($file);
        return true;
    }
    
    return false;
}
spl_autoload_register('autoloadOCClasses');

class DSAuthOC {

        private $user_id;
        private $username;
        public $change = false;
     
        function __construct()
        {
            $this->db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
            $this->request = new Request();
            $this->session = new OCSession();

            if (isset($this->session->data['user_id'])) {
                $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$this->session->data['user_id'] . "' AND status = '1'");

                if ($user_query->num_rows) {
                    $this->user_id = $user_query->row['user_id'];
                    $this->username = $user_query->row['username'];

                } else {
                    $this->logout();
                }
            }
        }

        public function isAuth() {
            return !empty($this->user_id)? $this->user_id : false;
        }

        public function authorization($login, $password) {
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $this->db->escape($login) . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($password) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");

            if ($user_query->num_rows) {
                $this->session->data['user_id'] = $user_query->row['user_id'];

                $this->user_id = $user_query->row['user_id'];
                $this->username = $user_query->row['username'];

                return true;
            } else {
                return false;
            }
        }
         
        public function getLogin() {
            return $this->username;
        }

        public function logout() {
            unset($this->session->data['user_id']);

            $this->user_id = '';
            $this->username = '';
        }

}