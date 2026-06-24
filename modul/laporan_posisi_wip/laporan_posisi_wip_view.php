<?php
$defaultDate = isset($tanggal) && $tanggal ? $tanggal : date('Y-m-d');
function lpw_view_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$summary = $db->fetch("SELECT
  (SELECT COUNT(DISTINCT ip.production_id) FROM erp_issue_production ip WHERE ip.status='POSTED' AND ip.posting_date<=?) order_count,
  (SELECT COUNT(DISTINCT ipd.material_code) FROM erp_issue_production ip JOIN erp_issue_production_detail ipd ON ipd.issue_id=ip.id WHERE ip.status='POSTED' AND ip.posting_date<=?) material_count,
  (SELECT COALESCE(SUM(ipt.qty),0) FROM erp_issue_production ip JOIN erp_issue_production_trace ipt ON ipt.issue_id=ip.id WHERE ip.status='POSTED' AND ip.posting_date<=?) issued_qty",
  array($defaultDate,$defaultDate,$defaultDate));
?>
<style>
.lpw-hero{background:linear-gradient(135deg,#334155,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
.lpw-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.lpw-hero p{margin:0;opacity:.92}
.lpw-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.lpw-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.lpw-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.lpw-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
.lpw-official-title{text-align:center;margin:10px 0 25px;color:#111827;font-family:"Times New Roman",serif;line-height:1.25}.lpw-official-title h3{margin:0;font-size:16px;font-weight:800;text-transform:uppercase}.lpw-official-title .subtitle{font-size:16px;font-weight:800;text-transform:uppercase}
.lpw-table-wrap{border:1px solid #d8e2ec;border-radius:10px;overflow:hidden;background:#fff}
#dtb_laporan_posisi_wip th,#dtb_laporan_posisi_wip td{font-size:12px;vertical-align:middle!important;border-color:#d8e2ec!important}
#dtb_laporan_posisi_wip thead th{background:#f8fafc;color:#1f2937;text-align:center}
#dtb_laporan_posisi_wip thead tr:first-child th{background:#ecfeff!important;font-weight:700}
#dtb_laporan_posisi_wip thead tr:nth-child(2) th{background:#fff!important;color:#475569;font-weight:600}
.lpw-number{text-align:right}.lpw-center{text-align:center}.lpw-detail-link{font-weight:700;text-decoration:underline;color:#0f766e}.select2-container{width:100%!important}
.lpw-filter .form-group{margin-bottom:12px}.lpw-help{font-size:12px;color:#64748b;margin-top:5px}
</style>
<section class="content-header">
  <h1><?=customs_h('wip_position_report','Laporan Posisi WIP');?> <small><?=customs_h('report','Customs Report');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li><li><a href="<?=base_index();?>laporan-posisi-wip"><?=customs_h('report','Customs Report');?></a></li><li class="active"><?=customs_h('wip_position_report','Laporan Posisi WIP');?></li></ol>
</section>
<section class="content">
  <div class="lpw-hero"><div class="row"><div class="col-md-8"><h1><?=customs_h('legacy_laporan_posisi_barang_dalam_proses','Laporan Posisi Barang Dalam Proses');?></h1><p>Posisi WIP dihitung dari GI to Production yang belum selesai menjadi GR from Production atau scrap sampai tanggal laporan.</p></div><div class="col-md-4 text-right"><span class="label label-info">As Of Date Report</span></div></div></div>
  <div class="row">
    <div class="col-sm-4"><div class="lpw-kpi"><i class="fa fa-industry"></i><span>Production Order Terkait</span><strong><?=number_format((float)$summary->order_count,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="lpw-kpi"><i class="fa fa-cubes"></i><span>Material Issued</span><strong><?=number_format((float)$summary->material_count,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="lpw-kpi"><i class="fa fa-balance-scale"></i><span>Total Qty Issued</span><strong><?=number_format((float)$summary->issued_qty,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_report','Filter Laporan');?></h3></div><div class="box-body"><form class="form-horizontal lpw-filter" onsubmit="return false;">
    <div class="form-group">
      <label class="control-label col-lg-2">Per Tanggal</label>
      <div class="col-lg-2"><div class="input-group date lpw-date"><input type="text" class="form-control" id="filter_tanggal" value="<?=lpw_view_h($defaultDate);?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div><div class="lpw-help">Posisi WIP sampai tanggal ini.</div></div>
      <label class="control-label col-lg-1"><?=customs_h('search','Search');?></label>
      <div class="col-lg-4"><input type="text" id="filter_keyword" class="form-control" placeholder="<?=customs_h('search_wip_placeholder','Kode/nama barang, production order, dokumen BC');?>"></div>
      <div class="col-lg-3"><button type="button" class="btn btn-primary" id="btn_filter_lpw"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button> <button type="button" class="btn btn-success" id="btn_excel_lpw"><i class="fa fa-file-excel-o"></i> <?=customs_h('excel','Excel');?></button> <button type="button" class="btn btn-default" id="btn_reset_lpw"><i class="fa fa-refresh"></i></button></div>
    </div>
  </form></div></div>
  <div class="box"><div class="box-body">
    <div class="alert alert-warning fade in error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
    <div class="lpw-official-title">
      <h3>KAWASAN BERIKAT <?=lpw_view_h(defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT'));?></h3>
      <div class="subtitle"><?=customs_h('wip_position_report_upper','LAPORAN POSISI BARANG DALAM PROSES (WIP)');?></div>
      <div class="subtitle">PERIODE: S.D. <span id="lpw_period_to"><?=lpw_view_h($defaultDate);?></span></div>
    </div>
    <div class="table-responsive lpw-table-wrap"><table id="dtb_laporan_posisi_wip" class="table table-bordered table-condensed" style="width:100%"><thead><tr><th>NO</th><th>KODE<br>BARANG</th><th>NAMA<br>BARANG</th><th><?=customs_h('uom','SAT');?></th><th>JUMLAH</th><th><?=customs_h('remarks','KETERANGAN');?></th></tr><tr><th>..(1)..</th><th>..(2)..</th><th>..(3)..</th><th>..(4)..</th><th>..(5)..</th><th>..(6)..</th></tr></thead><tbody></tbody></table></div>
  </div></div>
  <div id="modal_detail_lpw" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=customs_h('wip_position_detail','Detail Posisi WIP');?></h4></div><div class="modal-body" id="isi_detail_lpw"></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=customs_h('close','Close');?></button></div></div></div></div>
</section>
<script>
function lpwFilters(){return{tanggal:$('#filter_tanggal').val(),keyword:$('#filter_keyword').val()};}
function showLpwError(msg){$('.isi_warning_delete').text(msg||'Data laporan posisi WIP gagal dimuat.');$('.error_data_delete').fadeIn();}
$(function(){if($.fn.datepicker){$('.lpw-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
var dt=$('#dtb_laporan_posisi_wip').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:'<?=customs_h('export_data','Export Data');?>',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:0,width:'48px',orderable:false,searchable:false,className:'lpw-center'},{targets:4,className:'lpw-number'}],ajax:{url:'<?=base_admin();?>modul/laporan_posisi_wip/laporan_posisi_wip_data.php',type:'post',data:function(d){$.extend(d,lpwFilters());},error:function(xhr){console.log(xhr.responseText);showLpwError('Data laporan posisi WIP gagal dimuat.');}}});
$('#btn_filter_lpw').on('click',function(){$('#lpw_period_to').text($('#filter_tanggal').val()||'-');dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_excel_lpw').on('click',function(){window.location='<?=base_admin();?>modul/laporan_posisi_wip/laporan_posisi_wip_action.php?act=excel&'+$.param(lpwFilters());});
$('#btn_reset_lpw').on('click',function(){$('#filter_tanggal').val('<?=$defaultDate;?>');$('#filter_keyword').val('');$('#lpw_period_to').text('<?=$defaultDate;?>');dt.draw();});
$(document).on('click','.lpw-detail-link',function(){var el=$(this);$('#isi_detail_lpw').html('<div class="text-center text-muted" style="padding:25px"><i class="fa fa-spinner fa-spin"></i> <?=customs_h('loading_detail','Memuat detail...');?></div>');$('#modal_detail_lpw').modal('show');$.post('<?=base_admin();?>modul/laporan_posisi_wip/laporan_posisi_wip_action.php?act=detail',{material_code:el.data('material'),tanggal:$('#filter_tanggal').val()},function(html){$('#isi_detail_lpw').html(html);}).fail(function(xhr){$('#isi_detail_lpw').html('<div class="alert alert-danger"><?=customs_h('detail_load_failed','Detail gagal dimuat.');?><br>'+xhr.responseText+'</div>');});});$(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});});
</script>
