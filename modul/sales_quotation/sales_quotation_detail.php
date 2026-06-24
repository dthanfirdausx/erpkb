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
if (!$data_edit) {
  echo '<section class="content"><div class="alert alert-danger">Sales Quotation tidak ditemukan.</div></section>';
  return;
}
$details = sq_detail_rows($db, $data_edit->id_quotation);
?>
<style>
.sq-detail-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:18px}
.sq-detail-hero h1{margin:0;font-size:24px}.sq-detail-hero p{margin:6px 0 0;opacity:.92}
.sq-info-table th{width:180px;background:#f8fafc}.sq-info-table th,.sq-info-table td{font-size:12px;vertical-align:top}
.sq-item-table th,.sq-item-table td{font-size:12px;vertical-align:middle}
</style>
<section class="content-header">
  <h1>Sales Quotation Detail <small><?=sq_h($data_edit->no_sales_quotation);?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>sales-quotation"><?=sd_h('sales_quotation', 'Sales Quotation');?></a></li><li class="active"><?=sd_h('common_detail', 'Detail');?></li></ol>
</section>
<section class="content">
  <div class="sq-detail-hero">
    <div class="row">
      <div class="col-sm-8"><h1><?=sq_h($data_edit->no_sales_quotation);?></h1><p><?=sq_h($data_edit->subject ?: $data_edit->catatan);?></p></div>
      <div class="col-sm-4 text-right"><?=sq_status_label($data_edit->status);?></div>
    </div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-info-circle"></i> Quotation Header</h3><div class="box-tools"><a href="<?=base_index();?>sales-quotation/edit/<?=intval($data_edit->id_quotation);?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> <?=sd_h('common_edit', 'Edit');?></a> <a href="<?=base_index();?>sales-quotation" class="btn btn-default btn-sm"><i class="fa fa-step-backward"></i> Back</a></div></div><div class="box-body">
    <div class="row">
      <div class="col-md-6"><table class="table table-bordered table-condensed sq-info-table">
        <tr><th><?=sd_h('sales_quotation_no', 'Quotation No');?></th><td><?=sq_h($data_edit->no_sales_quotation);?></td></tr>
        <tr><th>Quotation Date</th><td><?=sq_h($data_edit->tgl);?></td></tr>
        <tr><th>Valid Until</th><td><?=sq_h($data_edit->valid_date);?></td></tr>
        <tr><th>Requested Delivery</th><td><?=sq_h($data_edit->requested_delivery_date);?></td></tr>
        <tr><th>Inquiry Ref</th><td><?=sq_h($data_edit->inquiry_id ?: '-');?></td></tr>
        <tr><th>Sales Person</th><td><?=sq_h($data_edit->sales_id ?: $data_edit->user);?></td></tr>
      </table></div>
      <div class="col-md-6"><table class="table table-bordered table-condensed sq-info-table">
        <tr><th><?=sd_h('sales_customer', 'Customer');?></th><td><?=sq_h(trim((string)$data_edit->kode_penerima.' - '.(string)$data_edit->customer_name,' -'));?></td></tr>
        <tr><th>Contact</th><td><?=sq_h($data_edit->contact_person);?></td></tr>
        <tr><th><?=sd_h('sales_currency', 'Currency');?></th><td><?=sq_h($data_edit->currency);?></td></tr>
        <tr><th><?=sd_h('sales_tax', 'Tax');?></th><td><?=sq_h($data_edit->tax);?></td></tr>
        <tr><th><?=sd_h('sales_payment_term', 'Payment Term');?></th><td><?=sq_h($data_edit->payment_term ?: $data_edit->term);?></td></tr>
        <tr><th>Incoterm</th><td><?=sq_h($data_edit->incoterm);?></td></tr>
      </table></div>
    </div>
    <div class="alert alert-info"><strong>Remarks:</strong> <?=nl2br(sq_h($data_edit->catatan ?: '-'));?></div>
  </div></div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Quotation Items</h3></div><div class="box-body">
    <div class="table-responsive"><table class="table table-bordered table-striped table-condensed sq-item-table"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('sales_material', 'Material');?></th><th class="text-right"><?=sd_h('sales_qty', 'Qty');?></th><th><?=sd_h('sales_uom', 'UOM');?></th><th class="text-right"><?=sd_h('sales_price', 'Price');?></th><th class="text-right">Disc %</th><th class="text-right">Tax %</th><th class="text-right"><?=sd_h('sales_amount', 'Amount');?></th><th>Delivery</th><th><?=sd_h('common_remarks', 'Remarks');?></th></tr></thead><tbody>
      <?php $no=1; $totalQty=0; $totalAmount=0; foreach($details as $d){ $totalQty+=(float)$d->qty; $totalAmount+=(float)$d->nilai; ?>
      <tr>
        <td class="text-center"><?=$no++;?></td>
        <td><strong><?=sq_h($d->kd_barang);?></strong><br><small><?=sq_h($d->nm_barang);?></small></td>
        <td class="text-right"><?=number_format((float)$d->qty,5,',','.');?></td>
        <td><?=sq_h($d->uom ?: $d->satuan);?></td>
        <td class="text-right"><?=number_format((float)$d->price,2,',','.');?></td>
        <td class="text-right"><?=number_format((float)$d->discount_percent,2,',','.');?></td>
        <td class="text-right"><?=number_format((float)$d->tax_percent,2,',','.');?></td>
        <td class="text-right"><?=number_format((float)$d->nilai,2,',','.');?></td>
        <td><?=sq_h($d->requested_delivery_date);?></td>
        <td><?=sq_h($d->ket);?></td>
      </tr>
      <?php } ?>
    </tbody><tfoot><tr class="bg-gray"><th colspan="2" class="text-right"><?=sd_h('sales_total', 'Total');?></th><th class="text-right"><?=number_format($totalQty,5,',','.');?></th><th colspan="4"></th><th class="text-right"><?=number_format($totalAmount,2,',','.');?></th><th colspan="2"></th></tr></tfoot></table></div>
  </div></div>
</section>
