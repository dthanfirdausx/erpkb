<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function lrmy_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
  exit;
}
function lrmy_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function lrmy_num($v) { return number_format((float)$v, 2, '.', ','); }
function lrmy_pct($v) { return $v === null ? '-' : number_format((float)$v, 2, '.', ',').'%'; }
function lrmy_req($k, $d = '') { return isset($_REQUEST[$k]) ? trim((string)$_REQUEST[$k]) : $d; }

function lrmy_filters() {
  $end = lrmy_req('end_year', date('Y'));
  $start = lrmy_req('start_year', (string)((int)$end - 2));
  if (!preg_match('/^\d{4}$/', $start) || !preg_match('/^\d{4}$/', $end)) throw new Exception('Format tahun tidak valid.');
  $start = (int)$start; $end = (int)$end;
  if ($start > $end) throw new Exception('Start year tidak boleh lebih besar dari end year.');
  if (($end - $start + 1) > 10) throw new Exception('Rentang multi year maksimal 10 tahun agar performa aman.');
  return array('start_year'=>$start, 'end_year'=>$end);
}

function lrmy_years($filters) {
  $years = array();
  for ($y=$filters['start_year']; $y<=$filters['end_year']; $y++) $years[] = (string)$y;
  return $years;
}

function lrmy_fiscal_bounds($db, $year) {
  $variant = function_exists('erp_config_get') ? strtoupper((string)erp_config_get('fiscal_year_variant', 'K4')) : 'K4';
  if ($variant === 'K4' || $variant === '') return array($year.'-01-01', $year.'-12-31');
  $row = $db->fetch(
    "SELECT MIN(start_date) start_date, MAX(end_date) end_date
     FROM erp_financial_period
     WHERE period_code LIKE ?",
    array($year.'-%')
  );
  if ($row && $row->start_date && $row->end_date) return array($row->start_date, $row->end_date);
  return array($year.'-01-01', $year.'-12-31');
}

function lrmy_empty_year_map($years) {
  $m = array();
  foreach ($years as $y) $m[$y] = 0;
  return $m;
}

function lrmy_group_key($kategoriAkun, $kategori) {
  $kategoriAkun = strtolower(trim((string)$kategoriAkun));
  $kategori = strtolower(trim((string)$kategori));
  if ($kategoriAkun === 'pendapatan') return strpos($kategori, 'lain') !== false ? 'pendapatan_lain' : 'pendapatan';
  if (strpos($kategori, 'pokok') !== false || strpos($kategori, 'persediaan') !== false) return 'hpp';
  return strpos($kategori, 'lain') !== false ? 'beban_lain' : 'beban_operasional';
}

function lrmy_empty_groups($years) {
  return array(
    'pendapatan'=>array('title'=>'PENDAPATAN', 'kind'=>'income', 'years'=>lrmy_empty_year_map($years), 'categories'=>array()),
    'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN', 'kind'=>'expense', 'years'=>lrmy_empty_year_map($years), 'categories'=>array()),
    'beban_operasional'=>array('title'=>'BEBAN OPERASIONAL', 'kind'=>'expense', 'years'=>lrmy_empty_year_map($years), 'categories'=>array()),
    'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN', 'kind'=>'income', 'years'=>lrmy_empty_year_map($years), 'categories'=>array()),
    'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN', 'kind'=>'expense', 'years'=>lrmy_empty_year_map($years), 'categories'=>array())
  );
}

function lrmy_data($db, $years) {
  $groups = lrmy_empty_groups($years);
  $net = lrmy_empty_year_map($years);
  $bounds = array();
  foreach ($years as $year) {
    list($start, $end) = lrmy_fiscal_bounds($db, $year);
    $bounds[$year] = $start.' s/d '.$end;
    $rows = $db->query(
      "SELECT k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
              r.no_rek,r.nama_rek,r.level,
              CASE
                WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0))
                ELSE SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0))
              END amount
       FROM jurnal_detail d
       INNER JOIN jurnal_header h ON h.id=d.id_header
       INNER JOIN rekening r ON r.no_rek=d.no_rek
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       WHERE h.tgl_jurnal BETWEEN ? AND ?
         AND h.posting_status='POSTED'
         AND k.kategori_akun IN ('pendapatan','beban')
       GROUP BY k.id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level
       HAVING ABS(amount) >= 0.005
       ORDER BY k.id,LENGTH(r.no_rek),r.no_rek",
      array($start, $end)
    );
    if ($rows === false) throw new Exception('Query laba/rugi multi year gagal: '.$db->getErrorMessage());
    foreach ($rows as $row) {
      $gk = lrmy_group_key($row->kategori_akun, $row->kategori);
      $ck = (string)$row->kategori_id;
      $ak = (string)$row->no_rek;
      if (!isset($groups[$gk]['categories'][$ck])) {
        $groups[$gk]['categories'][$ck] = array('label'=>$row->kategori, 'kategori_akun'=>$row->kategori_akun, 'years'=>lrmy_empty_year_map($years), 'accounts'=>array());
      }
      if (!isset($groups[$gk]['categories'][$ck]['accounts'][$ak])) {
        $groups[$gk]['categories'][$ck]['accounts'][$ak] = array('no_rek'=>$row->no_rek, 'nama_rek'=>$row->nama_rek, 'level'=>(int)$row->level, 'years'=>lrmy_empty_year_map($years));
      }
      $amount = (float)$row->amount;
      $groups[$gk]['categories'][$ck]['accounts'][$ak]['years'][$year] = $amount;
      $groups[$gk]['categories'][$ck]['years'][$year] += $amount;
      $groups[$gk]['years'][$year] += $amount;
      $net[$year] += $groups[$gk]['kind'] === 'income' ? $amount : -$amount;
    }
  }
  return array($groups, $net, $bounds);
}

function lrmy_growth($map, $years) {
  if (count($years) < 2) return array(null, null);
  $last = $years[count($years)-1];
  $prev = $years[count($years)-2];
  $diff = (float)$map[$last] - (float)$map[$prev];
  $pct = abs((float)$map[$prev]) < 0.005 ? null : ($diff / abs((float)$map[$prev]) * 100);
  return array($diff, $pct);
}

function lrmy_cells($map, $years) {
  $html = '';
  foreach ($years as $y) $html .= '<td class="text-right">'.lrmy_num(isset($map[$y]) ? $map[$y] : 0).'</td>';
  list($diff, $pct) = lrmy_growth($map, $years);
  $html .= '<td class="text-right">'.($diff === null ? '-' : lrmy_num($diff)).'</td><td class="text-right">'.lrmy_pct($pct).'</td>';
  return $html;
}

function lrmy_html($groups, $years, $net, $bounds) {
  $colspan = count($years) + 4;
  $html = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Tahun fiskal mengikuti erp_financial_period bila tersedia. Boundary: ';
  $pairs = array(); foreach ($bounds as $y=>$b) $pairs[] = lrmy_h($y).': '.lrmy_h($b);
  $html .= implode('; ', $pairs).'.</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed lrmy-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th>';
  foreach ($years as $y) $html .= '<th class="text-right" style="min-width:125px">'.lrmy_h($y).'</th>';
  $html .= '<th class="text-right" style="min-width:125px">Diff</th><th class="text-right" style="min-width:110px">Growth %</th></tr></thead><tbody>';
  foreach ($groups as $group) {
    $html .= '<tr class="lrmy-group"><th colspan="'.$colspan.'">'.lrmy_h($group['title']).'</th></tr>';
    if (!count($group['categories'])) $html .= '<tr><td colspan="'.$colspan.'" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    foreach ($group['categories'] as $cat) {
      $html .= '<tr class="lrmy-category"><th></th><th>'.lrmy_h($cat['label']).' <small>('.lrmy_h($cat['kategori_akun']).')</small></th>'.lrmy_cells($cat['years'], $years).'</tr>';
      foreach ($cat['accounts'] as $acc) {
        $level = max(0, min(6, (int)$acc['level']));
        $html .= '<tr><td>'.lrmy_h($acc['no_rek']).'</td><td class="lrmy-account lrmy-level-'.$level.'">'.lrmy_h($acc['nama_rek']).'</td>'.lrmy_cells($acc['years'], $years).'</tr>';
      }
      $html .= '<tr class="active lrmy-subtotal"><th></th><th>Subtotal '.lrmy_h($cat['label']).'</th>'.lrmy_cells($cat['years'], $years).'</tr>';
    }
    $html .= '<tr class="lrmy-total"><th></th><th>TOTAL '.lrmy_h($group['title']).'</th>'.lrmy_cells($group['years'], $years).'</tr>';
  }
  $html .= '<tr class="lrmy-net"><th></th><th>LABA (RUGI) BERSIH</th>'.lrmy_cells($net, $years).'</tr>';
  $html .= '</tbody></table></div>';
  return $html;
}

function lrmy_print_page($groups, $years, $net, $bounds) {
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = lrmy_html($groups, $years, $net, $bounds);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi Multi Year</title><link rel="stylesheet" href="'.lrmy_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.lrmy-table{width:100%;border-collapse:collapse!important}.lrmy-table th,.lrmy-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.lrmy-group th{background:#1d4ed8!important;color:#fff!important}.lrmy-category th,.lrmy-category td{background:#e0f2fe!important;font-weight:bold}.lrmy-total th,.lrmy-total td{background:#f3f4f6!important;font-weight:bold}.lrmy-account{padding-left:18px!important}.lrmy-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.lrmy-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.lrmy_h($company).'</h3><h4 style="margin:0 0 14px">Laba/Rugi (Multi Year)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = lrmy_filters();
  $years = lrmy_years($filters);
  list($groups, $net, $bounds) = lrmy_data($db, $years);
  if ($act === 'filter') lrmy_json('success', 'OK', array('html'=>lrmy_html($groups, $years, $net, $bounds), 'year_count'=>count($years), 'net_last'=>lrmy_num($net[$years[count($years)-1]])));
  if ($act === 'print') lrmy_print_page($groups, $years, $net, $bounds);
  if ($act === 'excel') {
    ini_set('display_errors', '0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('LR Multi Year'));
    $sheet->setCellValue('A4','COA'); $sheet->setCellValue('B4','Group / Akun'); $col=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($col++,4,$y); $sheet->setCellValueByColumnAndRow($col++,4,'Diff'); $sheet->setCellValueByColumnAndRow($col++,4,'Growth %');
    $lastCol = $col - 1; $row=5;
    foreach($groups as $group){ $sheet->setCellValue('A'.$row,$group['title']); $sheet->mergeCells('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF'); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8'); $row++;
      foreach($group['categories'] as $cat){ $sheet->setCellValue('B'.$row,$cat['label']); $c=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$cat['years'][$y]); list($d,$p)=lrmy_growth($cat['years'],$years); $sheet->setCellValueByColumnAndRow($c++,$row,$d); $sheet->setCellValueByColumnAndRow($c,$row,$p); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true); $row++;
        foreach($cat['accounts'] as $acc){ $sheet->setCellValueExplicit('A'.$row,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING); $sheet->setCellValue('B'.$row,$acc['nama_rek']); $c=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$acc['years'][$y]); list($d,$p)=lrmy_growth($acc['years'],$years); $sheet->setCellValueByColumnAndRow($c++,$row,$d); $sheet->setCellValueByColumnAndRow($c,$row,$p); $row++; }
        $sheet->setCellValue('B'.$row,'Subtotal '.$cat['label']); $c=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$cat['years'][$y]); list($d,$p)=lrmy_growth($cat['years'],$years); $sheet->setCellValueByColumnAndRow($c++,$row,$d); $sheet->setCellValueByColumnAndRow($c,$row,$p); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true); $row++; }
      $sheet->setCellValue('B'.$row,'TOTAL '.$group['title']); $c=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$group['years'][$y]); list($d,$p)=lrmy_growth($group['years'],$years); $sheet->setCellValueByColumnAndRow($c++,$row,$d); $sheet->setCellValueByColumnAndRow($c,$row,$p); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true); $row++; }
    $sheet->setCellValue('B'.$row,'LABA (RUGI) BERSIH'); $c=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$net[$y]); list($d,$p)=lrmy_growth($net,$years); $sheet->setCellValueByColumnAndRow($c++,$row,$d); $sheet->setCellValueByColumnAndRow($c,$row,$p); $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);
    $moneyCols=array(); for($i=2;$i<$lastCol;$i++)$moneyCols[]=PHPExcel_Cell::stringFromColumnIndex($i);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('LABA RUGI MULTI YEAR'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$row,'column_count'=>$lastCol+1,'money_columns'=>$moneyCols,'decimal_columns'=>array(PHPExcel_Cell::stringFromColumnIndex($lastCol)),'filters'=>array('Tahun'=>$filters['start_year'].' s/d '.$filters['end_year'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('laba_rugi_multi_year_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2); if(!$size||$sig!=='PK'){@unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="laba_rugi_multi_year_'.$filters['start_year'].'_sd_'.$filters['end_year'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
  }
  lrmy_json('error','Action tidak dikenal.');
} catch(Exception $e) { while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); lrmy_json('error',$e->getMessage()); }
?>
