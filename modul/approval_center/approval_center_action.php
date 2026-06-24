<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();
include __DIR__."/approval_center_helper.php";

function approval_action_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function approval_action_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function approval_action_msg($key, $fallback, $replace = array())
{
  $text = approval_action_t($key, $fallback);
  foreach ($replace as $search => $value) {
    $text = str_replace('{'.$search.'}', $value, $text);
  }
  return $text;
}

function approval_center_user()
{
  return array(
    'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
    'group_level' => isset($_SESSION['group_level']) ? $_SESSION['group_level'] : ''
  );
}

function approval_center_is_admin($profile)
{
  return in_array($profile['group_level'], array('admin', 'system_administrator'));
}

function approval_center_can_act($approval, $profile)
{
  if (approval_center_is_admin($profile)) {
    return true;
  }
  return $approval
    && ($approval->approver === $profile['username'] || $approval->approver === $profile['group_level']);
}

function approval_center_status_label($status)
{
  $class = 'default';
  if ($status === 'PENDING' || $status === 'SUBMITTED') $class = 'warning';
  if ($status === 'APPROVED') $class = 'success';
  if ($status === 'REJECTED' || $status === 'CANCELLED') $class = 'danger';
  if ($status === 'PARTIAL_PO') $class = 'info';
  if ($status === 'CONVERTED_PO') $class = 'primary';
  if ($status === 'CLOSED') $class = 'success';
  return "<span class='label label-".$class."'>".approval_action_h($status)."</span>";
}

function approval_center_get($idApproval)
{
  global $db;
  return $db->fetch(
    "SELECT a.*,
            pr.no_pr,pr.tgl_pr,pr.document_type,pr.plant,pr.storage_location,pr.department,
            pr.requestor,pr.priority,pr.status AS pr_status,pr.required_date,pr.note AS pr_note,
            pr.created_by,pr.created_at,pr.updated_by,pr.updated_at,
            ep.plant_name,es.storage_name
     FROM purchase_requisition_approval a
     JOIN purchase_requisition pr ON pr.id_pr=a.id_pr
     LEFT JOIN erp_plant ep ON ep.plant_code=pr.plant
     LEFT JOIN erp_storage_location es ON es.storage_code=pr.storage_location
     WHERE a.id_approval=?
     LIMIT 1",
    array('id_approval' => $idApproval)
  );
}

function approval_center_render_detail($approval)
{
  global $db;
  $items = $db->query("SELECT * FROM purchase_requisition_detail WHERE id_pr=? ORDER BY line_no", array('id_pr' => $approval->id_pr));
  $history = $db->query("SELECT * FROM purchase_requisition_history WHERE id_pr=? ORDER BY changed_at DESC,id DESC", array('id_pr' => $approval->id_pr));
  $approvals = $db->query("SELECT * FROM purchase_requisition_approval WHERE id_pr=? ORDER BY approval_level,id_approval", array('id_pr' => $approval->id_pr));
  ?>
  <div class="approval-detail">
    <div class="row">
      <div class="col-md-8">
        <h3 class="approval-title">
          <?=approval_action_h($approval->no_pr);?>
          <small><?=approval_action_h($approval->document_type);?> <?=approval_action_h(approval_action_t('purchase_requisition_title', 'Purchase Requisition'));?></small>
        </h3>
        <p class="text-muted"><?=approval_action_h($approval->pr_note);?></p>
      </div>
      <div class="col-md-4 text-right">
        <?=approval_center_status_label($approval->status);?>
        <?=approval_center_status_label($approval->pr_status);?>
      </div>
    </div>

    <div class="row approval-summary">
      <div class="col-sm-3">
        <span><?=approval_action_h(approval_action_t('approval_center_requestor', 'Requestor'));?></span>
        <strong><?=approval_action_h($approval->requestor);?></strong>
        <small><?=approval_action_h($approval->department);?></small>
      </div>
      <div class="col-sm-3">
        <span><?=approval_action_h(approval_action_t('form_plant', 'Plant'));?></span>
        <strong><?=approval_action_h(trim($approval->plant.' - '.$approval->plant_name));?></strong>
        <small><?=approval_action_h(trim($approval->storage_location.' - '.$approval->storage_name));?></small>
      </div>
      <div class="col-sm-2">
        <span><?=approval_action_h(approval_action_t('approval_center_priority', 'Priority'));?></span>
        <strong><?=approval_action_h($approval->priority);?></strong>
        <small><?=approval_action_h(approval_action_t('approval_center_required', 'Required'));?> <?=approval_action_h($approval->required_date);?></small>
      </div>
      <div class="col-sm-2">
        <span><?=approval_action_h(approval_action_t('approval_center_release_level', 'Release Level'));?></span>
        <strong><?=approval_action_h(approval_action_t('approval_center_release_level', 'Level'));?> <?=intval($approval->approval_level);?></strong>
        <small><?=approval_action_h($approval->approver);?></small>
      </div>
      <div class="col-sm-2">
        <span><?=approval_action_h(approval_action_t('approval_center_pr_date', 'PR Date'));?></span>
        <strong><?=approval_action_h($approval->tgl_pr);?></strong>
        <small><?=approval_action_h(approval_action_t('approval_center_created', 'Created'));?> <?=approval_action_h($approval->created_at);?></small>
      </div>
    </div>

    <h4><?=approval_action_h(approval_action_t('approval_center_material_items', 'Material Items'));?></h4>
    <div class="table-responsive approval-items">
      <table class="table table-bordered table-condensed">
        <thead>
          <tr class="bg-gray">
            <th><?=approval_action_h(approval_action_t('purchase_requisition_item', 'Item'));?></th>
            <th><?=approval_action_h(approval_action_t('purchase_requisition_material', 'Material'));?></th>
            <th class="text-right">Qty</th>
            <th class="text-right"><?=approval_action_h(approval_action_t('approval_center_open_qty', 'Open'));?></th>
            <th>UOM</th>
            <th><?=approval_action_h(approval_action_t('approval_center_required_date', 'Req. Date'));?></th>
            <th><?=approval_action_h(approval_action_t('purchase_requisition_account_assignment', 'Acct'));?></th>
            <th><?=approval_action_h(approval_action_t('purchase_requisition_cost_center', 'Cost Center'));?></th>
            <th class="text-right"><?=approval_action_h(approval_action_t('purchase_order_unit_price', 'Price'));?></th>
            <th class="text-right"><?=approval_action_h(approval_action_t('vendor_evaluation_po_value', 'Value'));?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item) { ?>
          <tr>
            <td><?=intval($item->line_no);?></td>
            <td><strong><?=approval_action_h($item->material_code);?></strong><br><span class="text-muted"><?=approval_action_h($item->material_name);?></span></td>
            <td class="text-right"><?=number_format((float) $item->qty, 5, ',', '.');?></td>
            <td class="text-right"><?=number_format((float) $item->qty_open, 5, ',', '.');?></td>
            <td><?=approval_action_h($item->uom);?></td>
            <td><?=approval_action_h($item->required_date);?></td>
            <td><?=approval_action_h($item->account_assignment);?></td>
            <td><?=approval_action_h($item->cost_center);?></td>
            <td class="text-right"><?=approval_action_h($item->currency);?> <?=number_format((float) $item->valuation_price, 2, ',', '.');?></td>
            <td class="text-right"><?=approval_action_h($item->currency);?> <?=number_format(((float) $item->qty * (float) $item->valuation_price), 2, ',', '.');?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <div class="row">
      <div class="col-md-6">
        <h4><?=approval_action_h(approval_action_t('approval_center_release_strategy', 'Release Strategy'));?></h4>
        <ul class="approval-timeline">
          <?php foreach ($approvals as $row) { ?>
            <li class="<?=strtolower($row->status);?>">
              <strong><?=approval_action_h(approval_action_t('approval_center_release_level', 'Level'));?> <?=intval($row->approval_level);?> - <?=approval_action_h($row->approver);?></strong>
              <span><?=approval_center_status_label($row->status);?> <?=approval_action_h($row->approval_date);?></span>
              <p><?=approval_action_h($row->note);?></p>
            </li>
          <?php } ?>
        </ul>
      </div>
      <div class="col-md-6">
        <h4><?=approval_action_h(approval_action_t('approval_center_document_history', 'Document History'));?></h4>
        <ul class="approval-timeline">
          <?php foreach ($history as $row) { ?>
            <li>
              <strong><?=approval_action_h($row->status_lama.' -> '.$row->status_baru);?></strong>
              <span><?=approval_action_h($row->changed_by.' '.approval_action_t('approval_center_at', 'at').' '.$row->changed_at);?></span>
              <p><?=approval_action_h($row->remarks);?></p>
            </li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </div>
  <?php
}

$profile = approval_center_user();
approval_center_sync_pr_history_approvals();

switch ($_GET['act']) {
  case 'summary':
    $params = array();
    $wh = "";
    if (!approval_center_is_admin($profile)) {
      $wh = "WHERE a.approver=? OR a.approver=?";
      $params[] = $profile['username'];
      $params[] = $profile['group_level'];
    }
    $summary = $db->fetch(
      "SELECT
          SUM(CASE WHEN a.status='PENDING' THEN 1 ELSE 0 END) AS pending_count,
          SUM(CASE WHEN a.status='APPROVED' AND DATE(a.approval_date)=CURDATE() THEN 1 ELSE 0 END) AS approved_today,
          SUM(CASE WHEN a.status='REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
          COUNT(*) AS total_count
       FROM purchase_requisition_approval a $wh",
      $params
    );
    echo json_encode(array(
      'status' => 'good',
      'pending_count' => (int) $summary->pending_count,
      'approved_today' => (int) $summary->approved_today,
      'rejected_count' => (int) $summary->rejected_count,
      'total_count' => (int) $summary->total_count
    ));
    break;

  case 'detail':
    $idApproval = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $approval = approval_center_get($idApproval);
    if (!$approval || !approval_center_can_act($approval, $profile)) {
      echo "<div class='alert alert-warning'>".approval_action_h(approval_action_t('approval_center_not_found_worklist', 'Approval not found or not in active user worklist.'))."</div>";
      break;
    }
    approval_center_render_detail($approval);
    break;

  case 'approve':
  case 'reject':
    $idApproval = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $targetStatus = $_GET['act'] === 'approve' ? 'APPROVED' : 'REJECTED';
    $username = $profile['username'] !== '' ? $profile['username'] : 'system';

    if ($idApproval <= 0) action_response(approval_action_t('approval_center_invalid', 'Approval is invalid.'));
    if ($targetStatus === 'REJECTED' && $note === '') action_response(approval_action_t('approval_center_reject_note_required', 'Rejection note is required.'));

    $db->query('START TRANSACTION');
    $approval = $db->fetch(
      "SELECT a.*,pr.no_pr,pr.status AS pr_status
       FROM purchase_requisition_approval a
       JOIN purchase_requisition pr ON pr.id_pr=a.id_pr
       WHERE a.id_approval=?
       LIMIT 1
       FOR UPDATE",
      array('id_approval' => $idApproval)
    );

    if (!$approval) {
      $db->query('ROLLBACK');
      action_response(approval_action_t('approval_center_not_found', 'Approval not found.'));
    }
    if (!approval_center_can_act($approval, $profile)) {
      $db->query('ROLLBACK');
      action_response(approval_action_t('approval_center_not_user_worklist', 'Approval is not in active user worklist.'));
    }
    if ($approval->status !== 'PENDING') {
      $db->query('ROLLBACK');
      action_response(approval_action_msg('approval_center_already_processed', 'Approval already processed with status {status}.', array('status' => $approval->status)));
    }
    if (!in_array($approval->pr_status, array('SUBMITTED', 'DRAFT'))) {
      $db->query('ROLLBACK');
      action_response(approval_action_msg('approval_center_pr_status_cannot_process', 'PR status {status} cannot be processed for approval.', array('status' => $approval->pr_status)));
    }

    $approvalNote = $note !== '' ? $note : approval_action_t('approval_center_approved_note', 'Purchase Requisition approved from Approval Center.');
    if (!$db->update('purchase_requisition_approval', array(
      'status' => $targetStatus,
      'approval_date' => date('Y-m-d H:i:s'),
      'note' => $approvalNote
    ), 'id_approval', $idApproval)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $newPrStatus = $approval->pr_status;
    if ($targetStatus === 'REJECTED') {
      $newPrStatus = 'REJECTED';
      $db->update('purchase_requisition_detail', array('item_status' => 'REJECTED'), 'id_pr', $approval->id_pr);
    } else {
      $pending = $db->fetch(
        "SELECT id_approval FROM purchase_requisition_approval WHERE id_pr=? AND status='PENDING' LIMIT 1",
        array('id_pr' => $approval->id_pr)
      );
      if (!$pending) {
        $newPrStatus = 'APPROVED';
      }
    }

    if ($newPrStatus !== $approval->pr_status) {
      if (!$db->update('purchase_requisition', array('status' => $newPrStatus, 'updated_by' => $username), 'id_pr', $approval->id_pr)) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    $historyText = $targetStatus === 'APPROVED'
      ? 'Purchase Requisition approved by '.$username.'. '.$approvalNote
      : 'Purchase Requisition rejected by '.$username.'. '.$approvalNote;
    if (!$db->insert('purchase_requisition_history', array(
      'id_pr' => $approval->id_pr,
      'status_lama' => $approval->pr_status,
      'status_baru' => $newPrStatus,
      'remarks' => $historyText,
      'changed_by' => $username
    ))) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $db->query('COMMIT');
    if (function_exists('simpan_log')) {
      $verb = $targetStatus === 'APPROVED' ? 'approve' : 'reject';
      simpan_log('User '.$username.' '.$verb.' Purchase Requisition '.$approval->no_pr.' melalui Approval Center pada '.date('Y-m-d H:i:s'), $username);
    }
    action_response('', array('id_approval' => $idApproval, 'id_pr' => $approval->id_pr, 'pr_status' => $newPrStatus));
    break;

  default:
    action_response(approval_action_t('approval_center_unknown_action', 'Unknown action.'));
    break;
}
?>
