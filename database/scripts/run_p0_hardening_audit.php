<?php
/**
 * Run ERPKB P0 hardening audit gates.
 *
 * Usage:
 *   php database/scripts/run_p0_hardening_audit.php
 *
 * Exit code:
 *   0 = all gates pass
 *   1 = one or more gates fail
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

function audit_count(PDO $pdo, $sql) {
    $st = $pdo->query($sql);
    $row = $st->fetch();
    if (!$row) {
        return 0;
    }
    return (int)reset($row);
}

$checks = array(
    'negative_stock_layer' => array(
        'sql' => "SELECT COUNT(*) FROM stock_layer WHERE COALESCE(qty_sisa,0) < 0",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'stock_layer_without_material' => array(
        'sql' => "SELECT COUNT(*) FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode WHERE sl.kode IS NOT NULL AND b.kd_barang IS NULL",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'detail_transaksi_without_material' => array(
        'sql' => "SELECT COUNT(*) FROM detail_transaksi dt LEFT JOIN barang b ON b.kd_barang=dt.kd_barang WHERE dt.kd_barang IS NOT NULL AND b.kd_barang IS NULL",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'unbalanced_posted_journal' => array(
        'sql' => "SELECT COUNT(*) FROM (
            SELECT h.id
            FROM jurnal_header h
            JOIN jurnal_detail d ON d.id_header=h.id
            WHERE h.posting_status='POSTED'
            GROUP BY h.id
            HAVING ABS(ROUND(COALESCE(SUM(d.debet),0)-COALESCE(SUM(d.kredit),0),2)) > 0.01
        ) x",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'posted_journal_header_without_detail' => array(
        'sql' => "SELECT COUNT(*) FROM jurnal_header h LEFT JOIN jurnal_detail d ON d.id_header=h.id WHERE h.posting_status='POSTED' AND d.id IS NULL",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'journal_detail_without_header' => array(
        'sql' => "SELECT COUNT(*) FROM jurnal_detail d LEFT JOIN jurnal_header h ON h.id=d.id_header WHERE h.id IS NULL",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'active_menu_without_role' => array(
        'sql' => "SELECT COUNT(*) FROM sys_menu m LEFT JOIN sys_menu_role r ON r.id_menu=m.id WHERE m.tampil='Y' AND m.type_menu='page' AND r.id_menu IS NULL",
        'expected' => 0,
        'severity' => 'P0',
    ),
    'open_stock_without_valuation' => array(
        'sql' => "SELECT COUNT(*) FROM stock_layer sl
            LEFT JOIN pemasukan_detail pd ON (sl.ref_table='pemasukan_detail' AND pd.id=sl.ref_id)
            LEFT JOIN detail_transaksi dtpd ON dtpd.id_incoming_detail=pd.id AND dtpd.kd_barang=sl.kode AND dtpd.direction='IN'
            LEFT JOIN detail_transaksi dtref ON dtref.no_bpb=sl.no_bpb AND dtref.kd_barang=sl.kode AND dtref.direction='IN'
            WHERE COALESCE(sl.qty_sisa,0)>0
              AND COALESCE(NULLIF(pd.harga,0),NULLIF(dtpd.price,0),NULLIF(dtref.price,0),CASE WHEN ABS(COALESCE(dtref.qty,0))>0 THEN ABS(COALESCE(dtref.amount,0))/ABS(dtref.qty) ELSE NULL END,0)=0",
        'expected' => 0,
        'severity' => 'P0',
    ),
);

$failed = 0;
echo "ERPKB P0 Hardening Audit\n";
echo "Generated at: ".date('Y-m-d H:i:s')."\n\n";
echo str_pad('Check', 42).str_pad('Result', 12).str_pad('Expected', 12)."Status\n";
echo str_repeat('-', 76)."\n";

foreach ($checks as $name => $cfg) {
    $value = audit_count($pdo, $cfg['sql']);
    $status = ($value === (int)$cfg['expected']) ? 'PASS' : 'FAIL';
    if ($status === 'FAIL') {
        $failed++;
    }
    echo str_pad($name, 42).str_pad((string)$value, 12).str_pad((string)$cfg['expected'], 12).$status."\n";
}

echo "\n";
if ($failed > 0) {
    echo "AUDIT FAILED: $failed gate(s) failed.\n";
    exit(1);
}

echo "AUDIT PASSED: all P0 gates are clean.\n";
exit(0);
