<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function lrm_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
  exit;
}
function lrm_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function lrm_num($v) { return number_format((float)$v, 2, '.', ','); }
function lrm_month_ok($v) { return preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', (string)$v); }
function lrm_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }

function lrm_periods($startMonth, $endMonth) {
  if (!lrm_month_ok($startMonth) || !lrm_month_ok($endMonth)) {
    throw new Exception('Format periode bulan tidak valid.');
  }
  $start = DateTime::createFromFormat('Y-m-d', $startMonth.'-01');
  $end = DateTime::createFromFormat('Y-m-d', $endMonth.'-01');
  if ($start > $end) throw new Exception('Start month tidak boleh lebih besar dari end month.');
  $periods = array();
  $cursor = clone $start;
  while ($cursor <= $end) {
    $periods[] = array(
      'key'=>$cursor->format('Y-m'),
      'label'=>$cursor->format('M Y'),
      'start'=>$cursor->format('Y-m-01'),
      'end'=>$cursor->format('Y-m-t')
    );
    $cursor->modify('+1 month');
    if (count($periods) > 12) {
      throw new Exception('Rentang multi periode maksimal 12 bulan agar performa tetap aman.');
    }
  }
  return $periods;
}

function lrm_filters() {
  $start = lrm_req('start_month', date('Y-m'));
  $end = lrm_req('end_month', date('Y-m'));
  $cost = lrm_req('cost_center');
  $profit = lrm_req('profit_center');
  if ($cost !== '' && !ctype_digit($cost)) throw new Exception('Cost center tidak valid.');
  if ($profit !== '' && !ctype_digit($profit)) throw new Exception('Profit center tidak valid.');
  return array('start_month'=>$start, 'end_month'=>$end, 'cost_center'=>$cost, 'profit_center'=>$profit);
}

function lrm_empty_period_map($periods) {
  $map = array();
  foreach ($periods as $p) $map[$p['key']] = 0;
  return $map;
}

function lrm_group_key($kategoriAkun, $kategori) {
  $kategoriAkun = strtolower(trim((string)$kategoriAkun));
  $kategori = strtolower(trim((string)$kategori));
  if ($kategoriAkun === 'pendapatan') return strpos($kategori, 'lain') !== false ? 'pendapatan_lain' : 'pendapatan';
  if (strpos($kategori, 'pokok') !== false || strpos($kategori, 'persediaan') !== false) return 'hpp';
  return strpos($kategori, 'lain') !== false ? 'beban_lain' : 'beban_operasional';
}

function lrm_empty_groups() {
  return array(
    'pendapatan'=>array('title'=>'PENDAPATAN', 'categories'=>array(), 'periods'=>array(), 'total'=>0, 'kind'=>'income'),
    'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN', 'categories'=>array(), 'periods'=>array(), 'total'=>0, 'kind'=>'expense'),
    'beban_operasional'=>array('title'=>'BEBAN OPERASIONAL', 'categories'=>array(), 'periods'=>array(), 'total'=>0, 'kind'=>'expense'),
    'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN', 'categories'=>array(), 'periods'=>array(), 'total'=>0, 'kind'=>'income'),
    'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN', 'categories'=>array(), 'periods'=>array(), 'total'=>0, 'kind'=>'expense')
  );
}

function lrm_data($db, $filters, $periods) {
  $startDate = $periods[0]['start'];
  $endDate = $periods[count($periods)-1]['end'];
  $params = array($startDate, $endDate);
  $where = "h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'";
  if ($filters['cost_center'] !== '') {
    $where .= " AND d.cost_center_id=?";
    $params[] = (int)$filters['cost_center'];
  }
  if ($filters['profit_center'] !== '') {
    $where .= " AND d.profit_center_id=?";
    $params[] = (int)$filters['profit_center'];
  }
  $rows = $db->query(
    "SELECT DATE_FORMAT(h.tgl_jurnal,'%Y-%m') period_key,
            k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
            r.no_rek,r.nama_rek,r.level,
            SUM(COALESCE(d.debet,0)) total_debet,
            SUM(COALESCE(d.kredit,0)) total_kredit,
            CASE
              WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0))
              ELSE SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0))
            END amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE $where
       AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY DATE_FORMAT(h.tgl_jurnal,'%Y-%m'),k.id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level
     HAVING ABS(amount) >= 0.005
     ORDER BY k.id,LENGTH(r.no_rek),r.no_rek,period_key",
    $params
  );
  if ($rows === false) throw new Exception('Query laba/rugi multi periode gagal: '.$db->getErrorMessage());

  $groups = lrm_empty_groups();
  foreach ($groups as $key=>$group) $groups[$key]['periods'] = lrm_empty_period_map($periods);
  $net = lrm_empty_period_map($periods);

  foreach ($rows as $row) {
    $groupKey = lrm_group_key($row->kategori_akun, $row->kategori);
    $categoryKey = (string)$row->kategori_id;
    $accountKey = (string)$row->no_rek;
    if (!isset($groups[$groupKey]['categories'][$categoryKey])) {
      $groups[$groupKey]['categories'][$categoryKey] = array(
        'label'=>$row->kategori,
        'kategori_akun'=>$row->kategori_akun,
        'saldo_normal'=>$row->saldo_normal,
        'periods'=>lrm_empty_period_map($periods),
        'total'=>0,
        'accounts'=>array()
      );
    }
    if (!isset($groups[$groupKey]['categories'][$categoryKey]['accounts'][$accountKey])) {
      $groups[$groupKey]['categories'][$categoryKey]['accounts'][$accountKey] = array(
        'no_rek'=>$row->no_rek,
        'nama_rek'=>$row->nama_rek,
        'level'=>(int)$row->level,
        'periods'=>lrm_empty_period_map($periods),
        'total'=>0
      );
    }
    $amount = (float)$row->amount;
    $pk = $row->period_key;
    if (!array_key_exists($pk, $net)) continue;
    $groups[$groupKey]['categories'][$categoryKey]['accounts'][$accountKey]['periods'][$pk] += $amount;
    $groups[$groupKey]['categories'][$categoryKey]['accounts'][$accountKey]['total'] += $amount;
    $groups[$groupKey]['categories'][$categoryKey]['periods'][$pk] += $amount;
    $groups[$groupKey]['categories'][$categoryKey]['total'] += $amount;
    $groups[$groupKey]['periods'][$pk] += $amount;
    $groups[$groupKey]['total'] += $amount;
    $net[$pk] += $groups[$groupKey]['kind'] === 'income' ? $amount : -$amount;
  }
  $netTotal = 0;
  foreach ($net as $v) $netTotal += $v;
  return array($groups, $net, $netTotal);
}

function lrm_row_cells($periodValues, $periods, $total, $class = '') {
  $html = '';
  foreach ($periods as $p) $html .= '<td class="text-right '.$class.'">'.lrm_num(isset($periodValues[$p['key']]) ? $periodValues[$p['key']] : 0).'</td>';
  $html .= '<td class="text-right '.$class.'">'.lrm_num($total).'</td>';
  return $html;
}

function lrm_html($groups, $periods, $net, $netTotal, $filters) {
  $colspan = count($periods) + 3;
  $html = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Menampilkan '.count($periods).' periode. Sumber kategori: coa_kategori.kategori_akun; jurnal hanya POSTED.</div>';
  if ($filters['cost_center'] !== '' || $filters['profit_center'] !== '') {
    $html .= '<div class="alert alert-warning"><i class="fa fa-filter"></i> Filter cost/profit center diterapkan dari jurnal_detail.</div>';
  }
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed lrm-table">';
  $html .= '<thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:260px">Group / Akun</th>';
  foreach ($periods as $p) $html .= '<th class="text-right" style="min-width:120px">'.lrm_h($p['label']).'</th>';
  $html .= '<th class="text-right" style="min-width:130px">Total</th></tr></thead><tbody>';
  foreach ($groups as $group) {
    $html .= '<tr class="lrm-group"><th colspan="'.$colspan.'">'.lrm_h($group['title']).'</th></tr>';
    if (!count($group['categories'])) {
      $html .= '<tr><td colspan="'.$colspan.'" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    }
    foreach ($group['categories'] as $category) {
      $html .= '<tr class="lrm-category"><th></th><th>'.lrm_h($category['label']).' <small>('.lrm_h($category['kategori_akun']).')</small></th>'.lrm_row_cells($category['periods'], $periods, $category['total']).'</tr>';
      foreach ($category['accounts'] as $account) {
        $level = max(0, min(6, (int)$account['level']));
        $html .= '<tr><td>'.lrm_h($account['no_rek']).'</td><td class="lrm-account lrm-level-'.$level.'">'.lrm_h($account['nama_rek']).'</td>'.lrm_row_cells($account['periods'], $periods, $account['total']).'</tr>';
      }
      $html .= '<tr class="active lrm-subtotal"><th></th><th>Subtotal '.lrm_h($category['label']).'</th>'.lrm_row_cells($category['periods'], $periods, $category['total']).'</tr>';
    }
    $html .= '<tr class="lrm-total"><th></th><th>TOTAL '.lrm_h($group['title']).'</th>'.lrm_row_cells($group['periods'], $periods, $group['total']).'</tr>';
  }
  $html .= '<tr class="'.($netTotal >= 0 ? 'success' : 'danger').' lrm-net"><th></th><th>LABA (RUGI) BERSIH</th>'.lrm_row_cells($net, $periods, $netTotal).'</tr>';
  $html .= '</tbody></table></div>';
  return $html;
}

function lrm_print_page($groups, $periods, $net, $netTotal, $filters) {
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = lrm_html($groups, $periods, $net, $netTotal, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi Multi Periode</title>'.
    '<link rel="stylesheet" href="'.lrm_h($assetBase).'bootstrap/css/bootstrap.min.css">'.
    '<style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.lrm-table{width:100%;border-collapse:collapse!important}.lrm-table th,.lrm-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.lrm-group th{background:#1d4ed8!important;color:#fff!important}.lrm-category th,.lrm-category td{background:#e0f2fe!important;font-weight:bold}.lrm-total th,.lrm-total td{background:#f3f4f6!important;font-weight:bold}.lrm-account{padding-left:18px!important}.lrm-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.lrm-table tr{page-break-inside:avoid}}</style>'.
    '</head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.lrm_h($company).'</h3><h4 style="margin:0 0 14px">Laba/Rugi (Multi Periode)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = lrm_filters();
  $periods = lrm_periods($filters['start_month'], $filters['end_month']);
  list($groups, $net, $netTotal) = lrm_data($db, $filters, $periods);

  if ($act === 'filter') {
    lrm_json('success', 'OK', array('html'=>lrm_html($groups, $periods, $net, $netTotal, $filters), 'period_count'=>count($periods), 'net_total'=>lrm_num($netTotal)));
  }
  if ($act === 'print') {
    lrm_print_page($groups, $periods, $net, $netTotal, $filters);
  }
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('LR Multi Periode'));
    $sheet->setCellValue('A4', 'COA');
    $sheet->setCellValue('B4', 'Group / Akun');
    $col = 2;
    foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($col++, 4, $p['label']);
    $sheet->setCellValueByColumnAndRow($col, 4, 'Total');
    $row = 5;
    foreach ($groups as $group) {
      $sheet->setCellValue('A'.$row, $group['title']);
      $sheet->mergeCells('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
      $row++;
      foreach ($group['categories'] as $category) {
        $sheet->setCellValue('B'.$row, $category['label']);
        $c = 2; foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $category['periods'][$p['key']]);
        $sheet->setCellValueByColumnAndRow($c, $row, $category['total']);
        $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFont()->setBold(true);
        $row++;
        foreach ($category['accounts'] as $account) {
          $sheet->setCellValueExplicit('A'.$row, $account['no_rek'], PHPExcel_Cell_DataType::TYPE_STRING);
          $sheet->setCellValue('B'.$row, $account['nama_rek']);
          $c = 2; foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $account['periods'][$p['key']]);
          $sheet->setCellValueByColumnAndRow($c, $row, $account['total']);
          $row++;
        }
        $sheet->setCellValue('B'.$row, 'Subtotal '.$category['label']);
        $c = 2; foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $category['periods'][$p['key']]);
        $sheet->setCellValueByColumnAndRow($c, $row, $category['total']);
        $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFont()->setBold(true);
        $row++;
      }
      $sheet->setCellValue('B'.$row, 'TOTAL '.$group['title']);
      $c = 2; foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $group['periods'][$p['key']]);
      $sheet->setCellValueByColumnAndRow($c, $row, $group['total']);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFont()->setBold(true);
      $row++;
    }
    $sheet->setCellValue('B'.$row, 'LABA (RUGI) BERSIH');
    $c = 2; foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $net[$p['key']]);
    $sheet->setCellValueByColumnAndRow($c, $row, $netTotal);
    $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col).$row)->getFont()->setBold(true);
    $lastRow = $row;
    $moneyCols = array();
    for ($i=2; $i<=$col; $i++) $moneyCols[] = PHPExcel_Cell::stringFromColumnIndex($i);
    erpkb_excel_apply_standard_style($excel, array(
      'sheet'=>$sheet,
      'title'=>erp_export_title('LABA RUGI MULTI PERIODE'),
      'header_row'=>4,
      'first_data_row'=>5,
      'last_data_row'=>$lastRow,
      'column_count'=>$col+1,
      'money_columns'=>$moneyCols,
      'filters'=>array('Periode'=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED', 'Cost Center'=>$filters['cost_center'] ?: 'All', 'Profit Center'=>$filters['profit_center'] ?: 'All')
    ));
    $tmp = erpkb_excel_temp_file('laba_rugi_multi_periode_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laba_rugi_multi_periode_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  lrm_json('error', 'Action tidak dikenal.');
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  lrm_json('error', $e->getMessage());
}
?>
