<?php
$initialOutputBufferLevel=ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cak_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');echo json_encode(array_merge(array('status'=>$status,'message'=>$message),$extra));exit;}
function cak_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function cak_num($v){return number_format((float)$v,2,'.',',');}
function cak_req($k,$d=''){return isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):$d;}
function cak_date_ok($v){return preg_match('/^\d{4}\-\d{2}\-\d{2}$/',(string)$v)&&strtotime($v)!==false;}
function cak_table_exists($db,$table){$r=$db->fetch("SHOW TABLES LIKE ?",array($table));return (bool)$r;}

function cak_ensure_mapping($db){
  $db->query("CREATE TABLE IF NOT EXISTS cash_flow_mapping (
    id INT(11) NOT NULL AUTO_INCREMENT,no_rek VARCHAR(50) NOT NULL,cash_flow_group VARCHAR(30) NOT NULL,cash_flow_type VARCHAR(30) DEFAULT NULL,note VARCHAR(255) DEFAULT NULL,is_active CHAR(1) NOT NULL DEFAULT 'Y',created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT NULL,PRIMARY KEY (id),UNIQUE KEY uq_cash_flow_mapping_account (no_rek),KEY idx_cash_flow_mapping_group (cash_flow_group),KEY idx_cash_flow_mapping_active (is_active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}

function cak_filters(){
  $start=cak_req('start_date',date('Y-m-d'));
  $unit=cak_req('horizon_unit','days');
  $value=(int)cak_req('horizon_value',$unit==='months'?3:90);
  $min=(float)cak_req('minimum_cash','0');
  if(!cak_date_ok($start)) throw new Exception('Start date tidak valid.');
  if(!in_array($unit,array('days','months'))) throw new Exception('Horizon unit tidak valid.');
  if($value<1) throw new Exception('Horizon harus lebih besar dari 0.');
  if($unit==='days'&&$value>365) throw new Exception('Horizon maksimal 365 hari.');
  if($unit==='months'&&$value>24) throw new Exception('Horizon maksimal 24 bulan.');
  $dt=new DateTime($start);
  if($unit==='months'){$dt->modify('+'.($value-1).' month');$dt->modify('last day of this month');}
  else{$dt->modify('+'.($value-1).' day');}
  return array('start_date'=>$start,'horizon_unit'=>$unit,'horizon_value'=>$value,'end_date'=>$dt->format('Y-m-d'),'minimum_cash'=>$min);
}

function cak_build_buckets($filters){
  $b=array();$cur=new DateTime($filters['start_date']);$end=new DateTime($filters['end_date']);
  if($filters['horizon_unit']==='months'){
    while($cur<=$end){$key=$cur->format('Y-m');$b[$key]=array('key'=>$key,'label'=>$cur->format('M Y'),'start'=>$cur->format('Y-m-01'),'end'=>$cur->format('Y-m-t'),'opening'=>0,'inflow'=>0,'outflow'=>0,'ending'=>0,'events'=>array());$cur->modify('first day of next month');}
  }else{
    while($cur<=$end){$key=$cur->format('Y-m-d');$b[$key]=array('key'=>$key,'label'=>$cur->format('d M'),'start'=>$key,'end'=>$key,'opening'=>0,'inflow'=>0,'outflow'=>0,'ending'=>0,'events'=>array());$cur->modify('+1 day');}
  }
  return $b;
}
function cak_bucket_key($date,$filters){
  if(!cak_date_ok($date)||strtotime($date)<strtotime($filters['start_date'])) $date=$filters['start_date'];
  if(strtotime($date)>strtotime($filters['end_date'])) return null;
  return $filters['horizon_unit']==='months'?date('Y-m',strtotime($date)):$date;
}
function cak_add_event(&$buckets,$filters,$date,$source,$docNo,$partner,$inflow,$outflow,$note=''){
  $key=cak_bucket_key($date,$filters); if($key===null||!isset($buckets[$key])) return;
  $inflow=(float)$inflow;$outflow=(float)$outflow; if(abs($inflow)<0.005&&abs($outflow)<0.005)return;
  $buckets[$key]['inflow']+=$inflow;$buckets[$key]['outflow']+=$outflow;
  $buckets[$key]['events'][]=array('date'=>$date,'source'=>$source,'doc_no'=>$docNo,'partner'=>$partner,'inflow'=>$inflow,'outflow'=>$outflow,'note'=>$note);
}

function cak_cash_balance($db,$asOfDate){
  cak_ensure_mapping($db);
  $year=(int)date('Y',strtotime($asOfDate));$yearStart=$year.'-01-01';
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
  $total=0;$mapped=0;$fallback=0;
  foreach($rows as $r){$cat=strtolower((string)$r->kategori);$is=$r->cash_flow_group==='cash_equivalent'||strpos($cat,'kas')!==false||strpos($cat,'bank')!==false;if(!$is)continue;$total+=(float)$r->saldo;if($r->cash_flow_group==='cash_equivalent')$mapped++;else$fallback++;}
  return array($total,$mapped,$fallback);
}

function cak_fetch_events($db,$filters,&$buckets,&$warnings){
  if(cak_table_exists($db,'sales_invoice')){
    $rows=$db->query("SELECT si.id_sales,si.no_sales_invoice doc_no,si.bill_to partner,COALESCE(si.due_date,si.invoice_date,si.posting_date) due_date,si.due_date real_due,COALESCE(si.gross_amount,si.net_amount+si.tax_amount,0)-COALESCE((SELECT SUM(p.amount) FROM erp_incoming_payment p WHERE p.status='POSTED' AND (p.sales_invoice_id=si.id_sales OR p.sales_invoice_no=si.no_sales_invoice)),0) outstanding FROM sales_invoice si WHERE si.billing_status='POSTED' AND COALESCE(si.posting_date,si.invoice_date)<=?",array($filters['end_date']));
    if($rows===false) throw new Exception('Query customer invoice gagal: '.$db->getErrorMessage());
    foreach($rows as $r){if((float)$r->outstanding<=0.005)continue;if(!$r->real_due)$warnings[]='Ada customer invoice tanpa due_date; dibucket ke tanggal invoice/start date.';cak_add_event($buckets,$filters,$r->due_date,'Customer Invoice',$r->doc_no,$r->partner,$r->outstanding,0,$r->real_due?'Outstanding AR':'Fallback due date');}
  }else $warnings[]='Tabel sales_invoice tidak tersedia.';
  if(cak_table_exists($db,'erp_incoming_payment')){
    $rows=$db->query("SELECT incoming_payment_no doc_no,customer_code partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_incoming_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false) foreach($rows as $r)cak_add_event($buckets,$filters,$r->due_date,'Incoming Payment Plan',$r->doc_no,$r->partner,$r->amount,0,'DRAFT planning'); else $warnings[]='Incoming payment planning tidak bisa dibaca: '.$db->getErrorMessage();
  }
  if(cak_table_exists($db,'erp_vendor_invoice')){
    $rows=$db->query("SELECT vi.id,vi.vendor_invoice_no doc_no,vi.vendor_code partner,COALESCE(vi.due_date,vi.document_date,vi.posting_date) due_date,vi.due_date real_due,vi.gross_amount-COALESCE((SELECT SUM(p.amount) FROM erp_vendor_payment p WHERE p.status='POSTED' AND (p.vendor_invoice_id=vi.id OR p.vendor_invoice_no=vi.vendor_invoice_no)),0) outstanding FROM erp_vendor_invoice vi WHERE vi.status='POSTED' AND vi.payment_status IN ('OPEN','PARTIAL') AND vi.posting_date<=?",array($filters['end_date']));
    if($rows===false) throw new Exception('Query vendor invoice gagal: '.$db->getErrorMessage());
    foreach($rows as $r){if((float)$r->outstanding<=0.005)continue;if(!$r->real_due)$warnings[]='Ada vendor invoice tanpa due_date; dibucket ke tanggal dokumen/start date.';cak_add_event($buckets,$filters,$r->due_date,'Vendor Invoice',$r->doc_no,$r->partner,0,$r->outstanding,$r->real_due?'Outstanding AP':'Fallback due date');}
  }else $warnings[]='Tabel erp_vendor_invoice tidak tersedia.';
  if(cak_table_exists($db,'erp_vendor_payment')){
    $rows=$db->query("SELECT vendor_payment_no doc_no,vendor_code partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_vendor_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false) foreach($rows as $r)cak_add_event($buckets,$filters,$r->due_date,'Vendor Payment Plan',$r->doc_no,$r->partner,0,$r->amount,'DRAFT planning'); else $warnings[]='Vendor payment planning tidak bisa dibaca: '.$db->getErrorMessage();
  }
  if(cak_table_exists($db,'erp_bank_receipt')){
    $rows=$db->query("SELECT bank_receipt_no doc_no,payer_name partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_bank_receipt WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false) foreach($rows as $r)cak_add_event($buckets,$filters,$r->due_date,'Bank Receipt Plan',$r->doc_no,$r->partner,$r->amount,0,'DRAFT planning');
  }
  if(cak_table_exists($db,'erp_bank_payment')){
    $rows=$db->query("SELECT bank_payment_no doc_no,payee_name partner,COALESCE(value_date,posting_date,document_date) due_date,amount FROM erp_bank_payment WHERE status='DRAFT' AND COALESCE(value_date,posting_date,document_date) BETWEEN ? AND ?",array($filters['start_date'],$filters['end_date']));
    if($rows!==false) foreach($rows as $r)cak_add_event($buckets,$filters,$r->due_date,'Bank Payment Plan',$r->doc_no,$r->partner,0,$r->amount,'DRAFT planning');
  }
  if(cak_table_exists($db,'purchase_order')) $warnings[]='Purchase order tidak diproyeksikan otomatis karena belum ada due date pembayaran resmi; gunakan vendor invoice/payment untuk proyeksi AP.';
}

function cak_data($db,$filters){
  list($opening,$mappedCash,$fallbackCash)=cak_cash_balance($db,$filters['start_date']);
  $warnings=array(); if($mappedCash<1)$warnings[]='Mapping kas/bank belum lengkap; saldo kas memakai fallback kategori COA Kas/Bank.'; if($fallbackCash>0)$warnings[]='Sebagian akun kas/bank terdeteksi dari kategori COA, bukan cash_flow_mapping.';
  $buckets=cak_build_buckets($filters); cak_fetch_events($db,$filters,$buckets,$warnings);
  $running=$opening;$totalIn=0;$totalOut=0;$minEnd=null;
  foreach($buckets as $k=>$b){$buckets[$k]['opening']=$running;$running+=(float)$b['inflow']-(float)$b['outflow'];$buckets[$k]['ending']=$running;$totalIn+=(float)$b['inflow'];$totalOut+=(float)$b['outflow'];$minEnd=$minEnd===null?$running:min($minEnd,$running);}
  return array($buckets,$warnings,array('opening_cash'=>$opening,'total_inflow'=>$totalIn,'total_outflow'=>$totalOut,'ending_cash'=>$running,'minimum_ending'=>$minEnd===null?$opening:$minEnd));
}

function cak_html($buckets,$filters){
  $html='<div class="table-responsive"><table class="table table-bordered table-condensed cak-table"><thead><tr class="bg-primary"><th>Periode</th><th class="text-right">Opening Cash</th><th class="text-right">Expected Inflow</th><th class="text-right">Expected Outflow</th><th class="text-right">Ending Cash</th><th>Minimum Cash Alert</th><th>Detail</th></tr></thead><tbody>';
  foreach($buckets as $b){$cls=$b['ending']<0?'cak-neg':($b['ending']<$filters['minimum_cash']?'cak-low':'');$details=array();foreach($b['events'] as $e)$details[]=cak_h($e['source'].' '.$e['doc_no'].' '.($e['inflow']>0?'+'.cak_num($e['inflow']):'-'.cak_num($e['outflow'])));$alert=$b['ending']<$filters['minimum_cash']?'Di bawah minimum':'OK';$html.='<tr class="'.$cls.'"><td>'.cak_h($b['label']).'</td><td class="text-right">'.cak_num($b['opening']).'</td><td class="text-right">'.cak_num($b['inflow']).'</td><td class="text-right">'.cak_num($b['outflow']).'</td><td class="text-right">'.cak_num($b['ending']).'</td><td>'.cak_h($alert).'</td><td>'.implode('<br>',$details).'</td></tr>';}
  return $html.'</tbody></table></div>';
}
function cak_warning_html($warnings){return count($warnings)?'<div class="alert alert-warning"><strong>Warning:</strong> '.cak_h(implode(' ',array_unique($warnings))).'</div>':'<div class="alert alert-info"><i class="fa fa-info-circle"></i> Data due date dan payment planning tersedia untuk horizon ini.</div>';}
function cak_chart($buckets){$c=array('labels'=>array(),'ending'=>array(),'inflow'=>array(),'outflow'=>array());foreach($buckets as $b){$c['labels'][]=$b['label'];$c['ending'][]=round($b['ending'],2);$c['inflow'][]=round($b['inflow'],2);$c['outflow'][]=round($b['outflow'],2);}return $c;}

$act=isset($_GET['act'])?$_GET['act']:'';
try{
  $filters=cak_filters(); list($buckets,$warnings,$summary)=cak_data($db,$filters);
  if($act==='filter') cak_json('success','OK',array('html'=>cak_html($buckets,$filters),'warning_html'=>cak_warning_html($warnings),'chart'=>cak_chart($buckets),'summary'=>$summary));
  cak_json('error','Action tidak dikenal.');
}catch(Exception $e){while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();cak_json('error',$e->getMessage());}
?>
