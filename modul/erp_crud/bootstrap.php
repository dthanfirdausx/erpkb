<?php
require_once dirname(__DIR__).'/erp_master/erp_master_config.php';

if (!isset($erpCrudUrl)) {
    $erpCrudUrl = uri_segment(1);
}
$erpCrudConfig = erp_master_config($erpCrudUrl);
if (!$erpCrudConfig) {
    throw new RuntimeException('Konfigurasi modul CRUD tidak ditemukan: '.$erpCrudUrl);
}

function erp_crud_options($db, $settings)
{
    if (!isset($settings['type'])) {
        return array();
    }
    if ($settings['type'] === 'select') {
        return $settings['options'];
    }
    if ($settings['type'] !== 'db_select') {
        return array();
    }

    $options = array();
    $rows = $db->query(
        'select '.$settings['source_value'].' as option_value, '.$settings['source_label'].' as option_label'.
        ' from '.$settings['source_table'].' order by '.$settings['source_order'].' asc'
    );
    foreach ($rows as $row) {
        $options[(string) $row->option_value] = $row->option_label;
    }
    return $options;
}

function erp_crud_display($db, $settings, $value)
{
    $options = erp_crud_options($db, $settings);
    $display = isset($options[(string) $value]) ? $options[(string) $value] : (string) $value;
    if (isset($settings['type']) && $settings['type'] === 'select') {
        return erp_master_text($display);
    }
    return $display;
}
?>
