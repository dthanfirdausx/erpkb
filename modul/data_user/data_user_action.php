<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();

function data_user_json($status, $message = '', $extra = array())
{
    $payload = array('status' => $status);
    if ($message !== '') {
        $payload['error_message'] = $message;
        $payload['message'] = $message;
    }
    foreach ($extra as $key => $value) {
        $payload[$key] = $value;
    }
    echo json_encode(array($payload));
    exit();
}

function data_user_can_impersonate()
{
    if (!isset($_SESSION['group_level'])) {
        return false;
    }
    if (in_array($_SESSION['group_level'], array('admin', 'system_administrator'), true)) {
        return true;
    }
    return isset($_SESSION['impersonator']['group_level'])
        && in_array($_SESSION['impersonator']['group_level'], array('admin', 'system_administrator'), true);
}

function data_user_apply_session($db, $user)
{
    $group = $db->fetch_single_row('sys_group_users', 'id', $user->group_level);
    if (!$group) {
        data_user_json('error', 'Group user target tidak ditemukan.');
    }

    $_SESSION['group_level'] = $group->level;
    $_SESSION['id_user'] = $user->id;
    $_SESSION['login'] = 1;
    $_SESSION['username'] = $user->username;
    $_SESSION['IKB4_status_UserName'] = $user->username;
    $_SESSION['nama'] = trim($user->first_name.' '.$user->last_name);
    $_SESSION['level'] = $user->group_level;
}

function data_user_app_index()
{
    return base_url().'index.php/';
}

switch ($_GET["act"]) {
  case "login_as":
    if (!data_user_can_impersonate()) {
      data_user_json('error', 'Akses ditolak. Fitur Login As hanya untuk super admin.');
    }

    $targetId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($targetId <= 0) {
      data_user_json('error', 'User target tidak valid.');
    }

    $target = $db->fetch_single_row('sys_users', 'id', $targetId);
    if (!$target) {
      data_user_json('error', 'User target tidak ditemukan.');
    }
    if (isset($_SESSION['id_user']) && (int) $_SESSION['id_user'] === $targetId && empty($_SESSION['impersonator'])) {
      data_user_json('error', 'Anda sudah login sebagai user ini.');
    }

    if (empty($_SESSION['impersonator'])) {
      $_SESSION['impersonator'] = array(
        'id_user' => isset($_SESSION['id_user']) ? $_SESSION['id_user'] : '',
        'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
        'nama' => isset($_SESSION['nama']) ? $_SESSION['nama'] : '',
        'group_level' => isset($_SESSION['group_level']) ? $_SESSION['group_level'] : '',
        'level' => isset($_SESSION['level']) ? $_SESSION['level'] : '',
        'IKB4_status_UserName' => isset($_SESSION['IKB4_status_UserName']) ? $_SESSION['IKB4_status_UserName'] : ''
      );
    }

    $adminUser = isset($_SESSION['impersonator']['username']) ? $_SESSION['impersonator']['username'] : $_SESSION['username'];
    data_user_apply_session($db, $target);
    simpan_log($adminUser.' login as user '.$target->username.' pada '.date('Y-m-d H:i:s'), $adminUser);
    data_user_json('good', '', array('redirect' => data_user_app_index(), 'target_username' => $target->username));
    break;

  case "stop_login_as":
    if (empty($_SESSION['impersonator'])) {
      data_user_json('error', 'Session Login As tidak aktif.');
    }

    $impersonatedUser = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $original = $_SESSION['impersonator'];
    $originalUser = $db->fetch_single_row('sys_users', 'id', $original['id_user']);
    if (!$originalUser) {
      unset($_SESSION['impersonator']);
      data_user_json('error', 'User admin asal tidak ditemukan. Silakan login ulang.');
    }

    unset($_SESSION['impersonator']);
    data_user_apply_session($db, $originalUser);
    simpan_log($_SESSION['username'].' kembali dari login as user '.$impersonatedUser.' pada '.date('Y-m-d H:i:s'), $_SESSION['username']);
    data_user_json('good', '', array('redirect' => data_user_app_index().'data-user'));
    break;

  case "in":
    mdg_required(array("first_name"=>"Nama depan","username"=>"Username","password"=>"Password","group_level"=>"Group user"));
    $_POST["username"] = strtolower(mdg_trim("username"));
    if (mdg_exists($db, "sys_users", "username", $_POST["username"])) {
      mdg_error("Username ".$_POST["username"]." sudah digunakan.");
    }
    $group = $db->fetch_single_row("sys_group_users", "id", (int) $_POST["group_level"]);
    if (!$group) {
      mdg_error("Group user tidak valid.");
    }
    if (!is_dir("../../../upload/data_user")) {
      mkdir("../../../upload/data_user", 0775, true);
    }
  $data = array(
      "first_name" => mdg_trim("first_name"),
      "last_name" => mdg_trim("last_name"),
      "username" => $_POST["username"],
      "password" => $_POST["password"],
      "email" => mdg_trim("email"),
      "group_level" => (int) $_POST["group_level"],
  );
    if (!empty($_FILES["foto_user"]["name"])) {
      if (!preg_match("/\.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto_user"]["name"]) ) {
        mdg_error("Pastikan file foto png, jpg, jpeg, gif, atau bmp.");
      }
      $db->compressImage($_FILES["foto_user"]["type"],$_FILES["foto_user"]["tmp_name"],"../../../upload/data_user/",$_FILES["foto_user"]["name"],300,);
      $data = array_merge($data, array("foto_user"=>$_FILES["foto_user"]["name"]));
    }
          if(isset($_POST["aktif"]) && $_POST["aktif"]=="on")
          {
            $aktif = array("aktif"=>"Y");
            $data=array_merge($data,$aktif);
          } else {
            $aktif = array("aktif"=>"N");
            $data=array_merge($data,$aktif);
          }
    $in = $db->insert("sys_users",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    $id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
    if ($id <= 0) mdg_error("User tidak valid.");
    if (isset($_SESSION["id_user"]) && (int)$_SESSION["id_user"] === $id) {
      mdg_error("User yang sedang login tidak boleh dihapus.");
    }
    $user = $db->fetch_single_row("sys_users","id",$id);
    if (!$user) mdg_error("User tidak ditemukan.");
    if ($user->foto_user && $user->foto_user !== "default_user.png" && $user->foto_user !== "default-user-neutral.svg") {
      $db->deleteDirectory("../../../upload/data_user/".$user->foto_user);
    }
    $db->delete("sys_users","id",$id);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          if (isset($_SESSION["id_user"]) && (int)$_SESSION["id_user"] === (int)$id) {
            mdg_error("User yang sedang login tidak boleh dihapus.");
          }
          $db->delete("sys_users","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    mdg_required(array("id"=>"ID user","first_name"=>"Nama depan","username"=>"Username","group_level"=>"Group user"));
    $_POST["username"] = strtolower(mdg_trim("username"));
    if (mdg_exists($db, "sys_users", "username", $_POST["username"], "id", $_POST["id"])) {
      mdg_error("Username ".$_POST["username"]." sudah digunakan.");
    }
    $group = $db->fetch_single_row("sys_group_users", "id", (int) $_POST["group_level"]);
    if (!$group) {
      mdg_error("Group user tidak valid.");
    }
   $data = array(
      "first_name" => mdg_trim("first_name"),
      "last_name" => mdg_trim("last_name"),
      "username" => $_POST["username"],
      "password" => $_POST["password"],
      "email" => mdg_trim("email"),
      "group_level" => (int) $_POST["group_level"],
   );
   
   
                         if(!empty($_FILES["foto_user"]["name"])) {
                        if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto_user"]["name"]) ) {
              mdg_error("Pastikan file yang anda pilih gambar.");

            } else {
              if (!is_dir("../../../upload/data_user")) {
                mkdir("../../../upload/data_user", 0775, true);
              }
$db->compressImage($_FILES["foto_user"]["type"],$_FILES["foto_user"]["tmp_name"],"../../../upload/data_user/",$_FILES["foto_user"]["name"],300,);
              $oldUser = $db->fetch_single_row("sys_users","id",$_POST["id"]);
              if ($oldUser && $oldUser->foto_user && $oldUser->foto_user !== "default_user.png" && $oldUser->foto_user !== "default-user-neutral.svg") {
                $db->deleteDirectory("../../../upload/data_user/".$oldUser->foto_user);
              }
              $foto_user = array("foto_user"=>$_FILES["foto_user"]["name"]);
              $data = array_merge($data,$foto_user);
            }

                         }
   

    
          if(isset($_POST["aktif"]) && $_POST["aktif"]=="on")
          {
            $aktif = array("aktif"=>"Y");
            $data=array_merge($data,$aktif);
          } else {
            $aktif = array("aktif"=>"N");
            $data=array_merge($data,$aktif);
          }
    
    $up = $db->update("sys_users",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
