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
function som_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function som_input($key, $default='') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function som_valid_date($date,$default) {
  $date=trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)?$date:$default;
}
function som_status_label($status) {
  $map=array(
    'BELUM PRODUKSI'=>'default',
    'PRODUKSI BELUM FULL'=>'warning',
    'PROSES PRODUKSI'=>'primary',
    'DIKIRIM SEBAGIAN'=>'info',
    'SUDAH DIKIRIM'=>'success'
  );
  $class=isset($map[$status])?$map[$status]:'default';
  return '<span class="label label-'.$class.'">'.som_h($status ?: 'OPEN').'</span>';
}
function som_approval_label($status) {
  $map=array('DRAFT'=>'default','SUBMITTED'=>'info','PENDING'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','CANCELLED'=>'warning');
  $class=isset($map[$status])?$map[$status]:'default';
  return '<span class="label label-'.$class.'">'.som_h($status ?: '-').'</span>';
}
function som_filters() {
  return array(
    'tgl_awal'=>som_input('tgl_awal',date('Y-01-01')),
    'tgl_akhir'=>som_input('tgl_akhir',date('Y-m-d')),
    'customer'=>som_input('customer','all'),
    'status_so'=>som_input('status_so','all'),
    'approval_status'=>som_input('approval_status','all'),
    'sales_person'=>som_input('sales_person'),
    'delivery_status'=>som_input('delivery_status','all'),
    'overdue_only'=>som_input('overdue_only'),
    'keyword'=>som_input('keyword')
  );
}
function som_filter_sql($input,&$params) {
  $where=" WHERE 1=1 ";
  $from=som_valid_date(isset($input['tgl_awal'])?$input['tgl_awal']:'',date('Y-01-01'));
  $to=som_valid_date(isset($input['tgl_akhir'])?$input['tgl_akhir']:'',date('Y-m-d'));
  $where.=" AND v.so_date BETWEEN ? AND ? ";
  $params[]=$from;$params[]=$to;
  if(!empty($input['customer'])&&$input['customer']!=='all'){ $where.=" AND v.kode_penerima=? ";$params[]=$input['customer'];}
  if(!empty($input['status_so'])&&$input['status_so']!=='all'){ $where.=" AND v.status_so=? ";$params[]=$input['status_so'];}
  if(!empty($input['approval_status'])&&$input['approval_status']!=='all'){ $where.=" AND so.approval_status=? ";$params[]=$input['approval_status'];}
  if(!empty($input['sales_person'])){ $where.=" AND v.sales_id=? ";$params[]=$input['sales_person'];}
  if(!empty($input['delivery_status'])&&$input['delivery_status']!=='all'){
    if($input['delivery_status']==='NOT_DELIVERED') $where.=" AND COALESCE(v.qty_kirim,0)=0 ";
    if($input['delivery_status']==='PARTIAL') $where.=" AND COALESCE(v.qty_kirim,0)>0 AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) ";
    if($input['delivery_status']==='FULL') $where.=" AND COALESCE(v.qty_so,0)>0 AND COALESCE(v.qty_kirim,0)>=COALESCE(v.qty_so,0) ";
  }
  if(!empty($input['overdue_only'])) $where.=" AND so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) ";
  if(!empty($input['keyword'])){
    $kw='%'.$input['keyword'].'%';
    $where.=" AND (v.no_sales_order LIKE ? OR v.no_po LIKE ? OR v.nama LIKE ? OR v.kode_penerima LIKE ? OR v.alasan LIKE ? OR so.shipping_address LIKE ?) ";
    for($i=0;$i<6;$i++)$params[]=$kw;
  }
  return $where;
}
function som_base_sql() {
  return " FROM v_sales_status v
           JOIN sales_order so ON so.id_sales_order=v.id_sales_order
           LEFT JOIN (
             SELECT id_sales_order,COUNT(*) AS item_count,COALESCE(SUM(nilai),0) AS total_amount
             FROM sales_order_detail
             GROUP BY id_sales_order
           ) d ON d.id_sales_order=v.id_sales_order ";
}
function som_select_sql() {
  return "SELECT v.*,so.delivery_date,so.approval_status,so.approved_by,so.approved_at,so.rejection_reason,
                 so.status AS document_status,so.catatan,so.no_sales_invoice,so.no_do,
                 COALESCE(d.item_count,0) AS item_count,COALESCE(d.total_amount,0) AS total_amount,
                 CASE WHEN COALESCE(v.qty_so,0)>0 THEN ROUND((COALESCE(v.qty_produksi,0)/v.qty_so)*100,2) ELSE 0 END AS production_percent,
                 CASE WHEN COALESCE(v.qty_so,0)>0 THEN ROUND((COALESCE(v.qty_kirim,0)/v.qty_so)*100,2) ELSE 0 END AS delivery_percent,
                 CASE WHEN so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) THEN 1 ELSE 0 END AS is_overdue ";
}
function som_load_rows($db,$input,$limit=0,$offset=0) {
  $params=array();$where=som_filter_sql($input,$params);
  $sql=som_select_sql().som_base_sql().$where." ORDER BY is_overdue DESC, so.delivery_date ASC, v.so_date DESC, v.id_sales_order DESC";
  if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$params);
}
function som_count_rows($db,$input) {
  $params=array();$where=som_filter_sql($input,$params);
  $row=$db->fetch("SELECT COUNT(*) AS total ".som_base_sql().$where,$params);
  return $row?(int)$row->total:0;
}
function som_summary($db,$input) {
  $params=array();$where=som_filter_sql($input,$params);
  return $db->fetch("SELECT COUNT(*) AS total_docs,
                            SUM(CASE WHEN COALESCE(v.qty_kirim,0)>=COALESCE(v.qty_so,0) AND COALESCE(v.qty_so,0)>0 THEN 1 ELSE 0 END) AS full_delivered,
                            SUM(CASE WHEN so.delivery_date IS NOT NULL AND so.delivery_date<CURDATE() AND COALESCE(v.qty_kirim,0)<COALESCE(v.qty_so,0) THEN 1 ELSE 0 END) AS overdue_docs,
                            COALESCE(SUM(v.qty_so),0) AS qty_so,
                            COALESCE(SUM(v.qty_kirim),0) AS qty_kirim,
                            COALESCE(SUM(d.total_amount),0) AS total_amount
                     ".som_base_sql().$where,$params);
}
?>
