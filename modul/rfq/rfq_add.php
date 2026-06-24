<style>
  .rfq-section{margin-bottom:18px}.required-label:after{content:' *';color:#dd4b39}.rfq-items,.rfq-vendors{font-size:12px}.rfq-items th,.rfq-vendors th{white-space:nowrap;background:#f5f5f5}.rfq-items .form-control,.rfq-vendors .form-control{height:30px;padding:4px 6px;font-size:12px}.select2-container{width:100%!important}.rfq-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:12px;padding:18px 20px;margin-bottom:18px}
</style>
<?php
if (!function_exists('rfq_t')) {
  function rfq_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('rfq_h')) {
  function rfq_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$rfqFormLang = array(
  'searchPrItem' => rfq_t('rfq_search_pr_item','Cari approved PR item'),
  'searchVendor' => rfq_t('rfq_search_vendor','Cari vendor'),
  'saveFailed' => rfq_t('rfq_save_failed','RFQ gagal disimpan.'),
  'minPrItem' => rfq_t('rfq_min_pr_item','Minimal satu PR item wajib dipilih.'),
  'minVendor' => rfq_t('rfq_min_vendor','Minimal satu vendor wajib dipilih.'),
);
?>
<section class="content-header">
  <h1><?=rfq_h(rfq_t('rfq_title','Request for Quotation'));?> <small><?=rfq_h(rfq_t('rfq_create_subtitle','Create SAP MM RFQ'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=rfq_h(rfq_t('common_home','Home'));?></a></li>
    <li><a href="<?=base_index();?>rfq"><?=rfq_h(rfq_t('rfq_title','Request for Quotation'));?></a></li>
    <li class="active"><?=rfq_h(rfq_t('common_create','Create'));?></li>
  </ol>
</section>
<section class="content">
  <div class="rfq-hero">
    <h3 style="margin-top:0"><?=rfq_h(rfq_t('rfq_create_from_pr','Create RFQ from Approved PR'));?></h3>
    <p style="margin-bottom:0"><?=rfq_h(rfq_t('rfq_create_intro','Pilih PR item yang sudah approved, invite vendor, lalu kirim RFQ untuk proses quotation comparison.'));?></p>
  </div>
  <form id="input_rfq" method="post" action="<?=base_admin();?>modul/rfq/rfq_action.php?act=in">
    <div class="alert alert-danger error_data" style="display:none"><span class="isi_warning"></span></div>
    <div class="box box-primary rfq-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> <?=rfq_h(rfq_t('purchase_requisition_header_data','Header'));?></h3></div>
      <div class="box-body">
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label"><?=rfq_h(rfq_t('rfq_date','RFQ Date'));?></label><input type="text" name="rfq_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-3 form-group"><label class="required-label"><?=rfq_h(rfq_t('rfq_deadline','Quotation Deadline'));?></label><input type="text" name="quotation_deadline" class="form-control date-field" value="<?=date('Y-m-d', strtotime('+7 days'));?>" required></div>
          <div class="col-md-3 form-group"><label><?=rfq_h(rfq_t('purchase_order_purchasing_org','Purchasing Org'));?></label><select name="purchasing_org" class="form-control select2-basic"><option value=""><?=rfq_h(rfq_t('purchase_requisition_select','Pilih'));?></option><?php foreach($db->query("SELECT org_code,org_name FROM erp_purchasing_organization WHERE status='Aktif' ORDER BY org_code") as $row){ ?><option value="<?=htmlspecialchars($row->org_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->org_code.' - '.$row->org_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=rfq_h(rfq_t('purchase_order_purchasing_group','Purchasing Group'));?></label><select name="purchasing_group" class="form-control select2-basic"><option value=""><?=rfq_h(rfq_t('purchase_requisition_select','Pilih'));?></option><?php foreach($db->query("SELECT group_code,group_name FROM erp_purchasing_group WHERE status='Aktif' ORDER BY group_code") as $row){ ?><option value="<?=htmlspecialchars($row->group_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->group_code.' - '.$row->group_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=rfq_h(rfq_t('common_plant','Plant'));?></label><select name="plant" id="plant" class="form-control select2-basic"><option value=""><?=rfq_h(rfq_t('purchase_requisition_select','Pilih'));?></option><?php foreach($db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $row){ ?><option value="<?=htmlspecialchars($row->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($row->plant_code.' - '.$row->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=rfq_h(rfq_t('purchase_order_storage_location','Storage Location'));?></label><input type="text" name="storage_location" class="form-control" placeholder="<?=rfq_h(rfq_t('purchase_order_sloc','Sloc'));?>"></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=rfq_h(rfq_t('purchase_order_currency','Currency'));?></label><input type="text" name="currency" class="form-control" value="IDR" required></div>
          <div class="col-md-4 form-group"><label class="required-label"><?=rfq_h(rfq_t('rfq_subject','Subject'));?></label><input type="text" name="subject" class="form-control" placeholder="<?=rfq_h(rfq_t('rfq_subject_placeholder','Contoh: RFQ Material Produksi Juni'));?>" required></div>
          <div class="col-md-6 form-group"><label><?=rfq_h(rfq_t('purchase_order_note','Note'));?></label><input type="text" name="note" class="form-control" placeholder="<?=rfq_h(rfq_t('rfq_note_placeholder','Instruksi vendor / delivery / incoterm'));?>"></div>
        </div>
      </div>
    </div>
    <div class="box box-success rfq-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> <?=rfq_h(rfq_t('rfq_pr_items','PR Items'));?></h3><div class="box-tools"><button type="button" class="btn btn-success btn-sm" onclick="addRfqItem()"><i class="fa fa-plus"></i> <?=rfq_h(rfq_t('rfq_add_pr_item','Add PR Item'));?></button></div></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-condensed rfq-items"><thead><tr><th></th><th><?=rfq_h(rfq_t('rfq_pr_item','PR Item'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_material','Material'));?></th><th><?=rfq_h(rfq_t('purchase_order_qty','Qty'));?></th><th><?=rfq_h(rfq_t('purchase_order_uom','UOM'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_req_date','Req. Date'));?></th><th><?=rfq_h(rfq_t('rfq_target_price','Target Price'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_remarks','Remarks'));?></th></tr></thead><tbody id="rfq_item_body"></tbody></table>
      </div>
    </div>
    <div class="box box-warning rfq-section">
      <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-users"></i> <?=rfq_h(rfq_t('rfq_invited_vendors','Invited Vendors'));?></h3><div class="box-tools"><button type="button" class="btn btn-warning btn-sm" onclick="addRfqVendor()"><i class="fa fa-plus"></i> <?=rfq_h(rfq_t('rfq_add_vendor','Add Vendor'));?></button></div></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-condensed rfq-vendors"><thead><tr><th></th><th><?=rfq_h(rfq_t('purchase_requisition_vendor','Vendor'));?></th><th><?=rfq_h(rfq_t('rfq_vendor_name','Name'));?></th><th><?=rfq_h(rfq_t('purchase_order_email','Email'));?></th><th><?=rfq_h(rfq_t('purchase_order_note','Note'));?></th></tr></thead><tbody id="rfq_vendor_body"></tbody></table>
      </div>
    </div>
    <div class="text-right">
      <a href="<?=base_index();?>rfq" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=rfq_h(rfq_t('common_back','Kembali'));?></a>
      <button type="submit" class="btn btn-default" data-mode="DRAFT"><i class="fa fa-save"></i> <?=rfq_h(rfq_t('rfq_save_draft','Save Draft'));?></button>
      <button type="submit" class="btn btn-primary" data-mode="SENT"><i class="fa fa-paper-plane"></i> <?=rfq_h(rfq_t('rfq_save_send','Save & Send RFQ'));?></button>
      <input type="hidden" name="save_mode" id="save_mode" value="DRAFT">
    </div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var rfqFormLang=<?=json_encode($rfqFormLang, JSON_UNESCAPED_UNICODE);?>;
var itemIndex=0,vendorIndex=0;
function esc(v){return $('<div>').text(v==null?'':v).html();}
function initPrSelect(scope){scope.find('.pr-item-select').select2({placeholder:rfqFormLang.searchPrItem,allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/rfq/rfq_action.php?act=available_pr_items',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
function initVendorSelect(scope){scope.find('.vendor-select').select2({placeholder:rfqFormLang.searchVendor,allowClear:true,width:'100%',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/rfq/rfq_action.php?act=vendor_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
function addRfqItem(){itemIndex++;var html='<tr id="rfq_item_'+itemIndex+'"><td><button type="button" class="btn btn-danger btn-xs btn-remove-row"><i class="fa fa-trash"></i></button></td><td><select name="pr_detail_id[]" class="form-control pr-item-select" required><option value="">'+esc(rfqFormLang.searchPrItem)+'</option></select><input type="hidden" name="line_no[]" value="'+(itemIndex*10)+'"></td><td><input class="form-control material-label" readonly><input type="hidden" name="item_remarks[]" value=""></td><td><input type="number" step="0.00001" name="qty[]" class="form-control qty-field" required></td><td><input class="form-control uom-field" readonly></td><td><input class="form-control req-field" readonly></td><td><input class="form-control price-field" readonly></td><td><input class="form-control remarks-field"></td></tr>';$('#rfq_item_body').append(html);var row=$('#rfq_item_'+itemIndex);initPrSelect(row);}
function addRfqVendor(){vendorIndex++;var html='<tr id="rfq_vendor_'+vendorIndex+'"><td><button type="button" class="btn btn-danger btn-xs btn-remove-row"><i class="fa fa-trash"></i></button></td><td><select name="vendor_code[]" class="form-control vendor-select" required><option value="">'+esc(rfqFormLang.searchVendor)+'</option></select></td><td><input class="form-control vendor-name" readonly></td><td><input class="form-control vendor-email" readonly></td><td><input name="vendor_note[]" class="form-control"></td></tr>';$('#rfq_vendor_body').append(html);initVendorSelect($('#rfq_vendor_'+vendorIndex));}
function showError(m){$('.isi_warning').text(m||rfqFormLang.saveFailed);$('.error_data').show();}
$(function(){if($.fn.datepicker){$('.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}if($.fn.select2){$('.select2-basic').select2({width:'100%'});}addRfqItem();addRfqVendor();$(document).on('click','.btn-remove-row',function(){$(this).closest('tr').remove();});$(document).on('change','.remarks-field',function(){$(this).closest('tr').find('input[name="item_remarks[]"]').val(this.value);});$(document).on('select2:select','.pr-item-select',function(e){var d=e.params.data,row=$(this).closest('tr');row.find('.material-label').val(d.material_code+' - '+d.material_name);row.find('.qty-field').val(d.qty);row.find('.qty-field').attr('max',d.qty);row.find('.uom-field').val(d.uom);row.find('.req-field').val(d.required_date);row.find('.price-field').val(d.currency+' '+d.target_price);if(!$('#plant').val()&&d.plant){$('#plant').val(d.plant).trigger('change');}});$(document).on('select2:select','.vendor-select',function(e){var d=e.params.data,row=$(this).closest('tr');row.find('.vendor-name').val(d.vendor_name||'');row.find('.vendor-email').val(d.email||'');});$('#input_rfq button[type=submit]').on('click',function(){$('#save_mode').val($(this).data('mode'));});$('#input_rfq').on('submit',function(e){e.preventDefault();if($('#rfq_item_body tr').length===0){showError(rfqFormLang.minPrItem);return;}if($('#rfq_vendor_body tr').length===0){showError(rfqFormLang.minVendor);return;}var btn=$('#input_rfq button[type=submit]:focus');btn.prop('disabled',true);$.ajax({url:$(this).attr('action'),type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){window.location='<?=base_index();?>rfq';return;}showError(r.error_message);btn.prop('disabled',false);},error:function(xhr){showError(xhr.responseText);btn.prop('disabled',false);}});});});
</script>
