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
      0 => 's.murzin@demis.ru',
    ),
    'cc_email' => 
    array (
    ),
    'from_email' => 'info@constructor.gray-group.ru',
    'from_name' => 'Info',
    'subject' => 'ааа',
    'reverse_email' => false,
  ),
  'configform' => 
  array (
    0 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '<div class="form-head">Калькулятор рассчета стоимости получения допуска(Екатеринбург)</div>',
      'container' => false,
    ),
    1 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '<table>
  <tbody>
    <tr>
      <td>',
      'container' => false,
    ),
    2 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '<p style="font-weight:bold">Тип СРО:</p>
<p>СРО строителей</p>
<p style="font-weight:bold">Нужен ли ген.подряд?</p>',
      'container' => false,
    ),
    3 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'podrad[]',
        'type' => 'radio',
        'value' => 'да',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'да',
      'name_mail' => 'Нужен ли ген.подряд?',
      'error' => '',
    ),
    4 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'podrad[]',
        'type' => 'radio',
        'value' => 'Нет',
        'checked' => '',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Нет',
      'name_mail' => 'Нужен ли ген.подряд?',
      'error' => '',
    ),
    5 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '</td>
<td>',
      'container' => false,
    ),
    6 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '<p style="font-weight:bold">Страхование ответственности</p>',
      'container' => false,
    ),
    7 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'strahovanie[]',
        'type' => 'radio',
        'value' => 'Заключить договор страхования',
        'checked' => '',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Заключить договор страхования',
      'name_mail' => 'Страхование ответственности',
      'error' => '',
    ),
    8 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'strahovanie[]',
        'type' => 'radio',
        'value' => 'Отказываюсь от страхования ответственности',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Отказываюсь от страхования ответственности',
      'name_mail' => 'Страхование ответственности',
      'error' => '',
    ),
    9 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'strahovanie[]',
        'type' => 'radio',
        'value' => 'Уже застрахованы',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Уже застрахованы',
      'name_mail' => 'Страхование ответственности',
      'error' => '',
    ),
    10 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '<p style="font-weight:bold">Особые условия</p>',
      'container' => false,
    ),
    11 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'osobie',
        'type' => 'checkbox',
        'value' => 'Опасные работы',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Опасные работы',
      'name_mail' => 'Особые условия',
      'error' => '',
    ),
    12 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => 'osobie',
        'type' => 'checkbox',
        'value' => 'Атомные работы',
      ),
      'container' => true,
      'id' => '',
      'class' => '',
      'formail' => true,
      'label' => 'Атомные работы',
      'name_mail' => 'Особые условия',
      'error' => '',
    ),
    13 => 
    array (
      'type' => 'freearea',
      'id' => '',
      'class' => '',
      'value' => '			</td>
		</tr>
	</tbody>
</table>',
      'container' => false,
    ),
    14 => 
    array (
      'type' => 'select',
      'attributs' => 
      array (
        'name' => 'price',
      ),
      'container' => true,
      'id' => '',
      'class' => 'fond',
      'formail' => true,
      'label' => 'Выберите размер компенсационного фонда:',
      'name_mail' => 'Размер компенсационного фонда',
      'error' => '',
      'options' => 
      array (
        0 => 
        array (
          'text' => '300 ТЫС. РУБЛЕЙ (выполнение генподрядных работ на сумму не более 10 млн. рублей)',
          'selected' => '',
        ),
        1 => 
        array (
          'text' => '500 ТЫС. РУБЛЕЙ (выполнение генподрядных работ на сумму не более 60 млн. рублей)',
        ),
        2 => 
        array (
          'text' => '1 МЛН. РУБЛЕЙ (выполнение генподрядных работ на сумму не более 500 млн. рублей)',
        ),
        3 => 
        array (
          'text' => '2 МЛН. РУБЛЕЙ (выполнение генподрядных работ на сумму не более 3 млрд. рублей)',
        ),
        4 => 
        array (
          'text' => '3 МЛН. РУБЛЕЙ (выполнение генподрядных работ на сумму не более 10 млрд. рублей)',
        ),
        5 => 
        array (
          'text' => '10 МЛН. РУБЛЕЙ (выполнение генподрядных работ на сумму до 10 млрд. рублей и более)',
        ),
      ),
    ),
    15 => 
    array (
      'type' => 'input',
      'attributs' => 
      array (
        'name' => '',
        'type' => 'submit',
        'value' => 'Рассчитать',
      ),
      'container' => true,
      'id' => '',
      'class' => 'buttonform',
      'label' => '',
      'name_mail' => '',
      'error' => '',
    ),
    16 => 
    array (
      'type' => 'freearea',
      'container' => false,
      'value' => '<div class="error_form"></div>',
    ),
  ),
);