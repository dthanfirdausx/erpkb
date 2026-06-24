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
include_once "customer_inquiry_lib.php";
$isEdit = isset($data_edit) && $data_edit;
$record = $isEdit ? $data_edit : null;
$details = $isEdit ? iterator_to_array(ciq_detail_rows($db, $record->id)) : array();
if (!$details) $details = array((object)array('material_code'=>'','material_name'=>'','description'=>'','qty'=>'','uom'=>'','target_price'=>'','requested_delivery_date'=>'','remarks'=>''));
?>
<style>
.ciq-form-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:18px}
.ciq-form-hero h1{margin:0;font-size:24px}.ciq-form-hero p{margin:6px 0 0;opacity:.92}
.ciq-item-table th,.ciq-item-table td{font-size:12px;vertical-align:middle}.ciq-item-table .form-control{font-size:12px;height:31px;padding:4px 7px}
.select2-container{width:100%!important}.ciq-required:after{content:" *";color:#dc2626}
</style>
<section class="content-header">
  <h1><?= $isEdit ? 'Edit' : 'Add'; ?> <?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?> <small>SAP SD Pre-Sales</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>customer-inquiry"><?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?></a></li><li class="active"><?= $isEdit ? 'Edit' : 'Add'; ?></li></ol>
</section>
<section class="content">
  <div class="ciq-form-hero"><h1><?= $isEdit ? ciq_h($record->inquiry_no) : 'New Customer Inquiry'; ?></h1><p>Isi kebutuhan awal customer, material yang diminta, estimasi qty, target price, dan target delivery. Inquiry ini menjadi dasar quotation.</p></div>
  <form id="form_ciq" class="form-horizontal">
    <?php if ($isEdit) { ?><input type="hidden" name="id" value="<?=intval($record->id);?>"><?php } ?>
    <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Header Inquiry</h3></div><div class="box-body">
      <div class="form-group">
        <label class="control-label col-lg-2 ciq-required">Inquiry Date</label><div class="col-lg-2"><div class="input-group date ciq-date"><input name="inquiry_date" class="form-control required-ciq" value="<?=ciq_h($record ? $record->inquiry_date : date('Y-m-d'));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-2">Valid Until</label><div class="col-lg-2"><div class="input-group date ciq-date"><input name="valid_until" class="form-control" value="<?=ciq_h($record ? $record->valid_until : date('Y-m-d', strtotime('+14 days')));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-2">Req. Delivery</label><div class="col-lg-2"><div class="input-group date ciq-date"><input name="requested_delivery_date" class="form-control" value="<?=ciq_h($record ? $record->requested_delivery_date : '');?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2 ciq-required"><?=sd_h('sales_customer', 'Customer');?></label>
        <div class="col-lg-4"><select id="customer_id" name="customer_id" class="form-control required-ciq"><?php if($record && $record->customer_id){ ?><option value="<?=intval($record->customer_id);?>" selected><?=ciq_h(trim((string)$record->customer_code.' - '.(string)$record->customer_name,' -'));?></option><?php } ?></select></div>
        <label class="control-label col-lg-2">Contact</label><div class="col-lg-2"><input name="contact_person" class="form-control" value="<?=ciq_h($record ? $record->contact_person : '');?>"></div>
        <div class="col-lg-2"><input name="phone" id="phone" class="form-control" placeholder="Phone" value="<?=ciq_h($record ? $record->phone : '');?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Email</label><div class="col-lg-4"><input name="email" id="email" class="form-control" value="<?=ciq_h($record ? $record->email : '');?>"></div>
        <label class="control-label col-lg-2">Sales Person</label><div class="col-lg-4"><input name="sales_person" class="form-control" value="<?=ciq_h($record ? $record->sales_person : ciq_username());?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2 ciq-required">Subject</label><div class="col-lg-6"><input name="subject" class="form-control required-ciq" value="<?=ciq_h($record ? $record->subject : '');?>" placeholder="Contoh: Inquiry kebutuhan material untuk project/customer PO"></div>
        <label class="control-label col-lg-1">Priority</label><div class="col-lg-3"><select name="priority" class="form-control select-basic"><?php foreach(array('LOW','NORMAL','HIGH','URGENT') as $p){ ?><option value="<?=$p;?>" <?=($record && $record->priority===$p) || (!$record && $p==='NORMAL') ? 'selected' : '';?>><?=$p;?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_status', 'Status');?></label><div class="col-lg-2"><select name="status" class="form-control select-basic"><?php foreach(array('OPEN','QUOTED','WON','LOST','CANCELLED') as $s){ ?><option value="<?=$s;?>" <?=($record && $record->status===$s) || (!$record && $s==='OPEN') ? 'selected' : '';?>><?=$s;?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_currency', 'Currency');?></label><div class="col-lg-2"><input name="currency" class="form-control" value="<?=ciq_h($record ? $record->currency : 'IDR');?>"></div>
        <label class="control-label col-lg-1">Source</label><div class="col-lg-4"><input name="source" class="form-control" value="<?=ciq_h($record ? $record->source : '');?>" placeholder="Email / Phone / Visit / Website"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('sales_payment_term', 'Payment Term');?></label><div class="col-lg-4"><input name="payment_term" class="form-control" value="<?=ciq_h($record ? $record->payment_term : '');?>"></div>
        <label class="control-label col-lg-2">Incoterm</label><div class="col-lg-4"><input name="incoterm" class="form-control" value="<?=ciq_h($record ? $record->incoterm : '');?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_remarks', 'Remarks');?></label><div class="col-lg-10"><textarea name="remarks" class="form-control" rows="3"><?=ciq_h($record ? $record->remarks : '');?></textarea></div>
      </div>
    </div></div>

    <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Inquiry Items</h3><div class="box-tools"><button type="button" class="btn btn-primary btn-sm" id="btn_add_item"><i class="fa fa-plus"></i> Add Item</button></div></div><div class="box-body">
      <div class="table-responsive"><table class="table table-bordered table-condensed ciq-item-table" id="table_ciq_item"><thead><tr><th style="width:42px">#</th><th style="min-width:260px"><?=sd_h('sales_material', 'Material');?></th><th style="min-width:220px">Description</th><th style="width:110px"><?=sd_h('sales_qty', 'Qty');?></th><th style="width:90px"><?=sd_h('sales_uom', 'UOM');?></th><th style="width:130px">Target Price</th><th style="width:140px"><?=sd_h('sales_amount', 'Amount');?></th><th style="width:140px">Delivery</th><th style="min-width:180px"><?=sd_h('common_remarks', 'Remarks');?></th><th style="width:48px"></th></tr></thead><tbody>
        <?php foreach($details as $d){ ?>
        <tr>
          <td class="line-no text-center"></td>
          <td><select name="material_code[]" class="form-control material-select"><?php if($d->material_code){ ?><option value="<?=ciq_h($d->material_code);?>" selected><?=ciq_h($d->material_code.' - '.($d->material_name ?: $d->nm_barang));?></option><?php } ?></select></td>
          <td><input name="description[]" class="form-control item-desc" value="<?=ciq_h($d->description);?>"></td>
          <td><input name="qty[]" class="form-control text-right item-qty" value="<?=ciq_h($d->qty);?>"></td>
          <td><input name="uom[]" class="form-control item-uom" value="<?=ciq_h($d->uom);?>"></td>
          <td><input name="target_price[]" class="form-control text-right item-price" value="<?=ciq_h($d->target_price);?>"></td>
          <td><input class="form-control text-right item-amount" readonly></td>
          <td><input name="item_delivery_date[]" class="form-control item-date" value="<?=ciq_h($d->requested_delivery_date);?>"></td>
          <td><input name="item_remarks[]" class="form-control" value="<?=ciq_h($d->remarks);?>"></td>
          <td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-item"><i class="fa fa-trash"></i></button></td>
        </tr>
        <?php } ?>
      </tbody></table></div>
      <div class="alert alert-info"><strong>Mandatory item:</strong> material/description, qty lebih dari 0, dan UOM. Target price boleh 0 jika harga belum diketahui.</div>
    </div></div>
    <div class="box"><div class="box-footer text-right">
      <a href="<?=base_index();?>customer-inquiry" class="btn btn-default"><i class="fa fa-step-backward"></i> Back</a>
      <button type="submit" id="btn_save_ciq" class="btn btn-primary" disabled><i class="fa fa-save"></i> Save Inquiry</button>
    </div></div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function initMaterialSelect(ctx){$(ctx).find('.material-select').each(function(){var el=$(this);if(el.data('select2'))return;el.select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_material', 'Search material...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){var data=e.params.data,row=el.closest('tr');row.find('.item-uom').val(data.uom||'');if(!row.find('.item-desc').val())row.find('.item-desc').val(data.name||'');validateCiq();});});}
function renumberItems(){$('#table_ciq_item tbody tr').each(function(i){$(this).find('.line-no').text(i+1);});}
function calcRow(row){var q=parseFloat(String(row.find('.item-qty').val()||'0').replace(',','.'))||0,p=parseFloat(String(row.find('.item-price').val()||'0').replace(',','.'))||0;row.find('.item-amount').val((q*p).toFixed(2));}
function validateCiq(){var ok=true;$('.required-ciq').each(function(){if(!$(this).val())ok=false;});var itemOk=false;$('#table_ciq_item tbody tr').each(function(){var row=$(this),qty=parseFloat(String(row.find('.item-qty').val()||'0').replace(',','.'))||0,uom=$.trim(row.find('.item-uom').val()),mat=row.find('.material-select').val(),desc=$.trim(row.find('.item-desc').val());calcRow(row);if(qty>0&&uom&&(mat||desc))itemOk=true;});$('#btn_save_ciq').prop('disabled',!(ok&&itemOk));}
$(function(){
  if($.fn.datepicker){$('.ciq-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('.select-basic').select2({width:'100%'});
    $('#customer_id').select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_customer', 'Search customer...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=customer_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){var d=e.params.data;$('#phone').val($('#phone').val()||d.phone||'');$('#email').val($('#email').val()||d.email||'');validateCiq();});
    initMaterialSelect(document);
  }
  $('.item-date').datepicker && $('.item-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('#table_ciq_item tbody tr').each(function(){calcRow($(this));});renumberItems();validateCiq();
  $('#btn_add_item').on('click',function(){var tr=$('#table_ciq_item tbody tr:first').clone();tr.find('input').val('');tr.find('select').empty().removeAttr('data-select2-id').removeClass('select2-hidden-accessible').next('.select2').remove();$('#table_ciq_item tbody').append(tr);initMaterialSelect(tr);tr.find('.item-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});renumberItems();validateCiq();});
  $(document).on('click','.btn-remove-item',function(){if($('#table_ciq_item tbody tr').length>1)$(this).closest('tr').remove();else $(this).closest('tr').find('input').val('').end().find('select').val(null).trigger('change');renumberItems();validateCiq();});
  $(document).on('keyup change','#form_ciq input,#form_ciq textarea,#form_ciq select',validateCiq);
  $('#form_ciq').on('submit',function(e){e.preventDefault();validateCiq();if($('#btn_save_ciq').prop('disabled'))return;var btn=$('#btn_save_ciq');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=sd_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=<?= $isEdit ? 'update' : 'save'; ?>',$(this).serialize(),function(r){if(r.status==='good'){window.location='<?=base_index();?>customer-inquiry/detail/'+r.id;}else{alert(r.error_message||<?=sd_js('sales_customer_inquiry_save_failed', 'Customer Inquiry failed to save.');?>);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Inquiry');}},'json').fail(function(xhr){console.log(xhr.responseText);alert(<?=sd_js('sales_customer_inquiry_save_failed', 'Customer Inquiry failed to save.');?>);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Inquiry');});});
});
</script>
