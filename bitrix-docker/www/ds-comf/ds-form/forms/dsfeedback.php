<?php

return array (
  'charset' => 'utf-8',
  'validate' => 
  array (
    'html5' => false,
    'strlen' => 3,
    'error' => 'Поля не заполнены или слишком короткие!',
  ),
  'smtp' => 
  array (
    'on' => false,
    'host' => '',
    'secure' => 'ssl',
    'port' => 465,
    'auth' => true,
    'username' => '',
    'password' => '',
    'from_email' => true,
  ),
  'mail' => 
  array (
    'to_email' => 
    array (
      0 => 'a@b.ru',
    ),
    'cc_email' => 
    array (
    ),
    'from_email' => 'info@constructor.gray-group.ru',
    'from_name' => 'Info',
    'subject' => 'Форма обратной связи',
    'reverse_email' => false,
  ),
  'configform' => 
  array (
    0 => 
    array (
      'type' => 'freearea',
      'container' => true,
      'id' => '',
      'class' => '',
      'value' => '<div class="form-head">
  <span>Заказать звонок</span>
</div>',
    ),
    1 => 
    array (
      'type' => 'input',
      'container' => true,
      'label' => 'Ваше имя',
      'error' => 'Поле "Ваше имя" заполнено некорректно',
      'formail' => 1,
      'name_mail' => 'Имя',
      'attributs' => 
      array (
        'id' => 'field-id203412',
        'name' => 'field-name203412',
        'type' => 'text',
        'placeholder' => 'Как к Вам обращаться?',
        'value' => '',
        'required' => 'required',
        'pattern' => '',
      ),
    ),
    2 => 
    array (
      'type' => 'input',
      'container' => true,
      'label' => 'Ваш телефон (*)',
      'error' => 'Поле "Ваш телефон" заполнено некорректно',
      'formail' => 1,
      'name_mail' => 'Телефон',
      'attributs' => 
      array (
        'id' => 'field-id238580',
        'name' => 'field-name238580',
        'type' => 'text',
        'placeholder' => '+ 7 (___) ___-__-__',
        'value' => '',
        'required' => 'required',
        'pattern' => '^\\+?[\\d,\\-,(,),\\s]+$',
      ),
    ),
    3 => 
    array (
      'type' => 'input',
      'container' => true,
      'class' => 'buttonform',
      'attributs' => 
      array (
        'type' => 'submit',
        'value' => 'Отправить',
      ),
    ),
    4 => 
    array (
      'type' => 'freearea',
      'container' => false,
      'value' => '<div class="error_form"></div>',
    ),
  ),
);