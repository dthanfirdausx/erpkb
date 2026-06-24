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
include_once "packing_list_lib.php"; ?>
<style>
.pl-page-hero{border-radius:14px;background:linear-gradient(135deg,#0f6fb4,#16a085);color:#fff;padding:18px 20px;margin-bottom:16px;box-shadow:0 10px 24px rgba(15,111,180,.18)}
.pl-page-hero h3{margin:0;font-weight:700}.pl-page-hero p{margin:6px 0 0;opacity:.9}
.pl-card{border-radius:14px;border:1px solid #e5edf5;background:#fff;margin-bottom:16px;box-shadow:0 6px 18px rgba(15,23,42,.06)}
.pl-card .pl-card-head{padding:14px 16px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.pl-card .pl-card-head h4{margin:0;font-size:15px;font-weight:700}.pl-card .pl-card-body{padding:16px}
.pl-step{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#0f6fb4;color:#fff;font-weight:700;margin-right:8px}
.pl-help{color:#64748b;font-size:12px;margin-top:5px}.pl-readonly{background:#f8fafc!important;color:#334155!important}
.pl-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:8px}
.pl-summary-box{border:1px solid #e5edf5;background:#f8fafc;border-radius:10px;padding:10px 12px;min-height:58px}
.pl-summary-box span{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}.pl-summary-box strong{display:block;margin-top:3px;color:#0f172a;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pl-item-table{margin-bottom:0}.pl-item-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc!important;color:#475569;vertical-align:middle}.pl-item-table td{font-size:12px;vertical-align:middle}
.pl-item-table .form-control{font-size:12px;height:30px;padding:4px 7px}.pl-item-table .material-name{color:#64748b;font-size:11px}
.pl-sticky-actions{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5edf5;padding:12px 0;margin-top:8px;z-index:5}
.select2-container{width:100%!important}.has-error .form-control{border-color:#dd4b39}
@media(max-width:991px){.pl-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){.pl-summary{grid-template-columns:1fr}.pl-card .pl-card-head{display:block}.pl-page-hero{padding:16px}}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_packing_list', 'Packing List');?> <small>Create from picked delivery</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>packing-list"><?=sd_h('sales_packing_list', 'Packing List');?></a></li>
    <li class="active"><?=sd_h('common_add', 'Add');?></li>
  </ol>
</section>
<section class="content">
  <div class="pl-page-hero">
    <h3><i class="fa fa-archive"></i> Create Packing List</h3>
    <p>Pilih Outbound Delivery yang sudah selesai picking, lalu sistem akan membawa item yang masih open untuk dipacking.</p>
  </div>
  <div class="alert alert-danger error_data" style="display:none">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <span class="isi_warning"></span>
  </div>
  <form id="input_packing_list" method="post" action="<?=base_admin();?>modul/packing_list/packing_list_action.php?act=in">
    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">1</span>Delivery Reference</h4>
        <span class="label label-info">Pick complete only</span>
      </div>
      <div class="pl-card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>Packing List No <span class="text-red">*</span></label>
              <input type="text" value="<?=generate_no_packing_list(date('Y'),date('m'));?>" name="no_packing_list" class="form-control pl-readonly" readonly required>
              <div class="pl-help">Nomor dibuat otomatis.</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?=sd_h('sales_outbound_delivery', 'Outbound Delivery');?> <span class="text-red">*</span></label>
              <select id="delivery_select" name="delivery_id" class="form-control" required></select>
              <div class="pl-help">Cari berdasarkan nomor delivery, customer, atau sales order.</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Packing Date <span class="text-red">*</span></label>
              <input type="text" class="form-control pl-date" name="tgl_sj" value="<?=date('Y-m-d');?>" readonly required>
            </div>
          </div>
        </div>
        <div class="pl-summary">
          <div class="pl-summary-box"><span><?=sd_h('sales_delivery_no', 'Delivery No');?></span><strong id="delivery_no_text">-</strong></div>
          <div class="pl-summary-box"><span>Picking No</span><strong id="picking_no_text">-</strong></div>
          <div class="pl-summary-box"><span><?=sd_h('sales_customer', 'Customer');?></span><strong id="customer_text">-</strong></div>
          <div class="pl-summary-box"><span><?=sd_h('sales_vehicle', 'Vehicle');?></span><strong id="vehicle_text">-</strong></div>
        </div>
        <input type="hidden" name="picking_id" id="picking_id">
        <input type="hidden" name="picking_no" id="picking_no">
        <input type="hidden" name="penerima" id="penerima">
        <input type="hidden" name="no_invoice" id="no_invoice">
        <input type="hidden" name="valuta" value="IDR">
        <input type="hidden" name="kurs" value="1">
      </div>
    </div>

    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">2</span>Packing Information</h4>
      </div>
      <div class="pl-card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Surat Jalan Ref</label>
              <input name="no_sj" id="no_sj" class="form-control" placeholder="Auto dari delivery jika tersedia">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>No PO Customer</label>
              <input name="no_po" id="no_po" class="form-control" placeholder="Nomor PO customer">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>Vehicle No</label>
              <input type="text" name="vehicle_no_display" id="vehicle_no_display" class="form-control pl-readonly" readonly>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label><?=sd_h('common_remarks', 'Remarks');?></label>
          <textarea name="remarks" class="form-control" rows="2" placeholder="Catatan packing, instruksi pengiriman, atau informasi tambahan"></textarea>
        </div>
      </div>
    </div>

    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">3</span>Packing Items</h4>
        <small class="text-muted">Qty tidak boleh melebihi open picked qty.</small>
      </div>
      <div class="pl-card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed pl-item-table" id="table_pl_items">
            <thead>
              <tr>
                <th style="width:44px" class="text-center"><?=sd_h('common_no', 'No');?></th>
                <th style="min-width:220px"><?=sd_h('sales_material', 'Material');?></th>
                <th class="text-right">Delivery</th>
                <th class="text-right">Picked</th>
                <th class="text-right">Packed</th>
                <th style="width:120px" class="text-right">Pack Qty</th>
                <th style="width:70px"><?=sd_h('sales_uom', 'UOM');?></th>
                <th style="width:150px">Packing Type</th>
                <th style="width:120px">Qty Pack</th>
                <th style="min-width:160px">Remark</th>
              </tr>
            </thead>
            <tbody><tr><td colspan="10" class="text-center text-muted">Pilih Outbound Delivery untuk load item.</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="pl-sticky-actions">
      <a href="<?=base_index();?>packing-list" class="btn btn-default"><i class="fa fa-step-backward"></i> <?=$lang["back_button"];?></a>
      <button type="submit" id="btn_save_pl" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=$lang["submit_button"];?></button>
    </div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function plError(msg){$('.isi_warning').text(msg||<?=sd_js('sales_packing_list_process_failed', 'Packing List failed to process.');?>);$('.error_data').fadeIn();$('html,body').animate({scrollTop:$('.error_data').offset().top-80},250);}
function plSetText(id,value){$(id).text(value&&String(value).trim()!==''?value:'-');}
function plValidateItems(){
  var hasQty=false, ok=true;
  $('.pl-pack-qty').each(function(){
    var q=parseFloat(String($(this).val()||'0').replace(',','.'))||0;
    var max=parseFloat($(this).data('max'))||0;
    if(q>0) hasQty=true;
    if(q<0||q>max+0.00001){ok=false;$(this).closest('td').addClass('has-error');}else{$(this).closest('td').removeClass('has-error');}
  });
  $('#btn_save_pl').prop('disabled',!(hasQty&&ok&&$('#delivery_select').val()));
  return hasQty&&ok;
}
function loadDeliveryItems(id){
  $('#table_pl_items tbody').html('<tr><td colspan="10" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading items...</td></tr>');
  $.post('<?=base_admin();?>modul/packing_list/packing_list_action.php?act=delivery_items',{delivery_id:id},function(html){
    $('#table_pl_items tbody').html(html);
    plValidateItems();
  },'html').fail(function(xhr){console.log(xhr.responseText);plError(<?=sd_js('sales_delivery_item_load_failed', 'Delivery item failed to load.');?>);});
}
$(function(){
  if($.fn.datepicker){$('.pl-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $('#delivery_select').select2({
    width:'100%',
    placeholder:'Cari Outbound Delivery picked...',
    minimumInputLength:1,
    ajax:{
      url:'<?=base_admin();?>modul/packing_list/packing_list_action.php?act=delivery_search',
      type:'POST',
      dataType:'json',
      delay:250,
      data:function(p){return{term:p.term||''};},
      processResults:function(d){return{results:d.results||[]};}
    }
  }).on('select2:select',function(e){
    var x=e.params.data;
    plSetText('#delivery_no_text',x.delivery_no);
    plSetText('#picking_no_text',x.picking_no);
    plSetText('#customer_text',(x.customer_code||'')+' - '+(x.customer_name||''));
    plSetText('#vehicle_text',x.vehicle_no);
    $('#picking_id').val(x.picking_id||'');
    $('#picking_no').val(x.picking_no||'');
    $('#penerima').val(x.customer_code||'');
    $('#vehicle_no_display').val(x.vehicle_no||'');
    $('#no_sj').val(x.reference_surat_jalan||'');
    $('#no_po').val(x.no_po||'');
    loadDeliveryItems(x.id);
  });
  $(document).on('keyup change','.pl-pack-qty',plValidateItems);
  $('#input_packing_list').on('submit',function(e){
    e.preventDefault();
    if(!plValidateItems()){plError('Minimal satu item harus punya Pack Qty dan tidak boleh melebihi open picked qty.');return;}
    $('#btn_save_pl').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=sd_h('common_saving', 'Saving...');?>');
    $(this).ajaxSubmit({
      dataType:'json',
      success:function(resp){
        var good=false,msg='';
        $.each(resp||[],function(_,r){if(r.status==='good')good=true;if(r.status==='error')msg=r.error_message;});
        if(good){window.location='<?=base_index();?>packing-list';}
        else{plError(msg||<?=sd_js('sales_packing_list_save_failed', 'Packing List failed to save.');?>);$('#btn_save_pl').prop('disabled',false).html('<i class="fa fa-save"></i> <?=$lang["submit_button"];?>');}
      },
      error:function(xhr){console.log(xhr.responseText);plError(<?=sd_js('sales_packing_list_save_failed', 'Packing List failed to save.');?>);$('#btn_save_pl').prop('disabled',false).html('<i class="fa fa-save"></i> <?=$lang["submit_button"];?>');}
    });
  });
});
</script>
