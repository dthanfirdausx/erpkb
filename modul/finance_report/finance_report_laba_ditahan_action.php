<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function fre_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function fre_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function fre_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function fre_num($value) { return number_format((float)$value, 2, '.', ','); }
function fre_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function fre_valid_date($value) { return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$value) && strtotime($value) !== false; }

function fre_table_exists($db, $table)
{
  return (bool)$db->fetch("SHOW TABLES LIKE ?", array($table));
}

function fre_filters()
{
  $year = fre_req('as_of_year', date('Y'));
  $date = fre_req('as_of_date', '');
  if ($date === '') $date = $year.'-12-31';
  if (!preg_match('/^\d{4}$/', $year)) throw new Exception(fre_t('finance_invalid_year', 'Format tahun tidak valid.'));
  if (!fre_valid_date($date)) throw new Exception(fre_t('finance_invalid_date', 'Format tanggal tidak valid.'));
  return array('as_of_year'=>(int)$year, 'as_of_date'=>$date, 'year_start'=>date('Y', strtotime($date)).'-01-01');
}

function fre_mapping_accounts($db)
{
  $warnings = array();
  $accounts = array();
  if (fre_table_exists($db, 'finance_retained_earning_mapping')) {
    $rows = $db->query(
      "SELECT r.no_rek,r.nama_rek,k.saldo_normal,'mapping' source
       FROM finance_retained_earning_mapping m
       INNER JOIN rekening r ON r.no_rek=m.no_rek
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       WHERE COALESCE(m.is_active,'Y')='Y'"
    );
    if ($rows === false) throw new Exception('Query mapping laba ditahan gagal: '.$db->getErrorMessage());
    foreach ($rows as $row) $accounts[$row->no_rek] = $row;
  } else {
    $warnings[] = fre_t('finance_retained_mapping_missing_warning', 'Mapping laba ditahan belum tersedia; akun laba ditahan dideteksi dari nama akun modal.');
  }
  if (!count($accounts)) {
    $rows = $db->query(
      "SELECT r.no_rek,r.nama_rek,k.saldo_normal,'fallback' source
       FROM rekening r
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       LEFT JOIN rekening child ON child.induk=r.no_rek
       WHERE k.kategori_akun='modal'
         AND child.no_rek IS NULL
         AND LOWER(r.nama_rek) LIKE '%laba%'
         AND (LOWER(r.nama_rek) LIKE '%ditahan%' OR LOWER(r.nama_rek) LIKE '%rugi%')
       ORDER BY LENGTH(r.no_rek) DESC,r.no_rek"
    );
    if ($rows === false) throw new Exception('Query akun laba ditahan gagal: '.$db->getErrorMessage());
    foreach ($rows as $row) $accounts[$row->no_rek] = $row;
    if (!count($accounts)) $warnings[] = fre_t('finance_retained_account_missing_warning', 'Akun laba ditahan tidak ditemukan. Isi finance_retained_earning_mapping untuk hasil resmi.');
  }
  return array(array_values($accounts), $warnings);
}

function fre_account_filter($accounts)
{
  $ids = array();
  foreach ($accounts as $account) $ids[] = $account->no_rek;
  if (!count($ids)) return array('1=0', array());
  return array('d.no_rek IN ('.implode(',', array_fill(0, count($ids), '?')).')', $ids);
}

function fre_retained_opening($db, $accounts, $year)
{
  if (!count($accounts)) return 0;
  list($filter, $params) = fre_account_filter($accounts);
  array_unshift($params, $year);
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN k.saldo_normal='kredit'
       THEN COALESCE(sa.kredit,0)-COALESCE(sa.debet,0)
       ELSE COALESCE(sa.debet,0)-COALESCE(sa.kredit,0)
     END),0) amount
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN saldo_awal sa ON sa.no_rek=r.no_rek AND sa.periode=?
     WHERE r.no_rek IN (".implode(',', array_fill(0, count($accounts), '?')).")",
    $params
  );
  if ($row === false) throw new Exception('Query saldo awal laba ditahan gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}

function fre_retained_adjustments($db, $accounts, $start, $end)
{
  if (!count($accounts)) return 0;
  list($filter, $params) = fre_account_filter($accounts);
  $params = array_merge(array($start, $end), $params);
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN k.saldo_normal='kredit'
       THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE COALESCE(d.debet,0)-COALESCE(d.kredit,0)
     END),0) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND $filter",
    $params
  );
  if ($row === false) throw new Exception('Query penyesuaian laba ditahan gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}

function fre_net_income($db, $start, $end)
{
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE
       WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE 0 END),0) amount,
       COUNT(*) line_count
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

function fre_prior_income($db, $yearStart)
{
  $row = $db->fetch(
    "SELECT MIN(h.tgl_jurnal) first_date
     FROM jurnal_header h
     INNER JOIN jurnal_detail d ON d.id_header=h.id
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.posting_status='POSTED'
       AND h.tgl_jurnal < ?
       AND k.kategori_akun IN ('pendapatan','beban')",
    array($yearStart)
  );
  if (!$row || !$row->first_date) return array(0, 0);
  return fre_net_income($db, $row->first_date, date('Y-m-d', strtotime($yearStart.' -1 day')));
}

function fre_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return fre_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return fre_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function fre_data($db, $filters)
{
  list($accounts, $warnings) = fre_mapping_accounts($db);
  $year = (int)date('Y', strtotime($filters['as_of_date']));
  $yearStart = $year.'-01-01';
  $openingWarning = fre_opening_warning($db, $year);
  if ($openingWarning !== '') $warnings[] = $openingWarning;
  $opening = fre_retained_opening($db, $accounts, $year);
  list($priorIncome, $priorLines) = fre_prior_income($db, $yearStart);
  $adjustment = fre_retained_adjustments($db, $accounts, $yearStart, $filters['as_of_date']);
  list($currentIncome, $currentLines) = fre_net_income($db, $yearStart, $filters['as_of_date']);
  if ($currentLines < 1) $warnings[] = fre_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.');
  $ending = $opening + $priorIncome + $adjustment + $currentIncome;
  return array($accounts, array('opening'=>$opening, 'prior_income'=>$priorIncome, 'adjustment'=>$adjustment, 'current_income'=>$currentIncome, 'ending'=>$ending, 'prior_lines'=>$priorLines, 'current_lines'=>$currentLines), array_values(array_unique($warnings)));
}

function fre_html($accounts, $data, $warnings, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.fre_h(fre_t('common_warning', 'Peringatan')).':</strong> '.fre_h(implode(' ', $warnings)).'</div>';
  $labels = array();
  foreach ($accounts as $account) $labels[] = $account->no_rek.' - '.$account->nama_rek;
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.fre_h(fre_t('finance_retained_source_summary', 'Sumber: jurnal_header/detail POSTED, rekening, coa_kategori, dan saldo_awal.')).' '.fre_h($filters['as_of_date']).'<br>'.fre_h(fre_t('finance_retained_accounts', 'Akun laba ditahan')).': '.fre_h(count($labels) ? implode(', ', $labels) : '-').'</div>';
  $html .= '<div class="row">';
  foreach (array(
    array('label'=>fre_t('finance_retained_opening_balance', 'Saldo Awal Laba Ditahan'), 'value'=>$data['opening'], 'class'=>'gray'),
    array('label'=>fre_t('finance_prior_year_income', 'Laba Tahun Sebelumnya'), 'value'=>$data['prior_income'], 'class'=>'green'),
    array('label'=>fre_t('finance_current_year_income', 'Laba Berjalan'), 'value'=>$data['current_income'], 'class'=>'green'),
    array('label'=>fre_t('finance_retained_ending_balance', 'Saldo Akhir Laba Ditahan'), 'value'=>$data['ending'], 'class'=>'orange')
  ) as $card) {
    $html .= '<div class="col-md-3 col-sm-6"><div class="fre-card '.$card['class'].'"><div class="label-text">'.fre_h($card['label']).'</div><div class="value-text">'.fre_num($card['value']).'</div></div></div>';
  }
  $html .= '</div><div class="table-responsive"><table class="table table-bordered table-condensed fre-table"><tbody>';
  $html .= '<tr class="fre-section"><th colspan="2">'.fre_h(fre_t('finance_retained_components', 'Komponen Laba Ditahan')).'</th></tr>';
  $rows = array(
    fre_t('finance_retained_opening_balance', 'Saldo Awal Laba Ditahan')=>$data['opening'],
    fre_t('finance_prior_year_income', 'Laba Tahun Sebelumnya')=>$data['prior_income'],
    fre_t('finance_dividend_adjustment', 'Dividen/Penyesuaian')=>$data['adjustment'],
    fre_t('finance_current_year_income', 'Laba Berjalan')=>$data['current_income']
  );
  foreach ($rows as $label=>$amount) $html .= '<tr><td>'.fre_h($label).'</td><td class="text-right">'.fre_num($amount).'</td></tr>';
  $html .= '<tr class="fre-grand"><th>'.fre_h(fre_t('finance_retained_ending_balance', 'Saldo Akhir Laba Ditahan')).'</th><td class="text-right">'.fre_num($data['ending']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function fre_print_page($accounts, $data, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = fre_html($accounts, $data, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.fre_h(fre_t('finance_report_retained_earnings', 'Laba Ditahan')).'</title><link rel="stylesheet" href="'.fre_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:980px;margin:18px auto}.fre-card{border:1px solid #d2d6de;border-left:4px solid #1d4ed8;padding:8px;margin-bottom:8px;min-height:70px}.fre-card .label-text{font-size:10px;color:#555;text-transform:uppercase}.fre-card .value-text{font-size:16px;font-weight:bold}.fre-table th,.fre-table td{font-size:11px;border:1px solid #d2d6de!important}.fre-section th{background:#1d4ed8!important;color:#fff!important}.fre-grand th,.fre-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.fre_h($company).'</h3><h4 style="margin:0 0 14px">'.fre_h(fre_t('finance_report_retained_earnings', 'Laba Ditahan')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = fre_filters();
  list($accounts, $data, $warnings) = fre_data($db, $filters);
  if ($act === 'filter') fre_json('success', 'OK', array('html'=>fre_html($accounts, $data, $warnings, $filters), 'warnings'=>$warnings));
  if ($act === 'print') fre_print_page($accounts, $data, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Laba Ditahan'));
    $sheet->setCellValue('A4', fre_t('common_description', 'Uraian'));
    $sheet->setCellValue('B4', fre_t('common_amount', 'Nilai'));
    $rows = array(
      fre_t('finance_retained_opening_balance', 'Saldo Awal Laba Ditahan')=>$data['opening'],
      fre_t('finance_prior_year_income', 'Laba Tahun Sebelumnya')=>$data['prior_income'],
      fre_t('finance_dividend_adjustment', 'Dividen/Penyesuaian')=>$data['adjustment'],
      fre_t('finance_current_year_income', 'Laba Berjalan')=>$data['current_income'],
      fre_t('finance_retained_ending_balance', 'Saldo Akhir Laba Ditahan')=>$data['ending']
    );
    $r = 5;
    foreach ($rows as $label=>$amount) {
      $sheet->setCellValue('A'.$r, $label);
      $sheet->setCellValue('B'.$r, $amount);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(fre_t('finance_report_retained_earnings', 'LABA DITAHAN')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>2, 'money_columns'=>array('B'), 'filters'=>array(fre_t('finance_as_of_date', 'As Of Date')=>$filters['as_of_date'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('laba_ditahan_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laba_ditahan_'.$filters['as_of_date'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  fre_json('error', fre_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  fre_json('error', $e->getMessage());
}
?>
