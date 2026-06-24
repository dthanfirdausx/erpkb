<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function sbt_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}
function sbt_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sbt_num($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function sbt_clean_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function sbt_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}
function sbt_next_number($postingDate) {
  global $db;
  $prefix = 'SBT'.date('Ym', strtotime($postingDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT transfer_no FROM erp_storage_bin_transfer WHERE transfer_no LIKE ? ORDER BY transfer_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->transfer_no, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function sbt_layer_where($materialCode, $storageLocationId, $storageBinId) {
  $where = " WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' ";
  $params = array($materialCode);
  if ($storageLocationId > 0) { $where .= " AND sl.storage_location_id=? "; $params[] = $storageLocationId; }
  if ($storageBinId > 0) { $where .= " AND sl.storage_bin_id=? "; $params[] = $storageBinId; }
  return array($where, $params);
}
function sbt_layer_price($layer) {
  if (isset($layer->purchase_price) && (float)$layer->purchase_price > 0) return (float)$layer->purchase_price;
  if (isset($layer->price) && (float)$layer->price > 0) return (float)$layer->price;
  return 0;
}
function sbt_fetch_layers($materialCode, $storageLocationId, $storageBinId, $forUpdate = false) {
  global $db;
  list($where, $params) = sbt_layer_where($materialCode, $storageLocationId, $storageBinId);
  $sql = "SELECT sl.*,b.nm_barang,b.satuan,pd.harga AS purchase_price,pd.unit AS purchase_uom,pd.lot_no AS purchase_lot_no,pd.hs_code AS purchase_hs_code
          FROM stock_layer sl
          LEFT JOIN barang b ON b.kd_barang=sl.kode
          LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
          ".$where."
          ORDER BY sl.tgl_masuk ASC,sl.id ASC";
  if ($forUpdate) $sql .= " FOR UPDATE";
  return $db->query($sql, $params);
}
function sbt_location($storageLocationId, $storageBinId = 0) {
  global $db;
  return $db->fetch(
    "SELECT s.*,p.plant_code,p.plant_name,b.id AS bin_id,b.bin_code,b.bin_name
     FROM erp_storage_location s
     JOIN erp_plant p ON p.id=s.plant_id
     LEFT JOIN erp_storage_bin b ON b.id=? AND b.storage_location_id=s.id AND b.status='Aktif'
     WHERE s.id=? AND s.status='Aktif'
     LIMIT 1",
    array($storageBinId, $storageLocationId)
  );
}
function sbt_insert_material_doc($data) {
  global $db;
  if (!$db->insert('detail_transaksi', $data)) return false;
  return $db->last_insert_id();
}
function sbt_render_detail($id) {
  global $db;
  $h = $db->fetch(
    "SELECT h.*,sp.plant_code AS source_plant_code,dp.plant_code AS destination_plant_code,
            src.storage_code AS source_storage_code,src.storage_name AS source_storage_name,
            dst.storage_code AS destination_storage_code,dst.storage_name AS destination_storage_name,
            sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code
     FROM erp_storage_bin_transfer h
     LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id
     LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
     LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id
     LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
     LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id
     LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
     WHERE h.id=? LIMIT 1",
    array($id)
  );
  if (!$h) { echo '<div class="alert alert-warning">Storage Bin Transfer tidak ditemukan.</div>'; return; }
  $items = $db->query("SELECT * FROM erp_storage_bin_transfer_detail WHERE transfer_id=? ORDER BY line_no,id", array($id));
  $history = $db->query("SELECT * FROM erp_storage_bin_transfer_history WHERE transfer_id=? ORDER BY changed_at DESC,id DESC", array($id));
  ?>
  <div class="row">
    <div class="col-md-8"><h3 style="margin-top:0;font-weight:700"><?=sbt_h($h->transfer_no);?> <small>MvT <?=sbt_h($h->movement_type);?></small></h3><p class="text-muted"><?=sbt_h($h->reason_code.' - '.$h->reason_text);?></p></div>
    <div class="col-md-4 text-right"><span class="label label-<?=($h->status==='POSTED'?'success':'danger');?>"><?=sbt_h($h->status);?></span></div>
  </div>
  <div class="row">
    <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></strong><br><?=sbt_h($h->posting_date);?></div>
    <div class="col-sm-3"><strong>Source</strong><br><?=sbt_h(trim($h->source_plant_code.' / '.$h->source_storage_code.' / '.$h->source_bin_code, ' /'));?></div>
    <div class="col-sm-3"><strong>Destination</strong><br><?=sbt_h(trim($h->destination_plant_code.' / '.$h->destination_storage_code.' / '.$h->destination_bin_code, ' /'));?></div>
    <div class="col-sm-3"><strong>Created By</strong><br><?=sbt_h($h->created_by);?></div>
  </div>
  <hr>
  <?php foreach ($items as $item) {
    $traces = $db->query(
      "SELECT t.*,ssl.storage_code AS source_storage_code,ds.storage_code AS destination_storage_code,sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code
       FROM erp_storage_bin_transfer_trace t
       LEFT JOIN erp_storage_location ssl ON ssl.id=t.source_storage_location_id
       LEFT JOIN erp_storage_location ds ON ds.id=t.destination_storage_location_id
       LEFT JOIN erp_storage_bin sb ON sb.id=t.source_storage_bin_id
       LEFT JOIN erp_storage_bin dbin ON dbin.id=t.destination_storage_bin_id
       WHERE t.transfer_detail_id=?
       ORDER BY t.id",
      array($item->id)
    );
  ?>
    <h4><?=sbt_h($item->line_no.'. '.$item->material_code.' - '.$item->material_name);?> <small>Qty <?=sbt_num($item->qty).' '.sbt_h($item->uom);?> | Amount <?=number_format((float)$item->amount,2,',','.');?></small></h4>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed">
        <thead><tr class="bg-gray"><th>Source Layer</th><th>Destination Layer</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th class="text-right">Price</th><th>Source</th><th>Destination</th><th>No BPB</th><th>No Aju</th><th>Dok Pabean</th><th>Material Docs</th></tr></thead>
        <tbody>
        <?php foreach ($traces as $t) { ?>
          <tr>
            <td>#<?=intval($t->source_stock_layer_id);?></td>
            <td>#<?=intval($t->destination_stock_layer_id);?></td>
            <td class="text-right"><?=sbt_num($t->qty);?></td>
            <td class="text-right"><?=number_format((float)$t->price,5,',','.');?></td>
            <td><?=sbt_h(trim($t->source_storage_code.' / '.$t->source_bin_code, ' /'));?></td>
            <td><?=sbt_h(trim($t->destination_storage_code.' / '.$t->destination_bin_code, ' /'));?></td>
            <td><?=sbt_h($t->no_bpb);?></td>
            <td><?=sbt_h($t->no_aju);?></td>
            <td><?=sbt_h(trim($t->jenis_dokpab.' '.$t->no_dokpab));?></td>
            <td>OUT #<?=intval($t->material_doc_out_id);?> / IN #<?=intval($t->material_doc_in_id);?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
  <h4>History</h4>
  <ul class="list-unstyled">
    <?php foreach ($history as $row) { ?><li><strong><?=sbt_h(($row->status_lama ?: '-').' -> '.$row->status_baru);?></strong> <span class="text-muted"><?=sbt_h($row->changed_by.' @ '.$row->changed_at);?></span><br><?=sbt_h($row->remarks);?></li><?php } ?>
  </ul>
  <?php
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $storageLocationId = isset($_POST['source_storage_location_id']) ? (int)$_POST['source_storage_location_id'] : 0;
    $storageBinId = isset($_POST['source_storage_bin_id']) ? (int)$_POST['source_storage_bin_id'] : 0;
    $like = '%'.$term.'%';
    $where = " WHERE sl.qty_sisa>0 AND sl.lokasi='GUDANG' ";
    $params = array();
    if ($storageLocationId > 0) { $where .= " AND sl.storage_location_id=? "; $params[] = $storageLocationId; }
    if ($storageBinId > 0) { $where .= " AND sl.storage_bin_id=? "; $params[] = $storageBinId; }
    $params[] = $term;
    $params[] = $like;
    $params[] = $like;
    $rows = $db->query(
      "SELECT sl.kode,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) AS stock
       FROM stock_layer sl
       LEFT JOIN barang b ON b.kd_barang=sl.kode
       $where
         AND (?='' OR sl.kode LIKE ? OR b.nm_barang LIKE ?)
       GROUP BY sl.kode,b.nm_barang,b.satuan
       HAVING stock>0
       ORDER BY sl.kode LIMIT 30",
      $params
    );
    $results = array();
    foreach ($rows as $row) $results[] = array('id'=>$row->kode,'text'=>$row->kode.' - '.$row->nm_barang.' | Stock '.sbt_num($row->stock).' '.$row->satuan,'material_name'=>$row->nm_barang,'uom'=>$row->satuan,'stock'=>(float)$row->stock);
    echo json_encode(array('results'=>$results));
    break;

  case 'stock_preview':
    $materialCode = isset($_POST['material_code']) ? trim($_POST['material_code']) : '';
    $storageLocationId = isset($_POST['source_storage_location_id']) ? (int)$_POST['source_storage_location_id'] : 0;
    $storageBinId = isset($_POST['source_storage_bin_id']) ? (int)$_POST['source_storage_bin_id'] : 0;
    if ($materialCode === '') { echo '<span class="text-muted">Pilih material.</span>'; break; }
    $layers = sbt_fetch_layers($materialCode, $storageLocationId, $storageBinId, false);
    if (!$layers || $layers->rowCount() === 0) { echo '<span class="text-danger">Stock layer tidak tersedia.</span>'; break; }
    echo '<table class="table table-condensed table-bordered" style="margin-bottom:0"><thead><tr><th>Layer</th><th class="text-right">Sisa</th><th>'.wh_h(wh_t('warehouse_stock_type', 'Stock Type')).'</th><th>BC/BPB</th></tr></thead><tbody>';
    foreach ($layers as $layer) echo '<tr><td>#'.intval($layer->id).'</td><td class="text-right">'.sbt_num($layer->qty_sisa).'</td><td>'.sbt_h($layer->stock_type).'</td><td>'.sbt_h($layer->no_bpb ?: '-').'<br><small>'.sbt_h(trim($layer->jenis_dokpab.' '.$layer->no_dokpab).' / '.$layer->no_aju).'</small></td></tr>';
    echo '</tbody></table>';
    break;

  case 'post':
    $documentDate = isset($_POST['document_date']) ? trim($_POST['document_date']) : '';
    $postingDate = isset($_POST['posting_date']) ? trim($_POST['posting_date']) : '';
    $sourceStorageLocationId = isset($_POST['source_storage_location_id']) ? (int)$_POST['source_storage_location_id'] : 0;
    $sourceStorageBinId = isset($_POST['source_storage_bin_id']) ? (int)$_POST['source_storage_bin_id'] : 0;
    $destinationStorageLocationId = isset($_POST['destination_storage_location_id']) ? (int)$_POST['destination_storage_location_id'] : 0;
    $destinationStorageBinId = isset($_POST['destination_storage_bin_id']) ? (int)$_POST['destination_storage_bin_id'] : 0;
    $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';
    $reasonCode = isset($_POST['reason_code']) ? trim($_POST['reason_code']) : '';
    $reasonText = isset($_POST['reason_text']) ? trim($_POST['reason_text']) : '';
    if (!sbt_valid_date($documentDate) || !sbt_valid_date($postingDate)) sbt_json('error', 'Document Date dan Posting Date wajib valid.');
    if ($sourceStorageLocationId <= 0 || $destinationStorageLocationId <= 0) sbt_json('error', 'Source dan Destination Storage Location wajib diisi.');
    if ($sourceStorageBinId <= 0 || $destinationStorageBinId <= 0) sbt_json('error', 'Source Bin dan Destination Bin wajib dipilih.');
    if ($sourceStorageLocationId !== $destinationStorageLocationId) sbt_json('error', 'Storage Bin Transfer hanya boleh dalam storage location yang sama.');
    if ($sourceStorageBinId === $destinationStorageBinId) sbt_json('error', 'Source Bin dan Destination Bin tidak boleh sama.');
    if ($reasonCode === '' || $reasonText === '') sbt_json('error', 'Reason wajib diisi.');
    $sourceLoc = sbt_location($sourceStorageLocationId, $sourceStorageBinId);
    $destLoc = sbt_location($destinationStorageLocationId, $destinationStorageBinId);
    if (!$sourceLoc || !$destLoc) sbt_json('error', 'Source atau destination storage bin tidak valid.');
    if ((int)$sourceLoc->plant_id !== (int)$destLoc->plant_id) sbt_json('error', 'Movement 311 hanya untuk transfer storage bin dalam plant yang sama.');
    if ($sourceStorageBinId > 0 && empty($sourceLoc->bin_id)) sbt_json('error', 'Source storage bin tidak valid.');
    if ($destinationStorageBinId > 0 && empty($destLoc->bin_id)) sbt_json('error', 'Destination storage bin tidak valid.');
    if (empty($_POST['material_code']) || !is_array($_POST['material_code'])) sbt_json('error', 'Minimal satu item material wajib diisi.');
    $items = array();
    foreach ($_POST['material_code'] as $idx => $code) {
      $code = trim((string)$code);
      $qty = isset($_POST['qty'][$idx]) ? sbt_clean_qty($_POST['qty'][$idx]) : 0;
      if ($code !== '' && $qty > 0) $items[] = array('material_code'=>$code,'qty'=>$qty,'remarks'=>isset($_POST['item_remarks'][$idx]) ? trim($_POST['item_remarks'][$idx]) : '');
    }
    if (!$items) sbt_json('error', 'Transfer Qty wajib lebih dari nol.');
    $db->query('START TRANSACTION');
    $transferNo = sbt_next_number($postingDate);
    if (!$db->insert('erp_storage_bin_transfer', array(
      'transfer_no'=>$transferNo,'document_date'=>$documentDate,'posting_date'=>$postingDate,'movement_type'=>'311',
      'source_plant_id'=>$sourceLoc->plant_id,'source_storage_location_id'=>$sourceStorageLocationId,'source_storage_bin_id'=>$sourceStorageBinId ?: null,
      'destination_plant_id'=>$destLoc->plant_id,'destination_storage_location_id'=>$destinationStorageLocationId,'destination_storage_bin_id'=>$destinationStorageBinId ?: null,
      'reference_no'=>$referenceNo,'reason_code'=>$reasonCode,'reason_text'=>$reasonText,'status'=>'POSTED','created_by'=>$username
    ))) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Header transfer gagal disimpan.'); }
    $transferId = $db->last_insert_id();
    $lineNo = 1;
    foreach ($items as $item) {
      $material = $db->fetch("SELECT kd_barang,nm_barang,satuan FROM barang WHERE kd_barang=? LIMIT 1", array($item['material_code']));
      if (!$material) { $db->query('ROLLBACK'); sbt_json('error', 'Material '.$item['material_code'].' tidak ditemukan.'); }
      list($layerWhere, $layerParams) = sbt_layer_where($item['material_code'], $sourceStorageLocationId, $sourceStorageBinId);
      $available = $db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) AS available_qty FROM stock_layer sl ".$layerWhere, $layerParams);
      if (!$available || (float)$available->available_qty + 0.00001 < $item['qty']) { $db->query('ROLLBACK'); sbt_json('error', 'Stock tidak cukup untuk '.$item['material_code'].'.'); }
      if (!$db->insert('erp_storage_bin_transfer_detail', array('transfer_id'=>$transferId,'line_no'=>$lineNo,'material_code'=>$material->kd_barang,'material_name'=>$material->nm_barang,'qty'=>$item['qty'],'uom'=>$material->satuan,'remarks'=>$item['remarks']))) {
        $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Detail transfer gagal disimpan.');
      }
      $detailId = $db->last_insert_id();
      $remaining = $item['qty'];
      $detailAmount = 0;
      $weightedValue = 0;
      $layers = sbt_fetch_layers($item['material_code'], $sourceStorageLocationId, $sourceStorageBinId, true);
      foreach ($layers as $layer) {
        if ($remaining <= 0) break;
        $take = min($remaining, (float)$layer->qty_sisa);
        if ($take <= 0) continue;
        $price = sbt_layer_price($layer);
        $amount = round($take * $price, 2);
        $ok = $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($take,$layer->id,$take));
        if (!$ok) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Source stock layer gagal dikurangi.'); }
        if (!$db->insert('stock_layer', array(
          'kode'=>$layer->kode,'qty_masuk'=>$take,'qty_sisa'=>$take,'no_aju'=>$layer->no_aju,'no_dokpab'=>$layer->no_dokpab,'lokasi'=>'GUDANG','stock_type'=>$layer->stock_type,
          'plant_id'=>$destLoc->plant_id,'storage_location_id'=>$destinationStorageLocationId,'storage_bin_id'=>$destinationStorageBinId ?: null,
          'jenis_dokpab'=>$layer->jenis_dokpab,'ref_table'=>'erp_storage_bin_transfer_trace','ref_id'=>null,'tgl_masuk'=>$postingDate,'no_bpb'=>$layer->no_bpb
        ))) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Destination stock layer gagal dibuat.'); }
        $destLayerId = $db->last_insert_id();
        $outDocId = sbt_insert_material_doc(array('no_ref'=>$transferNo,'ref_pengganti'=>$referenceNo,'no_aju'=>$layer->no_aju,'no_dokpab'=>$layer->no_dokpab,'id_incoming_detail'=>($layer->ref_table === 'pemasukan_detail') ? $layer->ref_id : null,'move_code'=>'311','posisi'=>'GUDANG','no_urut'=>$lineNo,'qty'=>$take * -1,'kd_barang'=>$material->kd_barang,'lokasi'=>'GUDANG','document_date'=>$documentDate,'posting_date'=>$postingDate,'user'=>$username,'direction'=>'OUT','ref_type'=>'SBT','ref_id'=>$layer->id,'ref_detail_id'=>$detailId,'uom'=>$material->satuan,'price'=>$price,'amount'=>$amount,'reason'=>$reasonCode,'created_by'=>$username,'no_bpb'=>$layer->no_bpb,'plant_id'=>$layer->plant_id,'storage_location_id'=>$layer->storage_location_id,'storage_bin_id'=>$layer->storage_bin_id,'stock_type'=>$layer->stock_type,'destination_storage_location_id'=>$destinationStorageLocationId,'destination_storage_bin_id'=>$destinationStorageBinId ?: null,'destination_stock_type'=>$layer->stock_type,'destination_material_code'=>$material->kd_barang,'remark'=>'Storage Bin Transfer 311 OUT '.$transferNo));
        if (!$outDocId) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Material document OUT gagal disimpan.'); }
        $inDocId = sbt_insert_material_doc(array('no_ref'=>$transferNo,'ref_pengganti'=>$referenceNo,'no_aju'=>$layer->no_aju,'no_dokpab'=>$layer->no_dokpab,'id_incoming_detail'=>($layer->ref_table === 'pemasukan_detail') ? $layer->ref_id : null,'move_code'=>'311','posisi'=>'GUDANG','no_urut'=>$lineNo,'qty'=>$take,'kd_barang'=>$material->kd_barang,'lokasi'=>'GUDANG','document_date'=>$documentDate,'posting_date'=>$postingDate,'user'=>$username,'direction'=>'IN','ref_type'=>'SBT','ref_id'=>$destLayerId,'ref_detail_id'=>$outDocId,'uom'=>$material->satuan,'price'=>$price,'amount'=>$amount,'reason'=>$reasonCode,'created_by'=>$username,'no_bpb'=>$layer->no_bpb,'plant_id'=>$destLoc->plant_id,'storage_location_id'=>$destinationStorageLocationId,'storage_bin_id'=>$destinationStorageBinId ?: null,'stock_type'=>$layer->stock_type,'destination_storage_location_id'=>$destinationStorageLocationId,'destination_storage_bin_id'=>$destinationStorageBinId ?: null,'destination_stock_type'=>$layer->stock_type,'destination_material_code'=>$material->kd_barang,'remark'=>'Storage Bin Transfer 311 IN '.$transferNo));
        if (!$inDocId) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Material document IN gagal disimpan.'); }
        if (!$db->insert('erp_storage_bin_transfer_trace', array(
          'transfer_id'=>$transferId,'transfer_detail_id'=>$detailId,'source_stock_layer_id'=>$layer->id,'destination_stock_layer_id'=>$destLayerId,'material_doc_out_id'=>$outDocId,'material_doc_in_id'=>$inDocId,'qty'=>$take,'price'=>$price,'amount'=>$amount,'stock_type'=>$layer->stock_type,
          'source_plant_id'=>$layer->plant_id,'source_storage_location_id'=>$layer->storage_location_id,'source_storage_bin_id'=>$layer->storage_bin_id,
          'destination_plant_id'=>$destLoc->plant_id,'destination_storage_location_id'=>$destinationStorageLocationId,'destination_storage_bin_id'=>$destinationStorageBinId ?: null,
          'no_bpb'=>$layer->no_bpb,'no_aju'=>$layer->no_aju,'jenis_dokpab'=>$layer->jenis_dokpab,'no_dokpab'=>$layer->no_dokpab,'source_ref_table'=>$layer->ref_table,'source_ref_id'=>$layer->ref_id
        ))) { $err=$db->getErrorMessage(); $db->query('ROLLBACK'); sbt_json('error',$err ?: 'Trace transfer gagal disimpan.'); }
        $traceId = $db->last_insert_id();
        $db->query("UPDATE stock_layer SET ref_id=? WHERE id=?", array($traceId,$destLayerId));
        $detailAmount += $amount;
        $weightedValue += $take * $price;
        $remaining -= $take;
      }
      if ($remaining > 0.00001) { $db->query('ROLLBACK'); sbt_json('error','Stock layer tidak cukup untuk '.$item['material_code'].'.'); }
      $detailPrice = $item['qty'] > 0 ? $weightedValue / $item['qty'] : 0;
      $db->query("UPDATE erp_storage_bin_transfer_detail SET price=?,amount=? WHERE id=?", array($detailPrice,$detailAmount,$detailId));
      $lineNo++;
    }
    $db->insert('erp_storage_bin_transfer_history', array('transfer_id'=>$transferId,'status_baru'=>'POSTED','remarks'=>'Storage Bin Transfer 311 posted','changed_by'=>$username));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' posting Storage Bin Transfer '.$transferNo.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    sbt_json('good','',array('transfer_no'=>$transferNo));
    break;

  case 'detail':
    sbt_render_detail(isset($_POST['id']) ? (int)$_POST['id'] : 0);
    break;

  case 'reversal':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($id <= 0 || $reason === '') sbt_json('error','Dokumen dan alasan reversal wajib diisi.');
    $h = $db->fetch("SELECT * FROM erp_storage_bin_transfer WHERE id=? LIMIT 1", array($id));
    if (!$h) sbt_json('error','Transfer tidak ditemukan.');
    if ($h->status === 'REVERSED') sbt_json('error','Transfer sudah reversal.');
    $traces = $db->query("SELECT t.*,d.material_code,d.material_name,d.uom,d.line_no FROM erp_storage_bin_transfer_trace t JOIN erp_storage_bin_transfer_detail d ON d.id=t.transfer_detail_id WHERE t.transfer_id=? ORDER BY t.id", array($id));
    $db->query('START TRANSACTION');
    $revNo = substr($h->transfer_no.'_REV', 0, 30);
    foreach ($traces as $t) {
      $qty = (float)$t->qty;
      $dest = $db->fetch("SELECT qty_sisa FROM stock_layer WHERE id=? FOR UPDATE", array($t->destination_stock_layer_id));
      if (!$dest || (float)$dest->qty_sisa + 0.00001 < $qty) { $db->query('ROLLBACK'); sbt_json('error','Destination stock layer #'.$t->destination_stock_layer_id.' tidak cukup untuk reversal.'); }
      $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=?", array($qty,$t->destination_stock_layer_id));
      $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa+? WHERE id=?", array($qty,$t->source_stock_layer_id));
      sbt_insert_material_doc(array('no_ref'=>$revNo,'ref_pengganti'=>$h->transfer_no,'no_aju'=>$t->no_aju,'no_dokpab'=>$t->no_dokpab,'move_code'=>'312','posisi'=>'GUDANG','no_urut'=>$t->line_no,'qty'=>$qty * -1,'kd_barang'=>$t->material_code,'lokasi'=>'GUDANG','document_date'=>date('Y-m-d'),'posting_date'=>date('Y-m-d'),'user'=>$username,'direction'=>'OUT','ref_type'=>'SBT_REV','ref_id'=>$t->destination_stock_layer_id,'ref_detail_id'=>$t->id,'is_reversal'=>1,'uom'=>$t->uom,'price'=>$t->price,'amount'=>$t->amount,'reason'=>$reason,'created_by'=>$username,'no_bpb'=>$t->no_bpb,'plant_id'=>$t->destination_plant_id,'storage_location_id'=>$t->destination_storage_location_id,'storage_bin_id'=>$t->destination_storage_bin_id,'stock_type'=>$t->stock_type,'destination_storage_location_id'=>$t->source_storage_location_id,'destination_storage_bin_id'=>$t->source_storage_bin_id,'destination_stock_type'=>$t->stock_type,'destination_material_code'=>$t->material_code,'remark'=>'Reversal 312 OUT '.$h->transfer_no));
      sbt_insert_material_doc(array('no_ref'=>$revNo,'ref_pengganti'=>$h->transfer_no,'no_aju'=>$t->no_aju,'no_dokpab'=>$t->no_dokpab,'move_code'=>'312','posisi'=>'GUDANG','no_urut'=>$t->line_no,'qty'=>$qty,'kd_barang'=>$t->material_code,'lokasi'=>'GUDANG','document_date'=>date('Y-m-d'),'posting_date'=>date('Y-m-d'),'user'=>$username,'direction'=>'IN','ref_type'=>'SBT_REV','ref_id'=>$t->source_stock_layer_id,'ref_detail_id'=>$t->id,'is_reversal'=>1,'uom'=>$t->uom,'price'=>$t->price,'amount'=>$t->amount,'reason'=>$reason,'created_by'=>$username,'no_bpb'=>$t->no_bpb,'plant_id'=>$t->source_plant_id,'storage_location_id'=>$t->source_storage_location_id,'storage_bin_id'=>$t->source_storage_bin_id,'stock_type'=>$t->stock_type,'destination_storage_location_id'=>$t->source_storage_location_id,'destination_storage_bin_id'=>$t->source_storage_bin_id,'destination_stock_type'=>$t->stock_type,'destination_material_code'=>$t->material_code,'remark'=>'Reversal 312 IN '.$h->transfer_no));
    }
    $db->query("UPDATE erp_storage_bin_transfer SET status='REVERSED',reversed_by=?,reversed_at=?,reversal_reason=? WHERE id=?", array($username,date('Y-m-d H:i:s'),$reason,$id));
    $db->insert('erp_storage_bin_transfer_history', array('transfer_id'=>$id,'status_lama'=>'POSTED','status_baru'=>'REVERSED','remarks'=>$reason,'changed_by'=>$username));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' reversal Storage Bin Transfer '.$h->transfer_no.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    sbt_json('good');
    break;

  case 'excel':
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    ini_set('display_errors', '0');
    $excelInitialOutputBufferLevel = ob_get_level();
    ob_start();
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $tglAwal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
    $tglAkhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
    $where = " WHERE 1=1 ";
    $params = array();
    if (sbt_valid_date($tglAwal) && sbt_valid_date($tglAkhir)) { $where .= " AND h.posting_date BETWEEN ? AND ? "; $params[]=$tglAwal; $params[]=$tglAkhir; }
    if (!empty($_GET['status'])) { $where .= " AND h.status=? "; $params[]=$_GET['status']; }
    if (!empty($_GET['source_storage_location_id'])) { $where .= " AND h.source_storage_location_id=? "; $params[]=(int)$_GET['source_storage_location_id']; }
    if (!empty($_GET['destination_storage_location_id'])) { $where .= " AND h.destination_storage_location_id=? "; $params[]=(int)$_GET['destination_storage_location_id']; }
    if (!empty($_GET['keyword'])) { $kw='%'.trim($_GET['keyword']).'%'; $where .= " AND (h.transfer_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.no_bpb LIKE ?) "; for($i=0;$i<6;$i++)$params[]=$kw; }
    $rows = $db->query(
      "SELECT h.transfer_no,h.posting_date,h.document_date,h.reason_code,h.reason_text,h.status,
              ss.storage_code AS source_sloc,ds.storage_code AS dest_sloc,sb.bin_code AS source_bin,dbin.bin_code AS dest_bin,
              d.material_code,d.material_name,d.qty,d.uom,d.price,d.amount,t.no_bpb,t.no_aju,t.jenis_dokpab,t.no_dokpab,t.source_stock_layer_id,t.destination_stock_layer_id
       FROM erp_storage_bin_transfer h
       JOIN erp_storage_bin_transfer_detail d ON d.transfer_id=h.id
       LEFT JOIN erp_storage_bin_transfer_trace t ON t.transfer_detail_id=d.id
       LEFT JOIN erp_storage_location ss ON ss.id=h.source_storage_location_id
       LEFT JOIN erp_storage_location ds ON ds.id=h.destination_storage_location_id
       LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id
       LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
       $where
       ORDER BY h.posting_date DESC,h.id DESC,d.line_no,t.id",
      $params
    );
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('SBin Transfer'));
    $sheet->mergeCells('A1:V1'); $sheet->mergeCells('A2:V2');
    $sheet->setCellValue('A1', namaPT); $sheet->setCellValue('A2', 'STORAGE BIN TRANSFER');
    $headers = array(erp_export_label("No"),erp_export_label("Transfer No"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Reason"),erp_export_label("Source SLoc"),erp_export_label("Source Bin"),erp_export_label("Destination SLoc"),erp_export_label("Destination Bin"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Amount"),erp_export_label("Source Layer"),erp_export_label("Destination Layer"),erp_export_label("No BPB"),erp_export_label("No Aju"),erp_export_label("Dok Pabean"),erp_export_label("Status"));
    foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
    $r=5; $n=1;
    foreach ($rows as $row) {
      $values = array($n++,$row->transfer_no,$row->posting_date,$row->document_date,$row->reason_code.' - '.$row->reason_text,$row->source_sloc,$row->source_bin,$row->dest_sloc,$row->dest_bin,$row->material_code,$row->material_name,(float)$row->qty,$row->uom,(float)$row->price,(float)$row->amount,$row->source_stock_layer_id,$row->destination_stock_layer_id,$row->no_bpb,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),$row->status);
      foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
      $r++;
    }
    $sheet->getStyle('A1:V2')->getFont()->setBold(true);
    $sheet->getStyle('A1:V2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:U4')->getFont()->setBold(true);
    $sheet->getStyle('A4:U'.max(4,$r-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle('L5:L'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00000');
    $sheet->getStyle('N5:O'.max(5,$r-1))->getNumberFormat()->setFormatCode('#,##0.00');
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('STORAGE BIN TRANSFER'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>22,'numeric_columns'=>array('L'),'money_columns'=>array('N','O'),'filters'=>array('Periode'=>$tglAwal.' s/d '.$tglAkhir,'Status'=>isset($_GET['status'])?$_GET['status']:'','Keyword'=>isset($_GET['keyword'])?$_GET['keyword']:'')));
    $tmp = tempnam(sys_get_temp_dir(), 'sbt_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = filesize($tmp);
    while (ob_get_level() > $excelInitialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="storage_bin_transfer_'.$tglAwal.'_sd_'.$tglAkhir.'.xlsx"');
    header('Content-Length: '.$size);
    readfile($tmp); @unlink($tmp); exit;

  default:
    sbt_json('error','Action tidak dikenal.');
}
?>
