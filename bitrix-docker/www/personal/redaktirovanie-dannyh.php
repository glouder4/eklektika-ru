<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "N";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Редактирование данных");
$APPLICATION->AddChainItem("Редактирование данных", "/personal/lichnyj-kabinet.php");

global $USER;
if ($USER->IsAuthorized()) {
    $userFields = CUser::GetByID($USER->GetID())->Fetch();

    $name        = $userFields['NAME'] ?? '';
    $lastName    = $userFields['LAST_NAME'] ?? '';
    $email       = $userFields['EMAIL'] ?? '';
    $phone       = $userFields['PERSONAL_PHONE'] ?? ''; // или WORK_PHONE, зависит от того, где хранится
    $workPhone   = $userFields['WORK_PHONE'] ?? '';
    $yurAdress   = $userFields['UF_UR_ADRES'] ?? '';
    $inn         = $userFields['UF_INN'] ?? '';
    $workProfile = $userFields['WORK_PROFILE'] ?? '';
    $workCompany = $userFields['WORK_COMPANY'] ?? '';
    $workWWW     = $userFields['WORK_WWW'] ?? '';
} else {
    header("Location: /");
    exit();
}

?>
<div class="cart-order" style="margin:0;">
    <br>
    <a href="/personal/lichnyj-kabinet.php">Главная страница личного кабинета</a> &nbsp; &nbsp;
    <a href="/personal/redaktirovanie-dannyh.php">Редактировать данные</a> &nbsp; &nbsp;
    <a href="/personal/prosmotr-zakazov.php">Просмотр заказов</a> &nbsp; &nbsp;
    <h1>Редактирование данных</h1>
    <font color="red"><div class="errors"></div></font>
    <form name="perosnal-profile-form" class="cart-order left6" enctype="multipart/form-data">
        <?=bitrix_sessid_post()?>
        <div class="row">
            <div class="col-md-4">
                <label>Имя <font color="red">*</font><span class="help-block text-error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="100" name="name" id="name" type="text" value="<?=$name;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label>Фамилия <font color="red">*</font><span class="help-block text-error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="100" name="lastname" id="lastname" type="text" value="<?=$lastName;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="phone">Телефон c указанием кода региона без скобок и пробелов<font color="red">*</font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input name="phone" inputmode="tel" id="phone" type="text" value="<?=$workPhone;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="mobilephone">Мобильный телефон c указанием кода региона без скобок и пробелов <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="20" name="mobilephone" id="mobilephone" type="text" inputmode="tel" class="input-number" value="<?=$phone;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="address">Юридический адрес<font color="red"></font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="100" name="address" type="text" id="address" value="<?=$yurAdress;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="activities">Сфера деятельности <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="50" name="activities" type="text" id="activities" value="<?=$workProfile;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="state">Название юридического лица<font color="red">*</font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input maxlength="100" id="state" type="text" name="name_company" value="<?=$workCompany?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="fax">ИНН организации <font color="red"></font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input class="input-number" name="inn" type="text" id="fax" value="<?=$inn;?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="sait">WEB сайт Вашей компании:<font color="red"></font> <span class="error"></span></label>
            </div>
            <div class="col-md-8">
                <input name="sait" inputmode="url" maxlength="50" type="text" id="sait" value="<?=$workWWW;?>">
            </div>
        </div>
        <div class="row">
            <input type="button" id="save-form" value="Редактировать" class="btn btn-round btn-shadow btn-blue" />
        </div>
    </form>
</div>
<script type="text/javascript">
    var google_conversion_id = 1017225008,
        google_conversion_language = "en",
        google_conversion_format = "3",
        google_conversion_color = "ffffff",
        google_conversion_label = "d7PMCJ-puGkQsL6G5QM",
        google_remarketing_only = !1;
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
    var manager = '';
    if(manager) {
        let val = $('#zip').val();
        $('#zip').find('option').each(function() {
            if($(this).val() == val) {
                $(this).attr('selected', 'selected');
            }
        })
    }
</script>

<script type="text/javascript">
    $(document).on('click', '#save-form', function(e) {
        e.preventDefault(); // отменяем стандартную отправку
        console.log('submit')

        const formData = $('form[name="perosnal-profile-form"]').serialize(); // собираем все поля формы

        // Пример: вывод данных в консоль
        console.log('Данные для отправки:', formData);

        $.ajax({
            url: '/personal/ajax/ajax.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Успех
                    alert('Профиль успешно обновлён!');
                    console.log('Данные сохранены:', response.message);
                } else {
                    // Ошибка на стороне сервера
                    alert('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                    console.error('Ошибка обновления:', response.error);
                }
            },
            error: function(xhr, status, error) {
                // Сетевая ошибка или невалидный JSON
                let errorMsg = 'Сетевая ошибка';
                try {
                    const resp = JSON.parse(xhr.responseText);
                    errorMsg = resp.error || 'Ошибка сервера';
                } catch (e) {
                    // Если ответ не JSON — возможно, фатальная ошибка PHP или 500
                    errorMsg = 'Ошибка сервера (неверный формат ответа). Проверьте логи.';
                }
                alert('Не удалось обновить профиль: ' + errorMsg);
                console.error('AJAX error:', error, xhr.responseText);
            }
        });
    });
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
