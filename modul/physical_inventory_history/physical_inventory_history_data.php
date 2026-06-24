<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if(session_status()===PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "physical_inventory_history_lib.php";
session_check_json();
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1; $start=isset($_POST['start'])?max(0,(int)$_POST['start']):0; $length=isset($_POST['length'])?(int)$_POST['length']:25; if($length<=0||$length>500)$length=25;
$input=array('tgl_awal'=>pih_input('tgl_awal',date('Y-m-01')),'tgl_akhir'=>pih_input('tgl_akhir',date('Y-m-d')),'doc_type'=>pih_input('doc_type'),'doc_no'=>pih_input('doc_no'),'material_code'=>pih_input('material_code'),'plant_id'=>pih_input('plant_id'),'storage_location_id'=>pih_input('storage_location_id'),'storage_bin_id'=>pih_input('storage_bin_id'),'stock_type'=>pih_input('stock_type'),'history_status'=>pih_input('history_status'),'keyword'=>pih_input('keyword'));
$rows=iterator_to_array(pih_load_rows($db,$input)); $pageRows=array_slice($rows,$start,$length); $data=array(); $no=$start+1;
foreach($pageRows as $row){
  $diff=$row->difference_qty===null?null:(float)$row->difference_qty; $location=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /');
  $status=$row->posting_no?'POSTED':$row->item_status;
  $detailAttrs=' data-doc="'.pih_h($row->doc_no).'" data-doc-type="'.pih_h(pih_doc_type_label($row->doc_type)).'" data-count-date="'.pih_h($row->count_date).'" data-status="'.pih_h($status).'" data-material="'.pih_h($row->material_code.' - '.$row->material_name).'" data-location="'.pih_h($location?:'-').'" data-stock-type="'.pih_h(pih_stock_type_label($row->stock_type)).'" data-system-qty="'.pih_h(number_format((float)$row->system_qty,5,',','.')).'" data-counted-qty="'.pih_h($row->counted_qty===null?'-':number_format((float)$row->counted_qty,5,',','.')).'" data-difference="'.pih_h($diff===null?'-':number_format($diff,5,',','.')).'" data-posting="'.pih_h($row->posting_no?:'-').'" data-material-doc="'.pih_h($row->material_doc_no?:'-').'"';
  $data[]=array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-pih-detail" '.$detailAttrs.' title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button>',
    '<strong>'.pih_h($row->doc_no).'</strong><br><small>'.pih_h(pih_doc_type_label($row->doc_type)).' | '.pih_h($row->count_date).'</small>',
    pih_status_badge($status).'<br><small>Doc: '.pih_h($row->document_status).'</small>',
    '<strong>'.pih_h($row->material_code).'</strong><br><small class="text-muted">'.pih_h($row->material_name).'</small>',
    pih_h($location?:'-').'<br><small>'.pih_h(pih_stock_type_label($row->stock_type)).'</small>',
    number_format((float)$row->system_qty,5,',','.'),
    $row->counted_qty===null?'-':number_format((float)$row->counted_qty,5,',','.'),
    $diff===null?'-':'<strong class="'.($diff<0?'text-danger':($diff>0?'text-success':'')).'">'.number_format($diff,5,',','.').'</strong>',
    pih_h($row->uom),
    pih_h($row->counted_by?:'-').'<br><small>'.pih_h($row->counted_at?:'').'</small>',
    pih_h($row->posting_no?:'-').'<br><small>'.pih_h($row->movement_type?:'').' '.pih_h($row->posted_at?:'').'</small>',
    pih_h($row->material_doc_no?:'-')
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
