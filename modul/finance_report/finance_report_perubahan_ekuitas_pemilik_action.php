<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function fec_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function fec_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function fec_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function fec_num($value) { return number_format((float)$value, 2, '.', ','); }
function fec_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function fec_valid_date($value) { return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$value) && strtotime($value) !== false; }
function fec_lower($value) { return strtolower(trim((string)$value)); }
function fec_prev_date($date) { $d = new DateTime($date); $d->modify('-1 day'); return $d->format('Y-m-d'); }

function fec_table_exists($db, $table) { return (bool)$db->fetch("SHOW TABLES LIKE ?", array($table)); }

function fec_filters()
{
  $start = fec_req('start_date', date('Y-m-01'));
  $end = fec_req('end_date', date('Y-m-d'));
  if (!fec_valid_date($start) || !fec_valid_date($end)) throw new Exception(fec_t('finance_invalid_date', 'Format tanggal tidak valid.'));
  if (strtotime($start) > strtotime($end)) throw new Exception(fec_t('finance_start_after_end', 'Start date tidak boleh lebih besar dari end date.'));
  return array('start_date'=>$start, 'end_date'=>$end);
}

function fec_mapping($db, &$warnings)
{
  $map = array();
  if (!fec_table_exists($db, 'finance_equity_movement_mapping')) {
    $warnings[] = fec_t('finance_equity_mapping_missing_warning', 'Mapping perubahan ekuitas belum tersedia; klasifikasi tambahan modal/prive/dividen memakai fallback nama akun.');
    return $map;
  }
  $rows = $db->query("SELECT no_rek,movement_type FROM finance_equity_movement_mapping WHERE COALESCE(is_active,'Y')='Y'");
  if ($rows === false) throw new Exception('Query mapping ekuitas gagal: '.$db->getErrorMessage());
  foreach ($rows as $row) $map[$row->no_rek] = fec_lower($row->movement_type);
  return $map;
}

function fec_classify($row, $map)
{
  if (isset($map[$row->no_rek])) {
    if (in_array($map[$row->no_rek], array('capital_addition','additional_capital','modal_tambahan'))) return 'capital_addition';
    if (in_array($map[$row->no_rek], array('withdrawal','dividend','prive','dividen'))) return 'withdrawal';
    return 'adjustment';
  }
  $name = fec_lower($row->nama_rek);
  if (strpos($name, 'prive') !== false || strpos($name, 'dividen') !== false || strpos($name, 'dividend') !== false) return 'withdrawal';
  if (strpos($name, 'modal') !== false || strpos($name, 'saham') !== false || strpos($name, 'disetor') !== false) return 'capital_addition';
  return 'adjustment';
}

function fec_net_income($db, $start, $end)
{
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE
       WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE 0 END),0) amount,COUNT(*) line_count
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')",
    array($start, $end)
  );
  if ($row === false) throw new Exception('Query laba rugi gagal: '.$db->getErrorMessage());
  return array((float)$row->amount, (int)$row->line_count);
}

function fec_equity_balance($db, $asOfDate)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN k.saldo_normal='kredit'
       THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
       ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
     END),0) amount
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun='modal'",
    array($year, $yearStart, $asOfDate)
  );
  if ($row === false) throw new Exception('Query saldo ekuitas gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}

function fec_equity_movements($db, $filters, $map)
{
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.saldo_normal,
      SUM(CASE WHEN k.saldo_normal='kredit' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE COALESCE(d.debet,0)-COALESCE(d.kredit,0) END) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun='modal'
     GROUP BY r.no_rek,r.nama_rek,k.saldo_normal
     HAVING ABS(amount) >= 0.005
     ORDER BY r.no_rek",
    array($filters['start_date'], $filters['end_date'])
  );
  if ($rows === false) throw new Exception('Query mutasi ekuitas gagal: '.$db->getErrorMessage());
  $sections = fec_empty_sections();
  foreach ($rows as $row) {
    $type = fec_classify($row, $map);
    fec_add_row($sections[$type], $row->no_rek, $row->nama_rek, (float)$row->amount);
  }
  return $sections;
}

function fec_empty_sections()
{
  return array(
    'capital_addition'=>array('title'=>fec_t('finance_capital_addition', 'Tambahan Modal'), 'rows'=>array(), 'total'=>0),
    'withdrawal'=>array('title'=>fec_t('finance_withdrawal_dividend', 'Prive/Dividen'), 'rows'=>array(), 'total'=>0),
    'adjustment'=>array('title'=>fec_t('finance_equity_adjustment', 'Penyesuaian'), 'rows'=>array(), 'total'=>0)
  );
}

function fec_add_row(&$section, $account, $label, $amount)
{
  if (abs($amount) < 0.005) return;
  $section['rows'][] = array('account'=>$account, 'label'=>$label, 'amount'=>$amount);
  $section['total'] += $amount;
}

function fec_opening_warning($db, $date)
{
  $year = (int)date('Y', strtotime($date));
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return fec_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return fec_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function fec_data($db, $filters)
{
  $warnings = array();
  $map = fec_mapping($db, $warnings);
  $openingDate = fec_prev_date($filters['start_date']);
  $openingEquity = fec_equity_balance($db, $openingDate);
  list($preIncome) = fec_net_income($db, date('Y', strtotime($openingDate)).'-01-01', $openingDate);
  $openingEquity += $preIncome;
  $sections = fec_equity_movements($db, $filters, $map);
  list($netIncome, $plLines) = fec_net_income($db, $filters['start_date'], $filters['end_date']);
  if ($plLines < 1) $warnings[] = fec_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.');
  $openWarn = fec_opening_warning($db, $filters['end_date']);
  if ($openWarn !== '') $warnings[] = $openWarn;
  $endingEquity = $openingEquity + $sections['capital_addition']['total'] + $sections['withdrawal']['total'] + $sections['adjustment']['total'] + $netIncome;
  return array($openingEquity, $sections, $netIncome, $endingEquity, array_values(array_unique($warnings)));
}

function fec_html($openingEquity, $sections, $netIncome, $endingEquity, $warnings, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.fec_h(fec_t('common_warning', 'Peringatan')).':</strong> '.fec_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.fec_h(fec_t('finance_equity_source_summary', 'Sumber: saldo_awal, jurnal_header/detail POSTED, rekening, dan coa_kategori.')).' '.fec_h($filters['start_date'].' s/d '.$filters['end_date']).'</div>';
  $html .= '<div class="row">';
  foreach (array(
    array('label'=>fec_t('finance_opening_equity', 'Saldo Awal Ekuitas'), 'value'=>$openingEquity, 'class'=>'gray'),
    array('label'=>fec_t('finance_capital_addition', 'Tambahan Modal'), 'value'=>$sections['capital_addition']['total'], 'class'=>'green'),
    array('label'=>fec_t('finance_net_income_period', 'Laba/Rugi Bersih'), 'value'=>$netIncome, 'class'=>$netIncome < 0 ? 'red' : 'green'),
    array('label'=>fec_t('finance_ending_equity', 'Saldo Akhir Ekuitas'), 'value'=>$endingEquity, 'class'=>'orange')
  ) as $card) $html .= '<div class="col-md-3 col-sm-6"><div class="fec-card '.$card['class'].'"><div class="label-text">'.fec_h($card['label']).'</div><div class="value-text">'.fec_num($card['value']).'</div></div></div>';
  $html .= '</div><div class="table-responsive"><table class="table table-bordered table-condensed fec-table"><tbody>';
  $html .= '<tr class="fec-section"><th colspan="3">'.fec_h(fec_t('finance_equity_components', 'Komponen Perubahan Ekuitas')).'</th></tr>';
  $html .= '<tr><td></td><td>'.fec_h(fec_t('finance_opening_equity', 'Saldo Awal Ekuitas')).'</td><td class="text-right">'.fec_num($openingEquity).'</td></tr>';
  foreach ($sections as $section) {
    $html .= '<tr class="fec-section"><th colspan="3">'.fec_h($section['title']).'</th></tr>';
    if (!count($section['rows'])) $html .= '<tr><td></td><td class="text-muted"><em>'.fec_h(fec_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</em></td><td class="text-right">0.00</td></tr>';
    foreach ($section['rows'] as $row) $html .= '<tr><td>'.fec_h($row['account']).'</td><td>'.fec_h($row['label']).'</td><td class="text-right">'.fec_num($row['amount']).'</td></tr>';
    $html .= '<tr class="fec-total"><th></th><th>Subtotal '.fec_h($section['title']).'</th><td class="text-right">'.fec_num($section['total']).'</td></tr>';
  }
  $html .= '<tr class="fec-total"><th></th><th>'.fec_h(fec_t('finance_net_income_period', 'Laba/Rugi Bersih')).'</th><td class="text-right">'.fec_num($netIncome).'</td></tr>';
  $html .= '<tr class="fec-grand"><th></th><th>'.fec_h(fec_t('finance_ending_equity', 'Saldo Akhir Ekuitas')).'</th><td class="text-right">'.fec_num($endingEquity).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function fec_print_page($openingEquity, $sections, $netIncome, $endingEquity, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = fec_html($openingEquity, $sections, $netIncome, $endingEquity, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.fec_h(fec_t('finance_report_owner_equity_changes', 'Perubahan Ekuitas Pemilik')).'</title><link rel="stylesheet" href="'.fec_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1100px;margin:18px auto}.fec-card{border:1px solid #d2d6de;border-left:4px solid #1d4ed8;padding:8px;margin-bottom:8px;min-height:70px}.fec-card .label-text{font-size:10px;color:#555;text-transform:uppercase}.fec-card .value-text{font-size:16px;font-weight:bold}.fec-table th,.fec-table td{font-size:11px;border:1px solid #d2d6de!important}.fec-section th{background:#1d4ed8!important;color:#fff!important}.fec-total th,.fec-total td{background:#f3f4f6!important;font-weight:bold}.fec-grand th,.fec-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.fec_h($company).'</h3><h4 style="margin:0 0 14px">'.fec_h(fec_t('finance_report_owner_equity_changes', 'Perubahan Ekuitas Pemilik')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = fec_filters();
  list($openingEquity, $sections, $netIncome, $endingEquity, $warnings) = fec_data($db, $filters);
  if ($act === 'filter') fec_json('success', 'OK', array('html'=>fec_html($openingEquity, $sections, $netIncome, $endingEquity, $warnings, $filters), 'warnings'=>$warnings));
  if ($act === 'print') fec_print_page($openingEquity, $sections, $netIncome, $endingEquity, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Perubahan Ekuitas'));
    $sheet->setCellValue('A4', 'COA');
    $sheet->setCellValue('B4', fec_t('common_description', 'Uraian'));
    $sheet->setCellValue('C4', fec_t('common_amount', 'Nilai'));
    $r = 5;
    $sheet->setCellValue('B'.$r, fec_t('finance_opening_equity', 'Saldo Awal Ekuitas')); $sheet->setCellValue('C'.$r++, $openingEquity);
    foreach ($sections as $section) {
      $sheet->setCellValue('A'.$r, $section['title']); $sheet->mergeCells('A'.$r.':C'.$r); $sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true); $r++;
      foreach ($section['rows'] as $row) { $sheet->setCellValueExplicit('A'.$r, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING); $sheet->setCellValue('B'.$r, $row['label']); $sheet->setCellValue('C'.$r, $row['amount']); $r++; }
      $sheet->setCellValue('B'.$r, 'Subtotal '.$section['title']); $sheet->setCellValue('C'.$r, $section['total']); $sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true); $r++;
    }
    $sheet->setCellValue('B'.$r, fec_t('finance_net_income_period', 'Laba/Rugi Bersih')); $sheet->setCellValue('C'.$r++, $netIncome);
    $sheet->setCellValue('B'.$r, fec_t('finance_ending_equity', 'Saldo Akhir Ekuitas')); $sheet->setCellValue('C'.$r, $endingEquity);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(fec_t('finance_report_owner_equity_changes', 'PERUBAHAN EKUITAS PEMILIK')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r), 'column_count'=>3, 'money_columns'=>array('C'), 'filters'=>array(fec_t('finance_period', 'Periode')=>$filters['start_date'].' s/d '.$filters['end_date'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('perubahan_ekuitas_pemilik_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="perubahan_ekuitas_pemilik_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  fec_json('error', fec_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  fec_json('error', $e->getMessage());
}
?>
