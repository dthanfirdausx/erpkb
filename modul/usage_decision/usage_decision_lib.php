<?php
require_once dirname(__DIR__)."/inspection_lot/inspection_lot_lib.php";

function ud_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function ud_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function ud_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function ud_h($value) { return ilot_h($value); }
function ud_num($value, $decimals = 5) { return number_format((float)$value, $decimals, ',', '.'); }
function ud_next_number($date = '') {
  global $db;
  $prefix = 'UD'.date('Ym', strtotime($date ?: date('Y-m-d')));
  $row = $db->fetch("SELECT ud_no FROM erp_usage_decision WHERE ud_no LIKE ? ORDER BY ud_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->ud_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function ud_decision_label($code) {
  $map = array('A'=>'Accept','R'=>'Reject','P'=>'Partial Accept','RW'=>'Rework','RTV'=>'Return to Vendor','SCRAP'=>'Scrap');
  return isset($map[$code]) ? $map[$code] : $code;
}
function ud_decision_badge($code) {
  $map = array('A'=>'success','R'=>'danger','P'=>'primary','RW'=>'warning','RTV'=>'danger','SCRAP'=>'default');
  $class = isset($map[$code]) ? $map[$code] : 'default';
  return '<span class="label label-'.$class.'">'.ud_h(ud_decision_label($code)).'</span>';
}
function ud_follow_up_label($value) {
  $map = array('RELEASE'=>'Release to unrestricted','BLOCK'=>'Block stock','REWORK'=>'Rework','RETURN_TO_VENDOR'=>'Return to vendor','SCRAP'=>'Scrap','PARTIAL_RELEASE'=>'Partial release/block','NO_STOCK_POSTING'=>'No stock posting');
  return isset($map[$value]) ? $map[$value] : $value;
}
function ud_filters() {
  return array(
    'tgl_awal' => ud_valid_date(ud_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => ud_valid_date(ud_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'inspection_origin' => ud_input('inspection_origin'),
    'decision_code' => ud_input('decision_code'),
    'stock_posted' => ud_input('stock_posted'),
    'keyword' => ud_input('keyword')
  );
}
function ud_where($filters, &$params) {
  $where = " WHERE DATE(ud.decision_at) BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['inspection_origin'] !== '') { $where .= " AND il.inspection_origin=? "; $params[] = $filters['inspection_origin']; }
  if ($filters['decision_code'] !== '') { $where .= " AND ud.decision_code=? "; $params[] = $filters['decision_code']; }
  if ($filters['stock_posted'] !== '') { $where .= " AND ud.stock_posted=? "; $params[] = $filters['stock_posted']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (ud.ud_no LIKE ? OR ud.lot_no LIKE ? OR ud.material_code LIKE ? OR ud.material_name LIKE ? OR ud.no_aju LIKE ? OR ud.no_dokpab LIKE ? OR ud.notes LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}
function ud_select_sql() {
  return "SELECT ud.*,il.inspection_origin,il.inspection_type,il.lot_status,il.source_ref_no,
                 ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
                 COALESCE(a.action_count,0) AS action_count
          FROM erp_usage_decision ud
          JOIN erp_inspection_lot il ON il.id=ud.inspection_lot_id
          LEFT JOIN erp_plant ep ON ep.id=ud.plant_id
          LEFT JOIN erp_storage_location es ON es.id=ud.storage_location_id
          LEFT JOIN erp_storage_bin eb ON eb.id=ud.storage_bin_id
          LEFT JOIN (SELECT usage_decision_id,COUNT(*) action_count FROM erp_usage_decision_action GROUP BY usage_decision_id) a ON a.usage_decision_id=ud.id";
}
function ud_load_rows($db, $filters) {
  $params = array();
  $where = ud_where($filters, $params);
  $rows = $db->query(ud_select_sql()." $where ORDER BY ud.decision_at DESC, ud.id DESC", $params);
  return $rows ? iterator_to_array($rows, false) : array();
}
function ud_fetch($db, $id) {
  return $db->fetch(ud_select_sql()." WHERE ud.id=? LIMIT 1", array((int)$id));
}
function ud_lot_candidates($db, $term = '') {
  $params = array();
  $where = " WHERE il.lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED') ";
  if ($term !== '') {
    $kw = '%'.$term.'%';
    $where .= " AND (il.lot_no LIKE ? OR il.material_code LIKE ? OR il.material_name LIKE ? OR il.source_ref_no LIKE ? OR il.no_aju LIKE ? OR il.no_dokpab LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $db->query(
    "SELECT il.*,COALESCE(rr.result_count,0) result_count,COALESCE(rr.fail_count,0) fail_count,COALESCE(rr.defect_qty,0) defect_qty,
            ep.plant_code,es.storage_code,eb.bin_code
     FROM erp_inspection_lot il
     LEFT JOIN erp_usage_decision ud ON ud.inspection_lot_id=il.id
     LEFT JOIN erp_plant ep ON ep.id=il.plant_id
     LEFT JOIN erp_storage_location es ON es.id=il.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=il.storage_bin_id
     LEFT JOIN (
       SELECT inspection_lot_id,COUNT(*) result_count,SUM(result_status='FAIL') fail_count,SUM(defect_qty) defect_qty
       FROM erp_inspection_lot_result GROUP BY inspection_lot_id
     ) rr ON rr.inspection_lot_id=il.id
     $where AND ud.id IS NULL
     ORDER BY il.created_at DESC,il.id DESC LIMIT 50",
    $params
  );
}
function ud_candidate_rows($db, $filters) {
  $params = array();
  $where = " WHERE il.lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED') ";
  if ($filters['inspection_origin'] !== '') { $where .= " AND il.inspection_origin=? "; $params[] = $filters['inspection_origin']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (il.lot_no LIKE ? OR il.material_code LIKE ? OR il.material_name LIKE ? OR il.source_ref_no LIKE ? OR il.no_aju LIKE ? OR il.no_dokpab LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  $rows = $db->query(
    "SELECT il.*,COALESCE(rr.result_count,0) result_count,COALESCE(rr.fail_count,0) fail_count,COALESCE(rr.defect_qty,0) defect_qty,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
     FROM erp_inspection_lot il
     LEFT JOIN erp_usage_decision ud ON ud.inspection_lot_id=il.id
     LEFT JOIN erp_plant ep ON ep.id=il.plant_id
     LEFT JOIN erp_storage_location es ON es.id=il.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=il.storage_bin_id
     LEFT JOIN (
       SELECT inspection_lot_id,COUNT(*) result_count,SUM(result_status='FAIL') fail_count,SUM(defect_qty) defect_qty
       FROM erp_inspection_lot_result GROUP BY inspection_lot_id
     ) rr ON rr.inspection_lot_id=il.id
     $where AND ud.id IS NULL
     ORDER BY il.created_at DESC,il.id DESC",
    $params
  );
  return $rows ? iterator_to_array($rows, false) : array();
}
function ud_kpi($db) {
  $row = $db->fetch("SELECT
    (SELECT COUNT(*) FROM erp_inspection_lot WHERE lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED')) pending_lot,
    (SELECT COUNT(*) FROM erp_usage_decision WHERE DATE(decision_at)=CURDATE()) today_ud,
    (SELECT COUNT(*) FROM erp_usage_decision WHERE decision_code IN ('R','P','RW','RTV','SCRAP')) exception_ud,
    (SELECT COUNT(*) FROM erp_usage_decision WHERE stock_posted='Y') posted_ud");
  return $row ?: (object)array('pending_lot'=>0,'today_ud'=>0,'exception_ud'=>0,'posted_ud'=>0);
}
function ud_defect_summary($db, $lotId) {
  $rows = $db->query("SELECT characteristic_name,defect_code,defect_qty,remarks FROM erp_inspection_lot_result WHERE inspection_lot_id=? AND result_status='FAIL' ORDER BY characteristic_no,id", array((int)$lotId));
  $parts = array();
  foreach ($rows as $row) $parts[] = trim($row->characteristic_name.' / '.$row->defect_code.' / '.ud_num($row->defect_qty).' / '.$row->remarks, ' /');
  return implode("\n", $parts);
}
function ud_insert_action($db, $id, $type, $text, $username) {
  return $db->insert('erp_usage_decision_action', array('usage_decision_id'=>$id,'action_type'=>$type,'action_text'=>$text,'action_by'=>$username));
}
function ud_decision_defaults($code) {
  $map = array(
    'A'=>array('text'=>'Accepted','follow'=>'RELEASE','move'=>'321','status'=>'UD_ACCEPTED','accepted_type'=>'UNRESTRICTED','rejected_type'=>null),
    'R'=>array('text'=>'Rejected','follow'=>'BLOCK','move'=>'350','status'=>'UD_REJECTED','accepted_type'=>null,'rejected_type'=>'BLOCKED'),
    'P'=>array('text'=>'Partial Accepted','follow'=>'PARTIAL_RELEASE','move'=>'321/350','status'=>'UD_PARTIAL','accepted_type'=>'UNRESTRICTED','rejected_type'=>'BLOCKED'),
    'RW'=>array('text'=>'Rework','follow'=>'REWORK','move'=>'350','status'=>'UD_REJECTED','accepted_type'=>null,'rejected_type'=>'BLOCKED'),
    'RTV'=>array('text'=>'Return to Vendor','follow'=>'RETURN_TO_VENDOR','move'=>'122','status'=>'UD_REJECTED','accepted_type'=>null,'rejected_type'=>'BLOCKED'),
    'SCRAP'=>array('text'=>'Scrap','follow'=>'SCRAP','move'=>'551','status'=>'UD_REJECTED','accepted_type'=>null,'rejected_type'=>'BLOCKED')
  );
  return isset($map[$code]) ? $map[$code] : $map['A'];
}
function ud_post_stock_effect($db, $lot, $acceptedQty, $rejectedQty, $decision, &$acceptedLayerId, &$rejectedLayerId) {
  $acceptedLayerId = null; $rejectedLayerId = null;
  if ((int)$lot->stock_layer_id <= 0) return 'N';
  $source = $db->fetch("SELECT * FROM stock_layer WHERE id=? LIMIT 1", array((int)$lot->stock_layer_id));
  if (!$source) return 'N';
  $total = $acceptedQty + $rejectedQty;
  if ($total <= 0) return 'N';
  if ((float)$source->qty_sisa + 0.00001 < $total) throw new Exception('Qty stock layer tidak cukup. Sisa '.$source->qty_sisa.', kebutuhan UD '.$total.'.');
  $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($total, (int)$source->id, $total));
  $base = array(
    'kode'=>$source->kode,
    'no_aju'=>$source->no_aju,
    'no_dokpab'=>$source->no_dokpab,
    'lokasi'=>$source->lokasi,
    'plant_id'=>$source->plant_id,
    'storage_location_id'=>$source->storage_location_id,
    'storage_bin_id'=>$source->storage_bin_id,
    'jenis_dokpab'=>$source->jenis_dokpab,
    'ref_table'=>'erp_usage_decision',
    'ref_id'=>0,
    'tgl_masuk'=>date('Y-m-d'),
    'no_bpb'=>$source->no_bpb
  );
  if ($acceptedQty > 0 && !empty($decision['accepted_type'])) {
    $row = $base; $row['qty_masuk']=$acceptedQty; $row['qty_sisa']=$acceptedQty; $row['stock_type']=$decision['accepted_type'];
    if (!$db->insert('stock_layer',$row)) throw new Exception($db->getErrorMessage() ?: 'Gagal membuat accepted stock layer.');
    $acceptedLayerId = (int)$db->last_insert_id();
  }
  if ($rejectedQty > 0 && !empty($decision['rejected_type'])) {
    $row = $base; $row['qty_masuk']=$rejectedQty; $row['qty_sisa']=$rejectedQty; $row['stock_type']=$decision['rejected_type'];
    if (!$db->insert('stock_layer',$row)) throw new Exception($db->getErrorMessage() ?: 'Gagal membuat rejected stock layer.');
    $rejectedLayerId = (int)$db->last_insert_id();
  }
  return 'Y';
}
?>
