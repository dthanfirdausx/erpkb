<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function mp_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') {
    if ($status === 'good') {
      $payload['message'] = $message;
    } else {
      $payload['error_message'] = $message;
    }
  }
  foreach ($extra as $key => $value) {
    $payload[$key] = $value;
  }
  echo json_encode(array($payload));
  exit;
}

function mp_current_user_id()
{
  return isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
}

function mp_current_employee()
{
  global $db;
  $userId = mp_current_user_id();
  if ($userId <= 0) return null;
  return $db->fetch("SELECT e.id, e.employee_no, e.full_name, u.username, u.foto_user
    FROM erp_employee_master e
    JOIN sys_users u ON u.id=e.user_id
    WHERE e.user_id=? LIMIT 1", array($userId));
}

function mp_upload_photo()
{
  global $db;
  $employee = mp_current_employee();
  if (!$employee) {
    mp_json('error', 'Data karyawan untuk user aktif belum terhubung.');
  }
  if (!isset($_FILES['foto_user']) || !is_array($_FILES['foto_user']) || $_FILES['foto_user']['error'] === UPLOAD_ERR_NO_FILE) {
    mp_json('error', 'Pilih file foto terlebih dahulu.');
  }

  $file = $_FILES['foto_user'];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    mp_json('error', 'Upload foto gagal. Kode error: '.$file['error']);
  }
  if ((int) $file['size'] > 3 * 1024 * 1024) {
    mp_json('error', 'Ukuran foto maksimal 3MB.');
  }

  $original = isset($file['name']) ? $file['name'] : 'profile';
  $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
  $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
  if (!in_array($ext, $allowedExt, true)) {
    mp_json('error', 'Format foto harus jpg, jpeg, png, gif, atau webp.');
  }

  $mime = '';
  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
      $mime = finfo_file($finfo, $file['tmp_name']);
    }
  }
  $allowedMime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
  if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
    mp_json('error', 'Tipe file tidak valid: '.$mime);
  }

  $uploadDir = rtrim(SITE_ROOT, '/').'/upload/back_profil_foto';
  if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true)) {
    mp_json('error', 'Folder upload foto tidak bisa dibuat.');
  }

  $fileName = 'ess_profile_'.mp_current_user_id().'_'.date('YmdHis').'_'.mt_rand(1000, 9999).'.'.$ext;
  $target = $uploadDir.'/'.$fileName;
  $uploaded = @move_uploaded_file($file['tmp_name'], $target);
  if (!$uploaded && PHP_SAPI === 'cli') {
    $uploaded = @copy($file['tmp_name'], $target);
  }
  if (!$uploaded) {
    mp_json('error', 'Foto gagal disimpan.');
  }

  $old = basename((string) $employee->foto_user);
  $protected = array('', 'default_user.png', 'default-user-neutral.svg');
  if (!in_array($old, $protected, true)) {
    $oldPath = $uploadDir.'/'.$old;
    if (is_file($oldPath)) {
      @unlink($oldPath);
    }
  }

  $db->update('sys_users', array('foto_user' => $fileName), 'id', mp_current_user_id());
  if ($db->getErrorMessage() !== '') {
    @unlink($target);
    mp_json('error', $db->getErrorMessage());
  }

  $username = isset($_SESSION['username']) ? $_SESSION['username'] : $employee->username;
  simpan_log($username.' mengganti foto profil employee '.$employee->employee_no.' - '.$employee->full_name.' pada '.date('Y-m-d H:i:s'), $username);
  mp_json('good', 'Foto profil berhasil diperbarui.', array(
    'photo_url' => erpkb_user_photo_url($fileName, 'back_profil_foto'),
    'file_name' => $fileName
  ));
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act) {
  case 'upload_photo':
    mp_upload_photo();
    break;
  default:
    mp_json('error', 'Aksi tidak dikenal.');
}
