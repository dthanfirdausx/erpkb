<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "goods_issue_report_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$moveTypes = iterator_to_array($db->query("SELECT DISTINCT move_code FROM detail_transaksi WHERE ".gir_allowed_move_sql()." ORDER BY move_code"));
$refTypes = iterator_to_array($db->query("SELECT DISTINCT ref_type FROM detail_transaksi WHERE ".gir_allowed_move_sql()." AND ref_type IS NOT NULL AND ref_type<>'' ORDER BY ref_type"));
$users = iterator_to_array($db->query("SELECT DISTINCT COALESCE(NULLIF(created_by,''),NULLIF(user,'')) AS username FROM detail_transaksi WHERE COALESCE(NULLIF(created_by,''),NULLIF(user,'')) IS NOT NULL ORDER BY username"));
$plants = iterator_to_array($db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code"));
$storageLocations = iterator_to_array($db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code"));
$storageBins = iterator_to_array($db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code"));
$summary = gir_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo));
?>
<style>
.gir-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.gir-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.gir-hero p{margin:0;opacity:.92}
.gir-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.gir-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.gir-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.gir-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.gir-filter .form-group{margin-bottom:12px}
#dtb_goods_issue_report th,#dtb_goods_issue_report td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.gir-help{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Goods Issue Report <small>SAP MM Issue Analysis</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>material-movement-report"><?=wh_h(wh_t('warehouse_reports', 'Warehouse Reports'));?></a></li><li class="active">Goods Issue Report</li></ol>
</section>
<section class="content">
  <div class="gir-hero"><div class="row"><div class="col-md-8"><h1>Goods Issue Report</h1><p>Laporan pengeluaran barang SAP-style: issue to production, cost center, asset, scrap, sample, return to vendor, other goods issue, dan physical inventory adjustment dengan trace lokasi serta dokumen pabean.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_read_only_report', 'Read Only Report'));?></span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="gir-kpi"><i class="fa fa-file-text-o"></i><span>GI Lines</span><strong><?=number_format((float)$summary->total_lines,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gir-kpi"><i class="fa fa-folder-open"></i><span>GI Documents</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gir-kpi"><i class="fa fa-arrow-up"></i><span>Issued Qty</span><strong><?=number_format((float)$summary->qty_out,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gir-kpi"><i class="fa fa-undo"></i><span>Return / Gain Qty</span><strong><?=number_format((float)$summary->qty_in,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Goods Issue</h3></div><div class="box-body">
    <form class="form-horizontal gir-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><div class="col-lg-2"><div class="input-group date gir-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date gir-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-5"><select id="filter_material" class="form-control"></select><div class="gir-help">Kosongkan untuk semua material.</div></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=gir_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=gir_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=gir_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">GI Type</label><div class="col-lg-2"><select id="filter_move_code" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($moveTypes as $m){ ?><option value="<?=gir_h($m->move_code);?>"><?=gir_h($m->move_code.' - '.gir_movement_label($m->move_code,'','OUT'));?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_direction', 'Direction'));?></label><div class="col-lg-2"><select id="filter_direction" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="OUT">Issue</option><option value="IN">Return / Gain</option></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-3"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_reference_type', 'Reference Type'));?></label><div class="col-lg-2"><select id="filter_ref_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($refTypes as $r){ ?><option value="<?=gir_h($r->ref_type);?>"><?=gir_h($r->ref_type);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_user', 'User'));?></label><div class="col-lg-2"><select id="filter_user" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($users as $u){ ?><option value="<?=gir_h($u->username);?>"><?=gir_h($u->username);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_doc_material_bc', 'Doc/material/BC/PO/vendor/remark'));?>"></div>
      </div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_gir" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_gir" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_gir" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_export_excel', 'Export Excel'));?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_goods_issue_report" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>GI Document</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>GI Type</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th><?=wh_h(wh_t('warehouse_customs', 'Customs'));?></th><th class="text-right">Return/Gain</th><th class="text-right">Issued</th><th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></th><th><?=wh_h(wh_t('warehouse_user', 'User'));?></th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_detail_gir" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Goods Issue Detail</h4></div><div class="modal-body" id="isi_detail_gir"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showGirError(m){$('.isi_warning_delete').text(m||'Data Goods Issue gagal diproses.');$('.error_data_delete').fadeIn();}
function girFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),move_code:$('#filter_move_code').val(),ref_type:$('#filter_ref_type').val(),direction:$('#filter_direction').val(),user:$('#filter_user').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.gir-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_move_code,#filter_ref_type,#filter_direction,#filter_user').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/goods_issue_report/goods_issue_report_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_goods_issue_report').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9,10],className:'text-right'},{width:'42px',targets:0},{width:'62px',targets:1}],ajax:{url:'<?=base_admin();?>modul/goods_issue_report/goods_issue_report_data.php',type:'post',data:function(d){$.extend(d,girFilters());},error:function(xhr){console.log(xhr);showGirError('Data Goods Issue Report gagal dimuat.');}}});
  $('#btn_filter_gir').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_gir').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_move_code,#filter_ref_type,#filter_direction,#filter_user').val('').trigger('change');dt.draw();});
  $('#btn_excel_gir').on('click',function(){window.location='<?=base_admin();?>modul/goods_issue_report/goods_issue_report_action.php?act=excel&'+$.param(girFilters());});
  $(document).on('click','.btn-gir-detail',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/goods_issue_report/goods_issue_report_action.php?act=detail',{id:id},function(html){$('#isi_detail_gir').html(html);$('#modal_detail_gir').modal('show');}).fail(function(){showGirError('Detail goods issue gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
