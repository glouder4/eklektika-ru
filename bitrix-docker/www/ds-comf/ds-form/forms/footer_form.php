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

		'subject'    => 'Форма Обратная связь',

		'reverse_email'  => false,

	),

	'configform' => array(







/* HTML код */ 

array(

    'type' => 'freearea', 

    'value'=>'<div class="form-head"></div>' 

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

 'error'=>'Поле "Имя" заполнено некорректно',

 'formail' => 1,

 'label' => 'Контактные данные',

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

 'error'=>'Поле "Email" заполнено некорректно',

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

 'error'=>'Поле "Телефон" заполнено некорректно',

 'formail' => 1,

 'name_mail'=>'Телефон',

 'attributs' => array(

      'id'=>'field-id829',

      'name'=>'gygyruliusat',

      'type'=>'text',

      'placeholder'=>'Телефон *', 

      'required'=>'required', 

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

 'label' => 'Сообщение',

 'formail' => 1,

 'name_mail'=>'',

 'attributs' => array(

      'id'=>'field-id616691',

      'name'=>'litiertihili',

      'placeholder'=>'Сообщение',

      'value'=>'',

       

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
  array(

    'type' => 'freearea', 

    'value'=>'</div>' 

   ), 
  array(

    'type' => 'freearea', 

    'value'=>'<div><br><br></div>' 

   ), 
 array(

    'type' => 'freearea', 

    'value'=>'<div>' 

   ), 
 
    /* HTML код */ 

   array(

    'type' => 'freearea',  'required'=>'required',

    'value'=>'<input type="checkbox"   class="obrabotkap1" /> 

     Настоящим подтверждаю, что я ознакомлен и согласен с условиями  <a href="https://eklektika.ru/oferta/">политики конфиденциальности</a> ' 

   ),
 
  array(

    'type' => 'freearea', 

    'value'=>'</div>' 

   ), array(

    'type' => 'freearea', 

    'value'=>'<div></div>' 

   ), 
  array(

    'type' => 'freearea', 

    'value'=>'<div>' 

   ), 
   
 array(

    'type' => 'freearea',  'required'=>'required',

    'value'=>'<input type="checkbox"   class="obrabotkap1" /> 

     Я даю <a href="https://eklektika.ru/sogl.docx"> согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями ' 

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

	) 
	





	) 

);