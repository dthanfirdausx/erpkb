<?php
require_once dirname(__DIR__)."/inspection_lot/inspection_lot_lib.php";

function fins_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function fins_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function fins_filters() {
  return array(
    'tgl_awal' => fins_valid_date(fins_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => fins_valid_date(fins_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => fins_input('material_code'),
    'plant_id' => fins_input('plant_id'),
    'storage_location_id' => fins_input('storage_location_id'),
    'storage_bin_id' => fins_input('storage_bin_id'),
    'stock_type' => fins_input('stock_type'),
    'inspection_status' => fins_input('inspection_status'),
    'keyword' => fins_input('keyword')
  );
}
function fins_status_badge($lotStatus) {
  if ($lotStatus === '' || $lotStatus === null) return '<span class="label label-default">PENDING_LOT</span>';
  return ilot_status_badge($lotStatus);
}
function fins_where($filters, &$params) {
  $where = " WHERE gr.status='POSTED' AND gr.posting_date BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND d.material_code=? "; $params[] = $filters['material_code']; }
  if ($filters['plant_id'] !== '') { $where .= " AND gr.plant_id=? "; $params[] = (int)$filters['plant_id']; }
  if ($filters['storage_location_id'] !== '') { $where .= " AND gr.storage_location_id=? "; $params[] = (int)$filters['storage_location_id']; }
  if ($filters['storage_bin_id'] !== '') { $where .= " AND gr.storage_bin_id=? "; $params[] = (int)$filters['storage_bin_id']; }
  if ($filters['stock_type'] !== '') { $where .= " AND gr.stock_type=? "; $params[] = $filters['stock_type']; }
  if ($filters['inspection_status'] === 'PENDING_LOT') $where .= " AND il.id IS NULL ";
  if ($filters['inspection_status'] !== '' && $filters['inspection_status'] !== 'PENDING_LOT') { $where .= " AND il.lot_status=? "; $params[] = $filters['inspection_status']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (gr.gr_no LIKE ? OR gr.no_production_order LIKE ? OR gr.confirmation_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR il.lot_no LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $where;
}
function fins_candidates($db, $filters) {
  $params = array();
  $where = fins_where($filters, $params);
  $rows = $db->query(
    "SELECT d.*,gr.gr_no,gr.id_confirmation,gr.id_production_order,gr.no_production_order,gr.confirmation_no,gr.document_date,gr.posting_date,gr.plant_id,gr.storage_location_id,gr.storage_bin_id,gr.stock_type AS gr_stock_type,gr.status AS gr_status,gr.remarks AS gr_remarks,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            il.id AS inspection_lot_id,il.lot_no,il.lot_status,il.sample_qty,il.accepted_qty,il.rejected_qty,il.ud_code,il.ud_text,il.ud_date,
            COALESCE(rr.result_count,0) AS result_count,COALESCE(rr.fail_count,0) AS fail_count
     FROM erp_gr_production_detail d
     JOIN erp_gr_production gr ON gr.id=d.gr_id
     LEFT JOIN erp_plant ep ON ep.id=gr.plant_id
     LEFT JOIN erp_storage_location es ON es.id=gr.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=gr.storage_bin_id
     LEFT JOIN erp_inspection_lot il ON il.source_ref_type='erp_gr_production_detail' AND il.source_ref_id=d.id AND il.inspection_origin='PRODUCTION' AND il.inspection_type='04'
     LEFT JOIN (
       SELECT inspection_lot_id,COUNT(*) AS result_count,SUM(result_status='FAIL') AS fail_count
       FROM erp_inspection_lot_result GROUP BY inspection_lot_id
     ) rr ON rr.inspection_lot_id=il.id
     $where
     ORDER BY gr.posting_date DESC, d.id DESC",
    $params
  );
  return $rows ? iterator_to_array($rows, false) : array();
}
function fins_candidate($db, $detailId) {
  $filters = array('tgl_awal'=>'1900-01-01','tgl_akhir'=>'2999-12-31','material_code'=>'','plant_id'=>'','storage_location_id'=>'','storage_bin_id'=>'','stock_type'=>'','inspection_status'=>'','keyword'=>'');
  foreach (fins_candidates($db, $filters) as $row) {
    if ((int)$row->id === (int)$detailId) return $row;
  }
  return null;
}
function fins_kpi($db) {
  $row = $db->fetch(
    "SELECT COUNT(*) AS candidate_count,
            SUM(il.id IS NULL) AS pending_lot,
            SUM(il.lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED')) AS open_lot,
            SUM(il.lot_status='UD_ACCEPTED') AS accepted_lot,
            SUM(il.lot_status IN ('UD_REJECTED','UD_PARTIAL')) AS exception_lot,
            COALESCE(SUM(d.qty),0) AS output_qty
     FROM erp_gr_production_detail d
     JOIN erp_gr_production gr ON gr.id=d.gr_id
     LEFT JOIN erp_inspection_lot il ON il.source_ref_type='erp_gr_production_detail' AND il.source_ref_id=d.id AND il.inspection_origin='PRODUCTION' AND il.inspection_type='04'
     WHERE gr.status='POSTED'"
  );
  return $row ?: (object)array('candidate_count'=>0,'pending_lot'=>0,'open_lot'=>0,'accepted_lot'=>0,'exception_lot'=>0,'output_qty'=>0);
}
function fins_plants($db) {
  return $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
}
function fins_storage_locations($db) {
  return $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
}
function fins_storage_bins($db) {
  return $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
}
function fins_create_lot_from_gr_detail($db, $detailId, $username) {
  $row = fins_candidate($db, $detailId);
  if (!$row) return array('status'=>'error','message'=>'GR production detail tidak ditemukan.');
  if (!empty($row->inspection_lot_id)) return array('status'=>'good','id'=>(int)$row->inspection_lot_id,'lot_no'=>$row->lot_no,'existing'=>true);
  $lotNo = ilot_next_number($row->posting_date ?: date('Y-m-d'));
  $data = array(
    'lot_no'=>$lotNo,
    'inspection_origin'=>'PRODUCTION',
    'inspection_type'=>'04',
    'source_ref_type'=>'erp_gr_production_detail',
    'source_ref_id'=>(int)$row->id,
    'source_ref_no'=>$row->gr_no,
    'stock_layer_id'=>(int)$row->stock_layer_id,
    'material_code'=>$row->material_code,
    'material_name'=>$row->material_name,
    'lot_qty'=>(float)$row->qty,
    'sample_qty'=>(float)$row->qty,
    'accepted_qty'=>0,
    'rejected_qty'=>0,
    'uom'=>$row->uom,
    'plant_id'=>(int)$row->plant_id,
    'storage_location_id'=>(int)$row->storage_location_id,
    'storage_bin_id'=>(int)$row->storage_bin_id,
    'stock_type'=>$row->gr_stock_type,
    'batch_no'=>$row->no_production_order,
    'inspection_plan'=>'Final inspection / GR production',
    'notes'=>'Created from Final Inspection workbench. GR: '.$row->gr_no,
    'created_by'=>$username
  );
  if (!$db->insert('erp_inspection_lot', $data)) return array('status'=>'error','message'=>$db->getErrorMessage());
  $id = (int)$db->last_insert_id();
  ilot_create_default_results($db, $id, (float)$row->qty, $username);
  return array('status'=>'good','id'=>$id,'lot_no'=>$lotNo,'existing'=>false);
}
?>
