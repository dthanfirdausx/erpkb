<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cfmp_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function cfmp_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function cfmp_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function cfmp_num($value) { return number_format((float)$value, 2, '.', ','); }
function cfmp_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function cfmp_lower($value) { return strtolower(trim((string)$value)); }
function cfmp_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function cfmp_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function cfmp_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function cfmp_filters()
{
  $start = cfmp_req('start_month', date('Y-m'));
  $end = cfmp_req('end_month', date('Y-m'));
  if (!cfmp_month_ok($start) || !cfmp_month_ok($end)) throw new Exception(cfmp_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(cfmp_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = cfmp_months($start, $end);
  if (count($months) > 12) throw new Exception(cfmp_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function cfmp_table_exists($db, $table)
{
  $row = $db->fetch("SHOW TABLES LIKE ?", array($table));
  return (bool)$row;
}

function cfmp_empty_sections($months)
{
  $blank = array();
  foreach ($months as $month) $blank[$month] = 0;
  return array(
    'operating_in'=>array('title'=>cfmp_t('finance_operating_receipts', 'PENERIMAAN DARI AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>$blank),
    'operating_out'=>array('title'=>cfmp_t('finance_operating_payments', 'PEMBAYARAN UNTUK AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>$blank),
    'investing'=>array('title'=>cfmp_t('finance_investing_cash_flow', 'ARUS KAS DARI AKTIVITAS INVESTASI'), 'rows'=>array(), 'total'=>$blank),
    'financing'=>array('title'=>cfmp_t('finance_financing_cash_flow', 'ARUS KAS DARI AKTIVITAS PENDANAAN'), 'rows'=>array(), 'total'=>$blank)
  );
}

function cfmp_classify_counter($row, $amount)
{
  $mapping = cfmp_lower($row->cash_flow_group);
  if ($mapping === 'investing') return 'investing';
  if ($mapping === 'financing') return 'financing';
  if ($mapping === 'operating') return $amount >= 0 ? 'operating_in' : 'operating_out';
  $kategori = cfmp_lower($row->kategori);
  if ($row->kategori_akun === 'modal') return 'financing';
  if ($row->kategori_akun === 'kewajiban' && strpos($kategori, 'jangka panjang') !== false) return 'financing';
  if ($row->kategori_akun === 'aset' && (strpos($kategori, 'aset tetap') !== false || strpos($kategori, 'aset lainnya') !== false)) return 'investing';
  return $amount >= 0 ? 'operating_in' : 'operating_out';
}

function cfmp_add_row(&$section, $months, $month, $account, $label, $amount, $source)
{
  if (abs((float)$amount) < 0.005) return;
  $key = $account.'|'.$label.'|'.$source;
  if (!isset($section['rows'][$key])) {
    $values = array();
    foreach ($months as $m) $values[$m] = 0;
    $section['rows'][$key] = array('account'=>$account, 'label'=>$label, 'source'=>$source, 'values'=>$values);
  }
  $section['rows'][$key]['values'][$month] += (float)$amount;
  $section['total'][$month] += (float)$amount;
}

function cfmp_rows($db, $start, $end, $hasMapping)
{
  $cashJoin = $hasMapping ? "LEFT JOIN cash_flow_mapping mcash ON mcash.no_rek=cash.no_rek AND mcash.is_active='Y'" : "";
  $counterJoin = $hasMapping ? "LEFT JOIN cash_flow_mapping m ON m.no_rek=counter.no_rek AND m.is_active='Y' LEFT JOIN cash_flow_mapping mcounter_cash ON mcounter_cash.no_rek=counter.no_rek AND mcounter_cash.is_active='Y'" : "";
  $cashMapExpr = $hasMapping ? "mcash.cash_flow_group='cash_equivalent' OR " : "";
  $counterCashExpr = $hasMapping ? "mcounter_cash.cash_flow_group='cash_equivalent' OR " : "";
  $groupSelect = $hasMapping ? "COALESCE(m.cash_flow_group,'') cash_flow_group, COALESCE(m.cash_flow_type,'') cash_flow_type" : "'' cash_flow_group, '' cash_flow_type";
  $rows = $db->query(
    "SELECT h.id,h.no_jurnal,h.tgl_jurnal,
            cash.no_rek cash_account,
            cash.debet cash_debet,
            cash.kredit cash_kredit,
            counter.no_rek counter_account,
            counter.line_text,
            counter.debet counter_debet,
            counter.kredit counter_kredit,
            r.nama_rek counter_name,
            k.kategori,
            k.kategori_akun,
            $groupSelect
     FROM jurnal_header h
     INNER JOIN jurnal_detail cash ON cash.id_header=h.id
     INNER JOIN rekening rcash ON rcash.no_rek=cash.no_rek
     INNER JOIN coa_kategori kcash ON kcash.id=rcash.kat_coa
     $cashJoin
     INNER JOIN jurnal_detail counter ON counter.id_header=h.id AND counter.id<>cash.id
     INNER JOIN rekening r ON r.no_rek=counter.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     $counterJoin
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND ($cashMapExpr LOWER(kcash.kategori) LIKE '%kas%' OR LOWER(kcash.kategori) LIKE '%bank%')
       AND NOT ($counterCashExpr LOWER(k.kategori) LIKE '%kas%' OR LOWER(k.kategori) LIKE '%bank%')
     ORDER BY h.tgl_jurnal,h.no_jurnal,h.id,cash.line_no,counter.line_no,counter.id",
    array($start, $end)
  );
  if ($rows === false) throw new Exception('Query arus kas multi period gagal: '.$db->getErrorMessage());
  return $rows;
}

function cfmp_mapping_warnings($db, $hasMapping)
{
  $warnings = array();
  if (!$hasMapping) {
    $warnings[] = cfmp_t('finance_cash_flow_mapping_missing', 'Tabel cash_flow_mapping belum tersedia.');
    $warnings[] = cfmp_t('finance_cash_mapping_missing_warning', 'Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA Kas & Bank.');
    $warnings[] = cfmp_t('finance_cash_activity_mapping_missing_warning', 'Mapping aktivitas operasi/investasi/pendanaan belum lengkap; akun lawan memakai fallback kategori COA.');
    return $warnings;
  }
  $mapping = $db->fetch("SELECT COUNT(*) total,SUM(cash_flow_group='cash_equivalent') cash_count,SUM(cash_flow_group IN ('operating','investing','financing')) activity_count FROM cash_flow_mapping WHERE is_active='Y'");
  if (!$mapping || (int)$mapping->total < 1) $warnings[] = cfmp_t('finance_cash_flow_mapping_empty', 'cash_flow_mapping belum diisi; klasifikasi memakai fallback kategori COA resmi.');
  if ($mapping && (int)$mapping->cash_count < 1) $warnings[] = cfmp_t('finance_cash_mapping_missing_warning', 'Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA Kas & Bank.');
  if ($mapping && (int)$mapping->activity_count < 1) $warnings[] = cfmp_t('finance_cash_activity_mapping_missing_warning', 'Mapping aktivitas operasi/investasi/pendanaan belum lengkap; akun lawan memakai fallback kategori COA.');
  return $warnings;
}

function cfmp_data($db, $filters)
{
  $months = $filters['months'];
  $sections = cfmp_empty_sections($months);
  $hasMapping = cfmp_table_exists($db, 'cash_flow_mapping');
  $warnings = cfmp_mapping_warnings($db, $hasMapping);
  $start = $filters['start_month'].'-01';
  $end = date('Y-m-t', strtotime($filters['end_month'].'-01'));
  $journalCash = array();
  $journalWeights = array();
  $rawRows = array();
  foreach (cfmp_rows($db, $start, $end, $hasMapping) as $row) {
    $month = date('Y-m', strtotime($row->tgl_jurnal));
    $cashMove = round((float)$row->cash_debet - (float)$row->cash_kredit, 2);
    $weight = abs((float)$row->counter_debet - (float)$row->counter_kredit);
    if ($weight < 0.005) $weight = abs((float)$row->counter_debet) + abs((float)$row->counter_kredit);
    $jid = $row->id.'|'.$row->cash_account.'|'.$cashMove;
    $journalCash[$jid] = $cashMove;
    if (!isset($journalWeights[$jid])) $journalWeights[$jid] = 0;
    $journalWeights[$jid] += $weight;
    $rawRows[] = array('jid'=>$jid, 'month'=>$month, 'row'=>$row, 'weight'=>$weight);
  }
  foreach ($rawRows as $entry) {
    $row = $entry['row'];
    $cashMove = $journalCash[$entry['jid']];
    $baseWeight = $journalWeights[$entry['jid']] > 0 ? $journalWeights[$entry['jid']] : 1;
    $amount = $cashMove * ($entry['weight'] / $baseWeight);
    if (abs($amount) < 0.005) continue;
    $sectionKey = cfmp_classify_counter($row, $amount);
    $direction = $amount >= 0 ? cfmp_t('finance_receipt_from', 'Penerimaan dari ') : cfmp_t('finance_payment_to', 'Pembayaran untuk ');
    cfmp_add_row($sections[$sectionKey], $months, $entry['month'], $row->counter_account, $direction.$row->counter_name, $amount, $row->cash_flow_group !== '' ? 'mapping' : 'coa');
  }
  if (!count($rawRows)) $warnings[] = cfmp_t('finance_cash_flow_empty_warning', 'Tidak ada jurnal POSTED yang menyentuh akun kas/bank pada periode ini.');
  foreach ($sections as &$section) {
    $section['rows'] = array_values($section['rows']);
    usort($section['rows'], function($a, $b) { return strcmp($a['account'].$a['label'], $b['account'].$b['label']); });
  }
  unset($section);
  return array($sections, array_values(array_unique($warnings)));
}

function cfmp_row_total($values)
{
  return array_sum($values);
}

function cfmp_html($sections, $warnings, $filters)
{
  $months = $filters['months'];
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.cfmp_h(cfmp_t('common_warning', 'Peringatan')).':</strong> '.cfmp_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.cfmp_h(cfmp_t('finance_cash_flow_direct_multi_period_source', 'Sumber: jurnal_header/detail POSTED, rekening, coa_kategori, dan cash_flow_mapping.')).' '.cfmp_h($filters['start_month'].' s/d '.$filters['end_month']).'</div>';
  $colspan = count($months) + 3;
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed cfmp-table"><thead><tr class="bg-primary"><th>COA</th><th>'.cfmp_h(cfmp_t('common_description', 'Deskripsi')).'</th>';
  foreach ($months as $month) $html .= '<th class="text-right">'.cfmp_h(cfmp_month_label($month)).'</th>';
  $html .= '<th class="text-right">'.cfmp_h(cfmp_t('common_total', 'Total')).'</th></tr></thead><tbody>';
  $net = array();
  foreach ($months as $month) $net[$month] = 0;
  foreach ($sections as $section) {
    $html .= '<tr class="cfmp-section"><th colspan="'.$colspan.'">'.cfmp_h($section['title']).'</th></tr>';
    if (!count($section['rows'])) $html .= '<tr><td></td><td class="text-muted cfmp-account"><em>'.cfmp_h(cfmp_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</em></td>'.str_repeat('<td class="text-right">0.00</td>', count($months) + 1).'</tr>';
    foreach ($section['rows'] as $row) {
      $html .= '<tr><td>'.cfmp_h($row['account']).'</td><td class="cfmp-account">'.cfmp_h($row['label']).'</td>';
      foreach ($months as $month) $html .= '<td class="text-right">'.cfmp_num($row['values'][$month]).'</td>';
      $html .= '<td class="text-right">'.cfmp_num(cfmp_row_total($row['values'])).'</td></tr>';
    }
    $html .= '<tr class="cfmp-total"><th></th><th>Subtotal</th>';
    foreach ($months as $month) {
      $net[$month] += $section['total'][$month];
      $html .= '<td class="text-right">'.cfmp_num($section['total'][$month]).'</td>';
    }
    $html .= '<td class="text-right">'.cfmp_num(cfmp_row_total($section['total'])).'</td></tr>';
  }
  $html .= '<tr class="cfmp-grand"><th></th><th>'.cfmp_h(cfmp_t('finance_net_cash_flow', 'Net Cash Flow')).'</th>';
  foreach ($months as $month) $html .= '<td class="text-right">'.cfmp_num($net[$month]).'</td>';
  $html .= '<td class="text-right">'.cfmp_num(cfmp_row_total($net)).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function cfmp_print_page($sections, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = cfmp_html($sections, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.cfmp_h(cfmp_t('finance_report_cash_flow_direct_multi_period', 'Arus Kas Multi Period (Langsung)')).'</title><link rel="stylesheet" href="'.cfmp_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:10px}.print-wrap{max-width:1180px;margin:18px auto}.cfmp-table{width:100%;border-collapse:collapse!important}.cfmp-table th,.cfmp-table td{font-size:10px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cfmp-section th{background:#1d4ed8!important;color:#fff!important}.cfmp-total th,.cfmp-total td{background:#f3f4f6!important;font-weight:bold}.cfmp-grand th,.cfmp-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.cfmp-account{padding-left:18px!important;min-width:220px}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:7mm}.table-responsive{overflow:visible!important}.cfmp-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cfmp_h($company).'</h3><h4 style="margin:0 0 14px">'.cfmp_h(cfmp_t('finance_report_cash_flow_direct_multi_period', 'Arus Kas Multi Period (Langsung)')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

function cfmp_excel($sections, $warnings, $filters)
{
  global $initialOutputBufferLevel;
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $months = $filters['months'];
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Arus Kas Multi'));
  $sheet->setCellValue('A4', 'COA');
  $sheet->setCellValue('B4', cfmp_t('common_description', 'Deskripsi'));
  $col = 2;
  foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, 4, cfmp_month_label($month));
  $sheet->setCellValueByColumnAndRow($col, 4, cfmp_t('common_total', 'Total'));
  $rowNo = 5;
  $net = array();
  foreach ($months as $month) $net[$month] = 0;
  foreach ($sections as $section) {
    $sheet->setCellValue('A'.$rowNo, $section['title']);
    $sheet->mergeCellsByColumnAndRow(0, $rowNo, count($months) + 2, $rowNo);
    $rowNo++;
    foreach ($section['rows'] as $row) {
      $sheet->setCellValueExplicit('A'.$rowNo, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValue('B'.$rowNo, $row['label']);
      $col = 2;
      foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $rowNo, $row['values'][$month]);
      $sheet->setCellValueByColumnAndRow($col, $rowNo, cfmp_row_total($row['values']));
      $rowNo++;
    }
    $sheet->setCellValue('B'.$rowNo, 'Subtotal');
    $col = 2;
    foreach ($months as $month) {
      $net[$month] += $section['total'][$month];
      $sheet->setCellValueByColumnAndRow($col++, $rowNo, $section['total'][$month]);
    }
    $sheet->setCellValueByColumnAndRow($col, $rowNo, cfmp_row_total($section['total']));
    $rowNo++;
  }
  $sheet->setCellValue('B'.$rowNo, cfmp_t('finance_net_cash_flow', 'Net Cash Flow'));
  $col = 2;
  foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $rowNo, $net[$month]);
  $sheet->setCellValueByColumnAndRow($col, $rowNo, cfmp_row_total($net));
  erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(cfmp_t('finance_report_cash_flow_direct_multi_period', 'ARUS KAS MULTI PERIOD LANGSUNG')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>$rowNo, 'column_count'=>count($months) + 3, 'money_columns'=>range('C', PHPExcel_Cell::stringFromColumnIndex(count($months) + 2)), 'filters'=>array(cfmp_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
  $tmp = erpkb_excel_temp_file('arus_kas_multi_period_langsung_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="arus_kas_multi_period_langsung_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = cfmp_filters();
  list($sections, $warnings) = cfmp_data($db, $filters);
  if ($act === 'filter') cfmp_json('success', 'OK', array('html'=>cfmp_html($sections, $warnings, $filters), 'warnings'=>$warnings));
  if ($act === 'print') cfmp_print_page($sections, $warnings, $filters);
  if ($act === 'excel') cfmp_excel($sections, $warnings, $filters);
  cfmp_json('error', cfmp_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  cfmp_json('error', $e->getMessage());
}
?>
