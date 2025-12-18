<?php

$DSconstructor = new DSConstructor;
$post = $DSconstructor->post;
$session = $DSconstructor->session->data;

$header = $DSconstructor->loadTemplate('header');
$footer = $DSconstructor->loadTemplate('footer');

if(isset($post['logout']) && !empty($post['logout'])) {
    $DSconstructor->auth->logout();
    header("Location: " . $DSconstructor->server['REQUEST_URI']);
}

if(!$DSconstructor->auth->isAuth()) {
    $data = array(
        'header'   => $header,
        'footer'   => $footer,
        'login'    => (isset($post['login']))? $post['login'] : '',
        'password' => (isset($post['password']))? $post['password'] : '',
    );
    $DSconstructor->viewTemplate('auth', $data);
    die;
}

if(isset($post['profile']) && !empty($post['profile']) || $DSconstructor->auth->change) {
    $data = array(
        'header'  => $header,
        'footer'  => $footer,
        'login'   => $DSconstructor->auth->getLogin(),
        'backUrl' => $DSconstructor->server['REQUEST_URI'],
    );
    $DSconstructor->viewTemplate('profile', $data);
    die;
}

if (isset($post['del-form']) && !empty($post['del-form'])) {
    $DSconstructor->delForm($post['del-form']);
    $post['form-id'] = '';
}

$copyForm = '';

if (isset($post['copy-form']) && !empty($post['copy-form'])) {
    $formId = '';
    $copyForm = $post['copy-form'];
    unset($DSconstructor->session->data['name-form']);
}elseif (isset($post['form-id'])) {
    $formId = $post['form-id'];
    $DSconstructor->session->data['name-form'] = $post['form-id'];
} elseif (isset($session['name-form'])) {
    $formId = $session['name-form'];
} else {
    $formId = '';
}

$data = array();

$data['formId']         = '';
$data['charset']          = 'utf-8';
$data['validateHtml5']    = false;
$data['validateStrlen']   = 3;
$data['validateError']    = 'Поля не заполнены или слишком короткие!';
$data['smtp']             = array('On' => false, 'Host' => '','Secure' => 'ssl', 'Port' => 465,
                                  'Auth' => true, 'Username' => '','Password' => '','FromEmail' => true,);
$data['mailToEmail']      = '';
$data['mailCcEmail']      = '';
$data['mailFromEmail']    = '';
$data['mailFromName']     = 'Info';
$data['mailSubject']      = '';
$data['mailReverseEmail'] = false;

$data['stylesheet'] = (!empty($formId))? "/*\n#{$formId}-form {\n\n}\n*/" : "/*\n#idформы-form {\n\n}\n*/";

if(!empty($formId) || !empty($copyForm)) {

    $configName = (!empty($copyForm))? $copyForm : $formId;

    try {
        if (!$DSconstructor->formConfig->getConfig($configName)) throw new Exception('Error file form!');
    } catch (Exception $e) {
        die($e->getMessage());
    }

    $data['formId']          = $formId;
    $data['charset']           = $DSconstructor->formConfig->charset;
    $data['validateHtml5']     = $DSconstructor->formConfig->validateHtml5;
    $data['validateStrlen']    = $DSconstructor->formConfig->validateStrlen;
    $data['validateError']     = $DSconstructor->formConfig->validateError;
    $data['smtp']['On']        = $DSconstructor->formConfig->smtpOn;
    $data['smtp']['Host']      = $DSconstructor->formConfig->smtpHost;
    $data['smtp']['Secure']    = $DSconstructor->formConfig->smtpSecure;
    $data['smtp']['Port']      = $DSconstructor->formConfig->smtpPort;
    $data['smtp']['Auth']      = $DSconstructor->formConfig->smtpAuth;
    $data['smtp']['Username']  = $DSconstructor->formConfig->smtpUsername;
    $data['smtp']['Password']  = $DSconstructor->formConfig->smtpPassword;
    $data['smtp']['FromEmail'] = $DSconstructor->formConfig->smtpFromEmail;
    $data['mailToEmail']       = implode(';', $DSconstructor->formConfig->mailToEmail);
    $data['mailCcEmail']       = implode(';', $DSconstructor->formConfig->mailCcEmail);
    $data['mailFromEmail']     = $DSconstructor->formConfig->mailFromEmail;
    $data['mailFromName']      = $DSconstructor->formConfig->mailFromName;
    $data['mailSubject']       = $DSconstructor->formConfig->mailSubject;
    $data['mailReverseEmail']  = $DSconstructor->formConfig->mailReverseEmail;

    if(file_exists(DS_FORM_ROOT . '/css/forms/' . $formId . '.css')) {
        $data['stylesheet'] = file_get_contents(DS_FORM_ROOT . '/css/forms/' . $formId . '.css');
        $data['stylesheet'] = str_replace('#idформы-form', '#' . $formId . '-form', $data['stylesheet']);
    }
 }

$leftColumn  = $DSconstructor->loadTemplate('left-column', $data);
$rightColumn = $DSconstructor->loadTemplate('right-column', $data);

$data['selectForm'] = $DSconstructor->globSelectForm();

$data['configform'] = array();

if((!empty($formId) || !empty($copyForm)) && isset($DSconstructor->formConfig->formFields)) {
    $data['configform'] = $DSconstructor->getJsonField();
}

$DSconstructor->viewTemplate('main', array(
                                        'header'      => $header,
                                        'footer'      => $footer,
                                        'leftColumn'  => $leftColumn,
                                        'rightColumn' => $rightColumn,
                                        'formId'      => $formId,
                                        'login'       => $DSconstructor->auth->getLogin(),
                                        'configform'  => $data['configform'],
                                        'selectForm'  => $data['selectForm'],
                                    )
                            );
?>