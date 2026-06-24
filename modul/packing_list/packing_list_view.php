<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include_once "packing_list_lib.php";
$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-m-d');
$customers = $db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$summary = pl_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo,'customer'=>'all','status'=>'all','keyword'=>''));
$canInsert = isset($role_act["insert_act"]) && $role_act["insert_act"] == "Y";
$canUpdate = isset($role_act["up_act"]) && $role_act["up_act"] == "Y";
$canDelete = isset($role_act["del_act"]) && $role_act["del_act"] == "Y";
?>
<style>
.pl-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.pl-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.pl-hero p{margin:0;opacity:.92}
.pl-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.pl-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.pl-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.pl-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.pl-filter .form-group{margin-bottom:12px}
#dtb_packing_list th,#dtb_packing_list td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.pl-action .btn{margin-right:3px}.pl-action .btn:last-child{margin-right:0}
</style>
<section class="content-header"><h1><?=sd_h('sales_packing_list', 'Packing List');?> <small>SAP SD Packing</small></h1><ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li class="active"><?=sd_h('sales_packing_list', 'Packing List');?></li></ol></section>
<section class="content">
  <div class="pl-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_packing_list', 'Packing List');?></h1><p>Membuat dan memonitor packing dari Outbound Delivery yang sudah picked.</p></div><div class="col-md-4 text-right"><?php if($canInsert){ ?><a href="<?=base_index();?>packing-list/tambah" class="btn btn-success"><i class="fa fa-plus"></i> Create Packing List</a><?php } ?></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="pl-kpi"><i class="fa fa-archive"></i><span>Total Packing</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pl-kpi"><i class="fa fa-check-circle"></i><span>Packed</span><strong><?=number_format((float)$summary->packed_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pl-kpi"><i class="fa fa-ban"></i><span><?=sd_h('sales_cancelled', 'Cancelled');?></span><strong><?=number_format((float)$summary->cancelled_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pl-kpi"><i class="fa fa-cubes"></i><span>Packed Qty</span><strong><?=number_format((float)$summary->packed_qty,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Packing List</h3></div><div class="box-body">
    <form class="form-horizontal pl-filter" onsubmit="return false;">
      <div class="form-group"><label class="control-label col-lg-2">Packing Date</label><div class="col-lg-2"><div class="input-group date pl-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date pl-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"><option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option><?php foreach($customers as $c){ ?><option value="<?=pl_h($c->kode_penerima);?>"><?=pl_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div></div>
      <div class="form-group"><label class="control-label col-lg-2"><?=sd_h('common_status', 'Status');?></label><div class="col-lg-2"><select id="filter_status" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option>CREATED</option><option>PACKED</option><option>CANCELLED</option></select></div><label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Packing/delivery/picking/SJ/customer/vehicle"></div><div class="col-lg-2"><button id="btn_filter_pl" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button></div></div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button id="btn_reset_pl" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_packing_list" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>Packing/Delivery</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=sd_h('sales_picking', 'Picking');?></th><th><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></th><th>Invoice</th><th>PO</th><th><?=sd_h('common_status', 'Status');?></th><th><?=sd_h('sales_items', 'Items');?></th><th>Packed Qty</th><th><?=sd_h('sales_vehicle', 'Vehicle');?></th></tr></thead><tbody></tbody></table></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var plCanUpdate=<?=$canUpdate?'true':'false';?>,plCanDelete=<?=$canDelete?'true':'false';?>;
function plFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function plError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_packing_list_process_failed', 'Packing List data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
 if($.fn.datepicker){$('.pl-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
 if($.fn.select2){$('#filter_customer,#filter_status').select2({width:'100%'});}
 var dt=$('#dtb_packing_list').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[10,11],className:'text-right'},{width:'42px',targets:0},{width:'168px',targets:1}],fnCreatedRow:function(nRow,aData){var id=aData[aData.length-1],status=String(aData[aData.length-2]||'');var b='<div class="pl-action"><a target="_blank" href="<?=base_url();?>modul/packing_list/download.php?id='+id+'" class="btn btn-success btn-xs" title="Download"><i class="fa fa-download"></i></a><a target="_blank" href="<?=base_url();?>modul/packing_list/print.php?id='+id+'" class="btn btn-primary btn-xs" title="<?=sd_h('common_print', 'Print');?>"><i class="fa fa-print"></i></a><a href="<?=base_index();?>packing-list/detail/'+id+'" class="btn btn-info btn-xs" title="<?=sd_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></a>';if(plCanUpdate)b+='<a href="<?=base_index();?>packing-list/edit/'+id+'" class="btn btn-warning btn-xs" title="<?=sd_h('common_edit', 'Edit');?>"><i class="fa fa-pencil"></i></a>';if(plCanDelete&&status!=='CANCELLED')b+='<button type="button" class="btn btn-danger btn-xs btn-pl-delete" data-id="'+id+'" title="Hapus"><i class="fa fa-trash"></i></button>';b+='</div>';$('td:eq(1)',nRow).html(b);},ajax:{url:'<?=base_admin();?>modul/packing_list/packing_list_data.php',type:'post',data:function(d){$.extend(d,plFilters());},error:function(xhr){console.log(xhr.responseText);plError(<?=sd_js('sales_packing_list_load_failed', 'Packing List data failed to load.');?>);}}});
 $('#btn_filter_pl').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
 $('#btn_reset_pl').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_customer,#filter_status').val('all').trigger('change');dt.draw();});
 $(document).on('click','.btn-pl-delete',function(){var id=$(this).data('id');if(!confirm('Hapus Packing List ini?'))return;$.getJSON('<?=base_admin();?>modul/packing_list/packing_list_action.php?act=delete&id='+id,function(r){var ok=false;$.each(r||[],function(_,x){if(x.status==='good')ok=true;if(x.status==='error')plError(x.error_message);});if(ok)dt.draw(false);}).fail(function(xhr){console.log(xhr.responseText);plError('Packing List gagal dihapus.');});});
 $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
