<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Регистрация");
?>

    <div class="content">



        <font color="FF0000">    </font>
        <form name="myform" action="" method="post" class="cart-order left6" enctype="multipart/form-data">




            <input type="hidden" name="formid" value="registerForm">
            <input type="hidden" name="nospam" id="nospam" value="">
            <div class="row">
                <div class="col-md-4"><label>
                        Электронная почта<font color="red">*</font><span class="help-block text-error">
            <!--  [[+reg.error.email]] -->
        </span>     </label>    </div>
                <div class="col-md-8"><input type="text" inputmode="email" maxlength="100" name="e-mail" value="">
                </div> </div>


            <div class="row"><div class="col-md-4">
                    <label for="password">Пароль<font color="red">*</font><!-- [[[+reg.error.password]] --></label>
                </div><div class="col-md-8">
                    <input type="password" maxlength="100" name="password" eform="'Пароль'::1">


                </div> </div>

            <div class="row">		<div class="col-md-4">
                    <label for="password_confirm">Пароль еще раз<font color="red">*</font><!-- [[[+reg.error.password_confirm]] --></label>
                </div><div class="col-md-8">
                    <input type="password" name="password_confirm" maxlength="100" value="">
                    <span class="help-block text-error">

        </span>

                </div> </div>


            <div class="row">		<div class="col-md-4">
                    <label>Полное имя
                        <font color="red">*</font><span class="help-block text-error">
                <!--  [[[+reg.error.fullname]] -->
            </span>
                    </label>	 </div>	<div class="col-md-8">
                    <input type="text" maxlength="100" name="fullname" id="fullname" value=""></div> </div>



            <div class="row">		<div class="col-md-4"><label for="phone"> Телефон c указанием кода региона без скобок и пробелов<font color="red">*</font>
                        <span class="error"><!-- [[[+reg.error.phone]] --></span> </label>    </div>		<div class="col-md-8">
                    <input type="text" inputmode="tel" name="phone" id="phone" value=""></div> </div>

            <div class="row"> 		<div class="col-md-4"> <label for="mobilephone">Мобильный телефон c указанием кода региона без скобок и пробелов
                        <span class="error"><!-- [[+reg.error.mobilephone]] --></span>
                    </label>	 </div>	<div class="col-md-8">
                    <input type="text" inputmode="tel" maxlength="20" name="mobilephone" id="mobilephone" class="input-number" value=""></div> </div>



            <div class="row">		<div class="col-md-4"> <label for="address">Юридический адрес<font color="red"></font>
                        <span class="error"><!-- [[[+reg.error.address]] --></span>
                    </label>	 </div>	<div class="col-md-8">
                    <input type="text" maxlength="100" name="address" id="address" value=""></div> </div>


            <div class="row">		<div class="col-md-4"> <label>Сфера деятельности
                        <span class="error"><!-- [[[+reg.error.country]] --></span>
                    </label> </div>		<div class="col-md-8">
                    <input type="text" maxlength="50" name="country" id="country" value=""></div>


            </div>

            <div class="row">		<div class="col-md-4"> <label for="state"> Название юридического лица<font color="red">*</font>
                        <span class="error"><!-- [[+reg.error.state]] --></span>
                    </label>		</div><div class="col-md-8">
                    <input type="text" maxlength="100" name="state" value=""></div></div>
            <div class="row">		<div class="col-md-4"> <label> ИНН организации <font color="red"></font>
                        <span class="error"><!-- [[+reg.error.fax]] --></span>
                    </label> </div><div class="col-md-8">
                    <input type="text" class="input-number" inputmode="numeric" name="fax" id="fax" value=""></div></div>

            <div class="row"><div class="col-md-4"> <label>Персональный менеджер Эклектика (если есть)
                        <span class="error"><!-- [[+reg.error.zip]] --></span>
                    </label> </div>
                <div class="col-md-8">  <select name="zip" id="zip" value=""> <option value="0">Нет персонального менеджера</option> <option value="2338">Абросимова Ирина </option><option value="2359">Вавилова Татьяна </option><option value="2565">Аникин Евгений </option><option value="2155">Авилова Инна </option><option value="1057">Сухорукова Юлия </option><option value="1723">Капышова Оксана </option><option value="2156">Алиев Евгений </option></select>
                </div> </div>


            <div class="row">  <div class="col-md-4">  <label for="website">WEB сайт Вашей компании:<font color="red"></font> <span class="error"><!-- [[+reg.error.website]] --></span>
                    </label> </div><div class="col-md-8">
                    <input type="text" name="website" inputmode="url" maxlength="50" id="website" value="">
                </div> </div>



            <div class="agree-block">
        <span id="oferta" class="checkbox">
            <label class="checkbox">
                <input type="checkbox" name="agree" id="agree" required="" value="1">


                <span>	<span class="star">*</span>
                    <span>Настоящим подтверждаю, что я ознакомлен с условиями <a href="https://eklektika.ru/oferta.php">политики конфиденциальности</a> и выражаю согласие на <a href="https://eklektika.ru/soglasie-posetitelya-sajta-na-obrabotku-personalnyh-dannyh.php">обработку персональных данных</a><br></span>
                </span>


            </label>





            <label class="checkbox">

                <input type="checkbox" name="ragree" value="1" id="ragree">

                <span>

                    <!--<span class="star">*</span>-->

                    Я даю согласие на получение email рассылки с новинками, скидками и специальными предложениями.

                </span>

            </label>




        </span>



                <div class="captcha">

                    <!--<div class="g-recaptcha" data-sitekey="6LdjEq8UAAAAAJO0H38xQUpWhJ0Sr_5Y6NMa6NhP"></div>-->
                    <div class="g-recaptcha" data-sitekey="6Le3nbIUAAAAAD4Ppo0_knJp3cahKbsAvJJrNCVI"><div style="width: 304px; height: 78px;"><div><iframe title="reCAPTCHA" width="304" height="78" role="presentation" name="a-hw5f1upgtzj" frameborder="0" scrolling="no" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation allow-modals allow-popups-to-escape-sandbox allow-storage-access-by-user-activation" src="https://www.google.com/recaptcha/api2/anchor?ar=1&amp;k=6Le3nbIUAAAAAD4Ppo0_knJp3cahKbsAvJJrNCVI&amp;co=aHR0cHM6Ly9la2xla3Rpa2EucnU6NDQz&amp;hl=ru&amp;v=7gg7H51Q-naNfhmCP3_R47ho&amp;size=normal&amp;anchor-ms=20000&amp;execute-ms=30000&amp;cb=6qchamvpqj7o"></iframe></div><textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid rgb(193, 193, 193); margin: 10px 25px; padding: 0px; resize: none; display: none;"></textarea></div><iframe style="display: none;"></iframe></div>
                </div>

                <!--<div class=" " >-->
                <!---->
                <!---->
                <!--</div>-->
                <!--<div class="capcha">-->
                <!---->
                <!--</div>-->
                <!---->
            </div>


            <div class="row">
                <input type="submit" value="Отправить" class="btn btn-round btn-shadow btn-blue" onclick="_gaq.push(['_trackEvent', 'Кнопки', 'Отправить-регистрация']); _gaq.push(['_trackPageview', '/registracziya/done']);">
            </div>



        </form>




        <!-- Google Code for Registraciy Conversion Page -->
        <script type="text/javascript">
            /* <![CDATA[ */
            var google_conversion_id = 1017225008;
            var google_conversion_language = "en";
            var google_conversion_format = "3";
            var google_conversion_color = "ffffff";
            var google_conversion_label = "d7PMCJ-puGkQsL6G5QM";
            var google_remarketing_only = false;
            /* ]]> */
        </script>
        <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">


            var manager = '';
            if(manager){
                let val = $('#zip').val();
                console.log('---');
                console.log(val);
                $('#zip').find('option').each(function(){
                    if($(this).val()==val){
                        $(this).attr('selected','selected');
                    }
                })
            }



        </script>
        <noscript>
            <div style="display:inline;">
                <img height="1" width="1" style="border-style:none;" alt=""
                     src="//www.googleadservices.com/pagead/conversion/1017225008/?label=d7PMCJ-puGkQsL6G5QM&amp;guid=ON&amp;script=0"/>
            </div>
        </noscript>






    </div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>