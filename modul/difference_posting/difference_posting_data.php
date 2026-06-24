<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if(session_status()===PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "difference_posting_lib.php";
session_check_json();
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1; $start=isset($_POST['start'])?max(0,(int)$_POST['start']):0; $length=isset($_POST['length'])?(int)$_POST['length']:25; if($length<=0||$length>500)$length=25;
$input=array('tgl_awal'=>dp_input('tgl_awal',date('Y-m-01')),'tgl_akhir'=>dp_input('tgl_akhir',date('Y-m-d')),'doc_type'=>dp_input('doc_type'),'doc_no'=>dp_input('doc_no'),'material_code'=>dp_input('material_code'),'plant_id'=>dp_input('plant_id'),'storage_location_id'=>dp_input('storage_location_id'),'storage_bin_id'=>dp_input('storage_bin_id'),'stock_type'=>dp_input('stock_type'),'posting_status'=>dp_input('posting_status'),'variance_type'=>dp_input('variance_type'),'keyword'=>dp_input('keyword'));
$rows=iterator_to_array(dp_load_rows($db,$input)); $pageRows=array_slice($rows,$start,$length); $data=array(); $no=$start+1;
foreach($pageRows as $row){
  $diff=(float)$row->difference_qty; $location=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /'); $ready=$row->item_status==='COUNTED' && abs($diff)>0.000001 && !$row->posting_no;
  $attrs=' data-doc-type="'.dp_h($row->doc_type).'" data-item-id="'.intval($row->item_id).'"';
  $detailAttrs=' data-doc="'.dp_h($row->doc_no).'" data-doc-type="'.dp_h(dp_doc_type_label($row->doc_type)).'" data-count-date="'.dp_h($row->count_date).'" data-status="'.dp_h($row->item_status).'" data-material="'.dp_h($row->material_code.' - '.$row->material_name).'" data-location="'.dp_h($location?:'-').'" data-stock-type="'.dp_h(dp_stock_type_label($row->stock_type)).'" data-system-qty="'.dp_h(number_format((float)$row->system_qty,5,',','.')).'" data-counted-qty="'.dp_h(number_format((float)$row->counted_qty,5,',','.')).'" data-difference="'.dp_h(number_format($diff,5,',','.')).'" data-movement="'.dp_h($diff>=0?'701':'702').'" data-posting="'.dp_h($row->posting_no?:'-').'"';
  $detailButton='<button type="button" class="btn btn-info btn-xs btn-dp-detail" '.$detailAttrs.' title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button>';
  $action=$detailButton.' '.($ready?'<button type="button" class="btn btn-danger btn-xs btn-dp-post" '.$attrs.' title="Post Difference"><i class="fa fa-balance-scale"></i></button>':($row->posting_no?'<span class="label label-success">'.dp_h($row->posting_no).'</span>':'<span class="label label-default">No Posting</span>'));
  $data[]=array(
    $no++,
    $action,
    '<strong>'.dp_h($row->doc_no).'</strong><br><small>'.dp_h(dp_doc_type_label($row->doc_type)).' | '.dp_h($row->count_date).'</small>',
    dp_status_badge($row->posting_no?'POSTED':$row->item_status),
    '<strong>'.dp_h($row->material_code).'</strong><br><small class="text-muted">'.dp_h($row->material_name).'</small>',
    dp_h($location?:'-').'<br><small>'.dp_h(dp_stock_type_label($row->stock_type)).'</small>',
    number_format((float)$row->system_qty,5,',','.'),
    number_format((float)$row->counted_qty,5,',','.'),
    '<strong class="'.($diff<0?'text-danger':($diff>0?'text-success':'')).'">'.number_format($diff,5,',','.').'</strong><br><small>MvT '.($diff>=0?'701':'702').'</small>',
    dp_h($row->uom),
    dp_h($row->posting_no?:'-').'<br><small>'.dp_h($row->posted_at?:'').'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
