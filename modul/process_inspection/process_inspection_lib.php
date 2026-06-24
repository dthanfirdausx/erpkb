<?php
require_once dirname(__DIR__)."/inspection_lot/inspection_lot_lib.php";

function pins_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function pins_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function pins_filters() {
  return array(
    'tgl_awal' => pins_valid_date(pins_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => pins_valid_date(pins_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => pins_input('material_code'),
    'plant' => pins_input('plant'),
    'work_center' => pins_input('work_center'),
    'po_status' => pins_input('po_status'),
    'inspection_status' => pins_input('inspection_status'),
    'keyword' => pins_input('keyword')
  );
}
function pins_status_badge($lotStatus) {
  if ($lotStatus === '' || $lotStatus === null) return '<span class="label label-default">PENDING_LOT</span>';
  return ilot_status_badge($lotStatus);
}
function pins_source_label($row) {
  if (!empty($row->confirmation_no)) return 'Confirmation '.$row->confirmation_no;
  return 'Production Order '.$row->no_production_order;
}
function pins_where($filters, &$params) {
  $where = " WHERE c.status='POSTED' AND c.posting_date BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND po.material_code=? "; $params[] = $filters['material_code']; }
  if ($filters['plant'] !== '') { $where .= " AND po.plant=? "; $params[] = $filters['plant']; }
  if ($filters['work_center'] !== '') { $where .= " AND c.work_center=? "; $params[] = $filters['work_center']; }
  if ($filters['po_status'] !== '') { $where .= " AND po.status=? "; $params[] = $filters['po_status']; }
  if ($filters['inspection_status'] === 'PENDING_LOT') $where .= " AND il.id IS NULL ";
  if ($filters['inspection_status'] !== '' && $filters['inspection_status'] !== 'PENDING_LOT') { $where .= " AND il.lot_status=? "; $params[] = $filters['inspection_status']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (po.no_production_order LIKE ? OR c.confirmation_no LIKE ? OR po.material_code LIKE ? OR po.material_name LIKE ? OR c.work_center LIKE ? OR c.operation_name LIKE ? OR il.lot_no LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}
function pins_candidates($db, $filters) {
  $params = array();
  $where = pins_where($filters, $params);
  $rows = $db->query(
    "SELECT c.*,po.no_production_order,po.material_code,po.material_name,po.uom,po.order_qty,po.completed_qty,po.scrap_qty AS po_scrap_qty,po.plant,po.storage_location,po.status AS po_status,
            il.id AS inspection_lot_id,il.lot_no,il.lot_status,il.sample_qty,il.accepted_qty,il.rejected_qty,il.ud_code,il.ud_text,il.ud_date,
            COALESCE(rr.result_count,0) AS result_count,COALESCE(rr.fail_count,0) AS fail_count
     FROM production_order_confirmation c
     LEFT JOIN production_order po ON po.id_production_order=c.id_production_order
     LEFT JOIN erp_inspection_lot il ON il.source_ref_type='production_order_confirmation' AND il.source_ref_id=c.id_confirmation AND il.inspection_origin='PRODUCTION'
     LEFT JOIN (
       SELECT inspection_lot_id,COUNT(*) AS result_count,SUM(result_status='FAIL') AS fail_count
       FROM erp_inspection_lot_result GROUP BY inspection_lot_id
     ) rr ON rr.inspection_lot_id=il.id
     $where
     ORDER BY c.posting_date DESC,c.id_confirmation DESC",
    $params
  );
  return $rows ? iterator_to_array($rows, false) : array();
}
function pins_candidate($db, $confirmationId) {
  $filters = array('tgl_awal'=>'1900-01-01','tgl_akhir'=>'2999-12-31','material_code'=>'','plant'=>'','work_center'=>'','po_status'=>'','inspection_status'=>'','keyword'=>'');
  foreach (pins_candidates($db, $filters) as $row) {
    if ((int)$row->id_confirmation === (int)$confirmationId) return $row;
  }
  return null;
}
function pins_kpi($db) {
  $row = $db->fetch(
    "SELECT COUNT(*) AS candidate_count,
            SUM(il.id IS NULL) AS pending_lot,
            SUM(il.lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED')) AS open_lot,
            SUM(il.lot_status='UD_ACCEPTED') AS accepted_lot,
            SUM(il.lot_status IN ('UD_REJECTED','UD_PARTIAL')) AS exception_lot,
            COALESCE(SUM(c.scrap_qty),0) AS scrap_qty,
            COALESCE(SUM(c.rework_qty),0) AS rework_qty
     FROM production_order_confirmation c
     LEFT JOIN erp_inspection_lot il ON il.source_ref_type='production_order_confirmation' AND il.source_ref_id=c.id_confirmation AND il.inspection_origin='PRODUCTION'
     WHERE c.status='POSTED'"
  );
  return $row ?: (object)array('candidate_count'=>0,'pending_lot'=>0,'open_lot'=>0,'accepted_lot'=>0,'exception_lot'=>0,'scrap_qty'=>0,'rework_qty'=>0);
}
function pins_plants($db) {
  return $db->query("SELECT DISTINCT plant AS plant_code FROM production_order WHERE plant IS NOT NULL AND plant<>'' ORDER BY plant");
}
function pins_work_centers($db) {
  return $db->query("SELECT DISTINCT work_center FROM production_order_confirmation WHERE work_center IS NOT NULL AND work_center<>'' ORDER BY work_center");
}
function pins_create_lot_from_confirmation($db, $confirmationId, $username) {
  $row = pins_candidate($db, $confirmationId);
  if (!$row) return array('status'=>'error','message'=>'Production confirmation tidak ditemukan.');
  if (!empty($row->inspection_lot_id)) return array('status'=>'good','id'=>(int)$row->inspection_lot_id,'lot_no'=>$row->lot_no,'existing'=>true);
  $lotQty = (float)$row->yield_qty + (float)$row->scrap_qty + (float)$row->rework_qty;
  if ($lotQty <= 0) $lotQty = (float)$row->order_qty;
  $lotNo = ilot_next_number($row->posting_date ?: date('Y-m-d'));
  $data = array(
    'lot_no'=>$lotNo,
    'inspection_origin'=>'PRODUCTION',
    'inspection_type'=>'03',
    'source_ref_type'=>'production_order_confirmation',
    'source_ref_id'=>(int)$row->id_confirmation,
    'source_ref_no'=>$row->confirmation_no ?: $row->no_production_order,
    'material_code'=>$row->material_code,
    'material_name'=>$row->material_name,
    'lot_qty'=>$lotQty,
    'sample_qty'=>$lotQty,
    'accepted_qty'=>(float)$row->yield_qty,
    'rejected_qty'=>(float)$row->scrap_qty,
    'uom'=>$row->uom,
    'stock_type'=>'WIP',
    'batch_no'=>$row->no_production_order,
    'inspection_plan'=>trim((string)$row->operation_no.' '.$row->work_center),
    'notes'=>'Created from In-Process Inspection workbench. Operation: '.$row->operation_name,
    'created_by'=>$username
  );
  if (!$db->insert('erp_inspection_lot', $data)) return array('status'=>'error','message'=>$db->getErrorMessage());
  $id = (int)$db->last_insert_id();
  ilot_create_default_results($db, $id, $lotQty, $username);
  return array('status'=>'good','id'=>$id,'lot_no'=>$lotNo,'existing'=>false);
}
?>
