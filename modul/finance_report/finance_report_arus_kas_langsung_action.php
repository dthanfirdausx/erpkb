<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cfd_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function cfd_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra)); exit; }
function cfd_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function cfd_num($value) { return number_format((float)$value, 2, '.', ','); }
function cfd_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function cfd_valid_date($value) { return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$value) && strtotime($value) !== false; }
function cfd_lower($value) { return strtolower(trim((string)$value)); }

function cfd_table_exists($db, $table)
{
  $row = $db->fetch("SHOW TABLES LIKE ?", array($table));
  return (bool)$row;
}

function cfd_filters($db)
{
  $start = cfd_req('start_date', date('Y-m-01'));
  $end = cfd_req('end_date', date('Y-m-d'));
  $cashAccount = cfd_req('cash_account');
  if (!cfd_valid_date($start) || !cfd_valid_date($end)) throw new Exception(cfd_t('finance_invalid_date', 'Format tanggal tidak valid.'));
  if (strtotime($start) > strtotime($end)) throw new Exception(cfd_t('finance_start_after_end', 'Start date tidak boleh lebih besar dari end date.'));
  if ($cashAccount !== '') {
    $account = $db->fetch(
      "SELECT r.no_rek
       FROM rekening r
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
       WHERE r.no_rek=?
         AND (m.cash_flow_group='cash_equivalent' OR LOWER(k.kategori) LIKE '%kas%' OR LOWER(k.kategori) LIKE '%bank%')
       LIMIT 1",
      array($cashAccount)
    );
    if (!$account) throw new Exception(cfd_t('finance_cash_account_invalid', 'Akun kas/bank tidak valid.'));
  }
  return array('start_date'=>$start, 'end_date'=>$end, 'cash_account'=>$cashAccount);
}

function cfd_cash_accounts($db)
{
  if (!cfd_table_exists($db, 'cash_flow_mapping')) {
    throw new Exception(cfd_t('finance_cash_flow_mapping_missing', 'Tabel cash_flow_mapping belum tersedia.'));
  }
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori,
            CASE WHEN m.cash_flow_group='cash_equivalent' THEN 'mapping' ELSE 'coa' END source
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
     WHERE m.cash_flow_group='cash_equivalent'
        OR LOWER(k.kategori) LIKE '%kas%'
        OR LOWER(k.kategori) LIKE '%bank%'
     ORDER BY r.no_rek"
  );
  if ($rows === false) throw new Exception('Query akun kas/bank gagal: '.$db->getErrorMessage());
  $accounts = array();
  foreach ($rows as $row) $accounts[$row->no_rek] = array('no_rek'=>$row->no_rek, 'nama_rek'=>$row->nama_rek, 'kategori'=>$row->kategori, 'source'=>$row->source);
  return array_values($accounts);
}

function cfd_empty_sections()
{
  return array(
    'operating_in'=>array('title'=>cfd_t('finance_operating_receipts', 'PENERIMAAN DARI AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>0),
    'operating_out'=>array('title'=>cfd_t('finance_operating_payments', 'PEMBAYARAN UNTUK AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>0),
    'investing'=>array('title'=>cfd_t('finance_investing_cash_flow', 'ARUS KAS DARI AKTIVITAS INVESTASI'), 'rows'=>array(), 'total'=>0),
    'financing'=>array('title'=>cfd_t('finance_financing_cash_flow', 'ARUS KAS DARI AKTIVITAS PENDANAAN'), 'rows'=>array(), 'total'=>0)
  );
}

function cfd_classify_counter($row, $amount)
{
  $mapping = cfd_lower($row->cash_flow_group);
  if ($mapping === 'investing') return 'investing';
  if ($mapping === 'financing') return 'financing';
  if ($mapping === 'operating') return $amount >= 0 ? 'operating_in' : 'operating_out';
  $kategori = cfd_lower($row->kategori);
  if ($row->kategori_akun === 'modal') return 'financing';
  if ($row->kategori_akun === 'kewajiban' && strpos($kategori, 'jangka panjang') !== false) return 'financing';
  if ($row->kategori_akun === 'aset' && (strpos($kategori, 'aset tetap') !== false || strpos($kategori, 'aset lainnya') !== false)) return 'investing';
  return $amount >= 0 ? 'operating_in' : 'operating_out';
}

function cfd_add_row(&$section, $account, $label, $amount, $source)
{
  if (abs((float)$amount) < 0.005) return;
  $key = $account.'|'.$label.'|'.$source;
  if (!isset($section['rows'][$key])) {
    $section['rows'][$key] = array('account'=>$account, 'label'=>$label, 'amount'=>0, 'source'=>$source);
  }
  $section['rows'][$key]['amount'] += (float)$amount;
  $section['total'] += (float)$amount;
}

function cfd_cash_balance($db, $asOfDate, $cashAccount)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $params = array($year, $yearStart, $asOfDate);
  $filter = '';
  if ($cashAccount !== '') {
    $filter = ' AND r.no_rek=?';
    $params[] = $cashAccount;
  }
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN k.saldo_normal='kredit'
       THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
       ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
     END),0) saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE (m.cash_flow_group='cash_equivalent' OR LOWER(k.kategori) LIKE '%kas%' OR LOWER(k.kategori) LIKE '%bank%') $filter",
    $params
  );
  if ($row === false) throw new Exception('Query saldo kas gagal: '.$db->getErrorMessage());
  return (float)$row->saldo;
}

function cfd_prev_date($date)
{
  $dt = new DateTime($date);
  $dt->modify('-1 day');
  return $dt->format('Y-m-d');
}

function cfd_opening_cash($db, $filters)
{
  if (substr($filters['start_date'], 5) === '01-01') {
    $year = (int)date('Y', strtotime($filters['start_date']));
    $params = array($year);
    $filter = '';
    if ($filters['cash_account'] !== '') {
      $filter = ' AND r.no_rek=?';
      $params[] = $filters['cash_account'];
    }
    $row = $db->fetch(
      "SELECT COALESCE(SUM(CASE WHEN k.saldo_normal='kredit' THEN COALESCE(sa.kredit,0)-COALESCE(sa.debet,0) ELSE COALESCE(sa.debet,0)-COALESCE(sa.kredit,0) END),0) saldo
       FROM rekening r
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
       LEFT JOIN saldo_awal sa ON sa.no_rek=r.no_rek AND sa.periode=?
       WHERE (m.cash_flow_group='cash_equivalent' OR LOWER(k.kategori) LIKE '%kas%' OR LOWER(k.kategori) LIKE '%bank%') $filter",
      $params
    );
    if ($row === false) throw new Exception('Query saldo awal kas gagal: '.$db->getErrorMessage());
    return (float)$row->saldo;
  }
  return cfd_cash_balance($db, cfd_prev_date($filters['start_date']), $filters['cash_account']);
}

function cfd_rows($db, $filters)
{
  $params = array($filters['start_date'], $filters['end_date']);
  $cashFilter = '';
  if ($filters['cash_account'] !== '') {
    $cashFilter = ' AND cash.no_rek=?';
    $params[] = $filters['cash_account'];
  }
  $rows = $db->query(
    "SELECT h.id,h.no_jurnal,h.no_bukti,h.tgl_jurnal,h.source_module,
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
            COALESCE(m.cash_flow_group,'') cash_flow_group,
            COALESCE(m.cash_flow_type,'') cash_flow_type
     FROM jurnal_header h
     INNER JOIN jurnal_detail cash ON cash.id_header=h.id
     INNER JOIN rekening rcash ON rcash.no_rek=cash.no_rek
     INNER JOIN coa_kategori kcash ON kcash.id=rcash.kat_coa
     LEFT JOIN cash_flow_mapping mcash ON mcash.no_rek=cash.no_rek AND mcash.is_active='Y'
     INNER JOIN jurnal_detail counter ON counter.id_header=h.id AND counter.id<>cash.id
     INNER JOIN rekening r ON r.no_rek=counter.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN cash_flow_mapping m ON m.no_rek=counter.no_rek AND m.is_active='Y'
     LEFT JOIN cash_flow_mapping mcounter_cash ON mcounter_cash.no_rek=counter.no_rek AND mcounter_cash.is_active='Y'
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND (mcash.cash_flow_group='cash_equivalent' OR LOWER(kcash.kategori) LIKE '%kas%' OR LOWER(kcash.kategori) LIKE '%bank%')
       AND NOT (mcounter_cash.cash_flow_group='cash_equivalent' OR LOWER(k.kategori) LIKE '%kas%' OR LOWER(k.kategori) LIKE '%bank%')
       $cashFilter
     ORDER BY h.tgl_jurnal,h.no_jurnal,h.id,cash.line_no,counter.line_no,counter.id",
    $params
  );
  if ($rows === false) throw new Exception('Query arus kas langsung gagal: '.$db->getErrorMessage());
  return $rows;
}

function cfd_data($db, $filters)
{
  if (!cfd_table_exists($db, 'cash_flow_mapping')) throw new Exception(cfd_t('finance_cash_flow_mapping_missing', 'Tabel cash_flow_mapping belum tersedia.'));
  $warnings = array();
  $mapping = $db->fetch("SELECT COUNT(*) total,SUM(cash_flow_group='cash_equivalent') cash_count,SUM(cash_flow_group IN ('operating','investing','financing')) activity_count FROM cash_flow_mapping WHERE is_active='Y'");
  if (!$mapping || (int)$mapping->total < 1) $warnings[] = cfd_t('finance_cash_flow_mapping_empty', 'cash_flow_mapping belum diisi; klasifikasi memakai fallback kategori COA resmi.');
  if ($mapping && (int)$mapping->cash_count < 1) $warnings[] = cfd_t('finance_cash_mapping_missing_warning', 'Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA Kas & Bank.');
  if ($mapping && (int)$mapping->activity_count < 1) $warnings[] = cfd_t('finance_cash_activity_mapping_missing_warning', 'Mapping aktivitas operasi/investasi/pendanaan belum lengkap; akun lawan memakai fallback kategori COA.');

  $sections = cfd_empty_sections();
  $journalCash = array();
  $journalWeights = array();
  $rawRows = array();
  foreach (cfd_rows($db, $filters) as $row) {
    $cashMove = round((float)$row->cash_debet - (float)$row->cash_kredit, 2);
    $weight = abs((float)$row->counter_debet - (float)$row->counter_kredit);
    if ($weight < 0.005) $weight = abs((float)$row->counter_debet) + abs((float)$row->counter_kredit);
    $jid = $row->id.'|'.$row->cash_account.'|'.$cashMove;
    $journalCash[$jid] = $cashMove;
    if (!isset($journalWeights[$jid])) $journalWeights[$jid] = 0;
    $journalWeights[$jid] += $weight;
    $rawRows[] = array('jid'=>$jid, 'row'=>$row, 'weight'=>$weight);
  }

  $totalInflow = 0;
  $totalOutflow = 0;
  foreach ($rawRows as $entry) {
    $row = $entry['row'];
    $cashMove = $journalCash[$entry['jid']];
    $baseWeight = $journalWeights[$entry['jid']] > 0 ? $journalWeights[$entry['jid']] : 1;
    $amount = $cashMove * ($entry['weight'] / $baseWeight);
    if (abs($amount) < 0.005) continue;
    if ($amount > 0) $totalInflow += $amount; else $totalOutflow += abs($amount);
    $sectionKey = cfd_classify_counter($row, $amount);
    $direction = $amount >= 0 ? cfd_t('finance_receipt_from', 'Penerimaan dari ') : cfd_t('finance_payment_to', 'Pembayaran untuk ');
    $label = $direction.$row->counter_name;
    cfd_add_row($sections[$sectionKey], $row->counter_account, $label, $amount, $row->cash_flow_group !== '' ? 'mapping' : 'coa');
  }

  $opening = cfd_opening_cash($db, $filters);
  $ending = cfd_cash_balance($db, $filters['end_date'], $filters['cash_account']);
  $netFlow = $sections['operating_in']['total'] + $sections['operating_out']['total'] + $sections['investing']['total'] + $sections['financing']['total'];
  $cashDelta = $ending - $opening;
  if (abs($netFlow - $cashDelta) > 0.01) $warnings[] = cfd_t('finance_cash_reconciliation_warning', 'Net cash flow belum sama dengan perubahan saldo kas; cek jurnal transfer kas, mapping kas/bank, atau jurnal multi-akun.');
  if (!count($rawRows)) $warnings[] = cfd_t('finance_cash_flow_empty_warning', 'Tidak ada jurnal POSTED yang menyentuh akun kas/bank pada periode ini.');

  foreach ($sections as &$section) {
    $section['rows'] = array_values($section['rows']);
    usort($section['rows'], function($a, $b) { return strcmp($a['account'].$a['label'], $b['account'].$b['label']); });
  }
  unset($section);

  return array($sections, $warnings, array(
    'opening_cash'=>$opening,
    'ending_cash'=>$ending,
    'total_inflow'=>$totalInflow,
    'total_outflow'=>$totalOutflow,
    'net_flow'=>$netFlow,
    'cash_delta'=>$cashDelta,
    'reconciliation_diff'=>$netFlow - $cashDelta
  ));
}

function cfd_html($sections, $warnings, $summary, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.cfd_h(cfd_t('common_warning', 'Warning')).':</strong> '.cfd_h(implode(' ', $warnings)).'</div>';
  $cashFilter = $filters['cash_account'] !== '' ? $filters['cash_account'] : cfd_t('common_all', 'Semua');
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.cfd_h(cfd_t('finance_period', 'Periode')).' '.cfd_h($filters['start_date']).' s/d '.cfd_h($filters['end_date']).' | '.cfd_h(cfd_t('finance_cash_bank_account', 'Akun Kas/Bank')).': '.cfd_h($cashFilter).' | Status: POSTED</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed cfd-table"><thead><tr class="bg-primary"><th style="width:140px">COA</th><th>'.cfd_h(cfd_t('finance_description', 'Deskripsi')).'</th><th class="text-right" style="width:180px">'.cfd_h(cfd_t('finance_amount', 'Nilai')).'</th></tr></thead><tbody>';
  foreach ($sections as $section) {
    $html .= '<tr class="cfd-section"><th colspan="3">'.cfd_h($section['title']).'</th></tr>';
    if (!count($section['rows'])) $html .= '<tr><td></td><td class="text-muted"><em>'.cfd_h(cfd_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</em></td><td class="text-right">0.00</td></tr>';
    foreach ($section['rows'] as $row) {
      $html .= '<tr><td>'.cfd_h($row['account']).'</td><td class="cfd-account">'.cfd_h($row['label']).'</td><td class="text-right">'.cfd_num($row['amount']).'</td></tr>';
    }
    $html .= '<tr class="cfd-total"><th></th><th>Subtotal</th><td class="text-right">'.cfd_num($section['total']).'</td></tr>';
  }
  $html .= '<tr class="cfd-grand"><th></th><th>NET CASH FLOW</th><td class="text-right">'.cfd_num($summary['net_flow']).'</td></tr>';
  $html .= '<tr class="cfd-total"><th></th><th>'.cfd_h(cfd_t('finance_opening_cash', 'Saldo Kas Awal')).'</th><td class="text-right">'.cfd_num($summary['opening_cash']).'</td></tr>';
  $html .= '<tr class="cfd-total"><th></th><th>'.cfd_h(cfd_t('finance_ending_cash', 'Saldo Kas Akhir')).'</th><td class="text-right">'.cfd_num($summary['ending_cash']).'</td></tr>';
  $html .= '<tr class="cfd-check"><th></th><th>'.cfd_h(cfd_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas')).'</th><td class="text-right">'.cfd_num($summary['reconciliation_diff']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function cfd_print_page($sections, $warnings, $summary, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = cfd_html($sections, $warnings, $summary, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Arus Kas Langsung</title><link rel="stylesheet" href="'.cfd_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.cfd-table{width:100%;border-collapse:collapse!important}.cfd-table th,.cfd-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cfd-section th{background:#1d4ed8!important;color:#fff!important}.cfd-total th,.cfd-total td{background:#f3f4f6!important;font-weight:bold}.cfd-grand th,.cfd-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.cfd-check th,.cfd-check td{background:#fff7ed!important;font-weight:bold}.cfd-account{padding-left:24px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.cfd-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cfd_h($company).'</h3><h4 style="margin:0 0 14px">'.cfd_h(cfd_t('finance_report_cash_flow_direct', 'Arus Kas (Langsung)')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

function cfd_excel($sections, $warnings, $summary, $filters)
{
  global $initialOutputBufferLevel;
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Arus Kas Langsung'));
  $headers = array('COA','Uraian','Nilai');
  foreach ($headers as $i=>$h) $sheet->setCellValueByColumnAndRow($i, 4, $h);
  $rowNo = 5;
  foreach ($sections as $section) {
    $sheet->setCellValue('A'.$rowNo, $section['title']);
    $sheet->mergeCells('A'.$rowNo.':C'.$rowNo);
    $sheet->getStyle('A'.$rowNo.':C'.$rowNo)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A'.$rowNo.':C'.$rowNo)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
    $rowNo++;
    foreach ($section['rows'] as $row) {
      $sheet->setCellValueExplicit('A'.$rowNo, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValue('B'.$rowNo, $row['label']);
      $sheet->setCellValue('C'.$rowNo, $row['amount']);
      $rowNo++;
    }
    $sheet->setCellValue('B'.$rowNo, 'Subtotal');
    $sheet->setCellValue('C'.$rowNo, $section['total']);
    $sheet->getStyle('A'.$rowNo.':C'.$rowNo)->getFont()->setBold(true);
    $rowNo++;
  }
  foreach (array('NET CASH FLOW'=>$summary['net_flow'],'Saldo Kas Awal'=>$summary['opening_cash'],'Saldo Kas Akhir'=>$summary['ending_cash'],'Selisih Rekonsiliasi Kas'=>$summary['reconciliation_diff']) as $label=>$amount) {
    $sheet->setCellValue('B'.$rowNo, $label);
    $sheet->setCellValue('C'.$rowNo, $amount);
    $sheet->getStyle('A'.$rowNo.':C'.$rowNo)->getFont()->setBold(true);
    $rowNo++;
  }
  erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('ARUS KAS LANGSUNG'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rowNo-1),'column_count'=>3,'money_columns'=>array('C'),'filters'=>array('Periode'=>$filters['start_date'].' s/d '.$filters['end_date'],'Akun Kas'=>$filters['cash_account'] ?: 'All','Status'=>'POSTED')));
  $tmp = erpkb_excel_temp_file('arus_kas_langsung_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="arus_kas_langsung_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  if ($act === 'accounts') cfd_json('success', 'OK', array('accounts'=>cfd_cash_accounts($db)));
  $filters = cfd_filters($db);
  list($sections, $warnings, $summary) = cfd_data($db, $filters);
  if ($act === 'filter') cfd_json('success', 'OK', array('html'=>cfd_html($sections, $warnings, $summary, $filters), 'warnings'=>$warnings, 'summary'=>$summary));
  if ($act === 'print') cfd_print_page($sections, $warnings, $summary, $filters);
  if ($act === 'excel') cfd_excel($sections, $warnings, $summary, $filters);
  cfd_json('error', cfd_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  cfd_json('error', $e->getMessage());
}
?>
