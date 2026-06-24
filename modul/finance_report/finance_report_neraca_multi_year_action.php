<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function nrmy_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(array('status'=>$status,'message'=>$message), $extra));
  exit;
}
function nrmy_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function nrmy_num($v) { return number_format((float)$v, 2, '.', ','); }
function nrmy_req($k, $d = '') { return isset($_REQUEST[$k]) ? trim((string)$_REQUEST[$k]) : $d; }

function nrmy_filters() {
  $end = nrmy_req('end_year', date('Y'));
  $start = nrmy_req('start_year', (string)((int)$end - 2));
  if (!preg_match('/^\d{4}$/', $start) || !preg_match('/^\d{4}$/', $end)) throw new Exception('Format tahun tidak valid.');
  $start = (int)$start; $end = (int)$end;
  if ($start > $end) throw new Exception('Start year tidak boleh lebih besar dari end year.');
  if (($end - $start + 1) > 10) throw new Exception('Rentang multi year maksimal 10 tahun agar performa aman.');
  return array('start_year'=>$start, 'end_year'=>$end);
}
function nrmy_years($filters) { $r=array(); for($y=$filters['start_year'];$y<=$filters['end_year'];$y++)$r[]=(string)$y; return $r; }
function nrmy_bounds($db, $year) {
  $variant = function_exists('erp_config_get') ? strtoupper((string)erp_config_get('fiscal_year_variant', 'K4')) : 'K4';
  if ($variant === 'K4' || $variant === '') return array($year.'-01-01', $year.'-12-31');
  $row = $db->fetch("SELECT MIN(start_date) start_date,MAX(end_date) end_date FROM erp_financial_period WHERE period_code LIKE ?", array($year.'-%'));
  if ($row && $row->start_date && $row->end_date) return array($row->start_date, $row->end_date);
  return array($year.'-01-01', $year.'-12-31');
}
function nrmy_empty_year_map($years){$m=array();foreach($years as $y)$m[$y]=0;return $m;}
function nrmy_empty_sections($years){return array('aset'=>array('title'=>'ASET','years'=>nrmy_empty_year_map($years),'categories'=>array()),'kewajiban'=>array('title'=>'KEWAJIBAN','years'=>nrmy_empty_year_map($years),'categories'=>array()),'modal'=>array('title'=>'MODAL','years'=>nrmy_empty_year_map($years),'categories'=>array()));}

function nrmy_opening_warnings($db, $years) {
  $warnings = array();
  foreach ($years as $year) {
    $r=$db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
    if(!$r || (int)$r->cnt<1) {
      $warnings[] = 'Saldo awal periode '.$year.' belum diisi, opening dianggap 0.';
      continue;
    }
    if(abs((float)$r->debet-(float)$r->kredit)>0.01) $warnings[] = 'Saldo awal periode '.$year.' tidak balance.';
  }
  return $warnings;
}
function nrmy_balance_rows($db,$year,$start,$end){
  $rows=$db->query(
    "SELECT r.no_rek,r.nama_rek,r.level,k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.id,LENGTH(r.no_rek),r.no_rek",
    array($year,$start,$end)
  );
  if($rows===false) throw new Exception('Query Neraca multi year gagal: '.$db->getErrorMessage());
  return $rows;
}
function nrmy_profit($db,$start,$end){
  $row=$db->fetch(
    "SELECT COALESCE(SUM(CASE WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE 0 END),0) amount
     FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header INNER JOIN rekening r ON r.no_rek=d.no_rek INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')",
    array($start,$end)
  );
  if($row===false) throw new Exception('Query laba/rugi berjalan gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}
function nrmy_data($db,$years){
  $warnings=nrmy_opening_warnings($db,$years);
  $sections=nrmy_empty_sections($years); $bounds=array();
  foreach($years as $year){
    list($start,$end)=nrmy_bounds($db,$year); $bounds[$year]=$start.' s/d '.$end;
    foreach(nrmy_balance_rows($db,$year,$start,$end) as $row){
      if(abs((float)$row->saldo)<0.005) continue;
      $sk=$row->kategori_akun; $ck=(string)$row->kategori_id; $ak=(string)$row->no_rek;
      if(!isset($sections[$sk]['categories'][$ck])) $sections[$sk]['categories'][$ck]=array('label'=>$row->kategori,'years'=>nrmy_empty_year_map($years),'accounts'=>array());
      if(!isset($sections[$sk]['categories'][$ck]['accounts'][$ak])) $sections[$sk]['categories'][$ck]['accounts'][$ak]=array('no_rek'=>$row->no_rek,'nama_rek'=>$row->nama_rek,'level'=>(int)$row->level,'years'=>nrmy_empty_year_map($years));
      $amt=(float)$row->saldo;
      $sections[$sk]['categories'][$ck]['accounts'][$ak]['years'][$year]=$amt;
      $sections[$sk]['categories'][$ck]['years'][$year]+=$amt;
      $sections[$sk]['years'][$year]+=$amt;
    }
    $profit=nrmy_profit($db,$start,$end);
    if(abs($profit)>=0.005){
      if(!isset($sections['modal']['categories']['CY-PROFIT'])) $sections['modal']['categories']['CY-PROFIT']=array('label'=>'Laba Tahun Berjalan','years'=>nrmy_empty_year_map($years),'accounts'=>array());
      if(!isset($sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT'])) $sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']=array('no_rek'=>'CY-PROFIT','nama_rek'=>'Laba (Rugi) Tahun Berjalan','level'=>3,'years'=>nrmy_empty_year_map($years));
      $sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']['years'][$year]=$profit;
      $sections['modal']['categories']['CY-PROFIT']['years'][$year]+=$profit;
      $sections['modal']['years'][$year]+=$profit;
    }
  }
  return array($sections,$bounds,$warnings);
}
function nrmy_passiva($sections,$years){$m=array();foreach($years as $y)$m[$y]=$sections['kewajiban']['years'][$y]+$sections['modal']['years'][$y];return $m;}
function nrmy_diff($sections,$years){$p=nrmy_passiva($sections,$years);$m=array();foreach($years as $y)$m[$y]=$sections['aset']['years'][$y]-$p[$y];return $m;}
function nrmy_cells($map,$years){$h='';foreach($years as $y)$h.='<td class="text-right">'.nrmy_num(isset($map[$y])?$map[$y]:0).'</td>';return $h;}
function nrmy_html($sections,$years,$bounds,$warnings=array()){
  $colspan=count($years)+2; $pairs=array(); foreach($bounds as $y=>$b)$pairs[]=nrmy_h($y).': '.nrmy_h($b);
  $html='';
  if(count($warnings)) $html.='<div class="alert alert-warning"><strong>Warning:</strong> '.nrmy_h(implode(' ', $warnings)).'</div>';
  $html.='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Saldo per akhir tahun. Boundary: '.implode('; ',$pairs).'.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed nrmy-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th>';
  foreach($years as $y)$html.='<th class="text-right" style="min-width:125px">'.nrmy_h($y).'</th>'; $html.='</tr></thead><tbody>';
  foreach($sections as $section){
    $html.='<tr class="nrmy-group"><th colspan="'.$colspan.'">'.nrmy_h($section['title']).'</th></tr>';
    foreach($section['categories'] as $cat){
      $html.='<tr class="nrmy-category"><th></th><th>'.nrmy_h($cat['label']).'</th>'.nrmy_cells($cat['years'],$years).'</tr>';
      foreach($cat['accounts'] as $acc){$level=max(0,min(6,(int)$acc['level']));$html.='<tr><td>'.nrmy_h($acc['no_rek']).'</td><td class="nrmy-account nrmy-level-'.$level.'">'.nrmy_h($acc['nama_rek']).'</td>'.nrmy_cells($acc['years'],$years).'</tr>';}
      $html.='<tr class="active nrmy-subtotal"><th></th><th>Subtotal '.nrmy_h($cat['label']).'</th>'.nrmy_cells($cat['years'],$years).'</tr>';
    }
    $html.='<tr class="nrmy-total"><th></th><th>TOTAL '.nrmy_h($section['title']).'</th>'.nrmy_cells($section['years'],$years).'</tr>';
  }
  $html.='<tr class="nrmy-grand"><th></th><th>TOTAL KEWAJIBAN + MODAL</th>'.nrmy_cells(nrmy_passiva($sections,$years),$years).'</tr>';
  $html.='<tr class="nrmy-diff"><th></th><th>SELISIH BALANCE</th>'.nrmy_cells(nrmy_diff($sections,$years),$years).'</tr>';
  return $html.'</tbody></table></div>';
}
function nrmy_print_page($sections,$years,$bounds,$warnings=array()){
  $info=function_exists('info_pt')?info_pt():null; $company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle; $body=nrmy_html($sections,$years,$bounds,$warnings); $assetBase=rtrim(base_url(),'/').'/assets/';
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean(); header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Neraca Multi Year</title><link rel="stylesheet" href="'.nrmy_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.nrmy-table{width:100%;border-collapse:collapse!important}.nrmy-table th,.nrmy-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.nrmy-group th{background:#1d4ed8!important;color:#fff!important}.nrmy-category th,.nrmy-category td{background:#e0f2fe!important;font-weight:bold}.nrmy-total th,.nrmy-total td{background:#f3f4f6!important;font-weight:bold}.nrmy-grand th,.nrmy-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.nrmy-account{padding-left:18px!important}.nrmy-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.nrmy-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.nrmy_h($company).'</h3><h4 style="margin:0 0 14px">Neraca (Multi Year)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>'; exit;
}
$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=nrmy_filters(); $years=nrmy_years($filters); list($sections,$bounds,$warnings)=nrmy_data($db,$years);
  if($act==='filter') nrmy_json('success','OK',array('html'=>nrmy_html($sections,$years,$bounds,$warnings),'year_count'=>count($years),'warnings'=>$warnings));
  if($act==='print') nrmy_print_page($sections,$years,$bounds,$warnings);
  if($act==='excel'){
    ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED); require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel(); $sheet=$excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Neraca Multi Year')); $sheet->setCellValue('A4','COA'); $sheet->setCellValue('B4','Group / Akun'); $col=2; foreach($years as $y)$sheet->setCellValueByColumnAndRow($col++,4,$y); $lastCol=$col-1; $row=5;
    foreach($sections as $section){$sheet->setCellValue('A'.$row,$section['title']);$sheet->mergeCells('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row);$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$row++;
      foreach($section['categories'] as $cat){$sheet->setCellValue('B'.$row,$cat['label']);$c=2;foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$cat['years'][$y]);$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);$row++;
        foreach($cat['accounts'] as $acc){$sheet->setCellValueExplicit('A'.$row,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$row,$acc['nama_rek']);$c=2;foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$acc['years'][$y]);$row++;}
        $sheet->setCellValue('B'.$row,'Subtotal '.$cat['label']);$c=2;foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$cat['years'][$y]);$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);$row++;}
      $sheet->setCellValue('B'.$row,'TOTAL '.$section['title']);$c=2;foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$section['years'][$y]);$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);$row++;}
    foreach(array('TOTAL KEWAJIBAN + MODAL'=>nrmy_passiva($sections,$years),'SELISIH BALANCE'=>nrmy_diff($sections,$years)) as $label=>$map){$sheet->setCellValue('B'.$row,$label);$c=2;foreach($years as $y)$sheet->setCellValueByColumnAndRow($c++,$row,$map[$y]);$sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);$row++;}
    $moneyCols=array();for($i=2;$i<=$lastCol;$i++)$moneyCols[]=PHPExcel_Cell::stringFromColumnIndex($i); erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('NERACA MULTI YEAR'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$row-1),'column_count'=>$lastCol+1,'money_columns'=>$moneyCols,'filters'=>array('Tahun'=>$filters['start_year'].' s/d '.$filters['end_year'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('neraca_multi_year_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2); if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="neraca_multi_year_'.$filters['start_year'].'_sd_'.$filters['end_year'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
  }
  nrmy_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();nrmy_json('error',$e->getMessage());}
?>
