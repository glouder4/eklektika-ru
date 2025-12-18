<div id="slide_panel_wrap">
  <div class="slide_panel">
    <h3>Основные настройки</h3>
    <form id="setting-form" role="form">
      <div class="form-group">
        <label class="col-xs-12">
          Адреса получателей
          <span style="color:red">*</span>
          <input class="form-control" type="text" name="mail_to_email" placeholder="a@b.ru;b@b.ru" value="<?php echo $mailToEmail;?>">
        </label>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Тема письма
          <span style="color:red">*</span>
          <input class="form-control" type="text" name="mail_subject" placeholder="Форма обратной связи" value="<?php echo $mailSubject;?>">
        </label>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Кодировка
          <input class="form-control" type="text" name="charset" placeholder="Charset" value="<?php echo $charset;?>">
        </label>
      </div>
      <div class="form-group">
        <div class="checkbox col-xs-12">
          <label>
            <input type="checkbox" name="validate_html5" <?php if($validateHtml5) echo 'checked';?>>
            Проверка HTML5
          </label>
        </div>
      </div>
      <div class="form-group inline-group">
        <label>
          <div class="col-xs-3">
            <input class="col-xs-3 form-control" type="text" name="validate_strlen" value="<?php echo $validateStrlen;?>">
          </div>
          <div class="col-xs-9 inline-label">
            Min количество символов
          </div>
        </label>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Общий текст ошибки
          <textarea class="form-control" rows="2" name="validate_error"><?php echo $validateError;?></textarea>
        </label>
      </div>
      <div class="form-group">
        <div class="checkbox col-xs-12">
          <label>
            <input type="checkbox" name="smtp_on" <?php if($smtp['On']) echo 'checked';?>>
            Использовать SMTP
          </label>
        </div>
      </div>
      <div class="smtp">
        <div class="form-group">
          <label class="col-xs-12">
            Сервер исходящей почты
            <input class="form-control" type="text" name="smtp_host" value="<?php echo $smtp['Host'];?>">
          </label>
        </div>
        <div class="form-group">
          <label class="col-xs-12">
            Протокол шифрования
            <input class="form-control" type="text" name="smtp_secure" value="<?php echo $smtp['Secure'];?>">
          </label>
        </div>
        <div class="form-group inline-group">
          <label>
            <div class="col-xs-4">
              <input class="col-xs-3 form-control" type="text" name="smtp_port" value="<?php echo $smtp['Port'];?>">
            </div>
            <div class="col-xs-8 inline-label">
              Порт
            </div>
          </label>
        </div>
        <div class="form-group">
          <div class="checkbox col-xs-12">
            <label>
              <input type="checkbox" name="smtp_auth" <?php if($smtp['Auth']) echo 'checked';?>>
              Аутентификация
            </label>
          </div>
        </div>
        <div class="auth">
          <div class="form-group">
            <label class="col-xs-12">
              Имя пользователя
              <input class="form-control" type="text" name="smtp_username" value="<?php echo $smtp['Username'];?>">
            </label>
          </div>
          <div class="form-group">
            <label class="col-xs-12">
              Пароль
              <input class="form-control" type="text" name="smtp_password" value="<?php echo $smtp['Password'];?>">
            </label>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Адреса копии письма
          <input class="form-control" type="text" name="mail_cc_email" placeholder="c@b.ru;d@b.ru" value="<?php echo $mailCcEmail ?>">
        </label>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Адрес отправителя
          <input class="form-control" type="text" name="mail_from_email" placeholder="info@domain.ru" value="<?php echo $mailFromEmail ?>">
        </label>
      </div>
      <div class="form-group">
        <label class="col-xs-12">
          Имя отправителя
          <input class="form-control" type="text" name="mail_from_name" placeholder="Info" value="<?php echo $mailFromName ?>">
        </label>
      </div>
      <div class="form-group">
        <div class="checkbox col-xs-12">
          <label>
            <input type="checkbox" name="reverse_email" <?php if($mailReverseEmail) echo 'checked';?>>
            Уведомление пользователя
          </label>
        </div>
      </div>
    </form>
  </div>
  <div class="slide_panel_label">Настройки</div>
</div>