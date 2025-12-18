<?php

return array(
	'mail'  => array(
		'to_email'   => array('team@eklektika.ru'),
		'subject'    => 'Форма обратной связи',
	),
	'configform' => array(
        /* HTML код */
        array(
        'type'      => 'freearea',
        'container' => false,
        'value'     => '<div class="form-head">Заявка на разработку сувенирной продукции</div>'
        ),
        /* Однострочный текст */
        /* Многострочный текст */ 

        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => '<div class="form-body">'
            ),

    
 array(
    'type' => 'textarea', 
    'label' => 'Наименование компании*',
    'error'=>'Поле "Наименование компании" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'Наименование компании',
    'attributs' => array(
         'id'=>'field-id144822',
         'name'=>'fieldname144822',
         'placeholder'=>'',
         'value'=>'', 
         'required'    => 'required',
         ), 
   ),
   
   
    
   /* Многострочный текст */ 
    array(
    'type' => 'textarea', 
    'label' => 'Сайт компании',
    'error'=>'Поле "Сайт компании" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'Сайт компании',
    'attributs' => array(
         'id'=>'field-id328356',
         'name'=>'fieldname328356',
         'placeholder'=>'',
         'value'=>'', 
         ), 
   ),
   
   
    
   /* Поле выбора  */ 
    array(
    'type' => 'select',
    'label' => 'Ваша целевая аудитория', 
    'name_mail'=>'Ваша целевая аудитория',
    'formail'=>1, 
    'attributs' => array(
         'name'=>'fieldname350825',
         ),
        'options' => array( 
                array('text'=>'Сотрудникам', 'value' => 'Сотрудникам', ),
                array('text'=>'Деловым партнёрам', 'value' => 'Деловым партнёрам', ), 
                array('text'=>'Другое', 'value' => 'Другое', ), 
         ), 
   ),
   
   
    
   /* Многострочный текст */ 
    array(
    'type' => 'textarea', 
    'label' => 'Ваши конкуренты и в чем ваше отличие от них',
    'error'=>'Поле "Ваши конкуренты и в чем ваше отличие от них" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'Ваши конкуренты и в чем ваше отличие от них',
    'attributs' => array(
         'id'=>'field-id230464',
         'name'=>'fieldname230464',
         'placeholder'=>'Добавить комментарий',
         'value'=>'', 
         ), 
   ),
   
   
    
   /* Поле выбора  */ 
    array(
    'type' => 'select', 
    'label' => 'Сфера деятельности',
    'name_mail'=>'',
    'formail'=>1, 
    'attributs' => array(
         'name'=>'fieldname495666',
         ),
        'options' => array( 
            array('text'=>'Стройматериалы (производители)', 'value' => 'Стройматериалы (производители)', ),
            array('text'=>'Эл.тех. промыш. и приборостроение', 'value' => 'Эл.тех. промыш. и приборостроение', ),
            array('text'=>'Авиационные предприятия', 'value' => 'Авиационные предприятия', ),
            array('text'=>'Банки, финансовые учреждения', 'value' => 'Банки, финансовые учреждения', ),
            array('text'=>'Ритейл (В2С)', 'value' => 'Ритейл (В2С)', ),
            array('text'=>'Энергетика', 'value' => 'Энергетика', ),
            array('text'=>'Благотворительные и соц. службы', 'value' => 'Благотворительные и соц. службы', ),
            array('text'=>'Строительство', 'value' => 'Строительство', ),
            array('text'=>'Медицинское оборудование', 'value' => 'Медицинское оборудование', ),
            array('text'=>'Досуг, развлечения, шоу-бизнес', 'value' => 'Досуг, развлечения, шоу-бизнес', ),
            array('text'=>'Интернет и IT-технологии', 'value' => 'Интернет и IT-технологии', ),
            array('text'=>'Легкая промышленность', 'value' => 'Легкая промышленность', ),
            array('text'=>'СМИ (ТВ, радио, пресса, интернет-медиа)', 'value' => 'СМИ (ТВ, радио, пресса, интернет-медиа)', ),
            array('text'=>'Услуги для бизнеса', 'value' => 'Услуги для бизнеса', ),
            array('text'=>'Машиностроение, оборудование', 'value' => 'Машиностроение, оборудование', ),
            array('text'=>'Логистика, экспедиционные услуги', 'value' => 'Логистика, экспедиционные услуги', ),
            array('text'=>'Производство мебели', 'value' => 'Производство мебели', ),
            array('text'=>'Хим. и нефтехим. промышленность', 'value' => 'Хим. и нефтехим. промышленность', ),
            array('text'=>'HoReCa (кафе, рестораны, отели)', 'value' => 'HoReCa (кафе, рестораны, отели)', ),
            array('text'=>'Оптовая торговля (дистр., дилеры)', 'value' => 'Оптовая торговля (дистр., дилеры)', ),
            array('text'=>'Сельское хозяйство', 'value' => 'Сельское хозяйство', ),
            array('text'=>'Фармацевтика', 'value' => 'Фармацевтика', ),
            array('text'=>'Наука и образование', 'value' => 'Наука и образование', ),
            array('text'=>'Гос. и муниципальные службы', 'value' => 'Гос. и муниципальные службы', ),
            array('text'=>'Топливная промышленность(нефть газ)', 'value' => 'Топливная промышленность(нефть газ)', ),
            array('text'=>'Другое', 'value' => 'Другое', ),
            array('text'=>'Железнодорожный транспорт', 'value' => 'Железнодорожный транспорт', ),
            array('text'=>'Спортивные организации', 'value' => 'Спортивные организации', ),
            array('text'=>'Здравоохранительные учреждения', 'value' => 'Здравоохранительные учреждения', ),
            array('text'=>'Телекоммуникации и связь', 'value' => 'Телекоммуникации и связь', ),
            array('text'=>'Недвижимость (аренда, продажа)', 'value' => 'Недвижимость (аренда, продажа)', ),
            array('text'=>'Автодилер', 'value' => 'Автодилер', ),
            array('text'=>'Металлургия', 'value' => 'Металлургия', ),
            array('text'=>'Рекламное агентство', 'value' => 'Рекламное агентство', ),
            array('text'=>'Издательское дело', 'value' => 'Издательское дело', ),
            array('text'=>'Потребительские товары (FMCG)', 'value' => 'Потребительские товары (FMCG)', ),
            array('text'=>'Типография', 'value' => 'Типография', ),
            array('text'=>'Ивент агентство', 'value' => 'Ивент агентство', ),
            array('text'=>'Туризм и отдых', 'value' => 'Туризм и отдых', ),
            array('text'=>'Добывающая промышленность (кроме Нефти и газа)', 'value' => 'Добывающая промышленность (кроме Нефти и газа)', ),
            array('text'=>'Атомная промышленность', 'value' => 'Атомная промышленность', ),
            array('text'=>'Культура, искусство', 'value' => 'Культура, искусство', ),
            array('text'=>'Авиа транспорт', 'value' => 'Авиа транспорт', ),
            array('text'=>'Страхование', 'value' => 'Страхование', ),
            array('text'=>'Прочие отрасли', 'value' => 'Прочие отрасли', ),
            array('text'=>'Прочая отрасль', 'value' => 'Прочая отрасль', ),
            array('text'=>'Физическое лицо', 'value' => 'Физическое лицо', ),
         ), 
   ),
   
   
    
   /* Многострочный текст */ 
    array(
    'type' => 'textarea', 
    'label' => 'Есть ли у вашей компании фирменный стиль, брендбук?',
    'error'=>'Поле "Есть ли у вашей компании фирменный стиль, брендбук?" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'Есть ли у вашей компании фирменный стиль, брендбук?',
    'attributs' => array(
         'id'=>'field-id660596',
         'name'=>'fieldname660596',
         'placeholder'=>'Добавить комментарий',
         'value'=>'', 
         ), 
   ),
   
   
    
   /* Файл */ 
    array(
    'type' => 'input', 
    'label' => 'Прикрепить документ',
    'formail' => 1,
    'name_mail' => 'документ',
    'attributs' => array(
         'id'=>'field-id209988',
         'type'=>'file',
         'name'=>'myfiles[]', 
         ), 
   ),

//         array(
//             'type'      => 'freearea',
//             'container' => false,
//             'value'     => '<div class="form-checkboxes">'
//             ),

//             array(
//                 'type'      => 'freearea',
//                 'container' => false,
//                 'value'     => '<label>Что требуется разработать</label>'
//                 ),

//                 array(
//                     'type'      => 'freearea',
//                     'container' => false,
//                     'value'     => '<div class="checkboxes">'
//                 ),

//    /* Чекбокс */ 
//  array(
//     'type' => 'input', 
//     'label' => 'Каталог',
//     'formail' => 1,
//     'name_mail'=>'Каталог',
//     'attributs' => array(
//          'id'=>'field-id307624',
//          'name'=>'fieldname307624',
//          'type'=>'checkbox',
//          'value'=>'', 
//          ), 
//    ),
   
   
    
//    /* Чекбокс */ 
//     array(
//     'type' => 'input', 
//     'label' => 'Календарь',
//     'formail' => 1,
//     'name_mail'=>'Календарь',
//     'attributs' => array(
//          'id'=>'field-id50114',
//          'name'=>'fieldname50114',
//          'type'=>'checkbox',
//          'value'=>'', 
//          ), 
//    ),
   
   
    
//    /* Чекбокс */ 
//     array(
//     'type' => 'input', 
//     'label' => 'Прочее',
//     'formail' => 1,
//     'name_mail'=>'Прочее',
//     'attributs' => array(
//          'id'=>'field-id27201',
//          'name'=>'fieldname27201',
//          'type'=>'checkbox',
//          'value'=>'', 
//          ), 
//    ),
   
   
    
//    /* Чекбокс */ 
//     array(
//     'type' => 'input', 
//     'label' => 'Открытка',
//     'formail' => 1,
//     'name_mail'=>'Открытка',
//     'attributs' => array(
//          'id'=>'field-id109437',
//          'name'=>'fieldname109437',
//          'type'=>'checkbox',
//          'value'=>'', 
//          ), 
//    ),
   
   
    
//    /* Чекбокс */ 
//     array(
//     'type' => 'input', 
//     'label' => 'Букет',
//     'formail' => 1,
//     'name_mail'=>'Букет',
//     'attributs' => array(
//          'id'=>'field-id16061',
//          'name'=>'fieldname16061',
//          'type'=>'checkbox',
//          'value'=>'', 
//          ), 
//    ),

//    array(
//     'type'      => 'freearea',
//     'container' => false,
//     'value'     => '</div>'
// ),

//    array(
//     'type' => 'textarea', 
//     'label' => '',
//     'error'=>'Поле заполнено некорректно',
//     'formail' => 1,
//     'name_mail'=>'checkboxes comment',
//     'attributs' => array(
//          'id'=>'field-id230464',
//          'class'=>'checkboxes-comment',
//          'name'=>'fieldname230464',
//          'placeholder'=>'Комментарий',
//          'value'=>'', 
//          ), 
//    ),

//    array(
//     'type'      => 'freearea',
//     'container' => false,
//     'value'     => '</div>'
//     ),




    array(
     'type' => 'textarea', 
     'label' => 'Технические характеристики макета',
     'error'=>'Поле "Технические характеристики макета" заполнено некорректно',
     'formail' => 1,
     'name_mail'=>'Технические характеристики макета',
     'attributs' => array(
          'id'=>'field-id230464',
          'name'=>'fieldname230464',
          'placeholder'=>'Комментарий',
          'value'=>'', 
          ), 
    ),

    array(
        'type' => 'select',
        'label' => 'Для какого мероприятия разрабатывается макет', 
        'name_mail'=>'Для какого мероприятия разрабатывается макет',
        'formail'=>1, 
        'attributs' => array(
             'name'=>'fieldname350825',
            ),
        'options' => array( 
            array('text'=>'Welcome-pack сотрудникам', 'value' => 'Welcome-pack сотрудникам', ),
            array('text'=>'Выставочные материалы', 'value' => 'Выставочные материалы', ),
            array('text'=>'Материалы для конференции', 'value' => 'Материалы для конференции', ),
            array('text'=>'Материалы для обучения', 'value' => 'Материалы для обучения', ),
            array('text'=>'Новогодняя продукция', 'value' => 'Новогодняя продукция', ),
            array('text'=>'День рождения компании', 'value' => 'День рождения компании', ),
            array('text'=>'Корпоративный отраслевой праздник', 'value' => 'Корпоративный отраслевой праздник', ),
            array('text'=>'Другое', 'value' => 'Другое', ),
        ), 
    ),

    array(
        'type' => 'textarea', 
        'label' => 'Информация, которую необходимо использовать в макете',
        'error'=>'Поле "Информация, которую необходимо использовать в макете" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Информация, которую необходимо использовать в макете',
        'attributs' => array(
             'id'=>'field-id230464',
             'name'=>'fieldname230464',
             'placeholder'=>'Комментарий',
             'value'=>'', 
        ), 
    ),

    // array(
    //     'type'      => 'freearea',
    //     'container' => false,
    //     'value'     => '<div class="form-checkboxes2">'
    // ),

    // array(
    //     'type'      => 'freearea',
    //     'container' => false,
    //     'value'     => '<label>Потребуются ли дополнительные услуги</label>'
    //     ),

    //     array(
    //         'type'      => 'freearea',
    //         'container' => false,
    //         'value'     => '<div class="checkboxes">'
    //     ),

    // array(
    //     'type' => 'input', 
    //     'label' => 'Фотосессия',
    //     'formail' => 1,
    //     'name_mail'=>'Фотосессия',
    //     'attributs' => array(
    //          'id'=>'field-id307624',
    //          'name'=>'fieldname307624',
    //          'type'=>'checkbox',
    //          'value'=>'', 
    //          ), 
    //    ),

    //    array(
    //     'type' => 'input', 
    //     'label' => '3D моделинг',
    //     'formail' => 1,
    //     'name_mail'=>'3D моделинг',
    //     'attributs' => array(
    //          'id'=>'field-id307624',
    //          'name'=>'fieldname307624',
    //          'type'=>'checkbox',
    //          'value'=>'', 
    //          ), 
    //    ),

    //    array(
    //     'type' => 'input', 
    //     'label' => 'Услуги копирайтера',
    //     'formail' => 1,
    //     'name_mail'=>'Услуги копирайтера',
    //     'attributs' => array(
    //          'id'=>'field-id307624',
    //          'name'=>'fieldname307624',
    //          'type'=>'checkbox',
    //          'value'=>'', 
    //          ), 
    //    ),

    //    array(
    //     'type'      => 'freearea',
    //     'container' => false,
    //     'value'     => '</div>'
    // ),

    //        array(
    //     'type' => 'textarea', 
    //     'label' => '',
    //     'error'=>'Поле заполнено некорректно',
    //     'formail' => 1,
    //     'name_mail'=>'дополнительные услуги комментарий',
    //     'attributs' => array(
    //          'id'=>'field-id230464',
    //          'class'=>'checkboxes-comment',
    //          'name'=>'fieldname230464',
    //          'placeholder'=>'',
    //          'value'=>'', 
    //     ), 
    // ),

    // array(
    //     'type'      => 'freearea',
    //     'container' => false,
    //     'value'     => '</div>'
    // ),




    array(
        'type' => 'textarea', 
        'label' => 'Цветовое решение',
        'error'=>'Поле "Цветовое решение" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Цветовое решение',
        'attributs' => array(
             'id'=>'field-id230464',
             'name'=>'fieldname230464',
             'placeholder'=>'',
             'value'=>'', 
        ), 
    ),


    array(
        'type'      => 'freearea',
        'container' => false,
        'value'     => '<div class="form-checkboxes">'
    ),

    array(
        'type'      => 'freearea',
        'container' => false,
        'value'     => '<label>В каком стиле должен быть выдержан макет</label>'
        ),

        array(
            'type'      => 'freearea',
            'container' => false,
            'value'     => '<div class="checkboxes">'
        ),

    array(
        'type' => 'input', 
        'label' => 'Консервативный',
        'formail' => 1,
        'name_mail'=>'Консервативный',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Динамичный',
        'formail' => 1,
        'name_mail'=>'Динамичный',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Современный',
        'formail' => 1,
        'name_mail'=>'Современный',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Ретро',
        'formail' => 1,
        'name_mail'=>'Ретро',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Молодежный',
        'formail' => 1,
        'name_mail'=>'Молодежный',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Минималистичный',
        'formail' => 1,
        'name_mail'=>'Минималистичный',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),
       
       array(
        'type' => 'input', 
        'label' => 'В стилистике бренд-бука компании',
        'formail' => 1,
        'name_mail'=>'В стилистике бренд-бука компании',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type' => 'input', 
        'label' => 'Иное',
        'formail' => 1,
        'name_mail'=>'Иное',
        'attributs' => array(
             'id'=>'field-id307624',
             'name'=>'fieldname307624',
             'type'=>'checkbox',
             'value'=>'', 
             ), 
       ),

       array(
        'type'      => 'freearea',
        'container' => false,
        'value'     => '</div>'
    ),

           array(
        'type' => 'textarea', 
        'label' => '',
        'error'=>'Поле заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'',
        'attributs' => array(
             'id'=>'field-id230464',
             'class'=>'checkboxes-comment',
             'name'=>'fieldname230464',
             'placeholder'=>'',
             'value'=>'', 
        ), 
    ),

    array(
        'type'      => 'freearea',
        'container' => false,
        'value'     => '</div>'
    ),




    array(
        'type' => 'textarea', 
        'label' => 'Примеры дизайна, который Вам нравится',
        'error'=>'Поле "Примеры дизайна, который Вам нравится" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Примеры дизайна, который Вам нравится',
        'attributs' => array(
             'id'=>'field-id230464',
             'name'=>'fieldname230464',
             'placeholder'=>'',
             'value'=>'', 
        ), 
    ),

    array(
        'type' => 'textarea', 
        'label' => 'Примеры дизайна, который Вам НЕ  нравится',
        'error'=>'Поле "Примеры дизайна, который Вам НЕ  нравится" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Примеры дизайна, который Вам НЕ  нравится',
        'attributs' => array(
             'id'=>'field-id230464',
             'name'=>'fieldname230464',
             'placeholder'=>'',
             'value'=>'', 
        ), 
    ),

    array(
        'type' => 'textarea', 
        'label' => 'Дополнительные требования и пожелания',
        'error'=>'Поле "Дополнительные требования и пожелания" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Дополнительные требования и пожелания',
        'attributs' => array(
             'id'=>'field-id230464',
             'name'=>'fieldname230464',
             'placeholder'=>'',
             'value'=>'', 
        ), 
    ),

        
   /* Однострочный текст */ 
   array(
    'type' => 'input', 
    'label' => 'Ваш телефон',
    'error'=>'Поле "Ваш телефон" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'Телефон',
    'attributs' => array(
         'id'=>'field-id138516',
         'name'=>'phone',
         'type'=>'text',
         'placeholder'=>'+ 7 (___) ___-__-__', 
         'value'=>'',
         'pattern'=>'^\+?[\d,\-,(,),\s]+$', 
         'mask'=>'+7 (999) 999-99-99',
         ), 
   ),
      /* Однострочный текст */ 
      array(
        'type' => 'input', 
        'label' => 'Ваше имя*',
        'error'=>'Поле "Ваше имя" заполнено некорректно',
        'formail' => 1,
        'name_mail'=>'Имя',
        'attributs' => array(
             'id'=>'field-id60576',
             'name'=>'fieldname60576',
             'type'=>'text',
             'placeholder'=>'Как к Вам обращаться?', 
             'value'=>'',
             'pattern'=>'', 
             'required'    => 'required',
             ), 
       ),

    
   /* Однострочный текст */ 
    array(
    'type' => 'input', 
    'label' => 'Ваш e-mail*',
    'error'=>'Поле "Ваш e-mail" заполнено некорректно',
    'formail' => 1,
    'name_mail'=>'E-mail',
    'attributs' => array(
         'id'=>'field-id162966',
         'name'=>'email',
         'type'=>'text',
         'placeholder'=>'email@email.com', 
         'value'=>'',
         'pattern'=>'^([a-z,A-Z,._,.\-,0-9])+@([a-z,A-Z,._,.\-,0-9])+(\.([a-z, A-Z])+)+$', 
         'required'    => 'required',
         ), 
   ),

   array(
    'type'      => 'freearea',
    'container' => false,
    'value'     => '</div>'
    ),
    
   array(
    'type'      => 'freearea',
    'container' => false,
    'value'     => '<div class="form-bottom">Нажимая на кнопку, вы соглашаетесь на обработку персональных данных</div>'
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
        'value'     => '<div class="error_form"></div>
        <script src="/ds-comf/ds-form/js/jquery.mask.min.js"></script>
        <script>
            $(`#dscallme [name="phone"]`).mask(`+7 (999) 999-99-99`);
            
            $(`.content #dscallme .buttonform input`).on(`click`, function () {
		if ($(`textarea[name="fieldname144822"]`).val() != "" && $(`input[name="fieldname60576"]`).val() != "" && $(`input[name="email"]`).val() != "") {
			ym(1087753, `reachGoal`, `otprav_razrabotk`);
			console.log(`Отправка формы на странице Заявка на разработку сувенирной продукции`);
		}
	});
        </script>',
        ),
    ),
);
