<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$docTypes = $db->query("SELECT DISTINCT jenis_dokpab FROM pemasukan WHERE jenis_dokpab IS NOT NULL AND jenis_dokpab<>'' ORDER BY jenis_dokpab");
$vendors = $db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama");
$kpi = $db->fetch("SELECT COUNT(*) AS total_gr,
                          COALESCE(SUM(CASE WHEN COALESCE(no_aju,'')<>'' AND COALESCE(no_dokpab,'')<>'' THEN 1 ELSE 0 END),0) AS complete_header,
                          COALESCE(SUM(CASE WHEN COALESCE(no_aju,'')='' OR COALESCE(no_dokpab,'')='' OR COALESCE(jenis_dokpab,'')='' THEN 1 ELSE 0 END),0) AS incomplete_header,
                          COALESCE(SUM(CASE WHEN status='REVERSED' OR is_reversal='Y' THEN 1 ELSE 0 END),0) AS reversed_doc
                   FROM pemasukan
                   WHERE COALESCE(posting_date,tgl_bpb) BETWEEN ? AND ?",
                   array('from' => $defaultFrom, 'to' => $defaultTo));
$importSummary = $db->fetch("SELECT COUNT(*) AS import_rows, COUNT(DISTINCT no_bpb) AS import_docs, COUNT(DISTINCT no_aju) AS import_aju FROM import_pemasukan_temp");
?>
<style>
  .crm-hero{background:linear-gradient(135deg,#4c1d95,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(76,29,149,.18)}
  .crm-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.crm-hero p{margin:0;opacity:.92}
  .crm-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .crm-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.crm-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}
  .crm-kpi i{float:right;font-size:26px;color:#6d28d9;opacity:.55}.crm-filter .form-group{margin-bottom:12px}
  #dtb_customs_receiving_monitor td,#dtb_customs_receiving_monitor th{font-size:12px;vertical-align:middle}.crm-action-buttons{white-space:nowrap;min-width:60px}
  .crm-badge{display:inline-block;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:700}
  .crm-ok{background:#dcfce7;color:#166534}.crm-warn{background:#fef3c7;color:#92400e}.crm-danger{background:#fee2e2;color:#991b1b}.crm-info{background:#dbeafe;color:#1e40af}
  .select2-container{width:100%!important}.crm-detail-table th{width:185px;background:#f8fafc}.crm-items td,.crm-items th{font-size:12px;vertical-align:middle!important}
</style>
<section class="content-header">
  <h1>Customs Receiving Monitor <small>SAP GTS / MM Receiving Reconciliation</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
    <li><a href="<?=base_index();?>customs-receiving-monitor">Goods Receipt</a></li>
    <li class="active">Customs Receiving Monitor</li>
  </ol>
</section>
<section class="content">
  <div class="crm-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Customs Receiving Workbench</h1>
        <p>Monitoring keterkaitan Goods Receipt dengan dokumen pabean, detail item, HS code, qty, nilai, berat, dan data import CEISA/temp.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary">Read Only Reconciliation</span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="crm-kpi"><i class="fa fa-download"></i><span>GR This Period</span><strong><?=number_format((float)$kpi->total_gr,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="crm-kpi"><i class="fa fa-check"></i><span>Header Complete</span><strong><?=number_format((float)$kpi->complete_header,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="crm-kpi"><i class="fa fa-warning"></i><span>Need Completion</span><strong><?=number_format((float)$kpi->incomplete_header,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="crm-kpi"><i class="fa fa-cloud-upload"></i><span>Import Rows</span><strong><?=number_format((float)$importSummary->import_rows,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_customs_receiving','Filter Customs Receiving');?></h3></div>
    <div class="box-body">
      <form id="filter_crm" class="form-horizontal crm-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=customs_h('posting_date','Posting Date');?></label>
          <div class="col-lg-2"><div class="input-group date crm-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date crm-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=customs_h('bc_type','Jenis BC');?></label>
          <div class="col-lg-2"><select id="filter_jenis_dokpab" class="form-control"><option value="">Semua</option><?php foreach($docTypes as $d){ ?><option value="<?=htmlspecialchars($d->jenis_dokpab,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($d->jenis_dokpab,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=customs_h('status','Status');?></label>
          <div class="col-lg-2"><select id="filter_recon_status" class="form-control"><option value="">Semua</option><option value="COMPLETE">Complete</option><option value="INCOMPLETE">Incomplete</option><option value="ERP_ONLY">ERP Only</option><option value="MISMATCH">Mismatch</option><option value="REVERSED">Reversed</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Vendor</label>
          <div class="col-lg-4"><select id="filter_vendor" class="form-control"><option value="">Semua Vendor</option><?php foreach($vendors as $v){ ?><option value="<?=htmlspecialchars($v->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($v->kode_pemasok.' - '.$v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-2">Customs Status</label>
          <div class="col-lg-4"><select id="filter_customs_status" class="form-control"><option value="">Semua</option><option value="DRAFT">DRAFT</option><option value="SUBMITTED">SUBMITTED</option><option value="REGISTERED">REGISTERED</option><option value="RELEASED">RELEASED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=customs_h('search','Search');?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="No BPB / No Aju / No Pabean / PO / Vendor / Material"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_crm" class="btn btn-primary"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button>
            <button type="button" id="btn_reset_crm" class="btn btn-default"><i class="fa fa-refresh"></i> <?=customs_h('reset','Reset');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_customs_receiving_monitor" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=customs_h('no','No');?></th>
              <th><?=customs_h('action','Action');?></th>
              <th>Goods Receipt</th>
              <th><?=customs_h('posting_date','Posting Date');?></th>
              <th>Vendor</th>
              <th>Customs Document</th>
              <th><?=customs_h('aju_no_short','No Aju');?></th>
              <th>Items</th>
              <th class="text-right">GR Qty</th>
              <th class="text-right">Customs Qty</th>
              <th class="text-right">Import Qty</th>
              <th><?=customs_h('status','Status');?></th>
              <th>Customs Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail_crm" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%">
      <div class="modal-content">
        <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Customs Receiving Detail</h4></div>
        <div class="modal-body" id="isi_detail_crm"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=customs_h('close','Close');?></button></div>
      </div>
    </div>
  </div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showCrmError(m){$('.isi_warning_delete').text(m||'Data Customs Receiving gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.crm-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_jenis_dokpab,#filter_recon_status,#filter_vendor,#filter_customs_status').select2({width:'100%'});}
  var dt=$('#dtb_customs_receiving_monitor').DataTable({
    bProcessing:true,
    bServerSide:true,
    pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'<?=customs_h('export_data','Export Data');?>',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9,10],className:'text-right'},{width:'42px',targets:0},{width:'62px',targets:1}],
    ajax:{
      url:'<?=base_admin();?>modul/customs_receiving_monitor/customs_receiving_monitor_data.php',
      type:'post',
      data:function(d){
        d.tgl_awal=$('#filter_tgl_awal').val();
        d.tgl_akhir=$('#filter_tgl_akhir').val();
        d.jenis_dokpab=$('#filter_jenis_dokpab').val();
        d.recon_status=$('#filter_recon_status').val();
        d.vendor=$('#filter_vendor').val();
        d.customs_status=$('#filter_customs_status').val();
        d.keyword=$('#filter_keyword').val();
      },
      error:function(xhr){console.log(xhr);showCrmError('Data Customs Receiving Monitor gagal dimuat.');}
    }
  });
  $('#btn_filter_crm').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_crm').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');
    $('#filter_jenis_dokpab,#filter_recon_status,#filter_vendor,#filter_customs_status').val('').trigger('change');
    dt.draw();
  });
  $(document).on('click','.btn-detail-crm',function(){
    $.post('<?=base_admin();?>modul/customs_receiving_monitor/customs_receiving_monitor_action.php?act=detail',{no_bpb:$(this).data('no-bpb')},function(html){
      $('#isi_detail_crm').html(html);$('#modal_detail_crm').modal('show');
    }).fail(function(){showCrmError('Detail Customs Receiving gagal dibuka.');});
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
