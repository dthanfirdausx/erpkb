<?php
/**
 * Generate ERPKB daily KPI monitoring snapshot.
 *
 * Usage:
 *   php database/scripts/generate_kpi_snapshot.php [YYYY-MM-DD]
 */

$date = isset($argv[1]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1]) ? $argv[1] : date('Y-m-d');

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

function kpi_one(PDO $pdo, $sql, array $params = array(), $default = 0) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    if (!$row) {
        return $default;
    }
    $value = reset($row);
    return $value === null ? $default : $value;
}

function kpi_table_exists(PDO $pdo, $table) {
    static $cache = array();
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    $exists = (int)kpi_one(
        $pdo,
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?",
        array($table),
        0
    );
    $cache[$table] = $exists > 0;
    return $cache[$table];
}

function kpi_save(PDO $pdo, array $row) {
    $sql = "INSERT INTO erp_kpi_monitoring_snapshot
        (snapshot_date,kpi_code,kpi_name,kpi_area,kpi_value,target_value,unit_of_measure,status,source_table,remarks,created_at,updated_at)
      VALUES
        (:snapshot_date,:kpi_code,:kpi_name,:kpi_area,:kpi_value,:target_value,:unit_of_measure,:status,:source_table,:remarks,NOW(),NOW())
      ON DUPLICATE KEY UPDATE
        kpi_name=VALUES(kpi_name),
        kpi_area=VALUES(kpi_area),
        kpi_value=VALUES(kpi_value),
        target_value=VALUES(target_value),
        unit_of_measure=VALUES(unit_of_measure),
        status=VALUES(status),
        source_table=VALUES(source_table),
        remarks=VALUES(remarks),
        updated_at=NOW()";
    $st = $pdo->prepare($sql);
    $st->execute($row);
}

function kpi_status_max_zero($value) {
    return ((float)$value <= 0.0001) ? 'GOOD' : 'CRITICAL';
}

$monthStart = date('Y-m-01', strtotime($date));
$rows = array();

$negativeStock = kpi_table_exists($pdo, 'stock_layer')
    ? kpi_one($pdo, "SELECT COUNT(*) FROM stock_layer WHERE COALESCE(qty_sisa,0)<0", array(), 0)
    : 0;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'P0_NEGATIVE_STOCK_CASE',
    'kpi_name' => 'Negative Stock Case',
    'kpi_area' => 'Warehouse',
    'kpi_value' => $negativeStock,
    'target_value' => 0,
    'unit_of_measure' => 'case',
    'status' => kpi_status_max_zero($negativeStock),
    'source_table' => 'stock_layer',
    'remarks' => 'Open stock layer with qty_sisa below zero.',
);

$unbalancedJournal = kpi_table_exists($pdo, 'jurnal_header') && kpi_table_exists($pdo, 'jurnal_detail')
    ? kpi_one($pdo, "SELECT COUNT(*) FROM (
        SELECT h.id
        FROM jurnal_header h
        JOIN jurnal_detail d ON d.id_header=h.id
        WHERE h.posting_status='POSTED'
        GROUP BY h.id
        HAVING ABS(ROUND(COALESCE(SUM(d.debet),0)-COALESCE(SUM(d.kredit),0),2))>0.01
      ) x", array(), 0)
    : 0;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'P0_UNBALANCED_POSTED_JOURNAL',
    'kpi_name' => 'Unbalanced Posted Journal',
    'kpi_area' => 'Finance',
    'kpi_value' => $unbalancedJournal,
    'target_value' => 0,
    'unit_of_measure' => 'journal',
    'status' => kpi_status_max_zero($unbalancedJournal),
    'source_table' => 'jurnal_header,jurnal_detail',
    'remarks' => 'Posted journals where debit and credit are not equal.',
);

$roleGap = kpi_one($pdo, "SELECT COUNT(*) FROM sys_menu m LEFT JOIN sys_menu_role r ON r.id_menu=m.id WHERE m.tampil='Y' AND m.type_menu='page' AND r.id_menu IS NULL", array(), 0);
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'P0_ACTIVE_MENU_ROLE_GAP',
    'kpi_name' => 'Active Menu Without Role',
    'kpi_area' => 'System',
    'kpi_value' => $roleGap,
    'target_value' => 0,
    'unit_of_measure' => 'menu',
    'status' => kpi_status_max_zero($roleGap),
    'source_table' => 'sys_menu,sys_menu_role',
    'remarks' => 'Active page menus without any role permission.',
);

$stockWithoutMaterial = kpi_table_exists($pdo, 'stock_layer')
    ? kpi_one($pdo, "SELECT COUNT(*) FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode WHERE sl.kode IS NOT NULL AND b.kd_barang IS NULL", array(), 0)
    : 0;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'P0_STOCK_LAYER_MATERIAL_GAP',
    'kpi_name' => 'Stock Layer Without Material Master',
    'kpi_area' => 'Master Data',
    'kpi_value' => $stockWithoutMaterial,
    'target_value' => 0,
    'unit_of_measure' => 'row',
    'status' => kpi_status_max_zero($stockWithoutMaterial),
    'source_table' => 'stock_layer,barang',
    'remarks' => 'Stock rows whose material code is not found in material master.',
);

$traceTotal = kpi_table_exists($pdo, 'erp_goods_issue_delivery_detail')
    ? kpi_one($pdo, "SELECT COUNT(*) FROM erp_goods_issue_delivery_detail", array(), 0)
    : 0;
$traceWithBc = 0;
if (kpi_table_exists($pdo, 'erp_goods_issue_delivery_trace')) {
    if (kpi_table_exists($pdo, 'erp_gr_production_trace')) {
        $traceWithBc = kpi_one($pdo, "SELECT COUNT(DISTINCT t.gi_detail_id)
            FROM erp_goods_issue_delivery_trace t
            LEFT JOIN erp_gr_production_trace gt ON gt.output_stock_layer_id=t.stock_layer_id
            WHERE COALESCE(t.no_dokpab,'')<>'' OR COALESCE(t.no_aju,'')<>''
               OR COALESCE(gt.no_dokpab,'')<>'' OR COALESCE(gt.no_aju,'')<>''", array(), 0);
    } else {
        $traceWithBc = kpi_one($pdo, "SELECT COUNT(DISTINCT gi_detail_id) FROM erp_goods_issue_delivery_trace WHERE COALESCE(no_dokpab,'')<>'' OR COALESCE(no_aju,'')<>''", array(), 0);
    }
}
$tracePct = $traceTotal > 0 ? round(((float)$traceWithBc / (float)$traceTotal) * 100, 4) : 100;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'CUS_TRACEABILITY_COMPLETENESS',
    'kpi_name' => 'Customs Traceability Completeness',
    'kpi_area' => 'Customs',
    'kpi_value' => $tracePct,
    'target_value' => 100,
    'unit_of_measure' => '%',
    'status' => $tracePct >= 100 ? 'GOOD' : ($tracePct >= 95 ? 'WARNING' : 'CRITICAL'),
    'source_table' => 'erp_goods_issue_delivery_detail,erp_goods_issue_delivery_trace,erp_gr_production_trace',
    'remarks' => 'GI Delivery detail rows traceable to direct or inherited customs document origin.',
);

$outputPlan = kpi_table_exists($pdo, 'production_order')
    ? kpi_one($pdo, "SELECT COALESCE(SUM(order_qty),0) FROM production_order WHERE DATE(created_at) BETWEEN ? AND ?", array($monthStart, $date), 0)
    : 0;
$outputActual = kpi_table_exists($pdo, 'production_order_confirmation')
    ? kpi_one($pdo, "SELECT COALESCE(SUM(yield_qty),0) FROM production_order_confirmation WHERE DATE(confirmation_date) BETWEEN ? AND ?", array($monthStart, $date), 0)
    : 0;
$outputPct = $outputPlan > 0 ? round(((float)$outputActual / (float)$outputPlan) * 100, 4) : 100;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'PP_OUTPUT_ACHIEVEMENT_MTD',
    'kpi_name' => 'Production Output Achievement MTD',
    'kpi_area' => 'Production',
    'kpi_value' => $outputPct,
    'target_value' => 95,
    'unit_of_measure' => '%',
    'status' => $outputPct >= 95 ? 'GOOD' : ($outputPct >= 85 ? 'WARNING' : 'CRITICAL'),
    'source_table' => 'production_order,production_order_confirmation',
    'remarks' => 'MTD yield quantity compared with production order quantity.',
);

$netResult = kpi_table_exists($pdo, 'jurnal_header') && kpi_table_exists($pdo, 'jurnal_detail')
    ? kpi_one($pdo, "SELECT COALESCE(SUM(CASE WHEN d.no_rek LIKE '4%' THEN d.kredit-d.debet WHEN d.no_rek LIKE '5%' THEN -(d.debet-d.kredit) ELSE 0 END),0)
        FROM jurnal_header h JOIN jurnal_detail d ON d.id_header=h.id
        WHERE h.posting_status='POSTED' AND h.tgl_jurnal BETWEEN ? AND ?", array($monthStart, $date), 0)
    : 0;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'FIN_NET_RESULT_MTD',
    'kpi_name' => 'Net Result MTD',
    'kpi_area' => 'Finance',
    'kpi_value' => $netResult,
    'target_value' => 0,
    'unit_of_measure' => 'IDR',
    'status' => $netResult >= 0 ? 'GOOD' : 'WARNING',
    'source_table' => 'jurnal_header,jurnal_detail',
    'remarks' => 'Revenue minus expense from posted journal in current month.',
);

$poOutstanding = kpi_table_exists($pdo, 'purchase_order_detail')
    ? kpi_one($pdo, "SELECT COUNT(*) FROM purchase_order_detail WHERE COALESCE(qty,0)>COALESCE(received_qty,0)", array(), 0)
    : 0;
$rows[] = array(
    'snapshot_date' => $date,
    'kpi_code' => 'PUR_PO_OUTSTANDING_LINE',
    'kpi_name' => 'PO Outstanding Line',
    'kpi_area' => 'Purchasing',
    'kpi_value' => $poOutstanding,
    'target_value' => 0,
    'unit_of_measure' => 'line',
    'status' => $poOutstanding > 0 ? 'INFO' : 'GOOD',
    'source_table' => 'purchase_order_detail',
    'remarks' => 'PO detail lines where ordered qty is greater than received qty.',
);

$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        kpi_save($pdo, $row);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}

echo "Generated ".count($rows)." KPI snapshot rows for $date\n";
foreach ($rows as $row) {
    echo $row['kpi_code'].": ".$row['kpi_value']." ".$row['unit_of_measure']." [".$row['status']."]\n";
}
