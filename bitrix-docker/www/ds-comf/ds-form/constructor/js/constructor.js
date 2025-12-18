var EDITOR,
    fieldName,
    $MODAL  = $('#field-edit'),
    $DIALOG = $('#dialog-confirm');

jQuery(function($) {
  var $panel       = $(".slide_panel"),
      $css         = $(".slide_css"),
      $primer      = $('#primer'),
      windowHeight = window.innerHeight 
                    || document.documentElement.clientHeight
                    || document.body.clientHeight;
  $panel.height(windowHeight-42);

  $css.find('textarea').height(windowHeight-111);
  var editorcss = CodeMirror.fromTextArea($('[name="form-css"]')[0], {
      lineNumbers   : true,
      matchBrackets : true,
      'mode'        : 'text/css',
      indentUnit    : 2,
      tabSize       : 2,
  });
  $css.find('.CodeMirror').height(windowHeight-135);
  $css.find('.savecss').on('click',function(){

    var parser = new CSSParser(),
        sheet = parser.parse(editorcss.getValue(), false, true);

    sheet.cssRules.forEach(function(item, i, arr) {
      item.mSelectorText = '#primer ' + item.mSelectorText;
    });
    
    $('#form-style-css').html(sheet.cssText());
    
  });
  $css.hide();

  //Панель настроек
  var panelSettings = function() {
    if($panel.is(':visible')) {
      $panel.animate({'margin-left':'-300px'},500,function(){
        $(this).hide();
      });
    } else {
      $panel.show();
      $panel.animate({'margin-left':0},500);
    }
  }

  var panelCSS = function () {
    if($css.is(':visible')) {
      $css.animate({'margin-right':'-300px'},500,function(){
        $(this).hide();
      });
    } else {
      $css.show();
      $css.animate({'margin-right':0},500);
    }
  }

  $('.slide_panel_label').on('click',function(){
    panelSettings();
  });

  //Панель CSS
  $('.slide_css_label').on('click',function(){
    panelCSS();
  });

  $('body').on('click', function(e) {
    if (!$(e.target).closest("#slide_panel_wrap").length && !$(e.target).closest("#slide_css_wrap").length) {
      if($panel.is(':visible')) {
        panelSettings();
      }
      if($css.is(':visible')) {
        panelCSS();
      }
    }
    e.stopPropagation();
  });

  //SMTP в настройках
  var $smtp = $('.smtp');
  if($('[name=smtp_on]').is(':checked')) {
    $smtp.slideToggle();
  }
  $('[name=smtp_on]').on('change',function(){
    if($(this).is(':checked')) {
      $smtp.slideToggle();
    } else {
      $smtp.slideToggle();
    }
  });
  //SMTP Аутентификация
  var $auth = $('.auth');
  if(!$('[name=smtp_auth]').is(':checked')) {
    $auth.find('input').prop("disabled", true);
  }
  $('[name=smtp_auth]').on('change',function(){
    if($(this).is(':checked')) {
      $auth.find('input').prop("disabled", false);
    } else {
      $auth.find('input').prop("disabled", true);
    }
  });

  var addAlert = function(selector, text) {
    $(selector).append($('<div />', {'class':'alert alert-danger fade in', 'html':text})
                          .prepend($('<button />', {
                                        'type':'button','class':'close', 'data-dismiss':'alert',
                                        'aria-hidden':'true', 'html':'&times;'
                                      })
                          )
                          .alert()
    );
  }

  var editField = function(obj) {
    var $li,
        $dataField = {};

    if(obj[0]) {
      $li = obj;
    } else {
      $li = $(this).hasClass('edit')? $(this).parent() : $(this);
    }

    fieldName  = $li.attr('data-edit');

    $MODAL.find('.modal-dialog').removeClass('modal-lg').addClass('modal-md');
    $MODAL.find('#alert').empty();
    $MODAL.find('#saveField').show();
    $MODAL.find('.modal-title').html('Редактирование <span>' + fieldName + '</span>');
    $MODAL.find('.modal-body').load(dsformROOT + 'index.php?route=DSConstructor&m=getField',{'fieldName':fieldName,'dataField':$li.find('.data-field').text()});
    $MODAL.find('#saveField').on('click', function(){
      var error = false;
      $MODAL.find('.form-group').removeClass('has-error');
      $MODAL.find('#alert').empty();
      switch (fieldName) {
          case 'Freearea':
            $.each($MODAL.find('form').serializeArray(), function(){
              $dataField[this.name] = this.value;
            });
            $dataField['value'] = EDITOR.getValue();
            if(!$dataField['value']) {
              addAlert('#alert', 'Не задано значение поля <b>«HTML код»</b>!');
              $MODAL.find('.CodeMirror').addClass('has-error');
              return false;
            }
            break;

          case 'Textarea':
          case 'Input':
              $dataField.attributs = {};
              if($MODAL.find('[name="formail"]').prop("checked")) {
                $MODAL.find('[name="name_mail"],[name="name"]').each(function(){
                  if(!this.value) {
                    error = true;
                    $(this).closest('.form-group').addClass('has-error');
                  }
                });
              }

              if(error) {
                  addAlert('#alert', 'Остались не заполненные поля!');
                return false;
              }

              $.each($MODAL.find('#field-settings').serializeArray(), function(){
                $dataField[this.name] = this.value;
              });
              $.each($MODAL.find('#field-attributs').serializeArray(), function(){
                $dataField.attributs[this.name] = this.value;
              });
            break;

          case 'Select':
            $dataField.attributs = {};
            if($MODAL.find('[name="formail"]').prop("checked")) {
              $MODAL.find('[name="name_mail"],[name="name"]').each(function(){
                if(!this.value) {
                  error = true;
                  $(this).closest('.form-group').addClass('has-error');
                }
              });
            }

            if($('.field-options').length) {
              $('.field-options [name="text"]').each(function(){
                if(!$(this).val()) {
                  error = true;
                  $(this).closest('.form-group').addClass('has-error');
                }
              });
            }

            if(error) {
              addAlert('#alert','Остались не заполненные поля!');
              return false;
            }

            $.each($MODAL.find('#field-attributs').serializeArray(), function(){
              $dataField.attributs[this.name] = this.value;
            });

            $.each($MODAL.find('#field-settings').serializeArray(), function(){
              $dataField[this.name] = this.value;
            });

            $dataField.options = {};
            var counter = 0;
            $MODAL.find('.field-options').each(function(){
              $dataField.options[counter] = {};
              $.each($(this).serializeArray(), function(){
                $dataField.options[counter][this.name] = this.value;
              });
              counter++;
            });

            break;
      }

      $dataField['container'] = !$dataField['container']? false : $dataField['container'];

      $li.find('.data-field').text(JSON.stringify($dataField));
      $MODAL.modal('hide');
    });
    $MODAL.on('hide.bs.modal',function(){
      $MODAL.off();
      $MODAL.find('#saveField').off();
      $MODAL.find('.modal-body').empty();
    });
    $MODAL.modal();
  }

  //Поле сортировки
  var addEditField = function(event, ui) {
        $('.sortable').find('li').each(function(){
          var $li = $(this);
          $li.find('.glyphicon.glyphicon-plus').remove();
          if(!$li.find('.delete').length) {
            $li.append($('<span />',{
                class :'delete glyphicon glyphicon-trash',
                click :function(){
                  var $liparent = $(this).parent();
                  $DIALOG.dialog({
                    resizable :false,
                    title     :"Внимание!",
                    width     :430,
                    modal     : true,
                    buttons   : {
                      "Удалить" : function() {
                        $(this).dialog("close");
                        $liparent.remove();
                      },
                      "Отмена" : function() {
                        $(this).dialog("close");
                      }
                    },
                    open : function() {
                      $(this).find('p .text').empty().append('Вы действительно хотите удалить элемент?');
                    }
                  });
                } 
              }))
              .append($('<span />',{
                class :'copy glyphicon glyphicon-file',
                click : function() {
                  var $li = $(this).parent();
                  $li.after($li.clone(true));
                }
              }))
              .append($('<span />',{
                class :'edit glyphicon glyphicon-pencil',
                click : editField
              }))
              .on('dblclick', editField);
            if(ui) editField($li);
          }
        });
      }

  addEditField();

  $( ".sortable" ).sortable({
    revert  : true,
    receive : addEditField,
    zIndex  : 0
  });
  $( "#draggable li" ).draggable({
    connectToSortable : ".sortable",
    revert            : "invalid",
    zIndex            : 999,
    helper            : "clone"
  });
  $( "ul, li" ).disableSelection();

  $('[name="select-form"]').on('change', function(){
    var $form = $(this),
        $formname = $('[name="form-id"]');
    $DIALOG.dialog({
      resizable : false,
      title     : "Внимание!",
      width     : 430,
      modal     : true,
      buttons   : {
        "Перезагрузить" : function() {
           $formname.val($form.val());
           $formname.closest('form').submit();
        },
        "Отмена" : function() {
          $form.val($formname.val());
          $(this).dialog("close");
        }
      },
      open : function( event, ui ) {
        $(this).find('p .text').empty().append('Перезагрузить форму?');
      }
    });
  });

  $('.button-del').on('click', function(e) {
    e.preventDefault();
    var $formname = $('[name="form-id"]'),
        $button   = $(this);
    $DIALOG.dialog({
      resizable : false,
      title     : "Внимание!",
      width     : 530,
      modal     : true,
      buttons   : {
        "Удалить" : function() {
          $button.siblings('[name="del-form"]').val($formname.val());
          $formname.closest('form').submit();
          $(this).dialog("close");
        },
        "Отмена" : function() {
          $(this).dialog("close");
        }
      },
      open : function( event, ui ) {
        $(this).find('p .text').empty().append('Удалить форму «' + $('[name="select-form"] option:selected').text() + '»?');
      }
    });
  });

  $('.button-copy').on('click', function(e) {
    e.preventDefault();
    var $formname = $('[name="form-id"]'),
        $button   = $(this);
    $DIALOG.dialog({
      resizable : false,
      title     : "Внимание!",
      width     : 530,
      modal     : true,
      buttons   : {
        "Копировать" : function() {
          $button.siblings('[name="copy-form"]').val($formname.val());
          $formname.closest('form').submit();
          $(this).dialog("close");
        },
        "Отмена" : function() {
          $(this).dialog("close");
        }
      },
      open : function( event, ui ) {
        $(this).find('p .text').empty().append('Копировать форму «' + $('[name="select-form"] option:selected').text() + '»?');
      }
    });
  });

  $('.clear').on('click',function(){
    $DIALOG.dialog({
      resizable : false,
      title     : "Внимание!",
      width     : 650,
      modal     : true,
      buttons   : {
        "Очистить" : function() {
          $(this).dialog("close");
          $( ".sortable li" ).remove();
        },
        "Отмена" : function() {
          $(this).dialog("close");
        }
      },
      open : function( event, ui ) {
        $(this).find('p .text').empty().append('Вы действительно хотите удалить все элементы и очистить конструктор?');
      }
    });
  });

  //Генерация формы
  var saveForm = function (e, btn, saveFunc) {
    var $formId      = $('[name="form-id"]'),
        $mailToEmail = $('[name="mail_to_email"]'),
        $mailSubject = $('[name="mail_subject"]'),
        $sortable    = $('#sortable ul'),
        reg          = /^[\+\.-\w]+@[-\w]+\.\w+/i,
        error        = false,
        data;
    $('#alert-form').empty();
    $formId.closest('.form-group').removeClass('has-error');
    $sortable.parent().removeClass('has-error');
    $mailToEmail.closest('.form-group').removeClass('has-error');
    $mailSubject.closest('.form-group').removeClass('has-error');

    if($formId.val().length < 3) {
      error = true;
      addAlert('#alert-form', 'Поле <b>«Символьный код»</b> заполнено не корректно!');
      $formId.closest('.form-group').addClass('has-error');
    }

    if(!$sortable.find('li').length) {
      error = true;
      addAlert('#alert-form', '<b>В форму</b> не добавлены поля!');
      $sortable.parent().addClass('has-error');
    }

    if(!reg.test($mailToEmail.val())) {
      error = true;
      if(!$panel.is(':visible')) panelSettings();
      addAlert('#alert-form', 'Поле <b>«Адреса получателей»</b> заполнено не корректно!');
      $mailToEmail.closest('.form-group').addClass('has-error');
    }

    if($mailSubject.val().length < 3) {
      error = true;
      if(!$panel.is(':visible')) panelSettings();
      addAlert('#alert-form', 'Поле <b>«Тема письма»</b> заполнено не корректно!');
      $mailSubject.closest('.form-group').addClass('has-error');
    }

    if(error)  {
      e.stopPropagation();
      btn.button('reset');
      return false;
    }

    data = {
      'formId'     : $formId.val(),
      'settings'    : $('#setting-form').serializeArray(),
      'stylesheet' : editorcss.getValue(),
    }

    data['configForm'] = [];

    $('#sortable ul li').each(function(i) {
      data['configForm'][i] = {};
      data['configForm'][i]['title'] = $(this).attr('data-edit');
      data['configForm'][i]['data-field'] = $(this).find('.data-field').text();
    });

    $.ajax({
      type     : "POST",
      url      : dsformROOT + 'index.php?route=DSConstructor&m=saveForm',
      cache    : false,
      dataType : "json",
      data     : data,
      success  : function (data) {
        if(data['error'] == 1) {
          addAlert('#alert-form', data['message']);
          return false;
        }
        if(saveFunc && typeof(saveFunc) == 'function') saveFunc();
      }
    }).always(function () {
        btn.button('reset');
    });

    return true;

  }

  $('body').on('keydown',function(e){
    if(e.keyCode == 116) {
      $DIALOG.dialog({
        resizable : false,
        title     : "Внимание!",
        width     : 430,
        modal     : true,
        buttons   : {
          "Перезагрузить" : function() {
            document.location.reload();

          },
          "Отмена" : function() {
            $(this).dialog("close");
          }
        },
        open : function( event, ui ) {
          $(this).find('p .text').empty().append('Перезагрузить форму?');
        }
      });
      e.preventDefault();
    }
    
    if(e.ctrlKey && e.keyCode == 83) {
      var btn = $('.save');
      btn.button('loading');
      saveForm(e, btn);
      e.preventDefault();
    }
  });

  $('.save').on('click',function(e){
    var btn = $(this);
    btn.button('loading');
    saveForm(e, btn, function(){
      $('[name="form-id"]').closest('form').submit();
    });
  });

  $('.generate').on('click', function (e) {
      var btn = $(this);
      btn.button('loading');
      $primer.find('div.primer').removeAttr('data-dsform-marker').empty();
      saveForm(e, btn, function(){
        $primer.find('div.primer').dsform({
          formID : $('[name="form-id"]').val(),
          modal  : false,
        });
        $primer.show();
      });
  });

  $('.getcode').on('click', function (e) {
    $MODAL.find('.modal-dialog').removeClass('modal-md').addClass('modal-lg');
    $MODAL.find('#alert').empty();
    $MODAL.find('#saveField').hide();
    $MODAL.find('.modal-title').html('Код для вставки');
    $MODAL.find('.modal-body').load(dsformROOT + 'index.php?route=DSConstructor&m=getCode',{'formId':$('[name="form-id"]').val(),'mailSubject':$('[name="mail_subject"]').val()});
    $MODAL.modal();
  });

});