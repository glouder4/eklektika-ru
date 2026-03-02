<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "Y";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Просмотр заказов");
$APPLICATION->AddChainItem("Просмотр заказов", "/personal/lichnyj-kabinet.php");

$APPLICATION->SetPageProperty("title", "Просмотр заказов купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


global $USER;
if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/order/parts/get-user-orders.php";
$orders = getUserOrders((int)$USER->GetID());
?>
<div class="orders-list">
    <?php require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/include/personal-menu.php"; ?>

    <div class="orders-list-content">
        <?php if (empty($orders)): ?>
        <div class="orders-empty">
            <p class="orders-empty__text">У вас пока нет заказов</p>
            <p class="orders-empty__hint">Перейдите в каталог, чтобы выбрать товары и оформить заказ.</p>
            <a href="/catalog/" class="btn btn-round btn-shadow btn-blue orders-empty__link">Перейти в каталог</a>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <hr>
            <h3>Заказ №<?=$order['id']?></h3>
            <span style="font-weight:bold;">Дата:</span> <?=$order['date']?> <span style="font-weight:bold;">Сумма:</span> <?=number_format($order['price'], 0, ',', ' ')?> <?=$order['currency']?>
            <ul>
                <?php foreach ($order['items'] as $item): ?>
                <li>
                    <span style="font-weight:bold;">Артикул:</span> <?=htmlspecialchars($item['properties']['ARTIKUL_POSTAVSHCHIKA'] ?? '')?>
                    <br>
                    <a href="<?=htmlspecialchars($item['detail_url'])?>"><?=htmlspecialchars($item['name'])?></a>
                    <br>
                    <?=$item['quantity']?> шт. × <?=number_format(($item['discount_price'] > 0) ? $item['discount_price'] : $item['price'], 0, ',', ' ')?> руб. = <?=number_format($item['total'], 0, ',', ' ')?> руб.
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.orders-empty { text-align: center; padding: 48px 24px; background: #f9f9f9; border-radius: 8px; margin: 24px 0; }
.orders-empty__text { font-size: 18px; font-weight: 600; color: #333; margin: 0 0 8px 0; }
.orders-empty__hint { color: #666; margin: 0 0 20px 0; font-size: 14px; }
.orders-empty__link { display: inline-block; margin-top: 8px; }
.orders-empty .orders-empty__link{
    color: #FFFFFF!important;
}
</style>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
