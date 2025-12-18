<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();

global $wpdb, $buttonsnap, $current_user;

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

class DSAuthWP {

        public $change = false;
     
        public function isAuth() {
            return is_user_logged_in();
        }

        public function authorization($login, $password) {
            $logindata = array(
                'user_login'    => $login,
                'user_password' => $password,
                'remember'      => false,
            );
            
            $user = wp_signon($logindata, false);

            if (is_wp_error($user)) {
               return false;
            } else {
                return true;
            }
        }
         
        public function getLogin() {
            if (is_user_logged_in()) {
                return $current_user->user_login;
            }
        }

        public function logout() {
            wp_logout();
        }

}