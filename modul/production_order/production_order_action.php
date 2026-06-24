<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();
include_once dirname(__DIR__)."/production_order_release/release_helper.php";

function po_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json');
  $payload = array('status' => $status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $key => $value) $payload[$key] = $value;
  echo json_encode($payload);
  exit;
}

function po_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function po_num($value, $dec = 5) {
  return number_format((float)$value, $dec, ',', '.');
}

function po_clean_qty($value) {
  return (float)str_replace(',', '.', trim((string)$value));
}

function po_next_number($startDate) {
  global $db;
  $prefix = 'PO'.date('Ym', strtotime($startDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT no_production_order FROM production_order WHERE no_production_order LIKE ? ORDER BY no_production_order DESC LIMIT 1", array('no' => $prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->no_production_order, $m)) $next = intval($m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}

function po_status_label($status) {
  $class = 'default';
  if ($status === 'CREATED') $class = 'default';
  if ($status === 'RELEASED') $class = 'success';
  if ($status === 'IN_PROCESS') $class = 'info';
  if ($status === 'CONFIRMED') $class = 'primary';
  if ($status === 'TECO') $class = 'warning';
  if ($status === 'CLOSED') $class = 'primary';
  if ($status === 'CANCELLED') $class = 'danger';
  return '<span class="label label-'.$class.'">'.po_h($status).'</span>';
}

function po_select_production_version($materialCode, $plant, $orderQty, $startDate) {
  global $db;
  $date = $startDate ?: date('Y-m-d');
  $params = array($materialCode, $plant, $date, $date);
  $where = "WHERE material_code=? AND plant_code=? AND version_status='RELEASED' AND locked='N'
            AND valid_from<=? AND (valid_to IS NULL OR valid_to>=?)";
  if ((float)$orderQty > 0) {
    $where .= " AND (lot_size_from IS NULL OR lot_size_from<=?) AND (lot_size_to IS NULL OR lot_size_to=0 OR lot_size_to>=?)";
    $params[] = (float)$orderQty;
    $params[] = (float)$orderQty;
  }
  return $db->fetch(
    "SELECT * FROM erp_production_version $where
     ORDER BY CASE WHEN is_default='Y' THEN 0 ELSE 1 END, valid_from DESC, version_code ASC, id DESC
     LIMIT 1",
    $params
  );
}

function po_get_bom_items($materialCode, $orderQty, $bomId = null) {
  global $db;
  if ($bomId) $bom = $db->fetch("SELECT * FROM bom WHERE id=? LIMIT 1", array('id' => $bomId));
  else $bom = $db->fetch("SELECT * FROM bom WHERE kodebj=? ORDER BY CASE WHEN bom_status='RELEASED' THEN 0 ELSE 1 END, valid_from DESC,id DESC LIMIT 1", array('kode' => $materialCode));
  $items = array();
  if (!$bom) return $items;
  $baseQty = (float)$bom->jumlah;
  if ($baseQty <= 0) $baseQty = 1;
  $rows = $db->query(
    "SELECT bd.*,COALESCE(NULLIF(bd.nm_barang,''),b.nm_barang) AS component_name,
            COALESCE(NULLIF(bd.satuan,''),b.satuan) AS component_uom,
            b.kd_kategori,b.default_storage_location_id,sl.storage_code
     FROM bom_detail bd
     LEFT JOIN barang b ON b.kd_barang=bd.kodebb
     LEFT JOIN erp_storage_location sl ON sl.id=b.default_storage_location_id
     WHERE bd.id_bom=?
     ORDER BY bd.id",
    array('id' => $bom->id)
  );
  foreach ($rows as $row) {
    $requiredQty = ((float)$row->jumlah / $baseQty) * (float)$orderQty;
    $items[] = array(
      'material_code' => $row->kodebb,
      'material_name' => $row->component_name,
      'kd_kategori' => $row->kd_kategori,
      'required_qty' => $requiredQty,
      'uom' => $row->component_uom,
      'storage_location' => $row->storage_code,
      'remarks' => 'BOM '.($bom->bom_no ?: $bom->kodebj)
    );
  }
  return $items;
}

function po_get_routing_operations($materialCode, $plant, $orderQty, $startDate, $routingId = null) {
  global $db;
  $items = array();
  $date = $startDate ?: date('Y-m-d');
  if ($routingId) $routing = $db->fetch("SELECT * FROM erp_routing WHERE id=? LIMIT 1", array('id' => $routingId));
  else {
    $params = array($materialCode, $date, $date);
    $where = "WHERE material_code=? AND routing_status='RELEASED' AND COALESCE(routing_usage,'PRODUCTION')='PRODUCTION'
              AND valid_from<=? AND (valid_to IS NULL OR valid_to>=?)";
    if ($plant !== '') {
      $where .= " AND (plant_code=? OR plant_code IS NULL OR plant_code='')";
      $params[] = $plant;
    }
    if ((float)$orderQty > 0) {
      $where .= " AND (lot_size_from IS NULL OR lot_size_from<=?) AND (lot_size_to IS NULL OR lot_size_to=0 OR lot_size_to>=?)";
      $params[] = (float)$orderQty;
      $params[] = (float)$orderQty;
    }
    $orderParams = $params;
    $orderParams[] = $plant;
    $routing = $db->fetch(
      "SELECT * FROM erp_routing $where
       ORDER BY CASE WHEN plant_code=? THEN 0 ELSE 1 END, valid_from DESC, alternative_routing ASC, id DESC
       LIMIT 1",
      $orderParams
    );
  }
  if (!$routing) return $items;
  $rows = $db->query("SELECT * FROM erp_routing_operation WHERE routing_id=? AND operation_status='ACTIVE' ORDER BY operation_no,id", array('routing_id' => $routing->id));
  foreach ($rows as $row) {
    $items[] = array(
      'operation_no' => $row->operation_no,
      'work_center' => $row->work_center_code,
      'operation_name' => $row->operation_name,
      'setup_time' => $row->setup_time,
      'machine_time' => $row->machine_time,
      'labor_time' => $row->labor_time,
      'remarks' => 'Routing '.$routing->routing_no.' / '.$row->control_key
    );
  }
  return $items;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'so_item_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT
          so.id_sales_order,
          so.no_sales_order,
          so.kode_penerima AS customer_code,
          so.no_po AS customer_po,
          p.nama AS customer_name,
          sod.id_detail AS id_sales_order_detail,
          sod.kd_barang AS material_code,
          COALESCE(b.nm_barang,sod.ket) AS material_name,
          COALESCE(b.satuan,'') AS uom,
          COALESCE(sod.qty,0) AS qty,
          COALESCE(prod.completed_qty,0) AS produced_qty,
          COALESCE(ship.shipped_qty,0) AS shipped_qty,
          GREATEST(COALESCE(sod.qty,0)-GREATEST(COALESCE(prod.completed_qty,0),COALESCE(ship.shipped_qty,0)),0) AS remaining_qty
       FROM sales_order_detail sod
       JOIN sales_order so ON so.id_sales_order=sod.id_sales_order
       LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima
       LEFT JOIN barang b ON b.kd_barang=sod.kd_barang
       LEFT JOIN (
          SELECT id_sales_order_detail, SUM(COALESCE(completed_qty,0)) AS completed_qty
          FROM production_order
          WHERE id_sales_order_detail IS NOT NULL AND status<>'CANCELLED'
          GROUP BY id_sales_order_detail
       ) prod ON prod.id_sales_order_detail=sod.id_detail
       LEFT JOIN (
          SELECT sj.no_sales_order,d.kode_barang,SUM(COALESCE(d.qty_kirim,0)) AS shipped_qty
          FROM surat_jalan sj
          JOIN surat_jalan_detail d ON d.surat_jalan_id=sj.id
          WHERE sj.status<>'dibatalkan'
          GROUP BY sj.no_sales_order,d.kode_barang
       ) ship ON ship.no_sales_order=so.no_sales_order AND ship.kode_barang=sod.kd_barang
       WHERE COALESCE(so.approval_status,'')='APPROVED'
         AND GREATEST(COALESCE(sod.qty,0)-GREATEST(COALESCE(prod.completed_qty,0),COALESCE(ship.shipped_qty,0)),0) > 0
         AND (?='' OR so.no_sales_order LIKE ? OR so.no_po LIKE ? OR p.nama LIKE ? OR sod.kd_barang LIKE ? OR b.nm_barang LIKE ?)
       ORDER BY so.so_date DESC, so.no_sales_order DESC, sod.id_detail ASC
       LIMIT 30",
      array($term, $like, $like, $like, $like, $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->id_sales_order_detail,
        'text' => $row->no_sales_order.' | '.$row->material_code.' - '.$row->material_name.' | Rem '.po_num($row->remaining_qty).' '.$row->uom,
        'id_sales_order' => $row->id_sales_order,
        'no_sales_order' => $row->no_sales_order,
        'id_sales_order_detail' => $row->id_sales_order_detail,
        'customer_code' => $row->customer_code,
        'customer_name' => $row->customer_name,
        'customer_po' => $row->customer_po,
        'material_code' => $row->material_code,
        'material_name' => $row->material_name,
        'uom' => $row->uom,
        'qty' => $row->qty,
        'produced_qty' => $row->produced_qty,
        'shipped_qty' => $row->shipped_qty,
        'remaining_qty' => $row->remaining_qty
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'material_search':
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';
    $like = '%'.$term.'%';
    $rows = $db->query(
      "SELECT b.kd_barang,b.nm_barang,b.satuan,b.kd_kategori,ep.plant_code,sl.storage_code
       FROM barang b
       LEFT JOIN erp_plant ep ON ep.id=b.plant_id
       LEFT JOIN erp_storage_location sl ON sl.id=b.default_storage_location_id
       WHERE COALESCE(b.status,1)=1
         AND (?='' OR b.kd_barang LIKE ? OR b.nm_barang LIKE ?)
       ORDER BY b.kd_barang
       LIMIT 30",
      array('term' => $term, 'a' => $like, 'b' => $like)
    );
    $results = array();
    foreach ($rows as $row) {
      $results[] = array(
        'id' => $row->kd_barang,
        'text' => $row->kd_barang.' - '.$row->nm_barang,
        'material_name' => $row->nm_barang,
        'uom' => $row->satuan,
        'plant' => $row->plant_code,
        'storage_location' => $row->storage_code,
        'kd_kategori' => $row->kd_kategori
      );
    }
    echo json_encode(array('results' => $results));
    break;

  case 'bom_preview':
    $materialCode = isset($_POST['material_code']) ? trim($_POST['material_code']) : '';
    $orderQty = isset($_POST['order_qty']) ? po_clean_qty($_POST['order_qty']) : 0;
    if ($materialCode === '' || $orderQty <= 0) {
      echo '<div class="alert alert-warning">Pilih material dan isi order qty untuk explode BOM.</div>';
      break;
    }
    $items = po_get_bom_items($materialCode, $orderQty);
    if (empty($items)) {
      echo '<div class="alert alert-warning">BOM belum ditemukan untuk material '.po_h($materialCode).'. Material requirement bisa ditambahkan manual nanti.</div>';
      break;
    }
    ?>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed po-items">
        <thead><tr><th>Component</th><th class="text-right">Required Qty</th><th>UOM</th><th>SLoc</th></tr></thead>
        <tbody>
        <?php foreach ($items as $idx => $item) { ?>
          <tr>
            <td><strong><?=po_h($item['material_code']);?></strong><br><small><?=po_h($item['material_name']);?></small></td>
            <td class="text-right"><?=po_num($item['required_qty']);?></td>
            <td><?=po_h($item['uom']);?></td>
            <td><?=po_h($item['storage_location']);?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case 'create':
    $materialCode = isset($_POST['material_code']) ? trim($_POST['material_code']) : '';
    $materialName = isset($_POST['material_name']) ? trim($_POST['material_name']) : '';
    $orderQty = isset($_POST['order_qty']) ? po_clean_qty($_POST['order_qty']) : 0;
    $uom = isset($_POST['uom']) ? trim($_POST['uom']) : '';
    $plant = isset($_POST['plant']) ? trim($_POST['plant']) : '';
    $storageLocation = isset($_POST['storage_location']) ? trim($_POST['storage_location']) : '';
    $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $finishDate = isset($_POST['finish_date']) ? trim($_POST['finish_date']) : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'NORMAL';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
    $autoRelease = isset($_POST['auto_release']) && $_POST['auto_release'] === 'Y';
    $orderStrategy = isset($_POST['order_strategy']) && $_POST['order_strategy'] === 'MTO' ? 'MTO' : 'MTS';
    $idSalesOrder = isset($_POST['id_sales_order']) && $_POST['id_sales_order'] !== '' ? (int)$_POST['id_sales_order'] : null;
    $noSalesOrder = isset($_POST['no_sales_order']) ? trim($_POST['no_sales_order']) : '';
    $idSalesOrderDetail = isset($_POST['id_sales_order_detail']) && $_POST['id_sales_order_detail'] !== '' ? (int)$_POST['id_sales_order_detail'] : null;
    $customerCode = isset($_POST['customer_code']) ? trim($_POST['customer_code']) : '';
    $customerPo = isset($_POST['customer_po']) ? trim($_POST['customer_po']) : '';

    if ($materialCode === '') po_json('error', 'Material FG/SFG wajib dipilih.');
    if ($orderQty <= 0) po_json('error', 'Order Qty wajib lebih dari nol.');
    if ($uom === '') po_json('error', 'UOM wajib diisi.');
    if ($plant === '') po_json('error', 'Plant wajib dipilih.');
    if ($startDate === '' || $finishDate === '') po_json('error', 'Start Date dan Finish Date wajib diisi.');
    if ($orderStrategy === 'MTO' && !$idSalesOrderDetail) po_json('error', 'Make to Order wajib memilih Sales Order Item.');
    if ($orderStrategy === 'MTS') {
      $idSalesOrder = null;
      $noSalesOrder = '';
      $idSalesOrderDetail = null;
      $customerCode = '';
      $customerPo = '';
    }

    if ($orderStrategy === 'MTO' && $idSalesOrderDetail) {
      $soItem = $db->fetch(
        "SELECT so.id_sales_order,so.no_sales_order,so.kode_penerima,so.no_po,sod.kd_barang,sod.qty,COALESCE(b.nm_barang,sod.ket) AS material_name,COALESCE(b.satuan,?) AS uom
         FROM sales_order_detail sod
         JOIN sales_order so ON so.id_sales_order=sod.id_sales_order
         LEFT JOIN barang b ON b.kd_barang=sod.kd_barang
         WHERE sod.id_detail=? LIMIT 1",
        array('uom' => $uom, 'id' => $idSalesOrderDetail)
      );
      if (!$soItem) po_json('error', 'Sales Order item tidak ditemukan.');
      $idSalesOrder = (int)$soItem->id_sales_order;
      $noSalesOrder = $soItem->no_sales_order;
      $customerCode = $soItem->kode_penerima;
      $customerPo = $soItem->no_po;
      $materialCode = $soItem->kd_barang;
      $materialName = $materialName ?: $soItem->material_name;
      $uom = $uom ?: $soItem->uom;
    }

    $mat = $db->fetch("SELECT kd_barang,nm_barang,satuan FROM barang WHERE kd_barang=? LIMIT 1", array('kode' => $materialCode));
    if ($mat) {
      $materialName = $materialName ?: $mat->nm_barang;
      $uom = $uom ?: $mat->satuan;
    }
    $noPo = po_next_number($startDate);
    $status = $autoRelease ? 'RELEASED' : 'CREATED';
    $productionVersion = po_select_production_version($materialCode, $plant, $orderQty, $startDate);

    $db->query('START TRANSACTION');
    if (!$db->insert('production_order', array(
      'no_production_order' => $noPo,
      'id_sales_order' => $idSalesOrder,
      'no_sales_order' => $noSalesOrder,
      'id_sales_order_detail' => $idSalesOrderDetail,
      'customer_code' => $customerCode,
      'customer_po' => $customerPo,
      'order_type' => isset($_POST['order_type']) ? $_POST['order_type'] : 'PP01',
      'order_strategy' => $orderStrategy,
      'production_version_id' => $productionVersion ? $productionVersion->id : null,
      'production_version_no' => $productionVersion ? $productionVersion->production_version_no : null,
      'bom_id' => $productionVersion ? $productionVersion->bom_id : null,
      'bom_no' => $productionVersion ? $productionVersion->bom_no : null,
      'routing_id' => $productionVersion ? $productionVersion->routing_id : null,
      'routing_no' => $productionVersion ? $productionVersion->routing_no : null,
      'plant' => $plant,
      'storage_location' => $storageLocation,
      'material_code' => $materialCode,
      'material_name' => $materialName,
      'order_qty' => $orderQty,
      'uom' => $uom,
      'start_date' => $startDate,
      'finish_date' => $finishDate,
      'priority' => $priority,
      'status' => $status,
      'remarks' => $remarks,
      'created_by' => $username,
      'updated_by' => $username
    ))) {
      $err = $db->getErrorMessage();
      $db->query('ROLLBACK');
      po_json('error', $err ?: 'Production Order gagal disimpan.');
    }
    $poId = $db->last_insert_id();

    $items = po_get_bom_items($materialCode, $orderQty, $productionVersion ? $productionVersion->bom_id : null);
    $line = 10;
    foreach ($items as $item) {
      if (!$db->insert('production_order_material', array(
        'id_production_order' => $poId,
        'material_code' => $item['material_code'],
        'material_name' => $item['material_name'],
        'kd_kategori' => $item['kd_kategori'],
        'required_qty' => $item['required_qty'],
        'issued_qty' => 0,
        'remaining_qty' => $item['required_qty'],
        'uom' => $item['uom'],
        'storage_location' => $item['storage_location'],
        'issue_status' => 'OPEN',
        'remarks' => $item['remarks']
      ))) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        po_json('error', $err ?: 'Material requirement gagal disimpan.');
      }
      $line += 10;
    }

    $ops = po_get_routing_operations($materialCode, $plant, $orderQty, $startDate, $productionVersion ? $productionVersion->routing_id : null);
    if (empty($ops)) {
      $ops = array(
        array('operation_no' => '0010', 'work_center' => 'PREP', 'operation_name' => 'Preparation', 'setup_time' => 0, 'machine_time' => 0, 'labor_time' => 0, 'remarks' => 'Default operation'),
        array('operation_no' => '0020', 'work_center' => 'PROC', 'operation_name' => 'Production Process', 'setup_time' => 0, 'machine_time' => 0, 'labor_time' => 0, 'remarks' => 'Default operation'),
        array('operation_no' => '0030', 'work_center' => 'QC', 'operation_name' => 'In Process Inspection', 'setup_time' => 0, 'machine_time' => 0, 'labor_time' => 0, 'remarks' => 'Default operation')
      );
    }
    foreach ($ops as $op) {
      $db->insert('production_order_operation', array(
        'id_production_order' => $poId,
        'operation_no' => $op['operation_no'],
        'work_center' => $op['work_center'],
        'operation_name' => $op['operation_name'],
        'setup_time' => $op['setup_time'],
        'machine_time' => $op['machine_time'],
        'labor_time' => $op['labor_time'],
        'status' => 'OPEN',
        'remarks' => $op['remarks']
      ));
    }
    if (function_exists('simpan_log')) simpan_log('User '.$username.' membuat Production Order '.$noPo.' material '.$materialCode.' qty '.$orderQty.' pada '.date('Y-m-d H:i:s'), $username);
    $db->query('COMMIT');
    po_json('good', '', array('production_order' => $noPo, 'id' => $poId));
    break;

  case 'release':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : 'Released from Production Order menu';
    $release = por_release_order($id, $username, $remarks);
    if ($release['status'] !== 'good') po_json('error', $release['message'], array('readiness' => $release['readiness']));
    po_json('good', '', array('readiness' => $release['readiness']));
    break;

  case 'cancel':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($reason === '') po_json('error', 'Reason cancel wajib diisi.');
    $po = $db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1", array('id' => $id));
    if (!$po) po_json('error', 'Production Order tidak ditemukan.');
    if (!in_array($po->status, array('CREATED','RELEASED'), true)) po_json('error', 'Production Order yang sudah proses tidak bisa cancel.');
    $db->query("UPDATE production_order SET status='CANCELLED',remarks=CONCAT(COALESCE(remarks,''), ?),updated_by=? WHERE id_production_order=?", array('reason' => "\nCancel reason: ".$reason, 'user' => $username, 'id' => $id));
    if (function_exists('simpan_log')) simpan_log('User '.$username.' cancel Production Order '.$po->no_production_order.' alasan '.$reason, $username);
    po_json('good');
    break;

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $po = $db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1", array('id' => $id));
    if (!$po) {
      echo '<div class="alert alert-warning">Production Order tidak ditemukan.</div>';
      break;
    }
    $materials = $db->query("SELECT * FROM production_order_material WHERE id_production_order=? ORDER BY id_material", array('id' => $id));
    $operations = $db->query("SELECT * FROM production_order_operation WHERE id_production_order=? ORDER BY operation_no,id_operation", array('id' => $id));
    $movements = $db->query("SELECT * FROM production_order_goods_movement WHERE id_production_order=? ORDER BY posting_date DESC,id DESC", array('id' => $id));
    ?>
    <div class="row">
      <div class="col-md-8">
        <h3 style="margin-top:0;font-weight:700"><?=po_h($po->no_production_order);?> <small><?=po_h($po->material_code.' - '.$po->material_name);?></small></h3>
        <p class="text-muted">Plant <?=po_h($po->plant);?> | SLoc <?=po_h($po->storage_location);?> | <?=po_h($po->order_type);?> | <?=po_h($po->order_strategy === 'MTO' ? 'Make to Order' : 'Make to Stock');?></p>
        <?php if ($po->no_sales_order) { ?>
          <p><span class="label label-info">Sales Order</span> <?=po_h($po->no_sales_order);?> | Customer <?=po_h($po->customer_code ?: '-');?> | Customer PO <?=po_h($po->customer_po ?: '-');?></p>
        <?php } ?>
      </div>
      <div class="col-md-4 text-right"><?=po_status_label($po->status);?></div>
    </div>
    <div class="row">
      <div class="col-sm-3"><strong>Order Qty</strong><br><?=po_num($po->order_qty).' '.po_h($po->uom);?></div>
      <div class="col-sm-3"><strong>Completed</strong><br><?=po_num($po->completed_qty).' '.po_h($po->uom);?></div>
      <div class="col-sm-3"><strong>Start</strong><br><?=po_h($po->start_date);?></div>
      <div class="col-sm-3"><strong>Finish</strong><br><?=po_h($po->finish_date);?></div>
    </div>
    <hr>
    <h4>Material Requirement</h4>
    <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Material</th><th class="text-right">Required</th><th class="text-right">Issued</th><th class="text-right">Remaining</th><th>UOM</th><th>Status</th><th>SLoc</th></tr></thead><tbody>
      <?php foreach ($materials as $m) { ?>
        <tr><td><strong><?=po_h($m->material_code);?></strong><br><small><?=po_h($m->material_name);?></small></td><td class="text-right"><?=po_num($m->required_qty);?></td><td class="text-right"><?=po_num($m->issued_qty);?></td><td class="text-right"><?=po_num($m->remaining_qty);?></td><td><?=po_h($m->uom);?></td><td><?=po_h($m->issue_status);?></td><td><?=po_h($m->storage_location);?></td></tr>
      <?php } ?>
    </tbody></table></div>
    <h4>Operations</h4>
    <table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Op</th><th>Work Center</th><th>Name</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
      <?php foreach ($operations as $op) { ?>
        <tr><td><?=po_h($op->operation_no);?></td><td><?=po_h($op->work_center);?></td><td><?=po_h($op->operation_name);?></td><td><?=po_h($op->status);?></td><td><?=po_h($op->remarks);?></td></tr>
      <?php } ?>
    </tbody></table>
    <h4>Goods Movement</h4>
    <table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Movement</th><th>Material</th><th class="text-right">Qty</th><th>UOM</th><th>Posting Date</th><th>Remarks</th></tr></thead><tbody>
      <?php foreach ($movements as $mv) { ?>
        <tr><td><?=po_h($mv->movement_type);?></td><td><?=po_h($mv->material_code.' - '.$mv->material_name);?></td><td class="text-right"><?=po_num($mv->qty);?></td><td><?=po_h($mv->uom);?></td><td><?=po_h($mv->posting_date);?></td><td><?=po_h($mv->remarks);?></td></tr>
      <?php } ?>
    </tbody></table>
    <?php
    break;

  default:
    po_json('error', 'Action tidak dikenal.');
    break;
}
?>
