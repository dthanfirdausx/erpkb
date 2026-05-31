<?php
error_reporting(0); 
session_start();
include "../../inc/config.php";
session_check_json(); 
switch ($_GET["act"]) {

  case "excel":

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once "../../inc/lib/PHPExcel.php";

// ==================
// PARAMETER
// ==================
$tgl_awal  = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$supplier  = $_GET['supplier'];
$status    = $_GET['status'];

$where = "";

// filter tanggal
if(!empty($tgl_awal) && !empty($tgl_akhir)){
    $where .= " AND a.po_date BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

// supplier
if($supplier != 'all'){
    $where .= " AND a.supplier = '$supplier'";
}

// status
if($status != 'all'){
    $where .= " AND a.status_po = '$status'";
}

// ==================
// QUERY
// ==================

$query = $db->query("
SELECT 
  a.id as id_po,
  a.purchase_order_no,
  a.po_date,
  a.seller_name,
  a.seller_address,
  a.delivery_term,
  a.status,
  b.kode_barang,
  b.nama_barang,
  b.qty,
  b.harga,
  b.amount
FROM purchase_order a
LEFT JOIN purchase_order_detail b 
  ON a.purchase_order_no = b.po_no
WHERE 1=1 $where
ORDER BY a.id ASC
");

// ==================
// EXCEL
// ==================
$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->setActiveSheetIndex(0);

// ==================
// JUDUL
// ==================
$sheet->setCellValue('A1', 'LAPORAN PURCHASE ORDER');
$sheet->mergeCells('A1:J1');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Periode: '.$tgl_awal.' s/d '.$tgl_akhir);
$sheet->mergeCells('A2:J2');

$sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ==================
// HEADER
// ==================
$headers = [
  'A4' => 'No PO',
  'B4' => 'Tanggal',
  'C4' => 'Supplier',
  'D4' => 'Alamat',
  'E4' => 'Trade Term',
  'F4' => 'Status',
  'G4' => 'Kode Barang',
  'H4' => 'Nama Barang',
  'I4' => 'Qty',
  'J4' => 'Harga'
];

foreach($headers as $cell => $value){
    $sheet->setCellValue($cell, $value);
}

$sheet->getStyle('A4:J4')->getFont()->setBold(true);
$sheet->getStyle('A4:J4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ==================
// DATA
// ==================
$rowNum = 5;
$last_po = "";

$total_qty = 0;
$total_nilai = 0;



foreach ($query as $row) {
 
    // HEADER PO
    if($last_po != $row->id_po){

        $sheet->setCellValue("A".$rowNum, $row->purchase_order_no);
        $sheet->setCellValue("B".$rowNum, $row->po_date);
        $sheet->setCellValue("C".$rowNum, $row->seller_name);
        $sheet->setCellValue("D".$rowNum, $row->seller_address);
        $sheet->setCellValue("E".$rowNum, $row->delivery_term);
        $sheet->setCellValue("F".$rowNum, $row->status);

        $sheet->getStyle("A".$rowNum.":F".$rowNum)->getFont()->setBold(true);

        $rowNum++;
    }

    // DETAIL
    $sheet->setCellValue("G".$rowNum, $row->kode_barang);
    $sheet->setCellValue("H".$rowNum, $row->nama_barang);
    $sheet->setCellValue("I".$rowNum, (float)$row->qty);
    $sheet->setCellValue("J".$rowNum, (float)$row->price);

    $total_qty += (float)$row->qty;
    $total_nilai += (float)$row->total;

    $rowNum++;
    $last_po = $row->id_po;
}

// ==================
// TOTAL
// ==================
$sheet->setCellValue("H".$rowNum, "TOTAL");
$sheet->setCellValue("I".$rowNum, $total_qty);
$sheet->setCellValue("J".$rowNum, $total_nilai);

$sheet->getStyle("H".$rowNum.":J".$rowNum)->getFont()->setBold(true);

// ==================
// STYLE
// ==================
$lastRow = $rowNum;

$sheet->getStyle("I5:I".$lastRow)->getNumberFormat()->setFormatCode('#,##0.0000');
$sheet->getStyle("J5:J".$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

$sheet->getStyle("A4:J".$lastRow)->applyFromArray([
  'borders' => [
    'allborders' => [
      'style' => PHPExcel_Style_Border::BORDER_THIN
    ]
  ]
]);

foreach(range('A','J') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$sheet->freezePane('A5');

// ==================
// OUTPUT
// ==================
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Purchase_Order.xls"');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

exit;

break;

  case "ganti_no_po":
   $t = explode("-", $_POST['tgl']); 

   echo generate_po_no($t[0],$t[1]); 
    break;

  case "cari_vendor":
    $kode = $_POST['kode_pemasok'];
    $vendor = $db->fetch_single_row("pemasok","nama",$kode);

    if ($vendor) {
        echo json_encode([
            "success" => true,
            "data" => array( "kode_pemasok" => $vendor->kode_pemasok,
            "nama"    => $vendor->nama,
            "alamat"  => $vendor->alamat,
            "kota"    => $vendor->kota,
            "negara"  => $vendor->negara,
            "notelp"  => $vendor->notelp,
            "nofax"   => $vendor->nofax,
            "email"   => $vendor->email)
           
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
      break;


  case "in":
     $tg = explode("-", $_POST["po_date"]);

     $no_po = generate_po_no($tg[0],$tg[1]);
        $data = array(
      "purchase_order_no" => $no_po,
      "customer_id"       => $_POST["customer_id"],
      "po_date"              => $_POST["po_date"], 
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],
      "seller_code"       => $_POST["customer_id"],
      "seller_name"       => $_POST["seller_code"],
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
       "pajak"      => $_POST["tax"],
      "consignee_name"    => $_POST["consignee_name"],
      "consignee_address" => $_POST["consignee_address"],
      "consignee_phone"   => $_POST["consignee_phone"],
      "consignee_email"   => $_POST["consignee_email"],
   );

   // print_r($data);
   // die();

   $db->insert("purchase_order", $data);

    $data2 = array('nopo' => $no_po, 
                  'typepo' => '1',
                  'paymentstatus' => '0',
                  'valuta' => $_POST["currency"],
                  'tglpo' => $_POST["date"] ,
                  'kode_pemasok' => $_POST["customer_id"],
                  'pic' => $_POST["seller_pic"]);
   // $db->insert("po",$data2);
    
  // $po_id = $db->lastInsertId();

   // --- SIMPAN DETAIL ---
   if (!empty($_POST["kode"])) {
      foreach ($_POST["kode"] as $i => $kode) {
         $detail = array(
            "po_no"       => $no_po, 
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
            "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
            "ket"         => $_POST["ket"][$i],
         );
        // nopo tglpo kode  jumlah  unit  harga nilai ket 
         $detail2 = array(
              "nopo"       => $no_po, 
              "tglpo"       => $_POST["date"], 
              "kode"       => $_POST["kode"][$i], 
              "jumlah"       => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
              "unit"       => $_POST["unit"][$i],
              "nilai" => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])) * str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
              "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])), 
             // "ket" => 
         );
        //  $db->insert("po_detail", $detail2);
         $db->insert("purchase_order_detail", $detail);
      } 
   }
    action_response($db->getErrorMessage());
   // echo "string";
  break;
  case "delete":
    
    
    
    $db->delete("purchase_order","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal": 
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("purchase_order","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "purchase_order_no" => $_POST["purchase_order_no"],
      "customer_id"       => $_POST["customer_id"],
      "po_date"              => $_POST["po_date"], 
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],

      "seller_code"       => $_POST["customer_id"],
      "seller_name"       => $_POST["seller_code"],
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
      "pajak"      => $_POST["tax"],

      "consignee_name"    => $_POST["consignee_name"],
      "consignee_address" => $_POST["consignee_address"],
      "consignee_phone"   => $_POST["consignee_phone"],
      "consignee_email"   => $_POST["consignee_email"],
   );


   
    $data2 = array('nopo' => $_POST["purchase_order_no"], 
                  'tglpo' => $_POST["date"] ,
                  'typepo' => '1',
                  'valuta' => $_POST["currency"],
                  'paymentstatus' => '0',
                  'kode_pemasok' => $_POST["customer_id"],
                  'pic' => $_POST["seller_pic"]);
    

    
    
    $up = $db->update("purchase_order",$data,"purchase_order_no",$_POST["purchase_order_no"]);
   
    $db->query("delete from purchase_order_detail where po_no='".$_POST["purchase_order_no"]."'");
    // $db->query("delete from po_detail where nopo='".$_POST["purchase_order_no"]."'");
    // $db->query("delete from po where nopo='".$_POST["purchase_order_no"]."'");
   // $db->insert("po",$data2); 

     if (!empty($_POST["kode"])) {  
      foreach ($_POST["kode"] as $i => $kode) {
         $detail = array(
            "po_no"       => $_POST["purchase_order_no"], 
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
            "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
            "ket"         => $_POST["ket"][$i], 
         );
         $detail2 = array(
              "nopo"       => $_POST["purchase_order_no"], 
              "tglpo"       => $_POST["date"], 
              "kode"       => $_POST["kode"][$i], 
              "jumlah"       => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
              "unit"       => $_POST["unit"][$i],
               "nilai" => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])) * str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
              "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])), 
             // "ket" => 
         );
       //   $db->insert("po_detail", $detail2);
         $db->insert("purchase_order_detail", $detail); 
      } 
   }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>