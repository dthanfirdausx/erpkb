<?php
/**
 * Simple i18n audit helper for ERPKB.
 *
 * Usage:
 *   php tools/i18n_audit.php summary
 *   php tools/i18n_audit.php keys
 */

$root = dirname(__DIR__);
$langFiles = array('en', 'id', 'ko', 'zh');

function audit_load_lang($root, $code)
{
    $lang = array();
    include $root . '/inc/lang/' . $code . '.php';
    return $lang;
}

function audit_text_count($file)
{
    $source = file_get_contents($file);
    $patterns = array(
        '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is',
        '/box-title[^>]*>(.*?)<\/h3>/is',
        '/modal-title[^>]*>(.*?)<\/h4>/is',
        '/<label[^>]*>(.*?)<\/label>/is',
        '/<th[^>]*>(.*?)<\/th>/is',
        '/<button[^>]*>(.*?)<\/button>/is',
        '/placeholder=["\']([^"\']+)["\']/i',
        '/title=["\']([^"\']+)["\']/i',
        '/Swal\.fire\((.*?)\)/is',
        '/alert\((.*?)\)/is',
        '/confirm\((.*?)\)/is',
    );
    $count = 0;
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $source, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim(strip_tags($text));
                if ($text !== '' && strpos($text, '<?=') === false && strpos($text, '<?php') === false) {
                    $count++;
                }
            }
        }
    }
    return $count;
}

$mode = isset($argv[1]) ? $argv[1] : 'summary';

if ($mode === 'keys') {
    $base = null;
    foreach ($langFiles as $code) {
        $lang = audit_load_lang($root, $code);
        $keys = array_keys($lang);
        echo strtoupper($code) . ': ' . count($keys) . " keys\n";
        if ($base === null) {
            $base = $keys;
            continue;
        }
        $missing = array_values(array_diff($base, $keys));
        $extra = array_values(array_diff($keys, $base));
        echo '  missing=' . count($missing) . ' extra=' . count($extra) . "\n";
        if ($missing) echo '  missing_keys=' . implode(',', $missing) . "\n";
        if ($extra) echo '  extra_keys=' . implode(',', $extra) . "\n";
    }
    exit;
}

$stats = array();
foreach (glob($root . '/modul/*/*.php') as $file) {
    $count = audit_text_count($file);
    if ($count <= 0) continue;
    $module = basename(dirname($file));
    if (!isset($stats[$module])) $stats[$module] = 0;
    $stats[$module] += $count;
}

arsort($stats);
foreach ($stats as $module => $count) {
    echo $module . "\t" . $count . "\n";
}
?>
