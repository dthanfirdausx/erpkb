<?php 
session_start();
include 'inc/config.php';

if (!isset($_SESSION['login'])) {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?= appTittle ?></title>
    <!-- MDB icon -->
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <!-- Google Fonts Roboto -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <!-- MDB -->
    <link rel="stylesheet" href="mdb/css/mdb.min.css" />
    <style type="text/css">
      .divider:after,
      .divider:before {
      content: "";
      flex: 1;
      height: 1px;
      background: #eee;
      }
      .h-custom {
      height: calc(100% - 73px);
      }
      @media (max-width: 450px) {
      .h-custom {
      height: 100%;
      }
      }
    </style>
  </head>
  <body>
    <!-- Start your project here-->
    <div class="container">
       <section class="vh-100">
  <div class="container-fluid h-custom">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-md-9 col-lg-6 col-xl-5">
        <img src="assets/logo_kb3.png" 
          class="img-fluid" alt="Sample image">
      </div>
      <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
        <form id="form_login">
          <div class="d-flex flex-row align-items-center justify-content-center justify-content-lg-start">
            <p class="lead fw-normal mb-0 me-3" style="width: 100%;text-align: center;"><strong><?= appTittle ?></strong><br>
            <?= namaPT ?>
            </p>
           
          </div>

          <div class="divider d-flex align-items-center my-4">
            <p class="text-center fw-bold mx-3 mb-0">Please Login</p>
          </div>
          <div class="alert alert-danger" id="gagal_login" style="display: none" role="alert" data-mdb-color="danger">
       <!--  <i class="fas fa-times-circle me-3"></i> -->
       <strong style="text-align: center">Login Gagal !!!</strong><br> Username Password tidak cocok
      </div>

          <!-- Email input -->
          <div class="form-outline mb-4">
            <input type="text" id="username" class="form-control form-control-lg"
              placeholder="Enter Username" />
            <label class="form-label" for="form3Example3">Username</label>
          </div>

          <!-- Password input -->
          <div class="form-outline mb-3">
            <input type="password" id="password" class="form-control form-control-lg"
              placeholder="Enter password" />
            <label class="form-label" for="form3Example4">Password</label>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <!-- Checkbox -->
            <div class="form-check mb-0">
              <input class="form-check-input me-2" type="checkbox" value="" id="form2Example3" />
              <label class="form-check-label" for="form2Example3">
                Remember me
              </label>
            </div>
           <!--  <a href="#!" class="text-body">Forgot password?</a> -->
          </div>

          <div class="text-center text-lg-start mt-4 pt-2">
            <button type="submit" id="btn_login" class="btn btn-primary btn-lg"
              style="padding-left: 2.5rem; padding-right: 2.5rem;">Login</button>
              
             <button class="btn btn-primary" id="btn_loading" type="button" disabled style="display: none">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
              </button>
          </div>

        </form>
      </div>
    </div>
  </div>
  
</section>

<footer class="bg-primary text-white text-center text-lg-start fixed-bottom">
    <!-- Copyright -->
    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2)">
      © <?= date("Y") ?> Copyright:
      <a class="text-white" href="https://transbyte.co.id/">Transbyte.co.id</a>
    </div>
    <!-- Copyright -->
  </footer>
     
    </div>
    <!-- End your project here-->

    <!-- MDB -->
    <script src="assets/login/js/jquery.js"></script>
    <script type="text/javascript" src="mdb/js/mdb.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript">
    $("#form_login").submit(function(){ 
        $("#btn_loading").show();
        $("#btn_login").hide();
        $.ajax({
          type : "POST",
         // dataType : "JSON", 
         // contentType: "application/json",
          url  : "inc/login_new.php", 
          data : {
             username : $("#username").val(),
             password : $("#password").val()
          },
          
          success : function(data){  
            //alert(data.status); 
            $("#btn_login").show();
            $("#btn_loading").hide();
            if (data=='0') {
               $("#gagal_login").show();
               $("#gagal_login").fadeOut(5000);
             }else{
                document.location = "index.php"; 
             }
           
           
           // document.location = "index.php"; 
          }
        })
        return false;
     });
  </script>
  </body>
</html>

<?php 
} else {
  header("location:index.php/");
}
?>