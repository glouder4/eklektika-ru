<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "Y";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Просмотр заказов");
$APPLICATION->AddChainItem("Просмотр заказов", "/personal/lichnyj-kabinet.php");

$APPLICATION->SetPageProperty("title", "Просмотр заказов купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Просмотр заказов оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


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
            <?php
            $itemsCount = count($order['items'] ?? []);
            if ($itemsCount % 100 >= 11 && $itemsCount % 100 <= 19) {
                $itemsLabel = $itemsCount . ' позиций';
            } elseif ($itemsCount % 10 === 1) {
                $itemsLabel = $itemsCount . ' позиция';
            } elseif ($itemsCount % 10 >= 2 && $itemsCount % 10 <= 4) {
                $itemsLabel = $itemsCount . ' позиции';
            } else {
                $itemsLabel = $itemsCount . ' позиций';
            }
            ?>
            <hr class="order-divider">
            <article class="order-card">
            <header class="order-card__head">
                <div class="order-card__top">
                    <h3 class="order-card__title">Заказ №<?= (int)$order['id'] ?></h3>
                    <div class="order-card__sum">
                        <?= number_format($order['price'], 0, ',', ' ') ?>
                        <span class="order-card__sum-currency"><?= htmlspecialchars($order['currency']) ?></span>
                    </div>
                </div>
                <time class="order-card__date" datetime="<?= htmlspecialchars($order['date']) ?>">
                    <?= htmlspecialchars($order['date']) ?>
                </time>
            </header>
            <?php
            $nanesMap = [];
            $nanesJson = '';
            if (!empty($order['properties']) && is_array($order['properties'])) {
                foreach ($order['properties'] as $propCode => $propValues) {
                    if (mb_strtolower((string)$propCode) === 'json_naneseniya') {
                        if (is_array($propValues)) {
                            $nanesJson = (string)($propValues[0] ?? '');
                        } else {
                            $nanesJson = (string)$propValues;
                        }
                        break;
                    }
                }
            }

            if ($nanesJson !== '') {
                $decoded = json_decode($nanesJson, true);
                if (is_array($decoded)) {
                    $rows = $decoded;
                    if (isset($decoded['items']) && is_array($decoded['items'])) {
                        $rows = $decoded['items'];
                    }
                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $mapKey = trim((string)($row['id'] ?? ''));
                        $nan = $row['NANESENIE'] ?? null;
                        if ($mapKey !== '' && is_array($nan)) {
                            $nanesMap[$mapKey] = $nan;
                        }
                    }
                }
            }
            ?>
            <details class="order-card__details">
                <summary class="order-card__toggle">
                    <span class="order-card__toggle-main">
                        <span class="order-card__toggle-count"><?= htmlspecialchars($itemsLabel) ?></span>
                        <span class="order-card__toggle-action order-card__toggle-action--show">Показать состав</span>
                        <span class="order-card__toggle-action order-card__toggle-action--hide">Скрыть состав</span>
                    </span>
                    <span class="order-card__toggle-icon" aria-hidden="true"></span>
                </summary>
            <ul class="order-items">
                <?php foreach ($order['items'] as $item): ?>
                <?php
                    $itemXmlId = trim((string)($item['xml_id'] ?? ''));
                    $nanRows = null;
                    if ($itemXmlId !== '' && isset($nanesMap[$itemXmlId])) {
                        $nanRows = $nanesMap[$itemXmlId];
                    } else {
                        $nanRows = $nanesMap[(string)(int)($item['product_id'] ?? 0)] ?? null;
                    }

                    $nanesDisplayRows = [];
                    if (is_array($nanRows)) {
                        foreach ($nanRows as $n) {
                            $nName = trim((string)($n['name'] ?? ''));
                            if ($nName === '') {
                                continue;
                            }
                            $nanesDisplayRows[] = [
                                'name' => $nName,
                                'price' => (float)($n['price'] ?? 0),
                                'is_default' => mb_strtolower($nName) === 'без нанесения',
                            ];
                        }
                    }

                    $unitPrice = ($item['discount_price'] > 0) ? $item['discount_price'] : $item['price'];
                ?>
                <li class="order-item">
                    <div class="order-item__head">
                        <div class="order-item__info">
                            <span class="order-item__sku">Арт. <?= htmlspecialchars($item['properties']['ARTIKUL_POSTAVSHCHIKA'] ?? '—') ?></span>
                            <a class="order-item__name" href="<?= htmlspecialchars($item['detail_url']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                        </div>
                        <div class="order-item__qty">
                            <?= (int)$item['quantity'] ?> шт. × <?= number_format($unitPrice, 0, ',', ' ') ?> ₽
                            <strong><?= number_format($item['total'], 0, ',', ' ') ?> ₽</strong>
                        </div>
                    </div>

                    <?php if ($nanesDisplayRows !== []): ?>
                    <div class="order-item__nanesenie">
                        <div class="order-item__nanesenie-head">
                            <span>Нанесение</span>
                            <span>Цена</span>
                        </div>
                        <ul class="order-item__nanesenie-rows">
                            <?php foreach ($nanesDisplayRows as $nanesRow): ?>
                            <?php
                                $price = $nanesRow['price'];
                                $isDefault = $nanesRow['is_default'];
                                $priceClass = 'order-item__nanesenie-price';
                                if ($isDefault) {
                                    $priceText = '—';
                                    $priceClass .= ' order-item__nanesenie-price--muted';
                                } elseif ($price > 0) {
                                    $priceText = number_format($price, 0, ',', ' ') . ' ₽';
                                } else {
                                    $priceText = 'по запросу';
                                    $priceClass .= ' order-item__nanesenie-price--pending';
                                }
                            ?>
                            <li class="order-item__nanesenie-row<?= $isDefault ? ' order-item__nanesenie-row--default' : '' ?>">
                                <span class="order-item__nanesenie-name"><?= htmlspecialchars($nanesRow['name']) ?></span>
                                <span class="<?= $priceClass ?>"><?= $priceText ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            </details>
            </article>
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

.order-divider {
    margin: 32px 0 20px;
    border: 0;
    border-top: 1px solid #e8e8e8;
}

.order-card {
    margin-bottom: 8px;
}

.order-card__head {
    margin-bottom: 12px;
}

.order-card__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 8px;
}

.order-card__title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #222;
}

.order-card__sum {
    flex-shrink: 0;
    font-size: 22px;
    font-weight: 700;
    color: #222;
    line-height: 1.2;
    text-align: right;
}

.order-card__sum-currency {
    display: block;
    margin-top: 2px;
    font-size: 13px;
    font-weight: 500;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.order-card__date {
    display: inline-block;
    padding: 5px 10px;
    font-size: 14px;
    font-weight: 500;
    color: #555;
    background: #f3f3f3;
    border-radius: 6px;
}

.order-card__details {
    margin-top: 4px;
}

.order-card__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    list-style: none;
    cursor: pointer;
    user-select: none;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: background 0.15s, border-color 0.15s;
}

.order-card__toggle::-webkit-details-marker {
    display: none;
}

.order-card__toggle:hover {
    background: #fafafa;
    border-color: #d0d0d0;
}

.order-card__toggle-main {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 6px 10px;
}

.order-card__toggle-count {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.order-card__toggle-action {
    font-size: 13px;
    color: #2a6ebb;
}

.order-card__toggle-action--hide {
    display: none;
}

.order-card__details[open] .order-card__toggle-action--show {
    display: none;
}

.order-card__details[open] .order-card__toggle-action--hide {
    display: inline;
}

.order-card__toggle-icon {
    width: 8px;
    height: 8px;
    border-right: 2px solid #888;
    border-bottom: 2px solid #888;
    transform: rotate(45deg);
    transition: transform 0.2s;
    flex-shrink: 0;
}

.order-card__details[open] .order-card__toggle-icon {
    transform: rotate(-135deg);
    margin-top: 4px;
}

.order-card__details[open] .order-card__toggle {
    border-radius: 8px 8px 0 0;
    border-bottom-color: #eee;
}

.order-card__details .order-items {
    border-top: 0;
    border-radius: 0 0 8px 8px;
    margin-top: 0;
}

.order-items {
    list-style: none;
    margin: 0;
    padding: 0;
    border: 1px solid #e6e6e6;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.order-item {
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: 0;
}

.order-item__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 10px;
}

.order-item__info {
    min-width: 0;
    flex: 1;
}

.order-item__sku {
    display: block;
    font-size: 12px;
    color: #999;
    margin-bottom: 2px;
}

.order-item__name {
    font-size: 15px;
    font-weight: 500;
    color: #2a6ebb;
    text-decoration: none;
    line-height: 1.35;
}

.order-item__name:hover {
    text-decoration: underline;
}

.order-item__qty {
    flex-shrink: 0;
    text-align: right;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.order-item__qty strong {
    display: block;
    margin-top: 2px;
    font-size: 15px;
    color: #222;
}

.order-item__nanesenie {
    margin-top: 4px;
    padding-top: 10px;
    border-top: 1px dashed #e8e8e8;
}

.order-item__nanesenie-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #aaa;
}

.order-item__nanesenie-rows {
    list-style: none;
    margin: 0;
    padding: 0;
}

.order-item__nanesenie-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 16px;
    padding: 5px 0;
    font-size: 13px;
    line-height: 1.35;
}

.order-item__nanesenie-row + .order-item__nanesenie-row {
    border-top: 1px solid #f5f5f5;
}

.order-item__nanesenie-name {
    color: #444;
    min-width: 0;
}

.order-item__nanesenie-price {
    flex-shrink: 0;
    font-weight: 500;
    color: #222;
    text-align: right;
}

.order-item__nanesenie-price--pending {
    font-weight: 400;
    color: #999;
}

.order-item__nanesenie-price--muted {
    font-weight: 400;
    color: #ccc;
}

.order-item__nanesenie-row--default .order-item__nanesenie-name {
    color: #888;
}

@media (max-width: 640px) {
    .order-item {
        padding: 12px;
    }

    .order-item__head {
        flex-direction: column;
        gap: 8px;
    }

    .order-item__qty {
        text-align: left;
    }

    .order-card__title {
        font-size: 18px;
    }

    .order-card__sum {
        font-size: 20px;
    }

    .order-card__top {
        align-items: center;
    }
}
</style>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
