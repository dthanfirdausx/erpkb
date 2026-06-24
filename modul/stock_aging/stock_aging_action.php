<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "stock_aging_lib.php";

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

if ($act === 'layer_detail') {
  session_check_json();
  $input = array(
    'as_of_date' => saging_input('as_of_date', date('Y-m-d')),
    'material_code' => saging_input('material_code'),
    'plant_id' => saging_input('plant_id'),
    'storage_location_id' => saging_input('storage_location_id'),
    'storage_bin_id' => saging_input('storage_bin_id'),
    'stock_type' => saging_input('stock_type'),
    'aging_bucket' => saging_input('aging_bucket'),
    'keyword' => ''
  );
  $layers = saging_load_layers($db, $input);
  $totalMasuk = 0; $totalSisa = 0; $rowCount = 0;
  ?>
  <div class="alert alert-info">
    <strong>Detail Stock Aging.</strong> Menampilkan layer stok terbuka sesuai bucket <?=saging_h(saging_bucket_label($input['aging_bucket']));?>, termasuk BPB, No Aju, dokumen BC, dan umur stok per layer.
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-condensed saging-layer-table">
      <thead><tr class="bg-gray"><th>Layer</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><th>Receipt Date</th><th class="text-right">Aging Days</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Sisa</th><th>Ref</th></tr></thead>
      <tbody>
      <?php foreach ($layers as $row) {
        $rowCount++; $totalMasuk += (float)$row->qty_masuk; $totalSisa += (float)$row->qty_sisa;
        $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
        $bc = trim((string)$row->jenis_dokpab.' '.$row->no_dokpab);
      ?>
        <tr>
          <td>#<?=intval($row->id);?></td>
          <td><strong><?=saging_h($row->kode);?></strong><br><small><?=saging_h($row->nm_barang);?></small></td>
          <td><?=saging_h($location ?: '-');?></td>
          <td><?=saging_h(saging_stock_type_label($row->stock_type));?></td>
          <td><?=saging_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></td>
          <td class="text-right"><?=number_format((int)$row->aging_days,0,',','.');?></td>
          <td><?=saging_h($row->no_bpb);?></td>
          <td><?=saging_h($row->no_aju);?></td>
          <td><?=saging_h($bc);?></td>
          <td class="text-right"><?=number_format((float)$row->qty_masuk,5,',','.');?></td>
          <td class="text-right"><strong><?=number_format((float)$row->qty_sisa,5,',','.');?></strong></td>
          <td><small><?=saging_h(trim((string)$row->ref_table.' #'.(string)$row->ref_id, ' #'));?></small></td>
        </tr>
      <?php } ?>
      <?php if ($rowCount === 0) { ?><tr><td colspan="12" class="text-center text-muted">Tidak ada layer untuk filter ini.</td></tr><?php } ?>
      </tbody>
      <tfoot><tr class="bg-gray"><th colspan="9" class="text-right">Total</th><th class="text-right"><?=number_format($totalMasuk,5,',','.');?></th><th class="text-right"><?=number_format($totalSisa,5,',','.');?></th><th></th></tr></tfoot>
    </table>
  </div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = array(
    'as_of_date' => saging_input('as_of_date', date('Y-m-d')),
    'material_code' => saging_input('material_code'),
    'plant_id' => saging_input('plant_id'),
    'storage_location_id' => saging_input('storage_location_id'),
    'storage_bin_id' => saging_input('storage_bin_id'),
    'stock_type' => saging_input('stock_type'),
    'aging_bucket' => saging_input('aging_bucket'),
    'keyword' => saging_input('keyword')
  );
  $groups = saging_group_layers(saging_load_layers($db, $input));
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Stock Aging'));
  $headers = array(erp_export_label("No"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Oldest Receipt"),erp_export_label("Max Age Days"),erp_export_label("Layers"),erp_export_label("0-30"),erp_export_label("31-60"),erp_export_label("61-90"),erp_export_label("91-180"),erp_export_label("181-365"),erp_export_label(">365"),erp_export_label("Total Qty"),erp_export_label("UOM"));
  foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
  $r=5; $n=1; $totals = array('0_30'=>0,'31_60'=>0,'61_90'=>0,'91_180'=>0,'181_365'=>0,'365_plus'=>0,'total'=>0);
  foreach ($groups as $row) {
    foreach ($row->bucket_qty as $k=>$v) $totals[$k] += (float)$v;
    $totals['total'] += (float)$row->total_qty;
    $values = array($n++,$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,saging_stock_type_label($row->stock_type),$row->oldest_date,(int)$row->max_age,(int)$row->layer_count,(float)$row->bucket_qty['0_30'],(float)$row->bucket_qty['31_60'],(float)$row->bucket_qty['61_90'],(float)$row->bucket_qty['91_180'],(float)$row->bucket_qty['181_365'],(float)$row->bucket_qty['365_plus'],(float)$row->total_qty,$row->uom);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  $summaryRow = $r + 1;
  $sheet->mergeCells('A'.$summaryRow.':J'.$summaryRow);
  $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
  $summaryValues = array('K'=>$totals['0_30'],'L'=>$totals['31_60'],'M'=>$totals['61_90'],'N'=>$totals['91_180'],'O'=>$totals['181_365'],'P'=>$totals['365_plus'],'Q'=>$totals['total']);
  foreach ($summaryValues as $col=>$value) $sheet->setCellValue($col.$summaryRow, $value);
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,'title'=>erp_export_title('STOCK AGING - SAP MM INVENTORY AGING'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>18,
    'numeric_columns'=>array('K','L','M','N','O','P','Q'),
    'filters'=>array('As Of Date'=>saging_valid_date($input['as_of_date'], date('Y-m-d')),'Material'=>$input['material_code'],'Stock Type'=>$input['stock_type'],'Aging Bucket'=>saging_bucket_label($input['aging_bucket']),'Keyword'=>$input['keyword']),
    'widths'=>array('A'=>6,'B'=>16,'C'=>36,'D'=>12,'E'=>16,'F'=>14,'G'=>18,'H'=>16,'I'=>12,'J'=>10,'K'=>13,'L'=>13,'M'=>13,'N'=>13,'O'=>13,'P'=>13,'Q'=>14,'R'=>10)
  ));
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('K'.$summaryRow.':Q'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');

  $tmp = erpkb_excel_temp_file('stock_aging_');
  $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
  $writer->save($tmp);
  $size = @filesize($tmp);
  $signature = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $signature !== 'PK') {
    @unlink($tmp);
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
    exit;
  }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  $asOf = saging_valid_date($input['as_of_date'], date('Y-m-d'));
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="stock_aging_'.$asOf.'.xlsx"');
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
