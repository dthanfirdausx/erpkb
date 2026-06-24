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
function soa_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function soa_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function soa_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}
function soa_username() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  if (isset($_SESSION['profile']['username']) && $_SESSION['profile']['username'] !== '') return $_SESSION['profile']['username'];
  return 'system';
}
function soa_group_level() {
  if (isset($_SESSION['group_level']) && $_SESSION['group_level'] !== '') return $_SESSION['group_level'];
  if (isset($_SESSION['level']) && $_SESSION['level'] !== '') return $_SESSION['level'];
  return '';
}
function soa_is_admin() {
  return in_array(soa_group_level(), array('admin','system_administrator'));
}
function soa_status_label($status) {
  $map = array('DRAFT'=>'default','SUBMITTED'=>'info','PENDING'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','CANCELLED'=>'warning');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.soa_h($status ?: 'PENDING').'</span>';
}
function soa_filters() {
  return array(
    'tgl_awal'=>soa_input('tgl_awal', date('Y-01-01')),
    'tgl_akhir'=>soa_input('tgl_akhir', date('Y-m-d')),
    'customer'=>soa_input('customer','all'),
    'approval_status'=>soa_input('approval_status','PENDING'),
    'sales_person'=>soa_input('sales_person'),
    'my_worklist'=>soa_input('my_worklist',''),
    'keyword'=>soa_input('keyword')
  );
}
function soa_access_sql(&$params, $alias='a') {
  if (soa_is_admin()) return '';
  $username = soa_username();
  $group = soa_group_level();
  $params[] = $username;
  $params[] = $group;
  return " AND (COALESCE($alias.approver,'')='' OR $alias.approver=? OR $alias.approver_group=?) ";
}
function soa_filter_sql($input, &$params, $applyAccess=false) {
  $where = " WHERE 1=1 ";
  $from = soa_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-01-01'));
  $to = soa_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND so.so_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if (!empty($input['customer']) && $input['customer'] !== 'all') { $where .= " AND so.kode_penerima=? "; $params[] = $input['customer']; }
  if (!empty($input['approval_status']) && $input['approval_status'] !== 'all') { $where .= " AND so.approval_status=? "; $params[] = $input['approval_status']; }
  if (!empty($input['sales_person'])) { $where .= " AND so.sales_id=? "; $params[] = $input['sales_person']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.$input['keyword'].'%';
    $where .= " AND (so.no_sales_order LIKE ? OR so.no_po LIKE ? OR p.nama LIKE ? OR so.kode_penerima LIKE ? OR so.alasan LIKE ?) ";
    for($i=0;$i<5;$i++) $params[]=$kw;
  }
  if ($applyAccess || !empty($input['my_worklist'])) $where .= soa_access_sql($params, 'a');
  return $where;
}
function soa_base_sql() {
  return " FROM sales_order so
           LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima
           LEFT JOIN sales_order_approval a ON a.id_sales_order=so.id_sales_order AND a.approval_level=1
           LEFT JOIN (
             SELECT id_sales_order,COUNT(*) AS item_count,COALESCE(SUM(qty),0) AS total_qty,COALESCE(SUM(nilai),0) AS total_amount
             FROM sales_order_detail
             GROUP BY id_sales_order
           ) d ON d.id_sales_order=so.id_sales_order ";
}
function soa_load_rows($db,$input,$limit=0,$offset=0) {
  $params=array(); $where=soa_filter_sql($input,$params,false);
  $sql="SELECT so.*,p.nama AS customer_name,
               a.id_approval,a.approval_level,a.approver,a.approver_group,a.status AS approval_line_status,a.approval_date,a.note,
               COALESCE(d.item_count,0) AS item_count,COALESCE(d.total_qty,0) AS total_qty,COALESCE(d.total_amount,0) AS total_amount
        ".soa_base_sql().$where."
        ORDER BY CASE so.approval_status WHEN 'PENDING' THEN 0 WHEN 'SUBMITTED' THEN 1 ELSE 2 END, so.so_date DESC, so.id_sales_order DESC";
  if($limit>0)$sql.=" LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql,$params);
}
function soa_count_rows($db,$input) {
  $params=array(); $where=soa_filter_sql($input,$params,false);
  $row=$db->fetch("SELECT COUNT(*) AS total ".soa_base_sql().$where,$params);
  return $row?(int)$row->total:0;
}
function soa_summary($db,$input) {
  $params=array(); $where=soa_filter_sql($input,$params,false);
  return $db->fetch("SELECT COUNT(*) AS total_docs,
                            SUM(CASE WHEN so.approval_status='PENDING' THEN 1 ELSE 0 END) AS pending_docs,
                            SUM(CASE WHEN so.approval_status='APPROVED' THEN 1 ELSE 0 END) AS approved_docs,
                            COALESCE(SUM(d.total_amount),0) AS total_amount
                     ".soa_base_sql().$where,$params);
}
?>
