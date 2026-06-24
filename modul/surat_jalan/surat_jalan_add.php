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
function sj_form_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$nextNo = "SJ-" . date('Ym') . "-" . str_pad(get_nomor('surat_jalan','id'), 4, '0', STR_PAD_LEFT);
?>
<style>
.sj-page-hero{border-radius:14px;background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;padding:18px 20px;margin-bottom:16px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.sj-page-hero h3{margin:0;font-weight:700}.sj-page-hero p{margin:6px 0 0;opacity:.92}
.sj-card{border-radius:14px;border:1px solid #e5edf5;background:#fff;margin-bottom:16px;box-shadow:0 6px 18px rgba(15,23,42,.06)}
.sj-card .sj-card-head{padding:14px 16px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.sj-card .sj-card-head h4{margin:0;font-size:15px;font-weight:700}.sj-card .sj-card-body{padding:16px}
.sj-step{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#0f766e;color:#fff;font-weight:700;margin-right:8px}
.sj-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:8px}
.sj-summary-box{border:1px solid #e5edf5;background:#f8fafc;border-radius:10px;padding:10px 12px;min-height:58px}
.sj-summary-box span{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}.sj-summary-box strong{display:block;margin-top:3px;color:#0f172a;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sj-item-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc!important;color:#475569;vertical-align:middle}.sj-item-table td{font-size:12px;vertical-align:middle}
.sj-item-table .form-control{font-size:12px;height:30px;padding:4px 7px}.sj-readonly{background:#f8fafc!important;color:#334155!important}.select2-container{width:100%!important}.has-error .form-control{border-color:#dd4b39}
.sj-sticky-actions{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5edf5;padding:12px 0;margin-top:8px;z-index:5}
@media(max-width:991px){.sj-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.sj-summary{grid-template-columns:1fr}}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_surat_jalan', 'Surat Jalan');?> <small>Create from Packing List</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>surat-jalan"><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></a></li>
    <li class="active">Tambah</li>
  </ol>
</section>
<section class="content">
  <div class="sj-page-hero">
    <h3><i class="fa fa-file-text-o"></i> Buat Surat Jalan dari Packing List</h3>
    <p>Surat Jalan sekarang mengikuti flow SAP SD: data item diambil dari Packing List yang sudah selesai dipacking.</p>
  </div>
  <div class="alert alert-danger error_data" style="display:none">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <span class="isi_warning"></span>
  </div>
  <form id="input_surat_jalan" method="post" action="<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=in">
    <input type="hidden" name="packing_list_id" id="packing_list_id">
    <input type="hidden" name="delivery_id" id="delivery_id">
    <input type="hidden" name="delivery_no" id="delivery_no">
    <input type="hidden" name="picking_no" id="picking_no">
    <input type="hidden" name="gi_id" id="gi_id">
    <input type="hidden" name="gi_no" id="gi_no">
    <input type="hidden" name="movement_type" id="movement_type">

    <div class="sj-card">
      <div class="sj-card-head">
        <h4><span class="sj-step">1</span><?=sd_h('sales_reference', 'Reference');?></h4>
        <span class="label label-info">Packing List status PACKED</span>
      </div>
      <div class="sj-card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>No Surat Jalan</label>
              <input type="text" id="no_surat_jalan" name="no_surat_jalan" value="<?=sj_form_h($nextNo);?>" class="form-control sj-readonly" readonly>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?=sd_h('sales_packing_list', 'Packing List');?> <span class="text-red">*</span></label>
              <select id="packing_select" class="form-control" required></select>
              <p class="help-block">Pilih Packing List yang belum pernah dibuatkan Surat Jalan.</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Tanggal Surat Jalan <span class="text-red">*</span></label>
              <input type="text" class="form-control sj-date" name="tgl_surat_jalan" value="<?=date('Y-m-d');?>" readonly required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3"><div class="form-group"><label><?=sd_h('sales_document_date', 'Document Date');?></label><input type="text" class="form-control sj-date" name="document_date_display" id="document_date_display" value="<?=date('Y-m-d');?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label><?=sd_h('sales_posting_date', 'Posting Date');?></label><input type="text" class="form-control sj-date" name="posting_date_display" id="posting_date_display" value="<?=date('Y-m-d');?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Movement Type</label><input type="text" class="form-control sj-readonly" id="movement_type_display" value="601" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Goods Issue</label><input type="text" class="form-control sj-readonly" id="gi_no_display" readonly></div></div>
        </div>
        <div class="sj-summary">
          <div class="sj-summary-box"><span><?=sd_h('sales_packing_list', 'Packing List');?></span><strong id="pl_text">-</strong></div>
          <div class="sj-summary-box"><span><?=sd_h('sales_outbound_delivery', 'Outbound Delivery');?></span><strong id="delivery_text">-</strong></div>
          <div class="sj-summary-box"><span><?=sd_h('sales_order', 'Sales Order');?></span><strong id="so_text">-</strong></div>
          <div class="sj-summary-box"><span><?=sd_h('sales_customer', 'Customer');?></span><strong id="customer_text">-</strong></div>
        </div>
      </div>
    </div>

    <div class="sj-card">
      <div class="sj-card-head"><h4><span class="sj-step">2</span>Shipping Information</h4></div>
      <div class="sj-card-body">
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label>No Invoice</label><input type="text" id="no_invoice" name="no_invoice" class="form-control"></div></div>
          <div class="col-md-4"><div class="form-group"><label>No PO Customer</label><input type="text" id="no_po" name="no_po" class="form-control"></div></div>
          <div class="col-md-4"><div class="form-group"><label>No Kendaraan <span class="text-red">*</span></label><input type="text" id="no_kendaraan" name="no_kendaraan" class="form-control" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label><?=sd_h('sales_shipping_point', 'Shipping Point');?></label><input type="text" id="shipping_point_display" class="form-control sj-readonly" readonly></div></div>
          <div class="col-md-4"><div class="form-group"><label>Route</label><input type="text" id="route_display" class="form-control sj-readonly" readonly></div></div>
          <div class="col-md-4"><div class="form-group"><label>Carrier</label><input type="text" id="carrier_display" class="form-control sj-readonly" readonly></div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="form-group"><label>Sopir</label><input type="text" id="sopir" name="sopir" class="form-control"></div></div>
          <div class="col-md-6"><div class="form-group"><label>Attn <span class="text-red">*</span></label><input type="text" id="attn" name="attn" class="form-control" required></div></div>
        </div>
        <div class="form-group"><label>Alamat Pengiriman <span class="text-red">*</span></label><textarea id="alamat_pengiriman" name="alamat_pengiriman" class="form-control" rows="3" required></textarea></div>
        <div class="form-group"><label>Keterangan</label><textarea name="keterangan" id="keterangan" class="form-control" rows="2" placeholder="Catatan pengiriman"></textarea></div>
      </div>
    </div>

    <div class="sj-card">
      <div class="sj-card-head">
        <h4><span class="sj-step">3</span>Item Surat Jalan</h4>
        <small class="text-muted">Qty berasal dari qty packed di Packing List.</small>
      </div>
      <div class="sj-card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed sj-item-table">
            <thead>
              <tr>
                <th style="width:44px" class="text-center"><?=sd_h('common_no', 'No');?></th>
                <th style="min-width:120px">Kode</th>
                <th style="min-width:220px">Nama Barang</th>
                <th class="text-right">Delivery Qty</th>
                <th style="width:130px">Packing</th>
                <th style="width:120px">Qty Packing</th>
                <th style="width:120px" class="text-right">Qty Kirim</th>
                <th style="width:70px"><?=sd_h('sales_uom', 'UOM');?></th>
                <th style="min-width:160px">Trace</th>
                <th style="min-width:160px">Keterangan</th>
              </tr>
            </thead>
            <tbody id="isi_tabel"><tr><td colspan="10" class="text-center text-muted">Pilih Packing List untuk load item.</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="sj-sticky-actions">
      <a href="<?=base_index();?>surat-jalan" class="btn btn-default"><i class="fa fa-step-backward"></i> Kembali</a>
      <button type="submit" id="btn_save_sj" class="btn btn-primary" disabled><i class="fa fa-save"></i> Buat Surat Jalan</button>
    </div>
  </form>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function sjError(msg){$('.isi_warning').text(msg||<?=sd_js('sales_surat_jalan_process_failed', 'Surat Jalan failed to process.');?>);$('.error_data').fadeIn();$('html,body').animate({scrollTop:$('.error_data').offset().top-80},250);}
function sjSetText(id,value){$(id).text(value&&String(value).trim()!==''?value:'-');}
function sjValidate(){
  var ok=true, hasQty=false;
  $('.sj-qty').each(function(){
    var qty=parseFloat(String($(this).val()||'0').replace(',','.'))||0;
    var max=parseFloat($(this).data('max'))||0;
    if(qty>0) hasQty=true;
    if(qty<0||qty>max+0.00001){ok=false;$(this).closest('td').addClass('has-error');}else{$(this).closest('td').removeClass('has-error');}
  });
  $('#btn_save_sj').prop('disabled',!(ok&&hasQty&&$('#packing_list_id').val()));
  return ok&&hasQty;
}
function loadPackingItems(id){
  $('#isi_tabel').html('<tr><td colspan="10" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading item...</td></tr>');
  $.post('<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=packing_items',{packing_list_id:id},function(html){
    $('#isi_tabel').html(html); sjValidate();
  },'html').fail(function(xhr){console.log(xhr.responseText);sjError(<?=sd_js('sales_packing_item_load_failed', 'Packing List item failed to load.');?>);});
}
$(function(){
  if($.fn.datepicker){$('.sj-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $('#packing_select').select2({
    width:'100%', placeholder:'Cari Packing List...', minimumInputLength:1,
    ajax:{url:'<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=packing_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}
  }).on('select2:select',function(e){
    var x=e.params.data;
    $('#packing_list_id').val(x.id||''); $('#delivery_id').val(x.delivery_id||''); $('#delivery_no').val(x.delivery_no||''); $('#picking_no').val(x.picking_no||''); $('#gi_id').val(x.gi_id||''); $('#gi_no').val(x.gi_no||''); $('#movement_type').val(x.movement_type||'601');
    sjSetText('#pl_text',x.no_packing_list); sjSetText('#delivery_text',x.delivery_no); sjSetText('#so_text',x.no_sales_order); sjSetText('#customer_text',(x.customer_code||'')+' - '+(x.customer_name||''));
    $('#no_invoice').val(x.no_invoice||''); $('#no_po').val(x.no_po||''); $('#no_kendaraan').val(x.vehicle_no||''); $('#sopir').val(x.driver_name||''); $('#attn').val(x.customer_name||''); $('#alamat_pengiriman').val(x.customer_address||x.ship_to_address||''); $('#keterangan').val(x.remarks||'');
    $('#posting_date_display').val(x.posting_date||$('input[name="tgl_surat_jalan"]').val()); $('#movement_type_display').val(x.movement_type||'601'); $('#gi_no_display').val(x.gi_no||'-'); $('#shipping_point_display').val(x.shipping_point||'-'); $('#route_display').val(x.route||'-'); $('#carrier_display').val(x.carrier||'-');
    loadPackingItems(x.id);
  });
  $(document).on('keyup change','.sj-qty',sjValidate);
  $('#input_surat_jalan').on('submit',function(e){
    e.preventDefault();
    if(!sjValidate()){sjError('Qty kirim wajib diisi dan tidak boleh melebihi qty Packing List.');return;}
    $('#btn_save_sj').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=sd_h('common_saving', 'Saving...');?>');
    $(this).ajaxSubmit({
      dataType:'json',
      success:function(resp){
        var good=false,msg=''; $.each(resp||[],function(_,r){if(r.status==='good')good=true;if(r.status==='error')msg=r.error_message;});
        if(good){window.location='<?=base_index();?>surat-jalan';}
        else{sjError(msg||<?=sd_js('sales_surat_jalan_save_failed', 'Surat Jalan failed to save.');?>);$('#btn_save_sj').prop('disabled',false).html('<i class="fa fa-save"></i> Buat Surat Jalan');}
      },
      error:function(xhr){console.log(xhr.responseText);sjError(<?=sd_js('sales_surat_jalan_save_failed', 'Surat Jalan failed to save.');?>);$('#btn_save_sj').prop('disabled',false).html('<i class="fa fa-save"></i> Buat Surat Jalan');}
    });
  });
});
</script>
