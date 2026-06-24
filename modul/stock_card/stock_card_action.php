<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
include "stock_card_lib.php";

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
     ORDER BY b.kd_barang
     LIMIT 30",
    $params
  );
  $results = array();
  foreach ($rows as $row) {
    $results[] = array(
      'id' => $row->kd_barang,
      'text' => $row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty, 5, ',', '.').' '.$row->satuan,
      'uom' => $row->satuan
    );
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results' => $results));
  exit;
}

if ($act === 'layer_detail') {
  session_check_json();
  $material = scard_post('material_code');
  $plantId = (int)scard_post('plant_id');
  $storageLocationId = (int)scard_post('storage_location_id');
  $storageBinId = (int)scard_post('storage_bin_id');
  $stockType = scard_post('stock_type', 'UNRESTRICTED');

  $params = array($material);
  $where = " WHERE sl.kode=? AND sl.qty_sisa>0 ";
  if ($plantId > 0) { $where .= " AND sl.plant_id=? "; $params[] = $plantId; }
  if ($storageLocationId > 0) { $where .= " AND sl.storage_location_id=? "; $params[] = $storageLocationId; }
  if ($storageBinId > 0) { $where .= " AND sl.storage_bin_id=? "; $params[] = $storageBinId; }
  if ($stockType !== '') { $where .= " AND sl.stock_type=? "; $params[] = $stockType; }

  $rows = $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY sl.tgl_masuk,sl.id",
    $params
  );

  $totalMasuk = 0;
  $totalSisa = 0;
  $rowCount = 0;
  ?>
  <div class="alert alert-info">
    <strong>Komposisi saldo stok.</strong> Detail ini membaca open layer dari FIFO/lot yang masih memiliki <code>qty_sisa</code>, sehingga user bisa melihat saldo berasal dari BPB, lot/batch, dan dokumen BC mana.
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-condensed scard-layer-table">
      <thead>
        <tr class="bg-gray">
          <th>Layer</th>
          <th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th>
          <th>Plant / SLoc / Bin</th>
          <th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th>
          <th>No BPB</th>
          <th>Tgl Masuk</th>
          <th>No Aju</th>
          <th>Dokumen BC</th>
          <th class="text-right">Qty Masuk</th>
          <th class="text-right">Qty Sisa</th>
          <th>Ref</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row) {
          $rowCount++;
          $totalMasuk += (float)$row->qty_masuk;
          $totalSisa += (float)$row->qty_sisa;
          $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
          $bc = trim((string)$row->jenis_dokpab.' '.$row->no_dokpab);
        ?>
          <tr>
            <td>#<?=intval($row->id);?></td>
            <td><strong><?=scard_h($row->kode);?></strong><br><small><?=scard_h($row->nm_barang);?></small></td>
            <td><?=scard_h($location ?: '-');?></td>
            <td><?=scard_h(scard_stock_type_label($row->stock_type));?></td>
            <td><?=scard_h($row->no_bpb);?></td>
            <td><?=scard_h($row->tgl_masuk);?></td>
            <td><?=scard_h($row->no_aju);?></td>
            <td><?=scard_h($bc);?></td>
            <td class="text-right"><?=number_format((float)$row->qty_masuk,5,',','.');?></td>
            <td class="text-right"><strong><?=number_format((float)$row->qty_sisa,5,',','.');?></strong></td>
            <td><small><?=scard_h(trim((string)$row->ref_table.' #'.(string)$row->ref_id, ' #'));?></small></td>
          </tr>
        <?php } ?>
        <?php if ($rowCount === 0) { ?>
          <tr><td colspan="11" class="text-center text-muted">Tidak ada open layer untuk kombinasi material/lokasi/stock type ini.</td></tr>
        <?php } ?>
      </tbody>
      <tfoot>
        <tr class="bg-gray">
          <th colspan="8" class="text-right">Total</th>
          <th class="text-right"><?=number_format($totalMasuk,5,',','.');?></th>
          <th class="text-right"><?=number_format($totalSisa,5,',','.');?></th>
          <th></th>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = array(
    'tgl_awal' => scard_post('tgl_awal', date('Y-m-01')),
    'tgl_akhir' => scard_post('tgl_akhir', date('Y-m-d')),
    'material_code' => scard_post('material_code'),
    'plant_id' => scard_post('plant_id'),
    'storage_location_id' => scard_post('storage_location_id'),
    'storage_bin_id' => scard_post('storage_bin_id'),
    'stock_type' => scard_post('stock_type'),
    'move_code' => scard_post('move_code'),
    'direction' => scard_post('direction'),
    'keyword' => scard_post('keyword')
  );
  $rows = scard_load_card_rows($db, $input);
  $from = scard_valid_date($input['tgl_awal'], date('Y-m-01'));
  $to = scard_valid_date($input['tgl_akhir'], date('Y-m-d'));
  $materialLabel = $input['material_code'] ?: erp_export_all_text().' '.erp_export_label('Material');
  if ($input['material_code'] !== '') {
    $materialInfo = $db->fetch("SELECT kd_barang,nm_barang FROM barang WHERE kd_barang=?", array($input['material_code']));
    if ($materialInfo) $materialLabel = $materialInfo->kd_barang.' - '.$materialInfo->nm_barang;
  }
  $plantLabel = erp_export_all_text().' '.erp_export_label('Plant');
  if ($input['plant_id'] !== '') {
    $plantInfo = $db->fetch("SELECT plant_code,plant_name FROM erp_plant WHERE id=?", array((int)$input['plant_id']));
    if ($plantInfo) $plantLabel = $plantInfo->plant_code.' - '.$plantInfo->plant_name;
  }
  $slocLabel = erp_export_all_text().' '.erp_export_label('Storage Location');
  if ($input['storage_location_id'] !== '') {
    $slocInfo = $db->fetch("SELECT storage_code,storage_name FROM erp_storage_location WHERE id=?", array((int)$input['storage_location_id']));
    if ($slocInfo) $slocLabel = $slocInfo->storage_code.' - '.$slocInfo->storage_name;
  }
  $binLabel = erp_export_all_text().' '.erp_export_label('Storage Bin');
  if ($input['storage_bin_id'] !== '') {
    $binInfo = $db->fetch("SELECT bin_code,bin_name FROM erp_storage_bin WHERE id=?", array((int)$input['storage_bin_id']));
    if ($binInfo) $binLabel = $binInfo->bin_code.' - '.$binInfo->bin_name;
  }
  $filterTexts = array(
    erp_export_label('Periode').': '.erp_export_period_text($from, $to),
    erp_export_label('Material').': '.$materialLabel,
    erp_export_label('Plant').': '.$plantLabel,
    erp_export_label('Storage Location').': '.$slocLabel,
    erp_export_label('Storage Bin').': '.$binLabel,
    erp_export_label('Stock Type').': '.($input['stock_type'] !== '' ? scard_stock_type_label($input['stock_type']) : erp_export_all_text().' '.erp_export_label('Stock Type')),
    erp_export_label('Movement').': '.($input['move_code'] !== '' ? $input['move_code'] : erp_export_all_text().' '.erp_export_label('Movement')),
    erp_export_label('Direction').': '.($input['direction'] !== '' ? $input['direction'] : erp_export_all_text().' '.erp_export_label('Direction'))
  );
  if ($input['keyword'] !== '') $filterTexts[] = erp_export_label('Keyword').': '.$input['keyword'];

  $excel = new PHPExcel();
  $excel->getProperties()
        ->setCreator('ERPKB')
        ->setLastModifiedBy('ERPKB')
        ->setTitle(erp_export_sheet_title('Stock Card'))
        ->setSubject('SAP MM Stock Ledger')
        ->setDescription('Stock Card export with running balance and customs reference.');
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Stock Card'));
  $sheet->mergeCells('A1:S1');
  $sheet->mergeCells('A2:S2');
  $sheet->mergeCells('A3:S3');
  $sheet->setCellValue('A1', namaPT);
  $sheet->setCellValue('A2', erp_export_title('STOCK CARD - SAP MM STOCK LEDGER'));
  $sheet->setCellValue('A3', erp_export_generated_text(date('Y-m-d H:i:s')).' | '.erp_export_label('Total Lines').': '.count($rows));
  $sheet->mergeCells('A5:S5');
  $sheet->setCellValue('A5', implode(' | ', $filterTexts));
  $headers = array(erp_export_label("No"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Posting Date"),erp_export_label("Movement"),erp_export_label("Direction"),erp_export_label("Document"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Qty In"),erp_export_label("Qty Out"),erp_export_label("Balance"),erp_export_label("UOM"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("User"),erp_export_label("Remark"));
  foreach ($headers as $c => $header) $sheet->setCellValueByColumnAndRow($c, 7, $header);

  $r = 8;
  $n = 1;
  $totalIn = 0;
  $totalOut = 0;
  $lastBalance = 0;
  foreach ($rows as $row) {
    $direction = (float)$row->signed_qty < 0 ? 'OUT' : 'IN';
    $totalIn += (float)$row->qty_in;
    $totalOut += (float)$row->qty_out;
    $lastBalance = (float)$row->running_balance;
    $values = array(
      $n++,
      $row->material_code,
      $row->nm_barang,
      $row->posting_date,
      trim($row->move_code.' - '.scard_movement_label($row->move_code, $row->ref_type, $direction), ' -'),
      $direction,
      $row->no_ref ?: ($row->no_bpb ?: $row->ref_pengganti),
      $row->plant_code,
      $row->storage_code,
      $row->bin_code,
      scard_stock_type_label($row->line_stock_type),
      (float)$row->qty_in,
      (float)$row->qty_out,
      (float)$row->running_balance,
      $row->uom ?: $row->satuan,
      $row->no_aju ?: $row->header_no_aju,
      $row->no_dokpab ?: $row->header_no_dokpab,
      $row->username,
      $row->remark ?: $row->reason
    );
    foreach ($values as $c => $value) $sheet->setCellValueByColumnAndRow($c, $r, $value);
    $r++;
  }
  $summaryRow = $r + 1;
  $sheet->mergeCells('A'.$summaryRow.':K'.$summaryRow);
  $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
  $sheet->setCellValue('L'.$summaryRow, $totalIn);
  $sheet->setCellValue('M'.$summaryRow, $totalOut);
  $sheet->setCellValue('N'.$summaryRow, $lastBalance);

  $lastCol = 'S';
  $lastDataRow = max(8, $r - 1);
  $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('FFFFFF');
  $sheet->getStyle('A2:'.$lastCol.'2')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
  $sheet->getStyle('A3:'.$lastCol.'3')->getFont()->setItalic(true)->getColor()->setRGB('E0F2FE');
  $sheet->getStyle('A1:'.$lastCol.'3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
  $sheet->getStyle('A1:'.$lastCol.'3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A5:'.$lastCol.'5')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E0F2FE');
  $sheet->getStyle('A5:'.$lastCol.'5')->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
  $sheet->getStyle('A7:'.$lastCol.'7')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
  $sheet->getStyle('A7:'.$lastCol.'7')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
  $sheet->getStyle('A7:'.$lastCol.$lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('A7:'.$lastCol.'7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
  $sheet->getStyle('A8:K'.$lastDataRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setWrapText(true);
  $sheet->getStyle('L8:N'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('L8:N'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  $sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('L'.$summaryRow.':N'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('L'.$summaryRow.':N'.$summaryRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  $sheet->getStyle('A8:A'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('D8:D'.$lastDataRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
  $sheet->getStyle('F8:F'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('O8:O'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $sheet->setAutoFilter('A7:'.$lastCol.$lastDataRow);
  $sheet->freezePane('A8');
  $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
  $sheet->getPageSetup()->setFitToWidth(1);
  $sheet->getPageSetup()->setFitToHeight(0);
  $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setLeft(0.35)->setBottom(0.5);
  $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(7, 7);
  $sheet->getColumnDimension('A')->setWidth(6);
  $sheet->getColumnDimension('B')->setWidth(16);
  $sheet->getColumnDimension('C')->setWidth(34);
  $sheet->getColumnDimension('D')->setWidth(20);
  $sheet->getColumnDimension('E')->setWidth(26);
  $sheet->getColumnDimension('F')->setWidth(10);
  $sheet->getColumnDimension('G')->setWidth(22);
  $sheet->getColumnDimension('H')->setWidth(12);
  $sheet->getColumnDimension('I')->setWidth(18);
  $sheet->getColumnDimension('J')->setWidth(14);
  $sheet->getColumnDimension('K')->setWidth(18);
  $sheet->getColumnDimension('L')->setWidth(14);
  $sheet->getColumnDimension('M')->setWidth(14);
  $sheet->getColumnDimension('N')->setWidth(14);
  $sheet->getColumnDimension('O')->setWidth(10);
  $sheet->getColumnDimension('P')->setWidth(28);
  $sheet->getColumnDimension('Q')->setWidth(20);
  $sheet->getColumnDimension('R')->setWidth(16);
  $sheet->getColumnDimension('S')->setWidth(32);
  $sheet->getRowDimension(5)->setRowHeight(36);
  $sheet->getRowDimension(7)->setRowHeight(30);

  $tempDirectory = ini_get('upload_tmp_dir');
  if (!$tempDirectory || !is_dir($tempDirectory) || !is_writable($tempDirectory)) {
    $tempDirectory = '/Applications/XAMPP/xamppfiles/temp';
  }
  if (!$tempDirectory || !is_dir($tempDirectory) || !is_writable($tempDirectory)) {
    $tempDirectory = sys_get_temp_dir();
  }

  $tmp = tempnam($tempDirectory, 'stock_card_');
  if ($tmp === false) {
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File sementara untuk export Excel tidak dapat dibuat.';
    exit;
  }

  $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
  try {
    $writer->save($tmp);
  } catch (Exception $writerException) {
    @unlink($tmp);
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Export Excel gagal: '.$writerException->getMessage();
    exit;
  }

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
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="stock_card_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status' => 'error', 'error_message' => 'Action tidak dikenal.'));
?>
