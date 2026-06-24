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
function pick_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pick_input($k,$d=''){if(isset($_POST[$k]))return trim((string)$_POST[$k]);if(isset($_GET[$k]))return trim((string)$_GET[$k]);return $d;}
function pick_date($v,$d){$v=trim((string)$v);return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)?$v:$d;}
function pick_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function pick_next_no($db){$p='PK'.date('Ym');$r=$db->fetch("SELECT picking_no FROM erp_picking WHERE picking_no LIKE ? ORDER BY picking_no DESC LIMIT 1",array($p.'%'));$n=1;if($r&&preg_match('/(\d{5})$/',$r->picking_no,$m))$n=(int)$m[1]+1;return $p.sprintf('%05d',$n);}
function pick_status_label($s){$m=array('CREATED'=>'default','IN_PROCESS'=>'info','PICKED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.pick_h($s?:'CREATED').'</span>';}
function pick_filters(){return array('tgl_awal'=>pick_input('tgl_awal',date('Y-01-01')),'tgl_akhir'=>pick_input('tgl_akhir',date('Y-m-d')),'customer'=>pick_input('customer','all'),'status'=>pick_input('status','all'),'warehouse'=>pick_input('warehouse'),'picker'=>pick_input('picker'),'keyword'=>pick_input('keyword'));}
function pick_filter_sql($in,&$p){$w=" WHERE 1=1 ";$from=pick_date(isset($in['tgl_awal'])?$in['tgl_awal']:'',date('Y-01-01'));$to=pick_date(isset($in['tgl_akhir'])?$in['tgl_akhir']:'',date('Y-m-d'));$w.=" AND pk.picking_date BETWEEN ? AND ? ";$p[]=$from;$p[]=$to;if(!empty($in['customer'])&&$in['customer']!=='all'){$w.=" AND pk.customer_code=? ";$p[]=$in['customer'];}if(!empty($in['status'])&&$in['status']!=='all'){$w.=" AND pk.status=? ";$p[]=$in['status'];}if(!empty($in['warehouse'])){$w.=" AND pk.warehouse=? ";$p[]=$in['warehouse'];}if(!empty($in['picker'])){$w.=" AND pk.picker=? ";$p[]=$in['picker'];}if(!empty($in['keyword'])){$kw='%'.$in['keyword'].'%';$w.=" AND (pk.picking_no LIKE ? OR pk.delivery_no LIKE ? OR pk.no_sales_order LIKE ? OR pk.customer_name LIKE ? OR pk.picker LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}return $w;}
function pick_base_sql(){return " FROM erp_picking pk LEFT JOIN (SELECT picking_id,COUNT(*) item_count,COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(picked_qty),0) picked_qty FROM erp_picking_detail GROUP BY picking_id) d ON d.picking_id=pk.id ";}
function pick_select_sql(){return "SELECT pk.*,COALESCE(d.item_count,0) item_count,COALESCE(d.delivery_qty,0) delivery_qty,COALESCE(d.picked_qty,0) picked_qty,CASE WHEN COALESCE(d.delivery_qty,0)>0 THEN ROUND((COALESCE(d.picked_qty,0)/d.delivery_qty)*100,2) ELSE 0 END picked_percent ";}
function pick_load($db,$in,$limit=0,$offset=0){$p=array();$w=pick_filter_sql($in,$p);$sql=pick_select_sql().pick_base_sql().$w." ORDER BY pk.picking_date DESC,pk.id DESC";if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;return $db->query($sql,$p);}
function pick_count($db,$in){$p=array();$w=pick_filter_sql($in,$p);$r=$db->fetch("SELECT COUNT(*) total ".pick_base_sql().$w,$p);return $r?(int)$r->total:0;}
function pick_summary($db,$in){$p=array();$w=pick_filter_sql($in,$p);return $db->fetch("SELECT COUNT(*) total_docs,SUM(CASE WHEN pk.status='PICKED' THEN 1 ELSE 0 END) picked_docs,SUM(CASE WHEN pk.status='CREATED' THEN 1 ELSE 0 END) created_docs,COALESCE(SUM(d.delivery_qty),0) delivery_qty,COALESCE(SUM(d.picked_qty),0) picked_qty ".pick_base_sql().$w,$p);}
?>
