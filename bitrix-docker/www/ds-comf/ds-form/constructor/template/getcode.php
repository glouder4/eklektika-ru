<div id="getcode">
	<h3>Подключение</h3>
	<p>Подключение скрипта формы в &lt;head /&gt;:</p>
	<pre>&lt;script type=&quot;text/javascript&quot; src=&quot;/ds-comf/ds-form/js/dsforms.js&quot;&gt;&lt;/script&gt;</pre>
	<p>Подключение скрипта формы и дополнительной библиотеки jQuery версии >= 1.5:</p>
<pre>&lt;script type=&quot;text/javascript&quot; src=&quot;/ds-comf/lib/jquery-1.11.3.min.js&quot;&gt;&lt;/script&gt;
&lt;script type=&quot;text/javascript&quot;&gt;
&nbsp;&nbsp;&nbsp;&nbsp;gKweri = $.noConflict(true);
&lt;/script&gt;
&lt;script type=&quot;text/javascript&quot; src=&quot;/ds-comf/ds-form/js/dsforms.js&quot;&gt;&lt;/script&gt;</pre>
	<h3>Инициализация</h3>
	<p>Размещение формы в контейнере, через атрибуты данных:</p>
	<pre>&lt;div id=&quot;<?php echo $formId;?>&quot; class=&quot;ds-form&quot;&gt;&lt;/div&gt;</pre>
	<p>Установка всплывающей формы на кнопку, через атрибуты данных:</p>
	<pre>&lt;button data-dspopup-id=&quot;<?php echo $formId;?>&quot;&gt;<?php echo $mailSubject;?>&lt;/button&gt;</pre>
	<ul class="settings">
		<li><b>data-dsconfig</b> - атрибут для передачи массива данных из шаблона в формате JSON {'name':'значение'}</li>
	</ul>
	<p>Размещение формы в контейнере, через вызов:</p>
	<pre>$(document).ready(function(){
    $('#form-block').dsform({
        formID : '<?php echo $formId;?>',
        modal  : false,
    });
});

&lt;div id=&quot;form-block&quot;&gt;&lt;/div&gt;</pre>
	<p>Установка всплывающей формы на кнопку, через вызов:</p>
	<pre>$(document).ready(function(){
    $('.form-button').dsform({
        formID : '<?php echo $formId;?>',
    });
});
&lt;button class=&quot;form-button&quot;&gt;&lt;/button&gt;</pre>
<ul class="settings">
	<li><b>formID</b> : (<i>строка</i>) — id формы назначаемой на элемент</li>
	<li><b>modal</b> : (<i>флаг</i>) — использовать назначенный элемент, как кнопку для всплывающей формы, по умолчанию true</li>
	<li><b>additionalClass</b> : (<i>строка</i>) — добавляет класс контейнеру в котором находится форма</li>
	<li><b>config</b> : (<i>строка</i>) — строка в JSON формате, то же самое что и атрибут data-dsconfig</li>
	<li><b>inputmask</b> : (<i>объект</i>) — объект формата {‘имя поля’ : {объект настроек}}, для inputMask</li>
	<li><b>dates</b> : (<i>объект</i>) — объект формата {‘имя поля’ : {объект настроек}}, для dscalendar</li>
	<li><b>showLoader</b> : (<i>флаг</i>) — определяет, показывать ли анимацию загрузки на месте "кнопки отправить", по умолчанию true</li>
	<li><b>useFormStyler</b> : (<i>строка или флаг</i>) — определяет использовать ли плагин Form Styler для формы, по умолчанию false. Если указать true, то стилизует все возможные элементы, можно указать строку с селекторами, чтобы ограничить стилизацию.
	<li><b>formstyler</b> : (<i>объект</i>) — настройки специфичные для плагина Form Styler</li>
	<li><b>onLoad</b> : (<i>функция</i>) — функция по событию загрузки кода формы, срабатывает при обновлении формы, а именно :</li>
	<ul>
		<li>при загрузке страницы у блоков;</li>
		<li>для всплывающих форм срабатывает каждый раз, когда происходит клик по новой кнопке для формы, например если сделать две кнопки которые будут открывать одну и ту же форму и нажимать их поочередно. При клике по одной и той же кнопке загрузка формы происходит только в первый раз.</li>
		<li>при клике на элемент с классом repeatform, который перезагружает форму.</li>
	</ul>
	<li><b>onShow</b> : (<i>функция</i>) — функция сработает по завершению анимации открытия модального окна.</li>
	<li><b>onSuccess</b> : (<i>функция</i>) — функция сработает, если сервер сообщит об успешной отправке письма, например если письмо действительно отправилось</li>
	<li><b>onFail</b> : (<i>функция</i>) — функция сработает, если в форме были ошибки заполнения полей</li>
	<li><b>onClose</b> : (<i>функция</i>) — функция сработает по завершению анимации закрытия модального окна</li>
	<li><b>onServerError</b> : (<i>функция</i>) — функция сработает, если сервер сообщит о неудачной отправке письма</li>
	<li><b>animationspeed</b> : (<i>число</i>) — скорость анимации модального окна в миллисекундах, на всякий случай</li>
	<li><b>closeonbackgroundclick</b> : (<i>флаг</i>) — определяет будет ли окно закрываться по клику на фоне, по умолчанию true</li>
	<li><b>dismissmodalclass</b> : (<i>строка</i>) — можно переназначить класс кнопки закрытия, принимает именно className, а не селектор</li>
</ul></div>