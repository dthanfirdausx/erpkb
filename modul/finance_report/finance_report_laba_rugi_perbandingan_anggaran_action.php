<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function lrba_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function lrba_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lrba_num($v){return number_format((float)$v,2,'.',',');}
function lrba_pct($v){return $v===null?'-':number_format((float)$v,2,'.',',').'%';}
function lrba_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function lrba_date_ok($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v)&&strtotime($v)!==false;}

function lrba_ensure_budget_tables($db){
  $ok=$db->query("CREATE TABLE IF NOT EXISTS finance_budget_header (id INT(11) NOT NULL AUTO_INCREMENT,budget_version VARCHAR(50) NOT NULL,budget_name VARCHAR(150) NOT NULL,fiscal_year INT(4) DEFAULT NULL,start_date DATE NOT NULL,end_date DATE NOT NULL,status ENUM('DRAFT','APPROVED','INACTIVE') NOT NULL DEFAULT 'DRAFT',description VARCHAR(255) DEFAULT NULL,created_by VARCHAR(100) DEFAULT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_by VARCHAR(100) DEFAULT NULL,updated_at DATETIME DEFAULT NULL,PRIMARY KEY (id),UNIQUE KEY uq_finance_budget_version (budget_version),KEY idx_finance_budget_period (start_date,end_date,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if($ok===false) throw new Exception('Gagal menyiapkan finance_budget_header: '.$db->getErrorMessage());
  $ok=$db->query("CREATE TABLE IF NOT EXISTS finance_budget_detail (id INT(11) NOT NULL AUTO_INCREMENT,budget_header_id INT(11) NOT NULL,no_rek VARCHAR(50) NOT NULL,period_month CHAR(7) NOT NULL,cost_center_id INT(11) DEFAULT NULL,profit_center_id INT(11) DEFAULT NULL,amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,note VARCHAR(255) DEFAULT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT NULL,PRIMARY KEY (id),KEY idx_budget_detail_header_month (budget_header_id,period_month),KEY idx_budget_detail_account (no_rek),KEY idx_budget_detail_cc_pc (cost_center_id,profit_center_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if($ok===false) throw new Exception('Gagal menyiapkan finance_budget_detail: '.$db->getErrorMessage());
}
function lrba_filters(){
  $s=lrba_req('start_date',date('Y-m-01'));$e=lrba_req('end_date',date('Y-m-d'));$bv=lrba_req('budget_version');$cc=lrba_req('cost_center');$pc=lrba_req('profit_center');
  if(!lrba_date_ok($s)||!lrba_date_ok($e)) throw new Exception('Format tanggal tidak valid.');
  if(strtotime($s)>strtotime($e)) throw new Exception('Start date tidak boleh lebih besar dari end date.');
  if($cc!==''&&!ctype_digit($cc)) throw new Exception('Cost center tidak valid.');
  if($pc!==''&&!ctype_digit($pc)) throw new Exception('Profit center tidak valid.');
  return array('start_date'=>$s,'end_date'=>$e,'budget_version'=>$bv,'cost_center'=>$cc,'profit_center'=>$pc);
}
function lrba_months($start,$end){$m=array();$c=new DateTime(date('Y-m-01',strtotime($start)));$last=new DateTime(date('Y-m-01',strtotime($end)));while($c<=$last){$m[]=$c->format('Y-m');$c->modify('+1 month');}return $m;}
function lrba_group_key($kategoriAkun,$kategori){$kategoriAkun=strtolower(trim((string)$kategoriAkun));$kategori=strtolower(trim((string)$kategori));if($kategoriAkun==='pendapatan')return strpos($kategori,'lain')!==false?'pendapatan_lain':'pendapatan';if(strpos($kategori,'pokok')!==false||strpos($kategori,'persediaan')!==false)return'hpp';return strpos($kategori,'lain')!==false?'beban_lain':'beban_operasional';}
function lrba_amount(){return array('actual'=>0,'budget'=>0);}
function lrba_groups(){return array('pendapatan'=>array('title'=>'PENDAPATAN','kind'=>'income','amounts'=>lrba_amount(),'categories'=>array()),'hpp'=>array('title'=>'BEBAN POKOK / PERSEDIAAN','kind'=>'expense','amounts'=>lrba_amount(),'categories'=>array()),'beban_operasional'=>array('title'=>'BEBAN OPERASIONAL','kind'=>'expense','amounts'=>lrba_amount(),'categories'=>array()),'pendapatan_lain'=>array('title'=>'PENDAPATAN LAIN-LAIN','kind'=>'income','amounts'=>lrba_amount(),'categories'=>array()),'beban_lain'=>array('title'=>'BEBAN LAIN-LAIN','kind'=>'expense','amounts'=>lrba_amount(),'categories'=>array()));}
function lrba_add(&$groups,&$net,$row,$slot,$amount){
  $gk=lrba_group_key($row->kategori_akun,$row->kategori);$ck=(string)$row->kategori_id;$ak=(string)$row->no_rek;
  if(!isset($groups[$gk]['categories'][$ck]))$groups[$gk]['categories'][$ck]=array('label'=>$row->kategori,'kategori_akun'=>$row->kategori_akun,'amounts'=>lrba_amount(),'accounts'=>array());
  if(!isset($groups[$gk]['categories'][$ck]['accounts'][$ak]))$groups[$gk]['categories'][$ck]['accounts'][$ak]=array('no_rek'=>$row->no_rek,'nama_rek'=>$row->nama_rek,'level'=>(int)$row->level,'amounts'=>lrba_amount());
  $amount=(float)$amount;
  $groups[$gk]['categories'][$ck]['accounts'][$ak]['amounts'][$slot]+=$amount;
  $groups[$gk]['categories'][$ck]['amounts'][$slot]+=$amount;
  $groups[$gk]['amounts'][$slot]+=$amount;
  $net[$slot]+=$groups[$gk]['kind']==='income'?$amount:-$amount;
}
function lrba_actual_rows($db,$filters){
  $params=array($filters['start_date'],$filters['end_date']);$where="h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED'";
  if($filters['cost_center']!==''){$where.=" AND d.cost_center_id=?";$params[]=(int)$filters['cost_center'];}
  if($filters['profit_center']!==''){$where.=" AND d.profit_center_id=?";$params[]=(int)$filters['profit_center'];}
  $rows=$db->query("SELECT k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level,CASE WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0)) ELSE SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0)) END amount FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header INNER JOIN rekening r ON r.no_rek=d.no_rek INNER JOIN coa_kategori k ON k.id=r.kat_coa WHERE $where AND k.kategori_akun IN ('pendapatan','beban') GROUP BY k.id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level HAVING ABS(amount)>=0.005 ORDER BY k.id,LENGTH(r.no_rek),r.no_rek",$params);
  if($rows===false) throw new Exception('Query actual gagal: '.$db->getErrorMessage());
  return $rows;
}
function lrba_budget_rows($db,$filters,&$warnings){
  $months=lrba_months($filters['start_date'],$filters['end_date']);$ph=implode(',',array_fill(0,count($months),'?'));$params=$months;
  $where="bd.period_month IN ($ph) AND bh.status IN ('DRAFT','APPROVED')";
  if($filters['budget_version']!==''){$where.=" AND bh.budget_version=?";$params[]=$filters['budget_version'];}
  else $where.=" AND bh.start_date<=? AND bh.end_date>=?";
  if($filters['budget_version']===''){ $params[]=$filters['end_date'];$params[]=$filters['start_date']; }
  if($filters['cost_center']!==''){$where.=" AND (bd.cost_center_id=? OR bd.cost_center_id IS NULL)";$params[]=(int)$filters['cost_center'];}
  if($filters['profit_center']!==''){$where.=" AND (bd.profit_center_id=? OR bd.profit_center_id IS NULL)";$params[]=(int)$filters['profit_center'];}
  $rows=$db->query("SELECT k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level,SUM(COALESCE(bd.amount,0)) amount,COUNT(*) line_count FROM finance_budget_detail bd INNER JOIN finance_budget_header bh ON bh.id=bd.budget_header_id INNER JOIN rekening r ON r.no_rek=bd.no_rek INNER JOIN coa_kategori k ON k.id=r.kat_coa WHERE $where AND k.kategori_akun IN ('pendapatan','beban') GROUP BY k.id,k.kategori,k.kategori_akun,k.saldo_normal,r.no_rek,r.nama_rek,r.level HAVING ABS(amount)>=0.005 ORDER BY k.id,LENGTH(r.no_rek),r.no_rek",$params);
  if($rows===false) throw new Exception('Query budget gagal: '.$db->getErrorMessage());
  $cnt=0;foreach($rows as $r)$cnt++;
  if($cnt<1)$warnings[]='Budget belum ditemukan untuk filter ini. Struktur tabel yang direkomendasikan sudah tersedia: finance_budget_header dan finance_budget_detail; isi budget per akun dan period_month agar kolom budget terisi.';
  return $rows;
}
function lrba_data($db,$filters){
  lrba_ensure_budget_tables($db);$warnings=array();$groups=lrba_groups();$net=lrba_amount();
  foreach(lrba_actual_rows($db,$filters) as $r)lrba_add($groups,$net,$r,'actual',$r->amount);
  $budgetRows=lrba_budget_rows($db,$filters,$warnings);foreach($budgetRows as $r)lrba_add($groups,$net,$r,'budget',$r->amount);
  return array($groups,$net,$warnings);
}
function lrba_variance($a){return (float)$a['actual']-(float)$a['budget'];}
function lrba_pct_var($a){$b=(float)$a['budget'];return abs($b)<0.005?null:(lrba_variance($a)/abs($b)*100);}
function lrba_fav($amounts,$kind){$v=lrba_variance($amounts);return $kind==='income'?$v>=0:$v<=0;}
function lrba_cells($amounts,$kind){$fav=lrba_fav($amounts,$kind);$cls=$fav?'lrba-fav':'lrba-unfav';return '<td class="text-right">'.lrba_num($amounts['actual']).'</td><td class="text-right">'.lrba_num($amounts['budget']).'</td><td class="text-right '.$cls.'">'.lrba_num(lrba_variance($amounts)).'</td><td class="text-right '.$cls.'">'.lrba_pct(lrba_pct_var($amounts)).'</td>';}
function lrba_html($groups,$net,$warnings,$filters){
  $html='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Periode '.lrba_h($filters['start_date']).' s/d '.lrba_h($filters['end_date']).'. Actual dari jurnal POSTED; budget dari finance_budget_header/detail.'.($filters['budget_version']!==''?' Version: '.lrba_h($filters['budget_version']).'.':'').'</div>';
  if(count($warnings))$html.='<div class="alert alert-warning"><strong>Warning:</strong> '.lrba_h(implode(' ',array_unique($warnings))).'</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed lrba-table"><thead><tr class="bg-primary"><th style="min-width:110px">COA</th><th style="min-width:280px">Group / Akun</th><th class="text-right" style="min-width:130px">Actual</th><th class="text-right" style="min-width:130px">Budget</th><th class="text-right" style="min-width:130px">Variance</th><th class="text-right" style="min-width:110px">Variance %</th></tr></thead><tbody>';
  foreach($groups as $group){$html.='<tr class="lrba-group"><th colspan="6">'.lrba_h($group['title']).'</th></tr>';if(!count($group['categories']))$html.='<tr><td colspan="6" class="text-muted"><em>Tidak ada transaksi/budget</em></td></tr>';foreach($group['categories'] as $cat){$html.='<tr class="lrba-category"><th></th><th>'.lrba_h($cat['label']).' <small>('.lrba_h($cat['kategori_akun']).')</small></th>'.lrba_cells($cat['amounts'],$group['kind']).'</tr>';foreach($cat['accounts'] as $acc){$level=max(0,min(6,(int)$acc['level']));$html.='<tr><td>'.lrba_h($acc['no_rek']).'</td><td class="lrba-account lrba-level-'.$level.'">'.lrba_h($acc['nama_rek']).'</td>'.lrba_cells($acc['amounts'],$group['kind']).'</tr>';}$html.='<tr class="active lrba-subtotal"><th></th><th>Subtotal '.lrba_h($cat['label']).'</th>'.lrba_cells($cat['amounts'],$group['kind']).'</tr>';}$html.='<tr class="lrba-total"><th></th><th>TOTAL '.lrba_h($group['title']).'</th>'.lrba_cells($group['amounts'],$group['kind']).'</tr>';}
  return $html.'<tr class="lrba-net"><th></th><th>LABA (RUGI) BERSIH</th>'.lrba_cells($net,'income').'</tr></tbody></table></div>';
}
function lrba_print_page($groups,$net,$warnings,$filters){
  $info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=lrba_html($groups,$net,$warnings,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Laba Rugi Perbandingan Anggaran</title><link rel="stylesheet" href="'.lrba_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.lrba-table{width:100%;border-collapse:collapse!important}.lrba-table th,.lrba-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.lrba-group th{background:#1d4ed8!important;color:#fff!important}.lrba-category th,.lrba-category td{background:#e0f2fe!important;font-weight:bold}.lrba-total th,.lrba-total td{background:#f3f4f6!important;font-weight:bold}.lrba-net th,.lrba-net td{background:#0f766e!important;color:#fff!important;font-weight:bold}.lrba-fav{color:#0f766e!important;font-weight:bold}.lrba-unfav{color:#b91c1c!important;font-weight:bold}.lrba-account{padding-left:18px!important}.lrba-level-3{padding-left:34px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.lrba-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.lrba_h($company).'</h3><h4 style="margin:0 0 14px">Laba/Rugi (Perbandingan Anggaran)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;
}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=lrba_filters();list($groups,$net,$warnings)=lrba_data($db,$filters);
  if($act==='filter')lrba_json('success','OK',array('html'=>lrba_html($groups,$net,$warnings,$filters),'warnings'=>$warnings));
  if($act==='print')lrba_print_page($groups,$net,$warnings,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('LR Budget Compare'));foreach(array('COA','Group / Akun','Actual','Budget','Variance','Variance %') as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$r=5;
    foreach($groups as $group){$sheet->setCellValue('A'.$r,$group['title']);$sheet->mergeCells('A'.$r.':F'.$r);$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$r.':F'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$r++;foreach($group['categories'] as $cat){$sheet->setCellValue('B'.$r,$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['actual']);$sheet->setCellValue('D'.$r,$cat['amounts']['budget']);$sheet->setCellValue('E'.$r,lrba_variance($cat['amounts']));$sheet->setCellValue('F'.$r,lrba_pct_var($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;foreach($cat['accounts'] as $acc){$sheet->setCellValueExplicit('A'.$r,$acc['no_rek'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$r,$acc['nama_rek']);$sheet->setCellValue('C'.$r,$acc['amounts']['actual']);$sheet->setCellValue('D'.$r,$acc['amounts']['budget']);$sheet->setCellValue('E'.$r,lrba_variance($acc['amounts']));$sheet->setCellValue('F'.$r,lrba_pct_var($acc['amounts']));$r++;}$sheet->setCellValue('B'.$r,'Subtotal '.$cat['label']);$sheet->setCellValue('C'.$r,$cat['amounts']['actual']);$sheet->setCellValue('D'.$r,$cat['amounts']['budget']);$sheet->setCellValue('E'.$r,lrba_variance($cat['amounts']));$sheet->setCellValue('F'.$r,lrba_pct_var($cat['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}$sheet->setCellValue('B'.$r,'TOTAL '.$group['title']);$sheet->setCellValue('C'.$r,$group['amounts']['actual']);$sheet->setCellValue('D'.$r,$group['amounts']['budget']);$sheet->setCellValue('E'.$r,lrba_variance($group['amounts']));$sheet->setCellValue('F'.$r,lrba_pct_var($group['amounts']));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);$r++;}
    $sheet->setCellValue('B'.$r,'LABA (RUGI) BERSIH');$sheet->setCellValue('C'.$r,$net['actual']);$sheet->setCellValue('D'.$r,$net['budget']);$sheet->setCellValue('E'.$r,lrba_variance($net));$sheet->setCellValue('F'.$r,lrba_pct_var($net));$sheet->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('LABA RUGI PERBANDINGAN ANGGARAN'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>$r,'column_count'=>6,'money_columns'=>array('C','D','E'),'decimal_columns'=>array('F'),'filters'=>array('Periode'=>$filters['start_date'].' s/d '.$filters['end_date'],'Budget Version'=>$filters['budget_version'],'Cost Center'=>$filters['cost_center'],'Profit Center'=>$filters['profit_center'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('laba_rugi_budget_compare_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="laba_rugi_budget_compare_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  lrba_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();lrba_json('error',$e->getMessage());}
?>
