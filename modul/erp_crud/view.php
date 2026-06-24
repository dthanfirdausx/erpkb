<?php
require_once __DIR__.'/bootstrap.php';
$rows = $db->query('select * from '.$erpCrudConfig['table'].' order by '.$erpCrudConfig['order'].' asc limit 2000');
$rowCount = 0;
$activeCount = 0;
$statusField = '';
foreach (array('status','is_active','calendar_status','schedule_status') as $candidateStatusField) {
  if (isset($erpCrudConfig['fields'][$candidateStatusField])) {
    $statusField = $candidateStatusField;
    break;
  }
}
$rowsArray = array();
foreach ($rows as $row) {
  $rowsArray[] = $row;
  $rowCount++;
  if ($statusField !== '' && isset($row->{$statusField}) && in_array(strtoupper((string)$row->{$statusField}), array('AKTIF','ACTIVE','OPEN','RELEASED'), true)) {
    $activeCount++;
  }
}
?>
<style>
  .erp-crud-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:18px 20px;margin-bottom:16px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
  .erp-crud-hero h1{margin:0 0 6px;font-size:24px;font-weight:700}.erp-crud-hero p{margin:0;opacity:.9}
  .erp-crud-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:14px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .erp-crud-kpi span{display:block;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.04em}.erp-crud-kpi strong{display:block;font-size:24px;color:#111827;margin-top:4px}.erp-crud-kpi i{float:right;font-size:24px;color:#1d4ed8;opacity:.55}
  .erp-crud-table th,.erp-crud-table td{font-size:12px;vertical-align:middle!important}.erp-crud-action{white-space:nowrap}.erp-crud-action .btn{margin-right:3px}
  .erp-crud-filter .form-group{margin-bottom:10px}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?> <small><?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'code'), ENT_QUOTES, 'UTF-8');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=htmlspecialchars(erp_t('common_home', 'Home'), ENT_QUOTES, 'UTF-8');?></a></li><li class="active"><?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?></li></ol>
</section>
<section class="content">
  <div class="erp-crud-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_t('master_workbench', 'Workbench'), ENT_QUOTES, 'UTF-8');?></h1>
        <p><?=htmlspecialchars(erp_t('master_workbench_intro', 'SAP-style master data to keep ERP transaction, reporting, approval, and audit trail consistency.'), ENT_QUOTES, 'UTF-8');?></p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act['insert_act']) && $role_act['insert_act'] === 'Y') { ?><button class="btn btn-warning btn-lg erp-crud-add"><i class="fa fa-plus"></i> <?=htmlspecialchars(erp_t('common_add_new', 'Add New'), ENT_QUOTES, 'UTF-8');?></button><?php } ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="erp-crud-kpi"><i class="fa fa-database"></i><span><?=htmlspecialchars(erp_t('master_total_data', 'Total Data'), ENT_QUOTES, 'UTF-8');?></span><strong><?=number_format($rowCount,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="erp-crud-kpi"><i class="fa fa-check-circle"></i><span><?=htmlspecialchars(erp_t('master_active_open', 'Active/Open'), ENT_QUOTES, 'UTF-8');?></span><strong><?=($statusField!=='' ? number_format($activeCount,0,',','.') : '-');?></strong></div></div>
    <div class="col-sm-4"><div class="erp-crud-kpi"><i class="fa fa-key"></i><span><?=htmlspecialchars(erp_t('master_primary_key', 'Primary Key'), ENT_QUOTES, 'UTF-8');?></span><strong><?=htmlspecialchars($erpCrudConfig['primary'], ENT_QUOTES, 'UTF-8');?></strong></div></div>
  </div>
  <div class="alert alert-danger erp-crud-error" style="display:none"></div>
  <div class="box box-default">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=htmlspecialchars(erp_t('common_filter', 'Filter'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h3></div>
    <div class="box-body">
      <form class="form-horizontal erp-crud-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=htmlspecialchars(erp_t('common_search', 'Search'), ENT_QUOTES, 'UTF-8');?></label>
          <div class="col-lg-5"><input type="text" id="erp_crud_keyword" class="form-control" placeholder="<?=htmlspecialchars(erp_t('master_search_placeholder', 'Search code, name, status, or master data'), ENT_QUOTES, 'UTF-8');?>"></div>
          <label class="control-label col-lg-1"><?=htmlspecialchars(erp_t('common_status', 'Status'), ENT_QUOTES, 'UTF-8');?></label>
          <div class="col-lg-2">
            <select id="erp_crud_status" class="form-control erp-crud-filter-select">
              <option value=""><?=htmlspecialchars(erp_t('common_all', 'All'), ENT_QUOTES, 'UTF-8');?></option>
              <option value="Aktif"><?=htmlspecialchars(erp_master_text('Aktif'), ENT_QUOTES, 'UTF-8');?></option>
              <option value="Active">Active</option>
              <option value="Open">Open</option>
              <option value="Nonaktif"><?=htmlspecialchars(erp_master_text('Nonaktif'), ENT_QUOTES, 'UTF-8');?></option>
              <option value="Inactive">Inactive</option>
              <option value="Closed">Closed</option>
            </select>
          </div>
          <div class="col-lg-2"><button type="button" id="erp_crud_filter_btn" class="btn btn-primary"><i class="fa fa-filter"></i> <?=htmlspecialchars(erp_t('common_filter', 'Filter'), ENT_QUOTES, 'UTF-8');?></button> <button type="button" id="erp_crud_reset_btn" class="btn btn-default"><i class="fa fa-refresh"></i></button></div>
        </div>
      </form>
    </div>
  </div>
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><?=htmlspecialchars(erp_t('master_list_title', 'List'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h3>
    </div>
    <div class="box-body table-responsive">
      <table class="table table-bordered table-striped erp-crud-table">
        <thead><tr><th><?=htmlspecialchars(erp_t('common_no', 'No'), ENT_QUOTES, 'UTF-8');?></th><?php foreach ($erpCrudConfig['list'] as $field) { ?><th><?=htmlspecialchars(erp_master_field_label($erpCrudConfig['fields'][$field]), ENT_QUOTES, 'UTF-8');?></th><?php } ?><th><?=htmlspecialchars(erp_t('common_action', 'Action'), ENT_QUOTES, 'UTF-8');?></th></tr></thead>
        <tbody><?php $no=1; foreach ($rowsArray as $row) { ?><tr><td><?=$no++;?></td>
          <?php foreach ($erpCrudConfig['list'] as $field) { ?><td><?=htmlspecialchars(erp_crud_display($db, $erpCrudConfig['fields'][$field], $row->{$field}), ENT_QUOTES, 'UTF-8');?></td><?php } ?>
          <td class="erp-crud-action">
            <button class="btn btn-success btn-xs erp-crud-detail" data-id="<?=htmlspecialchars($row->{$erpCrudConfig['primary']}, ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-eye"></i></button>
            <?php if (isset($role_act['up_act']) && $role_act['up_act'] === 'Y') { ?><button class="btn btn-primary btn-xs erp-crud-edit" data-id="<?=htmlspecialchars($row->{$erpCrudConfig['primary']}, ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-pencil"></i></button><?php } ?>
            <?php if (isset($role_act['del_act']) && $role_act['del_act'] === 'Y') { ?><button class="btn btn-danger btn-xs erp-crud-delete" data-id="<?=htmlspecialchars($row->{$erpCrudConfig['primary']}, ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-trash"></i></button><?php } ?>
          </td></tr><?php } ?></tbody>
      </table>
    </div>
  </div>
</section>
<div class="modal fade" id="erp_crud_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"></h4></div><div class="modal-body"></div></div></div></div>
<script>
$(function () {
  if ($.fn.select2) $('.erp-crud-filter-select').select2({width:'100%',allowClear:true});
  var erpCrudStatusColumn = <?=($statusField!=='' && in_array($statusField, $erpCrudConfig['list'], true) ? (array_search($statusField, $erpCrudConfig['list'], true) + 1) : 'null');?>;
  var erpCrudTable = $('.erp-crud-table').DataTable({
    pageLength:25,
    order:[[1,'asc']],
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:(window.ERPKB_LANG && ERPKB_LANG.common_export_data) || 'Export Data',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}]
  });
  function applyCrudFilter(){
    erpCrudTable.search($('#erp_crud_keyword').val() || '');
    if (erpCrudStatusColumn !== null) erpCrudTable.column(erpCrudStatusColumn).search($('#erp_crud_status').val() || '');
    erpCrudTable.draw();
  }
  $('#erp_crud_filter_btn').on('click', applyCrudFilter);
  $('#erp_crud_keyword').on('keyup', function(e){ if(e.keyCode===13) applyCrudFilter(); });
  $('#erp_crud_status').on('change', applyCrudFilter);
  $('#erp_crud_reset_btn').on('click', function(){ $('#erp_crud_keyword').val(''); $('#erp_crud_status').val('').trigger('change.select2'); erpCrudTable.search(''); if (erpCrudStatusColumn !== null) erpCrudTable.column(erpCrudStatusColumn).search(''); erpCrudTable.draw(); });
  function openCrud(title, endpoint, data) {
    $('#erp_crud_modal .modal-title').text(title); $('#erp_crud_modal .modal-body').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> '+(((window.ERPKB_LANG||{}).common_loading)||'Loading...')+'</p>'); $('#erp_crud_modal').modal({backdrop:'static',keyboard:false});
    $.post('<?=base_admin();?>modul/<?=$erpCrudFolder;?>/' + endpoint, data || {}, function (html) { $('#erp_crud_modal .modal-body').html(html); });
  }
  $('.erp-crud-add').click(function(){ openCrud(((window.ERPKB_LANG||{}).common_add_new || 'Add New')+' <?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?>','<?=$erpCrudFolder;?>_add.php'); });
  $('.erp-crud-edit').click(function(){ openCrud(((window.ERPKB_LANG||{}).common_edit || 'Edit')+' <?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?>','<?=$erpCrudFolder;?>_edit.php',{id_data:$(this).data('id')}); });
  $('.erp-crud-detail').click(function(){ openCrud(((window.ERPKB_LANG||{}).common_detail || 'Detail')+' <?=htmlspecialchars(erp_master_config_text($erpCrudConfig, 'title'), ENT_QUOTES, 'UTF-8');?>','<?=$erpCrudFolder;?>_detail.php',{id_data:$(this).data('id')}); });
  $('.erp-crud-delete').click(function(){ var id=$(this).data('id'); $('#ucing').modal({keyboard:false}).one('click','#delete',function(){ $.post('<?=base_admin();?>modul/<?=$erpCrudFolder;?>/<?=$erpCrudFolder;?>_action.php',{delete_id:id},function(response){var result=response[0]||{};if(result.status==='good'){location.reload();return;}$('.erp-crud-error').text(result.error_message||((window.ERPKB_LANG||{}).common_process_failed || 'Process failed.')).show();},'json');$('#ucing').modal('hide'); }); });
});
</script>
