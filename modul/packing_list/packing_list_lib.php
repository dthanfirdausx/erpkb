<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function pl_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function pl_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function pl_date($value, $default) {
  $value = trim((string)$value);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
}
function pl_num($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function pl_user() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  return 'system';
}
function pl_status_label($status) {
  $map = array('CREATED'=>'default','PACKED'=>'success','CANCELLED'=>'danger');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.pl_h($status ?: '-').'</span>';
}
function pl_delivery_label($status) {
  $map = array('CREATED'=>'default','PICKING'=>'info','PICKED'=>'primary','PACKED'=>'warning','PGI'=>'success','COMPLETED'=>'success','CANCELLED'=>'danger');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.pl_h($status ?: '-').'</span>';
}
function pl_filters() {
  return array(
    'tgl_awal' => pl_input('tgl_awal', date('Y-01-01')),
    'tgl_akhir' => pl_input('tgl_akhir', date('Y-m-d')),
    'customer' => pl_input('customer', 'all'),
    'status' => pl_input('status', 'all'),
    'keyword' => pl_input('keyword')
  );
}
function pl_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = pl_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-01-01'));
  $to = pl_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND COALESCE(pl.tgl_sj,DATE(pl.date_created)) BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if (!empty($input['customer']) && $input['customer'] !== 'all') { $where .= " AND pl.penerima=? "; $params[] = $input['customer']; }
  if (!empty($input['status']) && $input['status'] !== 'all') { $where .= " AND pl.status=? "; $params[] = $input['status']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.$input['keyword'].'%';
    $where .= " AND (pl.no_packing_list LIKE ? OR pl.delivery_no LIKE ? OR pl.picking_no LIKE ? OR pl.no_sj LIKE ? OR pl.no_po LIKE ? OR pl.no_invoice LIKE ? OR p.nama LIKE ? OR pl.vehicle_no LIKE ?) ";
    for ($i = 0; $i < 8; $i++) $params[] = $kw;
  }
  return $where;
}
function pl_base_sql() {
  return " FROM packing_list pl
           LEFT JOIN penerima p ON p.kode_penerima=pl.penerima
           LEFT JOIN (
             SELECT packing_list_id,COUNT(*) item_count,COALESCE(SUM(jumlah),0) packed_qty
             FROM packing_list_detail WHERE packing_list_id IS NOT NULL GROUP BY packing_list_id
           ) d ON d.packing_list_id=pl.id
           LEFT JOIN (
             SELECT no_sj,COUNT(*) item_count,COALESCE(SUM(jumlah),0) packed_qty
             FROM packing_list_detail WHERE packing_list_id IS NULL GROUP BY no_sj
           ) dl ON dl.no_sj=pl.no_sj ";
}
function pl_select_sql() {
  return "SELECT pl.*,p.nama AS customer_name,COALESCE(d.item_count,dl.item_count,0) item_count,COALESCE(d.packed_qty,dl.packed_qty,0) packed_qty ";
}
function pl_load_rows($db, $input, $limit = 0, $offset = 0) {
  $params = array(); $where = pl_filter_sql($input, $params);
  $sql = pl_select_sql().pl_base_sql().$where." ORDER BY COALESCE(pl.tgl_sj,DATE(pl.date_created)) DESC,pl.id DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}
function pl_count_rows($db, $input) {
  $params = array(); $where = pl_filter_sql($input, $params);
  $row = $db->fetch("SELECT COUNT(*) total ".pl_base_sql().$where, $params);
  return $row ? (int)$row->total : 0;
}
function pl_summary($db, $input) {
  $params = array(); $where = pl_filter_sql($input, $params);
  return $db->fetch("SELECT COUNT(*) total_docs,
                            SUM(CASE WHEN pl.status='PACKED' THEN 1 ELSE 0 END) packed_docs,
                            SUM(CASE WHEN pl.status='CANCELLED' THEN 1 ELSE 0 END) cancelled_docs,
                            COALESCE(SUM(COALESCE(d.packed_qty,dl.packed_qty,0)),0) packed_qty
                     ".pl_base_sql().$where, $params);
}
?>
