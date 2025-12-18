dsformROOT = '/ds-comf/ds-form/';
jQuery(function($) {
  $('#auth form').on('submit', function(e){
    e.preventDefault();
    $.ajax({
      url: dsformROOT,
      type: "POST",
      dataType: 'JSON',
      data:{
        route: "DSConstructor",
        m: 'auth',
        login: $('[name="login"]').val(),
        password: $('[name="password"]').val()
      },
      success: function(data) {
        if(data['error']) {
          $('.form-group').addClass('has-error');
          $('[type="submit"]').popover('show');
        } else {
          location.reload();
        }
      }
    });
  });
  $('#profile form').on('submit', function(e){
    e.preventDefault();
    $.ajax({
      url: dsformROOT,
      type: "POST",
      dataType: 'JSON',
      data:{
        route: "DSConstructor",
        m: 'profileEdit',
        login: $('[name="login"]').val(),
        old_password: $('[name="old_password"]').val(),
        new_password: $('[name="new_password"]').val(),
        new_password2: $('[name="new_password2"]').val()
      },
      success: function(data) {
        $('.form-group').removeClass('has-error');
        if(data['error']) {
          switch (data['error']) {
            case 1:
              $('[name="old_password"],[name="new_password"],[name="new_password2"]').parents('.form-group').addClass('has-error');
              break;
            case 6:
              $('[name="login"],[name="old_password"]').parents('.form-group').addClass('has-error');
              break;
            case 7:
              break;
            default:
              $('[name="new_password"],[name="new_password2"]').parents('.form-group').addClass('has-error');
              break;
          }
          $('[type="submit"]').attr('data-content', data['message']);
          $('[type="submit"]').popover('show');
        } else {
          window.location.href = window.location.href
        }
      }
    });
  });
});