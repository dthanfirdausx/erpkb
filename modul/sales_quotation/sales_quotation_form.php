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
include_once "sales_quotation_lib.php";
$isEdit = isset($data_edit) && $data_edit;
$record = $isEdit ? $data_edit : null;
$details = $isEdit ? iterator_to_array(sq_detail_rows($db, $record->id_quotation)) : array();
if (!$details) $details = array((object)array('kd_barang'=>'','nm_barang'=>'','qty'=>'','uom'=>'','price'=>'','discount_percent'=>'','tax_percent'=>'','nilai'=>'','requested_delivery_date'=>'','ket'=>''));
?>
<style>
.sq-form-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:18px}
.sq-form-hero h1{margin:0;font-size:24px}.sq-form-hero p{margin:6px 0 0;opacity:.92}
.sq-item-table th,.sq-item-table td{font-size:12px;vertical-align:middle}.sq-item-table .form-control{font-size:12px;height:31px;padding:4px 7px}
.select2-container{width:100%!important}.sq-required:after{content:" *";color:#dc2626}
</style>
<section class="content-header">
  <h1><?= $isEdit ? 'Edit' : 'Add'; ?> <?=sd_h('sales_quotation', 'Sales Quotation');?> <small>SAP SD</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>sales-quotation"><?=sd_h('sales_quotation', 'Sales Quotation');?></a></li><li class="active"><?= $isEdit ? 'Edit' : 'Add'; ?></li></ol>
</section>
<section class="content">
  <div class="sq-form-hero"><h1><?= $isEdit ? sq_h($record->no_sales_quotation) : 'New Sales Quotation'; ?></h1><p>Quotation resmi untuk customer, dapat dibuat dari inquiry atau langsung manual. Nilai item otomatis dari qty, price, discount, dan tax.</p></div>
  <form id="form_sq" class="form-horizontal">
    <?php if ($isEdit) { ?><input type="hidden" name="id" value="<?=intval($record->id_quotation);?>"><?php } ?>
    <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Header Quotation</h3></div><div class="box-body">
      <div class="form-group">
        <label class="control-label col-lg-2">Reference Inquiry</label><div class="col-lg-4"><select id="inquiry_id" name="inquiry_id" class="form-control"><?php if($record && $record->inquiry_id){ ?><option value="<?=intval($record->inquiry_id);?>" selected>Inquiry #<?=intval($record->inquiry_id);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2 sq-required">Quotation Date</label><div class="col-lg-2"><div class="input-group date sq-date"><input name="tgl" class="form-control required-sq" value="<?=sq_h($record ? $record->tgl : date('Y-m-d'));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><button type="button" id="btn_load_inquiry" class="btn btn-info btn-block"><i class="fa fa-download"></i> Load Inquiry</button></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Valid Until</label><div class="col-lg-2"><div class="input-group date sq-date"><input name="valid_date" class="form-control" value="<?=sq_h($record ? $record->valid_date : date('Y-m-d', strtotime('+14 days')));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-2">Req. Delivery</label><div class="col-lg-2"><div class="input-group date sq-date"><input name="requested_delivery_date" id="requested_delivery_date" class="form-control" value="<?=sq_h($record ? $record->requested_delivery_date : '');?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('common_status', 'Status');?></label><div class="col-lg-3"><select name="status" class="form-control select-basic"><?php foreach(array('OPEN','SENT','ACCEPTED','REJECTED','EXPIRED','CANCELLED') as $s){ ?><option value="<?=$s;?>" <?=($record && $record->status===$s) || (!$record && $s==='OPEN') ? 'selected' : '';?>><?=$s;?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2 sq-required"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-4"><select id="customer_id" name="customer_id" class="form-control required-sq"><?php if($record && $record->customer_id){ ?><option value="<?=intval($record->customer_id);?>" selected><?=sq_h(trim((string)$record->kode_penerima.' - '.(string)$record->customer_name,' -'));?></option><?php } ?></select></div>
        <label class="control-label col-lg-2">Contact</label><div class="col-lg-2"><input name="contact_person" class="form-control" value="<?=sq_h($record ? $record->contact_person : '');?>"></div>
        <div class="col-lg-2"><input id="phone" class="form-control" placeholder="Phone customer" readonly></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2 sq-required">Subject</label><div class="col-lg-6"><input name="subject" id="subject" class="form-control required-sq" value="<?=sq_h($record ? $record->subject : '');?>" placeholder="Contoh: Penawaran material untuk customer/project"></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_currency', 'Currency');?></label><div class="col-lg-3"><input name="currency" id="currency" class="form-control" value="<?=sq_h($record ? $record->currency : 'IDR');?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Sales Person</label><div class="col-lg-2"><input name="sales_id" class="form-control" value="<?=sq_h($record ? $record->sales_id : sq_username());?>"></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_tax', 'Tax');?></label><div class="col-lg-2"><select name="tax" class="form-control select-basic"><option value="EXCLUDE" <?=(!$record || strtoupper($record->tax)==='EXCLUDE')?'selected':'';?>>Exclude</option><option value="INCLUDE" <?=($record && strtoupper($record->tax)==='INCLUDE')?'selected':'';?>>Include</option><option value="NON" <?=($record && strtoupper($record->tax)==='NON')?'selected':'';?>>Non PPN</option></select></div>
        <label class="control-label col-lg-2"><?=sd_h('sales_payment_term', 'Payment Term');?></label><div class="col-lg-3"><input name="payment_term" id="payment_term" class="form-control" value="<?=sq_h($record ? ($record->payment_term ?: $record->term) : '');?>"></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Incoterm</label><div class="col-lg-2"><input name="incoterm" class="form-control" value="<?=sq_h($record ? $record->incoterm : '');?>"></div>
        <label class="control-label col-lg-1">Rate</label><div class="col-lg-2"><input name="rupiah_rate" class="form-control text-right" value="<?=sq_h($record ? $record->rupiah_rate : '1');?>"></div>
        <label class="control-label col-lg-2">Rate Sale</label><div class="col-lg-3"><input name="rupiah_rate_sale" class="form-control text-right" value="<?=sq_h($record ? $record->rupiah_rate_sale : '1');?>"></div>
      </div>
      <div class="form-group"><label class="control-label col-lg-2"><?=sd_h('common_remarks', 'Remarks');?></label><div class="col-lg-10"><textarea name="catatan" class="form-control" rows="3"><?=sq_h($record ? $record->catatan : '');?></textarea></div></div>
    </div></div>

    <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Quotation Items</h3><div class="box-tools"><button type="button" class="btn btn-primary btn-sm" id="btn_add_item"><i class="fa fa-plus"></i> Add Item</button></div></div><div class="box-body">
      <div class="table-responsive"><table class="table table-bordered table-condensed sq-item-table" id="table_sq_item"><thead><tr><th style="width:42px">#</th><th style="min-width:260px"><?=sd_h('sales_material', 'Material');?></th><th style="width:110px"><?=sd_h('sales_qty', 'Qty');?></th><th style="width:90px"><?=sd_h('sales_uom', 'UOM');?></th><th style="width:130px"><?=sd_h('sales_price', 'Price');?></th><th style="width:95px">Disc %</th><th style="width:95px">Tax %</th><th style="width:140px"><?=sd_h('sales_amount', 'Amount');?></th><th style="width:140px">Delivery</th><th style="min-width:180px"><?=sd_h('common_remarks', 'Remarks');?></th><th style="width:48px"></th></tr></thead><tbody>
        <?php foreach($details as $d){ ?>
        <tr>
          <td class="line-no text-center"></td>
          <td><select name="kd_barang[]" class="form-control material-select"><?php if($d->kd_barang){ ?><option value="<?=sq_h($d->kd_barang);?>" selected><?=sq_h($d->kd_barang.' - '.$d->nm_barang);?></option><?php } ?></select></td>
          <td><input name="qty[]" class="form-control text-right item-qty" value="<?=sq_h($d->qty);?>"></td>
          <td><input name="uom[]" class="form-control item-uom" value="<?=sq_h($d->uom ?: $d->satuan);?>"></td>
          <td><input name="price[]" class="form-control text-right item-price" value="<?=sq_h($d->price);?>"></td>
          <td><input name="discount_percent[]" class="form-control text-right item-disc" value="<?=sq_h($d->discount_percent);?>"></td>
          <td><input name="tax_percent[]" class="form-control text-right item-tax" value="<?=sq_h($d->tax_percent);?>"></td>
          <td><input class="form-control text-right item-amount" readonly></td>
          <td><input name="item_delivery_date[]" class="form-control item-date" value="<?=sq_h($d->requested_delivery_date);?>"></td>
          <td><input name="ket[]" class="form-control" value="<?=sq_h($d->ket);?>"></td>
          <td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-item"><i class="fa fa-trash"></i></button></td>
        </tr>
        <?php } ?>
      </tbody></table></div>
      <div class="alert alert-info"><strong>Mandatory item:</strong> material, qty lebih dari 0, UOM. Price boleh 0 jika quotation masih tahap draft.</div>
    </div></div>
    <div class="box"><div class="box-footer text-right"><a href="<?=base_index();?>sales-quotation" class="btn btn-default"><i class="fa fa-step-backward"></i> Back</a> <button type="submit" id="btn_save_sq" class="btn btn-primary" disabled><i class="fa fa-save"></i> Save Quotation</button></div></div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function initSqMaterial(ctx){$(ctx).find('.material-select').each(function(){var el=$(this);if(el.data('select2'))return;el.select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_material', 'Search material...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){var row=el.closest('tr'),data=e.params.data;row.find('.item-uom').val(data.uom||'');validateSq();});});}
function renumberSq(){$('#table_sq_item tbody tr').each(function(i){$(this).find('.line-no').text(i+1);});}
function calcSqRow(row){var q=parseFloat(String(row.find('.item-qty').val()||'0').replace(',','.'))||0,p=parseFloat(String(row.find('.item-price').val()||'0').replace(',','.'))||0,d=parseFloat(String(row.find('.item-disc').val()||'0').replace(',','.'))||0,t=parseFloat(String(row.find('.item-tax').val()||'0').replace(',','.'))||0,g=q*p,a=g-(g*d/100),n=a+(a*t/100);row.find('.item-amount').val(n.toFixed(2));}
function validateSq(){var ok=true;$('.required-sq').each(function(){if(!$(this).val())ok=false;});var itemOk=false;$('#table_sq_item tbody tr').each(function(){var row=$(this),qty=parseFloat(String(row.find('.item-qty').val()||'0').replace(',','.'))||0,uom=$.trim(row.find('.item-uom').val()),mat=row.find('.material-select').val();calcSqRow(row);if(qty>0&&uom&&mat)itemOk=true;});$('#btn_save_sq').prop('disabled',!(ok&&itemOk));}
function appendSqItem(item){var tr=$('#table_sq_item tbody tr:first').clone();tr.find('input').val('');tr.find('select').empty().removeAttr('data-select2-id').removeClass('select2-hidden-accessible').next('.select2').remove();$('#table_sq_item tbody').append(tr);initSqMaterial(tr);tr.find('.item-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});if(item){var opt=new Option((item.material_code||'')+' - '+(item.material_name||''),item.material_code,true,true);tr.find('.material-select').append(opt).trigger('change');tr.find('.item-qty').val(item.qty||'');tr.find('.item-uom').val(item.uom||'');tr.find('.item-price').val(item.target_price||'0');tr.find('.item-date').val(item.requested_delivery_date||'');tr.find('input[name="ket[]"]').val(item.remarks||item.description||'');}renumberSq();validateSq();}
$(function(){
  if($.fn.datepicker){$('.sq-date,.item-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('.select-basic').select2({width:'100%'});
    $('#customer_id').select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_customer', 'Search customer...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=customer_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){$('#phone').val(e.params.data.phone||'');validateSq();});
    $('#inquiry_id').select2({width:'100%',allowClear:true,placeholder:'Cari inquiry...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=inquiry_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){var d=e.params.data;$('#subject').val($('#subject').val()||d.subject||'');$('#requested_delivery_date').val($('#requested_delivery_date').val()||d.requested_delivery_date||'');$('#currency').val(d.currency||$('#currency').val());$('#payment_term').val($('#payment_term').val()||d.payment_term||'');if(d.customer_id){var opt=new Option(d.customer_text||('Customer #'+d.customer_id),d.customer_id,true,true);$('#customer_id').append(opt).trigger('change');}validateSq();});
    initSqMaterial(document);
  }
  $('#table_sq_item tbody tr').each(function(){calcSqRow($(this));});renumberSq();validateSq();
  $('#btn_add_item').on('click',function(){appendSqItem(null);});
  $('#btn_load_inquiry').on('click',function(){var id=$('#inquiry_id').val();if(!id){alert('Pilih inquiry terlebih dahulu.');return;}$.post('<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=inquiry_items',{inquiry_id:id},function(r){if(!r.items||!r.items.length){alert('Inquiry tidak memiliki item.');return;}$('#table_sq_item tbody').empty();$.each(r.items,function(_,it){appendSqItem(it);});},'json');});
  $(document).on('click','.btn-remove-item',function(){if($('#table_sq_item tbody tr').length>1)$(this).closest('tr').remove();else $(this).closest('tr').find('input').val('').end().find('select').val(null).trigger('change');renumberSq();validateSq();});
  $(document).on('keyup change','#form_sq input,#form_sq textarea,#form_sq select',validateSq);
  $('#form_sq').on('submit',function(e){e.preventDefault();validateSq();if($('#btn_save_sq').prop('disabled'))return;var btn=$('#btn_save_sq');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=sd_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=<?= $isEdit ? 'update' : 'save'; ?>',$(this).serialize(),function(r){if(r.status==='good'){window.location='<?=base_index();?>sales-quotation/detail/'+r.id;}else{alert(r.error_message||<?=sd_js('sales_quotation_save_failed', 'Sales Quotation failed to save.');?>);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Quotation');}},'json').fail(function(xhr){console.log(xhr.responseText);alert(<?=sd_js('sales_quotation_save_failed', 'Sales Quotation failed to save.');?>);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Quotation');});});
});
</script>
