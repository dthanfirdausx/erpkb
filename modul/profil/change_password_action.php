<?php
session_start();
include "../../inc/config.php";

function cp_response($status, $message)
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array(
    'status' => $status,
    'message' => $message
  ));
  exit;
}

function cp_verify_password($plainPassword, $storedPassword)
{
  $storedPassword = (string) $storedPassword;

  if ($storedPassword === '') {
    return false;
  }

  if (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword)) {
    return hash_equals(strtolower($storedPassword), md5($plainPassword));
  }

  return password_verify($plainPassword, $storedPassword);
}

function cp_password_score($password)
{
  $score = 0;
  if (strlen($password) >= 8) $score++;
  if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score++;
  if (preg_match('/[0-9]/', $password)) $score++;
  if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
  return $score;
}

if (empty($_SESSION['login'])) {
  cp_response('error', erp_t('session_expired_relogin', 'Your session has expired. Please sign in again.'));
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

switch ($act) {
  case "up_prof":
    $data = array(
      "first_name" => $_POST["first_name"],
      "last_name" => $_POST["last_name"],
      "username" => $_POST["username"],
      "email" => $_POST["email"],
    );

    if (isset($_FILES["foto_user"]["name"]) && $_FILES["foto_user"]["name"] !== '') {
      if (!preg_match("/\.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto_user"]["name"])) {
        echo "pastikan file yang anda pilih gambar";
        exit();
      }

      $db->compressImage($_FILES["foto_user"]["type"], $_FILES["foto_user"]["tmp_name"], "../../../upload/user/", $_FILES["foto_user"]["name"], 200);
      if (file_exists("../../../upload/user/".$db->fetch_single_row("sys_users", "id", $_POST["id"])->foto_user)) {
        $db->deleteDirectory("../../../upload/user/".$db->fetch_single_row("sys_users", "id", $_POST["id"])->foto_user);
      }
      $data = array_merge($data, array("foto_user" => $_FILES["foto_user"]["name"]));
    }

    $up = $db->update("sys_users", $data, "id", $_POST["id"]);
    echo $up ? "good" : "false";
    break;

  case "delete":
    $db->delete("sys_users", "id", $_GET["id"]);
    break;

  case "up":
    $userId = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
    $postedId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $oldPassword = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $newPassword = isset($_POST['password_baru']) ? (string) $_POST['password_baru'] : '';
    $confirmPassword = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
    $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

    if ($userId <= 0 || $postedId !== $userId) {
      cp_response('error', erp_t('password_user_session_invalid', 'User session is invalid. Please sign in again.'));
    }

    if (empty($_SESSION['change_password_csrf_token']) || !hash_equals($_SESSION['change_password_csrf_token'], $csrfToken)) {
      cp_response('error', erp_t('password_csrf_invalid', 'Change password session is invalid. Refresh the page and try again.'));
    }

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
      cp_response('error', erp_t('password_required', 'All password fields are required.'));
    }

    if ($newPassword !== $confirmPassword) {
      cp_response('error', erp_t('password_confirm_mismatch', 'New password confirmation does not match.'));
    }

    if (strlen($newPassword) < 8) {
      cp_response('error', erp_t('password_min_length', 'New password must be at least 8 characters.'));
    }

    if (cp_password_score($newPassword) < 2) {
      cp_response('error', erp_t('password_too_weak', 'New password is too weak. Use a combination of letters and numbers.'));
    }

    if (hash_equals($oldPassword, $newPassword)) {
      cp_response('error', erp_t('password_same_as_old', 'New password cannot be the same as the old password.'));
    }

    $user = $db->fetch("SELECT id, username, password FROM sys_users WHERE id=? LIMIT 1", array($userId));
    if (!$user) {
      cp_response('error', erp_t('password_user_not_found', 'User was not found. Please sign in again.'));
    }

    if (!cp_verify_password($oldPassword, $user->password)) {
      simpan_log("User ".$_SESSION['username']." gagal mengubah password karena password lama salah", $_SESSION['username']);
      cp_response('error', erp_t('password_old_incorrect', 'Your current password is incorrect.'));
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $up = $db->update("sys_users", array("password" => $hashedPassword), "id", $userId);

    if (!$up) {
      cp_response('error', erp_t('password_change_failed_try_again', 'Password failed to change. Please try again.'));
    }

    simpan_log("User ".$_SESSION['username']." mengubah password akun pada ".date('Y-m-d H:i:s'), $_SESSION['username']);
    session_destroy();
    cp_response('success', erp_t('password_changed_relogin', 'Password changed successfully. Please sign in again.'));
    break;

  default:
    cp_response('error', erp_t('common_unknown_action', 'Unknown action.'));
    break;
}
