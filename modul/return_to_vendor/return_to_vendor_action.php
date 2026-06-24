<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();

function rtv_json($status, $message = '', $extra = array())
{
  $payload = array_merge(array('status' => $status), $extra);
  if ($message !== '') $payload['error_message'] = $message;
  echo json_encode($payload);
  exit;
}

function rtv_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rtv_next_number($postingDate)
{
  global $db;
  $prefix = 'RTV'.date('Ym', strtotime($postingDate));
  $row = $db->fetch("SELECT return_no FROM erp_vendor_return WHERE return_no LIKE ? ORDER BY return_no DESC LIMIT 1", array('no' => $prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->return_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}

function rtv_source_header($noBpb)
{
  global $db;
  return $db->fetch(
    "SELECT p.*,v.nama AS vendor_name
     FROM pemasukan p
     LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
     WHERE p.no_bpb=? AND COALESCE(p.status,'POSTED')='POSTED' AND COALESCE(p.is_reversal,'N')<>'Y'
     LIMIT 1",
    array('no_bpb' => $noBpb)
  );
}

function rtv_source_items($noBpb)
{
  global $db;
  return $db->query(
    "SELECT d.id AS source_detail_id,d.no_urut,d.kode,d.unit,d.harga,d.nilai,d.no_aju,d.no_dokpab,d.jenis_dokpab,d.hs_code,d.lot_no,d.storage_bin_id,
            b.nm_barang,
            COALESCE(SUM(sl.qty_sisa),0) AS available_qty,
            GROUP_CONCAT(DISTINCT sl.stock_type ORDER BY sl.stock_type SEPARATOR ', ') AS stock_types,
            MAX(sl.plant_id) AS plant_id,
            MAX(sl.storage_location_id) AS storage_location_id
     FROM pemasukan_detail d
     LEFT JOIN barang b ON b.kd_barang=d.kode
     JOIN stock_layer sl ON sl.ref_table='pemasukan_detail' AND sl.ref_id=d.id AND sl.qty_sisa>0
     WHERE d.no_bpb=?
     GROUP BY d.id
     ORDER BY COALESCE(d.no_urut,d.id),d.id",
    array('no_bpb' => $noBpb)
  );
}

function rtv_returned_qty($sourceDetailId)
{
  global $db;
  $row = $db->fetch(
    "SELECT COALESCE(SUM(d.qty),0) AS qty
     FROM erp_vendor_return_detail d
     JOIN erp_vendor_return h ON h.id=d.return_id
     WHERE d.source_detail_id=? AND h.status='POSTED'",
    array('id' => $sourceDetailId)
  );
  return $row ? (float)$row->qty : 0;
}

function rtv_render_detail($id)
{
  global $db;
  $h = $db->fetch("SELECT * FROM erp_vendor_return WHERE id=? LIMIT 1", array('id' => $id));
  if (!$h) {
    echo "<div class='alert alert-warning'>Return document tidak ditemukan.</div>";
    return;
  }
  $items = $db->query("SELECT * FROM erp_vendor_return_detail WHERE return_id=? ORDER BY COALESCE(line_no,id),id", array('id' => $id));
  $history = $db->query("SELECT * FROM erp_vendor_return_history WHERE return_id=? ORDER BY changed_at DESC,id DESC", array('id' => $id));
  ?>
  <div class="row">
    <div class="col-md-8">
      <h3 style="margin-top:0;font-weight:700"><?=rtv_h($h->return_no);?> <small><?=rtv_h($h->vendor_code.' - '.$h->vendor_name);?></small></h3>
      <p class="text-muted">Source GR <?=rtv_h($h->source_no_bpb);?> | Movement Type <?=rtv_h($h->movement_type);?> | <?=rtv_h($h->return_reason_code);?></p>
    </div>
    <div class="col-md-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=rtv_h($h->status);?></span></div>
  </div>
  <div class="row">
    <div class="col-sm-3"><strong>Document Date</strong><br><?=rtv_h($h->document_date);?></div>
    <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></strong><br><?=rtv_h($h->posting_date);?></div>
    <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></strong><br><?=rtv_h($h->reference_no);?></div>
    <div class="col-sm-3"><strong>Created By</strong><br><?=rtv_h($h->created_by);?></div>
  </div>
  <hr>
  <h4>Return Items</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-condensed">
      <thead><tr class="bg-gray"><th>Line</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="text-right">Price</th><th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>No Aju</th><th>No Dokpab</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item) { ?>
        <tr>
          <td><?=intval($item->line_no);?></td>
          <td><strong><?=rtv_h($item->material_code);?></strong><br><small><?=rtv_h($item->material_name);?></small></td>
          <td class="text-right"><?=number_format((float)$item->qty,5,',','.');?></td>
          <td><?=rtv_h($item->uom);?></td>
          <td class="text-right"><?=number_format((float)$item->price,5,',','.');?></td>
          <td class="text-right"><?=number_format((float)$item->amount,5,',','.');?></td>
          <td><?=rtv_h($item->no_aju);?></td>
          <td><?=rtv_h($item->no_dokpab);?></td>
          <td><?=rtv_h($item->remarks);?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <h4>History</h4>
  <ul class="list-unstyled">
    <?php foreach ($history as $row) { ?>
      <li><strong><?=rtv_h(($row->status_lama ?: '-').' -> '.$row->status_baru);?></strong> <span class="text-muted"><?=rtv_h($row->changed_by.' @ '.$row->changed_at);?></span><br><?=rtv_h($row->remarks);?></li>
    <?php } ?>
  </ul>
  <?php
}

switch ($_GET['act']) {
  case 'gr_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT p.no_bpb,p.tgl_bpb,p.pemasok,COALESCE(v.nama,p.pemasok) AS vendor_name,p.no_aju,p.no_dokpab,COALESCE(SUM(sl.qty_sisa),0) AS available_qty
       FROM pemasukan p
       JOIN pemasukan_detail d ON d.no_bpb=p.no_bpb
       JOIN stock_layer sl ON sl.ref_table='pemasukan_detail' AND sl.ref_id=d.id AND sl.qty_sisa>0
       LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
       WHERE COALESCE(p.status,'POSTED')='POSTED'
         AND COALESCE(p.is_reversal,'N')<>'Y'
         AND (?='' OR p.no_bpb LIKE ? OR p.pemasok LIKE ? OR v.nama LIKE ? OR p.no_aju LIKE ? OR p.no_dokpab LIKE ?)
       GROUP BY p.no_bpb
       ORDER BY p.id DESC
       LIMIT 30",
      array('term' => $term, 'a' => $like, 'b' => $like, 'c' => $like, 'd' => $like, 'e' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->no_bpb,
        'text' => $row->no_bpb.' | '.$row->pemasok.' - '.$row->vendor_name.' | Avail '.number_format((float)$row->available_qty,5,'.','')
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'gr_items':
    $noBpb = isset($_POST['no_bpb']) ? trim($_POST['no_bpb']) : '';
    $header = rtv_source_header($noBpb);
    if (!$header) {
      echo "<div class='alert alert-warning'>Source GR tidak ditemukan atau tidak valid.</div>";
      break;
    }
    $items = rtv_source_items($noBpb);
    ?>
    <div class="alert alert-success">
      <strong><?=rtv_h($header->no_bpb);?></strong> - <?=rtv_h($header->pemasok.' / '.$header->vendor_name);?> |
      Posting <?=rtv_h($header->posting_date ?: $header->tgl_bpb);?> |
      BC <?=rtv_h(trim($header->jenis_dokpab.' '.$header->no_dokpab));?>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed rtv-items">
        <thead><tr><th>Return</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th class="text-right">Available</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="text-right">Return Qty</th><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><th>Customs Ref</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item) { $returned = rtv_returned_qty($item->source_detail_id); ?>
          <tr>
            <td class="text-center"><input type="checkbox" name="selected_detail[]" value="<?=intval($item->source_detail_id);?>"></td>
            <td><strong><?=rtv_h($item->kode);?></strong><br><small><?=rtv_h($item->nm_barang);?></small><input type="hidden" name="source_detail_id[]" value="<?=intval($item->source_detail_id);?>"></td>
            <td class="text-right"><?=number_format((float)$item->available_qty,5,',','.');?><br><small class="text-muted">Returned: <?=number_format($returned,5,',','.');?></small></td>
            <td><?=rtv_h($item->unit);?></td>
            <td><input type="number" step="0.00001" min="0" max="<?=rtv_h($item->available_qty);?>" name="return_qty[<?=intval($item->source_detail_id);?>]" class="form-control text-right" value="0"></td>
            <td><?=rtv_h($item->stock_types);?></td>
            <td><?=rtv_h($item->no_aju);?><br><small><?=rtv_h($item->jenis_dokpab.' '.$item->no_dokpab);?></small></td>
            <td><input name="item_remarks[<?=intval($item->source_detail_id);?>]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case 'post':
    $noBpb = isset($_POST['source_no_bpb']) ? trim($_POST['source_no_bpb']) : '';
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $reasonCode = isset($_POST['return_reason_code']) ? trim($_POST['return_reason_code']) : '';
    $reasonText = isset($_POST['return_reason_text']) ? trim($_POST['return_reason_text']) : '';
    if ($noBpb === '') rtv_json('error', 'Source Goods Receipt wajib dipilih.');
    if ($documentDate === '' || $postingDate === '') rtv_json('error', 'Document Date dan Posting Date wajib diisi.');
    if ($reasonCode === '' || $reasonText === '') rtv_json('error', 'Reason Code dan Reason Text wajib diisi.');
    if (empty($_POST['selected_detail']) || !is_array($_POST['selected_detail'])) rtv_json('error', 'Minimal satu item return wajib dipilih.');

    $header = rtv_source_header($noBpb);
    if (!$header) rtv_json('error', 'Source GR tidak ditemukan atau tidak valid.');
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $returnNo = rtv_next_number($postingDate);
    $plantId = !empty($_POST['plant_id']) ? (int)$_POST['plant_id'] : (int)$header->plant_id;
    $storageLocationId = !empty($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : (int)$header->storage_location_id;

    $selected = array();
    foreach ($_POST['selected_detail'] as $detailId) {
      $detailId = (int)$detailId;
      $qty = isset($_POST['return_qty'][$detailId]) ? (float)$_POST['return_qty'][$detailId] : 0;
      if ($detailId > 0 && $qty > 0) $selected[$detailId] = $qty;
    }
    if (empty($selected)) rtv_json('error', 'Return Qty wajib lebih dari nol untuk item yang dipilih.');

    $db->query('START TRANSACTION');
    $returnHeader = array(
      'return_no' => $returnNo,
      'source_no_bpb' => $header->no_bpb,
      'source_pemasukan_id' => $header->id,
      'vendor_code' => $header->pemasok,
      'vendor_name' => $header->vendor_name,
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'movement_type' => '122',
      'return_reason_code' => $reasonCode,
      'return_reason_text' => $reasonText,
      'reference_no' => isset($_POST['reference_no']) ? $_POST['reference_no'] : '',
      'plant_id' => $plantId,
      'storage_location_id' => $storageLocationId,
      'status' => 'POSTED',
      'created_by' => $username
    );
    if (!$db->insert('erp_vendor_return', $returnHeader)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      rtv_json('error', $err);
    }
    $returnId = $db->last_insert_id();

    $accountingItems = array();
    foreach ($selected as $detailId => $qty) {
      $src = $db->fetch(
        "SELECT d.*,b.nm_barang,b.kd_kategori,COALESCE(SUM(sl.qty_sisa),0) AS available_qty,MAX(sl.stock_type) AS stock_type
         FROM pemasukan_detail d
         LEFT JOIN barang b ON b.kd_barang=d.kode
         JOIN stock_layer sl ON sl.ref_table='pemasukan_detail' AND sl.ref_id=d.id AND sl.qty_sisa>0
         WHERE d.id=? AND d.no_bpb=?
         GROUP BY d.id
         LIMIT 1",
        array('id' => $detailId, 'no_bpb' => $header->no_bpb)
      );
      if (!$src) {
        $db->query('ROLLBACK');
        rtv_json('error', 'Source item tidak ditemukan atau stok sudah habis.');
      }
      if ((float)$src->available_qty + 0.00001 < $qty) {
        $db->query('ROLLBACK');
        rtv_json('error', 'Return Qty '.$src->kode.' melebihi available stock. Available '.$src->available_qty.'.');
      }
      $amount = $qty * (float)$src->harga;
      $detail = array(
        'return_id' => $returnId,
        'source_detail_id' => $src->id,
        'source_no_bpb' => $header->no_bpb,
        'line_no' => $src->no_urut,
        'material_code' => $src->kode,
        'material_name' => $src->nm_barang,
        'qty' => $qty,
        'uom' => $src->unit,
        'price' => $src->harga,
        'amount' => $amount,
        'no_aju' => $src->no_aju,
        'no_dokpab' => $src->no_dokpab,
        'jenis_dokpab' => $src->jenis_dokpab,
        'hs_code' => $src->hs_code,
        'lot_no' => $src->lot_no,
        'stock_type' => $src->stock_type,
        'storage_bin_id' => $src->storage_bin_id,
        'remarks' => isset($_POST['item_remarks'][$detailId]) ? $_POST['item_remarks'][$detailId] : ''
      );
      if (!$db->insert('erp_vendor_return_detail', $detail)) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        rtv_json('error', $err);
      }
      $returnDetailId = $db->last_insert_id();

      $remaining = $qty;
      $layers = $db->query(
        "SELECT * FROM stock_layer WHERE ref_table='pemasukan_detail' AND ref_id=? AND qty_sisa>0 ORDER BY id ASC FOR UPDATE",
        array('id' => $src->id)
      );
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        $remaining -= $take;
        $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array('qty' => $take, 'id' => $layer->id, 'check' => $take));
      }
      if ($remaining > 0.00001) {
        $db->query('ROLLBACK');
        rtv_json('error', 'Stock layer tidak cukup untuk item '.$src->kode.'.');
      }
      if (!empty($src->id_po_detail)) {
        $db->query("UPDATE purchase_order_detail SET received_qty=GREATEST(COALESCE(received_qty,0)-?,0) WHERE id=?", array('qty' => $qty, 'id' => $src->id_po_detail));
      }
      $transaction = array(
        'no_ref' => $returnNo,
        'ref_pengganti' => $header->no_bpb,
        'id_pemasukan' => $header->nomor,
        'no_aju' => $src->no_aju,
        'no_dokpab' => $src->no_dokpab,
        'id_incoming_detail' => $src->id,
        'move_code' => '122',
        'posisi' => 'GUDANG',
        'no_urut' => $src->no_urut,
        'qty' => $qty * -1,
        'id_bagian' => 1,
        'price' => $src->harga,
        'weight' => abs((float)$src->net_weight) * -1,
        'kd_barang' => $src->kode,
        'lokasi' => $src->lokasi ?: 'GUDANG',
        'document_date' => $documentDate,
        'posting_date' => $postingDate,
        'user' => $username,
        'is_produksi' => '0',
        'direction' => 'OUT',
        'ref_type' => 'RETURN_VENDOR',
        'ref_id' => $returnDetailId,
        'ref_detail_id' => $src->id,
        'id_po_detail' => $src->id_po_detail,
        'uom' => $src->unit,
        'amount' => $amount * -1,
        'reason' => $reasonCode,
        'created_by' => $username,
        'no_bpb' => $header->no_bpb,
        'destination_material_code' => $src->kode,
        'remark' => 'Return to vendor '.$header->no_bpb.' - '.$reasonText
      );
      if (!$db->insert('detail_transaksi', $transaction)) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        rtv_json('error', $err);
      }
      $accountingItems[] = array('kode'=>$src->kode,'amount'=>abs($amount),'kat_barang'=>$src->kd_kategori,'valuta'=>'IDR','kurs'=>1);
    }

    $journalResult = accounting_post_auto_journal('return_to_vendor', '', $accountingItems, array(
      'no_bukti' => $returnNo,
      'tgl_jurnal' => $postingDate,
      'ket' => 'Return to Vendor '.$returnNo.' from '.$header->no_bpb,
      'valuta' => 'IDR',
      'kurs' => 1,
      'source_module' => 'RETURN_TO_VENDOR'
    ));
    if ($journalResult !== true) { $db->query('ROLLBACK'); rtv_json('error', $journalResult); }

    $db->insert('erp_vendor_return_history', array(
      'return_id' => $returnId,
      'status_baru' => 'POSTED',
      'remarks' => 'Return to vendor posted from '.$header->no_bpb,
      'changed_by' => $username
    ));
    if (function_exists('simpan_log')) simpan_log($username.' posting Return to Vendor '.$returnNo.' dari GR '.$header->no_bpb, $username);
    $db->query('COMMIT');
    rtv_json('good', '', array('return_no' => $returnNo));
    break;

  case 'detail':
    rtv_render_detail((int)$_POST['id']);
    break;

  default:
    rtv_json('error', 'Action tidak dikenal.');
    break;
}
?>
