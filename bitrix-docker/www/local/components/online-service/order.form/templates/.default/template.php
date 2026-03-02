<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<style>
.cart-order .order-field-prefilled,
.cart-order input.order-field-prefilled,
.cart-order textarea.order-field-prefilled {
    background-color: #e9ecef !important;
    color: #6c757d !important;
    cursor: not-allowed;
}
.cart-order .inputfile.order-field-prefilled + label,
.cart-order input.inputfile:disabled + label {
    background-color: #e9ecef !important;
    color: #6c757d !important;
    cursor: not-allowed;
    opacity: 0.8;
}
</style>
<div class="order-page">
    <? include __DIR__ . '/sections/header.php'; ?>

    <form
            action="/local/components/online-service/order.form/ajax.php"
            data-action="/local/components/online-service/order.form/ajax.php"
            id="order-form" class="cart-order" enctype="multipart/form-data" method="POST">
        <input type="hidden" name="formid" value="performOrder">
        <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>">
        <input type="hidden" name="order_company_requisites_file_id" id="order_company_requisites_file_id" value="">

        <? include __DIR__ . '/sections/company-selector.php'; ?>
        <? include __DIR__ . '/sections/personal-info.php'; ?>
        <? include __DIR__ . '/sections/payment.php'; ?>
        <? include __DIR__ . '/sections/comment.php'; ?>
        <? include __DIR__ . '/sections/file-uploads.php'; ?>
        <? include __DIR__ . '/sections/summary.php'; ?>
        <? include __DIR__ . '/sections/agreements.php'; ?>

        <div class="text-center">
            <button id="submit-order" class="btn btn-round btn-shadow btn-blue submit-order" type="submit">Оформить</button>
        </div>
    </form>
</div>

<script src="/local/components/online-service/order.form/templates/.default/js/order-form.js"></script>