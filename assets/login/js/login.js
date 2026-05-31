$(document).ready(function(){

		$(".seePass").mousedown(function(){

	Save = $("#txtPassword").val();

	$("#txtPassword").replaceWith('<input class="m-wrap" id="txtVisible" type="text" value="'+ Save +'" placeholder="Your Password"/>');
	

});

$(".seePass").mouseup(function(){

	Save = $("#txtVisible").val();
	
	$("#txtVisible").replaceWith('<input class="m-wrap" id="txtPassword" type="password" value="'+ Save +'"  placeholder="Your Password"/>');

});

$(".seePass").mouseout(function(){

	$(this).mouseup();

});

   $("#form_login").submit(function(){ 
      $.ajax({
        type : "POST",
        url  : "inc/login.php",
        data : {
           username : $("#username").val(),
           password : $("#password").val()
        },
        success : function(data){
          //alert("sukses");
          document.location = "index.php"; 
        }
      })
      return false;
   });
    
		//kembali ke login
	$("#back").click(function(event) {
	     $('.bad').hide();
	     $('.m-input-prepend').show();
		});

  $.backstretch([
      "assets/login/img/bg_new.jpg"
    , "assets/login/img/bg_new3.jpg"
    , "assets/login/img/bg_new4.jpg"
    , "assets/login/img/bg_new5.jpg"
  ], {duration: 3000, fade: 1000});
	});