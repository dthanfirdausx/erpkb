<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
function sqr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function sqr_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function sqr_badge($s){$m=array('OPEN'=>'default','SENT'=>'info','ACCEPTED'=>'success','REJECTED'=>'danger','EXPIRED'=>'warning','CANCELLED'=>'warning');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.sqr_h($s?:'OPEN').'</span>';}
function sqr_conv($so){return ((int)$so>0)?'<span class="label label-success">CONVERTED</span>':'<span class="label label-default">NOT CONVERTED</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-01');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
$p=array($from,$to);$w=" WHERE sq.tgl BETWEEN ? AND ? ";
if(!empty($_POST['customer_id'])){$w.=" AND sq.customer_id=? ";$p[]=(int)$_POST['customer_id'];}
if(!empty($_POST['status'])){$w.=" AND sq.status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['sales_person'])){$w.=" AND sq.sales_id=? ";$p[]=$_POST['sales_person'];}
if(!empty($_POST['currency'])){$w.=" AND sq.currency=? ";$p[]=$_POST['currency'];}
if(!empty($_POST['conversion_status'])){if($_POST['conversion_status']==='CONVERTED')$w.=" AND COALESCE(so.so_count,0)>0 ";if($_POST['conversion_status']==='NOT_CONVERTED')$w.=" AND COALESCE(so.so_count,0)=0 ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (sq.no_sales_quotation LIKE ? OR sq.customer_name LIKE ? OR c.nama LIKE ? OR sq.kode_penerima LIKE ? OR sq.subject LIKE ? OR sq.contact_person LIKE ? OR sq.catatan LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
$joins=" LEFT JOIN customer c ON c.id_customer=sq.customer_id OR c.kode_pemasok=sq.kode_penerima
  LEFT JOIN (SELECT id_quotation,COUNT(*) item_count,COALESCE(SUM(qty),0) total_qty,COALESCE(SUM(nilai),0) total_amount FROM sales_quotation_detail GROUP BY id_quotation) d ON d.id_quotation=sq.id_quotation
  LEFT JOIN (SELECT id_quotation,COUNT(*) so_count,GROUP_CONCAT(no_sales_order ORDER BY so_date SEPARATOR ', ') so_numbers,MIN(so_date) first_so_date FROM sales_order WHERE COALESCE(id_quotation,0)>0 GROUP BY id_quotation) so ON so.id_quotation=sq.id_quotation
  LEFT JOIN (SELECT quotation_id,MAX(id) last_followup_id,MAX(followup_date) last_followup_date,MAX(next_followup_date) next_followup_date FROM sales_quotation_followup GROUP BY quotation_id) fu ON fu.quotation_id=sq.id_quotation ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM sales_quotation sq $joins $w",$p);
$rows=$db->query("SELECT sq.*,COALESCE(NULLIF(sq.customer_name,''),c.nama) customer_display,COALESCE(d.item_count,0)item_count,COALESCE(d.total_qty,0)total_qty,COALESCE(d.total_amount,0)total_amount,COALESCE(so.so_count,0)so_count,so.so_numbers,so.first_so_date,fu.last_followup_date,fu.next_followup_date FROM sales_quotation sq $joins $w ORDER BY sq.tgl DESC,sq.id_quotation DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-sqr-detail" data-id="'.(int)$r->id_quotation.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a class="btn btn-warning btn-xs" href="'.base_index().'sales-quotation/detail/'.(int)$r->id_quotation.'" title="Open Quotation"><i class="fa fa-external-link"></i></a>';
  $doc='<strong>'.sqr_h($r->no_sales_quotation).'</strong><br><small>'.sqr_h($r->tgl).' / '.sqr_h($r->currency?:'-').'</small>';
  $cust='<strong>'.sqr_h($r->customer_display?:$r->kode_penerima).'</strong><br><small>'.sqr_h($r->contact_person?:'-').'</small>';
  $valid='Valid '.sqr_h($r->valid_date?:'-').'<br><small>Req Deliv '.sqr_h($r->requested_delivery_date?:'-').'</small>';
  $amount='<span class="badge bg-aqua">'.(int)$r->item_count.' item</span><br><small>Qty '.sqr_num($r->total_qty,4).' | Amt '.sqr_num($r->total_amount).'</small>';
  $conversion=sqr_conv($r->so_count).'<br><small>'.sqr_h($r->so_numbers?:'-').'</small>';
  $follow='Last '.sqr_h($r->last_followup_date?:'-').'<br><small>Next '.sqr_h($r->next_followup_date?:'-').'</small>';
  $data[]=array($no++,$act,$doc,$cust,sqr_h($r->subject?:$r->catatan),sqr_badge($r->status),$valid,$amount,$conversion,$follow,sqr_h($r->sales_id?:'-'));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
