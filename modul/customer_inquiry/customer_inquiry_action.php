<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "customer_inquiry_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';

function ciq_json($payload) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function ciq_post_array($key) {
  return isset($_POST[$key]) && is_array($_POST[$key]) ? $_POST[$key] : array();
}

function ciq_save_items($db, $inquiryId) {
  $materials = ciq_post_array('material_code');
  $descs = ciq_post_array('description');
  $qtys = ciq_post_array('qty');
  $uoms = ciq_post_array('uom');
  $prices = ciq_post_array('target_price');
  $dates = ciq_post_array('item_delivery_date');
  $remarks = ciq_post_array('item_remarks');
  $line = 10;
  $saved = 0;
  foreach ($materials as $i => $material) {
    $material = trim((string)$material);
    $desc = isset($descs[$i]) ? trim((string)$descs[$i]) : '';
    $qty = isset($qtys[$i]) ? (float)str_replace(',', '.', $qtys[$i]) : 0;
    if ($material === '' && $desc === '' && $qty <= 0) continue;
    if ($qty <= 0) continue;
    $m = $material !== '' ? $db->fetch("SELECT kd_barang,nm_barang,satuan FROM barang WHERE kd_barang=? LIMIT 1", array($material)) : null;
    $uom = isset($uoms[$i]) ? trim((string)$uoms[$i]) : '';
    if ($uom === '' && $m) $uom = $m->satuan;
    $price = isset($prices[$i]) ? (float)str_replace(',', '.', $prices[$i]) : 0;
    $amount = $qty * $price;
    $deliveryDate = isset($dates[$i]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dates[$i]) ? $dates[$i] : null;
    $db->insert('sales_inquiry_detail', array(
      'inquiry_id' => $inquiryId,
      'line_no' => $line,
      'material_code' => $material,
      'material_name' => $m ? $m->nm_barang : '',
      'description' => $desc,
      'qty' => $qty,
      'uom' => $uom,
      'target_price' => $price,
      'estimated_amount' => $amount,
      'requested_delivery_date' => $deliveryDate,
      'remarks' => isset($remarks[$i]) ? trim((string)$remarks[$i]) : ''
    ));
    $line += 10;
    $saved++;
  }
  return $saved;
}

function ciq_has_valid_item() {
  $materials = ciq_post_array('material_code');
  $descs = ciq_post_array('description');
  $qtys = ciq_post_array('qty');
  $uoms = ciq_post_array('uom');
  foreach ($qtys as $i => $rawQty) {
    $qty = (float)str_replace(',', '.', $rawQty);
    $material = isset($materials[$i]) ? trim((string)$materials[$i]) : '';
    $desc = isset($descs[$i]) ? trim((string)$descs[$i]) : '';
    $uom = isset($uoms[$i]) ? trim((string)$uoms[$i]) : '';
    if ($qty > 0 && $uom !== '' && ($material !== '' || $desc !== '')) return true;
  }
  return false;
}

if ($act === 'customer_search') {
  session_check_json();
  $term = ciq_input('term');
  $params = array();
  $where = " WHERE 1=1 ";
  if ($term !== '') {
    $where .= " AND (nama LIKE ? OR kode_pemasok LIKE ? OR email LIKE ? OR notelp LIKE ?) ";
    for ($i = 0; $i < 4; $i++) $params[] = '%'.$term.'%';
  }
  $rows = $db->query("SELECT id_customer,kode_pemasok,nama,email,notelp,alamat FROM customer $where ORDER BY nama LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) {
    $results[] = array(
      'id' => $row->id_customer,
      'text' => trim((string)$row->kode_pemasok.' - '.(string)$row->nama, ' -'),
      'code' => $row->kode_pemasok,
      'name' => $row->nama,
      'email' => $row->email,
      'phone' => $row->notelp,
      'address' => $row->alamat
    );
  }
  ciq_json(array('results'=>$results));
}

if ($act === 'material_search') {
  session_check_json();
  $term = ciq_input('term');
  $params = array();
  $where = " WHERE COALESCE(status,1)=1 ";
  if ($term !== '') {
    $where .= " AND (kd_barang LIKE ? OR nm_barang LIKE ?) ";
    $params[] = '%'.$term.'%';
    $params[] = '%'.$term.'%';
  }
  $rows = $db->query("SELECT kd_barang,nm_barang,satuan FROM barang $where ORDER BY kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) {
    $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang,'uom'=>$row->satuan,'name'=>$row->nm_barang);
  }
  ciq_json(array('results'=>$results));
}

if ($act === 'save' || $act === 'update') {
  session_check_json();
  $username = ciq_username();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
  $customer = $customerId > 0 ? $db->fetch("SELECT * FROM customer WHERE id_customer=? LIMIT 1", array($customerId)) : null;
  $inquiryDate = ciq_valid_date(ciq_input('inquiry_date'), date('Y-m-d'));
  $validUntil = ciq_input('valid_until') !== '' ? ciq_valid_date(ciq_input('valid_until'), '') : null;
  $requestedDelivery = ciq_input('requested_delivery_date') !== '' ? ciq_valid_date(ciq_input('requested_delivery_date'), '') : null;

  if (!$customer) ciq_json(array('status'=>'error','error_message'=>'Customer wajib dipilih.'));
  if (trim(ciq_input('subject')) === '') ciq_json(array('status'=>'error','error_message'=>'Subject inquiry wajib diisi.'));
  if (!ciq_has_valid_item()) ciq_json(array('status'=>'error','error_message'=>'Minimal satu item inquiry wajib diisi dengan qty lebih dari 0 dan UOM.'));

  $header = array(
    'inquiry_date' => $inquiryDate,
    'valid_until' => $validUntil,
    'requested_delivery_date' => $requestedDelivery,
    'customer_id' => $customerId,
    'customer_code' => $customer->kode_pemasok,
    'customer_name' => $customer->nama,
    'contact_person' => ciq_input('contact_person'),
    'phone' => ciq_input('phone') ?: $customer->notelp,
    'email' => ciq_input('email') ?: $customer->email,
    'sales_person' => ciq_input('sales_person') ?: $username,
    'priority' => ciq_input('priority', 'NORMAL'),
    'status' => ciq_input('status', 'OPEN'),
    'source' => ciq_input('source'),
    'currency' => ciq_input('currency', 'IDR') ?: 'IDR',
    'incoterm' => ciq_input('incoterm'),
    'payment_term' => ciq_input('payment_term'),
    'subject' => ciq_input('subject'),
    'remarks' => ciq_input('remarks'),
    'lost_reason' => ciq_input('lost_reason'),
    'updated_by' => $username,
    'updated_at' => date('Y-m-d H:i:s')
  );

  if ($act === 'save') {
    $header['inquiry_no'] = ciq_next_no($db);
    $header['created_by'] = $username;
    $header['created_at'] = date('Y-m-d H:i:s');
    if (!$db->insert('sales_inquiry', $header)) ciq_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
    $id = (int)$db->last_insert_id();
  } else {
    if ($id <= 0) ciq_json(array('status'=>'error','error_message'=>'Inquiry tidak valid.'));
    if (!$db->update('sales_inquiry', $header, 'id', $id)) ciq_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
    $db->query("DELETE FROM sales_inquiry_detail WHERE inquiry_id=?", array($id));
  }

  $itemCount = ciq_save_items($db, $id);
  if ($itemCount <= 0) ciq_json(array('status'=>'error','error_message'=>'Minimal satu item inquiry wajib diisi dengan qty lebih dari 0.'));

  $saved = $db->fetch_single_row('sales_inquiry', 'id', $id);
  if (function_exists('simpan_log')) {
    $verb = $act === 'save' ? 'membuat' : 'mengubah';
    simpan_log('User '.$username.' '.$verb.' Customer Inquiry '.$saved->inquiry_no.' untuk customer '.$saved->customer_name.' dengan '.$itemCount.' item pada '.date('Y-m-d H:i:s'), $username);
  }
  ciq_json(array('status'=>'good','id'=>$id,'inquiry_no'=>$saved->inquiry_no));
}

if ($act === 'status') {
  session_check_json();
  $username = ciq_username();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $status = ciq_input('status');
  $allowed = array('OPEN','QUOTED','WON','LOST','CANCELLED');
  if ($id <= 0 || !in_array($status, $allowed)) ciq_json(array('status'=>'error','error_message'=>'Status inquiry tidak valid.'));
  $db->update('sales_inquiry', array('status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')), 'id', $id);
  $row = $db->fetch_single_row('sales_inquiry', 'id', $id);
  if (function_exists('simpan_log') && $row) simpan_log('User '.$username.' mengubah status Customer Inquiry '.$row->inquiry_no.' menjadi '.$status.' pada '.date('Y-m-d H:i:s'), $username);
  ciq_json(array('status'=>'good'));
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = ciq_filters();
  $rows = ciq_load_rows($db, $input);
  $from = ciq_valid_date($input['tgl_awal'], date('Y-m-01'));
  $to = ciq_valid_date($input['tgl_akhir'], date('Y-m-d'));

  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Customer Inquiry'));
  $headers = array(erp_export_label("No"),erp_export_label("Inquiry No"),erp_export_label("Inquiry Date"),erp_export_label("Valid Until"),erp_export_label("Requested Delivery"),erp_export_label("Customer Code"),erp_export_label("Customer"),erp_export_label("Contact"),erp_export_label("Phone"),erp_export_label("Email"),erp_export_label("Subject"),erp_export_label("Priority"),erp_export_label("Status"),erp_export_label("Sales"),erp_export_label("Currency"),erp_export_label("Source"),erp_export_label("Item Count"),erp_export_label("Total Qty"),erp_export_label("Estimated Amount"),erp_export_label("Remarks"));
  foreach ($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r = 5; $n = 1;
  foreach ($rows as $row) {
    $values = array($n++,$row->inquiry_no,$row->inquiry_date,$row->valid_until,$row->requested_delivery_date,$row->customer_code,$row->customer_display,$row->contact_person,$row->phone,$row->email,$row->subject,$row->priority,$row->status,$row->sales_person,$row->currency,$row->source,(float)$row->item_count,(float)$row->total_qty,(float)$row->total_amount,$row->remarks);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('CUSTOMER INQUIRY REPORT - SAP SD'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5,$r-1),
    'column_count'=>20,
    'numeric_columns'=>array('R'),
    'money_columns'=>array('S'),
    'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer_id'] ?: erp_export_all_text(),'Status'=>$input['status'] ?: erp_export_all_text(),'Priority'=>$input['priority'] ?: erp_export_all_text(),'Sales'=>$input['sales_person'] ?: erp_export_all_text(),'Keyword'=>$input['keyword']),
    'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>16,'F'=>16,'G'=>28,'H'=>18,'I'=>16,'J'=>24,'K'=>35,'L'=>12,'M'=>12,'N'=>18,'O'=>10,'P'=>14,'Q'=>10,'R'=>12,'S'=>16,'T'=>40)
  ));
  $tmp = erpkb_excel_temp_file('customer_inquiry_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size = @filesize($tmp); $signature = @file_get_contents($tmp,false,null,0,2);
  if(!$size || $signature !== 'PK'){ @unlink($tmp); while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="customer_inquiry_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

ciq_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
