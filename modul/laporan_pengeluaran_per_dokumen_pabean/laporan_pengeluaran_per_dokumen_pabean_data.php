<?php
include "../../inc/config.php";

function lpk_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function lpk_num($value, $dec = 2) { return number_format((float)$value, $dec, '.', ','); }
function lpk_post($key, $default = '') { return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default; }

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
$search = isset($_POST['search']['value']) ? trim((string)$_POST['search']['value']) : '';
$tglAwal = lpk_post('tgl_awal');
$tglAkhir = lpk_post('tgl_akhir');
$jenisBc = lpk_post('jenisbc', 'all');

$legacyParams = array();
$gidParams = array();
$legacyWhere = " WHERE 1=1 ";
$gidWhere = " WHERE gi.status='POSTED' ";

if ($tglAwal !== '' && $tglAkhir === '') {
  $legacyWhere .= " AND v.tgl_sj BETWEEN ? AND ? ";
  $gidWhere .= " AND gi.posting_date BETWEEN ? AND ? ";
  $legacyParams[] = $tglAwal; $legacyParams[] = date('Y-m-d');
  $gidParams[] = $tglAwal; $gidParams[] = date('Y-m-d');
} elseif ($tglAwal !== '' && $tglAkhir !== '') {
  $legacyWhere .= " AND v.tgl_sj BETWEEN ? AND ? ";
  $gidWhere .= " AND gi.posting_date BETWEEN ? AND ? ";
  $legacyParams[] = $tglAwal; $legacyParams[] = $tglAkhir;
  $gidParams[] = $tglAwal; $gidParams[] = $tglAkhir;
}

if ($jenisBc !== '' && $jenisBc !== 'all') {
  $legacyWhere .= " AND v.jenis_dokpab = ? ";
  $gidWhere .= " AND gi.outbound_bc_type = ? ";
  $legacyParams[] = $jenisBc;
  $gidParams[] = $jenisBc;
}

$baseSql = "
  SELECT * FROM (
    SELECT
      CONCAT('legacy-',v.id) AS row_uid,
      'legacy' AS source_type,
      v.id AS source_id,
      v.id AS source_detail_id,
      v.jenis_dokpab,
      v.no_dokpab,
      v.tgl_dokpab,
      v.no_sj AS no_pengeluaran,
      v.tgl_sj AS tgl_pengeluaran,
      v.nama AS partner_name,
      v.kode AS material_code,
      v.nm_barang AS material_name,
      v.satuan AS uom,
      v.jumlah AS qty,
      v.nilai AS amount,
      v.no_aju,
      v.nd_catatan AS tujuan_pengeluaran
    FROM vpengeluaranbyjenisdokpab v
    $legacyWhere
    UNION ALL
    SELECT
      CONCAT('gid-',d.id) AS row_uid,
      'gid' AS source_type,
      gi.id AS source_id,
      d.id AS source_detail_id,
      gi.outbound_bc_type AS jenis_dokpab,
      gi.outbound_no_daftar AS no_dokpab,
      gi.outbound_tgl_daftar AS tgl_dokpab,
      COALESCE(NULLIF(gi.reference_surat_jalan,''),gi.gi_no) AS no_pengeluaran,
      gi.posting_date AS tgl_pengeluaran,
      gi.customer_name AS partner_name,
      d.material_code,
      d.material_name,
      d.uom,
      d.qty,
      d.amount,
      gi.outbound_no_aju AS no_aju,
      gi.outbound_bc_purpose AS tujuan_pengeluaran
    FROM erp_goods_issue_delivery gi
    JOIN erp_goods_issue_delivery_detail d ON d.gi_id=gi.id
    $gidWhere
  ) x
";

$params = array_merge($legacyParams, $gidParams);
$filterParams = $params;
$searchWhere = "";
if ($search !== '') {
  $kw = '%'.$search.'%';
  $searchWhere = " WHERE (x.jenis_dokpab LIKE ? OR x.no_dokpab LIKE ? OR x.no_pengeluaran LIKE ? OR x.partner_name LIKE ? OR x.material_code LIKE ? OR x.material_name LIKE ? OR x.no_aju LIKE ?) ";
  for ($i = 0; $i < 7; $i++) $filterParams[] = $kw;
}

$totalRow = $db->fetch("SELECT COUNT(*) total FROM ($baseSql) t", $params);
$filteredRow = $db->fetch("SELECT COUNT(*) total FROM ($baseSql $searchWhere) y", $filterParams);

$orderMap = array(
  1 => 'jenis_dokpab',
  2 => 'no_dokpab',
  3 => 'tgl_dokpab',
  4 => 'no_pengeluaran',
  5 => 'tgl_pengeluaran',
  6 => 'partner_name',
  7 => 'material_code',
  8 => 'material_name',
  9 => 'uom',
  10 => 'qty',
  11 => 'amount'
);
$orderCol = 'tgl_pengeluaran';
$orderDir = 'DESC';
if (isset($_POST['order'][0]['column'])) {
  $idx = (int)$_POST['order'][0]['column'];
  if (isset($orderMap[$idx])) $orderCol = $orderMap[$idx];
}
if (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc') $orderDir = 'ASC';

$limitSql = $length > 0 ? " LIMIT ".$start.",".$length : "";
$rows = $db->query("SELECT * FROM ($baseSql $searchWhere) z ORDER BY $orderCol $orderDir, row_uid DESC $limitSql", $filterParams);

$data = array();
$no = $start + 1;
foreach ($rows as $row) {
  $qtyLink = '<a href="javascript:void(0)" class="lpk-trace-link" data-source-type="'.lpk_h($row->source_type).'" data-source-id="'.lpk_h($row->source_id).'" data-source-detail-id="'.lpk_h($row->source_detail_id).'" data-material="'.lpk_h($row->material_code).'" data-doc="'.lpk_h($row->no_pengeluaran).'">'.lpk_num($row->qty, 2).'</a>';
  $data[] = array(
    $no++,
    lpk_h($row->jenis_dokpab),
    lpk_h($row->no_dokpab),
    lpk_h($row->tgl_dokpab),
    lpk_h($row->no_pengeluaran),
    lpk_h($row->tgl_pengeluaran),
    lpk_h($row->partner_name),
    lpk_h($row->material_code),
    lpk_h($row->material_name),
    lpk_h($row->uom),
    $qtyLink,
    lpk_num($row->amount, 2)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => $totalRow ? (int)$totalRow->total : 0,
  'recordsFiltered' => $filteredRow ? (int)$filteredRow->total : 0,
  'data' => $data
));
?>
