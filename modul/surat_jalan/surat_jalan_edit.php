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
function sj_edit_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$customer = $db->fetch("SELECT nama FROM penerima WHERE kode_penerima=? LIMIT 1", array($data_edit->kode_penerima));
$q_detail = $db->query("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id=? ORDER BY row_no,id", array((int)$data_edit->id));
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
.sj-item-table .form-control{font-size:12px;height:30px;padding:4px 7px}.sj-readonly{background:#f8fafc!important;color:#334155!important}.has-error .form-control{border-color:#dd4b39}
.sj-sticky-actions{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5edf5;padding:12px 0;margin-top:8px;z-index:5}
@media(max-width:991px){.sj-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.sj-summary{grid-template-columns:1fr}}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_surat_jalan', 'Surat Jalan');?> <small><?=sd_h('common_edit', 'Edit');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>surat-jalan"><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></a></li>
    <li class="active"><?=sd_h('common_edit', 'Edit');?></li>
  </ol>
</section>
<section class="content">
  <div class="sj-page-hero">
    <h3><i class="fa fa-pencil"></i> Edit Surat Jalan</h3>
    <p>Referensi Packing List dikunci. Koreksi hanya untuk informasi pengiriman dan detail dokumen.</p>
  </div>
  <div class="alert alert-danger error_data" style="display:none"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="isi_warning"></span></div>
  <form id="edit_surat_jalan" method="post" action="<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=up">
    <input type="hidden" name="id" value="<?=intval($data_edit->id);?>">
    <div class="sj-card">
      <div class="sj-card-head">
        <h4><span class="sj-step">1</span><?=sd_h('sales_reference', 'Reference');?></h4>
        <span class="label label-<?=$data_edit->status === 'draft' ? 'default' : 'info';?>"><?=sj_edit_h(strtoupper($data_edit->status));?></span>
      </div>
      <div class="sj-card-body">
        <div class="row">
          <div class="col-md-3"><div class="form-group"><label>No Surat Jalan</label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->no_surat_jalan);?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Tanggal Surat Jalan</label><input type="text" name="tgl_surat_jalan" class="form-control sj-date" value="<?=sj_edit_h($data_edit->tgl_surat_jalan);?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label><?=sd_h('sales_document_date', 'Document Date');?></label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->document_date ?: $data_edit->tgl_surat_jalan);?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label><?=sd_h('sales_posting_date', 'Posting Date');?></label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->posting_date ?: $data_edit->tgl_surat_jalan);?>" readonly></div></div>
        </div>
        <div class="row">
          <div class="col-md-3"><div class="form-group"><label><?=sd_h('common_status', 'Status');?></label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h(strtoupper($data_edit->status));?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Total Qty</label><input type="text" class="form-control sj-readonly text-right" value="<?=number_format((float)$data_edit->total_qty,4,',','.');?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Movement Type</label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->movement_type ?: '601');?>" readonly></div></div>
          <div class="col-md-3"><div class="form-group"><label>Goods Issue</label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->gi_no ?: '-');?>" readonly></div></div>
        </div>
        <div class="sj-summary">
          <div class="sj-summary-box"><span><?=sd_h('sales_packing_list', 'Packing List');?></span><strong><?=sj_edit_h($data_edit->packing_list_no ?: '-');?></strong></div>
          <div class="sj-summary-box"><span><?=sd_h('sales_outbound_delivery', 'Outbound Delivery');?></span><strong><?=sj_edit_h($data_edit->delivery_no ?: '-');?></strong></div>
          <div class="sj-summary-box"><span><?=sd_h('sales_order', 'Sales Order');?></span><strong><?=sj_edit_h($data_edit->no_sales_order ?: '-');?></strong></div>
          <div class="sj-summary-box"><span>Print Count</span><strong><?=number_format((float)$data_edit->print_count,0,',','.');?>x</strong></div>
        </div>
      </div>
    </div>

    <div class="sj-card">
      <div class="sj-card-head"><h4><span class="sj-step">2</span>Shipping Information</h4></div>
      <div class="sj-card-body">
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label>No Invoice</label><input type="text" name="no_invoice" value="<?=sj_edit_h($data_edit->no_invoice);?>" class="form-control"></div></div>
          <div class="col-md-4"><div class="form-group"><label>No PO Customer</label><input type="text" name="no_po" value="<?=sj_edit_h($data_edit->no_po);?>" class="form-control"></div></div>
          <div class="col-md-4"><div class="form-group"><label>No Kendaraan</label><input type="text" name="no_kendaraan" value="<?=sj_edit_h($data_edit->no_kendaraan);?>" class="form-control"></div></div>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label><?=sd_h('sales_shipping_point', 'Shipping Point');?></label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->shipping_point ?: '-');?>" readonly></div></div>
          <div class="col-md-4"><div class="form-group"><label>Route</label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->route ?: '-');?>" readonly></div></div>
          <div class="col-md-4"><div class="form-group"><label>Carrier</label><input type="text" class="form-control sj-readonly" value="<?=sj_edit_h($data_edit->carrier ?: '-');?>" readonly></div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="form-group"><label>Sopir</label><input type="text" name="sopir" value="<?=sj_edit_h($data_edit->sopir);?>" class="form-control"></div></div>
          <div class="col-md-6"><div class="form-group"><label>Attn</label><input type="text" name="attn" value="<?=sj_edit_h($data_edit->attn);?>" class="form-control"></div></div>
        </div>
        <div class="form-group"><label>Alamat Pengiriman</label><textarea name="alamat_pengiriman" class="form-control" rows="3"><?=sj_edit_h($data_edit->alamat_pengiriman);?></textarea></div>
        <div class="form-group"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="2"><?=sj_edit_h($data_edit->keterangan);?></textarea></div>
      </div>
    </div>

    <div class="sj-card">
      <div class="sj-card-head"><h4><span class="sj-step">3</span>Item Surat Jalan</h4></div>
      <div class="sj-card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed sj-item-table">
            <thead><tr><th><?=sd_h('common_no', 'No');?></th><th>Kode</th><th>Nama Barang</th><th class="text-right">Delivery Qty</th><th>Packing</th><th>Qty Packing</th><th class="text-right">Qty Kirim</th><th><?=sd_h('sales_uom', 'UOM');?></th><th>Trace</th><th>Keterangan</th></tr></thead>
            <tbody>
              <?php $no=1; foreach ($q_detail as $d) { ?>
                <tr>
                  <td class="text-center"><?=intval($no);?></td>
                  <td><strong><?=sj_edit_h($d->kode_barang);?></strong>
                    <input type="hidden" name="line_no[]" value="<?=intval($d->line_no ?: ($no * 10));?>">
                    <input type="hidden" name="packing_list_detail_id[]" value="<?=intval($d->packing_list_detail_id);?>">
                    <input type="hidden" name="delivery_detail_id[]" value="<?=intval($d->delivery_detail_id);?>">
                    <input type="hidden" name="gi_detail_id[]" value="<?=intval($d->gi_detail_id);?>">
                    <input type="hidden" name="id_detail[]" value="<?=intval($d->id_sales_order_detail);?>">
                    <input type="hidden" name="kode_barang[]" value="<?=sj_edit_h($d->kode_barang);?>">
                    <input type="hidden" name="material_code[]" value="<?=sj_edit_h($d->material_code ?: $d->kode_barang);?>">
                    <input type="hidden" name="batch_no[]" value="<?=sj_edit_h($d->batch_no);?>">
                    <input type="hidden" name="lot_no[]" value="<?=sj_edit_h($d->lot_no);?>">
                    <input type="hidden" name="plant_id[]" value="<?=intval($d->plant_id);?>">
                    <input type="hidden" name="storage_location_id[]" value="<?=intval($d->storage_location_id);?>">
                    <input type="hidden" name="storage_bin_id[]" value="<?=intval($d->storage_bin_id);?>">
                    <input type="hidden" name="stock_type[]" value="<?=sj_edit_h($d->stock_type ?: 'UNRESTRICTED');?>">
                    <input type="hidden" name="bc_document_type[]" value="<?=sj_edit_h($d->bc_document_type);?>">
                    <input type="hidden" name="bc_document_no[]" value="<?=sj_edit_h($d->bc_document_no);?>">
                    <input type="hidden" name="bc_document_date[]" value="<?=sj_edit_h($d->bc_document_date);?>">
                    <input type="hidden" name="hs_code[]" value="<?=sj_edit_h($d->hs_code);?>">
                    <input type="hidden" name="net_weight[]" value="<?=sj_edit_h($d->net_weight);?>">
                    <input type="hidden" name="gross_weight[]" value="<?=sj_edit_h($d->gross_weight);?>">
                  </td>
                  <td><?=sj_edit_h($d->nama_barang);?><input type="hidden" name="nama_barang[]" value="<?=sj_edit_h($d->nama_barang);?>"><input type="hidden" name="material_name[]" value="<?=sj_edit_h($d->material_name ?: $d->nama_barang);?>"></td>
                  <td class="text-right"><?=number_format((float)$d->qty_order,4,',','.');?><input type="hidden" name="qty_order[]" value="<?=sj_edit_h($d->qty_order);?>"></td>
                  <td><input type="text" name="packing[]" class="form-control input-sm" value="<?=sj_edit_h($d->packing);?>"></td>
                  <td><input type="text" name="satuan_packing[]" class="form-control input-sm" value="<?=sj_edit_h($d->satuan_packing);?>"></td>
                  <td><input type="text" name="qty_kirim[]" class="form-control input-sm text-right sj-qty" value="<?=sj_edit_h(number_format((float)$d->qty_kirim,4,'.',''));?>" data-max="<?=sj_edit_h($d->qty_kirim);?>"></td>
                  <td><?=sj_edit_h($d->satuan);?><input type="hidden" name="satuan[]" value="<?=sj_edit_h($d->satuan);?>"></td>
                  <td><small>Lot: <?=sj_edit_h($d->lot_no ?: '-');?><br>BC: <?=sj_edit_h(trim($d->bc_document_type.' '.$d->bc_document_no) ?: '-');?><br>Stock: <?=sj_edit_h($d->stock_type ?: '-');?></small></td>
                  <td><input type="text" name="keterangan_barang[]" class="form-control input-sm" value="<?=sj_edit_h($d->keterangan);?>"></td>
                </tr>
              <?php $no++; } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="sj-sticky-actions">
      <a href="<?=base_index();?>surat-jalan" class="btn btn-default"><i class="fa fa-step-backward"></i> Kembali</a>
      <?php if ($data_edit->status === 'draft') { ?><button type="submit" id="btn_save_sj" class="btn btn-primary"><i class="fa fa-save"></i> <?=sd_h('common_update', 'Update');?></button><?php } ?>
    </div>
  </form>
</section>
<script>
function sjError(msg){$('.isi_warning').text(msg||<?=sd_js('sales_surat_jalan_process_failed', 'Surat Jalan failed to process.');?>);$('.error_data').fadeIn();$('html,body').animate({scrollTop:$('.error_data').offset().top-80},250);}
function sjValidate(){var ok=true,hasQty=false;$('.sj-qty').each(function(){var qty=parseFloat(String($(this).val()||'0').replace(',','.'))||0;var max=parseFloat($(this).data('max'))||0;if(qty>0)hasQty=true;if(qty<0||qty>max+0.00001){ok=false;$(this).closest('td').addClass('has-error');}else{$(this).closest('td').removeClass('has-error');}});$('#btn_save_sj').prop('disabled',!(ok&&hasQty));return ok&&hasQty;}
$(function(){
  if($.fn.datepicker){$('.sj-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  $(document).on('keyup change','.sj-qty',sjValidate); sjValidate();
  $('#edit_surat_jalan').on('submit',function(e){e.preventDefault();if(!sjValidate()){sjError(<?=sd_js('sales_delivery_qty_invalid', 'Delivery qty is required and cannot exceed document qty.');?>);return;}$('#btn_save_sj').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=sd_h('common_saving', 'Saving...');?>');$(this).ajaxSubmit({dataType:'json',success:function(resp){var good=false,msg='';$.each(resp||[],function(_,r){if(r.status==='good')good=true;if(r.status==='error')msg=r.error_message;});if(good){window.location='<?=base_index();?>surat-jalan';}else{sjError(msg||<?=sd_js('sales_surat_jalan_save_failed', 'Surat Jalan failed to save.');?>);$('#btn_save_sj').prop('disabled',false).html('<i class="fa fa-save"></i> Update');}},error:function(xhr){console.log(xhr.responseText);sjError(<?=sd_js('sales_surat_jalan_save_failed', 'Surat Jalan failed to save.');?>);$('#btn_save_sj').prop('disabled',false).html('<i class="fa fa-save"></i> Update');}});});
});
</script>
