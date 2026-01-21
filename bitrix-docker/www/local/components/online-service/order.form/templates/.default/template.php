<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<div class="order-page">
    <? include __DIR__ . '/sections/header.php'; ?>

    <form action="" class="cart-order" enctype="multipart/form-data" method="POST">
        <input type="hidden" name="formid" value="performOrder">

        <? include __DIR__ . '/sections/personal-info.php'; ?>
        <? include __DIR__ . '/sections/payment.php'; ?>
        <? include __DIR__ . '/sections/comment.php'; ?>
        <? include __DIR__ . '/sections/file-uploads.php'; ?>
        <? include __DIR__ . '/sections/summary.php'; ?>
        <? include __DIR__ . '/sections/agreements.php'; ?>

        <div class="text-center">
            <button class="btn btn-round btn-shadow btn-blue submit-order" type="submit">Оформить</button>
        </div>
    </form>
</div>

<script src="<?=SITE_TEMPLATE_PATH?>/js/order-form.js"></script>