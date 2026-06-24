<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function prp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function prp_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function prp_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(100,round(((float)$a/$b)*100,1))):0;}
function prp_filters(&$params,$alias='p'){
  $w=" WHERE 1=1 ";
  if(!empty($_REQUEST['tgl_awal'])&&!empty($_REQUEST['tgl_akhir'])){$w.=" AND $alias.start_date BETWEEN ? AND ? ";$params[]=$_REQUEST['tgl_awal'];$params[]=$_REQUEST['tgl_akhir'];}
  if(!empty($_REQUEST['plant'])){$w.=" AND $alias.plant=? ";$params[]=$_REQUEST['plant'];}
  if(!empty($_REQUEST['status'])){$w.=" AND $alias.status=? ";$params[]=$_REQUEST['status'];}
  if(!empty($_REQUEST['keyword'])){$kw='%'.trim($_REQUEST['keyword']).'%';$w.=" AND ($alias.no_production_order LIKE ? OR $alias.material_code LIKE ? OR $alias.material_name LIKE ? OR $alias.no_sales_order LIKE ? OR $alias.customer_po LIKE ?) ";for($i=0;$i<5;$i++)$params[]=$kw;}
  return $w;
}
function prp_order_sql($where){
  return "SELECT p.no_production_order,p.status,p.order_strategy,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.start_date,p.finish_date,
    COALESCE(mat.required_qty,0) required_qty,COALESCE(mat.issued_qty,0) issued_qty,
    COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) scrap_qty,COALESCE(conf.rework_qty,0) rework_qty,
    COALESCE(gr.gr_qty,0) gr_qty
    FROM production_order p
    LEFT JOIN(SELECT id_production_order,SUM(required_qty) required_qty,SUM(issued_qty) issued_qty FROM production_order_material GROUP BY id_production_order) mat ON mat.id_production_order=p.id_production_order
    LEFT JOIN(SELECT id_production_order,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty,SUM(CASE WHEN status='POSTED' THEN rework_qty ELSE 0 END) rework_qty FROM production_order_confirmation GROUP BY id_production_order) conf ON conf.id_production_order=p.id_production_order
    LEFT JOIN(SELECT h.id_production_order,SUM(CASE WHEN h.status='POSTED' THEN d.qty ELSE 0 END) gr_qty FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id GROUP BY h.id_production_order) gr ON gr.id_production_order=p.id_production_order
    $where";
}
?>
