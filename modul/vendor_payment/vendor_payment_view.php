<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
require_once "vendor_payment_lib.php";
function vp_rows($q){$r=array();foreach($q as $x)$r[]=$x;return $r;}
$vendors=vp_rows($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama"));
$invoices=vp_rows($db->query("SELECT vi.id,vi.vendor_invoice_no,vi.vendor_reference_no,vi.vendor_code,v.nama vendor_name,vi.gross_amount,vi.currency,vi.ap_account,COALESCE((SELECT SUM(vp.amount) FROM erp_vendor_payment vp WHERE vp.vendor_invoice_id=vi.id AND vp.status='POSTED'),0) paid_amount FROM erp_vendor_invoice vi LEFT JOIN pemasok v ON v.kode_pemasok=vi.vendor_code WHERE vi.status='POSTED' AND vi.payment_status IN ('OPEN','PARTIAL') ORDER BY vi.due_date ASC,vi.posting_date ASC LIMIT 500"));
$bankAccounts=vp_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '112%' OR r.nama_rek LIKE '%Bank%' OR r.nama_rek LIKE '%Giro%') ORDER BY r.no_rek"));
$apAccounts=vp_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '211%' OR r.nama_rek LIKE '%Hutang%' OR r.nama_rek LIKE '%A/P%') ORDER BY r.no_rek"));
$currencies=vp_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
?>
<link href="<?=base_admin();?>assets/plugins/select2/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datepicker/datepicker3.css">
<script src="<?=base_admin();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>.vp-table th,.vp-table td{font-size:12px;vertical-align:middle!important}.vp-kpi .description-header{font-size:20px}.vp-help{background:#fffaf3;border-left:3px solid #f39c12;padding:10px;margin-bottom:12px}.vp-source{background:#f8fbff;border:1px solid #d9edf7;border-radius:4px;padding:10px;margin-top:10px}.required:after{content:" *";color:#dd4b39}</style>

<section class="content-header">
  <h1><?=fin_h('finance_vendor_payment', 'Vendor Payment');?> <small>SAP FI-AP Payment</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Accounts Payable</li><li class="active"><?=fin_h('finance_vendor_payment', 'Vendor Payment');?></li></ol>
</section>

<section class="content">
  <div class="row vp-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Total Payment</span><h5 id="kpi_total" class="description-header text-red">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span><?=fin_h('finance_posted', 'Posted');?></span><h5 id="kpi_posted" class="description-header text-green">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span><?=fin_h('finance_draft', 'Draft');?></span><h5 id="kpi_draft" class="description-header text-yellow">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Documents</span><h5 id="kpi_count" class="description-header">0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Vendor Payment Cockpit</h3>
      <div class="box-tools"><button id="btn_add" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Vendor Payment</button> <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button></div>
    </div>
    <div class="box-body">
      <form id="vp_filter" class="form-horizontal">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option></select></div>
          <label class="control-label col-md-1"><?=fin_h('finance_vendor', 'Vendor');?></label>
          <div class="col-md-3"><select id="filter_vendor_code" class="form-control select2-filter"><option value=""></option><?php foreach($vendors as $v){ ?><option value="<?=vp_h($v->kode_pemasok);?>"><?=vp_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
        </div>
        <div class="form-group"><div class="col-md-12"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div></div>
      </form>
      <div class="vp-help"><i class="fa fa-info-circle"></i> Posting Vendor Payment otomatis membuat jurnal SAP FI document type <b>KZ</b>: <b>Dr AP Vendor</b> dan <b>Cr Bank</b>. Jika dipilih dari invoice, sistem otomatis memperbarui status invoice menjadi OPEN/PARTIAL/PAID.</div>
      <div class="table-responsive"><table id="vendor_payment_table" class="table table-bordered table-striped table-hover vp-table"><thead><tr><th><?=fin_h('common_no', 'No');?></th><th>Posting</th><th>Payment No</th><th><?=fin_h('finance_vendor', 'Vendor');?></th><th><?=fin_h('finance_invoice', 'Invoice');?></th><th><?=fin_h('common_status', 'Status');?></th><th><?=fin_h('finance_bank', 'Bank');?></th><th>Method</th><th>Bank Ref</th><th><?=fin_h('finance_amount', 'Amount');?></th><th>Curr</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead><tbody id="vp_body"></tbody></table></div>
    </div>
  </div>
</section>

<div class="modal fade" id="vp_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="vp_form"><input type="hidden" name="id" id="id"><input type="hidden" name="vendor_invoice_no" id="vendor_invoice_no"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Vendor Payment Entry</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('common_no', 'No');?></label><input name="vendor_payment_no" id="vendor_payment_no" class="form-control" readonly></div>
    <div class="col-md-5"><label><?=fin_h('finance_vendor_invoice', 'Vendor Invoice');?></label><select name="vendor_invoice_id" id="vendor_invoice_id" class="form-control select2-modal"><option value=""></option><?php foreach($invoices as $i){$open=max(0,(float)$i->gross_amount-(float)$i->paid_amount); ?><option value="<?=$i->id;?>" data-no="<?=vp_h($i->vendor_invoice_no);?>" data-vendor="<?=vp_h($i->vendor_code);?>" data-open="<?=$open;?>" data-currency="<?=vp_h($i->currency);?>" data-ap="<?=vp_h($i->ap_account);?>"><?=vp_h($i->vendor_invoice_no.' / '.$i->vendor_reference_no.' - '.$i->vendor_name.' - Open '.vp_num($open));?></option><?php } ?></select></div>
    <div class="col-md-4"><label class="required"><?=fin_h('finance_vendor', 'Vendor');?></label><select name="vendor_code" id="vendor_code" class="form-control select2-modal" required><option value=""></option><?php foreach($vendors as $v){ ?><option value="<?=vp_h($v->kode_pemasok);?>"><?=vp_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
  </div>
  <div id="invoice_summary" class="vp-source" style="display:none"></div><br>
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('finance_document_date', 'Document Date');?></label><div class="input-group date"><input name="document_date" id="document_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="posting_date" id="posting_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label>Value Date</label><div class="input-group date"><input name="value_date" id="value_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label>Method</label><select name="payment_method" id="payment_method" class="form-control select2-modal"><option value="TRANSFER">Transfer</option><option value="GIRO">Giro</option><option value="CHEQUE">Cheque</option><option value="VIRTUAL_ACCOUNT">Virtual Account</option><option value="OTHER">Other</option></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-6"><label class="required">Bank Account</label><select name="bank_account" id="bank_account" class="form-control select2-modal" required><option value=""></option><?php foreach($bankAccounts as $a){ ?><option value="<?=vp_h($a->no_rek);?>"><?=vp_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-6"><label class="required">AP Account</label><select name="ap_account" id="ap_account" class="form-control select2-modal" required><option value=""></option><?php foreach($apAccounts as $a){ ?><option value="<?=vp_h($a->no_rek);?>"><?=vp_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label class="required"><?=fin_h('finance_amount', 'Amount');?></label><input name="amount" id="amount" type="number" step="0.01" class="form-control text-right" required></div>
    <div class="col-md-2"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control select2-modal"><?php foreach($currencies as $c){ ?><option value="<?=vp_h($c->jenis_valas);?>" <?=$c->jenis_valas==='IDR'?'selected':'';?>><?=vp_h($c->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-2"><label>Kurs</label><input name="kurs" id="kurs" type="number" step="0.0001" class="form-control text-right" value="1"></div>
    <div class="col-md-2"><label>Bank Ref</label><input name="bank_reference" id="bank_reference" class="form-control"></div>
    <div class="col-md-3"><label>External Ref</label><input name="external_reference" id="external_reference" class="form-control"></div>
  </div><br>
  <label><?=fin_h('finance_description', 'Description');?></label><input name="description" id="description" class="form-control" required>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" class="btn btn-warning vp-save" data-act="save">Save Draft</button><button type="button" class="btn btn-success vp-save" data-act="post"><?=fin_h('common_post', 'Post');?></button></div></form></div></div></div>

<div class="modal fade" id="vp_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Vendor Payment Detail</h4></div><div class="modal-body" id="vp_detail_body"></div></div></div></div>

<script>
$(function(){
  if($.fn.datepicker){$('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});}
  var vendorPaymentTable=null;
  function initSel(){ if($.fn.select2){$('.select2-modal').select2({width:'100%',dropdownParent:$('#vp_modal'),allowClear:true});} }
  function params(){return{start_date:$('#start_date').val(),end_date:$('#end_date').val(),status:$('#filter_status').val(),vendor_code:$('#filter_vendor_code').val()};}
  function rebuildVendorPaymentTable(){ if(vendorPaymentTable){vendorPaymentTable.destroy();} vendorPaymentTable=$('#vendor_payment_table').DataTable({pageLength:25,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Vendor Payment'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Vendor Payment'}],columnDefs:[{targets:[9],className:'text-right'},{targets:[11],orderable:false,searchable:false,className:'text-center'}]});}
  function loadData(){ if(vendorPaymentTable){vendorPaymentTable.destroy();vendorPaymentTable=null;} $('#vp_body').html('<tr><td colspan="12" class="text-center">Loading...</td></tr>'); $.post('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=filter',params(),function(r){ if(r.status==='success'){$('#vp_body').html(r.html);$('#kpi_total').text(r.total);$('#kpi_posted').text(r.posted);$('#kpi_draft').text(r.draft);$('#kpi_count').text(r.count);rebuildVendorPaymentTable();}else{$('#vp_body').html('<tr><td colspan="12" class="text-danger text-center">'+r.message+'</td></tr>');}},'json');}
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#vp_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $('#btn_add').on('click',function(){ $('#vp_form')[0].reset(); $('#id').val(''); $('#invoice_summary').hide().html(''); $('#document_date,#posting_date,#value_date').val('<?=date('Y-m-d');?>'); $('#kurs').val('1'); $.getJSON('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=get_no',function(r){$('#vendor_payment_no').val(r.no);}); $('#vp_modal').modal('show'); initSel(); });
  $('#vendor_invoice_id').on('change',function(){var id=$(this).val(),o=$(this).find(':selected');$('#vendor_code').val(o.data('vendor')).trigger('change');$('#vendor_invoice_no').val(o.data('no'));$('#ap_account').val(o.data('ap')).trigger('change');if(o.data('open'))$('#amount').val(o.data('open'));if(o.data('currency'))$('#currency').val(o.data('currency')).trigger('change');if(!id){$('#invoice_summary').hide().html('');return;}$.getJSON('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=invoice_info&id='+id,function(r){if(r.status==='success'){var d=r.data;$('#invoice_summary').html('<div class="row"><div class="col-sm-3"><b><?=fin_h('finance_invoice', 'Invoice');?></b><br>'+d.vendor_invoice_no+'</div><div class="col-sm-3"><b>Vendor Ref</b><br>'+(d.vendor_reference_no||'-')+'</div><div class="col-sm-3"><b><?=fin_h('finance_due_date', 'Due Date');?></b><br>'+(d.due_date||'-')+'</div><div class="col-sm-3"><b>Open Amount</b><br><span class="text-blue">'+parseFloat(d.open_amount||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})+' '+(d.currency||'')+'</span></div></div>').show();}});});
  $(document).on('click','.vp-save',function(){var act=$(this).data('act');$.post('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act='+act,$('#vp_form').serialize(),function(r){if(r.status==='success'){Swal.fire('Berhasil',r.message,'success');$('#vp_modal').modal('hide');loadData();}else Swal.fire('Gagal',r.message,'error');},'json');});
  $(document).on('click','.vp-edit',function(){$.getJSON('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=get&id='+$(this).data('id'),function(r){if(r.status!=='success'){Swal.fire('Gagal',r.message,'error');return;}var d=r.data;$('#vp_form')[0].reset();$('#invoice_summary').hide().html('');$.each(d,function(k,v){$('#'+k).val(v);});$('#id').val(d.id);$('#vp_modal').modal('show');initSel();$('.select2-modal').trigger('change');$('#vendor_invoice_id').trigger('change');});});
  $(document).on('click','.vp-detail',function(){$('#vp_detail_modal').modal('show');$('#vp_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>);$.getJSON('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=detail&id='+$(this).data('id'),function(r){$('#vp_detail_body').html(r.status==='success'?r.html:'<div class="alert alert-danger">'+r.message+'</div>');});});
  $(document).on('click','.vp-post',function(){var id=$(this).data('id');Swal.fire({title:'Post vendor payment?',icon:'question',showCancelButton:true}).then(function(x){if(x.isConfirmed){$.post('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=post',{id:id},function(r){r.status==='success'?Swal.fire('Berhasil',r.message,'success'):Swal.fire('Gagal',r.message,'error');loadData();},'json');}});});
  $(document).on('click','.vp-reverse',function(){var id=$(this).data('id');Swal.fire({title:'Reversal vendor payment?',text:'Payment posted akan dikoreksi dengan jurnal reversal.',icon:'warning',showCancelButton:true}).then(function(x){if(x.isConfirmed){$.post('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=reverse',{id:id},function(r){r.status==='success'?Swal.fire('Berhasil',r.message,'success'):Swal.fire('Gagal',r.message,'error');loadData();},'json');}});});
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/vendor_payment/vendor_payment_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
