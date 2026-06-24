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
include_once "customer_inquiry_lib.php";
if (!$data_edit) {
  echo '<section class="content"><div class="alert alert-danger">Customer Inquiry tidak ditemukan.</div></section>';
  return;
}
$details = ciq_detail_rows($db, $data_edit->id);
?>
<style>
.ciq-detail-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:18px}
.ciq-detail-hero h1{margin:0;font-size:24px}.ciq-detail-hero p{margin:6px 0 0;opacity:.92}
.ciq-info-table th{width:180px;background:#f8fafc}.ciq-info-table th,.ciq-info-table td{font-size:12px;vertical-align:top}
.ciq-item-table th,.ciq-item-table td{font-size:12px;vertical-align:middle}
</style>
<section class="content-header">
  <h1>Customer Inquiry Detail <small><?=ciq_h($data_edit->inquiry_no);?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>customer-inquiry"><?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?></a></li><li class="active"><?=sd_h('common_detail', 'Detail');?></li></ol>
</section>
<section class="content">
  <div class="ciq-detail-hero">
    <div class="row">
      <div class="col-sm-8"><h1><?=ciq_h($data_edit->inquiry_no);?></h1><p><?=ciq_h($data_edit->subject);?></p></div>
      <div class="col-sm-4 text-right"><?=ciq_priority_label($data_edit->priority);?> <?=ciq_status_label($data_edit->status);?></div>
    </div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-info-circle"></i> Inquiry Header</h3><div class="box-tools"><a href="<?=base_index();?>customer-inquiry/edit/<?=intval($data_edit->id);?>" class="btn btn-warning btn-sm"><i class="fa fa-pencil"></i> <?=sd_h('common_edit', 'Edit');?></a> <a href="<?=base_index();?>customer-inquiry" class="btn btn-default btn-sm"><i class="fa fa-step-backward"></i> Back</a></div></div><div class="box-body">
    <div class="row">
      <div class="col-md-6"><table class="table table-bordered table-condensed ciq-info-table">
        <tr><th><?=sd_h('sales_inquiry_no', 'Inquiry No');?></th><td><?=ciq_h($data_edit->inquiry_no);?></td></tr>
        <tr><th>Inquiry Date</th><td><?=ciq_h($data_edit->inquiry_date);?></td></tr>
        <tr><th>Valid Until</th><td><?=ciq_h($data_edit->valid_until);?></td></tr>
        <tr><th>Requested Delivery</th><td><?=ciq_h($data_edit->requested_delivery_date);?></td></tr>
        <tr><th>Sales Person</th><td><?=ciq_h($data_edit->sales_person);?></td></tr>
        <tr><th><?=sd_h('sales_currency', 'Currency');?></th><td><?=ciq_h($data_edit->currency);?></td></tr>
      </table></div>
      <div class="col-md-6"><table class="table table-bordered table-condensed ciq-info-table">
        <tr><th><?=sd_h('sales_customer', 'Customer');?></th><td><?=ciq_h(trim((string)$data_edit->customer_code.' - '.(string)$data_edit->customer_name,' -'));?></td></tr>
        <tr><th>Contact</th><td><?=ciq_h($data_edit->contact_person);?></td></tr>
        <tr><th>Phone</th><td><?=ciq_h($data_edit->phone);?></td></tr>
        <tr><th>Email</th><td><?=ciq_h($data_edit->email);?></td></tr>
        <tr><th><?=sd_h('sales_payment_term', 'Payment Term');?></th><td><?=ciq_h($data_edit->payment_term);?></td></tr>
        <tr><th>Source</th><td><?=ciq_h($data_edit->source);?></td></tr>
      </table></div>
    </div>
    <div class="alert alert-info"><strong>Remarks:</strong> <?=nl2br(ciq_h($data_edit->remarks ?: '-'));?></div>
  </div></div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cubes"></i> Inquiry Items</h3></div><div class="box-body">
    <div class="table-responsive"><table class="table table-bordered table-striped table-condensed ciq-item-table"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('sales_material', 'Material');?></th><th>Description</th><th class="text-right"><?=sd_h('sales_qty', 'Qty');?></th><th><?=sd_h('sales_uom', 'UOM');?></th><th class="text-right">Target Price</th><th class="text-right">Estimated Amount</th><th>Requested Delivery</th><th><?=sd_h('common_remarks', 'Remarks');?></th></tr></thead><tbody>
      <?php $no=1; $totalQty=0; $totalAmount=0; foreach($details as $d){ $totalQty+=(float)$d->qty; $totalAmount+=(float)$d->estimated_amount; ?>
      <tr>
        <td class="text-center"><?=$no++;?></td>
        <td><strong><?=ciq_h($d->material_code ?: '-');?></strong><br><small><?=ciq_h($d->material_name ?: $d->nm_barang);?></small></td>
        <td><?=ciq_h($d->description);?></td>
        <td class="text-right"><?=number_format((float)$d->qty,5,',','.');?></td>
        <td><?=ciq_h($d->uom);?></td>
        <td class="text-right"><?=number_format((float)$d->target_price,2,',','.');?></td>
        <td class="text-right"><?=number_format((float)$d->estimated_amount,2,',','.');?></td>
        <td><?=ciq_h($d->requested_delivery_date);?></td>
        <td><?=ciq_h($d->remarks);?></td>
      </tr>
      <?php } ?>
    </tbody><tfoot><tr class="bg-gray"><th colspan="3" class="text-right"><?=sd_h('sales_total', 'Total');?></th><th class="text-right"><?=number_format($totalQty,5,',','.');?></th><th></th><th></th><th class="text-right"><?=number_format($totalAmount,2,',','.');?></th><th colspan="2"></th></tr></tfoot></table></div>
  </div></div>
</section>
