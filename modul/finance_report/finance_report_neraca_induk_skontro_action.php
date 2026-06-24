<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function nis_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function nis_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function nis_num($v){return number_format((float)$v,2,'.',',');}
function nis_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function nis_date_ok($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v)&&strtotime($v)!==false;}
function nis_filters(){$d=nis_req('as_of_date',date('Y-m-d'));if(!nis_date_ok($d))throw new Exception('As of date tidak valid.');return array('as_of_date'=>$d);}
function nis_empty_sections(){return array('aset'=>array('title'=>'ASET','categories'=>array(),'total'=>0),'kewajiban'=>array('title'=>'KEWAJIBAN','categories'=>array(),'total'=>0),'modal'=>array('title'=>'MODAL','categories'=>array(),'total'=>0));}
function nis_opening_warnings($db,$asOfDate){$year=(int)date('Y',strtotime($asOfDate));$r=$db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?",array($year));$w=array();if(!$r||(int)$r->cnt<1)$w[]='Saldo awal periode '.$year.' belum diisi; saldo awal dianggap 0.';elseif(abs((float)$r->debet-(float)$r->kredit)>0.01)$w[]='Saldo awal periode '.$year.' tidak balance.';return $w;}
function nis_balance_rows($db,$asOfDate){
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
  if($rows===false)throw new Exception('Query neraca skontro gagal: '.$db->getErrorMessage());
  return $rows;
}
function nis_profit($db,$asOfDate){$yearStart=date('Y-01-01',strtotime($asOfDate));$r=$db->fetch("SELECT COALESCE(SUM(CASE WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE 0 END),0) amount FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header INNER JOIN rekening r ON r.no_rek=d.no_rek INNER JOIN coa_kategori k ON k.id=r.kat_coa WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')",array($yearStart,$asOfDate));if($r===false)throw new Exception('Query laba/rugi berjalan gagal: '.$db->getErrorMessage());return(float)$r->amount;}
function nis_data($db,$filters){
  $warnings=nis_opening_warnings($db,$filters['as_of_date']);$sections=nis_empty_sections();
  foreach(nis_balance_rows($db,$filters['as_of_date']) as $row){if(abs((float)$row->saldo)<0.005)continue;$sk=$row->kategori_akun;$ck=(string)$row->kategori_id;if(!isset($sections[$sk]['categories'][$ck]))$sections[$sk]['categories'][$ck]=array('label'=>$row->kategori,'rows'=>array(),'total'=>0);$sections[$sk]['categories'][$ck]['rows'][]=$row;$sections[$sk]['categories'][$ck]['total']+=(float)$row->saldo;$sections[$sk]['total']+=(float)$row->saldo;}
  $profit=nis_profit($db,$filters['as_of_date']);if(abs($profit)>=0.005){if(!isset($sections['modal']['categories']['CY-PROFIT']))$sections['modal']['categories']['CY-PROFIT']=array('label'=>'Laba Tahun Berjalan','rows'=>array(),'total'=>0);$sections['modal']['categories']['CY-PROFIT']['rows'][]=(object)array('no_rek'=>'CY-PROFIT','nama_rek'=>'Laba (Rugi) Tahun Berjalan','level'=>3,'saldo'=>$profit);$sections['modal']['categories']['CY-PROFIT']['total']+=$profit;$sections['modal']['total']+=$profit;}
  return array($sections,$warnings);
}
function nis_lines($sections,$keys){
  $lines=array();
  foreach($keys as $key){$section=$sections[$key];$lines[]=array('type'=>'section','coa'=>'','label'=>$section['title'],'amount'=>null);foreach($section['categories'] as $cat){$lines[]=array('type'=>'category','coa'=>'','label'=>$cat['label'],'amount'=>$cat['total']);foreach($cat['rows'] as $row)$lines[]=array('type'=>'account','coa'=>$row->no_rek,'label'=>$row->nama_rek,'level'=>(int)$row->level,'amount'=>(float)$row->saldo);$lines[]=array('type'=>'subtotal','coa'=>'','label'=>'Subtotal '.$cat['label'],'amount'=>$cat['total']);}$lines[]=array('type'=>'total','coa'=>'','label'=>'TOTAL '.$section['title'],'amount'=>$section['total']);}
  return $lines;
}
function nis_line_cells($line,$side){
  if(!$line)return '<td></td><td></td><td class="text-right"></td>';
  $cls=$line['type']==='account'?'nis-account nis-level-'.max(0,min(6,(int)$line['level'])):'';
  return '<td>'.nis_h($line['coa']).'</td><td class="'.$cls.'">'.nis_h($line['label']).'</td><td class="text-right">'.($line['amount']===null?'':nis_num($line['amount'])).'</td>';
}
function nis_html($sections,$warnings,$filters){
  $asetLines=nis_lines($sections,array('aset'));$passivaLines=nis_lines($sections,array('kewajiban','modal'));$max=max(count($asetLines),count($passivaLines));$passiva=$sections['kewajiban']['total']+$sections['modal']['total'];$diff=$sections['aset']['total']-$passiva;
  $html='<div class="alert alert-info"><i class="fa fa-info-circle"></i> As of '.nis_h($filters['as_of_date']).'. Sumber: saldo_awal + jurnal POSTED; kategori dari coa_kategori.kategori_akun.</div>';
  if(count($warnings))$html.='<div class="alert alert-warning"><strong>Warning:</strong> '.nis_h(implode(' ',array_unique($warnings))).'</div>';
  if(abs($diff)>0.01)$html.='<div class="alert alert-warning"><strong>Balance check:</strong> Selisih aset dan kewajiban+modal '.nis_num($diff).'.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed nis-table"><thead><tr><th colspan="3" class="nis-side">ASET</th><th colspan="3" class="nis-side">KEWAJIBAN DAN MODAL</th></tr><tr class="bg-primary"><th style="width:90px">COA</th><th>Group / Akun</th><th class="text-right" style="width:140px">Saldo</th><th style="width:90px">COA</th><th>Group / Akun</th><th class="text-right" style="width:140px">Saldo</th></tr></thead><tbody>';
  for($i=0;$i<$max;$i++){$l=isset($asetLines[$i])?$asetLines[$i]:null;$r=isset($passivaLines[$i])?$passivaLines[$i]:null;$types=array();if($l)$types[]=$l['type'];if($r)$types[]=$r['type'];$cls=in_array('section',$types)?'nis-group':(in_array('category',$types)?'nis-category':(in_array('total',$types)||in_array('subtotal',$types)?'nis-total':''));$html.='<tr class="'.$cls.'">'.nis_line_cells($l,'left').nis_line_cells($r,'right').'</tr>';}
  $html.='<tr class="nis-grand"><td></td><td>TOTAL ASET</td><td class="text-right">'.nis_num($sections['aset']['total']).'</td><td></td><td>TOTAL KEWAJIBAN + MODAL</td><td class="text-right">'.nis_num($passiva).'</td></tr>';
  $html.='<tr class="nis-diff"><td colspan="4"></td><td>SELISIH BALANCE</td><td class="text-right">'.nis_num($diff).'</td></tr>';
  return $html.'</tbody></table></div>';
}
function nis_print_page($sections,$warnings,$filters){$info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=nis_html($sections,$warnings,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html><head><meta charset="utf-8"><title>Neraca Induk Skontro</title><link rel="stylesheet" href="'.nis_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.nis-table{width:100%;border-collapse:collapse!important}.nis-table th,.nis-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.nis-side,.nis-group td{background:#1d4ed8!important;color:#fff!important;font-weight:bold}.nis-category td{background:#e0f2fe!important;font-weight:bold}.nis-total td{background:#f3f4f6!important;font-weight:bold}.nis-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.nis-diff td{background:#fff7ed!important;font-weight:bold}.nis-account{padding-left:18px!important}.nis-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.nis-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.nis_h($company).'</h3><h4 style="margin:0 0 14px">Neraca (Induk Skontro)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=nis_filters();list($sections,$warnings)=nis_data($db,$filters);
  if($act==='filter')nis_json('success','OK',array('html'=>nis_html($sections,$warnings,$filters),'warnings'=>$warnings));
  if($act==='print')nis_print_page($sections,$warnings,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Neraca Skontro'));foreach(array('COA Aset','Aset','Saldo Aset','COA Passiva','Kewajiban dan Modal','Saldo Passiva') as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$asetLines=nis_lines($sections,array('aset'));$passivaLines=nis_lines($sections,array('kewajiban','modal'));$max=max(count($asetLines),count($passivaLines));$r=5;
    for($i=0;$i<$max;$i++){foreach(array(array(isset($asetLines[$i])?$asetLines[$i]:null,0),array(isset($passivaLines[$i])?$passivaLines[$i]:null,3)) as $pair){$line=$pair[0];$c=$pair[1];if(!$line)continue;$sheet->setCellValueByColumnAndRow($c,$r,$line['coa']);$sheet->setCellValueByColumnAndRow($c+1,$r,$line['label']);$sheet->setCellValueByColumnAndRow($c+2,$r,$line['amount']);if($line['type']!=='account')$sheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($c).$r.':'.PHPExcel_Cell::stringFromColumnIndex($c+2).$r)->getFont()->setBold(true);} $r++;}
    $passiva=$sections['kewajiban']['total']+$sections['modal']['total'];$diff=$sections['aset']['total']-$passiva;$sheet->setCellValue('B'.$r,'TOTAL ASET');$sheet->setCellValue('C'.$r,$sections['aset']['total']);$sheet->setCellValue('E'.$r,'TOTAL KEWAJIBAN + MODAL');$sheet->setCellValue('F'.$r,$passiva);$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;$sheet->setCellValue('E'.$r,'SELISIH BALANCE');$sheet->setCellValue('F'.$r,$diff);$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('NERACA INDUK SKONTRO'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$r,'column_count'=>6,'money_columns'=>array('C','F'),'filters'=>array('As Of Date'=>$filters['as_of_date'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('neraca_induk_skontro_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="neraca_induk_skontro_'.$filters['as_of_date'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  nis_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();nis_json('error',$e->getMessage());}
?>
