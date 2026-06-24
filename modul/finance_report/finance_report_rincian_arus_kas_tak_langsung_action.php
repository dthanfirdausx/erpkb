<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cfr_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function cfr_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function cfr_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function cfr_num($value) { return number_format((float)$value, 2, '.', ','); }
function cfr_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function cfr_valid_date($value) { return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$value) && strtotime($value) !== false; }
function cfr_lower($value) { return strtolower(trim((string)$value)); }
function cfr_prev_date($date) { $dt = new DateTime($date); $dt->modify('-1 day'); return $dt->format('Y-m-d'); }

function cfr_table_exists($db, $table)
{
  return (bool)$db->fetch("SHOW TABLES LIKE ?", array($table));
}

function cfr_filters()
{
  $start = cfr_req('start_date', date('Y-m-01'));
  $end = cfr_req('end_date', date('Y-m-d'));
  if (!cfr_valid_date($start) || !cfr_valid_date($end)) throw new Exception(cfr_t('finance_invalid_date', 'Format tanggal tidak valid.'));
  if (strtotime($start) > strtotime($end)) throw new Exception(cfr_t('finance_start_after_end', 'Start date tidak boleh lebih besar dari end date.'));
  return array('start_date'=>$start, 'end_date'=>$end);
}

function cfr_empty_sections()
{
  return array(
    'operating'=>array('title'=>cfr_t('finance_operating_cash_flow', 'ARUS KAS DARI AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>0),
    'investing'=>array('title'=>cfr_t('finance_investing_cash_flow', 'ARUS KAS DARI AKTIVITAS INVESTASI'), 'rows'=>array(), 'total'=>0),
    'financing'=>array('title'=>cfr_t('finance_financing_cash_flow', 'ARUS KAS DARI AKTIVITAS PENDANAAN'), 'rows'=>array(), 'total'=>0)
  );
}

function cfr_add_row(&$section, $account, $label, $amount, $type, $source)
{
  $amount = (float)$amount;
  if (abs($amount) < 0.005) return;
  $section['rows'][] = array('account'=>$account, 'label'=>$label, 'amount'=>$amount, 'type'=>$type, 'source'=>$source);
  $section['total'] += $amount;
}

function cfr_mapping_stats($db)
{
  $row = $db->fetch(
    "SELECT COUNT(*) total,
            SUM(cash_flow_group='cash_equivalent') cash_count,
            SUM(cash_flow_group='non_cash') noncash_count,
            SUM(cash_flow_group IN ('operating','investing','financing')) activity_count
     FROM cash_flow_mapping
     WHERE is_active='Y'"
  );
  if ($row === false) throw new Exception('Query cash_flow_mapping gagal: '.$db->getErrorMessage());
  return $row;
}

function cfr_opening_warnings($db, $filters)
{
  $warnings = array();
  $years = array((int)date('Y', strtotime($filters['start_date']))=>true, (int)date('Y', strtotime($filters['end_date']))=>true);
  foreach (array_keys($years) as $year) {
    $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
    if (!$row || (int)$row->cnt < 1) {
      $warnings[] = cfr_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
      continue;
    }
    if (abs((float)$row->debet - (float)$row->kredit) > 0.01) {
      $warnings[] = cfr_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
    }
  }
  return $warnings;
}

function cfr_net_income($db, $filters)
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
    array($filters['start_date'], $filters['end_date'])
  );
  if ($row === false) throw new Exception('Query laba bersih gagal: '.$db->getErrorMessage());
  return array((float)$row->amount, (int)$row->line_count);
}

function cfr_balance_rows($db, $asOfDate, $openingDate = null)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $params = array($year);
  if ($openingDate !== null && $openingDate === $yearStart) {
    $journalSql = "SELECT d.no_rek,0 debet,0 kredit FROM jurnal_detail d WHERE 1=0 GROUP BY d.no_rek";
  } else {
    $journalSql = "SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit
      FROM jurnal_detail d
      INNER JOIN jurnal_header h ON h.id=d.id_header
      WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'
      GROUP BY d.no_rek";
    $params[] = $yearStart;
    $params[] = $asOfDate;
  }
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori,k.kategori_akun,k.saldo_normal,
            COALESCE(m.cash_flow_group,'') cash_flow_group,
            COALESCE(m.cash_flow_type,'') cash_flow_type,
            CASE WHEN k.saldo_normal='kredit'
              THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
              ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
            END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN ($journalSql) j ON j.no_rek=r.no_rek
     LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.kategori,LENGTH(r.no_rek),r.no_rek",
    $params
  );
  if ($rows === false) throw new Exception('Query saldo akun gagal: '.$db->getErrorMessage());
  $map = array();
  foreach ($rows as $row) $map[$row->no_rek] = $row;
  return $map;
}

function cfr_begin_balance_rows($db, $startDate)
{
  if (substr($startDate, 5) === '01-01') return cfr_balance_rows($db, $startDate, $startDate);
  return cfr_balance_rows($db, cfr_prev_date($startDate), null);
}

function cfr_is_cash($row)
{
  $mapping = cfr_lower($row->cash_flow_group);
  $category = cfr_lower($row->kategori);
  return $mapping === 'cash_equivalent' || strpos($category, 'kas') !== false || strpos($category, 'bank') !== false;
}

function cfr_balance_class($row)
{
  $mapping = cfr_lower($row->cash_flow_group);
  if (in_array($mapping, array('operating','investing','financing','non_cash'))) return $mapping;
  $category = cfr_lower($row->kategori);
  if (strpos($category, 'akumulasi') !== false || strpos($category, 'penyusutan') !== false || strpos($category, 'amortisasi') !== false) return 'non_cash';
  if ($row->kategori_akun === 'aset' && (strpos($category, 'aset tetap') !== false || strpos($category, 'aset lainnya') !== false)) return 'investing';
  if ($row->kategori_akun === 'kewajiban' && strpos($category, 'jangka panjang') !== false) return 'financing';
  if ($row->kategori_akun === 'modal') return 'financing';
  return 'operating';
}

function cfr_delta_cash_effect($row, $delta, $group)
{
  if ($group === 'operating' || $group === 'investing' || $group === 'financing') {
    return $row->kategori_akun === 'aset' ? -$delta : $delta;
  }
  if ($group === 'non_cash') {
    if ($row->kategori_akun === 'aset') return $row->saldo_normal === 'kredit' ? $delta : -$delta;
    return $delta;
  }
  return 0;
}

function cfr_non_cash_profit_loss($db, $filters)
{
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori_akun,
      SUM(CASE
        WHEN k.kategori_akun='pendapatan' THEN -(COALESCE(d.kredit,0)-COALESCE(d.debet,0))
        ELSE COALESCE(d.debet,0)-COALESCE(d.kredit,0)
      END) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     INNER JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y' AND m.cash_flow_group='non_cash'
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY r.no_rek,r.nama_rek,k.kategori_akun
     ORDER BY r.no_rek",
    array($filters['start_date'], $filters['end_date'])
  );
  if ($rows === false) throw new Exception('Query adjustment non-cash gagal: '.$db->getErrorMessage());
  return $rows;
}

function cfr_cash_total($rows)
{
  $total = 0;
  foreach ($rows as $row) if (cfr_is_cash($row)) $total += (float)$row->saldo;
  return $total;
}

function cfr_data($db, $filters)
{
  if (!cfr_table_exists($db, 'cash_flow_mapping')) throw new Exception(cfr_t('finance_cash_flow_mapping_missing', 'Tabel cash_flow_mapping belum tersedia.'));

  $warnings = array();
  $stats = cfr_mapping_stats($db);
  if (!$stats || (int)$stats->total < 1) $warnings[] = cfr_t('finance_cash_flow_mapping_empty', 'cash_flow_mapping belum diisi; klasifikasi memakai fallback kategori COA resmi.');
  if ($stats && (int)$stats->cash_count < 1) $warnings[] = cfr_t('finance_cash_mapping_missing_warning', 'Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA Kas & Bank.');
  if ($stats && ((int)$stats->noncash_count < 1 || (int)$stats->activity_count < 1)) $warnings[] = cfr_t('finance_cash_mapping_detail_missing_warning', 'Mapping non-cash/operasi/investasi/pendanaan belum lengkap; beberapa akun memakai fallback kategori COA.');
  $warnings = array_merge($warnings, cfr_opening_warnings($db, $filters));

  $sections = cfr_empty_sections();
  list($netIncome, $plLines) = cfr_net_income($db, $filters);
  if ($plLines < 1) $warnings[] = cfr_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.');
  cfr_add_row($sections['operating'], '', cfr_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode'), $netIncome, 'net_income', 'posted_journal');

  foreach (cfr_non_cash_profit_loss($db, $filters) as $row) {
    cfr_add_row($sections['operating'], $row->no_rek, cfr_t('finance_non_cash_adjustment', 'Adjustment non-cash').' - '.$row->nama_rek, (float)$row->amount, 'non_cash', 'mapping');
  }

  $beginRows = cfr_begin_balance_rows($db, $filters['start_date']);
  $endRows = cfr_balance_rows($db, $filters['end_date'], null);
  $cashBegin = cfr_cash_total($beginRows);
  $cashEnd = cfr_cash_total($endRows);
  $accounts = array_unique(array_merge(array_keys($beginRows), array_keys($endRows)));
  sort($accounts);

  foreach ($accounts as $account) {
    $row = isset($endRows[$account]) ? $endRows[$account] : $beginRows[$account];
    if (cfr_is_cash($row)) continue;
    $begin = isset($beginRows[$account]) ? (float)$beginRows[$account]->saldo : 0;
    $end = isset($endRows[$account]) ? (float)$endRows[$account]->saldo : 0;
    $delta = $end - $begin;
    if (abs($delta) < 0.005) continue;
    $group = cfr_balance_class($row);
    $amount = cfr_delta_cash_effect($row, $delta, $group);
    $source = $row->cash_flow_group !== '' ? 'mapping' : 'coa';
    if ($group === 'non_cash') {
      cfr_add_row($sections['operating'], $row->no_rek, cfr_t('finance_non_cash_adjustment', 'Adjustment non-cash').' - '.$row->nama_rek, $amount, 'non_cash', $source);
    } elseif ($group === 'operating') {
      cfr_add_row($sections['operating'], $row->no_rek, cfr_t('finance_working_capital_change', 'Perubahan aset/kewajiban operasional').' - '.$row->nama_rek, $amount, 'working_capital', $source);
    } elseif ($group === 'investing') {
      cfr_add_row($sections['investing'], $row->no_rek, cfr_t('finance_balance_change', 'Perubahan saldo akun').' - '.$row->nama_rek, $amount, 'balance_change', $source);
    } elseif ($group === 'financing') {
      cfr_add_row($sections['financing'], $row->no_rek, cfr_t('finance_balance_change', 'Perubahan saldo akun').' - '.$row->nama_rek, $amount, 'balance_change', $source);
    }
  }

  $netFlow = $sections['operating']['total'] + $sections['investing']['total'] + $sections['financing']['total'];
  $cashDelta = $cashEnd - $cashBegin;
  if (abs($netFlow - $cashDelta) > 0.01) $warnings[] = cfr_t('finance_cash_reconciliation_warning', 'Net cash flow belum sama dengan perubahan saldo kas; cek jurnal transfer kas, mapping kas/bank, atau jurnal multi-akun.');

  return array($sections, $warnings, array(
    'opening_cash'=>$cashBegin,
    'ending_cash'=>$cashEnd,
    'net_cash_flow'=>$netFlow,
    'cash_delta'=>$cashDelta,
    'reconciliation_diff'=>$netFlow - $cashDelta
  ));
}

function cfr_html($sections, $warnings, $summary, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.cfr_h(cfr_t('common_warning', 'Peringatan')).':</strong> '.cfr_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.cfr_h(cfr_t('finance_source_summary', 'Sumber: jurnal_header/detail POSTED, rekening, coa_kategori, saldo_awal, dan cash_flow_mapping.')).' '.cfr_h($filters['start_date']).' s/d '.cfr_h($filters['end_date']).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed cfi-table"><thead><tr class="bg-primary"><th style="width:150px">COA</th><th>'.cfr_h(cfr_t('common_description', 'Uraian')).'</th><th class="text-right" style="width:180px">'.cfr_h(cfr_t('common_amount', 'Nilai')).'</th></tr></thead><tbody>';

  foreach ($sections as $section) {
    $html .= '<tr class="cfi-section"><th colspan="3">'.cfr_h($section['title']).'</th></tr>';
    if (!count($section['rows'])) {
      $html .= '<tr><td></td><td class="text-muted"><em>'.cfr_h(cfr_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</em></td><td class="text-right">0.00</td></tr>';
    } else {
      $lastType = '';
      foreach ($section['rows'] as $row) {
        if ($row['type'] !== $lastType) {
          $lastType = $row['type'];
          $typeLabel = $lastType === 'net_income' ? cfr_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode') : ($lastType === 'non_cash' ? cfr_t('finance_non_cash_adjustment', 'Adjustment non-cash') : ($lastType === 'working_capital' ? cfr_t('finance_working_capital_change', 'Perubahan aset/kewajiban operasional') : cfr_t('finance_balance_change', 'Perubahan saldo akun')));
          $html .= '<tr class="cfi-subsection"><td></td><td colspan="2">'.cfr_h($typeLabel).'</td></tr>';
        }
        $html .= '<tr><td>'.cfr_h($row['account']).'</td><td class="cfi-account">'.cfr_h($row['label']).'</td><td class="text-right">'.cfr_num($row['amount']).'</td></tr>';
      }
    }
    $html .= '<tr class="cfi-total"><th></th><th>Subtotal '.cfr_h($section['title']).'</th><td class="text-right">'.cfr_num($section['total']).'</td></tr>';
  }

  $html .= '<tr class="cfi-grand"><th></th><th>'.cfr_h(cfr_t('finance_net_cash_flow', 'NET CASH FLOW')).'</th><td class="text-right">'.cfr_num($summary['net_cash_flow']).'</td></tr>';
  $html .= '<tr class="cfi-total"><th></th><th>'.cfr_h(cfr_t('finance_opening_cash', 'Saldo Kas Awal')).'</th><td class="text-right">'.cfr_num($summary['opening_cash']).'</td></tr>';
  $html .= '<tr class="cfi-total"><th></th><th>'.cfr_h(cfr_t('finance_ending_cash', 'Saldo Kas Akhir')).'</th><td class="text-right">'.cfr_num($summary['ending_cash']).'</td></tr>';
  $html .= '<tr class="cfi-check"><th></th><th>'.cfr_h(cfr_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas')).'</th><td class="text-right">'.cfr_num($summary['reconciliation_diff']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function cfr_print_page($sections, $warnings, $summary, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = cfr_html($sections, $warnings, $summary, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.cfr_h(cfr_t('finance_report_cash_flow_indirect_detail', 'Rincian Arus Kas (Tak Langsung)')).'</title><link rel="stylesheet" href="'.cfr_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.cfi-table{width:100%;border-collapse:collapse!important}.cfi-table th,.cfi-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cfi-section th{background:#1d4ed8!important;color:#fff!important}.cfi-subsection td{background:#eef2ff!important;font-weight:bold}.cfi-total th,.cfi-total td{background:#f3f4f6!important;font-weight:bold}.cfi-grand th,.cfi-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.cfi-check th,.cfi-check td{background:#fff7ed!important;font-weight:bold}.cfi-account{padding-left:24px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.cfi-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cfr_h($company).'</h3><h4 style="margin:0 0 14px">'.cfr_h(cfr_t('finance_report_cash_flow_indirect_detail', 'Rincian Arus Kas (Tak Langsung)')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

function cfr_summary_payload($summary)
{
  return array(
    'opening_cash'=>$summary['opening_cash'],
    'ending_cash'=>$summary['ending_cash'],
    'net_cash_flow'=>$summary['net_cash_flow'],
    'reconciliation_diff'=>$summary['reconciliation_diff'],
    'opening_cash_text'=>cfr_num($summary['opening_cash']),
    'ending_cash_text'=>cfr_num($summary['ending_cash']),
    'net_cash_flow_text'=>cfr_num($summary['net_cash_flow']),
    'reconciliation_diff_text'=>cfr_num($summary['reconciliation_diff'])
  );
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = cfr_filters();
  list($sections, $warnings, $summary) = cfr_data($db, $filters);
  if ($act === 'filter') cfr_json('success', 'OK', array('html'=>cfr_html($sections, $warnings, $summary, $filters), 'warnings'=>$warnings, 'summary'=>cfr_summary_payload($summary)));
  if ($act === 'print') cfr_print_page($sections, $warnings, $summary, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Rincian Arus Kas'));
    $sheet->setCellValue('A4', 'COA');
    $sheet->setCellValue('B4', cfr_t('common_description', 'Uraian'));
    $sheet->setCellValue('C4', cfr_t('common_amount', 'Nilai'));
    $r = 5;
    foreach ($sections as $section) {
      $sheet->setCellValue('A'.$r, $section['title']);
      $sheet->mergeCells('A'.$r.':C'.$r);
      $sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
      $sheet->getStyle('A'.$r.':C'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
      $r++;
      foreach ($section['rows'] as $row) {
        $sheet->setCellValueExplicit('A'.$r, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValue('B'.$r, $row['label']);
        $sheet->setCellValue('C'.$r, $row['amount']);
        $r++;
      }
      $sheet->setCellValue('B'.$r, 'Subtotal '.$section['title']);
      $sheet->setCellValue('C'.$r, $section['total']);
      $sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true);
      $r++;
    }
    foreach (array(cfr_t('finance_net_cash_flow', 'NET CASH FLOW')=>$summary['net_cash_flow'], cfr_t('finance_opening_cash', 'Saldo Kas Awal')=>$summary['opening_cash'], cfr_t('finance_ending_cash', 'Saldo Kas Akhir')=>$summary['ending_cash'], cfr_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas')=>$summary['reconciliation_diff']) as $label=>$amount) {
      $sheet->setCellValue('B'.$r, $label);
      $sheet->setCellValue('C'.$r, $amount);
      $sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(cfr_t('finance_report_cash_flow_indirect_detail', 'RINCIAN ARUS KAS TAK LANGSUNG')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>3, 'money_columns'=>array('C'), 'filters'=>array(cfr_t('finance_period', 'Periode')=>$filters['start_date'].' s/d '.$filters['end_date'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('rincian_arus_kas_tak_langsung_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="rincian_arus_kas_tak_langsung_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  cfr_json('error', cfr_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  cfr_json('error', $e->getMessage());
}
?>
