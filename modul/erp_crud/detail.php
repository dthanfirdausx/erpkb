<?php
require_once __DIR__.'/bootstrap.php';
$record = $db->fetch_single_row($erpCrudConfig['table'], $erpCrudConfig['primary'], $_POST['id_data']);
if (!$record) { echo '<div class="alert alert-danger">'.htmlspecialchars(erp_t('common_not_found', 'Data not found.'), ENT_QUOTES, 'UTF-8').'</div>'; return; }
?>
<table class="table table-bordered table-striped">
  <?php foreach ($erpCrudConfig['fields'] as $field => $settings) { ?><tr><th style="width:35%"><?=htmlspecialchars(erp_master_field_label($settings), ENT_QUOTES, 'UTF-8');?></th><td><?=htmlspecialchars(erp_crud_display($db, $settings, $record->{$field}), ENT_QUOTES, 'UTF-8');?></td></tr><?php } ?>
</table>
<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=htmlspecialchars(erp_t('common_close', 'Close'), ENT_QUOTES, 'UTF-8');?></button></div>
