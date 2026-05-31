function Login(IdForm){
    $(IdForm).submit(function(e) {
       $('#xload').attr('class','preloader');
        e.preventDefault();
        var field = $(this);//object dari form 
         toastr.options = {
          "closeButton": false,
          "debug": false,
          "newestOnTop": false,
          "progressBar": false,
          "positionClass": "toast-top-right",
          "preventDuplicates": false,
          "onclick": null,
          "showDuration": "300",
          "hideDuration": "1000",
          "timeOut": "5000",
          "extendedTimeOut": "1000",
          "showEasing": "swing",
          "hideEasing": "linear",
          "showMethod": "toggle",
          "hideMethod": "fadeOut"
        };
        //toastr['error']('Username dan Password Yang Anda Masukkan Salah !!', "Informasi Gagal");
        $.ajax({
            url: field.attr('action'),
            type: 'post',
            data: field.serialize(),
            dataType: 'json',
            success: function(response) {
                $("#xload").removeClass('preloader');
                if (response.success == true) {
                    //console.log(response);
                   window.location.href = base_url+response.modul+'/home';
                }else {
                    $.each(response.messages, function(key, value) {
                    	if(value !=''){
                    		toastr['error'](value, "Informasi Gagal");
                    	}
					    
					}); 
                }
            }
        });
    });
}
Login("#form_login");