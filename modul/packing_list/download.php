<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$initialOutputBufferLevel = ob_get_level();
ob_start();

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

include "../../inc/config.php";
require '../../inc/lib/PHPExcel.php';
require_once '../../inc/excel_style_helper.php';

function pl_value($row, $key, $default = '')
{
    return (is_object($row) && isset($row->{$key}) && $row->{$key} !== null) ? $row->{$key} : $default;
}

function pl_date($value)
{
    if (!$value || $value === '0000-00-00') {
        return '';
    }
    $time = strtotime($value);
    return $time ? date('d-m-Y', $time) : '';
}

function pl_clean_output()
{
    global $initialOutputBufferLevel;
    while (ob_get_level() > $initialOutputBufferLevel) {
        ob_end_clean();
    }
}

function pl_exit_error($message)
{
    pl_clean_output();
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    pl_exit_error('ID packing list tidak valid.');
}

$header = $db->fetch("
    SELECT
        p.*,
        COALESCE(od.delivery_no, p.delivery_no) AS outbound_delivery_no,
        od.delivery_date,
        od.planned_gi_date,
        od.customer_code,
        COALESCE(od.customer_name, pr.nama) AS customer_name,
        COALESCE(od.ship_to_address, pr.alamat) AS ship_to_address,
        od.shipping_point,
        od.route,
        od.carrier,
        COALESCE(p.vehicle_no, od.vehicle_no) AS vehicle_number,
        od.driver_name,
        sj.no_surat_jalan,
        sj.no_sales_order AS sj_sales_order_no,
        so.no_sales_order AS so_no,
        COALESCE(so.no_po, p.no_po) AS customer_po_no,
        pr.nama AS receiver_name,
        pr.alamat AS receiver_address
    FROM packing_list p
    LEFT JOIN erp_outbound_delivery od ON od.id = p.delivery_id OR od.delivery_no = p.delivery_no
    LEFT JOIN penerima pr ON TRIM(pr.kode_penerima) = TRIM(p.penerima)
    LEFT JOIN surat_jalan sj ON TRIM(sj.no_surat_jalan) = TRIM(p.no_sj)
    LEFT JOIN sales_order so ON so.id_sales_order = od.id_sales_order OR so.no_sales_order = sj.no_sales_order
    WHERE p.id = ?
    LIMIT 1
", array($id));

if (!$header) {
    pl_exit_error('Data packing list tidak ditemukan.');
}

$details = $db->query("
    SELECT
        d.*,
        COALESCE(odd.material_code, d.kode) AS material_code,
        COALESCE(odd.material_name, d.material_name, b.nm_barang) AS material_name_final,
        COALESCE(odd.uom, d.unit, b.satuan) AS uom_final,
        COALESCE(odd.order_qty, sod.qty, 0) AS order_qty_final,
        COALESCE(odd.delivery_qty, d.delivery_qty, d.jumlah, 0) AS delivery_qty_final,
        COALESCE(odd.picked_qty, d.picked_qty, 0) AS picked_qty_final,
        COALESCE(odd.packed_qty, d.packed_qty, d.jumlah, 0) AS packed_qty_final,
        COALESCE(odd.batch_no, d.lot_no) AS batch_lot_final,
        COALESCE(odd.remarks, d.remark) AS remark_final
    FROM packing_list_detail d
    LEFT JOIN erp_outbound_delivery_detail odd ON odd.id = d.delivery_detail_id
    LEFT JOIN barang b ON TRIM(b.kd_barang) = TRIM(COALESCE(odd.material_code, d.kode))
    LEFT JOIN packing_list p ON p.id = d.packing_list_id OR TRIM(p.no_sj) = TRIM(d.no_sj)
    LEFT JOIN erp_outbound_delivery od ON od.id = p.delivery_id OR od.delivery_no = p.delivery_no
    LEFT JOIN sales_order_detail sod
        ON sod.id_detail = odd.sales_order_detail_id
        OR (
            sod.id_sales_order = od.id_sales_order
            AND TRIM(sod.kd_barang) = TRIM(COALESCE(odd.material_code, d.kode))
        )
    WHERE d.packing_list_id = ?
       OR (COALESCE(d.packing_list_id, 0) = 0 AND TRIM(d.no_sj) = TRIM(?))
    ORDER BY COALESCE(d.line_no, d.row_no, d.id), d.id
", array($id, pl_value($header, 'no_sj')));

if ($details === false) {
    pl_exit_error('Gagal membaca detail packing list: '.$db->getErrorMessage());
}

$excel = new PHPExcel();
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();
$sheet->setTitle(erp_export_sheet_title('Packing List'));

$company = defined('namaPT') ? namaPT : 'ERPKB';
$sheet->mergeCells('A1:L1');
$sheet->mergeCells('A2:L2');
$sheet->setCellValue('A1', $company);
$sheet->setCellValue('A2', 'PACKING LIST');
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('111827');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1D4ED8');
$sheet->getStyle('A2:L2')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
$sheet->getStyle('A2:L2')->getBorders()->getBottom()->getColor()->setRGB('CBD5E1');

$logo = '';
if (function_exists('infokb')) {
    $infoKb = infokb();
    $logo = is_object($infoKb) && isset($infoKb->logo) ? trim((string) $infoKb->logo) : '';
}
if ($logo !== '') {
    $logoPath = realpath(__DIR__.'/../../assets/'.$logo);
    if ($logoPath && is_file($logoPath)) {
        $drawing = new PHPExcel_Worksheet_Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
        $sheet->getRowDimension(1)->setRowHeight(42);
    }
}

$labelStyle = array(
    'font' => array('bold' => false, 'color' => array('rgb' => '64748B')),
    'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP),
);
$valueStyle = array(
    'font' => array('bold' => false, 'color' => array('rgb' => '111827')),
    'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP, 'wrap' => true),
);
$sectionLineStyle = array(
    'borders' => array(
        'bottom' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => array('rgb' => 'E2E8F0'),
        ),
    ),
);

$sheet->setCellValue('A4', 'Packing List No');
$sheet->setCellValue('B4', ': '.pl_value($header, 'no_packing_list'));
$sheet->setCellValue('A5', 'Packing Date');
$sheet->setCellValue('B5', ': '.pl_date(pl_value($header, 'tgl_sj')));
$sheet->setCellValue('A6', 'Outbound Delivery');
$sheet->setCellValue('B6', ': '.pl_value($header, 'outbound_delivery_no'));
$sheet->setCellValue('A7', 'Surat Jalan');
$sheet->setCellValue('B7', ': '.pl_value($header, 'no_sj'));
$sheet->setCellValue('A8', 'Sales Order');
$sheet->setCellValue('B8', ': '.pl_value($header, 'so_no', pl_value($header, 'sj_sales_order_no')));
$sheet->setCellValue('A9', 'Customer PO');
$sheet->setCellValue('B9', ': '.pl_value($header, 'customer_po_no'));

$sheet->setCellValue('D4', 'Customer');
$sheet->setCellValue('E4', ': '.pl_value($header, 'customer_name', pl_value($header, 'receiver_name')));
$sheet->setCellValue('D5', 'Ship To');
$sheet->setCellValue('E5', ': '.pl_value($header, 'ship_to_address', pl_value($header, 'receiver_address')));
$sheet->setCellValue('D7', 'Vehicle No');
$sheet->setCellValue('E7', ': '.pl_value($header, 'vehicle_number'));
$sheet->setCellValue('D8', 'Carrier / Driver');
$sheet->setCellValue('E8', ': '.trim(pl_value($header, 'carrier').' / '.pl_value($header, 'driver_name'), ' /'));
$sheet->setCellValue('D9', 'Remarks');
$sheet->setCellValue('E9', ': '.pl_value($header, 'remarks'));

$sheet->getStyle('A4:A9')->applyFromArray($labelStyle);
$sheet->getStyle('D4:D9')->applyFromArray($labelStyle);
$sheet->getStyle('B4:B9')->applyFromArray($valueStyle);
$sheet->getStyle('E4:E9')->applyFromArray($valueStyle);
$sheet->mergeCells('E5:L6');
$sheet->mergeCells('E9:L9');
$sheet->getStyle('A4:B9')->applyFromArray($sectionLineStyle);
$sheet->getStyle('D4:L9')->applyFromArray($sectionLineStyle);
$sheet->getStyle('A4:L9')->getFont()->setSize(10);
$sheet->getRowDimension(5)->setRowHeight(24);
$sheet->getRowDimension(6)->setRowHeight(24);

$headerRow = 12;
$columns = array(
    'A' => 'No',
    'B' => 'Material Code',
    'C' => 'Description Material',
    'D' => 'SO Qty',
    'E' => 'Delivery Qty',
    'F' => 'Packed Qty',
    'G' => 'UOM',
    'H' => 'Packing',
    'I' => 'Qty Packing',
    'J' => 'Batch / Lot',
    'K' => 'Dokumen BC',
    'L' => 'Remark',
);

foreach ($columns as $col => $title) {
    $sheet->setCellValue($col.$headerRow, $title);
}

$row = $headerRow + 1;
$no = 1;
$totalDelivery = 0;
$totalPacked = 0;
$totalPackage = 0;

foreach ($details as $detail) {
    $deliveryQty = (float) pl_value($detail, 'delivery_qty_final', 0);
    $packedQty = (float) pl_value($detail, 'packed_qty_final', 0);
    $qtyPacking = (float) pl_value($detail, 'qty_packing', 0);
    $bcDoc = trim(pl_value($detail, 'jenis_dokpab').' '.pl_value($detail, 'no_dokpab'));

    $sheet->setCellValue('A'.$row, $no);
    $sheet->setCellValueExplicit('B'.$row, pl_value($detail, 'material_code'), PHPExcel_Cell_DataType::TYPE_STRING);
    $sheet->setCellValue('C'.$row, pl_value($detail, 'material_name_final'));
    $sheet->setCellValue('D'.$row, (float) pl_value($detail, 'order_qty_final', 0));
    $sheet->setCellValue('E'.$row, $deliveryQty);
    $sheet->setCellValue('F'.$row, $packedQty);
    $sheet->setCellValue('G'.$row, pl_value($detail, 'uom_final'));
    $sheet->setCellValue('H'.$row, pl_value($detail, 'packing'));
    $sheet->setCellValue('I'.$row, $qtyPacking ?: pl_value($detail, 'qty_packing'));
    $sheet->setCellValue('J'.$row, pl_value($detail, 'batch_lot_final'));
    $sheet->setCellValue('K'.$row, $bcDoc);
    $sheet->setCellValue('L'.$row, pl_value($detail, 'remark_final'));

    $totalDelivery += $deliveryQty;
    $totalPacked += $packedQty;
    $totalPackage += $qtyPacking;
    $row++;
    $no++;
}

if ($no === 1) {
    $sheet->mergeCells('A'.$row.':L'.$row);
    $sheet->setCellValue('A'.$row, 'Detail packing list belum tersedia.');
    $row++;
}

$totalRow = $row;
$sheet->mergeCells('A'.$totalRow.':D'.$totalRow);
$sheet->setCellValue('A'.$totalRow, erp_export_label('TOTAL'));
$sheet->setCellValue('E'.$totalRow, $totalDelivery);
$sheet->setCellValue('F'.$totalRow, $totalPacked);
$sheet->setCellValue('I'.$totalRow, $totalPackage ?: '');
$sheet->getStyle('A'.$totalRow.':L'.$totalRow)->getFont()->setBold(true);

$lastDataRow = $totalRow;
$sheet->getStyle('A'.$headerRow.':L'.$lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
$sheet->getStyle('A'.$headerRow.':L'.$headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A'.$headerRow.':L'.$headerRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('1D4ED8');
$sheet->getStyle('A'.$headerRow.':L'.$headerRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
$sheet->getStyle('A'.($headerRow + 1).':L'.$lastDataRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setWrapText(true);
$sheet->getStyle('D'.($headerRow + 1).':F'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00000');
$sheet->getStyle('I'.($headerRow + 1).':I'.$lastDataRow)->getNumberFormat()->setFormatCode('#,##0.##');
$sheet->setAutoFilter('A'.$headerRow.':L'.$lastDataRow);
$sheet->freezePane('A'.($headerRow + 1));

$signatureRow = $totalRow + 3;
$sheet->setCellValue('A'.$signatureRow, 'Prepared By');
$sheet->setCellValue('E'.$signatureRow, 'Approved By');
$sheet->setCellValue('I'.$signatureRow, 'Received By');
$sheet->getStyle('A'.$signatureRow.':I'.$signatureRow)->getFont()->setBold(true);
$sheet->setCellValue('A'.($signatureRow + 4), '________________');
$sheet->setCellValue('E'.($signatureRow + 4), '________________');
$sheet->setCellValue('I'.($signatureRow + 4), '________________');

$widths = array(
    'A' => 6,
    'B' => 18,
    'C' => 32,
    'D' => 14,
    'E' => 14,
    'F' => 14,
    'G' => 10,
    'H' => 16,
    'I' => 14,
    'J' => 18,
    'K' => 18,
    'L' => 28,
);
foreach ($widths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setLeft(0.35)->setBottom(0.5);

$safeNo = preg_replace('/[^A-Za-z0-9_-]+/', '_', pl_value($header, 'no_packing_list', 'packing_list'));
$filename = 'packing_list_'.$safeNo.'.xlsx';
$tmpFile = erpkb_excel_temp_file('packing_list_');

try {
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $writer->save($tmpFile);
} catch (Exception $e) {
    if (is_file($tmpFile)) {
        unlink($tmpFile);
    }
    pl_exit_error('Gagal membuat file Excel: '.$e->getMessage());
}

$signature = is_file($tmpFile) ? file_get_contents($tmpFile, false, null, 0, 2) : '';
if ($signature !== 'PK') {
    if (is_file($tmpFile)) {
        unlink($tmpFile);
    }
    pl_exit_error('File Excel gagal dibuat dengan format XLSX yang valid.');
}

pl_clean_output();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Content-Length: '.filesize($tmpFile));

readfile($tmpFile);
unlink($tmpFile);
exit;
