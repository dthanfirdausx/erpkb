<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include "config.php";

function login_response($status, $message = '', $extra = array())
{
    echo json_encode(array_merge(array(
        'status' => $status,
        'message' => $message
    ), $extra));
    exit;
}

function verify_user_password($plainPassword, $storedPassword)
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

function login_attempt_is_locked()
{
    if (!isset($_SESSION['login_lock_until'])) {
        return false;
    }

    if ((int) $_SESSION['login_lock_until'] <= time()) {
        unset($_SESSION['login_lock_until'], $_SESSION['login_attempt_count']);
        return false;
    }

    return true;
}

function login_attempt_register_failed()
{
    $maxAttempts = 5;
    $lockSeconds = 15 * 60;

    if (!isset($_SESSION['login_attempt_count'])) {
        $_SESSION['login_attempt_count'] = 0;
    }

    $_SESSION['login_attempt_count']++;

    if ($_SESSION['login_attempt_count'] >= $maxAttempts) {
        $_SESSION['login_lock_until'] = time() + $lockSeconds;
    }
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    login_response('error', erp_t('login_request_invalid', 'Invalid login request.'));
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

if (empty($_SESSION['login_csrf_token']) || !hash_equals($_SESSION['login_csrf_token'], $csrfToken)) {
    login_response('error', erp_t('login_session_invalid', 'Login session is invalid. Refresh the page and try again.'));
}

if ($username === '' || $password === '') {
    login_response('error', erp_t('login_required', 'Username and password are required.'));
}

if (login_attempt_is_locked()) {
    login_response('error', erp_t('login_too_many_attempts', 'Too many login attempts. Try again in a few minutes.'));
}

try {
    $dt = $db->fetch("SELECT * FROM sys_users WHERE username=? LIMIT 1", array($username));

    if (!$dt || !verify_user_password($password, $dt->password)) {
        login_attempt_register_failed();
        simpan_log("Percobaan login gagal untuk username ".$username, $username);
        login_response('error', erp_t('login_invalid_default', 'Username or password does not match'));
    }

    if (isset($dt->aktif) && strtoupper((string) $dt->aktif) === 'N') {
        login_attempt_register_failed();
        simpan_log("Percobaan login user nonaktif untuk username ".$username, $username);
        login_response('error', erp_t('login_user_inactive', 'User is inactive. Please contact the administrator.'));
    }

    $group_dt = $db->fetch_single_row('sys_group_users', 'id', $dt->group_level);
    if (!$group_dt) {
        simpan_log("Login gagal karena group user tidak ditemukan untuk username ".$username, $username);
        login_response('error', erp_t('login_group_invalid', 'User group is not valid. Please contact the administrator.'));
    }

    session_regenerate_id(true);
    unset($_SESSION['login_attempt_count'], $_SESSION['login_lock_until']);

    $_SESSION['group_level'] = $group_dt->level;
    $_SESSION['id_user'] = $dt->id;
    $_SESSION['login'] = 1;
    $_SESSION['username'] = $dt->username;
    $_SESSION['IKB4_status_UserName'] = $dt->username;
    $_SESSION['nama'] = trim($dt->first_name." ".$dt->last_name);
    $_SESSION['level'] = $dt->group_level;

    simpan_log("User ".$_SESSION['username']." login ke aplikasi pada ".date('Y-m-d H:i:s'), $_SESSION['username']);
    login_response('success', erp_t('login_success', 'Login successful.'));
} catch (Exception $e) {
    login_response('error', erp_t('login_error', 'An error occurred during login.'));
}
