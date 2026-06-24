<?php
include_once "dokumen_pabean_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$dokumenRefs = iterator_to_array(dpb_ref_dokumen($db));
$statusRows = $db->query("SELECT DISTINCT statusDokumen FROM ws_header WHERE statusDokumen IS NOT NULL AND statusDokumen<>'' ORDER BY statusDokumen");
$kpi = $db->fetch("SELECT COUNT(*) total_doc,
                          SUM(kodeDokumen IN ('23','40','262')) incoming_doc,
                          SUM(kodeDokumen IN ('25','27','30','41','261')) outgoing_doc,
                          SUM(UPPER(COALESCE(statusDokumen,'DRAFT'))='DRAFT') draft_doc
                   FROM ws_header");
$canInsert = isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y";
$canEdit = isset($role_act["up_act"]) && $role_act["up_act"]=="Y";
$canDelete = isset($role_act["del_act"]) && $role_act["del_act"]=="Y";
?>
<link href="<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_modal.css" rel="stylesheet" />
<style>
.dpb-hero{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.dpb-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.dpb-hero p{margin:0;opacity:.92}
.dpb-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.dpb-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.dpb-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.dpb-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.dpb-filter .form-group{margin-bottom:12px}
#dtb_dokumen_pabean th,#dtb_dokumen_pabean td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.dpb-actions .btn{margin-right:3px}
.dokumen-pabean-page.dpb-list-page > .row,
.dokumen-pabean-page.dpb-list-page > .row > [class*="col-"]{min-height:0!important}
.dpb-list-page .dpb-hero{margin-bottom:14px}
.dpb-list-page .dpb-kpi{margin-bottom:12px}
.dpb-list-page > .box{margin-bottom:14px}
</style>

<section class="content-header">
  <h1><?=customs_h('documents','Customs Documents');?> <small><?=customs_h('documents_subtitle','H2H CEISA 4.0');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li><li class="active"><?=customs_h('documents','Dokumen Pabean');?></li></ol>
</section>

<section class="content dokumen-pabean-page dpb-list-page">
  <div class="dpb-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=customs_h('documents_workbench','Customs Documents Workbench');?></h1>
        <p><?=customs_h('documents_intro','Monitor dokumen BC, draft H2H CEISA, nomor aju, status dokumen, dan detail item pabean dalam satu layar.');?></p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary">CEISA 4.0</span><br>
        <?php if($canInsert){ ?>
        <button type="button" onclick="show_modal_dok()" class="btn btn-success btn-sm" style="margin-top:12px"><i class="fa fa-plus"></i> <?=customs_h('create_document','Buat Dokumen');?></button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="dpb-kpi"><i class="fa fa-file-text-o"></i><span><?=customs_h('total_documents','Total Dokumen');?></span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpb-kpi"><i class="fa fa-sign-in"></i><span><?=customs_h('incoming_bc','Incoming BC');?></span><strong><?=number_format((float)$kpi->incoming_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpb-kpi"><i class="fa fa-sign-out"></i><span><?=customs_h('outgoing_bc','Outgoing BC');?></span><strong><?=number_format((float)$kpi->outgoing_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpb-kpi"><i class="fa fa-pencil-square-o"></i><span><?=customs_h('draft','Draft');?></span><strong><?=number_format((float)$kpi->draft_doc,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=customs_h('filter_documents','Filter Dokumen Pabean');?></h3></div>
    <div class="box-body">
      <form class="form-horizontal dpb-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=customs_h('document_date','Tanggal Dokumen');?></label>
          <div class="col-lg-2"><div class="input-group date dpb-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date dpb-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=customs_h('bc_type','Jenis BC');?></label>
          <div class="col-lg-2">
            <select id="filter_jenis_bc" class="form-control">
              <option value=""><?=customs_h('all_bc','Semua BC');?></option>
              <?php foreach($dokumenRefs as $d){ ?><option value="<?=dpb_h($d->id_dokumen);?>"><?=dpb_h(($d->nama_pendek?:'BC '.$d->id_dokumen).' - '.$d->nama_dokumen);?></option><?php } ?>
            </select>
          </div>
          <label class="control-label col-lg-1"><?=customs_h('status','Status');?></label>
          <div class="col-lg-2">
            <select id="filter_status_dokumen" class="form-control">
              <option value=""><?=customs_h('all','Semua');?></option>
              <?php foreach($statusRows as $s){ ?><option value="<?=dpb_h($s->statusDokumen);?>"><?=dpb_h($s->statusDokumen);?></option><?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=customs_h('search','Search');?></label>
          <div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="<?=customs_h('keyword_customs_doc','Nomor aju / daftar / status / jenis dokumen');?>"></div>
          <div class="col-lg-6">
            <button type="button" id="btn_filter_dpb" class="btn btn-primary"><i class="fa fa-filter"></i> <?=customs_h('filter','Filter');?></button>
            <button type="button" id="btn_reset_dpb" class="btn btn-default"><i class="fa fa-refresh"></i> <?=customs_h('reset','Reset');?></button>
            <button type="button" id="btn_excel_dpb" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=customs_h('print_excel','Cetak Excel');?></button>
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
        <table id="dtb_dokumen_pabean" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=customs_h('no','No');?></th>
              <th><?=customs_h('bc_type','Jenis BC');?></th>
              <th><?=customs_h('aju_no','Nomor Aju');?></th>
              <th><?=customs_h('registration_no','No Daftar');?></th>
              <th><?=customs_h('document_date','Tgl Dokumen');?></th>
              <th><?=customs_h('signed_date','Tgl TTD');?></th>
              <th><?=customs_h('item','Item');?></th>
              <th><?=customs_h('status','Status');?></th>
              <th><?=customs_h('action','Action');?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</section>

<div id="modal_dok" class="modal fade dpb-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?=customs_h('document_type','Jenis Dokumen');?></h4>
      </div>
      <div class="modal-body">
        <form id="input_dokumen_pabean" method="post" class="form-horizontal" action="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=in">
          <div class="form-group">
            <label class="control-label col-lg-3"><?=customs_h('entity','Entitas');?></label>
            <div class="col-lg-9">
              <select class="form-control" name="entitas" id="entitas" onchange="pilih_dokpab(this.value)">
                <option value=""><?=customs_h('select_entity','Pilih Entitas');?></option>
                <option value="importir">IMPORTIR</option>
                <option value="eksportir">EKSPORTIR</option>
                <option value="tpb">TPB</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-3"><?=customs_h('document_type','Jenis Dokumen');?></label>
            <div class="col-lg-9">
              <select class="form-control" name="jenis_dokpab" id="jenis_dokpab"><option value=""><?=customs_h('select_entity','Pilih Entitas');?></option></select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><?=customs_h('close','Close');?></button>
        <button type="button" class="btn btn-success" onclick="lanjut_dok()"><?=customs_h('continue','Lanjut');?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
var canEditDpb = <?= $canEdit ? 'true' : 'false'; ?>;
var canDeleteDpb = <?= $canDelete ? 'true' : 'false'; ?>;
function show_modal_dok(){ $("#modal_dok").modal("show"); }
function lanjut_dok(){
  if(!$("#jenis_dokpab").val()){ alert(<?=customs_js('select_document_first','Pilih jenis dokumen terlebih dahulu.');?>); return; }
  $.ajax({
    url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=buat_dokumen",
    type : "POST",
    data : { jenis : $("#jenis_dokpab").val() },
    success : function(data){ document.location = "<?= base_url() ?>index.php/dokumen-pabean/buat_dokumen/"+data; }
  });
}
function pilih_dokpab(val){
  $.ajax({
    url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_dokpab",
    type : "POST",
    data : { jenis : val },
    success : function(data){ $("#jenis_dokpab").html(data).trigger('change'); }
  });
}
function dpbFilters(){
  return {
    tgl_awal: $('#filter_tgl_awal').val(),
    tgl_akhir: $('#filter_tgl_akhir').val(),
    jenis_bc: $('#filter_jenis_bc').val(),
    status_dokumen: $('#filter_status_dokumen').val(),
    keyword: $('#filter_keyword').val()
  };
}
$(function(){
  $('.dokumen-pabean-page .modal').each(function(){
    $(this).addClass('dpb-modal').appendTo('body');
  });
  if($.fn.datepicker){ $('.dpb-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true}); }
  if($.fn.select2){ $('#filter_jenis_bc,#filter_status_dokumen,#entitas,#jenis_dokpab').select2({width:'100%',allowClear:true}); }
  var dtb_dokumen_pabean = $("#dtb_dokumen_pabean").DataTable({
    bProcessing:true,
    bServerSide:true,
    pageLength:25,
    ordering:false,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:'<?=customs_h('export_data','Export Data');?>',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,8],orderable:false,searchable:false},{width:'42px',targets:0},{width:'110px',targets:8}],
    fnCreatedRow:function(nRow,aData){
      var uuid = aData[aData.length-1];
      var action = '<div class="dpb-actions">';
      if(canEditDpb) action += '<a href="<?=base_index();?>dokumen-pabean/buat_dokumen/'+uuid+'" class="btn btn-primary btn-xs" title="<?=customs_h('edit','Edit');?>"><i class="fa fa-pencil"></i></a>';
      if(canDeleteDpb) action += '<button data-id="'+uuid+'" data-uri="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php" class="btn btn-danger hapus_dtb_notif btn-xs" data-toggle="tooltip" title="<?=customs_h('delete','Hapus');?>" data-variable="dtb_dokumen_pabean"><i class="fa fa-trash"></i></button>';
      action += '</div>';
      $('td:eq(8)', nRow).html(action);
      $(nRow).attr('id','line_'+uuid);
    },
    ajax:{
      url:'<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_data.php',
      type:'post',
      data:function(d){ $.extend(d,dpbFilters()); },
      error:function(xhr){ console.log(xhr); $('.isi_warning_delete').text(<?=customs_js('data_load_failed','Data gagal dimuat.');?>); $('.error_data_delete').fadeIn(); }
    }
  });
  $('#btn_filter_dpb').on('click',function(){ dtb_dokumen_pabean.draw(); });
  $('#filter_keyword').on('keyup',function(e){ if(e.keyCode===13) dtb_dokumen_pabean.draw(); });
  $('#btn_reset_dpb').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');
    $('#filter_tgl_akhir').val('<?=$defaultTo;?>');
    $('#filter_keyword').val('');
    $('#filter_jenis_bc,#filter_status_dokumen').val('').trigger('change');
    dtb_dokumen_pabean.draw();
  });
  $('#btn_excel_dpb').on('click',function(){
    window.location = '<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=excel&'+$.param(dpbFilters());
  });
  $(document).on('click','.hide_alert_notif',function(){ $('.error_data_delete').hide(); });
});
</script>
