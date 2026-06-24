<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$grpoInitialOutputBufferLevel = ob_get_level();
ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();

function grpo_clear_buffers($initialLevel) { while (ob_get_level() > $initialLevel) ob_end_clean(); }
function grpo_json($status, $message = '', $extra = array()) {
  grpo_clear_buffers($GLOBALS['grpoInitialOutputBufferLevel']);
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}
function grpo_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function grpo_num($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function grpo_clean_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function grpo_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}
function grpo_is_internal_production_layer($trace) {
  $refTable = isset($trace->source_ref_table) ? trim((string)$trace->source_ref_table) : '';
  if ($refTable === '') $refTable = isset($trace->ref_table) ? trim((string)$trace->ref_table) : '';
  $noBpb = isset($trace->source_layer_no_bpb) ? trim((string)$trace->source_layer_no_bpb) : '';
  return $refTable === 'erp_gr_production' || strpos($noBpb, 'GRP') === 0;
}
function grpo_has_source_document($row) {
  $fields = array('no_bpb','no_aju','jenis_dokpab','no_dokpab');
  foreach ($fields as $field) {
    if (isset($row->$field) && trim((string)$row->$field) !== '') return true;
  }
  return false;
}
function grpo_next_number($postingDate) {
  global $db;
  $prefix = 'GRP'.date('Ym', strtotime($postingDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT gr_no FROM erp_gr_production WHERE gr_no LIKE ? ORDER BY gr_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->gr_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function grpo_wip_amount($productionId, $postingDate, $factor) {
  global $db;
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN COALESCE(t.amount,0)>0 THEN t.amount ELSE COALESCE(dt.amount,0) END),0) amount
     FROM erp_issue_production h
     JOIN erp_issue_production_detail d ON d.issue_id=h.id
     JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
     LEFT JOIN detail_transaksi dt ON dt.id=t.material_doc_id
     WHERE h.production_id=? AND h.status='POSTED' AND h.movement_type='261' AND h.posting_date<=?",
    array($productionId, $postingDate)
  );
  return round(($row ? (float)$row->amount : 0) * (float)$factor, 2);
}
function grpo_confirmation_source($idConfirmation) {
  global $db;
  return $db->fetch(
    "SELECT c.*,p.no_production_order,p.material_code,p.material_name,p.uom,p.plant,p.storage_location,p.status AS order_status,
            COALESCE(gr.received_qty,0) AS received_qty
     FROM production_order_confirmation c
     JOIN production_order p ON p.id_production_order=c.id_production_order
     LEFT JOIN (
       SELECT id_confirmation,COALESCE(SUM(d.qty),0) AS received_qty
       FROM erp_gr_production h
       JOIN erp_gr_production_detail d ON d.gr_id=h.id
       WHERE h.status='POSTED'
       GROUP BY id_confirmation
     ) gr ON gr.id_confirmation=c.id_confirmation
     WHERE c.id_confirmation=? LIMIT 1",
    array($idConfirmation)
  );
}
function grpo_insert_trace_rows($grId, $grDetailId, $outputStockLayerId, $productionId, $postingDate, $traceFactor) {
  global $db;
  $inserted = 0;
  $issueTraces = $db->query(
    "SELECT t.*,d.material_code,d.material_name,d.uom,h.id AS issue_id,h.issue_no,h.posting_date AS issue_posting_date,
            sl.qty_masuk AS source_layer_qty,sl.ref_table,sl.ref_id,sl.no_bpb AS source_layer_no_bpb,sl.kode AS source_layer_material
     FROM erp_issue_production h
     JOIN erp_issue_production_detail d ON d.issue_id=h.id
     JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
     LEFT JOIN stock_layer sl ON sl.id=t.stock_layer_id
     WHERE h.production_id=?
       AND h.status='POSTED'
       AND h.movement_type='261'
       AND h.posting_date<=?
     ORDER BY h.posting_date,h.issue_no,d.line_no,t.id",
    array($productionId, $postingDate)
  );
  if (!$issueTraces) return array('ok' => false, 'inserted' => 0, 'message' => $db->getErrorMessage() ?: 'Trace Issue to Production gagal dibaca.');
  if ($issueTraces->rowCount() === 0) return array('ok' => false, 'inserted' => 0, 'message' => 'GR Production ditolak: belum ada Issue to Production POSTED untuk production order ini. Trace bahan baku dan dokumen BC wajib tersedia.');

  foreach ($issueTraces as $trace) {
    $inherited = $db->query(
      "SELECT pt.*
       FROM erp_gr_production_trace pt
       WHERE pt.output_stock_layer_id=?
       ORDER BY pt.id",
      array($trace->stock_layer_id)
    );
    $inheritedCount = $inherited ? $inherited->rowCount() : 0;
    if (grpo_is_internal_production_layer($trace) && $inheritedCount === 0) {
      return array(
        'ok' => false,
        'inserted' => $inserted,
        'message' => 'GR Production ditolak: material '.$trace->material_code.' memakai stock layer internal #'.$trace->stock_layer_id.' ('.$trace->source_layer_no_bpb.') tetapi trace bahan baku asalnya belum tersedia.'
      );
    }
    if ($inheritedCount > 0) {
      $ratio = ((float)$trace->source_layer_qty > 0) ? ((float)$trace->qty / (float)$trace->source_layer_qty) : 1;
      foreach ($inherited as $raw) {
        if (!grpo_has_source_document($raw)) {
          return array(
            'ok' => false,
            'inserted' => $inserted,
            'message' => 'GR Production ditolak: trace bahan baku '.$raw->raw_material_code.' dari '.$trace->material_code.' belum memiliki referensi dokumen BC/BPB.'
          );
        }
        $ok = $db->insert('erp_gr_production_trace', array(
          'gr_id' => $grId,
          'gr_detail_id' => $grDetailId,
          'output_stock_layer_id' => $outputStockLayerId,
          'source_issue_id' => $trace->issue_id,
          'source_issue_detail_id' => $trace->issue_detail_id,
          'source_issue_trace_id' => $trace->id,
          'source_stock_layer_id' => $trace->stock_layer_id,
          'source_material_code' => $trace->material_code,
          'source_material_name' => $trace->material_name,
          'raw_material_code' => $raw->raw_material_code ?: $raw->source_material_code,
          'raw_material_name' => $raw->raw_material_name ?: $raw->source_material_name,
          'qty' => (float)$raw->qty * $ratio * $traceFactor,
          'uom' => $raw->uom,
          'lot_no' => $raw->lot_no,
          'no_bpb' => $raw->no_bpb,
          'no_aju' => $raw->no_aju,
          'jenis_dokpab' => $raw->jenis_dokpab,
          'no_dokpab' => $raw->no_dokpab,
          'hs_code' => $raw->hs_code,
          'trace_source' => 'INHERITED'
        ));
        if (!$ok) return array('ok' => false, 'inserted' => $inserted, 'message' => $db->getErrorMessage() ?: 'Trace inherited gagal disimpan.');
        $inserted++;
      }
      continue;
    }
    if (!grpo_has_source_document($trace)) {
      return array(
        'ok' => false,
        'inserted' => $inserted,
        'message' => 'GR Production ditolak: trace material '.$trace->material_code.' dari stock layer #'.$trace->stock_layer_id.' belum memiliki referensi dokumen BC/BPB.'
      );
    }
    $ok = $db->insert('erp_gr_production_trace', array(
      'gr_id' => $grId,
      'gr_detail_id' => $grDetailId,
      'output_stock_layer_id' => $outputStockLayerId,
      'source_issue_id' => $trace->issue_id,
      'source_issue_detail_id' => $trace->issue_detail_id,
      'source_issue_trace_id' => $trace->id,
      'source_stock_layer_id' => $trace->stock_layer_id,
      'source_material_code' => $trace->material_code,
      'source_material_name' => $trace->material_name,
      'raw_material_code' => $trace->material_code,
      'raw_material_name' => $trace->material_name,
      'qty' => (float)$trace->qty * $traceFactor,
      'uom' => $trace->uom,
      'lot_no' => $trace->lot_no,
      'no_bpb' => $trace->no_bpb,
      'no_aju' => $trace->no_aju,
      'jenis_dokpab' => $trace->jenis_dokpab,
      'no_dokpab' => $trace->no_dokpab,
      'hs_code' => $trace->hs_code,
      'trace_source' => 'DIRECT'
    ));
    if (!$ok) return array('ok' => false, 'inserted' => $inserted, 'message' => $db->getErrorMessage() ?: 'Trace direct gagal disimpan.');
    $inserted++;
  }
  if ($inserted === 0) return array('ok' => false, 'inserted' => 0, 'message' => 'GR Production ditolak: trace bahan baku asal tidak terbentuk.');
  return array('ok' => true, 'inserted' => $inserted, 'message' => '');
}
function grpo_trace_table($grId) {
  global $db;
  return $db->query(
    "SELECT tr.*,d.material_code AS output_material_code,d.material_name AS output_material_name,d.qty AS output_qty,d.uom AS output_uom,h.gr_no
     FROM erp_gr_production_trace tr
     JOIN erp_gr_production_detail d ON d.id=tr.gr_detail_id
     JOIN erp_gr_production h ON h.id=tr.gr_id
     WHERE tr.gr_id=?
     ORDER BY tr.raw_material_code,tr.no_aju,tr.no_dokpab,tr.id",
    array($grId)
  );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'confirmation_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT c.id_confirmation,c.confirmation_no,c.posting_date,c.yield_qty,c.status,
              p.id_production_order,p.no_production_order,p.material_code,p.material_name,p.uom,p.plant,p.storage_location,
              COALESCE(gr.received_qty,0) AS received_qty
       FROM production_order_confirmation c
       JOIN production_order p ON p.id_production_order=c.id_production_order
       LEFT JOIN (
         SELECT id_confirmation,COALESCE(SUM(d.qty),0) AS received_qty
         FROM erp_gr_production h
         JOIN erp_gr_production_detail d ON d.gr_id=h.id
         WHERE h.status='POSTED'
         GROUP BY id_confirmation
       ) gr ON gr.id_confirmation=c.id_confirmation
       WHERE c.status='POSTED'
         AND c.yield_qty>COALESCE(gr.received_qty,0)
         AND (?='' OR c.confirmation_no LIKE ? OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ?)
       ORDER BY c.posting_date DESC,c.id_confirmation DESC
       LIMIT 30",
      array($term, $like, $like, $like, $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $remaining = max(0, (float)$row->yield_qty - (float)$row->received_qty);
      $results[] = array(
        'id' => $row->id_confirmation,
        'text' => $row->confirmation_no.' | '.$row->no_production_order.' | '.$row->material_code.' - '.$row->material_name.' | Rem '.grpo_num($remaining).' '.$row->uom,
        'remaining_qty' => $remaining,
        'yield_qty' => (float)$row->yield_qty,
        'received_qty' => (float)$row->received_qty,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'uom' => $row->uom,
        'plant' => $row->plant,
        'storage_location' => $row->storage_location,
        'no_production_order' => $row->no_production_order
      );
    }
    grpo_clear_buffers($grpoInitialOutputBufferLevel);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('results' => $results));
    exit;

  case 'confirmation_info':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $src = grpo_confirmation_source($id);
    if (!$src) { echo '<div class="alert alert-warning">Confirmation tidak ditemukan.</div>'; break; }
    $remaining = max(0, (float)$src->yield_qty - (float)$src->received_qty);
    $trace = $db->fetch(
      "SELECT COUNT(*) trace_count,COUNT(DISTINCT d.material_code) material_count
       FROM erp_issue_production h
       JOIN erp_issue_production_detail d ON d.issue_id=h.id
       JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
       WHERE h.production_id=? AND h.status='POSTED' AND h.movement_type='261' AND h.posting_date<=?",
      array($src->id_production_order, $src->posting_date)
    );
    ?>
    <div class="alert alert-success">
      <strong><?=grpo_h($src->confirmation_no);?></strong> | <?=grpo_h($src->no_production_order);?> | <?=grpo_h($src->material_code.' - '.$src->material_name);?>
      <br><small>Yield <?=grpo_num($src->yield_qty);?> | Received <?=grpo_num($src->received_qty);?> | Remaining <?=grpo_num($remaining).' '.grpo_h($src->uom);?> | Trace <?=intval($trace->trace_count);?> lines</small>
    </div>
    <?php
    break;

  case 'post':
    $idConfirmation = isset($_POST['id_confirmation']) ? (int)$_POST['id_confirmation'] : 0;
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $qty = isset($_POST['qty']) ? grpo_clean_qty($_POST['qty']) : 0;
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) && $_POST['storage_bin_id'] !== '' ? (int)$_POST['storage_bin_id'] : null;
    $stockType = isset($_POST['stock_type']) ? trim($_POST['stock_type']) : 'UNRESTRICTED';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    if ($idConfirmation <= 0) grpo_json('error', 'Production Confirmation wajib dipilih.');
    if (!grpo_valid_date($documentDate) || !grpo_valid_date($postingDate)) grpo_json('error', 'Document Date dan Posting Date wajib valid.');
    if ($qty <= 0) grpo_json('error', 'Receipt Qty wajib lebih dari nol.');
    if ($plantId <= 0 || $storageLocationId <= 0) grpo_json('error', 'Plant dan Storage Location wajib dipilih.');
    if (!in_array($stockType, array('UNRESTRICTED','QUALITY','BLOCKED'), true)) grpo_json('error', 'Stock Type tidak valid.');

    $src = grpo_confirmation_source($idConfirmation);
    if (!$src) grpo_json('error', 'Production Confirmation tidak ditemukan.');
    if ($src->status !== 'POSTED') grpo_json('error', 'Confirmation harus status POSTED.');
    $remaining = max(0, (float)$src->yield_qty - (float)$src->received_qty);
    if ($qty > $remaining + 0.00001) grpo_json('error', 'Receipt Qty melebihi remaining confirmation. Remaining '.grpo_num($remaining).' '.$src->uom.'.');
    $plant = $db->fetch("SELECT * FROM erp_plant WHERE id=? AND status='Aktif' LIMIT 1", array($plantId));
    if (!$plant) grpo_json('error', 'Plant tidak valid.');
    $sloc = $db->fetch("SELECT * FROM erp_storage_location WHERE id=? AND plant_id=? AND status='Aktif' LIMIT 1", array($storageLocationId, $plantId));
    if (!$sloc) grpo_json('error', 'Storage Location tidak sesuai Plant.');
    if ($storageBinId) {
      $bin = $db->fetch("SELECT * FROM erp_storage_bin WHERE id=? AND storage_location_id=? AND status='Aktif' LIMIT 1", array($storageBinId, $storageLocationId));
      if (!$bin) grpo_json('error', 'Storage Bin tidak sesuai Storage Location.');
    }

    $grNo = grpo_next_number($postingDate);
    $yieldTotal = $db->fetch(
      "SELECT COALESCE(SUM(yield_qty),0) AS total_yield
       FROM production_order_confirmation
       WHERE id_production_order=? AND status='POSTED'",
      array($src->id_production_order)
    );
    $traceFactor = ($yieldTotal && (float)$yieldTotal->total_yield > 0) ? ((float)$qty / (float)$yieldTotal->total_yield) : 1;
    $grAmount = grpo_wip_amount($src->id_production_order, $postingDate, $traceFactor);
    if ($grAmount <= 0) grpo_json('error', 'Nilai WIP untuk Production Order '.$src->no_production_order.' belum tersedia. Pastikan Issue to Production sudah diposting dengan valuation price.');
    $grPrice = $qty > 0 ? $grAmount / $qty : 0;
    $db->query('START TRANSACTION');
    if (!$db->insert('erp_gr_production', array(
      'gr_no' => $grNo,
      'id_confirmation' => $src->id_confirmation,
      'id_production_order' => $src->id_production_order,
      'no_production_order' => $src->no_production_order,
      'confirmation_no' => $src->confirmation_no,
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'movement_type' => '101',
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'storage_bin_id' => $storageBinId,
      'stock_type' => $stockType,
      'status' => 'POSTED',
      'remarks' => $remarks,
      'created_by' => $username
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); grpo_json('error', $err ?: 'Header GR gagal disimpan.');
    }
    $grId = $db->last_insert_id();

    $dt = array(
      'no_ref' => $grNo,
      'ref_pengganti' => $src->confirmation_no,
      'move_code' => '101',
      'posisi' => 'GUDANG',
      'no_urut' => 1,
      'qty' => $qty,
      'kd_barang' => $src->material_code,
      'lokasi' => 'GUDANG',
      'document_date' => $documentDate.' 00:00:00',
      'posting_date' => $postingDate.' '.date('H:i:s'),
      'user' => $username,
      'is_produksi' => 'Y',
      'remark' => 'GR from Production Order '.$src->no_production_order,
      'direction' => 'IN',
      'ref_type' => 'GR_PROD',
      'ref_id' => $grId,
      'uom' => $src->uom,
      'price' => $grPrice,
      'amount' => $grAmount,
      'reason' => $remarks,
      'created_by' => $username,
      'no_bpb' => $grNo,
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'storage_bin_id' => $storageBinId,
      'stock_type' => $stockType,
      'destination_storage_location_id' => $storageLocationId,
      'destination_storage_bin_id' => $storageBinId,
      'destination_stock_type' => $stockType,
      'destination_material_code' => $src->material_code
    );
    if (!$db->insert('detail_transaksi', $dt)) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); grpo_json('error', $err ?: 'Material document gagal disimpan.');
    }
    $materialDocId = $db->last_insert_id();

    if (!$db->insert('stock_layer', array(
      'kode' => $src->material_code,
      'qty_masuk' => $qty,
      'qty_sisa' => $qty,
      'lokasi' => 'GUDANG',
      'stock_type' => $stockType,
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'storage_bin_id' => $storageBinId,
      'ref_table' => 'erp_gr_production',
      'ref_id' => $grId,
      'tgl_masuk' => $postingDate,
      'no_bpb' => $grNo
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); grpo_json('error', $err ?: 'Stock layer hasil produksi gagal disimpan.');
    }
    $stockLayerId = $db->last_insert_id();

    if (!$db->insert('erp_gr_production_detail', array(
      'gr_id' => $grId,
      'stock_layer_id' => $stockLayerId,
      'material_doc_id' => $materialDocId,
      'material_code' => $src->material_code,
      'material_name' => $src->material_name,
      'qty' => $qty,
      'uom' => $src->uom,
      'price' => $grPrice,
      'amount' => $grAmount,
      'stock_type' => $stockType,
      'remarks' => $remarks
    ))) {
      $err = $db->getErrorMessage(); $db->query('ROLLBACK'); grpo_json('error', $err ?: 'Detail GR gagal disimpan.');
    }
    $grDetailId = $db->last_insert_id();

    $db->insert('production_order_goods_movement', array(
      'id_production_order' => $src->id_production_order,
      'movement_type' => '101',
      'material_code' => $src->material_code,
      'material_name' => $src->material_name,
      'qty' => $qty,
      'uom' => $src->uom,
      'posting_date' => $postingDate.' '.date('H:i:s'),
      'storage_location_to' => $sloc->storage_code,
      'remarks' => 'GR '.$grNo.' from confirmation '.$src->confirmation_no,
      'created_by' => $username
    ));

    $traceResult = grpo_insert_trace_rows($grId, $grDetailId, $stockLayerId, $src->id_production_order, $postingDate, $traceFactor);
    if (!$traceResult['ok']) {
      $db->query('ROLLBACK');
      grpo_json('error', $traceResult['message']);
    }
    $db->update('erp_gr_production', array('total_amount'=>$grAmount), 'id', $grId);
    $journalResult = accounting_post_auto_journal('gr_production', '', array(array('kode'=>$src->material_code,'amount'=>$grAmount,'valuta'=>'IDR','kurs'=>1)), array(
      'no_bukti'=>$grNo,
      'tgl_jurnal'=>$postingDate,
      'ket'=>'GR 101 from Production Order '.$src->no_production_order,
      'valuta'=>'IDR',
      'kurs'=>1,
      'source_module'=>'GR_FROM_PRODUCTION'
    ));
    if ($journalResult !== true) { $db->query('ROLLBACK'); grpo_json('error', $journalResult); }
    if (function_exists('simpan_log')) simpan_log('User '.$username.' posting GR from Production Order '.$grNo.' order '.$src->no_production_order.' qty '.$qty.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    grpo_json('good', '', array('gr_no' => $grNo, 'trace_rows' => $traceResult['inserted']));

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $h = $db->fetch(
      "SELECT h.*,d.material_code,d.material_name,d.qty,d.uom,d.stock_layer_id,d.material_doc_id,
              ep.plant_code,es.storage_code,eb.bin_code
       FROM erp_gr_production h
       JOIN erp_gr_production_detail d ON d.gr_id=h.id
       LEFT JOIN erp_plant ep ON ep.id=h.plant_id
       LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
       WHERE h.id=? LIMIT 1",
      array($id)
    );
    if (!$h) { echo '<div class="alert alert-warning">GR from Production Order tidak ditemukan.</div>'; break; }
    $traces = grpo_trace_table($id);
    ?>
    <style>
      .grpo-detail-hero{border-radius:12px;background:linear-gradient(135deg,#eff6ff,#ecfdf5);padding:14px 16px;margin-bottom:14px;border:1px solid #e5e7eb}
      .grpo-metric{border:1px solid #e5e7eb;border-radius:10px;padding:10px;background:#fff;margin-bottom:10px}.grpo-metric span{display:block;color:#64748b;font-size:11px;text-transform:uppercase}.grpo-metric strong{font-size:18px}
      .grpo-trace-table th,.grpo-trace-table td{font-size:12px;vertical-align:middle!important}.grpo-trace-table thead th{background:#f8fafc}
      .grpo-pill{display:inline-block;border-radius:999px;background:#eff6ff;color:#1d4ed8;padding:3px 8px;font-size:11px;font-weight:600}.grpo-lot{background:#fef3c7;color:#92400e}.grpo-inherited{background:#f3e8ff;color:#6d28d9}
    </style>
    <div class="grpo-detail-hero">
      <div class="row">
        <div class="col-md-8"><h3 style="margin-top:0;font-weight:700"><?=grpo_h($h->gr_no);?> <small><?=grpo_h($h->no_production_order);?></small></h3><p class="text-muted">Confirmation <?=grpo_h($h->confirmation_no);?> | <?=grpo_h($h->material_code.' - '.$h->material_name);?></p></div>
        <div class="col-md-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=grpo_h($h->status);?></span></div>
      </div>
      <div class="row">
        <div class="col-sm-3"><div class="grpo-metric"><span>Receipt Qty</span><strong><?=grpo_num($h->qty).' '.grpo_h($h->uom);?></strong></div></div>
        <div class="col-sm-3"><div class="grpo-metric"><span>Stock Layer</span><strong>#<?=intval($h->stock_layer_id);?></strong></div></div>
        <div class="col-sm-3"><div class="grpo-metric"><span>Material Doc</span><strong>#<?=intval($h->material_doc_id);?></strong></div></div>
        <div class="col-sm-3"><div class="grpo-metric"><span><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></span><strong><?=grpo_h($h->stock_type);?></strong></div></div>
      </div>
    </div>
    <table class="table table-bordered">
      <tr><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><td><?=grpo_h($h->posting_date);?></td><th>Document Date</th><td><?=grpo_h($h->document_date);?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><td><?=grpo_h(trim((string)$h->plant_code.' / '.(string)$h->storage_code.' / '.(string)$h->bin_code, ' /'));?></td><th>Created By</th><td><?=grpo_h($h->created_by);?></td></tr>
      <tr><th>Remarks</th><td colspan="3"><?=grpo_h($h->remarks);?></td></tr>
      <?php if ($h->status === 'REVERSED') { ?><tr><th>Reversal</th><td colspan="3"><?=grpo_h($h->reversal_reason);?> | <?=grpo_h($h->reversed_by);?> <?=grpo_h($h->reversed_at);?></td></tr><?php } ?>
    </table>
    <h4><i class="fa fa-random"></i> Trace Bahan Baku Asal</h4>
    <p class="text-muted">Trace ini mengikuti bahan yang di-issue ke production order. Jika output memakai barang setengah jadi, trace akan diwariskan dari GR produksi barang setengah jadi tersebut.</p>
    <?php if (!$traces || $traces->rowCount() === 0) { ?>
      <div class="alert alert-warning">Trace bahan baku belum tersedia. Pastikan Issue to Production sudah diposting sebelum GR.</div>
    <?php } else { ?>
      <div class="table-responsive"><table class="table table-bordered table-condensed grpo-trace-table">
        <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th>Output</th><th>Source Material</th><th>Raw Material Asal</th><th class="text-right">Qty Trace</th><th>Lot</th><th>Dokumen BC</th><th>No BPB / Aju</th><th>HS Code</th><th>Trace</th></tr></thead>
        <tbody>
        <?php $no=1; foreach ($traces as $t) { $bc=trim((string)$t->jenis_dokpab.' '.(string)$t->no_dokpab); ?>
          <tr>
            <td class="text-center"><?=number_format($no++,0,',','.');?></td>
            <td><strong><?=grpo_h($t->output_material_code);?></strong><br><small><?=grpo_h($t->output_material_name);?></small></td>
            <td><strong><?=grpo_h($t->source_material_code);?></strong><br><small><?=grpo_h($t->source_material_name);?></small></td>
            <td><strong><?=grpo_h($t->raw_material_code);?></strong><br><small><?=grpo_h($t->raw_material_name);?></small></td>
            <td class="text-right"><?=grpo_num($t->qty);?><br><small><?=grpo_h($t->uom);?></small></td>
            <td><?=($t->lot_no ? '<span class="grpo-pill grpo-lot">'.grpo_h($t->lot_no).'</span>' : '-');?></td>
            <td><?=($bc ? '<span class="grpo-pill">'.grpo_h($bc).'</span>' : '-');?></td>
            <td><strong><?=grpo_h($t->no_bpb ?: '-');?></strong><br><small><?=grpo_h($t->no_aju ?: '-');?></small></td>
            <td><?=grpo_h($t->hs_code ?: '-');?></td>
            <td><span class="grpo-pill <?=($t->trace_source==='INHERITED'?'grpo-inherited':'');?>"><?=grpo_h($t->trace_source);?></span></td>
          </tr>
        <?php } ?>
        </tbody>
      </table></div>
    <?php } ?>
    <?php
    break;

  case 'reverse':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($id <= 0 || $reason === '') grpo_json('error', 'GR dan alasan reversal wajib diisi.');
    $h = $db->fetch("SELECT h.*,d.qty,d.price,d.amount,d.stock_layer_id,d.material_doc_id,d.material_code FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id WHERE h.id=? LIMIT 1", array($id));
    if (!$h) grpo_json('error', 'GR tidak ditemukan.');
    if ($h->status !== 'POSTED') grpo_json('error', 'GR sudah reversed.');
    $layer = $db->fetch("SELECT qty_sisa FROM stock_layer WHERE id=? LIMIT 1", array($h->stock_layer_id));
    if (!$layer || (float)$layer->qty_sisa + 0.00001 < (float)$h->qty) grpo_json('error', 'Stock hasil produksi sudah terpakai, reversal tidak bisa dilakukan.');
    $db->query('START TRANSACTION');
    $db->query("UPDATE erp_gr_production SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=? WHERE id=?", array($username, date('Y-m-d H:i:s'), $reason, $id));
    $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=?", array($h->qty, $h->stock_layer_id));
    $db->insert('detail_transaksi', array(
      'no_ref' => $h->gr_no,
      'ref_pengganti' => $h->confirmation_no,
      'move_code' => '102',
      'posisi' => 'GUDANG',
      'no_urut' => 1,
      'qty' => -abs((float)$h->qty),
      'kd_barang' => $h->material_code,
      'lokasi' => 'GUDANG',
      'document_date' => date('Y-m-d H:i:s'),
      'posting_date' => date('Y-m-d H:i:s'),
      'user' => $username,
      'is_produksi' => 'Y',
      'remark' => 'Reversal GR production '.$h->gr_no,
      'direction' => 'OUT',
      'ref_type' => 'GRP_REV',
      'ref_id' => $id,
      'is_reversal' => 1,
      'ref_detail_id' => $h->material_doc_id,
      'uom' => null,
      'price' => $h->price,
      'amount' => $h->amount,
      'reason' => $reason,
      'created_by' => $username,
      'no_bpb' => $h->gr_no
    ));
    $journalResult = accounting_reverse_auto_journal($h->gr_no, $h->gr_no.'_REV', array('tgl_jurnal'=>date('Y-m-d'), 'ket'=>'Reversal GR from Production '.$h->gr_no));
    if ($journalResult !== true) { $db->query('ROLLBACK'); grpo_json('error', $journalResult); }
    if (function_exists('simpan_log')) simpan_log('User '.$username.' reversal GR from Production '.$h->gr_no.' alasan '.$reason, $username);
    $db->query('COMMIT');
    grpo_json('good');

  case 'excel':
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    $filters = array(
      'tgl_awal' => isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'),
      'tgl_akhir' => isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'),
      'plant_id' => isset($_GET['plant_id']) ? $_GET['plant_id'] : '',
      'storage_location_id' => isset($_GET['storage_location_id']) ? $_GET['storage_location_id'] : '',
      'status' => isset($_GET['status']) ? $_GET['status'] : '',
      'keyword' => isset($_GET['keyword']) ? $_GET['keyword'] : ''
    );
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $where = " WHERE 1=1 ";
    $params = array();
    if (grpo_valid_date($filters['tgl_awal']) && grpo_valid_date($filters['tgl_akhir'])) { $where.=" AND h.posting_date BETWEEN ? AND ? "; $params[]=$filters['tgl_awal']; $params[]=$filters['tgl_akhir']; }
    if ($filters['plant_id'] !== '') { $where.=" AND h.plant_id=? "; $params[]=$filters['plant_id']; }
    if ($filters['storage_location_id'] !== '') { $where.=" AND h.storage_location_id=? "; $params[]=$filters['storage_location_id']; }
    if ($filters['status'] !== '') { $where.=" AND h.status=? "; $params[]=$filters['status']; }
    if ($filters['keyword'] !== '') { $kw='%'.$filters['keyword'].'%'; $where.=" AND (h.gr_no LIKE ? OR h.no_production_order LIKE ? OR h.confirmation_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ?) "; for($i=0;$i<5;$i++)$params[]=$kw; }
    $rows = $db->query(
      "SELECT h.*,d.material_code,d.material_name,d.qty,d.uom,ep.plant_code,es.storage_code,eb.bin_code
       FROM erp_gr_production h
       JOIN erp_gr_production_detail d ON d.gr_id=h.id
       LEFT JOIN erp_plant ep ON ep.id=h.plant_id
       LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
       $where
       ORDER BY h.posting_date DESC,h.id DESC",
      $params
    );
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('GR Production'));
    $sheet->mergeCells('A1:N1'); $sheet->mergeCells('A2:N2');
    $sheet->setCellValue('A1', namaPT); $sheet->setCellValue('A2', 'GR FROM PRODUCTION ORDER');
    $headers = array(erp_export_label("No"),erp_export_label("GR No"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Production Order"),erp_export_label("Confirmation"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Bin"),erp_export_label("Status"));
    foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
    $r=5; $n=1;
    foreach ($rows as $row) {
      $values = array($n++,$row->gr_no,$row->posting_date,$row->document_date,$row->no_production_order,$row->confirmation_no,$row->material_code,$row->material_name,(float)$row->qty,$row->uom,$row->plant_code,$row->storage_code,$row->bin_code,$row->status);
      foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
      $r++;
    }
    $sheet->getStyle('A1:N2')->getFont()->setBold(true);
    $sheet->getStyle('A1:N2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:N4')->getFont()->setBold(true);
    $sheet->getStyle('A4:N'.max(4,$r-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('I5:I'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00000');
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('GR FROM PRODUCTION ORDER'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>14,'numeric_columns'=>array('I'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['status'],'Keyword'=>$filters['keyword'])));
    $tmp = tempnam(sys_get_temp_dir(), 'gr_prod_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = filesize($tmp);
    grpo_clear_buffers($grpoInitialOutputBufferLevel);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="gr_from_production_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"');
    header('Content-Length: '.$size);
    readfile($tmp); @unlink($tmp); exit;

  default:
    grpo_json('error', 'Action tidak dikenal.');
}
?>
