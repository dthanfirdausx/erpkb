<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$jenisDokumen = $db->query("SELECT DISTINCT jenis_dokpab FROM pengeluaran WHERE COALESCE(jenis_dokpab,'')<>'' ORDER BY jenis_dokpab");
?>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<style>
  .gid-hero{background:linear-gradient(135deg,#1e3a8a,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
  .gid-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.gid-hero p{margin:0;opacity:.92}.gid-filter .form-group{margin-bottom:12px}.select2-container{width:100%!important}
  #dtb_pengeluaran_hamparan td,#dtb_pengeluaran_hamparan th{font-size:12px;vertical-align:middle}#dtb_pengeluaran_hamparan th{white-space:nowrap}.dt-center{text-align:center!important}
  .gid-action-buttons{white-space:nowrap;min-width:110px}.gid-action-buttons .btn{margin-right:3px}.gid-badge{display:inline-block;padding:3px 7px;border-radius:10px;background:#e0f2fe;color:#075985;font-weight:700;font-size:11px}
  #modal_detail .modal-dialog{width:96%}#modal_detail .table th,#modal_detail .table td{font-size:12px;vertical-align:middle}#modal_detail .table th{background:#f8fafc;white-space:nowrap}
</style>

<section class="content-header">
  <h1>Goods Issue for Delivery <small>Outbound PGI / Customs Trace</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Goods Issue for Delivery</li>
  </ol>
</section>

<section class="content">
  <div class="gid-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Goods Issue for Delivery Workbench</h1>
        <p>Monitor pengeluaran barang ke customer/tujuan pabean. Detail menampilkan item keluar dan trace bahan baku asal melalui stock layer dan dokumen BC.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if ($role_act["insert_act"]=="Y") { ?>
          <a href="<?=base_index();?>pengeluaran-hamparan/tambah" class="btn btn-warning btn-lg"><i class="fa fa-plus"></i> Tambah GI Delivery</a>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Goods Issue</h3></div>
    <div class="box-body">
      <form class="form-horizontal gid-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Tanggal GI</label>
          <div class="col-lg-2"><input type="text" id="filter_tgl_awal" class="form-control datepicker" value="<?=$defaultFrom;?>"></div>
          <div class="col-lg-2"><input type="text" id="filter_tgl_akhir" class="form-control datepicker" value="<?=$defaultTo;?>"></div>
          <label class="control-label col-lg-2">Jenis BC</label>
          <div class="col-lg-2">
            <select id="filter_jenis_dokpab" class="form-control select2-filter">
              <option value="">Semua</option>
              <?php foreach ($jenisDokumen as $j) { ?>
                <option value="<?=htmlspecialchars($j->jenis_dokpab,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($j->jenis_dokpab,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-lg-2">
            <button type="button" id="btn_filter_gid" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <button type="button" id="btn_reset_gid" class="btn btn-default"><i class="fa fa-refresh"></i></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>
      <div class="table-responsive">
        <table id="dtb_pengeluaran_hamparan" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>No</th>
              <th>Action</th>
              <th>No SJ</th>
              <th>Tanggal GI</th>
              <th>Penerima</th>
              <th>No Invoice/Kontrak</th>
              <th>No PO</th>
              <th>Jenis Dokpab</th>
              <th>No Dokpab</th>
              <th>No Aju</th>
              <th>No Efaktur</th>
              <th>Tgl Efaktur</th>
              <th>ID</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Detail Goods Issue for Delivery</h4>
        </div>
        <div class="modal-body" id="isi_detail"></div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
var gidCanEdit = <?=($role_act["up_act"]=="Y" ? 'true' : 'false');?>;
var gidEditBase = "<?=base_index();?>pengeluaran-hamparan/edit/";
function gidError(message){$('.isi_warning_delete').text(message||'Data Goods Issue gagal dimuat.');$('.error_data_delete').fadeIn();}
function show_detail(id) {
  $('#isi_detail').html("<div class='text-center text-muted' style='padding:30px'><i class='fa fa-spinner fa-spin'></i> Memuat detail Goods Issue...</div>");
  $("#modal_detail").modal("show");
  $.ajax({
    type:'POST',
    url:'<?=base_admin();?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=show_detail',
    data:{id:id},
    success:function(data){$("#isi_detail").html(data);},
    error:function(xhr){$("#isi_detail").html("<div class='alert alert-danger'>Detail Goods Issue gagal dibuka.</div>");console.log(xhr);}
  });
}

$(function(){
  if ($.fn.select2) $('.select2-filter').select2({width:'100%',allowClear:true});
  if ($.fn.datepicker) $('.datepicker').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});

  var dtb_pengeluaran_hamparan = $("#dtb_pengeluaran_hamparan").DataTable({
    fnCreatedRow:function(nRow,aData){
      var id = aData[aData.length-1];
      var bc = aData[7] || '-';
      var editButton = gidCanEdit ? '<a href="'+gidEditBase+id+'" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>' : '';
      $('td:eq(1)',nRow).html('<div class="gid-action-buttons"><button type="button" onclick="show_detail(\''+id+'\')" class="btn btn-info btn-xs" title="Detail Trace"><i class="fa fa-eye"></i></button> '+editButton+'</div>');
      $('td:eq(7)',nRow).html('<span class="gid-badge">'+bc+'</span>');
      $(nRow).attr('id','line_'+id);
    },
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'Export Data',buttons:[
      {extend:'copyHtml5',title:'Goods Issue for Delivery',exportOptions:{columns:[0,2,3,4,5,6,7,8,9,10,11]}},
      {extend:'excelHtml5',title:'Goods Issue for Delivery',exportOptions:{columns:[0,2,3,4,5,6,7,8,9,10,11]}},
      {extend:'pdfHtml5',title:'Goods Issue for Delivery',exportOptions:{columns:[0,2,3,4,5,6,7,8,9,10,11]}}
    ]}],
    bProcessing:true,
    bServerSide:true,
    order:[[3,'desc']],
    columnDefs:[
      {targets:[0,1],orderable:false,searchable:false,className:'dt-center'},
      {targets:[12],visible:false},
      {width:'42px',targets:0},
      {width:'92px',targets:1}
    ],
    ajax:{
      url:'<?=base_admin();?>modul/pengeluaran_hamparan/pengeluaran_hamparan_data.php',
      type:'post',
      data:function(d){
        d.tgl_awal=$('#filter_tgl_awal').val();
        d.tgl_akhir=$('#filter_tgl_akhir').val();
        d.jenis_dokpab=$('#filter_jenis_dokpab').val();
      },
      error:function(xhr){console.log(xhr);gidError('Data Goods Issue for Delivery gagal dimuat.');}
    }
  });

  $('#btn_filter_gid').on('click',function(){dtb_pengeluaran_hamparan.draw();});
  $('#filter_jenis_dokpab').on('change',function(){dtb_pengeluaran_hamparan.draw();});
  $('#btn_reset_gid').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');
    $('#filter_tgl_akhir').val('<?=$defaultTo;?>');
    $('#filter_jenis_dokpab').val('').trigger('change.select2');
    dtb_pengeluaran_hamparan.draw();
  });
  $('.hide_alert_notif').on('click',function(){$('.error_data_delete').hide();});
});
</script>
