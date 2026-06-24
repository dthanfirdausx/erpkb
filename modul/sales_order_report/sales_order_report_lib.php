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
function sor_h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function sor_num($value,$dec=2){return number_format((float)$value,$dec,',','.');}
function sor_input($key,$default=''){
  if(isset($_POST[$key]))return trim((string)$_POST[$key]);
  if(isset($_GET[$key]))return trim((string)$_GET[$key]);
  return $default;
}
function sor_valid_date($value,$default){
  $value=trim((string)$value);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)?$value:$default;
}
function sor_badge($text,$type='default'){return '<span class="label label-'.$type.'">'.sor_h($text).'</span>';}
function sor_approval_badge($status){
  $map=array('DRAFT'=>'default','SUBMITTED'=>'info','PENDING'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','CANCELLED'=>'warning');
  $status=$status?:'-';return sor_badge($status,isset($map[$status])?$map[$status]:'default');
}
function sor_status_badge($status){
  $map=array('BELUM PRODUKSI'=>'default','PRODUKSI BELUM FULL'=>'warning','PROSES PRODUKSI'=>'primary','DIKIRIM SEBAGIAN'=>'info','SUDAH DIKIRIM'=>'success');
  $status=$status?:'OPEN';return sor_badge($status,isset($map[$status])?$map[$status]:'default');
}
function sor_fulfillment_badge($row){
  if((int)$row->is_overdue===1)return sor_badge('OVERDUE','danger');
  if((float)$row->qty_so>0 && (float)$row->qty_kirim>=(float)$row->qty_so)return sor_badge('COMPLETED','success');
  if((float)$row->qty_kirim>0)return sor_badge('PARTIAL','info');
  if((float)$row->qty_produksi>0)return sor_badge('READY/PROD','primary');
  return sor_badge('OPEN','default');
}
function sor_filters(){
  return array(
    'tgl_awal'=>sor_valid_date(sor_input('tgl_awal',date('Y-m-01')),date('Y-m-01')),
    'tgl_akhir'=>sor_valid_date(sor_input('tgl_akhir',date('Y-m-d')),date('Y-m-d')),
    'customer'=>sor_input('customer'),
    'approval_status'=>sor_input('approval_status'),
    'status_so'=>sor_input('status_so'),
    'sales_person'=>sor_input('sales_person'),
    'currency'=>sor_input('currency'),
    'fulfillment_status'=>sor_input('fulfillment_status'),
    'keyword'=>sor_input('keyword')
  );
}
function sor_base_sql(){
  return " FROM v_sales_status v
    JOIN sales_order so ON so.id_sales_order=v.id_sales_order
    LEFT JOIN customer c ON c.kode_pemasok=v.kode_penerima
    LEFT JOIN (
      SELECT id_sales_order,COUNT(*) item_count,COALESCE(SUM(qty),0) detail_qty,COALESCE(SUM(nilai),0) total_amount
      FROM sales_order_detail GROUP BY id_sales_order
    ) d ON d.id_sales_order=v.id_sales_order
    LEFT JOIN (
      SELECT no_sales_order,COUNT(*) production_order_count,COALESCE(SUM(order_qty),0) production_order_qty,
             COALESCE(SUM(completed_qty),0) completed_qty,
             GROUP_CONCAT(DISTINCT no_production_order ORDER BY no_production_order SEPARATOR ', ') production_orders
      FROM production_order GROUP BY no_sales_order
    ) po ON po.no_sales_order=v.no_sales_order
    LEFT JOIN (
      SELECT no_sales_order,COUNT(DISTINCT id) delivery_count,COALESCE(SUM(total_qty),0) delivery_qty,
             GROUP_CONCAT(DISTINCT no_surat_jalan ORDER BY tgl_surat_jalan SEPARATOR ', ') delivery_docs
      FROM surat_jalan GROUP BY no_sales_order
    ) sj ON sj.no_sales_order=v.no_sales_order
    LEFT JOIN (
      SELECT si.no_sales_order,COUNT(DISTINCT si.id_sales) invoice_count,COALESCE(SUM(sid.qty),0) invoice_qty,COALESCE(SUM(sid.nilai),0) invoice_amount,
             GROUP_CONCAT(DISTINCT COALESCE(si.no_sales_invoice,si.invoice_no) ORDER BY si.invoice_date SEPARATOR ', ') invoice_docs
      FROM sales_invoice si
      LEFT JOIN sales_invoice_detail sid ON sid.id_sales=si.id_sales
      GROUP BY si.no_sales_order
    ) inv ON inv.no_sales_order=v.no_sales_order ";
}
function sor_select_sql(){
  return "SELECT v.*,so.delivery_date,so.approval_status,so.status document_status,so.catatan,so.shipping_address,
    so.term,so.tax,so.delivery_term,so.no_sales_invoice,so.no_do,
    COALESCE(c.nama,v.nama,v.kode_penerima) customer_name,COALESCE(c.alamat,so.shipping_address) customer_address,
    COALESCE(d.item_count,0) item_count,COALESCE(d.total_amount,0) total_amount,
    COALESCE(po.production_order_count,0) production_order_count,COALESCE(po.production_orders,'') production_orders,
    COALESCE(po.production_order_qty,0) production_order_qty,COALESCE(po.completed_qty,v.qty_produksi,0) completed_qty,
    COALESCE(sj.delivery_count,0) delivery_count,COALESCE(sj.delivery_docs,'') delivery_docs,
    COALESCE(inv.invoice_count,0) invoice_count,COALESCE(inv.invoice_qty,0) invoice_qty,COALESCE(inv.invoice_amount,0) invoice_amount,COALESCE(inv.invoice_docs,'') invoice_docs,
    CASE WHEN COALESCE(v.qty_so,0)>0 THEN ROUND((COALESCE(v.qty_produksi,0)/v.qty_so)*100,2) ELSE 0 END production_percent,
    CASE WHEN COALESCE(v.qty_so,0)>0 THEN ROUND((COALESCE(v.qty_kirim,0)/v.qty_so)*100,2) ELSE 0 END delivery_percent,
    CASE WHEN COALESCE(v.qty_so,0)>0 THEN ROUND((COALESCE(inv.invoice_qty,0)/v.qty_so)*100,2) ELSE 0 END billing_percent,
    CASE WHEN so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) THEN 1 ELSE 0 END is_overdue ";
}
function sor_filter_sql($input,&$params){
  $where=" WHERE v.so_date BETWEEN ? AND ? ";
  $params[]=$input['tgl_awal'];$params[]=$input['tgl_akhir'];
  if($input['customer']!==''){$where.=" AND v.kode_penerima=? ";$params[]=$input['customer'];}
  if($input['approval_status']!==''){$where.=" AND so.approval_status=? ";$params[]=$input['approval_status'];}
  if($input['status_so']!==''){$where.=" AND v.status_so=? ";$params[]=$input['status_so'];}
  if($input['sales_person']!==''){$where.=" AND v.sales_id=? ";$params[]=$input['sales_person'];}
  if($input['currency']!==''){$where.=" AND v.currency=? ";$params[]=$input['currency'];}
  if($input['fulfillment_status']==='OPEN')$where.=" AND COALESCE(v.qty_kirim,0)=0 AND COALESCE(v.qty_produksi,0)=0 ";
  if($input['fulfillment_status']==='READY')$where.=" AND COALESCE(v.qty_produksi,0)>0 AND COALESCE(v.qty_kirim,0)=0 ";
  if($input['fulfillment_status']==='PARTIAL')$where.=" AND COALESCE(v.qty_kirim,0)>0 AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) ";
  if($input['fulfillment_status']==='COMPLETED')$where.=" AND COALESCE(v.qty_so,0)>0 AND COALESCE(v.qty_kirim,0)>=COALESCE(v.qty_so,0) ";
  if($input['fulfillment_status']==='OVERDUE')$where.=" AND so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) ";
  if($input['keyword']!==''){
    $kw='%'.$input['keyword'].'%';
    $where.=" AND (v.no_sales_order LIKE ? OR v.no_po LIKE ? OR v.kode_penerima LIKE ? OR v.nama LIKE ? OR c.nama LIKE ? OR so.catatan LIKE ? OR so.shipping_address LIKE ?) ";
    for($i=0;$i<7;$i++)$params[]=$kw;
  }
  return $where;
}
function sor_load_rows($db,$input,$limit=0,$offset=0){
  $params=array();$sql=sor_select_sql().sor_base_sql().sor_filter_sql($input,$params)." ORDER BY is_overdue DESC, so.delivery_date ASC, v.so_date DESC, v.id_sales_order DESC";
  if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$params);
}
function sor_count_rows($db,$input){
  $params=array();$row=$db->fetch("SELECT COUNT(*) total ".sor_base_sql().sor_filter_sql($input,$params),$params);
  return $row?(int)$row->total:0;
}
function sor_summary($db,$input){
  $params=array();
  return $db->fetch("SELECT COUNT(*) total_docs,
    SUM(CASE WHEN so.approval_status='APPROVED' THEN 1 ELSE 0 END) approved_docs,
    SUM(CASE WHEN so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) THEN 1 ELSE 0 END) overdue_docs,
    SUM(CASE WHEN COALESCE(v.qty_so,0)>0 AND COALESCE(v.qty_kirim,0)>=COALESCE(v.qty_so,0) THEN 1 ELSE 0 END) completed_docs,
    COALESCE(SUM(v.qty_so),0) qty_so,COALESCE(SUM(v.qty_produksi),0) qty_produksi,COALESCE(SUM(v.qty_kirim),0) qty_kirim,
    COALESCE(SUM(d.total_amount),0) total_amount,COALESCE(SUM(inv.invoice_amount),0) invoice_amount
    ".sor_base_sql().sor_filter_sql($input,$params),$params);
}
?>
