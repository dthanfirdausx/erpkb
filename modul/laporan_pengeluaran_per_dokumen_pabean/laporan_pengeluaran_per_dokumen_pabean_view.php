<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$jenisRows = $db->query("SELECT DISTINCT kode FROM (
  SELECT jenis_dokpab kode FROM vpengeluaranbyjenisdokpab WHERE COALESCE(jenis_dokpab,'')<>''
  UNION
  SELECT outbound_bc_type kode FROM erp_goods_issue_delivery WHERE COALESCE(outbound_bc_type,'')<>''
) x ORDER BY kode");
?>
<style>
.lpk-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.lpk-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.lpk-hero p{margin:0;opacity:.92}
.lpk-card{border-radius:12px;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05)}
.lpk-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}
#dtb_laporan_pengeluaran_per_dokumen_pabean th,#dtb_laporan_pengeluaran_per_dokumen_pabean td{font-size:12px;vertical-align:middle}
.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=customs_h('outgoing_report_by_doc','Laporan Pengeluaran Per Dokumen Pabean');?> <small><?=customs_h('customs_outgoing_report','Customs Outgoing Report');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
    <li><?=customs_h('report','Customs Report');?></li>
    <li class="active"><?=customs_h('outgoing_by_doc','Pengeluaran Per Dokumen Pabean');?></li>
  </ol>
</section>
<section class="content">
  <div class="lpk-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=customs_h('outgoing_report_by_doc','Laporan Pengeluaran Per Dokumen Pabean');?></h1>
        <p>Membaca pengeluaran posted dari Goods Issue for Delivery dan referensi historis yang masih aktif untuk kebutuhan pelaporan pabean.</p>
      </div>
      <div class="col-md-4 text-right"><span class="label label-primary">CEISA / Kawasan Berikat</span></div>
    </div>
  </div>
  <div class="box lpk-card lpk-filter">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_report','Filter Laporan');?></h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-md-2"><?=customs_h('outgoing_date','Tanggal Pengeluaran');?></label>
          <div class="col-md-2"><div class="input-group date lpk-date"><input id="tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date lpk-date"><input id="tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-2"><?=customs_h('bc_type','Jenis BC');?></label>
          <div class="col-md-2"><select id="jenisbc" class="form-control select2"><option value="all"><?=customs_h('all_bc_types','Semua Jenis BC');?></option><?php foreach($jenisRows as $j){ ?><option value="<?=htmlspecialchars($j->kode,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($j->kode,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2"><button type="button" id="btn_filter_lpk" class="btn btn-primary"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button></div>
        </div>
      </form>
    </div>
  </div>
  <div class="box lpk-card">
    <div class="box-body table-responsive">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <table id="dtb_laporan_pengeluaran_per_dokumen_pabean" class="table table-bordered table-striped" style="width:100%">
        <thead>
          <tr>
            <th><?=customs_h('no','No');?></th><th><?=customs_h('document_type_short','Jenis Dokpab');?></th><th><?=customs_h('customs_doc_no','No Dokpab');?></th><th><?=customs_h('customs_doc_date','Tgl Dokpab');?></th><th><?=customs_h('outgoing_proof','Bukti Pengeluaran');?></th><th><?=customs_h('outgoing_date_short','Tgl Pengeluaran');?></th><th><?=customs_h('buyer_receiver','Pembeli/Penerima');?></th><th><?=customs_h('material_code','Kode Barang');?></th><th><?=customs_h('material_name','Nama Barang');?></th><th><?=customs_h('uom','Sat');?></th><th><?=customs_h('qty','Jumlah');?></th><th><?=customs_h('goods_value','Nilai Barang');?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</section>
<div class="modal fade" id="modal_lpk_detail">
  <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> <?=customs_h('outgoing_trace_detail','Detail Trace Pengeluaran');?></h4></div><div class="modal-body" id="lpk_detail_body"></div></div></div>
</div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
$(function(){
  if($.fn.datepicker){$('.lpk-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2').select2({width:'100%',allowClear:true});}
  var dt=$('#dtb_laporan_pengeluaran_per_dokumen_pabean').DataTable({
    bProcessing:true,
    bServerSide:true,
    pageLength:25,
    order:[[5,'desc']],
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'<?=customs_h('export_data','Export Data');?>',buttons:['copyHtml5','excelHtml5','print']}],
    columnDefs:[{targets:[0],orderable:false,searchable:false,className:'dt-center'},{targets:[10,11],className:'text-right'}],
    ajax:{url:'<?=base_admin();?>modul/laporan_pengeluaran_per_dokumen_pabean/laporan_pengeluaran_per_dokumen_pabean_data.php',type:'post',data:function(d){d.tgl_awal=$('#tgl_awal').val();d.tgl_akhir=$('#tgl_akhir').val();d.jenisbc=$('#jenisbc').val();},error:function(xhr){console.log(xhr.responseText);$('.isi_warning_delete').text(<?=customs_js('report_load_failed','Data laporan gagal dimuat.');?>);$('.error_data_delete').fadeIn();}}
  });
  $('#btn_filter_lpk,#jenisbc').on('click change',function(){dt.draw();});
  $(document).on('click','.lpk-trace-link',function(){
    var b=$(this);
    $('#lpk_detail_body').html('<div class="table-responsive"><table class="table table-bordered table-striped">'+
      '<tr><th style="width:190px">Source Type</th><td>'+b.data('source-type')+'</td></tr>'+
      '<tr><th>Source ID</th><td>'+b.data('source-id')+'</td></tr>'+
      '<tr><th><?=customs_h('detail_id','Detail ID');?></th><td>'+b.data('source-detail-id')+'</td></tr>'+
      '<tr><th><?=customs_h('material','Material');?></th><td>'+b.data('material')+'</td></tr>'+
      '<tr><th><?=customs_h('outgoing_document','Dokumen Pengeluaran');?></th><td>'+b.data('doc')+'</td></tr>'+
      '<tr><th><?=customs_h('qty','Jumlah');?></th><td class="text-right">'+b.text()+'</td></tr>'+
      '</table></div>');
    $('#modal_lpk_detail').modal('show');
  });
  $('.hide_alert_notif').on('click',function(){$('.error_data_delete').hide();});
});
</script>
