<?php
include_once "quality_dashboard_lib.php";
$filters = qdash_filters();
$plants = qdash_plants($db);
$storageLocations = qdash_storage_locations($db);
$storageBins = qdash_storage_bins($db);
$kpi = qdash_kpi($db, $filters);
$stockChart = qdash_stock_chart($db);
$trendChart = qdash_trend_chart($db, $filters);
?>
<style>
  .qdash-hero{background:linear-gradient(135deg,#0f172a,#7c3aed);color:#fff;border-radius:14px;padding:21px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
  .qdash-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.qdash-hero p{margin:0;opacity:.92}.qdash-hero .label{font-size:12px}
  .qdash-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .qdash-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.qdash-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
  .qdash-kpi small{display:block;color:#64748b;margin-top:4px}.qdash-kpi i{float:right;font-size:26px;color:#7c3aed;opacity:.55}
  .qdash-card{border-radius:12px;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05)}.qdash-filter .form-group{margin-bottom:12px}
  #dtb_quality_dashboard th,#dtb_quality_dashboard td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
  .qdash-chart{min-height:290px}.qdash-detail-table th{width:190px;background:#f8fafc}.qdash-note{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Quality Dashboard <small>SAP QM Cockpit</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>quality-dashboard">Quality Management</a></li><li class="active">Quality Dashboard</li></ol>
</section>
<section class="content">
  <div class="qdash-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Quality Management Cockpit</h1>
        <p>Monitor stock quality inspection, blocked stock, NG produksi, scrap/rework, serta trace lot dan dokumen BC dari satu dashboard.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-info">Read Only Monitor</span>
        <button id="btn_excel_qdash_top" class="btn btn-success" style="margin-left:8px"><i class="fa fa-file-excel-o"></i> Export Excel</button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="qdash-kpi"><i class="fa fa-search"></i><span>Quality Inspection Stock</span><strong><?=qdash_num($kpi['quality_qty'],2);?></strong><small><?=$kpi['quality_layers'];?> layer terbuka</small></div></div>
    <div class="col-sm-3"><div class="qdash-kpi"><i class="fa fa-ban"></i><span>Blocked Stock</span><strong><?=qdash_num($kpi['blocked_qty'],2);?></strong><small><?=$kpi['blocked_layers'];?> layer terbuka</small></div></div>
    <div class="col-sm-3"><div class="qdash-kpi"><i class="fa fa-warning"></i><span>NG / Defect</span><strong><?=qdash_num($kpi['ng_qty'],2);?></strong><small><?=$kpi['ng_count'];?> record periode filter</small></div></div>
    <div class="col-sm-3"><div class="qdash-kpi"><i class="fa fa-line-chart"></i><span>Scrap Rate</span><strong><?=qdash_num($kpi['scrap_rate'],2);?>%</strong><small>Scrap <?=qdash_num($kpi['scrap_qty'],2);?> | Rework <?=qdash_num($kpi['rework_qty'],2);?></small></div></div>
  </div>

  <div class="box qdash-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Quality Dashboard</h3></div><div class="box-body">
    <form class="form-horizontal qdash-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Periode</label>
        <div class="col-lg-2"><div class="input-group date qdash-date"><input id="filter_tgl_awal" class="form-control" value="<?=qdash_h($filters['tgl_awal']);?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date qdash-date"><input id="filter_tgl_akhir" class="form-control" value="<?=qdash_h($filters['tgl_akhir']);?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Material</label>
        <div class="col-lg-5"><select id="filter_material" class="form-control"></select><div class="qdash-note">Kosongkan untuk semua material.</div></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Plant</label>
        <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value="">Semua Plant</option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=qdash_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2">Storage Location</label>
        <div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value="">Semua SLoc</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=qdash_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1">Storage Bin</label>
        <div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value="">Semua Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=qdash_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Source</label>
        <div class="col-lg-2"><select id="filter_source_type" class="form-control"><option value="">Semua Source</option><option value="STOCK">Stock Quality/Blocked</option><option value="NG">Defect / NG</option><option value="SCRAP">Production Scrap/Rework</option></select></div>
        <label class="control-label col-lg-2">Stock Type</label>
        <div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value="">QI + Blocked</option><option value="QUALITY">Quality Inspection</option><option value="BLOCKED">Blocked Stock</option><option value="UNRESTRICTED">Unrestricted</option></select></div>
        <label class="control-label col-lg-1">Search</label>
        <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Material, BPB, BC, PO, NG, catatan"></div>
      </div>
      <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
          <button type="button" id="btn_filter_qdash" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
          <button type="button" id="btn_reset_qdash" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
          <button type="button" id="btn_excel_qdash" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        </div>
      </div>
    </form>
  </div></div>

  <div class="row">
    <div class="col-md-5"><div class="box qdash-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-pie-chart"></i> Stock by Quality Status</h3></div><div class="box-body"><div id="qdash_stock_chart" class="qdash-chart"></div></div></div></div>
    <div class="col-md-7"><div class="box qdash-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-line-chart"></i> Defect & Scrap Trend</h3></div><div class="box-body"><div id="qdash_trend_chart" class="qdash-chart"></div></div></div></div>
  </div>

  <div class="box qdash-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> Quality Exception Worklist</h3></div><div class="box-body">
    <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
    <div class="table-responsive">
      <table id="dtb_quality_dashboard" class="table table-bordered table-striped table-condensed" style="width:100%">
        <thead><tr><th>No</th><th>Action</th><th>Source</th><th>Date</th><th>Material</th><th>Location / Reference</th><th>Qty</th><th>Status</th><th>Dokumen BC</th><th>Remarks</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div></div>

  <div id="modal_detail_qdash" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Quality Detail</h4></div><div class="modal-body" id="qdash_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
var qdashStockChart = <?=json_encode($stockChart);?>;
var qdashTrendChart = <?=json_encode($trendChart);?>;
function qdashError(m){$('.isi_warning_delete').text(m||'Quality Dashboard gagal diproses.');$('.error_data_delete').fadeIn();}
function qdashFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),source_type:$('#filter_source_type').val(),stock_type:$('#filter_stock_type').val(),keyword:$('#filter_keyword').val()};}
function qdashQuery(){return $.param(qdashFilters());}
function qdashRenderCharts(){
  if(typeof Highcharts==='undefined')return;
  Highcharts.chart('qdash_stock_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y:,.2f}</b>'},plotOptions:{pie:{allowPointSelect:true,cursor:'pointer',dataLabels:{enabled:true,format:'{point.name}: {point.y:,.2f}'}}},series:[{name:'Qty',colorByPoint:true,data:qdashStockChart}]});
  Highcharts.chart('qdash_trend_chart',{chart:{type:'line'},title:{text:null},xAxis:{categories:qdashTrendChart.categories||[]},yAxis:{title:{text:'Qty'}},tooltip:{shared:true},series:[{name:'NG',data:qdashTrendChart.ng||[],color:'#f59e0b'},{name:'Scrap',data:qdashTrendChart.scrap||[],color:'#dc2626'}]});
}
$(function(){
  if($.fn.datepicker){$('.qdash-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_source_type,#filter_stock_type').select2({width:'100%',allowClear:true});
    $('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/quality_dashboard/quality_dashboard_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_quality_dashboard').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:'Export Data',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[6],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1},{width:'110px',targets:2},{width:'95px',targets:3}],ajax:{url:'<?=base_admin();?>modul/quality_dashboard/quality_dashboard_data.php',type:'post',data:function(d){$.extend(d,qdashFilters());},error:function(xhr){console.log(xhr.responseText);qdashError('Data Quality Dashboard gagal dimuat.');}}});
  $('#btn_filter_qdash').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_qdash').on('click',function(){$('#filter_tgl_awal').val('<?=date('Y-m-01');?>');$('#filter_tgl_akhir').val('<?=date('Y-m-d');?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_source_type,#filter_stock_type').val('').trigger('change');dt.draw();});
  $('#btn_excel_qdash,#btn_excel_qdash_top').on('click',function(){window.location='<?=base_admin();?>modul/quality_dashboard/quality_dashboard_action.php?act=excel&'+qdashQuery();});
  $(document).on('click','.btn-qm-detail',function(){var btn=$(this);$.post('<?=base_admin();?>modul/quality_dashboard/quality_dashboard_action.php?act=detail',{source:btn.data('source'),id:btn.data('id')},function(html){$('#qdash_detail_body').html(html);$('#modal_detail_qdash').modal('show');}).fail(function(){qdashError('Detail quality gagal dibuka.');});});
  $('.hide_alert_notif').on('click',function(){$('.error_data_delete').hide();});
  qdashRenderCharts();
});
</script>
