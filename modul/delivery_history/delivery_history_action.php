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
include "delivery_history_lib.php";
session_check_json();

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'detail') {
  dh_render_detail($db, (int)dh_input('id'));
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level();
  ob_start();
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = dh_filters();
  $rows = dh_load_rows($db, $input);
  $from = dh_date($input['tgl_awal'], date('Y-01-01'));
  $to = dh_date($input['tgl_akhir'], date('Y-m-d'));
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Delivery History'));
  $headers = array(
    erp_export_label("No"),erp_export_label("Delivery No"),erp_export_label("Delivery Date"),erp_export_label("Planned GI"),erp_export_label("Sales Order"),erp_export_label("Customer"),erp_export_label("Delivery Status"),erp_export_label("Picking Status"),erp_export_label("Packing Status"),erp_export_label("GI Status"),
    erp_export_label("Picking No"),erp_export_label("Packing List"),erp_export_label("Surat Jalan"),erp_export_label("GI No"),erp_export_label("Items"),erp_export_label("Delivery Qty"),erp_export_label("Picked Qty"),erp_export_label("Packed Qty"),erp_export_label("GI Qty"),erp_export_label("GI %"),erp_export_label("Delivery Value"),
    erp_export_label("Shipping Point"),erp_export_label("Vehicle"),erp_export_label("Driver"),erp_export_label("Remarks")
  );
  foreach ($headers as $c => $h) $sheet->setCellValueByColumnAndRow($c, 4, $h);
  $r = 5; $n = 1;
  foreach ($rows as $row) {
    $vals = array(
      $n++,$row->delivery_no,$row->delivery_date,$row->planned_gi_date,$row->no_sales_order,$row->customer_name,$row->status,$row->picking_status,$row->packing_status,$row->gi_status,
      $row->picking_nos,$row->packing_nos,$row->no_surat_jalan,$row->gi_nos,(float)$row->item_count,(float)$row->delivery_qty,(float)$row->picked_qty,(float)$row->packed_qty,(float)$row->gi_qty,(float)$row->gi_percent,(float)$row->delivery_amount,
      $row->shipping_point,$row->vehicle_no,$row->driver_name,$row->remarks
    );
    foreach ($vals as $c => $v) $sheet->setCellValueByColumnAndRow($c, $r, $v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('DELIVERY HISTORY REPORT - SAP SD DOCUMENT FLOW'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5,$r-1),
    'column_count'=>25,
    'numeric_columns'=>array('P','Q','R','S'),
    'decimal_columns'=>array('T'),
    'money_columns'=>array('U'),
    'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer']?:erp_export_all_text(),'Status'=>$input['status']?:erp_export_all_text(),'Shipping Point'=>$input['shipping_point']?:erp_export_all_text(),'Keyword'=>$input['keyword']),
    'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>20,'F'=>28,'G'=>14,'H'=>16,'I'=>16,'J'=>14,'K'=>24,'L'=>24,'M'=>24,'N'=>24,'O'=>10,'P'=>14,'Q'=>14,'R'=>14,'S'=>14,'T'=>10,'U'=>16,'V'=>18,'W'=>16,'X'=>18,'Y'=>40)
  ));
  $tmp = erpkb_excel_temp_file('delivery_history_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') {
    @unlink($tmp);
    while (ob_get_level() > $initial) ob_end_clean();
    header('Content-Type:text/plain; charset=utf-8');
    echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
    exit;
  }
  while (ob_get_level() > $initial) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="delivery_history_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
