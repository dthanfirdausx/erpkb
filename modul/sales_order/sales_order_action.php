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
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();

include "../../inc/config.php";
session_check_json();
function so_post($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}
function so_post_num($key, $default = 0) {
    if (!isset($_POST[$key])) return $default;
    return (float)str_replace(',', '.', (string)$_POST[$key]);
}
function so_arr($key, $index, $default = '') {
    return isset($_POST[$key][$index]) ? trim((string)$_POST[$key][$index]) : $default;
}
function so_arr_num($key, $index, $default = 0) {
    if (!isset($_POST[$key][$index])) return $default;
    return (float)str_replace(',', '.', (string)$_POST[$key][$index]);
}
function so_require_fields($fields) {
    foreach ($fields as $field => $label) {
        if (so_post($field) === '') action_response($label.' wajib diisi.');
    }
}
function so_header_payload($isUpdate = false) {
    $soldTo = so_post('sold_to_party');
    $shipTo = so_post('ship_to_party', $soldTo);
    $billTo = so_post('bill_to_party', $soldTo);
    $payer = so_post('payer', $soldTo);
    return array(
        'order_type' => so_post('order_type', 'OR'),
        'id_quotation' => so_post('id_quotation') !== '' ? so_post('id_quotation') : null,
        'sales_org_id' => so_post('sales_org_id') !== '' ? so_post('sales_org_id') : null,
        'distribution_channel_id' => so_post('distribution_channel_id') !== '' ? so_post('distribution_channel_id') : null,
        'division_code' => so_post('division_code', '00'),
        'so_date' => so_post('so_date'),
        'consignee' => so_post('consignee'),
        'currency' => so_post('currency'),
        'rupiah_rate' => so_post_num('rupiah_rate', 1),
        'delivery_term' => so_post('delivery_term'),
        'incoterm' => strtoupper(so_post('incoterm')),
        'vessel' => so_post('vessel'),
        'dari' => so_post('dari'),
        'ke' => so_post('ke'),
        'no_po' => so_post('no_po'),
        'rupiah_rate_sale' => so_post_num('rupiah_rate_sale', so_post_num('rupiah_rate', 1)),
        'kode_penerima' => $soldTo,
        'sold_to_party' => $soldTo,
        'ship_to_party' => $shipTo,
        'bill_to_party' => $billTo,
        'payer' => $payer,
        'tax' => so_post('tax', 'include'),
        'no_store' => so_post('no_store'),
        'notify_party' => so_post('notify_party'),
        'other_reference' => so_post('other_reference'),
        'sales_id' => so_post('sales_id'),
        'purchase_ref' => so_post('purchase_ref'),
        'user' => so_post('user', isset($_SESSION['username']) ? $_SESSION['username'] : 'system'),
        'term' => so_post('term') !== '' ? so_post('term') : null,
        'payment_term' => so_post('payment_term'),
        'discount' => so_post_num('discount', 0),
        'delivery_date' => so_post('delivery_date'),
        'shipping_address' => so_post('shipping_address'),
        'catatan' => so_post('catatan'),
        'delivery_block' => so_post('delivery_block'),
        'billing_block' => so_post('billing_block'),
        'status' => 'Waiting for Approve',
        'approval_status' => $isUpdate ? so_post('approval_status', 'SUBMITTED') : 'SUBMITTED',
    );
}
function so_validate_items() {
    if (empty($_POST['kode_input']) || !is_array($_POST['kode_input'])) action_response('Minimal satu item Sales Order wajib diisi.');
    foreach ($_POST['kode_input'] as $i => $kode) {
        if (trim((string)$kode) === '') action_response('Material baris '.($i + 1).' wajib diisi.');
        if (so_arr_num('qty', $i) <= 0) action_response('Qty baris '.($i + 1).' wajib lebih dari 0.');
        if (so_arr_num('harga', $i) < 0) action_response('Harga baris '.($i + 1).' tidak boleh minus.');
    }
}
function so_insert_items($db, $idSalesOrder) {
    foreach ($_POST['kode_input'] as $i => $kode) {
        $qty = so_arr_num('qty', $i);
        $price = so_arr_num('harga', $i);
        $disc = so_arr_num('discount_percent', $i);
        $tax = so_arr_num('tax_percent', $i);
        $net = ($qty * $price) - (($qty * $price) * $disc / 100);
        $amount = $net + ($net * $tax / 100);
        $db->insert('sales_order_detail', array(
            'id_sales_order' => $idSalesOrder,
            'line_no' => so_arr('line_no', $i, ($i + 1) * 10),
            'kd_barang' => trim((string)$kode),
            'item_category' => so_arr('item_category', $i, 'TAN'),
            'store' => so_arr('store', $i),
            'plant_id' => so_arr('plant_id', $i) !== '' ? so_arr('plant_id', $i) : null,
            'storage_location_id' => so_arr('storage_location_id', $i) !== '' ? so_arr('storage_location_id', $i) : null,
            'requested_delivery_date' => so_arr('requested_delivery_date', $i) !== '' ? so_arr('requested_delivery_date', $i) : so_post('delivery_date'),
            'qty' => $qty,
            'confirmed_qty' => $qty,
            'price' => $price,
            'discount_percent' => $disc,
            'tax_percent' => $tax,
            'nilai' => $amount,
            'ket' => so_arr('ket', $i),
        ));
        if ($db->getErrorMessage() !== '') action_response($db->getErrorMessage());
    }
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
$status_so = $_GET['status_so'];

$where = "";

// ==================
// FILTER TANGGAL
// ==================
if(!empty($tgl_awal) && !empty($tgl_akhir)){
    $where .= " AND v.so_date BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

// ==================
// FILTER CUSTOMER
// ==================
if($customer != 'all'){
    $where .= " AND v.kode_penerima = '$customer'";
}

// ==================
// FILTER STATUS SO
// ==================
if($status_so != '' && $status_so != 'all'){
    $where .= " AND v.status_so = '$status_so'";
}

// ==================
// QUERY
// ==================
$query = $db->query("
SELECT 
    v.id_sales_order,
    v.no_sales_order,
    v.so_date,
    v.no_po,
    v.currency,
    v.nama AS kode_penerima,
    v.sales_id,
    v.status_so,

    so.delivery_date,
    so.term,
    so.tax,
    so.tax_item,

    d.kd_barang,
    b.nm_barang,
    d.qty,
    d.price,
    d.nilai

FROM v_sales_status v

INNER JOIN sales_order so
    ON v.id_sales_order = so.id_sales_order

LEFT JOIN sales_order_detail d
    ON so.id_sales_order = d.id_sales_order

LEFT JOIN barang b
    ON d.kd_barang = b.kd_barang

WHERE 1=1
$where

ORDER BY v.so_date DESC,
         v.no_sales_order ASC
");

// ==================
// EXCEL
// ==================
$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->setActiveSheetIndex(0);

// ==================
// JUDUL
// ==================
$sheet->setCellValue('A1', 'LAPORAN SALES ORDER');
$sheet->mergeCells('A1:P1');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$sheet->getStyle('A1')->getAlignment()
      ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Periode: '.$tgl_awal.' s/d '.$tgl_akhir);
$sheet->mergeCells('A2:P2');

$sheet->getStyle('A2')->getAlignment()
      ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ==================
// HEADER
// ==================
$headers = [

  'A4' => 'SO No',
  'B4' => 'Tanggal',
  'C4' => 'Customer',
  'D4' => 'Customer PO',
  'E4' => 'Delivery Date',
  'F4' => 'Payment Term',
  'G4' => 'PPN',
  'H4' => 'PIC',
  'I4' => 'Currency',
  'J4' => 'Status SO',

  'K4' => 'Kode Barang',
  'L4' => 'Nama Barang',
  'M4' => 'Qty',
  'N4' => 'Harga',
  'O4' => 'Nilai',
  'P4' => 'PPN Value'
];

foreach($headers as $cell => $value){
    $sheet->setCellValue($cell, $value);
}

$sheet->getStyle('A4:P4')->getFont()->setBold(true);

$sheet->getStyle('A4:P4')->getAlignment()
      ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ==================
// DATA
// ==================
$rowNum = 5;

$total_qty   = 0;
$total_nilai = 0;
$total_ppn   = 0;

$query->setFetchMode(PDO::FETCH_OBJ);

$last_so = '';

foreach ($query as $row) {

    $ppn_type = !empty($row->tax)
        ? $row->tax
        : $row->tax_item;

    $nilai = (float)$row->qty * (float)$row->price;

    // ==================
    // HITUNG PPN
    // ==================
    if(strtolower($ppn_type) == 'exclude'){
        $nilai_ppn = $nilai * 0.11;
    }else{
        $nilai_ppn = 0;
    }

    // ==========================================
    // HEADER HANYA SEKALI
    // ==========================================
    if($last_so != $row->id_sales_order){

        $sheet->setCellValue("A".$rowNum, $row->no_sales_order);

        $sheet->setCellValue("B".$rowNum, $row->so_date);

        $sheet->setCellValue("C".$rowNum, $row->kode_penerima);

        $sheet->setCellValue("D".$rowNum, $row->no_po);

        $sheet->setCellValue("E".$rowNum, $row->delivery_date);

        $sheet->setCellValue("F".$rowNum, $row->term);

        $sheet->setCellValue("G".$rowNum, $ppn_type);

        $sheet->setCellValue("H".$rowNum, $row->sales_id);

        $sheet->setCellValue("I".$rowNum, $row->currency);

        $sheet->setCellValue("J".$rowNum, $row->status_so);

        // bold header so
        $sheet->getStyle("A".$rowNum.":J".$rowNum)
              ->getFont()
              ->setBold(true);

    }

    // ==========================================
    // DETAIL BARANG
    // ==========================================
    $sheet->setCellValue("K".$rowNum, $row->kd_barang);

    $sheet->setCellValue("L".$rowNum, $row->nm_barang);

    $sheet->setCellValue("M".$rowNum, (float)$row->qty);

    $sheet->setCellValue("N".$rowNum, (float)$row->price);

    $sheet->setCellValue("O".$rowNum, $nilai);

    $sheet->setCellValue("P".$rowNum, $nilai_ppn);

    // ==================
    // TOTAL
    // ==================
    $total_qty += (float)$row->qty;

    $total_nilai += $nilai;

    $total_ppn += $nilai_ppn;

    $last_so = $row->id_sales_order;

    $rowNum++;
} 

// ==================
// TOTAL
// ==================
$sheet->setCellValue("L".$rowNum, "TOTAL");

$sheet->setCellValue("M".$rowNum, $total_qty);

$sheet->setCellValue("O".$rowNum, $total_nilai);

$sheet->setCellValue("P".$rowNum, $total_ppn);

$sheet->getStyle("L".$rowNum.":P".$rowNum)
      ->getFont()
      ->setBold(true);

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

$sheet->getStyle("P5:P".$lastRow)
      ->getNumberFormat()
      ->setFormatCode('#,##0.00');

// ==================
// BORDER
// ==================
$sheet->getStyle("A4:P".$lastRow)->applyFromArray([
  'borders' => [
    'allborders' => [
      'style' => PHPExcel_Style_Border::BORDER_THIN
    ]
  ]
]);

// ==================
// AUTO SIZE
// ==================
foreach(range('A','P') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ==================
// FREEZE
// ==================
$sheet->freezePane('A5');

// ==================
// TITLE
// ==================
$sheet->setTitle(erp_export_sheet_title('Sales Order'));

// ==================
// OUTPUT
// ==================
header('Content-Type: application/vnd.ms-excel');

header('Content-Disposition: attachment;filename="Sales_Order.xls"');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

$objWriter->save('php://output');

exit;

break;

 

  case "material_search":
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query("SELECT kd_barang,nm_barang,satuan FROM barang WHERE kd_barang LIKE ? OR nm_barang LIKE ? ORDER BY kd_barang LIMIT 30", array('%'.$term.'%', '%'.$term.'%'));
    $results = array();
    if ($rows) {
        foreach ($rows as $r) {
            $results[] = array(
                'id' => $r->kd_barang,
                'text' => $r->kd_barang.' - '.$r->nm_barang,
                'material_code' => $r->kd_barang,
                'material_name' => $r->nm_barang,
                'uom' => $r->satuan,
                'price' => 0,
            );
        }
    }
    echo json_encode(array('results'=>$results));
    break;

  case "material_get":
    $kode = isset($_POST['kode_barang']) ? trim($_POST['kode_barang']) : '';
    $row = $db->fetch("SELECT kd_barang,nm_barang,satuan FROM barang WHERE kd_barang=? LIMIT 1", array($kode));
    if (!$row) {
        echo json_encode(array('status'=>'error','message'=>'Material tidak ditemukan.'));
        break;
    }
    echo json_encode(array(
        'status' => 'success',
        'material_code' => $row->kd_barang,
        'material_name' => $row->nm_barang,
        'uom' => $row->satuan,
        'price' => 0,
    ));
    break;

  case "quotation_load":
    $idQuotation = isset($_POST['id_quotation']) ? (int)$_POST['id_quotation'] : 0;
    $header = $db->fetch("SELECT * FROM sales_quotation WHERE id_quotation=? LIMIT 1", array($idQuotation));
    if (!$header) {
        echo json_encode(array('status'=>'error','message'=>'Quotation tidak ditemukan.'));
        break;
    }
    $items = array();
    $rows = $db->query("SELECT d.*, b.nm_barang, b.satuan FROM sales_quotation_detail d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.id_quotation=? ORDER BY d.line_no,d.id_detail", array($idQuotation));
    if ($rows) {
        foreach ($rows as $r) {
            $items[] = array(
                'material_code' => $r->kd_barang,
                'material_name' => $r->nm_barang,
                'uom' => $r->uom ?: $r->satuan,
                'qty' => $r->qty,
                'price' => $r->price,
                'discount_percent' => $r->discount_percent,
                'tax_percent' => $r->tax_percent,
                'required_date' => $r->requested_delivery_date ?: $header->requested_delivery_date,
                'remark' => $r->ket,
            );
        }
    }
    echo json_encode(array('status'=>'success','header'=>$header,'items'=>$items));
    break;

  case "get_pemasok":
    $kode_penerima = $_POST['kode_penerima'];
    $q = $db->query("select   alamat from penerima where kode_penerima='$kode_penerima' ");
    foreach ($q as $k) {
       echo "$k->alamat";
    }
    break;
  case 'get_pr2':
  $no_pr = $_POST['no_pr'];
  $q = $db->query("select no_sales_quotation,  kode_penerima, tgl, currency, rupiah_rate, rupiah_rate_sale,  tax, sales_id , user , term , valid_date  from sales_quotation where id_quotation='$no_pr' ");
  $res = array();
  foreach ($q as $k) {
     $res['no_sales_quotation'] = $k->no_sales_quotation;
     $res['kode_penerima'] = $k->kode_penerima;
     $res['tgl'] = $k->tgl;
     $res['currency'] = $k->currency;
     $res['rupiah_rate'] = $k->rupiah_rate;
     $res['rupiah_rate_sale'] = $k->rupiah_rate_sale;
     $res['tax'] = $k->tax;
     $res['sales_id'] = $k->sales_id;
     $res['user'] = $k->user;
     $res['term'] = $k->term;
     $res['valid_date'] = $k->valid_date;
  } 
  echo json_encode($res);
    break;

  case "get_pr":
   $no_pr = $_POST['no_pr'];
   $data_edit = $db->fetch_single_row("sales_quotation","id_quotation",$no_pr);
   $pajak = array();
   if ($data_edit!='') {
       $pajak = json_decode($data_edit->tax_item);
  }
    //echo count($pajak);
  ?>
   <div class="col-lg-12">
               <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>'.sd_h('sales_qty', 'Qty').'</th>  
                     <th>Harga</th>    
                     <th>Nilai</th>               
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $no=1;
                   $total_qty =0;
                 $total_harga = 0;
                 $total_nilai = 0;
                 $tot_nilai=0;
                 $qd = $db->query("select d.*,b.nm_barang,b.satuan from sales_quotation_detail d join barang b on b.kd_barang=d.kd_barang where id_quotation='$data_edit->id_quotation' ");
                 foreach ($qd as $kd) {
                   ?>
                   <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" value="<?= $kd->kd_barang ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" value="<?= $kd->kd_barang ?>"  id="kode_input_<?= $no ?>"> 
                     </td> 
                     <td><input type="text" id="form_unit_<?= $no ?>" value="<?= $kd->satuan ?>"  class="form-control" name="unit[]"  readonly=""></td> 
                    
                     <td><input type="text" id="form_qty_<?= $no ?>" value="<?= $kd->qty ?>"  class="form-control" name="qty[]" onkeyup="sum_nilai(this.value,'<?= $no ?>')" style="text-align: right;" required></td>
                     <td><input type="text" id="form_harga_<?= $no ?>" style="text-align: right;" value="<?= $kd->price ?>"  class="form-control" name="harga[]" onkeyup="sum_nilai(this.value,'<?= $no ?>')"  required></td>
                     <td><input type="text" id="form_nilai_<?= $no ?>" style="text-align: right;" value="<?= $kd->nilai ?>"  class="form-control" name="nilai[]"  readonly=""></td>
                     <td><input type="text" id="form_ket_<?= $no ?>" value="<?= $kd->ket ?>"  class="form-control" name="ket[]" ></td>
                   </tr>
                   <?php
                   $no++;
                   $total_qty = $total_qty + $kd->qty;
                   $total_harga = $total_harga + $kd->price; 
                   $tot_nilai = $tot_nilai + ($kd->price * $kd->qty);
                 }
                 ?>
                   
                 </tbody>
                  <tfoot>
                   <tr>
                   <td colspan="3" style="text-align: center;">'.sd_h('sales_total', 'Total').'</td>
                   <td><input type="text" id="total_qty" value="<?= $total_qty ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                   <td><input type="text" id="total_harga" value="<?= $total_harga ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                   <td><input type="text" id="nilai_total" value="<?= $tot_nilai ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                 </tr>
                    <tr id="baris_pajak"> 
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                   <td>
                      <?php
                      $j=1;
                  foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                     if (count($pajak)>0) {
                        for ($i=0; $i <count($pajak) ; $i++) { 
                         if ($pajak[$i]==$p->jenis_pajak) {
                           echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak' checked> $p->jenis_pajak [$p->jumlah%]</label><br>"; 
                         }else{
                          echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                         }
                        }
                     }else{
                       echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                     }
                     $j++;
                  }
                  ?>
                   </td>

                    <td style="text-align: right;">
                      <?php
                      $total_pajak = 0;
                      $j=1;
                  foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                    
                      if (count($pajak)>0) {
                        for ($i=0; $i <count($pajak) ; $i++) { 
                         if ($pajak[$i]==$p->jenis_pajak) {
                           echo "<input id='nilai_".$j."' type='text' class='form-control' value='".(($p->jumlah/100)*$tot_nilai)."' readonly style='text-align: right;'>  ";
                           $total_pajak = $total_pajak + (($p->jumlah/100)*$tot_nilai);
                         }else{
                           echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                         }
                         echo "<input type='hidden' id='nilai_pajak_$j' value='$p->jumlah' >";
                        }
                     }else{
                       echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                     }
                     $j++;
                  } 
                  ?>
                   </td>

                   
                 </tr>
                  <tr>
                   <td colspan="5" style="text-align: center;">Grand Total</td>
                   <td>
                     <input type="hidden" id="tmp_total" value="<?= ($total_pajak+$tot_nilai) ?>"> 
                     <input type="hidden" id="tmp_total_pajak" value="<?= $total_pajak ?>"> 
                     <input type="text" id="grand_total" class="form-control" style="text-align: right;" value="<?= ($total_pajak+$tot_nilai) ?>" readonly=""> 
                   </td>

                 </tr>
                 </tfoot> 
               </table>
                </div>
                <input type="hidden" id="jml" value="<?= ($no-1) ?>">
               <input type="hidden" id="jml2" value="<?= ($j) ?>">

              <script type="text/javascript">

                function set_pajak(jenis,jumlah){
                  var total_tmp = 0;
                   var nilai_total = parseFloat($("#tmp_total").val());
                  if ($("#tmp_total").val()=='') {
                      var nilai_total = parseFloat($("#nilai_total").val());
                  }
                 
                  if ($('#pajak_' + jenis).is(":checked")) {
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum;
                    nilai_total = nilai_total + nilai;

                   // alert(nilai);
                     $("#nilai_"+jenis).val(nilai.toFixed(3));
                  }else{
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum; 
                     nilai_total = nilai_total - nilai; 
                    $("#nilai_"+jenis).val("");
                  }
                  $("#tmp_total").val(nilai_total.toFixed(0));
                  $("#grand_total").val(nilai_total.toFixed(3));
                }

                 function sum_nilai(val,id){
                  var jml = parseFloat($("#jml").val());
                  var jml2 = parseFloat($("#jml2").val());
                  var total = 0;
                  var total_qty = 0;
                  var total_harga = 0;
                  var grand_total = 0;
                  for (var i = 1; i <= jml; i++) {
                     total = total + (parseFloat($("#form_qty_"+i).val()) * parseFloat($("#form_harga_"+i).val()));
                     total_qty = total_qty + parseFloat($("#form_qty_"+i).val()) ;
                     total_harga = total_harga + parseFloat($("#form_harga_"+i).val()) ;

                  }
                  var total_pajak = 0;
                  for (var i = 1; i <= jml2; i++) {
                    if ($('#pajak_'+i).is(":checked")) { 
                      tmp_pajak = parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
                      $("#nilai_"+i).val(tmp_pajak.toFixed(3));
                      total_pajak = total_pajak + parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
                    }
                  }
                  $("#tmp_total_pajak").val(total_pajak); 
                  grand_total = total+total_pajak;
                  $("#nilai_total").val(total); 
                  $("#tmp_total").val(); 
                  $("#total_qty").val(total_qty);
                  $("#grand_total").val(grand_total.toFixed(3));
                  $("#total_harga").val(total_harga);
                  var qty = parseFloat($("#form_qty_"+id).val());
                  var harga = parseFloat($("#form_harga_"+id).val());
                  nilai = qty * harga;
                  $("#form_nilai_"+id).val(nilai); 
                  
                } 
                   
              </script>
  <?php
 
    break;
  case "in":
    so_require_fields(array(
        'order_type'=>'Order Type',
        'sales_org_id'=>'Sales Organization',
        'distribution_channel_id'=>'Distribution Channel',
        'so_date'=>'SO Date',
        'sold_to_party'=>'Sold-to Party',
        'ship_to_party'=>'Ship-to Party',
        'currency'=>'Currency',
        'delivery_date'=>'Requested Delivery Date',
    ));
    so_validate_items();
    $data = so_header_payload(false);
    $data['no_sales_order'] = get_nomor_transaksi('so');
    $data['no_sales_invoice'] = get_no_si();
    $data['submitted_by'] = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $data['submitted_at'] = date('Y-m-d H:i:s');
    $db->query('START TRANSACTION');
    if (!$db->insert('sales_order', $data)) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($err);
    }
    $id_sales_order = $db->last_insert_id();
    so_insert_items($db, $id_sales_order);
    $err = $db->getErrorMessage();
    if ($err !== '') {
        $db->query('ROLLBACK');
        action_response($err);
    }
    $db->query('COMMIT');
    action_response('', array('id_sales_order'=>$id_sales_order, 'no_sales_order'=>$data['no_sales_order']));
    break;
  case "delete":
    
    
    
    $db->delete("sales_order","id_sales_order",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sales_order","id_sales_order",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    if (so_post('id') === '') action_response('ID Sales Order tidak valid.');
    so_require_fields(array(
        'order_type'=>'Order Type',
        'sales_org_id'=>'Sales Organization',
        'distribution_channel_id'=>'Distribution Channel',
        'so_date'=>'SO Date',
        'sold_to_party'=>'Sold-to Party',
        'ship_to_party'=>'Ship-to Party',
        'currency'=>'Currency',
        'delivery_date'=>'Requested Delivery Date',
    ));
    so_validate_items();
    $id = (int)so_post('id');
    $hasFollowUp = $db->fetch("SELECT COUNT(*) jml FROM surat_jalan WHERE id_sales_order=? OR no_sales_order=(SELECT no_sales_order FROM sales_order WHERE id_sales_order=? LIMIT 1)", array($id, $id));
    $data = so_header_payload(true);
    unset($data['approval_status']);
    $db->query('START TRANSACTION');
    if (!$db->update('sales_order', $data, 'id_sales_order', $id)) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        action_response($err);
    }
    if ($hasFollowUp && (int)$hasFollowUp->jml > 0) {
        $db->query('COMMIT');
        action_response('', array(
            'id_sales_order' => $id,
            'item_locked' => 'Y',
            'message' => 'Header Sales Order berhasil diperbarui. Item tidak diubah karena sudah memiliki proses delivery.'
        ));
    }
    $db->query("DELETE FROM sales_order_detail WHERE id_sales_order=?", array($id));
    so_insert_items($db, $id);
    $err = $db->getErrorMessage();
    if ($err !== '') {
        $db->query('ROLLBACK');
        action_response($err);
    }
    $db->query('COMMIT');
    action_response('', array('id_sales_order'=>$id));
    break;
  default:
    # code...
    break;
}

?>
