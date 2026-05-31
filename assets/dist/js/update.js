$(document).ready(function(){
//trigger validation onchange
$('select').on('change', function() {
    $(this).valid();
});
$.validator.setDefaults({ ignore: ":hidden:not(select)" });

    $("#update").validate({
        errorClass: 'help-block',
        errorElement: 'span',
        highlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
        },
        errorPlacement: function(error, element) {
          if(element.hasClass('chzn-select')) {
                var id = element.attr("id");
                error.insertAfter("#"+id+"_chosen");
            }
           else if(element.attr("type") == "checkbox") {
               element.parent().parent().append( error );
            }

            else if(element.attr("type") == "radio") {
               element.parent().parent().append( error );
            }
            else{
            error.insertAfter(element);
        }
      },

            submitHandler: function(form) {
               $('#loadnya').show();
                   $(form).ajaxSubmit({
                          type:"post",
                          url: $(this).attr('action'),
                          data: $("#update").serialize(),
                        success: function(data){
                         console.log(data);
                          $('#loadnya').hide();
                              if (data=='good') {
                          $('.notif_top_up').fadeIn(2000);
                           $(".notif_top_up").fadeOut(1000,function(){
                           window.history.back();
                        });

                                //$('.sukses').html(data);
                              } else {

                                 $('.errorna').fadeIn();
                              	//redirect jika berhasil login

                              }
                      }
                    });
            }

        });


  //special untuk foto
  $("form#upfoto").validate({
        errorClass: 'help-block',
        errorElement: 'span',
        highlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
        },
            submitHandler: function(form) {
               $('#loadnya').show();
                   $(form).ajaxSubmit({
                          type:"post",
                          url: $(this).attr('action'),
                          data: $("form#upfoto").serialize(),
                        success: function(data){

                          $('#loadnya').hide();
                              if (data=='good') {
                          $('.notif_top_up').fadeIn(1000);
                              $(".notif_top_up").fadeOut(1000,function(){
                           location.reload();
                        });
                                //$('.sukses').html(data);
                              } else {

                                 $('.errorna').fadeIn();
                                //redirect jika berhasil login

                              }
                      }
                    });
            }

        });

  $("form#up_lang").validate({
        errorClass: 'help-block',
        errorElement: 'span',
        highlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
        },

            submitHandler: function(form) {
               $('#loadnya').show();
                   $(form).ajaxSubmit({
                          type: "post",
                          url: $(this).attr('action'),
                          data: $("form#up_lang").serialize(),
                       //  enctype:  'multipart/form-data'
                        success: function(data){
                  $('#loadnya').hide();
                              if (data=='error') {
                         $('.lang_exist').focus();
                         $('.lang_exist').fadeIn();
                                //$('.sukses').html(data);
                              } else {
                                $('.notif_top').fadeIn(1000);
                                 setTimeout(function () {
                                 window.location.href = $("#back_to").val(); //will redirect to your blog page (an ex: blog.html)
                              }, 2000); //will call the function after 2 secs.
                                //redirect jika berhasil login
                              }
                      }
                    });
            }

        });

 //special untuk foto
  $("form#upalb").validate({
        errorClass: 'help-block',
        errorElement: 'span',
        highlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
        },
            submitHandler: function(form) {
               $('#loadnya').show();
                   $(form).ajaxSubmit({
                          type:"post",
                          url: $(this).attr('action'),
                          data: $("form#upalb").serialize(),
                        success: function(data){

                          $('#loadnya').hide();
                              if (data=='good') {
                          $('.notif_top_up').fadeIn(1000);
                           $(".notif_top_up").fadeOut(2000);

                                //$('.sukses').html(data);
                              } else {

                                 $('.errorna').fadeIn();
                                //redirect jika berhasil login

                              }
                      }
                    });
            }

        });

    $('body').on('click', '.edit_album', function() {

      event.preventDefault();
      var currentBtn = $(this);

      $('#album-modal')
          .modal({ keyboard: false })
          .one('click', '#save', function (e) {

            $('.title_album').html($("#nama_album").val());
             $('.desc_album').html($("#desc_album").val());

            $('#album-modal').modal('hide');


          });



    });

    $('body').on('click', '.up_foto', function() {

      event.preventDefault();
      var currentBtn = $(this);

      desc = currentBtn.attr('data-desc');
      id = currentBtn.attr('data-id');

      $('#desc_foto').attr("value",desc);
      $('#id').attr("value",id);
      $('#foto-modal')
          .modal({ keyboard: false })
          .one('click', '#save', function (e) {

          $('#the_foto_'+id).attr("title",$("#desc_foto").val());

            $('#foto-modal').modal('hide');

          });


    });

	})
