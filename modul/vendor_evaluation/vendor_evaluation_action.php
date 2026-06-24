<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function ve_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ve_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function ve_msg($key, $fallback, $replace = array())
{
  $text = ve_t($key, $fallback);
  foreach ($replace as $search => $value) {
    $text = str_replace('{'.$search.'}', $value, $text);
  }
  return $text;
}

function ve_clamp($value, $min = 0, $max = 100)
{
  $value = (float)$value;
  if ($value < $min) return $min;
  if ($value > $max) return $max;
  return $value;
}

function ve_next_number()
{
  global $db;
  $prefix = 'VE'.date('Ym');
  $row = $db->fetch("SELECT evaluation_no FROM erp_vendor_evaluation WHERE evaluation_no LIKE ? ORDER BY evaluation_no DESC LIMIT 1", array('no' => $prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->evaluation_no, $m)) {
    $next = intval($m[1]) + 1;
  }
  return $prefix.sprintf('%05d', $next);
}

function ve_rating($score)
{
  $score = (float)$score;
  if ($score >= 90) return 'A';
  if ($score >= 75) return 'B';
  if ($score >= 60) return 'C';
  return 'D';
}

function ve_status_label($status)
{
  $class = $status === 'FINALIZED' ? 'success' : ($status === 'CANCELLED' ? 'danger' : 'warning');
  return "<span class='label label-".$class."'>".ve_h($status)."</span>";
}

function ve_rating_label($rating)
{
  $class = $rating === 'A' ? 'success' : ($rating === 'B' ? 'primary' : ($rating === 'C' ? 'warning' : 'danger'));
  return "<span class='label label-".$class."'>".ve_h($rating)."</span>";
}

function ve_recalculate_total($price, $delivery, $quality, $service, $compliance)
{
  return round(((float)$price * 0.20) + ((float)$delivery * 0.30) + ((float)$quality * 0.25) + ((float)$service * 0.15) + ((float)$compliance * 0.10), 2);
}

function ve_write_details($evaluationId, $scores)
{
  global $db;
  $db->query("DELETE FROM erp_vendor_evaluation_detail WHERE evaluation_id=?", array('id' => $evaluationId));
  foreach ($scores as $row) {
    $db->insert('erp_vendor_evaluation_detail', array(
      'evaluation_id' => $evaluationId,
      'criterion_code' => $row['code'],
      'criterion_name' => $row['name'],
      'weight_pct' => $row['weight'],
      'score' => $row['score'],
      'weighted_score' => round($row['score'] * ($row['weight'] / 100), 2),
      'source_type' => $row['source'],
      'notes' => $row['notes']
    ));
  }
}

function ve_history($evaluationId, $oldStatus, $newStatus, $remarks)
{
  global $db;
  $db->insert('erp_vendor_evaluation_history', array(
    'evaluation_id' => $evaluationId,
    'status_lama' => $oldStatus,
    'status_baru' => $newStatus,
    'remarks' => $remarks,
    'changed_by' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system'
  ));
}

function ve_calculate_snapshot($vendorCode, $periodFrom, $periodTo, $purchasingOrg, $plant)
{
  global $db;
  $poWhere = "po.seller_code=? AND po.po_date BETWEEN ? AND ?";
  $poParams = array($vendorCode, $periodFrom, $periodTo);
  if ($purchasingOrg !== '') {
    $poWhere .= " AND po.purchasing_org=?";
    $poParams[] = $purchasingOrg;
  }
  if ($plant !== '') {
    $poWhere .= " AND po.plant=?";
    $poParams[] = $plant;
  }

  $po = $db->fetch(
    "SELECT COUNT(DISTINCT po.id) po_count,
            COALESCE(SUM(d.amount),0) total_po_value,
            COALESCE(SUM(d.qty),0) ordered_qty,
            COALESCE(SUM(d.received_qty),0) received_qty
     FROM purchase_order po
     LEFT JOIN purchase_order_detail d ON d.id_po=po.id
     WHERE $poWhere",
    $poParams
  );

  $grWhere = "po.seller_code=? AND p.tgl_bpb BETWEEN ? AND ? AND COALESCE(p.status,'POSTED')<>'REVERSED'";
  $grParams = array($vendorCode, $periodFrom, $periodTo);
  if ($purchasingOrg !== '') {
    $grWhere .= " AND po.purchasing_org=?";
    $grParams[] = $purchasingOrg;
  }
  if ($plant !== '') {
    $grWhere .= " AND po.plant=?";
    $grParams[] = $plant;
  }
  $gr = $db->fetch(
    "SELECT COUNT(DISTINCT p.no_bpb) gr_count,
            COALESCE(SUM(pd.jumlah),0) gr_qty,
            SUM(CASE WHEN p.tgl_bpb<=po.delivery_date THEN 1 ELSE 0 END) on_time_count,
            COUNT(DISTINCT p.no_bpb) delivery_count,
            COALESCE(SUM(CASE WHEN p.stock_type='BLOCKED' THEN pd.jumlah ELSE 0 END),0) blocked_qty
     FROM pemasukan p
     JOIN purchase_order po ON po.purchase_order_no=p.nopo
     LEFT JOIN pemasukan_detail pd ON pd.no_bpb=p.no_bpb
     WHERE $grWhere",
    $grParams
  );

  $price = $db->fetch(
    "SELECT AVG(CASE WHEN q.price>0 THEN ((d.harga-q.price)/q.price)*100 ELSE NULL END) avg_variance
     FROM purchase_order po
     JOIN purchase_order_detail d ON d.id_po=po.id
     JOIN erp_rfq_quotation q ON q.id=d.rfq_quotation_id
     WHERE $poWhere",
    $poParams
  );

  $poCount = $po ? (int)$po->po_count : 0;
  $grCount = $gr ? (int)$gr->gr_count : 0;
  $orderedQty = $po ? (float)$po->ordered_qty : 0;
  $receivedQty = $po ? (float)$po->received_qty : 0;
  $grQty = $gr ? (float)$gr->gr_qty : 0;
  $blockedQty = $gr ? (float)$gr->blocked_qty : 0;
  $onTimePct = ($gr && (int)$gr->delivery_count > 0) ? ((float)$gr->on_time_count / (float)$gr->delivery_count) * 100 : 0;
  $qtyAccuracyPct = $orderedQty > 0 ? ve_clamp(($receivedQty / $orderedQty) * 100) : 0;
  $priceVariance = $price && $price->avg_variance !== null ? (float)$price->avg_variance : 0;
  $defectRatePct = $grQty > 0 ? ve_clamp(($blockedQty / $grQty) * 100) : 0;

  $priceScore = $price && $price->avg_variance !== null ? ve_clamp(100 - abs($priceVariance * 2)) : ($poCount > 0 ? 80 : 0);
  $deliveryScore = $grCount > 0 ? ve_clamp(($onTimePct * 0.70) + ($qtyAccuracyPct * 0.30)) : 0;
  $qualityScore = $grQty > 0 ? ve_clamp(100 - ($defectRatePct * 2)) : ($poCount > 0 ? 80 : 0);

  return array(
    'po_count' => $poCount,
    'gr_count' => $grCount,
    'total_po_value' => $po ? (float)$po->total_po_value : 0,
    'ordered_qty' => $orderedQty,
    'received_qty' => $receivedQty,
    'on_time_delivery_pct' => round($onTimePct, 2),
    'qty_accuracy_pct' => round($qtyAccuracyPct, 2),
    'price_variance_pct' => round($priceVariance, 2),
    'defect_rate_pct' => round($defectRatePct, 2),
    'price_score' => round($priceScore, 2),
    'delivery_score' => round($deliveryScore, 2),
    'quality_score' => round($qualityScore, 2)
  );
}

function ve_render_detail($evaluationId)
{
  global $db;
  $e = $db->fetch("SELECT * FROM erp_vendor_evaluation WHERE id=? LIMIT 1", array('id' => $evaluationId));
  if (!$e) {
    echo "<div class='alert alert-warning'>".ve_h(ve_t('vendor_evaluation_not_found', 'Vendor Evaluation not found.'))."</div>";
    return;
  }
  $details = $db->query("SELECT * FROM erp_vendor_evaluation_detail WHERE evaluation_id=? ORDER BY FIELD(criterion_code,'PRICE','DELIVERY','QUALITY','SERVICE','COMPLIANCE')", array('id' => $evaluationId));
  $history = $db->query("SELECT * FROM erp_vendor_evaluation_history WHERE evaluation_id=? ORDER BY changed_at DESC,id DESC", array('id' => $evaluationId));
  $pos = $db->query(
    "SELECT po.purchase_order_no,po.po_date,po.delivery_date,po.status,po.approval_status,COALESCE(SUM(d.amount),0) total_value,COALESCE(SUM(d.qty),0) qty,COALESCE(SUM(d.received_qty),0) received_qty
     FROM purchase_order po
     LEFT JOIN purchase_order_detail d ON d.id_po=po.id
     WHERE po.seller_code=? AND po.po_date BETWEEN ? AND ?
     GROUP BY po.id
     ORDER BY po.po_date DESC
     LIMIT 10",
    array('vendor' => $e->vendor_code, 'from' => $e->period_from, 'to' => $e->period_to)
  );
  ?>
  <div class="row">
    <div class="col-md-8">
      <h3 style="margin-top:0;font-weight:700"><?=ve_h($e->evaluation_no);?> <small><?=ve_h($e->vendor_code.' - '.$e->vendor_name);?></small></h3>
      <p class="text-muted"><?=ve_h(ve_t('vendor_evaluation_period', 'Period'));?> <?=ve_h($e->period_from);?> s/d <?=ve_h($e->period_to);?> | <?=ve_h(ve_t('vendor_evaluation_evaluator', 'Evaluator'));?> <?=ve_h($e->evaluator);?></p>
    </div>
    <div class="col-md-4 text-right">
      <div class="ve-score-ring"><?=number_format((float)$e->total_score,0);?></div><br>
      <?=ve_rating_label($e->rating);?> <?=ve_status_label($e->status);?>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><strong><?=ve_h(ve_t('vendor_evaluation_po_count', 'PO Count'));?></strong><br><?=number_format((float)$e->po_count,0,',','.');?></div>
    <div class="col-sm-3"><strong><?=ve_h(ve_t('vendor_evaluation_gr_count', 'GR Count'));?></strong><br><?=number_format((float)$e->gr_count,0,',','.');?></div>
    <div class="col-sm-3"><strong><?=ve_h(ve_t('vendor_evaluation_po_value', 'PO Value'));?></strong><br><?=number_format((float)$e->total_po_value,2,',','.');?></div>
    <div class="col-sm-3"><strong><?=ve_h(ve_t('vendor_evaluation_on_time_delivery', 'On Time Delivery'));?></strong><br><?=number_format((float)$e->on_time_delivery_pct,2,',','.');?>%</div>
  </div>
  <hr>
  <h4><?=ve_h(ve_t('vendor_evaluation_scorecard', 'Scorecard'));?></h4>
  <table class="table table-bordered table-condensed ve-score-table">
    <thead><tr><th><?=ve_h(ve_t('vendor_evaluation_criterion', 'Criterion'));?></th><th><?=ve_h(ve_t('vendor_evaluation_source', 'Source'));?></th><th class="text-right"><?=ve_h(ve_t('vendor_evaluation_weight', 'Weight'));?></th><th class="text-right"><?=ve_h(ve_t('vendor_evaluation_score', 'Score'));?></th><th class="text-right"><?=ve_h(ve_t('vendor_evaluation_weighted', 'Weighted'));?></th><th><?=ve_h(ve_t('vendor_evaluation_notes', 'Notes'));?></th></tr></thead>
    <tbody>
    <?php foreach ($details as $d) { ?>
      <tr><td><strong><?=ve_h($d->criterion_code);?></strong><br><small><?=ve_h($d->criterion_name);?></small></td><td><?=ve_h($d->source_type);?></td><td class="text-right"><?=number_format((float)$d->weight_pct,2,',','.');?>%</td><td class="text-right"><?=number_format((float)$d->score,2,',','.');?></td><td class="text-right"><?=number_format((float)$d->weighted_score,2,',','.');?></td><td><?=ve_h($d->notes);?></td></tr>
    <?php } ?>
    </tbody>
  </table>
  <div class="row">
    <div class="col-md-6">
      <h4><?=ve_h(ve_t('vendor_evaluation_automatic_kpi', 'Automatic KPI'));?></h4>
      <table class="table table-bordered table-condensed">
        <tr><th><?=ve_h(ve_t('vendor_evaluation_ordered_qty', 'Ordered Qty'));?></th><td class="text-right"><?=number_format((float)$e->ordered_qty,5,',','.');?></td></tr>
        <tr><th><?=ve_h(ve_t('vendor_evaluation_received_qty', 'Received Qty'));?></th><td class="text-right"><?=number_format((float)$e->received_qty,5,',','.');?></td></tr>
        <tr><th><?=ve_h(ve_t('vendor_evaluation_qty_accuracy', 'Qty Accuracy'));?></th><td class="text-right"><?=number_format((float)$e->qty_accuracy_pct,2,',','.');?>%</td></tr>
        <tr><th><?=ve_h(ve_t('vendor_evaluation_price_variance', 'Price Variance'));?></th><td class="text-right"><?=number_format((float)$e->price_variance_pct,2,',','.');?>%</td></tr>
        <tr><th><?=ve_h(ve_t('vendor_evaluation_defect_rate', 'Defect / Blocked Rate'));?></th><td class="text-right"><?=number_format((float)$e->defect_rate_pct,2,',','.');?>%</td></tr>
      </table>
    </div>
    <div class="col-md-6">
      <h4><?=ve_h(ve_t('vendor_evaluation_recent_po', 'Recent PO in Period'));?></h4>
      <table class="table table-bordered table-condensed">
        <thead><tr><th>PO</th><th><?=ve_h(ve_t('table_date', 'Date'));?></th><th class="text-right">Qty</th><th class="text-right"><?=ve_h(ve_t('vendor_evaluation_received_qty', 'Received'));?></th><th class="text-right"><?=ve_h(ve_t('vendor_evaluation_po_value', 'Value'));?></th></tr></thead>
        <tbody>
        <?php foreach ($pos as $po) { ?>
          <tr><td><?=ve_h($po->purchase_order_no);?></td><td><?=ve_h($po->po_date);?></td><td class="text-right"><?=number_format((float)$po->qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$po->received_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$po->total_value,2,',','.');?></td></tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <h4><?=ve_h(ve_t('vendor_evaluation_notes', 'Remarks'));?></h4>
  <p><?=nl2br(ve_h($e->remarks));?></p>
  <h4><?=ve_h(ve_t('approval_center_history', 'History'));?></h4>
  <ul class="list-unstyled">
    <?php foreach ($history as $h) { ?>
      <li><strong><?=ve_h(($h->status_lama ?: '-').' -> '.$h->status_baru);?></strong> <span class="text-muted"><?=ve_h($h->changed_by.' @ '.$h->changed_at);?></span><br><?=ve_h($h->remarks);?></li>
    <?php } ?>
  </ul>
  <?php
}

switch ($_GET['act']) {
  case 'generate':
    $required = array(
      'vendor_code' => ve_t('vendor_evaluation_vendor', 'Vendor'),
      'period_from' => ve_t('vendor_evaluation_period_from', 'Period From'),
      'period_to' => ve_t('vendor_evaluation_period_to', 'Period To')
    );
    foreach ($required as $field => $label) {
      if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') action_response(ve_msg('vendor_evaluation_required', '{field} is required.', array('field' => $label)));
    }
    if ($_POST['period_from'] > $_POST['period_to']) action_response(ve_t('vendor_evaluation_period_invalid', 'Period From cannot be greater than Period To.'));
    $vendor = $db->fetch("SELECT * FROM pemasok WHERE kode_pemasok=? LIMIT 1", array('kode' => $_POST['vendor_code']));
    if (!$vendor) action_response(ve_t('vendor_evaluation_vendor_not_found', 'Vendor not found.'));

    $serviceScore = ve_clamp(isset($_POST['service_score']) ? $_POST['service_score'] : 80);
    $complianceScore = ve_clamp(isset($_POST['compliance_score']) ? $_POST['compliance_score'] : 80);
    $purchasingOrg = isset($_POST['purchasing_org']) ? trim($_POST['purchasing_org']) : '';
    $plant = isset($_POST['plant']) ? trim($_POST['plant']) : '';
    $snapshot = ve_calculate_snapshot($vendor->kode_pemasok, $_POST['period_from'], $_POST['period_to'], $purchasingOrg, $plant);
    if ($snapshot['po_count'] === 0 && $snapshot['gr_count'] === 0) action_response(ve_t('vendor_evaluation_no_transaction', 'No PO/GR transactions found for this vendor and period.'));

    $total = ve_recalculate_total($snapshot['price_score'], $snapshot['delivery_score'], $snapshot['quality_score'], $serviceScore, $complianceScore);
    $evaluationNo = ve_next_number();
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

    $db->query('START TRANSACTION');
    $insert = array_merge($snapshot, array(
      'evaluation_no' => $evaluationNo,
      'vendor_code' => $vendor->kode_pemasok,
      'vendor_name' => $vendor->nama,
      'period_from' => $_POST['period_from'],
      'period_to' => $_POST['period_to'],
      'purchasing_org' => $purchasingOrg,
      'plant' => $plant,
      'service_score' => $serviceScore,
      'compliance_score' => $complianceScore,
      'total_score' => $total,
      'rating' => ve_rating($total),
      'status' => 'DRAFT',
      'evaluator' => $username,
      'remarks' => isset($_POST['remarks']) ? $_POST['remarks'] : ''
    ));
    if (!$db->insert('erp_vendor_evaluation', $insert)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($err);
    }
    $evaluationId = $db->last_insert_id();
    ve_write_details($evaluationId, array(
      array('code' => 'PRICE', 'name' => 'Price competitiveness vs awarded RFQ', 'weight' => 20, 'score' => $snapshot['price_score'], 'source' => 'AUTO', 'notes' => 'Average price variance '.$snapshot['price_variance_pct'].'%.'),
      array('code' => 'DELIVERY', 'name' => 'On-time delivery and quantity reliability', 'weight' => 30, 'score' => $snapshot['delivery_score'], 'source' => 'AUTO', 'notes' => 'On-time '.$snapshot['on_time_delivery_pct'].'%, qty accuracy '.$snapshot['qty_accuracy_pct'].'%.'),
      array('code' => 'QUALITY', 'name' => 'Quality receipt and blocked stock rate', 'weight' => 25, 'score' => $snapshot['quality_score'], 'source' => 'AUTO', 'notes' => 'Blocked/defect rate '.$snapshot['defect_rate_pct'].'%.'),
      array('code' => 'SERVICE', 'name' => 'Responsiveness, documentation, support', 'weight' => 15, 'score' => $serviceScore, 'source' => 'MANUAL', 'notes' => 'Manual purchasing score.'),
      array('code' => 'COMPLIANCE', 'name' => 'Tax, customs, certificate, and audit compliance', 'weight' => 10, 'score' => $complianceScore, 'source' => 'MANUAL', 'notes' => 'Manual compliance score.')
    ));
    ve_history($evaluationId, null, 'DRAFT', 'Evaluation generated from PO/GR/RFQ data.');
    $db->query('COMMIT');
    action_response('', array('id' => $evaluationId, 'evaluation_no' => $evaluationNo));
    break;

  case 'detail':
    ve_render_detail((int)$_POST['id']);
    break;

  case 'score_form':
    $e = $db->fetch("SELECT * FROM erp_vendor_evaluation WHERE id=? LIMIT 1", array('id' => $_POST['id']));
    if (!$e) {
      echo "<div class='alert alert-warning'>".ve_h(ve_t('vendor_evaluation_data_not_found', 'Data not found.'))."</div>";
      break;
    }
    if ($e->status !== 'DRAFT') {
      echo "<div class='alert alert-warning'>".ve_h(ve_t('vendor_evaluation_draft_only_score', 'Score can only be changed while status is DRAFT.'))."</div>";
      break;
    }
    ?>
    <input type="hidden" name="id" value="<?=intval($e->id);?>">
    <div class="alert alert-info"><?=ve_h($e->evaluation_no.' - '.$e->vendor_name);?></div>
    <div class="form-group"><label><?=ve_h(ve_t('vendor_evaluation_service_score', 'Service Score'));?></label><input type="number" min="0" max="100" step="0.01" name="service_score" class="form-control" value="<?=ve_h($e->service_score);?>" required><small class="text-muted"><?=ve_h(ve_t('vendor_evaluation_service_help', 'Responsiveness, after-sales support, follow-up, document readiness.'));?></small></div>
    <div class="form-group"><label><?=ve_h(ve_t('vendor_evaluation_compliance_score', 'Compliance Score'));?></label><input type="number" min="0" max="100" step="0.01" name="compliance_score" class="form-control" value="<?=ve_h($e->compliance_score);?>" required><small class="text-muted"><?=ve_h(ve_t('vendor_evaluation_compliance_help', 'Tax, customs, certificates, audit findings, legal/ethical compliance.'));?></small></div>
    <div class="form-group"><label><?=ve_h(ve_t('vendor_evaluation_notes', 'Remarks'));?></label><textarea name="remarks" class="form-control" rows="4"><?=ve_h($e->remarks);?></textarea></div>
    <?php
    break;

  case 'save_score':
    $e = $db->fetch("SELECT * FROM erp_vendor_evaluation WHERE id=? LIMIT 1", array('id' => $_POST['id']));
    if (!$e) action_response(ve_t('vendor_evaluation_data_not_found', 'Data not found.'));
    if ($e->status !== 'DRAFT') action_response(ve_t('vendor_evaluation_draft_only_score', 'Score can only be changed while status is DRAFT.'));
    $serviceScore = ve_clamp($_POST['service_score']);
    $complianceScore = ve_clamp($_POST['compliance_score']);
    $total = ve_recalculate_total($e->price_score, $e->delivery_score, $e->quality_score, $serviceScore, $complianceScore);
    $db->query('START TRANSACTION');
    if (!$db->update('erp_vendor_evaluation', array(
      'service_score' => $serviceScore,
      'compliance_score' => $complianceScore,
      'total_score' => $total,
      'rating' => ve_rating($total),
      'remarks' => isset($_POST['remarks']) ? $_POST['remarks'] : $e->remarks
    ), 'id', $e->id)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($err);
    }
    ve_write_details($e->id, array(
      array('code' => 'PRICE', 'name' => 'Price competitiveness vs awarded RFQ', 'weight' => 20, 'score' => $e->price_score, 'source' => 'AUTO', 'notes' => 'Average price variance '.$e->price_variance_pct.'%.'),
      array('code' => 'DELIVERY', 'name' => 'On-time delivery and quantity reliability', 'weight' => 30, 'score' => $e->delivery_score, 'source' => 'AUTO', 'notes' => 'On-time '.$e->on_time_delivery_pct.'%, qty accuracy '.$e->qty_accuracy_pct.'%.'),
      array('code' => 'QUALITY', 'name' => 'Quality receipt and blocked stock rate', 'weight' => 25, 'score' => $e->quality_score, 'source' => 'AUTO', 'notes' => 'Blocked/defect rate '.$e->defect_rate_pct.'%.'),
      array('code' => 'SERVICE', 'name' => 'Responsiveness, documentation, support', 'weight' => 15, 'score' => $serviceScore, 'source' => 'MANUAL', 'notes' => 'Manual purchasing score updated.'),
      array('code' => 'COMPLIANCE', 'name' => 'Tax, customs, certificate, and audit compliance', 'weight' => 10, 'score' => $complianceScore, 'source' => 'MANUAL', 'notes' => 'Manual compliance score updated.')
    ));
    ve_history($e->id, 'DRAFT', 'DRAFT', 'Manual scorecard updated.');
    $db->query('COMMIT');
    action_response('');
    break;

  case 'finalize':
    $e = $db->fetch("SELECT * FROM erp_vendor_evaluation WHERE id=? LIMIT 1", array('id' => $_POST['id']));
    if (!$e) action_response(ve_t('vendor_evaluation_data_not_found', 'Data not found.'));
    if ($e->status !== 'DRAFT') action_response(ve_t('vendor_evaluation_draft_only_finalize', 'Only DRAFT status can be finalized.'));
    $db->query('START TRANSACTION');
    if (!$db->update('erp_vendor_evaluation', array('status' => 'FINALIZED', 'finalized_at' => date('Y-m-d H:i:s')), 'id', $e->id)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($err);
    }
    ve_history($e->id, 'DRAFT', 'FINALIZED', 'Evaluation finalized and locked.');
    $db->query('COMMIT');
    action_response('');
    break;

  case 'cancel':
    $e = $db->fetch("SELECT * FROM erp_vendor_evaluation WHERE id=? LIMIT 1", array('id' => $_POST['id']));
    if (!$e) action_response(ve_t('vendor_evaluation_data_not_found', 'Data not found.'));
    if ($e->status === 'FINALIZED') action_response(ve_t('vendor_evaluation_finalized_cannot_cancel', 'Finalized evaluation cannot be cancelled.'));
    $db->query('START TRANSACTION');
    if (!$db->update('erp_vendor_evaluation', array('status' => 'CANCELLED'), 'id', $e->id)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($err);
    }
    ve_history($e->id, $e->status, 'CANCELLED', 'Evaluation cancelled.');
    $db->query('COMMIT');
    action_response('');
    break;

  default:
    action_response(ve_t('vendor_evaluation_unknown_action', 'Unknown action.'));
    break;
}
?>
