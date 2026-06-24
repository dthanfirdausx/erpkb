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
function vp_user(){return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';}
function vp_h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function vp_num($v){return number_format((float)$v, 2, '.', ',');}
function vp_amount($v){return round((float)str_replace(',', '', trim((string)$v)), 2);}
function vp_valid_date($d){$dt=DateTime::createFromFormat('Y-m-d',$d);return $dt && $dt->format('Y-m-d')===$d;}
function vp_next_no($db){$p='VP/'.date('Y').'/'.date('m').'/';$r=$db->fetch("SELECT MAX(vendor_payment_no) max_no FROM erp_vendor_payment WHERE vendor_payment_no LIKE ?",array($p.'%'));$n=1;if($r&&$r->max_no){$x=explode('/',$r->max_no);$n=((int)end($x))+1;}return $p.str_pad($n,4,'0',STR_PAD_LEFT);}
function vp_period_open($db,$date){$p=$db->fetch("SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",array($date));if(!$p)return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';if($p->status!=='OPEN')return 'Fiscal period tanggal '.$date.' status '.$p->status.', tidak boleh posting.';return true;}
function vp_account_leaf($db,$account,$label){$r=$db->fetch("SELECT r.no_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE r.no_rek=? AND c.no_rek IS NULL LIMIT 1",array($account));if(!$r)throw new Exception($label.' tidak valid atau bukan akun detail.');}
function vp_paid_amount($db,$invoiceId){$r=$db->fetch("SELECT COALESCE(SUM(amount),0) paid FROM erp_vendor_payment WHERE vendor_invoice_id=? AND status='POSTED'",array($invoiceId));return $r?(float)$r->paid:0;}
function vp_update_invoice_payment_status($db,$invoiceId)
{
    if (!$invoiceId) return;
    $inv=$db->fetch("SELECT gross_amount,status FROM erp_vendor_invoice WHERE id=? LIMIT 1",array($invoiceId));
    if(!$inv || $inv->status!=='POSTED') return;
    $paid=vp_paid_amount($db,$invoiceId);
    $gross=(float)$inv->gross_amount;
    $status=$paid<=0?'OPEN':($paid+0.005>=$gross?'PAID':'PARTIAL');
    $db->update('erp_vendor_invoice',array('payment_status'=>$status,'updated_by'=>vp_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$invoiceId);
}
function vp_post_to_gl($db,$payment)
{
    $period=vp_period_open($db,$payment->posting_date);if($period!==true)throw new Exception($period);
    vp_account_leaf($db,$payment->bank_account,'Bank account');vp_account_leaf($db,$payment->ap_account,'AP account');
    if((float)$payment->amount<=0)throw new Exception('Amount wajib lebih dari nol.');
    if($payment->vendor_invoice_id){
        $inv=$db->fetch("SELECT status,payment_status,gross_amount,vendor_code FROM erp_vendor_invoice WHERE id=? LIMIT 1",array($payment->vendor_invoice_id));
        if(!$inv||$inv->status!=='POSTED')throw new Exception('Vendor invoice harus POSTED sebelum payment diposting.');
        if($inv->vendor_code!==$payment->vendor_code)throw new Exception('Vendor payment tidak sesuai dengan vendor invoice.');
        $open=max(0,(float)$inv->gross_amount-vp_paid_amount($db,$payment->vendor_invoice_id));
        if((float)$payment->amount>$open+0.005)throw new Exception('Amount melebihi outstanding vendor invoice.');
    }
    $db->query("START TRANSACTION");
    try{
        if($payment->journal_header_id){
            $db->delete('jurnal_detail','id_header',$payment->journal_header_id);
            $hid=$payment->journal_header_id;
            $db->update('jurnal_header',array('document_type'=>'KZ','posting_status'=>'POSTED','tgl_jurnal'=>$payment->posting_date,'ket'=>'VENDOR PAYMENT: '.$payment->description,'no_bukti'=>$payment->vendor_payment_no,'source_module'=>'VENDOR_PAYMENT','source_document_no'=>$payment->vendor_payment_no,'username'=>vp_user(),'posted_by'=>vp_user(),'posted_at'=>date('Y-m-d H:i:s'),'updated_by'=>vp_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$hid);
        }else{
            $db->insert('jurnal_header',array('no_jurnal'=>generate_no_jurnal(),'document_type'=>'KZ','posting_status'=>'POSTED','tgl_jurnal'=>$payment->posting_date,'ket'=>'VENDOR PAYMENT: '.$payment->description,'no_bukti'=>$payment->vendor_payment_no,'source_module'=>'VENDOR_PAYMENT','source_document_no'=>$payment->vendor_payment_no,'username'=>vp_user(),'posted_by'=>vp_user(),'posted_at'=>date('Y-m-d H:i:s'),'tgl_insert'=>date('Y-m-d H:i:s')));
            $hid=$db->last_insert_id();
        }
        $lines=array(array($payment->ap_account,(float)$payment->amount,0,'Clear vendor payable'),array($payment->bank_account,0,(float)$payment->amount,'Outgoing bank vendor payment'));
        $ln=1;foreach($lines as $l){$db->insert('jurnal_detail',array('id_header'=>$hid,'line_no'=>$ln++,'no_rek'=>$l[0],'line_text'=>$l[3].' '.$payment->vendor_payment_no,'debet'=>round($l[1],2),'kredit'=>round($l[2],2),'valuta'=>$payment->currency?:'IDR','kurs'=>$payment->kurs?:1));}
        $db->update('erp_vendor_payment',array('status'=>'POSTED','journal_header_id'=>$hid,'posted_by'=>vp_user(),'posted_at'=>date('Y-m-d H:i:s'),'updated_by'=>vp_user(),'updated_at'=>date('Y-m-d H:i:s')),'id',$payment->id);
        vp_update_invoice_payment_status($db,$payment->vendor_invoice_id);
        $db->query("COMMIT");return $hid;
    }catch(Exception $e){$db->query("ROLLBACK");throw $e;}
}
?>
