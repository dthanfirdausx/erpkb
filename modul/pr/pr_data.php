<?php
include "../../inc/config.php";

function pr_data_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function pr_data_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$columns = array(
  'pr.no_pr',
  'pr.tgl_pr',
  'pr.document_type',
  'pr.plant',
  'pr.requestor',
  'pr.department',
  'pr.priority',
  'pr.required_date',
  'pr.status',
  'pr.no_pr'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("pr.id_pr");
$datatable->set_order_type("desc");

$wh = "";
$params = array();

if (isset($_POST['tgl_awal']) && $_POST['tgl_awal']!='' && (!isset($_POST['tgl_akhir']) || $_POST['tgl_akhir']=='')) {
  $wh .= " AND pr.tgl_pr BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = date('Y-m-d');
} else if (isset($_POST['tgl_awal']) && $_POST['tgl_awal']!='' && isset($_POST['tgl_akhir']) && $_POST['tgl_akhir']!='') {
  $wh .= " AND pr.tgl_pr BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}

if (isset($_POST['status']) && $_POST['status']!='') {
  $wh .= " AND pr.status = ? ";
  $params[] = $_POST['status'];
}

if (isset($_POST['plant']) && $_POST['plant']!='') {
  $wh .= " AND pr.plant = ? ";
  $params[] = $_POST['plant'];
}

if (isset($_POST['reference']) && trim($_POST['reference'])!='') {
  $keyword = '%'.trim($_POST['reference']).'%';
  $wh .= " AND (
    pr.no_pr LIKE ?
    OR pr.requestor LIKE ?
    OR pr.department LIKE ?
    OR EXISTS (
      SELECT 1 FROM purchase_requisition_detail d
      WHERE d.id_pr=pr.id_pr
        AND (d.material_code LIKE ? OR d.material_name LIKE ? OR d.tracking_no LIKE ?)
    )
  ) ";
  for ($i=0; $i<6; $i++) $params[] = $keyword;
}

$query = $datatable->get_custom(
  "SELECT pr.*,
          ep.plant_name,
          COALESCE(ds.open_qty,0) AS open_qty,
          COALESCE(ds.item_count,0) AS item_count
   FROM purchase_requisition pr
   LEFT JOIN (
     SELECT id_pr,COUNT(*) AS item_count,SUM(qty_open) AS open_qty
     FROM purchase_requisition_detail
     GROUP BY id_pr
   ) ds ON ds.id_pr=pr.id_pr
   LEFT JOIN erp_plant ep ON ep.plant_code=pr.plant
   WHERE 1=1 $wh",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $statusClass = 'default';
  if ($value->status == 'SUBMITTED') $statusClass = 'warning';
  if ($value->status == 'APPROVED') $statusClass = 'success';
  if ($value->status == 'REJECTED') $statusClass = 'danger';
  if ($value->status == 'PARTIAL_PO') $statusClass = 'info';
  if ($value->status == 'CONVERTED_PO') $statusClass = 'primary';
  if ($value->status == 'CLOSED') $statusClass = 'success';
  if ($value->status == 'CANCELLED') $statusClass = 'danger';

  $action = '<div class="pr-action-buttons">
    <button type="button" class="btn btn-success btn-xs" onclick="detail_pr('.intval($value->id_pr).')" data-toggle="tooltip" title="'.pr_data_h(pr_data_t('common_detail','Detail')).'">
      <i class="fa fa-eye"></i>
    </button>';
  if (!in_array($value->status, array('CONVERTED_PO','CLOSED','CANCELLED'))) {
    $action .= ' <a href="'.base_url().'index.php/pr/edit/'.intval($value->id_pr).'" class="btn btn-primary btn-xs" data-toggle="tooltip" title="'.pr_data_h(pr_data_t('purchase_requisition_edit_pr','Edit PR')).'">
      <i class="fa fa-pencil"></i>
    </a>
    <button type="button" class="btn btn-danger btn-xs btn-cancel-pr" data-id="'.intval($value->id_pr).'" data-toggle="tooltip" title="'.pr_data_h(pr_data_t('purchase_requisition_cancel_pr','Cancel PR')).'">
      <i class="fa fa-ban"></i>
    </button>';
  }
  $action .= '</div>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = pr_data_h($value->no_pr);
  $result[] = pr_data_h($value->tgl_pr);
  $result[] = pr_data_h($value->document_type);
  $result[] = pr_data_h(trim($value->plant.' - '.$value->plant_name));
  $result[] = pr_data_h($value->requestor);
  $result[] = pr_data_h($value->department);
  $result[] = pr_data_h($value->priority);
  $result[] = pr_data_h($value->required_date);
  $result[] = number_format((float) $value->item_count, 0, ',', '.');
  $result[] = number_format((float) $value->open_qty, 5, ',', '.');
  $result[] = '<span class="label label-'.$statusClass.'">'.pr_data_h($value->status).'</span>';
  $result[] = $value->id_pr;
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
