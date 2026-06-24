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
function ciq_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ciq_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function ciq_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function ciq_username() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  if (isset($_SESSION['profile']['username']) && $_SESSION['profile']['username'] !== '') return $_SESSION['profile']['username'];
  return 'system';
}

function ciq_status_label($status) {
  $map = array(
    'OPEN' => 'default',
    'QUOTED' => 'info',
    'WON' => 'success',
    'LOST' => 'danger',
    'CANCELLED' => 'warning'
  );
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.ciq_h($status ?: 'OPEN').'</span>';
}

function ciq_priority_label($priority) {
  $map = array(
    'LOW' => 'default',
    'NORMAL' => 'primary',
    'HIGH' => 'warning',
    'URGENT' => 'danger'
  );
  $class = isset($map[$priority]) ? $map[$priority] : 'primary';
  return '<span class="label label-'.$class.'">'.ciq_h($priority ?: 'NORMAL').'</span>';
}

function ciq_next_no($db) {
  $prefix = 'INQ'.date('Ym');
  $row = $db->fetch("SELECT inquiry_no FROM sales_inquiry WHERE inquiry_no LIKE ? ORDER BY inquiry_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->inquiry_no, $m)) $next = (int)$m[1] + 1;
  return $prefix.sprintf('%05d', $next);
}

function ciq_filters() {
  return array(
    'tgl_awal' => ciq_input('tgl_awal', date('Y-m-01')),
    'tgl_akhir' => ciq_input('tgl_akhir', date('Y-m-d')),
    'customer_id' => ciq_input('customer_id'),
    'status' => ciq_input('status'),
    'priority' => ciq_input('priority'),
    'sales_person' => ciq_input('sales_person'),
    'keyword' => ciq_input('keyword')
  );
}

function ciq_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = ciq_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $to = ciq_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND si.inquiry_date BETWEEN ? AND ? ";
  $params[] = $from;
  $params[] = $to;

  if (!empty($input['customer_id'])) { $where .= " AND si.customer_id=? "; $params[] = (int)$input['customer_id']; }
  if (!empty($input['status'])) { $where .= " AND si.status=? "; $params[] = $input['status']; }
  if (!empty($input['priority'])) { $where .= " AND si.priority=? "; $params[] = $input['priority']; }
  if (!empty($input['sales_person'])) { $where .= " AND si.sales_person=? "; $params[] = $input['sales_person']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.$input['keyword'].'%';
    $where .= " AND (si.inquiry_no LIKE ? OR si.customer_name LIKE ? OR si.customer_code LIKE ? OR si.subject LIKE ? OR si.contact_person LIKE ? OR si.remarks LIKE ?) ";
    for ($i = 0; $i < 6; $i++) $params[] = $kw;
  }
  return $where;
}

function ciq_base_sql() {
  return " FROM sales_inquiry si
           LEFT JOIN customer c ON c.id_customer=si.customer_id
           LEFT JOIN sales_inquiry_detail sid ON sid.inquiry_id=si.id ";
}

function ciq_select_sql() {
  return "SELECT si.*,
                 COALESCE(si.customer_name,c.nama) AS customer_display,
                 COUNT(sid.id) AS item_count,
                 COALESCE(SUM(sid.qty),0) AS total_qty,
                 COALESCE(SUM(sid.estimated_amount),0) AS total_amount ";
}

function ciq_load_rows($db, $input, $limit = 0, $offset = 0) {
  $params = array();
  $where = ciq_filter_sql($input, $params);
  $sql = ciq_select_sql().ciq_base_sql().$where."
          GROUP BY si.id
          ORDER BY si.inquiry_date DESC, si.id DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}

function ciq_count_rows($db, $input) {
  $params = array();
  $where = ciq_filter_sql($input, $params);
  $row = $db->fetch("SELECT COUNT(*) AS total FROM sales_inquiry si LEFT JOIN customer c ON c.id_customer=si.customer_id ".$where, $params);
  return $row ? (int)$row->total : 0;
}

function ciq_summary($db, $input) {
  $params = array();
  $where = ciq_filter_sql($input, $params);
  return $db->fetch("SELECT COUNT(DISTINCT si.id) AS total_docs,
                            COALESCE(SUM(sid.qty),0) AS total_qty,
                            COALESCE(SUM(sid.estimated_amount),0) AS total_amount,
                            SUM(CASE WHEN si.status='OPEN' THEN 1 ELSE 0 END) AS open_docs
                     ".ciq_base_sql().$where, $params);
}

function ciq_detail_rows($db, $id) {
  return $db->query("SELECT d.*,b.nm_barang,b.satuan
                    FROM sales_inquiry_detail d
                    LEFT JOIN barang b ON b.kd_barang=d.material_code
                    WHERE d.inquiry_id=?
                    ORDER BY d.line_no,d.id", array((int)$id));
}
?>
