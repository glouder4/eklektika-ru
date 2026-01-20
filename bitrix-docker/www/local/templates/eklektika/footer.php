<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>
<? if ($APPLICATION->GetCurPage() != '/') { ?>
    </div>
<? } ?>

</div>
<!-- END middle -->

    <div class="container-wrap">
        <div class="h2">Обратная связь</div>
        <div id="footer_form" class="ds-form">
            <?$APPLICATION->IncludeComponent(
                "bitrix:form.result.new",
                "upper-footer-form",
                Array(
                    "CACHE_TIME" => "3600",
                    "CACHE_TYPE" => "A",
                    "CHAIN_ITEM_LINK" => "",
                    "CHAIN_ITEM_TEXT" => "",
                    "EDIT_URL" => "result_edit.php",
                    "IGNORE_CUSTOM_TEMPLATE" => "N",
                    "LIST_URL" => "result_list.php",
                    "SEF_MODE" => "N",
                    "SUCCESS_URL" => "",
                    "USE_EXTENDED_ERRORS" => "N",
                    "VARIABLE_ALIASES" => Array(
                        "RESULT_ID" => "RESULT_ID",
                        "WEB_FORM_ID" => "WEB_FORM_ID"
                    ),
                    "WEB_FORM_ID" => "1"
                )
            );?>
        </div>
    </div>

	<footer class="footer">
		<div class="footer-phone">
			<div class="phone"> <a href="tel:8(800)707 5211"><span class="icon-callback"></span>8(800)707 5211</a></div>
			<p>Бесплатный звонок из любой точки России</p>
		</div>
		<div class="footer-inner">
			<div class="container-wrap">
				<div class="row">
					<div class="col-md-6 col-xl-3">
                    	<a href="/nanesenie/" class="footer-title">Виды нанесения логотипов</a>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "footer-column",
                            Array(
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "left",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "1",
                                "MENU_CACHE_GET_VARS" => array(""),
                                "MENU_CACHE_TIME" => "3600",
                                "MENU_CACHE_TYPE" => "N",
                                "MENU_CACHE_USE_GROUPS" => "Y",
                                "ROOT_MENU_TYPE" => "footer_col1",
                                "USE_EXT" => "N"
                            )
                        );?>
					</div>
					<div class="col-md-6 col-xl-3">
						<p class="footer-title">Для клиентов</p>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "footer-column",
                            Array(
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "left",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "1",
                                "MENU_CACHE_GET_VARS" => array(""),
                                "MENU_CACHE_TIME" => "3600",
                                "MENU_CACHE_TYPE" => "N",
                                "MENU_CACHE_USE_GROUPS" => "Y",
                                "ROOT_MENU_TYPE" => "footer_col2",
                                "USE_EXT" => "N"
                            )
                        );?>
					</div>
					<div class="col-md-6 col-xl-3">
						<a href="/o-kompanii/" class="footer-title">О компании</a>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "footer-column",
                            Array(
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "left",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "1",
                                "MENU_CACHE_GET_VARS" => array(""),
                                "MENU_CACHE_TIME" => "3600",
                                "MENU_CACHE_TYPE" => "N",
                                "MENU_CACHE_USE_GROUPS" => "Y",
                                "ROOT_MENU_TYPE" => "footer_col3",
                                "USE_EXT" => "N"
                            )
                        );?>
                	</div>
					<div class="col-md-6 col-xl-3">
						<a href="/" class="footer-title">На складе в Москве</a>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "footer-column",
                            Array(
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "left",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "1",
                                "MENU_CACHE_GET_VARS" => array(""),
                                "MENU_CACHE_TIME" => "3600",
                                "MENU_CACHE_TYPE" => "N",
                                "MENU_CACHE_USE_GROUPS" => "Y",
                                "ROOT_MENU_TYPE" => "footer_col4",
                                "USE_EXT" => "N"
                            )
                        );?>
					</div>
					<div class="col-md-6 col-xl-3">
						<div class="address-item">
                            <p class="footer-title address">Почтовые адреса</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/postal-addresses.php"
                                )
                            );?>

						</div>
						<div class="address-item">
							<p class="footer-title address">Фактический адрес</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/actual-address.php"
                                )
                            );?>
						</div>
					</div>
					<div class="col-md-6 col-xl-3">
						<div class="address-item">
							<p class="footer-title address">Офис</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/footer-office.php"
                                )
                            );?>
						</div>
						<div class="address-item">
							<p class="footer-title address">Склад</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/footer-stash.php"
                                )
                            );?>
						</div>
					</div>
					<div class="col-md-6 col-xl-3">
						<div class="address-item">
							<p class="footer-title time">Режим работы</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/footer-work-mode.php"
                                )
                            );?>
						</div>
						<div class="address-item">
							<p class="footer-title phone">Телефон</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/footer-phone.php"
                                )
                            );?>
							<p class="footer-title footer-title-mail">Почта</p>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:main.include",
                                "",
                                Array(
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "inc",
                                    "EDIT_TEMPLATE" => "",
                                    "PATH" => "/include/footer-additional-info.php"
                                )
                            );?>
						</div>
					</div>
					<div class="col-md-6 col-xl-3">
						<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/payment/oplata1.jpg" alt="Оплата черея Яндекс-кассу" class="lazy-loaded">
						
						<div class="footer-payment">
							<div>
								<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/payment/mastercard.png" alt="mastercard">
								<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/payment/maestro.png" alt="Maestro">
							</div>
							<div>
								<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/payment/mir.png" alt="MIR">
								<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/payment/visa.png" alt="VISA">
							</div>
						</div>
						<div class="footer-socials">
							<a href="https://vk.com/eklektikaru" target='_blank' rel="nofollow noopener"><span class="icon-vkontakte"></span></a>
							<!-- <a target='_blank' href="" rel="nofollow noopener"><span class="icon-facebook"></span></a> -->
							<!-- <a target='_blank' href="" rel="nofollow noopener"><span class="icon-twitter"></span></a>       -->
							<!-- <a target='_blank' href="" rel="nofollow noopener"><span class="icon-instagram"></span></a>  -->
							
							<a target='_blank' href="https://www.youtube.com/channel/UCVmr_XNZRH_muUB3MoNhbfA/featured" rel="nofollow noopener"><span class="icon-youtube-play"></span></a>
							<a rel="nofollow noopener" class="telegram-footer" href="#">
								<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/telegram-footer.svg" alt="telegram">
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="copy" itemscope itemtype="http://schema.org/WPFooter">
				<meta itemprop="copyrightYear" content="2024">
				<div>
					© 2000-<?=date('Y');?> Эклектика Все права защищены
				</div>
				<div>
					Все права защищены <a href="/oferta/">Политика конфиденциальности</a>
				</div>
			</div>
		</div>
	</footer>
	<script>
		$('.obrabotkap').each(function() {
			var b = $(this).parents('form').eq(0).find('[type="submit"]')
			// если чекбокс не должен быть отмечен по умолчанию, раскомментировать
			// и удалить checked="checked"
			/*
				b.attr('disabled', 'disabled')
				.addClass('policy-improper')
			*/
			$(this).bind('change', function() {
				if (this.checked == true) {
					b.attr('disabled', false)
						.removeClass('policy-improper')
				} else {
					b.attr('disabled', 'disabled')
						.addClass('policy-improper')
				}
			})
		})
	</script>

<!-- END wrapper -->
<div id="info-modal" class="info-modal"></div>
<!-- end info modal -->
<!-- ////////////////////////////////////////////// -->
<!-- BEGIN modal-large -->
<div id="stock-modal" class="info-modal modal-large"></div>
<!-- end info modal -->
<!-- //////////////////////////////////////////// -->
<div id="in-cart" class="info-modal">
    <p>Товар добавлен в корзину</p>
    <div class="buttons text-center">
        <a href="/cart.php" class="btn btn-round btn-blue-border">Перейти к оформлению</a>
    </div>
    <div class="buttons text-center">
        <div  data-fancybox-close id="hg" style="line-height: 51px;cursor: pointer" class="btn btn-round btn-blue-border">Продолжить покупки</div>
    </div>
</div>
<!-- end info modal -->
<!-- ///////////////////////////////////////////// -->
<!-- ////////////////////////////////////////////////////// -->
<!-- BEGIN modal-large -->
<!-- ////////////////////////////////////////////// -->
<!-- BEGIN modal-large -->
<div id="callback" class="info-modal modal-large">
    <div class="text-center">
		<div  class=item-price>Заказать звонок</div>
    </div>
    <br>
    <br>
    <form action="" id="callback-form">
		<input name="utm-source" type="hidden" value="">
		<input name="utm-medium" type="hidden" value="">
		<input name="utm-compaign" type="hidden" value="">
		<input name="utm-content" type="hidden" value="">
		<input name="utm-term" type="hidden" value="">
        <input type="hidden" name="formid" value="callbackForm">
        <div class="form-group">
            <div class="placeholder">
                <label>Кого спросить</label>
                <span class="star">*</span>
            </div>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Телефон</label>
                <span class="star">*</span>
            </div>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <label class="checkbox">
            <input type="checkbox" name="agree1" value="1" >
            <span>
                <span class="star"> </span>
				Я даю <a href="/sogl.docx"> согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями
			</span>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="agree2"   value="2" required>
            <span>
                <span class="star">*</span>
                Настоящим подтверждаю, что я ознакомлен и согласен с условиями  <a href="/oferta/">политики конфиденциальности</a>
            </span>
        </label>
        <br>
		<div class="text-center">
            <button id="submit_callback" class="btn btn-bluelight btn-round btn-shadow" type="submit" aria-label="отправить">Отправить</button>
        </div>
    </form>
</div>

<!-- end info modal --><!-- BEGIN modal-large -->
<div id="ordertovar" class="info-modal modal-large">
    <div class="text-center">
       	<div class=item-price>Заявка на товар</div>
        <p>Введите ваши данные и мы свяжемся с Вами для уточнения деталей заказа и расчета его стоимости</p>
    </div>
    <div class="product-in-modal">
        <div class="row">
            <div class="col-sm-3">
                <a href="" class="pim-image">
                    <img src="" alt="">
                </a>
            </div>
            <div class="col-sm-9">
                <a href="" class="pim-title"></a>
                <table>
                    <tbody>
						<tr>
							<td>Артикул:</td>
							<td></td>
						</tr>
                    </tbody>
				</table>
            </div>
        </div>
    </div>
    <form action="" id="order-tovar-form">
        <input type="hidden" name="formid" value="orderTovarForm">
        <div class="pim-input">
			<input name="utm-source" type="hidden" value="">
			<input name="utm-medium" type="hidden" value="">
			<input name="utm-compaign" type="hidden" value="">
			<input name="utm-content" type="hidden" value="">
			<input name="utm-term" type="hidden" value="">
            <span>Укажите необходимый тираж</span>
            <input type="text" id="input-number" class="input-number" name="quantity" placeholder="0000000" required="">
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Имя</label>
                <span class="star">*</span>
            </div>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Email</label>
                <span class="star">*</span>
            </div>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder" style="display: block;">
                <label>Телефон</label>
                <span class="star">*</span>
            </div>
            <input type="text" id="phone" name="phone" class="form-control" required="">
        </div>
        <input style="display:none" type="text" name="artikul"  value="">
        <input style="display:none" type="text" name="tovar"  value="">
        <label class="checkbox">
            <input type="checkbox" name="agree1" value="1" >
            <span>
                <span class="star"> </span>
    			Я даю <a href="/sogl.docx"> согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями
            </span>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="agree2"   value="2" required>
            <span>
                <span class="star">*</span>
                Настоящим подтверждаю, что я ознакомлен и согласен с условиями  <a href="/oferta/">политики конфиденциальности</a>
            </span>
        </label>
        <br>
        <div class="text-center">
            <button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
        </div>
    </form>
</div>


<!-- end info modal --><!-- ////////////////////////////////////////////// -->
<!-- BEGIN modal-large -->
<div id="sendmessage" class="info-modal modal-large">
    <div class="text-center">
        <div  class=item-price>Сообщение</div>
    </div>
    <br>
	<br>
    <form action="" id="message-form">
        <input type="hidden" name="formid" value="messageForm">
		<input type="hidden" id="FORM_TRACE" name="TRACE">
		<input name="utm-source" type="hidden" value="">
		<input name="utm-medium" type="hidden" value="">
		<input name="utm-compaign" type="hidden" value="">
		<input name="utm-content" type="hidden" value="">
		<input name="utm-term" type="hidden" value="">
        <div class="form-group">
            <div class="placeholder">
                <label>Имя</label>
                <span class="star">*</span>
            </div>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Email</label>
                <span class="star">*</span>
            </div>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Телефон</label><span class="star">*</span>
            </div>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="form-group for-textarea">
			<label>Сообщение&nbsp;<span class="star">*</span></label>
            <textarea required name="message" class="form-control"></textarea>
        </div>
		<label class="checkbox">
            <input type="checkbox" name="agree1" value="1" >
            <span>
                <span class="star"> </span>
    			Я даю <a href="/sogl.docx"> согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями
            </span>
        </label>
        <label class="checkbox">
            <input type="checkbox" name="agree2" value="2" required>
            <span>
                <span class="star">*</span>
                Настоящим подтверждаю, что я ознакомлен и согласен с условиями  <a href="/oferta/">политики конфиденциальности</a>
            </span>
        </label>
        <br>
        <div class="text-center">
            <button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
        </div>
    </form>
	<script>
		window.onload = function(e){
			var traceInput = document.getElementById('FORM_TRACE');
			if(traceInput)
			{
				traceInput.value = b24Tracker.guest.getTrace();
			}
		}
	</script>
</div>


<!-- end info modal --><!-- BEGIN modal-large -->
<div id="calculate-application" class="info-modal modal-large">
    <div class="text-center">
        <div class=item-price>Сообщение</div>
    </div>
    <br>
	<br>
    <form action="" id="calculate-application-form">
        <input type="hidden" name="formid" value="calculateApplicationForm">
        <input name="utm-source" type="hidden" value="">
        <input name="utm-medium" type="hidden" value="">
        <input name="utm-compaign" type="hidden" value="">
        <input name="utm-content" type="hidden" value="">
        <input name="utm-term" type="hidden" value="">
        <div class="form-group">
            <div class="placeholder">
                <label>Имя</label>
                <span class="star">*</span>
            </div>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Email</label>
                <span class="star">*</span>
            </div>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="placeholder">
                <label>Телефон<span class="star">*</span></label>
            </div>
            <input type="text" name="phone"  required class="form-control">
        </div>
        <div class="form-group for-textarea">
            <label>Сообщение&nbsp;<span class="star">*</span></label>
            <textarea required name="message" class="form-control"></textarea>
        </div>
        <label class="checkbox">
            <input type="checkbox" name="agree1" value="1" >
            <span>
                <span class="star"></span>
                Я даю <a href="/sogl.docx"> согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями
            </span>
        </label>
		<label class="checkbox">
            <input type="checkbox" name="agree2" value="1" >
            <span>
                <span class="star"></span>
               	Настоящим подтверждаю, что я ознакомлен и согласен с условиями  <a href="/oferta/">политики конфиденциальности</a>
            </span>
        </label>
        <br>
        <div class="text-center">
            <button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
        </div>
    </form>
</div>


<!-- end info modal --><!-- BEGIN modal-large -->
<div id="remindtovar" class="info-modal modal-large">
    <div class="text-center">
       	<div class="h2">Оформите Ваш заказ в 1 клик!</div>
        <p>Уважаемые  клиенты! После отравки заявки на выбранный артикул, с Вами свяжется наш менеджер. С ним Вы сможете обсудить все дополнительные детали, включая нанесение и срок поставки.</p>
    	<div class="product-in-modal">
			<div class="row">
				<div class="col-sm-3">
					<a href="" class="pim-image">
						<img src="" alt="">
					</a>
				</div>
				<div class="col-sm-9">
                	<a href="" class="pim-title"></a>
					<table>
						<tbody>
							<tr>
								<td>Артикул:</td>
								<td></td>
							</tr>
						</tbody>
					</table>
            	</div>
        	</div>
    	</div>
		<form action="" id="remind-form">
			<input type="hidden" name="formid" value="remindForm">
			<div class="pim-input">
				<input name="utm-source" type="hidden" value="">
				<input name="utm-medium" type="hidden" value="">
				<input name="utm-compaign" type="hidden" value="">
				<input name="utm-content" type="hidden" value="">
				<input name="utm-term" type="hidden" value="">
				<div class="h3">Укажите тираж выбранного артикула</div>
				<input type="text" id="input-number" class="input-number" name="quantity" placeholder="0000000" required="">
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Имя</label>
					<span class="star">*</span>
				</div>
				<input type="text" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Email</label>
					<span class="star">*</span>
				</div>
				<input type="email" name="email" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder" style="display: block;">
					<label>Телефон</label>
					<span class="star">*</span>
				</div>
				<input type="text" id="phone" name="phone" class="form-control" required="">
			</div>
			<input style="display:none" type="text" name="artikul"  value="">
			<input style="display:none" type="text" name="tovar"  value="">
			<div class="h3">Благодарим Вас за интерес к нашей продукции !</div>
			<label class="checkbox">
				<input type="checkbox" name="agree1" value="1" >
				<span>
					<span class="star">*</span>
					Я даю согласие на получение email рассылки с новинками, скидками и специальными предложениями.
				</span>
			</label>
			<label class="checkbox">
				<input type="checkbox" name="agree2" value="2" required>
				<span>
					<span class="star">*</span>
					Настоящим подтверждаю, что я ознакомлен и согласен с условиями
					<a href="/oferta/">политики конфиденциальности.</a>
				</span>
			</label>
			<br>
			<div class="text-center">
				<button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
			</div>
		</form>
	</div>
</div> <?//ВОЗМОЖНО?>

<!-- end info modal --><!-- BEGIN modal-large -->
<div id="paket1" class="info-modal modal-large">
    <div class="text-center">
       	<p class="h2">Оформите Ваш заказ в 1 клик!</p>
        <p>Уважаемые  клиенты! После отравки заявки на выбранный вам товар, с Вами свяжется наш менеджер. С ним Вы сможете обсудить все дополнительные детали, включая нанесение и срок поставки.</p>
    	<div class="product-in-modal">
        	<div class="row">
            	<div class="col-sm-3">
					<p href="" class="pim-image">
						<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/pakety-s-logotipom/pak1.jpg" alt="">
					</p>
				</div>
				<div class="col-sm-9">
					<p href="" class="pim-title" style="font-size: 20px;">
						Бумажный пакет
					</p>
				</div>
       		</div>
    	</div>
		<form action="" id="remind-form">
			<input type="hidden" name="formid" value="remindForm">
			<div class="pim-input">
				<input name="utm-source" type="hidden" value="">
				<input name="utm-medium" type="hidden" value="">
				<input name="utm-compaign" type="hidden" value="">
				<input name="utm-content" type="hidden" value="">
				<input name="utm-term" type="hidden" value="">
				<p class="h3">Укажите тираж выбранного артикула</p>
				<input type="text" id="input-number" class="input-number" name="quantity" placeholder="0000000" required="">
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Имя</label>
					<span class="star">*</span>
				</div>
				<input type="text" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Email</label>
					<span class="star">*</span>
				</div>
				<input type="email" name="email" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder" style="display: block;">
					<label>Телефон</label>
					<span class="star">*</span>
				</div>
				<input type="text" id="phone" name="phone" class="form-control" required="">
			</div>
			<input style="display:none" type="text" name="artikul"  value="">
			<input style="display:none" type="text" name="tovar"  value="">
			<p class="h3">Благодарим Вас за интерес к нашей продукции !</p>
			<label class="checkbox">
				<input type="checkbox" name="agree1" value="1" >
				<span>
					<span class="star">*</span>
					Я даю согласие на получение email рассылки с новинками, скидками и специальными предложениями.
				</span>
			</label>
			<label class="checkbox">
				<input type="checkbox" name="agree2" value="2" required>
				<span>
					<span class="star">*</span>
					Настоящим подтверждаю, что я ознакомлен и согласен с условиями
					<a href="/oferta/">политики конфиденциальности.</a>
				</span>
			</label>
			<br>
			<div class="text-center">
				<button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
			</div>
		</form>
	</div>
</div> <?//ВОЗМОЖНО?>

<!-- end info modal --><!-- BEGIN modal-large -->
<div id="paket2" class="info-modal modal-large">
    <div class="text-center">
       	<p class="h2">Оформите Ваш заказ в 1 клик!</p>
       	<p>Уважаемые  клиенты! После отравки заявки на выбранный вам товар, с Вами свяжется наш менеджер. С ним Вы сможете обсудить все дополнительные детали, включая нанесение и срок поставки.</p>
    	<div class="product-in-modal">
			<div class="row">
				<div class="col-sm-3">
					<p href="" class="pim-image">
						<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/pakety-s-logotipom/pak2.jpg" alt="">
					</p>
				</div>
				<div class="col-sm-9">
					<p href="" class="pim-title" style="font-size: 20px;">
						Крафтовый пакет
					</p>
				</div>
			</div>
    	</div>
		<form action="" id="remind-form">
			<input type="hidden" name="formid" value="remindForm">
			<div class="pim-input">
				<input name="utm-source" type="hidden" value="">
				<input name="utm-medium" type="hidden" value="">
				<input name="utm-compaign" type="hidden" value="">
				<input name="utm-content" type="hidden" value="">
				<input name="utm-term" type="hidden" value="">
				<p class="h3">Укажите тираж выбранного артикула</p>
				<input type="text" id="input-number" class="input-number" name="quantity" placeholder="0000000" required="">
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Имя</label>
					<span class="star">*</span>
				</div>
				<input type="text" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Email</label>
					<span class="star">*</span>
				</div>
				<input type="email" name="email" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder" style="display: block;">
					<label>Телефон</label>
					<span class="star">*</span>
				</div>
				<input type="text" id="phone" name="phone" class="form-control" required="">
			</div>
			<input  style="display:none" type="text" name="artikul"  value="">
			<input   style="display:none" type="text" name="tovar"  value="">
			<p class="h3">Благодарим Вас за интерес к нашей продукции !</p>
			<label class="checkbox">
				<input type="checkbox" name="agree1" value="1" >
				<span>
					<span class="star">*</span>
					Я даю согласие на получение email рассылки с новинками, скидками и специальными предложениями.
				</span>
			</label>
			<label class="checkbox">
				<input type="checkbox" name="agree2" value="2" required>
				<span>
					<span class="star">*</span>
					Настоящим подтверждаю, что я ознакомлен и согласен с условиями
					<a href="/oferta/">политики конфиденциальности.</a>
				</span>
			</label>
			<br>
			<div class="text-center">
				<button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
			</div>
		</form>
	</div>
</div> <?//ВОЗМОЖНО?>

<!-- end info modal --><!-- BEGIN modal-large -->
<div id="paket3" class="info-modal modal-large">
    <div class="text-center">
       	<p class="h2">Оформите Ваш заказ в 1 клик!</p>
       	<p>Уважаемые  клиенты! После отравки заявки на выбранный вам товар, с Вами свяжется наш менеджер. С ним Вы сможете обсудить все дополнительные детали, включая нанесение и срок поставки.</p>
		<div class="product-in-modal">
			<div class="row">
				<div class="col-sm-3">
					<p href="" class="pim-image">
						<img src="<?=SITE_TEMPLATE_PATH?>/assets/img/pakety-s-logotipom/pak3.jpg" alt="">
					</p>
				</div>
				<div class="col-sm-9">
					<p href="" class="pim-title" style="font-size: 20px;">
						ПВД пакет
					</p>
				</div>
        	</div>
    	</div>
    	<form action="" id="remind-form">
			<input type="hidden" name="formid" value="remindForm">
			<div class="pim-input">
				<input name="utm-source" type="hidden" value="">
				<input name="utm-medium" type="hidden" value="">
				<input name="utm-compaign" type="hidden" value="">
				<input name="utm-content" type="hidden" value="">
				<input name="utm-term" type="hidden" value="">
            	<p class="h3">Укажите тираж выбранного артикула</p>
            	<input type="text" id="input-number" class="input-number" name="quantity" placeholder="0000000" required="">
        	</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Имя</label>
					<span class="star">*</span>
				</div>
				<input type="text" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder">
					<label>Email</label>
					<span class="star">*</span>
				</div>
				<input type="email" name="email" class="form-control" required>
			</div>
			<div class="form-group">
				<div class="placeholder" style="display: block;">
					<label>Телефон</label>
					<span class="star">*</span>
				</div>
            	<input type="text" id="phone" name="phone" class="form-control" required="">
        	</div>
			<input  style="display:none" type="text" name="artikul"  value="">
			<input   style="display:none" type="text" name="tovar"  value="">
 			<p class="h3">Благодарим Вас за интерес к нашей продукции !</p>
			<label class="checkbox">
				<input type="checkbox" name="agree1" value="1" >
				<span>
					<span class="star">*</span>
					Я даю согласие на получение email рассылки с новинками, скидками и специальными предложениями.
				</span>
			</label>
			<label class="checkbox">
				<input type="checkbox" name="agree2" value="2" required>
				<span>
					<span class="star">*</span>
					Настоящим подтверждаю, что я ознакомлен и согласен с условиями
					<a href="/oferta/">политики конфиденциальности.</a>
				</span>
			</label>
        	<br>
			<div class="text-center">
				<button id="submit_message"  class="btn btn-bluelight btn-round btn-shadow" type="submit">Отправить</button>
			</div>
    	</form>
	</div>
</div> <?//ВОЗМОЖНО?>

<!-- end info modal --><!-- ######################### JS ############################## -->
<script defer src="<?=SITE_TEMPLATE_PATH?>/assets/js/plugins.min.js"></script>
<script defer src="<?=SITE_TEMPLATE_PATH?>/assets/js/script.js"></script>
<!-- желательно все сложить в js файлы с атрибутом defer -->

<script src="<?=SITE_TEMPLATE_PATH?>/assets/js/evoshop-js/evoShop.js"></script>


<script src="<?=SITE_TEMPLATE_PATH?>/assets/js/custom_script.js"></script>

<script type="text/javascript">
    evoShop({
        currency: "RUB",
        // cartStyle: "table",
        cartStyle: "div",
        cartColumns: [
            { attr: "image", label: "Фото", view: function (item, column) {
                return '<a href="' + item.get("url") + '" class="basket-table__img"><img style="max-width:100px" src="' + item.get("image") + '" alt=""/>';
            }},
            { attr: "name", label: "Наименование", view: function (item, column) {
                return '<a href="' + item.get("url") + '" class="basket-table__name">' + item.get("name") + '</a>';
            }},
            { attr: "price", label: "Цена", view: function (item, column) {
                return '<span class="basket-table__value">' + evoShop.toCurrency(item.get("price")) + '</span>';
            }},
            { attr: "quantit", label: "Кол-во", view: function (item, column) {
                return '<div ><div class="spinner-switcher plus evoShop_increment" data-target="plus"></div><input type="text" maxlength="3" value="' + item.quantity() + '" class="spinner-input item_quantity" /><div class="spinner-switcher minus evoShop_decrement" data-target="minus"></div></div>';
            }},
            { attr: "nanesenie", label: "Метод нанесения", view: function (item, column) {
                return ' ';
            }},
            { view: "decrement", label: false, text: "<span style='font-weiht:bold;'>&nbsp;-</span>" },
            { view: "increment", label: false, text: "<span style='font-weiht:bold;'>&nbsp;+</span>" },
            { attr: "total", label: "Сумма", view: function (item, column) {
                return '<span class="basket-table__value">' + evoShop.toCurrency(item.get("price") * item.quantity()) + '</span>';
            }},
            { attr: "remove", label: false, view: function (item, column) {
                return '<div class="basket-remove evoShop_remove">Убрать</div>';
            }},
        ],
    });

    $(document).on("change", ".item-quantit .item_quantity", function (ev) {
        var input = $(this);
        evoShop.find(input.closest('.itemRow').attr('id').split("_")[1]).set('quantity', input.val());
        evoShop.update();
        return;
    });

    $(document).on("click", ".evoShop_submit", function (ev) {
        localStorage.setItem("evoShop_items", evoShop.items().serialize());
        $(".evoShop_items_json").val(localStorage.getItem("evoShop_items").replace("}}", "} }"));
    });
</script>
<script>
    (function(w,d,u){
    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
    })(window,document,'https://cdn-ru.bitrix24.ru/b17255864/crm/tag/call.tracker.js');
</script>
<script type="text/javascript">
    $(function(){
        $('#main-search-form').submit(function (eventObj) {
            var s_price_from = $(this).find('input[name ="s_price_from"]')
            var s_price_to = $(this).find('input[name ="s_price_to"]')
            var s_price_from_value = s_price_from.val();
            var s_price_to_value = s_price_to.val();
            // $('#main-search-form').find('input[name ="s_price_from"]').remove()
            // $('#main-search-form').find('input[name ="s_price_to"]').remove()
            $('#main-search-form').find('input[name ="s_price_from"]').prop("disabled", true);
            $('#main-search-form').find('input[name ="s_price_to"]').prop("disabled", true);
            $(this).append('<input type="hidden" name="f8" value="minmax~'+s_price_from_value + ',' + s_price_to_value +'" /> ');
            return true;
        });

        $(document).on('submit', '#callback-form', function (ev) {
            ev.preventDefault();
            var frm = $('#callback-form');
            //зачем эта строка?
            // $('#submit_callback').attr("disabled", true);
            $.ajax({
                type: 'post',
                url: 'ajax_callback_form.php',
                data: frm.serialize(),
                success: function (data) {
                    $('#callback-form').empty();
                    $('#callback-form').closest('#callback').find('.text-center').empty();
                    $('#callback-form').html(data)
                    // jcf.replaceAll();
                }
            });
        });
        $(document).on('submit', '#message-form', function (ev) {
            ev.preventDefault();
            var frm = $('#message-form');
            //зачем эта строка?
            // $('.submit-button').prop("disabled", true);
            $.ajax({
                type: 'post',
                url: 'ajax_message_form.php',
                data: frm.serialize(),
                success: function (data) {
                    $('#message-form').empty();
                    $('#message-form').closest('#sendmessage').find('.text-center').empty();
                    $('#message-form').html(data)
                    // jcf.replaceAll();
                }
            });
        });

        $(document).on('submit', '#calculate-application-form', function (ev) {
            ev.preventDefault();
            var frm = $('#calculate-application-form');
            $.ajax({
                type: 'post',
                url: 'ajax_calculate_application_form.php',
                data: frm.serialize(),
                success: function (data) {
                    $('#calculate-application-form').empty();
                    $('#calculate-application-form').closest('#calculate-application').find('.text-center').empty();
                    $('#calculate-application-form').html(data)
                }

            });

        });

        $(document).on('submit', '#remind-form', function (ev) {
            ev.preventDefault();
            var frm = $('#remind-form');
            //зачем эта строка?
            // $('.submit-button').prop("disabled", true);
            $.ajax({
                type: 'post',
                url: 'ajax_remind_form.php',
                data: frm.serialize(),
                success: function (data) {
                    $('#remind-form').empty();
                    $('#remind-form').html(data)
                    // jcf.replaceAll();
                }
            });
        });
        $(document).on('submit', '#order-tovar-form', function (ev) {
            ev.preventDefault();
            var frm = $('#order-tovar-form');
            //зачем эта строка?
            // $('.submit-button').prop("disabled", true);
            $.ajax({
                type: 'post',
                url: 'ajax_order_tovar_form.php',
                data: frm.serialize(),
                success: function (data) {
                    $('#order-tovar-form').empty();
                    $('#order-tovar-form').html(data)
                    // jcf.replaceAll();
                }
            });
        });
    });
</script>

<style>
    @media (max-width: 1199px) {
        .map-panel #map-one {
            margin: 0;
        }
    }
</style>

<script>
    $(document).ready(function() {
        var searchTimeout;
        
        // Функция для выполнения поиска
        function performSearch() {
            var fd;
            var priceFrom, priceTo, quantity;
            var formData;

            fd = $('#main-search-form input[name="q"]').val() || '';
            
            // Получаем значения дополнительных полей
            priceFrom = $('#main-search-form input[name="s_price_from"]').val() || '';
            priceTo = $('#main-search-form input[name="s_price_to"]').val() || '';
            quantity = $('#main-search-form input[name="kolvo"]').val() || '';

            // Проверяем, есть ли хотя бы один параметр для поиска
            if (fd.length < 1 && !priceFrom && !priceTo && !quantity) {
                $("#kategort").html('');
                $("#tovart").html('');
                return;
            }

            // Формируем данные для отправки
            formData = "term=" + encodeURIComponent(fd);
            if (priceFrom) {
                formData += "&s_price_from=" + encodeURIComponent(priceFrom);
            }
            if (priceTo) {
                formData += "&s_price_to=" + encodeURIComponent(priceTo);
            }
            if (quantity) {
                formData += "&kolvo=" + encodeURIComponent(quantity);
            }

            $.ajax({
                type: "POST",
                url: "/local/ajax/catalog_search.php",
                dataType: "json",
                data: formData,
                success: function (res) {
                    if (res && res.length >= 2) {
                        // Категории в #kategort
                        $("#kategort").html(res[0] || '');
                        // Товары в #tovart
                        $("#tovart").html(res[1] || '');
                        // Показываем результаты после загрузки
                        if ((res[0] && res[0].trim().length > 0) || (res[1] && res[1].trim().length > 0)) {
                            $('body').addClass('search-results-active');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Ошибка AJAX:', status, error);
                    console.log('Response:', xhr.responseText);
                }
            });
        }

        // Обработчик для поля поиска с задержкой (debounce)
        // Используем input вместо keyup, чтобы срабатывало при вставке и автозаполнении
        $(document).on('input keyup paste', '#main-search-form input[name="q"]', function (e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300); // Задержка 300мс
        });

        // Обработчик focus - показываем результаты, если они уже загружены
        $(document).on('focus', '#main-search-form input[name="q"]', function (e) {
            var hasResults = $('#kategort').html().trim().length > 0 || $('#tovart').html().trim().length > 0;
            var searchValue = $(this).val();
            // Показываем результаты, если они есть или если есть текст в поле
            if (hasResults || (searchValue && searchValue.length > 0)) {
                $('body').addClass('search-results-active');
            }
        });

        // Обработчики для дополнительных полей (цена от, цена до, остаток)
        // Поиск работает даже без текстового запроса, только по фильтрам
        // Используем input вместо keyup, чтобы срабатывало при вставке и автозаполнении
        $(document).on('input keyup paste', '#main-search-form input[name="s_price_from"], #main-search-form input[name="s_price_to"], #main-search-form input[name="kolvo"]', function (e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300);
        });
    });

    function myFocusFunction(a, b) {
        $as = parseInt($(document.getElementById(a)).val());
        $bs = parseInt($(document.getElementById(b)).val());
        if ($as > $bs) {
            $(document.getElementById(a)).val($(document.getElementById(b)).val());
        }
    }
</script>
 
<!-- /Google -->



<!-- BEGIN JIVOSITE CODE {literal} -->
<!-- <script>
     document.addEventListener('DOMContentLoaded', function () {
         window.setTimeout(function () {

             (function () {
                 var widget_id = 'UiCRTc95Sf'; var d = document; var w = window; function l() {
                     var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = '//code.jivosite.com/script/widget/' + widget_id; var ss = document.getElementsByTagName('script')[0];
ss.parentNode.insertBefore(s, ss);
                 } if (d.readyState == 'complete') { l(); } else { if
(w.attachEvent) { w.attachEvent('onload', l); } else { w.addEventListener('load', l, false); } }
             })();

         }, 5000);
     });
</script>
 {/literal} END JIVOSITE CODE 

-->
<!-- calltouch -->

<!-- calltouch -->



<script>
        (function(w,d,u){
                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b17255864/crm/site_button/loader_6_22nko2.js');
</script>
<!-- <script src="//cdn.callibri.ru/callibri.js" type="text/javascript" charset="utf-8"></script>-->

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Эклектика",
  "alternateName": "Eklektika",
  "url": "<?=SITE_URL?>/",
  "logo": "<?=SITE_URL?>/assets/images/akcii/logo7.png",
  "address": [
    {
      "@type": "PostalAddress",
      "addressLocality": "Москва",
      "postalCode": "109428",
      "streetAddress": "Рязанский проспект, дом 24, корп.2",
      "addressCountry": "RU",
      "name": "Адрес офиса"
    },
    {
      "@type": "PostalAddress",
      "addressLocality": "Москва",
      "postalCode": "109428",
      "streetAddress": "Рязанский проспект, дом 16, стр. 3",
      "addressCountry": "RU",
      "name": "Адрес склада",
      "description": "Проход и проезд через проходную предприятия по адресу: г. Москва, 2-й Вязовский проезд, д.2а"
    }
  ]
}
</script>
</body>
</html>