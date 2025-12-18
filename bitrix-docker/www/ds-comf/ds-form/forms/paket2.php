<?php

return array(
	'charset'  => 'utf-8',
	'validate' => array(
		'html5'  => false,
		'strlen' => 3,
		'error'  => 'Поля не заполнены или слишком короткие!',
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
		'to_email'      => array('team@eklektika.ru'),
		'cc_email'      => array(),
		'from_email'    => '',
		'from_name'     => 'Info',
		'subject'       => 'Форма обратной связи',
		'reverse_email' => false,
	),
	'configform' => array(
		/*--Заголовок--*/
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="form-head">Добавить отзыв</div>',
			),
		/*--Ваше имя--*/
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => 'Ваше имя (*)',
			'error'     => 'Поле "имя" заполнено некорректно!',
			'formail'   => true,
			'name_mail' => 'Имя',
			'attributs' => array(
							'id'          => 'youname',
							'name'        => 'name',
							'type'        => 'text',
							'placeholder' => 'Ваше имя',
							'value'       => '',
							'required'    => '',
							'autofocus'   => '',
						   ),
			),
		/*--Ваш e-mail--*/
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => 'Ваш e-mail (*)',
			'error'     => 'Поле "e-mail" заполнено некорректно!',
			'formail'   => true,
			'name_mail' => 'E-mail',
			'attributs' => array(
							'id'          => 'youemail',
							'name'        => 'email',
							'type'        => 'text',
							'placeholder' => 'Ваш e-mail',
							'required'    => '',
							'pattern'     => '^([a-z,._,.\-,0-9])+@([a-z,._,.\-,0-9])+(\.([a-z])+)+$',
						   ),
			),
		/*--Ваше сообщение--*/
		array(
			'type'      => 'textarea',
			'container' => true,
			'label'     => 'Ваш отзыв',
			'formail'   => true,
			'name_mail' => 'Отзыв',
			'attributs' => array(
							'name'        => 'message',
							'type'        => 'text',
							'placeholder' => 'Ваше сообщение',
							'rows'        => '8',
							'cols'        => '46',
							'value'       => '',
						   ),
			),
		/*--Кнопка--*/
		array(
			'type'      => 'input',
			'container' => true,
			'class'     => 'buttonform',
			'attributs' => array(
							'type'  => 'submit',
							'value' => 'Отправить',
						   ),
		),
		/*--Блок ошибок--*/
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="error_form"></div>',
		),
	),
);
