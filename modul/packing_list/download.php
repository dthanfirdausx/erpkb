<?php

include "../../inc/config.php";

require '../../inc/lib/PHPExcel.php';

$id = $_GET['id'] ?? '';

/* =========================================
   HEADER DATA
========================================= */

$header = $db->fetch("

SELECT 

    p.*,

    pr.nama,
    pr.alamat,

    d.kode,
    d.jumlah,
    d.qty_packing,
    d.packing,

    b.nm_barang AS material_name,
    b.satuan,

    sj.no_sales_order,

    so.no_po,

    sod.qty AS qty_po

FROM packing_list p 

LEFT JOIN penerima pr
    ON TRIM(pr.kode_penerima) = TRIM(p.penerima)

LEFT JOIN packing_list_detail d
    ON TRIM(d.no_sj) = TRIM(p.no_sj)

LEFT JOIN barang b
    ON TRIM(b.kd_barang) = TRIM(d.kode)

LEFT JOIN surat_jalan sj
    ON TRIM(sj.no_surat_jalan) = TRIM(p.no_sj)

LEFT JOIN sales_order so
    ON so.no_sales_order = sj.no_sales_order

LEFT JOIN sales_order_detail sod
    ON sod.id_sales_order = so.id_sales_order
    AND TRIM(sod.kd_barang) = TRIM(d.kode)

WHERE p.id = '$id'

GROUP BY d.kode

");

/* =========================================
   DETAIL DATA
========================================= */

$q_detail = $db->query("

SELECT 

    d.*,

    b.nm_barang,
    b.satuan

FROM packing_list_detail d

LEFT JOIN barang b
    ON TRIM(b.kd_barang) = TRIM(d.kode)

WHERE d.no_sj = '$header->no_sj'

");

/* =========================================
   PHP EXCEL
========================================= */

$objPHPExcel = new PHPExcel();

$objPHPExcel->setActiveSheetIndex(0);

$sheet = $objPHPExcel->getActiveSheet();

$sheet->setTitle('PACKING LIST');

/* =========================================
   STYLE
========================================= */

$style_bold = array(
    'font' => array(
        'bold' => true
    )
);

$style_center = array(
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
    )
);

$style_border = array(
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN
        )
    )
);

/* =========================================
   LOGO
========================================= */

$logo_path = base_url().'assets/'.infokb()->logo;

if(file_exists($logo_path)){

    $objDrawing = new PHPExcel_Worksheet_Drawing();

    $objDrawing->setName('Logo');
    $objDrawing->setDescription('Logo');

    $objDrawing->setPath($logo_path);

    $objDrawing->setHeight(60);

    $objDrawing->setCoordinates('A1');

    $objDrawing->setWorksheet($sheet);

}

$sheet->getRowDimension(1)->setRowHeight(50);

/* =========================================
   HEADER
========================================= */

$sheet->mergeCells('B1:E1');
$sheet->setCellValue('B1', 'PACKING LIST');

$sheet->getStyle('B1')->applyFromArray($style_bold);
$sheet->getStyle('B1')->applyFromArray($style_center);

$sheet->getStyle('B1')->getFont()->setSize(18);

$row = 4;

$sheet->setCellValue('A'.$row, 'CUSTOMER');
$sheet->setCellValue('B'.$row, ': '.$header->nama);

$row++;

$sheet->setCellValue('A'.$row, 'MATERIAL NAME');
$sheet->setCellValue('B'.$row, ': '.$header->material_name);

$row++;

$sheet->setCellValue('A'.$row, 'PO#');
$sheet->setCellValue('B'.$row, ': '.$header->no_po);

$row++;

$sheet->setCellValue('A'.$row, 'QTY PO');
$sheet->setCellValue('B'.$row, ': '.formatAngka($header->qty_po).' M');

$row++;

$sheet->setCellValue('A'.$row, 'CUST MATERIAL CODE');
$sheet->setCellValue('B'.$row, ': '.$header->kode);

$row++;

$sheet->setCellValue('A'.$row, 'PACKING LIST#');
$sheet->setCellValue('B'.$row, ': '.$header->no_packing_list);

$row++;

$sheet->setCellValue('A'.$row, 'DELIVERY DATE');
$sheet->setCellValue('B'.$row, ': '.date('d-m-Y', strtotime($header->tgl_sj)));

$row += 2;

/* =========================================
   TABLE HEADER
========================================= */

$sheet->setCellValue('A'.$row, 'ROLL NO#');
$sheet->setCellValue('B'.$row, 'DESCRIPTION MATERIAL');
$sheet->setCellValue('C'.$row, 'QTY (M)');
$sheet->setCellValue('D'.$row, 'QTY / PACKAGE');
$sheet->setCellValue('E'.$row, 'REMARK');

$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($style_bold);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($style_center);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($style_border);

$row++;

$total = 0;
$no = 1;

/* =========================================
   DETAIL LOOP
========================================= */

foreach($q_detail as $d){

    $sheet->setCellValue('A'.$row, $no);

    $sheet->setCellValue('B'.$row, $d->nm_barang);

    $sheet->setCellValue('C'.$row, $d->jumlah);

    $sheet->setCellValue(
        'D'.$row,
        $d->qty_packing.' / '.$d->packing
    );

    $sheet->setCellValue(
        'E'.$row,
        $header->material_name
    );

    $sheet->getStyle('A'.$row.':E'.$row)
          ->applyFromArray($style_border);

    $total += $d->jumlah;

    $row++;
    $no++;
}

/* =========================================
   TOTAL
========================================= */

$sheet->mergeCells('A'.$row.':B'.$row);

$sheet->setCellValue('A'.$row, 'TOTAL');

$sheet->setCellValue('C'.$row, $total);

$sheet->setCellValue(
    'D'.$row,
    $header->qty_packing.' PALLET'
);

$sheet->getStyle('A'.$row.':E'.$row)
      ->applyFromArray($style_bold);

$sheet->getStyle('A'.$row.':E'.$row)
      ->applyFromArray($style_border);

$row += 3;

/* =========================================
   SIGNATURE
========================================= */

$sheet->setCellValue('A'.$row, 'Prepared By');
$sheet->setCellValue('C'.$row, 'Approved By');
$sheet->setCellValue('E'.$row, 'Received By');

$sheet->getStyle('A'.$row.':E'.$row)
      ->applyFromArray($style_bold);

$row += 4;

$sheet->setCellValue('A'.$row, '________________');
$sheet->setCellValue('C'.$row, '________________');
$sheet->setCellValue('E'.$row, '________________');

/* =========================================
   AUTO WIDTH
========================================= */

foreach(range('A','E') as $col){

    $sheet->getColumnDimension($col)
          ->setAutoSize(true);

}

/* =========================================
   OUTPUT
========================================= */

$filename = "PACKING_LIST_".date('Ymd_His').".xls";

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter(
    $objPHPExcel,
    'Excel5'
);

$objWriter->save('php://output');

exit;

?>