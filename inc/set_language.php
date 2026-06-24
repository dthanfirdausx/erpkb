<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include __DIR__ . "/config.php";

$languages = erpkb_available_languages();
$langCode = isset($_POST['language']) ? trim($_POST['language']) : '';
$redirectTo = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '';

if (!isset($languages[$langCode])) {
  $langCode = 'en';
}

$_SESSION['language'] = $langCode;

if (!empty($_SESSION['username'])) {
  $db->update('sys_users', array('lang' => $langCode), 'username', $_SESSION['username']);
}

if ($redirectTo === '' || preg_match('/^https?:\/\//i', $redirectTo) || strpos($redirectTo, "\n") !== false || strpos($redirectTo, "\r") !== false) {
  $redirectTo = base_index();
}

redirect($redirectTo);
?>
