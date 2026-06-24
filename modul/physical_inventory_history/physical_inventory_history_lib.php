<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function pih_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function pih_input($key,$default=''){ if(isset($_POST[$key])) return trim((string)$_POST[$key]); if(isset($_GET[$key])) return trim((string)$_GET[$key]); return $default; }
function pih_valid_date($date,$default){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$date)) ? trim((string)$date) : $default; }
function pih_stock_type_label($stockType){ $labels=array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked'); return isset($labels[$stockType])?$labels[$stockType]:$stockType; }
function pih_doc_type_label($type){ return $type==='CYCLE_COUNT'?'Cycle Count':'Stock Opname'; }
function pih_status_badge($status){ $status=strtoupper((string)$status); if($status==='POSTED') return '<span class="label label-success">Posted</span>'; if($status==='COUNTED') return '<span class="label label-info">Counted</span>'; if($status==='OPEN') return '<span class="label label-warning">Open</span>'; if($status==='CANCELLED') return '<span class="label label-default">Cancelled</span>'; return '<span class="label label-default">'.pih_h($status).'</span>'; }

function pih_union_sql(){
  return "
    SELECT 'CYCLE_COUNT' doc_type,d.id document_id,d.doc_no,d.count_date,d.status document_status,d.created_by,d.created_at,d.updated_at,
           i.id item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
           p.posting_no,p.material_doc_id,p.movement_type,p.posted_at,p.posted_by,dt.no_ref material_doc_no,dt.posting_date material_posting_date
    FROM cycle_count_document_items i
    JOIN cycle_count_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
    LEFT JOIN physical_inventory_postings p ON p.doc_type='CYCLE_COUNT' AND p.item_id=i.id
    LEFT JOIN detail_transaksi dt ON dt.id_detail=p.material_doc_id
    UNION ALL
    SELECT 'STOCK_OPNAME' doc_type,d.id document_id,d.doc_no,d.opname_date count_date,d.status document_status,d.created_by,d.created_at,d.updated_at,
           i.id item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
           p.posting_no,p.material_doc_id,p.movement_type,p.posted_at,p.posted_by,dt.no_ref material_doc_no,dt.posting_date material_posting_date
    FROM stock_opname_document_items i
    JOIN stock_opname_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
    LEFT JOIN physical_inventory_postings p ON p.doc_type='STOCK_OPNAME' AND p.item_id=i.id
    LEFT JOIN detail_transaksi dt ON dt.id_detail=p.material_doc_id
  ";
}

function pih_filter_where($input,&$params){
  $where=" WHERE 1=1 ";
  $from=pih_valid_date(isset($input['tgl_awal'])?$input['tgl_awal']:'',date('Y-m-01'));
  $to=pih_valid_date(isset($input['tgl_akhir'])?$input['tgl_akhir']:'',date('Y-m-d'));
  $where.=" AND x.count_date BETWEEN ? AND ? "; $params[]=$from; $params[]=$to;
  if(!empty($input['doc_type'])){ $where.=" AND x.doc_type=? "; $params[]=$input['doc_type']; }
  if(!empty($input['doc_no'])){ $where.=" AND x.doc_no LIKE ? "; $params[]='%'.$input['doc_no'].'%'; }
  if(!empty($input['material_code'])){ $where.=" AND x.material_code=? "; $params[]=$input['material_code']; }
  if(!empty($input['plant_id'])){ $where.=" AND x.plant_id=? "; $params[]=(int)$input['plant_id']; }
  if(!empty($input['storage_location_id'])){ $where.=" AND x.storage_location_id=? "; $params[]=(int)$input['storage_location_id']; }
  if(!empty($input['storage_bin_id'])){ $where.=" AND x.storage_bin_id=? "; $params[]=(int)$input['storage_bin_id']; }
  if(!empty($input['stock_type'])){ $where.=" AND x.stock_type=? "; $params[]=$input['stock_type']; }
  if(!empty($input['history_status'])){
    if($input['history_status']==='OPEN') $where.=" AND x.item_status='OPEN' ";
    elseif($input['history_status']==='COUNTED') $where.=" AND x.item_status='COUNTED' AND x.posting_no IS NULL ";
    elseif($input['history_status']==='POSTED') $where.=" AND x.posting_no IS NOT NULL ";
    elseif($input['history_status']==='ZERO') $where.=" AND x.item_status='COUNTED' AND COALESCE(x.difference_qty,0)=0 ";
  }
  if(!empty($input['keyword'])){ $kw='%'.trim($input['keyword']).'%'; $where.=" AND (x.doc_no LIKE ? OR x.material_code LIKE ? OR x.material_name LIKE ? OR x.remarks LIKE ? OR x.posting_no LIKE ? OR x.material_doc_no LIKE ?) "; for($i=0;$i<6;$i++) $params[]=$kw; }
  return $where;
}

function pih_load_rows($db,$input){ $params=array(); $where=pih_filter_where($input,$params); return $db->query("SELECT x.* FROM (".pih_union_sql().") x $where ORDER BY x.count_date DESC,x.doc_no DESC,x.line_no", $params); }
?>
