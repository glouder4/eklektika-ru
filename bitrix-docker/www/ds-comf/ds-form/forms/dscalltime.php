<?php

return array(
	'mail'  => array(
		'to_email'   => array('a@b.ru'),
        'subject'    => 'Форма обратной связи',
	),
	'configform' => array(
        /* HTML код */
        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => '<div class="form-head">Заказать звонок</div>'
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
        /*--Позвонить--*/
        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => "<div class=\"calltime clearfix\">\n<label>Позвонить:</label>",
        ),
        /*--Время С--*/
        array(
            'type'      => 'select',
            'container' => true,
            'class'     => 'fromcall inline',
            'label'     => 'c',
            'formail'   => 1,
            'name_mail' => '<div style = "text-align: right;padding-right: 10px;">Позвонить с</div>',
            'attributs' => array(
                            'name'=> 'callfrom',
                           ),
            'options' => array(
                            array('text'=> '08-00', 'value' => '08-00'),
                            array('text'=> '09-00', 'value' => '09-00', 'selected'=> ''),
                            array('text'=> '10-00', 'value' => '10-00'),
                            array('text'=> '11-00', 'value' => '11-00'),
                            array('text'=> '12-00', 'value' => '12-00'),
                            array('text'=> '13-00', 'value' => '13-00'),
                            array('text'=> '14-00', 'value' => '14-00'),
                            array('text'=> '15-00', 'value' => '15-00'),
                            array('text'=> '16-00', 'value' => '16-00'),
                            array('text'=> '17-00', 'value' => '17-00'),
                            array('text'=> '18-00', 'value' => '18-00'),
                            array('text'=> '19-00', 'value' => '19-00'),
                            array('text'=> '20-00', 'value' => '20-00'),
                        ),
            ),
        /*--Время До--*/
        array(
            'type'      => 'select',
            'container' => true,
            'class'     => 'tocall inline',
            'label'     => 'до',
            'formail'   => 1,
            'name_mail' => '<div style = "text-align: right;padding-right: 10px;">Позвонить до</div>',
            'attributs' => array(
                            'name'=> 'callto',
                           ),
            'options'   => array(
                                array('text'=> '08-00', 'value' => '08-00'),
                                array('text'=> '09-00', 'value' => '09-00'),
                                array('text'=> '10-00', 'value' => '10-00'),
                                array('text'=> '11-00', 'value' => '11-00'),
                                array('text'=> '12-00', 'value' => '12-00'),
                                array('text'=> '13-00', 'value' => '13-00'),
                                array('text'=> '14-00', 'value' => '14-00'),
                                array('text'=> '15-00', 'value' => '15-00'),
                                array('text'=> '16-00', 'value' => '16-00'),
                                array('text'=> '17-00', 'value' => '17-00'),
                                array('text'=> '18-00', 'value' => '18-00', 'selected'=> ''),
                                array('text'=> '19-00', 'value' => '19-00'),
                                array('text'=> '20-00', 'value' => '20-00'),
                            ),
            ),
        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => '</div>',
        ),
        /*--Текст для звездочки--*/
        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => '<div class="infoform">Поля, отмеченные <strong>*</strong>, обязательны для заполнения</div>',
        ),
        /*--Кнопка--*/
        array(
            'type'      => 'input',
            'container' => true,
            'class'     => 'buttonform',
            'attributs' => array(
                                'type'=> 'submit',
                                'value'=> 'Отправить',
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
