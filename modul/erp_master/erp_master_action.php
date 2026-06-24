<?php
session_start();
include '../../inc/config.php';
require_once __DIR__.'/erp_master_config.php';

function erp_master_response($status, $message)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(array('status' => $status, 'error_message' => $message, 'message' => $message)));
    exit();
}

if (empty($_SESSION['login'])) {
    erp_master_response('die', erp_t('session_over', 'Sorry, your login session has expired'));
}

$url = isset($_POST['menu_url']) ? $_POST['menu_url'] : '';
$config = erp_master_config($url);
if (!$config) {
    erp_master_response('error', erp_t('master_config_not_found', 'Master data module configuration was not found.'));
}

$permission = $db->query(
    "select r.insert_act, r.update_act, r.delete_act
     from sys_menu_role r inner join sys_menu m on m.id=r.id_menu
     where r.group_level=? and m.url=? limit 1",
    array('group_level' => $_SESSION['group_level'], 'url' => $url)
)->fetch();
if (!$permission) {
    erp_master_response('error', erp_t('master_no_access', 'You do not have access to this master data.'));
}

if (isset($_POST['delete_id'])) {
    if ($permission->delete_act !== 'Y') {
        erp_master_response('error', erp_t('master_delete_denied', 'You do not have permission to delete data.'));
    }
    $deleted = $db->delete($config['table'], $config['primary'], $_POST['delete_id']);
    erp_master_response($deleted ? 'good' : 'error', $deleted ? erp_t('common_deleted', 'Deleted') : $db->getErrorMessage());
}

$recordId = isset($_POST['record_id']) ? trim($_POST['record_id']) : '';
$isUpdate = $recordId !== '';
if ($isUpdate && $permission->update_act !== 'Y') {
    erp_master_response('error', erp_t('master_update_denied', 'You do not have permission to edit data.'));
}
if (!$isUpdate && $permission->insert_act !== 'Y') {
    erp_master_response('error', erp_t('master_insert_denied', 'You do not have permission to add data.'));
}

$data = array();
foreach ($config['fields'] as $field => $settings) {
    $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    if (!empty($settings['required']) && $value === '') {
        erp_master_response('error', erp_master_field_label($settings).' '.erp_t('common_required_suffix', 'is required.'));
    }
    $data[$field] = $value;
}
if ($config['table'] === 'manufactur' && !$isUpdate) {
    $data['date_created'] = date('Y-m-d H:i:s');
}

$saved = $isUpdate
    ? $db->update($config['table'], $data, $config['primary'], $recordId)
    : $db->insert($config['table'], $data);
erp_master_response($saved ? 'good' : 'error', $saved ? erp_t('common_saved', 'Saved') : $db->getErrorMessage());
?>
