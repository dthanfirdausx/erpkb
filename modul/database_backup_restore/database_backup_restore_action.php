<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . "/../../inc/config.php";
session_check_json();

function dbr_json($status, $message, $extra = array()) {
  header('Content-Type: application/json');
  echo json_encode(array_merge(array('status'=>$status,'message'=>$message,'error_message'=>$message), $extra));
  exit;
}
function dbr_can_manage() {
  return isset($_SESSION['group_level']) && in_array($_SESSION['group_level'], array('admin','system_administrator'), true);
}
function dbr_quote_ident($name) {
  return '`'.str_replace('`','``',$name).'`';
}
function dbr_pdo() {
  global $host, $port, $db_username, $db_password, $db_name;
  $dsn = strpos($host, '/') === 0
    ? 'mysql:unix_socket='.$host.';dbname='.$db_name.';charset=utf8'
    : 'mysql:host='.$host.';port='.$port.';dbname='.$db_name.';charset=utf8';
  $pdo = new PDO($dsn, $db_username, $db_password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
  return $pdo;
}
function dbr_backup_dir() {
  $dir = __DIR__ . '/../../upload/db_backup';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);
  return $dir;
}
function dbr_export_sql($db, $pdo, $dbName, $targetFile = null) {
  $out = "-- ERPKB Database Backup\n";
  $out .= "-- Database: ".$dbName."\n";
  $out .= "-- Generated: ".date('Y-m-d H:i:s')."\n\n";
  $out .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
  $writer = null;
  if ($targetFile) {
    $writer = fopen($targetFile, 'w');
    if (!$writer) throw new Exception('Tidak bisa menulis file backup sementara.');
    fwrite($writer, $out);
    $out = '';
  }
  foreach ($db->query("SELECT table_name FROM information_schema.TABLES WHERE table_schema=DATABASE() AND table_type='BASE TABLE' ORDER BY table_name") as $tableRow) {
    $table = $tableRow->table_name;
    $create = $db->fetch("SHOW CREATE TABLE ".dbr_quote_ident($table));
    $createArr = (array)$create;
    $createSql = isset($createArr['Create Table']) ? $createArr['Create Table'] : array_pop($createArr);
    $chunk = "\nDROP TABLE IF EXISTS ".dbr_quote_ident($table).";\n".$createSql.";\n\n";
    if ($writer) fwrite($writer, $chunk); else $out .= $chunk;
    $rows = $db->query("SELECT * FROM ".dbr_quote_ident($table));
    if (!$rows) continue;
    foreach ($rows as $row) {
      $data = (array)$row;
      $cols = array_map('dbr_quote_ident', array_keys($data));
      $vals = array();
      foreach ($data as $value) {
        $vals[] = $value === null ? 'NULL' : $pdo->quote($value);
      }
      $line = "INSERT INTO ".dbr_quote_ident($table)." (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
      if ($writer) fwrite($writer, $line); else $out .= $line;
    }
  }
  $tail = "\nSET FOREIGN_KEY_CHECKS=1;\n";
  if ($writer) { fwrite($writer, $tail); fclose($writer); return $targetFile; }
  return $out.$tail;
}
function dbr_split_sql($sql) {
  $statements = array();
  $buffer = '';
  $quote = null;
  $escape = false;
  $len = strlen($sql);
  for ($i=0; $i<$len; $i++) {
    $ch = $sql[$i];
    $next = $i+1 < $len ? $sql[$i+1] : '';
    if ($quote === null && $ch === '-' && $next === '-') {
      while ($i < $len && $sql[$i] !== "\n") $i++;
      continue;
    }
    if ($quote === null && $ch === '#') {
      while ($i < $len && $sql[$i] !== "\n") $i++;
      continue;
    }
    if ($quote === null && $ch === '/' && $next === '*') {
      $i += 2;
      while ($i < $len-1 && !($sql[$i] === '*' && $sql[$i+1] === '/')) $i++;
      $i++;
      continue;
    }
    $buffer .= $ch;
    if ($escape) { $escape = false; continue; }
    if ($ch === '\\') { $escape = true; continue; }
    if (($ch === "'" || $ch === '"') && $quote === null) { $quote = $ch; continue; }
    if ($quote === $ch) { $quote = null; continue; }
    if ($quote === null && $ch === ';') {
      $stmt = trim(substr($buffer, 0, -1));
      if ($stmt !== '') $statements[] = $stmt;
      $buffer = '';
    }
  }
  $last = trim($buffer);
  if ($last !== '') $statements[] = $last;
  return $statements;
}

if (!dbr_can_manage()) dbr_json('error', 'Anda tidak punya akses Backup Restore Database.');
$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

try {
  if ($act === 'backup') {
    $pdo = dbr_pdo();
    $dbNameRow = $db->fetch("SELECT DATABASE() db_name");
    $dbName = $dbNameRow ? $dbNameRow->db_name : 'erpkb';
    $filename = 'erpkb_backup_'.$dbName.'_'.date('Ymd_His').'.sql';
    if (function_exists('simpan_log')) simpan_log('User '.$username.' download backup database '.$dbName.' pada '.date('Y-m-d H:i:s'), $username);
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    dbr_export_sql($db, $pdo, $dbName, 'php://output');
    exit;
  }

  if ($act === 'restore') {
    if (!isset($_POST['confirm_restore']) || $_POST['confirm_restore'] !== 'Y') dbr_json('error', 'Konfirmasi restore wajib dicentang.');
    if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) dbr_json('error', 'File SQL wajib diupload.');
    $ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') dbr_json('error', 'File restore harus format .sql.');
    if ($_FILES['sql_file']['size'] > 100 * 1024 * 1024) dbr_json('error', 'Ukuran file SQL maksimal 100MB.');

    $dbNameRow = $db->fetch("SELECT DATABASE() db_name");
    $dbName = $dbNameRow ? $dbNameRow->db_name : 'erpkb';
    $pdo = dbr_pdo();
    $safetyFile = dbr_backup_dir().'/before_restore_'.$dbName.'_'.date('Ymd_His').'.sql';
    dbr_export_sql($db, $pdo, $dbName, $safetyFile);

    $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
    if ($sql === false || trim($sql) === '') dbr_json('error', 'File SQL kosong atau tidak bisa dibaca.');
    $statements = dbr_split_sql($sql);
    $executed = 0;
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($statements as $stmt) {
      if (trim($stmt) === '') continue;
      try {
        $pdo->exec($stmt);
      } catch (Exception $e) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        dbr_json('error', 'Restore gagal pada statement ke-'.($executed+1).': '.$e->getMessage(), array('safety_backup'=>basename($safetyFile)));
      }
      $executed++;
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    if (function_exists('simpan_log')) simpan_log('User '.$username.' restore database '.$dbName.' dari file '.$_FILES['sql_file']['name'].' pada '.date('Y-m-d H:i:s').'. Safety backup: '.basename($safetyFile), $username);
    dbr_json('good', 'Restore berhasil. '.$executed.' statement dieksekusi. Safety backup dibuat: '.basename($safetyFile), array('executed'=>$executed));
  }

  dbr_json('error', 'Action tidak dikenal.');
} catch (Exception $e) {
  dbr_json('error', $e->getMessage());
}
?>
