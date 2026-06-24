<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function glr_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function glr_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function glr_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function glr_num($value) { return number_format((float)$value, 2, '.', ','); }
function glr_ratio_text($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ','); }
function glr_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function glr_lower($value) { return strtolower(trim((string)$value)); }
function glr_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function glr_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function glr_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function glr_filters()
{
  $start = glr_req('start_month', date('Y-m'));
  $end = glr_req('end_month', date('Y-m'));
  if (!glr_month_ok($start) || !glr_month_ok($end)) throw new Exception(glr_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(glr_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = glr_months($start, $end);
  if (count($months) > 12) throw new Exception(glr_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function glr_table_exists($db, $table)
{
  $row = $db->fetch("SHOW TABLES LIKE ?", array($table));
  return $row ? true : false;
}

function glr_columns($db, $table)
{
  $rows = $db->query("SHOW COLUMNS FROM `$table`");
  if ($rows === false) return array();
  $columns = array();
  foreach ($rows as $row) $columns[] = $row->Field;
  return $columns;
}

function glr_pick_col($columns, $candidates)
{
  foreach ($candidates as $candidate) if (in_array($candidate, $columns)) return $candidate;
  return '';
}

function glr_mapping($db)
{
  $result = array('account'=>array(), 'category'=>array(), 'warnings'=>array(), 'using_mapping'=>false);
  if (!glr_table_exists($db, 'finance_ratio_mapping')) {
    $result['warnings'][] = glr_t('finance_ratio_mapping_fallback_warning', 'finance_ratio_mapping belum tersedia; report memakai fallback kategori COA.');
    return $result;
  }
  $columns = glr_columns($db, 'finance_ratio_mapping');
  $typeCol = glr_pick_col($columns, array('ratio_key','ratio_type','mapping_type','type','kategori_ratio','classification'));
  $accountCol = glr_pick_col($columns, array('no_rek','account_no','coa_no','rekening_no'));
  $categoryCol = glr_pick_col($columns, array('kat_coa','coa_kategori_id','kategori_id','category_id'));
  if ($typeCol === '' || ($accountCol === '' && $categoryCol === '')) {
    $result['warnings'][] = glr_t('finance_ratio_mapping_fallback_warning', 'finance_ratio_mapping belum tersedia; report memakai fallback kategori COA.');
    return $result;
  }
  $select = "`$typeCol` type_value";
  if ($accountCol !== '') $select .= ",`$accountCol` account_value";
  if ($categoryCol !== '') $select .= ",`$categoryCol` category_value";
  $where = in_array('is_active', $columns) ? " WHERE COALESCE(is_active,'Y') IN ('Y','1','aktif','active')" : '';
  $rows = $db->query("SELECT $select FROM finance_ratio_mapping$where");
  if ($rows === false) {
    $result['warnings'][] = glr_t('finance_ratio_mapping_fallback_warning', 'finance_ratio_mapping belum tersedia; report memakai fallback kategori COA.');
    return $result;
  }
  foreach ($rows as $row) {
    $type = glr_lower($row->type_value);
    $bucket = '';
    if (in_array($type, array('current_asset','aset_lancar','aset lancar','current assets','liquid_asset','liquidity_asset'))) $bucket = 'asset';
    if (in_array($type, array('current_liability','kewajiban_lancar','kewajiban lancar','current liabilities','liquid_liability','liquidity_liability'))) $bucket = 'liability';
    if ($bucket === '') continue;
    if (isset($row->account_value) && trim((string)$row->account_value) !== '') $result['account'][trim((string)$row->account_value)] = $bucket;
    if (isset($row->category_value) && trim((string)$row->category_value) !== '') $result['category'][trim((string)$row->category_value)] = $bucket;
  }
  if (!count($result['account']) && !count($result['category'])) $result['warnings'][] = glr_t('finance_ratio_mapping_fallback_warning', 'finance_ratio_mapping belum tersedia; report memakai fallback kategori COA.');
  else $result['using_mapping'] = true;
  return $result;
}

function glr_is_current_asset($category)
{
  $category = glr_lower($category);
  return strpos($category, 'lancar') !== false || strpos($category, 'kas') !== false || strpos($category, 'bank') !== false || strpos($category, 'piutang') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'dibayar di muka') !== false;
}

function glr_is_current_liability($category)
{
  $category = glr_lower($category);
  return strpos($category, 'jangka pendek') !== false || strpos($category, 'hutang usaha') !== false || strpos($category, 'utang usaha') !== false || strpos($category, 'hutang pajak') !== false || strpos($category, 'utang pajak') !== false || strpos($category, 'hutang biaya') !== false || strpos($category, 'utang biaya') !== false;
}

function glr_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return glr_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return glr_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function glr_balance($db, $year, $end, $mapping)
{
  $yearStart = $year.'-01-01';
  $rows = $db->query(
    "SELECT r.no_rek,k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun IN ('aset','kewajiban')",
    array($year, $yearStart, $end)
  );
  if ($rows === false) throw new Exception('Query rasio likuiditas gagal: '.$db->getErrorMessage());
  $totals = array('current_assets'=>0, 'current_liabilities'=>0, 'line_count'=>0);
  foreach ($rows as $row) {
    $saldo = (float)$row->saldo;
    if (abs($saldo) < 0.005) continue;
    $totals['line_count']++;
    $bucket = '';
    if (isset($mapping['account'][$row->no_rek])) $bucket = $mapping['account'][$row->no_rek];
    elseif (isset($mapping['category'][(string)$row->kategori_id])) $bucket = $mapping['category'][(string)$row->kategori_id];
    elseif (!$mapping['using_mapping']) {
      if ($row->kategori_akun === 'aset' && glr_is_current_asset($row->kategori)) $bucket = 'asset';
      if ($row->kategori_akun === 'kewajiban' && glr_is_current_liability($row->kategori)) $bucket = 'liability';
    }
    if ($bucket === 'asset') $totals['current_assets'] += $saldo;
    if ($bucket === 'liability') $totals['current_liabilities'] += $saldo;
  }
  $totals['ratio'] = abs($totals['current_liabilities']) < 0.005 ? null : ($totals['current_assets'] / $totals['current_liabilities']);
  return $totals;
}

function glr_data($db, $filters)
{
  $warnings = array();
  $mapping = glr_mapping($db);
  $warnings = array_merge($warnings, $mapping['warnings']);
  $rows = array();
  $yearsChecked = array();
  foreach ($filters['months'] as $month) {
    $year = (int)substr($month, 0, 4);
    if (!isset($yearsChecked[$year])) {
      $warning = glr_opening_warning($db, $year);
      if ($warning !== '') $warnings[] = $warning;
      $yearsChecked[$year] = true;
    }
    $end = date('Y-m-t', strtotime($month.'-01'));
    $balance = glr_balance($db, $year, $end, $mapping);
    if ($balance['line_count'] < 1) $warnings[] = glr_t('finance_no_balance_data_warning', 'Data neraca belum tersedia untuk tahun ini.').' '.$month;
    if ($balance['current_assets'] == 0 && $balance['current_liabilities'] == 0) $warnings[] = glr_t('finance_ratio_category_mapping_warning', 'Kategori aset/kewajiban lancar belum cukup untuk menghitung current ratio.').' '.$month;
    if ($balance['current_liabilities'] == 0 && $balance['current_assets'] != 0) $warnings[] = glr_t('finance_current_liability_zero_warning', 'Kewajiban lancar bernilai 0; current ratio tidak dapat dihitung.').' '.$month;
    $rows[] = array('month'=>$month, 'current_assets'=>$balance['current_assets'], 'current_liabilities'=>$balance['current_liabilities'], 'ratio'=>$balance['ratio']);
  }
  return array($rows, array_values(array_unique($warnings)));
}

function glr_chart($rows)
{
  $labels = array(); $ratios = array();
  foreach ($rows as $row) { $labels[] = glr_month_label($row['month']); $ratios[] = $row['ratio']; }
  return array('labels'=>$labels, 'datasets'=>array(array('label'=>glr_t('finance_current_ratio', 'Current Ratio'), 'data'=>$ratios, 'borderColor'=>'#1d4ed8', 'backgroundColor'=>'#1d4ed8', 'tension'=>0.25, 'fill'=>false)));
}

function glr_html($rows, $warnings)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.glr_h(glr_t('common_warning', 'Peringatan')).':</strong> '.glr_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed glr-table"><thead><tr class="bg-primary"><th>'.glr_h(glr_t('finance_month', 'Bulan')).'</th><th class="text-right">'.glr_h(glr_t('finance_current_assets', 'Aset Lancar')).'</th><th class="text-right">'.glr_h(glr_t('finance_current_liabilities', 'Kewajiban Lancar')).'</th><th class="text-right">'.glr_h(glr_t('finance_current_ratio', 'Current Ratio')).'</th></tr></thead><tbody>';
  if (!count($rows)) $html .= '<tr><td colspan="4" class="text-center text-muted">'.glr_h(glr_t('finance_empty_period_warning', 'Tidak ada data POSTED pada periode ini.')).'</td></tr>';
  foreach ($rows as $row) $html .= '<tr><td>'.glr_h(glr_month_label($row['month'])).'</td><td class="text-right">'.glr_num($row['current_assets']).'</td><td class="text-right">'.glr_num($row['current_liabilities']).'</td><td class="text-right">'.glr_ratio_text($row['ratio']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function glr_svg_chart($rows)
{
  $width = 1060; $height = 300; $left = 70; $right = 24; $top = 28; $bottom = 52;
  $values = array();
  foreach ($rows as $row) if ($row['ratio'] !== null) $values[] = (float)$row['ratio'];
  if (!count($values)) $values = array(0);
  $min = min(0, min($values)); $max = max($values);
  if (abs($max - $min) < 0.005) { $max += 1; $min -= 1; }
  $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
  $xFor = function($i) use ($left, $plotW, $rows) { return $left + (count($rows) <= 1 ? 0 : ($plotW * $i / (count($rows) - 1))); };
  $yFor = function($v) use ($top, $plotH, $min, $max) { return $top + $plotH - (($v - $min) / ($max - $min) * $plotH); };
  $points = array();
  foreach ($rows as $i=>$row) if ($row['ratio'] !== null) $points[] = round($xFor($i),2).','.round($yFor((float)$row['ratio']),2);
  $svg = '<svg class="glr-print-chart" width="100%" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
  $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#fff"/><line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top+$plotH).'" stroke="#94a3b8"/><line x1="'.$left.'" y1="'.($top+$plotH).'" x2="'.($left+$plotW).'" y2="'.($top+$plotH).'" stroke="#94a3b8"/>';
  foreach ($rows as $i=>$row) $svg .= '<text x="'.round($xFor($i),2).'" y="'.($height-18).'" font-size="10" text-anchor="middle" fill="#475569">'.glr_h(glr_month_label($row['month'])).'</text>';
  $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#1d4ed8" stroke-width="2.5"/>';
  $svg .= '<text x="'.$left.'" y="16" font-size="11" fill="#1d4ed8">'.glr_h(glr_t('finance_current_ratio', 'Current Ratio')).'</text>';
  return $svg.'</svg>';
}

function glr_print_page($rows, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = glr_svg_chart($rows).glr_html($rows, $warnings);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.glr_h(glr_t('finance_report_liquidity_ratio_chart', 'Grafik Rasio Likuiditas')).'</title><link rel="stylesheet" href="'.glr_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.glr-table th,.glr-table td{font-size:11px;border:1px solid #d2d6de!important}.no-print{margin-bottom:12px}@media print{.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.glr_h($company).'</h3><h4 style="margin:0 0 14px">'.glr_h(glr_t('finance_report_liquidity_ratio_chart', 'Grafik Rasio Likuiditas')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = glr_filters();
  list($rows, $warnings) = glr_data($db, $filters);
  if ($act === 'filter') glr_json('success', 'OK', array('html'=>glr_html($rows, $warnings), 'warnings'=>$warnings, 'chart'=>glr_chart($rows)));
  if ($act === 'print') glr_print_page($rows, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Liquidity Ratio'));
    $sheet->setCellValue('A4', glr_t('finance_month', 'Bulan'));
    $sheet->setCellValue('B4', glr_t('finance_current_assets', 'Aset Lancar'));
    $sheet->setCellValue('C4', glr_t('finance_current_liabilities', 'Kewajiban Lancar'));
    $sheet->setCellValue('D4', glr_t('finance_current_ratio', 'Current Ratio'));
    $r = 5;
    foreach ($rows as $row) {
      $sheet->setCellValue('A'.$r, glr_month_label($row['month']));
      $sheet->setCellValue('B'.$r, $row['current_assets']);
      $sheet->setCellValue('C'.$r, $row['current_liabilities']);
      if ($row['ratio'] === null) $sheet->setCellValue('D'.$r, '-'); else $sheet->setCellValue('D'.$r, $row['ratio']);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(glr_t('finance_report_liquidity_ratio_chart', 'GRAFIK RASIO LIKUIDITAS')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>4, 'money_columns'=>array('B','C'), 'filters'=>array(glr_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('grafik_rasio_likuiditas_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="grafik_rasio_likuiditas_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  glr_json('error', glr_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  glr_json('error', $e->getMessage());
}
?>
