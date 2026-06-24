<?php
session_start();
include "../../inc/config.php";

function group_user_response($status, $message = '', $extra = array())
{
    $response = array_merge(array(
        'status' => $status,
        'message' => $message,
        'error_message' => $message,
    ), $extra);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array($response));
    exit();
}

function normalize_group_level($value)
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

function group_exists($db, $level, $levelName, $excludeId = 0)
{
    $sql = "select id from sys_group_users where (level=? or lower(level_name)=lower(?))";
    $params = array('level' => $level, 'level_name' => $levelName);

    if ($excludeId > 0) {
        $sql .= " and id!=?";
        $params['id'] = $excludeId;
    }

    $result = $db->query($sql, $params);
    return $result && $result->rowCount() > 0;
}

function create_group_permissions($db, $newLevel, $sourceLevel = '')
{
    if ($sourceLevel !== '') {
        return $db->query(
            "insert into sys_menu_role
                (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
             select m.id, ?,
                    coalesce(max(r.read_act), 'N'),
                    coalesce(max(r.insert_act), 'N'),
                    coalesce(max(r.update_act), 'N'),
                    coalesce(max(r.delete_act), 'N'),
                    coalesce(max(r.import_act), 'N')
             from sys_menu m
             left join sys_menu_role r
                    on r.id_menu=m.id and r.group_level=?
             group by m.id",
            array('new_level' => $newLevel, 'source_level' => $sourceLevel)
        );
    }

    return $db->query(
        "insert into sys_menu_role
            (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
         select id, ?, 'N', 'N', 'N', 'N', 'N' from sys_menu",
        array('group_level' => $newLevel)
    );
}

session_check_json();
if (!isset($_SESSION['group_level']) || !in_array($_SESSION['group_level'], array('admin', 'system_administrator'), true)) {
    group_user_response('error', 'Hanya administrator yang dapat mengelola role ERP.');
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

switch ($act) {
    case 'in':
        $levelName = trim(isset($_POST['level_name']) ? $_POST['level_name'] : '');
        $level = normalize_group_level(isset($_POST['level']) ? $_POST['level'] : $levelName);
        $description = trim(isset($_POST['deskripsi']) ? $_POST['deskripsi'] : '');
        $copyFromId = intval(isset($_POST['copy_from_group']) ? $_POST['copy_from_group'] : 0);

        if ($levelName === '' || $level === '') {
            group_user_response('error', 'Nama dan kode role wajib diisi.');
        }
        if (strlen($level) > 50 || strlen($levelName) > 50) {
            group_user_response('error', 'Nama dan kode role maksimal 50 karakter.');
        }
        if (group_exists($db, $level, $levelName)) {
            group_user_response('error', 'Kode atau nama role sudah digunakan.');
        }

        $sourceLevel = '';
        if ($copyFromId > 0) {
            $sourceGroup = $db->fetch_single_row('sys_group_users', 'id', $copyFromId);
            if (!$sourceGroup) {
                group_user_response('error', 'Role sumber tidak ditemukan.');
            }
            $sourceLevel = $sourceGroup->level;
        }

        $inserted = $db->insert('sys_group_users', array(
            'level' => $level,
            'level_name' => $levelName,
            'deskripsi' => $description,
        ));

        if (!$inserted) {
            group_user_response('error', $db->getErrorMessage());
        }

        $newId = $db->last_insert_id();
        if (!create_group_permissions($db, $level, $sourceLevel)) {
            $db->delete('sys_group_users', 'id', $newId);
            group_user_response('error', 'Role dibuat, tetapi inisialisasi hak akses gagal: '.$db->getErrorMessage());
        }

        group_user_response('good', 'Role berhasil dibuat.', array('id' => $newId));
        break;

    case 'up':
        $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $current = $db->fetch_single_row('sys_group_users', 'id', $id);
        if (!$current) {
            group_user_response('error', 'Role tidak ditemukan.');
        }
        if ($current->level === 'admin') {
            group_user_response('error', 'Role administrator sistem tidak dapat diubah.');
        }

        $levelName = trim(isset($_POST['level_name']) ? $_POST['level_name'] : '');
        $level = normalize_group_level(isset($_POST['level']) ? $_POST['level'] : $levelName);
        $description = trim(isset($_POST['deskripsi']) ? $_POST['deskripsi'] : '');

        if ($levelName === '' || $level === '') {
            group_user_response('error', 'Nama dan kode role wajib diisi.');
        }
        if (strlen($level) > 50 || strlen($levelName) > 50) {
            group_user_response('error', 'Nama dan kode role maksimal 50 karakter.');
        }
        if (group_exists($db, $level, $levelName, $id)) {
            group_user_response('error', 'Kode atau nama role sudah digunakan.');
        }

        if ($current->level !== $level) {
            $roleUpdated = $db->query(
                'update sys_menu_role set group_level=? where group_level=?',
                array('new_level' => $level, 'old_level' => $current->level)
            );
            if (!$roleUpdated) {
                group_user_response('error', 'Kode role gagal diperbarui pada hak akses.');
            }
        }

        $updated = $db->update('sys_group_users', array(
            'level' => $level,
            'level_name' => $levelName,
            'deskripsi' => $description,
        ), 'id', $id);

        if (!$updated) {
            if ($current->level !== $level) {
                $db->query(
                    'update sys_menu_role set group_level=? where group_level=?',
                    array('old_level' => $current->level, 'new_level' => $level)
                );
            }
            group_user_response('error', $db->getErrorMessage());
        }

        group_user_response('good', 'Role berhasil diperbarui.');
        break;

    case 'sync':
        $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
        $group = $db->fetch_single_row('sys_group_users', 'id', $id);
        if (!$group) {
            group_user_response('error', 'Role tidak ditemukan.');
        }

        $db->query(
            "delete from sys_menu_role
             where group_level=?
               and id_menu not in (select id from sys_menu)",
            array('group_level' => $group->level)
        );

        $synced = $db->query(
            "insert into sys_menu_role
                (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
             select m.id, ?, 'N', 'N', 'N', 'N', 'N'
             from sys_menu m
             where not exists (
                 select 1 from sys_menu_role r
                 where r.id_menu=m.id and r.group_level=?
             )",
            array('group_level' => $group->level, 'check_level' => $group->level)
        );

        if (!$synced) {
            group_user_response('error', $db->getErrorMessage());
        }
        group_user_response('good', 'Daftar menu role berhasil disinkronkan.');
        break;

    case 'delete':
        $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
        $group = $db->fetch_single_row('sys_group_users', 'id', $id);
        if (!$group) {
            group_user_response('error', 'Role tidak ditemukan.');
        }
        if ($group->level === 'admin') {
            group_user_response('error', 'Role administrator sistem tidak dapat dihapus.');
        }

        $users = $db->query(
            'select count(*) total from sys_users where group_level=?',
            array('group_level' => $id)
        )->fetch();
        if (intval($users->total) > 0) {
            group_user_response('error', 'Role masih digunakan oleh '.$users->total.' user. Pindahkan user ke role lain terlebih dahulu.');
        }

        $db->query('delete from sys_menu_role where group_level=?', array('group_level' => $group->level));
        if (!$db->delete('sys_group_users', 'id', $id)) {
            group_user_response('error', $db->getErrorMessage());
        }
        group_user_response('good', 'Role berhasil dihapus.');
        break;

    default:
        group_user_response('error', 'Aksi tidak dikenali.');
}
?>
