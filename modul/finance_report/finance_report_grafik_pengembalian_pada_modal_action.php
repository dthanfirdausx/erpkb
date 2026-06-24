<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function groe_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function groe_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function groe_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function groe_num($value) { return number_format((float)$value, 2, '.', ','); }
function groe_pct($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ',').'%'; }
function groe_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function groe_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function groe_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function groe_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function groe_filters()
{
  $start = groe_req('start_month', date('Y-m'));
  $end = groe_req('end_month', date('Y-m'));
  if (!groe_month_ok($start) || !groe_month_ok($end)) throw new Exception(groe_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(groe_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = groe_months($start, $end);
  if (count($months) > 12) throw new Exception(groe_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function groe_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return groe_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return groe_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function groe_net_income($db, $start, $end)
{
  $row = $db->fetch(
    "SELECT
       SUM(CASE WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE 0 END) revenue,
       SUM(CASE WHEN k.kategori_akun='beban' THEN COALESCE(d.debet,0)-COALESCE(d.kredit,0) ELSE 0 END) expense,
       COUNT(d.id) line_count
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')",
    array($start, $end)
  );
  if ($row === false) throw new Exception('Query ROE laba bersih gagal: '.$db->getErrorMessage());
  return array('net_income'=>($row ? (float)$row->revenue : 0) - ($row ? (float)$row->expense : 0), 'line_count'=>$row ? (int)$row->line_count : 0);
}

function groe_total_equity($db, $year, $end)
{
  $yearStart = $year.'-01-01';
  $row = $db->fetch(
    "SELECT
       SUM(CASE WHEN k.saldo_normal='kredit'
         THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
         ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
       END) equity,
       COUNT(r.no_rek) line_count
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun='modal'",
    array($year, $yearStart, $end)
  );
  if ($row === false) throw new Exception('Query ROE modal gagal: '.$db->getErrorMessage());
  return array('equity'=>$row ? (float)$row->equity : 0, 'line_count'=>$row ? (int)$row->line_count : 0);
}

function groe_ytd_income($db, $year, $end)
{
  $yearStart = $year.'-01-01';
  $pl = groe_net_income($db, $yearStart, $end);
  return $pl['net_income'];
}

function groe_data($db, $filters)
{
  $rows = array(); $warnings = array(); $yearsChecked = array();
  foreach ($filters['months'] as $month) {
    $year = (int)substr($month, 0, 4);
    if (!isset($yearsChecked[$year])) {
      $warning = groe_opening_warning($db, $year);
      if ($warning !== '') $warnings[] = $warning;
      $yearsChecked[$year] = true;
    }
    $start = $month.'-01';
    $end = date('Y-m-t', strtotime($start));
    $pl = groe_net_income($db, $start, $end);
    $equity = groe_total_equity($db, $year, $end);
    $ytdIncome = groe_ytd_income($db, $year, $end);
    $equityWithIncome = $equity['equity'] + $ytdIncome;
    if ($pl['line_count'] < 1) $warnings[] = groe_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.').' '.$month;
    if ($equity['line_count'] < 1 || abs($equityWithIncome) < 0.005) $warnings[] = groe_t('finance_no_equity_data_warning', 'Data modal belum tersedia untuk periode ini.').' '.$month;
    $roe = abs($equityWithIncome) < 0.005 ? null : ($pl['net_income'] / $equityWithIncome * 100);
    $rows[] = array('month'=>$month, 'net_income'=>$pl['net_income'], 'equity'=>$equityWithIncome, 'base_equity'=>$equity['equity'], 'ytd_income'=>$ytdIncome, 'roe'=>$roe);
  }
  return array($rows, array_values(array_unique($warnings)));
}

function groe_chart($rows)
{
  $labels = array(); $values = array();
  foreach ($rows as $row) { $labels[] = groe_month_label($row['month']); $values[] = $row['roe']; }
  return array('labels'=>$labels, 'datasets'=>array(array('label'=>groe_t('finance_roe_percent', 'ROE %'), 'data'=>$values, 'borderColor'=>'#7c3aed', 'backgroundColor'=>'#7c3aed', 'tension'=>0.25, 'fill'=>false)));
}

function groe_html($rows, $warnings)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.groe_h(groe_t('common_warning', 'Peringatan')).':</strong> '.groe_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed groe-table"><thead><tr class="bg-primary"><th>'.groe_h(groe_t('finance_month', 'Bulan')).'</th><th class="text-right">'.groe_h(groe_t('finance_net_income_period', 'Laba Bersih')).'</th><th class="text-right">'.groe_h(groe_t('finance_total_equity', 'Total Modal')).'</th><th class="text-right">'.groe_h(groe_t('finance_roe_percent', 'ROE %')).'</th></tr></thead><tbody>';
  if (!count($rows)) $html .= '<tr><td colspan="4" class="text-center text-muted">'.groe_h(groe_t('finance_empty_period_warning', 'Tidak ada data POSTED pada periode ini.')).'</td></tr>';
  foreach ($rows as $row) $html .= '<tr><td>'.groe_h(groe_month_label($row['month'])).'</td><td class="text-right">'.groe_num($row['net_income']).'</td><td class="text-right">'.groe_num($row['equity']).'</td><td class="text-right">'.groe_pct($row['roe']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function groe_svg_chart($rows)
{
  $width = 1060; $height = 300; $left = 70; $right = 24; $top = 28; $bottom = 52;
  $values = array();
  foreach ($rows as $row) if ($row['roe'] !== null) $values[] = (float)$row['roe'];
  if (!count($values)) $values = array(0);
  $min = min(0, min($values)); $max = max($values);
  if (abs($max - $min) < 0.005) { $max += 1; $min -= 1; }
  $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
  $xFor = function($i) use ($left, $plotW, $rows) { return $left + (count($rows) <= 1 ? 0 : ($plotW * $i / (count($rows) - 1))); };
  $yFor = function($v) use ($top, $plotH, $min, $max) { return $top + $plotH - (($v - $min) / ($max - $min) * $plotH); };
  $points = array();
  foreach ($rows as $i=>$row) if ($row['roe'] !== null) $points[] = round($xFor($i),2).','.round($yFor((float)$row['roe']),2);
  $svg = '<svg class="groe-print-chart" width="100%" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
  $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#fff"/><line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top+$plotH).'" stroke="#94a3b8"/><line x1="'.$left.'" y1="'.($top+$plotH).'" x2="'.($left+$plotW).'" y2="'.($top+$plotH).'" stroke="#94a3b8"/>';
  foreach ($rows as $i=>$row) $svg .= '<text x="'.round($xFor($i),2).'" y="'.($height-18).'" font-size="10" text-anchor="middle" fill="#475569">'.groe_h(groe_month_label($row['month'])).'</text>';
  $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#7c3aed" stroke-width="2.5"/>';
  $svg .= '<text x="'.$left.'" y="16" font-size="11" fill="#7c3aed">'.groe_h(groe_t('finance_roe_percent', 'ROE %')).'</text>';
  return $svg.'</svg>';
}

function groe_print_page($rows, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = groe_svg_chart($rows).groe_html($rows, $warnings);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.groe_h(groe_t('finance_report_roe_chart', 'Grafik Pengembalian pada Modal')).'</title><link rel="stylesheet" href="'.groe_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.groe-table th,.groe-table td{font-size:11px;border:1px solid #d2d6de!important}.no-print{margin-bottom:12px}@media print{.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.groe_h($company).'</h3><h4 style="margin:0 0 14px">'.groe_h(groe_t('finance_report_roe_chart', 'Grafik Pengembalian pada Modal')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = groe_filters();
  list($rows, $warnings) = groe_data($db, $filters);
  if ($act === 'filter') groe_json('success', 'OK', array('html'=>groe_html($rows, $warnings), 'warnings'=>$warnings, 'chart'=>groe_chart($rows)));
  if ($act === 'print') groe_print_page($rows, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('ROE'));
    $sheet->setCellValue('A4', groe_t('finance_month', 'Bulan'));
    $sheet->setCellValue('B4', groe_t('finance_net_income_period', 'Laba Bersih'));
    $sheet->setCellValue('C4', groe_t('finance_total_equity', 'Total Modal'));
    $sheet->setCellValue('D4', groe_t('finance_roe_percent', 'ROE %'));
    $r = 5;
    foreach ($rows as $row) {
      $sheet->setCellValue('A'.$r, groe_month_label($row['month']));
      $sheet->setCellValue('B'.$r, $row['net_income']);
      $sheet->setCellValue('C'.$r, $row['equity']);
      if ($row['roe'] === null) $sheet->setCellValue('D'.$r, '-'); else $sheet->setCellValue('D'.$r, $row['roe']);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(groe_t('finance_report_roe_chart', 'GRAFIK PENGEMBALIAN PADA MODAL')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>4, 'money_columns'=>array('B','C'), 'filters'=>array(groe_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('grafik_pengembalian_pada_modal_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="grafik_pengembalian_pada_modal_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  groe_json('error', groe_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  groe_json('error', $e->getMessage());
}
?>
