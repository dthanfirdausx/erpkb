<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "batch_lot_traceability_lib.php";
$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array(); $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'detail') {
  session_check_json();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $layer = blt_layer($db, $id);
  if (!$layer) { echo '<div class="alert alert-warning">Stock layer tidak ditemukan.</div>'; exit; }
  $location = trim((string)$layer->plant_code.' / '.(string)$layer->storage_code.' / '.(string)$layer->bin_code, ' /');
  $bc = trim((string)$layer->jenis_dokpab.' '.(string)$layer->no_dokpab);
  $movements = $db->query(
    "SELECT * FROM detail_transaksi
     WHERE (ref_id=? OR no_bpb=? OR no_ref=? OR ref_pengganti=?)
       AND (kd_barang=? OR destination_material_code=?)
     ORDER BY posting_date,id_detail",
    array($layer->id,$layer->no_bpb,$layer->no_bpb,$layer->no_bpb,$layer->kode,$layer->kode)
  );
  $productionSources = $db->query(
    "SELECT * FROM erp_gr_production_trace WHERE output_stock_layer_id=? ORDER BY raw_material_code,no_aju,no_dokpab,id",
    array($layer->id)
  );
  $usageSql = array(
    "SELECT 'Issue to Production' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_issue_production_trace WHERE stock_layer_id=?",
    "SELECT 'Issue to Cost Center' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_issue_cost_center_trace WHERE stock_layer_id=?",
    "SELECT 'Issue to Asset' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_issue_asset_trace WHERE stock_layer_id=?",
    "SELECT 'Scrap Issue' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_scrap_issue_trace WHERE stock_layer_id=?",
    "SELECT 'Sample Issue' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_sample_issue_trace WHERE stock_layer_id=?",
    "SELECT 'Other Goods Issue' AS usage_type,issue_id AS doc_id,issue_detail_id AS detail_id,qty,no_bpb,no_aju,jenis_dokpab,no_dokpab,lot_no,created_at FROM erp_other_goods_issue_trace WHERE stock_layer_id=?"
  );
  $usage = $db->query(implode(" UNION ALL ", $usageSql)." ORDER BY created_at", array($id,$id,$id,$id,$id,$id));
  ?>
  <style>.blt-detail-table th,.blt-detail-table td{font-size:12px;vertical-align:middle!important}.blt-card{border:1px solid #e5e7eb;border-radius:10px;background:#fff;padding:12px;margin-bottom:10px}.blt-card span{display:block;color:#64748b;font-size:11px;text-transform:uppercase}.blt-card strong{font-size:18px}</style>
  <div class="row">
    <div class="col-sm-3"><div class="blt-card"><span>Layer</span><strong>#<?=intval($layer->id);?></strong></div></div>
    <div class="col-sm-3"><div class="blt-card"><span>Qty Masuk</span><strong><?=number_format((float)$layer->qty_masuk,5,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="blt-card"><span>Qty Used</span><strong><?=number_format((float)$layer->qty_used,5,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="blt-card"><span>Qty Sisa</span><strong><?=number_format((float)$layer->qty_sisa,5,',','.');?></strong></div></div>
  </div>
  <table class="table table-bordered blt-detail-table">
    <tr><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><td><?=blt_h($layer->kode.' - '.$layer->nm_barang);?></td><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><td><?=blt_h($location ?: '-');?></td></tr>
    <tr><th>Receipt Date</th><td><?=blt_h($layer->tgl_masuk);?></td><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><td><?=blt_h(blt_stock_type_label($layer->stock_type));?></td></tr>
    <tr><th>No BPB</th><td><?=blt_h($layer->no_bpb);?></td><th>No Aju</th><td><?=blt_h($layer->no_aju);?></td></tr>
    <tr><th>Dokumen BC</th><td><?=blt_h($bc);?></td><th>Source Ref</th><td><?=blt_h(trim($layer->ref_table.' #'.$layer->ref_id,' #'));?></td></tr>
  </table>
  <h4><i class="fa fa-random"></i> Source / Genealogy</h4>
  <div class="table-responsive"><table class="table table-bordered table-condensed blt-detail-table"><thead><tr class="bg-gray"><th>Raw Material</th><th class="text-right">Qty Trace</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th>Lot</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th>Trace</th></tr></thead><tbody>
  <?php $c=0; foreach($productionSources as $s){$c++; ?><tr><td><strong><?=blt_h($s->raw_material_code);?></strong><br><small><?=blt_h($s->raw_material_name);?></small></td><td class="text-right"><?=number_format((float)$s->qty,5,',','.');?></td><td><?=blt_h($s->uom);?></td><td><?=blt_h($s->lot_no);?></td><td><?=blt_h($s->no_bpb);?></td><td><?=blt_h($s->no_aju);?></td><td><?=blt_h(trim($s->jenis_dokpab.' '.$s->no_dokpab));?></td><td><?=blt_h($s->trace_source);?></td></tr><?php } ?>
  <?php if($c===0){ ?><tr><td colspan="8" class="text-center text-muted">Layer ini berasal dari penerimaan langsung/transfer, bukan output produksi yang memiliki inherited raw trace.</td></tr><?php } ?>
  </tbody></table></div>
  <h4><i class="fa fa-share-alt"></i> Where Used / Consumption</h4>
  <div class="table-responsive"><table class="table table-bordered table-condensed blt-detail-table"><thead><tr class="bg-gray"><th>Usage</th><th>Doc ID</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th>Lot</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th>Created</th></tr></thead><tbody>
  <?php $u=0; foreach($usage as $row){$u++; ?><tr><td><?=blt_h($row->usage_type);?></td><td>#<?=intval($row->doc_id);?> / <?=intval($row->detail_id);?></td><td class="text-right"><?=number_format((float)$row->qty,5,',','.');?></td><td><?=blt_h($row->lot_no);?></td><td><?=blt_h($row->no_bpb);?></td><td><?=blt_h($row->no_aju);?></td><td><?=blt_h(trim($row->jenis_dokpab.' '.$row->no_dokpab));?></td><td><?=blt_h($row->created_at);?></td></tr><?php } ?>
  <?php if($u===0){ ?><tr><td colspan="8" class="text-center text-muted">Belum ada pemakaian tercatat dari layer ini.</td></tr><?php } ?>
  </tbody></table></div>
  <h4><i class="fa fa-file-text-o"></i> Material Document</h4>
  <div class="table-responsive"><table class="table table-bordered table-condensed blt-detail-table"><thead><tr class="bg-gray"><th>Doc</th><th>Posting</th><th><?=wh_h(wh_t('warehouse_movement', 'Movement'));?></th><th><?=wh_h(wh_t('warehouse_direction', 'Direction'));?></th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th>Ref Type</th><th>Remark</th><th><?=wh_h(wh_t('warehouse_user', 'User'));?></th></tr></thead><tbody>
  <?php $m=0; foreach($movements as $mv){$m++; ?><tr><td><?=blt_h($mv->no_ref ?: $mv->no_bpb);?></td><td><?=blt_h($mv->posting_date);?></td><td><?=blt_h($mv->move_code);?></td><td><?=blt_h($mv->direction);?></td><td class="text-right"><?=number_format((float)$mv->qty,5,',','.');?></td><td><?=blt_h($mv->ref_type);?></td><td><?=blt_h($mv->remark ?: $mv->reason);?></td><td><?=blt_h($mv->created_by ?: $mv->user);?></td></tr><?php } ?>
  <?php if($m===0){ ?><tr><td colspan="8" class="text-center text-muted">Material document belum terhubung langsung ke layer ini.</td></tr><?php } ?>
  </tbody></table></div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php';
  $input = array('tgl_awal'=>blt_input('tgl_awal'),'tgl_akhir'=>blt_input('tgl_akhir'),'material_code'=>blt_input('material_code'),'plant_id'=>blt_input('plant_id'),'storage_location_id'=>blt_input('storage_location_id'),'storage_bin_id'=>blt_input('storage_bin_id'),'stock_type'=>blt_input('stock_type'),'jenis_dokpab'=>blt_input('jenis_dokpab'),'open_only'=>blt_input('open_only','Y'),'keyword'=>blt_input('keyword'));
  $rows = blt_load_layers($db,$input);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Batch Lot Trace'));
  $headers = array(erp_export_label("No"),erp_export_label("Layer"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Receipt Date"),erp_export_label("No BPB"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("Qty Masuk"),erp_export_label("Qty Used"),erp_export_label("Qty Sisa"),erp_export_label("UOM"),erp_export_label("Source Trace"),erp_export_label("Usage Trace"),erp_export_label("Source Ref"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;
  foreach($rows as $row){$values=array($n++,$row->id,$row->kode,$row->nm_barang,$row->plant_code,$row->storage_code,$row->bin_code,blt_stock_type_label($row->stock_type),$row->tgl_masuk,$row->no_bpb,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),(float)$row->qty_masuk,(float)$row->qty_used,(float)$row->qty_sisa,$row->satuan,(int)$row->source_trace_count,(int)$row->usage_trace_count,trim($row->ref_table.' #'.$row->ref_id,' #'));foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('BATCH / LOT TRACEABILITY - SAP MM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('M','N','O'),'filters'=>array('Periode'=>($input['tgl_awal']?:erp_export_all_text()).' s/d '.($input['tgl_akhir']?:erp_export_all_text()),'Material'=>$input['material_code'],'Stock Type'=>$input['stock_type'],'Dokumen BC'=>$input['jenis_dokpab'],'Open Only'=>$input['open_only'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>10,'C'=>16,'D'=>36,'E'=>12,'F'=>16,'G'=>14,'H'=>18,'I'=>14,'J'=>22,'K'=>28,'L'=>20,'M'=>14,'N'=>14,'O'=>14,'P'=>10,'Q'=>12,'R'=>12,'S'=>22)));
  $tmp = erpkb_excel_temp_file('batch_lot_trace_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size=@filesize($tmp); $signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="batch_lot_traceability_'.date('Ymd_His').'.xlsx"');
  header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}
header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
