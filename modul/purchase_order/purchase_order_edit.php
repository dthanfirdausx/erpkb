<?php
if (!$data_edit) {
  echo "<section class='content'><div class='alert alert-warning'>".lang_text('purchase_order_not_found','Purchase Order tidak ditemukan.')."</div></section>";
  return;
}
if (!function_exists('po_form_t')) {
  function po_form_t($key, $fallback = '') {
    return lang_text($key, $fallback);
  }
}
function po_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$poFormLang = array(
  'add_manual_item' => po_form_t('purchase_order_add_manual_item', 'Add Manual Item'),
  'material_placeholder' => po_form_t('purchase_order_material_placeholder', 'Material description'),
  'search_material' => po_form_t('purchase_order_search_material', 'Search material from material master'),
  'no_item_required' => po_form_t('purchase_order_no_item_required', 'At least one PO item is required.'),
  'update_failed' => po_form_t('purchase_order_update_failed', 'PO failed to update.'),
  'received' => po_form_t('purchase_order_received', 'Received'),
  'source_manual' => po_form_t('purchase_order_source_manual_exceptional', 'Manual / Exceptional'),
);
$details = array();
$detailRows = $db->query("SELECT * FROM purchase_order_detail WHERE po_no=? ORDER BY id", array('po_no'=>$data_edit->purchase_order_no));
foreach ($detailRows as $row) {
  $details[] = array(
    'id_po_detail'=>$row->id,
    'id_pr'=>$row->id_pr,
    'id_pr_detail'=>$row->id_pr_detail,
    'rfq_id'=>$row->rfq_id,
    'rfq_item_id'=>$row->rfq_item_id,
    'rfq_quotation_id'=>$row->rfq_quotation_id,
    'material_code'=>$row->kode_barang,
    'material_name'=>$row->nama_barang,
    'spec'=>$row->spec,
    'uom'=>$row->unit,
    'qty'=>$row->qty,
    'received_qty'=>$row->received_qty,
    'price'=>$row->harga,
    'remarks'=>$row->ket
  );
}
?>
<style>
  .po-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .po-hero h1{margin:0 0 6px;font-size:25px;font-weight:700}.po-hero p{margin:0;opacity:.9}.po-section{border-radius:10px}.required-label:after{content:' *';color:#dd4b39}.po-items{font-size:12px}.po-items th{white-space:nowrap;background:#f5f5f5}.po-items td{vertical-align:top!important}.po-items .form-control{height:30px;padding:4px 6px;font-size:12px;min-width:90px}.po-items .material-name-input{margin-top:4px}.select2-container{width:100%!important}.po-total-box{font-size:18px;font-weight:700}
</style>
<section class="content-header">
  <h1><?=po_h(po_form_t('purchase_order_title','Purchase Order'));?> <small><?=po_h(po_form_t('common_edit','Edit'));?> <?=htmlspecialchars($data_edit->purchase_order_no,ENT_QUOTES,'UTF-8');?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=po_h(po_form_t('common_home','Home'));?></a></li><li><a href="<?=base_index();?>purchase-order"><?=po_h(po_form_t('purchase_order_title','Purchase Order'));?></a></li><li class="active"><?=po_h(po_form_t('common_edit','Edit'));?></li></ol>
</section>
<section class="content">
  <div class="po-hero"><h1><?=po_h(po_form_t('purchase_order_edit_title','Edit Purchase Order'));?></h1><p><?=po_h(po_form_t('purchase_order_edit_intro','PO changes keep outstanding quantity safe so GR for PO remains consistent.'));?></p></div>
  <form id="edit_purchase_order" method="post" action="<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=up">
    <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>
    <div class="box box-primary po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> <?=po_h(po_form_t('purchase_order_header_data','Header Data'));?></h3></div><div class="box-body"><div class="row">
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_po_number','PO Number'));?></label><input type="text" name="purchase_order_no" class="form-control" value="<?=htmlspecialchars($data_edit->purchase_order_no,ENT_QUOTES,'UTF-8');?>" readonly></div>
      <div class="col-md-2 form-group"><label class="required-label"><?=po_h(po_form_t('purchase_order_po_date','PO Date'));?></label><input type="text" name="po_date" class="form-control date-field" value="<?=htmlspecialchars($data_edit->po_date,ENT_QUOTES,'UTF-8');?>" required></div>
      <div class="col-md-2 form-group"><label class="required-label"><?=po_h(po_form_t('purchase_order_delivery_date','Delivery Date'));?></label><input type="text" name="delivery_date" class="form-control date-field" value="<?=po_h($data_edit->delivery_date);?>" required></div>
      <div class="col-md-2 form-group"><label><?=po_h(po_form_t('purchase_order_arrival_date','Arrival Date'));?></label><input type="text" name="arrival_date" class="form-control date-field" value="<?=po_h($data_edit->arrival_date);?>"></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_po_type','PO Type'));?></label><select name="po_type" class="form-control select2-basic"><?php foreach(array('NB'=>po_form_t('purchase_order_standard_po','NB - Standard PO'),'FO'=>po_form_t('purchase_order_framework_po','FO - Framework PO'),'ZKB'=>po_form_t('purchase_order_bonded_zone_po','ZKB - Kawasan Berikat')) as $k=>$v){ ?><option value="<?=$k;?>" <?=$data_edit->po_type==$k?'selected':'';?>><?=po_h($v);?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_source_type','Source Type'));?></label><select name="source_type" id="source_type" class="form-control select2-basic"><?php foreach(array('RFQ'=>po_form_t('purchase_order_source_rfq_award','RFQ Award'),'PR'=>po_form_t('purchase_order_source_approved_pr','Approved PR'),'MANUAL'=>po_form_t('purchase_order_source_manual_exceptional','Manual / Exceptional')) as $k=>$v){ ?><option value="<?=$k;?>" <?=$data_edit->source_type==$k?'selected':'';?>><?=po_h($v);?></option><?php } ?></select></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_source_reference','Source Reference'));?></label><input type="text" name="source_ref" id="source_ref" class="form-control" value="<?=po_h($data_edit->source_ref);?>"></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_purchasing_org','Purchasing Org'));?></label><input type="text" name="purchasing_org" class="form-control" value="<?=po_h($data_edit->purchasing_org);?>"></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_purchasing_group','Purchasing Group'));?></label><input type="text" name="purchasing_group" class="form-control" value="<?=po_h($data_edit->purchasing_group);?>"></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('common_plant','Plant'));?></label><input type="text" name="plant" id="plant" class="form-control" value="<?=po_h($data_edit->plant);?>"></div>
      <div class="col-md-3 form-group"><label><?=po_h(po_form_t('purchase_order_storage_location','Storage Location'));?></label><select name="storage_location" id="storage_location" class="form-control select2-basic"><option value=""><?=po_h(po_form_t('purchase_order_select_storage_location','Select Storage Location'));?></option><?php foreach($db->query("SELECT s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code") as $sloc){ ?><option value="<?=po_h($sloc->storage_code);?>" data-plant="<?=po_h($sloc->plant_code);?>" <?=$data_edit->storage_location==$sloc->storage_code?'selected':'';?>><?=po_h($sloc->plant_code.' / '.$sloc->storage_code.' - '.$sloc->storage_name);?></option><?php } ?></select></div>
      <div class="col-md-2 form-group"><label class="required-label"><?=po_h(po_form_t('purchase_order_currency','Currency'));?></label><input type="text" name="currency" id="currency" class="form-control" value="<?=po_h($data_edit->currency);?>" required></div>
      <div class="col-md-2 form-group"><label><?=po_h(po_form_t('purchase_order_tax','Tax'));?></label><select name="tax" class="form-control"><option value="no" <?=$data_edit->pajak!='ya'?'selected':'';?>><?=po_h(po_form_t('purchase_order_no_tax','No Tax'));?></option><option value="ya" <?=$data_edit->pajak=='ya'?'selected':'';?>><?=po_h(po_form_t('purchase_order_include_ppn','Include PPN 11%'));?></option></select></div>
      <div class="col-md-4 form-group"><label><?=po_h(po_form_t('purchase_order_delivery_term','Delivery Term'));?></label><input type="text" name="delivery_term" class="form-control" value="<?=po_h($data_edit->delivery_term);?>"></div>
      <div class="col-md-4 form-group"><label><?=po_h(po_form_t('purchase_order_payment_term','Payment Term'));?></label><select name="payment_term" class="form-control select2-basic"><option value=""><?=po_h(po_form_t('purchase_order_select_payment_term','Select Payment Term'));?></option><?php foreach($db->query("SELECT jenis_term,net_day FROM term_payment ORDER BY net_day,jenis_term") as $term){ ?><option value="<?=po_h($term->jenis_term);?>" <?=$data_edit->payment_term==$term->jenis_term?'selected':'';?>><?=po_h($term->jenis_term.' - Net '.$term->net_day.' hari');?></option><?php } ?></select></div>
      <div class="col-md-4 form-group"><label><?=po_h(po_form_t('purchase_order_shipped_via','Shipped Via'));?></label><input type="text" name="shipped_via" class="form-control" value="<?=po_h($data_edit->shipped_via);?>"></div>
      <div class="col-md-8 form-group"><label><?=po_h(po_form_t('purchase_order_note','Note'));?></label><input type="text" name="catatan" class="form-control" value="<?=po_h($data_edit->catatan);?>"></div>
    </div></div></div>
    <div class="row">
      <div class="col-md-6"><div class="box box-success po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-truck"></i> <?=po_h(po_form_t('purchase_order_vendor','Vendor'));?></h3></div><div class="box-body">
        <div class="form-group"><label class="required-label"><?=po_h(po_form_t('purchase_order_vendor','Vendor'));?></label><select name="seller_code" id="seller_code" class="form-control" required><option value=""><?=po_h(po_form_t('purchase_order_select_vendor','Select Vendor'));?></option><?php foreach($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $v){ ?><option value="<?=po_h($v->kode_pemasok);?>" <?=$data_edit->seller_code==$v->kode_pemasok?'selected':'';?>><?=po_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select><input type="hidden" name="customer_id" id="customer_id" value="<?=po_h($data_edit->customer_id);?>"></div>
        <div class="form-group"><label><?=po_h(po_form_t('purchase_order_address','Address'));?></label><textarea name="seller_address" id="seller_address" class="form-control" rows="2"><?=po_h($data_edit->seller_address);?></textarea></div>
        <div class="row"><div class="col-sm-4 form-group"><label><?=po_h(po_form_t('purchase_order_phone','Phone'));?></label><input name="seller_phone" id="seller_phone" class="form-control" value="<?=po_h($data_edit->seller_phone);?>"></div><div class="col-sm-4 form-group"><label><?=po_h(po_form_t('purchase_order_pic','PIC'));?></label><input name="seller_pic" id="seller_pic" class="form-control" value="<?=po_h($data_edit->seller_pic);?>"></div><div class="col-sm-4 form-group"><label><?=po_h(po_form_t('purchase_order_email','Email'));?></label><input name="seller_email" id="seller_email" class="form-control" value="<?=po_h($data_edit->seller_email);?>"></div></div>
      </div></div></div>
      <div class="col-md-6"><div class="box box-info po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-map-marker"></i> <?=po_h(po_form_t('purchase_order_ship_to','Ship To'));?></h3></div><div class="box-body">
        <div class="form-group"><label><?=po_h(po_form_t('purchase_order_consignee','Consignee'));?></label><input name="consignee_name" class="form-control" value="<?=po_h($data_edit->consignee_name);?>"></div>
        <div class="form-group"><label><?=po_h(po_form_t('purchase_order_address','Address'));?></label><textarea name="consignee_address" class="form-control" rows="2"><?=po_h($data_edit->consignee_address);?></textarea></div>
        <div class="row"><div class="col-sm-6 form-group"><label><?=po_h(po_form_t('purchase_order_phone','Phone'));?></label><input name="consignee_phone" class="form-control" value="<?=po_h($data_edit->consignee_phone);?>"></div><div class="col-sm-6 form-group"><label><?=po_h(po_form_t('purchase_order_email','Email'));?></label><input name="consignee_email" class="form-control" value="<?=po_h($data_edit->consignee_email);?>"></div></div>
      </div></div></div>
    </div>
    <div class="box box-warning po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> <?=po_h(po_form_t('purchase_order_items','PO Items'));?></h3><div class="box-tools"><button type="button" id="btn_add_manual" class="btn btn-warning btn-sm"><i class="fa fa-plus"></i> <?=po_h(po_form_t('purchase_order_add_manual_item','Add Manual Item'));?></button></div></div><div class="box-body table-responsive">
      <table class="table table-bordered table-condensed po-items"><thead><tr><th></th><th><?=po_h(po_form_t('purchase_order_source','Source'));?></th><th><?=po_h(po_form_t('purchase_order_material','Material'));?></th><th><?=po_h(po_form_t('purchase_order_qty','Qty'));?></th><th><?=po_h(po_form_t('purchase_order_received','Received'));?></th><th><?=po_h(po_form_t('purchase_order_uom','UOM'));?></th><th><?=po_h(po_form_t('purchase_order_price','Price'));?></th><th><?=po_h(po_form_t('purchase_order_amount','Amount'));?></th><th><?=po_h(po_form_t('purchase_order_remark','Remark'));?></th></tr></thead><tbody id="isi_tabel"></tbody></table>
      <div class="text-right po-total-box"><?=po_h(po_form_t('purchase_order_total','Total'));?>: <span id="po_total">0,00</span></div>
    </div></div>
    <div class="text-right"><a href="<?=base_index();?>purchase-order" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=po_h(po_form_t('common_back','Back'));?></a> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=po_h(po_form_t('purchase_order_update','Update Purchase Order'));?></button></div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var existingItems=<?=json_encode($details);?>, rowIndex=0;
var poFormLang=<?=json_encode($poFormLang, JSON_UNESCAPED_UNICODE);?>;
function esc(v){return $('<div>').text(v==null?'':v).html();}function fmt(n){return(parseFloat(n)||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2});}
function refreshTotal(){var total=0;$('.amount-field').each(function(){total+=parseFloat($(this).val()||0)||0;});$('#po_total').text(fmt(total));}
function calculateRow(row){var qty=parseFloat(row.find('.qty-field').val()||0)||0,price=parseFloat(row.find('.price-field').val()||0)||0;row.find('.amount-field').val((qty*price).toFixed(2));refreshTotal();}
function isManualItem(item){return !!item.manual_item || !(item.id_pr_detail||item.rfq_item_id||item.rfq_quotation_id);}
function materialCell(item,received){var manual=isManualItem(item),selected=item.material_code?'<option value="'+esc(item.material_code)+'" selected>'+esc(item.material_code+' - '+(item.material_name||''))+'</option>':'';if(manual&&received<=0){return '<select class="form-control material-select" name="kode[]" required>'+selected+'</select><input class="form-control material-name-input" name="name[]" value="'+esc(item.material_name||'')+'" placeholder="'+esc(poFormLang.material_placeholder)+'" readonly required><input type="hidden" name="spec[]" value="'+esc(item.spec||'')+'">';}return '<input class="form-control" name="kode[]" value="'+esc(item.material_code||'')+'" readonly required><input class="form-control material-name-input" name="name[]" value="'+esc(item.material_name||'')+'" readonly required><input type="hidden" name="spec[]" value="'+esc(item.spec||'')+'">';}
function addPoRow(item){item=item||{};rowIndex++;var received=parseFloat(item.received_qty||0)||0,sourceText=isManualItem(item)?poFormLang.source_manual:($('#source_type').val()||'PO');var html='<tr id="po_row_'+rowIndex+'"><td><button type="button" class="btn btn-danger btn-xs btn-remove-row" '+(received>0?'disabled title="'+esc(poFormLang.received)+'"':'')+'><i class="fa fa-trash"></i></button><input type="hidden" name="id_po_detail[]" value="'+esc(item.id_po_detail||'')+'"><input type="hidden" name="id_pr[]" value="'+esc(item.id_pr||'')+'"><input type="hidden" name="id_pr_detail[]" value="'+esc(item.id_pr_detail||'')+'"><input type="hidden" name="rfq_id[]" value="'+esc(item.rfq_id||'')+'"><input type="hidden" name="rfq_item_id[]" value="'+esc(item.rfq_item_id||'')+'"><input type="hidden" name="rfq_quotation_id[]" value="'+esc(item.rfq_quotation_id||'')+'"></td><td><span class="label label-default">'+sourceText+'</span></td><td>'+materialCell(item,received)+'</td><td><input type="number" step="0.00001" min="'+received+'" class="form-control qty-field" name="qty[]" value="'+esc(item.qty||'')+'" required></td><td><input class="form-control" value="'+esc(received)+'" readonly></td><td><input class="form-control" name="unit[]" value="'+esc(item.uom||'')+'" required></td><td><input type="number" step="0.00001" min="0" class="form-control price-field" name="harga[]" value="'+esc(item.price||0)+'" required></td><td><input class="form-control amount-field" value="0" readonly></td><td><input class="form-control" name="ket[]" value="'+esc(item.remarks||'')+'"></td></tr>';$('#isi_tabel').append(html);initMaterialSelect($('#po_row_'+rowIndex));calculateRow($('#po_row_'+rowIndex));}
function initMaterialSelect(row){if(!$.fn.select2)return;row.find('.material-select').each(function(){var el=$(this);if(el.data('select2'))return;el.select2({placeholder:poFormLang.search_material,allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};},cache:true}});});}
function filterStorageLocation(){var plant=$('#plant').val();$('#storage_location option').each(function(){var p=$(this).data('plant');$(this).toggle(!p||!plant||String(p)===String(plant));});}
function loadVendor(code){$.post('<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=cari_vendor',{kode_pemasok:code},function(res){if(res&&res.success){$('#customer_id').val(res.data.kode_pemasok);$('#seller_address').val([res.data.alamat,res.data.kota,res.data.negara].filter(Boolean).join(', '));$('#seller_phone').val(res.data.notelp);$('#seller_email').val(res.data.email);}},'json');}
$(function(){if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}if($.fn.select2){$('.select2-basic,#seller_code').select2({width:'100%'});}filterStorageLocation();$('#plant').on('change input',filterStorageLocation);$.each(existingItems,function(_,item){addPoRow(item);});$('#seller_code').on('change',function(){loadVendor(this.value);});$('#btn_add_manual').on('click',function(){addPoRow({manual_item:true});});$(document).on('select2:select','.material-select',function(e){var d=e.params.data||{},row=$(this).closest('tr');row.find('input[name="name[]"]').val(d.material_name||'');row.find('input[name="spec[]"]').val(d.spec||'');row.find('input[name="unit[]"]').val(d.uom||'');});$(document).on('select2:clear','.material-select',function(){var row=$(this).closest('tr');row.find('input[name="name[]"],input[name="spec[]"],input[name="unit[]"]').val('');});$(document).on('input','.qty-field,.price-field',function(){calculateRow($(this).closest('tr'));});$(document).on('click','.btn-remove-row',function(){$(this).closest('tr').remove();refreshTotal();});$('#edit_purchase_order').on('submit',function(e){e.preventDefault();if($('#isi_tabel tr').length===0){$('.isi_warning').text(poFormLang.no_item_required);$('.error_data').show();return;}$.ajax({url:$(this).attr('action'),type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){window.location='<?=base_index();?>purchase-order';return;}$('.isi_warning').text(r.error_message||poFormLang.update_failed);$('.error_data').show();},error:function(xhr){$('.isi_warning').text(xhr.responseText);$('.error_data').show();}});});});
</script>
