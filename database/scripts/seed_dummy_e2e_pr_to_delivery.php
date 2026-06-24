<?php
/**
 * Seed one end-to-end dummy flow:
 * PR -> PO -> GR PO with BC 2.3 -> Production BSJ -> Production FG -> SO ->
 * Outbound Delivery -> Picking -> Packing List -> Surat Jalan -> GI Delivery -> Invoice.
 *
 * Marker: DUMMY-E2E-001
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

$marker = 'DUMMY-E2E-001';
$date = '2026-06-21';
$now = $date.' 09:00:00';
$user = 'admin';

function one(PDO $pdo, $sql, $params = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch();
}

function all_rows(PDO $pdo, $sql, $params = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function exec_sql(PDO $pdo, $sql, $params = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
}

function insert_row(PDO $pdo, $table, array $data) {
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`".implode('`,`', $cols)."`) VALUES (".implode(',', array_fill(0, count($cols), '?')).")";
    exec_sql($pdo, $sql, array_values($data));
    return (int)$pdo->lastInsertId();
}

function material(PDO $pdo, $code) {
    $row = one($pdo, "SELECT * FROM barang WHERE kd_barang=? LIMIT 1", array($code));
    if (!$row) {
        throw new RuntimeException("Material $code tidak ditemukan.");
    }
    return $row;
}

function post_journal(PDO $pdo, $no, $date, $source, $desc, array $lines, $user) {
    $debit = 0;
    $credit = 0;
    foreach ($lines as $line) {
        $debit += round((float)$line['debet'], 2);
        $credit += round((float)$line['kredit'], 2);
    }
    if (abs($debit - $credit) > 0.01) {
        throw new RuntimeException("Jurnal $no tidak balance. Debit=$debit Kredit=$credit");
    }
    $id = insert_row($pdo, 'jurnal_header', array(
        'no_jurnal' => 'JRN-'.$no,
        'document_type' => 'SA',
        'posting_status' => 'POSTED',
        'tgl_jurnal' => $date,
        'ket' => 'AUTO: '.$desc,
        'no_bukti' => $no,
        'source_module' => $source,
        'source_document_no' => $no,
        'username' => $user,
        'posted_by' => $user,
        'posted_at' => $date.' 09:00:00',
        'tgl_insert' => $date.' 09:00:00',
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

function cleanup(PDO $pdo, $marker) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $tables = array(
        'jurnal_detail' => "id_header IN (SELECT id FROM jurnal_header WHERE source_document_no LIKE '%$marker%' OR no_bukti LIKE '%$marker%' OR ket LIKE '%$marker%')",
        'jurnal_header' => "source_document_no LIKE '%$marker%' OR no_bukti LIKE '%$marker%' OR ket LIKE '%$marker%'",
        'erp_goods_issue_delivery_trace' => "gi_id IN (SELECT id FROM erp_goods_issue_delivery WHERE remarks LIKE '%$marker%' OR gi_no LIKE '%$marker%')",
        'erp_goods_issue_delivery_detail' => "gi_id IN (SELECT id FROM erp_goods_issue_delivery WHERE remarks LIKE '%$marker%' OR gi_no LIKE '%$marker%')",
        'erp_goods_issue_delivery_history' => "gi_id IN (SELECT id FROM erp_goods_issue_delivery WHERE remarks LIKE '%$marker%' OR gi_no LIKE '%$marker%')",
        'erp_goods_issue_delivery' => "remarks LIKE '%$marker%' OR gi_no LIKE '%$marker%'",
        'surat_jalan_detail' => "surat_jalan_id IN (SELECT id FROM surat_jalan WHERE keterangan LIKE '%$marker%' OR no_surat_jalan LIKE '%$marker%')",
        'surat_jalan' => "keterangan LIKE '%$marker%' OR no_surat_jalan LIKE '%$marker%'",
        'packing_list_detail' => "packing_list_id IN (SELECT id FROM packing_list WHERE remarks LIKE '%$marker%' OR no_packing_list LIKE '%$marker%')",
        'packing_list' => "remarks LIKE '%$marker%' OR no_packing_list LIKE '%$marker%'",
        'erp_picking_detail' => "picking_id IN (SELECT id FROM erp_picking WHERE remarks LIKE '%$marker%' OR picking_no LIKE '%$marker%')",
        'erp_picking' => "remarks LIKE '%$marker%' OR picking_no LIKE '%$marker%'",
        'erp_outbound_delivery_detail' => "delivery_id IN (SELECT id FROM erp_outbound_delivery WHERE remarks LIKE '%$marker%' OR delivery_no LIKE '%$marker%')",
        'erp_outbound_delivery' => "remarks LIKE '%$marker%' OR delivery_no LIKE '%$marker%'",
        'sales_invoice_detail' => "id_sales IN (SELECT id_sales FROM sales_invoice WHERE catatan LIKE '%$marker%' OR no_sales_invoice LIKE '%$marker%')",
        'sales_invoice' => "catatan LIKE '%$marker%' OR no_sales_invoice LIKE '%$marker%'",
        'sales_order_detail' => "id_sales_order IN (SELECT id_sales_order FROM sales_order WHERE catatan LIKE '%$marker%' OR no_sales_order LIKE '%$marker%')",
        'sales_order' => "catatan LIKE '%$marker%' OR no_sales_order LIKE '%$marker%'",
        'erp_gr_production_trace' => "gr_id IN (SELECT id FROM erp_gr_production WHERE remarks LIKE '%$marker%' OR gr_no LIKE '%$marker%')",
        'erp_gr_production_detail' => "gr_id IN (SELECT id FROM erp_gr_production WHERE remarks LIKE '%$marker%' OR gr_no LIKE '%$marker%')",
        'erp_gr_production' => "remarks LIKE '%$marker%' OR gr_no LIKE '%$marker%'",
        'production_order_confirmation' => "remarks LIKE '%$marker%' OR confirmation_no LIKE '%$marker%'",
        'erp_issue_production_trace' => "issue_id IN (SELECT id FROM erp_issue_production WHERE reason_text LIKE '%$marker%' OR issue_no LIKE '%$marker%')",
        'erp_issue_production_detail' => "issue_id IN (SELECT id FROM erp_issue_production WHERE reason_text LIKE '%$marker%' OR issue_no LIKE '%$marker%')",
        'erp_issue_production_history' => "issue_id IN (SELECT id FROM erp_issue_production WHERE reason_text LIKE '%$marker%' OR issue_no LIKE '%$marker%')",
        'erp_issue_production' => "reason_text LIKE '%$marker%' OR issue_no LIKE '%$marker%'",
        'production_order_goods_movement' => "id_production_order IN (SELECT id_production_order FROM production_order WHERE remarks LIKE '%$marker%' OR no_production_order LIKE '%$marker%')",
        'production_order_operation' => "id_production_order IN (SELECT id_production_order FROM production_order WHERE remarks LIKE '%$marker%' OR no_production_order LIKE '%$marker%')",
        'production_order_material' => "id_production_order IN (SELECT id_production_order FROM production_order WHERE remarks LIKE '%$marker%' OR no_production_order LIKE '%$marker%')",
        'production_order_release_log' => "id_production_order IN (SELECT id_production_order FROM production_order WHERE remarks LIKE '%$marker%' OR no_production_order LIKE '%$marker%')",
        'production_order' => "remarks LIKE '%$marker%' OR no_production_order LIKE '%$marker%'",
        'detail_transaksi' => "remark LIKE '%$marker%' OR reason LIKE '%$marker%' OR no_ref LIKE '%$marker%'",
        'stock_layer' => "no_bpb LIKE '%$marker%'",
        'pemasukan_detail' => "no_bpb LIKE '%$marker%'",
        'pemasukan' => "catatan LIKE '%$marker%' OR no_bpb LIKE '%$marker%'",
        'purchase_order_detail' => "po_no LIKE '%$marker%'",
        'purchase_order' => "catatan LIKE '%$marker%' OR purchase_order_no LIKE '%$marker%'",
        'purchase_requisition_history' => "id_pr IN (SELECT id_pr FROM purchase_requisition WHERE note LIKE '%$marker%' OR no_pr LIKE '%$marker%')",
        'purchase_requisition_approval' => "id_pr IN (SELECT id_pr FROM purchase_requisition WHERE note LIKE '%$marker%' OR no_pr LIKE '%$marker%')",
        'purchase_requisition_detail' => "id_pr IN (SELECT id_pr FROM purchase_requisition WHERE note LIKE '%$marker%' OR no_pr LIKE '%$marker%')",
        'purchase_requisition' => "note LIKE '%$marker%' OR no_pr LIKE '%$marker%'",
    );

    foreach ($tables as $table => $where) {
        $pdo->exec("DELETE FROM `$table` WHERE $where");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function issue_to_production(PDO $pdo, array $po, array $components, $issueNo, $date, $plant, $sloc, $bin, $user, $marker) {
    $issueId = insert_row($pdo, 'erp_issue_production', array(
        'issue_no' => $issueNo,
        'production_id' => $po['id_production_order'],
        'production_no' => $po['no_production_order'],
        'document_date' => $date,
        'posting_date' => $date,
        'movement_type' => '261',
        'reference_no' => $po['no_production_order'],
        'reason_code' => 'PROD',
        'reason_text' => $marker.' issue material to production',
        'plant_id' => $plant['id'],
        'storage_location_id' => $sloc['id'],
        'storage_bin_id' => $bin['id'],
        'status' => 'POSTED',
        'created_by' => $user,
        'created_at' => $date.' 10:00:00',
    ));

    $totalAmount = 0;
    $inventoryCredits = array();
    $line = 10;
    $journalItems = array();
    foreach ($components as $component) {
        $mat = material($pdo, $component['code']);
        $qty = (float)$component['qty'];
        $layers = all_rows($pdo, "SELECT * FROM stock_layer WHERE kode=? AND qty_sisa>0 AND stock_type='UNRESTRICTED' ORDER BY tgl_masuk,id", array($component['code']));
        $remaining = $qty;
        $detailAmount = 0;
        $weighted = 0;
        $detailId = insert_row($pdo, 'erp_issue_production_detail', array(
            'issue_id' => $issueId,
            'production_detail_id' => isset($component['production_detail_id']) ? $component['production_detail_id'] : null,
            'line_no' => $line,
            'material_code' => $component['code'],
            'material_name' => $mat['nm_barang'],
            'planned_qty' => $qty,
            'issued_qty' => $qty,
            'uom' => $mat['satuan'],
            'price' => 0,
            'amount' => 0,
            'stock_type' => 'UNRESTRICTED',
            'remarks' => $marker,
        ));
        foreach ($layers as $layer) {
            if ($remaining <= 0.00001) {
                break;
            }
            $take = min($remaining, (float)$layer['qty_sisa']);
            if ($take <= 0) {
                continue;
            }
            $priceRow = one($pdo, "SELECT price FROM detail_transaksi WHERE id_detail=? LIMIT 1", array($layer['ref_id']));
            $price = $priceRow ? (float)$priceRow['price'] : 0;
            if ($price <= 0) {
                $price = isset($component['price']) ? (float)$component['price'] : 1;
            }
            $amount = round($take * $price, 2);
            exec_sql($pdo, "UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=?", array($take, $layer['id']));
            $matDocId = insert_row($pdo, 'detail_transaksi', array(
                'no_ref' => $issueNo,
                'ref_pengganti' => $po['no_production_order'],
                'no_aju' => $layer['no_aju'],
                'no_dokpab' => $layer['no_dokpab'],
                'move_code' => '261',
                'posisi' => 'PRODUKSI',
                'no_urut' => $line,
                'qty' => -1 * $take,
                'kd_barang' => $component['code'],
                'lokasi' => 'PRODUKSI',
                'document_date' => $date.' 10:00:00',
                'posting_date' => $date.' 10:00:00',
                'user' => $user,
                'is_produksi' => 'Y',
                'remark' => $marker.' Issue to Production '.$po['no_production_order'],
                'direction' => 'OUT',
                'ref_type' => 'ISSUE_PROD',
                'ref_id' => $issueId,
                'ref_detail_id' => $detailId,
                'uom' => $mat['satuan'],
                'price' => $price,
                'amount' => $amount,
                'reason' => $marker,
                'created_by' => $user,
                'no_bpb' => $layer['no_bpb'],
                'plant_id' => $layer['plant_id'],
                'storage_location_id' => $layer['storage_location_id'],
                'storage_bin_id' => $layer['storage_bin_id'],
                'stock_type' => $layer['stock_type'],
                'destination_material_code' => $component['code'],
            ));
            insert_row($pdo, 'erp_issue_production_trace', array(
                'issue_id' => $issueId,
                'issue_detail_id' => $detailId,
                'stock_layer_id' => $layer['id'],
                'material_doc_id' => $matDocId,
                'qty' => $take,
                'price' => $price,
                'amount' => $amount,
                'stock_type' => $layer['stock_type'],
                'plant_id' => $layer['plant_id'],
                'storage_location_id' => $layer['storage_location_id'],
                'storage_bin_id' => $layer['storage_bin_id'],
                'no_bpb' => $layer['no_bpb'],
                'no_aju' => $layer['no_aju'],
                'no_dokpab' => $layer['no_dokpab'],
                'jenis_dokpab' => $layer['jenis_dokpab'],
                'hs_code' => isset($component['hs_code']) ? $component['hs_code'] : '5903.20.00',
                'lot_no' => isset($component['lot_no']) ? $component['lot_no'] : $layer['no_bpb'].'-LOT',
                'source_ref_table' => $layer['ref_table'],
                'source_ref_id' => $layer['ref_id'],
            ));
            $detailAmount += $amount;
            $weighted += $take * $price;
            $remaining -= $take;
        }
        if ($remaining > 0.00001) {
            throw new RuntimeException('Stock tidak cukup untuk issue material '.$component['code']);
        }
        $detailPrice = $qty > 0 ? $weighted / $qty : 0;
        exec_sql($pdo, "UPDATE erp_issue_production_detail SET price=?, amount=? WHERE id=?", array($detailPrice, $detailAmount, $detailId));
        exec_sql($pdo, "UPDATE production_order_material SET issued_qty=issued_qty+?, remaining_qty=GREATEST(remaining_qty-?,0), issue_status='FULL_ISSUE' WHERE id_material=?", array($qty, $qty, $component['production_detail_id']));
        $totalAmount += $detailAmount;
        $journalItems[] = array('code' => $component['code'], 'category' => $mat['kd_kategori'], 'amount' => $detailAmount);
        $creditAccount = in_array($mat['kd_kategori'], array('K02', 'K07'), true) ? '14300' : '140';
        if (!isset($inventoryCredits[$creditAccount])) {
            $inventoryCredits[$creditAccount] = 0;
        }
        $inventoryCredits[$creditAccount] += $detailAmount;
        $line += 10;
    }
    exec_sql($pdo, "UPDATE erp_issue_production SET total_amount=? WHERE id=?", array($totalAmount, $issueId));
    $journalLines = array(array('no_rek' => '14302', 'debet' => $totalAmount, 'kredit' => 0));
    foreach ($inventoryCredits as $account => $amount) {
        $journalLines[] = array('no_rek' => $account, 'debet' => 0, 'kredit' => $amount);
    }
    post_journal($pdo, $issueNo, $date, 'ISSUE_TO_PRODUCTION', 'Issue to Production '.$po['no_production_order'].' '.$marker, $journalLines, $user);
    insert_row($pdo, 'erp_issue_production_history', array('issue_id' => $issueId, 'status_baru' => 'POSTED', 'remarks' => $marker, 'changed_by' => $user));
    return array('id' => $issueId, 'amount' => $totalAmount);
}

function confirm_and_gr(PDO $pdo, array $po, $confirmationNo, $grNo, $date, $yieldQty, $plant, $sloc, $bin, $user, $marker) {
    $confirmedBefore = (float)$po['completed_qty'];
    $confId = insert_row($pdo, 'production_order_confirmation', array(
        'confirmation_no' => $confirmationNo,
        'id_production_order' => $po['id_production_order'],
        'confirmation_date' => $date.' 13:00:00',
        'document_date' => $date,
        'posting_date' => $date,
        'yield_qty' => $yieldQty,
        'scrap_qty' => 0,
        'operation_no' => '0010',
        'work_center' => 'WC-LAM',
        'operation_name' => 'Lamination',
        'operator_name' => 'Operator Dummy',
        'shift_code' => 'SHIFT-1',
        'start_time' => $date.' 08:00:00',
        'end_time' => $date.' 13:00:00',
        'labor_time' => 5,
        'machine_time' => 5,
        'final_confirmation' => 'Y',
        'status' => 'POSTED',
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $date.' 13:00:00',
    ));
    exec_sql($pdo, "UPDATE production_order SET completed_qty=completed_qty+?, status='CONFIRMED', actual_finish=? WHERE id_production_order=?", array($yieldQty, $date.' 13:00:00', $po['id_production_order']));

    $factor = ((float)$po['order_qty'] > 0) ? ($yieldQty / (float)$po['order_qty']) : 1;
    $wip = one($pdo, "SELECT COALESCE(SUM(t.amount),0) amount FROM erp_issue_production h JOIN erp_issue_production_trace t ON t.issue_id=h.id WHERE h.production_id=? AND h.status='POSTED'", array($po['id_production_order']));
    $amount = round(((float)$wip['amount']) * $factor, 2);
    $price = $yieldQty > 0 ? $amount / $yieldQty : 0;

    $grId = insert_row($pdo, 'erp_gr_production', array(
        'gr_no' => $grNo,
        'id_confirmation' => $confId,
        'id_production_order' => $po['id_production_order'],
        'no_production_order' => $po['no_production_order'],
        'confirmation_no' => $confirmationNo,
        'document_date' => $date,
        'posting_date' => $date,
        'movement_type' => '101',
        'plant_id' => $plant['id'],
        'storage_location_id' => $sloc['id'],
        'storage_bin_id' => $bin['id'],
        'stock_type' => 'UNRESTRICTED',
        'status' => 'POSTED',
        'total_amount' => $amount,
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $date.' 13:10:00',
    ));
    $matDocId = insert_row($pdo, 'detail_transaksi', array(
        'no_ref' => $grNo,
        'ref_pengganti' => $po['no_production_order'],
        'move_code' => '101',
        'posisi' => 'GUDANG',
        'no_urut' => 10,
        'qty' => $yieldQty,
        'kd_barang' => $po['material_code'],
        'lokasi' => 'GUDANG',
        'document_date' => $date.' 13:10:00',
        'posting_date' => $date.' 13:10:00',
        'user' => $user,
        'is_produksi' => 'Y',
        'remark' => $marker.' GR from Production '.$po['no_production_order'],
        'direction' => 'IN',
        'ref_type' => 'GR_PROD',
        'ref_id' => $grId,
        'uom' => $po['uom'],
        'price' => $price,
        'amount' => $amount,
        'reason' => $marker,
        'created_by' => $user,
        'no_bpb' => $grNo.'-'.$marker,
        'plant_id' => $plant['id'],
        'storage_location_id' => $sloc['id'],
        'storage_bin_id' => $bin['id'],
        'stock_type' => 'UNRESTRICTED',
        'destination_material_code' => $po['material_code'],
    ));
    $layerId = insert_row($pdo, 'stock_layer', array(
        'kode' => $po['material_code'],
        'qty_masuk' => $yieldQty,
        'qty_sisa' => $yieldQty,
        'lokasi' => 'GUDANG',
        'stock_type' => 'UNRESTRICTED',
        'plant_id' => $plant['id'],
        'storage_location_id' => $sloc['id'],
        'storage_bin_id' => $bin['id'],
        'jenis_dokpab' => null,
        'ref_table' => 'erp_gr_production',
        'ref_id' => $matDocId,
        'tgl_masuk' => $date,
        'no_bpb' => $grNo.'-'.$marker,
    ));
    $detailId = insert_row($pdo, 'erp_gr_production_detail', array(
        'gr_id' => $grId,
        'stock_layer_id' => $layerId,
        'material_doc_id' => $matDocId,
        'material_code' => $po['material_code'],
        'material_name' => $po['material_name'],
        'qty' => $yieldQty,
        'uom' => $po['uom'],
        'price' => $price,
        'amount' => $amount,
        'stock_type' => 'UNRESTRICTED',
        'remarks' => $marker,
    ));

    $issueTraces = all_rows($pdo, "SELECT t.*,d.material_code,d.material_name,d.uom,sl.qty_masuk source_layer_qty FROM erp_issue_production h JOIN erp_issue_production_detail d ON d.issue_id=h.id JOIN erp_issue_production_trace t ON t.issue_detail_id=d.id LEFT JOIN stock_layer sl ON sl.id=t.stock_layer_id WHERE h.production_id=? AND h.status='POSTED' ORDER BY t.id", array($po['id_production_order']));
    foreach ($issueTraces as $trace) {
        $inherited = all_rows($pdo, "SELECT * FROM erp_gr_production_trace WHERE output_stock_layer_id=? ORDER BY id", array($trace['stock_layer_id']));
        if ($inherited) {
            $ratio = ((float)$trace['source_layer_qty'] > 0) ? ((float)$trace['qty'] / (float)$trace['source_layer_qty']) : 1;
            foreach ($inherited as $raw) {
                insert_row($pdo, 'erp_gr_production_trace', array(
                    'gr_id' => $grId,
                    'gr_detail_id' => $detailId,
                    'output_stock_layer_id' => $layerId,
                    'source_issue_id' => $trace['issue_id'],
                    'source_issue_detail_id' => $trace['issue_detail_id'],
                    'source_issue_trace_id' => $trace['id'],
                    'source_stock_layer_id' => $trace['stock_layer_id'],
                    'source_material_code' => $trace['material_code'],
                    'source_material_name' => $trace['material_name'],
                    'raw_material_code' => $raw['raw_material_code'],
                    'raw_material_name' => $raw['raw_material_name'],
                    'qty' => (float)$raw['qty'] * $ratio,
                    'uom' => $raw['uom'],
                    'lot_no' => $raw['lot_no'],
                    'no_bpb' => $raw['no_bpb'],
                    'no_aju' => $raw['no_aju'],
                    'jenis_dokpab' => $raw['jenis_dokpab'],
                    'no_dokpab' => $raw['no_dokpab'],
                    'hs_code' => $raw['hs_code'],
                    'trace_source' => 'INHERITED',
                ));
            }
        } else {
            insert_row($pdo, 'erp_gr_production_trace', array(
                'gr_id' => $grId,
                'gr_detail_id' => $detailId,
                'output_stock_layer_id' => $layerId,
                'source_issue_id' => $trace['issue_id'],
                'source_issue_detail_id' => $trace['issue_detail_id'],
                'source_issue_trace_id' => $trace['id'],
                'source_stock_layer_id' => $trace['stock_layer_id'],
                'source_material_code' => $trace['material_code'],
                'source_material_name' => $trace['material_name'],
                'raw_material_code' => $trace['material_code'],
                'raw_material_name' => $trace['material_name'],
                'qty' => $trace['qty'],
                'uom' => $trace['uom'],
                'lot_no' => $trace['lot_no'],
                'no_bpb' => $trace['no_bpb'],
                'no_aju' => $trace['no_aju'],
                'jenis_dokpab' => $trace['jenis_dokpab'],
                'no_dokpab' => $trace['no_dokpab'],
                'hs_code' => $trace['hs_code'],
                'trace_source' => 'DIRECT',
            ));
        }
    }
    post_journal($pdo, $grNo, $date, 'GR_FROM_PRODUCTION', 'GR from Production '.$po['no_production_order'].' '.$marker, array(
        array('no_rek' => '14300', 'debet' => $amount, 'kredit' => 0),
        array('no_rek' => '14302', 'debet' => 0, 'kredit' => $amount),
    ), $user);
    return array('confirmation_id' => $confId, 'gr_id' => $grId, 'gr_detail_id' => $detailId, 'stock_layer_id' => $layerId, 'amount' => $amount, 'price' => $price);
}

cleanup($pdo, $marker);

$pdo->beginTransaction();
try {
    $plant = one($pdo, "SELECT * FROM erp_plant WHERE plant_code='PL01' LIMIT 1");
    $rmSloc = one($pdo, "SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_code='RM01' LIMIT 1", array($plant['id']));
    $wipSloc = one($pdo, "SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_code='WIP1' LIMIT 1", array($plant['id']));
    $fgSloc = one($pdo, "SELECT * FROM erp_storage_location WHERE plant_id=? AND storage_code='FG01' LIMIT 1", array($plant['id']));
    $rmBin = one($pdo, "SELECT * FROM erp_storage_bin WHERE storage_location_id=? ORDER BY bin_code='DEFAULT' DESC,id LIMIT 1", array($rmSloc['id']));
    $wipBin = one($pdo, "SELECT * FROM erp_storage_bin WHERE storage_location_id=? ORDER BY bin_code='DEFAULT' DESC,id LIMIT 1", array($wipSloc['id']));
    $fgBin = one($pdo, "SELECT * FROM erp_storage_bin WHERE storage_location_id=? ORDER BY bin_code='DEFAULT' DESC,id LIMIT 1", array($fgSloc['id']));
    if (!$plant || !$rmSloc || !$wipSloc || !$fgSloc || !$rmBin || !$wipBin || !$fgBin) {
        throw new RuntimeException('Master plant/storage/bin belum lengkap.');
    }

    if (!one($pdo, "SELECT * FROM customer WHERE kode_pemasok='CUSTD001' LIMIT 1")) {
        insert_row($pdo, 'customer', array(
            'kode_pemasok' => 'CUSTD001',
            'npwp' => '00.000.000.0-000.000',
            'nama' => 'PT Dummy Customer Kawasan',
            'alamat' => 'Kawasan Industri Dummy Blok A1',
            'kota' => 'Tangerang',
            'negara' => 'Indonesia',
            'notelp' => '021-000000',
            'email' => 'customer.dummy@example.com',
            'status' => 'Aktif',
            'created_by' => $user,
        ));
    }
    $customer = one($pdo, "SELECT * FROM customer WHERE kode_pemasok='CUSTD001' LIMIT 1");
    $vendor = one($pdo, "SELECT * FROM pemasok WHERE kode_pemasok='S0000001' LIMIT 1");

    $bsjBom = one($pdo, "SELECT * FROM bom WHERE id=1097 LIMIT 1");
    $fgBom = one($pdo, "SELECT * FROM bom WHERE id=1113 LIMIT 1");
    $bsj = material($pdo, 'BSJ00001');
    $fg = material($pdo, 'BJ00001');

    $bsjQty = 10.0;
    $fgQty = 10.0;
    $rawComponents = array();
    $prices = array(
        'BP00015' => 50000, 'BP00001' => 30000, 'BP00002' => 32000, 'BP00007' => 45000,
        'BP00006' => 47000, 'BP00016' => 55000, 'BP00017' => 8000, 'BB00007' => 65000,
    );
    $bsjDetails = all_rows($pdo, "SELECT * FROM bom_detail WHERE id_bom=? ORDER BY line_no,id", array($bsjBom['id']));
    foreach ($bsjDetails as $d) {
        $rawComponents[] = array(
            'code' => $d['kodebb'],
            'qty' => round((float)$d['component_qty'] * $bsjQty, 5),
            'price' => isset($prices[$d['kodebb']]) ? $prices[$d['kodebb']] : 10000,
        );
    }
    $rawComponents[] = array('code' => 'BB00007', 'qty' => 10.0, 'price' => $prices['BB00007']);

    $prId = insert_row($pdo, 'purchase_requisition', array(
        'no_pr' => 'PR-'.$marker,
        'tgl_pr' => $date,
        'document_type' => 'NB',
        'plant' => $plant['plant_code'],
        'storage_location' => $rmSloc['storage_code'],
        'department' => 'PPIC',
        'requestor' => 'PPIC Dummy',
        'priority' => 'NORMAL',
        'status' => 'CONVERTED_PO',
        'required_date' => $date,
        'note' => $marker.' raw material for production FG with semi finish BOM',
        'created_by' => $user,
        'created_at' => $now,
    ));
    insert_row($pdo, 'purchase_requisition_approval', array(
        'id_pr' => $prId,
        'approval_level' => 1,
        'approver' => 'manager_ppic',
        'status' => 'APPROVED',
        'approval_date' => $now,
        'note' => $marker,
    ));
    insert_row($pdo, 'purchase_requisition_history', array(
        'id_pr' => $prId,
        'status_lama' => 'SUBMITTED',
        'status_baru' => 'APPROVED',
        'remarks' => $marker,
        'changed_by' => $user,
        'changed_at' => $now,
    ));

    $poId = insert_row($pdo, 'purchase_order', array(
        'purchase_order_no' => 'PO-'.$marker,
        'po_type' => 'NB',
        'source_type' => 'PR',
        'source_ref' => 'PR-'.$marker,
        'purchasing_org' => 'PO01',
        'purchasing_group' => 'PG01',
        'plant' => $plant['plant_code'],
        'storage_location' => $rmSloc['storage_code'],
        'po_date' => $date,
        'delivery_date' => $date,
        'arrival_date' => $date,
        'payment_term' => 'NET30',
        'seller_code' => $vendor['kode_pemasok'],
        'currency' => 'IDR',
        'seller_name' => $vendor['nama'],
        'seller_address' => $vendor['alamat'],
        'catatan' => $marker,
        'created_by' => $user,
        'status' => 'Approved',
        'approval_status' => 'Approved',
    ));

    $line = 10;
    $poDetails = array();
    foreach ($rawComponents as $idx => $component) {
        $mat = material($pdo, $component['code']);
        $prDetailId = insert_row($pdo, 'purchase_requisition_detail', array(
            'id_pr' => $prId,
            'line_no' => $line,
            'material_code' => $component['code'],
            'material_name' => $mat['nm_barang'],
            'material_group' => $mat['material_group_id'],
            'kd_kategori' => $mat['kd_kategori'],
            'qty' => $component['qty'],
            'qty_po' => $component['qty'],
            'qty_open' => 0,
            'uom' => $mat['satuan'],
            'required_date' => $date,
            'plant' => $plant['plant_code'],
            'storage_location' => $rmSloc['storage_code'],
            'valuation_price' => $component['price'],
            'currency' => 'IDR',
            'suggested_vendor' => $vendor['kode_pemasok'],
            'tracking_no' => $marker,
            'item_status' => 'CONVERTED_PO',
            'remarks' => $marker,
        ));
        $amount = round($component['qty'] * $component['price'], 2);
        $poDetailId = insert_row($pdo, 'purchase_order_detail', array(
            'id_po' => $poId,
            'id_pr' => $prId,
            'id_pr_detail' => $prDetailId,
            'po_no' => 'PO-'.$marker,
            'kode_barang' => $component['code'],
            'nama_barang' => $mat['nm_barang'],
            'unit' => $mat['satuan'],
            'qty' => $component['qty'],
            'received_qty' => $component['qty'],
            'harga' => $component['price'],
            'amount' => $amount,
            'ket' => $marker,
        ));
        $poDetails[] = $component + array('material' => $mat, 'po_detail_id' => $poDetailId, 'line' => $line, 'amount' => $amount);
        $line += 10;
    }

    $bpb = 'BPB-'.$marker;
    $pemasukanId = insert_row($pdo, 'pemasukan', array(
        'no_bpb' => $bpb,
        'tgl_bpb' => $date,
        'document_date' => $date,
        'posting_date' => $date,
        'pemasok' => $vendor['kode_pemasok'],
        'no_invoice' => 'INV-V-'.$marker,
        'tgl_invoice' => $date,
        'catatan' => $marker,
        'no_aju' => 'AJU23'.$marker,
        'tgl_aju' => $date,
        'jenis_dokpab' => 'BC 2.3',
        'no_dokpab' => 'BC23D001',
        'tgl_dokpab' => $date,
        'kantor_pabean' => '050100',
        'negara_asal' => 'KR',
        'customs_status' => 'REGISTERED',
        'userid' => $user,
        'nopo' => 'PO-'.$marker,
        'plant_id' => $plant['id'],
        'storage_location_id' => $rmSloc['id'],
        'stock_type' => 'UNRESTRICTED',
        'valuta' => 'IDR',
        'kurs' => '1',
        'date_created' => $now,
        'status' => 'POSTED',
    ));

    $grPurchaseTotal = 0;
    $line = 10;
    foreach ($poDetails as $item) {
        $detailId = insert_row($pdo, 'pemasukan_detail', array(
            'id_po_detail' => $item['po_detail_id'],
            'no_bpb' => $bpb,
            'tgl_bpb' => $date,
            'kode' => $item['code'],
            'jumlah' => $item['qty'],
            'harga' => $item['price'],
            'valuta' => 'IDR',
            'nilai' => $item['amount'],
            'unit' => $item['material']['satuan'],
            'no_urut' => $line,
            'customs_item_no' => $line / 10,
            'hs_code' => '5903.20.00',
            'customs_qty' => $item['qty'],
            'customs_uom' => $item['material']['satuan'],
            'customs_value' => $item['amount'],
            'net_weight' => $item['qty'],
            'gross_weight' => $item['qty'] * 1.02,
            'package_type' => 'PK',
            'package_qty' => 1,
            'origin_country' => 'KR',
            'no_aju' => 'AJU23'.$marker,
            'lot_no' => $item['code'].'-LOT-'.$marker,
            'tgl_aju' => $date,
            'tgl_masuk' => $date,
            'jenis_dokpab' => 'BC 2.3',
            'no_dokpab' => 'BC23D001',
            'lokasi' => 'GUDANG',
            'storage_bin_id' => $rmBin['id'],
            'userid' => $user,
            'status' => '1',
        ));
        $matDocId = insert_row($pdo, 'detail_transaksi', array(
            'no_ref' => $bpb,
            'id_pemasukan' => $pemasukanId,
            'no_aju' => 'AJU23'.$marker,
            'no_dokpab' => 'BC23D001',
            'id_incoming_detail' => $detailId,
            'move_code' => '101',
            'posisi' => 'GUDANG',
            'no_urut' => $line,
            'qty' => $item['qty'],
            'kd_barang' => $item['code'],
            'lokasi' => 'GUDANG',
            'document_date' => $date.' 09:30:00',
            'posting_date' => $date.' 09:30:00',
            'user' => $user,
            'remark' => $marker.' GR for Purchase Order',
            'direction' => 'IN',
            'ref_type' => 'GR_PO',
            'ref_id' => $pemasukanId,
            'ref_detail_id' => $detailId,
            'id_po' => $poId,
            'id_po_detail' => $item['po_detail_id'],
            'uom' => $item['material']['satuan'],
            'price' => $item['price'],
            'amount' => $item['amount'],
            'reason' => $marker,
            'created_by' => $user,
            'no_bpb' => $bpb,
            'plant_id' => $plant['id'],
            'storage_location_id' => $rmSloc['id'],
            'storage_bin_id' => $rmBin['id'],
            'stock_type' => 'UNRESTRICTED',
            'destination_material_code' => $item['code'],
        ));
        insert_row($pdo, 'stock_layer', array(
            'kode' => $item['code'],
            'qty_masuk' => $item['qty'],
            'qty_sisa' => $item['qty'],
            'no_aju' => 'AJU23'.$marker,
            'no_dokpab' => 'BC23D001',
            'lokasi' => 'GUDANG',
            'stock_type' => 'UNRESTRICTED',
            'plant_id' => $plant['id'],
            'storage_location_id' => $rmSloc['id'],
            'storage_bin_id' => $rmBin['id'],
            'jenis_dokpab' => 'BC 2.3',
            'ref_table' => 'pemasukan_detail',
            'ref_id' => $matDocId,
            'tgl_masuk' => $date,
            'no_bpb' => $bpb,
        ));
        $grPurchaseTotal += $item['amount'];
        $line += 10;
    }
    post_journal($pdo, $bpb, $date, 'GR_FOR_PO', 'GR for PO '.$marker, array(
        array('no_rek' => '140', 'debet' => $grPurchaseTotal, 'kredit' => 0),
        array('no_rek' => '211', 'debet' => 0, 'kredit' => $grPurchaseTotal),
    ), $user);

    $poBsjId = insert_row($pdo, 'production_order', array(
        'no_production_order' => 'POPR-BSJ-'.$marker,
        'order_strategy' => 'MTS',
        'bom_id' => $bsjBom['id'],
        'bom_no' => $bsjBom['bom_no'],
        'plant' => $plant['plant_code'],
        'storage_location' => $wipSloc['storage_code'],
        'material_code' => 'BSJ00001',
        'material_name' => $bsj['nm_barang'],
        'order_qty' => $bsjQty,
        'uom' => $bsj['satuan'],
        'start_date' => $date,
        'finish_date' => $date,
        'actual_start' => $date.' 10:00:00',
        'status' => 'IN_PROCESS',
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $now,
    ));
    $bsjPo = one($pdo, "SELECT * FROM production_order WHERE id_production_order=?", array($poBsjId));
    $bsjIssueComponents = array();
    foreach ($bsjDetails as $d) {
        $mat = material($pdo, $d['kodebb']);
        $req = round((float)$d['component_qty'] * $bsjQty, 5);
        $matId = insert_row($pdo, 'production_order_material', array(
            'id_production_order' => $poBsjId,
            'material_code' => $d['kodebb'],
            'material_name' => $mat['nm_barang'],
            'kd_kategori' => $mat['kd_kategori'],
            'required_qty' => $req,
            'issued_qty' => 0,
            'remaining_qty' => $req,
            'uom' => $mat['satuan'],
            'storage_location' => $rmSloc['storage_code'],
            'issue_status' => 'OPEN',
            'remarks' => $marker,
        ));
        $bsjIssueComponents[] = array('code' => $d['kodebb'], 'qty' => $req, 'production_detail_id' => $matId, 'price' => isset($prices[$d['kodebb']]) ? $prices[$d['kodebb']] : 10000);
    }
    insert_row($pdo, 'production_order_operation', array('id_production_order' => $poBsjId, 'operation_no' => '0010', 'work_center' => 'WC-LAM', 'operation_name' => 'Lamination BSJ', 'status' => 'FINISHED', 'remarks' => $marker));
    issue_to_production($pdo, $bsjPo, $bsjIssueComponents, 'GIP-BSJ-'.$marker, $date, $plant, $rmSloc, $rmBin, $user, $marker);
    $bsjPo = one($pdo, "SELECT * FROM production_order WHERE id_production_order=?", array($poBsjId));
    $bsjGr = confirm_and_gr($pdo, $bsjPo, 'PC-BSJ-'.$marker, 'GRP-BSJ-'.$marker, $date, $bsjQty, $plant, $wipSloc, $wipBin, $user, $marker);

    $soId = insert_row($pdo, 'sales_order', array(
        'no_sales_order' => 'SO-'.$marker,
        'so_date' => $date,
        'currency' => 'IDR',
        'consignee' => $customer['nama'],
        'catatan' => $marker,
        'dari' => 'PL01',
        'ke' => 'Customer',
        'rupiah_rate' => 1,
        'rupiah_rate_sale' => 1,
        'kode_penerima' => $customer['kode_pemasok'],
        'tax' => 'PPN',
        'no_po' => 'CPO-'.$marker,
        'sales_id' => $user,
        'user' => $user,
        'delivery_term' => 'FRANCO',
        'delivery_date' => $date,
        'shipping_address' => $customer['alamat'],
        'status' => 'APPROVED',
        'approval_status' => 'APPROVED',
        'submitted_by' => $user,
        'submitted_at' => $now,
        'approved_by' => $user,
        'approved_at' => $now,
    ));
    $soDetailId = insert_row($pdo, 'sales_order_detail', array(
        'id_sales_order' => $soId,
        'kd_barang' => 'BJ00001',
        'store' => $fgSloc['storage_code'],
        'ket' => $marker,
        'qty' => $fgQty,
        'qty_terima' => 0,
        'price' => 120000,
        'nilai' => $fgQty * 120000,
        'status' => '0',
    ));

    $poFgId = insert_row($pdo, 'production_order', array(
        'no_production_order' => 'POPR-FG-'.$marker,
        'id_sales_order' => $soId,
        'no_sales_order' => 'SO-'.$marker,
        'id_sales_order_detail' => $soDetailId,
        'customer_code' => $customer['kode_pemasok'],
        'customer_po' => 'CPO-'.$marker,
        'order_strategy' => 'MTO',
        'bom_id' => $fgBom['id'],
        'bom_no' => $fgBom['bom_no'],
        'plant' => $plant['plant_code'],
        'storage_location' => $fgSloc['storage_code'],
        'material_code' => 'BJ00001',
        'material_name' => $fg['nm_barang'],
        'order_qty' => $fgQty,
        'uom' => $fg['satuan'],
        'start_date' => $date,
        'finish_date' => $date,
        'actual_start' => $date.' 14:00:00',
        'status' => 'IN_PROCESS',
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $now,
    ));
    $fgPo = one($pdo, "SELECT * FROM production_order WHERE id_production_order=?", array($poFgId));
    $fgDetails = all_rows($pdo, "SELECT * FROM bom_detail WHERE id_bom=? ORDER BY line_no,id", array($fgBom['id']));
    $fgIssueComponents = array();
    foreach ($fgDetails as $d) {
        $mat = material($pdo, $d['kodebb']);
        $req = round((float)$d['component_qty'] * $fgQty, 5);
        $matId = insert_row($pdo, 'production_order_material', array(
            'id_production_order' => $poFgId,
            'material_code' => $d['kodebb'],
            'material_name' => $mat['nm_barang'],
            'kd_kategori' => $mat['kd_kategori'],
            'required_qty' => $req,
            'issued_qty' => 0,
            'remaining_qty' => $req,
            'uom' => $mat['satuan'],
            'storage_location' => ($d['kodebb'] === 'BSJ00001') ? $wipSloc['storage_code'] : $rmSloc['storage_code'],
            'issue_status' => 'OPEN',
            'remarks' => $marker,
        ));
        $fgIssueComponents[] = array('code' => $d['kodebb'], 'qty' => $req, 'production_detail_id' => $matId, 'price' => isset($prices[$d['kodebb']]) ? $prices[$d['kodebb']] : $bsjGr['price']);
    }
    insert_row($pdo, 'production_order_operation', array('id_production_order' => $poFgId, 'operation_no' => '0010', 'work_center' => 'WC-FIN', 'operation_name' => 'Final Lamination', 'status' => 'FINISHED', 'remarks' => $marker));
    issue_to_production($pdo, $fgPo, $fgIssueComponents, 'GIP-FG-'.$marker, $date, $plant, $fgSloc, $fgBin, $user, $marker);
    $fgPo = one($pdo, "SELECT * FROM production_order WHERE id_production_order=?", array($poFgId));
    $fgGr = confirm_and_gr($pdo, $fgPo, 'PC-FG-'.$marker, 'GRP-FG-'.$marker, $date, $fgQty, $plant, $fgSloc, $fgBin, $user, $marker);

    $deliveryId = insert_row($pdo, 'erp_outbound_delivery', array(
        'delivery_no' => 'OD-'.$marker,
        'delivery_date' => $date,
        'planned_gi_date' => $date,
        'id_sales_order' => $soId,
        'no_sales_order' => 'SO-'.$marker,
        'customer_code' => $customer['kode_pemasok'],
        'customer_name' => $customer['nama'],
        'shipping_point' => 'SP01',
        'route' => 'LOCAL',
        'carrier' => 'DUMMY LOGISTICS',
        'vehicle_no' => 'B 1234 KB',
        'driver_name' => 'Driver Dummy',
        'ship_to_address' => $customer['alamat'],
        'status' => 'PACKED',
        'picking_status' => 'COMPLETE',
        'packing_status' => 'COMPLETE',
        'gi_status' => 'NOT_POSTED',
        'remarks' => $marker,
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
        'order_qty' => $fgQty,
        'delivery_qty' => $fgQty,
        'picked_qty' => $fgQty,
        'packed_qty' => $fgQty,
        'gi_qty' => 0,
        'uom' => $fg['satuan'],
        'price' => 120000,
        'amount' => $fgQty * 120000,
        'batch_no' => 'FG-LOT-'.$marker,
        'remarks' => $marker,
    ));
    $pickingId = insert_row($pdo, 'erp_picking', array(
        'picking_no' => 'PICK-'.$marker,
        'picking_date' => $date,
        'delivery_id' => $deliveryId,
        'delivery_no' => 'OD-'.$marker,
        'id_sales_order' => $soId,
        'no_sales_order' => 'SO-'.$marker,
        'customer_code' => $customer['kode_pemasok'],
        'customer_name' => $customer['nama'],
        'warehouse' => $fgSloc['storage_code'],
        'picker' => 'Picker Dummy',
        'status' => 'PICKED',
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $now,
    ));
    insert_row($pdo, 'erp_picking_detail', array(
        'picking_id' => $pickingId,
        'delivery_detail_id' => $deliveryDetailId,
        'line_no' => 10,
        'material_code' => 'BJ00001',
        'material_name' => $fg['nm_barang'],
        'store' => $fgSloc['storage_code'],
        'delivery_qty' => $fgQty,
        'picked_qty' => $fgQty,
        'uom' => $fg['satuan'],
        'batch_no' => 'FG-LOT-'.$marker,
        'source_bin' => $fgBin['bin_code'],
        'remarks' => $marker,
    ));
    $packingId = insert_row($pdo, 'packing_list', array(
        'delivery_id' => $deliveryId,
        'delivery_no' => 'OD-'.$marker,
        'picking_id' => $pickingId,
        'picking_no' => 'PICK-'.$marker,
        'no_packing_list' => 'PL-'.$marker,
        'tgl_sj' => $date,
        'penerima' => $customer['kode_pemasok'],
        'pemilik' => $customer['kode_pemasok'],
        'no_po' => 'CPO-'.$marker,
        'valuta' => 'IDR',
        'kurs' => 1,
        'vehicle_no' => 'B 1234 KB',
        'status' => 'PACKED',
        'packed_by' => $user,
        'packed_at' => $now,
        'remarks' => $marker,
    ));
    $packingDetailId = insert_row($pdo, 'packing_list_detail', array(
        'packing_list_id' => $packingId,
        'delivery_detail_id' => $deliveryDetailId,
        'line_no' => 10,
        'tgl_sj' => $date,
        'kode_pemilik' => $customer['kode_pemasok'],
        'kode' => 'BJ00001',
        'material_name' => $fg['nm_barang'],
        'delivery_qty' => $fgQty,
        'picked_qty' => $fgQty,
        'packed_qty' => $fgQty,
        'jumlah' => $fgQty,
        'harga' => 120000,
        'valuta' => 'IDR',
        'hs_code' => '5903.20.00',
        'nilai' => $fgQty * 120000,
        'berat' => $fgQty,
        'bruto' => $fgQty * 1.02,
        'lot_no' => 'FG-LOT-'.$marker,
        'packing' => 'ROLL',
        'qty_packing' => '1',
        'remark' => $marker,
        'unit' => $fg['satuan'],
        'kurs' => 1,
        'row_no' => 10,
        'kd_kategori' => $fg['kd_kategori'],
    ));
    $sjId = insert_row($pdo, 'surat_jalan', array(
        'no_surat_jalan' => 'SJ-'.$marker,
        'id_sales_order' => $soId,
        'packing_list_id' => $packingId,
        'packing_list_no' => 'PL-'.$marker,
        'delivery_id' => $deliveryId,
        'delivery_no' => 'OD-'.$marker,
        'picking_no' => 'PICK-'.$marker,
        'movement_type' => '601',
        'no_sales_order' => 'SO-'.$marker,
        'shipping_point' => 'SP01',
        'route' => 'LOCAL',
        'carrier' => 'DUMMY LOGISTICS',
        'tgl_surat_jalan' => $date,
        'document_date' => $date,
        'posting_date' => $date,
        'kode_penerima' => $customer['kode_pemasok'],
        'sold_to_party' => $customer['kode_pemasok'],
        'ship_to_party' => $customer['kode_pemasok'],
        'bill_to_party' => $customer['kode_pemasok'],
        'payer' => $customer['kode_pemasok'],
        'no_po' => 'CPO-'.$marker,
        'alamat_pengiriman' => $customer['alamat'],
        'sopir' => 'Driver Dummy',
        'no_kendaraan' => 'B 1234 KB',
        'no_polisi' => 'B 1234 KB',
        'keterangan' => $marker,
        'total_qty' => $fgQty,
        'status' => 'dikirim',
        'delivery_status' => 'PGI',
        'tgl_kirim' => $now,
        'nama_penerima' => $customer['nama'],
        'created_by' => $user,
        'created_date' => $now,
    ));
    $sjDetailId = insert_row($pdo, 'surat_jalan_detail', array(
        'surat_jalan_id' => $sjId,
        'line_no' => 10,
        'packing_list_detail_id' => $packingDetailId,
        'delivery_detail_id' => $deliveryDetailId,
        'id_sales_order_detail' => $soDetailId,
        'material_code' => 'BJ00001',
        'material_name' => $fg['nm_barang'],
        'batch_no' => 'FG-LOT-'.$marker,
        'lot_no' => 'FG-LOT-'.$marker,
        'kode_barang' => 'BJ00001',
        'nama_barang' => $fg['nm_barang'],
        'packing' => 'ROLL',
        'satuan_packing' => 'ROLL',
        'qty_order' => $fgQty,
        'qty_kirim' => $fgQty,
        'satuan' => $fg['satuan'],
        'plant_id' => $plant['id'],
        'storage_location_id' => $fgSloc['id'],
        'storage_bin_id' => $fgBin['id'],
        'stock_type' => 'UNRESTRICTED',
        'bc_document_type' => 'BC 2.5',
        'bc_document_no' => 'BC25D001',
        'bc_document_date' => $date,
        'hs_code' => '5903.20.00',
        'net_weight' => $fgQty,
        'gross_weight' => $fgQty * 1.02,
        'keterangan' => $marker,
        'row_no' => 10,
    ));

    $giId = insert_row($pdo, 'erp_goods_issue_delivery', array(
        'gi_no' => 'GID-'.$marker,
        'delivery_id' => $deliveryId,
        'delivery_no' => 'OD-'.$marker,
        'id_sales_order' => $soId,
        'no_sales_order' => 'SO-'.$marker,
        'customer_code' => $customer['kode_pemasok'],
        'customer_name' => $customer['nama'],
        'document_date' => $date,
        'posting_date' => $date,
        'movement_type' => '601',
        'shipping_point' => 'SP01',
        'vehicle_no' => 'B 1234 KB',
        'driver_name' => 'Driver Dummy',
        'reference_surat_jalan' => 'SJ-'.$marker,
        'outbound_bc_type' => 'BC 2.5',
        'outbound_bc_purpose_code' => '25',
        'outbound_bc_purpose' => 'Pengeluaran ke TLDDP (Jual lokal)',
        'outbound_no_aju' => 'AJU25'.$marker,
        'outbound_tgl_aju' => $date,
        'outbound_no_daftar' => 'BC25D001',
        'outbound_tgl_daftar' => $date,
        'outbound_customs_office' => 'KPPBC Dummy',
        'outbound_destination_country' => 'ID',
        'outbound_customs_remarks' => $marker.' outbound bonded zone',
        'status' => 'POSTED',
        'total_qty' => $fgQty,
        'remarks' => $marker,
        'created_by' => $user,
        'created_at' => $now,
    ));
    $giDetailId = insert_row($pdo, 'erp_goods_issue_delivery_detail', array(
        'gi_id' => $giId,
        'delivery_detail_id' => $deliveryDetailId,
        'line_no' => 10,
        'material_code' => 'BJ00001',
        'material_name' => $fg['nm_barang'],
        'qty' => $fgQty,
        'uom' => $fg['satuan'],
        'price' => $fgGr['price'],
        'amount' => $fgGr['amount'],
        'stock_type' => 'UNRESTRICTED',
        'remarks' => $marker,
    ));
    exec_sql($pdo, "UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=?", array($fgQty, $fgGr['stock_layer_id']));
    $giMatDocId = insert_row($pdo, 'detail_transaksi', array(
        'no_ref' => 'GID-'.$marker,
        'ref_pengganti' => 'OD-'.$marker,
        'move_code' => '601',
        'posisi' => 'GUDANG',
        'no_urut' => 10,
        'qty' => -1 * $fgQty,
        'kd_barang' => 'BJ00001',
        'lokasi' => 'GUDANG',
        'document_date' => $date.' 16:00:00',
        'posting_date' => $date.' 16:00:00',
        'user' => $user,
        'remark' => $marker.' Goods Issue for Delivery',
        'direction' => 'OUT',
        'ref_type' => 'GI_DELIVERY',
        'ref_id' => $giId,
        'ref_detail_id' => $giDetailId,
        'uom' => $fg['satuan'],
        'price' => $fgGr['price'],
        'amount' => $fgGr['amount'],
        'reason' => $marker,
        'created_by' => $user,
        'no_bpb' => 'GRP-FG-'.$marker,
        'plant_id' => $plant['id'],
        'storage_location_id' => $fgSloc['id'],
        'storage_bin_id' => $fgBin['id'],
        'stock_type' => 'UNRESTRICTED',
        'destination_material_code' => 'BJ00001',
    ));
    insert_row($pdo, 'erp_goods_issue_delivery_trace', array(
        'gi_id' => $giId,
        'gi_detail_id' => $giDetailId,
        'stock_layer_id' => $fgGr['stock_layer_id'],
        'material_doc_id' => $giMatDocId,
        'qty' => $fgQty,
        'price' => $fgGr['price'],
        'amount' => $fgGr['amount'],
        'stock_type' => 'UNRESTRICTED',
        'plant_id' => $plant['id'],
        'storage_location_id' => $fgSloc['id'],
        'storage_bin_id' => $fgBin['id'],
        'no_bpb' => 'GRP-FG-'.$marker,
        'lot_no' => 'FG-LOT-'.$marker,
        'source_ref_table' => 'erp_gr_production',
        'source_ref_id' => $fgGr['gr_detail_id'],
    ));
    exec_sql($pdo, "UPDATE erp_goods_issue_delivery SET total_amount=? WHERE id=?", array($fgGr['amount'], $giId));
    exec_sql($pdo, "UPDATE erp_outbound_delivery_detail SET gi_qty=? WHERE id=?", array($fgQty, $deliveryDetailId));
    exec_sql($pdo, "UPDATE erp_outbound_delivery SET gi_status='POSTED',status='PGI',reference_gi=?,reference_surat_jalan=? WHERE id=?", array('GID-'.$marker, 'SJ-'.$marker, $deliveryId));
    exec_sql($pdo, "UPDATE surat_jalan SET gi_id=?,gi_no=? WHERE id=?", array($giId, 'GID-'.$marker, $sjId));
    exec_sql($pdo, "UPDATE surat_jalan_detail SET gi_detail_id=? WHERE id=?", array($giDetailId, $sjDetailId));
    post_journal($pdo, 'GID-'.$marker, $date, 'GOODS_ISSUE_DELIVERY', 'Goods Issue Delivery '.$marker, array(
        array('no_rek' => '51100', 'debet' => $fgGr['amount'], 'kredit' => 0),
        array('no_rek' => '14300', 'debet' => 0, 'kredit' => $fgGr['amount']),
    ), $user);
    insert_row($pdo, 'erp_goods_issue_delivery_history', array('gi_id' => $giId, 'status_baru' => 'POSTED', 'remarks' => $marker, 'changed_by' => $user));

    $net = $fgQty * 120000;
    $tax = round($net * 0.11, 2);
    $gross = $net + $tax;
    $invoiceId = insert_row($pdo, 'sales_invoice', array(
        'billing_type' => 'F2',
        'reference_type' => 'SURAT_JALAN',
        'reference_no' => 'SJ-'.$marker,
        'bill_to' => $customer['kode_pemasok'],
        'no_sales_invoice' => 'INV-S-'.$marker,
        'no_sales_order' => 'SO-'.$marker,
        'ship_to' => $customer['kode_pemasok'],
        'invoice_date' => $date,
        'posting_date' => $date,
        'invoice_no' => 'INV-S-'.$marker,
        'nopo' => 'CPO-'.$marker,
        'term' => 'NET30',
        'due_date' => '2026-07-21',
        'valuta' => 'IDR',
        'ship_date' => $date,
        'no_do' => 'OD-'.$marker,
        'catatan' => $marker,
        'tax' => 'PPN',
        'tax_code' => 'PPN11',
        'ar_account' => '12101',
        'revenue_account' => '41100',
        'tax_account' => '21807',
        'tax_rate' => 11,
        'net_amount' => $net,
        'tax_amount' => $tax,
        'gross_amount' => $gross,
        'billing_status' => 'POSTED',
        'created_by' => $user,
        'posted_by' => $user,
        'posted_at' => $now,
    ));
    insert_row($pdo, 'sales_invoice_detail', array(
        'id_sales' => $invoiceId,
        'sales_order_detail_id' => $soDetailId,
        'surat_jalan_detail_id' => $sjDetailId,
        'line_no' => 10,
        'billing_item_type' => 'STANDARD',
        'kd_barang' => 'BJ00001',
        'nm_barang' => $fg['nm_barang'],
        'material_number' => 'BJ00001',
        'material_description' => $fg['nm_barang'],
        'qty' => $fgQty,
        'harga' => 120000,
        'unit' => $fg['satuan'],
        'nilai' => $net,
        'tax_code' => 'PPN11',
        'tax_rate' => 11,
        'tax_amount' => $tax,
        'gross_amount' => $gross,
    ));
    $invoiceJournalId = post_journal($pdo, 'INV-S-'.$marker, $date, 'CUSTOMER_INVOICE', 'Customer Invoice '.$marker, array(
        array('no_rek' => '12101', 'debet' => $gross, 'kredit' => 0),
        array('no_rek' => '41100', 'debet' => 0, 'kredit' => $net),
        array('no_rek' => '21807', 'debet' => 0, 'kredit' => $tax),
    ), $user);
    exec_sql($pdo, "UPDATE sales_invoice SET journal_header_id=? WHERE id_sales=?", array($invoiceJournalId, $invoiceId));

    insert_row($pdo, 'log_aktifitas', array(
        'deskripsi' => 'User admin membuat dummy end-to-end '.$marker.' dari PR sampai GI Delivery dan invoice pada '.$now,
        'user' => $user,
        'tgl' => $now,
    ));

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$summary = array(
    'marker' => $marker,
    'documents' => array(
        'PR-'.$marker, 'PO-'.$marker, 'BPB-'.$marker, 'POPR-BSJ-'.$marker, 'GIP-BSJ-'.$marker,
        'PC-BSJ-'.$marker, 'GRP-BSJ-'.$marker, 'SO-'.$marker, 'POPR-FG-'.$marker,
        'GIP-FG-'.$marker, 'PC-FG-'.$marker, 'GRP-FG-'.$marker, 'OD-'.$marker,
        'PICK-'.$marker, 'PL-'.$marker, 'SJ-'.$marker, 'GID-'.$marker, 'INV-S-'.$marker,
    ),
);

echo json_encode($summary, JSON_PRETTY_PRINT).PHP_EOL;
