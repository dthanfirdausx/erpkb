<?php
include 'inc/config.php';
session_start();
simpan_log("Logout dari aplikasi Inventory",$_SESSION['username']);
session_destroy();

header("location:login.php");
?>