<?php
  $arrAttributs = array(
   'accesskey',
   'disabled',
   'form',
   'multiple',
   'size',
   'tabindex',
  );
  $arrAttributsOption = array(
    'disabled',
    'label',
    'selected',
    'value',
  );
?>
<div class="row">
  <div class="col-xs-12">
    <div class="row">
      <div class="col-xs-6">
        <form id="field-attributs" role="form">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="required" value="required" <?php if(isset($attributs['required'])) echo 'checked';?>> Обязательное для заполнения
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox"  name="autofocus" value="autofocus" <?php if(isset($attributs['autofocus'])) echo 'checked';?>> Установить автофокус
            </label>
          </div>
          <div class="form-group">
            <label>name</label>
            <input type="text" name="name" class="form-control" value="<?php if(isset($attributs['name'])) echo $attributs['name'];?>" onkeyup="this.value=this.value.replace(/[а-яА-Я]/g, '')">
          </div>
          <?php
            if(isset($attributs['required'])) unset($attributs['required']);
            if(isset($attributs['autofocus']))unset($attributs['autofocus']);
            if(isset($attributs['name']))unset($attributs['name']);
          ?>

          <?php if(isset($attributs) && sizeof($attributs)):?>
            <?php foreach($attributs as $attrname => $attrvalue):?>         
              <div class="form-group">
                <div class="row">
                  <div class="col-xs-10">
                    <label><span class="label-attr"><?php echo $attrname?></span></label>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-10">
                    <input type="text" name="<?php echo $attrname?>" class="form-control" value="<?php echo $attrvalue?>">              
                  </div>
                  <div class="col-xs-2">
                    <button type="button" class="del-attr btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
                  </div>
                </div>
              </div>
            <?php endforeach;?>
          <?php endif;?>


          <div id="attr-clone" class="form-group">
            <div class="row">
              <div class="col-xs-10">
                <label><span class="label-attr"></span></label>              
              </div>
            </div>
            <div class="row">
              <div class="col-xs-10">
                <input type="text" class="form-control">                
              </div>
              <div class="col-xs-2">
                <button type="button" class="del-attr btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-top:20px;">
            <div class="row">
              <div class="col-xs-10">
                <select id="attributs" class="form-control">
                  <option disabled selected>Выбрать атрибут</option>
                <?php foreach($arrAttributs as $attribut):?>
                  <option <?php if(isset($attributs) && array_key_exists($attribut, $attributs)) echo "disabled";?>><?php echo $attribut;?></option>
                <?php endforeach;?>
                </select>
              </div>
              <div class="col-xs-2">
                <button type="button" id="add-attr" class="btn btn-success pull-right text-center"><span class="glyphicon glyphicon-plus"></span></button>
              </div>    
            </div>
          </div>
        </form>
        <div class="clearfix"></div>

        <?php if(isset($options) && sizeof($options)):?>
          <?php foreach($options as $option):?>
            <form class="field-options" role="form">
              <div class="form-group">
                <div class="row">
                  <div class="col-xs-8">
                    <input type="text" name="text" class="form-control" placeholder="option" value="<?php echo $option['text'];?>">
                  </div>
                  <div class="col-xs-2">
                    <button type="button" class="slide-option btn btn-default pull-right text-center"><span class="glyphicon glyphicon-chevron-down"></span></button>
                  </div>
                  <div class="col-xs-2">
                    <button type="button" class="del-option btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
                  </div>
                </div>
              </div>
              <?php unset($option['text'])?>
              <div class="slider-option">
                <?php if(sizeof($option)):?>
                  <?php foreach($option as $attrname => $attrvalue):?>
                    <div class="form-group">
                      <div class="row">
                        <div class="col-xs-offset-1 col-xs-8">
                          <label><span class="label-attr"><?php echo $attrname;?></span></label>              
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-xs-offset-1 col-xs-8">
                          <input type="text" class="form-control" name="<?php echo $attrname;?>" value="<?php echo $attrvalue;?>">                
                        </div>
                        <div class="col-xs-2">
                          <button type="button" class="del-attr btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
                        </div>
                      </div>
                    </div>
                  <?php endforeach;?>
                <?php endif;?>
                <div class="form-group attr-option-clone">
                  <div class="row">
                    <div class="col-xs-offset-1 col-xs-8">
                      <label><span class="label-attr"></span></label>              
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-xs-offset-1 col-xs-8">
                      <input type="text" class="form-control">                
                    </div>
                    <div class="col-xs-2">
                      <button type="button" class="del-attr btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
                    </div>
                  </div>
                </div>
                <div class="form-group option-attribute">
                  <div class="row">
                    <div class="col-xs-offset-1 col-xs-8">
                      <select class="attributs form-control">
                          <option disabled selected>Выбрать атрибут</option>
                        <?php foreach($arrAttributsOption as $attribut):?>
                          <option <?php if(array_key_exists($attribut, $option)) echo "disabled";?>><?php echo $attribut;?></option>
                        <?php endforeach;?>
                        </select>
                      </select>
                    </div>
                    <div class="col-xs-2">
                      <button type="button" class="add-option-attr btn btn-success pull-right text-center"><span class="glyphicon glyphicon-plus"></span></button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          <?php endforeach;?>
        <?php endif;?>
        
        <div class="row">
          <div class="col-xs-12">
            <div class="form-group">
              <button type="button" class="add-option btn btn-warning center-block text-center"><span class="glyphicon glyphicon-plus"></span> Добавить option</button>
            </div>
          </div>
        </div>
        <form class="field-options-clone" role="form">
          <div class="form-group">
            <div class="row">
              <div class="col-xs-8">
                <input type="text" name="text" class="form-control" placeholder="option">
              </div>
              <div class="col-xs-2">
                <button type="button" class="slide-option btn btn-default pull-right text-center"><span class="glyphicon glyphicon-chevron-down"></span></button>
              </div>
              <div class="col-xs-2">
                <button type="button" class="del-option btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
              </div>
            </div>
          </div>
          <div class="slider-option">
            <div class="form-group attr-option-clone">
              <div class="row">
                <div class="col-xs-offset-1 col-xs-8">
                  <label><span class="label-attr"></span></label>              
                </div>
              </div>
              <div class="row">
                <div class="col-xs-offset-1 col-xs-8">
                  <input type="text" class="form-control">                
                </div>
                <div class="col-xs-2">
                  <button type="button" class="del-attr btn btn-danger pull-right text-center"><span class="glyphicon glyphicon-minus"></span></button>
                </div>
              </div>
            </div>
            <div class="form-group option-attribute">
              <div class="row">
                <div class="col-xs-offset-1 col-xs-8">
                  <select class="attributs form-control">
                    <option disabled selected>Выбрать атрибут</option>
                  <?php foreach($arrAttributsOption as $attribut):?>
                    <option><?php echo $attribut;?></option>
                  <?php endforeach;?>
                  </select>
                </div>
                <div class="col-xs-2">
                  <button type="button" class="add-option-attr btn btn-success pull-right text-center"><span class="glyphicon glyphicon-plus"></span></button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-xs-6">
        <form id="field-settings" role="form">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="container" value="true" <?php if(!isset($container) || !empty($container)) echo 'checked'?>> Обернуть в контейнер div
            </label>
          </div>
          <div class="form-group">
            <label>id контейнера div</label>
            <input type="text" name="id" class="form-control" value="<?php if(isset($id)) echo $id;?>">
          </div>
          <div class="form-group">
            <label>class контейнера div</label>
            <input type="text" name="class" class="form-control" value="<?php if(isset($class)) echo $class;?>">
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="formail" value="true"  <?php if(isset($formail)) echo 'checked'?>> Добавить в письмо
            </label>
          </div>
          <div class="form-group">
             <label>label</label>
             <input type="text" name="label" class="form-control" placeholder="label (*) - для обязательных полей" value='<?php if(isset($label)) echo $label;?>'>
          </div>
          <div class="form-group">
            <label>label поля в письме</label>
            <input type="text" name="name_mail" class="form-control" value='<?php if(isset($name_mail)) echo $name_mail;?>'>
           </div>
          <div class="form-group">
            <label>Текст ошибки</label>
            <textarea class="form-control" name="error" rows="2"><?php if(isset($error)) echo $error;?></textarea>
          </div>
        </form>
      </div>
    </div>
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
  $MODAL.find('[name="label"]').on('change', function(){
    if(!$MODAL.find('[name="name_mail"]').val()) {
      $MODAL.find('[name="name_mail"]').val(this.value)
    }
  });


  $MODAL.find('#field-attributs .del-attr').on('click', function(){
    var $formGroup = $(this).closest('.form-group'),
        label = $formGroup.find('.label-attr').text();
    $('#attributs option').each(function(){
      if($(this).text()==label) {
        $(this).prop('disabled',false);
      }
    });
    $formGroup.remove();
  });

  $('#add-attr').on('click', function(){
    var $attributs = $('#attributs'),
        label      = $attributs.val(),
        $clone     = $('#attr-clone').clone(true);
    if(label) {
      $clone.removeAttr('id').find('.label-attr').text(label);
      $clone.find('input').attr('name',label);
      $(this)
        .closest('.form-group')
        .before($clone.show());
      $attributs.find('option').each(function(){
        if($(this).text()==label) {
          $(this).prop('disabled',true);
        }
      });
    }
  });
  
  $('.slide-option').on('click', function(){
    $(this).closest('.field-options').find('.slider-option').slideToggle();
  });

  $('.add-option').on('click', function(){
    $(this).closest('.row').before($('.field-options-clone')
                                      .clone(true)
                                      .addClass('field-options')
                                      .removeClass('field-options-clone')
                                      .show());
  });


  $MODAL.find('.slider-option .del-attr').on('click', function(){
      var $formGroup = $(this).closest('.form-group'),
          label = $formGroup.find('.label-attr').text();
     
      $formGroup.closest('.slider-option').find('.attributs option').each(function(){
        if($(this).text()==label) {
          $(this).prop('disabled',false);
        }
      });
      $formGroup.remove();
  });

  $('.del-option').on('click', function() {
  	$(this).closest('.field-options').remove();
  });

  $('.add-option-attr').on('click', function(){
      var $attributs = $(this).closest('.option-attribute').find('.attributs');
          label      = $attributs.val(),
          $clone     = $(this).closest('.option-attribute').siblings('.attr-option-clone').clone(true);

    if(label) {

      $clone.removeClass('attr-option-clone').find('.label-attr').text(label);
      $clone.find('input').attr('name',label);
      $(this)
        .closest('.form-group')
        .before($clone.show());

      $attributs.find('option').each(function(){
        if($(this).text()==label) {
          $(this).prop('disabled',true);
        }
      });      
    }
  })
//]]>
</script>