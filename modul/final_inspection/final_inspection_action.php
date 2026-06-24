<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "final_inspection_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'material_search') {
  session_check_json();
  $term = fins_input('term');
  $params = array();
  $where = " WHERE 1=1 ";
  if ($term !== '') { $where .= " AND (material_code LIKE ? OR material_name LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT DISTINCT material_code,material_name,uom FROM erp_gr_production_detail $where ORDER BY material_code LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->material_code,'text'=>$row->material_code.' - '.$row->material_name.' | '.$row->uom);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'create_lot') {
  session_check_json();
  $res = fins_create_lot_from_gr_detail($db, (int)fins_input('detail_id',0), $username);
  if ($res['status'] === 'good') ilot_json('good','',array('id'=>$res['id'],'lot_no'=>$res['lot_no'],'existing'=>$res['existing']));
  ilot_json('error',$res['message']);
}

if ($act === 'source_detail') {
  session_check_json();
  $row = fins_candidate($db, (int)fins_input('detail_id',0));
  if (!$row) { echo '<div class="alert alert-warning">GR production detail tidak ditemukan.</div>'; exit; }
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $traces = $db->query("SELECT * FROM erp_gr_production_trace WHERE gr_detail_id=? ORDER BY id", array($row->id));
  ?>
  <div class="alert alert-info"><strong>Final Inspection Source Detail.</strong> Data ini berasal dari GR from Production Order dan menjadi kandidat final inspection tipe 04.</div>
  <div class="row">
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">GR Production</th><td><?=ilot_h($row->gr_no);?></td></tr><tr><th style="background:#f8fafc">Production Order</th><td><?=ilot_h($row->no_production_order);?></td></tr><tr><th style="background:#f8fafc">Confirmation</th><td><?=ilot_h($row->confirmation_no ?: '-');?></td></tr><tr><th style="background:#f8fafc">Posting Date</th><td><?=ilot_h($row->posting_date);?></td></tr><tr><th style="background:#f8fafc">Location</th><td><?=ilot_h($location ?: '-');?></td></tr></table></div>
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">Material</th><td><strong><?=ilot_h($row->material_code);?></strong><br><?=ilot_h($row->material_name);?></td></tr><tr><th style="background:#f8fafc">Output Qty</th><td class="text-right"><?=ilot_num($row->qty).' '.ilot_h($row->uom);?></td></tr><tr><th style="background:#f8fafc">Stock Type</th><td><?=ilot_h($row->gr_stock_type);?></td></tr><tr><th style="background:#f8fafc">Stock Layer</th><td>#<?=intval($row->stock_layer_id);?></td></tr><tr><th style="background:#f8fafc">Material Doc</th><td><?=intval($row->material_doc_id);?></td></tr></table></div>
  </div>
  <h4>Raw Material / Customs Trace</h4>
  <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Source Material</th><th>Raw Material</th><th class="text-right">Qty</th><th>Lot</th><th>No BPB</th><th>No Aju</th><th>BC Doc</th><th>Trace</th></tr></thead><tbody>
  <?php $count=0; foreach($traces as $tr){ $count++; ?>
    <tr><td><?=ilot_h($tr->source_material_code.' - '.$tr->source_material_name);?></td><td><?=ilot_h($tr->raw_material_code.' - '.$tr->raw_material_name);?></td><td class="text-right"><?=ilot_num($tr->qty).' '.ilot_h($tr->uom);?></td><td><?=ilot_h($tr->lot_no ?: '-');?></td><td><?=ilot_h($tr->no_bpb ?: '-');?></td><td><?=ilot_h($tr->no_aju ?: '-');?></td><td><?=ilot_h(trim($tr->jenis_dokpab.' '.$tr->no_dokpab) ?: '-');?></td><td><?=ilot_h($tr->trace_source);?></td></tr>
  <?php } if($count===0){ ?><tr><td colspan="8" class="text-center text-muted">Trace bahan baku belum tersedia.</td></tr><?php } ?>
  </tbody></table></div>
  <?php if (!empty($row->inspection_lot_id)) { ?><div class="alert alert-success">Inspection lot sudah dibuat: <strong><?=ilot_h($row->lot_no);?></strong> <?=fins_status_badge($row->lot_status);?></div><?php } else { ?><div class="alert alert-warning">Belum ada final inspection lot. Klik tombol <strong>+ Lot</strong> pada worklist untuk membuat lot.</div><?php } ?>
  <?php
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = fins_filters(); $rows = fins_candidates($db, $filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Final Inspection'));
  $heads = array(erp_export_label("No"),erp_export_label("GR No"),erp_export_label("Posting Date"),erp_export_label("Production Order"),erp_export_label("Confirmation"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Location"),erp_export_label("Stock Type"),erp_export_label("Stock Layer"),erp_export_label("Material Doc"),erp_export_label("Inspection Lot"),erp_export_label("Inspection Status"),erp_export_label("Result Count"),erp_export_label("Fail Count"),erp_export_label("UD"),erp_export_label("Remarks"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1;
  foreach($rows as $row){
    $loc=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /');
    $vals=array($n++,$row->gr_no,$row->posting_date,$row->no_production_order,$row->confirmation_no,$row->material_code,$row->material_name,(float)$row->qty,$row->uom,$loc,$row->gr_stock_type,(int)$row->stock_layer_id,(int)$row->material_doc_id,$row->lot_no,$row->lot_status ?: 'PENDING_LOT',(int)$row->result_count,(int)$row->fail_count,$row->ud_text,$row->remarks);
    foreach($vals as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('FINAL INSPECTION - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('H'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['inspection_status'],'Stock Type'=>$filters['stock_type'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('final_inspection_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp); while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="final_inspection_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

ilot_json('error','Action tidak dikenal.');
?>
