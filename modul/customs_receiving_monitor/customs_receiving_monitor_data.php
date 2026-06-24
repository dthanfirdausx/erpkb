<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function crm_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function crm_status($row) {
  if ($row->status === 'REVERSED' || $row->is_reversal === 'Y') return 'REVERSED';
  $missingHeader = trim((string)$row->no_aju) === '' || trim((string)$row->no_dokpab) === '' || trim((string)$row->jenis_dokpab) === '';
  $missingItem = (int)$row->missing_item_customs > 0;
  if ($missingHeader || $missingItem) return 'INCOMPLETE';
  if ((int)$row->import_rows <= 0) return 'ERP_ONLY';
  if (abs((float)$row->qty_variance) > 0.00001 || abs((float)$row->value_variance) > 0.01) return 'MISMATCH';
  return 'COMPLETE';
}

function crm_status_badge($status) {
  $classes = array('COMPLETE'=>'crm-ok','INCOMPLETE'=>'crm-warn','ERP_ONLY'=>'crm-info','MISMATCH'=>'crm-danger','REVERSED'=>'crm-danger');
  $class = isset($classes[$status]) ? $classes[$status] : 'crm-info';
  return '<span class="crm-badge '.$class.'">'.crm_h($status).'</span>';
}

$columns = array(
  'p.no_bpb',
  'p.posting_date',
  'p.pemasok',
  'v.nama',
  'p.jenis_dokpab',
  'p.no_dokpab',
  'p.no_aju',
  'ds.item_count',
  'ds.total_qty',
  'ds.customs_qty',
  'im.import_qty',
  'p.customs_status'
);

$where = "";
$params = array();

if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND COALESCE(p.posting_date,p.tgl_bpb) BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['jenis_dokpab'])) {
  $where .= " AND p.jenis_dokpab=? ";
  $params[] = $_POST['jenis_dokpab'];
}
if (!empty($_POST['vendor'])) {
  $where .= " AND p.pemasok=? ";
  $params[] = $_POST['vendor'];
}
if (!empty($_POST['customs_status'])) {
  $where .= " AND p.customs_status=? ";
  $params[] = $_POST['customs_status'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (p.no_bpb LIKE ? OR p.no_aju LIKE ? OR p.no_dokpab LIKE ? OR p.nopo LIKE ? OR p.pemasok LIKE ? OR v.nama LIKE ? OR EXISTS (SELECT 1 FROM pemasukan_detail dx LEFT JOIN barang bx ON bx.kd_barang=dx.kode WHERE dx.no_bpb=p.no_bpb AND (dx.kode LIKE ? OR bx.nm_barang LIKE ? OR dx.hs_code LIKE ?))) ";
  for ($i=0; $i<9; $i++) $params[] = $keyword;
}

$statusFilter = isset($_POST['recon_status']) ? trim($_POST['recon_status']) : '';
if ($statusFilter === 'REVERSED') {
  $where .= " AND (p.status='REVERSED' OR p.is_reversal='Y') ";
} elseif ($statusFilter === 'INCOMPLETE') {
  $where .= " AND (COALESCE(p.no_aju,'')='' OR COALESCE(p.no_dokpab,'')='' OR COALESCE(p.jenis_dokpab,'')='' OR COALESCE(ds.missing_item_customs,0)>0) AND COALESCE(p.status,'POSTED')<>'REVERSED' AND COALESCE(p.is_reversal,'N')<>'Y' ";
} elseif ($statusFilter === 'ERP_ONLY') {
  $where .= " AND COALESCE(im.import_rows,0)=0 AND COALESCE(p.no_aju,'')<>'' AND COALESCE(p.no_dokpab,'')<>'' AND COALESCE(p.jenis_dokpab,'')<>'' AND COALESCE(ds.missing_item_customs,0)=0 AND COALESCE(p.status,'POSTED')<>'REVERSED' AND COALESCE(p.is_reversal,'N')<>'Y' ";
} elseif ($statusFilter === 'MISMATCH') {
  $where .= " AND COALESCE(im.import_rows,0)>0 AND COALESCE(p.status,'POSTED')<>'REVERSED' AND COALESCE(p.is_reversal,'N')<>'Y' AND (ABS(COALESCE(ds.customs_qty,ds.total_qty,0)-COALESCE(im.import_qty,0))>0.00001 OR ABS(COALESCE(ds.customs_value,ds.total_value,0)-COALESCE(im.import_value,0))>0.01) ";
} elseif ($statusFilter === 'COMPLETE') {
  $where .= " AND COALESCE(im.import_rows,0)>0 AND COALESCE(p.no_aju,'')<>'' AND COALESCE(p.no_dokpab,'')<>'' AND COALESCE(p.jenis_dokpab,'')<>'' AND COALESCE(ds.missing_item_customs,0)=0 AND ABS(COALESCE(ds.customs_qty,ds.total_qty,0)-COALESCE(im.import_qty,0))<=0.00001 AND ABS(COALESCE(ds.customs_value,ds.total_value,0)-COALESCE(im.import_value,0))<=0.01 ";
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("COALESCE(p.posting_date,p.tgl_bpb)");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT p.no_bpb,p.tgl_bpb,p.posting_date,p.pemasok,COALESCE(v.nama,p.pemasok) AS vendor_name,
          p.nopo,p.no_invoice,p.jenis_dokpab,p.no_dokpab,p.tgl_dokpab,p.no_aju,p.tgl_aju,p.customs_status,p.status,p.is_reversal,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty,
          COALESCE(ds.customs_qty,ds.total_qty,0) AS customs_qty,
          COALESCE(ds.total_value,0) AS total_value,
          COALESCE(ds.customs_value,ds.total_value,0) AS customs_value,
          COALESCE(ds.total_weight,0) AS total_weight,
          COALESCE(ds.missing_item_customs,0) AS missing_item_customs,
          COALESCE(im.import_rows,0) AS import_rows,
          COALESCE(im.import_qty,0) AS import_qty,
          COALESCE(im.import_value,0) AS import_value,
          COALESCE(ds.customs_qty,ds.total_qty,0)-COALESCE(im.import_qty,0) AS qty_variance,
          COALESCE(ds.customs_value,ds.total_value,0)-COALESCE(im.import_value,0) AS value_variance
   FROM pemasukan p
   LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
   LEFT JOIN (
     SELECT d.no_bpb,COUNT(*) AS item_count,SUM(d.jumlah) AS total_qty,SUM(COALESCE(d.customs_qty,d.jumlah)) AS customs_qty,
            SUM(d.nilai) AS total_value,SUM(COALESCE(d.customs_value,d.nilai)) AS customs_value,SUM(COALESCE(d.net_weight,d.berat,0)) AS total_weight,
            SUM(CASE WHEN COALESCE(d.hs_code,'')='' OR COALESCE(d.customs_qty,d.jumlah) IS NULL OR COALESCE(d.customs_uom,d.unit,'')='' THEN 1 ELSE 0 END) AS missing_item_customs
     FROM pemasukan_detail d
     GROUP BY d.no_bpb
   ) ds ON ds.no_bpb=p.no_bpb
   LEFT JOIN (
     SELECT no_bpb,COUNT(*) AS import_rows,SUM(CAST(REPLACE(COALESCE(jumlah,'0'),',','') AS DECIMAL(20,5))) AS import_qty,
            SUM(CAST(REPLACE(COALESCE(nilai,'0'),',','') AS DECIMAL(20,5))) AS import_value
     FROM import_pemasukan_temp
     GROUP BY no_bpb
   ) im ON im.no_bpb COLLATE latin1_general_ci=p.no_bpb
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $status = crm_status($value);
  $doc = '<strong>'.crm_h($value->no_bpb).'</strong><br><small class="text-muted">PO '.crm_h($value->nopo).'</small>';
  $vendor = '<strong>'.crm_h($value->pemasok).'</strong><br><small class="text-muted">'.crm_h($value->vendor_name).'</small>';
  $customsDoc = '<strong>'.crm_h(trim($value->jenis_dokpab.' '.$value->no_dokpab)).'</strong><br><small class="text-muted">'.crm_h($value->tgl_dokpab).'</small>';
  $customsStatus = $value->customs_status ? '<span class="label label-info">'.crm_h($value->customs_status).'</span>' : '<span class="label label-default">-</span>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = '<div class="crm-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-crm" data-no-bpb="'.crm_h($value->no_bpb).'" title="'.htmlspecialchars(customs_t('detail','Detail'), ENT_QUOTES, 'UTF-8').'"><i class="fa fa-eye"></i></button></div>';
  $result[] = $doc;
  $result[] = crm_h($value->posting_date ?: $value->tgl_bpb);
  $result[] = $vendor;
  $result[] = $customsDoc;
  $result[] = crm_h($value->no_aju);
  $result[] = number_format((float)$value->item_count,0,',','.');
  $result[] = number_format((float)$value->total_qty,5,',','.');
  $result[] = number_format((float)$value->customs_qty,5,',','.');
  $result[] = number_format((float)$value->import_qty,5,',','.');
  $result[] = crm_status_badge($status).'<br><small class="text-muted">Import rows '.number_format((float)$value->import_rows,0,',','.').'</small>';
  $result[] = $customsStatus;
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
