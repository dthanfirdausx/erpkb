<?php
include '../../inc/config.php';
$erpCrudUrl = 'exchange-rate';
$erpCrudFolder = 'exchange_rate';
require dirname(__DIR__).'/erp_crud/bootstrap.php';
$erpCrudRecord = $db->fetch_single_row($erpCrudConfig['table'], $erpCrudConfig['primary'], $_POST['id_data']);
if (!$erpCrudRecord) { echo '<div class="alert alert-danger">Data tidak ditemukan.</div>'; return; }
require dirname(__DIR__).'/erp_crud/form.php';
?>
