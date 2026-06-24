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
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();

function si_json_response($status, $message = '', $extra = array()) {
  $payload = array_merge(array('status' => $status, 'error_message' => $message), $extra);
  echo json_encode(array($payload));
  exit;
}
function si_user() {
  return isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : 'system');
}
function si_number($value) {
  if (function_exists('formatNumber')) return formatNumber($value);
  $value = str_replace(array('.', ','), array('', '.'), (string)$value);
  return (float)$value;
}
function si_term_days($term) {
  return preg_match('/(\d+)/', (string)$term, $m) ? (int)$m[1] : 0;
}
function si_due_date($invoiceDate, $term) {
  $days = si_term_days($term);
  if (!$invoiceDate || $days <= 0) return null;
  return date('Y-m-d', strtotime($invoiceDate.' +'.$days.' days'));
}
function si_tax_rate($tax) {
  return ((string)$tax === '1') ? 11 : 0;
}
function si_post_sales_invoice_journal($invoiceId) {
  global $db;
  if (!function_exists('finance_post_journal')) return 'OK';
  $h = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? LIMIT 1", array($invoiceId));
  if (!$h) return 'Invoice tidak ditemukan untuk jurnal otomatis.';
  $net = (float)$h->net_amount;
  if ($net <= 0) {
    $row = $db->fetch("SELECT COALESCE(SUM(nilai),0) net_amount FROM sales_invoice_detail WHERE id_sales=?", array($invoiceId));
    $net = $row ? (float)$row->net_amount : 0;
  }
  $tax = (float)$h->tax_amount;
  $gross = (float)$h->gross_amount;
  if ($gross <= 0) $gross = $net + $tax;
  if ($gross <= 0) return 'Tidak ada nilai invoice yang bisa dijurnal.';
  $valuta = $h->valuta ?: 'IDR';
  $lines = array(
    array('no_rek'=>'12199','debet'=>$gross,'kredit'=>0,'line_text'=>'Customer receivable '.$h->no_sales_invoice,'expected_category'=>'aset','valuta'=>$valuta,'kurs'=>1),
    array('no_rek'=>'41100','debet'=>0,'kredit'=>$net,'line_text'=>'Sales revenue '.$h->no_sales_invoice,'expected_category'=>'pendapatan','valuta'=>$valuta,'kurs'=>1)
  );
  if ($tax > 0) {
    $lines[] = array('no_rek'=>'21807','debet'=>0,'kredit'=>$tax,'line_text'=>'Output VAT '.$h->no_sales_invoice,'expected_category'=>'kewajiban','valuta'=>$valuta,'kurs'=>1);
  }
  $res = finance_post_journal(array(
    'document_type'=>'DR',
    'posting_status'=>'POSTED',
    'tgl_jurnal'=>$h->posting_date ?: $h->invoice_date,
    'ket'=>'AUTO: Billing customer invoice '.$h->no_sales_invoice,
    'no_bukti'=>$h->no_sales_invoice,
    'source_module'=>'SALES_INVOICE',
    'source_document_no'=>$h->no_sales_invoice,
    'valuta'=>$valuta,
    'kurs'=>1,
    'lines'=>$lines
  ));
  return $res === true ? 'OK' : $res;
}
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
$sheet->setTitle(erp_export_sheet_title('Sales Invoice'));

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
      COALESCE(sj.posting_date,sj.tgl_surat_jalan,sj.document_date) tgl_surat_jalan,
      COALESCE(NULLIF(sj.bill_to_party,''),NULLIF(sj.ship_to_party,''),NULLIF(sj.kode_penerima,''),s.kode_penerima) kode_penerima,
      COALESCE(NULLIF(sj.no_po,''),s.no_po) no_po,
      sj.no_sales_order,
      s.tax,
      s.currency,
      s.term,
      s.delivery_term,
      p.nama customer_name
    FROM surat_jalan sj
    LEFT JOIN sales_order s on s.no_sales_order=sj.no_sales_order
    LEFT JOIN penerima p ON p.kode_penerima=COALESCE(NULLIF(sj.bill_to_party,''),NULLIF(sj.ship_to_party,''),NULLIF(sj.kode_penerima,''),s.kode_penerima)
    WHERE sj.id = ?
    ", array($_POST['id_sj']));

    $row = $q->fetch(PDO::FETCH_OBJ); 
    if (!$row) {
      echo json_encode(array('status'=>'error','error_message'=>'Surat Jalan tidak ditemukan.'));
      break;
    }

    echo json_encode([
      "kode_penerima" => $row->kode_penerima,
      "customer_name" => $row->customer_name,
      "no_po" => $row->no_po,
      "no_sales_order" => $row->no_sales_order,
      "tgl_surat_jalan" => $row->tgl_surat_jalan,
      "tax" => ($row->tax === 'include' || $row->tax === '1') ? '1' : '0',
      "currency" => $row->currency,
      "term" => is_numeric($row->term) ? 'TT '.$row->term : $row->term
    ]);

break;

case "get_so":

$id_sj = $_POST['id_sj'];

$q = $db->query("
SELECT 
  d.id AS surat_jalan_detail_id,
  d.id_sales_order_detail,
  d.line_no,
  d.kode_barang,
  d.nama_barang,
  d.material_code,
  d.material_name,
  d.qty_kirim,
  d.satuan,
  b.nm_barang,
  sd.price,
  '' AS material_number,
  sd.ket
FROM surat_jalan_detail d
LEFT JOIN sales_order_detail sd 
  ON sd.id_detail = d.id_sales_order_detail
  left join barang b on b.kd_barang=COALESCE(NULLIF(d.material_code,''),d.kode_barang)
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

<th>Kode Barang</th>
<th>Unit</th>
<th>'.sd_h('sales_qty', 'Qty').'</th>
<th>'.sd_h('sales_price', 'Price').'</th>
<th>'.sd_h('sales_amount', 'Amount').'</th>
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

  <!-- KODE BARANG -->
  <td>

    <input type="text"
           id="form_kode_'.$no.'"
           placeholder="Kode Barang"
           class="form-control"
           name="kode[]"
           value="'.($r->material_code ?: $r->kode_barang).' '.($r->material_name ?: $r->nm_barang ?: $r->nama_barang).'"
           readonly>
 
    <input type="hidden"
           name="kode_input[]"
           id="kode_input_'.$no.'"
           value="'.($r->material_code ?: $r->kode_barang).'">
    <input type="hidden" name="surat_jalan_detail_id[]" value="'.(int)$r->surat_jalan_detail_id.'">
    <input type="hidden" name="sales_order_detail_id[]" value="'.(int)$r->id_sales_order_detail.'">
    <input type="hidden" name="line_no[]" value="'.(int)$r->line_no.'">

  </td>

  <!-- UNIT -->
  <td>

    <input type="text"
           id="form_unit_'.$no.'"
           class="form-control"
           name="unit[]"
           value="'.$r->satuan.'"
           readonly>

  </td>

  <!-- QTY -->
  <td>

    <input type="number"
           onkeyup="sum_nilai(this.value,\''.$no.'\')"
           id="form_qty_'.$no.'"
           class="form-control"
           name="jumlah[]"
           value="'.number_format($r->qty_kirim,4,'.','').'"
           style="text-align:right;" readonly>

  </td>

  <!-- PRICE -->
  <td>

    <input type="number"
           onkeyup="sum_nilai(this.value,\''.$no.'\')"
           id="form_harga_'.$no.'"
           class="form-control"
           name="harga[]"
           value="'.number_format($r->price,2,'.','').'"
           style="text-align:right;" readonly>

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
           value="'.htmlspecialchars((string)$r->material_number,ENT_QUOTES,'UTF-8').'"
           readonly>

  </td>

  <!-- MATERIAL DESCRIPTION -->
  <td>

    <input type="text"
           id="form_material_desc_'.$no.'"
           class="form-control"
           name="material_description[]"
           value="'.htmlspecialchars((string)$r->ket,ENT_QUOTES,'UTF-8').'"
           readonly>

  </td>

</tr>';

  $no++;
}


// ================= TOTAL =================

echo '

</tbody>

<tfoot>

<tr style="font-weight:bold;background:#f2f2f2;">

  <td colspan="2" style="text-align:right">
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
    $sjId = isset($_POST["no_do"]) ? (int)$_POST["no_do"] : 0;
    $sj = $db->fetch("SELECT sj.*,so.tax so_tax,so.currency,so.term so_term,so.no_po so_no_po,so.kode_penerima so_customer
                      FROM surat_jalan sj
                      LEFT JOIN sales_order so ON so.no_sales_order=sj.no_sales_order
                      WHERE sj.id=? LIMIT 1", array($sjId));
    if (!$sj) si_json_response('error', 'Surat Jalan tidak ditemukan.');
    if ($sj->status === 'dibatalkan') si_json_response('error', 'Surat Jalan dibatalkan tidak bisa dibuat invoice.');
    $exists = $db->fetch("SELECT id_sales,no_sales_invoice FROM sales_invoice WHERE no_do=? AND billing_status<>'CANCELLED' LIMIT 1", array($sj->no_surat_jalan));
    if ($exists) si_json_response('error', 'Surat Jalan '.$sj->no_surat_jalan.' sudah dibuat invoice '.$exists->no_sales_invoice.'.');
    if (empty($_POST['kode_input']) || !is_array($_POST['kode_input'])) si_json_response('error', 'Item invoice wajib berasal dari Surat Jalan.');

    $tax = isset($_POST["tax"]) ? $_POST["tax"] : (($sj->so_tax === 'include' || $sj->so_tax === '1') ? '1' : '0');
    $taxRate = si_tax_rate($tax);
    $subtotal = 0; $taxAmount = 0; $grossAmount = 0;
    foreach ($_POST['kode_input'] as $key => $value) {
      $qty = si_number($_POST["jumlah"][$key]);
      $price = si_number($_POST["harga"][$key]);
      if ($value === '' || $qty <= 0) si_json_response('error', 'Material dan qty invoice wajib valid.');
      $lineNet = round($qty * $price, 2);
      $subtotal += $lineNet;
    }
    $taxAmount = round($subtotal * ($taxRate/100), 2);
    $grossAmount = $subtotal + $taxAmount;
    $invoiceDate = $_POST["invoice_date"] ?: date('Y-m-d');
    $term = $_POST["term"] ?: $sj->so_term;
    $invoiceNo = trim($_POST["no_sales_invoice"]);
    if ($invoiceNo === '') si_json_response('error', 'Nomor Sales Invoice wajib diisi.');
    if ($db->fetch("SELECT id_sales FROM sales_invoice WHERE no_sales_invoice=? LIMIT 1", array($invoiceNo))) si_json_response('error', 'Nomor Sales Invoice sudah dipakai.');

    $data = array(
      "billing_type" => isset($_POST["billing_type"]) ? $_POST["billing_type"] : 'F2',
      "bill_to" => $_POST["bill_to"],
      "ship_to" => $_POST["ship_to"],
      "invoice_date" => $invoiceDate,
      "posting_date" => $invoiceDate,
      "invoice_no" => $_POST["invoice_no"],
      "no_sales_order" => $_POST["no_sales_order"] ?: $sj->no_sales_order,
      "no_sales_invoice" => $invoiceNo,
      "nopo" => $_POST["nopo"] ?: $sj->no_po ?: $sj->so_no_po,
      "ttd" => $_POST["ttd"],
      "term" => $term,
      "due_date" => si_due_date($invoiceDate, $term),
      "valuta" => $_POST["valuta"] ?: $sj->currency,
      "ship_date" => $_POST["ship_date"] ?: ($sj->posting_date ?: $sj->tgl_surat_jalan),
      "catatan" => $_POST["catatan"],
      "no_do" => $sj->no_surat_jalan,
      "bank_detail" => $_POST["bank_detail"],
      "tax" => $tax,
      "tax_code" => $tax === '1' ? 'PPN11' : 'NON_TAX',
      "tax_rate" => $taxRate,
      "net_amount" => $subtotal,
      "tax_amount" => $taxAmount,
      "gross_amount" => $grossAmount,
      "billing_status" => "POSTED",
      "created_by" => si_user(),
      "posted_by" => si_user(),
      "posted_at" => date('Y-m-d H:i:s')
    );

    if (!$db->insert("sales_invoice",$data)) si_json_response('error', $db->getErrorMessage() ?: sd_t('sales_invoice_save_failed', 'Invoice failed to save.'));
    $id_sales = $db->last_insert_id();
    foreach ($_POST['kode_input'] as $key => $value) {
      $barang = att_barang($_POST['kode_input'][$key]);
      $qty = si_number($_POST["jumlah"][$key]);
      $price = si_number($_POST["harga"][$key]);
      $lineNet = round($qty * $price, 2);
      $lineTax = round($lineNet * ($taxRate/100), 2);
      $data_detail = array(
        'id_sales' => $id_sales,
        'sales_order_detail_id' => isset($_POST['sales_order_detail_id'][$key]) ? (int)$_POST['sales_order_detail_id'][$key] : null,
        'surat_jalan_detail_id' => isset($_POST['surat_jalan_detail_id'][$key]) ? (int)$_POST['surat_jalan_detail_id'][$key] : null,
        'line_no' => isset($_POST['line_no'][$key]) ? (int)$_POST['line_no'][$key] : ($key+1)*10,
        'unit' => $_POST['unit'][$key],
        'kd_barang' => $_POST['kode_input'][$key],
        'nm_barang' => $barang ? $barang->nm_barang : '',
        'qty' => $qty,
        'harga' => $price,
        'nilai' => $lineNet,
        'tax_code' => $tax === '1' ? 'PPN11' : 'NON_TAX',
        'tax_rate' => $taxRate,
        'tax_amount' => $lineTax,
        'gross_amount' => $lineNet + $lineTax,
        'material_number' => $_POST['material_number'][$key],
        'material_description' => $_POST['material_description'][$key],
      );
      if (!$db->insert("sales_invoice_detail",$data_detail)) si_json_response('error', $db->getErrorMessage() ?: sd_t('sales_invoice_detail_save_failed', 'Invoice detail failed to save.'));
    }
    $db->query("UPDATE surat_jalan SET no_invoice=? WHERE id=?", array($invoiceNo,$sjId));
    $journalResult = si_post_sales_invoice_journal($id_sales);
    if ($journalResult !== 'OK') si_json_response('error', 'Invoice tersimpan tetapi jurnal otomatis gagal: '.$journalResult);
    if (function_exists('simpan_log')) simpan_log('User '.si_user().' posting Sales Invoice '.$invoiceNo.' dari Surat Jalan '.$sj->no_surat_jalan.' pada '.date('Y-m-d H:i:s'), si_user());
    action_response('');
    break;
  case "delete":
    $invoice = $db->fetch("SELECT billing_status,no_sales_invoice FROM sales_invoice WHERE id_sales=? LIMIT 1", array((int)$_GET["id"]));
    if ($invoice && $invoice->billing_status === 'POSTED') si_json_response('error', 'Invoice POSTED tidak boleh dihapus. Gunakan cancellation/reversal.');
    $db->delete("sales_invoice_detail","id_sales",$_GET["id"]);
    $db->delete("sales_invoice","id_sales",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
  case "cancel":
    $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $invoice = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? LIMIT 1", array($id));
    if (!$invoice) si_json_response('error', 'Invoice tidak ditemukan.');
    if ($invoice->billing_status !== 'POSTED') si_json_response('error', 'Hanya invoice POSTED yang bisa dicancel.');
    if ($reason === '') si_json_response('error', 'Alasan cancel wajib diisi.');
    $db->update('sales_invoice', array(
      'billing_status'=>'CANCELLED',
      'cancelled_by'=>si_user(),
      'cancelled_at'=>date('Y-m-d H:i:s'),
      'cancel_reason'=>$reason
    ), 'id_sales', $id);
    $db->query("UPDATE surat_jalan SET no_invoice=NULL WHERE no_invoice=?", array($invoice->no_sales_invoice));
    if (function_exists('accounting_reverse_auto_journal')) {
      $revNo = $invoice->no_sales_invoice.'-C';
      accounting_reverse_auto_journal($invoice->no_sales_invoice, $revNo, array('tgl_jurnal'=>date('Y-m-d'), 'ket'=>'Cancel Sales Invoice '.$invoice->no_sales_invoice));
    }
    if (function_exists('simpan_log')) simpan_log('User '.si_user().' cancel Sales Invoice '.$invoice->no_sales_invoice.' alasan '.$reason.' pada '.date('Y-m-d H:i:s'), si_user());
    si_json_response('good', '', array('invoice_no'=>$invoice->no_sales_invoice));
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $invoice = $db->fetch("SELECT billing_status FROM sales_invoice WHERE id_sales=? LIMIT 1", array((int)$id));
          if ($invoice && $invoice->billing_status === 'POSTED') continue;
          $db->delete("sales_invoice_detail","id_sales",$id);
          $db->delete("sales_invoice","id_sales",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    $current = $db->fetch("SELECT billing_status,no_sales_invoice FROM sales_invoice WHERE id_sales=? LIMIT 1", array((int)$_POST["id"]));
    if ($current && $current->billing_status === 'POSTED') si_json_response('error', 'Invoice POSTED tidak boleh diedit. Gunakan cancellation/reversal.');
    
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
