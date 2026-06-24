<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "incoming_inspection_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'material_search') {
  session_check_json();
  $term = iinq_input('term');
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan FROM barang b $where ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'create_lot') {
  session_check_json();
  $res = iinq_create_lot_from_layer($db, (int)iinq_input('stock_layer_id',0), $username);
  if ($res['status'] === 'good') ilot_json('good','',array('id'=>$res['id'],'lot_no'=>$res['lot_no'],'existing'=>$res['existing']));
  ilot_json('error',$res['message']);
}

if ($act === 'source_detail') {
  session_check_json();
  $row = iinq_candidate($db, (int)iinq_input('stock_layer_id',0));
  if (!$row) { echo '<div class="alert alert-warning">Source incoming tidak ditemukan.</div>'; exit; }
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $customs = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
  ?>
  <div class="alert alert-info"><strong>Incoming Source Detail.</strong> Data ini berasal dari stock layer barang masuk dan menjadi kandidat inspection lot SAP QM.</div>
  <div class="row">
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">Stock Layer</th><td>#<?=intval($row->id);?></td></tr><tr><th style="background:#f8fafc">Source</th><td><?=ilot_h(iinq_source_label($row->ref_table).' #'.$row->ref_id);?></td></tr><tr><th style="background:#f8fafc">No BPB</th><td><?=ilot_h($row->no_bpb ?: '-');?></td></tr><tr><th style="background:#f8fafc">Receipt Date</th><td><?=ilot_h($row->receipt_date);?></td></tr><tr><th style="background:#f8fafc">Location</th><td><?=ilot_h($location ?: '-');?></td></tr></table></div>
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">Material</th><td><strong><?=ilot_h($row->kode);?></strong><br><?=ilot_h($row->nm_barang);?></td></tr><tr><th style="background:#f8fafc">Open Qty</th><td class="text-right"><?=ilot_num($row->qty_sisa).' '.ilot_h($row->satuan);?></td></tr><tr><th style="background:#f8fafc">Stock Type</th><td><?=ilot_h($row->stock_type);?></td></tr><tr><th style="background:#f8fafc">No Aju</th><td><?=ilot_h($row->no_aju ?: '-');?></td></tr><tr><th style="background:#f8fafc">Dokumen BC</th><td><?=ilot_h($customs ?: '-');?></td></tr></table></div>
  </div>
  <?php if (!empty($row->inspection_lot_id)) { ?>
    <div class="alert alert-success">Inspection lot sudah dibuat: <strong><?=ilot_h($row->lot_no);?></strong> <?=iinq_status_badge($row->lot_status);?></div>
  <?php } else { ?>
    <div class="alert alert-warning">Belum ada inspection lot. Klik tombol <strong>+ Lot</strong> pada worklist untuk membuat lot.</div>
  <?php } ?>
  <?php
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = iinq_filters(); $rows = iinq_candidates($db, $filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Incoming Inspection'));
  $heads = array(erp_export_label("No"),erp_export_label("No BPB"),erp_export_label("Receipt Date"),erp_export_label("Source"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Open Qty"),erp_export_label("UOM"),erp_export_label("Location"),erp_export_label("Stock Type"),erp_export_label("Inspection Lot"),erp_export_label("Inspection Status"),erp_export_label("Result Count"),erp_export_label("Fail Count"),erp_export_label("UD"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1;
  foreach($rows as $row){
    $loc=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /');
    $vals=array($n++,$row->no_bpb,$row->receipt_date,iinq_source_label($row->ref_table).' #'.$row->ref_id,$row->kode,$row->nm_barang,(float)$row->qty_sisa,$row->satuan,$loc,$row->stock_type,$row->lot_no,$row->lot_status ?: 'PENDING_LOT',(int)$row->result_count,(int)$row->fail_count,$row->ud_text,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab));
    foreach($vals as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('INCOMING INSPECTION - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>17,'numeric_columns'=>array('G'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['inspection_status'],'Stock Type'=>$filters['stock_type'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('incoming_inspection_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp); while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="incoming_inspection_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

ilot_json('error','Action tidak dikenal.');
?>
