<?php
session_start();
include 'inc/config.php';
//print_r($_GET);
$db->query("update sys_users set lang=?",array($_GET['lang']));
header("Location: ".$_GET['back_url']);
?>  