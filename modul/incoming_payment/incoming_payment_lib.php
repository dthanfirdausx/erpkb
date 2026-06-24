<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function ip_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function ip_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ip_num($v){return number_format((float)$v,2,'.',',');}
function ip_amount($v){return round((float)str_replace(',','',trim((string)$v)),2);}
function ip_valid_date($d){$dt=DateTime::createFromFormat('Y-m-d',$d);return $dt&&$dt->format('Y-m-d')===$d;}
function ip_next_no($db){$p='IP/'.date('Y').'/'.date('m').'/';$r=$db->fetch("SELECT MAX(incoming_payment_no) max_no FROM erp_incoming_payment WHERE incoming_payment_no LIKE ?",array($p.'%'));$n=1;if($r&&$r->max_no){$x=explode('/',$r->max_no);$n=((int)end($x))+1;}return $p.str_pad($n,4,'0',STR_PAD_LEFT);}
function ip_period_open($db,$date){$p=$db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",array($date));if(!$p)return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';if($p->status!=='OPEN')return 'Fiscal period tanggal '.$date.' status '.$p->status.', tidak boleh posting.';return true;}
function ip_account_leaf($db,$account,$label){$r=$db->fetch("SELECT r.no_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE r.no_rek=? AND c.no_rek IS NULL LIMIT 1",array($account));if(!$r)throw new Exception($label.' tidak valid atau bukan akun detail.');}
function ip_paid_amount($db,$invoiceId){$r=$db->fetch("SELECT COALESCE(SUM(amount),0) paid FROM erp_incoming_payment WHERE sales_invoice_id=? AND status='POSTED'",array($invoiceId));return $r?(float)$r->paid:0;}
function ip_post_to_gl($db,$pmt){
  $period=ip_period_open($db,$pmt->posting_date);if($period!==true)throw new Exception($period);
  ip_account_leaf($db,$pmt->bank_account,'Bank account');ip_account_leaf($db,$pmt->ar_account,'AR account');if((float)$pmt->amount<=0)throw new Exception('Amount wajib lebih dari nol.');
  $db->query("START TRANSACTION");
  try{
    if($pmt->journal_header_id){$db->delete('jurnal_detail','id_header',$pmt->journal_header_id);$hid=$pmt->journal_header_id;$db->update('jurnal_header',array('document_type'=>'DZ','posting_status'=>'POSTED','tgl_jurnal'=>$pmt->posting_date,'ket'=>'INCOMING PAYMENT: '.$pmt->description,'no_bukti'=>$pmt->incoming_payment_no,'source_module'=>'INCOMING_PAYMENT','source_document_no'=>$pmt->incoming_payment_no,'username'=>ip_user(),'posted_by'=>ip_user(),'posted_at'=>date('Y-m-d H:i:s'),'updated_by'=>ip_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$hid);}
    else{$db->insert('jurnal_header',array('no_jurnal'=>generate_no_jurnal(),'document_type'=>'DZ','posting_status'=>'POSTED','tgl_jurnal'=>$pmt->posting_date,'ket'=>'INCOMING PAYMENT: '.$pmt->description,'no_bukti'=>$pmt->incoming_payment_no,'source_module'=>'INCOMING_PAYMENT','source_document_no'=>$pmt->incoming_payment_no,'username'=>ip_user(),'posted_by'=>ip_user(),'posted_at'=>date('Y-m-d H:i:s'),'tgl_insert'=>date('Y-m-d H:i:s')));$hid=$db->last_insert_id();}
    $lines=array(array($pmt->bank_account,$pmt->amount,0,'Incoming payment bank'),array($pmt->ar_account,0,$pmt->amount,'Clear customer receivable'));
    $ln=1;foreach($lines as $l){$db->insert('jurnal_detail',array('id_header'=>$hid,'line_no'=>$ln++,'no_rek'=>$l[0],'line_text'=>$l[3].' '.$pmt->incoming_payment_no,'debet'=>$l[1],'kredit'=>$l[2],'valuta'=>$pmt->currency?:'IDR','kurs'=>$pmt->kurs?:1));}
    $db->update('erp_incoming_payment',array('status'=>'POSTED','journal_header_id'=>$hid,'posted_by'=>ip_user(),'posted_at'=>date('Y-m-d H:i:s'),'updated_by'=>ip_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$pmt->id);
    if($pmt->sales_invoice_id){$inv=$db->fetch("SELECT gross_amount FROM sales_invoice WHERE id_sales=? LIMIT 1",array($pmt->sales_invoice_id));if($inv){$paid=ip_paid_amount($db,$pmt->sales_invoice_id);$status=$paid+0.005>=(float)$inv->gross_amount?'PAID':'PARTIAL';$db->query("UPDATE sales_invoice SET billing_status=CASE WHEN billing_status='DRAFT' THEN 'POSTED' ELSE billing_status END WHERE id_sales=?",array($pmt->sales_invoice_id));}}
    $db->query("COMMIT");return $hid;
  }catch(Exception $e){$db->query("ROLLBACK");throw $e;}
}
?>
