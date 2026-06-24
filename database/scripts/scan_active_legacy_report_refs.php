<?php
/**
 * Scan active ERPKB menu/report files for legacy table/view references.
 *
 * This check focuses on read/report surfaces, not archived legacy write blocks.
 *
 * Usage:
 *   php database/scripts/scan_active_legacy_report_refs.php
 *
 * Exit code:
 *   0 = no legacy refs found in scanned active report/read files
 *   1 = one or more legacy refs found
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

$legacyPatterns = array(
    'brgjadi',
    'brgjadi_detail',
    'bahanbaku_detail',
    'stock_barang',
    'stock_incoming',
    'stock_outgoing',
    'v_stock_outgoing',
    'vtotalstock',
    'v_rekap_stok',
    'pemasukan_baru',
    'pemasukan_baru_detail',
);

$readFileRegex = '/(_view|_data|_lib|report|laporan|mutasi|stock|trace|overview|dashboard|home|export|excel)\.php$/i';
$actionScanUrlRegex = '/(report|laporan|mutasi|trace|overview|customs|inventory|valuation|aging|card|history|monitoring|dashboard|home)/i';

function candidate_module_dirs($url) {
    $url = trim((string)$url, "/ \t\r\n");
    if ($url === '' || $url === '#') {
        return array();
    }
    $dirs = array('modul/'.$url, 'modul/'.str_replace('-', '_', $url));
    return array_values(array_unique($dirs));
}

function should_scan_file($path, $url, $readFileRegex, $actionScanUrlRegex) {
    $base = basename($path);
    if (!preg_match('/\.php$/i', $base)) {
        return false;
    }
    if (preg_match($readFileRegex, $base)) {
        return true;
    }
    if (preg_match($actionScanUrlRegex, $url) && preg_match('/_action\.php$/i', $base)) {
        return true;
    }
    return false;
}

function legacy_read_hit($line, $pattern) {
    $table = preg_quote($pattern, '/');
    return preg_match('/\b(FROM|JOIN)\s+`?'.$table.'`?\b/i', $line) === 1;
}

$menus = $pdo->query("SELECT id,page_name,url,parent_name FROM sys_menu WHERE tampil='Y' AND type_menu='page' AND COALESCE(url,'')<>'' ORDER BY id")->fetchAll();
$hits = array();
$scannedFiles = 0;
$scannedMenus = 0;
$missingDirs = 0;

foreach ($menus as $menu) {
    $dirs = candidate_module_dirs($menu['url']);
    $existing = array();
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $existing[] = $dir;
        }
    }
    if (!$existing) {
        $missingDirs++;
        continue;
    }
    $scannedMenus++;
    foreach ($existing as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (!should_scan_file($path, $menu['url'], $readFileRegex, $actionScanUrlRegex)) {
                continue;
            }
            $scannedFiles++;
            $lines = @file($path);
            if ($lines === false) {
                continue;
            }
            foreach ($lines as $lineNo => $line) {
                foreach ($legacyPatterns as $pattern) {
                    if (legacy_read_hit($line, $pattern)) {
                        $hits[] = array(
                            'menu_id' => $menu['id'],
                            'url' => $menu['url'],
                            'page_name' => $menu['page_name'],
                            'file' => $path,
                            'line' => $lineNo + 1,
                            'pattern' => $pattern,
                            'snippet' => trim(preg_replace('/\s+/', ' ', $line)),
                        );
                    }
                }
            }
        }
    }
}

echo "ERPKB Active Legacy Report Reference Scan\n";
echo "Generated at: ".date('Y-m-d H:i:s')."\n";
echo "Active menus: ".count($menus)."\n";
echo "Menus with module folder: ".$scannedMenus."\n";
echo "Menus without module folder: ".$missingDirs."\n";
echo "Scanned files: ".$scannedFiles."\n";
echo "Legacy hits: ".count($hits)."\n\n";

if ($hits) {
    echo str_pad('Menu', 28).str_pad('Pattern', 22).str_pad('File:Line', 72)."Snippet\n";
    echo str_repeat('-', 150)."\n";
    foreach ($hits as $hit) {
        $fileLine = $hit['file'].':'.$hit['line'];
        echo str_pad(substr($hit['url'], 0, 27), 28).
             str_pad(substr($hit['pattern'], 0, 21), 22).
             str_pad(substr($fileLine, 0, 71), 72).
             substr($hit['snippet'], 0, 120)."\n";
    }
    echo "\nSCAN FAILED: legacy references found in active read/report files.\n";
    exit(1);
}

echo "SCAN PASSED: no legacy references found in active read/report files.\n";
exit(0);
