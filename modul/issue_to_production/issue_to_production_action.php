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

function gip_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}

function gip_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gip_num($value, $dec = 5) {
  return number_format((float)$value, $dec, ',', '.');
}

function gip_clean_qty($value) {
  return (float)str_replace(',', '.', trim((string)$value));
}

function gip_layer_price($layer) {
  if (isset($layer->purchase_price) && (float)$layer->purchase_price > 0) return (float)$layer->purchase_price;
  if (isset($layer->dt_price) && (float)$layer->dt_price > 0) return (float)$layer->dt_price;
  if (isset($layer->dt_amount) && isset($layer->dt_qty) && abs((float)$layer->dt_qty) > 0) return abs((float)$layer->dt_amount) / abs((float)$layer->dt_qty);
  return 0;
}

function gip_is_internal_production_layer($layer) {
  $refTable = isset($layer->ref_table) ? trim((string)$layer->ref_table) : '';
  $noBpb = isset($layer->no_bpb) ? trim((string)$layer->no_bpb) : '';
  return $refTable === 'erp_gr_production' || strpos($noBpb, 'GRP') === 0;
}

function gip_next_number($postingDate) {
  global $db;
  $prefix = 'GIP'.date('Ym', strtotime($postingDate));
  $row = $db->fetch("SELECT issue_no FROM erp_issue_production WHERE issue_no LIKE ? ORDER BY issue_no DESC LIMIT 1", array('no' => $prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->issue_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}

function gip_layer_query($materialCode, $plantId, $storageLocationId, $storageBinId) {
  $where = " WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED' ";
  $params = array('kode' => $materialCode);
  if ($plantId > 0) {
    $where .= " AND sl.plant_id=? ";
    $params[] = $plantId;
  }
  if ($storageLocationId > 0) {
    $where .= " AND sl.storage_location_id=? ";
    $params[] = $storageLocationId;
  }
  if ($storageBinId > 0) {
    $where .= " AND sl.storage_bin_id=? ";
    $params[] = $storageBinId;
  }
  return array($where, $params);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'production_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT p.id_production_order,p.no_production_order,p.start_date,p.finish_date,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.status,
              COALESCE(COUNT(m.id_material),0) AS item_count
       FROM production_order p
       LEFT JOIN production_order_material m ON m.id_production_order=p.id_production_order
       WHERE p.status IN ('RELEASED','IN_PROCESS')
         AND (?='' OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ?)
       GROUP BY p.id_production_order
       ORDER BY p.id_production_order DESC
       LIMIT 30",
      array('term' => $term, 'a' => $like, 'b' => $like, 'c' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->id_production_order,
        'text' => $row->no_production_order.' | '.$row->material_code.' - '.$row->material_name.' | Qty '.gip_num($row->order_qty).' '.$row->uom.' | '.$row->item_count.' component',
        'production_no' => $row->no_production_order,
        'production_date' => $row->start_date,
        'dept' => $row->plant,
        'remark' => $row->status
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT v.id_barang,v.kd_barang,v.nm_barang,v.satuan,SUM(v.stock) AS stock
       FROM v_stock_transaksi v
       WHERE (?='' OR v.kd_barang LIKE ? OR v.nm_barang LIKE ?)
       GROUP BY v.id_barang,v.kd_barang,v.nm_barang,v.satuan
       HAVING stock>0
       ORDER BY v.kd_barang
       LIMIT 30",
      array('term' => $term, 'a' => $like, 'b' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang.' | Stock '.gip_num($row->stock).' '.$row->satuan,
        'material_name' => $row->nm_barang,
        'uom' => $row->satuan,
        'stock' => (float)$row->stock
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'production_items':
    $productionId = isset($_POST['production_id']) ? (int)$_POST['production_id'] : 0;
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) ? (int)$_POST['storage_bin_id'] : 0;
    $header = $db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1", array('id' => $productionId));
    if (!$header) {
      echo '<div class="alert alert-warning">Production Order tidak ditemukan.</div>';
      break;
    }
    $details = $db->query(
      "SELECT m.*,b.nm_barang,b.satuan
       FROM production_order_material m
       LEFT JOIN barang b ON b.kd_barang=m.material_code
       WHERE m.id_production_order=? AND COALESCE(m.remaining_qty,m.required_qty)>0
       ORDER BY m.id_material",
      array('id' => $header->id_production_order)
    );
    ?>
    <div class="alert alert-success">
      <strong><?=gip_h($header->no_production_order);?></strong> | <?=gip_h($header->material_code.' - '.$header->material_name);?> | Qty <?=gip_num($header->order_qty).' '.gip_h($header->uom);?>
      <br><small>Status <?=gip_h($header->status);?> | Plant <?=gip_h($header->plant);?> | SLoc <?=gip_h($header->storage_location);?></small>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed gip-items">
        <thead><tr><th style="width:45px">Issue</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th class="text-right">Planned</th><th class="text-right">Available</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="width:140px">Issue Qty</th><th>Customs Preview</th><th>Remark</th></tr></thead>
        <tbody>
        <?php foreach ($details as $row) {
          list($layerWhere, $layerParams) = gip_layer_query($row->material_code, $plantId, $storageLocationId, $storageBinId);
          $stock = $db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) AS available_qty,
                                      GROUP_CONCAT(DISTINCT CONCAT(COALESCE(sl.no_aju,''),' / ',COALESCE(sl.no_dokpab,'')) ORDER BY sl.tgl_masuk,sl.id SEPARATOR '<br>') AS customs_refs
                               FROM stock_layer sl ".$layerWhere, $layerParams);
          $planned = (float)$row->remaining_qty;
          $available = $stock ? (float)$stock->available_qty : 0;
          $defaultQty = min($planned, $available);
        ?>
          <tr>
            <td class="text-center"><input type="checkbox" name="selected_line[]" value="<?=intval($row->id_material);?>" <?=($defaultQty>0?'checked':'');?>></td>
            <td>
              <strong><?=gip_h($row->material_code);?></strong><br><small><?=gip_h($row->material_name ?: $row->nm_barang);?></small>
              <input type="hidden" name="production_detail_id[]" value="<?=intval($row->id_material);?>">
              <input type="hidden" name="material_code[<?=intval($row->id_material);?>]" value="<?=gip_h($row->material_code);?>">
              <input type="hidden" name="material_name[<?=intval($row->id_material);?>]" value="<?=gip_h($row->material_name ?: $row->nm_barang);?>">
              <input type="hidden" name="uom[<?=intval($row->id_material);?>]" value="<?=gip_h($row->uom ?: $row->satuan);?>">
              <input type="hidden" name="planned_qty[<?=intval($row->id_material);?>]" value="<?=gip_h($planned);?>">
            </td>
            <td class="text-right"><?=gip_num($planned);?></td>
            <td class="text-right"><?=gip_num($available);?></td>
            <td><?=gip_h($row->uom ?: $row->satuan);?></td>
            <td><input type="number" step="0.00001" min="0" max="<?=gip_h($available);?>" name="issue_qty[<?=intval($row->id_material);?>]" class="form-control text-right issue-qty" value="<?=gip_h($defaultQty);?>"></td>
            <td><small><?=($stock && $stock->customs_refs ? $stock->customs_refs : '<span class="text-danger">Tidak ada stock layer</span>');?></small></td>
            <td><input name="item_remarks[<?=intval($row->id_material);?>]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case 'post':
    $productionId = isset($_POST['production_id']) ? (int)$_POST['production_id'] : 0;
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';
    $reasonCode = isset($_POST['reason_code']) ? trim($_POST['reason_code']) : '';
    $reasonText = isset($_POST['reason_text']) ? trim($_POST['reason_text']) : '';
    $plantId = isset($_POST['plant_id']) ? (int)$_POST['plant_id'] : 0;
    $storageLocationId = isset($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : 0;
    $storageBinId = isset($_POST['storage_bin_id']) ? (int)$_POST['storage_bin_id'] : 0;

    if ($productionId <= 0) gip_json('error', 'Production Order wajib dipilih.');
    if ($documentDate === '' || $postingDate === '') gip_json('error', 'Document Date dan Posting Date wajib diisi.');
    if ($reasonCode === '' || $reasonText === '') gip_json('error', 'Reason Code dan Reason Text wajib diisi.');
    if (empty($_POST['selected_line']) || !is_array($_POST['selected_line'])) gip_json('error', 'Minimal satu item bahan baku wajib dipilih.');

    $production = $db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1", array('id' => $productionId));
    if (!$production) gip_json('error', 'Production Order tidak ditemukan.');
    if (!in_array($production->status, array('RELEASED','IN_PROCESS'), true)) gip_json('error', 'Production Order harus status RELEASED atau IN_PROCESS.');

    $selected = array();
    foreach ($_POST['selected_line'] as $lineId) {
      $lineId = (int)$lineId;
      $qty = isset($_POST['issue_qty'][$lineId]) ? gip_clean_qty($_POST['issue_qty'][$lineId]) : 0;
      if ($lineId > 0 && $qty > 0) $selected[$lineId] = $qty;
    }
    if (empty($selected)) gip_json('error', 'Issue Qty wajib lebih dari nol untuk item yang dipilih.');

    $db->query('START TRANSACTION');
    $issueNo = gip_next_number($postingDate);
    $header = array(
      'issue_no' => $issueNo,
      'production_id' => $production->id_production_order,
      'production_no' => $production->no_production_order,
      'document_date' => $documentDate,
      'posting_date' => $postingDate,
      'movement_type' => '261',
      'reference_no' => $referenceNo,
      'reason_code' => $reasonCode,
      'reason_text' => $reasonText,
      'plant_id' => $plantId > 0 ? $plantId : null,
      'storage_location_id' => $storageLocationId > 0 ? $storageLocationId : null,
      'storage_bin_id' => $storageBinId > 0 ? $storageBinId : null,
      'status' => 'POSTED',
      'created_by' => $username
    );
    if (!$db->insert('erp_issue_production', $header)) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      gip_json('error', $err ?: 'Header Issue to Production gagal disimpan.');
    }
    $issueId = $db->last_insert_id();
    $lineNo = 1;
    $totalAmount = 0;
    $accountingItems = array();
    foreach ($selected as $lineId => $qty) {
      $prodDetail = $db->fetch(
        "SELECT m.*,b.nm_barang,b.satuan
         FROM production_order_material m
         LEFT JOIN barang b ON b.kd_barang=m.material_code
         WHERE m.id_material=? AND m.id_production_order=?
         LIMIT 1",
        array('id' => $lineId, 'po' => $production->id_production_order)
      );
      if (!$prodDetail) {
        $db->query('ROLLBACK');
        gip_json('error', 'Item produksi tidak ditemukan.');
      }
      if ((float)$prodDetail->remaining_qty + 0.00001 < $qty) {
        $db->query('ROLLBACK');
        gip_json('error', 'Issue Qty '.$prodDetail->material_code.' melebihi remaining requirement. Remaining '.gip_num($prodDetail->remaining_qty).'.');
      }
      list($layerWhere, $layerParams) = gip_layer_query($prodDetail->material_code, $plantId, $storageLocationId, $storageBinId);
      $available = $db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) AS available_qty FROM stock_layer sl ".$layerWhere, $layerParams);
      if ((float)$available->available_qty + 0.00001 < $qty) {
        $db->query('ROLLBACK');
        gip_json('error', 'Stock tidak cukup untuk '.$prodDetail->material_code.'. Available '.gip_num($available->available_qty).', request '.gip_num($qty).'.');
      }

      $detail = array(
        'issue_id' => $issueId,
        'production_detail_id' => $prodDetail->id_material,
        'line_no' => $lineNo,
        'material_code' => $prodDetail->material_code,
        'material_name' => $prodDetail->material_name ?: $prodDetail->nm_barang,
        'planned_qty' => $prodDetail->remaining_qty,
        'issued_qty' => $qty,
        'uom' => $prodDetail->uom ?: $prodDetail->satuan,
        'stock_type' => 'UNRESTRICTED',
        'remarks' => isset($_POST['item_remarks'][$lineId]) ? $_POST['item_remarks'][$lineId] : ''
      );
      if (!$db->insert('erp_issue_production_detail', $detail)) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        gip_json('error', $err ?: 'Detail Issue to Production gagal disimpan.');
      }
      $issueDetailId = $db->last_insert_id();

      $remaining = $qty;
      $detailAmount = 0;
      $weightedValue = 0;
      $layers = $db->query(
        "SELECT sl.*,pd.lot_no,pd.hs_code,pd.harga purchase_price,dt.price dt_price,dt.amount dt_amount,dt.qty dt_qty
         FROM stock_layer sl
         LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
         LEFT JOIN detail_transaksi dt ON dt.id=sl.ref_id AND sl.ref_table='detail_transaksi'
         ".$layerWhere."
         ORDER BY sl.tgl_masuk ASC,sl.id ASC
         FOR UPDATE",
        $layerParams
      );
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        if ($take <= 0) continue;
        if (gip_is_internal_production_layer($layer)) {
          $traceCheck = $db->fetch(
            "SELECT COUNT(*) AS trace_rows
             FROM erp_gr_production_trace
             WHERE output_stock_layer_id=?",
            array('stock_layer_id' => $layer->id)
          );
          if (!$traceCheck || (int)$traceCheck->trace_rows <= 0) {
            $db->query('ROLLBACK');
            gip_json('error', 'Issue ditolak: stock layer internal #'.$layer->id.' ('.$layer->kode.' / '.$layer->no_bpb.') belum memiliki trace bahan baku asal dan dokumen BC.');
          }
        }
        $price = gip_layer_price($layer);
        if ($price <= 0) {
          $db->query('ROLLBACK');
          gip_json('error', 'Valuation price stock layer #'.$layer->id.' material '.$prodDetail->material_code.' belum tersedia.');
        }
        $amount = round($take * $price, 2);
        $update = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array('qty' => $take, 'id' => $layer->id, 'check' => $take));
        if (!$update) {
          $err = $db->getErrorMessage();
          $db->query('ROLLBACK');
          gip_json('error', $err ?: 'Stock layer gagal diperbarui.');
        }
        $trx = array(
          'no_ref' => $issueNo,
          'ref_pengganti' => $production->no_production_order,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'id_incoming_detail' => ($layer->ref_table === 'pemasukan_detail') ? $layer->ref_id : null,
          'move_code' => '261',
          'posisi' => 'GUDANG',
          'no_urut' => $lineNo,
          'qty' => $take * -1,
          'id_bagian' => 1,
          'kd_barang' => $prodDetail->material_code,
          'lokasi' => 'GUDANG',
          'document_date' => $documentDate,
          'posting_date' => $postingDate,
          'user' => $username,
          'is_produksi' => '1',
          'direction' => 'OUT',
          'ref_type' => 'ISSUE_PROD',
          'ref_id' => $layer->id,
          'ref_detail_id' => $issueDetailId,
          'uom' => $prodDetail->uom ?: $prodDetail->satuan,
          'price' => $price,
          'amount' => $amount,
          'reason' => $reasonCode,
          'created_by' => $username,
          'no_bpb' => $layer->no_bpb,
          'plant_id' => $layer->plant_id,
          'storage_location_id' => $layer->storage_location_id,
          'storage_bin_id' => $layer->storage_bin_id,
          'stock_type' => $layer->stock_type,
          'destination_material_code' => $prodDetail->material_code,
          'remark' => 'Goods Issue 261 to Production '.$production->no_production_order.' - '.$reasonText
        );
        if (!$db->insert('detail_transaksi', $trx)) {
          $err = $db->getErrorMessage();
          $db->query('ROLLBACK');
          gip_json('error', $err ?: 'Material document 261 gagal disimpan.');
        }
        $materialDocId = $db->last_insert_id();
        $trace = array(
          'issue_id' => $issueId,
          'issue_detail_id' => $issueDetailId,
          'stock_layer_id' => $layer->id,
          'material_doc_id' => $materialDocId,
          'qty' => $take,
          'price' => $price,
          'amount' => $amount,
          'stock_type' => $layer->stock_type,
          'plant_id' => $layer->plant_id,
          'storage_location_id' => $layer->storage_location_id,
          'storage_bin_id' => $layer->storage_bin_id,
          'no_bpb' => $layer->no_bpb,
          'no_aju' => $layer->no_aju,
          'no_dokpab' => $layer->no_dokpab,
          'jenis_dokpab' => $layer->jenis_dokpab,
          'hs_code' => $layer->hs_code,
          'lot_no' => $layer->lot_no,
          'source_ref_table' => $layer->ref_table,
          'source_ref_id' => $layer->ref_id
        );
        if (!$db->insert('erp_issue_production_trace', $trace)) {
          $err = $db->getErrorMessage();
          $db->query('ROLLBACK');
          gip_json('error', $err ?: 'Trace dokumen pabean gagal disimpan.');
        }
        $detailAmount += $amount;
        $weightedValue += $take * $price;
        $remaining -= $take;
      }
      if ($remaining > 0.00001) {
        $db->query('ROLLBACK');
        gip_json('error', 'Stock layer tidak cukup untuk item '.$prodDetail->material_code.'.');
      }
      $detailPrice = $qty > 0 ? $weightedValue / $qty : 0;
      $db->query("UPDATE erp_issue_production_detail SET price=?,amount=? WHERE id=?", array($detailPrice,$detailAmount,$issueDetailId));
      $accountingItems[] = array('kode'=>$prodDetail->material_code,'amount'=>$detailAmount,'valuta'=>'IDR','kurs'=>1);
      $totalAmount += $detailAmount;
      $newIssued = (float)$prodDetail->issued_qty + $qty;
      $newRemaining = max((float)$prodDetail->required_qty - $newIssued, 0);
      $issueStatus = $newRemaining <= 0.00001 ? 'FULL_ISSUE' : 'PARTIAL_ISSUE';
      $db->query(
        "UPDATE production_order_material SET issued_qty=?,remaining_qty=?,issue_status=? WHERE id_material=?",
        array('issued' => $newIssued, 'remaining' => $newRemaining, 'status' => $issueStatus, 'id' => $prodDetail->id_material)
      );
      $db->insert('production_order_goods_movement', array(
        'id_production_order' => $production->id_production_order,
        'movement_type' => '261',
        'material_code' => $prodDetail->material_code,
        'material_name' => $prodDetail->material_name ?: $prodDetail->nm_barang,
        'qty' => $qty * -1,
        'uom' => $prodDetail->uom ?: $prodDetail->satuan,
        'posting_date' => $postingDate,
        'storage_location_from' => $storageLocationId > 0 ? (string)$storageLocationId : null,
        'storage_location_to' => $production->storage_location,
        'remarks' => $issueNo,
        'created_by' => $username
      ));
      $lineNo++;
    }
    if ($production->status === 'RELEASED') {
      $db->query("UPDATE production_order SET status='IN_PROCESS',actual_start=COALESCE(actual_start,NOW()),updated_by=? WHERE id_production_order=?", array('user' => $username, 'id' => $production->id_production_order));
    }
    $db->update('erp_issue_production', array('total_amount'=>$totalAmount), 'id', $issueId);
    $journalResult = accounting_post_auto_journal('issue_production', '', $accountingItems, array(
      'no_bukti' => $issueNo,
      'tgl_jurnal' => $postingDate,
      'ket' => 'Goods Issue 261 to Production '.$production->no_production_order,
      'valuta' => 'IDR',
      'kurs' => 1,
      'source_module' => 'ISSUE_TO_PRODUCTION'
    ));
    if ($journalResult !== true) { $db->query('ROLLBACK'); gip_json('error', $journalResult); }
    $db->insert('erp_issue_production_history', array(
      'issue_id' => $issueId,
      'status_baru' => 'POSTED',
      'remarks' => 'Goods Issue 261 posted to production '.$production->no_production_order,
      'changed_by' => $username
    ));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' posting Issue to Production '.$issueNo.' ke '.$production->no_production_order.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    gip_json('good', '', array('issue_no' => $issueNo));
    break;

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $h = $db->fetch(
      "SELECT h.*,p.plant_code,sl.storage_code,sb.bin_code
       FROM erp_issue_production h
       LEFT JOIN erp_plant p ON p.id=h.plant_id
       LEFT JOIN erp_storage_location sl ON sl.id=h.storage_location_id
       LEFT JOIN erp_storage_bin sb ON sb.id=h.storage_bin_id
       WHERE h.id=? LIMIT 1",
      array('id' => $id)
    );
    if (!$h) {
      echo '<div class="alert alert-warning">Issue document tidak ditemukan.</div>';
      break;
    }
    $items = $db->query("SELECT * FROM erp_issue_production_detail WHERE issue_id=? ORDER BY line_no,id", array('id' => $id));
    ?>
    <div class="row">
      <div class="col-md-8">
        <h3 style="margin-top:0;font-weight:700"><?=gip_h($h->issue_no);?> <small><?=gip_h($h->production_no);?></small></h3>
        <p class="text-muted">Movement <?=gip_h($h->movement_type);?> | <?=gip_h($h->reason_code.' - '.$h->reason_text);?></p>
      </div>
      <div class="col-md-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=gip_h($h->status);?></span></div>
    </div>
    <div class="row">
      <div class="col-sm-3"><strong>Document Date</strong><br><?=gip_h($h->document_date);?></div>
      <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></strong><br><?=gip_h($h->posting_date);?></div>
      <div class="col-sm-3"><strong>Source Location</strong><br><?=gip_h(trim($h->plant_code.' / '.$h->storage_code.' / '.$h->bin_code, ' /'));?></div>
      <div class="col-sm-3"><strong>Created By</strong><br><?=gip_h($h->created_by);?></div>
    </div>
    <hr>
    <?php foreach ($items as $item) {
      $traces = $db->query(
        "SELECT t.*,ep.plant_code,es.storage_code,eb.bin_code
         FROM erp_issue_production_trace t
         LEFT JOIN erp_plant ep ON ep.id=t.plant_id
         LEFT JOIN erp_storage_location es ON es.id=t.storage_location_id
         LEFT JOIN erp_storage_bin eb ON eb.id=t.storage_bin_id
         WHERE t.issue_detail_id=?
         ORDER BY t.id",
        array('id' => $item->id)
      );
      $inheritedTraces = $db->query(
        "SELECT t.stock_layer_id,t.qty AS issued_layer_qty,sl.qty_masuk AS output_layer_qty,
                gt.raw_material_code,gt.raw_material_name,gt.source_material_code,gt.source_material_name,
                gt.qty AS source_trace_qty,
                (gt.qty * CASE WHEN COALESCE(sl.qty_masuk,0)>0 THEN (t.qty/sl.qty_masuk) ELSE 1 END) AS proportional_qty,
                gt.uom,gt.lot_no,gt.no_bpb,gt.no_aju,gt.jenis_dokpab,gt.no_dokpab,gt.hs_code,gt.trace_source,gt.gr_id
         FROM erp_issue_production_trace t
         JOIN stock_layer sl ON sl.id=t.stock_layer_id
         JOIN erp_gr_production_trace gt ON gt.output_stock_layer_id=t.stock_layer_id
         WHERE t.issue_detail_id=?
         ORDER BY gt.raw_material_code,gt.no_aju,gt.no_dokpab,gt.id",
        array('id' => $item->id)
      );
    ?>
      <h4><?=gip_h($item->line_no.'. '.$item->material_code.' - '.$item->material_name);?> <small>Issued <?=gip_num($item->issued_qty).' '.gip_h($item->uom);?></small></h4>
      <div class="table-responsive">
        <table class="table table-bordered table-condensed">
          <thead><tr class="bg-gray"><th>Layer</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th>Lot/Batch</th><th>No Aju</th><th>Dok Pabean</th><th>No BPB</th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Material Doc</th></tr></thead>
          <tbody>
          <?php foreach ($traces as $trace) { ?>
            <tr>
              <td><?=intval($trace->stock_layer_id);?></td>
              <td class="text-right"><?=gip_num($trace->qty);?></td>
              <td><?=gip_h($trace->lot_no);?></td>
              <td><?=gip_h($trace->no_aju);?></td>
              <td><?=gip_h(trim($trace->jenis_dokpab.' '.$trace->no_dokpab));?></td>
              <td><?=gip_h($trace->no_bpb);?></td>
              <td><?=gip_h(trim($trace->plant_code.' / '.$trace->storage_code.' / '.$trace->bin_code, ' /'));?></td>
              <td><?=intval($trace->material_doc_id);?></td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
      <?php if ($inheritedTraces && $inheritedTraces->rowCount() > 0) { ?>
        <h5 style="font-weight:700;margin-top:14px"><i class="fa fa-random"></i> Trace Bahan Baku Asal Barang Setengah Jadi</h5>
        <p class="text-muted">
          Item ini berasal dari stock layer hasil GR Production. Qty bahan asal dihitung proporsional terhadap qty barang setengah jadi yang dipakai.
        </p>
        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr class="bg-gray">
                <th>Output Layer</th>
                <th>Raw Material Asal</th>
                <th class="text-right">Qty Proporsional</th>
                <th>Lot/Batch</th>
                <th>No Aju</th>
                <th>Dok Pabean</th>
                <th>No BPB</th>
                <th>HS Code</th>
                <th>Trace</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($inheritedTraces as $raw) {
              $traceLabel = $raw->trace_source === 'INHERITED' ? 'INHERITED' : 'DIRECT';
            ?>
              <tr>
                <td>#<?=intval($raw->stock_layer_id);?><br><small>Issue <?=gip_num($raw->issued_layer_qty);?> / Output <?=gip_num($raw->output_layer_qty);?></small></td>
                <td><strong><?=gip_h($raw->raw_material_code);?></strong><br><small><?=gip_h($raw->raw_material_name);?></small></td>
                <td class="text-right"><?=gip_num($raw->proportional_qty);?><br><small><?=gip_h($raw->uom);?></small></td>
                <td><?=gip_h($raw->lot_no ?: '-');?></td>
                <td><?=gip_h($raw->no_aju ?: '-');?></td>
                <td><?=gip_h(trim($raw->jenis_dokpab.' '.$raw->no_dokpab) ?: '-');?></td>
                <td><?=gip_h($raw->no_bpb ?: '-');?></td>
                <td><?=gip_h($raw->hs_code ?: '-');?></td>
                <td><span class="label label-<?=($traceLabel==='INHERITED'?'primary':'info');?>"><?=gip_h($traceLabel);?></span></td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    <?php } ?>
    <?php
    break;

  case 'reversal':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($id <= 0) gip_json('error', 'Issue document wajib dipilih.');
    if ($reason === '') gip_json('error', 'Reason reversal wajib diisi.');
    $h = $db->fetch("SELECT * FROM erp_issue_production WHERE id=? LIMIT 1", array('id' => $id));
    if (!$h) gip_json('error', 'Issue document tidak ditemukan.');
    if ($h->status === 'REVERSED') gip_json('error', 'Issue document sudah reversal.');
    $traces = $db->query(
      "SELECT t.*,d.material_code,d.material_name,d.uom,d.line_no,d.production_detail_id
       FROM erp_issue_production_trace t
       JOIN erp_issue_production_detail d ON d.id=t.issue_detail_id
       WHERE t.issue_id=?
       ORDER BY t.id",
      array('id' => $id)
    );
    if (!$traces) gip_json('error', 'Trace issue tidak ditemukan.');
    $db->query('START TRANSACTION');
    $revNo = $h->issue_no.'_REV';
    $reversedRequirementQty = array();
    foreach ($traces as $trace) {
      $qty = (float)$trace->qty;
      if ($qty <= 0) continue;
      if (!isset($reversedRequirementQty[$trace->production_detail_id])) $reversedRequirementQty[$trace->production_detail_id] = 0;
      $reversedRequirementQty[$trace->production_detail_id] += $qty;
      $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array('qty' => $qty, 'id' => $trace->stock_layer_id));
      if (!$db->insert('detail_transaksi', array(
        'no_ref' => $revNo,
        'ref_pengganti' => $h->issue_no,
        'no_aju' => $trace->no_aju,
        'no_dokpab' => $trace->no_dokpab,
        'move_code' => '262',
        'posisi' => 'GUDANG',
        'no_urut' => $trace->line_no,
        'qty' => $qty,
        'id_bagian' => 1,
        'kd_barang' => $trace->material_code,
        'lokasi' => 'GUDANG',
        'document_date' => date('Y-m-d'),
        'posting_date' => date('Y-m-d'),
        'user' => $username,
        'is_produksi' => '1',
        'direction' => 'IN',
        'ref_type' => 'ISSUE_REV',
        'ref_id' => $trace->stock_layer_id,
        'ref_detail_id' => $trace->id,
        'is_reversal' => 1,
        'uom' => $trace->uom,
        'price' => $trace->price,
        'amount' => $trace->amount,
        'reason' => $reason,
        'created_by' => $username,
        'no_bpb' => $trace->no_bpb,
        'plant_id' => $trace->plant_id,
        'storage_location_id' => $trace->storage_location_id,
        'storage_bin_id' => $trace->storage_bin_id,
        'stock_type' => $trace->stock_type,
        'destination_material_code' => $trace->material_code,
        'remark' => 'Reversal 262 Issue to Production '.$h->issue_no
      ))) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        gip_json('error', $err ?: 'Material document reversal gagal disimpan.');
      }
      $db->insert('production_order_goods_movement', array(
        'id_production_order' => $h->production_id,
        'movement_type' => '262',
        'material_code' => $trace->material_code,
        'material_name' => $trace->material_name,
        'qty' => $qty,
        'uom' => $trace->uom,
        'posting_date' => date('Y-m-d'),
        'remarks' => $revNo,
        'created_by' => $username
      ));
    }
    foreach ($reversedRequirementQty as $materialId => $qty) {
      $req = $db->fetch("SELECT * FROM production_order_material WHERE id_material=? LIMIT 1", array('id' => $materialId));
      if ($req) {
        $newIssued = max((float)$req->issued_qty - (float)$qty, 0);
        $newRemaining = max((float)$req->required_qty - $newIssued, 0);
        $issueStatus = $newIssued <= 0.00001 ? 'OPEN' : ($newRemaining <= 0.00001 ? 'FULL_ISSUE' : 'PARTIAL_ISSUE');
        $db->query(
          "UPDATE production_order_material SET issued_qty=?,remaining_qty=?,issue_status=? WHERE id_material=?",
          array('issued' => $newIssued, 'remaining' => $newRemaining, 'status' => $issueStatus, 'id' => $materialId)
        );
      }
    }
    $journalResult = accounting_reverse_auto_journal($h->issue_no, $revNo, array('tgl_jurnal'=>date('Y-m-d'), 'ket'=>'Reversal Issue to Production '.$h->issue_no));
    if ($journalResult !== true) { $db->query('ROLLBACK'); gip_json('error', $journalResult); }
    $db->query(
      "UPDATE erp_issue_production SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=? WHERE id=?",
      array('user' => $username, 'at' => date('Y-m-d H:i:s'), 'reason' => $reason, 'id' => $id)
    );
    $db->insert('erp_issue_production_history', array(
      'issue_id' => $id,
      'status_lama' => 'POSTED',
      'status_baru' => 'REVERSED',
      'remarks' => $reason,
      'changed_by' => $username
    ));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' reversal Issue to Production '.$h->issue_no.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    gip_json('good');
    break;

  default:
    gip_json('error', 'Action tidak dikenal.');
    break;
}
?>
