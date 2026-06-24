<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function rfq_t($key, $fallback = '') { return lang_text($key, $fallback); }
function rfq_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rfq_msg($key, $fallback = '', $replacements = array()) {
  $text = rfq_t($key, $fallback);
  foreach ($replacements as $name => $value) $text = str_replace('{'.$name.'}', (string)$value, $text);
  return $text;
}

function rfq_next_number()
{
  global $db;
  $prefix = 'RFQ'.date('Y');
  $row = $db->fetch("SELECT rfq_no FROM erp_rfq WHERE rfq_no LIKE ? ORDER BY rfq_no DESC LIMIT 1", array('rfq_no' => $prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{6})$/', $row->rfq_no, $m)) {
    $next = intval($m[1]) + 1;
  }
  return $prefix.sprintf('%06d', $next);
}

function rfq_status_label($status)
{
  $class = 'default';
  if ($status === 'DRAFT') $class = 'default';
  if ($status === 'SENT') $class = 'warning';
  if ($status === 'QUOTED') $class = 'info';
  if ($status === 'AWARDED') $class = 'success';
  if ($status === 'CLOSED') $class = 'primary';
  if ($status === 'CANCELLED') $class = 'danger';
  return "<span class='label label-".$class."'>".htmlspecialchars($status, ENT_QUOTES, 'UTF-8')."</span>";
}

function rfq_vendor_status_label($status)
{
  $class = 'default';
  if ($status === 'INVITED') $class = 'warning';
  if ($status === 'RESPONDED') $class = 'info';
  if ($status === 'AWARDED') $class = 'success';
  if ($status === 'REJECTED' || $status === 'DECLINED') $class = 'danger';
  return "<span class='label label-".$class."'>".htmlspecialchars($status, ENT_QUOTES, 'UTF-8')."</span>";
}

function rfq_history($rfqId, $oldStatus, $newStatus, $remarks)
{
  global $db;
  $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
  return $db->insert('erp_rfq_history', array(
    'rfq_id' => $rfqId,
    'status_lama' => $oldStatus,
    'status_baru' => $newStatus,
    'remarks' => $remarks,
    'changed_by' => $username
  ));
}

function rfq_rank_quotes($rfqId)
{
  global $db;
  $items = $db->query("SELECT id FROM erp_rfq_item WHERE rfq_id=?", array('rfq_id' => $rfqId));
  foreach ($items as $item) {
    $rank = 1;
    $quotes = $db->query(
      "SELECT id,
              (price * qty * (1 - (discount_percent/100)) * (1 + (tax_percent/100))) AS net_value
       FROM erp_rfq_quotation
       WHERE rfq_item_id=?
       ORDER BY net_value ASC, delivery_days ASC, id ASC",
      array('rfq_item_id' => $item->id)
    );
    foreach ($quotes as $quote) {
      $db->update('erp_rfq_quotation', array('rank_no' => $rank), 'id', $quote->id);
      $rank++;
    }
  }
}

function rfq_render_detail($rfqId)
{
  global $db;
  $header = $db->fetch("SELECT r.*,ep.plant_name FROM erp_rfq r LEFT JOIN erp_plant ep ON ep.plant_code=r.plant WHERE r.id=? LIMIT 1", array('id' => $rfqId));
  if (!$header) {
    echo "<div class='alert alert-warning'>".rfq_h(rfq_t('rfq_not_found','RFQ tidak ditemukan.'))."</div>";
    return;
  }
  $items = $db->query("SELECT i.*,pr.no_pr FROM erp_rfq_item i LEFT JOIN purchase_requisition pr ON pr.id_pr=i.id_pr WHERE i.rfq_id=? ORDER BY i.line_no", array('rfq_id' => $rfqId));
  $vendors = $db->query("SELECT * FROM erp_rfq_vendor WHERE rfq_id=? ORDER BY vendor_name,vendor_code", array('rfq_id' => $rfqId));
  $quotes = $db->query(
    "SELECT q.*,i.line_no,i.material_code,i.material_name,v.vendor_name
     FROM erp_rfq_quotation q
     JOIN erp_rfq_item i ON i.id=q.rfq_item_id
     JOIN erp_rfq_vendor v ON v.id=q.rfq_vendor_id
     WHERE q.rfq_id=?
     ORDER BY i.line_no, q.rank_no, v.vendor_name",
    array('rfq_id' => $rfqId)
  );
  $history = $db->query("SELECT * FROM erp_rfq_history WHERE rfq_id=? ORDER BY changed_at DESC,id DESC", array('rfq_id' => $rfqId));
  ?>
  <div class="rfq-detail">
    <div class="row">
      <div class="col-md-8">
        <h3 style="margin-top:0;font-weight:700"><?=htmlspecialchars($header->rfq_no, ENT_QUOTES, 'UTF-8');?> <small><?=htmlspecialchars($header->subject, ENT_QUOTES, 'UTF-8');?></small></h3>
        <p class="text-muted"><?=htmlspecialchars($header->note, ENT_QUOTES, 'UTF-8');?></p>
      </div>
      <div class="col-md-4 text-right">
        <?=rfq_status_label($header->status);?>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><strong><?=rfq_h(rfq_t('rfq_date','RFQ Date'));?></strong><br><?=htmlspecialchars($header->rfq_date, ENT_QUOTES, 'UTF-8');?></div>
      <div class="col-sm-3"><strong><?=rfq_h(rfq_t('rfq_deadline_short','Deadline'));?></strong><br><?=htmlspecialchars($header->quotation_deadline, ENT_QUOTES, 'UTF-8');?></div>
      <div class="col-sm-3"><strong><?=rfq_h(rfq_t('common_plant','Plant'));?></strong><br><?=htmlspecialchars(trim($header->plant.' - '.$header->plant_name), ENT_QUOTES, 'UTF-8');?></div>
      <div class="col-sm-3"><strong><?=rfq_h(rfq_t('purchase_order_currency','Currency'));?></strong><br><?=htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8');?></div>
    </div>
    <hr>
    <h4><?=rfq_h(rfq_t('rfq_pr_items','RFQ Items'));?></h4>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead><tr class="bg-gray"><th><?=rfq_h(rfq_t('purchase_requisition_item','Item'));?></th><th>PR</th><th><?=rfq_h(rfq_t('purchase_requisition_material','Material'));?></th><th class="text-right"><?=rfq_h(rfq_t('purchase_order_qty','Qty'));?></th><th><?=rfq_h(rfq_t('purchase_order_uom','UOM'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_req_date','Req. Date'));?></th><th class="text-right"><?=rfq_h(rfq_t('rfq_target_price','Target Price'));?></th></tr></thead>
        <tbody>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><?=intval($item->line_no);?></td>
            <td><?=htmlspecialchars($item->no_pr, ENT_QUOTES, 'UTF-8');?></td>
            <td><strong><?=htmlspecialchars($item->material_code, ENT_QUOTES, 'UTF-8');?></strong><br><span class="text-muted"><?=htmlspecialchars($item->material_name, ENT_QUOTES, 'UTF-8');?></span></td>
            <td class="text-right"><?=number_format((float) $item->qty, 5, ',', '.');?></td>
            <td><?=htmlspecialchars($item->uom, ENT_QUOTES, 'UTF-8');?></td>
            <td><?=htmlspecialchars($item->required_date, ENT_QUOTES, 'UTF-8');?></td>
            <td class="text-right"><?=htmlspecialchars($item->currency, ENT_QUOTES, 'UTF-8');?> <?=number_format((float) $item->target_price, 2, ',', '.');?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="row">
      <div class="col-md-5">
        <h4><?=rfq_h(rfq_t('rfq_invited_vendors','Invited Vendors'));?></h4>
        <table class="table table-bordered table-condensed">
          <thead><tr class="bg-gray"><th><?=rfq_h(rfq_t('purchase_requisition_vendor','Vendor'));?></th><th><?=rfq_h(rfq_t('purchase_order_email','Email'));?></th><th><?=rfq_h(rfq_t('common_status','Status'));?></th></tr></thead>
          <tbody>
          <?php foreach ($vendors as $vendor) { ?>
            <tr><td><strong><?=htmlspecialchars($vendor->vendor_code, ENT_QUOTES, 'UTF-8');?></strong><br><?=htmlspecialchars($vendor->vendor_name, ENT_QUOTES, 'UTF-8');?></td><td><?=htmlspecialchars($vendor->email, ENT_QUOTES, 'UTF-8');?></td><td><?=rfq_vendor_status_label($vendor->status);?></td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
      <div class="col-md-7">
        <h4><?=rfq_h(rfq_t('rfq_quotation_comparison','Quotation Comparison'));?></h4>
        <table class="table table-bordered table-condensed">
          <thead><tr class="bg-gray"><th><?=rfq_h(rfq_t('rfq_rank','Rank'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_item','Item'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_vendor','Vendor'));?></th><th class="text-right"><?=rfq_h(rfq_t('purchase_order_price','Price'));?></th><th class="text-right"><?=rfq_h(rfq_t('rfq_net_value','Net Value'));?></th><th><?=rfq_h(rfq_t('rfq_delivery','Delivery'));?></th><th><?=rfq_h(rfq_t('rfq_award','Award'));?></th></tr></thead>
          <tbody>
          <?php foreach ($quotes as $quote) {
            $net = (float) $quote->price * (float) $quote->qty * (1 - ((float) $quote->discount_percent / 100)) * (1 + ((float) $quote->tax_percent / 100));
          ?>
            <tr>
              <td><?=htmlspecialchars($quote->rank_no, ENT_QUOTES, 'UTF-8');?></td>
              <td><?=intval($quote->line_no);?> - <?=htmlspecialchars($quote->material_code, ENT_QUOTES, 'UTF-8');?></td>
              <td><?=htmlspecialchars($quote->vendor_name, ENT_QUOTES, 'UTF-8');?></td>
              <td class="text-right"><?=htmlspecialchars($quote->currency, ENT_QUOTES, 'UTF-8');?> <?=number_format((float) $quote->price, 2, ',', '.');?></td>
              <td class="text-right"><?=htmlspecialchars($quote->currency, ENT_QUOTES, 'UTF-8');?> <?=number_format($net, 2, ',', '.');?></td>
              <td><?=intval($quote->delivery_days);?> <?=rfq_h(rfq_t('rfq_days','days'));?></td>
              <td>
                <?php if ($quote->is_awarded === 'Y') { ?>
                  <span class="label label-success"><?=rfq_h(rfq_t('rfq_status_awarded','Awarded'));?></span>
                <?php } else if (!in_array($header->status, array('CLOSED','CANCELLED'))) { ?>
                  <button type="button" class="btn btn-success btn-xs btn-award-quote" data-id="<?=intval($quote->id);?>"><i class="fa fa-trophy"></i> <?=rfq_h(rfq_t('rfq_award','Award'));?></button>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
    <h4><?=rfq_h(rfq_t('rfq_history','History'));?></h4>
    <ul class="list-unstyled">
      <?php foreach ($history as $row) { ?>
        <li><strong><?=htmlspecialchars($row->status_lama.' -> '.$row->status_baru, ENT_QUOTES, 'UTF-8');?></strong> <span class="text-muted"><?=htmlspecialchars($row->changed_by.' @ '.$row->changed_at, ENT_QUOTES, 'UTF-8');?></span><br><?=htmlspecialchars($row->remarks, ENT_QUOTES, 'UTF-8');?></li>
      <?php } ?>
    </ul>
  </div>
  <?php
}

switch ($_GET['act']) {
  case 'available_pr_items':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $params = array();
    $wh = " AND pr.status IN ('APPROVED','PARTIAL_PO') AND d.qty_open>0 ";
    if ($term !== '') {
      $wh .= " AND (pr.no_pr LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ?) ";
      $params[] = '%'.$term.'%';
      $params[] = '%'.$term.'%';
      $params[] = '%'.$term.'%';
    }
    $rows = $db->query(
      "SELECT d.*,pr.no_pr,pr.requestor,pr.department
       FROM purchase_requisition_detail d
       JOIN purchase_requisition pr ON pr.id_pr=d.id_pr
       WHERE 1=1 $wh
       ORDER BY pr.no_pr,d.line_no
       LIMIT 30",
      $params
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->id_pr_detail,
        'text' => $row->no_pr.' / '.$row->line_no.' - '.$row->material_code.' - '.$row->material_name,
        'id_pr' => $row->id_pr,
        'no_pr' => $row->no_pr,
        'line_no' => $row->line_no,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'qty' => $row->qty_open,
        'uom' => $row->uom,
        'required_date' => $row->required_date,
        'plant' => $row->plant,
        'storage_location' => $row->storage_location,
        'target_price' => $row->valuation_price,
        'currency' => $row->currency
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'vendor_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query(
      "SELECT kode_pemasok,nama,email FROM pemasok
       WHERE kode_pemasok LIKE ? OR nama LIKE ?
       ORDER BY nama
       LIMIT 30",
      array('kode' => '%'.$term.'%', 'nama' => '%'.$term.'%')
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kode_pemasok,
        'text' => $row->kode_pemasok.' - '.$row->nama,
        'vendor_name' => $row->nama,
        'email' => $row->email
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'in':
    $required = array(
      'rfq_date' => rfq_t('rfq_date','RFQ Date'),
      'quotation_deadline' => rfq_t('rfq_deadline','Quotation Deadline'),
      'subject' => rfq_t('rfq_subject','Subject'),
      'currency' => rfq_t('purchase_order_currency','Currency')
    );
    foreach ($required as $field => $label) {
      if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') action_response(rfq_msg('rfq_required_message','{field} wajib diisi.', array('field'=>$label)));
    }
    if (empty($_POST['pr_detail_id']) || !is_array($_POST['pr_detail_id'])) action_response(rfq_t('rfq_min_pr_item','Minimal satu PR item wajib dipilih.'));
    if (empty($_POST['vendor_code']) || !is_array($_POST['vendor_code'])) action_response(rfq_t('rfq_min_vendor','Minimal satu vendor wajib dipilih.'));

    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $status = isset($_POST['save_mode']) && $_POST['save_mode'] === 'SENT' ? 'SENT' : 'DRAFT';
    $rfqNo = rfq_next_number();
    $db->query('START TRANSACTION');

    if (!$db->insert('erp_rfq', array(
      'rfq_no' => $rfqNo,
      'rfq_date' => $_POST['rfq_date'],
      'quotation_deadline' => $_POST['quotation_deadline'],
      'purchasing_org' => isset($_POST['purchasing_org']) ? $_POST['purchasing_org'] : '',
      'purchasing_group' => isset($_POST['purchasing_group']) ? $_POST['purchasing_group'] : '',
      'plant' => isset($_POST['plant']) ? $_POST['plant'] : '',
      'storage_location' => isset($_POST['storage_location']) ? $_POST['storage_location'] : '',
      'currency' => $_POST['currency'],
      'status' => $status,
      'subject' => $_POST['subject'],
      'note' => isset($_POST['note']) ? $_POST['note'] : '',
      'created_by' => $username,
      'updated_by' => $username
    ))) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }
    $rfqId = $db->last_insert_id();

    foreach ($_POST['pr_detail_id'] as $key => $prDetailId) {
      $prDetailId = intval($prDetailId);
      $source = $db->fetch(
        "SELECT d.*,pr.no_pr
         FROM purchase_requisition_detail d
         JOIN purchase_requisition pr ON pr.id_pr=d.id_pr
         WHERE d.id_pr_detail=? AND pr.status IN ('APPROVED','PARTIAL_PO') AND d.qty_open>0
         LIMIT 1",
        array('id_pr_detail' => $prDetailId)
      );
      if (!$source) {
        $db->query('ROLLBACK');
        action_response(rfq_t('rfq_pr_item_invalid','PR item tidak valid atau belum approved.'));
      }
      $qty = isset($_POST['qty'][$key]) ? floatval($_POST['qty'][$key]) : (float) $source->qty_open;
      if ($qty <= 0 || $qty - (float) $source->qty_open > 0.00001) {
        $db->query('ROLLBACK');
        action_response(rfq_msg('rfq_qty_invalid','Qty RFQ item {material} tidak valid.', array('material'=>$source->material_code)));
      }
      if (!$db->insert('erp_rfq_item', array(
        'rfq_id' => $rfqId,
        'id_pr' => $source->id_pr,
        'id_pr_detail' => $source->id_pr_detail,
        'line_no' => isset($_POST['line_no'][$key]) ? intval($_POST['line_no'][$key]) : (($key + 1) * 10),
        'material_code' => $source->material_code,
        'material_name' => $source->material_name,
        'qty' => $qty,
        'uom' => $source->uom,
        'required_date' => $source->required_date,
        'plant' => $source->plant,
        'storage_location' => $source->storage_location,
        'target_price' => $source->valuation_price,
        'currency' => $source->currency ?: $_POST['currency'],
        'remarks' => isset($_POST['item_remarks'][$key]) ? $_POST['item_remarks'][$key] : ''
      ))) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    $seenVendor = array();
    foreach ($_POST['vendor_code'] as $key => $vendorCode) {
      $vendorCode = trim((string) $vendorCode);
      if ($vendorCode === '' || isset($seenVendor[$vendorCode])) continue;
      $seenVendor[$vendorCode] = true;
      $vendor = $db->fetch("SELECT kode_pemasok,nama,email FROM pemasok WHERE kode_pemasok=? LIMIT 1", array('kode_pemasok' => $vendorCode));
      if (!$vendor) {
        $db->query('ROLLBACK');
        action_response(rfq_msg('rfq_vendor_not_found','Vendor {vendor} tidak ditemukan.', array('vendor'=>$vendorCode)));
      }
      if (!$db->insert('erp_rfq_vendor', array(
        'rfq_id' => $rfqId,
        'vendor_code' => $vendor->kode_pemasok,
        'vendor_name' => $vendor->nama,
        'email' => $vendor->email,
        'status' => $status === 'SENT' ? 'INVITED' : 'INVITED',
        'sent_at' => $status === 'SENT' ? date('Y-m-d H:i:s') : '',
        'note' => isset($_POST['vendor_note'][$key]) ? $_POST['vendor_note'][$key] : ''
      ))) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    rfq_history($rfqId, '', $status, $status === 'SENT' ? rfq_t('rfq_created_sent_history','RFQ created and sent to vendors.') : rfq_t('rfq_draft_history','RFQ saved as draft.'));
    $db->query('COMMIT');
    if (function_exists('simpan_log')) simpan_log(rfq_msg('rfq_created_log','User {user} membuat RFQ {rfq_no} status {status} pada {datetime}', array('user'=>$username,'rfq_no'=>$rfqNo,'status'=>$status,'datetime'=>date('Y-m-d H:i:s'))), $username);
    action_response('', array('rfq_id' => $rfqId, 'rfq_no' => $rfqNo));
    break;

  case 'send':
    $id = intval($_POST['id']);
    $rfq = $db->fetch("SELECT * FROM erp_rfq WHERE id=? LIMIT 1", array('id' => $id));
    if (!$rfq) action_response(rfq_t('rfq_not_found','RFQ tidak ditemukan.'));
    if ($rfq->status !== 'DRAFT') action_response(rfq_t('rfq_draft_only_send','Hanya RFQ draft yang bisa dikirim.'));
    $db->query('START TRANSACTION');
    $db->update('erp_rfq', array('status' => 'SENT', 'updated_by' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system'), 'id', $id);
    $db->query("UPDATE erp_rfq_vendor SET sent_at=COALESCE(sent_at,NOW()), status='INVITED' WHERE rfq_id=?", array('rfq_id' => $id));
    rfq_history($id, $rfq->status, 'SENT', rfq_t('rfq_sent_history','RFQ sent to invited vendors.'));
    $db->query('COMMIT');
    action_response('');
    break;

  case 'quote_form':
    $rfqId = intval($_POST['id']);
    $rfq = $db->fetch("SELECT * FROM erp_rfq WHERE id=? LIMIT 1", array('id' => $rfqId));
    if (!$rfq) {
      echo "<div class='alert alert-warning'>".rfq_h(rfq_t('rfq_not_found','RFQ tidak ditemukan.'))."</div>";
      break;
    }
    if (in_array($rfq->status, array('AWARDED','CLOSED','CANCELLED'))) {
      echo "<div class='alert alert-warning'>".rfq_h(rfq_msg('rfq_status_cannot_quote','RFQ status {status} tidak bisa input quotation.', array('status'=>$rfq->status)))."</div>";
      break;
    }
    $vendors = $db->query("SELECT * FROM erp_rfq_vendor WHERE rfq_id=? ORDER BY vendor_name,vendor_code", array('rfq_id' => $rfqId));
    $items = $db->query("SELECT * FROM erp_rfq_item WHERE rfq_id=? ORDER BY line_no", array('rfq_id' => $rfqId));
    ?>
    <input type="hidden" id="quote_rfq_id" value="<?=intval($rfqId);?>">
    <div class="form-group">
      <label><?=rfq_h(rfq_t('purchase_requisition_vendor','Vendor'));?></label>
      <select id="quote_vendor_id" name="rfq_vendor_id" class="form-control" required>
        <option value=""><?=rfq_h(rfq_t('rfq_vendor_required','Pilih Vendor'));?></option>
        <?php foreach ($vendors as $vendor) { ?>
          <option value="<?=intval($vendor->id);?>"><?=htmlspecialchars($vendor->vendor_code.' - '.$vendor->vendor_name.' ['.$vendor->status.']', ENT_QUOTES, 'UTF-8');?></option>
        <?php } ?>
      </select>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead><tr class="bg-gray"><th><?=rfq_h(rfq_t('purchase_requisition_item','Item'));?></th><th><?=rfq_h(rfq_t('purchase_requisition_material','Material'));?></th><th class="text-right"><?=rfq_h(rfq_t('purchase_order_qty','Qty'));?></th><th><?=rfq_h(rfq_t('purchase_order_uom','UOM'));?></th><th><?=rfq_h(rfq_t('purchase_order_price','Price'));?></th><th><?=rfq_h(rfq_t('rfq_disc_percent','Disc %'));?></th><th><?=rfq_h(rfq_t('rfq_tax_percent','Tax %'));?></th><th><?=rfq_h(rfq_t('rfq_delivery_days','Delivery Days'));?></th><th><?=rfq_h(rfq_t('rfq_payment_terms','Payment Terms'));?></th><th><?=rfq_h(rfq_t('rfq_valid_until','Valid Until'));?></th></tr></thead>
        <tbody>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><?=intval($item->line_no);?><input type="hidden" name="rfq_item_id[]" value="<?=intval($item->id);?>"></td>
            <td><strong><?=htmlspecialchars($item->material_code, ENT_QUOTES, 'UTF-8');?></strong><br><span class="text-muted"><?=htmlspecialchars($item->material_name, ENT_QUOTES, 'UTF-8');?></span></td>
            <td class="text-right"><?=number_format((float) $item->qty, 5, ',', '.');?><input type="hidden" name="quote_qty[]" value="<?=htmlspecialchars($item->qty, ENT_QUOTES, 'UTF-8');?>"></td>
            <td><?=htmlspecialchars($item->uom, ENT_QUOTES, 'UTF-8');?></td>
            <td><input type="number" step="0.00001" min="0.00001" name="price[]" class="form-control input-sm" required><input type="hidden" name="currency[]" value="<?=htmlspecialchars($item->currency, ENT_QUOTES, 'UTF-8');?>"></td>
            <td><input type="number" step="0.0001" min="0" name="discount_percent[]" class="form-control input-sm" value="0"></td>
            <td><input type="number" step="0.0001" min="0" name="tax_percent[]" class="form-control input-sm" value="0"></td>
            <td><input type="number" min="0" name="delivery_days[]" class="form-control input-sm" value="0"></td>
            <td><input type="text" name="payment_terms[]" class="form-control input-sm"></td>
            <td><input type="text" name="valid_until[]" class="form-control input-sm date-field-quote" value="<?=htmlspecialchars($rfq->quotation_deadline, ENT_QUOTES, 'UTF-8');?>"><input type="hidden" name="remarks[]" value=""></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case 'save_quote':
    $rfqVendorId = intval($_POST['rfq_vendor_id']);
    $vendor = $db->fetch("SELECT v.*,r.status AS rfq_status FROM erp_rfq_vendor v JOIN erp_rfq r ON r.id=v.rfq_id WHERE v.id=? LIMIT 1", array('id' => $rfqVendorId));
    if (!$vendor) action_response(rfq_t('rfq_vendor_rfq_not_found','Vendor RFQ tidak ditemukan.'));
    if (in_array($vendor->rfq_status, array('AWARDED','CLOSED','CANCELLED'))) action_response(rfq_t('rfq_final_cannot_change_quote','RFQ sudah final, quotation tidak bisa diubah.'));
    if (empty($_POST['rfq_item_id']) || !is_array($_POST['rfq_item_id'])) action_response(rfq_t('rfq_empty_quote_item','Item quotation kosong.'));
    $db->query('START TRANSACTION');
    foreach ($_POST['rfq_item_id'] as $key => $itemId) {
      $item = $db->fetch("SELECT * FROM erp_rfq_item WHERE id=? AND rfq_id=? LIMIT 1", array('id' => intval($itemId), 'rfq_id' => $vendor->rfq_id));
      if (!$item) {
        $db->query('ROLLBACK');
        action_response(rfq_t('rfq_quote_item_invalid','Item quotation tidak valid.'));
      }
      $price = isset($_POST['price'][$key]) ? floatval($_POST['price'][$key]) : 0;
      if ($price <= 0) {
        $db->query('ROLLBACK');
        action_response(rfq_msg('rfq_price_positive','Price item {line} wajib lebih dari nol.', array('line'=>$item->line_no)));
      }
      $existing = $db->fetch("SELECT id FROM erp_rfq_quotation WHERE rfq_vendor_id=? AND rfq_item_id=? LIMIT 1", array('rfq_vendor_id' => $rfqVendorId, 'rfq_item_id' => $item->id));
      $quote = array(
        'rfq_id' => $vendor->rfq_id,
        'rfq_vendor_id' => $rfqVendorId,
        'rfq_item_id' => $item->id,
        'vendor_code' => $vendor->vendor_code,
        'price' => $price,
        'qty' => isset($_POST['quote_qty'][$key]) && $_POST['quote_qty'][$key] !== '' ? floatval($_POST['quote_qty'][$key]) : $item->qty,
        'currency' => isset($_POST['currency'][$key]) && $_POST['currency'][$key] !== '' ? $_POST['currency'][$key] : $item->currency,
        'discount_percent' => isset($_POST['discount_percent'][$key]) ? floatval($_POST['discount_percent'][$key]) : 0,
        'tax_percent' => isset($_POST['tax_percent'][$key]) ? floatval($_POST['tax_percent'][$key]) : 0,
        'delivery_days' => isset($_POST['delivery_days'][$key]) ? intval($_POST['delivery_days'][$key]) : 0,
        'payment_terms' => isset($_POST['payment_terms'][$key]) ? $_POST['payment_terms'][$key] : '',
        'valid_until' => isset($_POST['valid_until'][$key]) ? $_POST['valid_until'][$key] : '',
        'remarks' => isset($_POST['remarks'][$key]) ? $_POST['remarks'][$key] : ''
      );
      if ($existing) {
        if (!$db->update('erp_rfq_quotation', $quote, 'id', $existing->id)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          action_response($error);
        }
      } else if (!$db->insert('erp_rfq_quotation', $quote)) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }
    $db->update('erp_rfq_vendor', array('status' => 'RESPONDED', 'responded_at' => date('Y-m-d H:i:s')), 'id', $rfqVendorId);
    $oldStatus = $vendor->rfq_status;
    if (in_array($oldStatus, array('DRAFT','SENT'))) {
      $db->update('erp_rfq', array('status' => 'QUOTED', 'updated_by' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system'), 'id', $vendor->rfq_id);
      rfq_history($vendor->rfq_id, $oldStatus, 'QUOTED', rfq_msg('rfq_quote_received_history','Quotation received from vendor {vendor}.', array('vendor'=>$vendor->vendor_code)));
    } else {
      rfq_history($vendor->rfq_id, $oldStatus, $oldStatus, rfq_msg('rfq_quote_updated_history','Quotation updated from vendor {vendor}.', array('vendor'=>$vendor->vendor_code)));
    }
    rfq_rank_quotes($vendor->rfq_id);
    $db->query('COMMIT');
    action_response('');
    break;

  case 'award':
    $quoteId = intval($_POST['quote_id']);
    $quote = $db->fetch("SELECT q.*,r.status AS rfq_status FROM erp_rfq_quotation q JOIN erp_rfq r ON r.id=q.rfq_id WHERE q.id=? LIMIT 1", array('id' => $quoteId));
    if (!$quote) action_response(rfq_t('rfq_quote_not_found','Quotation tidak ditemukan.'));
    if (in_array($quote->rfq_status, array('CLOSED','CANCELLED'))) action_response(rfq_t('rfq_final','RFQ sudah final.'));
    $db->query('START TRANSACTION');
    $db->query("UPDATE erp_rfq_quotation SET is_awarded='N' WHERE rfq_item_id=?", array('rfq_item_id' => $quote->rfq_item_id));
    $db->update('erp_rfq_quotation', array('is_awarded' => 'Y'), 'id', $quoteId);
    $db->query("UPDATE erp_rfq_vendor SET status='REJECTED' WHERE rfq_id=?", array('rfq_id' => $quote->rfq_id));
    $db->update('erp_rfq_vendor', array('status' => 'AWARDED'), 'id', $quote->rfq_vendor_id);
    $db->update('erp_rfq', array('status' => 'AWARDED', 'updated_by' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system'), 'id', $quote->rfq_id);
    rfq_history($quote->rfq_id, $quote->rfq_status, 'AWARDED', rfq_msg('rfq_awarded_history','Awarded quotation {quote_id} to vendor {vendor}.', array('quote_id'=>$quoteId,'vendor'=>$quote->vendor_code)));
    $db->query('COMMIT');
    action_response('');
    break;

  case 'cancel':
    $id = intval($_POST['id']);
    $rfq = $db->fetch("SELECT * FROM erp_rfq WHERE id=? LIMIT 1", array('id' => $id));
    if (!$rfq) action_response(rfq_t('rfq_not_found','RFQ tidak ditemukan.'));
    if (in_array($rfq->status, array('CLOSED','CANCELLED'))) action_response(rfq_t('rfq_final','RFQ sudah final.'));
    $db->update('erp_rfq', array('status' => 'CANCELLED', 'updated_by' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system'), 'id', $id);
    rfq_history($id, $rfq->status, 'CANCELLED', rfq_t('rfq_cancelled_history','RFQ cancelled.'));
    action_response($db->getErrorMessage());
    break;

  case 'detail':
    rfq_render_detail(intval($_POST['id']));
    break;

  default:
    action_response(rfq_t('rfq_unknown_action','Aksi tidak dikenal.'));
}
?>
