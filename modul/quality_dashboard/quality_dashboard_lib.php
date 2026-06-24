<?php
function qdash_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qdash_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function qdash_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function qdash_num($value, $decimals = 2) {
  return number_format((float)$value, $decimals, ',', '.');
}

function qdash_filters() {
  return array(
    'tgl_awal' => qdash_valid_date(qdash_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => qdash_valid_date(qdash_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => qdash_input('material_code'),
    'plant_id' => qdash_input('plant_id'),
    'storage_location_id' => qdash_input('storage_location_id'),
    'storage_bin_id' => qdash_input('storage_bin_id'),
    'stock_type' => qdash_input('stock_type'),
    'source_type' => qdash_input('source_type'),
    'keyword' => qdash_input('keyword')
  );
}

function qdash_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked Stock');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function qdash_status_badge($status) {
  $status = trim((string)$status);
  $class = 'label-default';
  if (in_array($status, array('QUALITY','Quality Inspection','NG','NCR','SCRAP','REWORK'))) $class = 'label-warning';
  if (in_array($status, array('BLOCKED','Blocked Stock'))) $class = 'label-danger';
  if (in_array($status, array('POSTED','OK','CLOSED'))) $class = 'label-success';
  return '<span class="label '.$class.'">'.qdash_h($status ?: '-').'</span>';
}

function qdash_location_text($row) {
  $parts = array();
  if (!empty($row->plant_code)) $parts[] = $row->plant_code;
  if (!empty($row->storage_code)) $parts[] = $row->storage_code;
  if (!empty($row->bin_code)) $parts[] = $row->bin_code;
  return implode(' / ', $parts);
}

function qdash_stock_where($filters, &$params, $dateColumnSql) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $where .= " AND $dateColumnSql BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND sl.kode=? "; $params[] = $filters['material_code']; }
  if ($filters['plant_id'] !== '') { $where .= " AND sl.plant_id=? "; $params[] = (int)$filters['plant_id']; }
  if ($filters['storage_location_id'] !== '') { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$filters['storage_location_id']; }
  if ($filters['storage_bin_id'] !== '') { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$filters['storage_bin_id']; }
  if ($filters['stock_type'] !== '') { $where .= " AND sl.stock_type=? "; $params[] = $filters['stock_type']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $where;
}

function qdash_stock_rows($db, $filters) {
  if ($filters['source_type'] !== '' && $filters['source_type'] !== 'STOCK') return array();
  $params = array();
  $dateColumn = "COALESCE(sl.tgl_masuk,DATE(sl.created_at))";
  $where = qdash_stock_where($filters, $params, $dateColumn);
  if ($filters['stock_type'] === '') {
    $where .= " AND sl.stock_type IN ('QUALITY','BLOCKED') ";
  }
  $rows = $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            $dateColumn AS doc_date
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY $dateColumn DESC, sl.id DESC",
    $params
  );
  $result = array();
  foreach ($rows as $row) {
    $bc = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
    $result[] = (object)array(
      'id' => $row->id,
      'source_type' => 'STOCK',
      'source_label' => 'Stock Layer',
      'doc_date' => $row->doc_date,
      'material_code' => $row->kode,
      'material_name' => $row->nm_barang,
      'qty' => (float)$row->qty_sisa,
      'uom' => $row->satuan,
      'status' => $row->stock_type,
      'location' => qdash_location_text($row),
      'reference' => trim((string)$row->no_bpb.' / '.(string)$row->ref_table.' #'.(string)$row->ref_id, ' /#'),
      'bc_document' => $bc,
      'remarks' => 'No Aju: '.$row->no_aju
    );
  }
  return $result;
}

function qdash_ng_rows($db, $filters) {
  if ($filters['source_type'] !== '' && $filters['source_type'] !== 'NG') return array();
  $where = " WHERE d.tgl_produksi BETWEEN ? AND ? ";
  $params = array($filters['tgl_awal'], $filters['tgl_akhir']);
  if ($filters['material_code'] !== '') { $where .= " AND d.kd_barang=? "; $params[] = $filters['material_code']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (d.kd_barang LIKE ? OR b.nm_barang LIKE ? OR d.ket LIKE ? OR n.catatan LIKE ? OR n.user LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[] = $kw;
  }
  $rows = $db->query(
    "SELECT d.*,n.user AS reporter,n.catatan,b.nm_barang,b.satuan
     FROM data_ng d
     LEFT JOIN ng n ON n.id=d.id_ng
     LEFT JOIN barang b ON b.kd_barang=d.kd_barang
     $where
     ORDER BY d.tgl_produksi DESC,d.id DESC",
    $params
  );
  $result = array();
  foreach ($rows as $row) {
    $result[] = (object)array(
      'id' => $row->id,
      'source_type' => 'NG',
      'source_label' => 'Defect / NG',
      'doc_date' => $row->tgl_produksi,
      'material_code' => $row->kd_barang,
      'material_name' => $row->nm_barang,
      'qty' => (float)$row->jumlah,
      'uom' => $row->satuan,
      'status' => 'NG',
      'location' => '',
      'reference' => 'NG #'.$row->id_ng,
      'bc_document' => '',
      'remarks' => trim((string)$row->ket.' '.$row->catatan)
    );
  }
  return $result;
}

function qdash_scrap_rows($db, $filters) {
  if ($filters['source_type'] !== '' && $filters['source_type'] !== 'SCRAP') return array();
  $where = " WHERE c.status='POSTED' AND COALESCE(c.scrap_qty,0)+COALESCE(c.rework_qty,0) > 0 AND c.posting_date BETWEEN ? AND ? ";
  $params = array($filters['tgl_awal'], $filters['tgl_akhir']);
  if ($filters['material_code'] !== '') { $where .= " AND po.material_code=? "; $params[] = $filters['material_code']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (c.confirmation_no LIKE ? OR po.no_production_order LIKE ? OR po.material_code LIKE ? OR po.material_name LIKE ? OR c.work_center LIKE ? OR c.remarks LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  $rows = $db->query(
    "SELECT c.*,po.no_production_order,po.material_code,po.material_name,po.uom,po.plant
     FROM production_order_confirmation c
     LEFT JOIN production_order po ON po.id_production_order=c.id_production_order
     $where
     ORDER BY c.posting_date DESC,c.id_confirmation DESC",
    $params
  );
  $result = array();
  foreach ($rows as $row) {
    $qty = (float)$row->scrap_qty + (float)$row->rework_qty;
    $result[] = (object)array(
      'id' => $row->id_confirmation,
      'source_type' => 'SCRAP',
      'source_label' => 'Production Scrap/Rework',
      'doc_date' => $row->posting_date,
      'material_code' => $row->material_code,
      'material_name' => $row->material_name,
      'qty' => $qty,
      'uom' => $row->uom,
      'status' => ((float)$row->scrap_qty > 0 ? 'SCRAP' : 'REWORK'),
      'location' => $row->plant.' / '.$row->work_center,
      'reference' => trim((string)$row->confirmation_no.' / '.(string)$row->no_production_order, ' /'),
      'bc_document' => '',
      'remarks' => $row->remarks
    );
  }
  return $result;
}

function qdash_exception_rows($db, $filters) {
  $rows = array_merge(qdash_stock_rows($db, $filters), qdash_ng_rows($db, $filters), qdash_scrap_rows($db, $filters));
  usort($rows, function($a, $b) {
    $da = (string)$a->doc_date; $dbb = (string)$b->doc_date;
    if ($da === $dbb) return strcmp((string)$b->source_type, (string)$a->source_type);
    return strcmp($dbb, $da);
  });
  return $rows;
}

function qdash_kpi($db, $filters) {
  $stockFilters = $filters;
  $stockFilters['source_type'] = '';
  $stockFilters['stock_type'] = '';
  $quality = $db->fetch("SELECT COALESCE(SUM(qty_sisa),0) AS qty,COUNT(*) AS layers FROM stock_layer WHERE qty_sisa>0 AND stock_type='QUALITY'");
  $blocked = $db->fetch("SELECT COALESCE(SUM(qty_sisa),0) AS qty,COUNT(*) AS layers FROM stock_layer WHERE qty_sisa>0 AND stock_type='BLOCKED'");
  $ng = $db->fetch("SELECT COALESCE(SUM(jumlah),0) AS qty,COUNT(*) AS rows_count FROM data_ng WHERE tgl_produksi BETWEEN ? AND ?", array($filters['tgl_awal'], $filters['tgl_akhir']));
  $prod = $db->fetch("SELECT COALESCE(SUM(yield_qty),0) AS yield_qty,COALESCE(SUM(scrap_qty),0) AS scrap_qty,COALESCE(SUM(rework_qty),0) AS rework_qty FROM production_order_confirmation WHERE status='POSTED' AND posting_date BETWEEN ? AND ?", array($filters['tgl_awal'], $filters['tgl_akhir']));
  $denominator = (float)$prod->yield_qty + (float)$prod->scrap_qty;
  $scrapRate = $denominator > 0 ? ((float)$prod->scrap_qty / $denominator) * 100 : 0;
  return array(
    'quality_qty' => (float)$quality->qty,
    'quality_layers' => (int)$quality->layers,
    'blocked_qty' => (float)$blocked->qty,
    'blocked_layers' => (int)$blocked->layers,
    'ng_qty' => (float)$ng->qty,
    'ng_count' => (int)$ng->rows_count,
    'scrap_qty' => (float)$prod->scrap_qty,
    'rework_qty' => (float)$prod->rework_qty,
    'yield_qty' => (float)$prod->yield_qty,
    'scrap_rate' => $scrapRate
  );
}

function qdash_stock_chart($db) {
  $rows = $db->query("SELECT stock_type,COALESCE(SUM(qty_sisa),0) AS qty FROM stock_layer WHERE qty_sisa>0 GROUP BY stock_type ORDER BY stock_type");
  $data = array();
  foreach ($rows as $row) $data[] = array('name'=>qdash_stock_type_label($row->stock_type),'y'=>(float)$row->qty);
  return $data;
}

function qdash_trend_chart($db, $filters) {
  $ngRows = $db->query("SELECT tgl_produksi AS dt,COALESCE(SUM(jumlah),0) AS qty FROM data_ng WHERE tgl_produksi BETWEEN ? AND ? GROUP BY tgl_produksi", array($filters['tgl_awal'],$filters['tgl_akhir']));
  $scrapRows = $db->query("SELECT posting_date AS dt,COALESCE(SUM(scrap_qty),0) AS qty FROM production_order_confirmation WHERE status='POSTED' AND posting_date BETWEEN ? AND ? GROUP BY posting_date", array($filters['tgl_awal'],$filters['tgl_akhir']));
  $map = array();
  foreach ($ngRows as $row) { if (!isset($map[$row->dt])) $map[$row->dt] = array('ng'=>0,'scrap'=>0); $map[$row->dt]['ng'] = (float)$row->qty; }
  foreach ($scrapRows as $row) { if (!isset($map[$row->dt])) $map[$row->dt] = array('ng'=>0,'scrap'=>0); $map[$row->dt]['scrap'] = (float)$row->qty; }
  ksort($map);
  $categories = array(); $ng = array(); $scrap = array();
  foreach ($map as $date => $values) { $categories[] = $date; $ng[] = $values['ng']; $scrap[] = $values['scrap']; }
  return array('categories'=>$categories,'ng'=>$ng,'scrap'=>$scrap);
}

function qdash_plants($db) {
  return $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
}

function qdash_storage_locations($db) {
  return $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
}

function qdash_storage_bins($db) {
  return $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
}
?>
