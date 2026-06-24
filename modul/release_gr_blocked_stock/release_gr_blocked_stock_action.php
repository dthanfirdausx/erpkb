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

session_check_json();

switch ($_GET["act"]) {
  case "in":
    $sourceLayerId = isset($_POST['source_layer_id']) ? intval($_POST['source_layer_id']) : 0;
    $releaseQty = isset($_POST['release_qty']) ? floatval($_POST['release_qty']) : 0;
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : $postingDate;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

    if ($sourceLayerId <= 0) action_response('Original GR blocked item wajib dipilih.');
    if ($releaseQty <= 0) action_response('Release Qty wajib lebih dari nol.');
    if ($documentDate === '') action_response('Document Date wajib diisi.');
    if ($postingDate === '') action_response('Posting Date wajib diisi.');

    $db->query('START TRANSACTION');

    $source = $db->fetch(
      "SELECT sl.*,
              p.id AS source_header_id,p.nomor AS source_nomor,p.no_bpb AS source_no_bpb,p.tgl_bpb,p.document_date AS source_document_date,
              p.posting_date AS source_posting_date,p.pemasok,p.no_invoice,p.tgl_invoice,p.no_do,p.catatan,p.no_aju,p.tgl_aju,
              p.jenis_dokpab,p.no_dokpab,p.tgl_dokpab,p.kantor_pabean,p.negara_asal,p.customs_status,p.kd_catdet,
              p.nopo,p.plant_id AS source_plant_id,p.storage_location_id AS source_storage_location_id,p.efaktur,p.tgl_efaktur,
              p.valuta,p.kurs,p.no_kontrak,p.tgl_kontrak,
              d.id AS source_detail_id,d.id_po_detail,d.no_urut,d.kode,d.jumlah,d.harga,d.valuta AS detail_valuta,d.nilai,d.berat,d.unit,
              d.customs_item_no,d.hs_code,d.customs_qty,d.customs_uom,d.customs_value,d.net_weight,d.gross_weight,
              d.package_type,d.package_qty,d.origin_country,d.lot_no,d.lokasi,d.storage_bin_id
       FROM stock_layer sl
       JOIN pemasukan_detail d ON d.id=sl.ref_id
       JOIN pemasukan p ON p.no_bpb=sl.no_bpb
       WHERE sl.id=?
         AND sl.stock_type='BLOCKED'
         AND sl.qty_sisa>0
       LIMIT 1
       FOR UPDATE",
      array('id' => $sourceLayerId)
    );

    if (!$source) {
      $db->query('ROLLBACK');
      action_response('Stock layer GR blocked tidak ditemukan atau sudah habis.');
    }

    if ((float) $source->qty_sisa + 0.00001 < $releaseQty) {
      $db->query('ROLLBACK');
      action_response('Release Qty melebihi sisa blocked stock. Sisa saat ini '.$source->qty_sisa.'.');
    }

    $year = date('Y', strtotime($postingDate));
    $releaseNoBpb = getNoBPB($year);
    $releaseNomor = get_nomor('pemasukan', 'id');
    $price = (float) $source->harga;
    $amount = $releaseQty * $price;
    $releaseReason = $reason !== '' ? $reason : 'Release GR blocked stock '.$source->source_no_bpb;
    $storageBinId = !empty($source->storage_bin_id) ? intval($source->storage_bin_id) : null;
    $plantId = !empty($source->plant_id) ? $source->plant_id : $source->source_plant_id;
    $storageLocationId = !empty($source->storage_location_id) ? $source->storage_location_id : $source->source_storage_location_id;

    $header = array(
      'no_bpb' => $releaseNoBpb,
      'nomor' => $releaseNomor,
      'tgl_bpb' => $postingDate,
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'pemasok' => $source->pemasok,
      'no_invoice' => $source->no_invoice,
      'tgl_invoice' => $source->tgl_invoice,
      'no_do' => 'REL-'.$source->source_no_bpb,
      'catatan' => substr($releaseReason, 0, 100),
      'no_aju' => $source->no_aju,
      'tgl_aju' => $source->tgl_aju,
      'jenis_dokpab' => $source->jenis_dokpab,
      'no_dokpab' => $source->no_dokpab,
      'tgl_dokpab' => $source->tgl_dokpab,
      'kantor_pabean' => $source->kantor_pabean,
      'negara_asal' => $source->negara_asal,
      'customs_status' => $source->customs_status,
      'userid' => $username,
      'kd_catdet' => $source->kd_catdet,
      'nopo' => $source->nopo,
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'stock_type' => 'UNRESTRICTED',
      'efaktur' => $source->efaktur,
      'tgl_efaktur' => $source->tgl_efaktur,
      'valuta' => $source->valuta,
      'kurs' => $source->kurs,
      'ref_no' => $source->source_no_bpb,
      'no_kontrak' => $source->no_kontrak,
      'tgl_kontrak' => $source->tgl_kontrak,
      'status' => 'POSTED'
    );

    foreach (array('tgl_invoice','tgl_efaktur','tgl_kontrak') as $optionalDate) {
      if (!isset($header[$optionalDate]) || trim((string) $header[$optionalDate]) === '') unset($header[$optionalDate]);
    }

    if (!$db->insert('pemasukan', $header)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $detail = array(
      'nomor' => $releaseNomor,
      'id_po_detail' => $source->id_po_detail,
      'no_bpb' => $releaseNoBpb,
      'tgl_bpb' => $postingDate,
      'kode' => $source->kode,
      'jumlah' => $releaseQty,
      'harga' => $price,
      'valuta' => $source->detail_valuta ?: $source->valuta,
      'nilai' => $amount,
      'berat' => $source->berat,
      'unit' => $source->unit,
      'no_urut' => $source->no_urut,
      'customs_item_no' => $source->customs_item_no,
      'hs_code' => $source->hs_code,
      'customs_qty' => $releaseQty,
      'customs_uom' => $source->customs_uom ?: $source->unit,
      'customs_value' => $amount,
      'net_weight' => $source->net_weight,
      'gross_weight' => $source->gross_weight,
      'package_type' => $source->package_type,
      'package_qty' => $source->package_qty,
      'origin_country' => $source->origin_country,
      'lot_no' => $source->lot_no,
      'no_aju' => $source->no_aju,
      'tgl_aju' => $source->tgl_aju,
      'tgl_masuk' => $postingDate,
      'jenis_dokpab' => $source->jenis_dokpab,
      'no_dokpab' => $source->no_dokpab,
      'tgl_dokpab' => $source->tgl_dokpab,
      'lokasi' => 'GUDANG',
      'storage_bin_id' => $storageBinId,
      'no_kontrak' => $source->no_kontrak,
      'userid' => $username
    );

    if (!$db->insert('pemasukan_detail', $detail)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }
    $releaseDetailId = $db->last_insert_id();

    $updateLayer = $db->query(
      "UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND stock_type='BLOCKED' AND qty_sisa>=?",
      array('qty' => $releaseQty, 'id' => $sourceLayerId, 'qty_check' => $releaseQty)
    );
    if (!$updateLayer) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    if (!$db->insert('stock_layer', array(
      'kode' => $source->kode,
      'qty_masuk' => $releaseQty,
      'qty_sisa' => $releaseQty,
      'lokasi' => 'GUDANG',
      'stock_type' => 'UNRESTRICTED',
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'storage_bin_id' => $storageBinId,
      'no_aju' => $source->no_aju,
      'no_dokpab' => $source->no_dokpab,
      'jenis_dokpab' => $source->jenis_dokpab,
      'ref_table' => 'pemasukan_detail',
      'ref_id' => $releaseDetailId,
      'no_bpb' => $releaseNoBpb,
      'tgl_masuk' => $postingDate,
      'created_at' => date('Y-m-d H:i:s')
    ))) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $transaction = array(
      'no_ref' => $releaseNoBpb,
      'ref_pengganti' => $source->source_no_bpb,
      'id_pemasukan' => $releaseNomor,
      'no_aju' => $source->no_aju,
      'no_dokpab' => $source->no_dokpab,
      'id_incoming_detail' => $source->source_detail_id,
      'move_code' => '105',
      'no_urut' => $source->no_urut,
      'posisi' => 'GUDANG',
      'qty' => $releaseQty,
      'id_bagian' => 1,
      'price' => $price,
      'weight' => $source->net_weight,
      'kd_barang' => $source->kode,
      'lokasi' => 'GUDANG',
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'user' => $username,
      'is_produksi' => '0',
      'direction' => 'IN',
      'ref_type' => 'GR_BLOCK_REL',
      'ref_id' => $sourceLayerId,
      'ref_detail_id' => $source->source_detail_id,
      'id_po_detail' => $source->id_po_detail,
      'uom' => $source->unit,
      'amount' => $amount,
      'reason' => $releaseReason,
      'created_by' => $username,
      'no_bpb' => $releaseNoBpb,
      'plant_id' => $source->plant_id,
      'storage_location_id' => $source->storage_location_id,
      'storage_bin_id' => $source->storage_bin_id,
      'stock_type' => 'UNRESTRICTED',
      'destination_storage_location_id' => $source->storage_location_id,
      'destination_storage_bin_id' => $source->storage_bin_id,
      'destination_stock_type' => 'UNRESTRICTED',
      'destination_material_code' => $source->kode,
      'remark' => 'Release GR blocked '.$source->source_no_bpb.' menjadi unrestricted'
    );

    if (!$db->insert('detail_transaksi', $transaction)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      action_response($error);
    }

    $db->query('COMMIT');

    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' release GR blocked stock '.$source->source_no_bpb.' material '.$source->kode.' qty '.$releaseQty.' menjadi unrestricted pada '.date('Y-m-d H:i:s'), $username);
    }

    action_response('', array('no_bpb' => $releaseNoBpb));
    break;

  default:
    action_response('Aksi tidak dikenal.');
    break;
}
?>
