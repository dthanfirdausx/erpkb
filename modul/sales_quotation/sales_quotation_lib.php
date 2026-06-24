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
function sq_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function sq_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function sq_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function sq_username() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  if (isset($_SESSION['profile']['username']) && $_SESSION['profile']['username'] !== '') return $_SESSION['profile']['username'];
  return 'system';
}

function sq_next_no($db) {
  $prefix = 'SQ'.date('Ym');
  $row = $db->fetch("SELECT no_sales_quotation FROM sales_quotation WHERE no_sales_quotation LIKE ? ORDER BY no_sales_quotation DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->no_sales_quotation, $m)) $next = (int)$m[1] + 1;
  return $prefix.sprintf('%05d', $next);
}

function sq_status_label($status) {
  $map = array('OPEN'=>'default','SENT'=>'info','ACCEPTED'=>'success','REJECTED'=>'danger','EXPIRED'=>'warning','CANCELLED'=>'warning');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.sq_h($status ?: 'OPEN').'</span>';
}

function sq_filters() {
  return array(
    'tgl_awal' => sq_input('tgl_awal', date('Y-m-01')),
    'tgl_akhir' => sq_input('tgl_akhir', date('Y-m-d')),
    'customer_id' => sq_input('customer_id'),
    'status' => sq_input('status'),
    'sales_person' => sq_input('sales_person'),
    'keyword' => sq_input('keyword')
  );
}

function sq_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = sq_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $to = sq_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND sq.tgl BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if (!empty($input['customer_id'])) { $where .= " AND sq.customer_id=? "; $params[] = (int)$input['customer_id']; }
  if (!empty($input['status'])) { $where .= " AND sq.status=? "; $params[] = $input['status']; }
  if (!empty($input['sales_person'])) { $where .= " AND sq.sales_id=? "; $params[] = $input['sales_person']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.$input['keyword'].'%';
    $where .= " AND (sq.no_sales_quotation LIKE ? OR sq.customer_name LIKE ? OR c.nama LIKE ? OR sq.kode_penerima LIKE ? OR sq.subject LIKE ? OR sq.contact_person LIKE ? OR sq.catatan LIKE ?) ";
    for ($i=0;$i<7;$i++) $params[] = $kw;
  }
  return $where;
}

function sq_base_sql() {
  return " FROM sales_quotation sq
           LEFT JOIN customer c ON c.id_customer=sq.customer_id OR c.kode_pemasok=sq.kode_penerima
           LEFT JOIN sales_quotation_detail d ON d.id_quotation=sq.id_quotation ";
}

function sq_select_sql() {
  return "SELECT sq.*,
                 COALESCE(NULLIF(sq.customer_name,''),c.nama) AS customer_display,
                 COUNT(d.id_detail) AS item_count,
                 COALESCE(SUM(d.qty),0) AS total_qty,
                 COALESCE(SUM(d.nilai),0) AS total_amount ";
}

function sq_load_rows($db, $input, $limit = 0, $offset = 0) {
  $params = array();
  $where = sq_filter_sql($input, $params);
  $sql = sq_select_sql().sq_base_sql().$where." GROUP BY sq.id_quotation ORDER BY sq.tgl DESC,sq.id_quotation DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}

function sq_count_rows($db, $input) {
  $params = array();
  $where = sq_filter_sql($input, $params);
  $row = $db->fetch("SELECT COUNT(*) AS total FROM sales_quotation sq LEFT JOIN customer c ON c.id_customer=sq.customer_id OR c.kode_pemasok=sq.kode_penerima ".$where, $params);
  return $row ? (int)$row->total : 0;
}

function sq_summary($db, $input) {
  $params = array();
  $where = sq_filter_sql($input, $params);
  return $db->fetch("SELECT COUNT(DISTINCT sq.id_quotation) AS total_docs,
                            COALESCE(SUM(d.qty),0) AS total_qty,
                            COALESCE(SUM(d.nilai),0) AS total_amount,
                            SUM(CASE WHEN sq.status IN ('OPEN','SENT') THEN 1 ELSE 0 END) AS open_docs
                     ".sq_base_sql().$where, $params);
}

function sq_detail_rows($db, $id) {
  return $db->query("SELECT d.*,b.nm_barang,b.satuan
                    FROM sales_quotation_detail d
                    LEFT JOIN barang b ON b.kd_barang=d.kd_barang
                    WHERE d.id_quotation=?
                    ORDER BY d.line_no,d.id_detail", array((int)$id));
}
?>
