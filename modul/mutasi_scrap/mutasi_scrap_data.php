<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
function ms_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function ms_num($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function ms_post($key, $default = '') { return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default; }

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$tglAwal = ms_post('tgl_awal', date('Y-m-01'));
$tglAkhir = ms_post('tgl_akhir', date('Y-m-d'));
$material = ms_post('material_code');
$plantId = ms_post('plant_id');
$slocId = ms_post('storage_location_id');
$binId = ms_post('storage_bin_id');
$stockType = ms_post('stock_type');
$keyword = ms_post('keyword');
$search = isset($_POST['search']['value']) ? trim((string)$_POST['search']['value']) : '';
if ($keyword === '' && $search !== '') $keyword = $search;

$where = " WHERE b.kd_kategori='K04' ";
$filterParams = array();
if ($material !== '') { $where .= " AND b.kd_barang=? "; $filterParams[] = $material; }
if ($plantId !== '') { $where .= " AND COALESCE(dt.plant_id,sl.plant_id)=? "; $filterParams[] = $plantId; }
if ($slocId !== '') { $where .= " AND COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)=? "; $filterParams[] = $slocId; }
if ($binId !== '') { $where .= " AND COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)=? "; $filterParams[] = $binId; }
if ($stockType !== '') { $where .= " AND COALESCE(dt.stock_type,dt.destination_stock_type,sl.stock_type,'UNRESTRICTED')=? "; $filterParams[] = $stockType; }
if ($keyword !== '') {
  $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ? OR dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.remark LIKE ?) ";
  $kw = '%'.$keyword.'%';
  for ($i=0; $i<7; $i++) $filterParams[] = $kw;
}

$movementExpr = "CASE WHEN dt.id_detail IS NULL THEN 'IN' WHEN dt.direction='OUT' OR COALESCE(dt.qty,0)<0 OR dt.move_code IN ('102','122','201','221','261','262','532','551','601','602','702','712') THEN 'OUT' ELSE 'IN' END";
$adjustExpr = "CASE WHEN dt.move_code IN ('701','702','711','712') OR COALESCE(dt.ref_type,'') LIKE '%DIFF%' OR COALESCE(dt.ref_type,'') LIKE '%OPNAME%' OR COALESCE(dt.ref_type,'') LIKE '%ADJUST%' THEN 1 ELSE 0 END";
$signedQtyExpr = "CASE WHEN ($movementExpr)='OUT' THEN -ABS(COALESCE(dt.qty,0)) ELSE ABS(COALESCE(dt.qty,0)) END";

$baseSql = "
  SELECT b.id,b.kd_barang,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code,
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
    AND (dt.posisi='GUDANG' OR dt.lokasi LIKE '%GUDANG%' OR dt.lokasi LIKE '%WAREHOUSE%' OR dt.posisi IS NULL)
    AND COALESCE(dt.is_reversal,0)=0
  LEFT JOIN stock_layer sl ON sl.id=dt.ref_id AND sl.kode=b.kd_barang
  LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,sl.plant_id)
  LEFT JOIN erp_storage_location es ON es.id=COALESCE(dt.storage_location_id,dt.destination_storage_location_id,sl.storage_location_id)
  LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,sl.storage_bin_id)
  $where
  GROUP BY b.id,b.kd_barang,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code,stock_type
  HAVING saldo_awal<>0 OR pemasukan<>0 OR pengeluaran<>0 OR penyesuaian<>0 OR saldo_akhir<>0
";
$params = array($tglAwal.' 00:00:00',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAkhir.' 23:59:59',$tglAwal.' 00:00:00',$tglAkhir.' 23:59:59',$tglAkhir.' 23:59:59');
$queryParams = array_merge($params, $filterParams);
$countRow = $db->fetch("SELECT COUNT(*) total FROM ($baseSql) x", $queryParams);

$orderMap = array(1=>'kd_barang',2=>'nm_barang',3=>'satuan',4=>'saldo_awal',5=>'pemasukan',6=>'pengeluaran',7=>'penyesuaian',8=>'saldo_akhir');
$orderCol = 'kd_barang'; $orderDir = 'ASC';
if (isset($_POST['order'][0]['column'])) { $idx=(int)$_POST['order'][0]['column']; if(isset($orderMap[$idx])) $orderCol=$orderMap[$idx]; }
if (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'desc') $orderDir='DESC';
$rows = $db->query("SELECT * FROM ($baseSql) y ORDER BY $orderCol $orderDir LIMIT $start,$length", $queryParams);

$data = array(); $no = $start + 1;
foreach ($rows as $row) {
  $saldoAkhir=(float)$row->saldo_akhir; $stockOpname=$saldoAkhir; $selisih=$stockOpname-$saldoAkhir;
  $ket=array();
  if((int)$row->movement_lines>0) $ket[]=(int)$row->movement_lines.' line transaksi';
  if($row->plant_code || $row->storage_code || $row->bin_code) $ket[]=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /');
  if($row->stock_type) $ket[]=$row->stock_type;
  $data[] = array(
    $no++,
    '<strong>'.ms_h($row->kd_barang).'</strong>',
    ms_h($row->nm_barang),
    ms_h($row->satuan),
    ms_num($row->saldo_awal),
    '<a href="javascript:void(0)" class="ms-detail-link" data-material="'.ms_h($row->kd_barang).'" data-type="IN">'.ms_num($row->pemasukan).'</a>',
    '<a href="javascript:void(0)" class="ms-detail-link" data-material="'.ms_h($row->kd_barang).'" data-type="OUT">'.ms_num($row->pengeluaran).'</a>',
    '<a href="javascript:void(0)" class="ms-detail-link" data-material="'.ms_h($row->kd_barang).'" data-type="ADJ">'.ms_num($row->penyesuaian).'</a>',
    ms_num($saldoAkhir),
    ms_num($stockOpname),
    ms_num($selisih),
    ms_h(implode(' | ', $ket))
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$countRow?(int)$countRow->total:0,'recordsFiltered'=>$countRow?(int)$countRow->total:0,'data'=>$data));
?>
