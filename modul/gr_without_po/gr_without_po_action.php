<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
error_reporting(0);
session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
require_once "../../inc/gr_reversal.php";
session_check_json();

function gr_without_po_required($field, $label) {
  if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
    action_response($label.' wajib diisi.');
  }
}

function gr_without_po_rollback_response($message) {
  global $db;
  $db->query('ROLLBACK');
  action_response($message);
}

switch ($_GET["act"]) {
  case "reversal":
    $result = gr_perform_full_reversal(
      $_POST['id'],
      isset($_POST['reason']) ? $_POST['reason'] : '',
      isset($_POST['reversal_date']) ? $_POST['reversal_date'] : date('Y-m-d')
    );
    if ($result['status'] === 'good') {
      action_response('', $result['data']);
    }
    action_response($result['message']);
    break;

  case "search_material":
    header('Content-Type: application/json; charset=utf-8');
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT kd_barang,nm_barang,satuan
       FROM barang
       WHERE COALESCE(status,1)=1
         AND (?='' OR kd_barang LIKE ? OR nm_barang LIKE ?)
       ORDER BY kd_barang
       LIMIT 30",
      array('term' => $term, 'kode' => $like, 'nama' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang,
        'unit' => $row->satuan
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case "in":
    $requiredHeader = array(
      'document_date' => 'Document Date',
      'posting_date' => 'Posting Date',
      'stock_type' => 'Stock Type',
      'ref_no' => 'Reference No',
      'pemasok' => 'Vendor / Source',
      'plant_id' => 'Plant',
      'storage_location_id' => 'Storage Location',
      'no_do' => 'Delivery Note / Surat Jalan',
      'jenisbcmasuk_jenis_dokumen' => 'Jenis Dokumen BC',
      'no_aju' => 'Nomor Aju',
      'tgl_aju' => 'Tanggal Aju',
      'no_dokpab' => 'Nomor Pendaftaran',
      'tgl_dokpab' => 'Tanggal Pendaftaran'
    );
    foreach ($requiredHeader as $field => $label) gr_without_po_required($field, $label);
    if (empty($_POST['kode_input']) || !is_array($_POST['kode_input'])) action_response('Item material belum diisi.');

    foreach ($_POST['kode_input'] as $key => $kode) {
      $lineNo = $key + 1;
      $qty = isset($_POST['jumlah'][$key]) ? floatval($_POST['jumlah'][$key]) : 0;
      $price = isset($_POST['harga'][$key]) ? floatval($_POST['harga'][$key]) : 0;
      $storageBinId = isset($_POST['storage_bin_id'][$key]) ? trim((string) $_POST['storage_bin_id'][$key]) : '';
      $customsItemNo = isset($_POST['customs_item_no'][$key]) ? trim((string) $_POST['customs_item_no'][$key]) : '';
      $customsQty = isset($_POST['customs_qty'][$key]) ? floatval($_POST['customs_qty'][$key]) : 0;
      $customsUom = isset($_POST['customs_uom'][$key]) ? trim((string) $_POST['customs_uom'][$key]) : '';
      $customsValue = isset($_POST['customs_value'][$key]) ? floatval($_POST['customs_value'][$key]) : 0;
      if (trim((string) $kode) === '') action_response('Material item '.$lineNo.' wajib diisi.');
      if ($qty <= 0) action_response('GR Qty item '.$lineNo.' wajib lebih dari nol.');
      if ($price <= 0) action_response('Price item '.$lineNo.' wajib lebih dari nol.');
      if ($storageBinId === '') action_response('Storage Bin item '.$lineNo.' wajib diisi.');
      if ($customsItemNo === '') action_response('Item Pabean item '.$lineNo.' wajib diisi.');
      if ($customsQty <= 0) action_response('Qty Pabean item '.$lineNo.' wajib lebih dari nol.');
      if ($customsUom === '') action_response('Sat. Pabean item '.$lineNo.' wajib diisi.');
      if ($customsValue <= 0) action_response('Nilai Pabean item '.$lineNo.' wajib lebih dari nol.');
    }

    $postingDate = $_POST['posting_date'];
    $documentDate = $_POST['document_date'];
    $thn = date("Y", strtotime($postingDate));
    $no_bpb = getNoBPB($thn);
    $nomor = get_nomor("pemasukan", "id");
    $moveCode = '501';

    $data = array(
      "no_bpb" => $no_bpb,
      "nomor" => $nomor,
      "tgl_bpb" => $postingDate,
      "document_date" => $documentDate,
      "posting_date" => $postingDate,
      "nopo" => "GR_WITHOUT_PO",
      "ref_no" => $_POST["ref_no"],
      "plant_id" => $_POST["plant_id"],
      "storage_location_id" => $_POST["storage_location_id"],
      "stock_type" => $_POST["stock_type"],
      "pemasok" => $_POST["pemasok"],
      "no_do" => $_POST["no_do"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "kantor_pabean" => isset($_POST["kantor_pabean"]) ? $_POST["kantor_pabean"] : '',
      "customs_status" => $_POST["customs_status"],
      "catatan" => trim($_POST["reason_code"].' '.$_POST["reason_text"]),
      "jenis_dokpab" => $_POST["jenisbcmasuk_jenis_dokumen"],
      "kd_catdet" => isset($_POST["kd_catdet"]) ? $_POST["kd_catdet"] : '',
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "valuta" => isset($_POST["valuta"]) ? $_POST["valuta"] : '',
      "kurs" => isset($_POST["kurs"]) ? $_POST["kurs"] : '',
      "userid" => $_SESSION['username']
    );
    $db->query('START TRANSACTION');
    $headerSaved = $db->insert("pemasukan", $data);
    if (!$headerSaved) gr_without_po_rollback_response($db->getErrorMessage());
    simpan_log("Input GR Without PO dengan No Dokpab ".$_POST["no_dokpab"]." No Aju ".$_POST["no_aju"], $_SESSION['username']);

    $no = 1;
    $accountingItems = array();
    foreach ($_POST['kode_input'] as $key => $kode) {
      $barang = att_barang($kode);
      $qty = floatval($_POST['jumlah'][$key]);
      $price = floatval($_POST['harga'][$key]);
      $amount = $qty * $price;
      $storageBinId = intval($_POST['storage_bin_id'][$key]);
      $storageBin = $storageBinId ? $db->fetch_single_row('erp_storage_bin', 'id', $storageBinId) : null;
      $locationCode = $storageBin ? $storageBin->bin_code : '';

      $dataDetail = array(
        'nomor' => $nomor,
        'no_bpb' => $no_bpb,
        'tgl_bpb' => $postingDate,
        'kode' => $kode,
        'jumlah' => $qty,
        'harga' => $price,
        'valuta' => isset($_POST['valuta']) ? $_POST['valuta'] : '',
        'nilai' => $amount,
        'unit' => $_POST['unit'][$key],
        'berat' => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
        'lot_no' => isset($_POST['lot_no'][$key]) ? $_POST['lot_no'][$key] : '',
        'no_urut' => $no,
        'customs_item_no' => $_POST['customs_item_no'][$key],
        'hs_code' => isset($_POST['hs_code'][$key]) ? $_POST['hs_code'][$key] : '',
        'customs_qty' => $_POST['customs_qty'][$key],
        'customs_uom' => $_POST['customs_uom'][$key],
        'customs_value' => $_POST['customs_value'][$key],
        'net_weight' => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
        'gross_weight' => isset($_POST['gross_weight'][$key]) ? $_POST['gross_weight'][$key] : 0,
        'package_type' => isset($_POST['package_type'][$key]) ? $_POST['package_type'][$key] : '',
        'package_qty' => isset($_POST['package_qty'][$key]) ? $_POST['package_qty'][$key] : 0,
        'origin_country' => isset($_POST['origin_country'][$key]) ? $_POST['origin_country'][$key] : '',
        'no_aju' => $_POST['no_aju'],
        'tgl_aju' => $_POST['tgl_aju'],
        'tgl_masuk' => $_POST['tgl_aju'],
        'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
        'no_dokpab' => $_POST['no_dokpab'],
        'tgl_dokpab' => $_POST['tgl_dokpab'],
        'lokasi' => 'GUDANG',
        'storage_bin_id' => $storageBinId,
        'userid' => $_SESSION['username']
      );
      if (!$db->insert("pemasukan_detail", $dataDetail)) gr_without_po_rollback_response($db->getErrorMessage());
      $idDetail = $db->last_insert_id();
      $accountingItems[] = array(
        'kode' => $kode,
        'amount' => $amount,
        'valuta' => isset($_POST['valuta']) ? $_POST['valuta'] : '',
        'kurs' => isset($_POST['kurs']) ? $_POST['kurs'] : 1
      );

      if (!$db->insert("stock_layer", array(
        'kode' => $kode,
        'qty_masuk' => $qty,
        'qty_sisa' => $qty,
        'lokasi' => 'GUDANG',
        'plant_id' => $_POST['plant_id'],
        'storage_location_id' => $_POST['storage_location_id'],
        'storage_bin_id' => $storageBinId,
        'no_aju' => $_POST['no_aju'],
        'no_dokpab' => $_POST['no_dokpab'],
        'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
        'ref_table' => 'pemasukan_detail',
        'ref_id' => $idDetail,
        'no_bpb' => $no_bpb,
        'tgl_masuk' => $postingDate,
        'created_at' => date("Y-m-d H:i:s")
      ))) gr_without_po_rollback_response($db->getErrorMessage());

      if (!$db->insert("detail_transaksi", array(
        "no_ref" => $no_bpb,
        "id_pemasukan" => $nomor,
        "no_aju" => $_POST['no_aju'],
        "no_dokpab" => $_POST['no_dokpab'],
        "move_code" => $moveCode,
        "no_urut" => $no,
        "posisi" => 'GUDANG',
        "qty" => $qty,
        "id_bagian" => 1,
        "price" => $price,
        "weight" => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
        "kd_barang" => $kode,
        "lokasi" => 'GUDANG',
        "document_date" => $documentDate,
        "posting_date" => $postingDate,
        "user" => $_SESSION['username'],
        "is_produksi" => '0',
        "direction" => 'IN',
        "ref_type" => 'GR_WITHOUT_PO',
        "uom" => $_POST['unit'][$key],
        "amount" => $amount,
        "no_bpb" => $no_bpb,
        "plant_id" => $_POST['plant_id'],
        "storage_location_id" => $_POST['storage_location_id'],
        "storage_bin_id" => $storageBinId,
        "stock_type" => 'UNRESTRICTED',
        "destination_storage_location_id" => $_POST['storage_location_id'],
        "destination_storage_bin_id" => $storageBinId,
        "destination_stock_type" => 'UNRESTRICTED',
        "destination_material_code" => $kode,
        "created_by" => $_SESSION['username'],
        "reason" => isset($_POST['reason_code']) ? $_POST['reason_code'] : '',
        "remark" => 'GR without PO '.$_POST['ref_no']
      ))) gr_without_po_rollback_response($db->getErrorMessage());
      $no++;
    }

    $journalResult = accounting_post_auto_journal(
      'pembelian',
      $_POST['jenisbcmasuk_jenis_dokumen'],
      $accountingItems,
      array(
        'no_bukti' => $no_bpb,
        'tgl_jurnal' => $postingDate,
        'ket' => 'GR Without PO '.$no_bpb.' No Aju '.$_POST['no_aju'],
        'valuta' => isset($_POST['valuta']) ? $_POST['valuta'] : '',
        'kurs' => isset($_POST['kurs']) ? $_POST['kurs'] : 1
      )
    );
    if ($journalResult !== true) gr_without_po_rollback_response($journalResult);

    $db->query('COMMIT');
    action_response('', array('no_bpb' => $no_bpb));
    break;
}
?>
