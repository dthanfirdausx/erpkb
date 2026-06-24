<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$companyName = defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT');
$summary = $db->fetch("
  SELECT
    COUNT(*) AS total_rows,
    COUNT(DISTINCT no_bpb) AS total_bpb,
    COUNT(DISTINCT nama) AS total_supplier,
    COALESCE(SUM(jumlah),0) AS total_qty,
    COALESCE(SUM(nilai),0) AS total_value
  FROM vpemasukanbyjenisdokpab
  WHERE tgl_bpb BETWEEN ? AND ?
", array($defaultFrom, $defaultTo));
?>
<style>
.lpp-hero{background:linear-gradient(135deg,#0f766e,#0369a1);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.lpp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.lpp-hero p{margin:0;opacity:.92}
.lpp-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.lpp-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.lpp-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.lpp-kpi i{float:right;font-size:26px;color:#0369a1;opacity:.55}.lpp-filter .form-group{margin-bottom:12px}.select2-container{width:100%!important}
.lpp-report-title{margin:0;font-size:16px;font-weight:700;color:#1f2937}.lpp-report-subtitle{margin:3px 0 0;color:#64748b;font-size:12px}
.lpp-table-wrap{border:1px solid #d8e2ec;border-radius:10px;overflow:hidden;background:#fff}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean{margin:0!important;border-collapse:separate!important;border-spacing:0!important;width:100%!important}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean thead th{background:#f8fafc!important;color:#1f2937!important;border-color:#d8e2ec!important;border-width:0 1px 1px 0!important;text-align:center;vertical-align:middle!important;font-size:12px;font-weight:700;line-height:1.25;padding:8px 7px!important;white-space:nowrap}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean thead tr:first-child th{background:#eef6fb!important}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean thead tr:nth-child(3) th{background:#fff!important;color:#475569;font-weight:600}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean tbody td{border-color:#d8e2ec!important;border-width:0 1px 1px 0!important;color:#334155;font-size:12px;vertical-align:middle!important;padding:7px 8px!important;background:#fff}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean tbody tr:nth-child(even) td{background:#fbfdff}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean tbody tr:hover td{background:#f0f9ff!important}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean th:last-child,#dtb_laporan_pemasukan_per_jenis_dokumen_pabean td:last-child{border-right:0!important}
#dtb_laporan_pemasukan_per_jenis_dokumen_pabean tbody tr:last-child td{border-bottom:0!important}
.lpp-table-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap}.lpp-table-toolbar .text-muted{font-size:12px}
.lpp-actions .btn{border-radius:6px;font-weight:600}.lpp-number{text-align:right}.lpp-center{text-align:center}
.lpp-official-title{text-align:center;margin:2px 0 16px;color:#111827;line-height:1.4}
.lpp-official-title h3{margin:0;font-size:17px;font-weight:800;letter-spacing:.02em}
.lpp-official-title .subtitle{font-size:14px;font-weight:700;text-transform:uppercase}
.lpp-official-title .period{font-size:13px;font-weight:700}
</style>

<section class="content-header">
  <h1><?=customs_h('report','Customs Report');?> <small><?=customs_h('incoming_report','Laporan Pemasukan Barang');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
    <li><a href="<?=base_index();?>laporan-pemasukan-per-jenis-dokumen-pabean"><?=customs_h('report','Customs Report');?></a></li>
    <li class="active"><?=customs_h('incoming_report','Laporan Pemasukan');?></li>
  </ol>
</section>

<section class="content">
  <div class="lpp-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=customs_h('incoming_report_by_doc','Laporan Pemasukan Barang Per Dokumen Pabean');?></h1>
        <p>Monitoring pemasukan barang berdasarkan dokumen pabean, bukti penerimaan barang, pemasok, material, jumlah, dan nilai barang.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary"><?=customs_h('read_only_report','Read Only Customs Report');?></span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="lpp-kpi"><i class="fa fa-file-text-o"></i><span><?=customs_h('incoming_rows','Baris Pemasukan');?></span><strong><?=number_format((float)$summary->total_rows,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpp-kpi"><i class="fa fa-archive"></i><span><?=customs_h('bpb_documents','Dokumen BPB');?></span><strong><?=number_format((float)$summary->total_bpb,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpp-kpi"><i class="fa fa-truck"></i><span>Pemasok</span><strong><?=number_format((float)$summary->total_supplier,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="lpp-kpi"><i class="fa fa-cubes"></i><span>Total Qty Bulan Ini</span><strong><?=number_format((float)$summary->total_qty,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_incoming','Filter Laporan Pemasukan');?></h3>
    </div>
    <div class="box-body">
      <form class="form-horizontal lpp-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=customs_h('date','Tanggal BPB');?></label>
          <div class="col-lg-2">
            <div class="input-group date lpp-date">
              <input type="text" class="form-control" id="tgl_awal" value="<?=$defaultFrom;?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <div class="col-lg-2">
            <div class="input-group date lpp-date">
              <input type="text" class="form-control" id="tgl_akhir" value="<?=$defaultTo;?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <label class="control-label col-lg-2"><?=customs_h('document_type','Jenis Dokumen');?></label>
          <div class="col-lg-2">
            <select id="jenisbc" name="jenisbc" class="form-control">
              <option value="all"><?=customs_h('all_bc_types','Semua Jenis BC');?></option>
              <?php foreach ($db->fetch_all("jenisbcmasuk") as $isi) { ?>
                <option value="<?=htmlspecialchars($isi->jenis,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($isi->jenis,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-lg-2 lpp-actions">
            <button type="button" class="btn btn-primary" onclick="filter()"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button>
            <button type="button" class="btn btn-success" onclick="download_data()"><i class="fa fa-file-excel-o"></i> <?=customs_h('excel','Excel');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <div class="lpp-table-toolbar">
        <div>
          <h3 class="lpp-report-title"><?=customs_h('incoming_report','Laporan Pemasukan Barang');?></h3>
          <p class="lpp-report-subtitle">Susunan kolom mengikuti format pelaporan kepabeanan.</p>
        </div>
        <span class="text-muted"><i class="fa fa-info-circle"></i> Gunakan tombol Excel untuk format .xlsx resmi.</span>
      </div>
    </div>
    <div class="box-body">
      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>
      <div class="lpp-official-title">
        <h3><?=customs_h('incoming_report_by_doc','LAPORAN PEMASUKAN BARANG PER DOKUMEN PABEAN');?></h3>
        <div class="subtitle">KAWASAN BERIKAT <?=htmlspecialchars($companyName,ENT_QUOTES,'UTF-8');?></div>
        <div class="period">PERIODE : <span id="lpp_period_from"><?=$defaultFrom;?></span> SD <span id="lpp_period_to"><?=$defaultTo;?></span></div>
      </div>
      <div class="table-responsive lpp-table-wrap">
        <table id="dtb_laporan_pemasukan_per_jenis_dokumen_pabean" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th rowspan="2"><?=customs_h('no','No');?></th>
              <th rowspan="2"><?=customs_h('document_type','Jenis Dokumen');?></th>
              <th colspan="2"><?=customs_h('customs_document','Dokumen Pabean');?></th>
              <th colspan="2"><?=customs_h('receipt_proof','Bukti Penerimaan Barang');?></th>
              <th rowspan="2"><?=customs_h('supplier_sender','Pemasok/Pengirim');?></th>
              <th rowspan="2"><?=customs_h('material_code','Kode Barang');?></th>
              <th rowspan="2"><?=customs_h('material_name','Nama Barang');?></th>
              <th rowspan="2"><?=customs_h('uom','Sat');?></th>
              <th rowspan="2"><?=customs_h('qty','Jumlah');?></th>
              <th rowspan="2"><?=customs_h('goods_value','Nilai Barang');?></th>
            </tr>
            <tr>
              <th><?=customs_h('number','Nomor');?></th>
              <th><?=customs_h('date','Tanggal');?></th>
              <th><?=customs_h('number','Nomor');?></th>
              <th><?=customs_h('date','Tanggal');?></th>
            </tr>
            <tr>
              <th>(3)</th>
              <th>(4)</th>
              <th>(5)</th>
              <th>(6)</th>
              <th>(7)</th>
              <th>(8)</th>
              <th>(9)</th>
              <th>(10)</th>
              <th>(11)</th>
              <th>(12)</th>
              <th>(13)</th>
              <th>(14)</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
$(function(){
  if($.fn.datepicker){$('.lpp-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#jenisbc').select2({width:'100%',allowClear:false});}

  window.dtb_laporan_pemasukan_per_jenis_dokumen_pabean = $("#dtb_laporan_pemasukan_per_jenis_dokumen_pabean").DataTable({
    "fnCreatedRow": function(nRow) {
      $('td:eq(0),td:eq(9)', nRow).addClass('lpp-center');
      $('td:eq(10),td:eq(11)', nRow).addClass('lpp-number');
    },
    "dom": "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
    "pageLength": 25,
    "bProcessing": true,
    "bServerSide": true,
    "columnDefs": [
      {"width":"48px","targets":0,"orderable":false,"searchable":false,"className":"lpp-center"},
      {"targets":[9],"className":"lpp-center"},
      {"targets":[10,11],"className":"lpp-number"}
    ],
    "ajax":{
      url: '<?=base_admin();?>modul/laporan_pemasukan_per_jenis_dokumen_pabean/laporan_pemasukan_per_jenis_dokumen_pabean_data.php',
      data: function(d) {
        d.tgl_awal = $("#tgl_awal").val();
        d.tgl_akhir = $("#tgl_akhir").val();
        d.jenisbc = $("#jenisbc").val();
      },
      type: 'post',
      error: function(xhr) {
        console.log(xhr);
        $('.isi_warning_delete').text(<?=customs_js('incoming_report_load_failed','Data laporan pemasukan gagal dimuat.');?>);
        $('.error_data_delete').fadeIn();
      }
    }
  });

  $(document).on('click', '.hide_alert_notif', function() {
    $('.error_data_delete').hide();
  });
});

function filter() {
  updateLppPeriodTitle();
  $("#dtb_laporan_pemasukan_per_jenis_dokumen_pabean").DataTable().draw();
}

function updateLppPeriodTitle() {
  $('#lpp_period_from').text($("#tgl_awal").val() || '-');
  $('#lpp_period_to').text($("#tgl_akhir").val() || '-');
}

function download_data(){
  var tgl_awal = $("#tgl_awal").val();
  var tgl_akhir = $("#tgl_akhir").val();
  var jenisbc = $("#jenisbc").val();
  if (tgl_awal==='' && tgl_akhir==='') {
    alert(<?=customs_js('choose_start_end','Pilih Tanggal Awal dan Akhir');?>);
  } else if (tgl_awal==='' && tgl_akhir!=='') {
    alert(<?=customs_js('choose_start','Pilih Tanggal Awal');?>);
  } else if (tgl_awal!=='' && tgl_akhir==='') {
    alert(<?=customs_js('choose_end','Pilih Tanggal Akhir');?>);
  } else {
    document.location="<?= base_url() ?>modul/laporan_pemasukan_per_jenis_dokumen_pabean/down.php?tgl_awal="+encodeURIComponent(tgl_awal)+"&tgl_akhir="+encodeURIComponent(tgl_akhir)+"&jenis_dokpab="+encodeURIComponent(jenisbc);
  }
}
</script>
