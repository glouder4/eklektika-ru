<?php echo $header?>
<link href="constructor/css/codemirror.css" rel="stylesheet">
<link href="constructor/css/jquery-ui.min.css" rel="stylesheet">
<div id="main" class="col-xs-12">
  <?php echo $leftColumn?>
  <?php echo $rightColumn?>
  <div class="container">
    <div id="constructor" class="col-sm-12">
      <form id="logout" method="post">
        Привет, <b><?php echo $login?></b>!
        <?php if(defined('AUTH_CMS') && !AUTH_CMS):?>
          <button type="submit" name="profile" value="on" class="btn btn-default"><span class="glyphicon glyphicon-user"></span> Профиль</button>
        <?php endif;?>
        <button type="submit" name="logout" value="off" class="btn btn-default"><span class="glyphicon glyphicon-off"></span> Выйти</button>
      </form>
      <h1>Конструктор форм</h1>
      <div id="menu" class="row">
        <form role="form" method="POST">
          <div class="form-group col-sm-6 col-lg-4">
            <select class="form-control" name="select-form">
              <option value="">Новая форма</option>
              <?php if(sizeof($selectForm)):?>
                <?php foreach($selectForm as $formid => $subject):?>
                  <option value="<?php echo $formid;?>" <?php if($formid == $formId) echo 'selected';?>><?php echo $formid;if(!empty($subject)) echo ' - ' . $subject?></option>
                <?php endforeach;?>
              <?endif;?>
            </select>
          </div>
          <div class="form-group col-sm-4 col-lg-3">
            <input class="form-control" name="form-id" placeholder="Символьный код" value="<?php echo $formId?>"  onkeyup="this.value=this.value.replace(/[а-яА-Я\W]/g, '')" <?php if(!empty($formId)) echo 'readonly'?>>
          </div>
          <div class="button-block form-group col-sm-4 <?php if(empty($formId)) echo "hidden";?>">
            <input type="hidden" name="copy-form">
            <button type="submit" class="button-copy btn btn-default btn-sm"><span class="glyphicon glyphicon-file"></span> Копировать</button>
            <input type="hidden" name="del-form">
            <button type="submit" class="button-del btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span> Удалить</button>
          </div>
        </form>
      </div>
      <div class="row">
        <ul id="draggable" class="col-sm-12">
          <div class="col-sm-3">
            <li data-edit="Freearea">
              <span class="title">
                <span class="glyphicon glyphicon-plus"></span> Freearea
              </span>
              <span class="data-field">{"value":""}</span>
            </li>
          </div>
          <div class="col-sm-3">
            <li data-edit="Input">
              <span class="title">
                <span class="glyphicon glyphicon-plus"></span> Input
              </span>
              <span class="data-field">{"container":"true","formail":"true"}</span>
            </li>
          </div>
          <div class="col-sm-3">
            <li data-edit="Textarea">
              <span class="title">
                <span class="glyphicon glyphicon-plus"></span> Textarea
              </span>
              <span class="data-field">{"container":"true","formail":"true"}</span>
            </li>
          </div>
          <div class="col-sm-3">
            <li data-edit="Select">
              <span class="title">
                <span class="glyphicon glyphicon-plus"></span> Select
              </span>
              <span class="data-field">{"container":"true","formail":"true"}</span>
            </li>
          </div>
        </ul>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <div id="sortable">
            <ul class="sortable">
            <?php if(sizeof($configform)):?>
              <?php foreach($configform as $fieldConfig):?>
                <?php foreach($fieldConfig as $fieldName => $fieldStr):?>
                  <?php 
                    $fieldStr = preg_replace('|\n|siU', '\\n', htmlspecialchars($fieldStr));
                    $fieldStr = preg_replace('|\t|siU', '\\t', $fieldStr);
                  ?>
                  <li data-edit="<?php echo ucfirst($fieldName);?>" class="ui-draggable" style="display: list-item;">
                    <span class="title"><?php echo $fieldName;?></span>
                    <span class="data-field"><?php echo $fieldStr;?></span>
                  </li>
                <?php endforeach;?>
              <?php endforeach;?>
            <?php else:?>
              <li data-edit="Freearea" class="ui-draggable" style="display: list-item;">
                <span class="title">freearea</span>
                <span class="data-field">{&quot;container&quot;:false,&quot;value&quot;:&quot;&lt;div class=\&quot;form-head\&quot;&gt;Новая форма&lt;/div&gt;&quot;}</span>
              </li>
              <li data-edit="Input" class="ui-draggable" style="display: list-item;">
                <span class="title">input</span>
                <span class="data-field">{&quot;container&quot;:true,&quot;class&quot;:&quot;buttonform&quot;,&quot;attributs&quot;:{&quot;type&quot;:&quot;submit&quot;,&quot;value&quot;:&quot;Отправить&quot;}}</span>
              </li>
              <li data-edit="Freearea" class="ui-draggable" style="display: list-item;">
                <span class="title">freearea</span>
                <span class="data-field">{&quot;container&quot;:false,&quot;value&quot;:&quot;&lt;div class=\&quot;error_form\&quot;&gt;&lt;/div&gt;&quot;}</span>
              </li>
            <?php endif;?>
            </ul>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <button class="generate pull-left btn btn-primary <?php if(empty($formId)) echo "hidden";?>" data-loading-text="Сохранение..." type="button">
            <span class="glyphicon glyphicon-retweet"></span>
            Генерировать форму
          </button>
          <button class="getcode pull-left btn btn-warning <?php if(empty($formId)) echo "hidden";?>" type="button">
            <span class="glyphicon glyphicon-align-left"></span>
            Код для вставки
          </button>
          <button class="clear pull-right btn btn-danger" type="button">
            <span class="glyphicon glyphicon-remove"></span>
            Очистить
          </button>
          <button class="save pull-right btn btn-success" data-loading-text="Сохранение..." type="button">
            <span class="glyphicon glyphicon-ok"></span>
            Сохранить
          </button>
        </div>
      </div>
      <div class="row">
        <div id="alert-form" class="col-sm-12"></div>
      </div>
      <div class="row">
        <div id="primer" class="col-sm-12">
          <h1 class="primer">Пример формы</h1>
          <div class="primer"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="dialog-confirm">
    <p>
      <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
      <span class="text"></span>
    </p>
  </div>
  <div id="field-edit" class="modal fade">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button class="close" data-dismiss="modal" aria-hidden="true" type="button">&times;</button>
          <h3 class="modal-title"></h3>
        </div>
        <div id="alert">
          <div class="alert alert-danger fade in">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <span class="alert-text"></span>
          </div>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
          <button id="saveField" class="btn btn-primary" type="button">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
  <style id="form-style-css" type="text/css"></style>
</div>
<script src="constructor/js/jquery-ui.min.js"></script>
<script src="constructor/js/jquery.ui.touch-punch.min.js"></script>
<script src="constructor/js/cssParser.js"></script>
<script src="constructor/js/codemirror.js"></script>
<script src="constructor/js/codemirror-css.js"></script>
<script src="constructor/js/codemirror-htmlmixed.js"></script>
<script src="constructor/js/codemirror-xml.js"></script>
<script src="constructor/js/matchbrackets.js"></script>
<script src="js/dsforms.js"></script>
<script src="constructor/js/constructor.js"></script>
<?php echo $footer?>