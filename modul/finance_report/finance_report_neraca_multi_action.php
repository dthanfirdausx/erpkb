<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function nrm_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
  exit;
}
function nrm_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function nrm_num($v) { return number_format((float)$v, 2, '.', ','); }
function nrm_req($k, $d = '') { return isset($_REQUEST[$k]) ? trim((string)$_REQUEST[$k]) : $d; }
function nrm_month_ok($v) { return preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', (string)$v); }

function nrm_filters() {
  $start = nrm_req('start_month', date('Y-m'));
  $end = nrm_req('end_month', date('Y-m'));
  $cost = nrm_req('cost_center');
  $profit = nrm_req('profit_center');
  if ($cost !== '' && !ctype_digit($cost)) throw new Exception('Cost center tidak valid.');
  if ($profit !== '' && !ctype_digit($profit)) throw new Exception('Profit center tidak valid.');
  return array('start_month'=>$start, 'end_month'=>$end, 'cost_center'=>$cost, 'profit_center'=>$profit);
}

function nrm_periods($startMonth, $endMonth) {
  if (!nrm_month_ok($startMonth) || !nrm_month_ok($endMonth)) throw new Exception('Format periode bulan tidak valid.');
  $start = DateTime::createFromFormat('Y-m-d', $startMonth.'-01');
  $end = DateTime::createFromFormat('Y-m-d', $endMonth.'-01');
  if ($start > $end) throw new Exception('Start month tidak boleh lebih besar dari end month.');
  $periods = array();
  $cursor = clone $start;
  while ($cursor <= $end) {
    $periods[] = array('key'=>$cursor->format('Y-m'), 'label'=>$cursor->format('M Y'), 'year'=>(int)$cursor->format('Y'), 'start'=>$cursor->format('Y-m-01'), 'end'=>$cursor->format('Y-m-t'));
    $cursor->modify('+1 month');
    if (count($periods) > 12) throw new Exception('Rentang multi periode maksimal 12 bulan agar performa tetap aman.');
  }
  return $periods;
}

function nrm_empty_period_map($periods) {
  $m = array();
  foreach ($periods as $p) $m[$p['key']] = 0;
  return $m;
}

function nrm_empty_sections($periods) {
  return array(
    'aset'=>array('title'=>'ASET', 'categories'=>array(), 'periods'=>nrm_empty_period_map($periods)),
    'kewajiban'=>array('title'=>'KEWAJIBAN', 'categories'=>array(), 'periods'=>nrm_empty_period_map($periods)),
    'modal'=>array('title'=>'MODAL', 'categories'=>array(), 'periods'=>nrm_empty_period_map($periods))
  );
}

function nrm_validate_opening($db, $periods) {
  $years = array();
  foreach ($periods as $p) $years[$p['year']] = true;
  foreach (array_keys($years) as $year) {
    $r = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
    if (!$r || (int)$r->cnt < 1) throw new Exception('Saldo awal periode '.$year.' belum diisi.');
    if (abs((float)$r->debet - (float)$r->kredit) > 0.01) throw new Exception('Saldo awal periode '.$year.' tidak balance.');
  }
}

function nrm_journal_where($filters, $yearStart, $endDate, &$params) {
  $params = array($yearStart, $endDate);
  $where = "h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'";
  if ($filters['cost_center'] !== '') {
    $where .= " AND d.cost_center_id=?";
    $params[] = (int)$filters['cost_center'];
  }
  if ($filters['profit_center'] !== '') {
    $where .= " AND d.profit_center_id=?";
    $params[] = (int)$filters['profit_center'];
  }
  return $where;
}

function nrm_fetch_balance_period($db, $filters, $period) {
  $yearStart = $period['year'].'-01-01';
  $jp = array();
  $where = nrm_journal_where($filters, $yearStart, $period['end'], $jp);
  $params = array_merge(array($period['year']), $jp);
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,r.level,k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
            CASE WHEN k.saldo_normal='kredit'
              THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
              ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
            END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (
       SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit
       FROM saldo_awal WHERE periode=? GROUP BY no_rek
     ) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (
       SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit
       FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header
       WHERE $where GROUP BY d.no_rek
     ) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.id,LENGTH(r.no_rek),r.no_rek",
    $params
  );
  if ($rows === false) throw new Exception('Query Neraca multi periode gagal: '.$db->getErrorMessage());
  return $rows;
}

function nrm_current_profit($db, $filters, $period) {
  $jp = array();
  $where = nrm_journal_where($filters, $period['year'].'-01-01', $period['end'], $jp);
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE
       WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE 0 END),0) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE $where AND k.kategori_akun IN ('pendapatan','beban')",
    $jp
  );
  if ($row === false) throw new Exception('Query laba/rugi berjalan gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}

function nrm_data($db, $filters, $periods) {
  nrm_validate_opening($db, $periods);
  $sections = nrm_empty_sections($periods);
  $profitMap = nrm_empty_period_map($periods);
  foreach ($periods as $period) {
    $pk = $period['key'];
    foreach (nrm_fetch_balance_period($db, $filters, $period) as $row) {
      if (abs((float)$row->saldo) < 0.005) continue;
      $sectionKey = $row->kategori_akun;
      $catKey = (string)$row->kategori_id;
      $accKey = (string)$row->no_rek;
      if (!isset($sections[$sectionKey]['categories'][$catKey])) {
        $sections[$sectionKey]['categories'][$catKey] = array('label'=>$row->kategori, 'periods'=>nrm_empty_period_map($periods), 'accounts'=>array());
      }
      if (!isset($sections[$sectionKey]['categories'][$catKey]['accounts'][$accKey])) {
        $sections[$sectionKey]['categories'][$catKey]['accounts'][$accKey] = array('no_rek'=>$row->no_rek, 'nama_rek'=>$row->nama_rek, 'level'=>(int)$row->level, 'periods'=>nrm_empty_period_map($periods));
      }
      $amount = (float)$row->saldo;
      $sections[$sectionKey]['categories'][$catKey]['accounts'][$accKey]['periods'][$pk] = $amount;
      $sections[$sectionKey]['categories'][$catKey]['periods'][$pk] += $amount;
      $sections[$sectionKey]['periods'][$pk] += $amount;
    }
    $profit = nrm_current_profit($db, $filters, $period);
    $profitMap[$pk] = $profit;
    if (abs($profit) >= 0.005) {
      if (!isset($sections['modal']['categories']['CY-PROFIT'])) {
        $sections['modal']['categories']['CY-PROFIT'] = array('label'=>'Laba Tahun Berjalan', 'periods'=>nrm_empty_period_map($periods), 'accounts'=>array());
      }
      if (!isset($sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT'])) {
        $sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT'] = array('no_rek'=>'CY-PROFIT', 'nama_rek'=>'Laba (Rugi) Tahun Berjalan', 'level'=>3, 'periods'=>nrm_empty_period_map($periods));
      }
      $sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']['periods'][$pk] = $profit;
      $sections['modal']['categories']['CY-PROFIT']['periods'][$pk] += $profit;
      $sections['modal']['periods'][$pk] += $profit;
    }
  }
  return array($sections, $profitMap);
}

function nrm_passiva($sections, $periods) {
  $m = array();
  foreach ($periods as $p) $m[$p['key']] = $sections['kewajiban']['periods'][$p['key']] + $sections['modal']['periods'][$p['key']];
  return $m;
}
function nrm_diff($sections, $periods) {
  $p = nrm_passiva($sections, $periods);
  $m = array();
  foreach ($periods as $period) $m[$period['key']] = $sections['aset']['periods'][$period['key']] - $p[$period['key']];
  return $m;
}

function nrm_cells($map, $periods, $class = '') {
  $html = '';
  foreach ($periods as $p) $html .= '<td class="text-right '.$class.'">'.nrm_num(isset($map[$p['key']]) ? $map[$p['key']] : 0).'</td>';
  return $html;
}

function nrm_html($sections, $periods, $filters) {
  $colspan = count($periods) + 2;
  $html = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Saldo tiap kolom dihitung sampai akhir bulan. Maksimal 12 bulan.</div>';
  if ($filters['cost_center'] !== '' || $filters['profit_center'] !== '') {
    $html .= '<div class="alert alert-warning"><i class="fa fa-filter"></i> Filter cost/profit center diterapkan pada mutasi jurnal dan laba/rugi berjalan. Saldo awal tidak memiliki dimensi cost/profit center.</div>';
  }
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed nrm-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th>';
  foreach ($periods as $p) $html .= '<th class="text-right" style="min-width:125px">'.nrm_h($p['label']).'</th>';
  $html .= '</tr></thead><tbody>';
  foreach ($sections as $section) {
    $html .= '<tr class="nrm-group"><th colspan="'.$colspan.'">'.nrm_h($section['title']).'</th></tr>';
    foreach ($section['categories'] as $cat) {
      $html .= '<tr class="nrm-category"><th></th><th>'.nrm_h($cat['label']).'</th>'.nrm_cells($cat['periods'], $periods).'</tr>';
      foreach ($cat['accounts'] as $acc) {
        $level = max(0, min(6, (int)$acc['level']));
        $html .= '<tr><td>'.nrm_h($acc['no_rek']).'</td><td class="nrm-account nrm-level-'.$level.'">'.nrm_h($acc['nama_rek']).'</td>'.nrm_cells($acc['periods'], $periods).'</tr>';
      }
      $html .= '<tr class="active nrm-subtotal"><th></th><th>Subtotal '.nrm_h($cat['label']).'</th>'.nrm_cells($cat['periods'], $periods).'</tr>';
    }
    $html .= '<tr class="nrm-total"><th></th><th>TOTAL '.nrm_h($section['title']).'</th>'.nrm_cells($section['periods'], $periods).'</tr>';
  }
  $passiva = nrm_passiva($sections, $periods);
  $diff = nrm_diff($sections, $periods);
  $html .= '<tr class="nrm-grand"><th></th><th>TOTAL KEWAJIBAN + MODAL</th>'.nrm_cells($passiva, $periods).'</tr>';
  $html .= '<tr class="nrm-diff"><th></th><th>SELISIH BALANCE</th>'.nrm_cells($diff, $periods).'</tr>';
  $html .= '</tbody></table></div>';
  return $html;
}

function nrm_print_page($sections, $periods, $filters) {
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = nrm_html($sections, $periods, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Neraca Multi Periode</title><link rel="stylesheet" href="'.nrm_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.nrm-table{width:100%;border-collapse:collapse!important}.nrm-table th,.nrm-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.nrm-group th{background:#1d4ed8!important;color:#fff!important}.nrm-category th,.nrm-category td{background:#e0f2fe!important;font-weight:bold}.nrm-total th,.nrm-total td{background:#f3f4f6!important;font-weight:bold}.nrm-grand th,.nrm-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.nrm-account{padding-left:18px!important}.nrm-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.nrm-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.nrm_h($company).'</h3><h4 style="margin:0 0 14px">Neraca (Multi Periode)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = nrm_filters();
  $periods = nrm_periods($filters['start_month'], $filters['end_month']);
  list($sections) = nrm_data($db, $filters, $periods);
  if ($act === 'filter') nrm_json('success', 'OK', array('html'=>nrm_html($sections, $periods, $filters), 'period_count'=>count($periods)));
  if ($act === 'print') nrm_print_page($sections, $periods, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Neraca Multi'));
    $sheet->setCellValue('A4', 'COA');
    $sheet->setCellValue('B4', 'Group / Akun');
    $col = 2;
    foreach ($periods as $p) $sheet->setCellValueByColumnAndRow($col++, 4, $p['label']);
    $row = 5;
    foreach ($sections as $section) {
      $sheet->setCellValue('A'.$row, $section['title']);
      $sheet->mergeCells('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
      $row++;
      foreach ($section['categories'] as $cat) {
        $sheet->setCellValue('B'.$row, $cat['label']);
        $c=2; foreach($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $cat['periods'][$p['key']]);
        $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFont()->setBold(true); $row++;
        foreach ($cat['accounts'] as $acc) {
          $sheet->setCellValueExplicit('A'.$row, $acc['no_rek'], PHPExcel_Cell_DataType::TYPE_STRING);
          $sheet->setCellValue('B'.$row, $acc['nama_rek']);
          $c=2; foreach($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $acc['periods'][$p['key']]);
          $row++;
        }
        $sheet->setCellValue('B'.$row, 'Subtotal '.$cat['label']);
        $c=2; foreach($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $cat['periods'][$p['key']]);
        $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFont()->setBold(true); $row++;
      }
      $sheet->setCellValue('B'.$row, 'TOTAL '.$section['title']);
      $c=2; foreach($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $section['periods'][$p['key']]);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFont()->setBold(true); $row++;
    }
    $passiva = nrm_passiva($sections, $periods); $diff = nrm_diff($sections, $periods);
    foreach (array('TOTAL KEWAJIBAN + MODAL'=>$passiva, 'SELISIH BALANCE'=>$diff) as $label=>$map) {
      $sheet->setCellValue('B'.$row, $label);
      $c=2; foreach($periods as $p) $sheet->setCellValueByColumnAndRow($c++, $row, $map[$p['key']]);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($col-1).$row)->getFont()->setBold(true); $row++;
    }
    $moneyCols = array(); for($i=2;$i<$col;$i++) $moneyCols[] = PHPExcel_Cell::stringFromColumnIndex($i);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('NERACA MULTI PERIODE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$row-1),'column_count'=>$col,'money_columns'=>$moneyCols,'filters'=>array('Periode'=>$filters['start_month'].' s/d '.$filters['end_month'],'Status'=>'POSTED','Cost Center'=>$filters['cost_center'] ?: 'All','Profit Center'=>$filters['profit_center'] ?: 'All')));
    $tmp = erpkb_excel_temp_file('neraca_multi_periode_');
    PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
    $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size || $sig!=='PK'){ @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="neraca_multi_periode_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public');
    readfile($tmp); @unlink($tmp); exit;
  }
  nrm_json('error', 'Action tidak dikenal.');
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  nrm_json('error', $e->getMessage());
}
?>
