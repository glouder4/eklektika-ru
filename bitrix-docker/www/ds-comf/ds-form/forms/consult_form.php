<?php

return array(
	'charset'  => 'utf-8',
	'validate' => array(
		'html5'  => false,
		'strlen' => 3,
		'error'  => 'Поля не заполнены либо слишком короткие',
	),
	'smtp'  => array(
        'on'         => false,
    	'host'       => 'smtp.mail.ru',
    	'secure'     => 'ssl',
    	'port'       => 465,
    	'auth'       => true,
    	'username'   => 'email@mail.ru',
    	'password'   => 'password',
    	'from_email' => true,
    ),
	'mail'  => array(
        'to_email'   => array('team@eklektika.ru', ),
        'cc_email'   => array('team@eklektika.ru', ),
		'from_email' => 'info@'.str_replace('www.', '', $_SERVER['HTTP_HOST']),
		'from_name'  => 'Info',
		'subject'    => 'Форма Закажите подробную консультацию',
		'reverse_email'  => false,
	),
	'configform' => array(



/* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'<div class="form-head">Закажите подробную консультацию</div>' 
   ),
   

/* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'<div>' 
   ),
   /* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'<div>' 
   ),
  
/* Однострочный текст */ 
 array(
 'type' => 'input', 
 'error'=>'Поле "" заполнено некорректно',
 'formail' => 1,
 'name_mail'=>'Имя',
 'attributs' => array(
      'id'=>'field-id639159',
      'name'=>'lierusonerru',
      'type'=>'text',
      'placeholder'=>'Имя *', 
      'value'=>'',
       'required'=>'required',
      'pattern'=>'', 
      ), 
),
 
/* Однострочный текст */ 
 array(
 'type' => 'input', 
 'error'=>'Поле "" заполнено некорректно',
 'formail' => 1,
 'name_mail'=>'E-mail',
 'attributs' => array(
      'id'=>'field-id334168',
      'name'=>'email',
      'type'=>'text',
      'placeholder'=>'E-mail *', 
      'value'=>'',
       'required'=>'required',
      'pattern'=>'^([a-z,A-Z,._,.\-,0-9])+@([a-z,A-Z,._,.\-,0-9])+(\.([a-z,A-Z])+)+$', 
      ), 
),
 
/* Однострочный текст */ 
 array(
 'type' => 'input', 
 'error'=>'Поле "" заполнено некорректно',
 'formail' => 1,
 'name_mail'=>'Телефон',
 'attributs' => array(
      'id'=>'field-id829',
      'name'=>'gygyruliusat',
      'type'=>'text',
      'placeholder'=>'Телефон', 
      'value'=>'',
      'pattern'=>'^\+?[\d,\-,(,),\s]+$', 
      ), 
),
 /* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'</div>' 
   ),
/* Многострочный текст */ 
 array(
 'type' => 'textarea', 
 'error'=>'Поле "" заполнено некорректно',
 'formail' => 1,
 'name_mail'=>'',
 'attributs' => array(
      'id'=>'field-id616691',
      'name'=>'litiertihili',
      'placeholder'=>'Сообщение *',
      'value'=>'',
       'required'=>'required', 
      ), 
),

/* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'</div>' 
   ),
 
/* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'<div>' 
   ),

/* Кнопка */
array(
	'type' => 'input',
	'class' => 'buttonform',
	'attributs' => array(
					'type'=>'submit',
					'value'=>'Отправить',
				   ),
	),

    /* HTML код */ 
 array(
    'type' => 'freearea', 
    'value'=>'<input type="checkbox" checked="checked" class="obrabotkap" /> 
    <p>Настоящим подтверждаю, что я ознакомлен и согласен с условиями <br> <a href="<?=SITE_URL?>/oferta/">политики конфиденциальности</a></p><br />' 
   ),
    /* HTML код */ 
array(
    'type' => 'freearea', 
    'value'=>'</div>' 
   ),

/* Блок ошибок */
array(
	'type' => 'freearea',
	'value' => '<div class="error_form"></div>',
	),
	


	),
);