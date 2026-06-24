<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cpm_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function cpm_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function cpm_num($v){return number_format((float)$v,2,'.',',');}
function cpm_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function cpm_table_exists($db,$table){$r=$db->fetch("SHOW TABLES LIKE ?",array($table));return (bool)$r;}

function cpm_ensure_mapping($db){
  $db->query("CREATE TABLE IF NOT EXISTS cash_flow_mapping (
    id INT(11) NOT NULL AUTO_INCREMENT,no_rek VARCHAR(50) NOT NULL,cash_flow_group VARCHAR(30) NOT NULL,cash_flow_type VARCHAR(30) DEFAULT NULL,note VARCHAR(255) DEFAULT NULL,is_active CHAR(1) NOT NULL DEFAULT 'Y',created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT NULL,PRIMARY KEY (id),UNIQUE KEY uq_cash_flow_mapping_account (no_rek),KEY idx_cash_flow_mapping_group (cash_flow_group),KEY idx_cash_flow_mapping_active (is_active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}
function cpm_filters(){
  $start=cpm_req('start_month',date('Y-m'));$months=(int)cpm_req('number_of_months','5');
  if(!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/',$start)) throw new Exception('Format start month tidak valid.');
  if($months<1||$months>24) throw new Exception('Jumlah bulan harus 1 sampai 24.');
  $startDate=$start.'-01';$end=new DateTime($startDate);$end->modify('+'.($months-1).' month');$end->modify('last day of this month');
  return array('start_month'=>$start,'number_of_months'=>$months,'start_date'=>$startDate,'end_date'=>$end->format('Y-m-d'));
}
function cpm_buckets($filters){
  $b=array();$cur=new DateTime($filters['start_date']);
  for($i=0;$i<$filters['number_of_months'];$i++){
    $key=$cur->format('Y-m');$b[$key]=array('key'=>$key,'label'=>$cur->format('M Y'),'start'=>$cur->format('Y-m-01'),'end'=>$cur->format('Y-m-t'),'opening'=>0,'inflow'=>0,'outflow'=>0,'net'=>0,'ending'=>0,'events'=>array());
    $cur->modify('first day of next month');
  }
  return $b;
}
function cpm_bucket_key($date,$filters){
  if(!$date||strtotime($date)<strtotime($filters['start_date'])) $date=$filters['start_date'];
  if(strtotime($date)>strtotime($filters['end_date'])) return null;
  return date('Y-m',strtotime($date));
}
function cpm_add_event(&$buckets,$filters,$date,$source,$docNo,$partner,$inflow,$outflow,$note=''){
  $key=cpm_bucket_key($date,$filters); if($key===null||!isset($buckets[$key])) return;
  $inflow=(float)$inflow;$outflow=(float)$outflow; if(abs($inflow)<0.005&&abs($outflow)<0.005)return;
  $buckets[$key]['inflow']+=$inflow;$buckets[$key]['outflow']+=$outflow;
  $buckets[$key]['events'][]=array('date'=>$date,'source'=>$source,'doc_no'=>$docNo,'partner'=>$partner,'inflow'=>$inflow,'outflow'=>$outflow,'note'=>$note);
}
function cpm_cash_balance($db,$asOfDate){
  cpm_ensure_mapping($db);$year=(int)date('Y',strtotime($asOfDate));$yearStart=$year.'-01-01';
  $rows=$db->query(
    "SELECT r.no_rek,k.kategori,k.saldo_normal,COALESCE(m.cash_flow_group,'') cash_flow_group,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'
     WHERE k.kategori_akun='aset'",
    array($year,$yearStart,$asOfDate)
  );
  if($rows===false) throw new Exception('Query saldo kas gagal: '.$db->getErrorMessage());
  $total=0;$mapped=0;$fallback=0;foreach($rows as $r){$cat=strtolower((string)$r->kategori);$is=$r->cash_flow_group==='cash_equivalent'||strpos($cat,'kas')!==false||strpos($cat,'bank')!==false;if(!$is)continue;$total+=(float)$r->saldo;if($r->cash_flow_group==='cash_equivalent')$mapped++;else$fallback++;}
  return array($total,$mapped,$fallback);
}
function cpm_fetch_events($db,$filters,&$buckets,&$warnings){
  if(cpm_table_exists($db,'sales_invoice')){
    $rows=$db->query("SELECT si.id_sales,si.no_sales_invoice doc_no,si.bill_to partner,COALESCE(si.due_date,si.invoice_date,si.posting_date) due_date,si.due_date real_due,COALESCE(si.gross_amount,si.net_amount+si.tax_amount,0)-COALESCE((SELECT SUM(p.amount) FROM erp_incoming_payment p WHERE p.status='POSTED' AND (p.sales_invoice_id=si.id_sales OR p.sales_invoice_no=si.no_sales_invoice)),0) outstanding FROM sales_invoice si WHERE si.billing_status='POSTED' AND COALESCE(si.posting_date,si.invoice_date)<=?",array($filters['end_date']));
    if($rows===false) throw new Exception('Query AR open gagal: '.$db->getErrorMessage());
    foreach($rows as $r){if((float)$r->outstanding<=0.005)continue;if(!$r->real_due)$warnings[]='Ada customer invoice tanpa due_date; dibucket ke bulan invoice/start month.';cpm_add_event($buckets,$filters,$r->due_date,'Customer Invoice',$r->doc_no,$r->partner,$r->outstanding,0,$r->real_due?'Outstanding AR':'Fallback due date');}
  }else $warnings[]='Tabel sales_invoice tidak tersedia.';
  if(cpm_table_exists($db,'erp_vendor_invoice')){
    $rows=$db->query("SELECT vi.id,vi.vendor_invoice_no doc_no,vi.vendor_code partner,COALESCE(vi.due_date,vi.document_date,vi.posting_date) due_date,vi.due_date real_due,vi.gross_amount-COALESCE((SELECT SUM(p.amount) FROM erp_vendor_payment p WHERE p.status='POSTED' AND (p.vendor_invoice_id=vi.id OR p.vendor_invoice_no=vi.vendor_invoice_no)),0) outstanding FROM erp_vendor_invoice vi WHERE vi.status='POSTED' AND vi.payment_status IN ('OPEN','PARTIAL') AND vi.posting_date<=?",array($filters['end_date']));
    if($rows===false) throw new Exception('Query AP open gagal: '.$db->getErrorMessage());
    foreach($rows as $r){if((float)$r->outstanding<=0.005)continue;if(!$r->real_due)$warnings[]='Ada vendor invoice tanpa due_date; dibucket ke bulan dokumen/start month.';cpm_add_event($buckets,$filters,$r->due_date,'Vendor Invoice',$r->doc_no,$r->partner,0,$r->outstanding,$r->real_due?'Outstanding AP':'Fallback due date');}
  }else $warnings[]='Tabel erp_vendor_invoice tidak tersedia.';
  if(cpm_table_exists($db,'erp_incoming_payment')){
    $rows=$db->query("SELECT incoming_payment_no doc_no,customer_code partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_incoming_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false)foreach($rows as $r)cpm_add_event($buckets,$filters,$r->due_date,'Incoming Payment Plan',$r->doc_no,$r->partner,$r->amount,0,'DRAFT planning');
  }
  if(cpm_table_exists($db,'erp_vendor_payment')){
    $rows=$db->query("SELECT vendor_payment_no doc_no,vendor_code partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_vendor_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false)foreach($rows as $r)cpm_add_event($buckets,$filters,$r->due_date,'Vendor Payment Plan',$r->doc_no,$r->partner,0,$r->amount,'DRAFT planning');
  }
  if(cpm_table_exists($db,'erp_bank_receipt')){
    $rows=$db->query("SELECT bank_receipt_no doc_no,payer_name partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_bank_receipt WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false)foreach($rows as $r)cpm_add_event($buckets,$filters,$r->due_date,'Bank Receipt Plan',$r->doc_no,$r->partner,$r->amount,0,'DRAFT planning');
  }
  if(cpm_table_exists($db,'erp_bank_payment')){
    $rows=$db->query("SELECT bank_payment_no doc_no,payee_name partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_bank_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false)foreach($rows as $r)cpm_add_event($buckets,$filters,$r->due_date,'Bank Payment Plan',$r->doc_no,$r->partner,0,$r->amount,'DRAFT planning');
  }
  if(cpm_table_exists($db,'purchase_order')) $warnings[]='Purchase order tidak diproyeksikan otomatis karena belum ada payment due date resmi; AP memakai vendor invoice/payment.';
}
function cpm_data($db,$filters){
  list($opening,$mapped,$fallback)=cpm_cash_balance($db,$filters['start_date']);$warnings=array();
  if($mapped<1)$warnings[]='Mapping kas/bank belum lengkap; opening cash memakai fallback kategori COA Kas/Bank.';
  if($fallback>0)$warnings[]='Sebagian akun kas/bank terdeteksi dari kategori COA, bukan cash_flow_mapping.';
  $buckets=cpm_buckets($filters);cpm_fetch_events($db,$filters,$buckets,$warnings);
  $running=$opening;$totalIn=0;$totalOut=0;foreach($buckets as $k=>$b){$buckets[$k]['opening']=$running;$buckets[$k]['net']=(float)$b['inflow']-(float)$b['outflow'];$running+=$buckets[$k]['net'];$buckets[$k]['ending']=$running;$totalIn+=(float)$b['inflow'];$totalOut+=(float)$b['outflow'];}
  return array($buckets,$warnings,array('opening_cash'=>$opening,'total_inflow'=>$totalIn,'total_outflow'=>$totalOut,'ending_cash'=>$running));
}
function cpm_assumption_html($filters){
  return '<div class="alert alert-info"><strong>Asumsi:</strong> Opening cash dihitung per '.cpm_h($filters['start_date']).'. Inflow berasal dari AR open dan draft incoming/bank receipt planning. Outflow berasal dari AP open dan draft vendor/bank payment planning. Dokumen overdue dibucket ke start month. Tidak ada posting jurnal dari laporan ini.</div>';
}
function cpm_warning_html($warnings){return count($warnings)?'<div class="alert alert-warning"><strong>Warning:</strong> '.cpm_h(implode(' ',array_unique($warnings))).'</div>':'';}
function cpm_html($buckets,$filters,$warnings){
  $html=cpm_assumption_html($filters).cpm_warning_html($warnings);
  $html.='<div class="table-responsive"><table class="table table-bordered table-condensed cpm-table"><thead><tr class="bg-primary"><th>Bulan</th><th class="text-right">Opening Cash</th><th class="text-right">Inflow</th><th class="text-right">Outflow</th><th class="text-right">Net Movement</th><th class="text-right">Ending Cash</th><th>Detail</th></tr></thead><tbody>';
  foreach($buckets as $b){$details=array();foreach($b['events'] as $e)$details[]=cpm_h($e['source'].' '.$e['doc_no'].' '.($e['inflow']>0?'+'.cpm_num($e['inflow']):'-'.cpm_num($e['outflow'])));$html.='<tr class="'.($b['ending']<0?'cpm-neg':'').'"><td>'.cpm_h($b['label']).'</td><td class="text-right">'.cpm_num($b['opening']).'</td><td class="text-right">'.cpm_num($b['inflow']).'</td><td class="text-right">'.cpm_num($b['outflow']).'</td><td class="text-right">'.cpm_num($b['net']).'</td><td class="text-right">'.cpm_num($b['ending']).'</td><td>'.implode('<br>',$details).'</td></tr>';}
  return $html.'</tbody></table></div>';
}
function cpm_chart($buckets){$c=array('labels'=>array(),'ending'=>array(),'inflow'=>array(),'outflow'=>array());foreach($buckets as $b){$c['labels'][]=$b['label'];$c['ending'][]=round($b['ending'],2);$c['inflow'][]=round($b['inflow'],2);$c['outflow'][]=round($b['outflow'],2);}return $c;}
function cpm_print_chart($buckets){
  $vals=array();foreach($buckets as $b)$vals[]=abs((float)$b['ending']);$max=max($vals);if($max<1)$max=1;$html='<div class="print-chart">';
  foreach($buckets as $b){$w=min(100,max(2,abs($b['ending'])/$max*100));$color=$b['ending']<0?'#dc2626':'#0f766e';$html.='<div class="pc-row"><span>'.cpm_h($b['label']).'</span><div><i style="width:'.$w.'%;background:'.$color.'"></i></div><strong>'.cpm_num($b['ending']).'</strong></div>';}
  return $html.'</div>';
}
function cpm_print_page($buckets,$filters,$warnings){
  $info=function_exists('info_pt')?info_pt():null;$company=$info&&isset($info->nama_pt)?$info->nama_pt:shortTittle;$assetBase=rtrim(base_url(),'/').'/assets/';$body=cpm_print_chart($buckets).cpm_html($buckets,$filters,$warnings);
  while(ob_get_level()>$GLOBALS['initialOutputBufferLevel'])ob_end_clean();header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Proyeksi Kas per Bulan</title><link rel="stylesheet" href="'.cpm_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.cpm-table{width:100%;border-collapse:collapse!important}.cpm-table th,.cpm-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cpm-neg td{background:#fee2e2!important}.print-chart{margin:10px 0 16px}.pc-row{display:flex;align-items:center;gap:8px;margin:4px 0}.pc-row span{width:70px}.pc-row div{flex:1;background:#e5e7eb;height:12px}.pc-row i{display:block;height:12px}.pc-row strong{width:120px;text-align:right}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.cpm-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cpm_h($company).'</h3><h4 style="margin:0 0 14px">Proyeksi Kas per Bulan</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';exit;
}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=cpm_filters();list($buckets,$warnings,$summary)=cpm_data($db,$filters);
  if($act==='filter') cpm_json('success','OK',array('html'=>cpm_html($buckets,$filters,$warnings),'warning_html'=>cpm_warning_html($warnings),'chart'=>cpm_chart($buckets),'summary'=>$summary));
  if($act==='print') cpm_print_page($buckets,$filters,$warnings);
  if($act==='excel'){
    ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Proyeksi Kas Bulan'));$headers=array('Bulan','Opening Cash','Inflow','Outflow','Net Movement','Ending Cash','Detail');foreach($headers as $i=>$h)$sheet->setCellValueByColumnAndRow($i,4,$h);$r=5;
    foreach($buckets as $b){$details=array();foreach($b['events'] as $e)$details[]=$e['source'].' '.$e['doc_no'].' '.($e['inflow']>0?'+'.cpm_num($e['inflow']):'-'.cpm_num($e['outflow']));$vals=array($b['label'],$b['opening'],$b['inflow'],$b['outflow'],$b['net'],$b['ending'],implode("\n",$details));foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
    $sheet->setCellValue('A'.$r,'Asumsi');$sheet->setCellValue('B'.$r,strip_tags(cpm_assumption_html($filters)));$sheet->mergeCells('B'.$r.':G'.$r);
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PROYEKSI KAS PER BULAN'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r),'column_count'=>7,'money_columns'=>array('B','C','D','E','F'),'filters'=>array('Start Month'=>$filters['start_month'],'Jumlah Bulan'=>$filters['number_of_months'])));
    $tmp=erpkb_excel_temp_file('proyeksi_kas_per_bulan_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);throw new Exception('File Excel gagal dibuat dengan benar.');}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="proyeksi_kas_per_bulan_'.$filters['start_month'].'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  cpm_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();cpm_json('error',$e->getMessage());}
?>
