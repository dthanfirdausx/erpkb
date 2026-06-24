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
function dr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function dr_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function dr_input($k,$d=''){if(isset($_POST[$k]))return trim((string)$_POST[$k]);if(isset($_GET[$k]))return trim((string)$_GET[$k]);return $d;}
function dr_date($v,$d){$v=trim((string)$v);return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)?$v:$d;}
function dr_badge($s,$c='default'){return '<span class="label label-'.$c.'">'.dr_h($s).'</span>';}
function dr_status_badge($s){$s=strtolower((string)$s);$m=array('draft'=>'default','dikirim'=>'info','diterima'=>'success','dibatalkan'=>'danger');return dr_badge(strtoupper($s?:'-'),isset($m[$s])?$m[$s]:'default');}
function dr_gi_badge($s){$m=array('POSTED'=>'success','REVERSED'=>'danger','CANCELLED'=>'warning');$s=$s?:'NOT POSTED';return dr_badge($s,isset($m[$s])?$m[$s]:'default');}
function dr_filters(){
  return array(
    'tgl_awal'=>dr_date(dr_input('tgl_awal',date('Y-m-01')),date('Y-m-01')),
    'tgl_akhir'=>dr_date(dr_input('tgl_akhir',date('Y-m-d')),date('Y-m-d')),
    'customer'=>dr_input('customer'),
    'status'=>dr_input('status'),
    'shipping_point'=>dr_input('shipping_point'),
    'bc_type'=>dr_input('bc_type'),
    'gi_status'=>dr_input('gi_status'),
    'keyword'=>dr_input('keyword')
  );
}
function dr_base_sql(){
  return " FROM surat_jalan sj
    LEFT JOIN sales_order so ON so.id_sales_order=sj.id_sales_order OR so.no_sales_order=sj.no_sales_order
    LEFT JOIN customer c ON c.kode_pemasok=COALESCE(NULLIF(sj.ship_to_party,''),NULLIF(sj.kode_penerima,''),so.kode_penerima)
    LEFT JOIN penerima p ON p.kode_penerima=COALESCE(NULLIF(sj.ship_to_party,''),NULLIF(sj.kode_penerima,''),so.kode_penerima)
    LEFT JOIN (
      SELECT surat_jalan_id,COUNT(*) item_count,COALESCE(SUM(qty_kirim),0) qty_kirim,COALESCE(SUM(qty_order),0) qty_order,
             COALESCE(SUM(net_weight),0) net_weight,COALESCE(SUM(gross_weight),0) gross_weight,
             COUNT(DISTINCT NULLIF(bc_document_no,'')) customs_doc_count,
             GROUP_CONCAT(DISTINCT NULLIF(bc_document_type,'') ORDER BY bc_document_type SEPARATOR ', ') bc_types,
             GROUP_CONCAT(DISTINCT NULLIF(bc_document_no,'') ORDER BY bc_document_date SEPARATOR ', ') bc_docs
      FROM surat_jalan_detail GROUP BY surat_jalan_id
    ) d ON d.surat_jalan_id=sj.id
    LEFT JOIN (
      SELECT no_sj,GROUP_CONCAT(DISTINCT no_packing_list ORDER BY date_created SEPARATOR ', ') packing_docs,COUNT(*) packing_count
      FROM packing_list GROUP BY no_sj
    ) pl ON pl.no_sj=sj.no_surat_jalan OR pl.no_sj=sj.packing_list_no
    LEFT JOIN (
      SELECT reference_surat_jalan,MAX(gi_no) gi_no,MAX(posting_date) gi_posting_date,MAX(status) gi_status,
             COALESCE(SUM(total_qty),0) gi_qty,COALESCE(SUM(total_amount),0) gi_amount,
             MAX(outbound_bc_type) outbound_bc_type,MAX(outbound_bc_purpose) outbound_bc_purpose,
             MAX(outbound_no_daftar) outbound_no_daftar,MAX(outbound_tgl_daftar) outbound_tgl_daftar
      FROM erp_goods_issue_delivery GROUP BY reference_surat_jalan
    ) gi ON gi.reference_surat_jalan=sj.no_surat_jalan ";
}
function dr_select_sql(){
  return "SELECT sj.*,so.currency,so.sales_id,so.delivery_date,COALESCE(NULLIF(c.nama,''),NULLIF(p.nama,''),NULLIF(so.consignee,''),NULLIF(sj.nama_penerima,''),sj.kode_penerima) customer_name,
    COALESCE(NULLIF(c.alamat,''),NULLIF(p.alamat,''),NULLIF(sj.alamat_pengiriman,''),so.shipping_address) customer_address,
    COALESCE(d.item_count,0) item_count,COALESCE(d.qty_order,0) qty_order,COALESCE(d.qty_kirim,sj.total_qty,0) shipped_qty,
    COALESCE(d.net_weight,0) net_weight,COALESCE(d.gross_weight,0) gross_weight,COALESCE(d.customs_doc_count,0) customs_doc_count,
    COALESCE(d.bc_types,'') bc_types,COALESCE(d.bc_docs,'') bc_docs,COALESCE(pl.packing_count,0) packing_count,COALESCE(pl.packing_docs,sj.packing_list_no,'') packing_docs,
    gi.gi_no,gi.gi_posting_date,gi.gi_status,COALESCE(gi.gi_qty,0) gi_qty,COALESCE(gi.gi_amount,0) gi_amount,
    COALESCE(gi.outbound_bc_type,d.bc_types,'') outbound_bc_type,gi.outbound_bc_purpose,gi.outbound_no_daftar,gi.outbound_tgl_daftar,
    CASE WHEN COALESCE(d.qty_kirim,sj.total_qty,0)>0 THEN ROUND((COALESCE(gi.gi_qty,0)/COALESCE(d.qty_kirim,sj.total_qty))*100,2) ELSE 0 END gi_percent ";
}
function dr_filter_sql($f,&$p){
  $w=" WHERE COALESCE(sj.posting_date,sj.tgl_surat_jalan,sj.document_date) BETWEEN ? AND ? ";$p[]=$f['tgl_awal'];$p[]=$f['tgl_akhir'];
  if($f['customer']!==''){$w.=" AND COALESCE(sj.ship_to_party,sj.kode_penerima,so.kode_penerima)=? ";$p[]=$f['customer'];}
  if($f['status']!==''){$w.=" AND sj.status=? ";$p[]=$f['status'];}
  if($f['shipping_point']!==''){$w.=" AND sj.shipping_point=? ";$p[]=$f['shipping_point'];}
  if($f['bc_type']!==''){$w.=" AND (d.bc_types LIKE ? OR gi.outbound_bc_type=?) ";$p[]='%'.$f['bc_type'].'%';$p[]=$f['bc_type'];}
  if($f['gi_status']==='NOT_POSTED')$w.=" AND gi.gi_no IS NULL ";
  elseif($f['gi_status']!==''){$w.=" AND gi.gi_status=? ";$p[]=$f['gi_status'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (sj.no_surat_jalan LIKE ? OR sj.no_sales_order LIKE ? OR sj.no_po LIKE ? OR c.nama LIKE ? OR p.nama LIKE ? OR so.consignee LIKE ? OR sj.packing_list_no LIKE ? OR gi.gi_no LIKE ? OR d.bc_docs LIKE ? OR sj.no_kendaraan LIKE ? OR sj.sopir LIKE ?) ";for($i=0;$i<11;$i++)$p[]=$kw;}
  return $w;
}
function dr_load_rows($db,$f,$limit=0,$offset=0){
  $p=array();$sql=dr_select_sql().dr_base_sql().dr_filter_sql($f,$p)." ORDER BY COALESCE(sj.posting_date,sj.tgl_surat_jalan) DESC,sj.id DESC";
  if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$p);
}
function dr_count_rows($db,$f){$p=array();$r=$db->fetch("SELECT COUNT(*) total ".dr_base_sql().dr_filter_sql($f,$p),$p);return $r?(int)$r->total:0;}
function dr_summary($db,$f){$p=array();return $db->fetch("SELECT COUNT(*) total_docs,SUM(CASE WHEN sj.status='dikirim' THEN 1 ELSE 0 END) sent_docs,SUM(CASE WHEN sj.status='diterima' THEN 1 ELSE 0 END) received_docs,SUM(CASE WHEN gi.gi_no IS NOT NULL AND gi.gi_status='POSTED' THEN 1 ELSE 0 END) gi_posted,COALESCE(SUM(d.qty_kirim),0) shipped_qty,COALESCE(SUM(gi.gi_qty),0) gi_qty,COALESCE(SUM(d.customs_doc_count),0) customs_docs ".dr_base_sql().dr_filter_sql($f,$p),$p);}
?>
