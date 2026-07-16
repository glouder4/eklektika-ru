<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Восстановление пароля");
$APPLICATION->AddChainItem("Восстановление пароля", "/personal/vosstanovlenie-parolya.php");

$APPLICATION->SetPageProperty("title", "Восстановление пароля | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Восстановление пароля личного кабинета компании Эклектика. ☎ 8(800) 777-4723");

global $USER;
if ($USER->IsAuthorized()) {
    header("Location: /personal/lichnyj-kabinet.php");
    exit();
}

$changePassword = isset($_REQUEST['change_password']) && $_REQUEST['change_password'] === 'yes';
$userLogin = isset($_REQUEST['USER_LOGIN']) ? trim((string)$_REQUEST['USER_LOGIN']) : '';
$userCheckword = isset($_REQUEST['USER_CHECKWORD']) ? trim((string)$_REQUEST['USER_CHECKWORD']) : '';
$isChangeMode = $changePassword && $userLogin !== '' && $userCheckword !== '';
?>

    <div class="auth-block">

        <div class="row">

            <div class="col-lg-6">
                <? if ($isChangeMode): ?>
                    <h2>Смена пароля</h2>
                    <p>Введите новый пароль и подтверждение.</p>

                    <form name="password-change-form" action="/personal/vosstanovlenie-parolya.php" method="post">
                        <?=bitrix_sessid_post()?>
                        <input type="hidden" name="action" value="change">
                        <input type="hidden" name="USER_LOGIN" value="<?=htmlspecialcharsbx($userLogin)?>">
                        <input type="hidden" name="USER_CHECKWORD" value="<?=htmlspecialcharsbx($userCheckword)?>">

                        <font color="red">
                            <div class="errors"></div>
                        </font>
                        <div class="success-message" style="display:none; color: green;"></div>
                        <br>

                        <label>
                            <input type="password" name="password" placeholder="Новый пароль" class="form-control" required="" autocomplete="new-password" value="">
                        </label>

                        <label>
                            <input type="password" name="password_confirm" placeholder="Подтверждение пароля" class="form-control" required="" autocomplete="new-password" value="">
                        </label>

                        <div class="buttons">
                            <button type="button" id="password-change-btn" class="btn btn-round btn-bluelight btn-shadow">Сохранить пароль</button>
                            <a href="/personal/vhod.php" class="btn btn-round btn-blue-border">Вернуться ко входу</a>
                        </div>
                    </form>
                <? else: ?>
                    <h2>Восстановление пароля</h2>
                    <p>Укажите e-mail, указанный при регистрации. Мы отправим инструкции по восстановлению.</p>

                    <form name="password-request-form" action="/personal/vosstanovlenie-parolya.php" method="post">
                        <?=bitrix_sessid_post()?>
                        <input type="hidden" name="action" value="request">

                        <font color="red">
                            <div class="errors"></div>
                        </font>
                        <div class="success-message" style="display:none; color: green;"></div>
                        <br>

                        <label>
                            <input type="email" name="email" placeholder="E-mail" class="form-control" required="" value="">
                        </label>

                        <div class="buttons">
                            <button type="button" id="password-request-btn" class="btn btn-round btn-bluelight btn-shadow">Отправить</button>
                            <a href="/personal/vhod.php" class="btn btn-round btn-blue-border">Вернуться ко входу</a>
                        </div>
                    </form>
                <? endif; ?>
            </div>

        </div>
        <!-- row -->
    </div>

    <script type="text/javascript">
        (function() {
            var ajaxUrl = '/personal/ajax/ajax-password-reset-action.php';

            function showError($form, message) {
                $form.find('.success-message').hide().html('');
                $form.find('.errors').html(message || 'Неизвестная ошибка').show();
            }

            function showSuccess($form, message) {
                $form.find('.errors').html('').hide();
                $form.find('.success-message').html(message || '').show();
            }

            function postPasswordReset($form) {
                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var action = $form.find('input[name="action"]').val();
                            if (action === 'change') {
                                window.location.href = response.redirect || '/personal/vhod.php';
                            } else {
                                showSuccess($form, response.message || '');
                            }
                        } else {
                            showError($form, response.error || 'Неизвестная ошибка');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = 'Сетевая ошибка';
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            errorMsg = resp.error || 'Ошибка сервера';
                        } catch (e) {
                            errorMsg = 'Ошибка сервера. Попробуйте позже.';
                        }
                        showError($form, errorMsg);
                        console.error('AJAX error:', error, xhr.responseText);
                    }
                });
            }

            $(document).on('click', '#password-request-btn', function(e) {
                e.preventDefault();
                postPasswordReset($('form[name="password-request-form"]'));
            });

            $(document).on('click', '#password-change-btn', function(e) {
                e.preventDefault();
                postPasswordReset($('form[name="password-change-form"]'));
            });
        })();
    </script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
