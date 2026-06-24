<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function tp_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}

function tp_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tp_num($value, $dec = 5) {
  return number_format((float)$value, $dec, ',', '.');
}

function tp_clean_qty($value) {
  return (float)str_replace(',', '.', trim((string)$value));
}

function tp_status_label($status) {
  if ((string)$status === '1') return '<span class="label label-success">Received</span>';
  if ((string)$status === '9') return '<span class="label label-danger">Reversed</span>';
  return '<span class="label label-warning">Open</span>';
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT v.id_barang,v.kd_barang,v.nm_barang,v.satuan,v.stock,v.plant_code,v.storage_location,v.storage_bin
       FROM v_stock_transaksi v
       WHERE (?='' OR v.kd_barang LIKE ? OR v.nm_barang LIKE ?)
       ORDER BY v.kd_barang
       LIMIT 30",
      array('term' => $term, 'kd' => $like, 'name' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang.' | Stock '.tp_num($row->stock).' '.$row->satuan,
        'id_barang' => $row->id_barang,
        'uom' => $row->satuan,
        'stock' => (float)$row->stock,
        'plant' => $row->plant_code,
        'storage_location' => $row->storage_location,
        'storage_bin' => $row->storage_bin
      );
    }
    header('Content-Type: application/json');
    echo json_encode(array('results' => $results));
    break;

  case 'material_master_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT id,kd_barang,nm_barang,satuan
       FROM barang
       WHERE (?='' OR kd_barang LIKE ? OR nm_barang LIKE ?)
       ORDER BY kd_barang
       LIMIT 30",
      array('term' => $term, 'kd' => $like, 'name' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang,
        'id_barang' => $row->id,
        'uom' => $row->satuan
      );
    }
    header('Content-Type: application/json');
    echo json_encode(array('results' => $results));
    break;

  case 'material_stock':
    $kode = isset($_POST['kode']) ? trim($_POST['kode']) : '';
    $stock = $db->fetch("SELECT COALESCE(SUM(qty_sisa),0) AS stock FROM stock_layer WHERE kode=? AND qty_sisa>0 AND lokasi='GUDANG' AND COALESCE(stock_type,'UNRESTRICTED')='UNRESTRICTED'", array('kode' => $kode));
    tp_json('good', '', array('stock' => (float)$stock->stock));
    break;

  case 'get_tgl_ro':
    $noRo = isset($_POST['no_ro']) ? trim($_POST['no_ro']) : '';
    $row = $db->fetch("SELECT tgl_ro FROM ro WHERE no_ro=? LIMIT 1", array('no_ro' => $noRo));
    echo $row ? $row->tgl_ro : '';
    break;

  case 'get_detail_ro':
    $noRo = isset($_POST['no_ro']) ? trim($_POST['no_ro']) : '';
    $rows = $db->query(
      "SELECT r.*,b.id AS id_barang,b.nm_barang,b.satuan,COALESCE(v.stock,0) AS available_stock
       FROM ro_detail r
       JOIN barang b ON b.kd_barang=r.kode
       LEFT JOIN (
         SELECT kd_barang,SUM(stock) AS stock
         FROM v_stock_transaksi
         GROUP BY kd_barang
       ) v ON v.kd_barang=r.kode
       WHERE r.no_ro=?
       ORDER BY COALESCE(r.row_no,0),r.kode",
      array('no_ro' => $noRo)
    );
    $items = array();
    foreach ($rows as $row) {
      $qty = min((float)$row->jumlah, (float)$row->available_stock);
      $items[] = array(
        'id' => $row->kode,
        'text' => $row->kode.' - '.$row->nm_barang.' | Stock '.tp_num($row->available_stock).' '.$row->satuan,
        'id_barang' => $row->id_barang,
        'uom' => $row->satuan,
        'stock' => (float)$row->available_stock,
        'qty' => $qty > 0 ? $qty : ''
      );
    }
    tp_json('good', '', array('items' => $items));
    break;

  case 'show_detail':
    $noSpb = isset($_POST['no_spb']) ? trim($_POST['no_spb']) : '';
    $header = $db->fetch(
      "SELECT t.*,src.nm_bagian AS source_name,dst.nm_bagian AS destination_name,
              dsl.storage_code AS destination_storage_code,
              dsl.storage_name AS destination_storage_name,
              dsb.bin_code AS destination_bin_code,
              dsb.bin_name AS destination_bin_name
       FROM transfer t
       LEFT JOIN bagian src ON src.id_bagian=t.dari
       LEFT JOIN bagian dst ON dst.id_bagian=t.ke
       LEFT JOIN erp_storage_location dsl ON dsl.id=t.destination_storage_location_id
       LEFT JOIN erp_storage_bin dsb ON dsb.id=t.destination_storage_bin_id
       WHERE t.no_transfer=?
       LIMIT 1",
      array('no_transfer' => $noSpb)
    );
    if (!$header) {
      echo '<div class="alert alert-danger">Transfer document tidak ditemukan.</div>';
      exit;
    }
    $destinationLabel = trim($header->destination_storage_code.' - '.$header->destination_storage_name.' / '.$header->destination_bin_code.' - '.$header->destination_bin_name, ' -/');
    if ($destinationLabel === '') $destinationLabel = $header->destination_name;
    $details = $db->query(
      "SELECT td.no,td.jml,td.ket,td.destination_material_code,
              b.kd_barang,b.nm_barang,b.satuan,
              bd.nm_barang AS destination_material_name,
              dt.no_aju,dt.no_dokpab,dt.move_code,dt.qty,dt.posting_date,dt.remark,
              dt.direction,dt.destination_stock_type,
              sl.no_bpb,sl.stock_type,ep.plant_code,es.storage_code,eb.bin_code,
              dsl.storage_code AS trx_destination_storage_code,
              dsb.bin_code AS trx_destination_bin_code
       FROM transfer_detail td
       LEFT JOIN barang b ON b.id=td.id_barang
       LEFT JOIN barang bd ON bd.kd_barang=td.destination_material_code
       LEFT JOIN detail_transaksi dt ON dt.no_ref=? AND dt.kd_barang=b.kd_barang AND dt.no_urut=td.no AND dt.posisi='GUDANG' AND dt.direction='OUT'
       LEFT JOIN stock_layer sl ON sl.id=dt.ref_id
       LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
       LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
       LEFT JOIN erp_storage_location dsl ON dsl.id=dt.destination_storage_location_id
       LEFT JOIN erp_storage_bin dsb ON dsb.id=dt.destination_storage_bin_id
       WHERE td.id_transfer=?
       ORDER BY td.no,td.id_transfer_detail",
      array('no_ref' => $noSpb, 'id_transfer' => $header->id_transfer)
    );
    ?>
    <style>.tp-detail-table th{width:175px;background:#f8fafc}.tp-items td,.tp-items th{font-size:12px;vertical-align:middle!important}</style>
    <div class="row">
      <div class="col-md-6">
        <table class="table table-bordered table-condensed tp-detail-table">
          <tr><th>Transfer Doc</th><td><?=tp_h($header->no_transfer);?></td></tr>
          <tr><th>Posting Date</th><td><?=tp_h($header->tgl_transfer);?></td></tr>
          <tr><th>Movement</th><td><?=((string)$header->status==='9')?'312 - Reversal Transfer Posting':'311 - Transfer Posting';?></td></tr>
          <tr><th>Status</th><td><?=tp_status_label($header->status);?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-bordered table-condensed tp-detail-table">
          <tr><th>Source</th><td><?=tp_h($header->source_name ?: 'Gudang');?></td></tr>
          <tr><th>Destination</th><td><?=tp_h($destinationLabel);?></td></tr>
          <tr><th>Destination Stock Type</th><td><?=tp_h($header->destination_stock_type);?></td></tr>
          <tr><th>Reference Request</th><td><?=tp_h($header->no_ro);?></td></tr>
          <tr><th>Remark</th><td><?=tp_h($header->ket);?></td></tr>
        </table>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-condensed tp-items">
        <thead><tr><th>Item</th><th>Source Material</th><th>Destination Material</th><th>Source Layer</th><th>Destination</th><th>No Aju</th><th>No Dokpab</th><th class="text-right">Qty</th><th>UOM</th><th>Move</th><th>Remark</th></tr></thead>
        <tbody>
          <?php foreach ($details as $row) { ?>
          <?php $destMaterial = $row->destination_material_code ? $row->destination_material_code.' - '.$row->destination_material_name : $row->kd_barang.' - '.$row->nm_barang; ?>
          <tr>
            <td><?=tp_h($row->no);?></td>
            <td><strong><?=tp_h($row->kd_barang);?></strong><br><small><?=tp_h($row->nm_barang);?></small></td>
            <td><?=tp_h($destMaterial);?></td>
            <td><?=tp_h(trim($row->no_bpb.' / '.$row->plant_code.' / '.$row->storage_code.' / '.$row->bin_code, ' /'));?></td>
            <td><?=tp_h(trim($row->trx_destination_storage_code.' / '.$row->trx_destination_bin_code.' / '.$row->destination_stock_type, ' /'));?></td>
            <td><?=tp_h($row->no_aju);?></td>
            <td><?=tp_h($row->no_dokpab);?></td>
            <td class="text-right"><?=tp_num(abs((float)($row->qty ?: $row->jml)));?></td>
            <td><?=tp_h($row->satuan);?></td>
            <td><?=tp_h($row->move_code);?></td>
            <td><?=tp_h($row->remark ?: $row->ket);?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case 'in':
    $postingDate = isset($_POST['tgl_spb']) ? trim($_POST['tgl_spb']) : '';
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : $postingDate;
    $destinationStorageLocationId = isset($_POST['destination_storage_location_id']) ? intval($_POST['destination_storage_location_id']) : 0;
    $destinationStorageBinId = isset($_POST['destination_storage_bin_id']) ? intval($_POST['destination_storage_bin_id']) : 0;
    $destinationStockType = isset($_POST['destination_stock_type']) ? trim($_POST['destination_stock_type']) : 'UNRESTRICTED';
    $remark = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';
    $noRequest = isset($_POST['no_request']) ? trim($_POST['no_request']) : '';
    $tglRequest = isset($_POST['tgl_request']) ? trim($_POST['tgl_request']) : null;

    if ($postingDate === '') tp_json('error', 'Posting Date wajib diisi.');
    if ($documentDate === '') tp_json('error', 'Document Date wajib diisi.');
    if ($destinationStorageLocationId <= 0) tp_json('error', 'Destination Storage Location wajib dipilih.');
    if ($destinationStorageBinId <= 0) tp_json('error', 'Destination Storage Bin wajib dipilih.');
    if (!in_array($destinationStockType, array('UNRESTRICTED','QUALITY','BLOCKED'), true)) tp_json('error', 'Destination Stock Type tidak valid.');
    if ($remark === '') tp_json('error', 'Reason / Remark wajib diisi.');
    if (empty($_POST['kode_input']) || !is_array($_POST['kode_input'])) tp_json('error', 'Minimal satu item material wajib diisi.');

    $destRow = $db->fetch(
      "SELECT s.id,s.storage_code,s.storage_name,s.plant_id,b.id AS bin_id,b.bin_code,b.bin_name
       FROM erp_storage_location s
       JOIN erp_storage_bin b ON b.storage_location_id=s.id
       WHERE s.id=? AND b.id=? AND s.status='Aktif' AND b.status='Aktif'
       LIMIT 1",
      array('storage_location_id' => $destinationStorageLocationId, 'bin_id' => $destinationStorageBinId)
    );
    if (!$destRow) tp_json('error', 'Kombinasi destination storage location/bin tidak valid.');
    $destinationName = trim($destRow->storage_code.' - '.$destRow->storage_name.' / '.$destRow->bin_code.' - '.$destRow->bin_name);

    $db->query('START TRANSACTION');
    $header = array(
      'tgl_transfer' => $postingDate,
      'no_ro' => $noRequest,
      'tgl_ro' => $tglRequest,
      'user' => $username,
      'ket' => $remark,
      'kd_dept' => '',
      'status' => '0',
      'dari' => 1,
      'ke' => 1,
      'destination_storage_location_id' => $destinationStorageLocationId,
      'destination_storage_bin_id' => $destinationStorageBinId,
      'destination_stock_type' => $destinationStockType,
      'date_created' => date('Y-m-d H:i:s')
    );
    if (!$db->insert('transfer', $header)) {
      $error = $db->getErrorMessage();
      $db->query('ROLLBACK');
      tp_json('error', $error ?: 'Header transfer gagal disimpan.');
    }
    $transferId = $db->last_insert_id();
    $noTransfer = GetNoTransfer($transferId, 5);
    $db->query("UPDATE transfer SET no_transfer=? WHERE id_transfer=?", array('no' => $noTransfer, 'id' => $transferId));

    $lineNo = 1;
    foreach ($_POST['kode_input'] as $key => $kodeBarang) {
      $kodeBarang = trim((string)$kodeBarang);
      $qtyRequest = isset($_POST['qty'][$key]) ? tp_clean_qty($_POST['qty'][$key]) : 0;
      $idBarang = isset($_POST['id_input'][$key]) ? intval($_POST['id_input'][$key]) : 0;
      $destinationMaterialCode = isset($_POST['destination_material_code'][$key]) ? trim((string)$_POST['destination_material_code'][$key]) : '';
      $lineRemark = isset($_POST['ket'][$key]) ? trim($_POST['ket'][$key]) : '';
      if ($destinationMaterialCode === '') $destinationMaterialCode = $kodeBarang;

      if ($kodeBarang === '') {
        $db->query('ROLLBACK');
        tp_json('error', 'Material pada item '.$lineNo.' wajib dipilih.');
      }
      if ($qtyRequest <= 0) {
        $db->query('ROLLBACK');
        tp_json('error', 'Qty item '.$kodeBarang.' wajib lebih dari nol.');
      }
      if ($idBarang <= 0) {
        $material = $db->fetch("SELECT id FROM barang WHERE kd_barang=? LIMIT 1", array('kode' => $kodeBarang));
        $idBarang = $material ? intval($material->id) : 0;
      }
      $destinationMaterial = $db->fetch("SELECT id,kd_barang,nm_barang,satuan FROM barang WHERE kd_barang=? LIMIT 1", array('kode' => $destinationMaterialCode));
      if (!$destinationMaterial) {
        $db->query('ROLLBACK');
        tp_json('error', 'Destination material '.$destinationMaterialCode.' pada item '.$lineNo.' tidak ditemukan.');
      }

      $available = $db->fetch(
        "SELECT COALESCE(SUM(qty_sisa),0) AS stock
         FROM stock_layer
         WHERE kode=? AND qty_sisa>0 AND lokasi='GUDANG' AND COALESCE(stock_type,'UNRESTRICTED')='UNRESTRICTED'",
        array('kode' => $kodeBarang)
      );
      if ((float)$available->stock + 0.00001 < $qtyRequest) {
        $db->query('ROLLBACK');
        tp_json('error', 'Stock tidak cukup untuk '.$kodeBarang.'. Available '.tp_num($available->stock).', request '.tp_num($qtyRequest).'.');
      }

      $remaining = $qtyRequest;
      $layers = $db->query(
        "SELECT *
         FROM stock_layer
         WHERE kode=? AND qty_sisa>0 AND lokasi='GUDANG' AND COALESCE(stock_type,'UNRESTRICTED')='UNRESTRICTED'
         ORDER BY tgl_masuk ASC,id ASC
         FOR UPDATE",
        array('kode' => $kodeBarang)
      );
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        if ($take <= 0) continue;

        if (!$db->insert('transfer_detail', array(
          'id_transfer' => $transferId,
          'id_barang' => $idBarang,
          'id_incoming_detail' => !empty($layer->ref_id) ? $layer->ref_id : null,
          'destination_material_code' => $destinationMaterialCode,
          'jml' => $take,
          'no' => $lineNo,
          'ket' => $lineRemark,
          'date_created' => date('Y-m-d H:i:s')
        ))) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          tp_json('error', $error ?: 'Detail transfer gagal disimpan.');
        }
        $transferDetailId = $db->last_insert_id();

        $trx = array(
          'no_ref' => $noTransfer,
          'move_code' => '311',
          'posisi' => 'GUDANG',
          'no_urut' => $lineNo,
          'qty' => $take * -1,
          'kd_barang' => $kodeBarang,
          'lokasi' => 'GUDANG',
          'document_date' => $documentDate,
          'posting_date' => $postingDate,
          'user' => $username,
          'direction' => 'OUT',
          'ref_type' => 'TRANSFER_POST',
          'ref_id' => $layer->id,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'uom' => isset($_POST['unit'][$key]) ? $_POST['unit'][$key] : null,
          'destination_storage_location_id' => $destinationStorageLocationId,
          'destination_storage_bin_id' => $destinationStorageBinId,
          'destination_stock_type' => $destinationStockType,
          'destination_material_code' => $destinationMaterialCode,
          'reason' => $remark,
          'remark' => 'Transfer posting 311 ke '.$destinationName,
          'created_by' => $username,
          'date_created' => date('Y-m-d H:i:s')
        );
        if (!$db->insert('detail_transaksi', $trx)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          tp_json('error', $error ?: 'Material document transfer gagal disimpan.');
        }

        $update = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array('qty' => $take, 'id' => $layer->id, 'check' => $take));
        if (!$update) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          tp_json('error', $error ?: 'Stock layer gagal diperbarui.');
        }

        $destLayer = array(
          'kode' => $destinationMaterialCode,
          'qty_masuk' => $take,
          'qty_sisa' => $take,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'lokasi' => 'GUDANG',
          'stock_type' => $destinationStockType,
          'plant_id' => $destRow->plant_id,
          'storage_location_id' => $destinationStorageLocationId,
          'storage_bin_id' => $destinationStorageBinId,
          'jenis_dokpab' => $layer->jenis_dokpab,
          'ref_table' => 'transfer_detail',
          'ref_id' => $transferDetailId,
          'tgl_masuk' => $postingDate,
          'no_bpb' => $noTransfer,
          'created_at' => date('Y-m-d H:i:s')
        );
        if (!$db->insert('stock_layer', $destLayer)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          tp_json('error', $error ?: 'Destination stock layer gagal dibuat.');
        }
        $destinationLayerId = $db->last_insert_id();

        $trxIn = $trx;
        $trxIn['qty'] = $take;
        $trxIn['kd_barang'] = $destinationMaterialCode;
        $trxIn['direction'] = 'IN';
        $trxIn['ref_id'] = $destinationLayerId;
        $trxIn['uom'] = $destinationMaterial->satuan ?: $trx['uom'];
        $trxIn['remark'] = 'Transfer posting 311 masuk ke '.$destinationName;
        if (!$db->insert('detail_transaksi', $trxIn)) {
          $error = $db->getErrorMessage();
          $db->query('ROLLBACK');
          tp_json('error', $error ?: 'Material document destination gagal disimpan.');
        }
        $remaining -= $take;
      }
      $lineNo++;
    }

    $db->query('COMMIT');
    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' posting transfer 311 '.$noTransfer.' dari Gudang ke '.$destinationName.' pada '.date('Y-m-d H:i:s'), $username);
    }
    tp_json('good', '', array('no_transfer' => $noTransfer));
    break;

  case 'reversal':
    $noSpb = isset($_POST['no_spb']) ? trim($_POST['no_spb']) : '';
    if ($noSpb === '') tp_json('error', 'No transfer wajib diisi.');

    $header = $db->fetch("SELECT * FROM transfer WHERE no_transfer=? LIMIT 1", array('no_transfer' => $noSpb));
    if (!$header) tp_json('error', 'Transfer document tidak ditemukan.');
    if ((string)$header->status === '9') tp_json('error', 'Transfer document sudah reversal.');

    $originals = $db->query("SELECT * FROM detail_transaksi WHERE no_ref=? AND posisi='GUDANG' AND COALESCE(is_reversal,0)=0 ORDER BY id_detail", array('no_ref' => $noSpb));
    if (!$originals) tp_json('error', 'Material document asal tidak ditemukan.');

    $db->query('START TRANSACTION');
    $revNo = $noSpb.'_REV';
    $lineNo = 1;
    foreach ($originals as $row) {
      $qty = abs((float)$row->qty);
      if ($qty <= 0) continue;

      $direction = strtoupper((string)$row->direction);
      if ($direction === '') $direction = ((float)$row->qty < 0) ? 'OUT' : 'IN';
      if ($direction === 'OUT') {
        if (!empty($row->ref_id)) {
          $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array('qty' => $qty, 'id' => $row->ref_id));
        } else {
          $layer = $db->fetch(
            "SELECT id FROM stock_layer WHERE kode=? AND no_aju=? AND no_dokpab=? AND lokasi='GUDANG' ORDER BY id DESC LIMIT 1",
            array('kode' => $row->kd_barang, 'no_aju' => $row->no_aju, 'no_dokpab' => $row->no_dokpab)
          );
          if ($layer) $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array('qty' => $qty, 'id' => $layer->id));
        }
        $revQty = $qty;
        $revDirection = 'IN';
      } else {
        if (!empty($row->ref_id)) {
          $reduce = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array('qty' => $qty, 'id' => $row->ref_id, 'check' => $qty));
          if (!$reduce) {
            $error = $db->getErrorMessage();
            $db->query('ROLLBACK');
            tp_json('error', $error ?: 'Stock tujuan tidak cukup untuk reversal '.$row->kd_barang.'.');
          }
        }
        $revQty = $qty * -1;
        $revDirection = 'OUT';
      }

      if (!$db->insert('detail_transaksi', array(
        'no_ref' => $revNo,
        'ref_pengganti' => $noSpb,
        'move_code' => '312',
        'posisi' => 'GUDANG',
        'no_urut' => $lineNo++,
        'qty' => $revQty,
        'kd_barang' => $row->kd_barang,
        'lokasi' => 'GUDANG',
        'document_date' => date('Y-m-d'),
        'posting_date' => date('Y-m-d'),
        'user' => $username,
        'direction' => $revDirection,
        'ref_type' => 'TRANSFER_REV',
        'ref_id' => $row->ref_id,
        'is_reversal' => 1,
        'ref_detail_id' => $row->id_detail,
        'no_aju' => $row->no_aju,
        'no_dokpab' => $row->no_dokpab,
        'uom' => $row->uom,
        'destination_storage_location_id' => $row->destination_storage_location_id,
        'destination_storage_bin_id' => $row->destination_storage_bin_id,
        'destination_stock_type' => $row->destination_stock_type,
        'destination_material_code' => $row->destination_material_code,
        'reason' => 'Reversal transfer '.$noSpb,
        'remark' => 'Reversal movement 312 untuk '.$noSpb,
        'created_by' => $username,
        'date_created' => date('Y-m-d H:i:s')
      ))) {
        $error = $db->getErrorMessage();
        $db->query('ROLLBACK');
        tp_json('error', $error ?: 'Material document reversal gagal disimpan.');
      }
    }

    $db->query("UPDATE transfer SET status='9' WHERE id_transfer=?", array('id' => $header->id_transfer));
    $db->query('COMMIT');
    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' reversal transfer '.$noSpb.' dengan movement 312 pada '.date('Y-m-d H:i:s'), $username);
    }
    tp_json('good');
    break;

  case 'delete':
    tp_json('error', 'Delete transfer posting tidak diizinkan. Gunakan reversal 312 agar audit trail tetap lengkap.');
    break;

  case 'del_massal':
    tp_json('error', 'Bulk delete tidak diizinkan. Gunakan reversal 312.');
    break;

  case 'up':
    tp_json('error', 'Edit transfer posting yang sudah diposting tidak diizinkan. Gunakan reversal 312 lalu buat dokumen baru.');
    break;

  default:
    tp_json('error', 'Aksi tidak dikenal.');
    break;
}
?>
