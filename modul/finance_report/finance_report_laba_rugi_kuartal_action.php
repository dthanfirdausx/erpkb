<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function lrq_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function lrq_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lrq_num($v){return number_format((float)$v,2,'.',',');}
function lrq_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function lrq_filters(){
  $year=lrq_req('year',date('Y'));
  if(!preg_match('/^\d{4}$/',$year)) throw new Exception('Format tahun tidak valid.');
  return array('year'=>(int)$year);
}
function lrq_quarter_keys(){return array('Q1','Q2','Q3','Q4');}
function lrq_empty_map(){$m=array();foreach(lrq_quarter_keys() as $q)$m[$q]=0;$m['total']=0;return $m;}
function lrq_group_key($kategoriAkun,$kategori){
  $kategoriAkun=strtolower(trim((string)$kategoriAkun));$kategori=strtolower(trim((string)$kategori));
  if($kategoriAkun==='pendapatan') return strpos($kategori,'lain')!==false?'pendapatan_lain':'pendapatan';
  if(strpos($kategori,'pokok')!==false||strpos($kategori,'persediaan')!==false) return 'hpp';
  return strpos($kategori,'lain')!==false?'beban_lain':'beban_operasional';
}
function lrq_empty_groups(){
  return array(
    'pendapatan'=>array('title'=>'PENDAPATAN','kind'=>'income','amounts'=>lrq_empty_map(),'categories'=>array()),
    'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN','kind'=>'expense','amounts'=>lrq_empty_map(),'categories'=>array()),
    'beban_operasional'=>array('title'=>'BEBAN OPERASIONAL','kind'=>'expense','amounts'=>lrq_empty_map(),'categories'=>array()),
    'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN','kind'=>'income','amounts'=>lrq_empty_map(),'categories'=>array()),
    'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN','kind'=>'expense','amounts'=>lrq_empty_map(),'categories'=>array())
  );
}
function lrq_quarters($db,$year){
  $variant=function_exists('erp_config_get')?strtoupper((string)erp_config_get('fiscal_year_variant','K4')):'K4';
  if($variant==='K4'||$variant==='') {
    return array(
      'Q1'=>array('label'=>'Q1','start'=>$year.'-01-01','end'=>$year.'-03-31'),
      'Q2'=>array('label'=>'Q2','start'=>$year.'-04-01','end'=>$year.'-06-30'),
      'Q3'=>array('label'=>'Q3','start'=>$year.'-07-01','end'=>$year.'-09-30'),
      'Q4'=>array('label'=>'Q4','start'=>$year.'-10-01','end'=>$year.'-12-31')
    );
  }
  $rows=$db->query("SELECT start_date,end_date FROM erp_financial_period WHERE period_code LIKE ? ORDER BY start_date",array($year.'-%'));
  if($rows!==false){
    $periods=array();foreach($rows as $r) if($r->start_date&&$r->end_date)$periods[]=array('start'=>$r->start_date,'end'=>$r->end_date);
    if(count($periods)>=12){
      $q=array();
      for($i=0;$i<4;$i++){
        $slice=array_slice($periods,$i*3,3);
        $q['Q'.($i+1)]=array('label'=>'Q'.($i+1),'start'=>$slice[0]['start'],'end'=>$slice[count($slice)-1]['end']);
      }
      return $q;
    }
  }
  return array(
    'Q1'=>array('label'=>'Q1','start'=>$year.'-01-01','end'=>$year.'-03-31'),
    'Q2'=>array('label'=>'Q2','start'=>$year.'-04-01','end'=>$year.'-06-30'),
    'Q3'=>array('label'=>'Q3','start'=>$year.'-07-01','end'=>$year.'-09-30'),
    'Q4'=>array('label'=>'Q4','start'=>$year.'-10-01','end'=>$year.'-12-31')
  );
}
function lrq_rows($db,$start,$end){
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
  if($rows===false) throw new Exception('Query laba/rugi kuartal gagal: '.$db->getErrorMessage());
  return $rows;
}
function lrq_data($db,$filters){
  $quarters=lrq_quarters($db,$filters['year']);
  $groups=lrq_empty_groups();$net=lrq_empty_map();
  foreach($quarters as $qk=>$q){
    foreach(lrq_rows($db,$q['start'],$q['end']) as $row){
      $gk=lrq_group_key($row->kategori_akun,$row->kategori);$ck=(string)$row->kategori_id;$ak=(string)$row->no_rek;
      if(!isset($groups[$gk]['categories'][$ck])) $groups[$gk]['categories'][$ck]=array('label'=>$row->kategori,'kategori_akun'=>$row->kategori_akun,'amounts'=>lrq_empty_map(),'accounts'=>array());
      if(!isset($groups[$gk]['categories'][$ck]['accounts'][$ak])) $groups[$gk]['categories'][$ck]['accounts'][$ak]=array('no_rek'=>$row->no_rek,'nama_rek'=>$row->nama_rek,'level'=>(int)$row->level,'amounts'=>lrq_empty_map());
      $amount=(float)$row->amount;
      $groups[$gk]['categories'][$ck]['accounts'][$ak]['amounts'][$qk]+=$amount;
      $groups[$gk]['categories'][$ck]['accounts'][$ak]['amounts']['total']+=$amount;
      $groups[$gk]['categories'][$ck]['amounts'][$qk]+=$amount;
      $groups[$gk]['categories'][$ck]['amounts']['total']+=$amount;
      $groups[$gk]['amounts'][$qk]+=$amount;
      $groups[$gk]['amounts']['total']+=$amount;
      $net[$qk]+=$groups[$gk]['kind']==='income'?$amount:-$amount;
      $net['total']+=$groups[$gk]['kind']==='income'?$amount:-$amount;
    }
  }
  return array($groups,$net,$quarters);
}
function lrq_cells($amounts){$html='';foreach(lrq_quarter_keys() as $q)$html.='<td class="text-right">'.lrq_num(isset($amounts[$q])?$amounts[$q]:0).'</td>';return $html.'<td class="text-right">'.lrq_num($amounts['total']).'</td>';}
function lrq_boundary_text($quarters){$p=array();foreach($quarters as $k=>$q)$p[]=lrq_h($k).': '.lrq_h($q['start']).' s/d '.lrq_h($q['end']);return implode('; ',$p);}
function lrq_html($groups,$net,$quarters,$filters){
  $html='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Boundary kuartal tahun '.lrq_h($filters['year']).': '.lrq_boundary_text($quarters).'.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed lrq-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th><th class="text-right" style="min-width:120px">Q1</th><th class="text-right" style="min-width:120px">Q2</th><th class="text-right" style="min-width:120px">Q3</th><th class="text-right" style="min-width:120px">Q4</th><th class="text-right" style="min-width:130px">Total Year</th></tr></thead><tbody>';
  foreach($groups as $group){
    $html.='<tr class="lrq-group"><th colspan="7">'.lrq_h($group['title']).'</th></tr>';
    if(!count($group['categories'])) $html.='<tr><td colspan="7" class="text-muted"><em>Tidak ada transaksi</em></td></tr>';
    foreach($group['categories'] as $cat){
      $html.='<tr class="lrq-category"><th></th><th>'.lrq_h($cat['label']).' <small>('.lrq_h($cat['kategori_akun']).')</small></th>'.lrq_cells($cat['amounts']).'</tr>';
      foreach($cat['accounts'] as $acc){$level=max(0,min(6,(int)$acc['level']));$html.='<tr><td>'.lrq_h($acc['no_rek']).'</td><td class="lrq-account lrq-level-'.$level.'">'.lrq_h($acc['nama_rek']).'</td>'.lrq_cells($acc['amounts']).'</tr>';}
      $html.='<tr class="active lrq-subtotal"><th></th><th>Subtotal '.lrq_h($cat['label']).'</th>'.lrq_cells($cat['amounts']).'</tr>';
    }
    $html.='<tr class="lrq-total"><th></th><th>TOTAL '.lrq_h($group['title']).'</th>'.lrq_cells($group['amounts']).'</tr>';
  }
  return $html.'<tr class="lrq-net"><th></th><th>LABA (RUGI) BERSIH</th>'.lrq_cells($net).'</tr></tbody></table></div>';
}
function lrq_print_page($groups,$net,$quarters,$filters){
  $info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=lrq_html($groups,$net,$quarters,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi Kuartal</title><link rel="stylesheet" href="'.lrq_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.lrq-table{width:100%;border-collapse:collapse!important}.lrq-table th,.lrq-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.lrq-group th{background:#1d4ed8!important;color:#fff!important}.lrq-category th,.lrq-category td{background:#e0f2fe!important;font-weight:bold}.lrq-total th,.lrq-total td{background:#f3f4f6!important;font-weight:bold}.lrq-net th,.lrq-net td{background:#0f766e!important;color:#fff!important;font-weight:bold}.lrq-account{padding-left:18px!important}.lrq-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.lrq-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.lrq_h($company).'</h3><h4 style="margin:0 0 14px">Laba/Rugi (Kuartal)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;
}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=lrq_filters();list($groups,$net,$quarters)=lrq_data($db,$filters);
  if($act==='filter') lrq_json('success','OK',array('html'=>lrq_html($groups,$net,$quarters,$filters),'net_total'=>lrq_num($net['total'])));
  if($act==='print') lrq_print_page($groups,$net,$quarters,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('LR Kuartal'));$headers=array('COA','Group / Akun','Q1','Q2','Q3','Q4','Total Year');foreach($headers as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$r=5;
    foreach($groups as $group){$sheet->setCellValue('A'.$r,$group['title']);$sheet->mergeCells('A'.$r.':G'.$r);$sheet->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$r.':G'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$r++;
      foreach($group['categories'] as $cat){$sheet->setCellValue('B'.$r,$cat['label']);$c=2;foreach(array('Q1','Q2','Q3','Q4','total') as $q)$sheet->setCellValueByColumnAndRow($c++,$r,$cat['amounts'][$q]);$sheet->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);$r++;
        foreach($cat['accounts'] as $acc){$sheet->setCellValueExplicit('A'.$r,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$r,$acc['nama_rek']);$c=2;foreach(array('Q1','Q2','Q3','Q4','total') as $q)$sheet->setCellValueByColumnAndRow($c++,$r,$acc['amounts'][$q]);$r++;}
        $sheet->setCellValue('B'.$r,'Subtotal '.$cat['label']);$c=2;foreach(array('Q1','Q2','Q3','Q4','total') as $q)$sheet->setCellValueByColumnAndRow($c++,$r,$cat['amounts'][$q]);$sheet->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);$r++;}
      $sheet->setCellValue('B'.$r,'TOTAL '.$group['title']);$c=2;foreach(array('Q1','Q2','Q3','Q4','total') as $q)$sheet->setCellValueByColumnAndRow($c++,$r,$group['amounts'][$q]);$sheet->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);$r++;}
    $sheet->setCellValue('B'.$r,'LABA (RUGI) BERSIH');$c=2;foreach(array('Q1','Q2','Q3','Q4','total') as $q)$sheet->setCellValueByColumnAndRow($c++,$r,$net[$q]);$sheet->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('LABA RUGI KUARTAL'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$r,'column_count'=>7,'money_columns'=>array('C','D','E','F','G'),'filters'=>array('Tahun'=>$filters['year'],'Boundary'=>strip_tags(lrq_boundary_text($quarters)),'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('laba_rugi_kuartal_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="laba_rugi_kuartal_'.$filters['year'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  lrq_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();lrq_json('error',$e->getMessage());}
?>
