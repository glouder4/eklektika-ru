function is_array(inputArray) {
    return inputArray && !(inputArray.propertyIsEnumerable('length')) && typeof inputArray === 'object' && typeof inputArray.length === 'number';
}

(function( $ ){
    var url = decodeURIComponent(window.location.href);
    window.history.replaceState('Object', 'Title', url);
    var GET = decodeURIComponent(window.location.search.slice(1))
        .split('&')
        .reduce(function _reduce (a,b) {
            b = b.split('=');
            if (a[b[0]]) {
                if (is_array(a[b[0]])) {
                    a[b[0]].push(b[1])
                }
                else {
                    var arr=[];
                    arr.push(a[b[0]]);
                    arr.push( b[1]);
                    a[b[0]]=arr;
                }

            } else {a[b[0]] = b[1];}
            return a;
        }, {});

    var methods = {
        defaults:{
            delimiter:'_'
        },
        reset : function( options ) {
            var setting={
                radio:true,
                checkbox:true,
                select:true,
                textarea:true,
                input_text:true
            }

            options = $.extend(setting,options);

            if (options.radio){
                $(this).find(':radio').each(function(){
                    $(this).prop('checked', false);
                });
            }

            if (options.checkbox){
                $(this).find(':checkbox').each(function(){
                    $(this).prop('checked', false);
                });
            }
            if (options.input_text){
                $(this).find('input:text').each(function(){
                    var value=$(this).attr('placeholder')||'';
                    $(this).val(value);
                });
            }
            if (options.textarea){
                $(this).find('textarea').each(function(){
                    var value=$(this).attr('placeholder')||'';
                    $(this).val(value);
                });
            }
            if (options.select){
                $(this).find('select').each(function(){
                    $(this).val( $(this).prop('defaultSelected') );
                });
            }
        },
        set_from_request : function( options) {
            $(this).find(' :input').each(function(){
                var val = $(this).val();
                var name = $(this).attr('name');

                switch($(this).prop('type')){
                    case 'text':
                        if (GET[name]){
                            $(this).val(GET[name]);
                        }
                        $(this).data('oldvalue',val);
                        break;
                    case 'radio':
                        if (GET[name]){
                            if (GET[name].indexOf(val) !== -1) $(this).prop('checked', true);
                        }
                        break;
                    case 'checkbox':
                        if (GET[name]){
                            if ($.inArray( val,GET[name].split(',') ) > -1) {
                                $(this).prop('checked', true);
                            }
                        }
                        break;
                    case 'select-one':
                        if (GET[name]){
                            $('option',this).filter(function(){return $(this).val() == GET[name];}).prop('selected',true);
                            /*
                            $(this).find('option').each(function(){
                                $(this).filter(function() {
                                    var string = '||||'+GET[name].replace('+',' ').split(methods.defaults.delimiter).join('||||')+'||||';
                                    return GET[name].indexOf('||||'+$(this).val()+'||||')!== -1;

                                }).attr('selected', true);
                            });
                            */
                        }
                        break;
                }
            });
        },
        send : function(){

        },
        onsubmit:function( options ) {
            options = $.extend({delimiter:methods.defaults.delimiter},options);

            var url=[];
            var index=0;
            var url=[];
            var minmax='';
            var minmax0='';
            var names = [];
            var form = $(this);
            $.each( form.find(':input'), function(){var myname= this.name;if( $.inArray( myname, names ) < 0 ){
                names[myname]=$(this).prop('type');
                // names[myname]=$(this).data('placeholder');
            }});

            console.log(names);
            //отключаем страну если выбран бренд

            var brand = '';
            for(var key in names){
                var el=form.find(':input[name="'+key+'"]');
                /*
                switch(names[key]){
                    case 'radio':
                        if (key == 'f9' && form.find(':input[name="'+key+'"]:checked').val() != ''){
                            brand = '1';
                        }
                        break;
                }
                */
            }
            console.log(names);

            for (var key in names){

                var el=form.find(':input[name="'+key+'"]');
                switch(names[key]){
                    case 'text':

                        if (el.val()) {
                            if( el.val().search('minmax') != -1) {
                                if (el.val().replace('minmax~','') != '' && el.val() != 'minmax~,'){
                                    //alert( el.val() );
                                    minmax = '?minmax=' + el.val().replace('minmax~','');
                                    minmax0 =el.val().replace('minmax~','');
                                }
                            }else{
                                url.push( el.val() );
                            }
                        }

                        break;
                    case 'radio':
                        var val = form.find(':input[name="'+key+'"]:checked').val();
                        //alert(brand + form.find(':input[name="'+key+'"]:checked').attr('name') );

                        //if ( brand == 1 && key == 'f7' && form.find(':input[name="'+key+'"]:checked').val() != ''){
                        //удаляем страну из фильтра
                        //}else{
                        if (val) url.push( val );
                        //}
                        break;
                    case 'checkbox':
                        var tmp = form.find(':input[name="'+key+'"]:checked').map(function () {return this.value;}).get();
                        if (index < tmp.length) url.push(tmp.join(options.delimiter));
                        break;
                    case 'select-one':
                        if (el.val())
                            url.push( el.val() );
                            // url.push('122');
                        break;
                    case 'select-multiple':
                        console.log(url);
                        console.log(el.val());
                        if (el.val()) {
                            //for (var part in el.val()) {

                                url.push(el.val().join('_'));

                           // }
                        }
                            // url.push('122');
                        break;
                }
            }
            // console.log(names);
            if(options.link){
                uri=$('.filter_submit').attr('href').split('?');
            } else {
                uri=form.attr('action').split('?');
            }
            var url_string = window.location.href; //window.location.href
            var url2 = new URL(url_string);

            url2.searchParams.delete("minmax");
            url2.searchParams.append("minmax",minmax0);
            //
            window.location.href = url2;


            // console.log(uri);
            // console.log(url2);
            //
            // // if (url[0]) url=url.join('/')+'/';
            // var a =uri[0]+url+minmax;
            // //
            // if(options.link) form.find(options.link).attr('href',URL);
            // // alert( a );

            // return;
            // window.location.href=a;


            return false;
        },
        update : function( content ) {
            // future TODO
        },

        defaulted: function(options){
            options = $.extend(methods.defaults,options);
            $(this).click(function(){
                $(this).parent('section').BForms('reset');
                options.callback();
                //console.log(options);
                //return false;
            });
        }
    };

    $.fn.BForms = function( method ) {
        if ( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Метод с именем ' +  method + ' не существует для jQuery.BForms' );
        }
    };

})( jQuery );
/*
$('.params form').BForms('reset',{radio:false});

$('#filter').BForms('set_from_request');

$('#filter .filter_submit').click(function(){
  $('#filter').BForms('onsubmit',{link:'.filter_submit'});
});
*/