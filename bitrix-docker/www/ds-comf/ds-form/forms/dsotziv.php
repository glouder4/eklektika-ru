<?php
return array(
	'mail'  => array(
		'to_email' => array('a@b.ru'),
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
			'formail'   => 1,
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
			'label'     => 'Ваш e-mail',
			'formail'   => 1,
			'name_mail' => 'E-mail',
			'attributs' => array(
				'id'          => 'youemail',
				'name'        => 'email',
				'type'        => 'text',
				'placeholder' => 'Ваш e-mail',
				'pattern'     => '^([a-z,._,.\-,0-9])+@([a-z,._,.\-,0-9])+(\.([a-z])+)+$',
			),
		),
		/*--Ваше сообщение--*/
		array(
			'type'      => 'textarea',
			'container' => true,
			'label'     => 'Ваш отзыв (*)',
			'error'     => 'Поле "Отзыв" заполнено некорректно!',
			'formail'   => 1,
			'name_mail' => 'Отзыв',
			'attributs' => array(
				'name'        => 'message',
				'type'        => 'text',
				'rows'        => '8',
				'cols'        => '46',
				'value'       => '',
				'required'    => '',
				'placeholder' => '',
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