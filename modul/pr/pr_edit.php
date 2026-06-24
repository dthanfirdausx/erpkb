<?php
if (!function_exists('pr_form_t')) {
  function pr_form_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('pr_form_h')) {
  function pr_form_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (!$data_edit) {
  echo "<section class='content'><div class='alert alert-warning'>".pr_form_h(pr_form_t('purchase_requisition_not_found','Purchase Requisition tidak ditemukan.'))."</div></section>";
  return;
}
$detailRows = $db->query("SELECT * FROM purchase_requisition_detail WHERE id_pr=? ORDER BY line_no", array('id_pr' => $data_edit->id_pr));
$details = array();
foreach ($detailRows as $row) {
  $details[] = array(
    'id_pr_detail' => $row->id_pr_detail,
    'line_no' => $row->line_no,
    'material_code' => $row->material_code,
    'material_name' => $row->material_name,
    'material_group' => $row->material_group,
    'kd_kategori' => $row->kd_kategori,
    'qty' => $row->qty,
    'qty_po' => $row->qty_po,
    'uom' => $row->uom,
    'required_date' => $row->required_date,
    'plant' => $row->plant,
    'storage_location' => $row->storage_location,
    'valuation_price' => $row->valuation_price,
    'currency' => $row->currency,
    'account_assignment' => $row->account_assignment,
    'cost_center' => $row->cost_center,
    'suggested_vendor' => $row->suggested_vendor,
    'tracking_no' => $row->tracking_no,
    'remarks' => $row->remarks
  );
}
$prFormLang = array(
  'select' => pr_form_t('purchase_requisition_select', 'Pilih'),
  'searchMaterial' => pr_form_t('purchase_requisition_search_material', 'Cari Material'),
  'completeRequired' => pr_form_t('purchase_requisition_complete_required', 'Lengkapi field mandatory dan minimal satu item.'),
  'saving' => pr_form_t('purchase_requisition_saving', 'Menyimpan...'),
  'updateFailed' => pr_form_t('purchase_requisition_update_failed', 'Purchase Requisition gagal diupdate.'),
  'serverUpdateError' => pr_form_t('purchase_requisition_server_update_error', 'Server error saat update Purchase Requisition.'),
  'itemHasPo' => pr_form_t('purchase_requisition_item_has_po', 'Item sudah punya PO'),
);
?>
<link rel="stylesheet" href="<?=base_url();?>assets/css/jquery-ui.css">
<style>
  .pr-section{margin-bottom:18px}.required-label:after{content:' *';color:#dd4b39}.pr-items{font-size:12px}.pr-items th{white-space:nowrap;background:#f5f5f5;vertical-align:middle!important}.pr-items td{vertical-align:top!important}.pr-items .form-control{min-width:90px;height:30px;padding:4px 6px;font-size:12px}.pr-items .material-select{min-width:280px}.pr-required-missing{border-color:#dd4b39!important;background:#fff8f8!important}.pr-submit-help{display:inline-block;margin-right:10px;color:#dd4b39;font-size:12px}.select2-container{width:100%!important}
</style>

<section class="content-header">
  <h1><?=pr_form_h(pr_form_t('purchase_requisition_title','Purchase Requisition'));?> <small><?=pr_form_h(pr_form_t('common_edit','Edit'));?> <?=htmlspecialchars($data_edit->no_pr,ENT_QUOTES,'UTF-8');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=pr_form_h(pr_form_t('common_home','Home'));?></a></li>
    <li><a href="<?=base_index();?>pr"><?=pr_form_h(pr_form_t('purchase_requisition_title','Purchase Requisition'));?></a></li>
    <li class="active"><?=pr_form_h(pr_form_t('common_edit','Edit'));?></li>
  </ol>
</section>

<section class="content">
  <?php if (in_array($data_edit->status, array('CONVERTED_PO','CLOSED','CANCELLED'))) { ?>
    <div class="alert alert-warning"><?=pr_form_h(str_replace('{status}', $data_edit->status, pr_form_t('purchase_requisition_cannot_edit_status','Status PR {status} tidak bisa diedit.')));?></div>
  <?php } ?>
  <form id="input_pr" method="post" action="<?=base_admin();?>modul/pr/pr_action.php?act=up">
    <input type="hidden" name="id_pr" value="<?=intval($data_edit->id_pr);?>">
    <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>

    <div class="box box-primary pr-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> <?=pr_form_h(pr_form_t('purchase_requisition_header_data','Header Data'));?></h3></div>
      <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label><?=pr_form_h(pr_form_t('purchase_requisition_no_pr','No PR'));?></label><input class="form-control" value="<?=htmlspecialchars($data_edit->no_pr,ENT_QUOTES,'UTF-8');?>" readonly></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_pr_date','PR Date'));?></label><input type="text" name="tgl_pr" class="form-control date-field" value="<?=htmlspecialchars($data_edit->tgl_pr,ENT_QUOTES,'UTF-8');?>" required></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_document_type','Document Type'));?></label><select name="document_type" class="form-control" required><?php foreach(array('NB'=>'NB - Standard Purchase Requisition','FO'=>'FO - Framework Requisition','RV'=>'RV - Stock Transfer Requisition','ZKB'=>'ZKB - Kawasan Berikat') as $k=>$v){ ?><option value="<?=$k;?>" <?=$data_edit->document_type==$k?'selected':'';?>><?=$v;?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_priority','Priority'));?></label><select name="priority" class="form-control" required><?php foreach(array('LOW','NORMAL','HIGH','URGENT') as $p){ ?><option value="<?=$p;?>" <?=$data_edit->priority==$p?'selected':'';?>><?=$p;?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_required_date','Required Date'));?></label><input type="text" name="required_date" class="form-control date-field" value="<?=htmlspecialchars($data_edit->required_date,ENT_QUOTES,'UTF-8');?>" required></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_requestor','Requestor'));?></label><input type="text" name="requestor" class="form-control" value="<?=htmlspecialchars($data_edit->requestor,ENT_QUOTES,'UTF-8');?>" required></div>
        <div class="col-md-3 form-group"><label><?=pr_form_h(pr_form_t('common_department','Department'));?></label><input type="text" name="department" class="form-control" value="<?=htmlspecialchars((string) $data_edit->department,ENT_QUOTES,'UTF-8');?>"></div>
        <div class="col-md-3 form-group"><label class="required-label"><?=pr_form_h(pr_form_t('common_plant','Plant'));?></label><select id="plant" name="plant" class="form-control" required><option value=""><?=pr_form_h(pr_form_t('purchase_requisition_select_plant','Pilih Plant'));?></option><?php foreach($db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $plant){ ?><option value="<?=htmlspecialchars($plant->plant_code,ENT_QUOTES,'UTF-8');?>" <?=$data_edit->plant==$plant->plant_code?'selected':'';?>><?=htmlspecialchars($plant->plant_code.' - '.$plant->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label><?=pr_form_h(pr_form_t('purchase_order_storage_location','Storage Location'));?></label><select id="storage_location" name="storage_location" class="form-control"><option value=""><?=pr_form_h(pr_form_t('purchase_requisition_select_storage_location','Pilih Storage Location'));?></option><?php foreach($db->query("SELECT s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code") as $sloc){ ?><option value="<?=htmlspecialchars($sloc->storage_code,ENT_QUOTES,'UTF-8');?>" data-plant="<?=htmlspecialchars($sloc->plant_code,ENT_QUOTES,'UTF-8');?>" <?=$data_edit->storage_location==$sloc->storage_code?'selected':'';?>><?=htmlspecialchars($sloc->plant_code.' / '.$sloc->storage_code.' - '.$sloc->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        <div class="col-md-3 form-group"><label><?=pr_form_h(pr_form_t('common_status','Status'));?></label><select name="submit_mode" id="submit_mode" class="form-control"><option value="DRAFT" <?=$data_edit->status=='DRAFT'?'selected':'';?>>DRAFT</option><option value="SUBMITTED" <?=in_array($data_edit->status,array('SUBMITTED','APPROVED'))?'selected':'';?>>SUBMITTED / <?=pr_form_h(pr_form_t('purchase_requisition_waiting_approval','Waiting Approval'));?></option></select></div>
        <div class="col-md-3 form-group"><label><?=pr_form_h(pr_form_t('purchase_requisition_approver_level_1','Approver Level 1'));?></label><input type="text" name="approver" class="form-control" placeholder="<?=pr_form_h(pr_form_t('purchase_requisition_approver_placeholder','username / group approver'))?>"></div>
        <div class="col-md-6 form-group"><label><?=pr_form_h(pr_form_t('purchase_requisition_header_note','Header Note'));?></label><input type="text" name="note" class="form-control" value="<?=htmlspecialchars((string) $data_edit->note,ENT_QUOTES,'UTF-8');?>"></div>
      </div></div>
    </div>

    <div class="box box-success pr-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> <?=pr_form_h(pr_form_t('purchase_requisition_item_overview','Item Overview'));?></h3><div class="box-tools"><button type="button" class="btn btn-success btn-sm" onclick="addPrRow()"><i class="fa fa-plus"></i> <?=pr_form_h(pr_form_t('purchase_requisition_add_item','Add Item'));?></button></div></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-condensed pr-items"><thead><tr><th></th><th><?=pr_form_h(pr_form_t('purchase_requisition_item','Item'));?></th><th class="required-label"><?=pr_form_h(pr_form_t('purchase_requisition_material','Material'));?></th><th class="required-label"><?=pr_form_h(pr_form_t('purchase_order_qty','Qty'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_qty_po','Qty PO'));?></th><th class="required-label"><?=pr_form_h(pr_form_t('purchase_order_uom','UOM'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_req_date','Req. Date'));?></th><th><?=pr_form_h(pr_form_t('common_plant','Plant'));?></th><th><?=pr_form_h(pr_form_t('purchase_order_sloc','Sloc'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_val_price','Val. Price'));?></th><th><?=pr_form_h(pr_form_t('purchase_order_currency','Currency'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_acct_assign','Acct Assign'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_cost_center','Cost Center'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_vendor','Vendor'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_tracking_no','Tracking No'));?></th><th><?=pr_form_h(pr_form_t('purchase_requisition_remarks','Remarks'));?></th></tr></thead><tbody id="isi_tabel"></tbody></table>
        <input type="hidden" id="jml" value="0">
      </div>
    </div>

    <div class="text-right">
      <span id="pr_submit_help" class="pr-submit-help"><?=pr_form_h(pr_form_t('purchase_requisition_complete_required','Lengkapi field mandatory dan minimal satu item.'));?></span>
      <a href="<?=base_index();?>pr" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=pr_form_h(pr_form_t('common_back','Kembali'));?></a>
      <button type="submit" id="btn_submit_pr" class="btn btn-primary" <?=in_array($data_edit->status,array('CONVERTED_PO','CLOSED','CANCELLED'))?'disabled':'';?>><i class="fa fa-save"></i> <?=pr_form_h(pr_form_t('purchase_requisition_update','Update Purchase Requisition'));?></button>
    </div>
  </form>
</section>

<script src="<?=base_url();?>assets/js/jquery-ui.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var prFormLang = <?=json_encode($prFormLang, JSON_UNESCAPED_UNICODE);?>;
var existingPrDetails = <?=json_encode($details);?>;
var costCenterOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->cost_center_code,'label'=>$x->cost_center_code.' - '.$x->cost_center_name);}, iterator_to_array($db->query("SELECT cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code"))));?>;
var vendorOptions = <?=json_encode(array_map(function($x){return array('value'=>$x->kode_pemasok,'label'=>$x->kode_pemasok.' - '.$x->nama);}, iterator_to_array($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama"))));?>;
function esc(v){return $('<div>').text(v==null?'':v).html();}
function optionHtml(list,selected){var h='<option value="">'+esc(prFormLang.select)+'</option>';$.each(list,function(_,o){h+='<option value="'+esc(o.value)+'" '+(String(selected||'')===String(o.value)?'selected':'')+'>'+esc(o.label)+'</option>';});return h;}
function addPrRow(item){item=item||{};var id=parseInt($('#jml').val()||0)+1,lineNo=item.line_no||id*10,plant=item.plant||$('#plant').val(),sloc=item.storage_location||$('#storage_location').val(),reqDate=item.required_date||$('input[name="required_date"]').val(),qtyPo=parseFloat(item.qty_po||0);$('#jml').val(id);var selectedMaterial=item.material_code?'<option value="'+esc(item.material_code)+'" selected>'+esc(item.material_code+' - '+(item.material_name||''))+'</option>':'<option value="">'+esc(prFormLang.searchMaterial)+'</option>';var html='<tr id="baris_'+id+'" class="pr-main-row">'+
'<td><button type="button" class="btn btn-danger btn-xs" onclick="hapusPrRow('+id+')" '+(qtyPo>0?'disabled title="'+esc(prFormLang.itemHasPo)+'"':'')+'><i class="fa fa-trash"></i></button><input type="hidden" name="id_pr_detail[]" value="'+esc(item.id_pr_detail||'')+'"></td>'+
'<td><input class="form-control" name="line_no[]" value="'+esc(lineNo)+'" readonly></td>'+
'<td><select class="form-control material-select pr-required" name="material_code[]" required>'+selectedMaterial+'</select><input type="hidden" name="material_name[]" value="'+esc(item.material_name||'')+'"><input type="hidden" name="material_group[]" value="'+esc(item.material_group||'')+'"><input type="hidden" name="kd_kategori[]" value="'+esc(item.kd_kategori||'')+'"></td>'+
'<td><input type="number" step="0.00001" min="'+(qtyPo>0?qtyPo:'0.00001')+'" class="form-control pr-required qty-field" name="qty[]" value="'+esc(item.qty||'')+'" required></td>'+
'<td><input class="form-control" value="'+esc(item.qty_po||0)+'" readonly></td>'+
'<td><input class="form-control pr-required uom-field" name="uom[]" value="'+esc(item.uom||'')+'" required></td>'+
'<td><input class="form-control date-field-row" name="item_required_date[]" value="'+esc(reqDate)+'"></td>'+
'<td><input class="form-control item-plant" name="item_plant[]" value="'+esc(plant)+'"></td>'+
'<td><input class="form-control item-sloc" name="item_storage_location[]" value="'+esc(sloc)+'"></td>'+
'<td><input type="number" step="0.00001" min="0" class="form-control" name="valuation_price[]" value="'+esc(item.valuation_price||0)+'"></td>'+
'<td><input class="form-control" name="currency[]" value="'+esc(item.currency||'IDR')+'"></td>'+
'<td><select class="form-control" name="account_assignment[]"><option value="" '+(!item.account_assignment?'selected':'')+'>Stock Item</option><option value="K" '+(item.account_assignment==='K'?'selected':'')+'>K - Cost Center</option><option value="A" '+(item.account_assignment==='A'?'selected':'')+'>A - Asset</option><option value="F" '+(item.account_assignment==='F'?'selected':'')+'>F - Order</option></select></td>'+
'<td><select class="form-control" name="cost_center[]">'+optionHtml(costCenterOptions,item.cost_center||'')+'</select></td>'+
'<td><select class="form-control" name="suggested_vendor[]">'+optionHtml(vendorOptions,item.suggested_vendor||'')+'</select></td>'+
'<td><input class="form-control" name="tracking_no[]" value="'+esc(item.tracking_no||'')+'"></td>'+
'<td><input class="form-control" name="remarks[]" value="'+esc(item.remarks||'')+'"></td></tr>';$('#isi_tabel').append(html);initMaterialSelect($('#baris_'+id));if($.fn.datepicker){$('#baris_'+id+' .date-field-row').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}updateSubmitState();}
function hapusPrRow(id){$('#baris_'+id).remove();updateSubmitState();}
function initMaterialSelect(scope){var target=scope?scope.find('.material-select'):$('.material-select');if(!$.fn.select2)return;target.each(function(){var el=$(this);if(el.data('select2'))return;el.select2({placeholder:prFormLang.searchMaterial,allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/pr/pr_action.php?act=search_material',type:'POST',dataType:'json',delay:250,data:function(params){return {term:params.term||''};},processResults:function(data){return {results:data.results||[]};},cache:true}});});}
function isRequiredFilled(el){var $el=$(el),val=$.trim($el.val()||'');if(val==='')return false;if($el.attr('type')==='number'){var num=parseFloat(val),min=$el.attr('min')!==undefined?parseFloat($el.attr('min')):null;if(isNaN(num))return false;if(min!==null&&num<min)return false;}return true;}
function updateSubmitState(){var valid=true;$('#input_pr [required]').each(function(){var ok=isRequiredFilled(this);$(this).toggleClass('pr-required-missing',!ok);if(!ok)valid=false;});if($('#isi_tabel .pr-main-row').length===0)valid=false;$('#btn_submit_pr').prop('disabled',!valid);$('#pr_submit_help').toggle(!valid);return valid;}
function showPrError(message){$('.isi_warning').text(message||prFormLang.updateFailed);$('.error_data').show();$('html,body').animate({scrollTop:$('.error_data').offset().top-90},300);}
function parsePrResponse(response){if(typeof response==='string'){try{return JSON.parse(response);}catch(e){return [{status:'error',error_message:response}];}}return response;}
$(function(){if($.fn.select2){$('#plant,#storage_location,#submit_mode').select2({width:'100%'});}if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}$.each(existingPrDetails,function(_,item){addPrRow(item);});if(existingPrDetails.length===0)addPrRow();$('#plant').on('change',function(){var plant=this.value;$('#storage_location option').each(function(){var p=$(this).data('plant');$(this).toggle(!p||String(p)===String(plant));});$('.item-plant').val(plant);updateSubmitState();});$('#storage_location').on('change',function(){$('.item-sloc').val(this.value);});$(document).on('select2:select','.material-select',function(e){var row=$(this).closest('tr'),data=e.params.data||{};row.find('input[name="material_name[]"]').val(data.material_name||'');row.find('input[name="material_group[]"]').val(data.material_group||'');row.find('input[name="kd_kategori[]"]').val(data.kd_kategori||'');row.find('.uom-field').val(data.uom||'');updateSubmitState();});$(document).on('select2:clear','.material-select',function(){var row=$(this).closest('tr');row.find('input[name="material_name[]"],input[name="material_group[]"],input[name="kd_kategori[]"],.uom-field').val('');updateSubmitState();});$('#input_pr').on('input change','input,select,textarea',updateSubmitState);$('#input_pr').on('submit',function(e){e.preventDefault();if(!updateSubmitState()){showPrError(prFormLang.completeRequired);return false;}var form=this,button=$('#btn_submit_pr');button.prop('disabled',true).data('original-text',button.html()).html('<i class="fa fa-spinner fa-spin"></i> '+esc(prFormLang.saving));$('.error_data').hide();$.ajax({url:$(form).attr('action'),type:'POST',data:$(form).serialize(),dataType:'json',success:function(response){response=parsePrResponse(response);var result=$.isArray(response)?response[0]:response;if(result&&result.status==='good'){window.location='<?=base_index();?>pr';return;}showPrError(result&&result.error_message?result.error_message:prFormLang.updateFailed);button.prop('disabled',false).html(button.data('original-text'));},error:function(xhr){var response=parsePrResponse(xhr.responseText),result=$.isArray(response)?response[0]:response;showPrError(result&&result.error_message?result.error_message:prFormLang.serverUpdateError);button.prop('disabled',false).html(button.data('original-text'));}});return false;});updateSubmitState();});
</script>
