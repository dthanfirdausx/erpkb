<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function grprod_json($status, $message = '', $extra = array())
{
  $payload = array_merge(array('status' => $status), $extra);
  if ($message !== '') $payload['error_message'] = $message;
  echo json_encode($payload);
  exit;
}

function grprod_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function grprod_transfer($idTransfer)
{
  global $db;
  return $db->fetch(
    "SELECT t.*,bd.nm_bagian AS source_name,bk.nm_bagian AS destination_name,dp.nm_dept
     FROM transfer t
     LEFT JOIN bagian bd ON bd.id_bagian=t.dari
     LEFT JOIN bagian bk ON bk.id_bagian=t.ke
     LEFT JOIN dept dp ON dp.kd_dept=t.kd_dept
     WHERE t.id_transfer=? AND t.ke='1'
     LIMIT 1",
    array('id' => $idTransfer)
  );
}

function grprod_items($idTransfer, $noTransfer = '')
{
  global $db;
  return $db->query(
    "SELECT d.id_transfer_detail,d.no,d.jml,d.ket,b.id AS id_barang,b.kd_barang,b.nm_barang,b.satuan,
            COALESCE(lt.transit_qty,d.jml) AS transit_qty,
            lt.no_aju,lt.no_dokpab,lt.no_bpb
     FROM transfer_detail d
     LEFT JOIN barang b ON b.id=d.id_barang
     LEFT JOIN (
       SELECT kd_barang,no_ref,MAX(no_aju) AS no_aju,MAX(no_dokpab) AS no_dokpab,MAX(no_bpb) AS no_bpb,SUM(ABS(qty)) AS transit_qty
       FROM detail_transaksi
       WHERE posisi='TRANSIT' AND COALESCE(is_reversal,0)=0
       GROUP BY kd_barang,no_ref
     ) lt ON lt.kd_barang=b.kd_barang AND lt.no_ref=?
     WHERE d.id_transfer=?
     ORDER BY d.no,d.id_transfer_detail",
    array('ref' => $noTransfer, 'id' => $idTransfer)
  );
}

function grprod_render_detail($idTransfer, $compact = false)
{
  $header = grprod_transfer($idTransfer);
  if (!$header) {
    echo "<div class='alert alert-warning'>Dokumen transfer produksi tidak ditemukan.</div>";
    return;
  }
  $items = grprod_items($header->id_transfer, $header->no_transfer);
  $status = ((string)$header->status === '1') ? "<span class='label label-success'>POSTED</span>" : "<span class='label label-warning'>OUTSTANDING</span>";
  ?>
  <div class="grprod-detail">
    <?php if (!$compact) { ?>
      <div class="row">
        <div class="col-md-8">
          <h3 style="margin-top:0;font-weight:700"><?=grprod_h($header->no_transfer);?> <small><?=grprod_h($header->no_ro);?></small></h3>
          <p class="text-muted"><?=grprod_h($header->ket);?></p>
        </div>
        <div class="col-md-4 text-right"><?=$status;?></div>
      </div>
      <div class="row grprod-summary">
        <div class="col-sm-3"><span>Source</span><strong><?=grprod_h($header->source_name ?: $header->dari);?></strong></div>
        <div class="col-sm-3"><span>Destination</span><strong><?=grprod_h($header->destination_name ?: $header->ke);?></strong></div>
        <div class="col-sm-3"><span>Transfer Date</span><strong><?=grprod_h($header->tgl_transfer);?></strong></div>
        <div class="col-sm-3"><span>Material Doc</span><strong><?=grprod_h($header->no_terima ?: '-');?></strong></div>
      </div>
    <?php } ?>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead><tr class="bg-gray"><th>No</th><th>Material</th><th class="text-right">Transfer Qty</th><th class="text-right">Transit Qty</th><th>UOM</th><th>No Aju</th><th>No Dokpab</th><th>Remark</th></tr></thead>
        <tbody>
        <?php
        $total = 0;
        foreach ($items as $item) {
          $total += (float)$item->jml;
          ?>
          <tr>
            <td><?=intval($item->no);?></td>
            <td><strong><?=grprod_h($item->kd_barang);?></strong><br><small class="text-muted"><?=grprod_h($item->nm_barang);?></small></td>
            <td class="text-right"><?=number_format((float)$item->jml,5,',','.');?></td>
            <td class="text-right"><?=number_format((float)$item->transit_qty,5,',','.');?></td>
            <td><?=grprod_h($item->satuan);?></td>
            <td><?=grprod_h($item->no_aju);?></td>
            <td><?=grprod_h($item->no_dokpab);?></td>
            <td><?=grprod_h($item->ket);?></td>
          </tr>
        <?php } ?>
        </tbody>
        <tfoot><tr><th colspan="2" class="text-right">Total</th><th class="text-right"><?=number_format($total,5,',','.');?></th><th colspan="5"></th></tr></tfoot>
      </table>
    </div>
  </div>
  <?php
}

switch ($_GET["act"]) {
  case "detail":
    grprod_render_detail((int)$_POST['id_transfer']);
    break;

  case "item_preview":
    grprod_render_detail((int)$_POST['id_transfer'], true);
    break;

  case "receive":
    $idTransfer = isset($_POST['id_transfer']) ? (int)$_POST['id_transfer'] : 0;
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $plant = isset($_POST['plant']) ? trim($_POST['plant']) : '';
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) && $_POST['storage_bin_id'] !== '' ? (int)$_POST['storage_bin_id'] : null;
    $stockType = isset($_POST['stock_type']) ? trim($_POST['stock_type']) : 'UNRESTRICTED';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if ($idTransfer <= 0) grprod_json('error', 'Dokumen transfer wajib dipilih.');
    if ($postingDate === '' || $documentDate === '') grprod_json('error', 'Document Date dan Posting Date wajib diisi.');
    if ($plant === '' || $storageLocationId <= 0) grprod_json('error', 'Plant dan Storage Location wajib diisi.');
    if (!in_array($stockType, array('UNRESTRICTED','QUALITY','BLOCKED'))) grprod_json('error', 'Stock Type tidak valid.');

    $header = grprod_transfer($idTransfer);
    if (!$header) grprod_json('error', 'Dokumen transfer produksi tidak ditemukan.');
    if ((string)$header->status === '1') grprod_json('error', 'Dokumen ini sudah pernah diposting GR.');

    $plantRow = $db->fetch("SELECT id,plant_code FROM erp_plant WHERE plant_code=? LIMIT 1", array('plant' => $plant));
    if (!$plantRow) grprod_json('error', 'Plant tidak valid.');
    $sloc = $db->fetch("SELECT * FROM erp_storage_location WHERE id=? AND plant_id=? AND status='Aktif' LIMIT 1", array('id' => $storageLocationId, 'plant_id' => $plantRow->id));
    if (!$sloc) grprod_json('error', 'Storage Location tidak sesuai Plant.');
    if ($storageBinId) {
      $bin = $db->fetch("SELECT * FROM erp_storage_bin WHERE id=? AND storage_location_id=? AND status='Aktif' LIMIT 1", array('id' => $storageBinId, 'sloc' => $storageLocationId));
      if (!$bin) grprod_json('error', 'Storage Bin tidak sesuai Storage Location.');
    }

    $items = grprod_items($header->id_transfer, $header->no_transfer);
    $itemCount = 0;
    foreach ($items as $x) $itemCount++;
    if ($itemCount === 0) grprod_json('error', 'Item transfer tidak ditemukan.');

    $noTerima = GetNoTerima($idTransfer, 5);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

    $db->query('START TRANSACTION');
    $locked = $db->fetch("SELECT status FROM transfer WHERE id_transfer=? FOR UPDATE", array('id' => $idTransfer));
    if (!$locked || (string)$locked->status === '1') {
      $db->query('ROLLBACK');
      grprod_json('error', 'Dokumen sudah diposting oleh user lain.');
    }

    $updated = $db->query(
      "UPDATE transfer
       SET status='1', no_terima=?, tgl_terima=?, user_terima=?
       WHERE id_transfer=?",
      array('no_terima' => $noTerima, 'tgl' => $postingDate.' '.date('H:i:s'), 'user' => $username, 'id' => $idTransfer)
    );
    if (!$updated) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      grprod_json('error', $err);
    }

    $detailRows = grprod_items($header->id_transfer, $header->no_transfer);
    foreach ($detailRows as $item) {
      $qty = (float)$item->transit_qty > 0 ? (float)$item->transit_qty : (float)$item->jml;

      $db->query(
        "UPDATE detail_transaksi
         SET posisi='GUDANG',
             move_code='101',
             document_date=?,
             posting_date=?,
             no_bpb=?,
             reason=?,
             destination_storage_location_id=?,
             destination_storage_bin_id=?,
             destination_stock_type=?,
             destination_material_code=COALESCE(destination_material_code,kd_barang),
             updated_at=NOW(),
             remark=CONCAT('GR production ', ?)
         WHERE no_ref=? AND kd_barang=? AND posisi='TRANSIT' AND COALESCE(is_reversal,0)=0",
        array('doc' => $documentDate, 'post' => $postingDate, 'no_bpb' => $noTerima, 'reason' => $reason, 'dest_sloc' => $header->destination_storage_location_id, 'dest_bin' => $header->destination_storage_bin_id, 'dest_stock_type' => $header->destination_stock_type, 'ref1' => $header->no_transfer, 'ref2' => $header->no_transfer, 'kode' => $item->kd_barang)
      );

      $remaining = $qty;
      $layers = $db->query(
        "SELECT * FROM stock_layer
         WHERE kode=? AND lokasi='TRANSIT' AND qty_sisa>0
         ORDER BY id ASC
         FOR UPDATE",
        array('kode' => $item->kd_barang)
      );
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        $remaining -= $take;
        $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=?", array('qty' => $take, 'id' => $layer->id));
        $db->insert('stock_layer', array(
          'kode' => $item->kd_barang,
          'qty_masuk' => $take,
          'qty_sisa' => $take,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'lokasi' => 'GUDANG',
          'stock_type' => $stockType,
          'plant_id' => $plantRow->id,
          'storage_location_id' => $storageLocationId,
          'storage_bin_id' => $storageBinId,
          'jenis_dokpab' => $layer->jenis_dokpab,
          'ref_table' => 'transfer',
          'ref_id' => $idTransfer,
          'tgl_masuk' => $postingDate,
          'no_bpb' => $noTerima
        ));
      }

      if ($remaining > 0) {
        $db->insert('stock_layer', array(
          'kode' => $item->kd_barang,
          'qty_masuk' => $remaining,
          'qty_sisa' => $remaining,
          'no_aju' => $item->no_aju,
          'no_dokpab' => $item->no_dokpab,
          'lokasi' => 'GUDANG',
          'stock_type' => $stockType,
          'plant_id' => $plantRow->id,
          'storage_location_id' => $storageLocationId,
          'storage_bin_id' => $storageBinId,
          'ref_table' => 'transfer',
          'ref_id' => $idTransfer,
          'tgl_masuk' => $postingDate,
          'no_bpb' => $noTerima
        ));
      }
    }

    if (function_exists('simpan_log')) {
      simpan_log("{$username} posting GR from Production {$header->no_transfer} menjadi material document {$noTerima} pada {$postingDate}", $username);
    }
    $db->query('COMMIT');
    grprod_json('good', '', array('no_terima' => $noTerima));
    break;

  case "delete":
    grprod_json('error', 'Dokumen GR produksi tidak boleh dihapus dari workbench. Gunakan reversal bila sudah tersedia.');
    break;

  case "del_massal":
    grprod_json('error', 'Bulk delete tidak diperbolehkan untuk dokumen material.');
    break;

  case "in":
  case "up":
    grprod_json('error', 'Input manual GR production dinonaktifkan. Posting harus dari dokumen transfer produksi outstanding.');
    break;

  default:
    grprod_json('error', 'Action tidak dikenal.');
    break;
}
?>
