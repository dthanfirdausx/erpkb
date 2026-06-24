<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function nrp_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function nrp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function nrp_num($v){return number_format((float)$v,2,'.',',');}
function nrp_pct($v){return $v===null?'-':number_format((float)$v,2,'.',',').'%';}
function nrp_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function nrp_date_ok($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v)&&strtotime($v)!==false;}
function nrp_default_compare($current){$d=new DateTime($current);$d->modify('first day of this month');$d->modify('-1 day');return $d->format('Y-m-d');}
function nrp_filters(){
  $cur=nrp_req('current_as_of_date',date('Y-m-d'));if(!nrp_date_ok($cur))throw new Exception('Current as-of date tidak valid.');
  $cmp=nrp_req('compare_as_of_date',nrp_default_compare($cur));if(!nrp_date_ok($cmp))throw new Exception('Compare as-of date tidak valid.');
  return array('current_as_of_date'=>$cur,'compare_as_of_date'=>$cmp);
}
function nrp_empty_amount(){return array('current'=>0,'compare'=>0);}
function nrp_empty_sections(){return array('aset'=>array('title'=>'ASET','amounts'=>nrp_empty_amount(),'categories'=>array()),'kewajiban'=>array('title'=>'KEWAJIBAN','amounts'=>nrp_empty_amount(),'categories'=>array()),'modal'=>array('title'=>'MODAL','amounts'=>nrp_empty_amount(),'categories'=>array()));}
function nrp_opening_warnings($db,$dates){
  $warnings=array();$years=array();foreach($dates as $d)$years[(int)date('Y',strtotime($d))]=true;
  foreach(array_keys($years) as $year){$r=$db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?",array($year));if(!$r||(int)$r->cnt<1){$warnings[]='Saldo awal periode '.$year.' belum diisi; saldo awal dianggap 0.';continue;}if(abs((float)$r->debet-(float)$r->kredit)>0.01)$warnings[]='Saldo awal periode '.$year.' tidak balance.';}
  return $warnings;
}
function nrp_balance_rows($db,$asOfDate){
  $year=(int)date('Y',strtotime($asOfDate));$yearStart=$year.'-01-01';
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
    array($year,$yearStart,$asOfDate)
  );
  if($rows===false) throw new Exception('Query neraca gagal: '.$db->getErrorMessage());
  return $rows;
}
function nrp_profit($db,$asOfDate){
  $yearStart=date('Y-01-01',strtotime($asOfDate));
  $row=$db->fetch("SELECT COALESCE(SUM(CASE WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE 0 END),0) amount FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header INNER JOIN rekening r ON r.no_rek=d.no_rek INNER JOIN coa_kategori k ON k.id=r.kat_coa WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')",array($yearStart,$asOfDate));
  if($row===false) throw new Exception('Query laba/rugi berjalan gagal: '.$db->getErrorMessage());
  return (float)$row->amount;
}
function nrp_apply(&$sections,$rows,$slot){
  foreach($rows as $row){if(abs((float)$row->saldo)<0.005)continue;$sk=$row->kategori_akun;$ck=(string)$row->kategori_id;$ak=(string)$row->no_rek;if(!isset($sections[$sk]['categories'][$ck]))$sections[$sk]['categories'][$ck]=array('label'=>$row->kategori,'amounts'=>nrp_empty_amount(),'accounts'=>array());if(!isset($sections[$sk]['categories'][$ck]['accounts'][$ak]))$sections[$sk]['categories'][$ck]['accounts'][$ak]=array('no_rek'=>$row->no_rek,'nama_rek'=>$row->nama_rek,'level'=>(int)$row->level,'amounts'=>nrp_empty_amount());$amt=(float)$row->saldo;$sections[$sk]['categories'][$ck]['accounts'][$ak]['amounts'][$slot]+=$amt;$sections[$sk]['categories'][$ck]['amounts'][$slot]+=$amt;$sections[$sk]['amounts'][$slot]+=$amt;}
}
function nrp_apply_profit(&$sections,$amount,$slot){if(abs($amount)<0.005)return;if(!isset($sections['modal']['categories']['CY-PROFIT']))$sections['modal']['categories']['CY-PROFIT']=array('label'=>'Laba Tahun Berjalan','amounts'=>nrp_empty_amount(),'accounts'=>array());if(!isset($sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']))$sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']=array('no_rek'=>'CY-PROFIT','nama_rek'=>'Laba (Rugi) Tahun Berjalan','level'=>3,'amounts'=>nrp_empty_amount());$sections['modal']['categories']['CY-PROFIT']['accounts']['CY-PROFIT']['amounts'][$slot]+=$amount;$sections['modal']['categories']['CY-PROFIT']['amounts'][$slot]+=$amount;$sections['modal']['amounts'][$slot]+=$amount;}
function nrp_data($db,$filters){
  $warnings=nrp_opening_warnings($db,array($filters['current_as_of_date'],$filters['compare_as_of_date']));$sections=nrp_empty_sections();
  nrp_apply($sections,nrp_balance_rows($db,$filters['current_as_of_date']),'current');nrp_apply_profit($sections,nrp_profit($db,$filters['current_as_of_date']),'current');
  nrp_apply($sections,nrp_balance_rows($db,$filters['compare_as_of_date']),'compare');nrp_apply_profit($sections,nrp_profit($db,$filters['compare_as_of_date']),'compare');
  return array($sections,$warnings);
}
function nrp_diff($a){return (float)$a['current']-(float)$a['compare'];}
function nrp_pct_var($a){$b=(float)$a['compare'];return abs($b)<0.005?null:(nrp_diff($a)/abs($b)*100);}
function nrp_cells($a){return '<td class="text-right">'.nrp_num($a['current']).'</td><td class="text-right">'.nrp_num($a['compare']).'</td><td class="text-right">'.nrp_num(nrp_diff($a)).'</td><td class="text-right">'.nrp_pct(nrp_pct_var($a)).'</td>';}
function nrp_passiva($sections){return array('current'=>$sections['kewajiban']['amounts']['current']+$sections['modal']['amounts']['current'],'compare'=>$sections['kewajiban']['amounts']['compare']+$sections['modal']['amounts']['compare']);}
function nrp_balance_diff($sections){$p=nrp_passiva($sections);return array('current'=>$sections['aset']['amounts']['current']-$p['current'],'compare'=>$sections['aset']['amounts']['compare']-$p['compare']);}
function nrp_html($sections,$warnings,$filters){
  $html='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Periode ini as of '.nrp_h($filters['current_as_of_date']).'. Pembanding as of '.nrp_h($filters['compare_as_of_date']).'. Sumber: saldo_awal + jurnal POSTED.</div>';
  if(count($warnings))$html.='<div class="alert alert-warning"><strong>Warning:</strong> '.nrp_h(implode(' ',array_unique($warnings))).'</div>';
  $bd=nrp_balance_diff($sections);if(abs($bd['current'])>0.01||abs($bd['compare'])>0.01)$html.='<div class="alert alert-warning"><strong>Balance check:</strong> Selisih current '.nrp_num($bd['current']).', compare '.nrp_num($bd['compare']).'.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed nrp-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th><th class="text-right" style="min-width:130px">Periode Ini</th><th class="text-right" style="min-width:130px">Pembanding</th><th class="text-right" style="min-width:130px">Selisih</th><th class="text-right" style="min-width:110px">Selisih %</th></tr></thead><tbody>';
  foreach($sections as $section){$html.='<tr class="nrp-group"><th colspan="6">'.nrp_h($section['title']).'</th></tr>';if(!count($section['categories']))$html.='<tr><td colspan="6" class="text-muted"><em>Tidak ada saldo</em></td></tr>';foreach($section['categories'] as $cat){$html.='<tr class="nrp-category"><th></th><th>'.nrp_h($cat['label']).'</th>'.nrp_cells($cat['amounts']).'</tr>';foreach($cat['accounts'] as $acc){$level=max(0,min(6,(int)$acc['level']));$html.='<tr><td>'.nrp_h($acc['no_rek']).'</td><td class="nrp-account nrp-level-'.$level.'">'.nrp_h($acc['nama_rek']).'</td>'.nrp_cells($acc['amounts']).'</tr>';}$html.='<tr class="active nrp-subtotal"><th></th><th>Subtotal '.nrp_h($cat['label']).'</th>'.nrp_cells($cat['amounts']).'</tr>';}$html.='<tr class="nrp-total"><th></th><th>TOTAL '.nrp_h($section['title']).'</th>'.nrp_cells($section['amounts']).'</tr>';}
  $html.='<tr class="nrp-grand"><th></th><th>TOTAL KEWAJIBAN + MODAL</th>'.nrp_cells(nrp_passiva($sections)).'</tr><tr class="nrp-diff"><th></th><th>SELISIH BALANCE</th>'.nrp_cells($bd).'</tr>';
  return $html.'</tbody></table></div>';
}
function nrp_print_page($sections,$warnings,$filters){$info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=nrp_html($sections,$warnings,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html><head><meta charset="utf-8"><title>Neraca Perbandingan Periode</title><link rel="stylesheet" href="'.nrp_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.nrp-table{width:100%;border-collapse:collapse!important}.nrp-table th,.nrp-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.nrp-group th{background:#1d4ed8!important;color:#fff!important}.nrp-category th,.nrp-category td{background:#e0f2fe!important;font-weight:bold}.nrp-total th,.nrp-total td{background:#f3f4f6!important;font-weight:bold}.nrp-grand th,.nrp-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.nrp-diff th,.nrp-diff td{background:#fff7ed!important;font-weight:bold}.nrp-account{padding-left:18px!important}.nrp-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.nrp-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.nrp_h($company).'</h3><h4 style="margin:0 0 14px">Neraca (Perbandingan Periode)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=nrp_filters();list($sections,$warnings)=nrp_data($db,$filters);
  if($act==='filter')nrp_json('success','OK',array('html'=>nrp_html($sections,$warnings,$filters),'warnings'=>$warnings));
  if($act==='print')nrp_print_page($sections,$warnings,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Neraca Compare'));foreach(array('COA','Group / Akun','Periode Ini','Pembanding','Selisih','Selisih %') as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$r=5;
    foreach($sections as $section){$sheet->setCellValue('A'.$r,$section['title']);$sheet->mergeCells('A'.$r.':F'.$r);$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$r.':F'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$r++;foreach($section['categories'] as $cat){$sheet->setCellValue('B'.$r,$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['current']);$sheet->setCellValue('D'.$r,$cat['amounts']['compare']);$sheet->setCellValue('E'.$r,nrp_diff($cat['amounts']));$sheet->setCellValue('F'.$r,nrp_pct_var($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;foreach($cat['accounts'] as $acc){$sheet->setCellValueExplicit('A'.$r,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$r,$acc['nama_rek']);$sheet->setCellValue('C'.$r,$acc['amounts']['current']);$sheet->setCellValue('D'.$r,$acc['amounts']['compare']);$sheet->setCellValue('E'.$r,nrp_diff($acc['amounts']));$sheet->setCellValue('F'.$r,nrp_pct_var($acc['amounts']));$r++;}$sheet->setCellValue('B'.$r,'Subtotal '.$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['current']);$sheet->setCellValue('D'.$r,$cat['amounts']['compare']);$sheet->setCellValue('E'.$r,nrp_diff($cat['amounts']));$sheet->setCellValue('F'.$r,nrp_pct_var($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}$sheet->setCellValue('B'.$r,'TOTAL '.$section['title']);$sheet->setCellValue('C'.$r,$section['amounts']['current']);$sheet->setCellValue('D'.$r,$section['amounts']['compare']);$sheet->setCellValue('E'.$r,nrp_diff($section['amounts']));$sheet->setCellValue('F'.$r,nrp_pct_var($section['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}
    foreach(array('TOTAL KEWAJIBAN + MODAL'=>nrp_passiva($sections),'SELISIH BALANCE'=>nrp_balance_diff($sections)) as $label=>$amounts){$sheet->setCellValue('B'.$r,$label);$sheet->setCellValue('C'.$r,$amounts['current']);$sheet->setCellValue('D'.$r,$amounts['compare']);$sheet->setCellValue('E'.$r,nrp_diff($amounts));$sheet->setCellValue('F'.$r,nrp_pct_var($amounts));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('NERACA PERBANDINGAN PERIODE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$r-1,'column_count'=>6,'money_columns'=>array('C','D','E'),'decimal_columns'=>array('F'),'filters'=>array('Periode Ini'=>$filters['current_as_of_date'],'Pembanding'=>$filters['compare_as_of_date'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('neraca_perbandingan_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="neraca_perbandingan_'.$filters['current_as_of_date'].'_vs_'.$filters['compare_as_of_date'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  nrp_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();nrp_json('error',$e->getMessage());}
?>
