<?php
require_once __DIR__.'/erp_master_config.php';
$masterUrl = uri_segment(1);
$masterConfig = erp_master_config($masterUrl);
if (!$masterConfig) {
    echo '<section class="content"><div class="alert alert-danger">'.htmlspecialchars(erp_t('master_config_not_found', 'Master data module configuration was not found.'), ENT_QUOTES, 'UTF-8').'</div></section>';
    return;
}
$rowsResult = $db->query(
    'select * from '.$masterConfig['table'].' order by '.$masterConfig['order'].' asc limit 1000'
);
$rows = array();
foreach ($rowsResult as $rowItem) {
    $rows[] = $rowItem;
}
$totalRows = count($rows);
$statusRows = 0;
if (isset($masterConfig['fields']['status'])) {
    foreach ($rows as $rowItem) {
        $statusValue = isset($rowItem->status) ? strtoupper((string) $rowItem->status) : '';
        if ($statusValue === 'AKTIF' || $statusValue === 'ACTIVE' || $statusValue === 'OPEN') {
            $statusRows++;
        }
    }
}
$fieldOptions = array();
foreach ($masterConfig['fields'] as $fieldName => $fieldSettings) {
    if (isset($fieldSettings['type']) && $fieldSettings['type'] === 'db_select') {
        $fieldOptions[$fieldName] = array();
        $optionRows = $db->query(
            'select '.$fieldSettings['source_value'].' as option_value, '.$fieldSettings['source_label'].' as option_label'.
            ' from '.$fieldSettings['source_table'].' order by '.$fieldSettings['source_order'].' asc'
        );
        foreach ($optionRows as $optionRow) {
            $fieldOptions[$fieldName][(string) $optionRow->option_value] = $optionRow->option_label;
        }
    }
}

function erp_master_display_value($field, $value, $settings, $fieldOptions)
{
    if (isset($settings['type']) && $settings['type'] === 'db_select' && isset($fieldOptions[$field][(string) $value])) {
        return $fieldOptions[$field][(string) $value];
    }
    if (isset($settings['type']) && $settings['type'] === 'select' && isset($settings['options'][(string) $value])) {
        return $settings['options'][(string) $value];
    }
    return (string) $value;
}
?>
<style>
  .erp-master-hero {
    border-radius: 10px;
    background: linear-gradient(135deg, #1f4f8f 0%, #2d9cdb 100%);
    color: #fff;
    padding: 18px 22px;
    margin-bottom: 15px;
    box-shadow: 0 8px 22px rgba(31,79,143,.16);
  }
  .erp-master-hero h3 { margin: 0 0 6px; font-weight: 600; }
  .erp-master-hero p { margin: 0; opacity: .9; }
  .erp-master-hero-actions { display: flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap; }
  .erp-master-hero-actions .btn { border: 0; border-radius: 8px; font-weight: 700; box-shadow: 0 8px 18px rgba(15,23,42,.12); }
  .erp-master-kpi .small-box { border-radius: 8px; }
  .erp-master-filter .form-group { margin-bottom: 10px; }
  .erp-master-actions { white-space: nowrap; min-width: 125px; }
  .erp-master-actions .btn { margin-right: 3px; }
  #erp_master_table td { vertical-align: middle; }
  .erp-master-detail-table th { width: 34%; background: #f7f9fb; }
  @media(max-width:991px){ .erp-master-hero-actions { justify-content: flex-start; margin-top: 12px; } }
</style>
<section class="content-header">
  <h1><?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?> <small><?=htmlspecialchars(erp_master_config_text($masterConfig, 'code'), ENT_QUOTES, 'UTF-8');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=htmlspecialchars(erp_t('common_home', 'Home'), ENT_QUOTES, 'UTF-8');?></a></li>
    <li class="active"><?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?></li>
  </ol>
</section>

<section class="content">
  <div id="erp_master_alert" class="alert" style="display:none"></div>
  <div class="erp-master-hero">
    <div class="row">
      <div class="col-md-8">
        <h3><i class="fa fa-database"></i> <?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h3>
        <p><?=htmlspecialchars(erp_master_config_text($masterConfig, 'code'), ENT_QUOTES, 'UTF-8');?> - <?=htmlspecialchars(erp_t('master_reference_intro', 'this master data is used as ERP transaction, validation, and reporting reference.'), ENT_QUOTES, 'UTF-8');?></p>
      </div>
      <div class="col-md-4 erp-master-hero-actions">
        <?php if (isset($role_act['insert_act']) && $role_act['insert_act'] === 'Y') { ?>
          <button type="button" id="add_erp_master" class="btn btn-warning"><i class="fa fa-plus"></i> <?=htmlspecialchars(erp_t('common_add_new', 'Add New'), ENT_QUOTES, 'UTF-8');?></button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row erp-master-kpi">
    <div class="col-md-4 col-sm-6">
      <div class="small-box bg-aqua">
        <div class="inner"><h3><?=number_format($totalRows, 0, ',', '.');?></h3><p><?=htmlspecialchars(erp_t('master_total_data', 'Total Data'), ENT_QUOTES, 'UTF-8');?></p></div>
        <div class="icon"><i class="fa fa-list"></i></div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="small-box bg-green">
        <div class="inner"><h3><?=isset($masterConfig['fields']['status']) ? number_format($statusRows, 0, ',', '.') : '-';?></h3><p><?=htmlspecialchars(erp_t('master_active_open', 'Active/Open'), ENT_QUOTES, 'UTF-8');?></p></div>
        <div class="icon"><i class="fa fa-check-circle"></i></div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="small-box bg-blue">
        <div class="inner"><h3><?=count($masterConfig['fields']);?></h3><p><?=htmlspecialchars(erp_t('master_field_count', 'Master Fields'), ENT_QUOTES, 'UTF-8');?></p></div>
        <div class="icon"><i class="fa fa-sliders"></i></div>
      </div>
    </div>
  </div>

  <div class="box box-default erp-master-filter">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-filter"></i> <?=htmlspecialchars(erp_t('common_filter', 'Filter'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h3>
    </div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="col-sm-3 control-label"><?=htmlspecialchars(erp_t('common_search', 'Search'), ENT_QUOTES, 'UTF-8');?></label>
              <div class="col-sm-9">
                <input type="text" id="erp_master_keyword" class="form-control" placeholder="<?=htmlspecialchars(erp_t('master_search_placeholder', 'Search code, name, status, or master data'), ENT_QUOTES, 'UTF-8');?>">
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="col-sm-4 control-label"><?=htmlspecialchars(erp_t('common_status', 'Status'), ENT_QUOTES, 'UTF-8');?></label>
              <div class="col-sm-8">
                <select id="erp_master_status_filter" class="form-control select2">
                  <option value=""><?=htmlspecialchars(erp_t('common_all', 'All'), ENT_QUOTES, 'UTF-8');?></option>
                  <option value="Aktif"><?=htmlspecialchars(erp_master_text('Aktif'), ENT_QUOTES, 'UTF-8');?></option>
                  <option value="Nonaktif"><?=htmlspecialchars(erp_master_text('Nonaktif'), ENT_QUOTES, 'UTF-8');?></option>
                  <option value="OPEN">OPEN</option>
                  <option value="CLOSED">CLOSED</option>
                </select>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <button type="button" id="erp_master_reset_filter" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> <?=htmlspecialchars(erp_t('common_reset', 'Reset'), ENT_QUOTES, 'UTF-8');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><?=htmlspecialchars(erp_t('master_list_title', 'List'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h3>
    </div>
    <div class="box-body table-responsive">
      <table id="erp_master_table" class="table table-bordered table-striped table-hover">
        <thead><tr><th style="width:40px"><?=htmlspecialchars(erp_t('common_no', 'No'), ENT_QUOTES, 'UTF-8');?></th>
          <?php foreach ($masterConfig['list'] as $field) { ?><th><?=htmlspecialchars(erp_master_field_label($masterConfig['fields'][$field]), ENT_QUOTES, 'UTF-8');?></th><?php } ?>
          <th style="width:110px"><?=htmlspecialchars(erp_t('common_action', 'Action'), ENT_QUOTES, 'UTF-8');?></th>
        </tr></thead>
        <tbody>
        <?php $number = 1; foreach ($rows as $row) { ?>
          <tr id="master_row_<?=$row->{$masterConfig['primary']};?>">
            <td><?=$number++;?></td>
            <?php foreach ($masterConfig['list'] as $field) { ?><td><?=htmlspecialchars(erp_master_display_value($field, $row->{$field}, $masterConfig['fields'][$field], $fieldOptions), ENT_QUOTES, 'UTF-8');?></td><?php } ?>
            <td>
              <div class="erp-master-actions">
                <button type="button" class="btn btn-info btn-xs detail-erp-master" data-record='<?=htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'><i class="fa fa-eye"></i></button>
                <?php if (isset($role_act['up_act']) && $role_act['up_act'] === 'Y') { ?><button type="button" class="btn btn-primary btn-xs edit-erp-master" data-record='<?=htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'><i class="fa fa-pencil"></i></button><?php } ?>
                <?php if (isset($role_act['del_act']) && $role_act['del_act'] === 'Y') { ?><button type="button" class="btn btn-danger btn-xs delete-erp-master" data-id="<?=$row->{$masterConfig['primary']};?>"><i class="fa fa-trash"></i></button><?php } ?>
              </div>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<div class="modal fade" id="erp_master_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form id="erp_master_form" method="post" action="<?=base_admin();?>modul/erp_master/erp_master_action.php">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=htmlspecialchars(erp_t('module_master_data', 'Master Data'), ENT_QUOTES, 'UTF-8');?></h4></div>
      <div class="modal-body">
        <input type="hidden" name="menu_url" value="<?=htmlspecialchars($masterUrl, ENT_QUOTES, 'UTF-8');?>">
        <input type="hidden" name="record_id" id="record_id" value="">
        <?php foreach ($masterConfig['fields'] as $field => $settings) { ?>
          <div class="form-group">
            <label for="field_<?=$field;?>"><?=htmlspecialchars(erp_master_field_label($settings), ENT_QUOTES, 'UTF-8');?><?php if (!empty($settings['required'])) { ?> <span class="text-red">*</span><?php } ?></label>
            <?php if (isset($settings['type']) && ($settings['type'] === 'select' || $settings['type'] === 'db_select')) { ?>
              <select id="field_<?=$field;?>" name="<?=$field;?>" class="form-control select2 erp-master-select" <?=!empty($settings['required']) ? 'required' : '';?>>
                <option value=""><?=htmlspecialchars(erp_t('select2_placeholder', 'Select data'), ENT_QUOTES, 'UTF-8');?></option><?php $options = $settings['type'] === 'db_select' ? $fieldOptions[$field] : $settings['options']; foreach ($options as $value => $label) { ?><option value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($settings['type'] === 'select' ? erp_master_text($label) : $label, ENT_QUOTES, 'UTF-8');?></option><?php } ?>
              </select>
            <?php } else { ?>
              <input id="field_<?=$field;?>" name="<?=$field;?>" type="<?=isset($settings['type']) ? $settings['type'] : 'text';?>" class="form-control" <?=isset($settings['maxlength']) ? 'maxlength="'.intval($settings['maxlength']).'"' : '';?> <?=isset($settings['step']) ? 'step="'.htmlspecialchars($settings['step'], ENT_QUOTES, 'UTF-8').'"' : '';?> <?=!empty($settings['required']) ? 'required' : '';?>>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=htmlspecialchars(erp_t('common_cancel', 'Cancel'), ENT_QUOTES, 'UTF-8');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=htmlspecialchars(erp_t('common_save', 'Save'), ENT_QUOTES, 'UTF-8');?></button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="erp_master_detail_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <h4 class="modal-title"><i class="fa fa-eye"></i> <?=htmlspecialchars(erp_t('common_detail', 'Detail'), ENT_QUOTES, 'UTF-8');?> <?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?></h4>
    </div>
    <div class="modal-body">
      <table class="table table-bordered table-condensed erp-master-detail-table">
        <tbody id="erp_master_detail_body"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?=htmlspecialchars(erp_t('common_close', 'Close'), ENT_QUOTES, 'UTF-8');?></button>
    </div>
  </div></div>
</div>

<script>
$(function () {
  var fieldLabels = <?=json_encode(array_map(function ($settings) { return erp_master_field_label($settings); }, $masterConfig['fields']));?>;
  var table = $('#erp_master_table').DataTable({
    pageLength: 25,
    order: [[1, 'asc']],
    dom: 'Bfrtip',
    buttons: [
      {extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> '+((window.ERPKB_LANG||{}).common_export_excel || 'Export Excel'), className: 'btn btn-success btn-sm', title: '<?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?>'},
      {extend: 'print', text: '<i class="fa fa-print"></i> '+((window.ERPKB_LANG||{}).common_print || 'Print'), className: 'btn btn-default btn-sm', title: '<?=htmlspecialchars(erp_master_config_text($masterConfig, 'title'), ENT_QUOTES, 'UTF-8');?>'}
    ],
    columnDefs: [
      {targets: [0, -1], orderable: false},
      {targets: [-1], searchable: false, className: 'text-center'}
    ]
  });
  if ($.fn.select2) {
    $('.select2').select2({width: '100%'});
  }
  $('#erp_master_keyword').on('keyup change', function () {
    table.search(this.value).draw();
  });
  $('#erp_master_status_filter').on('change', function () {
    table.column().search('');
    table.search($('#erp_master_keyword').val()).draw();
    var status = this.value;
    if (status) {
      table.rows().every(function () {
        var rowText = $('<div>').html(this.node().innerHTML).text();
        $(this.node()).toggle(rowText.indexOf(status) !== -1);
      });
    } else {
      table.rows().every(function () { $(this.node()).show(); });
    }
  });
  $('#erp_master_reset_filter').on('click', function () {
    $('#erp_master_keyword').val('');
    $('#erp_master_status_filter').val('').trigger('change');
    table.search('').draw();
    table.rows().every(function () { $(this.node()).show(); });
  });
  function message(type, text) { $('#erp_master_alert').removeClass('alert-success alert-danger').addClass('alert-' + type).text(text).show(); }
  $('#add_erp_master').on('click', function () { $('#erp_master_form')[0].reset(); $('#record_id').val(''); $('.erp-master-select').val('').trigger('change'); $('#erp_master_modal .modal-title').text(((window.ERPKB_LANG||{}).common_add_new || 'Add New')+' '+((window.ERPKB_LANG||{}).module_master_data || 'Master Data')); $('#erp_master_modal').modal('show'); });
  $('.edit-erp-master').on('click', function () {
    var record = $(this).data('record'); $('#erp_master_form')[0].reset(); $('#record_id').val(record['<?=$masterConfig['primary'];?>']);
    <?php foreach ($masterConfig['fields'] as $field => $settings) { ?>$('#field_<?=$field;?>').val(record['<?=$field;?>']).trigger('change');<?php } ?>
    $('#erp_master_modal .modal-title').text(((window.ERPKB_LANG||{}).common_edit || 'Edit')+' '+((window.ERPKB_LANG||{}).module_master_data || 'Master Data')); $('#erp_master_modal').modal('show');
  });
  $('.detail-erp-master').on('click', function () {
    var record = $(this).data('record');
    var rows = '';
    $.each(fieldLabels, function (field, label) {
      var value = record[field] === null || typeof record[field] === 'undefined' ? '' : record[field];
      rows += '<tr><th>' + $('<div>').text(label).html() + '</th><td>' + $('<div>').text(value).html() + '</td></tr>';
    });
    $('#erp_master_detail_body').html(rows);
    $('#erp_master_detail_modal').modal('show');
  });
  $('#erp_master_form').on('submit', function (event) {
    event.preventDefault(); var form = $(this); var button = form.find('button[type=submit]').prop('disabled', true);
    $.post(form.attr('action'), form.serialize(), function (response) { var result = response[0] || {}; if (result.status === 'good') { location.reload(); return; } message('danger', result.error_message || ((window.ERPKB_LANG||{}).common_process_failed || 'Process failed.')); button.prop('disabled', false); $('#erp_master_modal').modal('hide'); }, 'json').fail(function () { message('danger', ((window.ERPKB_LANG||{}).validation_remote || 'Please fix this field.')); button.prop('disabled', false); });
  });
  $('.delete-erp-master').on('click', function () {
    var id = $(this).data('id'); $('#ucing').modal({keyboard:false}).one('click', '#delete', function () {
      $.post('<?=base_admin();?>modul/erp_master/erp_master_action.php', {menu_url:'<?=htmlspecialchars($masterUrl, ENT_QUOTES, 'UTF-8');?>', delete_id:id}, function (response) { var result=response[0]||{}; if(result.status==='good'){location.reload();return;} message('danger',result.error_message||((window.ERPKB_LANG||{}).common_process_failed || 'Process failed.')); }, 'json'); $('#ucing').modal('hide');
    });
  });
});
</script>
