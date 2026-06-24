<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$companyName = defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT');
$summary = $db->fetch("
  SELECT COUNT(*) total_rows, COUNT(DISTINCT no_pengeluaran) total_docs, COUNT(DISTINCT partner_name) total_partner,
         COALESCE(SUM(qty),0) total_qty, COALESCE(SUM(amount),0) total_amount
  FROM (
    SELECT v.no_sj no_pengeluaran,v.nama partner_name,v.jumlah qty,v.nilai amount,v.tgl_sj tgl
    FROM vpengeluaranbyjenisdokpab v
    UNION ALL
    SELECT COALESCE(NULLIF(gi.reference_surat_jalan,''),gi.gi_no) no_pengeluaran,gi.customer_name partner_name,d.qty,d.amount,gi.posting_date tgl
    FROM erp_goods_issue_delivery gi
    JOIN erp_goods_issue_delivery_detail d ON d.gi_id=gi.id
    WHERE gi.status='POSTED'
  ) x
  WHERE x.tgl BETWEEN ? AND ?
", array($defaultFrom, $defaultTo));
?>
<style>
.lpk-hero{background:linear-gradient(135deg,#7c2d12,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.lpk-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.lpk-hero p{margin:0;opacity:.92}
.lpk-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.lpk-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.lpk-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.lpk-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.lpk-filter .form-group{margin-bottom:12px}.select2-container{width:100%!important}
.lpk-report-title{margin:0;font-size:16px;font-weight:700;color:#1f2937}.lpk-report-subtitle{margin:3px 0 0;color:#64748b;font-size:12px}
.lpk-official-title{text-align:center;margin:2px 0 16px;color:#111827;line-height:1.4}.lpk-official-title h3{margin:0;font-size:16px;font-weight:800;letter-spacing:.02em}.lpk-official-title .subtitle,.lpk-official-title .period{font-size:14px;font-weight:700}
.lpk-table-wrap{border:1px solid #d8e2ec;border-radius:10px;overflow:hidden;background:#fff}
#dtb_laporan_pengeluaran_per_dokumen_pabean{margin:0!important;border-collapse:separate!important;border-spacing:0!important;width:100%!important}
#dtb_laporan_pengeluaran_per_dokumen_pabean thead th{background:#f8fafc!important;color:#1f2937!important;border-color:#d8e2ec!important;border-width:0 1px 1px 0!important;text-align:center;vertical-align:middle!important;font-size:12px;font-weight:700;line-height:1.25;padding:8px 7px!important;white-space:nowrap}
#dtb_laporan_pengeluaran_per_dokumen_pabean thead tr:first-child th{background:#fff7ed!important}
#dtb_laporan_pengeluaran_per_dokumen_pabean thead tr:nth-child(3) th{background:#fff!important;color:#475569;font-weight:600}
#dtb_laporan_pengeluaran_per_dokumen_pabean tbody td{border-color:#d8e2ec!important;border-width:0 1px 1px 0!important;color:#334155;font-size:12px;vertical-align:middle!important;padding:7px 8px!important;background:#fff}
#dtb_laporan_pengeluaran_per_dokumen_pabean tbody tr:nth-child(even) td{background:#fbfdff}
#dtb_laporan_pengeluaran_per_dokumen_pabean tbody tr:hover td{background:#fff7ed!important}
#dtb_laporan_pengeluaran_per_dokumen_pabean th:last-child,#dtb_laporan_pengeluaran_per_dokumen_pabean td:last-child{border-right:0!important}
.lpk-number{text-align:right}.lpk-center{text-align:center}.lpk-actions .btn{border-radius:6px;font-weight:600}.lpk-trace-link{font-weight:700;text-decoration:underline;color:#0f766e}
.lpk-trace-table th,.lpk-trace-table td{font-size:12px;vertical-align:middle!important}
</style>

<section class="content-header">
  <h1><?=customs_h('report','Customs Report');?> <small><?=customs_h('outgoing_report','Laporan Pengeluaran Barang');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
    <li><a href="<?=base_index();?>laporan-pengeluaran"><?=customs_h('report','Customs Report');?></a></li>
    <li class="active"><?=customs_h('outgoing_report','Laporan Pengeluaran');?></li>
  </ol>
</section>

<section class="content">
  <div class="lpk-hero"><div class="row"><div class="col-md-8"><h1><?=customs_h('outgoing_report_by_doc','Laporan Pengeluaran Barang Per Dokumen Pabean');?></h1><p>Monitoring pengeluaran barang berdasarkan dokumen pabean keluar, bukti pengeluaran, pembeli/penerima, material, jumlah, nilai, dan trace dokumen asal.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=customs_h('read_only_report','Read Only Customs Report');?></span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="lpk-kpi"><i class="fa fa-file-text-o"></i><span>Baris Pengeluaran</span><strong><?=number_format((float)$summary->total_rows,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpk-kpi"><i class="fa fa-truck"></i><span><?=customs_h('outgoing_documents','Dokumen Keluar');?></span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpk-kpi"><i class="fa fa-users"></i><span><?=customs_h('buyer_receiver','Pembeli/Penerima');?></span><strong><?=number_format((float)$summary->total_partner,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpk-kpi"><i class="fa fa-cubes"></i><span>Total Qty Bulan Ini</span><strong><?=number_format((float)$summary->total_qty,2,',','.');?></strong></div></div>
  </div>

  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_outgoing','Filter Laporan Pengeluaran');?></h3></div><div class="box-body">
    <form class="form-horizontal lpk-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2"><?=customs_h('date','Tanggal Pengeluaran');?></label>
        <div class="col-lg-2"><div class="input-group date lpk-date"><input type="text" class="form-control" id="tgl_awal" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date lpk-date"><input type="text" class="form-control" id="tgl_akhir" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-2"><?=customs_h('document_type','Jenis Dokumen');?></label>
        <div class="col-lg-2"><select id="jenisbc" class="form-control"><option value="all"><?=customs_h('all_bc_types','Semua Jenis BC');?></option><?php foreach($db->fetch_all('jenisbckeluar') as $isi){ ?><option value="<?=htmlspecialchars($isi->jenis,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($isi->jenis,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-lg-2 lpk-actions"><button type="button" class="btn btn-primary" onclick="filter()"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button> <button type="button" class="btn btn-success" onclick="download_data()"><i class="fa fa-file-excel-o"></i> <?=customs_h('excel','Excel');?></button></div>
      </div>
    </form>
  </div></div>

  <div class="box"><div class="box-header with-border"><h3 class="lpk-report-title"><?=customs_h('outgoing_report','Laporan Pengeluaran Barang');?></h3><p class="lpk-report-subtitle">Klik jumlah untuk melihat trace dokumen asal bahan baku/barang setengah jadi.</p></div><div class="box-body">
    <div class="alert alert-warning fade in error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
    <div class="lpk-official-title">
      <h3><?=customs_h('outgoing_report_by_doc','LAPORAN PENGELUARAN BARANG PER DOKUMEN PABEAN');?></h3>
      <div class="subtitle">KAWASAN BERIKAT <?=htmlspecialchars($companyName,ENT_QUOTES,'UTF-8');?></div>
      <div class="period">PERIODE : <span id="lpk_period_from"><?=$defaultFrom;?></span> SD <span id="lpk_period_to"><?=$defaultTo;?></span></div>
    </div>
    <div class="table-responsive lpk-table-wrap">
      <table id="dtb_laporan_pengeluaran_per_dokumen_pabean" class="table table-bordered table-condensed">
        <thead>
          <tr><th rowspan="2"><?=customs_h('no','No');?></th><th rowspan="2"><?=customs_h('document_type','Jenis Dokumen');?></th><th colspan="2"><?=customs_h('customs_document','Dokumen Pabean');?></th><th colspan="2">Bukti/Dokumen<br><?=customs_h('outgoing','Pengeluaran');?></th><th rowspan="2"><?=customs_h('buyer_receiver','Pembeli/Penerima');?></th><th rowspan="2"><?=customs_h('material_code','Kode Barang');?></th><th rowspan="2"><?=customs_h('material_name','Nama Barang');?></th><th rowspan="2"><?=customs_h('uom','Sat');?></th><th rowspan="2"><?=customs_h('qty','Jumlah');?></th><th rowspan="2"><?=customs_h('goods_value','Nilai Barang');?></th></tr>
          <tr><th><?=customs_h('number','Nomor');?></th><th><?=customs_h('date','Tanggal');?></th><th><?=customs_h('number','Nomor');?></th><th><?=customs_h('date','Tanggal');?></th></tr>
          <tr><th>(3)</th><th>(4)</th><th>(5)</th><th>(6)</th><th>(7)</th><th>(8)</th><th>(9)</th><th>(10)</th><th>(11)</th><th>(12)</th><th>(13)</th><th>(14)</th></tr>
        </thead><tbody></tbody>
      </table>
    </div>
  </div></div>

  <div id="modal_trace_pengeluaran" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=customs_h('trace_origin','Trace Dokumen Asal Pengeluaran');?></h4></div><div class="modal-body" id="isi_trace_pengeluaran"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=customs_h('close','Close');?></button></div></div></div></div>
</section>

<script>
$(function(){
  if($.fn.datepicker){$('.lpk-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#jenisbc').select2({width:'100%'});}
  window.dtb_laporan_pengeluaran_per_dokumen_pabean = $('#dtb_laporan_pengeluaran_per_dokumen_pabean').DataTable({
    fnCreatedRow:function(nRow){$('td:eq(0),td:eq(9)',nRow).addClass('lpk-center');$('td:eq(10),td:eq(11)',nRow).addClass('lpk-number');},
    dom:"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    lengthMenu:[[10,25,50,-1],[10,25,50,'All']],pageLength:25,bProcessing:true,bServerSide:true,
    columnDefs:[{width:'48px',targets:0,orderable:false,searchable:false,className:'lpk-center'},{targets:[9],className:'lpk-center'},{targets:[10,11],className:'lpk-number'}],
    ajax:{url:'<?=base_admin();?>modul/laporan_pengeluaran_per_dokumen_pabean/laporan_pengeluaran_per_dokumen_pabean_data.php',type:'post',data:function(d){d.tgl_awal=$('#tgl_awal').val();d.tgl_akhir=$('#tgl_akhir').val();d.jenisbc=$('#jenisbc').val();},error:function(xhr){console.log(xhr);$('.isi_warning_delete').text(<?=customs_js('outgoing_report_load_failed','Data laporan pengeluaran gagal dimuat.');?>);$('.error_data_delete').fadeIn();}}
  });
  $('#tgl_awal,#tgl_akhir').on('change keyup', updateLpkPeriodTitle);
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
  $(document).on('click','.lpk-trace-link',function(){
    var el=$(this);
    $('#isi_trace_pengeluaran').html('<div class="text-center text-muted" style="padding:25px"><i class="fa fa-spinner fa-spin"></i> <?=customs_h('trace_loading','Memuat trace dokumen asal...');?></div>');
    $('#modal_trace_pengeluaran').modal('show');
    $.post('<?=base_admin();?>modul/laporan_pengeluaran/laporan_pengeluaran_action.php?act=trace_nilai',{source_type:el.data('source-type'),source_id:el.data('source-id'),source_detail_id:el.data('source-detail-id'),material_code:el.data('material'),doc_no:el.data('doc')},function(html){$('#isi_trace_pengeluaran').html(html);}).fail(function(xhr){$('#isi_trace_pengeluaran').html('<div class="alert alert-danger"><?=customs_h('trace_failed','Trace gagal dimuat.');?><br>'+xhr.responseText+'</div>');});
  });
});
function updateLpkPeriodTitle(){ $('#lpk_period_from').text($('#tgl_awal').val()||'-'); $('#lpk_period_to').text($('#tgl_akhir').val()||'-'); }
function filter(){ updateLpkPeriodTitle(); $('#dtb_laporan_pengeluaran_per_dokumen_pabean').DataTable().draw(); }
function download_data(){var a=$('#tgl_awal').val(),b=$('#tgl_akhir').val(),c=$('#jenisbc').val(); if(a===''&&b==='')alert(<?=customs_js('choose_start_end','Pilih Tanggal Awal dan Akhir');?>); else if(a===''&&b!=='')alert(<?=customs_js('choose_start','Pilih Tanggal Awal');?>); else if(a!==''&&b==='')alert(<?=customs_js('choose_end','Pilih Tanggal Akhir');?>); else document.location='<?=base_url();?>modul/laporan_pengeluaran/down.php?tgl_awal='+encodeURIComponent(a)+'&tgl_akhir='+encodeURIComponent(b)+'&jenis_dokpab='+encodeURIComponent(c);}
</script>
