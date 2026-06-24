<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require "../../inc/lib/PHPExcel.php";
require_once "../../inc/excel_style_helper.php";

PHPExcel_Shared_File::setUseUploadTempDirectory(true);

$tgl_awal = isset($_GET['tgl_awal']) ? trim($_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? trim($_GET['tgl_akhir']) : '';
$jenis_dokpab = isset($_GET['jenis_dokpab']) ? trim($_GET['jenis_dokpab']) : 'all';
$companyName = defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT');

$where = " WHERE 1=1 ";
$params = array();
if ($tgl_awal !== '' && $tgl_akhir === '') {
  $where .= " AND vpemasukanbyjenisdokpab.tgl_bpb BETWEEN ? AND ? ";
  $params[] = $tgl_awal;
  $params[] = date("Y-m-d");
} elseif ($tgl_awal !== '' && $tgl_akhir !== '') {
  $where .= " AND vpemasukanbyjenisdokpab.tgl_bpb BETWEEN ? AND ? ";
  $params[] = $tgl_awal;
  $params[] = $tgl_akhir;
}

if ($jenis_dokpab !== '' && $jenis_dokpab !== 'all') {
  $where .= " AND vpemasukanbyjenisdokpab.jenis_dokpab = ? ";
  $params[] = $jenis_dokpab;
}

$rows = $db->query("
  SELECT
    vpemasukanbyjenisdokpab.jenis_dokpab,
    vpemasukanbyjenisdokpab.no_dokpab,
    vpemasukanbyjenisdokpab.tgl_dokpab,
    vpemasukanbyjenisdokpab.no_bpb,
    vpemasukanbyjenisdokpab.tgl_bpb,
    vpemasukanbyjenisdokpab.nama,
    vpemasukanbyjenisdokpab.kode,
    vpemasukanbyjenisdokpab.nm_barang,
    vpemasukanbyjenisdokpab.unit,
    vpemasukanbyjenisdokpab.jumlah,
    vpemasukanbyjenisdokpab.nilai
  FROM vpemasukanbyjenisdokpab
  $where
  ORDER BY vpemasukanbyjenisdokpab.tgl_dokpab ASC,
           vpemasukanbyjenisdokpab.no_bpb ASC,
           vpemasukanbyjenisdokpab.jenis_dokpab ASC
", $params);

$excel = new PHPExcel();
$sheet = $excel->setActiveSheetIndex(0);
$sheet->setTitle(erp_export_sheet_title('Laporan Pemasukan'));

$headerStart = 4;
$headerEnd = 6;
$firstDataRow = 7;
$lastCol = 'L';

$sheet->setCellValue('A'.$headerStart, 'No');
$sheet->setCellValue('B'.$headerStart, 'Jenis'."\n".'Dokumen');
$sheet->setCellValue('C'.$headerStart, 'Dokumen Pabean');
$sheet->setCellValue('E'.$headerStart, 'Bukti Penerimaan'."\n".'Barang');
$sheet->setCellValue('G'.$headerStart, 'Pemasok/Pengirim');
$sheet->setCellValue('H'.$headerStart, 'Kode'."\n".'Barang');
$sheet->setCellValue('I'.$headerStart, 'Nama'."\n".'Barang');
$sheet->setCellValue('J'.$headerStart, 'Sat');
$sheet->setCellValue('K'.$headerStart, 'Jumlah');
$sheet->setCellValue('L'.$headerStart, 'Nilai Barang');

$sheet->setCellValue('C5', 'Nomor');
$sheet->setCellValue('D5', 'Tanggal');
$sheet->setCellValue('E5', 'Nomor');
$sheet->setCellValue('F5', 'Tanggal');

$columnNumbers = array('(3)','(4)','(5)','(6)','(7)','(8)','(9)','(10)','(11)','(12)','(13)','(14)');
foreach ($columnNumbers as $i => $label) {
  $sheet->setCellValueByColumnAndRow($i, $headerEnd, $label);
}

$sheet->mergeCells('A4:A5');
$sheet->mergeCells('B4:B5');
$sheet->mergeCells('C4:D4');
$sheet->mergeCells('E4:F4');
$sheet->mergeCells('G4:G5');
$sheet->mergeCells('H4:H5');
$sheet->mergeCells('I4:I5');
$sheet->mergeCells('J4:J5');
$sheet->mergeCells('K4:K5');
$sheet->mergeCells('L4:L5');

$rowNo = $firstDataRow;
$no = 1;
$totalQty = 0;
$totalValue = 0;
foreach ($rows as $row) {
  $totalQty += (float)$row->jumlah;
  $totalValue += (float)$row->nilai;
  $values = array(
    $no++,
    $row->jenis_dokpab,
    $row->no_dokpab,
    $row->tgl_dokpab,
    $row->no_bpb,
    $row->tgl_bpb,
    $row->nama,
    $row->kode,
    $row->nm_barang,
    $row->unit,
    (float)$row->jumlah,
    (float)$row->nilai
  );
  foreach ($values as $col => $value) {
    $sheet->setCellValueByColumnAndRow($col, $rowNo, $value);
  }
  $rowNo++;
}

$lastDataRow = max($firstDataRow, $rowNo - 1);
$summaryRow = $rowNo + 1;
$sheet->mergeCells('A'.$summaryRow.':J'.$summaryRow);
$sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
$sheet->setCellValue('K'.$summaryRow, $totalQty);
$sheet->setCellValue('L'.$summaryRow, $totalValue);

$periodeText = ($tgl_awal !== '' ? $tgl_awal : erp_export_all_text()).' s/d '.($tgl_akhir !== '' ? $tgl_akhir : erp_export_all_text());
$jenisText = ($jenis_dokpab !== '' && $jenis_dokpab !== 'all') ? $jenis_dokpab : erp_export_all_text();
$officialPeriodText = ($tgl_awal !== '' ? $tgl_awal : '-').' SD '.($tgl_akhir !== '' ? $tgl_akhir : '-');

erpkb_excel_apply_standard_style($excel, array(
  'sheet' => $sheet,
  'title' => erp_export_title('LAPORAN PEMASUKAN BARANG PER DOKUMEN PABEAN'),
  'header_row' => $headerStart,
  'first_data_row' => $firstDataRow,
  'last_data_row' => $lastDataRow,
  'column_count' => 12,
  'last_col' => $lastCol,
  'decimal_columns' => array('K'),
  'money_columns' => array('L'),
  'filters' => array(
    'Periode' => $periodeText,
    'Jenis Dokumen' => $jenisText
  ),
  'widths' => array(
    'A' => 7,
    'B' => 14,
    'C' => 22,
    'D' => 14,
    'E' => 22,
    'F' => 14,
    'G' => 30,
    'H' => 16,
    'I' => 34,
    'J' => 9,
    'K' => 16,
    'L' => 18
  )
));

$sheet->setCellValue('A1', 'LAPORAN PEMASUKAN BARANG PER DOKUMEN PABEAN');
$sheet->setCellValue('A2', 'KAWASAN BERIKAT '.$companyName);
$sheet->setCellValue('A3', 'PERIODE : '.$officialPeriodText.' | Jenis Dokumen: '.$jenisText);
$sheet->getStyle('A1:'.$lastCol.'3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:'.$lastCol.'3')->getFont()->setBold(true);
$sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setSize(15);
$sheet->getStyle('A2:'.$lastCol.'2')->getFont()->setSize(12);
$sheet->getStyle('A3:'.$lastCol.'3')->getFont()->setSize(11);

$headerStyleRange = 'A'.$headerStart.':'.$lastCol.$headerEnd;
$dataStyleRange = 'A'.$headerStart.':'.$lastCol.$lastDataRow;
$sheet->getStyle($headerStyleRange)->getFont()->setBold(true)->getColor()->setRGB('1F2937');
$sheet->getStyle($headerStyleRange)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEF6FB');
$sheet->getStyle('A'.$headerEnd.':'.$lastCol.$headerEnd)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
$sheet->getStyle($headerStyleRange)->getAlignment()
      ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
      ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
      ->setWrapText(true);
$sheet->getStyle($dataStyleRange)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getStyle('A'.$firstDataRow.':A'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('D'.$firstDataRow.':F'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('J'.$firstDataRow.':J'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('K'.$firstDataRow.':L'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('K'.$firstDataRow.':K'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('L'.$firstDataRow.':L'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getRowDimension(4)->setRowHeight(30);
$sheet->getRowDimension(5)->setRowHeight(24);
$sheet->getRowDimension(6)->setRowHeight(22);
$sheet->setAutoFilter('A'.$headerEnd.':'.$lastCol.$lastDataRow);
$sheet->freezePane('A'.$firstDataRow);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerStart, $headerEnd);

$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFont()->setBold(true);
$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getStyle('K'.$summaryRow.':L'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('K'.$summaryRow.':L'.$summaryRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$tmp = erpkb_excel_temp_file('laporan_pemasukan_pabean_');
PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
$size = @filesize($tmp);
$signature = @file_get_contents($tmp, false, null, 0, 2);
if (!$size || $signature !== 'PK') {
  @unlink($tmp);
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  header('Content-Type:text/plain; charset=utf-8');
  echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
  exit;
}

while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_pemasukan_barang_per_dokumen_pabean_'.date('Ymd_His').'.xlsx"');
header('Content-Length: '.$size);
header('Cache-Control: max-age=0');
header('Pragma: public');
readfile($tmp);
@unlink($tmp);
exit;
