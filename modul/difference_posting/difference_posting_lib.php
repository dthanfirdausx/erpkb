<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function dp_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dp_input($key,$default=''){ if(isset($_POST[$key])) return trim((string)$_POST[$key]); if(isset($_GET[$key])) return trim((string)$_GET[$key]); return $default; }
function dp_valid_date($date,$default){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string)$date)) ? trim((string)$date) : $default; }
function dp_stock_type_label($stockType){ $labels=array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked'); return isset($labels[$stockType])?$labels[$stockType]:$stockType; }
function dp_doc_type_label($type){ return $type==='CYCLE_COUNT'?'Cycle Count':'Stock Opname'; }
function dp_status_badge($status){ $status=strtoupper((string)$status); if($status==='POSTED') return '<span class="label label-success">Posted</span>'; if($status==='COUNTED') return '<span class="label label-info">Ready</span>'; if($status==='OPEN') return '<span class="label label-warning">Open</span>'; return '<span class="label label-default">'.dp_h($status).'</span>'; }
function dp_next_no($db){ $prefix='PDI'.date('Ym'); $row=$db->fetch("SELECT posting_no FROM physical_inventory_postings WHERE posting_no LIKE ? ORDER BY posting_no DESC LIMIT 1", array($prefix.'%')); $next=1; if($row && preg_match('/(\d{4})$/',$row->posting_no,$m)) $next=((int)$m[1])+1; return $prefix.sprintf('%04d',$next); }

function dp_union_sql(){
  return "
    SELECT 'CYCLE_COUNT' doc_type,d.id document_id,d.doc_no,d.count_date count_date,d.status document_status,
           i.id item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
           p.posting_no,p.material_doc_id,p.posted_at,p.posted_by
    FROM cycle_count_document_items i
    JOIN cycle_count_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
    LEFT JOIN physical_inventory_postings p ON p.doc_type='CYCLE_COUNT' AND p.item_id=i.id
    UNION ALL
    SELECT 'STOCK_OPNAME' doc_type,d.id document_id,d.doc_no,d.opname_date count_date,d.status document_status,
           i.id item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
           p.posting_no,p.material_doc_id,p.posted_at,p.posted_by
    FROM stock_opname_document_items i
    JOIN stock_opname_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
    LEFT JOIN physical_inventory_postings p ON p.doc_type='STOCK_OPNAME' AND p.item_id=i.id
  ";
}

function dp_filter_where($input,&$params){
  $where=" WHERE 1=1 ";
  $from=dp_valid_date(isset($input['tgl_awal'])?$input['tgl_awal']:'',date('Y-m-01'));
  $to=dp_valid_date(isset($input['tgl_akhir'])?$input['tgl_akhir']:'',date('Y-m-d'));
  $where.=" AND x.count_date BETWEEN ? AND ? "; $params[]=$from; $params[]=$to;
  if(!empty($input['doc_type'])){ $where.=" AND x.doc_type=? "; $params[]=$input['doc_type']; }
  if(!empty($input['doc_no'])){ $where.=" AND x.doc_no LIKE ? "; $params[]='%'.$input['doc_no'].'%'; }
  if(!empty($input['material_code'])){ $where.=" AND x.material_code=? "; $params[]=$input['material_code']; }
  if(!empty($input['plant_id'])){ $where.=" AND x.plant_id=? "; $params[]=(int)$input['plant_id']; }
  if(!empty($input['storage_location_id'])){ $where.=" AND x.storage_location_id=? "; $params[]=(int)$input['storage_location_id']; }
  if(!empty($input['storage_bin_id'])){ $where.=" AND x.storage_bin_id=? "; $params[]=(int)$input['storage_bin_id']; }
  if(!empty($input['stock_type'])){ $where.=" AND x.stock_type=? "; $params[]=$input['stock_type']; }
  if(!empty($input['posting_status'])){
    if($input['posting_status']==='READY') $where.=" AND x.item_status='COUNTED' AND COALESCE(x.difference_qty,0)<>0 AND x.posting_no IS NULL ";
    elseif($input['posting_status']==='POSTED') $where.=" AND x.posting_no IS NOT NULL ";
    elseif($input['posting_status']==='ZERO') $where.=" AND x.item_status='COUNTED' AND COALESCE(x.difference_qty,0)=0 ";
  }
  if(!empty($input['variance_type'])){
    if($input['variance_type']==='POSITIVE') $where.=" AND COALESCE(x.difference_qty,0)>0 ";
    elseif($input['variance_type']==='NEGATIVE') $where.=" AND COALESCE(x.difference_qty,0)<0 ";
    elseif($input['variance_type']==='NONZERO') $where.=" AND COALESCE(x.difference_qty,0)<>0 ";
    elseif($input['variance_type']==='ZERO') $where.=" AND COALESCE(x.difference_qty,0)=0 ";
  }
  if(!empty($input['keyword'])){ $kw='%'.trim($input['keyword']).'%'; $where.=" AND (x.doc_no LIKE ? OR x.material_code LIKE ? OR x.material_name LIKE ? OR x.remarks LIKE ? OR x.posting_no LIKE ?) "; for($i=0;$i<5;$i++) $params[]=$kw; }
  return $where;
}

function dp_load_rows($db,$input){ $params=array(); $where=dp_filter_where($input,$params); return $db->query("SELECT x.* FROM (".dp_union_sql().") x $where ORDER BY x.count_date DESC,x.doc_no DESC,x.line_no", $params); }
function dp_get_item($db,$docType,$itemId){ $params=array('doc_type'=>$docType,'item_id'=>(int)$itemId); $rows=$db->query("SELECT x.* FROM (".dp_union_sql().") x WHERE x.doc_type=? AND x.item_id=? LIMIT 1", $params); foreach($rows as $r) return $r; return null; }
?>
