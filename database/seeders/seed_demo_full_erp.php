<?php
/**
 * Full demo data seeder for ERPKB.
 *
 * Safe rules:
 * - Does not truncate master data.
 * - Uses deterministic DEMO-* / DUMMY-* document numbers.
 * - Upserts by business keys to avoid duplicates.
 * - Runs the existing end-to-end PR -> PO -> GR -> Production -> Delivery -> Invoice seed.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));
$pdo->exec('SET NAMES utf8mb4');

$MARKER = 'DEMO-ERP-202606';
$DATE = '2026-06-21';
$NOW = $DATE.' 09:00:00';
$USER = 'admin';

function table_exists(PDO $pdo, $table)
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute(array($table));
    return (bool)$st->fetchColumn();
}

function table_columns(PDO $pdo, $table)
{
    static $cache = array();
    if (!isset($cache[$table])) {
        $cache[$table] = array();
        if (table_exists($pdo, $table)) {
            foreach ($pdo->query('SHOW COLUMNS FROM `'.$table.'`') as $row) {
                $cache[$table][$row['Field']] = $row;
            }
        }
    }
    return $cache[$table];
}

function filter_data(PDO $pdo, $table, array $data)
{
    $cols = table_columns($pdo, $table);
    return array_intersect_key($data, $cols);
}

function one(PDO $pdo, $sql, array $params = array())
{
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch();
}

function exec_sql(PDO $pdo, $sql, array $params = array())
{
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

function insert_row(PDO $pdo, $table, array $data)
{
    $data = filter_data($pdo, $table, $data);
    if (!$data) {
        throw new RuntimeException("No valid columns for insert into $table");
    }
    $cols = array_keys($data);
    $sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', array_fill(0, count($cols), '?')).')';
    $st = $pdo->prepare($sql);
    $st->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}

function upsert_row(PDO $pdo, $table, array $keys, array $data)
{
    if (!table_exists($pdo, $table)) {
        return null;
    }
    $keys = filter_data($pdo, $table, $keys);
    $data = filter_data($pdo, $table, $data);
    if (!$keys) {
        throw new RuntimeException("No valid upsert key columns for $table");
    }
    $where = array();
    $params = array();
    foreach ($keys as $k => $v) {
        $where[] = "`$k`=?";
        $params[] = $v;
    }
    $row = one($pdo, 'SELECT * FROM `'.$table.'` WHERE '.implode(' AND ', $where).' LIMIT 1', $params);
    if ($row) {
        $update = array_diff_key($data, $keys);
        if ($update) {
            $sets = array();
            $vals = array();
            foreach ($update as $k => $v) {
                $sets[] = "`$k`=?";
                $vals[] = $v;
            }
            $pdo->prepare('UPDATE `'.$table.'` SET '.implode(',', $sets).' WHERE '.implode(' AND ', $where))
                ->execute(array_merge($vals, $params));
        }
        return $row;
    }
    insert_row($pdo, $table, array_merge($data, $keys));
    return one($pdo, 'SELECT * FROM `'.$table.'` WHERE '.implode(' AND ', $where).' LIMIT 1', $params);
}

function post_journal(PDO $pdo, $no, $date, $source, $desc, array $lines, $user)
{
    if (!table_exists($pdo, 'jurnal_header') || !table_exists($pdo, 'jurnal_detail')) {
        return null;
    }
    $debit = 0;
    $credit = 0;
    foreach ($lines as $line) {
        $debit += round((float)$line['debet'], 2);
        $credit += round((float)$line['kredit'], 2);
    }
    if (abs($debit - $credit) > 0.01) {
        throw new RuntimeException("Journal $no is not balance. Debit=$debit Credit=$credit");
    }
    $existing = one($pdo, "SELECT id FROM jurnal_header WHERE source_document_no=? OR no_bukti=? LIMIT 1", array($no, $no));
    if ($existing) {
        $pdo->prepare('DELETE FROM jurnal_detail WHERE id_header=?')->execute(array($existing['id']));
        $pdo->prepare('DELETE FROM jurnal_header WHERE id=?')->execute(array($existing['id']));
    }
    $id = insert_row($pdo, 'jurnal_header', array(
        'no_jurnal' => 'JRN-'.$no,
        'document_type' => 'SA',
        'posting_status' => 'POSTED',
        'tgl_jurnal' => $date,
        'ket' => $desc,
        'no_bukti' => $no,
        'source_module' => $source,
        'source_document_no' => $no,
        'username' => $user,
        'posted_by' => $user,
        'posted_at' => $date.' 12:00:00',
        'tgl_insert' => $date.' 12:00:00',
    ));
    $lineNo = 1;
    foreach ($lines as $line) {
        insert_row($pdo, 'jurnal_detail', array(
            'id_header' => $id,
            'line_no' => $lineNo++,
            'no_rek' => $line['no_rek'],
            'line_text' => $desc,
            'debet' => round((float)$line['debet'], 2),
            'kredit' => round((float)$line['kredit'], 2),
            'valuta' => 'IDR',
            'kurs' => 1,
        ));
    }
    return $id;
}

function seed_sales_order_started_flow(PDO $pdo, $date, $now, $user)
{
    $marker = 'DEMO-SO-FLOW';
    $plant = one($pdo, "SELECT * FROM erp_plant WHERE plant_code='PL01' LIMIT 1");
    $fgSloc = one($pdo, "SELECT * FROM erp_storage_location WHERE storage_code='FG01' LIMIT 1");
    $fgBin = one($pdo, "SELECT * FROM erp_storage_bin WHERE storage_location_id=? AND bin_code IN ('FG-A1','DEFAULT','001') ORDER BY FIELD(bin_code,'FG-A1','DEFAULT','001'),id LIMIT 1", array($fgSloc ? $fgSloc['id'] : 0));
    $customer = one($pdo, "SELECT * FROM customer WHERE kode_pemasok='DCUS0001' LIMIT 1");
    $fg = one($pdo, "SELECT * FROM barang WHERE kd_barang='BJ00001' LIMIT 1");
    if (!$plant || !$fgSloc || !$fgBin || !$customer || !$fg) {
        return array('skipped' => 'Required master data for sales-order-started flow is incomplete.');
    }

    upsert_row($pdo, 'penerima', array('kode_penerima' => 'DCUS0001'), array(
        'nama' => $customer['nama'],
        'alamat' => $customer['alamat'],
        'kota' => isset($customer['kota']) ? $customer['kota'] : 'Subang',
        'negara' => 'ID',
        'notelp' => isset($customer['notelp']) ? $customer['notelp'] : '021-000000',
        'email' => isset($customer['email']) ? $customer['email'] : 'demo.customer1@erpkb.local',
        'status' => 'Aktif',
    ));

    $soIds = array();
    foreach ($pdo->query("SELECT id_sales_order FROM sales_order WHERE no_sales_order LIKE 'SO-$marker-%'") as $row) {
        $soIds[] = (int)$row['id_sales_order'];
    }
    $deliveryIds = array();
    foreach ($pdo->query("SELECT id FROM erp_outbound_delivery WHERE delivery_no LIKE 'OD-$marker-%'") as $row) {
        $deliveryIds[] = (int)$row['id'];
    }
    $packingIds = array();
    foreach ($pdo->query("SELECT id FROM packing_list WHERE no_packing_list LIKE 'PL-$marker-%'") as $row) {
        $packingIds[] = (int)$row['id'];
    }
    $sjIds = array();
    foreach ($pdo->query("SELECT id FROM surat_jalan WHERE no_surat_jalan LIKE 'SJ-$marker-%'") as $row) {
        $sjIds[] = (int)$row['id'];
    }
    $giIds = array();
    foreach ($pdo->query("SELECT id FROM erp_goods_issue_delivery WHERE gi_no LIKE 'GID-$marker-%'") as $row) {
        $giIds[] = (int)$row['id'];
    }
    $grIds = array();
    foreach ($pdo->query("SELECT id FROM erp_gr_production WHERE gr_no LIKE 'GRP-$marker-%'") as $row) {
        $grIds[] = (int)$row['id'];
    }
    $poIds = array();
    foreach ($pdo->query("SELECT id_production_order FROM production_order WHERE no_production_order LIKE 'POPR-$marker-%'") as $row) {
        $poIds[] = (int)$row['id_production_order'];
    }

    if ($giIds) {
        $in = implode(',', array_fill(0, count($giIds), '?'));
        exec_sql($pdo, "DELETE FROM erp_goods_issue_delivery_trace WHERE gi_id IN ($in)", $giIds);
        exec_sql($pdo, "DELETE FROM erp_goods_issue_delivery_detail WHERE gi_id IN ($in)", $giIds);
        exec_sql($pdo, "DELETE FROM erp_goods_issue_delivery_history WHERE gi_id IN ($in)", $giIds);
        exec_sql($pdo, "DELETE FROM erp_goods_issue_delivery WHERE id IN ($in)", $giIds);
    }
    if ($sjIds) {
        $in = implode(',', array_fill(0, count($sjIds), '?'));
        exec_sql($pdo, "DELETE FROM surat_jalan_detail WHERE surat_jalan_id IN ($in)", $sjIds);
        exec_sql($pdo, "DELETE FROM surat_jalan WHERE id IN ($in)", $sjIds);
    }
    if ($packingIds) {
        $in = implode(',', array_fill(0, count($packingIds), '?'));
        exec_sql($pdo, "DELETE FROM packing_list_detail WHERE packing_list_id IN ($in)", $packingIds);
        exec_sql($pdo, "DELETE FROM packing_list WHERE id IN ($in)", $packingIds);
    }
    if ($deliveryIds) {
        $in = implode(',', array_fill(0, count($deliveryIds), '?'));
        exec_sql($pdo, "DELETE pd FROM erp_picking_detail pd JOIN erp_picking p ON p.id=pd.picking_id WHERE p.delivery_id IN ($in)", $deliveryIds);
        exec_sql($pdo, "DELETE FROM erp_picking WHERE delivery_id IN ($in)", $deliveryIds);
        exec_sql($pdo, "DELETE FROM erp_outbound_delivery_detail WHERE delivery_id IN ($in)", $deliveryIds);
        exec_sql($pdo, "DELETE FROM erp_outbound_delivery WHERE id IN ($in)", $deliveryIds);
    }
    if ($grIds) {
        $in = implode(',', array_fill(0, count($grIds), '?'));
        exec_sql($pdo, "DELETE FROM erp_gr_production_trace WHERE gr_id IN ($in)", $grIds);
        exec_sql($pdo, "DELETE FROM erp_gr_production_detail WHERE gr_id IN ($in)", $grIds);
        exec_sql($pdo, "DELETE FROM erp_gr_production WHERE id IN ($in)", $grIds);
    }
    if ($poIds) {
        $in = implode(',', array_fill(0, count($poIds), '?'));
        exec_sql($pdo, "DELETE FROM production_order_confirmation WHERE id_production_order IN ($in)", $poIds);
        exec_sql($pdo, "DELETE FROM production_order_material WHERE id_production_order IN ($in)", $poIds);
        exec_sql($pdo, "DELETE FROM production_order_operation WHERE id_production_order IN ($in)", $poIds);
        exec_sql($pdo, "DELETE FROM production_order WHERE id_production_order IN ($in)", $poIds);
    }
    if ($soIds) {
        $in = implode(',', array_fill(0, count($soIds), '?'));
        exec_sql($pdo, "DELETE sid FROM sales_invoice_detail sid JOIN sales_invoice si ON si.id_sales=sid.id_sales WHERE si.no_sales_order LIKE 'SO-$marker-%'");
        exec_sql($pdo, "DELETE FROM sales_invoice WHERE no_sales_order LIKE 'SO-$marker-%'");
        exec_sql($pdo, "DELETE FROM sales_order_detail WHERE id_sales_order IN ($in)", $soIds);
        exec_sql($pdo, "DELETE FROM sales_order WHERE id_sales_order IN ($in)", $soIds);
    }
    exec_sql($pdo, "DELETE FROM jurnal_detail WHERE id_header IN (SELECT id FROM jurnal_header WHERE source_document_no LIKE '%$marker%')");
    exec_sql($pdo, "DELETE FROM jurnal_header WHERE source_document_no LIKE '%$marker%'");
    exec_sql($pdo, "DELETE FROM detail_transaksi WHERE no_ref LIKE '%$marker%'");
    exec_sql($pdo, "DELETE FROM stock_layer WHERE no_bpb LIKE 'GRP-$marker-%'");

    $rawTrace = array();
    foreach ($pdo->query("SELECT sl.kode,COALESCE(b.nm_barang,sl.kode) AS nm_barang,sl.qty_masuk,COALESCE(b.satuan,'KGM') AS satuan,sl.no_bpb,sl.no_aju,sl.no_dokpab,sl.jenis_dokpab,'5903.20.00' AS hs_code FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode WHERE sl.no_aju LIKE 'AJU23%DUMMY%' ORDER BY sl.id LIMIT 5") as $row) {
        $rawTrace[] = $row;
    }
    if (!$rawTrace) {
        $rawTrace[] = array('kode' => 'BP00001', 'nm_barang' => 'Raw Material Demo', 'qty_masuk' => 1, 'satuan' => 'KGM', 'no_bpb' => 'BPB-DUMMY-E2E-001', 'no_aju' => 'AJU23DUMMY-E2E-001', 'no_dokpab' => 'BC23D001', 'jenis_dokpab' => 'BC 2.3', 'hs_code' => '5903.20.00');
    }

    $scenarios = array(
        array('suffix' => '001', 'label' => 'Belum Produksi', 'qty' => 100, 'produced' => 0, 'shipped' => 0),
        array('suffix' => '002', 'label' => 'Produksi Sebagian', 'qty' => 100, 'produced' => 40, 'shipped' => 0),
        array('suffix' => '003', 'label' => 'Selesai Produksi Belum Kirim', 'qty' => 100, 'produced' => 100, 'shipped' => 0),
        array('suffix' => '004', 'label' => 'Kirim Sebagian', 'qty' => 100, 'produced' => 100, 'shipped' => 40),
        array('suffix' => '005', 'label' => 'Sudah Dikirim Full', 'qty' => 100, 'produced' => 100, 'shipped' => 100),
    );

    $created = array();
    foreach ($scenarios as $idx => $s) {
        $suffix = $s['suffix'];
        $soNo = 'SO-'.$marker.'-'.$suffix;
        $poNo = 'POPR-'.$marker.'-'.$suffix;
        $grNo = 'GRP-'.$marker.'-'.$suffix;
        $unitPrice = 120000;
        $stdCost = 80000;
        $soId = insert_row($pdo, 'sales_order', array(
            'no_sales_order' => $soNo,
            'order_type' => 'OR',
            'so_date' => $date,
            'currency' => 'IDR',
            'consignee' => $customer['nama'],
            'catatan' => $marker.' '.$s['label'],
            'dari' => 'PL01',
            'ke' => 'Customer',
            'rupiah_rate' => 1,
            'rupiah_rate_sale' => 1,
            'kode_penerima' => 'DCUS0001',
            'sold_to_party' => 'DCUS0001',
            'ship_to_party' => 'DCUS0001',
            'bill_to_party' => 'DCUS0001',
            'payer' => 'DCUS0001',
            'tax' => 'PPN',
            'no_po' => 'CPO-'.$marker.'-'.$suffix,
            'sales_id' => $user,
            'user' => $user,
            'delivery_term' => 'FRANCO',
            'payment_term' => 'NET30',
            'delivery_date' => date('Y-m-d', strtotime($date.' +'.(3 + $idx).' days')),
            'shipping_address' => $customer['alamat'],
            'status' => 'APPROVED',
            'approval_status' => 'APPROVED',
            'submitted_by' => $user,
            'submitted_at' => $now,
            'approved_by' => $user,
            'approved_at' => $now,
            'date_created' => $now,
        ));
        $soDetailId = insert_row($pdo, 'sales_order_detail', array(
            'id_sales_order' => $soId,
            'line_no' => 10,
            'kd_barang' => 'BJ00001',
            'item_category' => 'TAN',
            'store' => $fgSloc['storage_code'],
            'plant_id' => $plant['id'],
            'storage_location_id' => $fgSloc['id'],
            'requested_delivery_date' => date('Y-m-d', strtotime($date.' +'.(3 + $idx).' days')),
            'ket' => $marker.' '.$s['label'],
            'qty' => $s['qty'],
            'confirmed_qty' => $s['qty'],
            'qty_terima' => 0,
            'price' => $unitPrice,
            'tax_percent' => 11,
            'nilai' => $s['qty'] * $unitPrice,
            'status' => '0',
            'date_created' => $now,
        ));
        $poId = insert_row($pdo, 'production_order', array(
            'no_production_order' => $poNo,
            'id_sales_order' => $soId,
            'no_sales_order' => $soNo,
            'id_sales_order_detail' => $soDetailId,
            'customer_code' => 'DCUS0001',
            'customer_po' => 'CPO-'.$marker.'-'.$suffix,
            'order_type' => 'PP01',
            'order_strategy' => 'MTO',
            'plant' => $plant['plant_code'],
            'storage_location' => $fgSloc['storage_code'],
            'material_code' => 'BJ00001',
            'material_name' => $fg['nm_barang'],
            'order_qty' => $s['qty'],
            'completed_qty' => $s['produced'],
            'scrap_qty' => 0,
            'uom' => $fg['satuan'],
            'start_date' => $date,
            'finish_date' => date('Y-m-d', strtotime($date.' +2 days')),
            'actual_start' => $s['produced'] > 0 ? $date.' 08:00:00' : null,
            'actual_finish' => $s['produced'] >= $s['qty'] ? $date.' 14:00:00' : null,
            'priority' => 'NORMAL',
            'status' => $s['produced'] <= 0 ? 'RELEASED' : ($s['produced'] < $s['qty'] ? 'IN_PROCESS' : 'CONFIRMED'),
            'remarks' => $marker.' '.$s['label'],
            'created_by' => $user,
            'created_at' => $now,
        ));
        insert_row($pdo, 'production_order_material', array(
            'id_production_order' => $poId,
            'material_code' => 'BP00001',
            'material_name' => 'Demo Raw Material',
            'required_qty' => $s['qty'],
            'issued_qty' => $s['produced'] > 0 ? $s['qty'] : 0,
            'remaining_qty' => $s['produced'] > 0 ? 0 : $s['qty'],
            'uom' => 'KGM',
            'storage_location' => 'RM01',
            'issue_status' => $s['produced'] > 0 ? 'FULL_ISSUE' : 'OPEN',
            'remarks' => $marker,
        ));
        insert_row($pdo, 'production_order_operation', array('id_production_order' => $poId, 'operation_no' => '0010', 'work_center' => 'WC-FIN', 'operation_name' => 'Demo Final Assembly', 'status' => $s['produced'] > 0 ? 'FINISHED' : 'OPEN', 'remarks' => $marker));

        $stockLayerId = null;
        $grDetailId = null;
        if ($s['produced'] > 0) {
            $amount = $s['produced'] * $stdCost;
            $confirmationNo = 'PC-'.$marker.'-'.$suffix;
            $confirmationId = insert_row($pdo, 'production_order_confirmation', array(
                'confirmation_no' => $confirmationNo,
                'id_production_order' => $poId,
                'confirmation_date' => $date.' 11:00:00',
                'document_date' => $date,
                'posting_date' => $date,
                'yield_qty' => $s['produced'],
                'scrap_qty' => 0,
                'scrap_handling' => 'LOSS',
                'rework_qty' => 0,
                'operation_no' => '0010',
                'work_center' => 'WC-FIN',
                'operation_name' => 'Demo Final Assembly',
                'operator_name' => 'Demo Operator',
                'shift_code' => 'SHIFT-1',
                'start_time' => $date.' 08:00:00',
                'end_time' => $date.' 11:00:00',
                'labor_time' => 3,
                'machine_time' => 3,
                'final_confirmation' => $s['produced'] >= $s['qty'] ? 'Y' : 'N',
                'status' => 'POSTED',
                'remarks' => $marker.' '.$s['label'],
                'created_by' => $user,
                'created_at' => $now,
            ));
            $grId = insert_row($pdo, 'erp_gr_production', array(
                'gr_no' => $grNo,
                'id_confirmation' => $confirmationId,
                'id_production_order' => $poId,
                'no_production_order' => $poNo,
                'confirmation_no' => $confirmationNo,
                'document_date' => $date,
                'posting_date' => $date,
                'movement_type' => '101',
                'plant_id' => $plant['id'],
                'storage_location_id' => $fgSloc['id'],
                'storage_bin_id' => $fgBin['id'],
                'stock_type' => 'UNRESTRICTED',
                'status' => 'POSTED',
                'total_amount' => $amount,
                'remarks' => $marker.' '.$s['label'],
                'created_by' => $user,
                'created_at' => $now,
            ));
            $stockLayerId = insert_row($pdo, 'stock_layer', array(
                'kode' => 'BJ00001',
                'qty_masuk' => $s['produced'],
                'qty_sisa' => $s['produced'] - $s['shipped'],
                'satuan' => $fg['satuan'],
                'price' => $stdCost,
                'amount' => $amount,
                'no_bpb' => $grNo,
                'no_aju' => 'AJU23DUMMY-E2E-001',
                'no_dokpab' => 'BC23D001',
                'jenis_dokpab' => 'BC 2.3',
                'lot_no' => 'FG-LOT-'.$marker.'-'.$suffix,
                'stock_type' => 'UNRESTRICTED',
                'plant_id' => $plant['id'],
                'storage_location_id' => $fgSloc['id'],
                'storage_bin_id' => $fgBin['id'],
                'ref_table' => 'erp_gr_production',
                'ref_id' => $grId,
                'tgl_masuk' => $date,
            ));
            $matDocId = insert_row($pdo, 'detail_transaksi', array(
                'no_ref' => $grNo,
                'ref_pengganti' => $poNo,
                'move_code' => '101',
                'posisi' => 'GUDANG',
                'no_urut' => 10,
                'qty' => $s['produced'],
                'kd_barang' => 'BJ00001',
                'lokasi' => 'GUDANG',
                'document_date' => $date.' 12:00:00',
                'posting_date' => $date.' 12:00:00',
                'user' => $user,
                'remark' => $marker.' GR Production '.$s['label'],
                'direction' => 'IN',
                'ref_type' => 'GR_PRODUCTION',
                'ref_id' => $grId,
                'uom' => $fg['satuan'],
                'price' => $stdCost,
                'amount' => $amount,
                'created_by' => $user,
                'no_bpb' => $grNo,
                'plant_id' => $plant['id'],
                'storage_location_id' => $fgSloc['id'],
                'storage_bin_id' => $fgBin['id'],
                'stock_type' => 'UNRESTRICTED',
                'destination_material_code' => 'BJ00001',
            ));
            $grDetailId = insert_row($pdo, 'erp_gr_production_detail', array(
                'gr_id' => $grId,
                'stock_layer_id' => $stockLayerId,
                'material_doc_id' => $matDocId,
                'material_code' => 'BJ00001',
                'material_name' => $fg['nm_barang'],
                'qty' => $s['produced'],
                'uom' => $fg['satuan'],
                'price' => $stdCost,
                'amount' => $amount,
                'stock_type' => 'UNRESTRICTED',
                'remarks' => $marker,
                'created_at' => $now,
            ));
            $traceQty = $s['produced'] / max(count($rawTrace), 1);
            foreach ($rawTrace as $rt) {
                insert_row($pdo, 'erp_gr_production_trace', array(
                    'gr_id' => $grId,
                    'gr_detail_id' => $grDetailId,
                    'output_stock_layer_id' => $stockLayerId,
                    'source_material_code' => $rt['kode'],
                    'source_material_name' => $rt['nm_barang'],
                    'raw_material_code' => $rt['kode'],
                    'raw_material_name' => $rt['nm_barang'],
                    'qty' => $traceQty,
                    'uom' => $rt['satuan'],
                    'lot_no' => 'FG-LOT-'.$marker.'-'.$suffix,
                    'no_bpb' => $rt['no_bpb'],
                    'no_aju' => $rt['no_aju'],
                    'jenis_dokpab' => $rt['jenis_dokpab'],
                    'no_dokpab' => $rt['no_dokpab'],
                    'hs_code' => $rt['hs_code'],
                    'trace_source' => 'INHERITED',
                    'created_at' => $now,
                ));
            }
            post_journal($pdo, $grNo, $date, 'GR_FROM_PRODUCTION', 'Sales-order-started GR Production '.$s['label'], array(
                array('no_rek' => '14300', 'debet' => $amount, 'kredit' => 0),
                array('no_rek' => '14302', 'debet' => 0, 'kredit' => $amount),
            ), $user);
        }

        if ($s['shipped'] > 0) {
            $shipAmount = $s['shipped'] * $stdCost;
            $deliveryNo = 'OD-'.$marker.'-'.$suffix;
            $pickingNo = 'PICK-'.$marker.'-'.$suffix;
            $plNo = 'PL-'.$marker.'-'.$suffix;
            $sjNo = 'SJ-'.$marker.'-'.$suffix;
            $gidNo = 'GID-'.$marker.'-'.$suffix;
            $deliveryId = insert_row($pdo, 'erp_outbound_delivery', array(
                'delivery_no' => $deliveryNo,
                'delivery_date' => $date,
                'planned_gi_date' => $date,
                'id_sales_order' => $soId,
                'no_sales_order' => $soNo,
                'customer_code' => 'DCUS0001',
                'customer_name' => $customer['nama'],
                'shipping_point' => 'SP01',
                'route' => 'LOCAL',
                'carrier' => 'DEMO LOGISTICS',
                'vehicle_no' => 'B 2026 KB',
                'driver_name' => 'Demo Driver',
                'ship_to_address' => $customer['alamat'],
                'status' => 'PGI',
                'picking_status' => 'COMPLETE',
                'packing_status' => 'COMPLETE',
                'gi_status' => 'POSTED',
                'remarks' => $marker.' '.$s['label'],
                'created_by' => $user,
                'created_at' => $now,
            ));
            $deliveryDetailId = insert_row($pdo, 'erp_outbound_delivery_detail', array(
                'delivery_id' => $deliveryId,
                'sales_order_detail_id' => $soDetailId,
                'line_no' => 10,
                'material_code' => 'BJ00001',
                'material_name' => $fg['nm_barang'],
                'store' => $fgSloc['storage_code'],
                'order_qty' => $s['qty'],
                'delivery_qty' => $s['shipped'],
                'picked_qty' => $s['shipped'],
                'packed_qty' => $s['shipped'],
                'gi_qty' => $s['shipped'],
                'uom' => $fg['satuan'],
                'price' => $unitPrice,
                'amount' => $s['shipped'] * $unitPrice,
                'batch_no' => 'FG-LOT-'.$marker.'-'.$suffix,
                'remarks' => $marker,
            ));
            $pickingId = insert_row($pdo, 'erp_picking', array('picking_no' => $pickingNo, 'picking_date' => $date, 'delivery_id' => $deliveryId, 'delivery_no' => $deliveryNo, 'id_sales_order' => $soId, 'no_sales_order' => $soNo, 'customer_code' => 'DCUS0001', 'customer_name' => $customer['nama'], 'warehouse' => $fgSloc['storage_code'], 'picker' => 'Demo Picker', 'status' => 'PICKED', 'remarks' => $marker, 'created_by' => $user, 'created_at' => $now));
            insert_row($pdo, 'erp_picking_detail', array('picking_id' => $pickingId, 'delivery_detail_id' => $deliveryDetailId, 'line_no' => 10, 'material_code' => 'BJ00001', 'material_name' => $fg['nm_barang'], 'store' => $fgSloc['storage_code'], 'delivery_qty' => $s['shipped'], 'picked_qty' => $s['shipped'], 'uom' => $fg['satuan'], 'batch_no' => 'FG-LOT-'.$marker.'-'.$suffix, 'source_bin' => $fgBin['bin_code'], 'remarks' => $marker));
            $packingId = insert_row($pdo, 'packing_list', array('delivery_id' => $deliveryId, 'delivery_no' => $deliveryNo, 'picking_id' => $pickingId, 'picking_no' => $pickingNo, 'no_packing_list' => $plNo, 'tgl_sj' => $date, 'penerima' => 'DCUS0001', 'pemilik' => 'DCUS0001', 'no_po' => 'CPO-'.$marker.'-'.$suffix, 'valuta' => 'IDR', 'kurs' => 1, 'vehicle_no' => 'B 2026 KB', 'status' => 'PACKED', 'packed_by' => $user, 'packed_at' => $now, 'remarks' => $marker));
            $packingDetailId = insert_row($pdo, 'packing_list_detail', array('packing_list_id' => $packingId, 'delivery_detail_id' => $deliveryDetailId, 'line_no' => 10, 'tgl_sj' => $date, 'kode_pemilik' => 'DCUS0001', 'kode' => 'BJ00001', 'material_name' => $fg['nm_barang'], 'delivery_qty' => $s['shipped'], 'picked_qty' => $s['shipped'], 'packed_qty' => $s['shipped'], 'jumlah' => $s['shipped'], 'harga' => $unitPrice, 'valuta' => 'IDR', 'hs_code' => '5903.20.00', 'nilai' => $s['shipped'] * $unitPrice, 'berat' => $s['shipped'], 'bruto' => $s['shipped'] * 1.02, 'lot_no' => 'FG-LOT-'.$marker.'-'.$suffix, 'packing' => 'ROLL', 'qty_packing' => '1', 'remark' => $marker, 'unit' => $fg['satuan'], 'kurs' => 1, 'row_no' => 10, 'kd_kategori' => isset($fg['kd_kategori']) ? $fg['kd_kategori'] : null));
            $sjId = insert_row($pdo, 'surat_jalan', array('no_surat_jalan' => $sjNo, 'id_sales_order' => $soId, 'packing_list_id' => $packingId, 'packing_list_no' => $plNo, 'delivery_id' => $deliveryId, 'delivery_no' => $deliveryNo, 'picking_no' => $pickingNo, 'movement_type' => '601', 'no_sales_order' => $soNo, 'shipping_point' => 'SP01', 'route' => 'LOCAL', 'carrier' => 'DEMO LOGISTICS', 'tgl_surat_jalan' => $date, 'document_date' => $date, 'posting_date' => $date, 'kode_penerima' => 'DCUS0001', 'sold_to_party' => 'DCUS0001', 'ship_to_party' => 'DCUS0001', 'bill_to_party' => 'DCUS0001', 'payer' => 'DCUS0001', 'no_po' => 'CPO-'.$marker.'-'.$suffix, 'alamat_pengiriman' => $customer['alamat'], 'sopir' => 'Demo Driver', 'no_kendaraan' => 'B 2026 KB', 'no_polisi' => 'B 2026 KB', 'keterangan' => $marker, 'total_qty' => $s['shipped'], 'status' => 'dikirim', 'delivery_status' => 'PGI', 'tgl_kirim' => $now, 'nama_penerima' => $customer['nama'], 'created_by' => $user, 'created_date' => $now));
            $sjDetailId = insert_row($pdo, 'surat_jalan_detail', array('surat_jalan_id' => $sjId, 'line_no' => 10, 'packing_list_detail_id' => $packingDetailId, 'delivery_detail_id' => $deliveryDetailId, 'id_sales_order_detail' => $soDetailId, 'material_code' => 'BJ00001', 'material_name' => $fg['nm_barang'], 'batch_no' => 'FG-LOT-'.$marker.'-'.$suffix, 'lot_no' => 'FG-LOT-'.$marker.'-'.$suffix, 'kode_barang' => 'BJ00001', 'nama_barang' => $fg['nm_barang'], 'packing' => 'ROLL', 'satuan_packing' => 'ROLL', 'qty_order' => $s['qty'], 'qty_kirim' => $s['shipped'], 'satuan' => $fg['satuan'], 'plant_id' => $plant['id'], 'storage_location_id' => $fgSloc['id'], 'storage_bin_id' => $fgBin['id'], 'stock_type' => 'UNRESTRICTED', 'bc_document_type' => 'BC 2.5', 'bc_document_no' => 'BC25-'.$marker.'-'.$suffix, 'bc_document_date' => $date, 'hs_code' => '5903.20.00', 'net_weight' => $s['shipped'], 'gross_weight' => $s['shipped'] * 1.02, 'keterangan' => $marker, 'row_no' => 10));
            $giId = insert_row($pdo, 'erp_goods_issue_delivery', array('gi_no' => $gidNo, 'delivery_id' => $deliveryId, 'delivery_no' => $deliveryNo, 'id_sales_order' => $soId, 'no_sales_order' => $soNo, 'customer_code' => 'DCUS0001', 'customer_name' => $customer['nama'], 'document_date' => $date, 'posting_date' => $date, 'movement_type' => '601', 'shipping_point' => 'SP01', 'vehicle_no' => 'B 2026 KB', 'driver_name' => 'Demo Driver', 'reference_surat_jalan' => $sjNo, 'outbound_bc_type' => 'BC 2.5', 'outbound_bc_purpose_code' => '25', 'outbound_bc_purpose' => 'Pengeluaran ke TLDDP (Jual lokal)', 'outbound_no_aju' => 'AJU25'.$marker.$suffix, 'outbound_tgl_aju' => $date, 'outbound_no_daftar' => 'BC25-'.$marker.'-'.$suffix, 'outbound_tgl_daftar' => $date, 'outbound_customs_office' => 'KPPBC Demo', 'outbound_destination_country' => 'ID', 'outbound_customs_remarks' => $marker, 'status' => 'POSTED', 'total_qty' => $s['shipped'], 'total_amount' => $shipAmount, 'remarks' => $marker, 'created_by' => $user, 'created_at' => $now));
            $giDetailId = insert_row($pdo, 'erp_goods_issue_delivery_detail', array('gi_id' => $giId, 'delivery_detail_id' => $deliveryDetailId, 'line_no' => 10, 'material_code' => 'BJ00001', 'material_name' => $fg['nm_barang'], 'qty' => $s['shipped'], 'uom' => $fg['satuan'], 'price' => $stdCost, 'amount' => $shipAmount, 'stock_type' => 'UNRESTRICTED', 'remarks' => $marker));
            $matDocId = insert_row($pdo, 'detail_transaksi', array('no_ref' => $gidNo, 'ref_pengganti' => $deliveryNo, 'move_code' => '601', 'posisi' => 'GUDANG', 'no_urut' => 10, 'qty' => -1 * $s['shipped'], 'kd_barang' => 'BJ00001', 'lokasi' => 'GUDANG', 'document_date' => $date.' 16:00:00', 'posting_date' => $date.' 16:00:00', 'user' => $user, 'remark' => $marker.' Goods Issue Delivery', 'direction' => 'OUT', 'ref_type' => 'GI_DELIVERY', 'ref_id' => $giId, 'ref_detail_id' => $giDetailId, 'uom' => $fg['satuan'], 'price' => $stdCost, 'amount' => $shipAmount, 'created_by' => $user, 'no_bpb' => $grNo, 'plant_id' => $plant['id'], 'storage_location_id' => $fgSloc['id'], 'storage_bin_id' => $fgBin['id'], 'stock_type' => 'UNRESTRICTED', 'destination_material_code' => 'BJ00001'));
            insert_row($pdo, 'erp_goods_issue_delivery_trace', array('gi_id' => $giId, 'gi_detail_id' => $giDetailId, 'stock_layer_id' => $stockLayerId, 'material_doc_id' => $matDocId, 'qty' => $s['shipped'], 'price' => $stdCost, 'amount' => $shipAmount, 'stock_type' => 'UNRESTRICTED', 'plant_id' => $plant['id'], 'storage_location_id' => $fgSloc['id'], 'storage_bin_id' => $fgBin['id'], 'no_bpb' => $grNo, 'no_aju' => 'AJU23DUMMY-E2E-001', 'no_dokpab' => 'BC23D001', 'jenis_dokpab' => 'BC 2.3', 'lot_no' => 'FG-LOT-'.$marker.'-'.$suffix, 'source_ref_table' => 'erp_gr_production', 'source_ref_id' => $grDetailId));
            exec_sql($pdo, "UPDATE surat_jalan SET gi_id=?,gi_no=? WHERE id=?", array($giId, $gidNo, $sjId));
            exec_sql($pdo, "UPDATE surat_jalan_detail SET gi_detail_id=? WHERE id=?", array($giDetailId, $sjDetailId));
            exec_sql($pdo, "UPDATE erp_outbound_delivery SET reference_gi=?,reference_surat_jalan=?,reference_packing_list=? WHERE id=?", array($gidNo, $sjNo, $plNo, $deliveryId));
            post_journal($pdo, $gidNo, $date, 'GOODS_ISSUE_DELIVERY', 'Sales-order-started GI Delivery '.$s['label'], array(
                array('no_rek' => '51100', 'debet' => $shipAmount, 'kredit' => 0),
                array('no_rek' => '14300', 'debet' => 0, 'kredit' => $shipAmount),
            ), $user);

            if ($s['shipped'] >= $s['qty']) {
                $net = $s['shipped'] * $unitPrice;
                $tax = round($net * 0.11, 2);
                $gross = $net + $tax;
                $invoiceNo = 'INV-S-'.$marker.'-'.$suffix;
                $invoiceId = insert_row($pdo, 'sales_invoice', array('billing_type' => 'F2', 'reference_type' => 'SURAT_JALAN', 'reference_no' => $sjNo, 'bill_to' => 'DCUS0001', 'no_sales_invoice' => $invoiceNo, 'no_sales_order' => $soNo, 'ship_to' => 'DCUS0001', 'invoice_date' => $date, 'posting_date' => $date, 'invoice_no' => $invoiceNo, 'nopo' => 'CPO-'.$marker.'-'.$suffix, 'term' => 'NET30', 'due_date' => '2026-07-21', 'valuta' => 'IDR', 'ship_date' => $date, 'no_do' => $deliveryNo, 'catatan' => $marker, 'tax' => 'PPN', 'tax_code' => 'PPN11', 'ar_account' => '12101', 'revenue_account' => '41100', 'tax_account' => '21807', 'tax_rate' => 11, 'net_amount' => $net, 'tax_amount' => $tax, 'gross_amount' => $gross, 'billing_status' => 'POSTED', 'created_by' => $user, 'posted_by' => $user, 'posted_at' => $now));
                insert_row($pdo, 'sales_invoice_detail', array('id_sales' => $invoiceId, 'sales_order_detail_id' => $soDetailId, 'surat_jalan_detail_id' => $sjDetailId, 'line_no' => 10, 'billing_item_type' => 'STANDARD', 'kd_barang' => 'BJ00001', 'nm_barang' => $fg['nm_barang'], 'material_number' => 'BJ00001', 'material_description' => $fg['nm_barang'], 'qty' => $s['shipped'], 'harga' => $unitPrice, 'unit' => $fg['satuan'], 'nilai' => $net, 'tax_code' => 'PPN11', 'tax_rate' => 11, 'tax_amount' => $tax, 'gross_amount' => $gross));
                $j = post_journal($pdo, $invoiceNo, $date, 'CUSTOMER_INVOICE', 'Sales-order-started Customer Invoice '.$s['label'], array(
                    array('no_rek' => '12101', 'debet' => $gross, 'kredit' => 0),
                    array('no_rek' => '41100', 'debet' => 0, 'kredit' => $net),
                    array('no_rek' => '21807', 'debet' => 0, 'kredit' => $tax),
                ), $user);
                exec_sql($pdo, "UPDATE sales_invoice SET journal_header_id=? WHERE id_sales=?", array($j, $invoiceId));
            }
        }
        $created[] = $soNo.' - '.$s['label'];
    }
    return $created;
}

function seed_prerequisite_master(PDO $pdo, $date, $now, $user)
{
    $plant = upsert_row($pdo, 'erp_plant', array('plant_code' => 'PL01'), array(
        'plant_code' => 'PL01',
        'plant_name' => 'Main Plant',
        'company_name' => 'PT ABC',
        'address' => 'Kawasan Berikat Demo',
        'city' => 'Subang',
        'country' => 'ID',
        'status' => 'Aktif',
    ));
    foreach (array(
        array('RM01', 'Raw Material Warehouse', 'RAW_MATERIAL'),
        array('WIP1', 'Work In Process Area', 'WIP'),
        array('FG01', 'Finished Goods Warehouse', 'FINISHED_GOODS'),
        array('SCR1', 'Scrap Warehouse', 'SCRAP'),
        array('GEN1', 'General Warehouse', 'GENERAL'),
    ) as $sloc) {
        $row = upsert_row($pdo, 'erp_storage_location', array('storage_code' => $sloc[0], 'plant_id' => $plant['id']), array(
            'storage_code' => $sloc[0],
            'plant_id' => $plant['id'],
            'storage_name' => $sloc[1],
            'storage_type' => $sloc[2],
            'status' => 'Aktif',
        ));
        upsert_row($pdo, 'erp_storage_bin', array('bin_code' => 'DEFAULT', 'storage_location_id' => $row['id']), array(
            'bin_code' => 'DEFAULT',
            'storage_location_id' => $row['id'],
            'bin_name' => 'Default Bin',
            'zone' => $sloc[0],
            'status' => 'Aktif',
        ));
    }
    foreach (array(
        array('ROH', 'Raw Material'),
        array('HALB', 'Semi Finished Goods'),
        array('FERT', 'Finished Goods'),
        array('SCRP', 'Scrap Material'),
    ) as $type) {
        upsert_row($pdo, 'erp_material_type', array('type_code' => $type[0]), array('type_name' => $type[1], 'status' => 'Aktif'));
    }
    foreach (array(
        array('RM-FILM', 'Raw Material Film'),
        array('RM-CHEM', 'Raw Material Chemical'),
        array('SFG-LAM', 'Semi Finished Lamination'),
        array('FG-PACK', 'Finished Packaging Product'),
        array('SCRAP', 'Scrap Material'),
    ) as $group) {
        upsert_row($pdo, 'erp_material_group', array('group_code' => $group[0]), array('group_name' => $group[1], 'description' => 'Demo '.$group[1], 'status' => 'Aktif'));
    }
    foreach (array(
        array('21806', '218', 3, 'PPN Masukan', 4, 1),
        array('61000', '610', 3, 'Beban Operasional Umum', 18, 1),
        array('71900', '711', 3, 'Pendapatan Penyesuaian Stock', 20, 1),
    ) as $account) {
        upsert_row($pdo, 'rekening', array('no_rek' => $account[0]), array(
            'induk' => $account[1],
            'level' => $account[2],
            'nama_rek' => $account[3],
            'kat_coa' => $account[4],
            'jenis' => $account[5],
        ));
    }
    upsert_row($pdo, 'pemasok', array('kode_pemasok' => 'S0000001'), array(
        'kode_pemasok' => 'S0000001',
        'npwp' => '00.000.000.0-000.000',
        'nama' => 'GBLIGHT CO.,LTD',
        'alamat' => '#22, BEOMBANG 3-RO, BUSAN',
        'kota' => 'Busan',
        'jenis' => 'IMPORT',
        'negara' => 'KR',
        'notelp' => '+82-51-0000',
        'email' => 'sales@gblight.example',
        'status' => 'Aktif',
    ));
    for ($i = 1; $i <= 5; $i++) {
        upsert_row($pdo, 'pemasok', array('kode_pemasok' => sprintf('DVEN%04d', $i)), array(
            'nama' => 'Demo Vendor '.$i,
            'alamat' => 'Industrial Estate Vendor '.$i,
            'kota' => $i % 2 ? 'Jakarta' : 'Busan',
            'jenis' => $i % 2 ? 'LOCAL' : 'IMPORT',
            'negara' => $i % 2 ? 'ID' : 'KR',
            'notelp' => '021-55500'.$i,
            'email' => 'vendor'.$i.'@demo.example',
            'status' => 'Aktif',
        ));
        upsert_row($pdo, 'customer', array('kode_pemasok' => sprintf('DCUS%04d', $i)), array(
            'nama' => 'Demo Customer '.$i,
            'alamat' => 'Customer Industrial Park '.$i,
            'kota' => $i % 2 ? 'Bandung' : 'Singapore',
            'negara' => $i % 2 ? 'Indonesia' : 'Singapore',
            'notelp' => '022-55500'.$i,
            'email' => 'customer'.$i.'@demo.example',
            'status' => 'Aktif',
            'created_by' => $user,
        ));
    }
    upsert_row($pdo, 'erp_cost_center', array('cost_center_code' => 'CC-PROD'), array('cost_center_name' => 'Production Cost Center', 'department_code' => 'PROD', 'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'status' => 'Aktif'));
    upsert_row($pdo, 'erp_cost_center', array('cost_center_code' => 'CC-HR'), array('cost_center_name' => 'Human Resource Cost Center', 'department_code' => 'HRD', 'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'status' => 'Aktif'));
    upsert_row($pdo, 'erp_profit_center', array('profit_center_code' => 'PC-MFG'), array('profit_center_name' => 'Manufacturing Profit Center', 'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'status' => 'Aktif'));
    upsert_row($pdo, 'erp_shift', array('kode_shift' => 'SHIFT-1'), array('nama_shift' => 'Shift 1', 'jam_mulai' => '08:00:00', 'jam_selesai' => '17:00:00', 'status' => 'Aktif'));
}

function seed_demo_materials(PDO $pdo, $date, $now, $user)
{
    $plant = one($pdo, "SELECT * FROM erp_plant WHERE plant_code='PL01' LIMIT 1");
    $rm = one($pdo, "SELECT * FROM erp_storage_location WHERE storage_code='RM01' AND plant_id=? LIMIT 1", array($plant['id']));
    $fg = one($pdo, "SELECT * FROM erp_storage_location WHERE storage_code='FG01' AND plant_id=? LIMIT 1", array($plant['id']));
    $typeRm = one($pdo, "SELECT id FROM erp_material_type WHERE type_code='ROH' LIMIT 1");
    $typeSfg = one($pdo, "SELECT id FROM erp_material_type WHERE type_code='HALB' LIMIT 1");
    $typeFg = one($pdo, "SELECT id FROM erp_material_type WHERE type_code='FERT' LIMIT 1");
    $grpRm = one($pdo, "SELECT id FROM erp_material_group WHERE group_code='RM-FILM' LIMIT 1");
    $grpSfg = one($pdo, "SELECT id FROM erp_material_group WHERE group_code='SFG-LAM' LIMIT 1");
    $grpFg = one($pdo, "SELECT id FROM erp_material_group WHERE group_code='FG-PACK' LIMIT 1");

    for ($i = 1; $i <= 20; $i++) {
        upsert_row($pdo, 'barang', array('kd_barang' => sprintf('DEMO-RM-%03d', $i)), array(
            'kategori' => 'Bahan Baku',
            'nm_barang' => 'Demo Raw Material '.$i,
            'type' => 'RAW_MATERIAL',
            'material_type_id' => $typeRm ? $typeRm['id'] : null,
            'material_group_id' => $grpRm ? $grpRm['id'] : null,
            'plant_id' => $plant['id'],
            'default_storage_location_id' => $rm['id'],
            'spec' => 'Demo spec RM '.$i,
            'satuan' => $i % 2 ? 'KGM' : 'MTR',
            'kd_kategori' => 'K01',
            'status' => 1,
            'ket' => 'Demo master '.$user,
        ));
    }
    for ($i = 1; $i <= 5; $i++) {
        upsert_row($pdo, 'barang', array('kd_barang' => sprintf('DEMO-SFG-%03d', $i)), array(
            'kategori' => 'Barang Setengah Jadi',
            'nm_barang' => 'Demo Semi Finished Goods '.$i,
            'type' => 'SEMI_FINISHED',
            'material_type_id' => $typeSfg ? $typeSfg['id'] : null,
            'material_group_id' => $grpSfg ? $grpSfg['id'] : null,
            'plant_id' => $plant['id'],
            'default_storage_location_id' => $fg['id'],
            'spec' => 'Demo SFG layered material '.$i,
            'satuan' => 'MTR',
            'kd_kategori' => 'K02',
            'status' => 1,
        ));
        upsert_row($pdo, 'barang', array('kd_barang' => sprintf('DEMO-FG-%03d', $i)), array(
            'kategori' => 'Barang Jadi',
            'nm_barang' => 'Demo Finished Goods '.$i,
            'type' => 'FINISHED_GOODS',
            'material_type_id' => $typeFg ? $typeFg['id'] : null,
            'material_group_id' => $grpFg ? $grpFg['id'] : null,
            'plant_id' => $plant['id'],
            'default_storage_location_id' => $fg['id'],
            'spec' => 'Demo FG export/local product '.$i,
            'satuan' => 'MTR',
            'kd_kategori' => 'K02',
            'status' => 1,
        ));
    }
}

function run_core_e2e_seed($root)
{
    $script = $root.'/database/scripts/seed_dummy_e2e_pr_to_delivery.php';
    if (!file_exists($script)) {
        throw new RuntimeException('Core E2E seed script was not found: '.$script);
    }
    $cmd = PHP_BINARY.' '.escapeshellarg($script).' 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new RuntimeException("Core E2E seed failed:\n".implode("\n", $out));
    }
    return implode("\n", $out);
}

function seed_planning_sales_quality_hr_finance(PDO $pdo, $marker, $date, $now, $user)
{
    $plant = one($pdo, "SELECT * FROM erp_plant WHERE plant_code='PL01' LIMIT 1");
    $fg = one($pdo, "SELECT * FROM barang WHERE kd_barang='DEMO-FG-001' LIMIT 1");
    $rm = one($pdo, "SELECT * FROM barang WHERE kd_barang='DEMO-RM-001' LIMIT 1");
    $customer = one($pdo, "SELECT * FROM customer WHERE kode_pemasok='DCUS0001' LIMIT 1");
    $cc = one($pdo, "SELECT * FROM erp_cost_center WHERE cost_center_code='CC-PROD' LIMIT 1");
    $pc = one($pdo, "SELECT * FROM erp_profit_center WHERE profit_center_code='PC-MFG' LIMIT 1");

    $forecast = upsert_row($pdo, 'erp_forecast', array('forecast_no' => 'FC-'.$marker), array(
        'forecast_type' => 'SALES', 'forecast_version' => 'BASE', 'plant_id' => $plant['id'], 'plant_code' => 'PL01',
        'customer_code' => $customer['kode_pemasok'], 'customer_name' => $customer['nama'], 'period_from' => '2026-06-01',
        'period_to' => '2026-06-30', 'status' => 'RELEASED', 'total_qty' => 500, 'remarks' => $marker,
        'created_by' => $user, 'created_at' => $now, 'released_by' => $user, 'released_at' => $now,
    ));
    upsert_row($pdo, 'erp_forecast_detail', array('forecast_id' => $forecast['id'], 'line_no' => 10), array(
        'material_code' => $fg['kd_barang'], 'material_name' => $fg['nm_barang'], 'period_month' => '2026-06-01',
        'forecast_qty' => 500, 'uom' => $fg['satuan'], 'source_type' => 'MANUAL', 'confidence_percent' => 90,
        'remarks' => $marker,
    ));
    $demand = upsert_row($pdo, 'erp_demand_plan', array('demand_no' => 'DM-'.$marker), array(
        'demand_type' => 'PIR', 'demand_version' => 'BASE', 'plant_id' => $plant['id'], 'plant_code' => 'PL01',
        'period_from' => '2026-06-01', 'period_to' => '2026-06-30', 'status' => 'RELEASED', 'total_qty' => 500,
        'source_forecast_id' => $forecast['id'], 'remarks' => $marker, 'created_by' => $user, 'created_at' => $now,
        'released_by' => $user, 'released_at' => $now,
    ));
    upsert_row($pdo, 'erp_demand_plan_detail', array('demand_id' => $demand['id'], 'line_no' => 10), array(
        'material_code' => $fg['kd_barang'], 'material_name' => $fg['nm_barang'], 'period_date' => $date,
        'demand_qty' => 500, 'uom' => $fg['satuan'], 'requirement_type' => 'VSF', 'source_type' => 'FORECAST',
        'source_ref' => 'FC-'.$marker, 'customer_code' => $customer['kode_pemasok'], 'customer_name' => $customer['nama'],
        'open_qty' => 500, 'remarks' => $marker,
    ));
    $mrp = upsert_row($pdo, 'erp_mrp_run', array('mrp_no' => 'MRP-'.$marker), array(
        'mrp_type' => 'NET_CHANGE', 'planning_scope' => 'DEMAND_PLAN', 'plant_id' => $plant['id'], 'plant_code' => 'PL01',
        'period_from' => '2026-06-01', 'period_to' => '2026-06-30', 'source_demand_id' => $demand['id'],
        'status' => 'RELEASED', 'total_material' => 2, 'total_gross_req' => 1500, 'total_shortage' => 900,
        'remarks' => $marker, 'created_by' => $user, 'created_at' => $now, 'released_by' => $user, 'released_at' => $now,
    ));
    upsert_row($pdo, 'erp_mrp_run_detail', array('mrp_id' => $mrp['id'], 'line_no' => 10), array(
        'material_code' => $fg['kd_barang'], 'material_name' => $fg['nm_barang'], 'requirement_date' => $date,
        'gross_requirement' => 500, 'available_stock' => 0, 'net_requirement' => 500, 'planned_order_qty' => 500,
        'uom' => $fg['satuan'], 'procurement_type' => 'IN_HOUSE', 'source_type' => 'DEMAND_PLAN', 'source_ref' => 'DM-'.$marker,
        'remarks' => $marker,
    ));
    $req = upsert_row($pdo, 'erp_material_requirement', array('requirement_no' => 'MR-'.$marker), array(
        'requirement_type' => 'MRP', 'requirement_date' => $date, 'required_from' => $date, 'required_to' => '2026-06-30',
        'plant_id' => $plant['id'], 'plant_code' => 'PL01', 'source_mrp_id' => $mrp['id'], 'source_mrp_no' => 'MRP-'.$marker,
        'requestor' => 'PPIC Demo', 'department' => 'PPIC', 'priority' => 'NORMAL', 'status' => 'APPROVED',
        'total_items' => 1, 'total_required_qty' => 500, 'total_open_qty' => 500, 'remarks' => $marker,
        'created_by' => $user, 'created_at' => $now, 'submitted_by' => $user, 'submitted_at' => $now,
        'approved_by' => $user, 'approved_at' => $now,
    ));
    upsert_row($pdo, 'erp_material_requirement_detail', array('requirement_id' => $req['id'], 'line_no' => 10), array(
        'material_code' => $rm['kd_barang'], 'material_name' => $rm['nm_barang'], 'required_date' => $date,
        'required_qty' => 500, 'approved_qty' => 500, 'open_qty' => 500, 'uom' => $rm['satuan'],
        'source_type' => 'MRP', 'source_ref' => 'MRP-'.$marker, 'procurement_type' => 'EXTERNAL', 'remarks' => $marker,
    ));

    $inquiry = upsert_row($pdo, 'sales_inquiry', array('inquiry_no' => 'INQ-'.$marker), array(
        'inquiry_date' => $date, 'valid_until' => '2026-06-30', 'requested_delivery_date' => '2026-06-28',
        'customer_code' => $customer['kode_pemasok'], 'customer_name' => $customer['nama'], 'contact_person' => 'Buyer Demo',
        'sales_person' => $user, 'priority' => 'NORMAL', 'status' => 'QUOTED', 'currency' => 'IDR',
        'subject' => 'Demo inquiry bonded zone product', 'remarks' => $marker, 'created_by' => $user, 'created_at' => $now,
    ));
    upsert_row($pdo, 'sales_inquiry_detail', array('inquiry_id' => $inquiry['id'], 'line_no' => 10), array(
        'material_code' => $fg['kd_barang'], 'material_name' => $fg['nm_barang'], 'qty' => 25, 'uom' => $fg['satuan'],
        'target_price' => 125000, 'estimated_amount' => 3125000, 'requested_delivery_date' => '2026-06-28', 'remarks' => $marker,
    ));
    $quote = upsert_row($pdo, 'sales_quotation', array('no_sales_quotation' => 'SQ-'.$marker), array(
        'inquiry_id' => $inquiry['id'], 'kode_penerima' => $customer['kode_pemasok'], 'customer_name' => $customer['nama'],
        'tgl' => $date, 'currency' => 'IDR', 'rupiah_rate' => 1, 'rupiah_rate_sale' => 1, 'tax' => 'PPN',
        'sales_id' => $user, 'user' => $user, 'payment_term' => 'NET30', 'valid_date' => '2026-06-30',
        'requested_delivery_date' => '2026-06-28', 'status' => 'SENT', 'contact_person' => 'Buyer Demo',
        'subject' => 'Demo quotation', 'catatan' => $marker, 'created_by' => $user,
    ));
    upsert_row($pdo, 'sales_quotation_detail', array('id_quotation' => $quote['id_quotation'], 'line_no' => 10), array(
        'kd_barang' => $fg['kd_barang'], 'valuta' => 'IDR', 'qty' => 25, 'uom' => $fg['satuan'],
        'price' => 125000, 'tax_percent' => 11, 'nilai' => 3125000, 'requested_delivery_date' => '2026-06-28',
        'ket' => $marker,
    ));
    upsert_row($pdo, 'sales_quotation_followup', array('quotation_id' => $quote['id_quotation'], 'followup_date' => $date.' 10:00:00'), array(
        'contact_method' => 'EMAIL', 'contact_person' => 'Buyer Demo', 'sales_person' => $user,
        'activity_type' => 'REMINDER', 'result_status' => 'WAITING_CUSTOMER', 'probability_percent' => 75,
        'discussion_summary' => $marker.' quotation follow up', 'next_action' => 'Customer confirmation',
        'next_followup_date' => '2026-06-24 10:00:00', 'created_by' => $user,
    ));

    seed_hr($pdo, $marker, $date, $now, $user, $cc, $pc);
    seed_quality($pdo, $marker, $date, $now, $user, $plant, $rm);
    seed_inventory_and_finance($pdo, $marker, $date, $now, $user, $plant, $rm, $cc, $pc);
}

function seed_hr(PDO $pdo, $marker, $date, $now, $user, $cc, $pc)
{
    $company = upsert_row($pdo, 'erp_company_structure', array('structure_code' => 'DEMO-COMP'), array(
        'structure_name' => 'PT Demo Kawasan Berikat', 'structure_type' => 'COMPANY', 'legal_entity_name' => 'PT Demo Kawasan Berikat',
        'tax_id' => '00.000.000.0-000.000', 'country' => 'ID', 'currency' => 'IDR', 'valid_from' => '2026-01-01',
        'valid_to' => '9999-12-31', 'address' => 'Kawasan Berikat Demo', 'city' => 'Subang',
        'cost_center_code' => $cc ? $cc['cost_center_code'] : 'CC-PROD', 'profit_center_code' => $pc ? $pc['profit_center_code'] : 'PC-MFG',
        'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user,
    ));
    foreach (array('HRD' => 'Human Resource', 'PROD' => 'Production', 'WH' => 'Warehouse', 'QA' => 'Quality Assurance', 'FIN' => 'Finance') as $code => $name) {
        upsert_row($pdo, 'dept', array('kd_dept' => $code), array(
            'nm_dept' => $name, 'dept_short_name' => $code, 'dept_type' => $code === 'PROD' ? 'PRODUCTION' : ($code === 'FIN' ? 'FINANCE' : ($code === 'QA' ? 'QUALITY' : ($code === 'WH' ? 'WAREHOUSE' : 'HR'))),
            'company_structure_id' => $company['id'], 'cost_center_code' => $code === 'HRD' ? 'CC-HR' : 'CC-PROD',
            'profit_center_code' => 'PC-MFG', 'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31',
            'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user,
        ));
    }
    $job = upsert_row($pdo, 'erp_job_title', array('job_title_code' => 'DEMO-OPR'), array(
        'job_title_name' => 'Production Operator', 'job_family' => 'PRODUCTION', 'job_level' => 'L2',
        'employee_group' => 'OPERATOR', 'department_code' => 'PROD', 'company_structure_id' => $company['id'],
        'cost_center_code' => 'CC-PROD', 'profit_center_code' => 'PC-MFG', 'pay_grade' => 'G2',
        'work_location_type' => 'PLANT', 'headcount_plan' => 10, 'minimum_education' => 'SMA/SMK',
        'job_purpose' => 'Run demo production operation', 'valid_from' => '2026-01-01',
        'valid_to' => '9999-12-31', 'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user,
    ));
    $location = upsert_row($pdo, 'erp_work_location', array('location_code' => 'DEMO-PLANT'), array(
        'location_name' => 'Demo Main Plant', 'location_type' => 'PLANT', 'company_structure_id' => $company['id'],
        'cost_center_code' => 'CC-PROD', 'profit_center_code' => 'PC-MFG', 'country' => 'ID', 'city' => 'Subang',
        'address' => 'Kawasan Berikat Demo', 'capacity_headcount' => 250, 'default_shift_code' => 'SHIFT-1',
        'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user,
    ));
    $position = upsert_row($pdo, 'erp_position', array('position_code' => 'DEMO-POS-OPR'), array(
        'position_name' => 'Production Operator Demo', 'position_type' => 'OPERATIONAL', 'position_category' => 'REGULAR',
        'job_title_id' => $job['id'], 'department_code' => 'PROD', 'company_structure_id' => $company['id'],
        'cost_center_code' => 'CC-PROD', 'profit_center_code' => 'PC-MFG', 'work_location_id' => $location['id'],
        'employee_group' => 'OPERATOR', 'pay_grade' => 'G2', 'planned_fte' => 10, 'headcount_plan' => 10,
        'vacancy_status' => 'PARTIAL', 'position_status' => 'ACTIVE', 'valid_from' => '2026-01-01',
        'valid_to' => '9999-12-31', 'job_description' => 'Demo operator role', 'remarks' => $marker, 'created_by' => $user,
    ));
    $employees = array();
    for ($i = 1; $i <= 10; $i++) {
        $emp = upsert_row($pdo, 'erp_employee_master', array('employee_no' => sprintf('DEMO-EMP-%03d', $i)), array(
            'personnel_no' => sprintf('900%03d', $i), 'first_name' => 'Demo', 'last_name' => 'Employee '.$i,
            'full_name' => 'Demo Employee '.$i, 'gender' => $i % 2 ? 'MALE' : 'FEMALE', 'birth_place' => 'Subang',
            'birth_date' => '1990-01-'.sprintf('%02d', min($i, 28)), 'nationality' => 'ID',
            'identity_no' => '3276'.str_pad((string)$i, 12, '0', STR_PAD_LEFT), 'email' => 'demo.employee'.$i.'@erpkb.local',
            'phone' => '08120000'.sprintf('%04d', $i), 'address' => 'Alamat demo employee '.$i,
            'hire_date' => '2026-01-01', 'employment_status' => 'ACTIVE', 'employee_group' => $i <= 2 ? 'STAFF' : 'OPERATOR',
            'company_structure_id' => $company['id'], 'department_code' => $i <= 2 ? 'HRD' : 'PROD', 'job_title_id' => $job['id'],
            'cost_center_code' => $i <= 2 ? 'CC-HR' : 'CC-PROD', 'profit_center_code' => 'PC-MFG',
            'payroll_area' => 'ID01', 'pay_grade' => 'G2', 'work_location_type' => 'PLANT', 'shift_code' => 'SHIFT-1',
            'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'remarks' => $marker, 'created_by' => $user,
        ));
        $employees[] = $emp;
        upsert_row($pdo, 'erp_employee_family_data', array('family_no' => sprintf('DEMO-FAM-%03d', $i)), array('employee_id' => $emp['id'], 'relationship_type' => 'SPOUSE', 'family_name' => 'Family Demo '.$i, 'gender' => $i % 2 ? 'FEMALE' : 'MALE', 'nationality' => 'ID', 'effective_from' => '2026-01-01', 'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user));
        upsert_row($pdo, 'erp_employee_education', array('education_no' => sprintf('DEMO-EDU-%03d', $i)), array('employee_id' => $emp['id'], 'education_level' => $i <= 2 ? 'S1' : 'SMA_SMK', 'education_type' => 'FORMAL', 'institution_name' => 'Demo School '.$i, 'major' => 'Manufacturing', 'country' => 'ID', 'start_year' => 2010, 'graduation_year' => 2014, 'highest_education' => 'Y', 'verified_status' => 'VERIFIED', 'effective_from' => '2026-01-01', 'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user));
        upsert_row($pdo, 'erp_employee_document', array('document_no' => sprintf('DEMO-DOC-%03d', $i)), array('employee_id' => $emp['id'], 'document_type' => 'KTP', 'document_category' => 'PERSONAL', 'document_title' => 'KTP Demo '.$i, 'document_number' => 'KTP-DEMO-'.$i, 'issue_date' => '2026-01-01', 'file_ref' => 'upload/hr/demo_ktp_'.$i.'.pdf', 'file_name' => 'demo_ktp_'.$i.'.pdf', 'file_type' => 'pdf', 'verification_status' => 'VERIFIED', 'effective_from' => '2026-01-01', 'status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user));
        upsert_row($pdo, 'erp_attendance', array('attendance_no' => sprintf('DEMO-ATT-%03d', $i)), array('employee_id' => $emp['id'], 'employee_no' => $emp['employee_no'], 'department_code' => $emp['department_code'], 'shift_code' => 'SHIFT-1', 'work_location_id' => $location['id'], 'attendance_date' => $date, 'planned_start' => $date.' 08:00:00', 'planned_end' => $date.' 17:00:00', 'actual_clock_in' => $date.' 07:55:00', 'actual_clock_out' => $date.' 17:05:00', 'actual_hours' => 8, 'attendance_type' => 'REGULAR', 'attendance_source' => 'MACHINE', 'attendance_status' => 'POSTED', 'posted_by' => $user, 'posted_at' => $now, 'remarks' => $marker, 'created_by' => $user));
    }
    $emp = $employees[0];
    upsert_row($pdo, 'erp_overtime', array('overtime_no' => 'DEMO-OT-001'), array('employee_id' => $emp['id'], 'employee_no' => $emp['employee_no'], 'department_code' => 'PROD', 'cost_center_code' => 'CC-PROD', 'overtime_date' => $date, 'planned_start' => $date.' 17:00:00', 'planned_end' => $date.' 19:00:00', 'actual_start' => $date.' 17:05:00', 'actual_end' => $date.' 19:05:00', 'requested_hours' => 2, 'approved_hours' => 2, 'payable_hours' => 2, 'hourly_rate' => 25000, 'estimated_amount' => 75000, 'overtime_reason' => 'Production catch up '.$marker, 'overtime_status' => 'APPROVED', 'created_by' => $user));
    upsert_row($pdo, 'erp_leave_request', array('leave_no' => 'DEMO-LV-001'), array(
        'employee_id' => $emp['id'], 'department_code' => 'PROD', 'job_title_id' => $job['id'], 'leave_type' => 'ANNUAL_LEAVE',
        'request_date' => $date, 'start_date' => '2026-06-25', 'end_date' => '2026-06-25', 'total_days' => 1,
        'leave_quota_before' => 12, 'leave_quota_after' => 11, 'reason' => $marker.' annual leave',
        'approver_employee_id' => $employees[1]['id'], 'workflow_status' => 'APPROVED', 'approval_level' => 'FINAL',
        'decision' => 'APPROVED', 'decision_by' => $user, 'decision_at' => $now, 'remarks' => $marker, 'created_by' => $user,
    ));
    $leave = one($pdo, "SELECT * FROM erp_leave_request WHERE leave_no='DEMO-LV-001'");
    upsert_row($pdo, 'erp_leave_approval', array('approval_no' => 'DEMO-LVA-001'), array(
        'leave_request_id' => $leave ? $leave['id'] : null, 'approval_step' => 'MANAGER',
        'approver_employee_id' => $employees[1]['id'], 'decision' => 'APPROVED', 'decision_date' => $now,
        'approval_note' => $marker, 'previous_status' => 'SUBMITTED', 'new_status' => 'APPROVED', 'created_by' => $user,
    ));
    $component = upsert_row($pdo, 'erp_payroll_component', array('component_code' => 'DEMO-BASIC'), array(
        'component_name' => 'Demo Basic Salary', 'wage_type_code' => '9100', 'component_type' => 'EARNING',
        'component_category' => 'BASIC_PAY', 'payroll_area' => 'MONTHLY', 'employee_group' => 'ALL',
        'calculation_method' => 'FIXED_AMOUNT', 'calculation_base' => 'NONE', 'default_amount' => 6500000,
        'currency' => 'IDR', 'taxable' => 'Y', 'bpjs_base' => 'Y', 'posting_required' => 'Y',
        'debit_credit' => 'DEBIT', 'gl_account_code' => '61000', 'cost_center_code' => 'CC-PROD',
        'valid_from' => '2026-01-01', 'valid_to' => '9999-12-31', 'component_status' => 'ACTIVE',
        'remarks' => $marker, 'created_by' => $user,
    ));
    $salary = upsert_row($pdo, 'erp_salary_structure', array('structure_code' => 'DEMO-SAL-001'), array(
        'structure_name' => 'Demo Operator Salary Structure', 'pay_scale_type' => 'MONTHLY', 'pay_scale_area' => 'ID',
        'pay_grade' => 'G2', 'pay_level' => 'L2', 'position_level' => 'Operator', 'employee_group' => 'OPERATOR',
        'payroll_area' => 'MONTHLY', 'currency' => 'IDR', 'base_salary_min' => 5500000, 'base_salary_mid' => 6500000,
        'base_salary_max' => 7500000, 'annual_ctc_min' => 66000000, 'annual_ctc_max' => 90000000,
        'cost_center_code' => 'CC-PROD', 'profit_center_code' => 'PC-MFG', 'valid_from' => '2026-06-01',
        'valid_to' => '9999-12-31', 'structure_status' => 'ACTIVE', 'remarks' => $marker, 'created_by' => $user,
    ));
    upsert_row($pdo, 'erp_salary_structure_detail', array('structure_id' => $salary['id'], 'component_code' => 'DEMO-BASIC'), array(
        'calculation_method' => 'FIXED_AMOUNT', 'amount' => 6500000, 'mandatory' => 'Y', 'taxable' => 'Y',
        'payslip_display' => 'Y', 'sequence_no' => 10,
    ));
    $process = upsert_row($pdo, 'erp_payroll_process', array('payroll_run_no' => 'DEMO-PAY-202606'), array(
        'period_year' => 2026, 'period_month' => 6, 'period_from' => '2026-06-01', 'period_to' => '2026-06-30',
        'pay_date' => '2026-06-30', 'payroll_area' => 'MONTHLY', 'process_type' => 'REGULAR', 'run_mode' => 'LIVE',
        'control_record_status' => 'EXITED', 'process_status' => 'POSTED', 'total_employee' => 1,
        'total_gross' => 6500000, 'total_deduction' => 300000, 'total_tax' => 0, 'total_net' => 6200000,
        'currency' => 'IDR', 'posting_reference' => 'PY-'.$marker, 'approved_by' => $user, 'approved_at' => $now,
        'posted_by' => $user, 'posted_at' => $now, 'remarks' => $marker, 'created_by' => $user,
    ));
    $payEmp = upsert_row($pdo, 'erp_payroll_process_employee', array('payroll_process_id' => $process['id'], 'employee_id' => $emp['id']), array(
        'employee_no' => $emp['employee_no'], 'full_name' => $emp['full_name'], 'department_code' => $emp['department_code'],
        'employee_group' => $emp['employee_group'], 'payroll_area' => 'MONTHLY', 'salary_structure_id' => $salary['id'],
        'salary_structure_code' => 'DEMO-SAL-001', 'cost_center_code' => 'CC-PROD', 'profit_center_code' => 'PC-MFG',
        'working_days' => 22, 'paid_days' => 22, 'gross_pay' => 6500000, 'total_earning' => 6500000,
        'total_deduction' => 300000, 'net_pay' => 6200000, 'process_status' => 'POSTED',
    ));
    upsert_row($pdo, 'erp_payroll_process_result', array('payroll_process_id' => $process['id'], 'payroll_employee_id' => $payEmp['id'], 'component_code' => 'DEMO-BASIC'), array(
        'employee_id' => $emp['id'], 'component_name' => 'Demo Basic Salary', 'wage_type_code' => '1000',
        'component_type' => 'EARNING', 'calculation_method' => 'FIXED_AMOUNT', 'base_amount' => 6500000,
        'quantity' => 1, 'amount' => 6500000, 'currency' => 'IDR', 'taxable' => 'Y', 'payslip_display' => 'Y',
        'sequence_no' => 10,
    ));
    $payslip = upsert_row($pdo, 'erp_payslip', array('payslip_no' => 'DEMO-PS-001'), array(
        'payroll_process_id' => $process['id'], 'payroll_employee_id' => $payEmp['id'], 'payroll_run_no' => 'DEMO-PAY-202606',
        'employee_id' => $emp['id'], 'employee_no' => $emp['employee_no'], 'full_name' => $emp['full_name'],
        'department_code' => $emp['department_code'], 'employee_group' => $emp['employee_group'], 'payroll_area' => 'MONTHLY',
        'period_year' => 2026, 'period_month' => 6, 'period_from' => '2026-06-01', 'period_to' => '2026-06-30',
        'pay_date' => '2026-06-30', 'salary_structure_code' => 'DEMO-SAL-001', 'working_days' => 22,
        'paid_days' => 22, 'gross_pay' => 6500000, 'total_earning' => 6500000, 'total_deduction' => 300000,
        'net_pay' => 6200000, 'currency' => 'IDR', 'payslip_status' => 'RELEASED', 'generated_by' => $user,
        'generated_at' => $now, 'released_by' => $user, 'released_at' => $now, 'remarks' => $marker, 'created_by' => $user,
    ));
    upsert_row($pdo, 'erp_payslip_detail', array('payslip_id' => $payslip['id'], 'line_no' => 10), array(
        'component_code' => 'DEMO-BASIC', 'component_name' => 'Demo Basic Salary', 'wage_type_code' => '1000',
        'component_type' => 'EARNING', 'quantity' => 1, 'rate' => 1, 'amount' => 6500000, 'currency' => 'IDR',
        'taxable' => 'Y', 'sequence_no' => 10,
    ));
}

function seed_quality(PDO $pdo, $marker, $date, $now, $user, $plant, $material)
{
    $layer = one($pdo, "SELECT * FROM stock_layer WHERE kode=? ORDER BY id DESC LIMIT 1", array($material['kd_barang']));
    $lot = upsert_row($pdo, 'erp_inspection_lot', array('lot_no' => 'DEMO-QM-LOT-001'), array(
        'inspection_origin' => 'GOODS_RECEIPT', 'inspection_type' => '01', 'source_ref_type' => 'DEMO',
        'source_ref_no' => $marker, 'stock_layer_id' => $layer ? $layer['id'] : null, 'material_code' => $material['kd_barang'],
        'material_name' => $material['nm_barang'], 'lot_qty' => 100, 'sample_qty' => 5, 'accepted_qty' => 95, 'rejected_qty' => 5,
        'uom' => $material['satuan'], 'plant_id' => $plant['id'], 'stock_type' => 'QUALITY',
        'batch_no' => 'DEMO-QM-BATCH', 'lot_status' => 'UD_PARTIAL', 'ud_code' => 'P', 'ud_text' => 'Partial Accepted',
        'ud_date' => $now, 'ud_by' => $user, 'no_aju' => 'AJU23'.$marker, 'jenis_dokpab' => 'BC 2.3',
        'no_dokpab' => 'BC23-DEMO', 'no_bpb' => 'BPB-'.$marker, 'notes' => $marker, 'created_by' => $user,
    ));
    $qn = upsert_row($pdo, 'erp_quality_notification', array('notification_no' => 'DEMO-QN-001'), array(
        'notification_type' => 'NCR', 'source_type' => 'INSPECTION_LOT', 'source_ref_id' => $lot['id'],
        'source_ref_no' => $lot['lot_no'], 'inspection_lot_id' => $lot['id'], 'material_code' => $material['kd_barang'],
        'material_name' => $material['nm_barang'], 'defect_qty' => 5, 'uom' => $material['satuan'], 'severity' => 'MEDIUM',
        'priority' => 'NORMAL', 'defect_category' => 'VISUAL', 'defect_code' => 'SCRATCH', 'defect_description' => $marker.' visual scratch',
        'containment_action' => 'Segregate suspect lot', 'root_cause' => 'Handling issue', 'corrective_action' => 'Improve handling',
        'preventive_action' => 'Operator refresh training', 'responsible_user' => $user, 'due_date' => '2026-06-30',
        'status' => 'CAPA_REQUIRED', 'plant_id' => $plant['id'], 'no_aju' => 'AJU23'.$marker, 'jenis_dokpab' => 'BC 2.3',
        'no_dokpab' => 'BC23-DEMO', 'no_bpb' => 'BPB-'.$marker, 'created_by' => $user,
    ));
    upsert_row($pdo, 'erp_capa', array('capa_no' => 'DEMO-CAPA-001'), array(
        'capa_type' => 'BOTH', 'source_type' => 'QUALITY_NOTIFICATION', 'notification_id' => $qn['id'],
        'notification_no' => $qn['notification_no'], 'material_code' => $material['kd_barang'], 'material_name' => $material['nm_barang'],
        'defect_category' => 'VISUAL', 'defect_code' => 'SCRATCH', 'problem_statement' => $marker.' surface scratch found',
        'root_cause' => 'Material handling', 'correction_action' => 'Sort affected lot', 'corrective_action' => 'Revise handling SOP',
        'preventive_action' => 'Training and audit', 'verification_plan' => 'Check next 3 lots', 'owner_user' => $user,
        'approver_user' => 'qa_manager', 'start_date' => $date, 'due_date' => '2026-06-30', 'priority' => 'NORMAL',
        'risk_level' => 'MEDIUM', 'status' => 'IN_PROGRESS', 'created_by' => $user,
    ));
    upsert_row($pdo, 'erp_usage_decision', array('ud_no' => 'DEMO-UD-001'), array(
        'inspection_lot_id' => $lot['id'], 'lot_no' => $lot['lot_no'], 'decision_code' => 'P',
        'decision_text' => 'Partial release demo lot', 'follow_up_action' => 'PARTIAL_RELEASE', 'movement_type' => '321',
        'stock_posted' => 'N', 'source_stock_layer_id' => $layer ? $layer['id'] : null, 'material_code' => $material['kd_barang'],
        'material_name' => $material['nm_barang'], 'lot_qty' => 100, 'accepted_qty' => 95, 'rejected_qty' => 5,
        'uom' => $material['satuan'], 'plant_id' => $plant['id'], 'source_stock_type' => 'QUALITY',
        'accepted_stock_type' => 'UNRESTRICTED', 'rejected_stock_type' => 'BLOCKED', 'no_aju' => 'AJU23'.$marker,
        'jenis_dokpab' => 'BC 2.3', 'no_dokpab' => 'BC23-DEMO', 'no_bpb' => 'BPB-'.$marker,
        'reason_code' => 'PARTIAL', 'defect_summary' => $marker, 'decision_by' => $user, 'decision_at' => $now,
    ));
}

function seed_inventory_and_finance(PDO $pdo, $marker, $date, $now, $user, $plant, $material, $cc, $pc)
{
    $sloc = one($pdo, "SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_code='RM01' LIMIT 1", array($plant['id']));
    $bin = one($pdo, "SELECT * FROM erp_storage_bin WHERE storage_location_id=? ORDER BY id LIMIT 1", array($sloc['id']));
    $price = 25000;
    $qty = 100;
    $amount = $qty * $price;
    $docNo = 'ADJ-'.$marker;
    $adj = upsert_row($pdo, 'erp_manual_stock_adjustment', array('adjustment_no' => $docNo), array(
        'document_date' => $date, 'posting_date' => $date, 'adjustment_type' => 'INCREASE', 'movement_type' => '701',
        'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'],
        'stock_type' => 'UNRESTRICTED', 'reason_code' => 'DEMO_OPENING', 'reason_text' => $marker.' opening demo stock',
        'status' => 'POSTED', 'total_qty' => $qty, 'total_amount' => $amount, 'created_by' => $user, 'created_at' => $now,
    ));
    $matDoc = one($pdo, "SELECT * FROM detail_transaksi WHERE no_ref=? AND kd_barang=? LIMIT 1", array($docNo, $material['kd_barang']));
    if (!$matDoc) {
        $matDocId = insert_row($pdo, 'detail_transaksi', array(
            'no_ref' => $docNo, 'no_aju' => 'AJU23'.$marker, 'no_dokpab' => 'BC23-DEMO', 'move_code' => '701',
            'posisi' => 'GUDANG', 'no_urut' => 10, 'qty' => $qty, 'price' => $price, 'kd_barang' => $material['kd_barang'],
            'lokasi' => 'GUDANG', 'document_date' => $now, 'posting_date' => $now, 'user' => $user, 'remark' => $marker.' manual stock adjustment',
            'direction' => 'IN', 'ref_type' => 'MAN_ADJ', 'ref_id' => $adj['id'], 'uom' => $material['satuan'], 'amount' => $amount,
            'reason' => $marker, 'created_by' => $user, 'no_bpb' => $docNo, 'plant_id' => $plant['id'],
            'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED',
            'destination_material_code' => $material['kd_barang'],
        ));
        $layerId = insert_row($pdo, 'stock_layer', array(
            'kode' => $material['kd_barang'], 'qty_masuk' => $qty, 'qty_sisa' => $qty, 'no_aju' => 'AJU23'.$marker,
            'no_dokpab' => 'BC23-DEMO', 'lokasi' => 'GUDANG', 'stock_type' => 'UNRESTRICTED',
            'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'],
            'jenis_dokpab' => 'BC 2.3', 'ref_table' => 'erp_manual_stock_adjustment', 'ref_id' => $matDocId,
            'tgl_masuk' => $date, 'no_bpb' => $docNo,
        ));
        insert_row($pdo, 'erp_manual_stock_adjustment_detail', array(
            'adjustment_id' => $adj['id'], 'line_no' => 10, 'material_code' => $material['kd_barang'],
            'material_name' => $material['nm_barang'], 'qty' => $qty, 'uom' => $material['satuan'], 'price' => $price,
            'amount' => $amount, 'system_qty_before' => 0, 'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'],
            'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED', 'material_doc_id' => $matDocId,
            'new_stock_layer_id' => $layerId, 'remarks' => $marker,
        ));
    }
    post_journal($pdo, $docNo, $date, 'MANUAL_STOCK_ADJUSTMENT', 'Manual stock adjustment '.$marker, array(
        array('no_rek' => '140', 'debet' => $amount, 'kredit' => 0),
        array('no_rek' => '71900', 'debet' => 0, 'kredit' => $amount),
    ), $user);

    $cycle = upsert_row($pdo, 'cycle_count_documents', array('doc_no' => 'CC-'.$marker), array('count_date' => $date, 'count_type' => 'CYCLE_COUNT', 'status' => 'COUNTED', 'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED', 'created_by' => $user, 'remarks' => $marker));
    upsert_row($pdo, 'cycle_count_document_items', array('document_id' => $cycle['id'], 'line_no' => 10), array('material_code' => $material['kd_barang'], 'material_name' => $material['nm_barang'], 'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED', 'cycle_class' => 'A', 'system_qty' => $qty, 'counted_qty' => $qty - 2, 'difference_qty' => -2, 'uom' => $material['satuan'], 'layer_count' => 1, 'customs_doc_count' => 1, 'status' => 'COUNTED', 'counted_by' => $user, 'counted_at' => $now, 'remarks' => $marker));
    $opname = upsert_row($pdo, 'stock_opname_documents', array('doc_no' => 'SO-'.$marker), array('opname_date' => $date, 'status' => 'COUNTED', 'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED', 'created_by' => $user, 'remarks' => $marker));
    $opItem = upsert_row($pdo, 'stock_opname_document_items', array('document_id' => $opname['id'], 'line_no' => 10), array('material_code' => $material['kd_barang'], 'material_name' => $material['nm_barang'], 'plant_id' => $plant['id'], 'storage_location_id' => $sloc['id'], 'storage_bin_id' => $bin['id'], 'stock_type' => 'UNRESTRICTED', 'system_qty' => $qty, 'counted_qty' => $qty + 3, 'difference_qty' => 3, 'uom' => $material['satuan'], 'layer_count' => 1, 'customs_doc_count' => 1, 'status' => 'COUNTED', 'counted_by' => $user, 'counted_at' => $now, 'remarks' => $marker));
    upsert_row($pdo, 'physical_inventory_postings', array('posting_no' => 'PIP-'.$marker), array('doc_type' => 'STOCK_OPNAME', 'document_id' => $opname['id'], 'item_id' => $opItem['id'], 'movement_type' => '701', 'difference_qty' => 3, 'posted_by' => $user, 'posted_at' => $now, 'remarks' => $marker));

    $vendorInvoice = upsert_row($pdo, 'erp_vendor_invoice', array('vendor_invoice_no' => 'VI-'.$marker), array('vendor_code' => 'DVEN0001', 'vendor_reference_no' => 'SUP-INV-'.$marker, 'invoice_type' => 'STANDARD', 'document_date' => $date, 'posting_date' => $date, 'due_date' => '2026-07-21', 'payment_term' => 'NET30', 'reference_po' => 'PO-DUMMY-E2E-001', 'reference_gr' => 'BPB-DUMMY-E2E-001', 'expense_account' => '140', 'ap_account' => '211', 'tax_account' => '21806', 'net_amount' => 1000000, 'tax_amount' => 110000, 'gross_amount' => 1110000, 'currency' => 'IDR', 'description' => $marker.' vendor invoice', 'status' => 'POSTED', 'payment_status' => 'OPEN', 'created_by' => $user, 'created_at' => $now, 'posted_by' => $user, 'posted_at' => $now));
    $j = post_journal($pdo, 'VI-'.$marker, $date, 'VENDOR_INVOICE', 'Vendor invoice '.$marker, array(array('no_rek' => '140', 'debet' => 1000000, 'kredit' => 0), array('no_rek' => '21806', 'debet' => 110000, 'kredit' => 0), array('no_rek' => '211', 'debet' => 0, 'kredit' => 1110000)), $user);
    $pdo->prepare('UPDATE erp_vendor_invoice SET journal_header_id=? WHERE id=?')->execute(array($j, $vendorInvoice['id']));
    $vp = upsert_row($pdo, 'erp_vendor_payment', array('vendor_payment_no' => 'VP-'.$marker), array('vendor_code' => 'DVEN0001', 'vendor_invoice_id' => $vendorInvoice['id'], 'vendor_invoice_no' => 'VI-'.$marker, 'document_date' => $date, 'posting_date' => $date, 'value_date' => $date, 'bank_account' => '11001', 'ap_account' => '211', 'amount' => 1110000, 'currency' => 'IDR', 'payment_method' => 'TRANSFER', 'bank_reference' => 'BNK-'.$marker, 'description' => $marker.' vendor payment', 'status' => 'POSTED', 'created_by' => $user, 'created_at' => $now, 'posted_by' => $user, 'posted_at' => $now));
    $j = post_journal($pdo, 'VP-'.$marker, $date, 'VENDOR_PAYMENT', 'Vendor payment '.$marker, array(array('no_rek' => '211', 'debet' => 1110000, 'kredit' => 0), array('no_rek' => '11001', 'debet' => 0, 'kredit' => 1110000)), $user);
    $pdo->prepare('UPDATE erp_vendor_payment SET journal_header_id=? WHERE id=?')->execute(array($j, $vp['id']));
    $br = upsert_row($pdo, 'erp_bank_receipt', array('bank_receipt_no' => 'BR-'.$marker), array('receipt_category' => 'CUSTOMER', 'document_date' => $date, 'posting_date' => $date, 'value_date' => $date, 'bank_account' => '11001', 'offset_account' => '12101', 'amount' => 500000, 'payer_name' => 'Demo Customer 1', 'bank_reference' => 'BRC-'.$marker, 'description' => $marker.' bank receipt', 'status' => 'POSTED', 'created_by' => $user, 'created_at' => $now, 'posted_by' => $user, 'posted_at' => $now));
    $j = post_journal($pdo, 'BR-'.$marker, $date, 'BANK_RECEIPT', 'Bank receipt '.$marker, array(array('no_rek' => '11001', 'debet' => 500000, 'kredit' => 0), array('no_rek' => '12101', 'debet' => 0, 'kredit' => 500000)), $user);
    $pdo->prepare('UPDATE erp_bank_receipt SET journal_header_id=? WHERE id=?')->execute(array($j, $br['id']));
    $bp = upsert_row($pdo, 'erp_bank_payment', array('bank_payment_no' => 'BP-'.$marker), array('payment_category' => 'EXPENSE', 'document_date' => $date, 'posting_date' => $date, 'value_date' => $date, 'bank_account' => '11001', 'offset_account' => '61000', 'amount' => 250000, 'payee_name' => 'Demo Expense Vendor', 'bank_reference' => 'BPY-'.$marker, 'description' => $marker.' bank payment', 'status' => 'POSTED', 'created_by' => $user, 'created_at' => $now, 'posted_by' => $user, 'posted_at' => $now));
    $j = post_journal($pdo, 'BP-'.$marker, $date, 'BANK_PAYMENT', 'Bank payment '.$marker, array(array('no_rek' => '61000', 'debet' => 250000, 'kredit' => 0), array('no_rek' => '11001', 'debet' => 0, 'kredit' => 250000)), $user);
    $pdo->prepare('UPDATE erp_bank_payment SET journal_header_id=? WHERE id=?')->execute(array($j, $bp['id']));
    $statement = upsert_row($pdo, 'erp_bank_statement_line', array('statement_no' => 'BSL-'.$marker), array('bank_account' => '11001', 'statement_date' => $date, 'value_date' => $date, 'bank_reference' => 'BRC-'.$marker, 'description' => $marker.' bank statement', 'credit_amount' => 500000, 'currency' => 'IDR', 'status' => 'MATCHED', 'matched_at' => $now, 'matched_by' => $user, 'created_by' => $user, 'created_at' => $now));
    upsert_row($pdo, 'erp_bank_reconciliation_match', array('match_no' => 'BRM-'.$marker), array('bank_statement_line_id' => $statement['id'], 'source_module' => 'BANK_RECEIPT', 'source_id' => $br['id'], 'source_document_no' => 'BR-'.$marker, 'bank_account' => '11001', 'match_date' => $date, 'statement_amount' => 500000, 'erp_amount' => 500000, 'difference_amount' => 0, 'status' => 'MATCHED', 'notes' => $marker, 'created_by' => $user, 'created_at' => $now));
    upsert_row($pdo, 'erp_tax_invoice', array('tax_invoice_no' => 'TAX-IN-'.$marker), array('tax_direction' => 'IN', 'tax_invoice_date' => $date, 'tax_period' => '2026-06', 'partner_code' => 'DVEN0001', 'partner_name' => 'Demo Vendor 1', 'source_module' => 'VENDOR_INVOICE', 'source_id' => $vendorInvoice['id'], 'source_document_no' => 'VI-'.$marker, 'dpp_amount' => 1000000, 'vat_amount' => 110000, 'status' => 'POSTED', 'validation_status' => 'VALID', 'description' => $marker, 'created_by' => $user, 'posted_by' => $user, 'posted_at' => $now));
    upsert_row($pdo, 'erp_tax_invoice', array('tax_invoice_no' => 'TAX-OUT-'.$marker), array('tax_direction' => 'OUT', 'tax_invoice_date' => $date, 'tax_period' => '2026-06', 'partner_code' => 'DCUS0001', 'partner_name' => 'Demo Customer 1', 'source_module' => 'CUSTOMER_INVOICE', 'source_document_no' => 'INV-S-DUMMY-E2E-001', 'dpp_amount' => 1200000, 'vat_amount' => 132000, 'status' => 'POSTED', 'validation_status' => 'VALID', 'description' => $marker, 'created_by' => $user, 'posted_by' => $user, 'posted_at' => $now));
}

seed_prerequisite_master($pdo, $DATE, $NOW, $USER);
seed_demo_materials($pdo, $DATE, $NOW, $USER);
$coreOutput = run_core_e2e_seed($root);
seed_planning_sales_quality_hr_finance($pdo, $MARKER, $DATE, $NOW, $USER);
$salesOrderStartedFlow = seed_sales_order_started_flow($pdo, $DATE, $NOW, $USER);

$checks = array(
    'negative_stock_layers' => one($pdo, "SELECT COUNT(*) c FROM stock_layer WHERE COALESCE(qty_sisa,0)<0")['c'],
    'unbalanced_journals' => one($pdo, "SELECT COUNT(*) c FROM (SELECT h.id,ROUND(SUM(COALESCE(d.debet,0)-COALESCE(d.kredit,0)),2) diff FROM jurnal_header h JOIN jurnal_detail d ON d.id_header=h.id WHERE h.posting_status='POSTED' GROUP BY h.id HAVING ABS(diff)>0.01) x")['c'],
    'demo_materials' => one($pdo, "SELECT COUNT(*) c FROM barang WHERE kd_barang LIKE 'DEMO-%'")['c'],
    'demo_employees' => one($pdo, "SELECT COUNT(*) c FROM erp_employee_master WHERE employee_no LIKE 'DEMO-EMP-%'")['c'],
    'sales_order_started_flow' => one($pdo, "SELECT COUNT(*) c FROM sales_order WHERE no_sales_order LIKE 'SO-DEMO-SO-FLOW-%'")['c'],
    'customs_trace_rows' => table_exists($pdo, 'erp_gr_production_trace') ? one($pdo, "SELECT COUNT(*) c FROM erp_gr_production_trace WHERE no_aju IS NOT NULL AND no_aju<>''")['c'] : 0,
);

echo json_encode(array(
    'status' => 'OK',
    'marker' => $MARKER,
    'core_seed' => json_decode($coreOutput, true) ?: $coreOutput,
    'sales_order_started_flow' => $salesOrderStartedFlow,
    'checks' => $checks,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
