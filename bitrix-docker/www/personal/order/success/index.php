<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Спасибо, ваш заказ принят в обработку.");

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;

// Подключаем модуль
if (!Loader::includeModule('sale')) {
    ShowError('Системная ошибка: модуль sale недоступен');
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    exit;
}

$orderId = (int)($_GET['ORDER_ID'] ?? 0);
$email = '';

if ($orderId > 0) {
    try {
        $order = Order::load($orderId);
        if ($order) {
            // Проверка: заказ принадлежит текущему авторизованному пользователю
            global $USER;
            if ($USER->IsAuthorized() && (int)$order->getUserId() === (int)$USER->GetID()) {
                // Получаем email из свойств заказа
                $prop = $order->getPropertyCollection()->getItemByOrderPropertyCode('off_email');
                if ($prop) {
                    $email = $prop->getValue();
                }
            }
            // Если пользователь не авторизован — можно использовать FUSER_ID (опционально)
            // Но для безопасности лучше требовать авторизацию
        }
    } catch (\Exception $e) {
        // Логируем ошибку, но не показываем пользователю
        \Bitrix\Main\Diag\Debug::writeToFile($e->getMessage(), '', 'order_success_error.log');
    }
}

// Если email не получен — используем заглушку или скрываем блок
?>
    <div class="middle-content middle-content_success_order">
        <? if (!empty($email)): ?>
            <p>
                    На почтовый ящик <?= htmlspecialchars($email) ?> выслано письмо с заказом.<br>
                    Если его нет во «Входящих», проверьте папку «Спам».<br>
                    Наш менеджер свяжется с вами в ближайшее время.
            </p>
        <? else: ?>
            <p>
                    Ваш заказ оформлен. Наш менеджер свяжется с вами в ближайшее время.
            </p>
        <? endif; ?>

        <a onclick="ym(1087753, 'reachGoal', 'successful_order')" href="/cart.php" class="ubtn blue-ubtn">Продолжить</a>
    </div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>