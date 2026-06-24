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
function bh_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function bh_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function bh_badge($s){$m=array('BILLED'=>'success','TAXED'=>'primary','NO_TAX'=>'default','INCOMPLETE'=>'warning');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.bh_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-01');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
$p=array($from,$to);$w=" WHERE si.invoice_date BETWEEN ? AND ? ";
if(!empty($_POST['customer'])){$w.=" AND si.bill_to=? ";$p[]=$_POST['customer'];}
if(!empty($_POST['currency'])){$w.=" AND si.valuta=? ";$p[]=$_POST['currency'];}
if(!empty($_POST['tax_status'])){if($_POST['tax_status']==='TAXED')$w.=" AND si.tax='1' ";if($_POST['tax_status']==='NO_TAX')$w.=" AND (si.tax='0' OR si.tax IS NULL OR si.tax='') ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (si.no_sales_invoice LIKE ? OR si.invoice_no LIKE ? OR si.no_sales_order LIKE ? OR si.no_do LIKE ? OR si.nopo LIKE ? OR bill.nama LIKE ? OR ship.nama LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
$joins=" LEFT JOIN penerima bill ON bill.kode_penerima=si.bill_to
  LEFT JOIN penerima ship ON ship.kode_penerima=si.ship_to
  LEFT JOIN (SELECT id_sales,COUNT(*) item_count,COALESCE(SUM(qty),0) total_qty,COALESCE(SUM(nilai),0) subtotal FROM sales_invoice_detail GROUP BY id_sales) d ON d.id_sales=si.id_sales ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM sales_invoice si $joins $w",$p);
$rows=$db->query("SELECT si.*,bill.nama bill_name,ship.nama ship_name,COALESCE(d.item_count,0)item_count,COALESCE(d.total_qty,0)total_qty,COALESCE(d.subtotal,0)subtotal FROM sales_invoice si $joins $w ORDER BY si.invoice_date DESC,si.id_sales DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $taxAmt=($r->tax==='1')?(float)$r->subtotal*0.11:0;$grand=(float)$r->subtotal+$taxAmt;$status=((int)$r->item_count<=0)?'INCOMPLETE':(($r->tax==='1')?'TAXED':'NO_TAX');
  $act='<button class="btn btn-info btn-xs btn-bh-detail" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a target="_blank" class="btn btn-success btn-xs" href="'.base_url().'modul/sales_invoice/print.php?id='.(int)$r->id_sales.'" title="'.sd_h('common_print', 'Print').'"><i class="fa fa-print"></i></a>';
  $doc='<strong>'.bh_h($r->no_sales_invoice?:$r->invoice_no).'</strong><br><small>'.bh_h($r->invoice_date).' / '.bh_h($r->valuta?:'-').'</small>';
  $cust='<strong>'.bh_h($r->bill_name?:$r->bill_to).'</strong><br><small>Ship: '.bh_h($r->ship_name?:$r->ship_to).'</small>';
  $ref='SO '.bh_h($r->no_sales_order?:'-').'<br><small>SJ/DO '.bh_h($r->no_do?:'-').' | PO '.bh_h($r->nopo?:'-').'</small>';
  $amount='DPP '.bh_num($r->subtotal).'<br><small>PPN '.bh_num($taxAmt).' | Total '.bh_num($grand).'</small>';
  $items='<span class="badge bg-aqua">'.(int)$r->item_count.' item</span><br><small>Qty '.bh_num($r->total_qty,4).'</small>';
  $data[]=array($no++,$act,$doc,$cust,$ref,bh_h($r->term?:'-'),$items,$amount,bh_badge($status),bh_h($r->ttd?:'-'),bh_h($r->catatan?:'-'));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
