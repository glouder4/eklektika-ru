<?php

return array(
	'mail'  => array(
		'to_email'   => array('a@b.ru'),
		'subject'    => 'Форма купить в один клик',
	),
	'configform' => array(
		/* HTML код */
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="form-head">Купить в один клик</div>'
		),

		/* Поле для превьюшки */
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="field-thumbnail"><img src="//placehold.it/340x223" alt=""></div>'
		),

		/* Поле для превьюшки */
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="field-itemTitle">Телевизор 32" LG 32LF650V Black/Silver</div>'
		),

		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="field-float clearfix">'
		),
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '
				<div class="group-fields field-info">
					<input type="button" value="&minus;" class="button-quantity quantity-minus" field="field-quantity">
			'
		),
		array(
			'type'      => 'input',
			'label'     => '',
			'container' => false,
			'class'     => '',
			'error'     => '',
			'formail'   => 1,
			'name_mail' => 'Количество',
			'attributs' => array(
				'id'          => 'field-quantity',
				'name'        => 'field-quantity',
				'type'        => 'number',
				'placeholder' => '',
				'value'       => '1',
				'min'         => '1',
				'step'        => '1',
				'required'    => 'required',
				'pattern'     => '',
			),
		),
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '
					<input type="button" value="+" class="button-quantity quantity-plus" field="field-quantity">
				</div>
			'
		),
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => '',
			'class'     => 'field-cost',
			'error'     => '',
			'formail'   => 1,
			'name_mail' => '',
			'attributs' => array(
				'id'          => 'field-cost',
				'name'        => 'field-cost',
				'class'        => 'focusout',
				'type'        => 'number',
				'placeholder' => '',
				'value'       => '26400',
				'readonly'    => 'readonly',
				'pattern'     => '',
			),
		),
		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '</div>'
		),

		/* Однострочный текст */
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => 'Ваше имя',
			'error'     => 'Поле "Ваше имя" заполнено некорректно',
			'formail'   => 1,
			'name_mail' => 'Имя',
			'attributs' => array(
				'id'          => 'field-id203412',
				'name'        => 'field-name203412',
				'type'        => 'text',
				'placeholder' => 'Как к Вам обращаться?',
				'value'       => '',
				'required'    => 'required',
				'pattern'     => '',
			),
		),

		/* Однострочный текст */
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => 'Ваш телефон (*)',
			'error'     => 'Поле "Ваш телефон" заполнено некорректно',
			'formail'   => 1,
			'name_mail' => 'Телефон',
			'attributs' => array(
				'id'          => 'field-id238580',
				'name'        => 'field-name238580',
				'type'        => 'text',
				'placeholder' => '+ 7 (___) ___-__-__',
				'value'       => '',
				'required'    => 'required',
				'pattern'     => '^\+?[\d,\-,(,),\s]+$',
			),
		),

		/* Однострочный текст */
		array(
			'type'      => 'input',
			'container' => true,
			'label'     => 'Email (*)',
			'error'     => 'Поле Email заполнено некорректно',
			'formail'   => 1,
			'name_mail' => 'Email',
			'attributs' => array(
				'id'          => 'field-id238580',
				'name'        => 'field-name238580',
				'type'        => 'text',
				'placeholder' => '',
				'value'       => '',
				'required'    => 'required',
				'pattern'     => '^\+?[\d,\-,(,),\s]+$',
			),
		),

		/*--Ваше сообщение--*/
		array(
			'type'      => 'textarea',
			'container' => true,
			'label'     => 'Комментарий',
			'error'     => 'Поле "Комментарий" заполнено некорректно!',
			'formail'   => 1,
			'name_mail' => 'Комментарий',
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

		array(
			'type'      => 'freearea',
			'container' => false,
			'value'     => '<div class="error_form"></div>
			<script><![CDATA[
				$(document).ready(function(){
					$(\'.quantity-plus\').click(function(e){
						e.preventDefault();
						fieldName = $(this).attr(\'field\');
						var currentVal = parseInt($(\'input[name=\'+fieldName+\']\').val());
						if (!isNaN(currentVal)) {
							$(\'input[name=\'+fieldName+\']\').val(currentVal + 1);
						} else {
							$(\'input[name=\'+fieldName+\']\').val(1);
						}
					});
					$(".quantity-minus").click(function(e) {
						e.preventDefault();
						fieldName = $(this).attr(\'field\');
						var currentVal = parseInt($(\'input[name=\'+fieldName+\']\').val());
						if (!isNaN(currentVal) && currentVal > 1) {
							$(\'input[name=\'+fieldName+\']\').val(currentVal - 1);
						} else {
							$(\'input[name=\'+fieldName+\']\').val(1);
						}
					});
				});
			]]></script>
			',
		),
	),
);
