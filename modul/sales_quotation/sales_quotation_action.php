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
include "sales_quotation_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';

function sq_json($payload) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function sq_post_array($key) {
  return isset($_POST[$key]) && is_array($_POST[$key]) ? $_POST[$key] : array();
}

function sq_has_valid_item() {
  $materials = sq_post_array('kd_barang');
  $qtys = sq_post_array('qty');
  $uoms = sq_post_array('uom');
  foreach ($qtys as $i=>$rawQty) {
    $qty = (float)str_replace(',', '.', $rawQty);
    $mat = isset($materials[$i]) ? trim((string)$materials[$i]) : '';
    $uom = isset($uoms[$i]) ? trim((string)$uoms[$i]) : '';
    if ($qty > 0 && $mat !== '' && $uom !== '') return true;
  }
  return false;
}

function sq_save_items($db, $quotationId, $currency) {
  $materials = sq_post_array('kd_barang');
  $qtys = sq_post_array('qty');
  $uoms = sq_post_array('uom');
  $prices = sq_post_array('price');
  $discounts = sq_post_array('discount_percent');
  $taxes = sq_post_array('tax_percent');
  $dates = sq_post_array('item_delivery_date');
  $remarks = sq_post_array('ket');
  $line = 10; $saved = 0;
  foreach ($materials as $i=>$material) {
    $material = trim((string)$material);
    $qty = isset($qtys[$i]) ? (float)str_replace(',', '.', $qtys[$i]) : 0;
    $uom = isset($uoms[$i]) ? trim((string)$uoms[$i]) : '';
    if ($material === '' || $qty <= 0 || $uom === '') continue;
    $price = isset($prices[$i]) ? (float)str_replace(',', '.', $prices[$i]) : 0;
    $disc = isset($discounts[$i]) ? (float)str_replace(',', '.', $discounts[$i]) : 0;
    $tax = isset($taxes[$i]) ? (float)str_replace(',', '.', $taxes[$i]) : 0;
    $gross = $qty * $price;
    $afterDisc = $gross - ($gross * $disc / 100);
    $amount = $afterDisc + ($afterDisc * $tax / 100);
    $deliveryDate = isset($dates[$i]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dates[$i]) ? $dates[$i] : null;
    $db->insert('sales_quotation_detail', array(
      'id_quotation'=>$quotationId,
      'line_no'=>$line,
      'kd_barang'=>$material,
      'valuta'=>$currency,
      'qty'=>$qty,
      'uom'=>$uom,
      'price'=>$price,
      'discount_percent'=>$disc,
      'tax_percent'=>$tax,
      'nilai'=>$amount,
      'requested_delivery_date'=>$deliveryDate,
      'ket'=>isset($remarks[$i]) ? trim((string)$remarks[$i]) : '',
      'status_stock'=>'OPEN'
    ));
    $line += 10; $saved++;
  }
  return $saved;
}

if ($act === 'customer_search') {
  session_check_json();
  $term = sq_input('term');
  $params = array(); $where = " WHERE 1=1 ";
  if ($term !== '') {
    $where .= " AND (nama LIKE ? OR kode_pemasok LIKE ? OR email LIKE ? OR notelp LIKE ?) ";
    for ($i=0;$i<4;$i++) $params[] = '%'.$term.'%';
  }
  $rows = $db->query("SELECT id_customer,kode_pemasok,nama,email,notelp,alamat FROM customer $where ORDER BY nama LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) {
    $results[] = array('id'=>$row->id_customer,'text'=>trim((string)$row->kode_pemasok.' - '.(string)$row->nama,' -'),'code'=>$row->kode_pemasok,'name'=>$row->nama,'email'=>$row->email,'phone'=>$row->notelp,'address'=>$row->alamat);
  }
  sq_json(array('results'=>$results));
}

if ($act === 'material_search') {
  session_check_json();
  $term = sq_input('term');
  $params = array(); $where = " WHERE COALESCE(status,1)=1 ";
  if ($term !== '') { $where .= " AND (kd_barang LIKE ? OR nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT kd_barang,nm_barang,satuan FROM barang $where ORDER BY kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang,'uom'=>$row->satuan,'name'=>$row->nm_barang);
  sq_json(array('results'=>$results));
}

if ($act === 'inquiry_search') {
  session_check_json();
  $term = sq_input('term');
  $params = array();
  $where = " WHERE si.status IN ('OPEN','QUOTED') ";
  if ($term !== '') {
    $where .= " AND (si.inquiry_no LIKE ? OR si.customer_name LIKE ? OR si.subject LIKE ?) ";
    $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; $params[]='%'.$term.'%';
  }
  $rows = $db->query("SELECT si.* FROM sales_inquiry si $where ORDER BY si.inquiry_date DESC LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->id,'text'=>$row->inquiry_no.' - '.$row->customer_name.' - '.$row->subject,'customer_id'=>$row->customer_id,'customer_text'=>trim((string)$row->customer_code.' - '.(string)$row->customer_name,' -'),'subject'=>$row->subject,'contact_person'=>$row->contact_person,'phone'=>$row->phone,'email'=>$row->email,'requested_delivery_date'=>$row->requested_delivery_date,'currency'=>$row->currency,'payment_term'=>$row->payment_term);
  sq_json(array('results'=>$results));
}

if ($act === 'inquiry_items') {
  session_check_json();
  $id = (int)sq_input('inquiry_id');
  $rows = $db->query("SELECT d.*,b.nm_barang,b.satuan FROM sales_inquiry_detail d LEFT JOIN barang b ON b.kd_barang=d.material_code WHERE d.inquiry_id=? ORDER BY d.line_no,d.id", array($id));
  $items = array();
  foreach ($rows as $row) $items[] = array('material_code'=>$row->material_code,'material_name'=>$row->material_name ?: $row->nm_barang,'description'=>$row->description,'qty'=>$row->qty,'uom'=>$row->uom ?: $row->satuan,'target_price'=>$row->target_price,'requested_delivery_date'=>$row->requested_delivery_date,'remarks'=>$row->remarks);
  sq_json(array('items'=>$items));
}

if ($act === 'save' || $act === 'update') {
  session_check_json();
  $username = sq_username();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
  $customer = $customerId > 0 ? $db->fetch("SELECT * FROM customer WHERE id_customer=? LIMIT 1", array($customerId)) : null;
  if (!$customer) sq_json(array('status'=>'error','error_message'=>'Customer wajib dipilih.'));
  if (trim(sq_input('subject')) === '') sq_json(array('status'=>'error','error_message'=>'Subject quotation wajib diisi.'));
  if (!sq_has_valid_item()) sq_json(array('status'=>'error','error_message'=>'Minimal satu item wajib diisi dengan material, qty, dan UOM.'));

  $quotationDate = sq_valid_date(sq_input('tgl'), date('Y-m-d'));
  $validDate = sq_input('valid_date') !== '' ? sq_valid_date(sq_input('valid_date'), '') : null;
  $requestedDelivery = sq_input('requested_delivery_date') !== '' ? sq_valid_date(sq_input('requested_delivery_date'), '') : null;
  $currency = sq_input('currency', 'IDR') ?: 'IDR';
  $termText = sq_input('payment_term');
  $termNumber = preg_match('/^\d+$/', $termText) ? (int)$termText : null;
  $header = array(
    'inquiry_id'=>sq_input('inquiry_id') !== '' ? (int)sq_input('inquiry_id') : null,
    'kode_penerima'=>$customer->kode_pemasok,
    'customer_id'=>$customerId,
    'customer_name'=>$customer->nama,
    'tgl'=>$quotationDate,
    'currency'=>$currency,
    'rupiah_rate'=>(float)(sq_input('rupiah_rate') ?: 1),
    'rupiah_rate_sale'=>(float)(sq_input('rupiah_rate_sale') ?: 1),
    'tax'=>sq_input('tax', 'EXCLUDE'),
    'tax_item'=>sq_input('tax_item'),
    'sales_id'=>sq_input('sales_id') ?: $username,
    'user'=>$username,
    'term'=>$termNumber,
    'payment_term'=>$termText,
    'incoterm'=>sq_input('incoterm'),
    'valid_date'=>$validDate,
    'requested_delivery_date'=>$requestedDelivery,
    'status'=>sq_input('status', 'OPEN'),
    'contact_person'=>sq_input('contact_person'),
    'subject'=>sq_input('subject'),
    'catatan'=>sq_input('catatan'),
    'updated_by'=>$username,
    'updated_at'=>date('Y-m-d H:i:s'),
    'rejected_reason'=>sq_input('rejected_reason')
  );
  if ($act === 'save') {
    $header['no_sales_quotation'] = sq_next_no($db);
    $header['created_by'] = $username;
    if (!$db->insert('sales_quotation', $header)) sq_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
    $id = (int)$db->last_insert_id();
  } else {
    if ($id <= 0) sq_json(array('status'=>'error','error_message'=>'Quotation tidak valid.'));
    if (!$db->update('sales_quotation', $header, 'id_quotation', $id)) sq_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
    $db->query("DELETE FROM sales_quotation_detail WHERE id_quotation=?", array($id));
  }
  $itemCount = sq_save_items($db, $id, $currency);
  if ($itemCount <= 0) sq_json(array('status'=>'error','error_message'=>'Minimal satu item quotation wajib diisi.'));
  $saved = $db->fetch_single_row('sales_quotation','id_quotation',$id);
  if (!empty($saved->inquiry_id)) $db->update('sales_inquiry', array('status'=>'QUOTED','quotation_id'=>$id,'quotation_no'=>$saved->no_sales_quotation,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')), 'id', $saved->inquiry_id);
  if (function_exists('simpan_log')) {
    $verb = $act === 'save' ? 'membuat' : 'mengubah';
    simpan_log('User '.$username.' '.$verb.' Sales Quotation '.$saved->no_sales_quotation.' untuk customer '.$saved->customer_name.' dengan '.$itemCount.' item pada '.date('Y-m-d H:i:s'), $username);
  }
  sq_json(array('status'=>'good','id'=>$id,'quotation_no'=>$saved->no_sales_quotation));
}

if ($act === 'status') {
  session_check_json();
  $username = sq_username();
  $id = (int)sq_input('id');
  $status = sq_input('status');
  $allowed = array('OPEN','SENT','ACCEPTED','REJECTED','EXPIRED','CANCELLED');
  if ($id <= 0 || !in_array($status, $allowed)) sq_json(array('status'=>'error','error_message'=>'Status quotation tidak valid.'));
  $extra = array('status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
  if ($status === 'ACCEPTED') $extra['accepted_at'] = date('Y-m-d H:i:s');
  $db->update('sales_quotation', $extra, 'id_quotation', $id);
  $row = $db->fetch_single_row('sales_quotation','id_quotation',$id);
  if (function_exists('simpan_log') && $row) simpan_log('User '.$username.' mengubah status Sales Quotation '.$row->no_sales_quotation.' menjadi '.$status.' pada '.date('Y-m-d H:i:s'), $username);
  sq_json(array('status'=>'good'));
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = sq_filters();
  $rows = sq_load_rows($db, $input);
  $from = sq_valid_date($input['tgl_awal'], date('Y-m-01'));
  $to = sq_valid_date($input['tgl_akhir'], date('Y-m-d'));
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Sales Quotation'));
  $headers = array(erp_export_label("No"),erp_export_label("Quotation No"),erp_export_label("Date"),erp_export_label("Valid Until"),erp_export_label("Requested Delivery"),erp_export_label("Customer Code"),erp_export_label("Customer"),erp_export_label("Contact"),erp_export_label("Subject"),erp_export_label("Status"),erp_export_label("Sales"),erp_export_label("Currency"),erp_export_label("Tax"),erp_export_label("Payment Term"),erp_export_label("Item Count"),erp_export_label("Total Qty"),erp_export_label("Amount"),erp_export_label("Inquiry"),erp_export_label("Remarks"));
  foreach ($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1;
  foreach ($rows as $row) {
    $values = array($n++,$row->no_sales_quotation,$row->tgl,$row->valid_date,$row->requested_delivery_date,$row->kode_penerima,$row->customer_display,$row->contact_person,$row->subject,$row->status,$row->sales_id,$row->currency,$row->tax,$row->payment_term,(float)$row->item_count,(float)$row->total_qty,(float)$row->total_amount,$row->inquiry_id,$row->catatan);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('SALES QUOTATION REPORT - SAP SD'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5,$r-1),
    'column_count'=>19,
    'numeric_columns'=>array('P'),
    'money_columns'=>array('Q'),
    'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer_id'] ?: erp_export_all_text(),'Status'=>$input['status'] ?: erp_export_all_text(),'Sales'=>$input['sales_person'] ?: erp_export_all_text(),'Keyword'=>$input['keyword']),
    'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>16,'F'=>16,'G'=>28,'H'=>18,'I'=>35,'J'=>12,'K'=>16,'L'=>10,'M'=>12,'N'=>18,'O'=>10,'P'=>12,'Q'=>16,'R'=>12,'S'=>40)
  ));
  $tmp = erpkb_excel_temp_file('sales_quotation_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size = @filesize($tmp); $signature = @file_get_contents($tmp,false,null,0,2);
  if(!$size || $signature !== 'PK'){ @unlink($tmp); while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="sales_quotation_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

sq_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
