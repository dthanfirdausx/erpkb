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

$tglAwal = isset($_GET['tgl_awal']) ? trim($_GET['tgl_awal']) : '';
$tglAkhir = isset($_GET['tgl_akhir']) ? trim($_GET['tgl_akhir']) : '';
$jenisDokpab = isset($_GET['jenis_dokpab']) ? trim($_GET['jenis_dokpab']) : 'all';
$companyName = defined('namaPT') ? namaPT : (defined('shortTittle') ? shortTittle : 'NAMA_PT');

$legacyParams = array(); $gidParams = array();
$legacyWhere = " WHERE 1=1 "; $gidWhere = " WHERE gi.status='POSTED' ";
if ($tglAwal !== '' && $tglAkhir === '') {
  $legacyWhere .= " AND v.tgl_sj BETWEEN ? AND ? "; $gidWhere .= " AND gi.posting_date BETWEEN ? AND ? ";
  $legacyParams[] = $tglAwal; $legacyParams[] = date('Y-m-d'); $gidParams[] = $tglAwal; $gidParams[] = date('Y-m-d');
} elseif ($tglAwal !== '' && $tglAkhir !== '') {
  $legacyWhere .= " AND v.tgl_sj BETWEEN ? AND ? "; $gidWhere .= " AND gi.posting_date BETWEEN ? AND ? ";
  $legacyParams[] = $tglAwal; $legacyParams[] = $tglAkhir; $gidParams[] = $tglAwal; $gidParams[] = $tglAkhir;
}
if ($jenisDokpab !== '' && $jenisDokpab !== 'all') {
  $legacyWhere .= " AND v.jenis_dokpab=? "; $gidWhere .= " AND gi.outbound_bc_type=? ";
  $legacyParams[] = $jenisDokpab; $gidParams[] = $jenisDokpab;
}

$sql = "
  SELECT * FROM (
    SELECT v.jenis_dokpab,v.no_dokpab,v.tgl_dokpab,v.no_sj no_pengeluaran,v.tgl_sj tgl_pengeluaran,v.nama partner_name,
           v.kode material_code,v.nm_barang material_name,v.satuan uom,v.jumlah qty,v.nilai amount
    FROM vpengeluaranbyjenisdokpab v
    $legacyWhere
    UNION ALL
    SELECT gi.outbound_bc_type jenis_dokpab,gi.outbound_no_daftar no_dokpab,gi.outbound_tgl_daftar tgl_dokpab,
           COALESCE(NULLIF(gi.reference_surat_jalan,''),gi.gi_no) no_pengeluaran,gi.posting_date tgl_pengeluaran,gi.customer_name partner_name,
           d.material_code,d.material_name,d.uom,d.qty,d.amount
    FROM erp_goods_issue_delivery gi
    JOIN erp_goods_issue_delivery_detail d ON d.gi_id=gi.id
    $gidWhere
  ) x
  ORDER BY x.tgl_dokpab ASC,x.no_pengeluaran ASC,x.jenis_dokpab ASC
";
$rows = $db->query($sql, array_merge($legacyParams, $gidParams));

$excel = new PHPExcel();
$sheet = $excel->setActiveSheetIndex(0);
$sheet->setTitle(erp_export_sheet_title('Laporan Pengeluaran'));
$headerStart = 4; $headerEnd = 6; $firstDataRow = 7; $lastCol = 'L';

$sheet->setCellValue('A4','No');
$sheet->setCellValue('B4',"Jenis\nDokumen");
$sheet->setCellValue('C4','Dokumen Pabean');
$sheet->setCellValue('E4',"Bukti/Dokumen\nPengeluaran");
$sheet->setCellValue('G4','Pembeli/Penerima');
$sheet->setCellValue('H4',"Kode\nBarang");
$sheet->setCellValue('I4',"Nama\nBarang");
$sheet->setCellValue('J4','Sat');
$sheet->setCellValue('K4','Jumlah');
$sheet->setCellValue('L4','Nilai Barang');
$sheet->setCellValue('C5','Nomor'); $sheet->setCellValue('D5','Tanggal');
$sheet->setCellValue('E5','Nomor'); $sheet->setCellValue('F5','Tanggal');
foreach (array('(3)','(4)','(5)','(6)','(7)','(8)','(9)','(10)','(11)','(12)','(13)','(14)') as $i=>$label) $sheet->setCellValueByColumnAndRow($i,6,$label);
$sheet->mergeCells('A4:A5'); $sheet->mergeCells('B4:B5'); $sheet->mergeCells('C4:D4'); $sheet->mergeCells('E4:F4'); $sheet->mergeCells('G4:G5'); $sheet->mergeCells('H4:H5'); $sheet->mergeCells('I4:I5'); $sheet->mergeCells('J4:J5'); $sheet->mergeCells('K4:K5'); $sheet->mergeCells('L4:L5');

$r = $firstDataRow; $n = 1; $totalQty = 0; $totalAmount = 0;
foreach ($rows as $row) {
  $totalQty += (float)$row->qty; $totalAmount += (float)$row->amount;
  $vals = array($n++,$row->jenis_dokpab,$row->no_dokpab,$row->tgl_dokpab,$row->no_pengeluaran,$row->tgl_pengeluaran,$row->partner_name,$row->material_code,$row->material_name,$row->uom,(float)$row->qty,(float)$row->amount);
  foreach ($vals as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
  $r++;
}
$lastDataRow = max($firstDataRow, $r - 1);
$summaryRow = $r + 1;
$sheet->mergeCells('A'.$summaryRow.':J'.$summaryRow);
$sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
$sheet->setCellValue('K'.$summaryRow,$totalQty);
$sheet->setCellValue('L'.$summaryRow,$totalAmount);

$periodeText = ($tglAwal !== '' ? $tglAwal : erp_export_all_text()).' s/d '.($tglAkhir !== '' ? $tglAkhir : erp_export_all_text());
$officialPeriodText = ($tglAwal !== '' ? $tglAwal : '-').' SD '.($tglAkhir !== '' ? $tglAkhir : '-');
$jenisText = ($jenisDokpab !== '' && $jenisDokpab !== 'all') ? $jenisDokpab : erp_export_all_text();
erpkb_excel_apply_standard_style($excel, array(
  'sheet'=>$sheet,'title'=>erp_export_title('LAPORAN PENGELUARAN BARANG PER DOKUMEN PABEAN'),'header_row'=>$headerStart,'first_data_row'=>$firstDataRow,'last_data_row'=>$lastDataRow,'column_count'=>12,'last_col'=>$lastCol,
  'decimal_columns'=>array('K'),'money_columns'=>array('L'),'filters'=>array('Periode'=>$periodeText,'Jenis Dokumen'=>$jenisText),
  'widths'=>array('A'=>7,'B'=>14,'C'=>22,'D'=>14,'E'=>24,'F'=>14,'G'=>30,'H'=>16,'I'=>34,'J'=>9,'K'=>16,'L'=>18)
));
$sheet->setCellValue('A1','LAPORAN PENGELUARAN BARANG PER DOKUMEN PABEAN');
$sheet->setCellValue('A2','KAWASAN BERIKAT '.$companyName);
$sheet->setCellValue('A3','PERIODE : '.$officialPeriodText.' | Jenis Dokumen: '.$jenisText);
$sheet->getStyle('A1:'.$lastCol.'3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:'.$lastCol.'3')->getFont()->setBold(true);
$sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setSize(15);
$sheet->getStyle('A2:'.$lastCol.'2')->getFont()->setSize(12);
$sheet->getStyle('A3:'.$lastCol.'3')->getFont()->setSize(11);

$headerStyleRange = 'A'.$headerStart.':'.$lastCol.$headerEnd;
$dataStyleRange = 'A'.$headerStart.':'.$lastCol.$lastDataRow;
$sheet->getStyle($headerStyleRange)->getFont()->setBold(true)->getColor()->setRGB('1F2937');
$sheet->getStyle($headerStyleRange)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF7ED');
$sheet->getStyle('A'.$headerEnd.':'.$lastCol.$headerEnd)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
$sheet->getStyle($headerStyleRange)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
$sheet->getStyle($dataStyleRange)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getStyle('A'.$firstDataRow.':A'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('D'.$firstDataRow.':F'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('J'.$firstDataRow.':J'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('K'.$firstDataRow.':L'.$lastDataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('K'.$firstDataRow.':L'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getRowDimension(4)->setRowHeight(30); $sheet->getRowDimension(5)->setRowHeight(24); $sheet->getRowDimension(6)->setRowHeight(22);
$sheet->setAutoFilter('A'.$headerEnd.':'.$lastCol.$lastDataRow);
$sheet->freezePane('A'.$firstDataRow);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerStart,$headerEnd);
$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFont()->setBold(true);
$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
$sheet->getStyle('A'.$summaryRow.':'.$lastCol.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getStyle('K'.$summaryRow.':L'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('K'.$summaryRow.':L'.$summaryRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$tmp = erpkb_excel_temp_file('laporan_pengeluaran_pabean_');
PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
$size = @filesize($tmp); $sig = @file_get_contents($tmp,false,null,0,2);
if (!$size || $sig !== 'PK') { @unlink($tmp); while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan_pengeluaran_barang_per_dokumen_pabean_'.date('Ymd_His').'.xlsx"');
header('Content-Length: '.$size);
header('Cache-Control: max-age=0');
header('Pragma: public');
readfile($tmp);
@unlink($tmp);
exit;
?>
