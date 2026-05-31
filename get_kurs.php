<?php
error_reporting(0);
session_start();
include "inc/config.php";
$data = get_valuta($_POST['kode']);
   // print_r($data);
    $valuta = $data->data;
    if (!empty($valuta)) {
       echo $valuta[0]->nilaiKurs;
    }

    ?>