<div class="row">
  <div class="col-xs-12">
    <form role="form">
      <div class="checkbox">
        <label>
          <input type="checkbox" name="container" value="true" <?php if(!isset($container) || !empty($container)) echo 'checked'?>> Обернуть в контейнер div
        </label>
      </div>
      <div class="form-group col-xs-6" style="margin: 0 0 15px;padding-left: 0;">
        <label>id контейнера div</label>
        <input type="text" name="id" class="form-control" value="<?php if(isset($id)) echo $id;?>">
      </div>
      <div class="form-group col-xs-6" style="margin: 0 0 15px;padding-right: 0;">
        <label>class контейнера div</label>
        <input type="text" name="class" class="form-control" value="<?php if(isset($class)) echo $class;?>">
      </div>
      <div class="form-group">
        <label>HTML код</label>
        <textarea id="freeTextArea" class="form-control" name="value" rows="3"><?php echo $value?></textarea>
      </div>
    </form>
  </div>
</div>
<script type="text/javascript">
//<![CDATA[
  if(!$MODAL.find('[name="container"]').prop('checked')) {
    $MODAL.find('[name="id"]').prop("readonly", true);
    $MODAL.find('[name="class"]').prop("readonly", true);
  }
  $MODAL.find('[name="container"]').on('change',function() {
    if($(this).is(':checked')) {
      $MODAL.find('[name="id"]').prop("readonly", false);
      $MODAL.find('[name="class"]').prop("readonly", false);
    } else {
      $MODAL.find('[name="id"]').prop("readonly", true);
      $MODAL.find('[name="class"]').prop("readonly", true);
    }
  });
  if($MODAL.is(':visible')) {
    EDITOR = CodeMirror.fromTextArea(document.getElementById("freeTextArea"), {
      mode: {name: "htmlmixed"},
      lineNumbers: true,
      tabSize: 2,
      viewportMargin:5
    });
    EDITOR.on("scroll",function(){
      EDITOR.setSize('100%', 'auto');
    });
  } else {
    $MODAL.on('shown.bs.modal', function (e) {
      EDITOR = CodeMirror.fromTextArea(document.getElementById("freeTextArea"), {
        mode: {name: "htmlmixed"},
        lineNumbers: true,
        tabSize: 2,
        viewportMargin:5
      });
      EDITOR.on("scroll",function(){
        EDITOR.setSize('100%', 'auto');
      });
    });
  }
//]]>
</script>