<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Как заказать продукцию на сайте компании Эклектика");
$APPLICATION->SetPageProperty("description", "Компания «Эклектика»: сувенирная продукция и корпоративные подарки с логотипом на заказ оптом в Москве предлагает ознакомиться с инструкцией как совершить заказ на сайте");
$APPLICATION->SetTitle("Как заказать");
?>

    <div class="zakaz-cont">
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-1.svg">
                <div>1</div>
            </div>
            <p class="zakaz-item__title">Выбор товара</p>
            <p>Выберите товар, укажите нужный тираж и сделайте заказ через корзину</p>
        </div>
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-2.svg">
                <div>2</div>
            </div>
            <p class="zakaz-item__title">Звонок менеджера</p>
            <p>Менеджер свяжется с Вами для уточнения необходимых деталей заказа</p>
        </div>
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-3.svg">
                <div>3</div>
            </div>
            <p class="zakaz-item__title">Согласование итогового макета</p>
            <p>Согласование итогового заказа с нанесением, подписание документов</p>
        </div>
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-4.svg">
                <div>4</div>
            </div>
            <p class="zakaz-item__title">Оплата</p>
            <p>После согласования заказа менеджер выставляет счет на оплату, который Вы можете оплатить удобным для Вас способом</p>
            <a href="/oplata/">Подробнее о способах оплаты</a></div>
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-5.svg">
                <div>5</div>
            </div>
            <p class="zakaz-item__title">Получение заказа</p>
            <p>Изготавливаем Ваш заказ и доставляем удобным для Вас способом</p>
            <a href="/dostavka/">Подробнее о способах доставки</a></div>
        <div class="zakaz-item">
            <div><img src="<?=SITE_TEMPLATE_PATH?>/assets/images/kak-zakazat-6.svg">
                <div>6</div>
            </div>
            <p class="zakaz-item__title">Подписание акта приема заказа</p>
            <p>Проверяете Ваш заказ и подписываете акт приема заказа</p>
        </div>
    </div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>