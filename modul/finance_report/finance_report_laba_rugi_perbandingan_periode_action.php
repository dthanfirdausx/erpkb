<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function lrp_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function lrp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lrp_num($v){return number_format((float)$v,2,'.',',');}
function lrp_pct($v){return $v===null?'-':number_format((float)$v,2,'.',',').'%';}
function lrp_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function lrp_date_ok($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v)&&strtotime($v)!==false;}

function lrp_default_compare($start,$end){
  $s=new DateTime($start);$e=new DateTime($end);$days=(int)$s->diff($e)->format('%a');$ce=clone $s;$ce->modify('-1 day');$cs=clone $ce;$cs->modify('-'.$days.' day');return array($cs->format('Y-m-d'),$ce->format('Y-m-d'));
}
function lrp_filters(){
  $cs=lrp_req('current_start_date',date('Y-m-01'));$ce=lrp_req('current_end_date',date('Y-m-d'));
  if(!lrp_date_ok($cs)||!lrp_date_ok($ce)) throw new Exception('Tanggal periode ini tidak valid.');
  if(strtotime($cs)>strtotime($ce)) throw new Exception('Current start date tidak boleh lebih besar dari current end date.');
  $auto=lrp_default_compare($cs,$ce);
  $ps=lrp_req('compare_start_date',$auto[0]);$pe=lrp_req('compare_end_date',$auto[1]);
  if(!lrp_date_ok($ps)||!lrp_date_ok($pe)) throw new Exception('Tanggal periode pembanding tidak valid.');
  if(strtotime($ps)>strtotime($pe)) throw new Exception('Compare start date tidak boleh lebih besar dari compare end date.');
  return array('current_start_date'=>$cs,'current_end_date'=>$ce,'compare_start_date'=>$ps,'compare_end_date'=>$pe);
}
function lrp_group_key($kategoriAkun,$kategori){
  $kategoriAkun=strtolower(trim((string)$kategoriAkun));$kategori=strtolower(trim((string)$kategori));
  if($kategoriAkun==='pendapatan') return strpos($kategori,'lain')!==false?'pendapatan_lain':'pendapatan';
  if(strpos($kategori,'pokok')!==false||strpos($kategori,'persediaan')!==false) return 'hpp';
  return strpos($kategori,'lain')!==false?'beban_lain':'beban_operasional';
}
function lrp_empty_amount(){return array('current'=>0,'compare'=>0);}
function lrp_empty_groups(){
  return array(
    'pendapatan'=>array('title'=>'PENDAPATAN','kind'=>'income','amounts'=>lrp_empty_amount(),'categories'=>array()),
    'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN','kind'=>'expense','amounts'=>lrp_empty_amount(),'categories'=>array()),
    'beban_operasional'=>array('title'=>'BEBAN OPERASIONAL','kind'=>'expense','amounts'=>lrp_empty_amount(),'categories'=>array()),
    'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN','kind'=>'income','amounts'=>lrp_empty_amount(),'categories'=>array()),
    'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN','kind'=>'expense','amounts'=>lrp_empty_amount(),'categories'=>array())
  );
}
function lrp_fetch_period($db,$start,$end){
  $rows=$db->query(
    "SELECT k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level,
      CASE WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0)) ELSE SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0)) END amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY k.id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level
     HAVING ABS(amount)>=0.005
     ORDER BY k.id,LENGTH(r.no_rek),r.no_rek",
    array($start,$end)
  );
  if($rows===false) throw new Exception('Query laba/rugi perbandingan gagal: '.$db->getErrorMessage());
  return $rows;
}
function lrp_apply_rows(&$groups,&$net,$rows,$slot){
  foreach($rows as $row){
    $gk=lrp_group_key($row->kategori_akun,$row->kategori);$ck=(string)$row->kategori_id;$ak=(string)$row->no_rek;
    if(!isset($groups[$gk]['categories'][$ck])) $groups[$gk]['categories'][$ck]=array('label'=>$row->kategori,'kategori_akun'=>$row->kategori_akun,'amounts'=>lrp_empty_amount(),'accounts'=>array());
    if(!isset($groups[$gk]['categories'][$ck]['accounts'][$ak])) $groups[$gk]['categories'][$ck]['accounts'][$ak]=array('no_rek'=>$row->no_rek,'nama_rek'=>$row->nama_rek,'level'=>(int)$row->level,'amounts'=>lrp_empty_amount());
    $amount=(float)$row->amount;
    $groups[$gk]['categories'][$ck]['accounts'][$ak]['amounts'][$slot]+=$amount;
    $groups[$gk]['categories'][$ck]['amounts'][$slot]+=$amount;
    $groups[$gk]['amounts'][$slot]+=$amount;
    $net[$slot]+=$groups[$gk]['kind']==='income'?$amount:-$amount;
  }
}
function lrp_data($db,$filters){
  $groups=lrp_empty_groups();$net=lrp_empty_amount();
  lrp_apply_rows($groups,$net,lrp_fetch_period($db,$filters['current_start_date'],$filters['current_end_date']),'current');
  lrp_apply_rows($groups,$net,lrp_fetch_period($db,$filters['compare_start_date'],$filters['compare_end_date']),'compare');
  return array($groups,$net);
}
function lrp_diff($a){return (float)$a['current']-(float)$a['compare'];}
function lrp_growth($a){$base=(float)$a['compare'];return abs($base)<0.005?null:(lrp_diff($a)/abs($base)*100);}
function lrp_cells($amounts){
  return '<td class="text-right">'.lrp_num($amounts['current']).'</td><td class="text-right">'.lrp_num($amounts['compare']).'</td><td class="text-right">'.lrp_num(lrp_diff($amounts)).'</td><td class="text-right">'.lrp_pct(lrp_growth($amounts)).'</td>';
}
function lrp_html($groups,$net,$filters){
  $html='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Periode ini: '.lrp_h($filters['current_start_date']).' s/d '.lrp_h($filters['current_end_date']).'. Pembanding: '.lrp_h($filters['compare_start_date']).' s/d '.lrp_h($filters['compare_end_date']).'. Sumber: jurnal POSTED dan coa_kategori.kategori_akun.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed lrp-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th><th class="text-right" style="min-width:130px">Periode Ini</th><th class="text-right" style="min-width:130px">Pembanding</th><th class="text-right" style="min-width:130px">Selisih</th><th class="text-right" style="min-width:110px">Selisih %</th></tr></thead><tbody>';
  foreach($groups as $group){
    $html.='<tr class="lrp-group"><th colspan="6">'.lrp_h($group['title']).'</th></tr>';
    if(!count($group['categories'])) $html.='<tr><td colspan="6" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    foreach($group['categories'] as $cat){
      $html.='<tr class="lrp-category"><th></th><th>'.lrp_h($cat['label']).' <small>('.lrp_h($cat['kategori_akun']).')</small></th>'.lrp_cells($cat['amounts']).'</tr>';
      foreach($cat['accounts'] as $acc){$level=max(0,min(6,(int)$acc['level']));$html.='<tr><td>'.lrp_h($acc['no_rek']).'</td><td class="lrp-account lrp-level-'.$level.'">'.lrp_h($acc['nama_rek']).'</td>'.lrp_cells($acc['amounts']).'</tr>';}
      $html.='<tr class="active lrp-subtotal"><th></th><th>Subtotal '.lrp_h($cat['label']).'</th>'.lrp_cells($cat['amounts']).'</tr>';
    }
    $html.='<tr class="lrp-total"><th></th><th>TOTAL '.lrp_h($group['title']).'</th>'.lrp_cells($group['amounts']).'</tr>';
  }
  $html.='<tr class="lrp-net"><th></th><th>LABA (RUGI) BERSIH</th>'.lrp_cells($net).'</tr>';
  return $html.'</tbody></table></div>';
}
function lrp_print_page($groups,$net,$filters){
  $info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=lrp_html($groups,$net,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi Perbandingan Periode</title><link rel="stylesheet" href="'.lrp_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.lrp-table{width:100%;border-collapse:collapse!important}.lrp-table th,.lrp-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.lrp-group th{background:#1d4ed8!important;color:#fff!important}.lrp-category th,.lrp-category td{background:#e0f2fe!important;font-weight:bold}.lrp-total th,.lrp-total td{background:#f3f4f6!important;font-weight:bold}.lrp-net th,.lrp-net td{background:#0f766e!important;color:#fff!important;font-weight:bold}.lrp-account{padding-left:18px!important}.lrp-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.lrp-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.lrp_h($company).'</h3><h4 style="margin:0 0 14px">Laba/Rugi (Perbandingan Periode)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;
}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=lrp_filters();list($groups,$net)=lrp_data($db,$filters);
  if($act==='filter') lrp_json('success','OK',array('html'=>lrp_html($groups,$net,$filters),'net_current'=>lrp_num($net['current']),'net_compare'=>lrp_num($net['compare'])));
  if($act==='print') lrp_print_page($groups,$net,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('LR Compare'));$headers=array('COA','Group / Akun','Periode Ini','Pembanding','Selisih','Selisih %');foreach($headers as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$r=5;
    foreach($groups as $group){$sheet->setCellValue('A'.$r,$group['title']);$sheet->mergeCells('A'.$r.':F'.$r);$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$r.':F'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$r++;
      foreach($group['categories'] as $cat){$sheet->setCellValue('B'.$r,$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['current']);$sheet->setCellValue('D'.$r,$cat['amounts']['compare']);$sheet->setCellValue('E'.$r,lrp_diff($cat['amounts']));$sheet->setCellValue('F'.$r,lrp_growth($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;
        foreach($cat['accounts'] as $acc){$sheet->setCellValueExplicit('A'.$r,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$r,$acc['nama_rek']);$sheet->setCellValue('C'.$r,$acc['amounts']['current']);$sheet->setCellValue('D'.$r,$acc['amounts']['compare']);$sheet->setCellValue('E'.$r,lrp_diff($acc['amounts']));$sheet->setCellValue('F'.$r,lrp_growth($acc['amounts']));$r++;}
        $sheet->setCellValue('B'.$r,'Subtotal '.$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['current']);$sheet->setCellValue('D'.$r,$cat['amounts']['compare']);$sheet->setCellValue('E'.$r,lrp_diff($cat['amounts']));$sheet->setCellValue('F'.$r,lrp_growth($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}
      $sheet->setCellValue('B'.$r,'TOTAL '.$group['title']);$sheet->setCellValue('C'.$r,$group['amounts']['current']);$sheet->setCellValue('D'.$r,$group['amounts']['compare']);$sheet->setCellValue('E'.$r,lrp_diff($group['amounts']));$sheet->setCellValue('F'.$r,lrp_growth($group['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}
    $sheet->setCellValue('B'.$r,'LABA (RUGI) BERSIH');$sheet->setCellValue('C'.$r,$net['current']);$sheet->setCellValue('D'.$r,$net['compare']);$sheet->setCellValue('E'.$r,lrp_diff($net));$sheet->setCellValue('F'.$r,lrp_growth($net));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('LABA RUGI PERBANDINGAN PERIODE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$r,'column_count'=>6,'money_columns'=>array('C','D','E'),'decimal_columns'=>array('F'),'filters'=>array('Periode Ini'=>$filters['current_start_date'].' s/d '.$filters['current_end_date'],'Pembanding'=>$filters['compare_start_date'].' s/d '.$filters['compare_end_date'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('laba_rugi_perbandingan_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="laba_rugi_perbandingan_'.$filters['current_start_date'].'_sd_'.$filters['current_end_date'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  lrp_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();lrp_json('error',$e->getMessage());}
?>
