<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();
include __DIR__."/approval_center_helper.php";

function approval_data_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function approval_data_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

approval_center_sync_pr_history_approvals();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$groupLevel = isset($_SESSION['group_level']) ? $_SESSION['group_level'] : '';
$isAdmin = in_array($groupLevel, array('admin', 'system_administrator'));

$columns = array(
  'a.id_approval',
  'pr.no_pr',
  'pr.tgl_pr',
  'pr.document_type',
  'pr.requestor',
  'pr.department',
  'pr.priority',
  'pr.required_date',
  'a.approver',
  'a.status',
  'pr.status',
  'pr.plant',
  'lh.changed_at',
  'lh.changed_by'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("FIELD(a.status,'PENDING','REJECTED','APPROVED'), COALESCE(lh.changed_at,a.created_at)");
$datatable->set_order_type("desc");

$wh = "";
$params = array();

if (!$isAdmin) {
  $wh .= " AND (a.approver=? OR a.approver=?) ";
  $params[] = $username;
  $params[] = $groupLevel;
}

if (isset($_POST['status']) && $_POST['status'] !== '') {
  $wh .= " AND a.status=? ";
  $params[] = $_POST['status'];
}

if (isset($_POST['tgl_awal']) && $_POST['tgl_awal'] !== '' && (!isset($_POST['tgl_akhir']) || $_POST['tgl_akhir'] === '')) {
  $wh .= " AND pr.tgl_pr BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = date('Y-m-d');
} else if (isset($_POST['tgl_awal'], $_POST['tgl_akhir']) && $_POST['tgl_awal'] !== '' && $_POST['tgl_akhir'] !== '') {
  $wh .= " AND pr.tgl_pr BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}

if (isset($_POST['approver']) && trim($_POST['approver']) !== '') {
  $wh .= " AND a.approver=? ";
  $params[] = trim($_POST['approver']);
}

if (isset($_POST['reference']) && trim($_POST['reference']) !== '') {
  $keyword = '%'.trim($_POST['reference']).'%';
  $wh .= " AND (
    pr.no_pr LIKE ?
    OR pr.requestor LIKE ?
    OR pr.department LIKE ?
    OR pr.note LIKE ?
    OR EXISTS (
      SELECT 1 FROM purchase_requisition_detail d
      WHERE d.id_pr=pr.id_pr
        AND (d.material_code LIKE ? OR d.material_name LIKE ? OR d.tracking_no LIKE ?)
    )
  ) ";
  for ($i = 0; $i < 7; $i++) $params[] = $keyword;
}

$query = $datatable->get_custom(
  "SELECT a.*,
          pr.no_pr,pr.tgl_pr,pr.document_type,pr.plant,pr.storage_location,pr.department,
          pr.requestor,pr.priority,pr.status AS pr_status,pr.required_date,pr.note AS pr_note,
          lh.status_lama AS history_status_lama,lh.status_baru AS history_status_baru,
          lh.remarks AS history_remarks,lh.changed_by AS history_changed_by,lh.changed_at AS history_changed_at,
          ep.plant_name,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.open_qty,0) AS open_qty,
          COALESCE(ds.total_value,0) AS total_value
   FROM purchase_requisition_approval a
   JOIN purchase_requisition pr ON pr.id_pr=a.id_pr
   LEFT JOIN (
     SELECT h1.*
     FROM purchase_requisition_history h1
     JOIN (
       SELECT id_pr,MAX(id) AS max_id
       FROM purchase_requisition_history
       GROUP BY id_pr
     ) hx ON hx.id_pr=h1.id_pr AND hx.max_id=h1.id
   ) lh ON lh.id_pr=pr.id_pr
   LEFT JOIN erp_plant ep ON ep.plant_code=pr.plant
   LEFT JOIN (
     SELECT id_pr,COUNT(*) AS item_count,SUM(qty_open) AS open_qty,SUM(qty * valuation_price) AS total_value
     FROM purchase_requisition_detail
     GROUP BY id_pr
   ) ds ON ds.id_pr=pr.id_pr
   WHERE 1=1 $wh",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $approvalClass = 'default';
  if ($value->status === 'PENDING') $approvalClass = 'warning';
  if ($value->status === 'APPROVED') $approvalClass = 'success';
  if ($value->status === 'REJECTED') $approvalClass = 'danger';

  $priorityClass = 'default';
  if ($value->priority === 'HIGH') $priorityClass = 'warning';
  if ($value->priority === 'URGENT') $priorityClass = 'danger';
  if ($value->priority === 'LOW') $priorityClass = 'info';

  $action = '<div class="approval-actions">
    <button type="button" class="btn btn-info btn-xs btn-detail-approval" data-id="'.intval($value->id_approval).'" title="'.approval_data_h(approval_data_t('common_detail', 'Detail')).'">
      <i class="fa fa-eye"></i>
    </button>';

  if ($value->status === 'PENDING') {
    $action .= ' <button type="button" class="btn btn-success btn-xs btn-approve" data-id="'.intval($value->id_approval).'" data-no="'.approval_data_h($value->no_pr).'" title="'.approval_data_h(approval_data_t('approval_center_approve', 'Approve')).'">
      <i class="fa fa-check"></i>
    </button>
    <button type="button" class="btn btn-danger btn-xs btn-reject" data-id="'.intval($value->id_approval).'" data-no="'.approval_data_h($value->no_pr).'" title="'.approval_data_h(approval_data_t('approval_center_reject', 'Reject')).'">
      <i class="fa fa-times"></i>
    </button>';
  }
  $action .= '</div>';

  $historyLine = $value->history_changed_at
    ? '<br><small class="text-muted">'.approval_data_h(approval_data_t('approval_center_history', 'History')).': '.approval_data_h($value->history_status_baru.' '.approval_data_t('approval_center_by', 'by').' '.$value->history_changed_by.' @ '.$value->history_changed_at).'</small>'
    : '';
  $doc = '<strong>'.approval_data_h($value->no_pr).'</strong><br><small class="text-muted">'.approval_data_h($value->document_type).' - '.approval_data_h(approval_data_t('approval_center_release_level', 'Level')).' '.intval($value->approval_level).'</small>'.$historyLine;
  $requestor = approval_data_h($value->requestor).'<br><small class="text-muted">'.approval_data_h($value->department).'</small>';
  $plant = approval_data_h(trim($value->plant.' - '.$value->plant_name));

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = $doc;
  $result[] = approval_data_h($value->tgl_pr);
  $result[] = $requestor;
  $result[] = '<span class="label label-'.$priorityClass.'">'.approval_data_h($value->priority).'</span>';
  $result[] = $plant;
  $result[] = number_format((float) $value->item_count, 0, ',', '.');
  $result[] = number_format((float) $value->open_qty, 5, ',', '.');
  $result[] = 'Rp '.number_format((float) $value->total_value, 2, ',', '.');
  $result[] = approval_data_h($value->approver);
  $result[] = '<span class="label label-'.$approvalClass.'">'.approval_data_h($value->status).'</span>';
  $result[] = approval_data_h($value->required_date);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
