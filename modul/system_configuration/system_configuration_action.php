<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . "/../../inc/config.php";
session_check_json();

function sc_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json');
  $row = array('status' => $status);
  if ($message !== '') {
    $row['message'] = $message;
    $row['error_message'] = $message;
  }
  foreach ((array)$extra as $key => $value) $row[$key] = $value;
  echo json_encode($row);
  exit;
}

function sc_can_update() {
  if (isset($_SESSION['group_level']) && in_array($_SESSION['group_level'], array('admin', 'system_administrator'), true)) {
    return true;
  }
  return false;
}

function sc_clean($value) {
  return trim((string)$value);
}

function sc_validate_value($row, $value) {
  $type = strtoupper((string)$row->value_type);
  if ($type === 'BOOLEAN') {
    return in_array(strtoupper($value), array('Y', 'N'), true) ? strtoupper($value) : 'N';
  }
  if ($type === 'NUMBER') {
    if ($value === '') return '0';
    if (!is_numeric($value)) sc_json('error', $row->config_label.' wajib angka.');
    return (string)(int)$value;
  }
  if ($type === 'DECIMAL') {
    if ($value === '') return '0';
    if (!is_numeric($value)) sc_json('error', $row->config_label.' wajib angka decimal.');
    return (string)(float)$value;
  }
  if ($type === 'EMAIL' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
    sc_json('error', $row->config_label.' harus format email valid.');
  }
  if ($type === 'URL' && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
    sc_json('error', $row->config_label.' harus format URL valid.');
  }
  if ($type === 'SELECT') {
    $options = json_decode((string)$row->options_json, true);
    if (is_array($options) && !empty($options) && !in_array($value, $options, true)) {
      sc_json('error', $row->config_label.' tidak ada dalam opsi konfigurasi.');
    }
  }
  return $value;
}

function sc_separator_char($value) {
  if ($value === 'SPACE') return ' ';
  if ($value === 'NONE') return '';
  return (string)$value;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

switch ($act) {
  case 'save':
    if (!sc_can_update()) sc_json('error', 'Anda tidak punya akses update System Configuration.');
    if (empty($_POST['config']) || !is_array($_POST['config'])) sc_json('error', 'Tidak ada konfigurasi yang dikirim.');

    $rows = array();
    foreach ($db->query("SELECT * FROM erp_system_config ORDER BY config_group, sort_order, config_key") as $row) {
      $rows[$row->config_key] = $row;
    }

    $incoming = $_POST['config'];
    $thousand = isset($incoming['number_thousand_separator'])
      ? sc_separator_char(sc_clean($incoming['number_thousand_separator']))
      : sc_separator_char(isset($rows['number_thousand_separator']) ? (($rows['number_thousand_separator']->config_value !== '' && $rows['number_thousand_separator']->config_value !== null) ? $rows['number_thousand_separator']->config_value : $rows['number_thousand_separator']->default_value) : '.');
    $decimal = isset($incoming['number_decimal_separator'])
      ? sc_separator_char(sc_clean($incoming['number_decimal_separator']))
      : sc_separator_char(isset($rows['number_decimal_separator']) ? (($rows['number_decimal_separator']->config_value !== '' && $rows['number_decimal_separator']->config_value !== null) ? $rows['number_decimal_separator']->config_value : $rows['number_decimal_separator']->default_value) : ',');
    if ($decimal === '') {
      sc_json('error', 'Pemisah desimal tidak boleh kosong.');
    }
    if ($thousand !== '' && $thousand === $decimal) {
      sc_json('error', 'Separator ribuan dan pemisah desimal tidak boleh sama.');
    }

    $updated = 0;
    $db->query('START TRANSACTION');
    foreach ($_POST['config'] as $key => $value) {
      $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
      if (!isset($rows[$key])) continue;
      $row = $rows[$key];
      $value = sc_clean($value);
      if ($row->is_sensitive === 'Y' && $value === '') continue;
      $value = sc_validate_value($row, $value);
      $ok = $db->query(
        "UPDATE erp_system_config SET config_value=?, updated_by=?, updated_at=NOW() WHERE config_key=?",
        array($value, $username, $key)
      );
      if (!$ok) {
        $err = $db->getErrorMessage();
        $db->query('ROLLBACK');
        sc_json('error', $err ? $err : 'Konfigurasi gagal disimpan.');
      }
      $updated++;
    }
    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' update '.$updated.' parameter System Configuration pada '.date('Y-m-d H:i:s'), $username);
    }
    $db->query('COMMIT');
    sc_json('good', 'System Configuration berhasil disimpan. '.$updated.' parameter diproses.', array('updated' => $updated));
    break;

  case 'reset_group':
    if (!sc_can_update()) sc_json('error', 'Anda tidak punya akses reset System Configuration.');
    $group = isset($_POST['group']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['group']) : '';
    if ($group === '') sc_json('error', 'Group konfigurasi wajib dipilih.');
    $ok = $db->query(
      "UPDATE erp_system_config SET config_value=default_value, updated_by=?, updated_at=NOW() WHERE config_group=?",
      array($username, $group)
    );
    if (!$ok) sc_json('error', $db->getErrorMessage() ?: 'Reset konfigurasi gagal.');
    if (function_exists('simpan_log')) {
      simpan_log('User '.$username.' reset group System Configuration '.$group.' pada '.date('Y-m-d H:i:s'), $username);
    }
    sc_json('good', 'Group '.$group.' berhasil dikembalikan ke default.');
    break;

  default:
    sc_json('error', 'Action tidak dikenal.');
}
?>
