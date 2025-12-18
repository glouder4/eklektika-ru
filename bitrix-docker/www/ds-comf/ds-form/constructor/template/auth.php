<?php echo $header?>
<div id="auth" class="col-sm-6 col-sm-offset-3 col-md-offset-4 col-md-4">
	<h1>DSForm</h1>
	<form method="post" role="form" enctype="multipart/form-data">
		<div class="form-group">
			<label for="input-login">Логин</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
				<input type="text" name="login" value="<?php echo $login?>" placeholder="Username" id="input-login" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="input-password"> Пароль</label>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
				<input type="password" name="password" value="<?php echo $password?>" placeholder="Password" id="input-password" class="form-control">
			</div>
		</div>
		<div class="text-right" style="margin-top:20px;">
			<button type="submit" class="btn btn-primary" data-container="body" data-toggle="popover" data-placement="left" data-content="Логин или/и пароль не подходят"><span class="glyphicon glyphicon-log-in"></span> Войти</button>
		</div>
	</form>
</div>
<script src="constructor/js/auth.js"></script>
<?php echo $footer?>