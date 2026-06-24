<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";

function mbb_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function mbb_num($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function mbb_post($key, $default = '') { return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default; }
function mbb_stock_opname_row($db, $material, $tglAkhir, $plantId, $slocId, $binId, $stockType) {
  $sql = "
    SELECT doc_type,doc_no,count_date,counted_qty FROM (
      SELECT 'STOCK_OPNAME' AS doc_type,d.doc_no,d.opname_date AS count_date,i.counted_qty,i.counted_at
      FROM stock_opname_document_items i
      JOIN stock_opname_documents d ON d.id=i.document_id
      WHERE d.status<>'CANCELLED' AND i.status IN ('COUNTED','POSTED') AND i.material_code=? AND d.opname_date=?
        AND (? IS NULL OR COALESCE(i.plant_id,d.plant_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_location_id,d.storage_location_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_bin_id,d.storage_bin_id)=?)
        AND (?='' OR COALESCE(i.stock_type,d.stock_type,'UNRESTRICTED')=?)
      UNION ALL
      SELECT 'CYCLE_COUNT' AS doc_type,d.doc_no,d.count_date AS count_date,i.counted_qty,i.counted_at
      FROM cycle_count_document_items i
      JOIN cycle_count_documents d ON d.id=i.document_id
      WHERE d.status<>'CANCELLED' AND i.status IN ('COUNTED','POSTED') AND i.material_code=? AND d.count_date=?
        AND (? IS NULL OR COALESCE(i.plant_id,d.plant_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_location_id,d.storage_location_id)=?)
        AND (? IS NULL OR COALESCE(i.storage_bin_id,d.storage_bin_id)=?)
        AND (?='' OR COALESCE(i.stock_type,d.stock_type,'UNRESTRICTED')=?)
    ) x
    ORDER BY count_date DESC, counted_at DESC, doc_no DESC
    LIMIT 1";
  $plantParam = $plantId === null || $plantId === '' ? null : (int)$plantId;
  $slocParam = $slocId === null || $slocId === '' ? null : (int)$slocId;
  $binParam = $binId === null || $binId === '' ? null : (int)$binId;
  $stockParam = trim((string)$stockType);
  $params = array(
    $material, $tglAkhir, $plantParam, $plantParam, $slocParam, $slocParam, $binParam, $binParam, $stockParam, $stockParam,
    $material, $tglAkhir, $plantParam, $plantParam, $slocParam, $slocParam, $binParam, $binParam, $stockParam, $stockParam
  );
  return $db->fetch($sql, $params);
}

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$tglAwal = mbb_post('tgl_awal', date('Y-m-01'));
$tglAkhir = mbb_post('tgl_akhir', date('Y-m-d'));
$material = mbb_post('material_code');
$plantId = mbb_post('plant_id');
$slocId = mbb_post('storage_location_id');
$binId = mbb_post('storage_bin_id');
$stockType = mbb_post('stock_type');
$keyword = mbb_post('keyword');
$search = isset($_POST['search']['value']) ? trim((string)$_POST['search']['value']) : '';
if ($keyword === '' && $search !== '') $keyword = $search;

$where = " WHERE b.kd_kategori='K01' ";
$params = array($tglAwal.' 00:00:00', $tglAwal.' 00:00:00', $tglAkhir.' 23:59:59', $tglAkhir.' 23:59:59');

if ($material !== '') { $where .= " AND b.kd_barang=? "; $params[] = $material; }
if ($plantId !== '') { $where .= " AND COALESCE(dt.plant_id,sl.plant_id)=? "; $params[] = $plantId; }
if ($slocId !== '') { $where .= " AND COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)=? "; $params[] = $slocId; }
if ($binId !== '') { $where .= " AND COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)=? "; $params[] = $binId; }
if ($stockType !== '') { $where .= " AND COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED')=? "; $params[] = $stockType; }
if ($keyword !== '') {
  $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ? OR dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.remark LIKE ?) ";
  $kw = '%'.$keyword.'%';
  for ($i=0; $i<7; $i++) $params[] = $kw;
}

$movementExpr = "
  CASE
    WHEN dt.id_detail IS NULL THEN 'IN'
    WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','551','601','602') THEN 'OUT'
    ELSE 'IN'
  END
";
$adjustExpr = "
  CASE
    WHEN dt.move_code IN ('701','702','711','712')
      OR COALESCE(dt.ref_type,'') LIKE '%DIFF%'
      OR COALESCE(dt.ref_type,'') LIKE '%OPNAME%'
      OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%'
    THEN 1 ELSE 0
  END
";
$signedQtyExpr = "CASE WHEN ($movementExpr)='OUT' THEN -ABS(COALESCE(dt.qty,0)) ELSE ABS(COALESCE(dt.qty,0)) END";

$baseSql = "
  SELECT
    b.id,
    b.kd_barang,
    b.nm_barang,
    b.satuan,
    COALESCE(dt.plant_id,sl.plant_id) AS plant_id,
    COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id) AS storage_location_id,
    COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id) AS storage_bin_id,
    ep.plant_code,
    es.storage_code,
    eb.bin_code,
    COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED') AS stock_type,
    COALESCE(SUM(CASE WHEN dt.document_date < ? THEN $signedQtyExpr ELSE 0 END),0) AS saldo_awal,
    COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=0 AND ($movementExpr)='IN' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS pemasukan,
    COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=0 AND ($movementExpr)='OUT' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS pengeluaran,
    COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? AND ($adjustExpr)=1 THEN $signedQtyExpr ELSE 0 END),0) AS penyesuaian,
    COALESCE(SUM(CASE WHEN dt.document_date <= ? THEN $signedQtyExpr ELSE 0 END),0) AS saldo_akhir,
    COALESCE(SUM(CASE WHEN dt.document_date BETWEEN ? AND ? THEN 1 ELSE 0 END),0) AS movement_lines
  FROM barang b
  LEFT JOIN detail_transaksi dt ON dt.kd_barang=b.kd_barang
    AND dt.document_date <= ?
    AND (
      dt.posisi='GUDANG'
      OR dt.lokasi LIKE '%GUDANG%'
      OR dt.lokasi LIKE '%WAREHOUSE%'
      OR dt.move_code IN ('201','221','261','262','551','601','602','701','702','711','712')
      OR COALESCE(dt.ref_type,'') IN ('ISSUE_PROD','ISSUE_PRODUCTION','GI_DELIVERY','MANUAL_ADJUSTMENT','PI_DIFF')
      OR COALESCE(dt.ref_type,'') LIKE '%DIFF%'
      OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%'
      OR dt.posisi IS NULL
    )
    AND COALESCE(dt.is_reversal,0)=0
  LEFT JOIN stock_layer sl ON sl.id=dt.ref_id AND sl.kode=b.kd_barang
  LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,sl.plant_id)
  LEFT JOIN erp_storage_location es ON es.id=COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)
  LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)
  $where
  GROUP BY b.id,b.kd_barang,b.nm_barang,b.satuan,plant_id,storage_location_id,storage_bin_id,ep.plant_code,es.storage_code,eb.bin_code,stock_type
  HAVING saldo_awal<>0 OR pemasukan<>0 OR pengeluaran<>0 OR penyesuaian<>0 OR saldo_akhir<>0
";

$queryParams = array(
  $tglAwal.' 00:00:00',
  $tglAwal.' 00:00:00', $tglAkhir.' 23:59:59',
  $tglAwal.' 00:00:00', $tglAkhir.' 23:59:59',
  $tglAwal.' 00:00:00', $tglAkhir.' 23:59:59',
  $tglAkhir.' 23:59:59',
  $tglAwal.' 00:00:00', $tglAkhir.' 23:59:59',
  $tglAkhir.' 23:59:59'
);
$queryParams = array_merge($queryParams, array_slice($params, 4));

$countRow = $db->fetch("SELECT COUNT(*) total FROM ($baseSql) x", $queryParams);
$orderMap = array(
  1 => 'kd_barang',
  2 => 'nm_barang',
  3 => 'satuan',
  4 => 'saldo_awal',
  5 => 'pemasukan',
  6 => 'pengeluaran',
  7 => 'penyesuaian',
  8 => 'saldo_akhir',
  9 => 'plant_code',
  10 => 'stock_type'
);
$orderCol = 'kd_barang';
$orderDir = 'ASC';
if (isset($_POST['order'][0]['column'])) {
  $idx = (int)$_POST['order'][0]['column'];
  if (isset($orderMap[$idx])) $orderCol = $orderMap[$idx];
}
if (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'desc') $orderDir = 'DESC';

$rows = $db->query("SELECT * FROM ($baseSql) y ORDER BY $orderCol $orderDir LIMIT $start,$length", $queryParams);
$data = array();
$no = $start + 1;
foreach ($rows as $row) {
  $saldoAkhir = (float)$row->saldo_akhir;
  $stockOpnameRow = mbb_stock_opname_row($db, $row->kd_barang, $tglAkhir, $row->plant_id, $row->storage_location_id, $row->storage_bin_id, $row->stock_type);
  $hasStockOpname = $stockOpnameRow && $stockOpnameRow->counted_qty !== null;
  $stockOpname = $hasStockOpname ? (float)$stockOpnameRow->counted_qty : null;
  $selisih = $hasStockOpname ? $stockOpname - $saldoAkhir : null;
  $keterangan = array();
  if ((int)$row->movement_lines > 0) $keterangan[] = (int)$row->movement_lines.' line transaksi';
  if ($hasStockOpname) $keterangan[] = $stockOpnameRow->doc_type.' '.$stockOpnameRow->doc_no.' tgl '.$stockOpnameRow->count_date;
  if ($row->plant_code || $row->storage_code || $row->bin_code) $keterangan[] = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  if ($row->stock_type) $keterangan[] = $row->stock_type;
  $data[] = array(
    $no++,
    '<strong>'.mbb_h($row->kd_barang).'</strong>',
    mbb_h($row->nm_barang),
    mbb_h($row->satuan),
    mbb_num($row->saldo_awal),
    '<a href="javascript:void(0)" class="mbb-detail-link" data-material="'.mbb_h($row->kd_barang).'" data-type="IN">'.mbb_num($row->pemasukan).'</a>',
    '<a href="javascript:void(0)" class="mbb-detail-link" data-material="'.mbb_h($row->kd_barang).'" data-type="OUT">'.mbb_num($row->pengeluaran).'</a>',
    '<a href="javascript:void(0)" class="mbb-detail-link" data-material="'.mbb_h($row->kd_barang).'" data-type="ADJ">'.mbb_num($row->penyesuaian).'</a>',
    mbb_num($saldoAkhir),
    $hasStockOpname ? mbb_num($stockOpname) : '-',
    $hasStockOpname ? mbb_num($selisih) : '-',
    mbb_h(implode(' | ', $keterangan))
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => $countRow ? (int)$countRow->total : 0,
  'recordsFiltered' => $countRow ? (int)$countRow->total : 0,
  'data' => $data
));
?>
