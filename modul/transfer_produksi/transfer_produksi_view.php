<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$summary = $db->fetch("SELECT COUNT(*) AS total_doc,
                              COALESCE(SUM(status='0'),0) AS open_doc,
                              COALESCE(SUM(status='1'),0) AS received_doc,
                              COALESCE(SUM(status='9'),0) AS reversed_doc
                       FROM transfer
                       WHERE dari='1'
                         AND DATE(tgl_transfer) BETWEEN ? AND ?",
                       array('from' => $defaultFrom, 'to' => $defaultTo));
?>
<style>
  .tp-hero{background:linear-gradient(135deg,#1e3a8a,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(30,58,138,.18)}
  .tp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.tp-hero p{margin:0;opacity:.92}
  .tp-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .tp-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.tp-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}
  .tp-kpi i{float:right;font-size:26px;color:#2563eb;opacity:.55}
  #dtb_transfer_produksi td,#dtb_transfer_produksi th{font-size:12px;vertical-align:middle}.tp-action-buttons{white-space:nowrap;min-width:132px}
  .tp-badge{display:inline-block;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:700}.tp-open{background:#fef3c7;color:#92400e}.tp-ok{background:#dcfce7;color:#166534}.tp-rev{background:#fee2e2;color:#991b1b}
  .select2-container{width:100%!important}.tp-detail-table th{width:175px;background:#f8fafc}.tp-items td,.tp-items th{font-size:12px;vertical-align:middle!important}
</style>
<section class="content-header">
  <h1>Transfer Posting <small>SAP MM Movement 311</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Transfer Posting</li>
  </ol>
</section>
<section class="content">
  <div class="tp-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Warehouse Transfer Posting Workbench</h1>
        <p>Posting pemindahan material dari gudang ke area tujuan dengan validasi stock layer, material document, dan reversal movement 312.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <a href="<?=base_index();?>transfer-produksi/tambah" class="btn btn-warning"><i class="fa fa-plus"></i> Create Transfer Posting</a>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="tp-kpi"><i class="fa fa-exchange"></i><span>Total Transfer</span><strong><?=number_format((float)$summary->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="tp-kpi"><i class="fa fa-clock-o"></i><span>Open</span><strong><?=number_format((float)$summary->open_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="tp-kpi"><i class="fa fa-check"></i><span>Received</span><strong><?=number_format((float)$summary->received_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="tp-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$summary->reversed_doc,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Transfer Posting</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_tp" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Posting Date</label>
          <div class="col-lg-2"><div class="input-group date tp-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date tp-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Dest. SLoc</label>
          <div class="col-lg-2"><select id="filter_destination_storage_location_id" class="form-control"><option value="">Semua</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1">Status</label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value="">Semua</option><option value="0">Open</option><option value="1">Received</option><option value="9">Reversed</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Search</label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="No transfer / RO / material / catatan / user"></div>
          <div class="col-lg-5"><button type="button" id="btn_filter_tp" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button> <button type="button" id="btn_reset_tp" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button></div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_transfer_produksi" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>No</th>
              <th>Action</th>
              <th>Transfer Doc</th>
              <th>Posting Date</th>
              <th>Movement</th>
              <th>Source</th>
              <th>Destination</th>
              <th>Reference</th>
              <th>Items</th>
              <th class="text-right">Total Qty</th>
              <th>Status</th>
              <th>Created By</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Transfer Posting Detail</h4></div><div class="modal-body" id="isi_detail"></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showTpError(m){$('.isi_warning_delete').text(m||'Transfer Posting gagal diproses.');$('.error_data_delete').fadeIn();}
function show_detail(no_spb){$.post('<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=show_detail',{no_spb:no_spb},function(html){$('#isi_detail').html(html);$('#modal_detail').modal('show');}).fail(function(){showTpError('Detail transfer gagal dibuka.');});}
function reversal(no_spb){
  Swal.fire({title:'Reversal transfer posting?',text:'Movement 312 akan dibuat dan stock gudang akan dikembalikan.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, reversal'}).then(function(result){
    if(!result.isConfirmed) return;
    $.ajax({url:'<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=reversal',type:'POST',data:{no_spb:no_spb},dataType:'json',success:function(res){
      if(res.status==='good'){Swal.fire('Success','Reversal berhasil','success');dtb_transfer_produksi.draw(false);}else{Swal.fire('Error',res.error_message||'Reversal gagal','error');}
    },error:function(xhr){Swal.fire('Error',xhr.responseText,'error');}});
  });
}
var dtb_transfer_produksi;
$(function(){
  if($.fn.datepicker){$('.tp-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_destination_storage_location_id,#filter_status').select2({width:'100%'});}
  dtb_transfer_produksi=$('#dtb_transfer_produksi').DataTable({
    bProcessing:true,bServerSide:true,pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'Export Data',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[9],className:'text-right'},{width:'42px',targets:0},{width:'132px',targets:1}],
    ajax:{url:'<?=base_admin();?>modul/transfer_produksi/transfer_produksi_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.destination_storage_location_id=$('#filter_destination_storage_location_id').val();d.status=$('#filter_status').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showTpError('Data Transfer Posting gagal dimuat.');}}
  });
  $('#btn_filter_tp').on('click',function(){dtb_transfer_produksi.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dtb_transfer_produksi.draw();});
  $('#btn_reset_tp').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_destination_storage_location_id,#filter_status').val('').trigger('change');dtb_transfer_produksi.draw();});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
