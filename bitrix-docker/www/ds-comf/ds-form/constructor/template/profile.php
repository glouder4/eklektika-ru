<?php echo $header?>
<div id="profile" class="col-sm-6 col-sm-offset-3 col-md-offset-4 col-md-4">
	<h1>Редактирование профиля</h1>
	<form method="post" role="form" enctype="multipart/form-data">
		<div class="form-group">
			<label for="input-login">Логин</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
				<input type="text" name="login" value="<?php echo $login?>" placeholder="Username" id="input-login" class="form-control" readonly>
			</div>
		</div>
		<div class="form-group">
			<label for="input-password"> Старый пароль</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
				<input type="password" name="old_password" placeholder="Old password" id="input-password" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="input-password"> Новый пароль</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
				<input type="password" name="new_password" placeholder="New password" id="input-password" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="input-password"> Повторите пароль</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
				<input type="password" name="new_password2" placeholder="New password" id="input-password" class="form-control">
			</div>
		</div>
		<div class="text-right" style="margin-top:20px;">
			<a class="btn btn-warning" href="<?php $backUrl?>"><span class="glyphicon glyphicon-share"></span> Назад</a>
			&nbsp;
			<button type="submit" class="btn btn-primary" data-container="body" data-toggle="popover" data-placement="left" data-content="Поля заполнены не корректно"><span class="glyphicon glyphicon-edit"></span> Изменить пароль</button>
		</div>
	</form>
</div>
<script src="constructor/js/auth.js"></script>
<?php echo $footer?>