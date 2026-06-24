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
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
include "goods_issue_delivery_lib.php";
session_check_json();

function gid_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = gid_user();

if ($act === 'delivery_search') {
  $term = gid_input('term');
  $kw = '%'.$term.'%';
  $rows = $db->query(
    "SELECT od.*,COALESCE(SUM(d.delivery_qty-d.gi_qty),0) open_qty
     FROM erp_outbound_delivery od
     JOIN erp_outbound_delivery_detail d ON d.delivery_id=od.id
     WHERE od.status NOT IN ('CANCELLED','PGI','COMPLETED')
       AND od.gi_status <> 'POSTED'
       AND (?='' OR od.delivery_no LIKE ? OR od.no_sales_order LIKE ? OR od.customer_name LIKE ? OR od.customer_code LIKE ?)
     GROUP BY od.id
     HAVING open_qty>0
     ORDER BY od.delivery_date DESC,od.id DESC
     LIMIT 30",
    array($term,$kw,$kw,$kw,$kw)
  );
  $results = array();
  foreach ($rows as $r) {
    $results[] = array(
      'id'=>$r->id,
      'text'=>$r->delivery_no.' - '.$r->customer_name.' - Open '.gid_qty($r->open_qty),
      'delivery_no'=>$r->delivery_no,
      'customer'=>$r->customer_code.' - '.$r->customer_name,
      'shipping_point'=>$r->shipping_point,
      'vehicle_no'=>$r->vehicle_no,
      'driver_name'=>$r->driver_name,
      'reference_surat_jalan'=>$r->reference_surat_jalan
    );
  }
  gid_json('good', '', array('results'=>$results));
}

if ($act === 'delivery_items') {
  $id = (int)gid_input('delivery_id');
  $rows = $db->query(
    "SELECT d.*,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty
     FROM erp_outbound_delivery_detail d
     LEFT JOIN barang b ON b.kd_barang=d.material_code
     LEFT JOIN stock_layer sl ON sl.kode=d.material_code AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED'
     WHERE d.delivery_id=?
     GROUP BY d.id
     ORDER BY d.line_no,d.id",
    array($id)
  );
  $no = 1; $count = 0;
  foreach ($rows as $r) {
    $openQty = max(0, (float)$r->delivery_qty - (float)$r->gi_qty);
    if ($openQty <= 0) continue;
    $count++;
    ?>
    <tr>
      <td class="text-center"><?=intval($no++);?><input type="hidden" name="delivery_detail_id[]" value="<?=intval($r->id);?>"><input type="hidden" name="material_code[]" value="<?=gid_h($r->material_code);?>"></td>
      <td><strong><?=gid_h($r->material_code);?></strong><br><small><?=gid_h($r->material_name);?></small></td>
      <td class="text-right"><?=gid_qty($r->delivery_qty);?></td>
      <td class="text-right"><?=gid_qty($r->gi_qty);?></td>
      <td><input name="gi_qty[]" class="form-control input-sm text-right gid-qty" value="<?=gid_h(number_format($openQty,5,'.',''));?>" data-max="<?=gid_h($openQty);?>"></td>
      <td><?=gid_h($r->uom ?: $r->satuan);?><input type="hidden" name="uom[]" value="<?=gid_h($r->uom ?: $r->satuan);?>"></td>
      <td class="text-right"><?=gid_qty($r->stock_qty);?></td>
      <td><input name="item_remarks[]" class="form-control input-sm" value="<?=gid_h($r->remarks);?>"></td>
    </tr>
    <?php
  }
  if ($count === 0) echo '<tr><td colspan="8" class="text-center text-muted">Delivery tidak memiliki open quantity untuk GI.</td></tr>';
  exit;
}

if ($act === 'customs_purpose') {
  $jenis = gid_input('jenis_dokpab');
  $rows = $db->query(
    "SELECT kdd_catatan,nd_catatan,jenis_dokpab,kod_dokpab
     FROM detail_catatan
     WHERE status=2 AND (?='' OR jenis_dokpab=?)
     ORDER BY jenis_dokpab,kdd_catatan",
    array($jenis,$jenis)
  );
  $results = array();
  foreach ($rows as $r) {
    $results[] = array(
      'id' => $r->kdd_catatan,
      'text' => $r->nd_catatan,
      'purpose' => $r->nd_catatan,
      'jenis_dokpab' => $r->jenis_dokpab,
      'kod_dokpab' => $r->kod_dokpab
    );
  }
  gid_json('good', '', array('results'=>$results));
}

if ($act === 'post') {
  $deliveryId = (int)gid_input('delivery_id');
  $documentDate = gid_date(gid_input('document_date'), '');
  $postingDate = gid_date(gid_input('posting_date'), '');
  $remarks = gid_input('remarks');
  $outboundBcType = gid_input('outbound_bc_type');
  $outboundBcPurposeCode = gid_input('outbound_bc_purpose_code');
  $outboundBcPurpose = gid_input('outbound_bc_purpose');
  $outboundNoAju = gid_input('outbound_no_aju');
  $outboundTglAju = gid_date(gid_input('outbound_tgl_aju'), '');
  $outboundNoDaftar = gid_input('outbound_no_daftar');
  $outboundTglDaftar = gid_date(gid_input('outbound_tgl_daftar'), '');
  $outboundCustomsOffice = gid_input('outbound_customs_office');
  $outboundDestinationCountry = gid_input('outbound_destination_country');
  $outboundCustomsRemarks = gid_input('outbound_customs_remarks');
  if ($deliveryId <= 0) gid_json('error', 'Outbound Delivery wajib dipilih.');
  if ($documentDate === '' || $postingDate === '') gid_json('error', 'Document Date dan Posting Date wajib valid.');
  if ($outboundBcType === '') gid_json('error', 'Jenis Dokumen BC Keluar wajib dipilih.');
  if ($outboundBcPurpose === '') gid_json('error', 'Tujuan pengeluaran dokumen BC wajib diisi.');
  if ($outboundNoAju !== '' && $outboundTglAju === '') gid_json('error', 'Tanggal Aju wajib valid jika No Aju diisi.');
  if ($outboundNoDaftar !== '' && $outboundTglDaftar === '') gid_json('error', 'Tanggal Daftar wajib valid jika No Daftar diisi.');
  if (empty($_POST['delivery_detail_id']) || !is_array($_POST['delivery_detail_id'])) gid_json('error', 'Minimal satu item delivery wajib diposting.');

  $delivery = $db->fetch("SELECT * FROM erp_outbound_delivery WHERE id=? LIMIT 1", array($deliveryId));
  if (!$delivery) gid_json('error', 'Outbound Delivery tidak ditemukan.');
  if (in_array($delivery->status, array('CANCELLED','PGI','COMPLETED'))) gid_json('error', 'Delivery sudah selesai/cancelled, tidak bisa posting GI.');
  if ($delivery->gi_status === 'POSTED') gid_json('error', 'Delivery sudah posting GI.');

  $items = array();
  foreach ($_POST['delivery_detail_id'] as $idx => $detailId) {
    $qty = isset($_POST['gi_qty'][$idx]) ? (float)str_replace(',', '.', $_POST['gi_qty'][$idx]) : 0;
    if ($qty > 0) {
      $items[] = array('detail_id'=>(int)$detailId,'qty'=>$qty,'remarks'=>isset($_POST['item_remarks'][$idx]) ? trim($_POST['item_remarks'][$idx]) : '');
    }
  }
  if (!$items) gid_json('error', 'GI Qty wajib lebih dari nol.');

  $db->query('START TRANSACTION');
  $giNo = gid_next_no($db, $postingDate);
  if (!$db->insert('erp_goods_issue_delivery', array(
    'gi_no'=>$giNo,
    'delivery_id'=>$delivery->id,
    'delivery_no'=>$delivery->delivery_no,
    'id_sales_order'=>$delivery->id_sales_order,
    'no_sales_order'=>$delivery->no_sales_order,
    'customer_code'=>$delivery->customer_code,
    'customer_name'=>$delivery->customer_name,
    'document_date'=>$documentDate,
    'posting_date'=>$postingDate,
    'movement_type'=>'601',
    'shipping_point'=>$delivery->shipping_point,
    'vehicle_no'=>$delivery->vehicle_no,
    'driver_name'=>$delivery->driver_name,
    'reference_surat_jalan'=>$delivery->reference_surat_jalan,
    'outbound_bc_type'=>$outboundBcType,
    'outbound_bc_purpose_code'=>$outboundBcPurposeCode,
    'outbound_bc_purpose'=>$outboundBcPurpose,
    'outbound_no_aju'=>$outboundNoAju,
    'outbound_tgl_aju'=>$outboundTglAju ?: null,
    'outbound_no_daftar'=>$outboundNoDaftar,
    'outbound_tgl_daftar'=>$outboundTglDaftar ?: null,
    'outbound_customs_office'=>$outboundCustomsOffice,
    'outbound_destination_country'=>$outboundDestinationCountry,
    'outbound_customs_remarks'=>$outboundCustomsRemarks,
    'status'=>'POSTED',
    'remarks'=>$remarks,
    'created_by'=>$username
  ))) {
    $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: sd_t('sales_gi_delivery_header_save_failed', 'GI Delivery header failed to save.'));
  }
  $giId = $db->last_insert_id();
  $lineNo = 10; $totalQty = 0; $totalAmount = 0; $accountingItems = array();

  foreach ($items as $item) {
    $d = $db->fetch("SELECT d.*,b.kd_kategori,b.satuan FROM erp_outbound_delivery_detail d LEFT JOIN barang b ON b.kd_barang=d.material_code WHERE d.id=? AND d.delivery_id=? LIMIT 1", array($item['detail_id'],$deliveryId));
    if (!$d) { $db->query('ROLLBACK'); gid_json('error', 'Detail delivery tidak valid.'); }
    $openQty = (float)$d->delivery_qty - (float)$d->gi_qty;
    if ($item['qty'] > $openQty + 0.00001) { $db->query('ROLLBACK'); gid_json('error', 'GI Qty '.$d->material_code.' melebihi open qty.'); }
    $available = $db->fetch("SELECT COALESCE(SUM(qty_sisa),0) available_qty FROM stock_layer WHERE kode=? AND qty_sisa>0 AND lokasi='GUDANG' AND COALESCE(stock_type,'UNRESTRICTED')='UNRESTRICTED'", array($d->material_code));
    if (!$available || (float)$available->available_qty + 0.00001 < $item['qty']) {
      $db->query('ROLLBACK'); gid_json('error', 'Stock tidak cukup untuk '.$d->material_code.'. Available '.gid_qty($available ? $available->available_qty : 0).', request '.gid_qty($item['qty']).'.');
    }
    if (!$db->insert('erp_goods_issue_delivery_detail', array(
      'gi_id'=>$giId,
      'delivery_detail_id'=>$d->id,
      'line_no'=>$lineNo,
      'material_code'=>$d->material_code,
      'material_name'=>$d->material_name,
      'qty'=>$item['qty'],
      'uom'=>$d->uom ?: $d->satuan,
      'stock_type'=>'UNRESTRICTED',
      'remarks'=>$item['remarks']
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: sd_t('sales_gi_delivery_detail_save_failed', 'GI Delivery detail failed to save.'));
    }
    $giDetailId = $db->last_insert_id();
    $remaining = $item['qty']; $detailAmount = 0; $weighted = 0;
    $layers = gid_fetch_layers($db, $d->material_code, true);
    foreach ($layers as $layer) {
      if ($remaining <= 0) break;
      $take = min($remaining, (float)$layer->qty_sisa);
      if ($take <= 0) continue;
      $price = gid_layer_price($layer);
      if ($price <= 0) { $db->query('ROLLBACK'); gid_json('error', 'Valuation price stock layer #'.$layer->id.' material '.$d->material_code.' belum tersedia.'); }
      if (!gid_source_document_ok($layer)) { $db->query('ROLLBACK'); gid_json('error', 'Stock layer #'.$layer->id.' material '.$d->material_code.' belum punya referensi BC/BPB.'); }
      $amount = round($take * $price, 2);
      $upd = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($take,$layer->id,$take));
      if (!$upd) { $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: 'Stock layer gagal diperbarui.'); }
      if (!$db->insert('detail_transaksi', array(
        'no_ref'=>$giNo,
        'ref_pengganti'=>$delivery->delivery_no,
        'no_aju'=>$layer->no_aju,
        'no_dokpab'=>$layer->no_dokpab,
        'id_incoming_detail'=>($layer->ref_table === 'pemasukan_detail') ? $layer->ref_id : null,
        'move_code'=>'601',
        'posisi'=>'GUDANG',
        'no_urut'=>$lineNo,
        'qty'=>$take * -1,
        'kd_barang'=>$d->material_code,
        'lokasi'=>'GUDANG',
        'document_date'=>$documentDate,
        'posting_date'=>$postingDate,
        'user'=>$username,
        'direction'=>'OUT',
        'ref_type'=>'GI_DELIVERY',
        'ref_id'=>$giId,
        'ref_detail_id'=>$giDetailId,
        'uom'=>$d->uom ?: $d->satuan,
        'price'=>$price,
        'amount'=>$amount,
        'reason'=>'601',
        'created_by'=>$username,
        'no_bpb'=>$layer->no_bpb,
        'plant_id'=>$layer->plant_id,
        'storage_location_id'=>$layer->storage_location_id,
        'storage_bin_id'=>$layer->storage_bin_id,
        'stock_type'=>$layer->stock_type,
        'destination_material_code'=>$d->material_code,
        'remark'=>'Goods Issue 601 for Delivery '.$delivery->delivery_no
      ))) {
        $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: sd_t('sales_material_doc_601_save_failed', 'Material document 601 failed to save.'));
      }
      $matDocId = $db->last_insert_id();
      if (!$db->insert('erp_goods_issue_delivery_trace', array(
        'gi_id'=>$giId,
        'gi_detail_id'=>$giDetailId,
        'stock_layer_id'=>$layer->id,
        'material_doc_id'=>$matDocId,
        'qty'=>$take,
        'price'=>$price,
        'amount'=>$amount,
        'stock_type'=>$layer->stock_type,
        'plant_id'=>$layer->plant_id,
        'storage_location_id'=>$layer->storage_location_id,
        'storage_bin_id'=>$layer->storage_bin_id,
        'no_bpb'=>$layer->no_bpb,
        'no_aju'=>$layer->no_aju,
        'no_dokpab'=>$layer->no_dokpab,
        'jenis_dokpab'=>$layer->jenis_dokpab,
        'hs_code'=>$layer->purchase_hs_code,
        'lot_no'=>$layer->purchase_lot_no,
        'source_ref_table'=>$layer->ref_table,
        'source_ref_id'=>$layer->ref_id
      ))) {
        $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: sd_t('sales_gi_trace_save_failed', 'GI Delivery trace failed to save.'));
      }
      $detailAmount += $amount; $weighted += $take * $price; $remaining -= $take;
    }
    if ($remaining > 0.00001) { $db->query('ROLLBACK'); gid_json('error', 'Stock layer tidak cukup untuk '.$d->material_code.'.'); }
    $detailPrice = $item['qty'] > 0 ? $weighted / $item['qty'] : 0;
    $db->query("UPDATE erp_goods_issue_delivery_detail SET price=?,amount=? WHERE id=?", array($detailPrice,$detailAmount,$giDetailId));
    $db->query("UPDATE erp_outbound_delivery_detail SET gi_qty=gi_qty+? WHERE id=?", array($item['qty'],$d->id));
    $accountingItems[] = array('kode'=>$d->material_code,'amount'=>$detailAmount,'kat_barang'=>$d->kd_kategori,'valuta'=>'IDR','kurs'=>1);
    $totalQty += $item['qty']; $totalAmount += $detailAmount; $lineNo += 10;
  }

  $sum = $db->fetch("SELECT COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(gi_qty),0) gi_qty FROM erp_outbound_delivery_detail WHERE delivery_id=?", array($deliveryId));
  $giStatus = ((float)$sum->gi_qty + 0.00001 >= (float)$sum->delivery_qty) ? 'POSTED' : 'PARTIAL';
  $deliveryStatus = $giStatus === 'POSTED' ? 'PGI' : $delivery->status;
  $db->query("UPDATE erp_outbound_delivery SET gi_status=?,status=?,reference_gi=?,updated_by=?,updated_at=? WHERE id=?", array($giStatus,$deliveryStatus,$giNo,$username,date('Y-m-d H:i:s'),$deliveryId));
  $db->query("UPDATE erp_goods_issue_delivery SET total_qty=?,total_amount=? WHERE id=?", array($totalQty,$totalAmount,$giId));

  $journalResult = accounting_post_auto_journal('goods_issue_delivery', '', $accountingItems, array(
    'no_bukti'=>$giNo,
    'tgl_jurnal'=>$postingDate,
    'ket'=>'Goods Issue 601 Delivery '.$delivery->delivery_no,
    'valuta'=>'IDR',
    'kurs'=>1
  ));
  if ($journalResult !== true) { $db->query('ROLLBACK'); gid_json('error', $journalResult); }
  $db->insert('erp_goods_issue_delivery_history', array('gi_id'=>$giId,'status_baru'=>'POSTED','remarks'=>'Goods Issue 601 posted for delivery '.$delivery->delivery_no,'changed_by'=>$username));
  if (function_exists('simpan_log')) simpan_log('User '.$username.' posting Goods Issue for Delivery '.$giNo.' dari '.$delivery->delivery_no.' pada '.date('Y-m-d H:i:s'), $username);
  $db->query('COMMIT');
  gid_json('good', '', array('gi_no'=>$giNo));
}

if ($act === 'detail') {
  gid_render_detail($db, (int)gid_input('id'));
  exit;
}

if ($act === 'reversal') {
  $id = (int)gid_input('id'); $reason = gid_input('reason');
  if ($id <= 0) gid_json('error', 'Dokumen GI wajib dipilih.');
  if ($reason === '') gid_json('error', 'Reason reversal wajib diisi.');
  $h = $db->fetch("SELECT * FROM erp_goods_issue_delivery WHERE id=? LIMIT 1", array($id));
  if (!$h) gid_json('error', 'Dokumen GI tidak ditemukan.');
  if ($h->status === 'REVERSED') gid_json('error', 'Dokumen GI sudah reversal.');
  $traces = $db->query("SELECT t.*,d.delivery_detail_id,d.material_code,d.material_name,d.uom,d.line_no FROM erp_goods_issue_delivery_trace t JOIN erp_goods_issue_delivery_detail d ON d.id=t.gi_detail_id WHERE t.gi_id=? ORDER BY t.id", array($id));
  $db->query('START TRANSACTION');
  $revNo = $h->gi_no.'_REV';
  foreach ($traces as $trace) {
    $qty = (float)$trace->qty;
    if ($qty <= 0) continue;
    $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array($qty,$trace->stock_layer_id));
    $db->query("UPDATE erp_outbound_delivery_detail SET gi_qty=GREATEST(gi_qty-?,0) WHERE id=?", array($qty,$trace->delivery_detail_id));
    if (!$db->insert('detail_transaksi', array(
      'no_ref'=>$revNo,
      'ref_pengganti'=>$h->gi_no,
      'no_aju'=>$trace->no_aju,
      'no_dokpab'=>$trace->no_dokpab,
      'move_code'=>'602',
      'posisi'=>'GUDANG',
      'no_urut'=>$trace->line_no,
      'qty'=>$qty,
      'kd_barang'=>$trace->material_code,
      'lokasi'=>'GUDANG',
      'document_date'=>date('Y-m-d'),
      'posting_date'=>date('Y-m-d'),
      'user'=>$username,
      'direction'=>'IN',
      'ref_type'=>'GI_DELIVERY_REV',
      'ref_id'=>$id,
      'ref_detail_id'=>$trace->id,
      'is_reversal'=>1,
      'uom'=>$trace->uom,
      'price'=>$trace->price,
      'amount'=>$trace->amount,
      'reason'=>$reason,
      'created_by'=>$username,
      'no_bpb'=>$trace->no_bpb,
      'plant_id'=>$trace->plant_id,
      'storage_location_id'=>$trace->storage_location_id,
      'storage_bin_id'=>$trace->storage_bin_id,
      'stock_type'=>$trace->stock_type,
      'destination_material_code'=>$trace->material_code,
      'remark'=>'Reversal 602 Goods Issue Delivery '.$h->gi_no
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); gid_json('error', $err ?: sd_t('sales_material_doc_reversal_save_failed', 'Material document reversal failed to save.'));
    }
  }
  $sum = $db->fetch("SELECT COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(gi_qty),0) gi_qty FROM erp_outbound_delivery_detail WHERE delivery_id=?", array($h->delivery_id));
  $newGiStatus = ((float)$sum->gi_qty <= 0.00001) ? 'NOT_POSTED' : (((float)$sum->gi_qty + 0.00001 >= (float)$sum->delivery_qty) ? 'POSTED' : 'PARTIAL');
  $newDeliveryStatus = $newGiStatus === 'POSTED' ? 'PGI' : 'PACKED';
  $db->query("UPDATE erp_outbound_delivery SET gi_status=?,status=?,reference_gi=NULL,updated_by=?,updated_at=? WHERE id=?", array($newGiStatus,$newDeliveryStatus,$username,date('Y-m-d H:i:s'),$h->delivery_id));
  $journalResult = accounting_reverse_auto_journal($h->gi_no, $revNo, array('tgl_jurnal'=>date('Y-m-d'), 'ket'=>'Reversal Goods Issue Delivery '.$h->gi_no));
  if ($journalResult !== true) { $db->query('ROLLBACK'); gid_json('error', $journalResult); }
  $db->query("UPDATE erp_goods_issue_delivery SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=? WHERE id=?", array($username,date('Y-m-d H:i:s'),$reason,$id));
  $db->insert('erp_goods_issue_delivery_history', array('gi_id'=>$id,'status_lama'=>'POSTED','status_baru'=>'REVERSED','remarks'=>$reason,'changed_by'=>$username));
  if (function_exists('simpan_log')) simpan_log('User '.$username.' reversal Goods Issue for Delivery '.$h->gi_no.' pada '.date('Y-m-d H:i:s'), $username);
  $db->query('COMMIT');
  gid_json('good');
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start(); ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input = gid_filters(); $rows = gid_load_rows($db, $input); $from = gid_date($input['tgl_awal'], date('Y-01-01')); $to = gid_date($input['tgl_akhir'], date('Y-m-d'));
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('GI Delivery'));
  $headers = array(erp_export_label("No"),erp_export_label("GI No"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Delivery"),erp_export_label("Sales Order"),erp_export_label("Customer"),erp_export_label("Status"),erp_export_label("Items"),erp_export_label("Qty"),erp_export_label("Amount"),erp_export_label("Shipping Point"),erp_export_label("Vehicle"),erp_export_label("Driver"),erp_export_label("Surat Jalan"),erp_export_label("BC Keluar"),erp_export_label("Tujuan BC"),erp_export_label("No Aju"),erp_export_label("Tgl Aju"),erp_export_label("No Daftar"),erp_export_label("Tgl Daftar"),erp_export_label("Kantor BC"),erp_export_label("Negara Tujuan"),erp_export_label("Created By"),erp_export_label("Remarks"));
  foreach ($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1;
  foreach ($rows as $row) {
    $vals = array($n++,$row->gi_no,$row->posting_date,$row->document_date,$row->delivery_no,$row->no_sales_order,$row->customer_name,$row->status,(float)$row->item_count,(float)$row->posted_qty,(float)$row->posted_amount,$row->shipping_point,$row->vehicle_no,$row->driver_name,$row->reference_surat_jalan,$row->outbound_bc_type,$row->outbound_bc_purpose,$row->outbound_no_aju,$row->outbound_tgl_aju,$row->outbound_no_daftar,$row->outbound_tgl_daftar,$row->outbound_customs_office,$row->outbound_destination_country,$row->created_by,$row->remarks);
    foreach ($vals as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('GOODS ISSUE FOR DELIVERY REPORT - SAP SD 601'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>25,'numeric_columns'=>array('J'),'money_columns'=>array('K'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer']?:erp_export_all_text(),'Status'=>$input['status']?:erp_export_all_text(),'Shipping Point'=>$input['shipping_point']?:erp_export_all_text(),'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>18,'F'=>20,'G'=>28,'H'=>12,'I'=>10,'J'=>14,'K'=>16,'L'=>18,'M'=>16,'N'=>18,'O'=>18,'P'=>14,'Q'=>22,'R'=>18,'S'=>14,'T'=>18,'U'=>14,'V'=>24,'W'=>18,'X'=>16,'Y'=>40)));
  $tmp = erpkb_excel_temp_file('gid_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); while (ob_get_level() > $initial) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while (ob_get_level() > $initial) ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="goods_issue_delivery_'.$from.'_sd_'.$to.'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

gid_json('error', 'Action tidak dikenal.');
?>
