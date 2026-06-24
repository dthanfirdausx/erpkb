<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function gav_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function gav_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function gav_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function gav_num($value) { return number_format((float)$value, 2, '.', ','); }
function gav_req($key, $default = '') { return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default; }
function gav_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function gav_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function gav_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function gav_filters()
{
  $start = trim((string)gav_req('start_month', date('Y-m')));
  $end = trim((string)gav_req('end_month', date('Y-m')));
  $mode = trim((string)gav_req('value_mode', 'ending'));
  $accounts = gav_req('accounts', array());
  if (!is_array($accounts)) $accounts = $accounts === '' ? array() : explode(',', (string)$accounts);
  $accounts = array_values(array_filter(array_map('trim', $accounts), function($v) { return $v !== ''; }));
  if (!gav_month_ok($start) || !gav_month_ok($end)) throw new Exception(gav_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(gav_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = gav_months($start, $end);
  if (count($months) > 12) throw new Exception(gav_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  if (!count($accounts)) throw new Exception(gav_t('finance_select_account_warning', 'Pilih minimal satu akun untuk memuat laporan.'));
  if (!in_array($mode, array('ending','movement'))) $mode = 'ending';
  return array('start_month'=>$start, 'end_month'=>$end, 'value_mode'=>$mode, 'accounts'=>$accounts, 'months'=>$months);
}

function gav_accounts_endpoint($db)
{
  $term = trim((string)gav_req('term', ''));
  $params = array();
  $where = '';
  if ($term !== '') {
    $where = "WHERE r.no_rek LIKE ? OR r.nama_rek LIKE ?";
    $params = array('%'.$term.'%', '%'.$term.'%');
  }
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori,k.kategori_akun
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     $where
     ORDER BY r.no_rek
     LIMIT 50",
    $params
  );
  if ($rows === false) gav_json('error', $db->getErrorMessage());
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->no_rek, 'text'=>$row->no_rek.' - '.$row->nama_rek.' ['.$row->kategori.']');
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}

function gav_account_map($db, $accounts)
{
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori,k.kategori_akun,k.saldo_normal
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE r.no_rek IN (".implode(',', array_fill(0, count($accounts), '?')).")
     ORDER BY r.no_rek",
    $accounts
  );
  if ($rows === false) throw new Exception('Query akun gagal: '.$db->getErrorMessage());
  $map = array();
  foreach ($rows as $row) $map[$row->no_rek] = $row;
  return $map;
}

function gav_balance($db, $account, $asOfDate)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $row = $db->fetch(
    "SELECT CASE WHEN k.saldo_normal='kredit'
       THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
       ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
     END amount
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE r.no_rek=?",
    array($year, $yearStart, $asOfDate, $account)
  );
  if ($row === false) throw new Exception('Query saldo akun gagal: '.$db->getErrorMessage());
  return $row ? (float)$row->amount : 0;
}

function gav_movement($db, $account, $start, $end)
{
  $row = $db->fetch(
    "SELECT CASE WHEN k.saldo_normal='kredit'
       THEN COALESCE(j.kredit,0)-COALESCE(j.debet,0)
       ELSE COALESCE(j.debet,0)-COALESCE(j.kredit,0)
     END amount
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit
       FROM jurnal_detail d
       INNER JOIN jurnal_header h ON h.id=d.id_header
       WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'
       GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE r.no_rek=?",
    array($start, $end, $account)
  );
  if ($row === false) throw new Exception('Query mutasi akun gagal: '.$db->getErrorMessage());
  return $row ? (float)$row->amount : 0;
}

function gav_data($db, $filters)
{
  $accountMap = gav_account_map($db, $filters['accounts']);
  $warnings = array();
  foreach ($filters['accounts'] as $account) if (!isset($accountMap[$account])) $warnings[] = gav_t('finance_account_not_found_warning', 'Akun tidak ditemukan.').' '.$account;
  $rows = array();
  $series = array();
  foreach ($accountMap as $account=>$meta) {
    $series[$account] = array('label'=>$account.' - '.$meta->nama_rek, 'values'=>array());
    foreach ($filters['months'] as $month) {
      $start = $month.'-01';
      $end = date('Y-m-t', strtotime($start));
      $value = $filters['value_mode'] === 'movement' ? gav_movement($db, $account, $start, $end) : gav_balance($db, $account, $end);
      $series[$account]['values'][] = $value;
      $rows[] = array('month'=>$month, 'account'=>$account, 'account_name'=>$meta->nama_rek, 'value'=>$value);
    }
  }
  return array($accountMap, $series, $rows, $warnings);
}

function gav_chart($series, $months)
{
  $colors = array('#1d4ed8','#0f766e','#f97316','#dc2626','#7c3aed','#0891b2','#be123c','#4b5563');
  $datasets = array();
  $i = 0;
  foreach ($series as $item) {
    $color = $colors[$i % count($colors)];
    $datasets[] = array('label'=>$item['label'], 'data'=>$item['values'], 'borderColor'=>$color, 'backgroundColor'=>$color, 'tension'=>0.25, 'fill'=>false);
    $i++;
  }
  return array('labels'=>array_map('gav_month_label', $months), 'datasets'=>$datasets);
}

function gav_html($rows, $warnings, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.gav_h(gav_t('common_warning', 'Peringatan')).':</strong> '.gav_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.gav_h(gav_t('finance_account_chart_source_summary', 'Sumber: saldo_awal, jurnal_header/detail POSTED, rekening, dan coa_kategori.')).' '.gav_h($filters['start_month'].' s/d '.$filters['end_month']).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed gav-table"><thead><tr class="bg-primary"><th>'.gav_h(gav_t('finance_month', 'Bulan')).'</th><th>COA</th><th>'.gav_h(gav_t('finance_account', 'Akun')).'</th><th class="text-right">'.gav_h(gav_t('common_amount', 'Nilai')).'</th></tr></thead><tbody>';
  if (!count($rows)) $html .= '<tr><td colspan="4" class="text-center text-muted">'.gav_h(gav_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</td></tr>';
  foreach ($rows as $row) $html .= '<tr><td>'.gav_h(gav_month_label($row['month'])).'</td><td>'.gav_h($row['account']).'</td><td>'.gav_h($row['account_name']).'</td><td class="text-right">'.gav_num($row['value']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function gav_svg_chart($series, $months)
{
  $width = 1060; $height = 300; $left = 70; $right = 24; $top = 24; $bottom = 48;
  $values = array();
  foreach ($series as $item) foreach ($item['values'] as $value) $values[] = (float)$value;
  if (!count($values)) $values = array(0);
  $min = min($values); $max = max($values);
  if (abs($max - $min) < 0.005) { $max += 1; $min -= 1; }
  $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
  $colors = array('#1d4ed8','#0f766e','#f97316','#dc2626','#7c3aed','#0891b2','#be123c','#4b5563');
  $xFor = function($i) use ($left, $plotW, $months) { return $left + (count($months) <= 1 ? 0 : ($plotW * $i / (count($months) - 1))); };
  $yFor = function($v) use ($top, $plotH, $min, $max) { return $top + $plotH - (($v - $min) / ($max - $min) * $plotH); };
  $svg = '<svg class="gav-print-chart" width="100%" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
  $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#fff"/><line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top+$plotH).'" stroke="#94a3b8"/><line x1="'.$left.'" y1="'.($top+$plotH).'" x2="'.($left+$plotW).'" y2="'.($top+$plotH).'" stroke="#94a3b8"/>';
  foreach ($months as $i=>$month) {
    $x = $xFor($i);
    $svg .= '<text x="'.$x.'" y="'.($height-16).'" font-size="10" text-anchor="middle" fill="#475569">'.gav_h(gav_month_label($month)).'</text>';
  }
  $idx = 0;
  foreach ($series as $item) {
    $color = $colors[$idx % count($colors)];
    $points = array();
    foreach ($item['values'] as $i=>$value) $points[] = round($xFor($i), 2).','.round($yFor((float)$value), 2);
    $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="'.$color.'" stroke-width="2.5"/>';
    foreach ($item['values'] as $i=>$value) $svg .= '<circle cx="'.round($xFor($i), 2).'" cy="'.round($yFor((float)$value), 2).'" r="3" fill="'.$color.'"/>';
    $svg .= '<text x="'.($left + ($idx * 250)).'" y="14" font-size="11" fill="'.$color.'">'.gav_h($item['label']).'</text>';
    $idx++;
  }
  return $svg.'</svg>';
}

function gav_print_page($series, $rows, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = gav_svg_chart($series, $filters['months']).gav_html($rows, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.gav_h(gav_t('finance_report_account_value_chart', 'Grafik Perbandingan Nilai Akun')).'</title><link rel="stylesheet" href="'.gav_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.gav-table th,.gav-table td{font-size:11px;border:1px solid #d2d6de!important}.no-print{margin-bottom:12px}@media print{.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.gav_h($company).'</h3><h4 style="margin:0 0 14px">'.gav_h(gav_t('finance_report_account_value_chart', 'Grafik Perbandingan Nilai Akun')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  if ($act === 'accounts') gav_accounts_endpoint($db);
  $filters = gav_filters();
  list($accountMap, $series, $rows, $warnings) = gav_data($db, $filters);
  if ($act === 'filter') gav_json('success', 'OK', array('html'=>gav_html($rows, $warnings, $filters), 'warnings'=>$warnings, 'chart'=>gav_chart($series, $filters['months'])));
  if ($act === 'print') gav_print_page($series, $rows, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Grafik Akun'));
    $sheet->setCellValue('A4', gav_t('finance_month', 'Bulan'));
    $sheet->setCellValue('B4', 'COA');
    $sheet->setCellValue('C4', gav_t('finance_account', 'Akun'));
    $sheet->setCellValue('D4', gav_t('common_amount', 'Nilai'));
    $r = 5;
    foreach ($rows as $row) {
      $sheet->setCellValue('A'.$r, gav_month_label($row['month']));
      $sheet->setCellValueExplicit('B'.$r, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValue('C'.$r, $row['account_name']);
      $sheet->setCellValue('D'.$r, $row['value']);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(gav_t('finance_report_account_value_chart', 'GRAFIK PERBANDINGAN NILAI AKUN')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>4, 'money_columns'=>array('D'), 'filters'=>array(gav_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], gav_t('finance_value_mode', 'Mode Nilai')=>$filters['value_mode'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('grafik_perbandingan_nilai_akun_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="grafik_perbandingan_nilai_akun_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  gav_json('error', gav_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  gav_json('error', $e->getMessage());
}
?>
