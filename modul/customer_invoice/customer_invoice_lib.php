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
function ci_user(){return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';}
function ci_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ci_num($v){return number_format((float)$v,2,'.',',');}
function ci_amount($v){return round((float)str_replace(',','',trim((string)$v)),2);}
function ci_valid_date($d){$dt=DateTime::createFromFormat('Y-m-d',$d);return $dt && $dt->format('Y-m-d')===$d;}
function ci_next_no($db){$p='CI/'.date('Y').'/'.date('m').'/';$r=$db->fetch("SELECT MAX(no_sales_invoice) max_no FROM sales_invoice WHERE no_sales_invoice LIKE ?",array($p.'%'));$n=1;if($r&&$r->max_no){$x=explode('/',$r->max_no);$n=((int)end($x))+1;}return $p.str_pad($n,4,'0',STR_PAD_LEFT);}
function ci_period_open($db,$date){$p=$db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",array($date));if(!$p)return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';if($p->status!=='OPEN')return 'Fiscal period tanggal '.$date.' status '.$p->status.', tidak boleh posting.';return true;}
function ci_account_leaf($db,$account,$label,$required=true){if(!$required && trim((string)$account)==='')return;$r=$db->fetch("SELECT r.no_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE r.no_rek=? AND c.no_rek IS NULL LIMIT 1",array($account));if(!$r)throw new Exception($label.' tidak valid atau bukan akun detail.');}
function ci_paid_amount($db,$invoiceId){$r=$db->fetch("SELECT COALESCE(SUM(amount),0) paid FROM erp_incoming_payment WHERE sales_invoice_id=? AND status='POSTED'",array($invoiceId));return $r?(float)$r->paid:0;}
function ci_post_to_gl($db,$invoice)
{
    $period=ci_period_open($db,$invoice->posting_date ?: $invoice->invoice_date);
    if($period!==true)throw new Exception($period);
    ci_account_leaf($db,$invoice->ar_account,'AR account');
    ci_account_leaf($db,$invoice->revenue_account,'Revenue account');
    ci_account_leaf($db,$invoice->tax_account,'Tax account',(float)$invoice->tax_amount>0);
    if((float)$invoice->gross_amount<=0)throw new Exception('Gross amount wajib lebih dari nol.');
    $db->query("START TRANSACTION");
    try{
        if($invoice->journal_header_id){
            $db->delete('jurnal_detail','id_header',$invoice->journal_header_id);
            $hid=$invoice->journal_header_id;
            $db->update('jurnal_header',array('document_type'=>'DR','posting_status'=>'POSTED','tgl_jurnal'=>$invoice->posting_date ?: $invoice->invoice_date,'ket'=>'CUSTOMER INVOICE: '.$invoice->catatan,'no_bukti'=>$invoice->no_sales_invoice,'source_module'=>'CUSTOMER_INVOICE','source_document_no'=>$invoice->no_sales_invoice,'username'=>ci_user(),'posted_by'=>ci_user(),'posted_at'=>date('Y-m-d H:i:s'),'updated_by'=>ci_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$hid);
        }else{
            $db->insert('jurnal_header',array('no_jurnal'=>generate_no_jurnal(),'document_type'=>'DR','posting_status'=>'POSTED','tgl_jurnal'=>$invoice->posting_date ?: $invoice->invoice_date,'ket'=>'CUSTOMER INVOICE: '.$invoice->catatan,'no_bukti'=>$invoice->no_sales_invoice,'source_module'=>'CUSTOMER_INVOICE','source_document_no'=>$invoice->no_sales_invoice,'username'=>ci_user(),'posted_by'=>ci_user(),'posted_at'=>date('Y-m-d H:i:s'),'tgl_insert'=>date('Y-m-d H:i:s')));
            $hid=$db->last_insert_id();
        }
        $lines=array(array($invoice->ar_account,(float)$invoice->gross_amount,0,'Customer receivable'));
        $lines[]=array($invoice->revenue_account,0,(float)$invoice->net_amount,'Customer invoice revenue');
        if((float)$invoice->tax_amount>0)$lines[]=array($invoice->tax_account,0,(float)$invoice->tax_amount,'Output VAT');
        $ln=1;foreach($lines as $l){$db->insert('jurnal_detail',array('id_header'=>$hid,'line_no'=>$ln++,'no_rek'=>$l[0],'line_text'=>$l[3].' '.$invoice->no_sales_invoice,'debet'=>round($l[1],2),'kredit'=>round($l[2],2),'valuta'=>$invoice->valuta?:'IDR','kurs'=>1));}
        $db->update('sales_invoice',array('billing_status'=>'POSTED','journal_header_id'=>$hid,'posted_by'=>ci_user(),'posted_at'=>date('Y-m-d H:i:s')),'id_sales',$invoice->id_sales);
        $db->query("COMMIT");return $hid;
    }catch(Exception $e){$db->query("ROLLBACK");throw $e;}
}
?>
