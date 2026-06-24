<?php
/**
 * Audit ERPKB master data consistency.
 *
 * Usage:
 *   php database/scripts/audit_master_data_consistency.php
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

function mdc_one(PDO $pdo, $sql) {
    $row = $pdo->query($sql)->fetch();
    if (!$row) return 0;
    $value = reset($row);
    return (int)$value;
}

$checks = array(
    'duplicate_material_code' => "SELECT COUNT(*) FROM (SELECT kd_barang FROM barang WHERE COALESCE(kd_barang,'')<>'' GROUP BY kd_barang HAVING COUNT(*)>1) x",
    'duplicate_vendor_code' => "SELECT COUNT(*) FROM (SELECT kode_pemasok FROM pemasok WHERE COALESCE(kode_pemasok,'')<>'' GROUP BY kode_pemasok HAVING COUNT(*)>1) x",
    'duplicate_uom_code' => "SELECT COUNT(*) FROM (SELECT kode FROM satuan WHERE COALESCE(kode,'')<>'' GROUP BY kode HAVING COUNT(*)>1) x",
    'duplicate_packing_unit' => "SELECT COUNT(*) FROM (SELECT satuan_packing FROM satuan_packing WHERE COALESCE(satuan_packing,'')<>'' GROUP BY satuan_packing HAVING COUNT(*)>1) x",
    'duplicate_category_code' => "SELECT COUNT(*) FROM (SELECT kd_kategori FROM kategori WHERE COALESCE(kd_kategori,'')<>'' GROUP BY kd_kategori HAVING COUNT(*)>1) x",
    'duplicate_bc_in_code' => "SELECT COUNT(*) FROM (SELECT kode FROM jenisbcmasuk WHERE COALESCE(kode,'')<>'' GROUP BY kode HAVING COUNT(*)>1) x",
    'duplicate_bc_out_code' => "SELECT COUNT(*) FROM (SELECT kode FROM jenisbckeluar WHERE COALESCE(kode,'')<>'' GROUP BY kode HAVING COUNT(*)>1) x",
    'duplicate_username' => "SELECT COUNT(*) FROM (SELECT username FROM sys_users WHERE COALESCE(username,'')<>'' GROUP BY username HAVING COUNT(*)>1) x",
    'material_without_category' => "SELECT COUNT(*) FROM barang b LEFT JOIN kategori k ON k.kd_kategori=b.kd_kategori WHERE COALESCE(b.kd_kategori,'')<>'' AND k.kd_kategori IS NULL",
    'stock_without_material' => "SELECT COUNT(*) FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode WHERE sl.kode IS NOT NULL AND b.kd_barang IS NULL",
    'transaction_without_material' => "SELECT COUNT(*) FROM detail_transaksi d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.kd_barang IS NOT NULL AND b.kd_barang IS NULL",
    'user_without_group' => "SELECT COUNT(*) FROM sys_users u LEFT JOIN sys_group_users g ON g.id=u.group_level WHERE u.group_level IS NOT NULL AND g.id IS NULL",
    'role_without_menu' => "SELECT COUNT(*) FROM sys_menu_role r LEFT JOIN sys_menu m ON m.id=r.id_menu WHERE r.id_menu IS NOT NULL AND m.id IS NULL",
    'role_without_group' => "SELECT COUNT(*) FROM sys_menu_role r LEFT JOIN sys_group_users g ON g.level=r.group_level WHERE COALESCE(r.group_level,'')<>'' AND g.level IS NULL",
);

echo "ERPKB Master Data Consistency Audit\n";
echo "Generated at: ".date('Y-m-d H:i:s')."\n\n";
echo str_pad('Check', 36).str_pad('Result', 12).str_pad('Expected', 12)."Status\n";
echo str_repeat('-', 70)."\n";

$failed = 0;
foreach ($checks as $name => $sql) {
    $result = mdc_one($pdo, $sql);
    $status = $result === 0 ? 'PASS' : 'FAIL';
    if ($status === 'FAIL') $failed++;
    echo str_pad($name, 36).str_pad((string)$result, 12).str_pad('0', 12).$status."\n";
}

if ($failed > 0) {
    echo "\nAUDIT FAILED: ".$failed." master data consistency check(s) need attention.\n";
    exit(1);
}

echo "\nAUDIT PASSED: master data consistency checks are clean.\n";
?>
