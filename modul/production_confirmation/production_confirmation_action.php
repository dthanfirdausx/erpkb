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
$pcInitialOutputBufferLevel = ob_get_level();
ob_start();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function pc_clear_buffers($initialLevel) {
  while (ob_get_level() > $initialLevel) {
    ob_end_clean();
  }
}

function pc_json($status, $message = '', $extra = array()) {
  pc_clear_buffers($GLOBALS['pcInitialOutputBufferLevel']);
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}

function pc_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pc_num($value, $dec = 5) {
  return number_format((float)$value, $dec, ',', '.');
}

function pc_clean_qty($value) {
  return (float)str_replace(',', '.', trim((string)$value));
}

function pc_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}

function pc_clean_datetime($value) {
  $value = trim((string)$value);
  if ($value === '') return '';
  $value = str_replace('T', ' ', $value);
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
    $value .= ':00';
  }
  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
  return ($dt && $dt->format('Y-m-d H:i:s') === $value) ? $value : '';
}

function pc_next_number($postingDate) {
  global $db;
  $prefix = 'PC'.date('Ym', strtotime($postingDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT confirmation_no FROM production_order_confirmation WHERE confirmation_no LIKE ? ORDER BY confirmation_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->confirmation_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}

function pc_status_badge($status) {
  $class = 'default';
  if ($status === 'CREATED') $class = 'default';
  if ($status === 'RELEASED') $class = 'success';
  if ($status === 'IN_PROCESS') $class = 'info';
  if ($status === 'CONFIRMED') $class = 'primary';
  if ($status === 'TECO') $class = 'warning';
  if ($status === 'CLOSED') $class = 'primary';
  if ($status === 'CANCELLED') $class = 'danger';
  return '<span class="label label-'.$class.'">'.pc_h($status).'</span>';
}

function pc_scrap_location($plantCode, $fallbackStorageCode = '') {
  global $db;
  $plant = $db->fetch("SELECT * FROM erp_plant WHERE plant_code=? LIMIT 1", array($plantCode));
  if (!$plant) return array(null, null, null);
  $sloc = $db->fetch(
    "SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_type='SCRAP' AND status='Aktif' ORDER BY id LIMIT 1",
    array($plant->id)
  );
  if (!$sloc && $fallbackStorageCode !== '') {
    $sloc = $db->fetch("SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_code=? LIMIT 1", array($plant->id, $fallbackStorageCode));
  }
  if (!$sloc) $sloc = $db->fetch("SELECT * FROM erp_storage_location WHERE plant_id=? AND status='Aktif' ORDER BY id LIMIT 1", array($plant->id));
  if (!$sloc) return array($plant, null, null);
  $bin = $db->fetch("SELECT * FROM erp_storage_bin WHERE storage_location_id=? AND status='Aktif' ORDER BY bin_code='DEFAULT' DESC,id LIMIT 1", array($sloc->id));
  return array($plant, $sloc, $bin);
}

function pc_post_scrap_stock($confirmationId, $confirmationNo, $po, $postingDate, $scrapMaterial, $scrapQty, $remarks, $username) {
  global $db;
  list($plant, $sloc, $bin) = pc_scrap_location($po->plant, $po->storage_location);
  if (!$plant || !$sloc || !$bin) return array('ok'=>false,'message'=>prod_t('production_scrap_location_incomplete','Default scrap location is incomplete. Make sure Plant, Storage Location type SCRAP, and Bin DEFAULT are available.'));

  $noRef = 'SCR-'.$confirmationNo;
  $materialName = $scrapMaterial->nm_barang ?: $scrapMaterial->kd_barang;
  $uom = $scrapMaterial->satuan ?: $po->uom;
  $dt = array(
    'no_ref' => $noRef,
    'ref_pengganti' => $confirmationNo,
    'move_code' => '531',
    'posisi' => 'GUDANG',
    'qty' => $scrapQty,
    'price' => 0,
    'kd_barang' => $scrapMaterial->kd_barang,
    'lokasi' => 'GUDANG',
    'document_date' => $postingDate.' '.date('H:i:s'),
    'posting_date' => $postingDate.' '.date('H:i:s'),
    'user' => $username,
    'is_produksi' => 'Y',
    'remark' => 'Scrap receipt from Production Confirmation '.$confirmationNo,
    'direction' => 'IN',
    'ref_type' => 'PC_SCRAP',
    'ref_id' => $confirmationId,
    'uom' => $uom,
    'amount' => 0,
    'reason' => $remarks,
    'created_by' => $username,
    'no_bpb' => $noRef,
    'plant_id' => $plant->id,
    'storage_location_id' => $sloc->id,
    'storage_bin_id' => $bin->id,
    'stock_type' => 'UNRESTRICTED',
    'destination_storage_location_id' => $sloc->id,
    'destination_storage_bin_id' => $bin->id,
    'destination_stock_type' => 'UNRESTRICTED',
    'destination_material_code' => $scrapMaterial->kd_barang
  );
  if (!$db->insert('detail_transaksi', $dt)) return array('ok'=>false,'message'=>$db->getErrorMessage() ?: prod_t('production_scrap_matdoc_failed','Scrap material document failed to create.'));
  $materialDocId = $db->last_insert_id();

  if (!$db->insert('stock_layer', array(
    'kode' => $scrapMaterial->kd_barang,
    'qty_masuk' => $scrapQty,
    'qty_sisa' => $scrapQty,
    'lokasi' => 'GUDANG',
    'stock_type' => 'UNRESTRICTED',
    'plant_id' => $plant->id,
    'storage_location_id' => $sloc->id,
    'storage_bin_id' => $bin->id,
    'ref_table' => 'production_order_confirmation',
    'ref_id' => $confirmationId,
    'tgl_masuk' => $postingDate,
    'no_bpb' => $noRef
  ))) return array('ok'=>false,'message'=>$db->getErrorMessage() ?: prod_t('production_scrap_stock_layer_failed','Scrap stock layer failed to create.'));
  $stockLayerId = $db->last_insert_id();

  $db->query(
    "UPDATE production_order_confirmation
     SET scrap_stock_layer_id=?,scrap_material_doc_id=?
     WHERE id_confirmation=?",
    array($stockLayerId, $materialDocId, $confirmationId)
  );

  $traceBase = $db->query(
    "SELECT h.id AS issue_id,d.id AS issue_detail_id,t.id AS trace_id,t.stock_layer_id,t.qty,
            d.material_code,d.material_name,d.uom,t.lot_no,t.no_bpb,t.no_aju,t.jenis_dokpab,t.no_dokpab,t.hs_code,
            sl.qty_masuk AS source_layer_qty,sl.ref_table
     FROM erp_issue_production h
     JOIN erp_issue_production_detail d ON d.issue_id=h.id
     JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
     LEFT JOIN stock_layer sl ON sl.id=t.stock_layer_id
     WHERE h.production_id=? AND h.status='POSTED' AND h.movement_type='261' AND h.posting_date<=?
     ORDER BY h.posting_date,h.issue_no,d.line_no,t.id",
    array($po->id_production_order, $postingDate)
  );
  $baseQty = max(1, (float)$po->completed_qty + (float)$po->scrap_qty + $scrapQty);
  $factor = $scrapQty / $baseQty;
  if ($traceBase) foreach ($traceBase as $trace) {
    $inherited = $db->query("SELECT * FROM erp_gr_production_trace WHERE output_stock_layer_id=? ORDER BY id", array($trace->stock_layer_id));
    if ($inherited && $inherited->rowCount() > 0) {
      $ratio = ((float)$trace->source_layer_qty > 0) ? ((float)$trace->qty / (float)$trace->source_layer_qty) : 1;
      foreach ($inherited as $raw) {
        $db->insert('erp_production_scrap_trace', array(
          'confirmation_id'=>$confirmationId,'confirmation_no'=>$confirmationNo,'scrap_stock_layer_id'=>$stockLayerId,'scrap_material_doc_id'=>$materialDocId,
          'source_issue_id'=>$trace->issue_id,'source_issue_detail_id'=>$trace->issue_detail_id,'source_issue_trace_id'=>$trace->trace_id,'source_stock_layer_id'=>$trace->stock_layer_id,
          'raw_material_code'=>$raw->raw_material_code ?: $raw->source_material_code,'raw_material_name'=>$raw->raw_material_name ?: $raw->source_material_name,
          'qty'=>(float)$raw->qty*$ratio*$factor,'uom'=>$raw->uom,'lot_no'=>$raw->lot_no,'no_bpb'=>$raw->no_bpb,'no_aju'=>$raw->no_aju,'jenis_dokpab'=>$raw->jenis_dokpab,'no_dokpab'=>$raw->no_dokpab,'hs_code'=>$raw->hs_code,'trace_source'=>'INHERITED'
        ));
      }
    } else {
      $db->insert('erp_production_scrap_trace', array(
        'confirmation_id'=>$confirmationId,'confirmation_no'=>$confirmationNo,'scrap_stock_layer_id'=>$stockLayerId,'scrap_material_doc_id'=>$materialDocId,
        'source_issue_id'=>$trace->issue_id,'source_issue_detail_id'=>$trace->issue_detail_id,'source_issue_trace_id'=>$trace->trace_id,'source_stock_layer_id'=>$trace->stock_layer_id,
        'raw_material_code'=>$trace->material_code,'raw_material_name'=>$trace->material_name,'qty'=>(float)$trace->qty*$factor,'uom'=>$trace->uom,
        'lot_no'=>$trace->lot_no,'no_bpb'=>$trace->no_bpb,'no_aju'=>$trace->no_aju,'jenis_dokpab'=>$trace->jenis_dokpab,'no_dokpab'=>$trace->no_dokpab,'hs_code'=>$trace->hs_code,'trace_source'=>'DIRECT'
      ));
    }
  }
  return array('ok'=>true,'stock_layer_id'=>$stockLayerId,'material_doc_id'=>$materialDocId);
}

function pc_confirmation_rows($filters) {
  global $db;
  $where = "";
  $params = array();
  if (!empty($filters['tgl_awal']) && !empty($filters['tgl_akhir'])) {
    $where .= " AND c.posting_date BETWEEN ? AND ? ";
    $params[] = $filters['tgl_awal'];
    $params[] = $filters['tgl_akhir'];
  }
  if (!empty($filters['plant'])) {
    $where .= " AND p.plant=? ";
    $params[] = $filters['plant'];
  }
  if (!empty($filters['status'])) {
    $where .= " AND c.status=? ";
    $params[] = $filters['status'];
  }
  if (!empty($filters['operator_name'])) {
    $where .= " AND c.operator_name=? ";
    $params[] = $filters['operator_name'];
  }
  if (!empty($filters['keyword'])) {
    $keyword = '%'.trim($filters['keyword']).'%';
    $where .= " AND (c.confirmation_no LIKE ? OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR c.work_center LIKE ? OR c.remarks LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $keyword;
  }
  return $db->query(
    "SELECT c.*,p.no_production_order,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.status AS order_status
     FROM production_order_confirmation c
     JOIN production_order p ON p.id_production_order=c.id_production_order
     WHERE 1=1 $where
     ORDER BY c.posting_date DESC,c.id_confirmation DESC",
    $params
  );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'scrap_material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT kd_barang,nm_barang,satuan,kd_kategori,type
       FROM barang
       WHERE (status=1 OR status IS NULL)
         AND (?='' OR kd_barang LIKE ? OR nm_barang LIKE ?)
       ORDER BY CASE WHEN kd_barang LIKE 'SCR%' THEN 0 ELSE 1 END,kd_barang
       LIMIT 30",
      array($term,$like,$like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id'=>$row->kd_barang,
        'text'=>$row->kd_barang.' - '.$row->nm_barang.' | '.$row->satuan,
        'material_name'=>$row->nm_barang,
        'uom'=>$row->satuan
      );
    }
    pc_clear_buffers($pcInitialOutputBufferLevel);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('results'=>$results));
    exit;

  case 'order_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT p.*,
              COALESCE(SUM(CASE WHEN gm.movement_type='261' THEN ABS(gm.qty) WHEN gm.movement_type='262' THEN -ABS(gm.qty) ELSE 0 END),0) AS issued_qty
       FROM production_order p
       LEFT JOIN production_order_goods_movement gm ON gm.id_production_order=p.id_production_order
       WHERE p.status IN ('RELEASED','IN_PROCESS')
         AND (?='' OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ?)
       GROUP BY p.id_production_order
       ORDER BY p.id_production_order DESC
       LIMIT 30",
      array($term, $like, $like, $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $remaining = max(0, (float)$row->order_qty - (float)$row->completed_qty);
      $results[] = array(
        'id' => $row->id_production_order,
        'text' => $row->no_production_order.' | '.$row->material_code.' - '.$row->material_name.' | Rem '.pc_num($remaining).' '.$row->uom.' | '.$row->status,
        'no_production_order' => $row->no_production_order,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'order_qty' => (float)$row->order_qty,
        'completed_qty' => (float)$row->completed_qty,
        'remaining_qty' => $remaining,
        'scrap_qty' => (float)$row->scrap_qty,
        'uom' => $row->uom,
        'plant' => $row->plant,
        'storage_location' => $row->storage_location,
        'status' => $row->status,
        'issued_qty' => (float)$row->issued_qty
      );
    }
    pc_clear_buffers($pcInitialOutputBufferLevel);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('results' => $results));
    exit;

  case 'order_info':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $po = $db->fetch(
      "SELECT p.*,
              COALESCE(SUM(CASE WHEN gm.movement_type='261' THEN ABS(gm.qty) WHEN gm.movement_type='262' THEN -ABS(gm.qty) ELSE 0 END),0) AS issued_qty
       FROM production_order p
       LEFT JOIN production_order_goods_movement gm ON gm.id_production_order=p.id_production_order
       WHERE p.id_production_order=?
       GROUP BY p.id_production_order
       LIMIT 1",
      array($id)
    );
    if (!$po) {
      echo '<div class="alert alert-warning">'.prod_h('production_order_not_found','Production Order was not found.').'</div>';
      break;
    }
    $remaining = max(0, (float)$po->order_qty - (float)$po->completed_qty);
    $issuePct = (float)$po->order_qty > 0 ? min(100, ((float)$po->issued_qty / (float)$po->order_qty) * 100) : 0;
    ?>
    <div class="pc-order-card">
      <div class="row">
        <div class="col-md-7">
          <h4><?=pc_h($po->no_production_order);?> <?=pc_status_badge($po->status);?></h4>
          <p><strong><?=pc_h($po->material_code);?></strong> - <?=pc_h($po->material_name);?></p>
          <p class="text-muted"><?=prod_h('production_plant','Plant');?> <?=pc_h($po->plant);?> / SLoc <?=pc_h($po->storage_location);?> | <?=pc_h($po->start_date);?> s.d. <?=pc_h($po->finish_date);?></p>
        </div>
        <div class="col-md-5">
          <div class="row pc-mini-kpi">
            <div class="col-xs-4"><span><?=prod_h('production_order_qty_short','Order');?></span><strong><?=pc_num($po->order_qty);?></strong></div>
            <div class="col-xs-4"><span><?=prod_h('production_done','Done');?></span><strong><?=pc_num($po->completed_qty);?></strong></div>
            <div class="col-xs-4"><span><?=prod_h('production_remain','Remain');?></span><strong><?=pc_num($remaining);?></strong></div>
          </div>
          <div class="progress progress-xs"><div class="progress-bar progress-bar-success" style="width:<?=number_format($issuePct,2,'.','');?>%"></div></div>
          <small><?=prod_h('production_material_issue_reference','Material issue reference');?>: <?=pc_num($po->issued_qty);?> / <?=pc_num($po->order_qty);?> <?=pc_h($po->uom);?></small>
        </div>
      </div>
    </div>
    <?php
    break;

  case 'operations':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $ops = $db->query("SELECT * FROM production_order_operation WHERE id_production_order=? ORDER BY operation_no,id_operation", array($id));
    $results = array();
    foreach ($ops as $op) {
      $results[] = array(
        'id' => $op->operation_no,
        'text' => $op->operation_no.' - '.$op->work_center.' - '.$op->operation_name.' ['.$op->status.']',
        'work_center' => $op->work_center,
        'operation_name' => $op->operation_name,
        'status' => $op->status
      );
    }
    pc_clear_buffers($pcInitialOutputBufferLevel);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('results' => $results));
    exit;

  case 'confirm':
    $idPo = isset($_POST['id_production_order']) ? (int)$_POST['id_production_order'] : 0;
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $operationNo = isset($_POST['operation_no']) ? trim($_POST['operation_no']) : '';
    $operatorName = isset($_POST['operator_name']) ? trim($_POST['operator_name']) : '';
    $shiftCode = isset($_POST['shift_code']) ? trim($_POST['shift_code']) : '';
    $startTime = isset($_POST['start_time']) ? pc_clean_datetime($_POST['start_time']) : '';
    $endTime = isset($_POST['end_time']) ? pc_clean_datetime($_POST['end_time']) : '';
    $yieldQty = isset($_POST['yield_qty']) ? pc_clean_qty($_POST['yield_qty']) : 0;
    $scrapQty = isset($_POST['scrap_qty']) ? pc_clean_qty($_POST['scrap_qty']) : 0;
    $scrapHandling = (isset($_POST['scrap_handling']) && $_POST['scrap_handling'] === 'STOCK') ? 'STOCK' : 'LOSS';
    $scrapMaterialCode = isset($_POST['scrap_material_code']) ? trim($_POST['scrap_material_code']) : '';
    $reworkQty = isset($_POST['rework_qty']) ? pc_clean_qty($_POST['rework_qty']) : 0;
    $laborTime = isset($_POST['labor_time']) ? pc_clean_qty($_POST['labor_time']) : 0;
    $machineTime = isset($_POST['machine_time']) ? pc_clean_qty($_POST['machine_time']) : 0;
    $finalConfirmation = (isset($_POST['final_confirmation']) && $_POST['final_confirmation'] === 'Y') ? 'Y' : 'N';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    if ($idPo <= 0) pc_json('error', prod_t('production_order_required','Production Order is required.'));
    if (!pc_valid_date($documentDate) || !pc_valid_date($postingDate)) pc_json('error', prod_t('production_doc_posting_date_invalid','Document Date and Posting Date must be valid.'));
    if ($operationNo === '') pc_json('error', prod_t('production_operation_required','Operation is required.'));
    if ($operatorName === '') pc_json('error', prod_t('production_operator_required','Operator is required.'));
    if ($yieldQty <= 0 && $scrapQty <= 0 && $reworkQty <= 0) pc_json('error', prod_t('production_qty_min_required','At least one yield/scrap/rework qty must be greater than zero.'));
    if ($yieldQty < 0 || $scrapQty < 0 || $reworkQty < 0) pc_json('error', prod_t('production_qty_no_negative','Qty cannot be negative.'));
    if ($scrapHandling === 'STOCK' && $scrapQty <= 0) pc_json('error', prod_t('production_scrap_qty_stock_required','Scrap Qty must be greater than zero when Scrap Handling goes to stock.'));
    if ($scrapHandling === 'STOCK' && $scrapMaterialCode === '') pc_json('error', prod_t('production_scrap_material_required','Scrap material is required when Scrap Handling goes to stock.'));
    if (!empty($_POST['start_time']) && $startTime === '') pc_json('error', prod_t('production_start_time_invalid','Start Time is invalid.'));
    if (!empty($_POST['end_time']) && $endTime === '') pc_json('error', prod_t('production_end_time_invalid','End Time is invalid.'));
    if ($startTime !== '' && $endTime !== '' && strtotime($endTime) < strtotime($startTime)) pc_json('error', prod_t('production_end_before_start','End Time cannot be earlier than Start Time.'));

    $po = $db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1", array($idPo));
    if (!$po) pc_json('error', prod_t('production_order_not_found','Production Order was not found.'));
    if (!in_array($po->status, array('RELEASED','IN_PROCESS'), true)) pc_json('error', prod_t('production_order_must_released','Production Order must have status RELEASED or IN_PROCESS.'));
    $remaining = max(0, (float)$po->order_qty - (float)$po->completed_qty);
    if ($yieldQty > $remaining + 0.00001) pc_json('error', prod_t('production_yield_exceeds_remaining','Yield Qty exceeds remaining order. Remaining').' '.pc_num($remaining).' '.$po->uom.'.');

    $op = $db->fetch("SELECT * FROM production_order_operation WHERE id_production_order=? AND operation_no=? LIMIT 1", array($idPo, $operationNo));
    if (!$op) pc_json('error', prod_t('production_operation_not_found','Operation was not found in this Production Order.'));
    $scrapMaterial = null;
    if ($scrapHandling === 'STOCK') {
      $scrapMaterial = $db->fetch("SELECT * FROM barang WHERE kd_barang=? LIMIT 1", array($scrapMaterialCode));
      if (!$scrapMaterial) pc_json('error', prod_t('production_scrap_material_not_found','Scrap material was not found in material master.'));
    }

    $confirmationNo = pc_next_number($postingDate);
    $db->query('START TRANSACTION');
    if (!$db->insert('production_order_confirmation', array(
      'confirmation_no' => $confirmationNo,
      'id_production_order' => $idPo,
      'confirmation_date' => date('Y-m-d H:i:s'),
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'yield_qty' => $yieldQty,
      'scrap_qty' => $scrapQty,
      'scrap_handling' => $scrapHandling,
      'scrap_material_code' => $scrapMaterial ? $scrapMaterial->kd_barang : null,
      'scrap_material_name' => $scrapMaterial ? $scrapMaterial->nm_barang : null,
      'scrap_uom' => $scrapMaterial ? ($scrapMaterial->satuan ?: $po->uom) : null,
      'rework_qty' => $reworkQty,
      'operation_no' => $operationNo,
      'work_center' => $op->work_center,
      'operation_name' => $op->operation_name,
      'operator_name' => $operatorName,
      'shift_code' => $shiftCode,
      'start_time' => $startTime !== '' ? $startTime : null,
      'end_time' => $endTime !== '' ? $endTime : null,
      'labor_time' => $laborTime,
      'machine_time' => $machineTime,
      'final_confirmation' => $finalConfirmation,
      'status' => 'POSTED',
      'remarks' => $remarks,
      'created_by' => $username
    ))) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      pc_json('error', $err ?: prod_t('production_confirmation_save_failed','Production Confirmation failed to save.'));
    }
    $confirmationId = $db->last_insert_id();

    if ($scrapHandling === 'STOCK') {
      $scrapPost = pc_post_scrap_stock($confirmationId, $confirmationNo, $po, $postingDate, $scrapMaterial, $scrapQty, $remarks, $username);
      if (!$scrapPost['ok']) {
        $db->query('ROLLBACK');
        pc_json('error', $scrapPost['message']);
      }
    }

    $newCompleted = (float)$po->completed_qty + $yieldQty;
    $newScrap = (float)$po->scrap_qty + $scrapQty;
    $newStatus = ($finalConfirmation === 'Y' || $newCompleted + 0.00001 >= (float)$po->order_qty) ? 'CONFIRMED' : 'IN_PROCESS';
    $actualStartSql = $po->actual_start ? "actual_start" : "?";
    $actualStartParam = $po->actual_start ? array() : array($startTime !== '' ? $startTime : date('Y-m-d H:i:s'));
    $actualFinish = ($newStatus === 'CONFIRMED') ? ($endTime !== '' ? $endTime : date('Y-m-d H:i:s')) : $po->actual_finish;

    $params = array_merge(
      array($newCompleted, $newScrap),
      $actualStartParam,
      array($actualFinish, $newStatus, $username, $idPo)
    );
    $db->query(
      "UPDATE production_order
       SET completed_qty=?,scrap_qty=?,actual_start=$actualStartSql,actual_finish=?,status=?,updated_by=?
       WHERE id_production_order=?",
      $params
    );
    $db->query(
      "UPDATE production_order_operation
       SET status=?
       WHERE id_production_order=? AND operation_no=?",
      array($finalConfirmation === 'Y' ? 'FINISHED' : 'STARTED', $idPo, $operationNo)
    );

    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' membuat Production Confirmation '.$confirmationNo.' untuk Production Order '.$po->no_production_order.' yield '.$yieldQty.' scrap '.$scrapQty.' pada '.date('Y-m-d H:i:s'), $username);
    }
    $db->query('COMMIT');
    pc_json('good', '', array('confirmation_no' => $confirmationNo));

  case 'reverse':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($id <= 0) pc_json('error', prod_t('production_confirmation_invalid','Confirmation is invalid.'));
    if ($reason === '') pc_json('error', prod_t('production_reason_required','Reason is required'));
    $conf = $db->fetch(
      "SELECT c.*,p.no_production_order,p.completed_qty,p.scrap_qty AS order_scrap_qty,p.status AS order_status
       FROM production_order_confirmation c
       JOIN production_order p ON p.id_production_order=c.id_production_order
       WHERE c.id_confirmation=? LIMIT 1",
      array($id)
    );
    if (!$conf) pc_json('error', prod_t('production_confirmation_not_found','Production Confirmation was not found.'));
    if ($conf->status !== 'POSTED') pc_json('error', prod_t('production_confirmation_already_reversed','This confirmation has already been reversed.'));
    if (in_array($conf->order_status, array('TECO','CLOSED','CANCELLED'), true)) pc_json('error', prod_t('production_order_cannot_reverse_status','Order cannot be reversed because status is').' '.$conf->order_status.'.');

    $newCompleted = max(0, (float)$conf->completed_qty - (float)$conf->yield_qty);
    $newScrap = max(0, (float)$conf->order_scrap_qty - (float)$conf->scrap_qty);
    $nextStatus = $newCompleted > 0 ? 'IN_PROCESS' : 'RELEASED';

    $db->query('START TRANSACTION');
    if ($conf->scrap_handling === 'STOCK' && (int)$conf->scrap_stock_layer_id > 0 && (float)$conf->scrap_qty > 0) {
      $layer = $db->fetch("SELECT * FROM stock_layer WHERE id=? LIMIT 1", array($conf->scrap_stock_layer_id));
      if (!$layer) {
        $db->query('ROLLBACK');
        pc_json('error', prod_t('production_scrap_stock_layer_not_found','Scrap stock layer was not found, reversal cancelled.'));
      }
      if ((float)$layer->qty_sisa + 0.00001 < (float)$conf->scrap_qty) {
        $db->query('ROLLBACK');
        pc_json('error', prod_t('production_scrap_stock_used','Reversal rejected: scrap stock has already been used. Remaining').' '.pc_num($layer->qty_sisa).' '.prod_t('production_from_reversal_need','from reversal need').' '.pc_num($conf->scrap_qty).'.');
      }
      $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($conf->scrap_qty, $conf->scrap_stock_layer_id, $conf->scrap_qty));
      if (!$db->insert('detail_transaksi', array(
        'no_ref'=>'RV-'.$conf->confirmation_no,
        'ref_pengganti'=>$conf->confirmation_no,
        'move_code'=>'532',
        'posisi'=>'GUDANG',
        'qty'=>-$conf->scrap_qty,
        'price'=>0,
        'kd_barang'=>$conf->scrap_material_code,
        'lokasi'=>'GUDANG',
        'document_date'=>date('Y-m-d H:i:s'),
        'posting_date'=>date('Y-m-d H:i:s'),
        'user'=>$username,
        'is_produksi'=>'Y',
        'remark'=>'Reversal scrap receipt '.$conf->confirmation_no,
        'direction'=>'OUT',
        'ref_type'=>'PC_SCRAP_REV',
        'ref_id'=>$conf->id_confirmation,
        'uom'=>$conf->scrap_uom,
        'amount'=>0,
        'reason'=>$reason,
        'created_by'=>$username,
        'no_bpb'=>$layer->no_bpb,
        'plant_id'=>$layer->plant_id,
        'storage_location_id'=>$layer->storage_location_id,
        'storage_bin_id'=>$layer->storage_bin_id,
        'stock_type'=>$layer->stock_type
      ))) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        pc_json('error', $err ?: prod_t('production_scrap_reversal_matdoc_failed','Scrap reversal material document failed to create.'));
      }
    }
    $db->query(
      "UPDATE production_order_confirmation
       SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=?
       WHERE id_confirmation=?",
      array($username, date('Y-m-d H:i:s'), $reason, $id)
    );
    $db->query(
      "UPDATE production_order
       SET completed_qty=?,scrap_qty=?,status=?,actual_finish=NULL,updated_by=?
       WHERE id_production_order=?",
      array($newCompleted, $newScrap, $nextStatus, $username, $conf->id_production_order)
    );
    $postedCount = $db->fetch("SELECT COUNT(*) AS jml FROM production_order_confirmation WHERE id_production_order=? AND operation_no=? AND status='POSTED'", array($conf->id_production_order, $conf->operation_no));
    if (!$postedCount || (int)$postedCount->jml === 0) {
      $db->query(
        "UPDATE production_order_operation SET status='OPEN' WHERE id_production_order=? AND operation_no=?",
        array($conf->id_production_order, $conf->operation_no)
      );
    }
    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' reversal Production Confirmation '.$conf->confirmation_no.' alasan '.$reason.' pada '.date('Y-m-d H:i:s'), $username);
    }
    $db->query('COMMIT');
    pc_json('good');

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $conf = $db->fetch(
      "SELECT c.*,p.no_production_order,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.completed_qty,p.uom,p.status AS order_status
       FROM production_order_confirmation c
       JOIN production_order p ON p.id_production_order=c.id_production_order
       WHERE c.id_confirmation=? LIMIT 1",
      array($id)
    );
    if (!$conf) {
      echo '<div class="alert alert-warning">'.prod_h('production_confirmation_not_found','Production Confirmation was not found.').'</div>';
      break;
    }
    $issuedMaterials = $db->query(
      "SELECT h.issue_no,h.posting_date,h.movement_type,h.reference_no,
              d.material_code,d.material_name,d.uom,
              t.qty,t.lot_no,t.no_bpb,t.no_aju,t.jenis_dokpab,t.no_dokpab,t.hs_code,t.stock_type,
              ep.plant_code,es.storage_code,eb.bin_code,
              t.stock_layer_id,t.material_doc_id
       FROM erp_issue_production h
       JOIN erp_issue_production_detail d ON d.issue_id=h.id
       LEFT JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
       LEFT JOIN erp_plant ep ON ep.id=t.plant_id
       LEFT JOIN erp_storage_location es ON es.id=t.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=t.storage_bin_id
       WHERE h.production_id=?
         AND h.status='POSTED'
         AND h.movement_type='261'
         AND h.posting_date<=?
       ORDER BY h.posting_date,h.issue_no,d.line_no,t.id",
      array($conf->id_production_order, $conf->posting_date)
    );
    $issueSummary = $db->fetch(
      "SELECT COUNT(DISTINCT h.id) AS issue_docs,
              COUNT(DISTINCT d.material_code) AS material_count,
              COALESCE(SUM(t.qty),0) AS traced_qty,
              COUNT(t.id) AS trace_count
       FROM erp_issue_production h
       JOIN erp_issue_production_detail d ON d.issue_id=h.id
       LEFT JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id
       WHERE h.production_id=?
         AND h.status='POSTED'
         AND h.movement_type='261'
         AND h.posting_date<=?",
      array($conf->id_production_order, $conf->posting_date)
    );
    ?>
    <style>
      .pc-detail-hero{border-radius:12px;background:linear-gradient(135deg,#eef2ff,#ecfdf5);padding:14px 16px;margin-bottom:14px;border:1px solid #e5e7eb}
      .pc-detail-hero h3{margin-top:0;font-weight:700}.pc-detail-metric{border:1px solid #e5e7eb;border-radius:10px;padding:10px;background:#fff;margin-bottom:10px}
      .pc-detail-metric span{display:block;color:#64748b;font-size:11px;text-transform:uppercase}.pc-detail-metric strong{display:block;font-size:18px;color:#111827}
      .pc-raw-table th,.pc-raw-table td{font-size:12px;vertical-align:middle!important}.pc-raw-table thead th{background:#f8fafc}
      .pc-customs-pill{display:inline-block;border-radius:999px;background:#eff6ff;color:#1d4ed8;padding:3px 8px;font-size:11px;font-weight:600}
      .pc-lot-pill{display:inline-block;border-radius:999px;background:#fef3c7;color:#92400e;padding:3px 8px;font-size:11px;font-weight:600}
    </style>
    <div class="pc-detail-hero">
    <div class="row">
      <div class="col-md-8">
        <h3 style="margin-top:0;font-weight:700"><?=pc_h($conf->confirmation_no);?> <small><?=pc_h($conf->no_production_order);?></small></h3>
        <p class="text-muted"><?=pc_h($conf->material_code.' - '.$conf->material_name);?> | Plant <?=pc_h($conf->plant);?> / SLoc <?=pc_h($conf->storage_location);?></p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-<?=($conf->status==='POSTED'?'success':'danger');?>"><?=pc_h($conf->status);?></span>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><div class="pc-detail-metric"><span><?=prod_h('production_yield','Yield');?></span><strong><?=pc_num($conf->yield_qty).' '.pc_h($conf->uom);?></strong></div></div>
      <div class="col-sm-3"><div class="pc-detail-metric"><span><?=prod_h('production_scrap','Scrap');?></span><strong><?=pc_num($conf->scrap_qty).' '.pc_h($conf->uom);?></strong></div></div>
      <div class="col-sm-3"><div class="pc-detail-metric"><span><?=prod_h('production_issue_docs','Issue Docs');?></span><strong><?=number_format((float)$issueSummary->issue_docs,0,',','.');?></strong></div></div>
      <div class="col-sm-3"><div class="pc-detail-metric"><span><?=prod_h('production_trace_lines','Trace Lines');?></span><strong><?=number_format((float)$issueSummary->trace_count,0,',','.');?></strong></div></div>
    </div>
    </div>
    <table class="table table-bordered pc-detail-table">
      <tr><th><?=prod_h('production_posting_date','Posting Date');?></th><td><?=pc_h($conf->posting_date);?></td><th><?=prod_h('production_document_date','Document Date');?></th><td><?=pc_h($conf->document_date);?></td></tr>
      <tr><th><?=prod_h('production_operation','Operation');?></th><td><?=pc_h($conf->operation_no.' - '.$conf->work_center.' - '.$conf->operation_name);?></td><th><?=prod_h('production_final','Final');?></th><td><?=pc_h($conf->final_confirmation);?></td></tr>
      <tr><th><?=prod_h('production_yield','Yield');?></th><td><?=pc_num($conf->yield_qty).' '.pc_h($conf->uom);?></td><th><?=prod_h('production_scrap','Scrap');?></th><td><?=pc_num($conf->scrap_qty).' '.pc_h($conf->uom);?></td></tr>
      <tr><th><?=prod_h('production_scrap_handling','Scrap Handling');?></th><td><?=pc_h($conf->scrap_handling ?: 'LOSS');?></td><th><?=prod_h('production_scrap_material','Scrap Material');?></th><td><?=pc_h($conf->scrap_material_code ? ($conf->scrap_material_code.' - '.$conf->scrap_material_name) : '-');?></td></tr>
      <?php if ($conf->scrap_handling === 'STOCK') { ?>
        <tr><th><?=prod_h('production_scrap_stock_layer','Scrap Stock Layer');?></th><td>#<?=pc_h($conf->scrap_stock_layer_id ?: '-');?></td><th><?=prod_h('production_scrap_matdoc','Scrap MatDoc');?></th><td><?=pc_h($conf->scrap_material_doc_id ?: '-');?></td></tr>
      <?php } ?>
      <tr><th><?=prod_h('production_rework','Rework');?></th><td><?=pc_num($conf->rework_qty).' '.pc_h($conf->uom);?></td><th><?=prod_h('production_order_status','Order Status');?></th><td><?=pc_status_badge($conf->order_status);?></td></tr>
      <tr><th><?=prod_h('production_operator','Operator');?></th><td><?=pc_h($conf->operator_name);?></td><th><?=prod_h('production_shift','Shift');?></th><td><?=pc_h($conf->shift_code);?></td></tr>
      <tr><th><?=prod_h('production_start_time','Start Time');?></th><td><?=pc_h($conf->start_time);?></td><th><?=prod_h('production_end_time','End Time');?></th><td><?=pc_h($conf->end_time);?></td></tr>
      <tr><th><?=prod_h('production_labor_time','Labor Time');?></th><td><?=pc_num($conf->labor_time,2);?> <?=prod_h('production_hour_short','hour');?></td><th><?=prod_h('production_machine_time','Machine Time');?></th><td><?=pc_num($conf->machine_time,2);?> <?=prod_h('production_hour_short','hour');?></td></tr>
      <tr><th><?=prod_h('common_created_by','Created By');?></th><td><?=pc_h($conf->created_by);?></td><th><?=prod_h('common_created_at','Created At');?></th><td><?=pc_h($conf->created_at);?></td></tr>
      <tr><th><?=prod_h('common_remarks','Remarks');?></th><td colspan="3"><?=nl2br(pc_h($conf->remarks));?></td></tr>
      <?php if ($conf->status === 'REVERSED') { ?>
        <tr><th><?=prod_h('production_reversed_by','Reversed By');?></th><td><?=pc_h($conf->reversed_by);?></td><th><?=prod_h('production_reversed_at','Reversed At');?></th><td><?=pc_h($conf->reversed_at);?></td></tr>
        <tr><th><?=prod_h('production_reversal_reason','Reversal Reason');?></th><td colspan="3"><?=nl2br(pc_h($conf->reversal_reason));?></td></tr>
      <?php } ?>
    </table>
    <h4><i class="fa fa-cubes"></i> <?=prod_h('production_used_raw_material_bc_trace','Used Raw Materials & BC Document Trace');?></h4>
    <p class="text-muted">
      <?=prod_h('production_used_raw_material_bc_trace_help','This list is taken from Issue to Production movement 261 for the same Production Order up to the confirmation posting date. It is used to trace raw materials, lots, stock layers, and source customs documents.');?>
    </p>
    <?php if (!$issuedMaterials || $issuedMaterials->rowCount() === 0) { ?>
      <div class="alert alert-warning">
        <?=prod_h('production_no_used_raw_material','No used raw material has been recorded from Issue to Production for this production order.');?>
      </div>
    <?php } else { ?>
      <div class="table-responsive">
        <table class="table table-bordered table-condensed pc-raw-table">
          <thead>
            <tr>
              <th style="width:42px"><?=prod_h('common_no','No');?></th>
              <th><?=prod_h('production_issue_doc','Issue Doc');?></th>
              <th><?=prod_h('production_raw_material','Raw Material');?></th>
              <th class="text-right"><?=prod_h('production_qty','Qty');?></th>
              <th><?=prod_h('production_lot_batch','Lot / Batch');?></th>
              <th><?=prod_h('production_bc_document','BC Document');?></th>
              <th><?=prod_h('production_bpb_aju_no','No BPB / Aju');?></th>
              <th><?=prod_h('production_hs_code','HS Code');?></th>
              <th><?=prod_h('warehouse_location','Location');?></th>
              <th><?=prod_h('production_stock_layer','Stock Layer');?></th>
            </tr>
          </thead>
          <tbody>
          <?php $no=1; foreach ($issuedMaterials as $mat) {
            $bcText = trim((string)$mat->jenis_dokpab.' '.(string)$mat->no_dokpab);
            $locationText = trim((string)$mat->plant_code.' / '.(string)$mat->storage_code.' / '.(string)$mat->bin_code, ' /');
          ?>
            <tr>
              <td class="text-center"><?=number_format($no++,0,',','.');?></td>
              <td><strong><?=pc_h($mat->issue_no);?></strong><br><small><?=pc_h($mat->posting_date);?> | MvT <?=pc_h($mat->movement_type);?></small></td>
              <td><strong><?=pc_h($mat->material_code);?></strong><br><small><?=pc_h($mat->material_name);?></small></td>
              <td class="text-right"><?=pc_num($mat->qty);?><br><small><?=pc_h($mat->uom);?></small></td>
              <td><?=($mat->lot_no ? '<span class="pc-lot-pill">'.pc_h($mat->lot_no).'</span>' : '<span class="text-muted">-</span>');?></td>
              <td><?=($bcText !== '' ? '<span class="pc-customs-pill">'.pc_h($bcText).'</span>' : '<span class="text-muted">-</span>');?></td>
              <td><strong><?=pc_h($mat->no_bpb ?: '-');?></strong><br><small><?=pc_h($mat->no_aju ?: '-');?></small></td>
              <td><?=pc_h($mat->hs_code ?: '-');?></td>
              <td><?=pc_h($locationText ?: '-');?><br><small><?=pc_h($mat->stock_type ?: '-');?></small></td>
              <td>#<?=pc_h($mat->stock_layer_id ?: '-');?><br><small>MatDoc <?=pc_h($mat->material_doc_id ?: '-');?></small></td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
    <?php if ($conf->scrap_handling === 'STOCK') {
      $scrapTraces = $db->query("SELECT * FROM erp_production_scrap_trace WHERE confirmation_id=? ORDER BY raw_material_code,no_aju,no_dokpab,id", array($conf->id_confirmation));
    ?>
      <h4><i class="fa fa-recycle"></i> <?=prod_h('production_scrap_stock_raw_trace','Scrap Stock Source Raw Material Trace');?></h4>
      <p class="text-muted"><?=prod_h('production_scrap_stock_raw_trace_help','If scrap is received as material stock, this table shows the raw materials and source BC documents inherited by the scrap stock.');?></p>
      <div class="table-responsive">
        <table class="table table-bordered table-condensed pc-raw-table">
          <thead><tr><th><?=prod_h('common_no','No');?></th><th><?=prod_h('production_raw_material','Raw Material');?></th><th class="text-right"><?=prod_h('production_proportional_qty','Proportional Qty');?></th><th><?=prod_h('production_uom','UOM');?></th><th><?=prod_h('production_inbound_bc_document','Inbound BC Document');?></th><th><?=prod_h('production_aju_no','No AJU');?></th><th><?=prod_h('production_bpb_no','No BPB');?></th><th><?=prod_h('production_lot_batch','Lot/Batch');?></th><th><?=prod_h('production_hs_code','HS Code');?></th><th><?=prod_h('production_trace','Trace');?></th></tr></thead>
          <tbody>
          <?php $sno=1;$hasScrapTrace=false;if($scrapTraces) foreach($scrapTraces as $tr){$hasScrapTrace=true; ?>
            <tr><td><?=intval($sno++);?></td><td><strong><?=pc_h($tr->raw_material_code);?></strong><br><small><?=pc_h($tr->raw_material_name);?></small></td><td class="text-right"><?=pc_num($tr->qty);?></td><td><?=pc_h($tr->uom);?></td><td><?=pc_h(trim((string)$tr->jenis_dokpab.' '.(string)$tr->no_dokpab) ?: '-');?></td><td><?=pc_h($tr->no_aju ?: '-');?></td><td><?=pc_h($tr->no_bpb ?: '-');?></td><td><?=pc_h($tr->lot_no ?: '-');?></td><td><?=pc_h($tr->hs_code ?: '-');?></td><td><span class="label label-<?=($tr->trace_source==='INHERITED'?'primary':'info');?>"><?=pc_h($tr->trace_source);?></span><br><small>Layer #<?=pc_h($tr->source_stock_layer_id ?: '-');?></small></td></tr>
          <?php } if(!$hasScrapTrace){ ?>
            <tr><td colspan="10" class="text-center text-muted"><?=prod_h('production_scrap_trace_empty','Scrap trace is not available yet.');?></td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
    <?php
    break;

  case 'excel':
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    $filters = array(
      'tgl_awal' => isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'),
      'tgl_akhir' => isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'),
      'plant' => isset($_GET['plant']) ? $_GET['plant'] : '',
      'status' => isset($_GET['status']) ? $_GET['status'] : '',
      'operator_name' => isset($_GET['operator_name']) ? $_GET['operator_name'] : '',
      'keyword' => isset($_GET['keyword']) ? $_GET['keyword'] : ''
    );
    if (!pc_valid_date($filters['tgl_awal']) || !pc_valid_date($filters['tgl_akhir'])) pc_json('error', prod_t('export_invalid_date','Export date is invalid.'));
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $excel->getProperties()->setCreator(namaPT)->setTitle(erp_export_sheet_title(prod_t('production_confirmation','Production Confirmation')))->setSubject(prod_t('production_result','Production Result'));
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title(prod_t('production_result','Production Result')));
    $sheet->mergeCells('A1:U1');
    $sheet->mergeCells('A2:U2');
    $sheet->setCellValue('A1', namaPT);
    $sheet->setCellValue('A2', prod_t('production_result_report_title','PRODUCTION RESULT REPORT / PRODUCTION CONFIRMATION'));
    $sheet->setCellValue('A3', prod_t('production_period','Period'));
    $sheet->setCellValue('B3', $filters['tgl_awal'].' s.d. '.$filters['tgl_akhir']);

    $headers = array(prod_t('common_no','No'),prod_t('production_confirmation_no','Confirmation No'),prod_t('production_posting_date','Posting Date'),prod_t('production_document_date','Document Date'),prod_t('production_order','Production Order'),prod_t('production_plant','Plant'),prod_t('production_sloc','SLoc'),prod_t('production_material_code','Material Code'),prod_t('production_material_name','Material Name'),prod_t('production_order_qty','Order Qty'),prod_t('production_yield_qty','Yield Qty'),prod_t('production_scrap_qty','Scrap Qty'),prod_t('production_scrap_handling','Scrap Handling'),prod_t('production_scrap_material','Scrap Material'),prod_t('production_scrap_matdoc','Scrap MatDoc'),prod_t('production_rework_qty','Rework Qty'),prod_t('production_uom','UOM'),prod_t('production_operation','Operation'),prod_t('production_work_center','Work Center'),prod_t('production_operator','Operator'),prod_t('common_status','Status'));
    $col = 0;
    foreach ($headers as $header) {
      $sheet->setCellValueByColumnAndRow($col, 5, $header);
      $col++;
    }
    $titleStyle = array('font'=>array('bold'=>true,'size'=>14),'alignment'=>array('horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
    $headerStyle = array('font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),'fill'=>array('type'=>PHPExcel_Style_Fill::FILL_SOLID,'color'=>array('rgb'=>'3C8DBC')),'borders'=>array('allborders'=>array('style'=>PHPExcel_Style_Border::BORDER_THIN)));
    $sheet->getStyle('A1:U2')->applyFromArray($titleStyle);
    $sheet->getStyle('A5:U5')->applyFromArray($headerStyle);

    $rowNo = 6;
    $no = 1;
    $rows = pc_confirmation_rows($filters);
    foreach ($rows as $row) {
      $values = array(
        $no,
        $row->confirmation_no,
        $row->posting_date,
        $row->document_date,
        $row->no_production_order,
        $row->plant,
        $row->storage_location,
        $row->material_code,
        $row->material_name,
        (float)$row->order_qty,
        (float)$row->yield_qty,
        (float)$row->scrap_qty,
        $row->scrap_handling,
        $row->scrap_material_code ? $row->scrap_material_code.' - '.$row->scrap_material_name : '',
        $row->scrap_material_doc_id,
        (float)$row->rework_qty,
        $row->uom,
        $row->operation_no.' - '.$row->operation_name,
        $row->work_center,
        $row->operator_name,
        $row->status
      );
      for ($c=0; $c<count($values); $c++) {
        $sheet->setCellValueByColumnAndRow($c, $rowNo, $values[$c]);
      }
      $rowNo++;
      $no++;
    }
    $lastRow = max(6, $rowNo - 1);
    $sheet->getStyle('A5:U'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('J6:L'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00000');
    $sheet->getStyle('P6:P'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00000');
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>prod_t('production_result_report_title','PRODUCTION RESULT REPORT / PRODUCTION CONFIRMATION'),'header_row'=>5,'first_data_row'=>6,'last_data_row'=>$lastRow,'column_count'=>21,'numeric_columns'=>array('J','K','L','P'),'filters'=>array(prod_t('production_period','Period')=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],prod_t('production_plant','Plant')=>$filters['plant'],prod_t('common_status','Status')=>$filters['status'],prod_t('production_operator','Operator')=>$filters['operator_name'],prod_t('common_keyword','Keyword')=>$filters['keyword'])));

    $tempDirectory = ini_get('upload_tmp_dir');
    if (!$tempDirectory || !is_dir($tempDirectory) || !is_writable($tempDirectory)) $tempDirectory = sys_get_temp_dir();
    $tempFile = tempnam($tempDirectory, 'production_confirmation_');
    if ($tempFile === false) pc_json('error', prod_t('export_temp_file_failed','Export temporary file could not be created.'));
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    try {
      $writer->save($tempFile);
    } catch (Exception $e) {
      @unlink($tempFile);
      pc_json('error', $e->getMessage());
    }
    $fileSize = @filesize($tempFile);
    $signature = @file_get_contents($tempFile, false, null, 0, 2);
    if (!$fileSize || $signature !== 'PK') {
      @unlink($tempFile);
      pc_json('error', prod_t('export_excel_invalid_file','Excel file could not be created correctly.'));
    }
    pc_clear_buffers($pcInitialOutputBufferLevel);
    $filename = 'production_confirmation_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    header('Content-Length: '.$fileSize);
    header('Pragma: public');
    readfile($tempFile);
    @unlink($tempFile);
    exit;

  default:
    pc_json('error', prod_t('common_unknown_action','Unknown action.'));
}
?>
