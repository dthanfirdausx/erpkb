<?php
error_reporting(0); 
session_start();
include "../../inc/config.php";
session_check_json(); 
function po_action_t($key, $fallback = '') {
  return lang_text($key, $fallback);
}
function po_action_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
switch ($_GET["act"]) {
  case "detail":
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $po = $db->fetch("SELECT * FROM purchase_order WHERE id=? LIMIT 1", array($id));
    if (!$po) {
      echo '<div class="alert alert-danger">'.po_action_h(po_action_t('purchase_order_not_found','Purchase Order tidak ditemukan.')).'</div>';
      exit;
    }
    $details = $db->query("SELECT * FROM purchase_order_detail WHERE id_po=? OR po_no=? ORDER BY id", array($po->id, $po->purchase_order_no));
    $totalQty = 0;
    $totalReceived = 0;
    $totalAmount = 0;
    ob_start();
    ?>
    <div class="row">
      <div class="col-sm-3"><div class="po-detail-card"><span><?=po_action_h(po_action_t('purchase_order_po_number','No PO'));?></span><strong><?=htmlspecialchars($po->purchase_order_no,ENT_QUOTES,'UTF-8');?></strong></div></div>
      <div class="col-sm-3"><div class="po-detail-card"><span><?=po_action_h(po_action_t('purchase_order_po_date','PO Date'));?></span><strong><?=htmlspecialchars($po->po_date,ENT_QUOTES,'UTF-8');?></strong></div></div>
      <div class="col-sm-3"><div class="po-detail-card"><span><?=po_action_h(po_action_t('purchase_order_delivery_date','Delivery Date'));?></span><strong><?=htmlspecialchars($po->delivery_date,ENT_QUOTES,'UTF-8');?></strong></div></div>
      <div class="col-sm-3"><div class="po-detail-card"><span><?=po_action_h(po_action_t('common_status','Status'));?></span><strong><?=htmlspecialchars(($po->approval_status ?: 'Pending').' / '.($po->status ?: 'OPEN'),ENT_QUOTES,'UTF-8');?></strong></div></div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="po-detail-card">
          <span><?=po_action_h(po_action_t('purchase_order_vendor','Vendor'));?></span>
          <strong><?=htmlspecialchars(trim($po->seller_code.' - '.$po->seller_name,' -'),ENT_QUOTES,'UTF-8');?></strong>
          <div class="text-muted" style="margin-top:6px"><?=nl2br(htmlspecialchars((string)$po->seller_address,ENT_QUOTES,'UTF-8'));?></div>
          <small><?=htmlspecialchars(trim(($po->seller_pic ?: '-').' / '.($po->seller_phone ?: '-').' / '.($po->seller_email ?: '-')),ENT_QUOTES,'UTF-8');?></small>
        </div>
      </div>
      <div class="col-md-6">
        <div class="po-detail-card">
          <span><?=po_action_h(po_action_t('purchase_order_terms_location','Terms & Location'));?></span>
          <strong><?=htmlspecialchars(($po->currency ?: '-').' | '.($po->delivery_term ?: '-').' | '.($po->payment_term ?: '-'),ENT_QUOTES,'UTF-8');?></strong>
          <div class="text-muted" style="margin-top:6px">
            <?=po_action_h(po_action_t('common_plant','Plant'));?>: <?=htmlspecialchars((string)$po->plant,ENT_QUOTES,'UTF-8');?> |
            <?=po_action_h(po_action_t('purchase_order_sloc','SLoc'));?>: <?=htmlspecialchars((string)$po->storage_location,ENT_QUOTES,'UTF-8');?> |
            <?=po_action_h(po_action_t('purchase_order_shipped_via','Shipped Via'));?>: <?=htmlspecialchars((string)$po->shipped_via,ENT_QUOTES,'UTF-8');?>
          </div>
          <small><?=po_action_h(po_action_t('purchase_order_source','Source'));?>: <?=htmlspecialchars(trim(($po->source_type ?: '-').' '.$po->source_ref),ENT_QUOTES,'UTF-8');?></small>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped po-detail-table">
        <thead>
          <tr>
            <th class="text-center" style="width:42px">No</th>
            <th><?=po_action_h(po_action_t('purchase_order_material','Material'));?></th>
            <th><?=po_action_h(po_action_t('common_spec','Spec'));?></th>
            <th class="text-right"><?=po_action_h(po_action_t('purchase_order_po_qty','PO Qty'));?></th>
            <th class="text-right"><?=po_action_h(po_action_t('purchase_order_gr_qty','GR Qty'));?></th>
            <th><?=po_action_h(po_action_t('purchase_order_uom','UOM'));?></th>
            <th class="text-right"><?=po_action_h(po_action_t('purchase_order_price','Price'));?></th>
            <th class="text-right"><?=po_action_h(po_action_t('purchase_order_amount','Amount'));?></th>
            <th><?=po_action_h(po_action_t('purchase_order_note','Note'));?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          foreach ($details as $d) {
            $qty = (float)$d->qty;
            $received = (float)$d->received_qty;
            $amount = (float)$d->amount;
            $totalQty += $qty;
            $totalReceived += $received;
            $totalAmount += $amount;
          ?>
          <tr>
            <td class="text-center"><?=$no++;?></td>
            <td><strong><?=htmlspecialchars($d->kode_barang,ENT_QUOTES,'UTF-8');?></strong><br><small><?=htmlspecialchars($d->nama_barang,ENT_QUOTES,'UTF-8');?></small></td>
            <td><?=htmlspecialchars((string)$d->spec,ENT_QUOTES,'UTF-8');?></td>
            <td class="text-right"><?=erp_format_qty($qty,2);?></td>
            <td class="text-right"><?=erp_format_qty($received,2);?></td>
            <td><?=htmlspecialchars((string)$d->unit,ENT_QUOTES,'UTF-8');?></td>
            <td class="text-right"><?=erp_format_number((float)$d->harga,2);?></td>
            <td class="text-right"><?=erp_format_number($amount,2);?></td>
            <td><?=htmlspecialchars((string)$d->ket,ENT_QUOTES,'UTF-8');?></td>
          </tr>
          <?php } ?>
          <?php if ($no === 1) { ?>
          <tr><td colspan="9" class="text-center text-muted"><?=po_action_h(po_action_t('purchase_order_no_item','No PO item.'));?></td></tr>
          <?php } ?>
        </tbody>
        <tfoot>
          <tr class="bg-gray">
            <th colspan="3" class="text-right"><?=po_action_h(po_action_t('purchase_order_total','Total'));?></th>
            <th class="text-right"><?=erp_format_qty($totalQty,2);?></th>
            <th class="text-right"><?=erp_format_qty($totalReceived,2);?></th>
            <th></th>
            <th></th>
            <th class="text-right"><?=htmlspecialchars((string)$po->currency,ENT_QUOTES,'UTF-8');?> <?=erp_format_number($totalAmount,2);?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php if (trim((string)$po->catatan) !== '') { ?>
      <div class="po-detail-card"><span><?=po_action_h(po_action_t('purchase_order_note','Note'));?></span><div><?=nl2br(htmlspecialchars((string)$po->catatan,ENT_QUOTES,'UTF-8'));?></div></div>
    <?php } ?>
    <div class="text-right">
      <a href="<?=base_url();?>modul/purchase_order/cetak_po.php?po_no=<?=urlencode($po->id);?>" target="_blank" class="btn btn-success"><i class="fa fa-print"></i> <?=po_action_h(po_action_t('purchase_order_print','Print PO'));?></a>
      <a href="<?=base_index();?>purchase-order/edit/<?=intval($po->id);?>" class="btn btn-primary"><i class="fa fa-pencil"></i> <?=po_action_h(po_action_t('common_edit','Edit'));?> PO</a>
    </div>
    <?php
    echo ob_get_clean();
    exit;
    break;

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
    $where .= " AND a.seller_code = '".addslashes($supplier)."'";
}

// status
if($status != 'all'){
    $where .= " AND a.status = '".addslashes($status)."'";
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
$sheet->setCellValue('A1', po_action_t('purchase_order_report_title', 'LAPORAN PURCHASE ORDER'));
$sheet->mergeCells('A1:J1');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', po_action_t('common_period', 'Periode').': '.$tgl_awal.' s/d '.$tgl_akhir);
$sheet->mergeCells('A2:J2');

$sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ==================
// HEADER
// ==================
$headers = [
  'A4' => 'No PO',
  'B4' => po_action_t('common_date', 'Tanggal'),
  'C4' => po_action_t('common_supplier', 'Supplier'),
  'D4' => po_action_t('common_address', 'Alamat'),
  'E4' => po_action_t('purchase_order_trade_term', 'Trade Term'),
  'F4' => po_action_t('common_status', 'Status'),
  'G4' => po_action_t('common_material_code', 'Kode Barang'),
  'H4' => po_action_t('common_material_name', 'Nama Barang'),
  'I4' => po_action_t('purchase_order_qty', 'Qty'),
  'J4' => po_action_t('purchase_order_price', 'Harga')
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
    $sheet->setCellValue("J".$rowNum, (float)$row->harga);

    $total_qty += (float)$row->qty;
    $total_nilai += (float)$row->amount;

    $rowNum++;
    $last_po = $row->id_po;
}

// ==================
// TOTAL
// ==================
$sheet->setCellValue("H".$rowNum, po_action_t('purchase_order_total', 'TOTAL'));
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

  case "material_search":
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query(
      "SELECT b.kd_barang,b.nm_barang,b.satuan,b.spec,b.kd_kategori,mg.group_code,mg.group_name
       FROM barang b
       LEFT JOIN erp_material_group mg ON mg.id=b.material_group_id
       WHERE (b.kd_barang LIKE ? OR b.nm_barang LIKE ?)
         AND (b.status IS NULL OR b.status=1)
       ORDER BY b.kd_barang
       LIMIT 30",
      array('kode'=>'%'.$term.'%','nama'=>'%'.$term.'%')
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang,
        'material_code' => $row->kd_barang,
        'material_name' => $row->nm_barang,
        'uom' => $row->satuan,
        'spec' => $row->spec,
        'kd_kategori' => $row->kd_kategori,
        'material_group' => $row->group_code ?: $row->kd_kategori
      );
    }
    echo json_encode(array('results'=>$results));
    break;

  case "rfq_award_search":
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query(
      "SELECT q.id AS rfq_quotation_id,q.price,q.qty AS quote_qty,q.currency,q.discount_percent,q.tax_percent,q.delivery_days,
              i.id AS rfq_item_id,i.rfq_id,i.id_pr,i.id_pr_detail,i.line_no,i.material_code,i.material_name,i.uom,i.required_date,i.plant,i.storage_location,
              v.vendor_code,v.vendor_name,v.email,r.rfq_no
       FROM erp_rfq_quotation q
       JOIN erp_rfq_item i ON i.id=q.rfq_item_id
       JOIN erp_rfq_vendor v ON v.id=q.rfq_vendor_id
       JOIN erp_rfq r ON r.id=q.rfq_id
       WHERE q.is_awarded='Y'
         AND (r.rfq_no LIKE ? OR i.material_code LIKE ? OR i.material_name LIKE ? OR v.vendor_name LIKE ?)
       ORDER BY r.rfq_no DESC,i.line_no
       LIMIT 30",
      array('a'=>'%'.$term.'%','b'=>'%'.$term.'%','c'=>'%'.$term.'%','d'=>'%'.$term.'%')
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->rfq_quotation_id,
        'text' => $row->rfq_no.' / '.$row->line_no.' - '.$row->material_code.' - '.$row->vendor_name,
        'source_type' => 'RFQ',
        'source_ref' => $row->rfq_no,
        'vendor_code' => $row->vendor_code,
        'vendor_name' => $row->vendor_name,
        'vendor_email' => $row->email,
        'rfq_id' => $row->rfq_id,
        'rfq_item_id' => $row->rfq_item_id,
        'rfq_quotation_id' => $row->rfq_quotation_id,
        'id_pr' => $row->id_pr,
        'id_pr_detail' => $row->id_pr_detail,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'qty' => $row->quote_qty,
        'uom' => $row->uom,
        'price' => $row->price,
        'currency' => $row->currency,
        'required_date' => $row->required_date,
        'plant' => $row->plant,
        'storage_location' => $row->storage_location
      );
    }
    echo json_encode(array('results'=>$results));
    break;

  case "pr_item_search":
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $rows = $db->query(
      "SELECT d.*,pr.no_pr
       FROM purchase_requisition_detail d
       JOIN purchase_requisition pr ON pr.id_pr=d.id_pr
       WHERE pr.status IN ('APPROVED','PARTIAL_PO')
         AND d.qty_open>0
         AND (pr.no_pr LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ?)
       ORDER BY pr.no_pr DESC,d.line_no
       LIMIT 30",
      array('a'=>'%'.$term.'%','b'=>'%'.$term.'%','c'=>'%'.$term.'%')
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->id_pr_detail,
        'text' => $row->no_pr.' / '.$row->line_no.' - '.$row->material_code.' - '.$row->material_name,
        'source_type' => 'PR',
        'source_ref' => $row->no_pr,
        'id_pr' => $row->id_pr,
        'id_pr_detail' => $row->id_pr_detail,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'qty' => $row->qty_open,
        'uom' => $row->uom,
        'price' => $row->valuation_price,
        'currency' => $row->currency,
        'required_date' => $row->required_date,
        'plant' => $row->plant,
        'storage_location' => $row->storage_location
      );
    }
    echo json_encode(array('results'=>$results));
    break;

  case "cari_vendor":
    $kode = isset($_POST['kode_pemasok']) ? $_POST['kode_pemasok'] : '';
    $vendor = $db->fetch("SELECT * FROM pemasok WHERE kode_pemasok=? OR nama=? LIMIT 1", array('kode'=>$kode,'nama'=>$kode));

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
    $required = array(
      'po_date'=>po_action_t('purchase_order_po_date','PO Date'),
      'seller_code'=>po_action_t('purchase_order_vendor','Vendor'),
      'currency'=>po_action_t('purchase_order_currency','Currency'),
      'delivery_date'=>po_action_t('purchase_order_delivery_date','Delivery Date')
    );
    foreach ($required as $field => $label) {
      if (!isset($_POST[$field]) || trim((string)$_POST[$field])==='') action_response($label.' '.po_action_t('common_required_suffix','wajib diisi.'));
    }
    if (empty($_POST['kode']) || !is_array($_POST['kode'])) action_response(po_action_t('purchase_order_no_item_required','Minimal satu item PO wajib diisi.'));
    $vendor = $db->fetch("SELECT * FROM pemasok WHERE kode_pemasok=? LIMIT 1", array('kode'=>$_POST['seller_code']));
    if (!$vendor) action_response(po_action_t('purchase_order_vendor_invalid','Vendor tidak valid.'));

    $tg = explode("-", $_POST["po_date"]);

     $no_po = generate_po_no($tg[0],$tg[1]);
        $data = array(
      "purchase_order_no" => $no_po,
      "po_type"       => isset($_POST["po_type"]) ? $_POST["po_type"] : 'NB',
      "source_type"       => isset($_POST["source_type"]) ? $_POST["source_type"] : '',
      "source_ref"       => isset($_POST["source_ref"]) ? $_POST["source_ref"] : '',
      "customer_id"       => $vendor->kode_pemasok,
      "purchasing_org"       => isset($_POST["purchasing_org"]) ? $_POST["purchasing_org"] : '',
      "purchasing_group"       => isset($_POST["purchasing_group"]) ? $_POST["purchasing_group"] : '',
      "plant"       => isset($_POST["plant"]) ? $_POST["plant"] : '',
      "storage_location"       => isset($_POST["storage_location"]) ? $_POST["storage_location"] : '',
      "po_date"              => $_POST["po_date"],
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],
      "seller_code"       => $vendor->kode_pemasok,
      "seller_name"       => $vendor->nama,
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
      "pajak"      => isset($_POST["tax"]) ? $_POST["tax"] : 'no',
      "consignee_name"    => $_POST["consignee_name"],
      "consignee_address" => $_POST["consignee_address"],
      "consignee_phone"   => $_POST["consignee_phone"],
      "consignee_email"   => $_POST["consignee_email"],
   );

   // print_r($data);
   // die();

    $db->query('START TRANSACTION');
    if (!$db->insert("purchase_order", $data)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }
    $poId = $db->last_insert_id();

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
         $qty = floatval(str_replace(",", ".", $_POST["qty"][$i]));
         $price = floatval(str_replace(",", ".", $_POST["harga"][$i]));
         if (trim((string)$_POST["kode"][$i])==='' || $qty<=0 || $price<0) {
           $db->query('ROLLBACK');
           action_response(po_action_t('purchase_order_item_invalid','Item PO baris').' '.($i+1).' '.po_action_t('common_invalid','tidak valid.'));
         }
         $detail = array(
            "id_po"       => $poId,
            "po_no"       => $no_po,
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => $qty,
            "harga"       => $price,
            "amount"      => $qty*$price,
            "ket"         => $_POST["ket"][$i],
         );
         foreach (array('id_pr','id_pr_detail','rfq_id','rfq_item_id','rfq_quotation_id') as $refField) {
           if (isset($_POST[$refField][$i]) && $_POST[$refField][$i] !== '') {
             $detail[$refField] = $_POST[$refField][$i];
           }
         }
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
         if (!$db->insert("purchase_order_detail", $detail)) {
           $error = $db->getErrorMessage();
           $db->query('ROLLBACK');
           action_response($error);
         }
      }
   }
    $db->query('COMMIT');
    action_response('', array('po_no'=>$no_po,'id_po'=>$poId));
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
    $po = $db->fetch("SELECT * FROM purchase_order WHERE purchase_order_no=? LIMIT 1", array('po'=>$_POST["purchase_order_no"]));
    if (!$po) action_response(po_action_t('purchase_order_not_found','PO tidak ditemukan.'));
    $vendor = $db->fetch("SELECT * FROM pemasok WHERE kode_pemasok=? LIMIT 1", array('kode'=>$_POST['seller_code']));
    if (!$vendor) action_response(po_action_t('purchase_order_vendor_invalid','Vendor tidak valid.'));

   $data = array(
      "purchase_order_no" => $_POST["purchase_order_no"],
      "po_type"       => isset($_POST["po_type"]) ? $_POST["po_type"] : 'NB',
      "source_type"       => isset($_POST["source_type"]) ? $_POST["source_type"] : '',
      "source_ref"       => isset($_POST["source_ref"]) ? $_POST["source_ref"] : '',
      "customer_id"       => $vendor->kode_pemasok,
      "purchasing_org"       => isset($_POST["purchasing_org"]) ? $_POST["purchasing_org"] : '',
      "purchasing_group"       => isset($_POST["purchasing_group"]) ? $_POST["purchasing_group"] : '',
      "plant"       => isset($_POST["plant"]) ? $_POST["plant"] : '',
      "storage_location"       => isset($_POST["storage_location"]) ? $_POST["storage_location"] : '',
      "po_date"              => $_POST["po_date"], 
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],

      "seller_code"       => $vendor->kode_pemasok,
      "seller_name"       => $vendor->nama,
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
      "pajak"      => isset($_POST["tax"]) ? $_POST["tax"] : 'no',

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
    

    
    
    $db->query('START TRANSACTION');
    if (!$db->update("purchase_order",$data,"purchase_order_no",$_POST["purchase_order_no"])) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $existingRows = $db->query("SELECT id,received_qty FROM purchase_order_detail WHERE po_no=? FOR UPDATE", array('po_no'=>$_POST["purchase_order_no"]));
    $existing = array();
    foreach ($existingRows as $row) $existing[$row->id] = $row;
    $postedIds = array();
    if (!empty($_POST['id_po_detail'])) {
      foreach ($_POST['id_po_detail'] as $postedId) {
        $postedId = intval($postedId);
        if ($postedId>0) $postedIds[] = $postedId;
      }
    }
    foreach ($existing as $existingId => $row) {
      if (!in_array($existingId, $postedIds)) {
        if ((float)$row->received_qty>0) {
          $db->query('ROLLBACK');
          action_response('Item yang sudah GR tidak bisa dihapus.');
        }
        $db->delete('purchase_order_detail','id',$existingId);
      }
    }
    // $db->query("delete from po_detail where nopo='".$_POST["purchase_order_no"]."'");
    // $db->query("delete from po where nopo='".$_POST["purchase_order_no"]."'");
   // $db->insert("po",$data2); 

     if (!empty($_POST["kode"])) {  
      foreach ($_POST["kode"] as $i => $kode) {
         $detailId = isset($_POST['id_po_detail'][$i]) ? intval($_POST['id_po_detail'][$i]) : 0;
         $qty = floatval(str_replace(",", ".", $_POST["qty"][$i]));
         $price = floatval(str_replace(",", ".", $_POST["harga"][$i]));
         $receivedQty = ($detailId>0 && isset($existing[$detailId])) ? (float)$existing[$detailId]->received_qty : 0;
         if ($qty + 0.00001 < $receivedQty) {
           $db->query('ROLLBACK');
           action_response('Qty PO tidak boleh lebih kecil dari Qty GR.');
         }
         $detail = array(
            "id_po"       => $po->id,
            "po_no"       => $_POST["purchase_order_no"],
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => $qty,
            "harga"       => $price,
            "amount"      => $qty*$price,
            "ket"         => $_POST["ket"][$i],
         );
         foreach (array('id_pr','id_pr_detail','rfq_id','rfq_item_id','rfq_quotation_id') as $refField) {
           if (isset($_POST[$refField][$i]) && $_POST[$refField][$i] !== '') {
             $detail[$refField] = $_POST[$refField][$i];
           }
         }
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
         if ($detailId>0) {
           if (!$db->update("purchase_order_detail", $detail, "id", $detailId)) {
             $error = $db->getErrorMessage();
             $db->query('ROLLBACK');
             action_response($error);
           }
         } else if (!$db->insert("purchase_order_detail", $detail)) {
           $error = $db->getErrorMessage();
           $db->query('ROLLBACK');
           action_response($error);
         }
      }
   }
    $db->query('COMMIT');
    action_response('');
    break;
  default:
    # code...
    break;
}

?>
