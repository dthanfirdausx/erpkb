<?php
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
$customer  = $_GET['customer'];

$where = "";

// ==================
// FILTER TANGGAL
// ==================
if(!empty($tgl_awal) && !empty($tgl_akhir)){
    $where .= " AND a.invoice_date BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

// ==================
// FILTER CUSTOMER
// ==================
if($customer != 'all'){
    $where .= " AND a.bill_to = '$customer'";
}

// ==================
// QUERY
// ==================
$query = $db->query("

SELECT

    a.id_sales,
    a.bill_to,
    a.ship_to,
    a.invoice_date,
    a.invoice_no,
    a.nopo,
    a.term,
    a.valuta,
    a.ship_date,
    a.no_do,
    a.tax,

    p.nama AS nama_customer,

    d.kd_barang,
    d.nm_barang,
    d.qty,
    d.harga,
    d.nilai,
    d.unit

FROM sales_invoice a

LEFT JOIN penerima p
    ON p.kode_penerima = a.bill_to

LEFT JOIN sales_invoice_detail d
    ON a.id_sales = d.id_sales

WHERE 1=1
$where

ORDER BY a.invoice_date DESC,
         a.invoice_no ASC

");

// ==================
// EXCEL
// ==================
$objPHPExcel = new PHPExcel();

$sheet = $objPHPExcel->setActiveSheetIndex(0);

// ==================
// JUDUL
// ==================
$sheet->setCellValue('A1', 'LAPORAN SALES INVOICE');

$sheet->mergeCells('A1:O1');

$sheet->getStyle('A1')->getFont()
      ->setBold(true)
      ->setSize(14);

$sheet->getStyle('A1')->getAlignment()
      ->setHorizontal(
          PHPExcel_Style_Alignment::HORIZONTAL_CENTER
      );

$sheet->setCellValue(
    'A2',
    'Periode: '.$tgl_awal.' s/d '.$tgl_akhir
);

$sheet->mergeCells('A2:O2');

$sheet->getStyle('A2')->getAlignment()
      ->setHorizontal(
          PHPExcel_Style_Alignment::HORIZONTAL_CENTER
      );

// ==================
// HEADER
// ==================
$headers = [

  'A4' => 'Invoice No',
  'B4' => 'Invoice Date',
  'C4' => 'Customer',
  'D4' => 'Ship To',
  'E4' => 'PO No',
  'F4' => 'Term',
  'G4' => 'Currency',
  'H4' => 'Ship Date',
  'I4' => 'No DO',
  'J4' => 'Tax',

  'K4' => 'Kode Barang',
  'L4' => 'Nama Barang',
  'M4' => 'Qty',
  'N4' => 'Harga',
  'O4' => 'Nilai'

];

foreach($headers as $cell => $value){
    $sheet->setCellValue($cell, $value);
}

$sheet->getStyle('A4:O4')
      ->getFont()
      ->setBold(true);

$sheet->getStyle('A4:O4')
      ->getAlignment()
      ->setHorizontal(
          PHPExcel_Style_Alignment::HORIZONTAL_CENTER
      );

// ==================
// DATA
// ==================
$rowNum = 5;

$total_qty   = 0;
$total_nilai = 0;

$query->setFetchMode(PDO::FETCH_OBJ);

$last_invoice = '';

foreach ($query as $row) {

    // ==========================================
    // HEADER INVOICE HANYA SEKALI
    // ==========================================
    if($last_invoice != $row->id_sales){

        $sheet->setCellValue(
            "A".$rowNum,
            $row->invoice_no
        );

        $sheet->setCellValue(
            "B".$rowNum,
            $row->invoice_date
        );

        $sheet->setCellValue(
            "C".$rowNum,
            $row->nama_customer
        );

        $sheet->setCellValue(
            "D".$rowNum,
            $row->ship_to
        );

        $sheet->setCellValue(
            "E".$rowNum,
            $row->nopo
        );

        $sheet->setCellValue(
            "F".$rowNum,
            $row->term
        );

        $sheet->setCellValue(
            "G".$rowNum,
            $row->valuta
        );

        $sheet->setCellValue(
            "H".$rowNum,
            $row->ship_date
        );

        $sheet->setCellValue(
            "I".$rowNum,
            $row->no_do
        );

        $sheet->setCellValue(
            "J".$rowNum,
            $row->tax
        );

        // bold invoice header
        $sheet->getStyle(
            "A".$rowNum.":J".$rowNum
        )->getFont()->setBold(true);
    }

    // ==========================================
    // DETAIL BARANG
    // ==========================================
    $sheet->setCellValue(
        "K".$rowNum,
        $row->kd_barang
    );

    $sheet->setCellValue(
        "L".$rowNum,
        $row->nm_barang
    );

    $sheet->setCellValue(
        "M".$rowNum,
        (float)$row->qty
    );

    $sheet->setCellValue(
        "N".$rowNum,
        (float)$row->harga
    );

    $sheet->setCellValue(
        "O".$rowNum,
        (float)$row->nilai
    );

    // ==================
    // TOTAL
    // ==================
    $total_qty += (float)$row->qty;

    $total_nilai += (float)$row->nilai;

    $last_invoice = $row->id_sales;

    $rowNum++;
}

// ==================
// TOTAL
// ==================
$sheet->setCellValue(
    "L".$rowNum,
    "TOTAL"
);

$sheet->setCellValue(
    "M".$rowNum,
    $total_qty
);

$sheet->setCellValue(
    "O".$rowNum,
    $total_nilai
);

$sheet->getStyle(
    "L".$rowNum.":O".$rowNum
)->getFont()->setBold(true);

// ==================
// FORMAT NUMBER
// ==================
$lastRow = $rowNum;

$sheet->getStyle("M5:M".$lastRow)
      ->getNumberFormat()
      ->setFormatCode('#,##0.0000');

$sheet->getStyle("N5:N".$lastRow)
      ->getNumberFormat()
      ->setFormatCode('#,##0.00');

$sheet->getStyle("O5:O".$lastRow)
      ->getNumberFormat()
      ->setFormatCode('#,##0.00');

// ==================
// BORDER
// ==================
$sheet->getStyle("A4:O".$lastRow)
      ->applyFromArray([
          'borders' => [
              'allborders' => [
                  'style' =>
                  PHPExcel_Style_Border::BORDER_THIN
              ]
          ]
      ]);

// ==================
// AUTO SIZE
// ==================
foreach(range('A','O') as $col){
    $sheet->getColumnDimension($col)
          ->setAutoSize(true);
}

// ==================
// FREEZE
// ==================
$sheet->freezePane('A5');

// ==================
// TITLE
// ==================
$sheet->setTitle("Sales Invoice");

// ==================
// OUTPUT
// ==================
header('Content-Type: application/vnd.ms-excel');

header(
    'Content-Disposition: attachment;filename="Sales_Invoice.xls"'
);

$objWriter = PHPExcel_IOFactory::createWriter(
    $objPHPExcel,
    'Excel5'
);

$objWriter->save('php://output');

exit;

break;

  case "get_nomor":

$tgl = $_POST['tgl'];

$tahun = date('Y', strtotime($tgl));
$bulan = date('m', strtotime($tgl));
$nomor = generate_no_sales_infoice($tahun,$bulan);

echo json_encode([
  "nomor" => $nomor
]);

break;

  case "get_detail_do":

    $q = $db->query("
    SELECT 
      sj.id,
      sj.no_surat_jalan,
      sj.tgl_surat_jalan,
      sj.kode_penerima,
      sj.no_po,
      sj.no_sales_order,
      s.tax,
      s.currency
    FROM surat_jalan sj
    join sales_order s on s.no_sales_order=sj.no_sales_order
    WHERE id = ?
    ", array($_POST['id_sj']));

    $row = $q->fetch(PDO::FETCH_OBJ); 

    echo json_encode([
      "kode_penerima" => $row->kode_penerima,
      "no_po" => $row->no_po,
      "no_sales_order" => $row->no_sales_order,
      "tgl_surat_jalan" => $row->tgl_surat_jalan,
      "tax" => $row->tax,
      "currency" => $row->currency
    ]);

break;

case "get_so":

$id_sj = $_POST['id_sj'];

$q = $db->query("
SELECT 
  d.kode_barang,
  d.nama_barang,
  d.qty_kirim,
  d.satuan,
  b.nm_barang,
  sd.price
FROM surat_jalan_detail d
LEFT JOIN sales_order_detail sd 
  ON sd.id_detail = d.id_sales_order_detail
  left join barang b on b.kd_barang=d.kode_barang
WHERE d.surat_jalan_id = ?
", array($id_sj));

$no = 1;
$total_qty   = 0;
$total_nilai = 0;

echo '
<div class="col-lg-12">

<table class="table">

<thead>
<tr>

<th style="width:50px;text-align:center">
  <a style="cursor:pointer;" onclick="add_baris()">
    <i class="fa fa-plus"></i>
  </a>
</th>

<th>Kode Barang</th>
<th>Unit</th>
<th>Qty</th>
<th>Price</th>
<th>Amount</th>
<th>Material Number</th>
<th>Material Description</th>

</tr>
</thead>

<tbody id="isi_tabel">';

foreach($q as $r){

  $nilai = $r->qty_kirim * $r->price;

  // TOTAL
  $total_qty   += $r->qty_kirim;
  $total_nilai += $nilai;

  echo '

<tr id="baris_'.$no.'">

  <td style="text-align:center">
    <a style="cursor:pointer;" onclick="hapus_baris(\''.$no.'\')">
      <i class="fa fa-trash-o" style="font-size:25px;"></i>
    </a>
  </td>

  <!-- KODE BARANG -->
  <td>

    <input type="text"
           id="form_kode_'.$no.'"
           placeholder="Kode Barang"
           class="form-control"
           name="kode[]"
           value="'.$r->kode_barang.' '.$r->nm_barang.'"
           readonly>
 
    <input type="hidden"
           name="kode_input[]"
           id="kode_input_'.$no.'"
           value="'.$r->kode_barang.'">

  </td>

  <!-- UNIT -->
  <td>

    <input type="text"
           id="form_unit_'.$no.'"
           class="form-control"
           name="unit[]"
           value="'.$r->satuan.'"
           >

  </td>

  <!-- QTY -->
  <td>

    <input type="number"
           onkeyup="sum_nilai(this.value,\''.$no.'\')"
           id="form_qty_'.$no.'"
           class="form-control"
           name="jumlah[]"
           value="'.number_format($r->qty_kirim,4,'.','').'"
           style="text-align:right;">

  </td>

  <!-- PRICE -->
  <td>

    <input type="number"
           onkeyup="sum_nilai(this.value,\''.$no.'\')"
           id="form_harga_'.$no.'"
           class="form-control"
           name="harga[]"
           value="'.number_format($r->price,2,'.','').'"
           style="text-align:right;">

  </td>

  <!-- AMOUNT -->
  <td>

    <input type="text"
           id="form_nilai_'.$no.'"
           class="form-control"
           name="nilai[]"
           value="'.number_format($nilai,2,'.','').'"
           style="text-align:right;"
           readonly>

  </td>

  <!-- MATERIAL NUMBER -->
  <td>

    <input type="text"
           id="form_material_'.$no.'"
           class="form-control"
           name="material_number[]"
          >

  </td>

  <!-- MATERIAL DESCRIPTION -->
  <td>

    <input type="text"
           id="form_material_desc_'.$no.'"
           class="form-control"
           name="material_description[]"
          >

  </td>

</tr>';

  $no++;
}


// ================= TOTAL =================

echo '

</tbody>

<tfoot>

<tr style="font-weight:bold;background:#f2f2f2;">

  <td colspan="3" style="text-align:right">
    TOTAL
  </td>

  <!-- TOTAL QTY -->
  <td>

    <input type="text"
           id="form_total_qty"
           class="form-control"
           value="'.number_format($total_qty,4,'.','').'"
           style="text-align:right;"
           readonly>

  </td>

  <!-- TOTAL PRICE -->
  <td>

    <input type="text"
           id="form_total_harga"
           class="form-control"
           style="text-align:right;"
           readonly>

  </td>

  <!-- TOTAL AMOUNT -->
  <td>

    <input type="text"
           id="form_total_nilai"
           class="form-control"
           value="'.number_format($total_nilai,2,'.','').'"
           style="text-align:right;"
           readonly>

  </td>

  <td></td>
  <td></td>

</tr>

</tfoot>

</table>

</div>

<input type="hidden"
       id="jml"
       value="'.($no-1).'">';

break;

 

   case 'sync_acc':
    $curl = curl_init();
    $tgl = date("d/m/Y H:i:s");    
    $signature = get_signature($tgl);  
   // echo "$tgl == $signature";
   // $awal = $i * 100; 
    $q = $db->query("select s.*,pp.penerima as kode_penerima,ss.kode_penerima as kode_penerima2,
p.nama,p.alamat,s.valuta from sales_invoice s 
join pengeluaran pp on pp.no_sj=s.no_do 
join sales_order ss on ss.no_sales_order=pp.no_sales_order
join penerima p on pp.penerima=p.kode_penerima"); 
        foreach ($q as $v) { 

           $pajak = 'false';
            if ($v->tax=='1') {
              $pajak = 'true'; 
            } 
 
          $qp = $db->query("select kode_penerima,nama,email,customer_status from penerima where kode_penerima='$v->kode_penerima' ");
            foreach ($qp as $vp) { 
                $datap = [
                'name'       => $vp->nama,
                'transDate'  => date('d/m/Y'),  
                'customerNo'   => $vp->kode_penerima,
                'categoryName' => $vp->customer_status
              ]; 
            }    
            // print_r($data);
            // die();
          $json_datap = json_encode( $datap);
         // print_r($json_data);
            curl_setopt_array($curl, array(
              CURLOPT_URL => url_accurate.'customer/save.do',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $json_datap,
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Api-Timestamp: '.$tgl,
                'X-Api-Signature: '.$signature, 
                'Authorization: Bearer '.BEARER_TOKEN
              ),
            ));

            curl_exec($curl);


            $qq = $db->query("select kd_barang as kode,qty as jumlah, harga from sales_invoice_detail  where id_sales='$v->id_sales' ");
            foreach ($qq as $kk) {
              $data2[] = [ 
              'itemNo' =>  $kk->kode,
              'quantity' => $kk->jumlah, 
              'unitPrice' => $kk->harga,
            //  'salesOrderNumber' => $v->no_sales_order,
              'deliveryOrderNumber' => $v->no_do,
              'useTax1' => $pajak, 
              'warehouseName' => 'Gudang PDPLB',
             // 'salesOrderNumber' => $v->no_sales_order
              ]; 
               $qb = $db->query("select kd_barang, kategori,nm_barang,satuan from barang where kd_barang='$kk->kode' ");
                foreach ($qb as $vb) {
                    $datab = [
                    'no' => $vb->kd_barang,
                    'itemCategoryName' => $vb->kategori,
                    'itemType'  => "INVENTORY",   
                    'name'   => $vb->nm_barang,
                    'unit1Name' => $vb->satuan  
                  ];
                }    
               //  print_r($datab);
                // die();
                $json_datab = json_encode($datab);  
                // print_r($json_data);
                curl_setopt_array($curl, array( 
                  CURLOPT_URL => url_accurate.'item/save.do', 
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS => $json_datab,
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'X-Api-Timestamp: '.$tgl,
                    'X-Api-Signature: '.$signature, 
                    'Authorization: Bearer '.BEARER_TOKEN
                  ),
                )); 

                $response = curl_exec($curl); 
              //  echo $response;
            }
            $data[] = [
              'customerNo'        => $v->kode_penerima,
              'toAddress'       => strip_tags($v->alamat),
              'transDate'      =>  date("d/m/Y",strtotime($v->invoice_date)),
              'number'          => $v->no_sales_invoice, 
              'poNumber' => $v->nopo, 
              'currencyCode' =>  $v->valuta,
              'taxable'=> $pajak,
              'paymentTermName'  => $v->term,  
              'detailItem'      => $data2 
            ];
             unset($data2); 
            //$data[]['detailItem'] = $data2; 
            

        }   
 
 
      // print_r($data); 
        // die();
    $json_data = json_encode(['data' => $data]);  
    // print_r($json_data);
    // die();
    // echo "<pre>"; 
    //  print_r($json_data); 
    // // print_r($json_encode);
    //  die();
    curl_setopt_array($curl, array(
          CURLOPT_URL            => url_accurate.'sales-invoice/bulk-save.do', 
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => '',
          CURLOPT_MAXREDIRS      => 10, 
          CURLOPT_TIMEOUT        => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => 'POST',
          CURLOPT_POSTFIELDS     => $json_data,
          CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/json',
            'X-Api-Timestamp: '.$tgl,
            'X-Api-Signature: '.$signature, 
            'Authorization: Bearer '.BEARER_TOKEN
          ),
        ));
    $response = curl_exec($curl);
    
      
   // }
      curl_close($curl);
    echo $response;
 
    break;
  case "in":
    
  
  $qp = $db->fetch("select no_surat_jalan from surat_jalan where id='".$_POST["no_do"]."' ");
  $no_do = $qp->no_surat_jalan;

  
  $data = array(
      "bill_to" => $_POST["bill_to"],
      "ship_to" => $_POST["ship_to"],
      "invoice_date" => $_POST["invoice_date"],
      "invoice_no" => $_POST["invoice_no"],
      "no_sales_order" => $_POST["no_sales_order"],
      "no_sales_invoice" => $_POST["no_sales_invoice"],
      "nopo" => $_POST["nopo"], 
      "ttd" => $_POST["ttd"],
      "term" => $_POST["term"],
      "valuta" => $_POST["valuta"],
      "ship_date" => $_POST["ship_date"],
      "catatan" => $_POST["catatan"],
      "no_do" => $no_do,
      "bank_detail" => $_POST["bank_detail"],
      "tax" => $_POST["tax"],
  );
   
   
  
   
    $in = $db->insert("sales_invoice",$data); 

    $id_sales = $db->last_insert_id();
    // $db->query("delete from pemasukan_detail where no_bpb='$no_bpb' ");
   $no=1;
   foreach ($_POST['kode_input'] as $key => $value) {
       $barang = att_barang($_POST['kode_input'][$key]); 
      $data_detail = array( 
                    'id_sales' => $id_sales , 
                    'unit' => $_POST['unit'][$key],
                    'kd_barang' => $_POST['kode_input'][$key],
                    'nm_barang' => $barang->nm_barang,
                    'qty' => formatNumber($_POST["jumlah"][$key]), 
                    'harga' => formatNumber($_POST["harga"][$key]), 
                    'nilai' => formatNumber($_POST["nilai"][$key]),
                    'material_number' => $_POST['material_number'][$key],
                    'material_description' => $_POST['material_description'][$key],
                    
                   // 'unit' => $_POST['unit'][$key], 
                   
                  );
     // print_r($data_detail);   

     
       // update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);
        $db->insert("sales_invoice_detail",$data_detail); 

      $no++;
   }

    $db->query("update sales_invoice set tax='0' where tax is null");
    $db->query("update `sales_invoice_detail` set nilai = qty*harga");
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("sales_invoice","id_sales",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sales_invoice","id_sales",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "bill_to" => $_POST["bill_to"],
      "ship_to" => $_POST["ship_to"],
       "no_sales_invoice" => $_POST["no_sales_invoice"],
      "invoice_date" => $_POST["invoice_date"],
      "invoice_no" => $_POST["invoice_no"],
      "nopo" => $_POST["nopo"],
       "catatan" => $_POST["catatan"],
      "term" => $_POST["term"],
       "no_sales_order" => $_POST["no_sales_order"],
      "ttd" => $_POST["ttd"],
      "valuta" => $_POST["valuta"],
      "ship_date" => $_POST["ship_date"],
      "no_do" => $_POST["no_do"],
      "bank_detail" => $_POST["bank_detail"],
      "tax" => $_POST["tax"],
   ); 
   
    
   

    $id_sales= $_POST['id_sales'];
    
    $up = $db->update("sales_invoice",$data,"id_sales",$_POST["id"]);
    $db->query("delete from sales_invoice_detail where id_sales='".$_POST["id"]."' ");
      $no=1;
   foreach ($_POST['kode'] as $key => $value) {
       $barang = att_barang($_POST['kode_input'][$key]); 
       $data_detail = array(
                    'id_sales' => $id_sales ,                     
                    'kd_barang' => $_POST['kode_input'][$key],
                      'unit' => $_POST['unit'][$key],
                    'nm_barang' => $barang->nm_barang,
                    'qty' => formatNumber($_POST["jumlah"][$key]),
                    'harga' => formatNumber($_POST["harga"][$key]), 
                    'nilai' => (formatNumber($_POST["jumlah"][$key]) *  formatNumber($_POST["harga"][$key])),
                      'material_number' => $_POST['material_number'][$key],
                    'material_description' => $_POST['material_description'][$key],
                   
                  );
       // update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);
        $db->insert("sales_invoice_detail",$data_detail); 
        echo $db->getErrorMessage();

      $no++;
   }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>