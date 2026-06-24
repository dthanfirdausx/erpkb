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
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function gid_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function gid_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function gid_date($value, $default) {
  $value = trim((string)$value);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
}
function gid_user() {
  if (isset($_SESSION['username']) && $_SESSION['username'] !== '') return $_SESSION['username'];
  if (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '') return $_SESSION['nama_lengkap'];
  return 'system';
}
function gid_qty($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function gid_money($value) { return number_format((float)$value, 2, ',', '.'); }
function gid_next_no($db, $postingDate) {
  $prefix = 'GID'.date('Ym', strtotime($postingDate ?: date('Y-m-d')));
  $row = $db->fetch("SELECT gi_no FROM erp_goods_issue_delivery WHERE gi_no LIKE ? ORDER BY gi_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->gi_no, $m)) $next = (int)$m[1] + 1;
  return $prefix.sprintf('%05d', $next);
}
function gid_status_label($status) {
  $map = array('POSTED'=>'success','REVERSED'=>'danger','CANCELLED'=>'default');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.gid_h($status ?: '-').'</span>';
}
function gid_delivery_status_label($status) {
  $map = array('CREATED'=>'default','PICKING'=>'info','PICKED'=>'primary','PACKED'=>'warning','PGI'=>'success','COMPLETED'=>'success','CANCELLED'=>'danger');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.gid_h($status ?: '-').'</span>';
}
function gid_filters() {
  return array(
    'tgl_awal' => gid_input('tgl_awal', date('Y-01-01')),
    'tgl_akhir' => gid_input('tgl_akhir', date('Y-m-d')),
    'customer' => gid_input('customer', 'all'),
    'status' => gid_input('status', 'all'),
    'shipping_point' => gid_input('shipping_point'),
    'keyword' => gid_input('keyword')
  );
}
function gid_filter_sql($in, &$params) {
  $where = " WHERE 1=1 ";
  $from = gid_date(isset($in['tgl_awal']) ? $in['tgl_awal'] : '', date('Y-01-01'));
  $to = gid_date(isset($in['tgl_akhir']) ? $in['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND gi.posting_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if (!empty($in['customer']) && $in['customer'] !== 'all') { $where .= " AND gi.customer_code=? "; $params[] = $in['customer']; }
  if (!empty($in['status']) && $in['status'] !== 'all') { $where .= " AND gi.status=? "; $params[] = $in['status']; }
  if (!empty($in['shipping_point'])) { $where .= " AND gi.shipping_point=? "; $params[] = $in['shipping_point']; }
  if (!empty($in['keyword'])) {
    $kw = '%'.$in['keyword'].'%';
    $where .= " AND (gi.gi_no LIKE ? OR gi.delivery_no LIKE ? OR gi.no_sales_order LIKE ? OR gi.customer_name LIKE ? OR gi.reference_surat_jalan LIKE ? OR gi.vehicle_no LIKE ? OR gi.driver_name LIKE ?) ";
    for ($i = 0; $i < 7; $i++) $params[] = $kw;
  }
  return $where;
}
function gid_base_sql() {
  return " FROM erp_goods_issue_delivery gi
           LEFT JOIN (
             SELECT gi_id,COUNT(*) item_count,COALESCE(SUM(qty),0) qty,COALESCE(SUM(amount),0) amount
             FROM erp_goods_issue_delivery_detail GROUP BY gi_id
           ) d ON d.gi_id=gi.id ";
}
function gid_select_sql() {
  return "SELECT gi.*,COALESCE(d.item_count,0) item_count,COALESCE(d.qty,0) posted_qty,COALESCE(d.amount,0) posted_amount ";
}
function gid_load_rows($db, $in, $limit = 0, $offset = 0) {
  $params = array(); $where = gid_filter_sql($in, $params);
  $sql = gid_select_sql().gid_base_sql().$where." ORDER BY gi.posting_date DESC,gi.id DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}
function gid_count_rows($db, $in) {
  $params = array(); $where = gid_filter_sql($in, $params);
  $row = $db->fetch("SELECT COUNT(*) total ".gid_base_sql().$where, $params);
  return $row ? (int)$row->total : 0;
}
function gid_summary($db, $in) {
  $params = array(); $where = gid_filter_sql($in, $params);
  return $db->fetch("SELECT COUNT(*) total_docs,
                            SUM(CASE WHEN gi.status='POSTED' THEN 1 ELSE 0 END) posted_docs,
                            SUM(CASE WHEN gi.status='REVERSED' THEN 1 ELSE 0 END) reversed_docs,
                            COALESCE(SUM(d.qty),0) posted_qty,
                            COALESCE(SUM(d.amount),0) posted_amount
                     ".gid_base_sql().$where, $params);
}
function gid_layer_price($layer) {
  if (isset($layer->purchase_price) && (float)$layer->purchase_price > 0) return (float)$layer->purchase_price;
  if (isset($layer->production_price) && (float)$layer->production_price > 0) return (float)$layer->production_price;
  return 0;
}
function gid_source_document_ok($layer) {
  return trim((string)$layer->no_bpb) !== '' || trim((string)$layer->no_aju) !== '' || trim((string)$layer->no_dokpab) !== '' || trim((string)$layer->jenis_dokpab) !== '';
}
function gid_fetch_layers($db, $materialCode, $forUpdate = false) {
  $sql = "SELECT sl.*,b.nm_barang,b.satuan,pd.harga AS purchase_price,pd.unit AS purchase_uom,pd.lot_no AS purchase_lot_no,pd.hs_code AS purchase_hs_code,
                 COALESCE(CASE WHEN COALESCE(gpd.qty,0)>0 THEN gpd.amount/gpd.qty END,CASE WHEN ABS(COALESCE(gdt.qty,0))>0 THEN ABS(gdt.amount)/ABS(gdt.qty) END,0) AS production_price
          FROM stock_layer sl
          LEFT JOIN barang b ON b.kd_barang=sl.kode
          LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
          LEFT JOIN erp_gr_production gp ON gp.id=sl.ref_id AND sl.ref_table='erp_gr_production'
          LEFT JOIN erp_gr_production_detail gpd ON gpd.stock_layer_id=sl.id
          LEFT JOIN detail_transaksi gdt ON gdt.id=gpd.material_doc_id
          WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED'
          ORDER BY sl.tgl_masuk ASC,sl.id ASC";
  if ($forUpdate) $sql .= " FOR UPDATE";
  return $db->query($sql, array($materialCode));
}
function gid_render_detail($db, $id) {
  $h = $db->fetch("SELECT * FROM erp_goods_issue_delivery WHERE id=? LIMIT 1", array($id));
  if (!$h) { echo '<div class="alert alert-warning">Goods Issue Delivery tidak ditemukan.</div>'; return; }
  $items = $db->query("SELECT * FROM erp_goods_issue_delivery_detail WHERE gi_id=? ORDER BY line_no,id", array($id));
  $history = $db->query("SELECT * FROM erp_goods_issue_delivery_history WHERE gi_id=? ORDER BY changed_at DESC,id DESC", array($id));
  ?>
  <div class="row">
    <div class="col-md-8">
      <h3 style="margin-top:0;font-weight:700"><?=gid_h($h->gi_no);?> <small>Delivery <?=gid_h($h->delivery_no);?></small></h3>
      <p><?=gid_status_label($h->status);?> <span class="text-muted">Movement <?=gid_h($h->movement_type);?> | SO <?=gid_h($h->no_sales_order);?></span></p>
    </div>
    <div class="col-md-4 text-right"><strong><?=gid_h($h->customer_code.' - '.$h->customer_name);?></strong><br><span class="text-muted"><?=gid_h($h->posting_date);?></span></div>
  </div>
  <div class="row">
    <div class="col-sm-3"><strong>'.sd_h('sales_document_date', 'Document Date').'</strong><br><?=gid_h($h->document_date);?></div>
    <div class="col-sm-3"><strong><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></strong><br><?=gid_h($h->posting_date);?></div>
    <div class="col-sm-3"><strong>'.sd_h('sales_shipping_point', 'Shipping Point').'</strong><br><?=gid_h($h->shipping_point ?: '-');?></div>
    <div class="col-sm-3"><strong>Vehicle / Driver</strong><br><?=gid_h(trim($h->vehicle_no.' / '.$h->driver_name, ' /') ?: '-');?></div>
  </div>
  <hr>
  <h4>Dokumen Pabean Keluar</h4>
  <div class="row">
    <div class="col-sm-3"><strong>Jenis BC</strong><br><?=gid_h(trim($h->outbound_bc_type.' - '.$h->outbound_bc_purpose, ' - ') ?: '-');?></div>
    <div class="col-sm-3"><strong>No / Tgl Aju</strong><br><?=gid_h(($h->outbound_no_aju ?: '-').' / '.($h->outbound_tgl_aju ?: '-'));?></div>
    <div class="col-sm-3"><strong>No / Tgl Daftar</strong><br><?=gid_h(($h->outbound_no_daftar ?: '-').' / '.($h->outbound_tgl_daftar ?: '-'));?></div>
    <div class="col-sm-3"><strong>Kantor BC / Negara</strong><br><?=gid_h(trim($h->outbound_customs_office.' / '.$h->outbound_destination_country, ' / ') ?: '-');?></div>
  </div>
  <?php if (trim((string)$h->outbound_customs_remarks) !== '') { ?>
    <p class="text-muted" style="margin-top:8px"><?=gid_h($h->outbound_customs_remarks);?></p>
  <?php } ?>
  <hr>
  <?php foreach ($items as $item) {
    $traces = $db->query(
      "SELECT t.*,ep.plant_code,es.storage_code,eb.bin_code
       FROM erp_goods_issue_delivery_trace t
       LEFT JOIN erp_plant ep ON ep.id=t.plant_id
       LEFT JOIN erp_storage_location es ON es.id=t.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=t.storage_bin_id
       WHERE t.gi_detail_id=? ORDER BY t.id",
      array($item->id)
    );
  ?>
    <h4><?=gid_h($item->line_no.'. '.$item->material_code.' - '.$item->material_name);?> <small>'.sd_h('sales_qty', 'Qty').' <?=gid_qty($item->qty).' '.gid_h($item->uom);?> | Amount <?=gid_money($item->amount);?></small></h4>
    <div class="table-responsive">
      <table class="table table-bordered table-condensed gid-trace-table">
        <thead><tr class="bg-gray"><th>Layer</th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>Lot/Batch</th><th>No Aju</th><th>Dok Pabean</th><th>No BPB</th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Mat Doc</th></tr></thead>
        <tbody>
        <?php foreach ($traces as $trace) { ?>
          <tr>
            <td>#<?=intval($trace->stock_layer_id);?></td>
            <td class="text-right"><?=gid_qty($trace->qty);?></td>
            <td class="text-right"><?=number_format((float)$trace->price,5,',','.');?></td>
            <td class="text-right"><?=gid_money($trace->amount);?></td>
            <td><?=gid_h($trace->lot_no ?: '-');?></td>
            <td><?=gid_h($trace->no_aju ?: '-');?></td>
            <td><?=gid_h(trim($trace->jenis_dokpab.' '.$trace->no_dokpab) ?: '-');?></td>
            <td><?=gid_h($trace->no_bpb ?: '-');?></td>
            <td><?=gid_h(trim($trace->plant_code.' / '.$trace->storage_code.' / '.$trace->bin_code, ' /') ?: '-');?></td>
            <td><?=intval($trace->material_doc_id);?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
  <h4>History</h4>
  <ul class="list-unstyled">
    <?php foreach ($history as $row) { ?>
      <li><strong><?=gid_h(($row->status_lama ?: '-').' -> '.$row->status_baru);?></strong> <span class="text-muted"><?=gid_h($row->changed_by.' @ '.$row->changed_at);?></span><br><?=gid_h($row->remarks);?></li>
    <?php } ?>
  </ul>
  <?php
}
?>
