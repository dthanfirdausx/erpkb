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
include_once "packing_list_lib.php";
$isDeliveryFlow = !empty($data_edit->delivery_id);
$delivery = null;
$customerName = '';
if ($isDeliveryFlow) {
  $delivery = $db->fetch("SELECT * FROM erp_outbound_delivery WHERE id=? LIMIT 1", array((int)$data_edit->delivery_id));
  $customerName = $delivery ? $delivery->customer_name : '';
}
if ($customerName === '') {
  $customer = $db->fetch("SELECT nama FROM penerima WHERE kode_penerima=? LIMIT 1", array($data_edit->penerima));
  $customerName = $customer ? $customer->nama : '';
}
$detailRows = array();
if ($isDeliveryFlow) {
  $detailRows = $db->query(
    "SELECT d.*,odd.material_code,odd.material_name AS od_material_name,odd.delivery_qty AS od_delivery_qty,
            odd.picked_qty AS od_picked_qty,odd.packed_qty AS current_total_packed,odd.uom AS od_uom,odd.price
     FROM packing_list_detail d
     LEFT JOIN erp_outbound_delivery_detail odd ON odd.id=d.delivery_detail_id
     WHERE d.packing_list_id=?
     ORDER BY d.line_no,d.id",
    array((int)$data_edit->id)
  );
} else {
  $detailRows = $db->query(
    "SELECT d.*,b.nm_barang,b.satuan
     FROM packing_list_detail d
     LEFT JOIN barang b ON b.kd_barang=d.kode
     WHERE d.no_sj=?
     ORDER BY d.row_no,d.id",
    array($data_edit->no_sj)
  );
}
?>
<style>
.pl-page-hero{border-radius:14px;background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;padding:18px 20px;margin-bottom:16px;box-shadow:0 10px 24px rgba(30,64,175,.18)}
.pl-page-hero h3{margin:0;font-weight:700}.pl-page-hero p{margin:6px 0 0;opacity:.9}
.pl-card{border-radius:14px;border:1px solid #e5edf5;background:#fff;margin-bottom:16px;box-shadow:0 6px 18px rgba(15,23,42,.06)}
.pl-card .pl-card-head{padding:14px 16px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.pl-card .pl-card-head h4{margin:0;font-size:15px;font-weight:700}.pl-card .pl-card-body{padding:16px}
.pl-step{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#1d4ed8;color:#fff;font-weight:700;margin-right:8px}
.pl-help{color:#64748b;font-size:12px;margin-top:5px}.pl-readonly{background:#f8fafc!important;color:#334155!important}
.pl-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:8px}
.pl-summary-box{border:1px solid #e5edf5;background:#f8fafc;border-radius:10px;padding:10px 12px;min-height:58px}
.pl-summary-box span{display:block;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}.pl-summary-box strong{display:block;margin-top:3px;color:#0f172a;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pl-item-table{margin-bottom:0}.pl-item-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc!important;color:#475569;vertical-align:middle}.pl-item-table td{font-size:12px;vertical-align:middle}
.pl-item-table .form-control{font-size:12px;height:30px;padding:4px 7px}.pl-item-table .material-name{color:#64748b;font-size:11px}
.pl-sticky-actions{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5edf5;padding:12px 0;margin-top:8px;z-index:5}
.has-error .form-control{border-color:#dd4b39}
@media(max-width:991px){.pl-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){.pl-summary{grid-template-columns:1fr}.pl-card .pl-card-head{display:block}.pl-page-hero{padding:16px}}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_packing_list', 'Packing List');?> <small><?=sd_h('common_edit', 'Edit');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>packing-list"><?=sd_h('sales_packing_list', 'Packing List');?></a></li>
    <li class="active">Edit Packing List</li>
  </ol>
</section>
<section class="content">
  <div class="pl-page-hero">
    <h3><i class="fa fa-pencil"></i> Edit Packing List</h3>
    <p>Delivery dikunci setelah Packing List dibuat. Koreksi dilakukan pada informasi packing dan quantity item.</p>
  </div>
  <div class="alert alert-danger error_data" style="display:none">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <span class="isi_warning"></span>
  </div>
  <form id="edit_packing_list" method="post" action="<?=base_admin();?>modul/packing_list/packing_list_action.php?act=up">
    <input type="hidden" name="id" value="<?=intval($data_edit->id);?>">
    <?php if ($isDeliveryFlow) { ?>
      <input type="hidden" name="delivery_id" value="<?=intval($data_edit->delivery_id);?>">
      <input type="hidden" name="picking_id" value="<?=intval($data_edit->picking_id);?>">
      <input type="hidden" name="picking_no" value="<?=pl_h($data_edit->picking_no);?>">
      <input type="hidden" name="penerima" value="<?=pl_h($data_edit->penerima);?>">
      <input type="hidden" name="valuta" value="<?=pl_h($data_edit->valuta ?: 'IDR');?>">
      <input type="hidden" name="kurs" value="<?=pl_h($data_edit->kurs ?: 1);?>">
    <?php } ?>

    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">1</span><?=sd_h('sales_reference', 'Reference');?></h4>
        <span class="label label-success"><?=pl_h($data_edit->status ?: 'PACKED');?></span>
      </div>
      <div class="pl-card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>Packing List No <span class="text-red">*</span></label>
              <input type="text" name="no_packing_list" value="<?=pl_h($data_edit->no_packing_list);?>" class="form-control" required>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Packing Date <span class="text-red">*</span></label>
              <input type="text" class="form-control pl-date" value="<?=pl_h($data_edit->tgl_sj);?>" name="tgl_sj" readonly required>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Surat Jalan Ref</label>
              <input type="text" name="no_sj" value="<?=pl_h($data_edit->no_sj);?>" class="form-control">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Vehicle No</label>
              <input type="text" name="vehicle_no" value="<?=pl_h($data_edit->vehicle_no);?>" class="form-control">
            </div>
          </div>
        </div>
        <div class="pl-summary">
          <div class="pl-summary-box"><span><?=sd_h('sales_delivery_no', 'Delivery No');?></span><strong><?=pl_h($data_edit->delivery_no ?: '-');?></strong></div>
          <div class="pl-summary-box"><span>Picking No</span><strong><?=pl_h($data_edit->picking_no ?: '-');?></strong></div>
          <div class="pl-summary-box"><span><?=sd_h('sales_customer', 'Customer');?></span><strong><?=pl_h(trim(($data_edit->penerima ?: '').' - '.$customerName, ' -'));?></strong></div>
          <div class="pl-summary-box"><span>Mode</span><strong><?=$isDeliveryFlow ? 'Outbound Delivery Flow' : 'Legacy Surat Jalan';?></strong></div>
        </div>
      </div>
    </div>

    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">2</span>Shipping Information</h4>
      </div>
      <div class="pl-card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label><?=sd_h('sales_invoice_no', 'Invoice No');?></label>
              <input type="text" name="no_invoice" value="<?=pl_h($data_edit->no_invoice);?>" class="form-control">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>No PO Customer</label>
              <input type="text" name="no_po" value="<?=pl_h($data_edit->no_po);?>" class="form-control">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>Packed By</label>
              <input type="text" value="<?=pl_h($data_edit->packed_by ?: '-');?>" class="form-control pl-readonly" readonly>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label><?=sd_h('common_remarks', 'Remarks');?></label>
          <textarea name="remarks" class="form-control" rows="2" placeholder="Catatan packing"><?=pl_h($data_edit->remarks);?></textarea>
        </div>
      </div>
    </div>

    <div class="pl-card">
      <div class="pl-card-head">
        <h4><span class="pl-step">3</span>Packing Items</h4>
        <small class="text-muted"><?=$isDeliveryFlow ? 'Maksimum = picked qty yang tersedia termasuk qty dokumen ini.' : 'Data lama tetap bisa dikoreksi.';?></small>
      </div>
      <div class="pl-card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed pl-item-table">
            <thead>
              <tr>
                <th style="width:44px" class="text-center"><?=sd_h('common_no', 'No');?></th>
                <th style="min-width:220px"><?=sd_h('sales_material', 'Material');?></th>
                <th class="text-right">Delivery</th>
                <th class="text-right">Picked</th>
                <th class="text-right">Total Packed</th>
                <th style="width:120px" class="text-right">Pack Qty</th>
                <th style="width:70px"><?=sd_h('sales_uom', 'UOM');?></th>
                <th style="width:150px">Packing Type</th>
                <th style="width:120px">Qty Pack</th>
                <th style="min-width:160px">Remark</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            foreach ($detailRows as $row) {
              $materialCode = $isDeliveryFlow ? ($row->material_code ?: $row->kode) : $row->kode;
              $materialName = $isDeliveryFlow ? ($row->od_material_name ?: $row->material_name) : ($row->material_name ?: $row->nm_barang);
              $deliveryQty = $isDeliveryFlow ? $row->od_delivery_qty : $row->jumlah;
              $pickedQty = $isDeliveryFlow ? $row->od_picked_qty : $row->jumlah;
              $currentTotalPacked = $isDeliveryFlow ? (float)$row->current_total_packed : (float)$row->jumlah;
              $currentQty = (float)$row->jumlah;
              $maxQty = $isDeliveryFlow ? max(0, $currentQty + ((float)$row->od_picked_qty - $currentTotalPacked)) : 999999999;
              $unit = $isDeliveryFlow ? ($row->od_uom ?: $row->unit) : ($row->unit ?: $row->satuan);
            ?>
              <tr>
                <td class="text-center">
                  <?=intval($no);?>
                  <input type="hidden" name="delivery_detail_id[]" value="<?=intval($row->delivery_detail_id);?>">
                  <input type="hidden" name="kode_input[]" value="<?=pl_h($materialCode);?>">
                  <input type="hidden" name="material_name[]" value="<?=pl_h($materialName);?>">
                  <input type="hidden" name="line_no[]" value="<?=intval($row->line_no ?: $no);?>">
                  <input type="hidden" name="delivery_qty[]" value="<?=pl_h($deliveryQty);?>">
                  <input type="hidden" name="picked_qty[]" value="<?=pl_h($pickedQty);?>">
                </td>
                <td><strong><?=pl_h($materialCode);?></strong><div class="material-name"><?=pl_h($materialName);?></div></td>
                <td class="text-right"><?=pl_num($deliveryQty);?></td>
                <td class="text-right"><?=pl_num($pickedQty);?></td>
                <td class="text-right"><?=pl_num($currentTotalPacked);?></td>
                <td><input type="text" name="jumlah[]" class="form-control input-sm text-right pl-pack-qty" value="<?=pl_h(number_format($currentQty,5,'.',''));?>" data-max="<?=pl_h($maxQty);?>"></td>
                <td><?=pl_h($unit);?><input type="hidden" name="unit[]" value="<?=pl_h($unit);?>"></td>
                <td><input type="text" name="packing[]" class="form-control input-sm" value="<?=pl_h($row->packing);?>" placeholder="Carton / pallet / roll"></td>
                <td><input type="text" name="qty_packing[]" class="form-control input-sm" value="<?=pl_h($row->qty_packing);?>" placeholder="Jumlah kemasan"></td>
                <td><input type="text" name="remark[]" class="form-control input-sm" value="<?=pl_h($row->remark);?>"></td>
              </tr>
            <?php $no++; } ?>
            <?php if ($no === 1) { ?>
              <tr><td colspan="10" class="text-center text-muted">Detail item tidak ditemukan.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="pl-sticky-actions">
      <a href="<?=base_index();?>packing-list" class="btn btn-default"><i class="fa fa-step-backward"></i> <?=$lang["back_button"];?></a>
      <button type="submit" id="btn_save_pl" class="btn btn-primary"><i class="fa fa-save"></i> <?=$lang["submit_button"];?></button>
    </div>
  </form>
</section>
<script>
function plError(msg){$('.isi_warning').text(msg||<?=sd_js('sales_packing_list_process_failed', 'Packing List failed to process.');?>);$('.error_data').fadeIn();$('html,body').animate({scrollTop:$('.error_data').offset().top-80},250);}
function plValidateItems(){
  var hasQty=false, ok=true;
  $('.pl-pack-qty').each(function(){
    var q=parseFloat(String($(this).val()||'0').replace(',','.'))||0;
    var max=parseFloat($(this).data('max'))||0;
    if(q>0) hasQty=true;
    if(q<0||q>max+0.00001){ok=false;$(this).closest('td').addClass('has-error');}else{$(this).closest('td').removeClass('has-error');}
  });
  $('#btn_save_pl').prop('disabled',!(hasQty&&ok));
  return hasQty&&ok;
}
$(function(){
  if($.fn.datepicker){$('.pl-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $(document).on('keyup change','.pl-pack-qty',plValidateItems);
  plValidateItems();
  $('#edit_packing_list').on('submit',function(e){
    e.preventDefault();
    if(!plValidateItems()){plError('Minimal satu item harus punya Pack Qty dan tidak boleh melebihi qty tersedia.');return;}
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
