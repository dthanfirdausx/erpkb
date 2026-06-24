<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$moveTypes = $db->query("SELECT DISTINCT move_code FROM detail_transaksi WHERE move_code IS NOT NULL AND move_code<>'' ORDER BY move_code");
$users = $db->query("SELECT DISTINCT COALESCE(NULLIF(created_by,''),NULLIF(user,'')) AS username FROM detail_transaksi WHERE COALESCE(NULLIF(created_by,''),NULLIF(user,'')) IS NOT NULL ORDER BY username");
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$kpi = $db->fetch("SELECT COUNT(*) AS total_lines,
                          COUNT(DISTINCT no_ref) AS total_docs,
                          COALESCE(SUM(CASE WHEN direction='IN' THEN qty ELSE 0 END),0) AS qty_in,
                          COALESCE(SUM(CASE WHEN direction='OUT' THEN ABS(qty) ELSE 0 END),0) AS qty_out
                   FROM detail_transaksi
                   WHERE posting_date BETWEEN ? AND ?",
                   array('from' => $defaultFrom.' 00:00:00', 'to' => $defaultTo.' 23:59:59'));
?>
<style>
  .mdoc-hero{background:linear-gradient(135deg,#0f766e,#0ea5e9);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(14,116,144,.18)}
  .mdoc-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.mdoc-hero p{margin:0;opacity:.92}
  .mdoc-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .mdoc-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.mdoc-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}
  .mdoc-kpi i{float:right;font-size:26px;color:#0284c7;opacity:.55}.mdoc-filter .form-group{margin-bottom:12px}
  #dtb_material_document td,#dtb_material_document th{font-size:12px;vertical-align:middle}.mdoc-action-buttons{white-space:nowrap;min-width:60px}
  .mdoc-doc strong{font-size:13px}.mdoc-badge{display:inline-block;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:600}
  .mdoc-in{background:#dcfce7;color:#166534}.mdoc-out{background:#fee2e2;color:#991b1b}.mdoc-neutral{background:#e0f2fe;color:#075985}
  .select2-container{width:100%!important}.mdoc-detail-table th{width:180px;background:#f8fafc}
</style>
<section class="content-header">
  <h1>Material Documents <small>SAP MM Material Document Monitor</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>material-documents">Goods Receipt</a></li>
    <li class="active">Material Documents</li>
  </ol>
</section>
<section class="content">
  <div class="mdoc-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Material Document Workbench</h1>
        <p>Monitoring seluruh material movement: GR 101, reversal, blocked stock, release 105, GR without PO 501, dan return to vendor 122.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary">Read Only Monitor</span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="mdoc-kpi"><i class="fa fa-file-text-o"></i><span>Documents This Period</span><strong><?=number_format((float)$kpi->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="mdoc-kpi"><i class="fa fa-list"></i><span>Line Items</span><strong><?=number_format((float)$kpi->total_lines,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="mdoc-kpi"><i class="fa fa-arrow-down"></i><span>Qty In</span><strong><?=number_format((float)$kpi->qty_in,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="mdoc-kpi"><i class="fa fa-arrow-up"></i><span>Qty Out</span><strong><?=number_format((float)$kpi->qty_out,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-filter"></i> Filter Material Document</h3>
    </div>
    <div class="box-body">
      <form id="filter_mdoc" class="form-horizontal mdoc-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Posting Date</label>
          <div class="col-lg-2"><div class="input-group date mdoc-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date mdoc-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Movement</label>
          <div class="col-lg-2">
            <select id="filter_move_code" class="form-control">
              <option value="">Semua Movement</option>
              <?php foreach($moveTypes as $m){ ?><option value="<?=htmlspecialchars($m->move_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($m->move_code,ENT_QUOTES,'UTF-8');?></option><?php } ?>
            </select>
          </div>
          <label class="control-label col-lg-1">Direction</label>
          <div class="col-lg-2"><select id="filter_direction" class="form-control"><option value="">Semua</option><option value="IN">IN</option><option value="OUT">OUT</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Plant</label>
          <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value="">Semua Plant</option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-2">Storage Location</label>
          <div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value="">Semua SLoc</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1">User</label>
          <div class="col-lg-3"><select id="filter_user" class="form-control"><option value="">Semua User</option><?php foreach($users as $u){ ?><option value="<?=htmlspecialchars($u->username,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($u->username,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Search</label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Material doc / GR no / PO / material / vendor / no aju / remark"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_mdoc" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <button type="button" id="btn_reset_mdoc" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_material_document" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>No</th>
              <th>Action</th>
              <th>Material Document</th>
              <th>Posting Date</th>
              <th>Movement</th>
              <th>Source</th>
              <th>Material</th>
              <th>Plant</th>
              <th>Storage Location</th>
              <th class="text-right">Qty</th>
              <th>UOM</th>
              <th class="text-right">Amount</th>
              <th>User</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail_mdoc" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%">
      <div class="modal-content">
        <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Material Document Detail</h4></div>
        <div class="modal-body" id="isi_detail_mdoc"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showMdocError(m){$('.isi_warning_delete').text(m||'Data Material Document gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.mdoc-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_move_code,#filter_direction,#filter_plant,#filter_storage_location,#filter_user').select2({width:'100%'});}
  var dt=$('#dtb_material_document').DataTable({
    bProcessing:true,
    bServerSide:true,
    pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'Export Data',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[
      {targets:[0,1],orderable:false,searchable:false},
      {targets:[9,11],className:'text-right'},
      {width:'42px',targets:0},
      {width:'62px',targets:1}
    ],
    ajax:{
      url:'<?=base_admin();?>modul/material_document/material_document_data.php',
      type:'post',
      data:function(d){
        d.tgl_awal=$('#filter_tgl_awal').val();
        d.tgl_akhir=$('#filter_tgl_akhir').val();
        d.move_code=$('#filter_move_code').val();
        d.direction=$('#filter_direction').val();
        d.plant_id=$('#filter_plant').val();
        d.storage_location_id=$('#filter_storage_location').val();
        d.user=$('#filter_user').val();
        d.keyword=$('#filter_keyword').val();
      },
      error:function(xhr){console.log(xhr);showMdocError('Data Material Document gagal dimuat.');}
    }
  });
  $('#btn_filter_mdoc').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_mdoc').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');
    $('#filter_move_code,#filter_direction,#filter_plant,#filter_storage_location,#filter_user').val('').trigger('change');
    dt.draw();
  });
  $(document).on('click','.btn-detail-mdoc',function(){
    var id=$(this).data('id');
    $.post('<?=base_admin();?>modul/material_document/material_document_action.php?act=detail',{id:id},function(html){
      $('#isi_detail_mdoc').html(html);$('#modal_detail_mdoc').modal('show');
    }).fail(function(){showMdocError('Detail Material Document gagal dibuka.');});
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
