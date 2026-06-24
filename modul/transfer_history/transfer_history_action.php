<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function th_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function th_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}
function th_row($label, $value) {
  return '<tr><th>'.th_h($label).'</th><td>'.($value === '' || $value === null ? '-' : th_h($value)).'</td></tr>';
}
function th_map($type) {
  $maps = array(
    'SLT' => array('label'=>'Storage Location Transfer','header'=>'erp_storage_location_transfer','detail'=>'erp_storage_location_transfer_detail','trace'=>'erp_storage_location_transfer_trace','stock_cols'=>'stock_type'),
    'SBT' => array('label'=>'Storage Bin Transfer','header'=>'erp_storage_bin_transfer','detail'=>'erp_storage_bin_transfer_detail','trace'=>'erp_storage_bin_transfer_trace','stock_cols'=>'stock_type'),
    'STT' => array('label'=>'Stock Type Transfer','header'=>'erp_stock_type_transfer','detail'=>'erp_stock_type_transfer_detail','trace'=>'erp_stock_type_transfer_trace','stock_cols'=>'source_destination')
  );
  return isset($maps[$type]) ? $maps[$type] : null;
}
function th_union_sql() {
  return "
    SELECT 'SLT' AS transfer_type,'Storage Location Transfer' AS transfer_type_label,h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,NULL AS source_stock_type,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,NULL AS destination_stock_type,
           sp.plant_code AS source_plant_code,dp.plant_code AS destination_plant_code,
           src.storage_code AS source_storage_code,src.storage_name AS source_storage_name,dst.storage_code AS destination_storage_code,dst.storage_name AS destination_storage_name,
           sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,h.reversal_reason,
           COALESCE(ds.item_count,0) AS item_count,COALESCE(ds.total_qty,0) AS total_qty,COALESCE(ds.total_amount,0) AS total_amount,COALESCE(ts.trace_count,0) AS trace_count,
           COALESCE(kw.keyword_text,'') AS keyword_text
    FROM erp_storage_location_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_storage_location_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_storage_location_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab) SEPARATOR ' ') keyword_text FROM erp_storage_location_transfer_detail d LEFT JOIN erp_storage_location_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
    UNION ALL
    SELECT 'SBT','Storage Bin Transfer',h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,NULL,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,NULL,
           sp.plant_code,dp.plant_code,src.storage_code,src.storage_name,dst.storage_code,dst.storage_name,sb.bin_code,dbin.bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,h.reversal_reason,
           COALESCE(ds.item_count,0),COALESCE(ds.total_qty,0),COALESCE(ds.total_amount,0),COALESCE(ts.trace_count,0),COALESCE(kw.keyword_text,'')
    FROM erp_storage_bin_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_storage_bin_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_storage_bin_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab) SEPARATOR ' ') keyword_text FROM erp_storage_bin_transfer_detail d LEFT JOIN erp_storage_bin_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
    UNION ALL
    SELECT 'STT','Stock Type Transfer',h.id,h.transfer_no,h.document_date,h.posting_date,h.movement_type,
           h.source_plant_id,h.source_storage_location_id,h.source_storage_bin_id,h.source_stock_type,
           h.destination_plant_id,h.destination_storage_location_id,h.destination_storage_bin_id,h.destination_stock_type,
           sp.plant_code,dp.plant_code,src.storage_code,src.storage_name,dst.storage_code,dst.storage_name,sb.bin_code,dbin.bin_code,
           h.reference_no,h.reason_code,h.reason_text,h.status,h.created_by,h.created_at,h.reversed_by,h.reversed_at,h.reversal_reason,
           COALESCE(ds.item_count,0),COALESCE(ds.total_qty,0),COALESCE(ds.total_amount,0),COALESCE(ts.trace_count,0),COALESCE(kw.keyword_text,'')
    FROM erp_stock_type_transfer h
    LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
    LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
    LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
    LEFT JOIN (SELECT transfer_id,COUNT(*) item_count,SUM(qty) total_qty,SUM(amount) total_amount FROM erp_stock_type_transfer_detail GROUP BY transfer_id) ds ON ds.transfer_id=h.id
    LEFT JOIN (SELECT transfer_id,COUNT(*) trace_count FROM erp_stock_type_transfer_trace GROUP BY transfer_id) ts ON ts.transfer_id=h.id
    LEFT JOIN (SELECT d.transfer_id,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',d.material_code,d.material_name,t.no_bpb,t.no_aju,t.no_dokpab,t.jenis_dokpab,t.source_stock_type,t.destination_stock_type) SEPARATOR ' ') keyword_text FROM erp_stock_type_transfer_detail d LEFT JOIN erp_stock_type_transfer_trace t ON t.transfer_detail_id=d.id GROUP BY d.transfer_id) kw ON kw.transfer_id=h.id
  ";
}
function th_filter(&$params, $source) {
  $where = " WHERE 1=1 ";
  $from = isset($source['tgl_awal']) ? $source['tgl_awal'] : date('Y-m-01');
  $to = isset($source['tgl_akhir']) ? $source['tgl_akhir'] : date('Y-m-d');
  if (th_valid_date($from) && th_valid_date($to)) { $where .= " AND th.posting_date BETWEEN ? AND ? "; $params[]=$from; $params[]=$to; }
  foreach (array('transfer_type','status','movement_type','created_by'=>'user') as $col=>$key) {
    if (is_int($col)) $col = $key;
    if (!empty($source[$key])) { $where .= " AND th.".$col."=? "; $params[]=$source[$key]; }
  }
  if (!empty($source['plant_id'])) { $where .= " AND (th.source_plant_id=? OR th.destination_plant_id=?) "; $params[]=(int)$source['plant_id']; $params[]=(int)$source['plant_id']; }
  if (!empty($source['storage_location_id'])) { $where .= " AND (th.source_storage_location_id=? OR th.destination_storage_location_id=?) "; $params[]=(int)$source['storage_location_id']; $params[]=(int)$source['storage_location_id']; }
  if (!empty($source['storage_bin_id'])) { $where .= " AND (th.source_storage_bin_id=? OR th.destination_storage_bin_id=?) "; $params[]=(int)$source['storage_bin_id']; $params[]=(int)$source['storage_bin_id']; }
  if (!empty($source['stock_type'])) { $where .= " AND (th.source_stock_type=? OR th.destination_stock_type=? OR th.keyword_text LIKE ?) "; $params[]=$source['stock_type']; $params[]=$source['stock_type']; $params[]='%'.$source['stock_type'].'%'; }
  if (!empty($source['keyword'])) {
    $kw='%'.trim($source['keyword']).'%';
    $where .= " AND (th.transfer_no LIKE ? OR th.reference_no LIKE ? OR th.reason_code LIKE ? OR th.reason_text LIKE ? OR th.keyword_text LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[]=$kw;
  }
  return $where;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'detail') {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $type = isset($_POST['type']) ? $_POST['type'] : '';
  $map = th_map($type);
  if (!$map || $id <= 0) { echo '<div class="alert alert-danger">Transfer document tidak valid.</div>'; exit; }
  $h = $db->fetch(
    "SELECT h.*,sp.plant_code AS source_plant_code,dp.plant_code AS destination_plant_code,
            src.storage_code AS source_storage_code,src.storage_name AS source_storage_name,dst.storage_code AS destination_storage_code,dst.storage_name AS destination_storage_name,
            sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code
     FROM ".$map['header']." h
     LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
     LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
     LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
     WHERE h.id=? LIMIT 1",
    array($id)
  );
  if (!$h) { echo '<div class="alert alert-danger">Transfer document tidak ditemukan.</div>'; exit; }
  $items = $db->query("SELECT * FROM ".$map['detail']." WHERE transfer_id=? ORDER BY line_no,id", array($id));
  $history = $db->query("SELECT * FROM ".$map['header']."_history WHERE transfer_id=? ORDER BY changed_at DESC,id DESC", array($id));
  ?>
  <style>.th-detail-head{border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:14px 16px;margin-bottom:14px}.th-detail-head h3{margin:0 0 6px;font-size:20px}.th-detail-table th{width:190px;background:#f8fafc}.th-detail-table td,.th-detail-table th{font-size:12px}</style>
  <div class="th-detail-head">
    <div class="row">
      <div class="col-sm-8"><h3><?=th_h($h->transfer_no);?> <small><?=th_h($map['label']);?> / MvT <?=th_h($h->movement_type);?></small></h3><p><?=th_h($h->reason_code.' - '.$h->reason_text);?></p></div>
      <div class="col-sm-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=th_h($h->status);?></span></div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6"><h4>Header</h4><table class="table table-bordered table-condensed th-detail-table">
      <?=th_row('Document Date',$h->document_date);?><?=th_row('Posting Date',$h->posting_date);?><?=th_row('Reference',$h->reference_no);?><?=th_row('Created By',$h->created_by);?><?=th_row('Created At',$h->created_at);?><?=th_row('Reversal Reason',$h->reversal_reason);?>
    </table></div>
    <div class="col-md-6"><h4>Source & Destination</h4><table class="table table-bordered table-condensed th-detail-table">
      <?=th_row('Source',trim($h->source_plant_code.' / '.$h->source_storage_code.' / '.$h->source_bin_code, ' /'));?><?=th_row('Destination',trim($h->destination_plant_code.' / '.$h->destination_storage_code.' / '.$h->destination_bin_code, ' /'));?>
      <?=th_row('Source Stock Type',isset($h->source_stock_type)?$h->source_stock_type:'');?><?=th_row('Destination Stock Type',isset($h->destination_stock_type)?$h->destination_stock_type:'');?>
    </table></div>
  </div>
  <?php foreach ($items as $item) {
    $stockSelect = $map['stock_cols'] === 'source_destination' ? "t.source_stock_type,t.destination_stock_type" : "t.stock_type AS source_stock_type,t.stock_type AS destination_stock_type";
    $traces = $db->query(
      "SELECT t.*,".$stockSelect.",ssl.storage_code AS source_storage_code,ds.storage_code AS destination_storage_code,sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code
       FROM ".$map['trace']." t
       LEFT JOIN erp_storage_location ssl ON ssl.id=t.source_storage_location_id
       LEFT JOIN erp_storage_location ds ON ds.id=t.destination_storage_location_id
       LEFT JOIN erp_storage_bin sb ON sb.id=t.source_storage_bin_id
       LEFT JOIN erp_storage_bin dbin ON dbin.id=t.destination_storage_bin_id
       WHERE t.transfer_detail_id=? ORDER BY t.id",
      array($item->id)
    );
  ?>
    <h4><?=th_h($item->line_no.'. '.$item->material_code.' - '.$item->material_name);?> <small>Qty <?=number_format((float)$item->qty,5,',','.').' '.th_h($item->uom);?></small></h4>
    <div class="table-responsive"><table class="table table-bordered table-condensed">
      <thead><tr class="bg-gray"><th>Source Layer</th><th>Destination Layer</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>Source</th><th>Destination</th><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><th>No BPB</th><th>No Aju</th><th>Dok Pabean</th></tr></thead><tbody>
      <?php foreach ($traces as $t) { ?><tr><td>#<?=intval($t->source_stock_layer_id);?></td><td>#<?=intval($t->destination_stock_layer_id);?></td><td class="text-right"><?=number_format((float)$t->qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$t->amount,2,',','.');?></td><td><?=th_h(trim($t->source_storage_code.' / '.$t->source_bin_code, ' /'));?></td><td><?=th_h(trim($t->destination_storage_code.' / '.$t->destination_bin_code, ' /'));?></td><td><?=th_h(trim($t->source_stock_type.' -> '.$t->destination_stock_type, ' ->'));?></td><td><?=th_h($t->no_bpb);?></td><td><?=th_h($t->no_aju);?></td><td><?=th_h(trim($t->jenis_dokpab.' '.$t->no_dokpab));?></td></tr><?php } ?>
      </tbody></table></div>
  <?php } ?>
  <h4>History</h4><ul class="list-unstyled"><?php foreach($history as $row){ ?><li><strong><?=th_h(($row->status_lama ?: '-').' -> '.$row->status_baru);?></strong> <span class="text-muted"><?=th_h($row->changed_by.' @ '.$row->changed_at);?></span><br><?=th_h($row->remarks);?></li><?php } ?></ul>
  <?php
  exit;
}

if ($act === 'excel') {
  $excelInitialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $params = array();
  $where = th_filter($params, $_GET);
  $rows = $db->query("SELECT th.* FROM (".th_union_sql().") th ".$where." ORDER BY th.posting_date DESC,th.id DESC", $params);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Transfer History'));
  $sheet->mergeCells('A1:U1'); $sheet->mergeCells('A2:U2');
  $sheet->setCellValue('A1', namaPT); $sheet->setCellValue('A2', 'TRANSFER HISTORY');
  $headers = array(erp_export_label("No"),erp_export_label("Transfer Type"),erp_export_label("Transfer No"),erp_export_label("Movement"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Source Plant"),erp_export_label("Source SLoc"),erp_export_label("Source Bin"),erp_export_label("Source Stock Type"),erp_export_label("Destination Plant"),erp_export_label("Destination SLoc"),erp_export_label("Destination Bin"),erp_export_label("Destination Stock Type"),erp_export_label("Items"),erp_export_label("Total Qty"),erp_export_label("Total Amount"),erp_export_label("Trace"),erp_export_label("Status"),erp_export_label("Created By"),erp_export_label("Reason"));
  foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
  $r=5; $n=1;
  foreach ($rows as $row) {
    $values = array($n++,$row->transfer_type_label,$row->transfer_no,$row->movement_type,$row->posting_date,$row->document_date,$row->source_plant_code,$row->source_storage_code,$row->source_bin_code,$row->source_stock_type,$row->destination_plant_code,$row->destination_storage_code,$row->destination_bin_code,$row->destination_stock_type,(float)$row->item_count,(float)$row->total_qty,(float)$row->total_amount,(float)$row->trace_count,$row->status,$row->created_by,trim($row->reason_code.' - '.$row->reason_text,' -'));
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  $sheet->getStyle('A1:U2')->getFont()->setBold(true);
  $sheet->getStyle('A1:U2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A4:U4')->getFont()->setBold(true);
  $sheet->getStyle('A4:U'.max(4,$r-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('P5:P'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('Q5:Q'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00');
  $from = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
  $to = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
  erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('TRANSFER HISTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>21,'numeric_columns'=>array('P'),'money_columns'=>array('Q'),'filters'=>array('Periode'=>$from.' s/d '.$to)));
  $tmp = tempnam(sys_get_temp_dir(), 'transfer_history_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = filesize($tmp);
  while (ob_get_level() > $excelInitialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="transfer_history_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  readfile($tmp); @unlink($tmp); exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
