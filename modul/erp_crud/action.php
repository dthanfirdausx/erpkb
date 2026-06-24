<?php
session_start();
include dirname(__DIR__, 2).'/inc/config.php';
require dirname(__DIR__).'/erp_master/erp_master_config.php';

function erp_crud_response($status, $message)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(array('status'=>$status, 'error_message'=>$message, 'message'=>$message)));
    exit();
}

if (empty($_SESSION['login'])) { erp_crud_response('die', erp_t('session_over', 'Sorry, your login session has expired')); }
$config = erp_master_config($erpCrudUrl);
if (!$config) { erp_crud_response('error', erp_t('master_config_not_found', 'Master data module configuration was not found.')); }
$permission = $db->query("select r.insert_act,r.update_act,r.delete_act from sys_menu_role r join sys_menu m on m.id=r.id_menu where r.group_level=? and m.url=? limit 1", array('group_level'=>$_SESSION['group_level'], 'url'=>$erpCrudUrl))->fetch();
if (!$permission) { erp_crud_response('error', erp_t('master_no_access', 'You do not have access.')); }

if (isset($_POST['delete_id'])) {
    if ($permission->delete_act !== 'Y') { erp_crud_response('error', erp_t('master_delete_denied', 'You do not have permission to delete data.')); }
    $deleteGuards = array(
        'plant' => array(array('barang','plant_id'), array('erp_storage_location','plant_id'), array('erp_purchasing_organization','plant_id'), array('erp_shipping_point','plant_id')),
        'storage-location' => array(array('barang','default_storage_location_id'), array('erp_storage_bin','storage_location_id')),
        'material-type' => array(array('barang','material_type_id')),
        'material-group' => array(array('barang','material_group_id')),
        'purchasing-organization' => array(array('erp_purchasing_group','purchasing_org_id')),
        'sales-organization' => array(array('erp_distribution_channel','sales_org_id')),
        'fiscal-period' => array(array('erp_financial_closing_checklist','period_id')),
    );
    if (isset($deleteGuards[$erpCrudUrl])) {
        foreach ($deleteGuards[$erpCrudUrl] as $guard) {
            $used = $db->query('select count(*) as total from '.$guard[0].' where '.$guard[1].'=?', array($guard[1]=>$_POST['delete_id']))->fetch();
            if ($used && intval($used->total) > 0) {
                erp_crud_response('error', sprintf(erp_t('master_delete_used', 'Data cannot be deleted because it is already used by %s. Deactivate the data status if it is no longer used.'), $guard[0]));
            }
        }
    }
    $ok = $db->delete($config['table'], $config['primary'], $_POST['delete_id']);
    erp_crud_response($ok ? 'good' : 'error', $ok ? erp_t('common_deleted', 'Deleted') : $db->getErrorMessage());
}

$recordId = isset($_POST['record_id']) ? trim($_POST['record_id']) : '';
$isUpdate = $recordId !== '';
if ($isUpdate && $permission->update_act !== 'Y') { erp_crud_response('error', erp_t('master_update_denied', 'You do not have permission to edit data.')); }
if (!$isUpdate && $permission->insert_act !== 'Y') { erp_crud_response('error', erp_t('master_insert_denied', 'You do not have permission to add data.')); }
$data = array();
foreach ($config['fields'] as $field => $settings) {
    $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    if (!empty($settings['required']) && $value === '') { erp_crud_response('error', erp_master_field_label($settings).' '.erp_t('common_required_suffix', 'is required.')); }
    $data[$field] = $value;
}
$ok = $isUpdate ? $db->update($config['table'], $data, $config['primary'], $recordId) : $db->insert($config['table'], $data);
erp_crud_response($ok ? 'good' : 'error', $ok ? erp_t('common_saved', 'Saved') : $db->getErrorMessage());
?>
