<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$customers = $db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$salesOrders = $db->query("
  SELECT so.id_sales_order,so.no_sales_order,so.so_date,so.currency,so.no_po,so.term,so.tax,p.nama customer_name,
         COALESCE(SUM(sod.nilai),0) so_amount
  FROM sales_order so
  LEFT JOIN sales_order_detail sod ON sod.id_sales_order=so.id_sales_order
  LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima
  WHERE COALESCE(so.approval_status,'APPROVED') NOT IN ('REJECTED','CANCELLED')
  GROUP BY so.id_sales_order
  ORDER BY so.so_date DESC,so.id_sales_order DESC
");
$kpi = $db->fetch("
  SELECT COUNT(*) doc_count,
         COALESCE(SUM(net_amount),0) net_amount,
         COALESCE(SUM(tax_amount),0) tax_amount,
         COALESCE(SUM(dp_open_amount),0) open_amount
  FROM sales_invoice
  WHERE billing_type='DP'
    AND invoice_date BETWEEN ? AND ?
    AND billing_status<>'CANCELLED'
", array($defaultFrom,$defaultTo));
?>
<style>
.dpi-hero{background:linear-gradient(135deg,#064e3b,#10b981);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(6,78,59,.18)}
.dpi-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.dpi-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.dpi-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px}.dpi-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase}.dpi-kpi strong{display:block;font-size:22px;margin-top:6px}.dpi-kpi i{float:right;font-size:26px;color:#10b981;opacity:.58}
.dpi-help{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:12px;margin-bottom:14px;color:#475569}
#dtb_down_payment_invoice th,#dtb_down_payment_invoice td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.dpi-section{font-weight:700;border-left:4px solid #10b981;background:#f0fdf4;padding:9px 12px;border-radius:6px;margin:10px 0 14px}.dpi-money{font-size:18px;font-weight:700}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_down_payment_invoice', 'Down Payment Invoice');?> <small>SAP SD/FI-AR</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li>Billing</li><li class="active"><?=sd_h('sales_down_payment_invoice', 'Down Payment Invoice');?></li></ol>
</section>
<section class="content">
  <div class="dpi-hero">
    <div class="row">
      <div class="col-md-8"><h1>Down Payment Invoice Cockpit</h1><p>Billing uang muka pelanggan berdasarkan Sales Order sebelum delivery. Cocok untuk proses DP, advance payment, dan monitoring sisa uang muka.</p></div>
      <div class="col-md-4 text-right"><button class="btn btn-warning btn-lg" id="btn_add_dp"><i class="fa fa-plus"></i> Create DP Invoice</button></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="dpi-kpi"><i class="fa fa-file-text-o"></i><span>DP Documents</span><strong><?=number_format((float)$kpi->doc_count,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpi-kpi"><i class="fa fa-calculator"></i><span>DPP</span><strong><?=number_format((float)$kpi->net_amount,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpi-kpi"><i class="fa fa-percent"></i><span>PPN</span><strong><?=number_format((float)$kpi->tax_amount,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dpi-kpi"><i class="fa fa-money"></i><span>Open DP</span><strong><?=number_format((float)$kpi->open_amount,2,',','.');?></strong></div></div>
  </div>
  <div class="dpi-help"><strong>Standar SAP:</strong> Down Payment Invoice dibuat dari Sales Order, tidak mengambil Surat Jalan. Jurnalnya mencatat piutang uang muka pelanggan dan liability uang muka, bukan revenue final barang.</div>
  <div class="box dpi-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></h3></div><div class="box-body">
    <form class="form-horizontal" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Invoice Date</label>
        <div class="col-lg-2"><div class="input-group date dpi-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date dpi-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label>
        <div class="col-lg-3"><select id="filter_customer" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($customers as $c){ ?><option value="<?=htmlspecialchars($c->kode_penerima,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($c->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=sd_h('sales_all_status', 'All Status');?></option><option value="POSTED">POSTED</option><option value="CANCELLED">CANCELLED</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_search', 'Search');?></label>
        <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="DP invoice, Sales Order, PO, customer"></div>
        <div class="col-lg-5"><button id="btn_filter_dpi" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button id="btn_reset_dpi" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button id="btn_excel_dpi" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box dpi-card"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_down_payment_invoice" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>DP Invoice</th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=sd_h('sales_order', 'Sales Order');?></th><th>DP Amount</th><th>Open Amount</th><th><?=sd_h('common_status', 'Status');?></th><th>Created</th></tr></thead><tbody></tbody></table></div></div></div>

  <div id="modal_dp_form" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
    <form id="form_dp_invoice" class="form-horizontal" method="post" action="<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=in">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-credit-card"></i> Create Down Payment Invoice</h4></div>
      <div class="modal-body">
        <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>
        <div class="dpi-section">Sales Order Reference</div>
        <div class="form-group"><label class="control-label col-lg-2"><?=sd_h('sales_order', 'Sales Order');?> <span class="text-red">*</span></label><div class="col-lg-10"><select name="sales_order_id" id="sales_order_id" class="form-control" required><option value=""></option><?php foreach($salesOrders as $so){ ?><option value="<?=(int)$so->id_sales_order;?>"><?=htmlspecialchars($so->no_sales_order.' | '.$so->customer_name.' | '.$so->currency.' '.number_format((float)$so->so_amount,2,'.',','),ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div></div>
        <div id="so_summary" class="well well-sm" style="display:none"></div>
        <div class="dpi-section">Billing & Amount</div>
        <div class="form-group"><label class="control-label col-lg-2">DP Invoice No</label><div class="col-lg-4"><input name="no_sales_invoice" id="no_sales_invoice" class="form-control" readonly></div><label class="control-label col-lg-2">Invoice Date</label><div class="col-lg-4"><div class="input-group date dpi-date"><input name="invoice_date" id="invoice_date" class="form-control" value="<?=date('Y-m-d');?>" required><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div></div>
        <div class="form-group"><label class="control-label col-lg-2">DP %</label><div class="col-lg-2"><input name="dp_percent" id="dp_percent" type="number" step="0.01" min="0" max="100" class="form-control text-right" value="30"></div><label class="control-label col-lg-2">SO Amount</label><div class="col-lg-2"><input id="so_amount" class="form-control text-right" readonly></div><label class="control-label col-lg-2">DP Base Amount</label><div class="col-lg-2"><input name="dp_base_amount" id="dp_base_amount" type="number" step="0.01" min="0" class="form-control text-right" required></div></div>
        <div class="form-group"><label class="control-label col-lg-2"><?=sd_h('sales_tax', 'Tax');?></label><div class="col-lg-2"><select name="tax" id="tax" class="form-control"><option value="1">PPN 11%</option><option value="0">Non PPN</option></select></div><label class="control-label col-lg-2"><?=sd_h('sales_currency', 'Currency');?></label><div class="col-lg-2"><input name="valuta" id="valuta" class="form-control" readonly></div><label class="control-label col-lg-2"><?=sd_h('sales_payment_term', 'Payment Term');?></label><div class="col-lg-2"><input name="term" id="term" class="form-control"></div></div>
        <div class="row"><div class="col-sm-4"><div class="well well-sm"><span>DPP</span><div class="dpi-money" id="net_preview">0.00</div></div></div><div class="col-sm-4"><div class="well well-sm"><span>PPN</span><div class="dpi-money" id="tax_preview">0.00</div></div></div><div class="col-sm-4"><div class="well well-sm"><span>Grand Total</span><div class="dpi-money" id="gross_preview">0.00</div></div></div></div>
        <div class="dpi-section">Notes & Signature</div>
        <div class="form-group"><label class="control-label col-lg-2">Signed By</label><div class="col-lg-4"><input name="ttd" class="form-control" required></div><label class="control-label col-lg-2">Notes</label><div class="col-lg-4"><input name="catatan" class="form-control" placeholder="Catatan DP invoice"></div></div>
        <input type="hidden" name="bill_to" id="bill_to"><input type="hidden" name="ship_to" id="ship_to"><input type="hidden" name="no_sales_order" id="no_sales_order"><input type="hidden" name="nopo" id="nopo"><input type="hidden" name="so_total_amount" id="so_total_amount">
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=sd_h('common_close', 'Close');?></button><button type="submit" id="btn_save_dp" class="btn btn-success" disabled><i class="fa fa-save"></i> Post DP Invoice</button></div>
    </form>
  </div></div></div>
  <div id="modal_detail_dpi" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Down Payment Detail</h4></div><div class="modal-body" id="dpi_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=sd_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function dpiNumber(v){v=parseFloat(v||0);return isNaN(v)?0:v;}
function dpiFmt(v){return dpiNumber(v).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});}
function dpiError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_down_payment_process_failed', 'Down Payment Invoice failed to process.');?>);$('.error_data_delete').fadeIn();}
function dpiFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function dpiQuery(){return $.param(dpiFilters());}
function recalcDp(){var so=dpiNumber($('#so_total_amount').val()), pct=dpiNumber($('#dp_percent').val()), net=dpiNumber($('#dp_base_amount').val());if(so>0 && pct>0 && document.activeElement && document.activeElement.id==='dp_percent'){net=so*pct/100;$('#dp_base_amount').val(net.toFixed(2));}var tax=$('#tax').val()==='1'?net*.11:0;$('#net_preview').text(dpiFmt(net));$('#tax_preview').text(dpiFmt(tax));$('#gross_preview').text(dpiFmt(net+tax));$('#btn_save_dp').prop('disabled',!$('#sales_order_id').val() || net<=0);}
$(function(){
  if($.fn.datepicker){$('.dpi-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_customer,#filter_status,#sales_order_id,#tax').select2({width:'100%',allowClear:true});}
  var dt=$('#dtb_down_payment_invoice').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'100px',targets:1}],ajax:{url:'<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_data.php',type:'post',data:function(d){$.extend(d,dpiFilters());},error:function(xhr){console.log(xhr.responseText);dpiError(<?=sd_js('sales_down_payment_load_failed', 'DP Invoice data failed to load.');?>);}}});
  $('#btn_filter_dpi').click(function(){dt.draw();});$('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});$('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#btn_reset_dpi').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_customer,#filter_status').val('').trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_excel_dpi').click(function(){window.location='<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=excel&'+dpiQuery();});
  $('#btn_add_dp').click(function(){$('#form_dp_invoice')[0].reset();$('.error_data').hide();$('#invoice_date').val('<?=date('Y-m-d');?>');$('#dp_percent').val('30');$('#so_summary').hide().html('');$('#btn_save_dp').prop('disabled',true);$('#sales_order_id,#tax').val('').trigger('change');$.getJSON('<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=get_no',function(r){$('#no_sales_invoice').val(r.no||'');});recalcDp();$('#modal_dp_form').modal('show');});
  $('#sales_order_id').change(function(){var id=$(this).val();if(!id){$('#so_summary').hide();recalcDp();return;}$.getJSON('<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=get_so',{id:id},function(r){if(r.status==='error'){Swal.fire('Error',r.error_message,'error');return;}$('#bill_to').val(r.customer_code);$('#ship_to').val(r.customer_code);$('#no_sales_order').val(r.no_sales_order);$('#nopo').val(r.no_po);$('#valuta').val(r.currency);$('#term').val(r.term);$('#tax').val(r.tax).trigger('change');$('#so_total_amount').val(r.so_amount);$('#so_amount').val(dpiFmt(r.so_amount));$('#so_summary').html(r.html).show();$('#dp_percent').trigger('keyup');recalcDp();});});
  $('#dp_percent,#dp_base_amount,#tax').on('keyup change',recalcDp);
  $('#form_dp_invoice').ajaxForm({dataType:'json',beforeSubmit:function(){recalcDp();$('#loadnya').show();},success:function(res){$('#loadnya').hide();var ok=false,msg='';$.each(res||[],function(_,r){if(r.status==='good')ok=true;if(r.status==='error')msg=r.error_message;});if(ok){$('#modal_dp_form').modal('hide');Swal.fire('Success','Down Payment Invoice berhasil diposting.','success');dt.draw(false);}else{$('.isi_warning').text(msg||<?=sd_js('sales_dp_invoice_save_failed', 'DP invoice failed to save.');?>);$('.error_data').fadeIn();}},error:function(xhr){$('#loadnya').hide();console.log(xhr.responseText);$('.isi_warning').text(<?=sd_js('sales_dp_invoice_save_failed', 'DP invoice failed to save.');?>);$('.error_data').fadeIn();}});
  $(document).on('click','.btn-dpi-detail',function(){$.post('<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=detail',{id:$(this).data('id')},function(html){$('#dpi_detail_body').html(html);$('#modal_detail_dpi').modal('show');}).fail(function(){dpiError('Detail gagal dibuka.');});});
  $(document).on('click','.btn-dpi-cancel',function(){var id=$(this).data('id');Swal.fire({title:'Cancel DP Invoice?',input:'text',inputLabel:'Alasan cancel',inputPlaceholder:'Masukkan alasan',icon:'warning',showCancelButton:true,confirmButtonText:'Cancel DP',inputValidator:function(v){if(!v)return 'Alasan wajib diisi';}}).then(function(r){if(!r.isConfirmed)return;$.post('<?=base_admin();?>modul/down_payment_invoice/down_payment_invoice_action.php?act=cancel',{id:id,reason:r.value},function(res){var ok=false,msg='';$.each(res||[],function(_,x){if(x.status==='good')ok=true;if(x.status==='error')msg=x.error_message;});if(ok){Swal.fire('Success','DP Invoice berhasil dicancel.','success');dt.draw(false);}else Swal.fire('Error',msg||'Cancel gagal.','error');},'json').fail(function(xhr){console.log(xhr.responseText);Swal.fire('Error','Cancel gagal.','error');});});});
});
</script>
