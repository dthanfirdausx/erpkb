<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8"> 
    <title><?= appTittle ?></title>
    <link rel="stylesheet" href="<?=base_admin();?>assets/bootstrap/css/bootstrap.min.css">
   
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="<?=base_admin();?>assets/dist/css/AdminLTE.min.css">
     <link rel="stylesheet" href="<?=base_admin();?>assets/plugins/select2/select2.min.css">
     <link href="<?=base_admin();?>assets/plugins/chosen/chosen.min.css" rel="stylesheet" type="text/css" />
         <!-- Bootstrap 3.3.2 -->
    <link href="<?=base_admin();?>assets/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="<?=base_admin();?>assets/plugins/chosen/chosen-bootstrap.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?=base_admin();?>assets/css/modern-erp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

     <script src="<?=base_admin();?>assets/plugins/jQuery/jQuery-2.1.3.min.js"></script>
    <script src="<?=base_admin();?>assets/plugins/datatables/jquery.dataTables.js" type="text/javascript"></script>
    <script src="<?=base_admin();?>assets/plugins/datatables/dataTables.bootstrap.js" type="text/javascript"></script>
    <script src="<?=base_admin();?>assets/plugins/datatables/dataTables.tableTools.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="<?=base_admin();?>assets/plugins/datatables/extensions/TableTools/css/dataTables.tableTools.css">
    <link rel="stylesheet" type="text/css" href="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/css/buttons.dataTables.min.css">
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/dataTables.buttons.min.js">
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/buttons.flash.min.js">
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/jszip.min.js">
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/pdfmake.min.js">
    </script>
    <!-- AutoNumerci -->
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/autoNumeric/autoNumeric.js">        
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/vfs_fonts.js"> 
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/buttons.html5.min.js">
    </script>
    <script type="text/javascript" language="javascript" src="<?=base_admin();?>assets/plugins/datatables/extensions/Buttons/js/buttons.print.min.js">
    </script>
    <script src="<?=base_admin();?>assets/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min.js" type="text/javascript"></script>
    <style type="text/css"> 
      /* AUTOCOMPLETE FIX */
        .ui-autocomplete {
            position: absolute;
            z-index: 9999 !important;
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 5px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* ITEM */
        .ui-menu-item {
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        /* HOVER */
        .ui-menu-item:hover,
        .ui-state-focus {
            background: transparent;
            color: #fff;
        }

        /* REMOVE BULLET */
        .ui-menu-item-wrapper {
            display: block;
        }

        /* BIAR RAPI */
        .ui-menu {
            list-style: none;
            margin: 0;
        }
    </style>
  </head> 
  <body class="skin-blue">
    <div class="fakeloader"></div>
    <div id="loadnya" style="display:none">
      <img src="<?=base_admin();?>assets/dist/img/loadnya.gif" class="ajax-loader"/>
    </div>
    <!--notif here -->
    <div class="notif_top" style="display:none">
      <div class="alert alert-success" style="margin-left:0">
        <button class="close" data-dismiss="alert">×</button>
        <center>
        <strong>Data Berhasil di Tambahkan</strong>
        </center>
      </div>
    </div>
    <div class="notif_top_up" style="display:none">
      <div class="alert alert-success" style="margin-left:0">
        <button class="close" data-dismiss="alert">×</button>
        <center>
        <strong>Data Berhasil di Perbaharui</strong>
        </center>
      </div>
    </div>
    <div class="wrapper">
      <?php
      include "top_bar.php";
      include "left_nav.php";
      ?>
