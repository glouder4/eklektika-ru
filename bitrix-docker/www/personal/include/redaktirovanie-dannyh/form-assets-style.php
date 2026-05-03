<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<style>
    .reg-form .field-error {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 1px #e74c3c;
    }

    .reg-form .field-valid {
        border-color: #27ae60 !important;
    }

    .reg-form .help-block.text-error, .reg-form .error {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 4px;
        display: block;
    }

    .reg-form .error:empty {
        display: none;
    }

    .reg-form .validation-summary {
        background: #fdf2f2;
        border: 1px solid #e74c3c;
        border-radius: 6px;
        padding: 12px 16px;
        margin-bottom: 20px;
        color: #c0392b;
        font-size: 14px;
        display: none;
    }

    .reg-form .validation-summary.visible {
        display: block;
    }

    .reg-form .validation-summary ul {
        margin: 0;
        padding-left: 20px;
    }

    .reg-form-section {
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e5e5e5;
    }

    .reg-form-section:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .reg-form-section__title {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .reg-form-section--submit {
        border-bottom: none;
        padding-top: 8px;
        margin-bottom: 0;
    }

    .reg-form #save-form {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .reg-form #save-form .reg-form__submit-loader {
        display: none;
        box-sizing: border-box;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        animation: reg-form-spin 0.65s linear infinite;
        flex-shrink: 0;
    }

    .reg-form #save-form.is-loading .reg-form__submit-loader {
        display: inline-block;
    }

    .reg-form #save-form.is-loading {
        opacity: 0.92;
        cursor: wait;
    }

    @keyframes reg-form-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
