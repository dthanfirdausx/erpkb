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
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
session_check_json();
include "release_helper.php";
function por_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function por_badge($s){$c=$s==='CREATED'?'default':($s==='RELEASED'?'success':'warning');return '<span class="label label-'.$c.'">'.por_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-d');$plant=isset($_POST['plant'])?trim($_POST['plant']):'';$keyword=isset($_POST['keyword'])?trim($_POST['keyword']):'';$onlyReady=isset($_POST['only_ready'])?trim($_POST['only_ready']):'';
$where=" WHERE p.status='CREATED' AND p.start_date BETWEEN ? AND ? ";$params=array($from,$to);
if($plant!==''){$where.=" AND p.plant=? ";$params[]=$plant;}
if($keyword!==''){$kw='%'.$keyword.'%';$where.=" AND (p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR p.production_version_no LIKE ? OR p.bom_no LIKE ? OR p.routing_no LIKE ?) ";for($i=0;$i<6;$i++)$params[]=$kw;}
$cnt=$db->fetch("SELECT COUNT(*) jml FROM production_order p $where",$params);$total=$cnt?(int)$cnt->jml:0;
$rows=$db->query("SELECT p.*,COALESCE(m.item_count,0)item_count,COALESCE(o.op_count,0)op_count FROM production_order p LEFT JOIN(SELECT id_production_order,COUNT(*) item_count FROM production_order_material GROUP BY id_production_order)m ON m.id_production_order=p.id_production_order LEFT JOIN(SELECT id_production_order,COUNT(*) op_count FROM production_order_operation GROUP BY id_production_order)o ON o.id_production_order=p.id_production_order $where ORDER BY p.start_date ASC,p.priority DESC,p.id_production_order DESC LIMIT $start,$length",$params);
$data=array();$no=$start+1;$filtered=0;
foreach($rows as $r){$ready=por_readiness($r->id_production_order);$errors=count($ready['errors']);$warnings=count($ready['warnings']);if($onlyReady==='Y'&&$errors>0)continue;$filtered++;$score=$ready['score'];$scoreClass=$errors>0?'danger':($warnings>0?'warning':'success');$act='<div class="por-action"><button class="btn btn-info btn-xs btn-por-detail" data-id="'.(int)$r->id_production_order.'" title="Detail"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-por-check" data-id="'.(int)$r->id_production_order.'" title="Readiness"><i class="fa fa-search"></i></button> ';if($errors===0)$act.='<button class="btn btn-success btn-xs btn-por-release" data-id="'.(int)$r->id_production_order.'" data-no="'.por_h($r->no_production_order).'" title="Release"><i class="fa fa-unlock"></i></button>';$act.='</div>';
  $data[]=array($no++,$act,'<strong>'.por_h($r->no_production_order).'</strong><br><small>'.por_h($r->order_strategy.' / '.$r->order_type.' / '.$r->priority).'</small>','<strong>'.por_h($r->material_code).'</strong><br><small>'.por_h($r->material_name).'</small>',number_format((float)$r->order_qty,5,',','.').' '.por_h($r->uom),por_h($r->plant.' / '.$r->storage_location),por_h($r->start_date.' s/d '.$r->finish_date),'<strong>'.por_h($r->production_version_no?:'-').'</strong><br><small>BOM '.por_h($r->bom_no?:'-').' / RT '.por_h($r->routing_no?:'-').'</small>',(int)$r->item_count.' mat / '.(int)$r->op_count.' op','<span class="label label-'.$scoreClass.'">'.$score.'%</span><br><small>'.$errors.' error / '.$warnings.' warning</small>',por_badge($r->status));
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$onlyReady==='Y'?$filtered:$total,'data'=>$data));
?>
