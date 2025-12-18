<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();

class DSConstructor extends DSMain
{
    public $auth;
    public $session;
    public $formConfig;
    
    function __construct()
    {
        $this->request();
        $this->session = new OCSession;

        if(defined('AUTH_CMS') && AUTH_CMS) {
            $classAuthName = 'DSAuth' . AUTH_CMS;
            $this->auth = new $classAuthName;
        } else {
            $this->auth = new DSAuth($this);
        }

        $this->formConfig = new DSConfig;
    }

    public function loadTemplate($template, $data = array()) {
        if(sizeof($data)) extract($data);
        unset($data);
        ob_start();
            include DS_FORM_ROOT . '/constructor/template/' . $template . '.php';
            $renderTemplate = ob_get_contents();
        ob_end_clean();
        return $renderTemplate;
    }

    public function viewTemplate($template, $data = array()) {
        if(sizeof($data)) extract($data);
        unset($data);
        include DS_FORM_ROOT . '/constructor/template/' . $template . '.php';
    }

    public function auth() {

        if(!empty($this->post['login']) && !empty($this->post['password'])) {
           
            if($this->auth->authorization($this->post['login'], $this->post['password'])) {
                $this->responseJson(array('error' => false));
            } else {
                $this->responseJson(array('error' => true));
            }

        } else {
            $this->responseJson(array('error' => true));
        }
    }

    public function profileEdit() {
        if(empty($this->post['old_password']) || empty($this->post['new_password']) || empty($this->post['new_password2'])) {
            $this->responseJson(array('error' => 1, 'message' => 'Остались не заполненные поля!'));
        } elseif($this->post['new_password'] != $this->post['new_password2']) {
            $this->responseJson(array('error' => 2, 'message' => 'Пароли не совпадают!'));
        } elseif($this->post['old_password'] == $this->post['new_password']){
            $this->responseJson(array('error' => 3, 'message' => 'Новый и старый пароль совпадают!'));
        } elseif(preg_match('|[А-я]|siU', $this->post['new_password'])) {
            $this->responseJson(array('error' => 4, 'message' => 'Пароль не должен содержать русские буквы!'));
        } elseif(strlen($this->post['new_password']) < 6 || !preg_match('|^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$|siU', $this->post['new_password'])) {
            $this->responseJson(array('error' => 5, 'message' => 'Пароль слишком слабый!'));
        } else {
            $changePassword = $this->auth->changePassword($this->post['login'], $this->post['old_password'], $this->post['new_password']);
            switch ($changePassword) {
                case 1:
                    $this->responseJson(array('error' => false));
                    break;
                case 6:
                    $this->responseJson(array('error' => $changePassword, 'message' => 'Логин или старый пароль указаны не верно!'));
                    break;

                default:
                    $this->responseJson(array('error' => 7, 'message' => $changePassword));
                    break;
            }
        }
    }

    public function addCSlashes($str, $simbol = '\''){
        if(is_array($str)) {
            return array_map(array($this,'addCSlashes'), $str);
        }
        return addcslashes($str,$simbol);
    }

    public function getField() {
        $dataField  = $this->jsonDecode($this->post['dataField']);
        $fieldName  = strtolower($this->post['fieldName']);
        $data = (isset($dataField) && sizeof($dataField))? $dataField : array();
        $this->viewTemplate($fieldName, $dataField);
    }

    public function getCode() {
        $this->viewTemplate('getcode', array(
                                                'formId'      => $this->post['formId'],
                                                'mailSubject' => $this->post['mailSubject']
                                            )
        );
    }

    public function getJsonField() {
        foreach ($this->formConfig->formFields as $field) {
            $fieldType = $field['type'];
            unset($field['type']);
            $configform[] = array($fieldType => $this->jsonEncode($field));
        }
        return $configform;
    }

    public function globSelectForm() {
        foreach (glob(DS_FORM_ROOT . "/forms/*.php") as $filepath) {
            $formConfig = include $filepath;
            $selectForm[basename($filepath, ".php")] = isset($formConfig['mail']['subject'])? $formConfig['mail']['subject'] : '';
        }

        return $selectForm;
    }

    public function delForm($formId) {
        @unlink(DS_FORM_ROOT . '/css/forms/' . $formId . '.css');
        @unlink(DS_FORM_ROOT . '/forms/' . $formId . '.php');
    }

    public function saveForm() {
        $arrConfigForm = array();
        $configForm    = array();
        $settings      = array();
        $formId        = $this->post['formId'];
        $stylesheet    = (get_magic_quotes_gpc())? stripslashes($this->post['stylesheet']) : $this->post['stylesheet'];

        foreach ($this->post['settings'] as $setting) {
            $settings[$setting['name']] = $setting['value'];
        }

        $arrConfigForm['charset']  = $settings['charset'];

        $arrConfigForm['validate'] = array(
            'html5'  => (isset($settings['validate_html5'])
                         && $settings['validate_html5'] == 'on')? true : false,
            'strlen' => (int) $settings['validate_strlen'],
            'error'  => $settings['validate_error'],
        );

        $arrConfigForm['smtp'] = array(
            'on'         => (isset($settings['smtp_on'])
                             && $settings['smtp_on'] == 'on')? true : false,
            'host'       => $settings['smtp_host'],
            'secure'     => $settings['smtp_secure'],
            'port'       => (int) $settings['smtp_port'],
            'auth'       => (isset($settings['smtp_auth'])
                             && $settings['smtp_auth'] == 'on')? true : false,
            'username'   => $settings['smtp_username'],
            'password'   => $settings['smtp_password'],
            'from_email' => true,
        );

        $arrConfigForm['mail'] = array(
            'to_email'      => array_filter(explode(';', $settings['mail_to_email']), array($this, 'filterNotEmpty')),
            'cc_email'      => array_filter(explode(';', $settings['mail_cc_email']), array($this, 'filterNotEmpty')),
            'from_email'    => $settings['mail_from_email'],
            'from_name'     => $settings['mail_from_name'],
            'subject'       => $settings['mail_subject'],
            'reverse_email' => (isset($settings['reverse_email'])
                                && $settings['reverse_email'] == 'on')? true : false,
        );

        foreach ($this->post['configForm'] as $config) {
            $arrConfigFild = array();

            $arrConfigFild['type'] = strtolower(trim($config['title']));

            $config['field'] = $this->jsonDecode(htmlspecialchars_decode($config['data-field']));
            $config['field']['container'] = (isset($config['field']['container']))? $config['field']['container'] : false;
            array_walk_recursive($config['field'], array($this, 'booleanFilter'));

            $arrConfigFild = array_merge($arrConfigFild, $config['field']);
            $configForm[]  = $arrConfigFild;
        }

        $arrConfigForm['configform'] = $configForm;

        try {
            $this->saveInFile($formId, $arrConfigForm, $stylesheet);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $this->responseJson(array('error'=>1, 'message'=> $errorMessage));
            return false;
        }

        $this->responseJson(array('error'=>0));

    }

    private function saveInFile($formId, $arrConfigForm, $stylesheet) {
        if($stylesheet != "/*\n#idформы-form {\n\n}\n*/" || $stylesheet != "/*\n#{$formId}-form {\n\n}\n*/") {
            if(file_exists(DS_FORM_ROOT . '/css/forms/' . $formId . '.css')) {
                if(!@copy(DS_FORM_ROOT . '/css/forms/' . $formId . '.css',
                          DS_FORM_ROOT . '/css/forms/' . $formId . '.css.bak')) {
                    throw new Exception('Невозможно создать копию файла '
                                      . $formId . '.css. Проверьте права на папку "'
                                      . DS_FORM_ROOT . '/css/forms/".');
                }
            }

            $this->writeInFile(
                               DS_FORM_ROOT . '/css/forms/' . $formId . '.css',
                               $stylesheet,
                               'Невозможно сохранить файл ' . DS_FORM_ROOT . '/css/forms/' . $formId . '.css'
                               );
        }

        if(file_exists(DS_FORM_ROOT . '/forms/' . $formId . '.php')) {
            if(!@copy(DS_FORM_ROOT . '/forms/' . $formId . '.php',
                      DS_FORM_ROOT . '/forms/' . $formId . '.php.bak')) {
                throw new Exception('Невозможно создать копию файла '
                                  . $formId . '.php. Проверьте права на папку "'
                                  . DS_FORM_ROOT . '/forms/".');
            }
        }

        $this->writeInFile(
                           DS_FORM_ROOT . '/forms/' . $formId . '.php',
                           "<?php\n\nreturn " . var_export($arrConfigForm, true) . ";",
                           'Невозможно сохранить файл ' . DS_FORM_ROOT . '/forms/' . $formId . '.php'
                           );

        return true;
    }

    public function writeInFile($path, $data, $errorMessage){
        $file = @fopen($path, "w");
        if(!$file) throw new Exception($errorMessage);
        if(!@fwrite($file, $data)) {
            fclose($file);
            throw new Exception($errorMessage);
        };
        fclose($file);

        @chmod($path, 0777);
    }

    private function booleanFilter(&$value, $key) {
        if($value === 'true') $value = true;
        if($value === 'false') $value = false;
    }

    private function filterNotEmpty($element) {
        return !empty($element);
    }

}