<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include __DIR__ . "/../../inc/config.php";
session_check_json();

function pr_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function pr_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pr_msg($key, $fallback = '', $replacements = array())
{
  $text = pr_t($key, $fallback);
  foreach ($replacements as $name => $value) {
    $text = str_replace('{'.$name.'}', (string)$value, $text);
  }
  return $text;
}

function pr_next_number()
{
  global $db;
  $row = $db->fetch("SELECT AUTO_INCREMENT AS next_id
                     FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA=DATABASE()
                       AND TABLE_NAME='purchase_requisition'");
  $nextId = $row ? intval($row->next_id) : 1;
  return 'PR'.date('Y').sprintf('%06d', $nextId);
}

function pr_status_label($status)
{
  $class = 'default';
  if ($status === 'DRAFT') $class = 'default';
  if ($status === 'SUBMITTED') $class = 'warning';
  if ($status === 'APPROVED') $class = 'success';
  if ($status === 'REJECTED') $class = 'danger';
  if ($status === 'PARTIAL_PO') $class = 'info';
  if ($status === 'CONVERTED_PO') $class = 'primary';
  if ($status === 'CLOSED') $class = 'success';
  if ($status === 'CANCELLED') $class = 'danger';
  return "<span class='label label-".$class."'>".htmlspecialchars($status, ENT_QUOTES, 'UTF-8')."</span>";
}

function pr_resolve_approver($postedApprover, $priority = '')
{
  $approver = trim((string) $postedApprover);
  if ($approver !== '') {
    return $approver;
  }

  if (in_array($priority, array('HIGH', 'URGENT'))) {
    return 'manager_approver';
  }

  return 'purchasing';
}

function pr_ensure_pending_approval($idPr, $approver, $note)
{
  global $db;

  $pending = $db->fetch(
    "SELECT id_approval,approver
     FROM purchase_requisition_approval
     WHERE id_pr=? AND status='PENDING'
     ORDER BY approval_level,id_approval
     LIMIT 1
     FOR UPDATE",
    array('id_pr' => $idPr)
  );

  if ($pending) {
    return $db->update('purchase_requisition_approval', array(
      'approval_level' => 1,
      'approver' => $approver,
      'note' => $note
    ), 'id_approval', $pending->id_approval);
  }

  return $db->insert('purchase_requisition_approval', array(
    'id_pr' => $idPr,
    'approval_level' => 1,
    'approver' => $approver,
    'status' => 'PENDING',
    'note' => $note
  ));
}

function pr_filter_value($source, $key, $default = '')
{
  return isset($source[$key]) ? trim((string) $source[$key]) : $default;
}

function pr_where_from_filter($source, &$params)
{
  $where = " WHERE 1=1 ";
  $params = array();
  $from = pr_filter_value($source, 'tgl_awal');
  $to = pr_filter_value($source, 'tgl_akhir');
  if ($from !== '' && $to === '') $to = date('Y-m-d');
  if ($from !== '' && $to !== '') {
    $where .= " AND pr.tgl_pr BETWEEN ? AND ? ";
    $params[] = $from;
    $params[] = $to;
  }
  $status = pr_filter_value($source, 'status');
  if ($status !== '') {
    $where .= " AND pr.status = ? ";
    $params[] = $status;
  }
  $plant = pr_filter_value($source, 'plant');
  if ($plant !== '') {
    $where .= " AND pr.plant = ? ";
    $params[] = $plant;
  }
  $reference = pr_filter_value($source, 'reference');
  if ($reference !== '') {
    $keyword = '%'.$reference.'%';
    $where .= " AND (
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
  return $where;
}

function pr_report_rows($source)
{
  global $db;
  $params = array();
  $where = pr_where_from_filter($source, $params);
  return $db->query("SELECT pr.*,
                            ep.plant_name,
                            COALESCE(ds.open_qty,0) AS open_qty,
                            COALESCE(ds.item_count,0) AS item_count,
                            COALESCE(ds.total_qty,0) AS total_qty,
                            COALESCE(ds.total_value,0) AS total_value
                     FROM purchase_requisition pr
                     LEFT JOIN (
                       SELECT id_pr,COUNT(*) AS item_count,SUM(qty_open) AS open_qty,SUM(qty) AS total_qty,SUM(qty*valuation_price) AS total_value
                       FROM purchase_requisition_detail
                       GROUP BY id_pr
                     ) ds ON ds.id_pr=pr.id_pr
                     LEFT JOIN erp_plant ep ON ep.plant_code=pr.plant
                     $where
                     ORDER BY pr.id_pr DESC", $params);
}

switch ($_GET["act"]) {
  case "search_material":
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,b.kd_kategori,k.nm_kategori,mg.group_code,mg.group_name
                        FROM barang b
                        LEFT JOIN kategori k ON k.kd_kategori=b.kd_kategori
                        LEFT JOIN erp_material_group mg ON mg.id=b.material_group_id
                        WHERE b.kd_barang LIKE ? OR b.nm_barang LIKE ?
                        ORDER BY b.kd_barang
                        LIMIT 20", array('kode' => '%'.$term.'%', 'nama' => '%'.$term.'%'));
    $results = array();
    if ($rows) {
      foreach ($rows as $row) {
        $materialGroup = $row->group_code ?: $row->kd_kategori;
        $results[] = array(
          'id' => $row->kd_barang,
          'text' => $row->kd_barang.' - '.$row->nm_barang,
          'material_name' => $row->nm_barang,
          'uom' => $row->satuan,
          'kd_kategori' => $row->kd_kategori,
          'material_group' => $materialGroup
        );
      }
    }
    echo json_encode(array('results' => $results));
    break;

  case "excel":
    $initialOutputBufferLevel = ob_get_level();
    ob_start();
    ini_set('display_errors','0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once __DIR__ . "/../../inc/lib/PHPExcel.php";
    require_once __DIR__ . "/../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);

    $rows = pr_report_rows($_GET);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(pr_t('purchase_requisition_title', 'Purchase Requisition'));
    $headers = array(
      erp_export_label("No"),
      pr_t(erp_export_label("purchase_requisition_no_pr"),erp_export_label("No PR")),
      pr_t(erp_export_label("purchase_requisition_pr_date"),erp_export_label("PR Date")),
      pr_t(erp_export_label("purchase_requisition_document_type"),erp_export_label("Document Type")),
      pr_t(erp_export_label("common_plant"),erp_export_label("Plant")),
      pr_t(erp_export_label("purchase_requisition_plant_name"),erp_export_label("Plant Name")),
      pr_t(erp_export_label("purchase_order_storage_location"),erp_export_label("Storage Location")),
      pr_t(erp_export_label("purchase_requisition_requestor"),erp_export_label("Requestor")),
      pr_t(erp_export_label("common_department"),erp_export_label("Department")),
      pr_t(erp_export_label("purchase_requisition_priority"),erp_export_label("Priority")),
      pr_t(erp_export_label("purchase_requisition_required_date"),erp_export_label("Required Date")),
      pr_t(erp_export_label("purchase_order_items"),erp_export_label("Items")),
      pr_t(erp_export_label("purchase_requisition_total_qty"),erp_export_label("Total Qty")),
      pr_t(erp_export_label("purchase_requisition_open_qty"),erp_export_label("Open Qty")),
      pr_t(erp_export_label("purchase_requisition_estimated_value"),erp_export_label("Estimated Value")),
      pr_t(erp_export_label("common_status"),erp_export_label("Status")),
      pr_t(erp_export_label("purchase_requisition_created_by"),erp_export_label("Created By")),
      pr_t(erp_export_label("purchase_requisition_updated_by"),erp_export_label("Updated By")),
      pr_t(erp_export_label("purchase_order_note"),erp_export_label("Note"))
    );
    foreach ($headers as $i=>$h) {
      $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
    }
    $r = 5; $n = 1;
    foreach ($rows as $row) {
      $values = array(
        $n++,
        $row->no_pr,
        $row->tgl_pr,
        $row->document_type,
        $row->plant,
        $row->plant_name,
        $row->storage_location,
        $row->requestor,
        $row->department,
        $row->priority,
        $row->required_date,
        (int) $row->item_count,
        (float) $row->total_qty,
        (float) $row->open_qty,
        (float) $row->total_value,
        $row->status,
        $row->created_by,
        $row->updated_by,
        $row->note
      );
      foreach ($values as $i=>$v) {
        $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r, $v);
      }
      $r++;
    }
    $allLabel = pr_t('common_all', erp_export_all_text());
    $from = pr_filter_value($_GET, 'tgl_awal', $allLabel);
    $to = pr_filter_value($_GET, 'tgl_akhir', $from === $allLabel ? $allLabel : date('Y-m-d'));
    erpkb_excel_apply_standard_style($excel, array(
      'sheet'=>$sheet,
      'title'=>pr_t('purchase_requisition_report_title','PURCHASE REQUISITION REPORT'),
      'header_row'=>4,
      'first_data_row'=>5,
      'last_data_row'=>max(5, $r-1),
      'column_count'=>count($headers),
      'numeric_columns'=>array('M','N'),
      'decimal_columns'=>array('L'),
      'money_columns'=>array('O'),
      'filters'=>array(
        pr_t('purchase_requisition_pr_date','PR Date')=>$from.' s/d '.$to,
        pr_t('common_status','Status')=>pr_filter_value($_GET,'status',$allLabel),
        pr_t('common_plant','Plant')=>pr_filter_value($_GET,'plant',$allLabel),
        pr_t('search','Search')=>pr_filter_value($_GET,'reference','')
      ),
      'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>18,'E'=>12,'F'=>24,'G'=>18,'H'=>20,'I'=>22,'J'=>12,'K'=>14,'L'=>10,'M'=>14,'N'=>14,'O'=>16,'P'=>16,'Q'=>18,'R'=>18,'S'=>36)
    ));
    $tmp = erpkb_excel_temp_file('purchase_requisition_');
    PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $signature = @file_get_contents($tmp,false,null,0,2);
    if(!$size || $signature !== 'PK'){
      @unlink($tmp);
      while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
      header('Content-Type:text/plain; charset=utf-8');
      echo pr_t('purchase_requisition_excel_failed', 'File Excel gagal dibuat dengan benar.');
      exit;
    }
    while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="purchase_requisition_'.date('Ymd_His').'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;

  case "in":
    $requiredHeader = array(
      'tgl_pr' => pr_t('purchase_requisition_pr_date','PR Date'),
      'document_type' => pr_t('purchase_requisition_document_type','Document Type'),
      'plant' => pr_t('common_plant','Plant'),
      'requestor' => pr_t('purchase_requisition_requestor','Requestor'),
      'priority' => pr_t('purchase_requisition_priority','Priority'),
      'required_date' => pr_t('purchase_requisition_required_date','Required Date')
    );
    foreach ($requiredHeader as $field => $label) {
      if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        action_response(pr_msg('purchase_requisition_required_message', '{field} wajib diisi.', array('field'=>$label)));
      }
    }
    if (empty($_POST['material_code']) || !is_array($_POST['material_code'])) {
      action_response(pr_t('purchase_requisition_min_item_required', 'Minimal satu item PR wajib diisi.'));
    }

    foreach ($_POST['material_code'] as $key => $material) {
      $lineNo = $key + 1;
      $material = trim((string) $material);
      $qty = isset($_POST['qty'][$key]) ? floatval($_POST['qty'][$key]) : 0;
      $uom = isset($_POST['uom'][$key]) ? trim((string) $_POST['uom'][$key]) : '';
      if ($material === '') action_response(pr_msg('purchase_requisition_material_required', 'Material item {line} wajib diisi.', array('line'=>$lineNo)));
      if ($qty <= 0) action_response(pr_msg('purchase_requisition_qty_positive', 'Qty item {line} wajib lebih dari nol.', array('line'=>$lineNo)));
      if ($uom === '') action_response(pr_msg('purchase_requisition_uom_required', 'UOM item {line} wajib diisi.', array('line'=>$lineNo)));
    }

    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $status = (isset($_POST['submit_mode']) && $_POST['submit_mode'] === 'SUBMITTED') ? 'SUBMITTED' : 'DRAFT';
    $noPr = pr_next_number();

    $db->query('START TRANSACTION');
    $header = array(
      'no_pr' => $noPr,
      'tgl_pr' => $_POST['tgl_pr'],
      'document_type' => $_POST['document_type'],
      'plant' => $_POST['plant'],
      'storage_location' => isset($_POST['storage_location']) ? $_POST['storage_location'] : '',
      'department' => isset($_POST['department']) ? $_POST['department'] : '',
      'requestor' => $_POST['requestor'],
      'priority' => $_POST['priority'],
      'status' => $status,
      'required_date' => $_POST['required_date'],
      'note' => isset($_POST['note']) ? $_POST['note'] : '',
      'created_by' => $username,
      'updated_by' => $username
    );

    if (!$db->insert('purchase_requisition', $header)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }
    $idPr = $db->last_insert_id();

    foreach ($_POST['material_code'] as $key => $material) {
      $qty = floatval($_POST['qty'][$key]);
      $detail = array(
        'id_pr' => $idPr,
        'line_no' => isset($_POST['line_no'][$key]) ? intval($_POST['line_no'][$key]) : (($key + 1) * 10),
        'material_code' => trim((string) $material),
        'material_name' => isset($_POST['material_name'][$key]) ? $_POST['material_name'][$key] : '',
        'material_group' => isset($_POST['material_group'][$key]) ? $_POST['material_group'][$key] : '',
        'kd_kategori' => isset($_POST['kd_kategori'][$key]) ? $_POST['kd_kategori'][$key] : '',
        'qty' => $qty,
        'qty_po' => 0,
        'qty_open' => $qty,
        'uom' => $_POST['uom'][$key],
        'required_date' => isset($_POST['item_required_date'][$key]) && $_POST['item_required_date'][$key] !== '' ? $_POST['item_required_date'][$key] : $_POST['required_date'],
        'plant' => isset($_POST['item_plant'][$key]) && $_POST['item_plant'][$key] !== '' ? $_POST['item_plant'][$key] : $_POST['plant'],
        'storage_location' => isset($_POST['item_storage_location'][$key]) ? $_POST['item_storage_location'][$key] : $_POST['storage_location'],
        'valuation_price' => isset($_POST['valuation_price'][$key]) ? $_POST['valuation_price'][$key] : 0,
        'currency' => isset($_POST['currency'][$key]) && $_POST['currency'][$key] !== '' ? $_POST['currency'][$key] : 'IDR',
        'account_assignment' => isset($_POST['account_assignment'][$key]) ? $_POST['account_assignment'][$key] : '',
        'cost_center' => isset($_POST['cost_center'][$key]) ? $_POST['cost_center'][$key] : '',
        'internal_order' => '',
        'asset_no' => '',
        'suggested_vendor' => isset($_POST['suggested_vendor'][$key]) ? $_POST['suggested_vendor'][$key] : '',
        'tracking_no' => isset($_POST['tracking_no'][$key]) ? $_POST['tracking_no'][$key] : '',
        'item_status' => 'OPEN',
        'remarks' => isset($_POST['remarks'][$key]) ? $_POST['remarks'][$key] : ''
      );
      if (!$db->insert('purchase_requisition_detail', $detail)) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    $history = array(
      'id_pr' => $idPr,
      'status_lama' => '',
      'status_baru' => $status,
      'remarks' => $status === 'SUBMITTED' ? pr_t('purchase_requisition_submitted_history','Purchase Requisition submitted for approval.') : pr_t('purchase_requisition_draft_history','Purchase Requisition saved as draft.'),
      'changed_by' => $username
    );
    if (!$db->insert('purchase_requisition_history', $history)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    if ($status === 'SUBMITTED') {
      $approver = pr_resolve_approver(isset($_POST['approver']) ? $_POST['approver'] : '', $_POST['priority']);
      if (!pr_ensure_pending_approval($idPr, $approver, pr_t('purchase_requisition_auto_approval_note','Auto-created release strategy level 1 from PR submit'))) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    $db->query('COMMIT');
    if (function_exists('simpan_log')) {
      simpan_log(pr_msg('purchase_requisition_created_log', 'User {user} membuat Purchase Requisition {no_pr} status {status} pada {datetime}', array('user'=>$username,'no_pr'=>$noPr,'status'=>$status,'datetime'=>date('Y-m-d H:i:s'))), $username);
    }
    action_response('', array('no_pr' => $noPr, 'id_pr' => $idPr));
    break;

  case "show_detail":
    $id = intval($_POST['id']);
    $header = $db->fetch("SELECT pr.*,ep.plant_name,es.storage_name
                          FROM purchase_requisition pr
                          LEFT JOIN erp_plant ep ON ep.plant_code=pr.plant
                          LEFT JOIN erp_storage_location es ON es.storage_code=pr.storage_location
                          WHERE pr.id_pr=?
                          LIMIT 1", array('id_pr' => $id));
    if (!$header) {
      echo "<div class='alert alert-warning'>".pr_h(pr_t('purchase_requisition_not_found','Purchase Requisition tidak ditemukan.'))."</div>";
      break;
    }
    ?>
    <div class="row">
      <div class="col-md-6">
        <table class="table table-condensed">
          <tr><th style="width:150px"><?=pr_h(pr_t('purchase_requisition_no_pr','No PR'));?></th><td><?=htmlspecialchars($header->no_pr,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('purchase_requisition_pr_date','PR Date'));?></th><td><?=htmlspecialchars($header->tgl_pr,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('purchase_requisition_document_type','Document Type'));?></th><td><?=htmlspecialchars($header->document_type,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('common_status','Status'));?></th><td><?=pr_status_label($header->status);?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-condensed">
          <tr><th style="width:150px"><?=pr_h(pr_t('common_plant','Plant'));?></th><td><?=htmlspecialchars($header->plant.' - '.$header->plant_name,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('purchase_order_storage_location','Storage Location'));?></th><td><?=htmlspecialchars($header->storage_location.' - '.$header->storage_name,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('purchase_requisition_requestor','Requestor'));?></th><td><?=htmlspecialchars($header->requestor,ENT_QUOTES,'UTF-8');?></td></tr>
          <tr><th><?=pr_h(pr_t('purchase_requisition_priority','Priority'));?></th><td><?=htmlspecialchars($header->priority,ENT_QUOTES,'UTF-8');?></td></tr>
        </table>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead>
          <tr class="bg-gray">
            <th><?=pr_h(pr_t('purchase_requisition_item','Item'));?></th>
            <th><?=pr_h(pr_t('purchase_requisition_material','Material'));?></th>
            <th class="text-right"><?=pr_h(pr_t('purchase_order_qty','Qty'));?></th>
            <th class="text-right"><?=pr_h(pr_t('purchase_requisition_qty_po','Qty PO'));?></th>
            <th class="text-right"><?=pr_h(pr_t('purchase_requisition_open_qty','Open Qty'));?></th>
            <th><?=pr_h(pr_t('purchase_order_uom','UOM'));?></th>
            <th><?=pr_h(pr_t('purchase_requisition_req_date','Req. Date'));?></th>
            <th><?=pr_h(pr_t('common_plant','Plant'));?></th>
            <th><?=pr_h(pr_t('purchase_order_sloc','Sloc'));?></th>
            <th><?=pr_h(pr_t('purchase_requisition_acct','Acct'));?></th>
            <th><?=pr_h(pr_t('purchase_requisition_cost_center','Cost Center'));?></th>
            <th><?=pr_h(pr_t('purchase_requisition_vendor','Vendor'));?></th>
            <th><?=pr_h(pr_t('common_status','Status'));?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = $db->query("SELECT * FROM purchase_requisition_detail WHERE id_pr=? ORDER BY line_no", array('id_pr' => $id));
          foreach ($rows as $row) {
          ?>
          <tr>
            <td><?=htmlspecialchars($row->line_no,ENT_QUOTES,'UTF-8');?></td>
            <td><strong><?=htmlspecialchars($row->material_code,ENT_QUOTES,'UTF-8');?></strong><br><span class="text-muted"><?=htmlspecialchars($row->material_name,ENT_QUOTES,'UTF-8');?></span></td>
            <td class="text-right"><?=number_format((float) $row->qty, 5, ',', '.');?></td>
            <td class="text-right"><?=number_format((float) $row->qty_po, 5, ',', '.');?></td>
            <td class="text-right"><?=number_format((float) $row->qty_open, 5, ',', '.');?></td>
            <td><?=htmlspecialchars($row->uom,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->required_date,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->plant,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->storage_location,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->account_assignment,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->cost_center,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->suggested_vendor,ENT_QUOTES,'UTF-8');?></td>
            <td><?=htmlspecialchars($row->item_status,ENT_QUOTES,'UTF-8');?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case "up":
    $idPr = isset($_POST['id_pr']) ? intval($_POST['id_pr']) : 0;
    if ($idPr <= 0) action_response(pr_t('purchase_requisition_invalid','Purchase Requisition tidak valid.'));

    $headerOld = $db->fetch("SELECT * FROM purchase_requisition WHERE id_pr=? LIMIT 1", array('id_pr' => $idPr));
    if (!$headerOld) action_response(pr_t('purchase_requisition_not_found','Purchase Requisition tidak ditemukan.'));
    if (in_array($headerOld->status, array('CONVERTED_PO','CLOSED','CANCELLED'))) {
      action_response(pr_msg('purchase_requisition_cannot_edit_status', 'Status PR {status} tidak bisa diedit.', array('status'=>$headerOld->status)));
    }

    $requiredHeader = array(
      'tgl_pr' => pr_t('purchase_requisition_pr_date','PR Date'),
      'document_type' => pr_t('purchase_requisition_document_type','Document Type'),
      'plant' => pr_t('common_plant','Plant'),
      'requestor' => pr_t('purchase_requisition_requestor','Requestor'),
      'priority' => pr_t('purchase_requisition_priority','Priority'),
      'required_date' => pr_t('purchase_requisition_required_date','Required Date')
    );
    foreach ($requiredHeader as $field => $label) {
      if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
        action_response(pr_msg('purchase_requisition_required_message', '{field} wajib diisi.', array('field'=>$label)));
      }
    }
    if (empty($_POST['material_code']) || !is_array($_POST['material_code'])) {
      action_response(pr_t('purchase_requisition_min_item_required', 'Minimal satu item PR wajib diisi.'));
    }

    foreach ($_POST['material_code'] as $key => $material) {
      $lineNo = $key + 1;
      $material = trim((string) $material);
      $qty = isset($_POST['qty'][$key]) ? floatval($_POST['qty'][$key]) : 0;
      $uom = isset($_POST['uom'][$key]) ? trim((string) $_POST['uom'][$key]) : '';
      if ($material === '') action_response(pr_msg('purchase_requisition_material_required', 'Material item {line} wajib diisi.', array('line'=>$lineNo)));
      if ($qty <= 0) action_response(pr_msg('purchase_requisition_qty_positive', 'Qty item {line} wajib lebih dari nol.', array('line'=>$lineNo)));
      if ($uom === '') action_response(pr_msg('purchase_requisition_uom_required', 'UOM item {line} wajib diisi.', array('line'=>$lineNo)));
    }

    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $newStatus = isset($_POST['submit_mode']) ? $_POST['submit_mode'] : $headerOld->status;
    if (!in_array($newStatus, array('DRAFT','SUBMITTED'))) {
      $newStatus = $headerOld->status;
    }

    $db->query('START TRANSACTION');

    $existingRows = $db->query("SELECT * FROM purchase_requisition_detail WHERE id_pr=? FOR UPDATE", array('id_pr' => $idPr));
    $existing = array();
    if ($existingRows) {
      foreach ($existingRows as $row) {
        $existing[$row->id_pr_detail] = $row;
      }
    }

    $postedIds = array();
    if (!empty($_POST['id_pr_detail']) && is_array($_POST['id_pr_detail'])) {
      foreach ($_POST['id_pr_detail'] as $postedId) {
        $postedId = intval($postedId);
        if ($postedId > 0) $postedIds[] = $postedId;
      }
    }

    foreach ($existing as $existingId => $row) {
      if (!in_array($existingId, $postedIds)) {
        if ((float) $row->qty_po > 0) {
          $db->query('ROLLBACK');
          action_response(pr_msg('purchase_requisition_item_delete_has_po', 'Item {line} sudah memiliki PO, tidak bisa dihapus.', array('line'=>$row->line_no)));
        }
        $deleted = $db->query("DELETE FROM purchase_requisition_detail WHERE id_pr_detail=? AND id_pr=?", array('id_pr_detail' => $existingId, 'id_pr' => $idPr));
        if (!$deleted) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          action_response($error);
        }
      }
    }

    $header = array(
      'tgl_pr' => $_POST['tgl_pr'],
      'document_type' => $_POST['document_type'],
      'plant' => $_POST['plant'],
      'storage_location' => isset($_POST['storage_location']) ? $_POST['storage_location'] : '',
      'department' => isset($_POST['department']) ? $_POST['department'] : '',
      'requestor' => $_POST['requestor'],
      'priority' => $_POST['priority'],
      'status' => $newStatus,
      'required_date' => $_POST['required_date'],
      'note' => isset($_POST['note']) ? $_POST['note'] : '',
      'updated_by' => $username
    );
    if (!$db->update('purchase_requisition', $header, 'id_pr', $idPr)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    foreach ($_POST['material_code'] as $key => $material) {
      $detailId = isset($_POST['id_pr_detail'][$key]) ? intval($_POST['id_pr_detail'][$key]) : 0;
      $qty = floatval($_POST['qty'][$key]);
      $qtyPo = 0;
      if ($detailId > 0) {
        if (!isset($existing[$detailId])) {
          $db->query('ROLLBACK');
          action_response(pr_t('purchase_requisition_item_invalid','Item PR tidak valid.'));
        }
        $qtyPo = (float) $existing[$detailId]->qty_po;
        if ($qty + 0.00001 < $qtyPo) {
          $db->query('ROLLBACK');
          action_response(pr_msg('purchase_requisition_qty_less_than_po', 'Qty item {line} tidak boleh lebih kecil dari Qty PO {qty_po}.', array('line'=>$_POST['line_no'][$key], 'qty_po'=>$qtyPo)));
        }
      }
      $qtyOpen = $qty - $qtyPo;
      $itemStatus = 'OPEN';
      if ($qtyPo > 0 && $qtyOpen > 0) $itemStatus = 'PARTIAL_PO';
      if ($qtyPo > 0 && $qtyOpen <= 0.00001) $itemStatus = 'CONVERTED_PO';

      $detail = array(
        'id_pr' => $idPr,
        'line_no' => isset($_POST['line_no'][$key]) ? intval($_POST['line_no'][$key]) : (($key + 1) * 10),
        'material_code' => trim((string) $material),
        'material_name' => isset($_POST['material_name'][$key]) ? $_POST['material_name'][$key] : '',
        'material_group' => isset($_POST['material_group'][$key]) ? $_POST['material_group'][$key] : '',
        'kd_kategori' => isset($_POST['kd_kategori'][$key]) ? $_POST['kd_kategori'][$key] : '',
        'qty' => $qty,
        'qty_po' => $qtyPo,
        'qty_open' => max($qtyOpen, 0),
        'uom' => $_POST['uom'][$key],
        'required_date' => isset($_POST['item_required_date'][$key]) && $_POST['item_required_date'][$key] !== '' ? $_POST['item_required_date'][$key] : $_POST['required_date'],
        'plant' => isset($_POST['item_plant'][$key]) && $_POST['item_plant'][$key] !== '' ? $_POST['item_plant'][$key] : $_POST['plant'],
        'storage_location' => isset($_POST['item_storage_location'][$key]) ? $_POST['item_storage_location'][$key] : $_POST['storage_location'],
        'valuation_price' => isset($_POST['valuation_price'][$key]) ? $_POST['valuation_price'][$key] : 0,
        'currency' => isset($_POST['currency'][$key]) && $_POST['currency'][$key] !== '' ? $_POST['currency'][$key] : 'IDR',
        'account_assignment' => isset($_POST['account_assignment'][$key]) ? $_POST['account_assignment'][$key] : '',
        'cost_center' => isset($_POST['cost_center'][$key]) ? $_POST['cost_center'][$key] : '',
        'internal_order' => '',
        'asset_no' => '',
        'suggested_vendor' => isset($_POST['suggested_vendor'][$key]) ? $_POST['suggested_vendor'][$key] : '',
        'tracking_no' => isset($_POST['tracking_no'][$key]) ? $_POST['tracking_no'][$key] : '',
        'item_status' => $itemStatus,
        'remarks' => isset($_POST['remarks'][$key]) ? $_POST['remarks'][$key] : ''
      );
      if ($detailId > 0) {
        if (!$db->update('purchase_requisition_detail', $detail, 'id_pr_detail', $detailId)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          action_response($error);
        }
      } else {
        if (!$db->insert('purchase_requisition_detail', $detail)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          action_response($error);
        }
      }
    }

    if ($headerOld->status !== $newStatus) {
      $db->insert('purchase_requisition_history', array(
        'id_pr' => $idPr,
        'status_lama' => $headerOld->status,
        'status_baru' => $newStatus,
        'remarks' => pr_msg('purchase_requisition_status_changed_history', 'Purchase Requisition updated. Status changed from {old_status} to {new_status}.', array('old_status'=>$headerOld->status, 'new_status'=>$newStatus)),
        'changed_by' => $username
      ));
    } else {
      $db->insert('purchase_requisition_history', array(
        'id_pr' => $idPr,
        'status_lama' => $headerOld->status,
        'status_baru' => $newStatus,
        'remarks' => pr_t('purchase_requisition_updated_history','Purchase Requisition data updated.'),
        'changed_by' => $username
      ));
    }

    if ($newStatus === 'SUBMITTED') {
      $approver = pr_resolve_approver(isset($_POST['approver']) ? $_POST['approver'] : '', $_POST['priority']);
      if (!pr_ensure_pending_approval($idPr, $approver, pr_t('purchase_requisition_update_approval_note','Release strategy level 1 after PR update/submit'))) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($error);
      }
    }

    $db->query('COMMIT');
    if (function_exists('simpan_log')) {
      simpan_log(pr_msg('purchase_requisition_updated_log', 'User {user} update Purchase Requisition {no_pr} pada {datetime}', array('user'=>$username, 'no_pr'=>$headerOld->no_pr, 'datetime'=>date('Y-m-d H:i:s'))), $username);
    }
    action_response('', array('id_pr' => $idPr, 'no_pr' => $headerOld->no_pr));
    break;

  case "cancel":
  case "delete":
    $id = intval(isset($_POST['id']) ? $_POST['id'] : $_GET['id']);
    $header = $db->fetch("SELECT * FROM purchase_requisition WHERE id_pr=? LIMIT 1", array('id_pr' => $id));
    if (!$header) action_response(pr_t('purchase_requisition_not_found','Purchase Requisition tidak ditemukan.'));
    if (in_array($header->status, array('CONVERTED_PO','CLOSED'))) {
      action_response(pr_t('purchase_requisition_cancel_not_allowed','PR yang sudah converted/closed tidak bisa dicancel.'));
    }
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $db->update('purchase_requisition', array('status' => 'CANCELLED', 'updated_by' => $username), 'id_pr', $id);
    $db->query("UPDATE purchase_requisition_detail SET item_status='CANCELLED' WHERE id_pr=?", array('id_pr' => $id));
    $db->insert('purchase_requisition_history', array(
      'id_pr' => $id,
      'status_lama' => $header->status,
      'status_baru' => 'CANCELLED',
      'remarks' => pr_t('purchase_requisition_cancelled_history','Purchase Requisition cancelled.'),
      'changed_by' => $username
    ));
    action_response($db->getErrorMessage());
    break;

  default:
    action_response(pr_t('purchase_requisition_unknown_action','Aksi tidak dikenal.'));
    break;
}
?>
