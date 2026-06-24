<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "slow_moving_stock_lib.php";
$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') {
    $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) ";
    $params[] = '%'.$term.'%';
    $params[] = '%'.$term.'%';
  }
  $rows = $db->query(
    "SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) AS stock_qty
     FROM barang b
     LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0
     $where
     GROUP BY b.kd_barang,b.nm_barang,b.satuan
     ORDER BY b.kd_barang LIMIT 30",
    $params
  );
  $results = array();
  foreach ($rows as $row) {
    $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}

function sms_detail_input() {
  return array(
    'as_of_date'=>sms_input('as_of_date', date('Y-m-d')),
    'threshold_days'=>sms_input('threshold_days', 90),
    'material_code'=>sms_input('material_code'),
    'plant_id'=>sms_input('plant_id'),
    'storage_location_id'=>sms_input('storage_location_id'),
    'storage_bin_id'=>sms_input('storage_bin_id'),
    'stock_type'=>sms_input('stock_type'),
    'risk_label'=>sms_input('risk_label'),
    'jenis_dokpab'=>sms_input('jenis_dokpab'),
    'no_aju'=>sms_input('no_aju'),
    'no_dokpab'=>sms_input('no_dokpab'),
    'slow_only'=>sms_input('slow_only','Y'),
    'keyword'=>''
  );
}

if ($act === 'detail') {
  session_check_json();
  $input = sms_detail_input();
  $threshold = sms_threshold($input['threshold_days']);
  $layers = sms_load_layers($db, $input);
  $totalMasuk = 0; $totalSisa = 0; $rowCount = 0;
  ?>
  <div class="alert alert-info">
    <strong>Detail Slow Moving Stock.</strong> Menampilkan layer/lot pembentuk saldo slow moving, termasuk trace key layer, dokumen BC, No Aju, lokasi, last issue, dan idle days. Jika batch fisik belum tersedia di stock_layer, Layer ID menjadi trace key stok.
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-condensed sms-detail-table">
      <thead><tr class="bg-gray"><th>Layer / Batch</th><th>Risk</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Receipt Date</th><th>Last Issue</th><th class="text-right">Idle Days</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Sisa</th><th>Ref</th></tr></thead>
      <tbody>
      <?php foreach ($layers as $row) {
        $rowCount++; $totalMasuk += (float)$row->qty_masuk; $totalSisa += (float)$row->qty_sisa;
        $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
        $bc = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
      ?>
        <tr>
          <td><strong>Layer #<?=intval($row->id);?></strong><br><small>Trace key</small></td>
          <td><?=sms_risk_badge($row->risk_label);?></td>
          <td><strong><?=sms_h($row->kode);?></strong><br><small><?=sms_h($row->nm_barang);?></small></td>
          <td><?=sms_h($location ?: '-');?><br><small><?=sms_h(sms_stock_type_label($row->stock_type));?></small></td>
          <td><?=sms_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></td>
          <td><?=sms_h($row->last_out_date ?: '-');?><br><small>Last move: <?=sms_h($row->last_move_date ?: '-');?></small></td>
          <td class="text-right"><strong><?=number_format((int)$row->idle_days,0,',','.');?></strong><br><small>threshold <?=intval($threshold);?></small></td>
          <td><?=sms_h($row->no_bpb);?></td>
          <td><?=sms_h($row->no_aju);?></td>
          <td><?=sms_h($bc);?></td>
          <td class="text-right"><?=number_format((float)$row->qty_masuk,5,',','.');?></td>
          <td class="text-right"><strong><?=number_format((float)$row->qty_sisa,5,',','.');?></strong></td>
          <td><small><?=sms_h(trim((string)$row->ref_table.' #'.(string)$row->ref_id, ' #'));?></small></td>
        </tr>
      <?php } ?>
      <?php if ($rowCount === 0) { ?><tr><td colspan="13" class="text-center text-muted">Tidak ada layer untuk filter ini.</td></tr><?php } ?>
      </tbody>
      <tfoot><tr class="bg-gray"><th colspan="10" class="text-right">Total</th><th class="text-right"><?=number_format($totalMasuk,5,',','.');?></th><th class="text-right"><?=number_format($totalSisa,5,',','.');?></th><th></th></tr></tfoot>
    </table>
  </div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = array(
    'as_of_date'=>sms_input('as_of_date', date('Y-m-d')),
    'threshold_days'=>sms_input('threshold_days', 90),
    'material_code'=>sms_input('material_code'),
    'plant_id'=>sms_input('plant_id'),
    'storage_location_id'=>sms_input('storage_location_id'),
    'storage_bin_id'=>sms_input('storage_bin_id'),
    'stock_type'=>sms_input('stock_type'),
    'risk_label'=>sms_input('risk_label'),
    'jenis_dokpab'=>sms_input('jenis_dokpab'),
    'no_aju'=>sms_input('no_aju'),
    'no_dokpab'=>sms_input('no_dokpab'),
    'slow_only'=>sms_input('slow_only','Y'),
    'keyword'=>sms_input('keyword')
  );
  $threshold = sms_threshold($input['threshold_days']);
  $groups = sms_group_layers(sms_load_layers($db, $input), $threshold);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Slow Moving Stock'));
  $headers = array(erp_export_label("No"),erp_export_label("Risk"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Last Issue"),erp_export_label("Last Movement"),erp_export_label("Oldest Receipt"),erp_export_label("Max Idle Days"),erp_export_label("Max Aging Days"),erp_export_label("Layers"),erp_export_label("Customs Docs"),erp_export_label("Critical Qty"),erp_export_label("Slow Qty"),erp_export_label("Total Qty"),erp_export_label("UOM"));
  foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
  $r=5; $n=1; $totCritical=0; $totSlow=0; $totQty=0;
  foreach ($groups as $row) {
    $totCritical += (float)$row->qty_critical;
    $totSlow += (float)$row->qty_slow;
    $totQty += (float)$row->qty_sisa;
    $values = array($n++,$row->risk_label,$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,sms_stock_type_label($row->stock_type),$row->last_out_date,$row->last_move_date,$row->oldest_receipt,(int)$row->max_idle_days,(int)$row->max_aging_days,(int)$row->layer_count,(int)$row->doc_total,(float)$row->qty_critical,(float)$row->qty_slow,(float)$row->qty_sisa,$row->uom);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  $summaryRow = $r + 1;
  $sheet->mergeCells('A'.$summaryRow.':O'.$summaryRow);
  $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
  $sheet->setCellValue('P'.$summaryRow,$totCritical);
  $sheet->setCellValue('Q'.$summaryRow,$totSlow);
  $sheet->setCellValue('R'.$summaryRow,$totQty);
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('SLOW MOVING STOCK - SAP MM INVENTORY'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5,$r-1),
    'column_count'=>19,
    'numeric_columns'=>array('P','Q','R'),
    'filters'=>array(
      'As Of Date'=>sms_valid_date($input['as_of_date'], date('Y-m-d')),
      'Threshold Days'=>$threshold,
      'Material'=>$input['material_code'],
      'Stock Type'=>$input['stock_type'],
      'Risk'=>$input['risk_label'],
      'Jenis BC'=>$input['jenis_dokpab'],
      'No Aju'=>$input['no_aju'],
      'No Daftar'=>$input['no_dokpab'],
      'Slow Only'=>$input['slow_only'],
      'Keyword'=>$input['keyword']
    ),
    'widths'=>array('A'=>6,'B'=>16,'C'=>16,'D'=>36,'E'=>12,'F'=>16,'G'=>14,'H'=>18,'I'=>14,'J'=>14,'K'=>14,'L'=>14,'M'=>14,'N'=>10,'O'=>12,'P'=>14,'Q'=>14,'R'=>14,'S'=>10)
  ));
  $sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('P'.$summaryRow.':R'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');

  $tmp = erpkb_excel_temp_file('slow_moving_stock_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $signature = @file_get_contents($tmp,false,null,0,2);
  if (!$size || $signature !== 'PK') {
    @unlink($tmp);
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type:text/plain; charset=utf-8');
    echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
    exit;
  }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  $asOf = sms_valid_date($input['as_of_date'], date('Y-m-d'));
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="slow_moving_stock_'.$asOf.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
