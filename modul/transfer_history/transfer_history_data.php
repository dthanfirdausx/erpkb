<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function thd_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function thd_status($status) {
  if ($status === 'POSTED') return '<span class="th-badge th-posted">POSTED</span>';
  if ($status === 'REVERSED') return '<span class="th-badge th-reversed">REVERSED</span>';
  return '<span class="label label-default">'.thd_h($status).'</span>';
}
function thd_union_sql() {
  return "
    SELECT 'SLT' AS transfer_type,'Storage Location Transfer' AS transfer_type_label,h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,NULL AS source_stock_type,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,NULL AS destination_stock_type,
           sp.plant_code AS source_plant_code,dp.plant_code AS destination_plant_code,
           src.storage_code AS source_storage_code,src.storage_name AS source_storage_name,dst.storage_code AS destination_storage_code,dst.storage_name AS destination_storage_name,
           sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,
           COALESCE(ds.item_count,0) AS item_count,COALESCE(ds.total_qty,0) AS total_qty,COALESCE(ds.total_amount,0) AS total_amount,COALESCE(ts.trace_count,0) AS trace_count,
           COALESCE(kw.keyword_text,'') AS keyword_text
    FROM erp_storage_location_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_storage_location_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_storage_location_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab) SEPARATOR ' ') keyword_text FROM erp_storage_location_transfer_detail d LEFT JOIN erp_storage_location_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
    UNION ALL
    SELECT 'SBT','Storage Bin Transfer',h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,NULL,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,NULL,
           sp.plant_code,dp.plant_code,src.storage_code,src.storage_name,dst.storage_code,dst.storage_name,sb.bin_code,dbin.bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,
           COALESCE(ds.item_count,0),COALESCE(ds.total_qty,0),COALESCE(ds.total_amount,0),COALESCE(ts.trace_count,0),
           COALESCE(kw.keyword_text,'')
    FROM erp_storage_bin_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_storage_bin_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_storage_bin_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab) SEPARATOR ' ') keyword_text FROM erp_storage_bin_transfer_detail d LEFT JOIN erp_storage_bin_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
    UNION ALL
    SELECT 'STT','Stock Type Transfer',h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,h.source_stock_type,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,h.destination_stock_type,
           sp.plant_code,dp.plant_code,src.storage_code,src.storage_name,dst.storage_code,dst.storage_name,sb.bin_code,dbin.bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,
           COALESCE(ds.item_count,0),COALESCE(ds.total_qty,0),COALESCE(ds.total_amount,0),COALESCE(ts.trace_count,0),
           COALESCE(kw.keyword_text,'')
    FROM erp_stock_type_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_stock_type_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_stock_type_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab,t.source_stock_type,t.destination_stock_type) SEPARATOR ' ') keyword_text FROM erp_stock_type_transfer_detail d LEFT JOIN erp_stock_type_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
  ";
}
function thd_filter(&$params) {
  $where = " WHERE 1=1 ";
  if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) { $where .= " AND th.posting_date BETWEEN ? AND ? "; $params[]=$_POST['tgl_awal']; $params[]=$_POST['tgl_akhir']; }
  if (!empty($_POST['transfer_type'])) { $where .= " AND th.transfer_type=? "; $params[]=$_POST['transfer_type']; }
  if (!empty($_POST['status'])) { $where .= " AND th.status=? "; $params[]=$_POST['status']; }
  if (!empty($_POST['movement_type'])) { $where .= " AND th.movement_type=? "; $params[]=$_POST['movement_type']; }
  if (!empty($_POST['plant_id'])) { $where .= " AND (th.source_plant_id=? OR th.destination_plant_id=?) "; $params[]=(int)$_POST['plant_id']; $params[]=(int)$_POST['plant_id']; }
  if (!empty($_POST['storage_location_id'])) { $where .= " AND (th.source_storage_location_id=? OR th.destination_storage_location_id=?) "; $params[]=(int)$_POST['storage_location_id']; $params[]=(int)$_POST['storage_location_id']; }
  if (!empty($_POST['storage_bin_id'])) { $where .= " AND (th.source_storage_bin_id=? OR th.destination_storage_bin_id=?) "; $params[]=(int)$_POST['storage_bin_id']; $params[]=(int)$_POST['storage_bin_id']; }
  if (!empty($_POST['stock_type'])) { $where .= " AND (th.source_stock_type=? OR th.destination_stock_type=? OR th.keyword_text LIKE ?) "; $params[]=$_POST['stock_type']; $params[]=$_POST['stock_type']; $params[]='%'.$_POST['stock_type'].'%'; }
  if (!empty($_POST['user'])) { $where .= " AND th.created_by=? "; $params[]=$_POST['user']; }
  if (!empty($_POST['keyword'])) {
    $kw = '%'.trim($_POST['keyword']).'%';
    $where .= " AND (th.transfer_no LIKE ? OR th.reference_no LIKE ? OR th.reason_code LIKE ? OR th.reason_text LIKE ? OR th.keyword_text LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[]=$kw;
  }
  return $where;
}

$request = $_REQUEST;
$draw = isset($request['draw']) ? (int)$request['draw'] : 0;
$start = isset($request['start']) ? max(0,(int)$request['start']) : 0;
$length = isset($request['length']) ? (int)$request['length'] : 25;
if ($length <= 0) $length = 25;

$params = array();
$where = thd_filter($params);
$base = " FROM (".thd_union_sql().") th ".$where;
$total = $db->fetch("SELECT COUNT(*) AS jml ".$base, $params);

$orderColumns = array('th.transfer_no','th.posting_date','th.transfer_type_label','th.movement_type','th.source_storage_code','th.destination_storage_code','th.item_count','th.total_qty','th.total_amount','th.trace_count','th.status','th.created_by');
$order = " ORDER BY th.posting_date DESC, th.id DESC ";
if (isset($request['order'][0]['column'])) {
  $idx = (int)$request['order'][0]['column'] - 2;
  $dir = (isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
  if (isset($orderColumns[$idx])) $order = " ORDER BY ".$orderColumns[$idx]." ".$dir." ";
}
$rows = $db->query("SELECT th.* ".$base.$order." LIMIT ".$start.",".$length, $params);

$data = array();
$i = $start + 1;
foreach ($rows as $row) {
  $source = '<strong>'.thd_h(trim($row->source_plant_code.' / '.$row->source_storage_code, ' /')).'</strong><br><small>'.thd_h(trim($row->source_storage_name.' / '.$row->source_bin_code, ' /')).'</small>';
  if ($row->source_stock_type) $source .= '<br><span class="label label-default">'.thd_h($row->source_stock_type).'</span>';
  $dest = '<strong>'.thd_h(trim($row->destination_plant_code.' / '.$row->destination_storage_code, ' /')).'</strong><br><small>'.thd_h(trim($row->destination_storage_name.' / '.$row->destination_bin_code, ' /')).'</small>';
  if ($row->destination_stock_type) $dest .= '<br><span class="label label-primary">'.thd_h($row->destination_stock_type).'</span>';
  $doc = '<strong>'.thd_h($row->transfer_no).'</strong><br><small>'.thd_h($row->reference_no ?: $row->reason_code).'</small>';
  $type = '<span class="th-badge th-type">'.thd_h($row->transfer_type).'</span><br><small>'.thd_h($row->transfer_type_label).'</small>';
  $data[] = array(
    $i++,
    '<div class="th-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-th" data-id="'.intval($row->id).'" data-type="'.thd_h($row->transfer_type).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button></div>',
    $doc,
    thd_h($row->posting_date),
    $type,
    '<strong>MvT '.thd_h($row->movement_type).'</strong>',
    $source,
    $dest,
    number_format((float)$row->item_count,0,',','.'),
    number_format((float)$row->total_qty,5,',','.'),
    number_format((float)$row->total_amount,2,',','.'),
    '<span class="badge bg-aqua">'.intval($row->trace_count).' trace</span>',
    thd_status($row->status),
    thd_h($row->created_by)
  );
}

echo json_encode(array(
  'draw'=>$draw,
  'recordsTotal'=>intval($total ? $total->jml : 0),
  'recordsFiltered'=>intval($total ? $total->jml : 0),
  'data'=>$data
));
?>
