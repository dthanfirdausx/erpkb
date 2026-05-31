<?php
session_start();
include "inc/config.php";
if (!isset($_SESSION['login'])) {
?>
<!DOCTYPE html>

<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
        <meta charset="utf-8" />
        <title>IAIN Kerinci</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        
        <!-- BEGIN GLOBAL MANDATORY STYLES -->
        <link href="assets/assets/custom/css/font.css" rel="stylesheet" type="text/css" />
        <link href="assets/assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- END GLOBAL MANDATORY STYLES -->
        <!-- BEGIN THEME GLOBAL STYLES -->
        <link href="assets/assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <!-- END THEME GLOBAL STYLES -->
        <!-- BEGIN THEME LAYOUT STYLES -->
        <link href="assets/assets/layouts/layout/css/layout.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/assets/layouts/layout/css/themes/darkblue.min.css" rel="stylesheet" type="text/css" id="style_color" />


        <link href="assets/assets/custom/css/login.css" rel="stylesheet" type="text/css" />
    <style type="text/css">.link-login:hover {text-decoration: none;}</style>
                <!-- END THEME LAYOUT STYLES -->
        <link rel="shortcut icon" href="assets/media/kdi.ico" /> 



       <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
  
 


      <script src="assets/login/js/shake.js"></script>
    <script src="assets/login/js/login.js"></script>
        </head>
    <body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-full-width">
        <!-- BEGIN HEADER -->
        <div id="xload"></div>
        <div class="page-header navbar navbar-fixed-top">
            <!-- BEGIN HEADER INNER -->
            <div class="page-header-inner ">
                <!-- BEGIN LOGO-->
                <div class="page-logo">
                    <div class="logo-default">
                        <a href="<?=base_admin();?>" class="link-login"><b style="color: #99b43f;">SIAKAD</b> KERINCI</a>
                    </div>
                 </div> 
                <div class="hor-menu " >

                    <ul class="nav navbar-nav" >

                       
                    </ul>
                </div>
                <!-- END MEGA MENU -->
             
                
            </div>
            <!-- END HEADER INNER -->
        </div>
        <!-- END HEADER -->
        <!-- BEGIN HEADER & CONTENT DIVIDER -->
        <div class="clearfix"> </div>
        <!-- END HEADER & CONTENT DIVIDER -->
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
            <!-- BEGIN SIDEBAR -->
            
            <!-- END SIDEBAR -->
            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <!-- BEGIN CONTENT BODY -->
                <div class="page-content">
                    <!-- BEGIN PAGE HEADER-->
                    <!-- BEGIN THEME PANEL -->
                    
                    <!-- END THEME PANEL -->
                    <!-- BEGIN PAGE BAR -->
                    <div class="page-bar">
                                                <div class="page-toolbar">
                            <div class="btn-group pull-right">
                                
                            </div>
                        </div>
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="row">
                         <div class="col-xs-12 col-sm-12 col-md-12">
                            <a href="<?=base_admin();?>" class="link-login">
                            <div class="logo_univ">
                                <img src="<?=base_url();?>upload/logo/<?=$db->fetch_single_row('identitas_logo','id_logo','1')->logo;?>"  class="thumb-logo">
                            </div>
                            <h3 class="page-title integrasi"> Sistem Informasi <small  class="system">Akademik</small><br>
                            <h3 class="page-title instansi" >
                                <strong>IAIN </strong> <small class="instansi-ket"> KERINCI</small>
                            </h3> 
                            </h3>
                             <h3 class="page-title sistem-informasi">
                                <small>Login Area SIMAK-Terpadu</small>
                            </h3>
                            </a>
                        </div>
                    </div>
                    <!-- END PAGE TITLE-->
                    <div class="row">

    <div class="col-md-8">
        <div class="portlet light bordered dash">
        <div class="portlet-body">
            <div class="general-item-list">
<?php
$news_detail = $db->fetch_single_row("tabel_berita","id_news",$dec->dec($_GET['detail']));
if ($news_detail) {
    ?>

                <div class="item">
                    <div class="item-head">
                        <div class="item-details">
                            <a href="" class="item-name primary-link" style="color:#c67b1f">
                                <?=$news_detail->judul;?></a>
                            
                        </div>
                        
                    </div>
                    <div class="item-body" style="color: #000;"> 
      <?=$news_detail->isi;?>
                        <div class="posted">
                            <i class="fa fa-user"></i> <?=$news_detail->created_by;?> | <i class="fa fa-calendar-check-o"></i> <?=tgl_indo($news_detail->date_created);?></div>
                    </div>
                </div>
<?php
}
?>
            </div>
        </div>
    </div>
    </div>
          
 <div class="col-md-4">
    <div class="portlet light bordered dash">
        <div class="portlet-title">
            <div class="caption">
                <i class="icon-edit font-dark"></i>
                <span class="caption-subject font-dark bold uppercase"><i class="icon-lock"></i> Masuk ke SIAKAD</span>
            </div>
            
        </div>
        <div class="portlet-body">
            <div class="row error_login" style="display: none">
                <div class="col-md-12">
            <div class="alert alert-block alert-danger">
                 <span class="invalid"></span>                   
                                        </div>
            </div>

                </div>
            <div class="row">
                
                    <div class="col-md-4 col-sm-8 col-xs-12">
                        <img src="assets/media/Login-128.png" class="icon-login">
                    </div>

                    <div class="col-sm-8">

                           <form>

                            <div class="form-group">
                               <!--  ie8, ie9 does not support html5 placeholder, so we just show field title for that -->
                                
                                <input class="form-control form-control-solid placeholder-no-fix" type="text" autocomplete="off" placeholder="Username" name="username" id="username"> </div>
                            <div class="form-group">
                                
                                <input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="Password" name="password" id="password"> 
                            </div>
                                                            <div class="form-actions">
                                <button type="submit" id="login"class="btn green uppercase">Login</button>
                            </div>
                            
                        </form>                    </div>
                
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="info_login">
                        <b style="">Informasi</b>
                        <p>
                           User yang dapat menggunakan layanan ini adalah Mahasiswa, Dosen dan Staff kampus, dan pastikan saat anda login isikan Username dan Password dengan benar
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>
 </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
            <!-- BEGIN QUICK SIDEBAR -->
            
            <!-- END QUICK SIDEBAR -->
        </div>

        <!-- END CONTAINER -->
        <!-- BEGIN FOOTER -->
         <div class="page-footer">
            <div class="page-footer-inner"> 2015 &copy; TIPD IAIN Kerinci.
            </div>
            <div class="scroll-to-top">
                <i class="icon-arrow-up"></i>
            </div>
        </div>
        <script  src="assets/assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
      
        <!-- END CORE PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
      
        <script src="assets/assets/custom/js/jquery.bootstrap.newsbox.min.js" type="text/javascript"></script>
        <script src="assets/assets/custom/js/halaman/home.js" type="text/javascript"></script>  



          <!-- END THEME LAYOUT SCRIPTS -->
    </body>

</html>


<?php
} else {
  header("location:index.php/");
}

?>