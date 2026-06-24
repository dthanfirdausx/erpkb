<?php
if (!function_exists('mdg_trim')) {
  function mdg_trim($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
  }

  function mdg_error($message) {
    action_response($message);
  }

  function mdg_required($fields) {
    foreach ($fields as $field => $label) {
      if (mdg_trim($field) === '') {
        mdg_error($label.' wajib diisi.');
      }
    }
  }

  function mdg_exists($db, $table, $column, $value, $excludeColumn = '', $excludeValue = '') {
    $sql = "SELECT COUNT(*) total FROM `$table` WHERE `$column`=?";
    $params = array($value);
    if ($excludeColumn !== '' && $excludeValue !== '') {
      $sql .= " AND `$excludeColumn`<>?";
      $params[] = $excludeValue;
    }
    $row = $db->fetch($sql, $params);
    return $row && (int)$row->total > 0;
  }

  function mdg_table_has_column($db, $table, $column) {
    $row = $db->fetch(
      "SELECT COUNT(*) total FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?",
      array($table, $column)
    );
    return $row && (int)$row->total > 0;
  }

  function mdg_table_exists($db, $table) {
    $row = $db->fetch(
      "SELECT COUNT(*) total FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?",
      array($table)
    );
    return $row && (int)$row->total > 0;
  }

  function mdg_used($db, $table, $column, $value) {
    if (!mdg_table_exists($db, $table) || !mdg_table_has_column($db, $table, $column)) {
      return false;
    }
    $row = $db->fetch("SELECT COUNT(*) total FROM `$table` WHERE `$column`=? LIMIT 1", array($value));
    return $row && (int)$row->total > 0;
  }

  function mdg_block_delete_if_used($db, $label, $value, $checks) {
    foreach ($checks as $check) {
      if (mdg_used($db, $check[0], $check[1], $value)) {
        mdg_error($label.' sudah dipakai di '.$check[0].'. Data master tidak boleh dihapus, ubah status menjadi nonaktif.');
      }
    }
  }
}
?>
