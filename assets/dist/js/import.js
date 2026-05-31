$(document).ready(function(){
	   $("#input_import").validate({
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
               uri = $("#uri").val();
                   $(form).ajaxSubmit({
                          type: "post",
                          url: $(this).attr('action'),
                          data: $("#input_import").serialize(),
                        success: function(data){
                            $('#loadnya').hide();
                        if (data=='good') {
                           $.fn.run_write(uri);
                            $.fn.delete_write(uri);
                            $('.notif_top').fadeIn(1000);
                                 $(".notif_top").fadeOut(1000,function(){
                                 window.history.back();
                              });
                        } else {
                           $(".error_msg_import").html(data).show();
                            $("#error_msg_imports").show();
                        }


                      }
                    });
            }

        });

    $.fn.run_write = function(uri) {
    	$.ajax({
          url : uri+"modul/write.php",
          success : function(data) {
	            console.log(data);
          }

        });
    }

    $.fn.delete_write = function(uri) {
    	$.ajax({
          url : uri+"system/page/page_action.php?act=delete_write",
          success : function(data) {
	            console.log(data);
          }

        });
    }


});
