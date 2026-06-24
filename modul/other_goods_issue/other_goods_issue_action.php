<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();

function ogi_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}
function ogi_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function ogi_num($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function ogi_clean_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function ogi_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}
function ogi_next_number($postingDate) {
  global $db;
  $prefix = 'OGI'.date('Ym', strtotime($postingDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT issue_no FROM erp_other_goods_issue WHERE issue_no LIKE ? ORDER BY issue_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->issue_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function ogi_layer_where($materialCode, $plantId, $storageLocationId, $storageBinId) {
  $where = " WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED' ";
  $params = array($materialCode);
  if ($plantId > 0) { $where .= " AND sl.plant_id=? "; $params[] = $plantId; }
  if ($storageLocationId > 0) { $where .= " AND sl.storage_location_id=? "; $params[] = $storageLocationId; }
  if ($storageBinId > 0) { $where .= " AND sl.storage_bin_id=? "; $params[] = $storageBinId; }
  return array($where, $params);
}
function ogi_layer_price($layer) {
  if (isset($layer->purchase_price) && (float)$layer->purchase_price > 0) return (float)$layer->purchase_price;
  if (isset($layer->production_price) && (float)$layer->production_price > 0) return (float)$layer->production_price;
  return 0;
}
function ogi_fetch_layers($materialCode, $plantId, $storageLocationId, $storageBinId, $forUpdate = false) {
  global $db;
  list($where, $params) = ogi_layer_where($materialCode, $plantId, $storageLocationId, $storageBinId);
  $sql = "SELECT sl.*,b.nm_barang,b.satuan,pd.harga AS purchase_price,pd.unit AS purchase_uom,pd.lot_no AS purchase_lot_no,pd.hs_code AS purchase_hs_code,
                 gp.gr_no,gpd.qty AS production_qty,COALESCE(CASE WHEN COALESCE(gpd.qty,0)>0 THEN gpd.amount/gpd.qty END,CASE WHEN ABS(COALESCE(gdt.qty,0))>0 THEN ABS(gdt.amount)/ABS(gdt.qty) END,0) AS production_price
          FROM stock_layer sl
          LEFT JOIN barang b ON b.kd_barang=sl.kode
          LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
          LEFT JOIN erp_gr_production gp ON gp.id=sl.ref_id AND sl.ref_table='erp_gr_production'
          LEFT JOIN erp_gr_production_detail gpd ON gpd.stock_layer_id=sl.id
          LEFT JOIN detail_transaksi gdt ON gdt.id=gpd.material_doc_id
          ".$where."
          ORDER BY sl.tgl_masuk ASC,sl.id ASC";
  if ($forUpdate) $sql .= " FOR UPDATE";
  return $db->query($sql, $params);
}
function ogi_source_document_ok($layer) {
  return trim((string)$layer->no_bpb) !== '' || trim((string)$layer->no_aju) !== '' || trim((string)$layer->no_dokpab) !== '' || trim((string)$layer->jenis_dokpab) !== '';
}
function ogi_render_detail($id) {
  global $db;
  $h = $db->fetch(
    "SELECT h.*,ep.plant_code,es.storage_code,eb.bin_code
     FROM erp_other_goods_issue h
     LEFT JOIN erp_plant ep ON ep.id=h.plant_id
     LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
     WHERE h.id=? LIMIT 1",
    array($id)
  );
  if (!$h) { echo '<div class="alert alert-warning">Other Goods Issue tidak ditemukan.</div>'; return; }
  $items = $db->query("SELECT * FROM erp_other_goods_issue_detail WHERE issue_id=? ORDER BY line_no,id", array($id));
  $history = $db->query("SELECT * FROM erp_other_goods_issue_history WHERE issue_id=? ORDER BY changed_at DESC,id DESC", array($id));
  ?>
  <div class="row">
    <div class="col-md-8">
      <h3 style="margin-top:0;font-weight:700"><?=ogi_h($h->issue_no);?> <small><?=ogi_h($h->reason_code.' - '.$h->reason_text);?></small></h3>
      <p class="text-muted">Movement <?=ogi_h($h->movement_type);?> | <?=ogi_h($h->reason_code.' - '.$h->reason_text);?></p>
    </div>
    <div class="col-md-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=ogi_h($h->status);?></span></div>
  </div>
  <div class="row">
    <div class="col-sm-3"><strong>Document Date</strong><br><?=ogi_h($h->document_date);?></div>
    <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></strong><br><?=ogi_h($h->posting_date);?></div>
    <div class="col-sm-3"><strong>Source Location</strong><br><?=ogi_h(trim($h->plant_code.' / '.$h->storage_code.' / '.$h->bin_code, ' /'));?></div>
    <div class="col-sm-3"><strong>Created By</strong><br><?=ogi_h($h->created_by);?></div>
  </div>
  <hr>
  <?php foreach ($items as $item) {
    $traces = $db->query(
      "SELECT t.*,ep.plant_code,es.storage_code,eb.bin_code
       FROM erp_other_goods_issue_trace t
       LEFT JOIN erp_plant ep ON ep.id=t.plant_id
       LEFT JOIN erp_storage_location es ON es.id=t.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=t.storage_bin_id
       WHERE t.issue_detail_id=?
       ORDER BY t.id",
      array($item->id)
    );
  ?>
    <h4><?=ogi_h($item->line_no.'. '.$item->material_code.' - '.$item->material_name);?> <small>Qty <?=ogi_num($item->qty).' '.ogi_h($item->uom);?> | Amount <?=number_format((float)$item->amount,2,',','.');?></small></h4>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead><tr class="bg-gray"><th>Layer</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th class="text-right">Price</th><th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>Lot/Batch</th><th>No Aju</th><th>Dok Pabean</th><th>No BPB</th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Material Doc</th></tr></thead>
        <tbody>
        <?php foreach ($traces as $trace) { ?>
          <tr>
            <td>#<?=intval($trace->stock_layer_id);?></td>
            <td class="text-right"><?=ogi_num($trace->qty);?></td>
            <td class="text-right"><?=number_format((float)$trace->price,5,',','.');?></td>
            <td class="text-right"><?=number_format((float)$trace->amount,2,',','.');?></td>
            <td><?=ogi_h($trace->lot_no ?: '-');?></td>
            <td><?=ogi_h($trace->no_aju ?: '-');?></td>
            <td><?=ogi_h(trim($trace->jenis_dokpab.' '.$trace->no_dokpab) ?: '-');?></td>
            <td><?=ogi_h($trace->no_bpb ?: '-');?></td>
            <td><?=ogi_h(trim($trace->plant_code.' / '.$trace->storage_code.' / '.$trace->bin_code, ' /') ?: '-');?></td>
            <td><?=intval($trace->material_doc_id);?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
  <h4>History</h4>
  <ul class="list-unstyled">
    <?php foreach ($history as $row) { ?>
      <li><strong><?=ogi_h(($row->status_lama ?: '-').' -> '.$row->status_baru);?></strong> <span class="text-muted"><?=ogi_h($row->changed_by.' @ '.$row->changed_at);?></span><br><?=ogi_h($row->remarks);?></li>
    <?php } ?>
  </ul>
  <?php
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) ? (int)$_POST['storage_bin_id'] : 0;
    $like = '%'.$term.'%';
    $where = " WHERE sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED' ";
    $params = array($term,$like,$like);
    if ($plantId > 0) { $where .= " AND sl.plant_id=? "; $params[]=$plantId; }
    if ($storageLocationId > 0) { $where .= " AND sl.storage_location_id=? "; $params[]=$storageLocationId; }
    if ($storageBinId > 0) { $where .= " AND sl.storage_bin_id=? "; $params[]=$storageBinId; }
    $rows = $db->query(
      "SELECT sl.kode,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) AS stock
       FROM stock_layer sl
       LEFT JOIN barang b ON b.kd_barang=sl.kode
       $where
         AND (?='' OR sl.kode LIKE ? OR b.nm_barang LIKE ?)
       GROUP BY sl.kode,b.nm_barang,b.satuan
       HAVING stock>0
       ORDER BY sl.kode LIMIT 30",
      $params
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array('id'=>$row->kode,'text'=>$row->kode.' - '.$row->nm_barang.' | Stock '.ogi_num($row->stock).' '.$row->satuan,'material_name'=>$row->nm_barang,'uom'=>$row->satuan,'stock'=>(float)$row->stock);
    }
    echo json_encode(array('results'=>$results));
    break;

  case 'stock_preview':
    $materialCode = isset($_POST['material_code']) ? trim($_POST['material_code']) : '';
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) ? (int)$_POST['storage_bin_id'] : 0;
    if ($materialCode === '') { echo '<span class="text-muted">Pilih material.</span>'; break; }
    $layers = ogi_fetch_layers($materialCode, $plantId, $storageLocationId, $storageBinId, false);
    if (!$layers || $layers->rowCount() === 0) { echo '<span class="text-danger">Stock layer tidak tersedia.</span>'; break; }
    echo '<table class="table table-condensed table-bordered" style="margin-bottom:0"><thead><tr><th>Layer</th><th class="text-right">Sisa</th><th class="text-right">Price</th><th>BC/BPB</th></tr></thead><tbody>';
    foreach ($layers as $layer) {
      echo '<tr><td>#'.intval($layer->id).'</td><td class="text-right">'.ogi_num($layer->qty_sisa).'</td><td class="text-right">'.number_format(ogi_layer_price($layer),5,',','.').'</td><td>'.ogi_h($layer->no_bpb ?: '-').'<br><small>'.ogi_h(trim($layer->jenis_dokpab.' '.$layer->no_dokpab).' / '.$layer->no_aju).'</small></td></tr>';
    }
    echo '</tbody></table>';
    break;

  case 'post':
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';
    $reasonCode = isset($_POST['reason_code']) ? trim($_POST['reason_code']) : '';
    $reasonText = isset($_POST['reason_text']) ? trim($_POST['reason_text']) : '';
    $issueCategory = isset($_POST['issue_category']) ? trim($_POST['issue_category']) : '';
    $recipientType = isset($_POST['recipient_type']) ? trim($_POST['recipient_type']) : '';
    $recipientName = isset($_POST['recipient_name']) ? trim($_POST['recipient_name']) : '';
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) ? (int)$_POST['storage_bin_id'] : 0;

    if (!ogi_valid_date($documentDate) || !ogi_valid_date($postingDate)) ogi_json('error', 'Document Date dan Posting Date wajib valid.');
    if ($reasonCode === '' || $reasonText === '') ogi_json('error', 'Reason Code dan Reason Text wajib diisi.');
    if (empty($_POST['material_code']) || !is_array($_POST['material_code'])) ogi_json('error', 'Minimal satu item material wajib diisi.');

    $items = array();
    foreach ($_POST['material_code'] as $idx => $code) {
      $code = trim((string)$code);
      $qty = isset($_POST['qty'][$idx]) ? ogi_clean_qty($_POST['qty'][$idx]) : 0;
      if ($code !== '' && $qty > 0) {
        $items[] = array(
          'material_code' => $code,
          'qty' => $qty,
          'remarks' => isset($_POST['item_remarks'][$idx]) ? trim($_POST['item_remarks'][$idx]) : ''
        );
      }
    }
    if (empty($items)) ogi_json('error', 'Issue Qty wajib lebih dari nol.');

    $db->query('START TRANSACTION');
    $issueNo = ogi_next_number($postingDate);
    if (!$db->insert('erp_other_goods_issue', array(
      'issue_no' => $issueNo,
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'movement_type' => '291',
      'reference_no' => $referenceNo,
      'reason_code' => $reasonCode,
      'reason_text' => $reasonText,
      'issue_category' => $issueCategory,
      'recipient_type' => $recipientType,
      'recipient_name' => $recipientName,
      'plant_id' => $plantId > 0 ? $plantId : null,
      'storage_location_id' => $storageLocationId > 0 ? $storageLocationId : null,
      'storage_bin_id' => $storageBinId > 0 ? $storageBinId : null,
      'status' => 'POSTED',
      'created_by' => $username
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Header Other Goods Issue gagal disimpan.');
    }
    $issueId = $db->last_insert_id();
    $lineNo = 1;
    $accountingItems = array();

    foreach ($items as $item) {
      $material = $db->fetch("SELECT kd_barang,nm_barang,satuan,kd_kategori FROM barang WHERE kd_barang=? LIMIT 1", array($item['material_code']));
      if (!$material) { $db->query('ROLLBACK'); ogi_json('error', 'Material '.$item['material_code'].' tidak ditemukan.'); }
      list($layerWhere, $layerParams) = ogi_layer_where($item['material_code'], $plantId, $storageLocationId, $storageBinId);
      $available = $db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) AS available_qty FROM stock_layer sl ".$layerWhere, $layerParams);
      if (!$available || (float)$available->available_qty + 0.00001 < $item['qty']) {
        $db->query('ROLLBACK'); ogi_json('error', 'Stock tidak cukup untuk '.$item['material_code'].'. Available '.ogi_num($available ? $available->available_qty : 0).', request '.ogi_num($item['qty']).'.');
      }
      if (!$db->insert('erp_other_goods_issue_detail', array(
        'issue_id' => $issueId,
        'line_no' => $lineNo,
        'material_code' => $material->kd_barang,
        'material_name' => $material->nm_barang,
        'qty' => $item['qty'],
        'uom' => $material->satuan,
        'stock_type' => 'UNRESTRICTED',
        'remarks' => $item['remarks']
      ))) {
        $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Detail Issue gagal disimpan.');
      }
      $detailId = $db->last_insert_id();
      $remaining = $item['qty'];
      $detailAmount = 0;
      $weightedValue = 0;
      $layers = ogi_fetch_layers($item['material_code'], $plantId, $storageLocationId, $storageBinId, true);
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        if ($take <= 0) continue;
        $price = ogi_layer_price($layer);
        if ($price <= 0) { $db->query('ROLLBACK'); ogi_json('error', 'Valuation price untuk stock layer #'.$layer->id.' material '.$item['material_code'].' belum tersedia. Posting 291 dibatalkan agar jurnal tidak salah.'); }
        if (!ogi_source_document_ok($layer)) { $db->query('ROLLBACK'); ogi_json('error', 'Stock layer #'.$layer->id.' material '.$item['material_code'].' belum punya referensi BC/BPB.'); }
        $amount = round($take * $price, 2);
        $update = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($take,$layer->id,$take));
        if (!$update) { $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Stock layer gagal diperbarui.'); }
        if (!$db->insert('detail_transaksi', array(
          'no_ref' => $issueNo,
          'ref_pengganti' => $reasonCode,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'id_incoming_detail' => ($layer->ref_table === 'pemasukan_detail') ? $layer->ref_id : null,
          'move_code' => '291',
          'posisi' => 'GUDANG',
          'no_urut' => $lineNo,
          'qty' => $take * -1,
          'kd_barang' => $material->kd_barang,
          'lokasi' => 'GUDANG',
          'document_date' => $documentDate,
          'posting_date' => $postingDate,
          'user' => $username,
          'direction' => 'OUT',
          'ref_type' => 'GI_OTHER',
          'ref_id' => $issueId,
          'ref_detail_id' => $detailId,
          'uom' => $material->satuan,
          'price' => $price,
          'amount' => $amount,
          'reason' => $reasonCode,
          'created_by' => $username,
          'no_bpb' => $layer->no_bpb,
          'plant_id' => $layer->plant_id,
          'storage_location_id' => $layer->storage_location_id,
          'storage_bin_id' => $layer->storage_bin_id,
          'stock_type' => $layer->stock_type,
          'destination_material_code' => $material->kd_barang,
          'remark' => 'Goods Issue 291 Other '.$reasonCode.' - '.$reasonText
        ))) {
          $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Material document 291 gagal disimpan.');
        }
        $materialDocId = $db->last_insert_id();
        if (!$db->insert('erp_other_goods_issue_trace', array(
          'issue_id' => $issueId,
          'issue_detail_id' => $detailId,
          'stock_layer_id' => $layer->id,
          'material_doc_id' => $materialDocId,
          'qty' => $take,
          'price' => $price,
          'amount' => $amount,
          'stock_type' => $layer->stock_type,
          'plant_id' => $layer->plant_id,
          'storage_location_id' => $layer->storage_location_id,
          'storage_bin_id' => $layer->storage_bin_id,
          'no_bpb' => $layer->no_bpb,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'jenis_dokpab' => $layer->jenis_dokpab,
          'hs_code' => $layer->purchase_hs_code,
          'lot_no' => $layer->purchase_lot_no,
          'source_ref_table' => $layer->ref_table,
          'source_ref_id' => $layer->ref_id
        ))) {
          $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Trace Other Goods Issue gagal disimpan.');
        }
        $detailAmount += $amount;
        $weightedValue += $take * $price;
        $remaining -= $take;
      }
      if ($remaining > 0.00001) { $db->query('ROLLBACK'); ogi_json('error', 'Stock layer tidak cukup untuk item '.$item['material_code'].'.'); }
      $detailPrice = $item['qty'] > 0 ? $weightedValue / $item['qty'] : 0;
      $db->query("UPDATE erp_other_goods_issue_detail SET price=?,amount=? WHERE id=?", array($detailPrice,$detailAmount,$detailId));
      $accountingItems[] = array('kode'=>$material->kd_barang,'amount'=>$detailAmount,'kat_barang'=>$material->kd_kategori,'valuta'=>'IDR','kurs'=>1);
      $lineNo++;
    }
    $journalResult = accounting_post_auto_journal('other_goods_issue', '', $accountingItems, array(
      'no_bukti' => $issueNo,
      'tgl_jurnal' => $postingDate,
      'ket' => 'Goods Issue 291 Other '.$issueNo.' '.$reasonCode,
      'valuta' => 'IDR',
      'kurs' => 1
    ));
    if ($journalResult !== true) { $db->query('ROLLBACK'); ogi_json('error', $journalResult); }
    $db->insert('erp_other_goods_issue_history', array('issue_id'=>$issueId,'status_baru'=>'POSTED','remarks'=>'Goods Issue 291 posted untuk reason '.$reasonCode,'changed_by'=>$username));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' posting Other Goods Issue '.$issueNo.' reason '.$reasonCode.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    ogi_json('good', '', array('issue_no'=>$issueNo));
    break;

  case 'detail':
    ogi_render_detail(isset($_POST['id']) ? (int)$_POST['id'] : 0);
    break;

  case 'reversal':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($id <= 0) ogi_json('error', 'Issue document wajib dipilih.');
    if ($reason === '') ogi_json('error', 'Reason reversal wajib diisi.');
    $h = $db->fetch("SELECT * FROM erp_other_goods_issue WHERE id=? LIMIT 1", array($id));
    if (!$h) ogi_json('error', 'Issue document tidak ditemukan.');
    if ($h->status === 'REVERSED') ogi_json('error', 'Issue document sudah reversal.');
    $traces = $db->query("SELECT t.*,d.material_code,d.material_name,d.uom,d.line_no FROM erp_other_goods_issue_trace t JOIN erp_other_goods_issue_detail d ON d.id=t.issue_detail_id WHERE t.issue_id=? ORDER BY t.id", array($id));
    if (!$traces) ogi_json('error', 'Trace issue tidak ditemukan.');
    $db->query('START TRANSACTION');
    $revNo = $h->issue_no.'_REV';
    foreach ($traces as $trace) {
      $qty = (float)$trace->qty;
      if ($qty <= 0) continue;
      $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array($qty,$trace->stock_layer_id));
      if (!$db->insert('detail_transaksi', array(
        'no_ref' => $revNo,
        'ref_pengganti' => $h->issue_no,
        'no_aju' => $trace->no_aju,
        'no_dokpab' => $trace->no_dokpab,
        'move_code' => '292',
        'posisi' => 'GUDANG',
        'no_urut' => $trace->line_no,
        'qty' => $qty,
        'kd_barang' => $trace->material_code,
        'lokasi' => 'GUDANG',
        'document_date' => date('Y-m-d'),
        'posting_date' => date('Y-m-d'),
        'user' => $username,
        'direction' => 'IN',
        'ref_type' => 'GI_OTHER_REV',
        'ref_id' => $id,
        'ref_detail_id' => $trace->id,
        'is_reversal' => 1,
        'uom' => $trace->uom,
        'price' => $trace->price,
        'amount' => $trace->amount,
        'reason' => $reason,
        'created_by' => $username,
        'no_bpb' => $trace->no_bpb,
        'remark' => 'Reversal 292 Other Goods Issue '.$h->issue_no
      ))) {
        $err = $db->getErrorMessage(); $db->query('ROLLBACK'); ogi_json('error', $err ?: 'Material document reversal gagal disimpan.');
      }
    }
    $journalResult = accounting_reverse_auto_journal($h->issue_no, $revNo, array('tgl_jurnal'=>date('Y-m-d'), 'ket'=>'Reversal Other Goods Issue '.$h->issue_no));
    if ($journalResult !== true) { $db->query('ROLLBACK'); ogi_json('error', $journalResult); }
    $db->query("UPDATE erp_other_goods_issue SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=? WHERE id=?", array($username,date('Y-m-d H:i:s'),$reason,$id));
    $db->insert('erp_other_goods_issue_history', array('issue_id'=>$id,'status_lama'=>'POSTED','status_baru'=>'REVERSED','remarks'=>$reason,'changed_by'=>$username));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' reversal Other Goods Issue '.$h->issue_no.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    ogi_json('good');
    break;

  case 'excel':
    $excelInitialOutputBufferLevel = ob_get_level();
    ob_start();
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $tglAwal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
    $tglAkhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
    $where = " WHERE 1=1 ";
    $params = array();
    if (ogi_valid_date($tglAwal) && ogi_valid_date($tglAkhir)) { $where .= " AND h.posting_date BETWEEN ? AND ? "; $params[]=$tglAwal; $params[]=$tglAkhir; }
    if (!empty($_GET['reason_code'])) { $where .= " AND h.reason_code=? "; $params[]=$_GET['reason_code']; }
    if (!empty($_GET['status'])) { $where .= " AND h.status=? "; $params[]=$_GET['status']; }
    if (!empty($_GET['keyword'])) { $kw='%'.trim($_GET['keyword']).'%'; $where .= " AND (h.issue_no LIKE ? OR h.reason_code LIKE ? OR h.reason_text LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.no_bpb LIKE ?) "; for($i=0;$i<8;$i++)$params[]=$kw; }
    $rows = $db->query(
      "SELECT h.issue_no,h.posting_date,h.document_date,h.reason_code,h.reason_text,h.issue_category,h.recipient_type,h.recipient_name,h.status,
              d.material_code,d.material_name,d.qty,d.uom,d.price,d.amount,t.no_bpb,t.no_aju,t.jenis_dokpab,t.no_dokpab,t.lot_no,t.stock_layer_id
       FROM erp_other_goods_issue h
       JOIN erp_other_goods_issue_detail d ON d.issue_id=h.id
       LEFT JOIN erp_other_goods_issue_trace t ON t.issue_detail_id=d.id
       $where
       ORDER BY h.posting_date DESC,h.id DESC,d.line_no,t.id",
      $params
    );
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Other Goods Issue'));
    $sheet->mergeCells('A1:S1'); $sheet->mergeCells('A2:S2');
    $sheet->setCellValue('A1', namaPT); $sheet->setCellValue('A2', 'OTHER GOODS ISSUE');
    $headers = array(erp_export_label("No"),erp_export_label("Issue No"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Purpose"),erp_export_label("Issue Category"),erp_export_label("Recipient"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Amount"),erp_export_label("Stock Layer"),erp_export_label("No BPB"),erp_export_label("No Aju"),erp_export_label("Dok Pabean"),erp_export_label("Lot"),erp_export_label("Status"));
    foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
    $r=5; $n=1;
    foreach ($rows as $row) {
      $values = array($n++,$row->issue_no,$row->posting_date,$row->document_date,$row->reason_code.' - '.$row->reason_text,$row->issue_category,trim($row->recipient_type.' '.$row->recipient_name),$row->material_code,$row->material_name,(float)$row->qty,$row->uom,(float)$row->price,(float)$row->amount,$row->stock_layer_id,$row->no_bpb,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),$row->lot_no,$row->status);
      foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
      $r++;
    }
    $sheet->getStyle('A1:S2')->getFont()->setBold(true);
    $sheet->getStyle('A1:S2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:S4')->getFont()->setBold(true);
    $sheet->getStyle('A4:S'.max(4,$r-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('J5:J'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00000');
    $sheet->getStyle('L5:M'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00');
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('OTHER GOODS ISSUE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('J'),'money_columns'=>array('L','M'),'filters'=>array('Periode'=>$tglAwal.' s/d '.$tglAkhir,'Status'=>isset($_GET['status'])?$_GET['status']:'','Keyword'=>isset($_GET['keyword'])?$_GET['keyword']:'')));
    $tmp = tempnam(sys_get_temp_dir(), 'ogi_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = filesize($tmp);
    while (ob_get_level() > $excelInitialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="other_goods_issue_'.$tglAwal.'_sd_'.$tglAkhir.'.xlsx"');
    header('Content-Length: '.$size);
    readfile($tmp); @unlink($tmp); exit;

  default:
    ogi_json('error', 'Action tidak dikenal.');
}
?>
