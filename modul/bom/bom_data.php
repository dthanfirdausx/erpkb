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

function bom_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function bom_n($v,$d=5){return number_format((float)$v,$d,',','.');}
function bom_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','INACTIVE'=>'warning','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.bom_h($s).'</span>';}
function bom_filters(){return array('from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),'status'=>isset($_POST['status'])?trim($_POST['status']):'','usage'=>isset($_POST['usage'])?trim($_POST['usage']):'','plant'=>isset($_POST['plant'])?trim($_POST['plant']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function bom_where($f,&$p){$w=" WHERE COALESCE(h.valid_from,DATE(h.tgl_input),CURDATE()) BETWEEN ? AND ? ";$p[]=$f['from'];$p[]=$f['to'];if($f['status']!==''){$w.=" AND COALESCE(h.bom_status,CASE WHEN COALESCE(h.status,1)=1 THEN 'RELEASED' ELSE 'INACTIVE' END)=? ";$p[]=$f['status'];}if($f['usage']!==''){$w.=" AND h.bom_usage=? ";$p[]=$f['usage'];}if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.bom_no LIKE ? OR h.kodebj LIKE ? OR h.nm_barang LIKE ? OR h.revision LIKE ? OR h.change_number LIKE ? OR EXISTS(SELECT 1 FROM bom_detail d WHERE d.id_bom=h.id AND (d.kodebb LIKE ? OR d.nm_barang LIKE ?))) ";for($i=0;$i<7;$i++)$p[]=$kw;}return $w;}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=bom_filters();$p=array();$w=bom_where($f,$p);$c=$db->fetch("SELECT COUNT(*) jml FROM bom h $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT h.*,COALESCE(x.item_count,0)item_count FROM bom h LEFT JOIN(SELECT id_bom,COUNT(*) item_count FROM bom_detail GROUP BY id_bom)x ON x.id_bom=h.id $w ORDER BY COALESCE(h.valid_from,DATE(h.tgl_input),CURDATE()) DESC,h.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){$status=$r->bom_status?:((int)$r->status===1?'RELEASED':'INACTIVE');$bomNo=$r->bom_no?:'BOM'.str_pad($r->id,6,'0',STR_PAD_LEFT);$act='<div class="bom-action"><button class="btn btn-info btn-xs btn-bom-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';
  if($status==='DRAFT'){$act.='<button class="btn btn-primary btn-xs btn-bom-edit" data-id="'.(int)$r->id.'" title="Edit"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-bom-release" data-id="'.(int)$r->id.'" data-no="'.bom_h($bomNo).'" title="Release"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-bom-delete" data-id="'.(int)$r->id.'" data-no="'.bom_h($bomNo).'" title="Delete"><i class="fa fa-trash"></i></button>';}
  elseif($status==='RELEASED'){$act.='<button class="btn btn-warning btn-xs btn-bom-inactive" data-id="'.(int)$r->id.'" data-no="'.bom_h($bomNo).'" title="Inactive"><i class="fa fa-ban"></i></button>';}
  $act.='</div>';
  $data[]=array($no++,$act,'<strong>'.bom_h($bomNo).'</strong><br><small>Rev '.bom_h($r->revision?:'-').' / Change '.bom_h($r->change_number?:'-').'</small>','<strong>'.bom_h($r->kodebj).'</strong><br><small>'.bom_h($r->nm_barang).'</small>',bom_h($r->plant_code?:'All Plant'),bom_h($r->bom_usage?:'PRODUCTION'),bom_h($r->alternative_bom?:'01'),bom_h(($r->valid_from?:'-').' s/d '.($r->valid_to?:'Open')),bom_n($r->base_qty?:$r->jumlah).' '.bom_h($r->base_uom?:$r->satuan),'<span class="badge bg-purple">'.(int)$r->item_count.'</span>',bom_label($status),bom_h($r->revision?:'-'));
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
