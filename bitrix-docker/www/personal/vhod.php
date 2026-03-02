<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Вход");
$APPLICATION->AddChainItem("Вход", "/personal/vhod.php");

$APPLICATION->SetPageProperty("title", "Вход купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


global $USER;
if( $USER->IsAuthorized() ){
    header("Location: /personal/lichnyj-kabinet.php");
    exit();
}
?>

    <div class="auth-block">

        <div class="row">

            <div class="col-lg-6">
                <h2>Вход для зарегистрированных пользователей</h2>
                <p>Используйте почту и пароль указанные вами при регистрации.</p>

                <form name="entry-form" action="<?=SITE_URL?>/personal/vhod.php" enctype="multipart/form-data">
                    <?=bitrix_sessid_post()?>
                    <font color="red">
                        <div class="errors">

                        </div>
                    </font>
                    <br>
                    <input type="hidden" name="formid" value="login">
                    <label>
                        <input type="text" name="username" placeholder="E-mail" class="form-control" required="" value="">
                    </label>
                    <!--  -->

                    <label>
                        <input type="password" name="password" placeholder="Пароль" class="form-control" required="">
                    </label>
                    <!--  -->

                    <label class="checkbox remember-me">

                        <input type="checkbox" name="rememberme" value="1">
                        <span>
                                Запомнить меня
                            </span>
                    </label>
                    <!--  -->

                    <div class="buttons">
                        <button type="button" id="entry-btn" class="btn btn-round btn-bluelight btn-shadow">Войти</button>
                        <a href="/personal/vosstanovlenie-parolya.php" class="btn btn-round btn-blue-border">Забыли пароль?</a>
                    </div>

                </form>
            </div>
            <!--  -->

            <div class="col-1 col-xl1-0"></div>


            <div class="col-lg-6 col-xl1-5">
                <h2>Регистрация <br>для новых пользователей</h2>
                <p>Используйте почту и пароль указанные вами при регистрации.</p>

                <a href="/personal/registraciya.php" class="btn btn-round btn-big btn-bluelight btn-shadow">Зарегистрироваться</a>
            </div>
            <!--  -->

        </div>
        <!-- row -->
    </div>

    <script type="text/javascript">
        (function() {

            $(document).on('click', '#entry-btn', function(e) {
                e.preventDefault();
                var $form = $('form[name="entry-form"]');

                var formData = $form.serialize();

                $.ajax({
                    url: '/personal/ajax/ajax-entry-action.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect || '/personal/lichnyj-kabinet.php';
                        } else {
                            $('.errors').html(response.error || 'Неизвестная ошибка').show();
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
                        $('.errors').html(errorMsg).show();
                        console.error('AJAX error:', error, xhr.responseText);
                    }
                });
            });
        })();
    </script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>