<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
function lpw_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function lpw_num($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function lpw_post($key, $default = '') { return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default; }

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$tanggal = lpw_post('tanggal', date('Y-m-d'));
$keyword = lpw_post('keyword');
$search = isset($_POST['search']['value']) ? trim((string)$_POST['search']['value']) : '';
if ($keyword === '' && $search !== '') $keyword = $search;

$where = "";
$filter = array();
if ($keyword !== '') {
  $where .= " AND (ipd.material_code LIKE ? OR ipd.material_name LIKE ? OR ip.production_no LIKE ? OR ipt.no_aju LIKE ? OR ipt.no_bpb LIKE ? OR ipt.no_dokpab LIKE ?) ";
  $kw = '%'.$keyword.'%';
  for ($i=0; $i<6; $i++) $filter[] = $kw;
}

$baseSql = "
  SELECT w.material_code,w.material_name,w.uom,
         SUM(w.wip_qty) AS jumlah,
         COUNT(DISTINCT w.production_no) AS po_count,
         COUNT(DISTINCT w.process_label) AS process_count,
         GROUP_CONCAT(DISTINCT w.process_label ORDER BY w.process_label SEPARATOR ', ') AS process_list
  FROM (
    SELECT ipt.id issue_trace_id,ip.production_no,COALESCE(po.no_production_order,ip.production_no) production_order_no,
           ipd.material_code,ipd.material_name,ipd.uom,ipt.no_bpb,ipt.no_aju,ipt.no_dokpab,ipt.jenis_dokpab,
           CASE
             WHEN COALESCE(conf.confirmed_qty,0) > 0 AND COALESCE(gr.consumed_qty,0) = 0 THEN 'Production Confirmation'
             WHEN COALESCE(gr.consumed_qty,0) > 0 THEN 'Partial GR Production'
             ELSE 'Issued to Production'
           END AS process_label,
           GREATEST(COALESCE(ipt.qty,0)-COALESCE(gr.consumed_qty,0)-COALESCE(scr.scrap_qty,0),0) AS wip_qty
    FROM erp_issue_production_trace ipt
    JOIN erp_issue_production ip ON ip.id=ipt.issue_id AND ip.status='POSTED' AND ip.posting_date<=?
    JOIN erp_issue_production_detail ipd ON ipd.id=ipt.issue_detail_id
    LEFT JOIN production_order po ON po.id_production_order=ip.production_id
    LEFT JOIN (
      SELECT gt.source_issue_trace_id,SUM(gt.qty) consumed_qty
      FROM erp_gr_production_trace gt
      JOIN erp_gr_production gr ON gr.id=gt.gr_id AND gr.status='POSTED' AND gr.posting_date<=?
      GROUP BY gt.source_issue_trace_id
    ) gr ON gr.source_issue_trace_id=ipt.id
    LEFT JOIN (
      SELECT pst.source_issue_trace_id,SUM(pst.qty) scrap_qty
      FROM erp_production_scrap_trace pst
      JOIN production_order_confirmation pc ON pc.id_confirmation=pst.confirmation_id AND pc.status='POSTED' AND pc.posting_date<=?
      GROUP BY pst.source_issue_trace_id
    ) scr ON scr.source_issue_trace_id=ipt.id
    LEFT JOIN (
      SELECT pst.source_issue_trace_id,SUM(pc.yield_qty) confirmed_qty
      FROM erp_production_scrap_trace pst
      JOIN production_order_confirmation pc ON pc.id_confirmation=pst.confirmation_id AND pc.status='POSTED' AND pc.posting_date<=?
      GROUP BY pst.source_issue_trace_id
    ) conf ON conf.source_issue_trace_id=ipt.id
    WHERE 1=1 $where
  ) w
  WHERE w.wip_qty > 0
  GROUP BY w.material_code,w.material_name,w.uom
";
$params = array_merge(array($tanggal,$tanggal,$tanggal,$tanggal), $filter);
$countRow = $db->fetch("SELECT COUNT(*) total FROM ($baseSql) x", $params);

$orderMap = array(1=>'material_code',2=>'material_name',3=>'uom',4=>'jumlah',5=>'process_list');
$orderCol = 'material_code'; $orderDir = 'ASC';
if (isset($_POST['order'][0]['column'])) { $idx=(int)$_POST['order'][0]['column']; if(isset($orderMap[$idx])) $orderCol=$orderMap[$idx]; }
if (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'desc') $orderDir='DESC';
$rows = $db->query("SELECT * FROM ($baseSql) y ORDER BY $orderCol $orderDir LIMIT $start,$length", $params);

$data = array(); $no = $start + 1;
foreach ($rows as $row) {
  $ket = array();
  if ((int)$row->po_count > 0) $ket[] = (int)$row->po_count.' production order';
  if ((int)$row->process_count > 0) $ket[] = (int)$row->process_count.' posisi proses';
  if ($row->process_list) $ket[] = $row->process_list;
  $data[] = array(
    $no++,
    '<strong>'.lpw_h($row->material_code).'</strong>',
    lpw_h($row->material_name),
    lpw_h($row->uom),
    '<a href="javascript:void(0)" class="lpw-detail-link" data-material="'.lpw_h($row->material_code).'">'.lpw_num($row->jumlah).'</a>',
    lpw_h(implode(' | ', $ket))
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$countRow?(int)$countRow->total:0,'recordsFiltered'=>$countRow?(int)$countRow->total:0,'data'=>$data));
?>
