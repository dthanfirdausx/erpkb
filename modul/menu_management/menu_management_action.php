<?php
session_start();
include '../../inc/config.php';

if (!isset($_SESSION['group_level']) || !in_array($_SESSION['group_level'], array('admin', 'system_administrator'), true)) {
    http_response_code(403);
    exit('permission denied');
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$columnMap = array(
    'change_read' => 'read_act',
    'change_insert' => 'insert_act',
    'change_update' => 'update_act',
    'change_delete' => 'delete_act',
    'change_import' => 'import_act',
);

if (isset($columnMap[$act])) {
    $roleId = intval(isset($_POST['role_id']) ? $_POST['role_id'] : 0);
    $value = isset($_POST['data_act']) && $_POST['data_act'] === 'Y' ? 'Y' : 'N';
    $role = $db->query(
        "select r.id, r.group_level from sys_menu_role r where r.id=? limit 1",
        array('id' => $roleId)
    )->fetch();

    if (!$role) {
        http_response_code(404);
        exit('role permission not found');
    }
    if ($_SESSION['group_level'] === 'system_administrator' && $role->group_level === 'admin') {
        http_response_code(403);
        exit('super administrator permission is protected');
    }

    $db->update('sys_menu_role', array($columnMap[$act] => $value), 'id', $roleId);
    echo 'good';
    exit();
}

if ($act === 'delete' && $_SESSION['group_level'] === 'admin') {
    $db->delete('sys_menu_role', 'id', intval($_GET['id']));
    echo 'good';
    exit();
}

http_response_code(400);
echo 'invalid action';
?>
