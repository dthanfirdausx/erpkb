<?php
if (!function_exists('po_form_t')) {
  function po_form_t($key, $fallback = '') {
    return lang_text($key, $fallback);
  }
}
if (!function_exists('po_form_h')) {
  function po_form_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
$shipName = isset($infokb->nama) ? $infokb->nama : '';
$shipAddress = isset($infokb->alamat) ? $infokb->alamat : '';
$shipPhone = isset($infokb->telp) ? $infokb->telp : '';
$shipEmail = isset($infokb->email) ? $infokb->email : '';
$poFormLang = array(
  'add_source_item' => po_form_t('purchase_order_add_source_item', 'Add Source Item'),
  'add_manual_item' => po_form_t('purchase_order_add_manual_item', 'Add Manual Item'),
  'manual_ref_placeholder' => po_form_t('purchase_order_manual_ref_placeholder', 'Optional manual reference / reason'),
  'auto_source_placeholder' => po_form_t('purchase_order_auto_source_placeholder', 'Auto from selected source'),
  'material_placeholder' => po_form_t('purchase_order_material_placeholder', 'Material description'),
  'search_material' => po_form_t('purchase_order_search_material', 'Search material from material master'),
  'search_source_item' => po_form_t('purchase_order_search_source_item', 'Search source item'),
  'no_item_required' => po_form_t('purchase_order_no_item_required', 'At least one PO item is required.'),
  'save_failed' => po_form_t('purchase_order_save_failed', 'PO failed to save.'),
  'source_item_loading' => po_form_t('purchase_order_search_source_item', 'Search source item'),
  'source_rfq' => po_form_t('purchase_order_source_rfq_award', 'RFQ Award'),
  'source_pr' => po_form_t('purchase_order_source_approved_pr', 'Approved PR'),
  'source_manual' => po_form_t('purchase_order_source_manual_exceptional', 'Manual / Exceptional'),
);
?>
<style>
  .po-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .po-hero h1{margin:0 0 6px;font-size:25px;font-weight:700}.po-hero p{margin:0;opacity:.9}.po-section{border-radius:10px}.required-label:after{content:' *';color:#dd4b39}.po-items{font-size:12px}.po-items th{white-space:nowrap;background:#f5f5f5}.po-items td{vertical-align:top!important}.po-items .form-control{height:30px;padding:4px 6px;font-size:12px;min-width:90px}.po-items .material-name-input{margin-top:4px}.select2-container{width:100%!important}.po-total-box{font-size:18px;font-weight:700}
</style>
<section class="content-header">
  <h1><?=po_form_h(po_form_t('purchase_order_title','Purchase Order'));?> <small><?=po_form_h(po_form_t('purchase_order_create_subtitle','Create SAP MM PO'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=po_form_h(po_form_t('common_home','Home'));?></a></li>
    <li><a href="<?=base_index();?>purchase-order"><?=po_form_h(po_form_t('purchase_order_title','Purchase Order'));?></a></li>
    <li class="active"><?=po_form_h(po_form_t('common_create','Create'));?></li>
  </ol>
</section>
<section class="content">
  <div class="po-hero">
    <h1><?=po_form_h(po_form_t('purchase_order_create_title','Create Purchase Order'));?></h1>
    <p><?=po_form_h(po_form_t('purchase_order_create_intro','Create PO from RFQ award or approved PR. Data remains compatible with GR for PO and GR Blocked Stock.'));?></p>
  </div>
  <form id="input_purchase_order" method="post" action="<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=in">
    <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>
    <div class="box box-primary po-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> <?=po_form_h(po_form_t('purchase_order_header_data','Header Data'));?></h3></div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_po_number','PO Number'));?></label><input type="text" id="purchase_order_no" class="form-control" value="<?=generate_po_no(date('Y'),date('m'));?>" readonly></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=po_form_h(po_form_t('purchase_order_po_date','PO Date'));?></label><input type="text" name="po_date" id="po_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=po_form_h(po_form_t('purchase_order_delivery_date','Delivery Date'));?></label><input type="text" name="delivery_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label><?=po_form_h(po_form_t('purchase_order_arrival_date','Arrival Date'));?></label><input type="text" name="arrival_date" class="form-control date-field" value="<?=date('Y-m-d');?>"></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_po_type','PO Type'));?></label><select name="po_type" class="form-control select2-basic"><option value="NB"><?=po_form_h(po_form_t('purchase_order_standard_po','NB - Standard PO'));?></option><option value="FO"><?=po_form_h(po_form_t('purchase_order_framework_po','FO - Framework PO'));?></option><option value="ZKB"><?=po_form_h(po_form_t('purchase_order_bonded_zone_po','ZKB - Kawasan Berikat'));?></option></select></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_source_type','Source Type'));?></label><select name="source_type" id="source_type" class="form-control select2-basic"><option value="RFQ"><?=po_form_h(po_form_t('purchase_order_source_rfq_award','RFQ Award'));?></option><option value="PR"><?=po_form_h(po_form_t('purchase_order_source_approved_pr','Approved PR'));?></option><option value="MANUAL"><?=po_form_h(po_form_t('purchase_order_source_manual_exceptional','Manual / Exceptional'));?></option></select></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_source_reference','Source Reference'));?></label><input type="text" name="source_ref" id="source_ref" class="form-control" readonly></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_purchasing_org','Purchasing Org'));?></label><select name="purchasing_org" class="form-control select2-basic"><option value=""><?=po_form_h(po_form_t('purchase_order_select','Select'));?></option><?php foreach($db->query("SELECT org_code,org_name FROM erp_purchasing_organization WHERE status='Aktif' ORDER BY org_code") as $row){ ?><option value="<?=htmlspecialchars($row->org_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->org_code.' - '.$row->org_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_purchasing_group','Purchasing Group'));?></label><select name="purchasing_group" class="form-control select2-basic"><option value=""><?=po_form_h(po_form_t('purchase_order_select','Select'));?></option><?php foreach($db->query("SELECT group_code,group_name FROM erp_purchasing_group WHERE status='Aktif' ORDER BY group_code") as $row){ ?><option value="<?=htmlspecialchars($row->group_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->group_code.' - '.$row->group_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('common_plant','Plant'));?></label><select name="plant" id="plant" class="form-control select2-basic"><option value=""><?=po_form_h(po_form_t('purchase_order_select','Select'));?></option><?php foreach($db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $row){ ?><option value="<?=htmlspecialchars($row->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->plant_code.' - '.$row->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=po_form_h(po_form_t('purchase_order_storage_location','Storage Location'));?></label><select name="storage_location" id="storage_location" class="form-control select2-basic"><option value=""><?=po_form_h(po_form_t('purchase_order_select_storage_location','Select Storage Location'));?></option><?php foreach($db->query("SELECT s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code") as $sloc){ ?><option value="<?=htmlspecialchars($sloc->storage_code,ENT_QUOTES,'UTF-8');?>" data-plant="<?=htmlspecialchars($sloc->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($sloc->plant_code.' / '.$sloc->storage_code.' - '.$sloc->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=po_form_h(po_form_t('purchase_order_currency','Currency'));?></label><input type="text" name="currency" id="currency" class="form-control" value="IDR" required></div>
          <div class="col-md-2 form-group"><label><?=po_form_h(po_form_t('purchase_order_tax','Tax'));?></label><select name="tax" class="form-control"><option value="no"><?=po_form_h(po_form_t('purchase_order_no_tax','No Tax'));?></option><option value="ya"><?=po_form_h(po_form_t('purchase_order_include_ppn','Include PPN 11%'));?></option></select></div>
          <div class="col-md-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_delivery_term','Delivery Term'));?></label><input type="text" name="delivery_term" class="form-control" placeholder="FOB / CIF / DDP"></div>
          <div class="col-md-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_payment_term','Payment Term'));?></label><select name="payment_term" class="form-control select2-basic"><option value=""><?=po_form_h(po_form_t('purchase_order_select_payment_term','Select Payment Term'));?></option><?php foreach($db->query("SELECT jenis_term,net_day FROM term_payment ORDER BY net_day,jenis_term") as $term){ ?><option value="<?=htmlspecialchars($term->jenis_term,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($term->jenis_term.' - Net '.$term->net_day.' hari',ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_shipped_via','Shipped Via'));?></label><input type="text" name="shipped_via" class="form-control"></div>
          <div class="col-md-8 form-group"><label><?=po_form_h(po_form_t('purchase_order_note','Note'));?></label><input type="text" name="catatan" class="form-control" placeholder="<?=po_form_h(po_form_t('purchase_order_note','Note'));?>"></div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="box box-success po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-truck"></i> <?=po_form_h(po_form_t('purchase_order_vendor','Vendor'));?></h3></div><div class="box-body">
          <div class="form-group"><label class="required-label"><?=po_form_h(po_form_t('purchase_order_vendor','Vendor'));?></label><select name="seller_code" id="seller_code" class="form-control" required><option value=""><?=po_form_h(po_form_t('purchase_order_select_vendor','Select Vendor'));?></option><?php foreach($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $v){ ?><option value="<?=htmlspecialchars($v->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($v->kode_pemasok.' - '.$v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select><input type="hidden" name="customer_id" id="customer_id"></div>
          <div class="form-group"><label><?=po_form_h(po_form_t('purchase_order_address','Address'));?></label><textarea name="seller_address" id="seller_address" class="form-control" rows="2"></textarea></div>
          <div class="row"><div class="col-sm-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_phone','Phone'));?></label><input name="seller_phone" id="seller_phone" class="form-control"></div><div class="col-sm-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_pic','PIC'));?></label><input name="seller_pic" id="seller_pic" class="form-control"></div><div class="col-sm-4 form-group"><label><?=po_form_h(po_form_t('purchase_order_email','Email'));?></label><input name="seller_email" id="seller_email" class="form-control"></div></div>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="box box-info po-section"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-map-marker"></i> <?=po_form_h(po_form_t('purchase_order_ship_to','Ship To'));?></h3></div><div class="box-body">
          <div class="form-group"><label><?=po_form_h(po_form_t('purchase_order_consignee','Consignee'));?></label><input name="consignee_name" class="form-control" value="<?=htmlspecialchars($shipName,ENT_QUOTES,'UTF-8');?>"></div>
          <div class="form-group"><label><?=po_form_h(po_form_t('purchase_order_address','Address'));?></label><textarea name="consignee_address" class="form-control" rows="2"><?=htmlspecialchars($shipAddress,ENT_QUOTES,'UTF-8');?></textarea></div>
          <div class="row"><div class="col-sm-6 form-group"><label><?=po_form_h(po_form_t('purchase_order_phone','Phone'));?></label><input name="consignee_phone" class="form-control" value="<?=htmlspecialchars($shipPhone,ENT_QUOTES,'UTF-8');?>"></div><div class="col-sm-6 form-group"><label><?=po_form_h(po_form_t('purchase_order_email','Email'));?></label><input name="consignee_email" class="form-control" value="<?=htmlspecialchars($shipEmail,ENT_QUOTES,'UTF-8');?>"></div></div>
        </div></div>
      </div>
    </div>
    <div class="box box-warning po-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> <?=po_form_h(po_form_t('purchase_order_items','PO Items'));?></h3><div class="box-tools"><button type="button" id="btn_add_source" class="btn btn-warning btn-sm"><i class="fa fa-plus"></i> <?=po_form_h(po_form_t('purchase_order_add_source_item','Add Source Item'));?></button></div></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-condensed po-items"><thead><tr><th></th><th><?=po_form_h(po_form_t('purchase_order_source_item','Source Item'));?></th><th><?=po_form_h(po_form_t('purchase_order_material','Material'));?></th><th><?=po_form_h(po_form_t('purchase_order_qty','Qty'));?></th><th><?=po_form_h(po_form_t('purchase_order_uom','UOM'));?></th><th><?=po_form_h(po_form_t('purchase_order_price','Price'));?></th><th><?=po_form_h(po_form_t('purchase_order_amount','Amount'));?></th><th><?=po_form_h(po_form_t('purchase_order_required_date','Req. Date'));?></th><th><?=po_form_h(po_form_t('purchase_order_remark','Remark'));?></th></tr></thead><tbody id="isi_tabel"></tbody></table>
        <div class="text-right po-total-box"><?=po_form_h(po_form_t('purchase_order_total','Total'));?>: <span id="po_total">0,00</span></div>
      </div>
    </div>
    <div class="text-right"><a href="<?=base_index();?>purchase-order" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=po_form_h(po_form_t('common_back','Back'));?></a> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=po_form_h(po_form_t('purchase_order_save','Save Purchase Order'));?></button></div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var rowIndex=0;
var poFormLang=<?=json_encode($poFormLang, JSON_UNESCAPED_UNICODE);?>;
function esc(v){return $('<div>').text(v==null?'':v).html();}
function fmt(n){return (parseFloat(n)||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2});}
function refreshTotal(){var total=0;$('.amount-field').each(function(){total+=parseFloat($(this).val()||0)||0;});$('#po_total').text(fmt(total));}
function setVendor(code){if(!code)return;$('#seller_code').val(code).trigger('change');}
function getSourceType(){return ($('#source_type').val()||'MANUAL').toUpperCase();}
function sourceLabel(type){if(type==='PR')return poFormLang.source_pr;if(type==='RFQ')return poFormLang.source_rfq;return poFormLang.source_manual;}
function refreshSourceMode(){var type=getSourceType(),isManual=type==='MANUAL';$('#btn_add_source').html('<i class="fa fa-plus"></i> '+(isManual?poFormLang.add_manual_item:poFormLang.add_source_item));$('#source_ref').prop('readonly',!isManual).attr('placeholder',isManual?poFormLang.manual_ref_placeholder:poFormLang.auto_source_placeholder);if(isManual&&$('#source_ref').val()===poFormLang.source_item_loading)$('#source_ref').val('');}
function materialCell(item,isManual){if(isManual){var selected=item.material_code?'<option value="'+esc(item.material_code)+'" selected>'+esc(item.material_code+' - '+(item.material_name||''))+'</option>':'';return '<select class="form-control material-select" name="kode[]" required>'+selected+'</select><input class="form-control material-name-input" name="name[]" value="'+esc(item.material_name||'')+'" placeholder="'+esc(poFormLang.material_placeholder)+'" readonly required><input type="hidden" name="spec[]" value="'+esc(item.spec||'')+'">';}return '<input class="form-control" name="kode[]" value="'+esc(item.material_code||'')+'" readonly required><input class="form-control material-name-input" name="name[]" value="'+esc(item.material_name||'')+'" readonly required><input type="hidden" name="spec[]" value="'+esc(item.spec||'')+'">';}
function addPoRow(item){item=item||{};rowIndex++;var type=getSourceType();var isManual=type==='MANUAL';var sourceText=item.source_ref?item.source_ref:sourceLabel(type);var sourceCell=isManual?'<span class="label label-default">'+esc(sourceText)+'</span>':'<select class="form-control source-select"><option value="'+esc(item.id||'')+'" selected>'+esc(sourceText)+'</option></select>';var html='<tr id="po_row_'+rowIndex+'">'+
'<td><button type="button" class="btn btn-danger btn-xs btn-remove-row"><i class="fa fa-trash"></i></button><input type="hidden" name="id_po_detail[]" value=""><input type="hidden" name="id_pr[]" value="'+esc(item.id_pr||'')+'"><input type="hidden" name="id_pr_detail[]" value="'+esc(item.id_pr_detail||'')+'"><input type="hidden" name="rfq_id[]" value="'+esc(item.rfq_id||'')+'"><input type="hidden" name="rfq_item_id[]" value="'+esc(item.rfq_item_id||'')+'"><input type="hidden" name="rfq_quotation_id[]" value="'+esc(item.rfq_quotation_id||'')+'"></td>'+
'<td>'+sourceCell+'</td>'+
'<td>'+materialCell(item,isManual)+'</td>'+
'<td><input type="number" step="0.00001" min="0.00001" class="form-control qty-field" name="qty[]" value="'+esc(item.qty||'')+'" required></td>'+
'<td><input class="form-control" name="unit[]" value="'+esc(item.uom||'')+'" required></td>'+
'<td><input type="number" step="0.00001" min="0" class="form-control price-field" name="harga[]" value="'+esc(item.price||0)+'" required></td>'+
'<td><input class="form-control amount-field" value="0" readonly></td>'+
'<td><input class="form-control" value="'+esc(item.required_date||'')+'" readonly></td>'+
'<td><input class="form-control" name="ket[]" value="'+esc(item.remarks||'')+'"></td></tr>';$('#isi_tabel').append(html);calculateRow($('#po_row_'+rowIndex));}
function calculateRow(row){var qty=parseFloat(row.find('.qty-field').val()||0)||0,price=parseFloat(row.find('.price-field').val()||0)||0;row.find('.amount-field').val((qty*price).toFixed(2));refreshTotal();}
function initMaterialSelect(row){if(!$.fn.select2)return;row.find('.material-select').select2({placeholder:poFormLang.search_material,allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};},cache:true}});}
function initSourceSelect(row){var type=getSourceType();if(type==='MANUAL'){initMaterialSelect(row);return;}row.find('.source-select').select2({placeholder:poFormLang.search_source_item,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act='+(type==='PR'?'pr_item_search':'rfq_award_search'),type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
function filterStorageLocation(){var plant=$('#plant').val();$('#storage_location option').each(function(){var p=$(this).data('plant');$(this).toggle(!p||!plant||String(p)===String(plant));});var selected=$('#storage_location option:selected');if(plant&&selected.length&&selected.data('plant')&&String(selected.data('plant'))!==String(plant)){$('#storage_location').val('').trigger('change.select2');}}
function loadVendor(code){$.post('<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=cari_vendor',{kode_pemasok:code},function(res){if(res&&res.success){$('#customer_id').val(res.data.kode_pemasok);$('#seller_address').val([res.data.alamat,res.data.kota,res.data.negara].filter(Boolean).join(', '));$('#seller_phone').val(res.data.notelp);$('#seller_email').val(res.data.email);}},'json');}
$(function(){if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}if($.fn.select2){$('.select2-basic,#seller_code').select2({width:'100%'});}refreshSourceMode();filterStorageLocation();$('#plant').on('change',filterStorageLocation);$('#source_type').on('change',refreshSourceMode);$('#po_date').on('change',function(){$.post('<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=ganti_no_po',{tgl:this.value},function(res){$('#purchase_order_no').val(res);});});$('#seller_code').on('change',function(){loadVendor(this.value);});$('#btn_add_source').on('click',function(){var isManual=getSourceType()==='MANUAL';addPoRow(isManual?{}:{source_ref:poFormLang.source_item_loading});initSourceSelect($('#isi_tabel tr:last'));});$(document).on('select2:select','.material-select',function(e){var d=e.params.data||{},row=$(this).closest('tr');row.find('input[name="name[]"]').val(d.material_name||'');row.find('input[name="spec[]"]').val(d.spec||'');row.find('input[name="unit[]"]').val(d.uom||'');});$(document).on('select2:clear','.material-select',function(){var row=$(this).closest('tr');row.find('input[name="name[]"],input[name="spec[]"],input[name="unit[]"]').val('');});$(document).on('select2:select','.source-select',function(e){var d=e.params.data,row=$(this).closest('tr');row.find('input[name="id_pr[]"]').val(d.id_pr||'');row.find('input[name="id_pr_detail[]"]').val(d.id_pr_detail||'');row.find('input[name="rfq_id[]"]').val(d.rfq_id||'');row.find('input[name="rfq_item_id[]"]').val(d.rfq_item_id||'');row.find('input[name="rfq_quotation_id[]"]').val(d.rfq_quotation_id||'');row.find('input[name="kode[]"]').val(d.material_code||'');row.find('input[name="name[]"]').val(d.material_name||'');row.find('.qty-field').val(d.qty||'');row.find('input[name="unit[]"]').val(d.uom||'');row.find('.price-field').val(d.price||0);row.find('td:eq(7) input').val(d.required_date||'');$('#source_ref').val(d.source_ref||'');if(d.currency)$('#currency').val(d.currency);if(d.plant)$('#plant').val(d.plant).trigger('change');if(d.storage_location)$('#storage_location').val(d.storage_location).trigger('change.select2');if(d.vendor_code)setVendor(d.vendor_code);calculateRow(row);});$(document).on('input','.qty-field,.price-field',function(){calculateRow($(this).closest('tr'));});$(document).on('click','.btn-remove-row',function(){$(this).closest('tr').remove();refreshTotal();});$('#input_purchase_order').on('submit',function(e){e.preventDefault();if($('#isi_tabel tr').length===0){$('.isi_warning').text(poFormLang.no_item_required);$('.error_data').show();return;}$.ajax({url:$(this).attr('action'),type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){window.location='<?=base_index();?>purchase-order';return;}$('.isi_warning').text(r.error_message||poFormLang.save_failed);$('.error_data').show();},error:function(xhr){$('.isi_warning').text(xhr.responseText);$('.error_data').show();}});});});
</script>
