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
function qfu_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function qfu_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function qfu_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}
function qfu_valid_datetime($date, $default) {
  $date = trim((string)$date);
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date.' 00:00:00';
  if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(:\d{2})?$/', $date)) return strlen($date) === 16 ? $date.':00' : $date;
  return $default;
}
function qfu_username() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  if (isset($_SESSION['profile']['username']) && $_SESSION['profile']['username'] !== '') return $_SESSION['profile']['username'];
  return 'system';
}
function qfu_status_label($status) {
  $map = array('OPEN'=>'default','WAITING_CUSTOMER'=>'info','NEED_REVISION'=>'warning','WON'=>'success','LOST'=>'danger','CANCELLED'=>'warning');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.qfu_h($status ?: 'OPEN').'</span>';
}
function qfu_quote_status_label($status) {
  $map = array('OPEN'=>'default','SENT'=>'info','ACCEPTED'=>'success','REJECTED'=>'danger','EXPIRED'=>'warning','CANCELLED'=>'warning');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.qfu_h($status ?: 'OPEN').'</span>';
}
function qfu_filters() {
  return array(
    'tgl_awal'=>qfu_input('tgl_awal', date('Y-m-01')),
    'tgl_akhir'=>qfu_input('tgl_akhir', date('Y-m-d')),
    'customer_id'=>qfu_input('customer_id'),
    'quote_status'=>qfu_input('quote_status'),
    'followup_status'=>qfu_input('followup_status'),
    'sales_person'=>qfu_input('sales_person'),
    'due_only'=>qfu_input('due_only'),
    'keyword'=>qfu_input('keyword')
  );
}
function qfu_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = qfu_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $to = qfu_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND sq.tgl BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if (!empty($input['customer_id'])) { $where .= " AND sq.customer_id=? "; $params[]=(int)$input['customer_id']; }
  if (!empty($input['quote_status'])) { $where .= " AND sq.status=? "; $params[]=$input['quote_status']; }
  if (!empty($input['followup_status'])) { $where .= " AND COALESCE(lf.result_status,'OPEN')=? "; $params[]=$input['followup_status']; }
  if (!empty($input['sales_person'])) { $where .= " AND COALESCE(lf.sales_person,sq.sales_id)=? "; $params[]=$input['sales_person']; }
  if (!empty($input['due_only'])) { $where .= " AND lf.next_followup_date IS NOT NULL AND DATE(lf.next_followup_date)<=CURDATE() AND COALESCE(lf.result_status,'OPEN') NOT IN ('WON','LOST','CANCELLED') "; }
  if (!empty($input['keyword'])) {
    $kw='%'.$input['keyword'].'%';
    $where .= " AND (sq.no_sales_quotation LIKE ? OR sq.customer_name LIKE ? OR c.nama LIKE ? OR sq.subject LIKE ? OR lf.discussion_summary LIKE ? OR lf.next_action LIKE ?) ";
    for($i=0;$i<6;$i++) $params[]=$kw;
  }
  return $where;
}
function qfu_base_sql() {
  return " FROM sales_quotation sq
           LEFT JOIN customer c ON c.id_customer=sq.customer_id OR c.kode_pemasok=sq.kode_penerima
           LEFT JOIN (
             SELECT f.*
             FROM sales_quotation_followup f
             JOIN (
               SELECT quotation_id,MAX(id) AS last_id
               FROM sales_quotation_followup
               GROUP BY quotation_id
             ) x ON x.last_id=f.id
           ) lf ON lf.quotation_id=sq.id_quotation ";
}
function qfu_load_rows($db, $input, $limit=0, $offset=0) {
  $params=array(); $where=qfu_filter_sql($input,$params);
  $sql="SELECT sq.id_quotation,sq.no_sales_quotation,sq.tgl,sq.valid_date,sq.status AS quote_status,
               sq.customer_id,sq.kode_penerima,COALESCE(NULLIF(sq.customer_name,''),c.nama) AS customer_display,
               sq.subject,sq.currency,sq.sales_id,
               lf.id AS followup_id,lf.followup_date,lf.contact_method,lf.activity_type,lf.result_status,lf.probability_percent,
               lf.next_action,lf.next_followup_date,lf.sales_person,lf.discussion_summary
        ".qfu_base_sql().$where."
        ORDER BY COALESCE(lf.next_followup_date,sq.valid_date,sq.tgl) ASC,sq.id_quotation DESC";
  if($limit>0) $sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$params);
}
function qfu_count_rows($db,$input) {
  $params=array(); $where=qfu_filter_sql($input,$params);
  $row=$db->fetch("SELECT COUNT(*) AS total ".qfu_base_sql().$where,$params);
  return $row?(int)$row->total:0;
}
function qfu_summary($db,$input) {
  $params=array(); $where=qfu_filter_sql($input,$params);
  return $db->fetch("SELECT COUNT(*) AS total_quotes,
                            SUM(CASE WHEN lf.id IS NULL THEN 1 ELSE 0 END) AS never_followed,
                            SUM(CASE WHEN lf.next_followup_date IS NOT NULL AND DATE(lf.next_followup_date)<=CURDATE() AND COALESCE(lf.result_status,'OPEN') NOT IN ('WON','LOST','CANCELLED') THEN 1 ELSE 0 END) AS due_today,
                            AVG(COALESCE(lf.probability_percent,0)) AS avg_probability
                     ".qfu_base_sql().$where,$params);
}
?>
