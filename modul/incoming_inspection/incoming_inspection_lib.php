<?php
require_once dirname(__DIR__)."/inspection_lot/inspection_lot_lib.php";

function iinq_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function iinq_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function iinq_filters() {
  return array(
    'tgl_awal' => iinq_valid_date(iinq_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => iinq_valid_date(iinq_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => iinq_input('material_code'),
    'plant_id' => iinq_input('plant_id'),
    'storage_location_id' => iinq_input('storage_location_id'),
    'storage_bin_id' => iinq_input('storage_bin_id'),
    'stock_type' => iinq_input('stock_type'),
    'inspection_status' => iinq_input('inspection_status'),
    'keyword' => iinq_input('keyword')
  );
}
function iinq_status_badge($lotStatus) {
  if ($lotStatus === '' || $lotStatus === null) return '<span class="label label-default">PENDING_LOT</span>';
  return ilot_status_badge($lotStatus);
}
function iinq_source_label($refTable) {
  $refTable = trim((string)$refTable);
  if ($refTable === 'pemasukan_detail') return 'GR / Pemasukan';
  if ($refTable === 'incoming_terima_detail') return 'Incoming Terima';
  if ($refTable === 'erp_gr_production') return 'GR Production';
  if ($refTable === 'transfer_detail') return 'Transfer Receipt';
  return $refTable ?: 'Stock Layer';
}
function iinq_candidate_where($filters, &$params) {
  $dateCol = "COALESCE(sl.tgl_masuk,DATE(sl.created_at))";
  $where = " WHERE sl.qty_sisa>0 AND sl.ref_table IN ('pemasukan_detail','incoming_terima_detail') AND $dateCol BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND sl.kode=? "; $params[] = $filters['material_code']; }
  if ($filters['plant_id'] !== '') { $where .= " AND sl.plant_id=? "; $params[] = (int)$filters['plant_id']; }
  if ($filters['storage_location_id'] !== '') { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$filters['storage_location_id']; }
  if ($filters['storage_bin_id'] !== '') { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$filters['storage_bin_id']; }
  if ($filters['stock_type'] !== '') { $where .= " AND sl.stock_type=? "; $params[] = $filters['stock_type']; }
  if ($filters['inspection_status'] === 'PENDING_LOT') $where .= " AND il.id IS NULL ";
  if ($filters['inspection_status'] !== '' && $filters['inspection_status'] !== 'PENDING_LOT') { $where .= " AND il.lot_status=? "; $params[] = $filters['inspection_status']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR il.lot_no LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $where;
}
function iinq_candidates($db, $filters) {
  $params = array();
  $dateCol = "COALESCE(sl.tgl_masuk,DATE(sl.created_at))";
  $where = iinq_candidate_where($filters, $params);
  $rows = $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            $dateCol AS receipt_date,
            il.id AS inspection_lot_id,il.lot_no,il.lot_status,il.sample_qty,il.accepted_qty,il.rejected_qty,il.ud_code,il.ud_text,il.ud_date,
            COALESCE(rr.result_count,0) AS result_count,COALESCE(rr.fail_count,0) AS fail_count
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     LEFT JOIN erp_inspection_lot il ON il.stock_layer_id=sl.id AND il.inspection_origin='GOODS_RECEIPT'
     LEFT JOIN (
       SELECT inspection_lot_id,COUNT(*) AS result_count,SUM(result_status='FAIL') AS fail_count
       FROM erp_inspection_lot_result GROUP BY inspection_lot_id
     ) rr ON rr.inspection_lot_id=il.id
     $where
     ORDER BY receipt_date DESC, sl.id DESC",
    $params
  );
  return $rows ? iterator_to_array($rows, false) : array();
}
function iinq_candidate($db, $stockLayerId) {
  $filters = array('tgl_awal'=>'1900-01-01','tgl_akhir'=>'2999-12-31','material_code'=>'','plant_id'=>'','storage_location_id'=>'','storage_bin_id'=>'','stock_type'=>'','inspection_status'=>'','keyword'=>'');
  $rows = iinq_candidates($db, $filters);
  foreach ($rows as $row) if ((int)$row->id === (int)$stockLayerId) return $row;
  return null;
}
function iinq_kpi($db) {
  $row = $db->fetch(
    "SELECT COUNT(*) AS candidate_count,
            SUM(il.id IS NULL) AS pending_lot,
            SUM(il.lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED')) AS open_lot,
            SUM(il.lot_status='UD_ACCEPTED') AS accepted_lot,
            SUM(il.lot_status IN ('UD_REJECTED','UD_PARTIAL')) AS exception_lot
     FROM stock_layer sl
     LEFT JOIN erp_inspection_lot il ON il.stock_layer_id=sl.id AND il.inspection_origin='GOODS_RECEIPT'
     WHERE sl.qty_sisa>0 AND sl.ref_table IN ('pemasukan_detail','incoming_terima_detail')"
  );
  return $row ?: (object)array('candidate_count'=>0,'pending_lot'=>0,'open_lot'=>0,'accepted_lot'=>0,'exception_lot'=>0);
}
function iinq_create_lot_from_layer($db, $stockLayerId, $username) {
  $row = iinq_candidate($db, $stockLayerId);
  if (!$row) return array('status'=>'error','message'=>'Stock layer tidak ditemukan.');
  if (!empty($row->inspection_lot_id)) return array('status'=>'good','id'=>(int)$row->inspection_lot_id,'lot_no'=>$row->lot_no,'existing'=>true);
  $lotNo = ilot_next_number($row->receipt_date ?: date('Y-m-d'));
  $data = array(
    'lot_no'=>$lotNo,
    'inspection_origin'=>'GOODS_RECEIPT',
    'inspection_type'=>'01',
    'source_ref_type'=>$row->ref_table,
    'source_ref_id'=>(int)$row->ref_id,
    'source_ref_no'=>$row->no_bpb,
    'stock_layer_id'=>(int)$row->id,
    'material_code'=>$row->kode,
    'material_name'=>$row->nm_barang,
    'lot_qty'=>(float)$row->qty_sisa,
    'sample_qty'=>(float)$row->qty_sisa,
    'uom'=>$row->satuan,
    'plant_id'=>(int)$row->plant_id,
    'storage_location_id'=>(int)$row->storage_location_id,
    'storage_bin_id'=>(int)$row->storage_bin_id,
    'stock_type'=>$row->stock_type,
    'no_aju'=>$row->no_aju,
    'jenis_dokpab'=>$row->jenis_dokpab,
    'no_dokpab'=>$row->no_dokpab,
    'no_bpb'=>$row->no_bpb,
    'notes'=>'Created from Incoming Inspection workbench.',
    'created_by'=>$username
  );
  if (!$db->insert('erp_inspection_lot', $data)) return array('status'=>'error','message'=>$db->getErrorMessage());
  $id = (int)$db->last_insert_id();
  ilot_create_default_results($db, $id, (float)$row->qty_sisa, $username);
  return array('status'=>'good','id'=>$id,'lot_no'=>$lotNo,'existing'=>false);
}
?>
