<?php
/**
 * Audit active ERPKB module UI conventions.
 *
 * Usage:
 *   php database/scripts/audit_active_module_ui.php
 *
 * The audit is heuristic: it checks active menu module files for common UI
 * markers used by ERPKB's current workbench standard.
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=erpkb;charset=utf8mb4', 'dthan', 'realmadrid', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));

function ui_candidate_dirs($url, $navAct = '') {
    $url = trim((string)$url, "/ \t\r\n");
    $navAct = trim((string)$navAct, "/ \t\r\n");
    $dirs = array();
    if ($navAct !== '' && $navAct !== '#') {
        $dirs[] = 'modul/'.$navAct;
        $dirs[] = 'modul/'.str_replace('-', '_', $navAct);
    }
    if ($url !== '' && $url !== '#') {
        $dirs[] = 'modul/'.$url;
        $dirs[] = 'modul/'.str_replace('-', '_', $url);
    }
    return array_values(array_unique($dirs));
}

function ui_find_file($dirs, $suffix) {
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $files = glob($dir.'/*'.$suffix);
        if ($files) {
            sort($files);
            return $files[0];
        }
        if ($suffix === '_view.php') {
            $moduleName = basename($dir);
            $mainFile = $dir.'/'.$moduleName.'.php';
            if (is_file($mainFile)) {
                return $mainFile;
            }
        }
    }
    return '';
}

function ui_read($file) {
    if (!$file || !is_file($file)) {
        return '';
    }
    $text = file_get_contents($file);
    $baseDir = dirname($file);
    if (preg_match_all('/include(?:_once)?\s+(?:__DIR__\s*\.\s*)?[\'"]([^\'"]+\.php)[\'"]\s*;/i', $text, $matches)) {
        foreach ($matches[1] as $includePath) {
            $includePath = str_replace(array('".', "'."), '', $includePath);
            $candidate = $baseDir.'/'.ltrim($includePath, '/');
            if (!is_file($candidate)) {
                $candidate = realpath($baseDir.'/'.$includePath);
            }
            if ($candidate && is_file($candidate)) {
                $text .= "\n".file_get_contents($candidate);
            }
        }
    }
    if (strpos($text, "erp_crud/view.php") !== false && is_file('modul/erp_crud/view.php')) {
        $text .= "\n".file_get_contents('modul/erp_crud/view.php');
    }
    if (strpos($text, "erp_workspace/erp_workspace.php") !== false && is_file('modul/erp_workspace/erp_workspace.php')) {
        $text .= "\n".file_get_contents('modul/erp_workspace/erp_workspace.php');
    }
    return $text;
}

function ui_has($text, $patterns) {
    foreach ((array)$patterns as $pattern) {
        if (@preg_match($pattern, $text)) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        } elseif (stripos($text, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

$menus = $pdo->query("SELECT id,page_name,url,nav_act,parent_name FROM sys_menu WHERE tampil='Y' AND type_menu='page' AND COALESCE(url,'')<>'' ORDER BY parent_name,urutan_menu,page_name")->fetchAll();
$rows = array();

foreach ($menus as $menu) {
    $dirs = ui_candidate_dirs($menu['url'], $menu['nav_act']);
    $view = ui_find_file($dirs, '_view.php');
    $data = ui_find_file($dirs, '_data.php');
    $action = ui_find_file($dirs, '_action.php');
    $viewText = ui_read($view);
    $dataText = ui_read($data);
    $actionText = ui_read($action);
    $allText = $viewText."\n".$dataText."\n".$actionText;

    $checks = array(
        'view_file' => $view !== '',
        'content_header' => ui_has($viewText, '/content-header/i'),
        'breadcrumb' => ui_has($viewText, '/breadcrumb/i'),
        'filter_box' => ui_has($viewText, array('/fa-filter/i', '/Filter /i', '/filter_/i')),
        'bootstrap_form' => ui_has($viewText, array('/form-horizontal/i', '/form-group/i')),
        'select2' => ui_has($viewText, array('/select2/i', '/chzn-select/i')),
        'datatable' => ui_has($viewText, array('/DataTable\s*\(/i', '/dataTable\s*\(/i')),
        'export_ui' => ui_has($allText, array('/Export Excel/i', '/excelHtml5/i', '/act=excel/i', '/act=export/i')),
        'detail_action' => ui_has($allText, array('/show_detail/i', '/btn-.*detail/i', '/detail_body/i', '/modal_.*detail/i')),
        'kpi_or_hero' => ui_has($viewText, array('/hero/i', '/kpi/i', '/small-box/i', '/info-box/i')),
    );

    $score = 0;
    foreach ($checks as $ok) {
        if ($ok) {
            $score++;
        }
    }
    $percent = round(($score / count($checks)) * 100, 1);
    $missing = array();
    foreach ($checks as $name => $ok) {
        if (!$ok) {
            $missing[] = $name;
        }
    }

    $priority = 'P2';
    $parent = strtolower((string)$menu['parent_name']);
    $name = strtolower((string)$menu['page_name']);
    if (preg_match('/goods receipt|goods issue|inventory management|physical inventory|warehouse reports|customs report|finance|billing|accounts|cash and bank/', $parent.' '.$name)) {
        $priority = 'P0';
    } elseif (preg_match('/sales|delivery|purchasing|ppic|production|quality|hr|employee|manager/', $parent.' '.$name)) {
        $priority = 'P1';
    }

    $rows[] = array(
        'priority' => $priority,
        'percent' => $percent,
        'score' => $score,
        'total' => count($checks),
        'id' => $menu['id'],
        'parent' => $menu['parent_name'],
        'name' => $menu['page_name'],
        'url' => $menu['url'],
        'view' => $view,
        'missing' => implode(',', $missing),
    );
}

usort($rows, function($a, $b) {
    $rank = array('P0' => 0, 'P1' => 1, 'P2' => 2);
    if ($rank[$a['priority']] !== $rank[$b['priority']]) {
        return $rank[$a['priority']] - $rank[$b['priority']];
    }
    if ($a['percent'] == $b['percent']) {
        return strcmp($a['url'], $b['url']);
    }
    return ($a['percent'] < $b['percent']) ? -1 : 1;
});

$outDir = 'docs/qa';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}
$report = $outDir.'/active_module_ui_audit.md';
$fh = fopen($report, 'w');
fwrite($fh, "# ERPKB Active Module UI Audit\n\n");
fwrite($fh, "Generated at: ".date('Y-m-d H:i:s')."\n\n");
fwrite($fh, "Heuristic checks: view file, content header, breadcrumb, filter box, bootstrap form, Select2/chosen, DataTable, export UI, detail action, KPI/hero.\n\n");
fwrite($fh, "| Priority | Score | Module | URL | Parent | Missing |\n");
fwrite($fh, "|---|---:|---|---|---|---|\n");
foreach ($rows as $row) {
    fwrite($fh, '| '.$row['priority'].' | '.$row['percent'].'% | '.str_replace('|', '/', $row['name']).' | `'.$row['url'].'` | '.str_replace('|', '/', (string)$row['parent']).' | `'.$row['missing'].'` |'."\n");
}
fclose($fh);

$summary = array('P0' => array('count' => 0, 'avg' => 0), 'P1' => array('count' => 0, 'avg' => 0), 'P2' => array('count' => 0, 'avg' => 0));
foreach ($rows as $row) {
    $summary[$row['priority']]['count']++;
    $summary[$row['priority']]['avg'] += $row['percent'];
}
foreach ($summary as $key => $value) {
    if ($value['count'] > 0) {
        $summary[$key]['avg'] = round($value['avg'] / $value['count'], 1);
    }
}

echo "ERPKB Active Module UI Audit\n";
echo "Active menus audited: ".count($rows)."\n";
echo "Report: ".$report."\n";
foreach ($summary as $key => $value) {
    echo $key.": ".$value['count']." module(s), avg ".$value['avg']."%\n";
}
echo "\nLowest priority gaps:\n";
$shown = 0;
foreach ($rows as $row) {
    if ($shown >= 20) {
        break;
    }
    echo str_pad($row['priority'], 4).str_pad((string)$row['percent'].'%', 8).str_pad($row['url'], 32).$row['missing']."\n";
    $shown++;
}
