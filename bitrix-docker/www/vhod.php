<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Вход");
?>

    <div class="auth-block">

        <div class="row">

            <div class="col-lg-6">
                <h2>Вход для зарегистрированных пользователей</h2>
                <p>Используйте почту и пароль указанные вами при регистрации.</p>

                <form method="post" action="<?=SITE_URL?>/vhod.php">
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
                        <button type="submit" class="btn btn-round btn-bluelight btn-shadow">Войти</button>
                        <a href="vosstanovlenie-parolya.php" class="btn btn-round btn-blue-border">Забыли пароль?</a>
                    </div>

                </form>
            </div>
            <!--  -->

            <div class="col-1 col-xl1-0"></div>


            <div class="col-lg-6 col-xl1-5">
                <h2>Регистрация <br>для новых пользователей</h2>
                <p>Используйте почту и пароль указанные вами при регистрации.</p>

                <a href="/registraciya.php" class="btn btn-round btn-big btn-bluelight btn-shadow">Зарегистрироваться</a>
            </div>
            <!--  -->

        </div>
        <!-- row -->
    </div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>