<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cfi_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function cfi_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function cfi_num($v){return number_format((float)$v,2,'.',',');}
function cfi_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function cfi_valid_date($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v) && strtotime($v)!==false;}

function cfi_ensure_mapping_table($db){
  $ok=$db->query("CREATE TABLE IF NOT EXISTS cash_flow_mapping (
    id INT(11) NOT NULL AUTO_INCREMENT,
    no_rek VARCHAR(50) NOT NULL,
    cash_flow_group VARCHAR(30) NOT NULL,
    cash_flow_type VARCHAR(30) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    is_active CHAR(1) NOT NULL DEFAULT 'Y',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cash_flow_mapping_account (no_rek),
    KEY idx_cash_flow_mapping_group (cash_flow_group),
    KEY idx_cash_flow_mapping_active (is_active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if($ok===false) throw new Exception('Gagal menyiapkan cash_flow_mapping: '.$db->getErrorMessage());
}

function cfi_filters(){
  $start=cfi_req('start_date',date('Y-m-01'));
  $end=cfi_req('end_date',date('Y-m-d'));
  if(!cfi_valid_date($start)||!cfi_valid_date($end)) throw new Exception('Format tanggal tidak valid.');
  if(strtotime($start)>strtotime($end)) throw new Exception('Start date tidak boleh lebih besar dari end date.');
  return array('start_date'=>$start,'end_date'=>$end);
}

function cfi_prev_date($date){$d=new DateTime($date);$d->modify('-1 day');return $d->format('Y-m-d');}
function cfi_empty_sections(){return array('operasi'=>array('title'=>'ARUS KAS DARI AKTIVITAS OPERASI','rows'=>array(),'total'=>0),'investasi'=>array('title'=>'ARUS KAS DARI AKTIVITAS INVESTASI','rows'=>array(),'total'=>0),'pendanaan'=>array('title'=>'ARUS KAS DARI AKTIVITAS PENDANAAN','rows'=>array(),'total'=>0));}
function cfi_add_row(&$section,$label,$amount,$account='',$source=''){$amount=(float)$amount;if(abs($amount)<0.005)return;$section['rows'][]=array('label'=>$label,'amount'=>$amount,'account'=>$account,'source'=>$source);$section['total']+=$amount;}
function cfi_lower($v){return strtolower(trim((string)$v));}

function cfi_mapping_stats($db){
  $row=$db->fetch("SELECT COUNT(*) total,SUM(cash_flow_group='cash_equivalent') cash_count,SUM(cash_flow_group='non_cash') noncash_count,SUM(cash_flow_group='investing') investing_count,SUM(cash_flow_group='financing') financing_count FROM cash_flow_mapping WHERE is_active='Y'");
  if($row===false) throw new Exception('Query mapping arus kas gagal: '.$db->getErrorMessage());
  return $row;
}

function cfi_opening_warnings($db,$filters){
  $years=array((int)date('Y',strtotime($filters['start_date']))=>true,(int)date('Y',strtotime($filters['end_date']))=>true);
  $warnings=array();
  foreach(array_keys($years) as $year){
    $row=$db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?",array($year));
    if(!$row||(int)$row->cnt<1){$warnings[]='Saldo awal periode '.$year.' belum diisi; saldo awal akun dianggap 0.';continue;}
    if(abs((float)$row->debet-(float)$row->kredit)>0.01)$warnings[]='Saldo awal periode '.$year.' tidak balance.';
  }
  return $warnings;
}

function cfi_net_income($db,$filters){
  $row=$db->fetch(
    "SELECT COALESCE(SUM(CASE
       WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE 0 END),0) amount,COUNT(*) line_count
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')",
    array($filters['start_date'],$filters['end_date'])
  );
  if($row===false) throw new Exception('Query laba bersih gagal: '.$db->getErrorMessage());
  return array((float)$row->amount,(int)$row->line_count);
}

function cfi_balance_rows($db,$asOfDate,$openingDate=null){
  $year=(int)date('Y',strtotime($asOfDate));
  $yearStart=$year.'-01-01';
  $params=array($year);
  $journalJoin='';
  if($openingDate!==null && $openingDate===$yearStart){
    $journalJoin="SELECT d.no_rek,0 debet,0 kredit FROM jurnal_detail d WHERE 1=0 GROUP BY d.no_rek";
  }else{
    $journalJoin="SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek";
    $params[]=$yearStart;$params[]=$asOfDate;
  }
  $rows=$db->query(
    "SELECT r.no_rek,r.nama_rek,r.level,k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,COALESCE(m.cash_flow_group,'') cash_flow_group,COALESCE(m.cash_flow_type,'') cash_flow_type,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN ($journalJoin) j ON j.no_rek=r.no_rek
     LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.id,LENGTH(r.no_rek),r.no_rek",
    $params
  );
  if($rows===false) throw new Exception('Query saldo akun gagal: '.$db->getErrorMessage());
  $map=array();foreach($rows as $r)$map[$r->no_rek]=$r;return $map;
}

function cfi_balance_before_start($db,$startDate){
  if(substr($startDate,5)==='01-01') return cfi_balance_rows($db,$startDate,$startDate);
  return cfi_balance_rows($db,cfi_prev_date($startDate),null);
}

function cfi_is_cash($row){
  $m=cfi_lower($row->cash_flow_group);
  $cat=cfi_lower($row->kategori);
  return $m==='cash_equivalent'||strpos($cat,'kas')!==false||strpos($cat,'bank')!==false;
}

function cfi_classify_bs($row){
  $m=cfi_lower($row->cash_flow_group);
  if(in_array($m,array('operating','investing','financing','non_cash'))) return $m;
  $cat=cfi_lower($row->kategori);
  if(strpos($cat,'akumulasi')!==false||strpos($cat,'penyusutan')!==false) return 'non_cash';
  if($row->kategori_akun==='aset'&&(strpos($cat,'aset tetap')!==false||strpos($cat,'aset lainnya')!==false)) return 'investing';
  if($row->kategori_akun==='kewajiban'&&strpos($cat,'jangka panjang')!==false) return 'financing';
  if($row->kategori_akun==='modal') return 'financing';
  return 'operating';
}

function cfi_delta_amount($row,$delta,$group){
  if($group==='operating'){
    return $row->kategori_akun==='aset' ? -$delta : $delta;
  }
  if($group==='investing'){
    return $row->kategori_akun==='aset' ? -$delta : $delta;
  }
  if($group==='financing') return $row->kategori_akun==='aset' ? -$delta : $delta;
  if($group==='non_cash'){
    if($row->kategori_akun==='aset') return $row->saldo_normal==='kredit' ? $delta : -$delta;
    return $delta;
  }
  return 0;
}

function cfi_non_cash_pl($db,$filters){
  $rows=$db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori_akun,
      SUM(CASE WHEN k.kategori_akun='pendapatan' THEN -(COALESCE(d.kredit,0)-COALESCE(d.debet,0)) ELSE COALESCE(d.debet,0)-COALESCE(d.kredit,0) END) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     INNER JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y' AND m.cash_flow_group='non_cash'
     WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY r.no_rek,r.nama_rek,k.kategori_akun",
    array($filters['start_date'],$filters['end_date'])
  );
  if($rows===false) throw new Exception('Query adjustment non-cash gagal: '.$db->getErrorMessage());
  return $rows;
}

function cfi_cash_total($map){$t=0;foreach($map as $row)if(cfi_is_cash($row))$t+=(float)$row->saldo;return $t;}

function cfi_data($db,$filters){
  cfi_ensure_mapping_table($db);
  $warnings=array();
  $stats=cfi_mapping_stats($db);
  if(!$stats || (int)$stats->total<1) $warnings[]='cash_flow_mapping belum diisi; klasifikasi memakai fallback kategori COA resmi.';
  if($stats && (int)$stats->cash_count<1) $warnings[]='Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA yang mengandung Kas/Bank.';
  if($stats && ((int)$stats->noncash_count<1||(int)$stats->investing_count<1||(int)$stats->financing_count<1)) $warnings[]='Mapping non-cash/investasi/pendanaan belum lengkap; beberapa akun memakai fallback kategori COA.';
  $warnings=array_merge($warnings,cfi_opening_warnings($db,$filters));

  list($netIncome,$plLines)=cfi_net_income($db,$filters);
  if($plLines<1) $warnings[]='Tidak ada jurnal P&L POSTED pada periode ini.';
  $beginMap=cfi_balance_before_start($db,$filters['start_date']);
  $endMap=cfi_balance_rows($db,$filters['end_date'],null);
  $cashBegin=cfi_cash_total($beginMap);
  $cashEnd=cfi_cash_total($endMap);

  $sections=cfi_empty_sections();
  cfi_add_row($sections['operasi'],'Laba (Rugi) Bersih Periode',$netIncome,'','posted_journal');
  foreach(cfi_non_cash_pl($db,$filters) as $row) cfi_add_row($sections['operasi'],'Adjustment non-cash - '.$row->nama_rek,(float)$row->amount,$row->no_rek,'mapping');

  $accounts=array_unique(array_merge(array_keys($beginMap),array_keys($endMap)));
  sort($accounts);
  foreach($accounts as $no){
    $row=isset($endMap[$no])?$endMap[$no]:$beginMap[$no];
    if(cfi_is_cash($row)) continue;
    $begin=isset($beginMap[$no])?(float)$beginMap[$no]->saldo:0;
    $end=isset($endMap[$no])?(float)$endMap[$no]->saldo:0;
    $delta=$end-$begin;
    if(abs($delta)<0.005) continue;
    $group=cfi_classify_bs($row);
    $amount=cfi_delta_amount($row,$delta,$group);
    $label='Perubahan '.$row->kategori.' - '.$row->nama_rek;
    if($group==='operating') cfi_add_row($sections['operasi'],$label,$amount,$row->no_rek,$row->cash_flow_group?'mapping':'coa');
    elseif($group==='investing') cfi_add_row($sections['investasi'],$label,$amount,$row->no_rek,$row->cash_flow_group?'mapping':'coa');
    elseif($group==='financing') cfi_add_row($sections['pendanaan'],$label,$amount,$row->no_rek,$row->cash_flow_group?'mapping':'coa');
    elseif($group==='non_cash') cfi_add_row($sections['operasi'],'Adjustment non-cash - '.$row->nama_rek,$amount,$row->no_rek,$row->cash_flow_group?'mapping':'coa');
  }
  $netFlow=$sections['operasi']['total']+$sections['investasi']['total']+$sections['pendanaan']['total'];
  $cashDelta=$cashEnd-$cashBegin;
  if(abs($netFlow-$cashDelta)>0.01) $warnings[]='Net cash flow belum sama dengan perubahan saldo kas; cek kelengkapan cash_flow_mapping dan akun kas/bank.';
  return array($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta);
}

function cfi_html($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta,$filters){
  $html='';
  if(count($warnings)) $html.='<div class="alert alert-warning"><strong>Warning:</strong> '.cfi_h(implode(' ', $warnings)).'</div>';
  $html.='<div class="alert alert-info"><i class="fa fa-info-circle"></i> Periode '.cfi_h($filters['start_date']).' s/d '.cfi_h($filters['end_date']).'. Sumber: jurnal POSTED, COA resmi, saldo_awal, dan cash_flow_mapping.</div>';
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed cfi-table"><thead><tr class="bg-primary"><th style="width:150px">COA</th><th>Uraian</th><th class="text-right" style="width:180px">Nilai</th></tr></thead><tbody>';
  foreach($sections as $section){
    $html.='<tr class="cfi-section"><th colspan="3">'.cfi_h($section['title']).'</th></tr>';
    if(!count($section['rows'])) $html.='<tr><td></td><td class="text-muted"><em>Tidak ada mutasi</em></td><td class="text-right">0.00</td></tr>';
    foreach($section['rows'] as $row)$html.='<tr><td>'.cfi_h($row['account']).'</td><td class="cfi-account">'.cfi_h($row['label']).'</td><td class="text-right">'.cfi_num($row['amount']).'</td></tr>';
    $html.='<tr class="cfi-total"><th></th><th>Net '.cfi_h(strtolower($section['title'])).'</th><td class="text-right">'.cfi_num($section['total']).'</td></tr>';
  }
  $html.='<tr class="cfi-grand"><th></th><th>NET CASH FLOW</th><td class="text-right">'.cfi_num($netFlow).'</td></tr>';
  $html.='<tr class="cfi-total"><th></th><th>Saldo Kas Awal</th><td class="text-right">'.cfi_num($cashBegin).'</td></tr>';
  $html.='<tr class="cfi-total"><th></th><th>Saldo Kas Akhir</th><td class="text-right">'.cfi_num($cashEnd).'</td></tr>';
  $html.='<tr class="cfi-check"><th></th><th>Selisih Rekonsiliasi Kas</th><td class="text-right">'.cfi_num($netFlow-$cashDelta).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function cfi_print_page($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta,$filters){
  $info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$body=cfi_html($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta,$filters);$assetBase=rtrim(base_url(),'/').'/assets/';
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Arus Kas Tak Langsung</title><link rel="stylesheet" href="'.cfi_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.cfi-table{width:100%;border-collapse:collapse!important}.cfi-table th,.cfi-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cfi-section th{background:#1d4ed8!important;color:#fff!important}.cfi-total th,.cfi-total td{background:#f3f4f6!important;font-weight:bold}.cfi-grand th,.cfi-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.cfi-check th,.cfi-check td{background:#fff7ed!important;font-weight:bold}.cfi-account{padding-left:24px!important}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.cfi-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cfi_h($company).'</h3><h4 style="margin:0 0 14px">Arus Kas (Tak Langsung)</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;
}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=cfi_filters();list($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta)=cfi_data($db,$filters);
  if($act==='filter') cfi_json('success','OK',array('html'=>cfi_html($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta,$filters),'warnings'=>$warnings));
  if($act==='print') cfi_print_page($sections,$warnings,$cashBegin,$cashEnd,$netFlow,$cashDelta,$filters);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Arus Kas'));$sheet->setCellValue('A4','COA');$sheet->setCellValue('B4','Uraian');$sheet->setCellValue('C4','Nilai');$r=5;
    foreach($sections as $section){$sheet->setCellValue('A'.$r,$section['title']);$sheet->mergeCells('A'.$r.':C'.$r);$sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');$sheet->getStyle('A'.$r.':C'.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');$r++;
      foreach($section['rows'] as $row){$sheet->setCellValueExplicit('A'.$r,$row['account'],PHPExcel_Cell_DataType::TYPE_STRING);$sheet->setCellValue('B'.$r,$row['label']);$sheet->setCellValue('C'.$r,$row['amount']);$r++;}
      $sheet->setCellValue('B'.$r,'Net '.strtolower($section['title']));$sheet->setCellValue('C'.$r,$section['total']);$sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true);$r++;}
    foreach(array('NET CASH FLOW'=>$netFlow,'Saldo Kas Awal'=>$cashBegin,'Saldo Kas Akhir'=>$cashEnd,'Selisih Rekonsiliasi Kas'=>$netFlow-$cashDelta) as $label=>$amount){$sheet->setCellValue('B'.$r,$label);$sheet->setCellValue('C'.$r,$amount);$sheet->getStyle('A'.$r.':C'.$r)->getFont()->setBold(true);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('ARUS KAS TAK LANGSUNG'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>3,'money_columns'=>array('C'),'filters'=>array('Periode'=>$filters['start_date'].' s/d '.$filters['end_date'],'Status'=>'POSTED')));
    $tmp=erpkb_excel_temp_file('arus_kas_tak_langsung_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="arus_kas_tak_langsung_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  cfi_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();cfi_json('error',$e->getMessage());}
?>
