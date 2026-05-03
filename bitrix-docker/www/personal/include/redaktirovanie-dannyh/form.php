<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var string $name
 * @var string $lastName
 * @var string $email
 * @var string $phone
 * @var string $workPhone
 * @var array|null $company
 * @var bool $isUserCompanyDirector признак «руководитель компании» (UF_IS_DIRECTOR), см. {@see \OnlineService\Sync\FromCrm\CrmInboundUfMap::userDirectorUfToCrmInt}
 */
?>
<font color="red">
    <div class="errors"></div>
</font>
<form name="perosnal-profile-form" class="cart-order left6 reg-form edit-form" enctype="multipart/form-data">
    <?= bitrix_sessid_post() ?>

    <div class="reg-form-section">
        <h3 class="reg-form-section__title">Данные контактного лица</h3>
        <div class="row">
            <div class="col-md-4">
                <label>Имя <font color="red">*</font><span class="help-block text-error"></span></label>
            </div>
            <div class="col-md-8">
                <input required maxlength="100" name="name" id="name" type="text" value="<?= htmlspecialchars($name) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label>Фамилия <font color="red">*</font><span class="help-block text-error"></span></label>
            </div>
            <div class="col-md-8">
                <input required maxlength="100" name="lastname" id="lastname" type="text" value="<?= htmlspecialchars($lastName) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="phone">Телефон c указанием кода региона без скобок и пробелов<font color="red">*</font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input required name="phone" inputmode="tel" id="phone" type="text" value="<?= htmlspecialchars($workPhone) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="mobilephone">Мобильный телефон <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="20" name="mobilephone" id="mobilephone" type="text" inputmode="tel" class="input-number"
                       value="<?= htmlspecialchars($phone) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="email">E-mail <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input name="email" type="email" id="email" maxlength="100" value="<?= htmlspecialchars($email) ?>">
            </div>
        </div>
    </div>

    <?php if ($company && !empty($isUserCompanyDirector)): ?>
        <div class="reg-form-section">
            <p class="reg-form-section__text">Для редактирования компании перейдите по
                <a href="/company/profile/edit/?id=<?= (int)$company['id'] ?>">ссылке</a>.</p>
        </div>
    <?php endif; ?>

    <div class="row reg-form-section reg-form-section--submit reg-form__submit-row">
        <button type="button" id="save-form" class="btn btn-round btn-shadow btn-blue" data-default-label="Сохранить">
            <span class="reg-form__submit-loader" aria-hidden="true"></span>
            <span class="reg-form__submit-label">Сохранить</span>
        </button>
    </div>
</form>
