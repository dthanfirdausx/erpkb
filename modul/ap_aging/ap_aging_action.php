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
$initialOutputBufferLevel=ob_get_level();ob_start();
session_start();
include "../../inc/config.php";
require_once "../../inc/lib/PHPExcel.php";
require_once "../../inc/excel_style_helper.php";
session_check_json();

function ap_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ap_n($v){return number_format((float)$v,2,'.',',');}
function ap_json($s,$m='',$e=array()){header('Content-Type: application/json');echo json_encode(array_merge(array('status'=>$s,'message'=>$m),$e));exit;}
function ap_valid_date($d){$dt=DateTime::createFromFormat('Y-m-d',$d);return $dt&&$dt->format('Y-m-d')===$d;}
function ap_params(){
  $as=$_REQUEST['as_of_date']??date('Y-m-d');
  $vendor=trim($_REQUEST['vendor_code']??'');
  $bucket=trim($_REQUEST['bucket']??'');
  $status=trim($_REQUEST['payment_status']??'');
  if(!ap_valid_date($as))throw new Exception('Tanggal as of tidak valid.');
  return array($as,$vendor,$bucket,$status);
}
function ap_bucket($age){
  $age=(int)$age;
  if($age<=0)return 'CURRENT';
  if($age<=30)return '1-30';
  if($age<=60)return '31-60';
  if($age<=90)return '61-90';
  return '>90';
}
function ap_bucket_label($bucket){
  $labels=array('CURRENT'=>'Current / Not Due','1-30'=>'1-30 Days','31-60'=>'31-60 Days','61-90'=>'61-90 Days','>90'=>'>90 Days');
  return isset($labels[$bucket])?$labels[$bucket]:$bucket;
}
function ap_rows($db,$asOf,$vendor,$bucketFilter='',$paymentStatus=''){
  $where="vi.status='POSTED' AND vi.posting_date<=?";
  $p=array($asOf);
  if($vendor!==''){$where.=" AND vi.vendor_code=?";$p[]=$vendor;}
  if($paymentStatus!==''){$where.=" AND vi.payment_status=?";$p[]=$paymentStatus;}
  $rows=$db->query(
    "SELECT vi.*,v.nama vendor_name,aa.nama_rek ap_account_name,jh.no_jurnal,
            COALESCE((SELECT SUM(vp.amount) FROM erp_vendor_payment vp WHERE vp.vendor_invoice_id=vi.id AND vp.status='POSTED' AND vp.posting_date<=?),0) paid_amount,
            DATEDIFF(?,COALESCE(vi.due_date,vi.document_date,vi.posting_date)) age_days
     FROM erp_vendor_invoice vi
     LEFT JOIN pemasok v ON v.kode_pemasok=vi.vendor_code
     LEFT JOIN rekening aa ON aa.no_rek=vi.ap_account
     LEFT JOIN jurnal_header jh ON jh.id=vi.journal_header_id
     WHERE $where
     ORDER BY COALESCE(v.nama,vi.vendor_code),COALESCE(vi.due_date,vi.document_date,vi.posting_date),vi.vendor_invoice_no",
    array_merge(array($asOf,$asOf),$p)
  );
  $out=array();
  foreach($rows as $r){
    $open=round((float)$r->gross_amount-(float)$r->paid_amount,2);
    if($open<=0)continue;
    $b=ap_bucket($r->age_days);
    if($bucketFilter!==''&&$bucketFilter!==$b)continue;
    $r->open_amount=$open;
    $r->aging_bucket=$b;
    $out[]=$r;
  }
  return $out;
}

$act=$_GET['act']??'';
try{
  if($act==='filter'){
    list($as,$vendor,$bucketFilter,$paymentStatus)=ap_params();
    $rows=ap_rows($db,$as,$vendor,$bucketFilter,$paymentStatus);
    $bucket=array('CURRENT'=>0,'1-30'=>0,'31-60'=>0,'61-90'=>0,'>90'=>0,'TOTAL'=>0,'OVERDUE'=>0);
    $html='';$n=0;
    foreach($rows as $r){
      $n++;$bucket[$r->aging_bucket]+=(float)$r->open_amount;$bucket['TOTAL']+=(float)$r->open_amount;if((int)$r->age_days>0)$bucket['OVERDUE']+=(float)$r->open_amount;
      $statusClass=$r->payment_status==='OPEN'?'warning':($r->payment_status==='PARTIAL'?'info':'success');
      $dueClass=(int)$r->age_days>0?'text-red':'text-green';
      $html.='<tr>
        <td>'.$n.'</td>
        <td><strong>'.ap_h($r->vendor_code).'</strong><br><small>'.ap_h($r->vendor_name).'</small></td>
        <td><strong>'.ap_h($r->vendor_invoice_no).'</strong><br><small>Ref: '.ap_h($r->vendor_reference_no).'</small></td>
        <td>'.ap_h($r->posting_date).'</td>
        <td>'.ap_h($r->due_date).'</td>
        <td><span class="label label-'.$statusClass.'">'.ap_h($r->payment_status).'</span></td>
        <td class="text-right">'.ap_n($r->gross_amount).'</td>
        <td class="text-right">'.ap_n($r->paid_amount).'</td>
        <td class="text-right"><strong>'.ap_n($r->open_amount).'</strong><br><small>'.ap_h($r->currency).'</small></td>
        <td class="text-right '.$dueClass.'">'.(int)$r->age_days.'</td>
        <td>'.ap_h(ap_bucket_label($r->aging_bucket)).'</td>
        <td><button class="btn btn-info btn-xs ap-detail" data-id="'.$r->id.'"><i class="fa fa-search"></i></button></td>
      </tr>';
    }
    if($n===0)$html='<tr><td colspan="12" class="text-center text-muted">Tidak ada AP outstanding.</td></tr>';
    ap_json('success','OK',array(
      'html'=>$html,
      'total'=>ap_n($bucket['TOTAL']),
      'current'=>ap_n($bucket['CURRENT']),
      'overdue'=>ap_n($bucket['OVERDUE']),
      'd1'=>ap_n($bucket['1-30']),
      'd31'=>ap_n($bucket['31-60']),
      'd61'=>ap_n($bucket['61-90']),
      'd91'=>ap_n($bucket['>90']),
      'count'=>$n
    ));
  }
  if($act==='detail'){
    $id=(int)($_GET['id']??0);
    $r=$db->fetch("SELECT vi.*,v.nama vendor_name,v.alamat vendor_address,ea.nama_rek expense_account_name,aa.nama_rek ap_account_name,ta.nama_rek tax_account_name,jh.no_jurnal FROM erp_vendor_invoice vi LEFT JOIN pemasok v ON v.kode_pemasok=vi.vendor_code LEFT JOIN rekening ea ON ea.no_rek=vi.expense_account LEFT JOIN rekening aa ON aa.no_rek=vi.ap_account LEFT JOIN rekening ta ON ta.no_rek=vi.tax_account LEFT JOIN jurnal_header jh ON jh.id=vi.journal_header_id WHERE vi.id=? LIMIT 1",array($id));
    if(!$r)throw new Exception('Vendor invoice tidak ditemukan.');
    $payments=$db->query("SELECT vp.*,jh.no_jurnal FROM erp_vendor_payment vp LEFT JOIN jurnal_header jh ON jh.id=vp.journal_header_id WHERE vp.vendor_invoice_id=? ORDER BY vp.posting_date,vp.id",array($id));
    $paid=0;$payHtml='';
    foreach($payments as $p){if($p->status==='POSTED')$paid+=(float)$p->amount;$payHtml.='<tr><td>'.ap_h($p->posting_date).'</td><td>'.ap_h($p->vendor_payment_no).'</td><td>'.ap_h($p->status).'</td><td>'.ap_h($p->payment_method).'</td><td>'.ap_h($p->bank_reference).'</td><td class="text-right">'.ap_n($p->amount).'</td><td>'.ap_h($p->no_jurnal).'</td></tr>';}
    if($payHtml==='')$payHtml='<tr><td colspan="7" class="text-center text-muted">Belum ada pembayaran.</td></tr>';
    $open=max(0,(float)$r->gross_amount-$paid);
    $html='<div class="row">
      <div class="col-md-3"><b>'.fin_h('finance_vendor_invoice', 'Vendor Invoice').'</b><br>'.ap_h($r->vendor_invoice_no).'</div>
      <div class="col-md-3"><b>Vendor Ref</b><br>'.ap_h($r->vendor_reference_no).'</div>
      <div class="col-md-3"><b>'.fin_h('common_status', 'Status').'</b><br>'.ap_h($r->status.' / '.$r->payment_status).'</div>
      <div class="col-md-3"><b>Journal</b><br>'.ap_h($r->no_jurnal).'</div>
    </div><hr>
    <table class="table table-bordered table-condensed">
      <tr><th>'.fin_h('finance_vendor', 'Vendor').'</th><td>'.ap_h($r->vendor_code.' - '.$r->vendor_name).'</td><th>Posting / Due</th><td>'.ap_h($r->posting_date.' / '.$r->due_date).'</td></tr>
      <tr><th>AP Account</th><td>'.ap_h($r->ap_account.' - '.$r->ap_account_name).'</td><th>Expense</th><td>'.ap_h($r->expense_account.' - '.$r->expense_account_name).'</td></tr>
      <tr><th>Tax Account</th><td>'.ap_h(trim($r->tax_account.' - '.$r->tax_account_name,' -')).'</td><th>PO / GR</th><td>'.ap_h($r->reference_po.' / '.$r->reference_gr).'</td></tr>
      <tr><th>Gross</th><td class="text-right">'.ap_n($r->gross_amount).' '.ap_h($r->currency).'</td><th>Paid / Outstanding</th><td class="text-right">'.ap_n($paid).' / <b>'.ap_n($open).'</b></td></tr>
      <tr><th>'.fin_h('finance_description', 'Description').'</th><td colspan="3">'.ap_h($r->description).'</td></tr>
    </table>
    <h4>Payment History</h4><table class="table table-bordered table-condensed"><thead><tr><th>Posting</th><th>Payment No</th><th>'.fin_h('common_status', 'Status').'</th><th>Method</th><th>Bank Ref</th><th>'.fin_h('finance_amount', 'Amount').'</th><th>Journal</th></tr></thead><tbody>'.$payHtml.'</tbody></table>';
    ap_json('success','OK',array('html'=>$html));
  }
  if($act==='excel'){
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    list($as,$vendor,$bucketFilter,$paymentStatus)=ap_params();
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('AP Aging'));
    $headers=array(erp_export_label("No"),erp_export_label("Vendor Code"),erp_export_label("Vendor Name"),erp_export_label("Invoice No"),erp_export_label("Vendor Ref"),erp_export_label("Posting Date"),erp_export_label("Due Date"),erp_export_label("Payment Status"),erp_export_label("Gross"),erp_export_label("Paid"),erp_export_label("Outstanding"),erp_export_label("Currency"),erp_export_label("Age Days"),erp_export_label("Bucket"),erp_export_label("AP Account"),erp_export_label("Journal"));
    foreach($headers as $i=>$h)$sh->setCellValueByColumnAndRow($i,4,$h);
    $rn=5;$no=1;
    foreach(ap_rows($db,$as,$vendor,$bucketFilter,$paymentStatus) as $r){
      $vals=array($no++,$r->vendor_code,$r->vendor_name,$r->vendor_invoice_no,$r->vendor_reference_no,$r->posting_date,$r->due_date,$r->payment_status,$r->gross_amount,$r->paid_amount,$r->open_amount,$r->currency,(int)$r->age_days,ap_bucket_label($r->aging_bucket),trim($r->ap_account.' - '.$r->ap_account_name,' -'),$r->no_jurnal);
      foreach($vals as $i=>$v)$sh->setCellValueByColumnAndRow($i,$rn,$v);
      $rn++;
    }
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('AP AGING'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($headers),'money_columns'=>array('I','J','K'),'filters'=>array('As Of'=>$as,'Vendor'=>$vendor,'Bucket'=>$bucketFilter,'Payment Status'=>$paymentStatus),'widths'=>array('C'=>28,'D'=>18,'E'=>18,'O'=>28)));
    $tmp=erpkb_excel_temp_file('ap_aging_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment;filename="ap_aging_'.date('YmdHis').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  }
  ap_json('error','Action tidak dikenal.');
}catch(Exception $e){ap_json('error',$e->getMessage());}
