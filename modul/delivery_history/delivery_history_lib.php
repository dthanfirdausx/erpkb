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
function dh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dh_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function dh_date($value, $default) {
  $value = trim((string)$value);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
}
function dh_qty($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function dh_money($value) { return number_format((float)$value, 2, ',', '.'); }
function dh_status_label($status) {
  $map = array('CREATED'=>'default','PICKING'=>'info','PICKED'=>'primary','PACKED'=>'warning','PGI'=>'success','COMPLETED'=>'success','CANCELLED'=>'danger');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.dh_h($status ?: '-').'</span>';
}
function dh_process_label($status) {
  $map = array('NOT_STARTED'=>'default','NOT_POSTED'=>'default','PARTIAL'=>'warning','COMPLETE'=>'success','POSTED'=>'success');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.dh_h($status ?: '-').'</span>';
}
function dh_sj_label($status) {
  $status = strtolower((string)$status);
  $map = array('draft'=>'default','dikirim'=>'info','diterima'=>'success','dibatalkan'=>'danger');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.dh_h(strtoupper($status ?: '-')).'</span>';
}
function dh_filters() {
  return array(
    'tgl_awal' => dh_input('tgl_awal', date('Y-01-01')),
    'tgl_akhir' => dh_input('tgl_akhir', date('Y-m-d')),
    'customer' => dh_input('customer', 'all'),
    'status' => dh_input('status', 'all'),
    'shipping_point' => dh_input('shipping_point'),
    'keyword' => dh_input('keyword')
  );
}
function dh_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = dh_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-01-01'));
  $to = dh_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND od.delivery_date BETWEEN ? AND ? ";
  $params[] = $from;
  $params[] = $to;
  if (!empty($input['customer']) && $input['customer'] !== 'all') { $where .= " AND od.customer_code=? "; $params[] = $input['customer']; }
  if (!empty($input['status']) && $input['status'] !== 'all') { $where .= " AND od.status=? "; $params[] = $input['status']; }
  if (!empty($input['shipping_point'])) { $where .= " AND od.shipping_point=? "; $params[] = $input['shipping_point']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.$input['keyword'].'%';
    $where .= " AND (od.delivery_no LIKE ? OR od.no_sales_order LIKE ? OR od.customer_name LIKE ? OR od.customer_code LIKE ? OR od.reference_surat_jalan LIKE ? OR od.reference_gi LIKE ? OR od.vehicle_no LIKE ? OR od.driver_name LIKE ? OR sj.no_surat_jalan LIKE ? OR pl.no_packing_list LIKE ? OR gi.gi_no LIKE ?) ";
    for ($i = 0; $i < 11; $i++) $params[] = $kw;
  }
  return $where;
}
function dh_base_sql() {
  return " FROM erp_outbound_delivery od
           LEFT JOIN (
             SELECT delivery_id,COUNT(*) item_count,COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(picked_qty),0) picked_qty,COALESCE(SUM(packed_qty),0) packed_qty,COALESCE(SUM(gi_qty),0) gi_qty,COALESCE(SUM(amount),0) amount
             FROM erp_outbound_delivery_detail GROUP BY delivery_id
           ) dd ON dd.delivery_id=od.id
           LEFT JOIN (
             SELECT delivery_id,GROUP_CONCAT(DISTINCT picking_no ORDER BY picking_date SEPARATOR ', ') picking_nos,MAX(picking_date) picking_date,MAX(status) picking_status_doc,COUNT(*) picking_docs
             FROM erp_picking GROUP BY delivery_id
           ) pk ON pk.delivery_id=od.id
           LEFT JOIN (
             SELECT delivery_id,GROUP_CONCAT(DISTINCT gi_no ORDER BY posting_date SEPARATOR ', ') gi_nos,MAX(posting_date) gi_posting_date,SUM(CASE WHEN status='POSTED' THEN total_amount ELSE 0 END) gi_amount,COUNT(*) gi_docs
             FROM erp_goods_issue_delivery GROUP BY delivery_id
           ) gi ON gi.delivery_id=od.id
           LEFT JOIN (
             SELECT no_sales_order,GROUP_CONCAT(DISTINCT no_surat_jalan ORDER BY tgl_surat_jalan SEPARATOR ', ') no_surat_jalan,MAX(tgl_surat_jalan) tgl_surat_jalan,MAX(status) sj_status,SUM(total_qty) sj_qty,COUNT(*) sj_docs
             FROM surat_jalan GROUP BY no_sales_order
           ) sj ON sj.no_sales_order=od.no_sales_order OR sj.no_surat_jalan=od.reference_surat_jalan
           LEFT JOIN (
             SELECT no_sj,GROUP_CONCAT(DISTINCT no_packing_list ORDER BY date_created SEPARATOR ', ') packing_nos,COUNT(*) packing_docs
             FROM packing_list GROUP BY no_sj
           ) pl ON pl.no_sj=sj.no_surat_jalan OR pl.no_sj=od.reference_surat_jalan ";
}
function dh_select_sql() {
  return "SELECT od.*,COALESCE(dd.item_count,0) item_count,COALESCE(dd.delivery_qty,0) delivery_qty,COALESCE(dd.picked_qty,0) picked_qty,COALESCE(dd.packed_qty,0) packed_qty,COALESCE(dd.gi_qty,0) gi_qty,COALESCE(dd.amount,0) delivery_amount,
                 COALESCE(pk.picking_docs,0) picking_docs,pk.picking_nos,pk.picking_date,pk.picking_status_doc,
                 COALESCE(gi.gi_docs,0) gi_docs,gi.gi_nos,gi.gi_posting_date,COALESCE(gi.gi_amount,0) gi_amount,
                 COALESCE(sj.sj_docs,0) sj_docs,sj.no_surat_jalan,sj.tgl_surat_jalan,sj.sj_status,COALESCE(sj.sj_qty,0) sj_qty,
                 COALESCE(pl.packing_docs,0) packing_docs,pl.packing_nos,
                 CASE WHEN COALESCE(dd.delivery_qty,0)>0 THEN ROUND((COALESCE(dd.gi_qty,0)/dd.delivery_qty)*100,2) ELSE 0 END gi_percent,
                 CASE WHEN COALESCE(dd.delivery_qty,0)>0 THEN ROUND((COALESCE(dd.picked_qty,0)/dd.delivery_qty)*100,2) ELSE 0 END picked_percent,
                 CASE WHEN COALESCE(dd.delivery_qty,0)>0 THEN ROUND((COALESCE(dd.packed_qty,0)/dd.delivery_qty)*100,2) ELSE 0 END packed_percent ";
}
function dh_load_rows($db, $input, $limit = 0, $offset = 0) {
  $params = array(); $where = dh_filter_sql($input, $params);
  $sql = dh_select_sql().dh_base_sql().$where." ORDER BY od.delivery_date DESC,od.id DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}
function dh_count_rows($db, $input) {
  $params = array(); $where = dh_filter_sql($input, $params);
  $row = $db->fetch("SELECT COUNT(*) total ".dh_base_sql().$where, $params);
  return $row ? (int)$row->total : 0;
}
function dh_summary($db, $input) {
  $params = array(); $where = dh_filter_sql($input, $params);
  return $db->fetch("SELECT COUNT(*) total_delivery,
                            SUM(CASE WHEN od.status IN ('PGI','COMPLETED') THEN 1 ELSE 0 END) completed_delivery,
                            SUM(CASE WHEN od.gi_status='POSTED' THEN 1 ELSE 0 END) posted_gi,
                            COALESCE(SUM(dd.delivery_qty),0) delivery_qty,
                            COALESCE(SUM(dd.gi_qty),0) gi_qty,
                            COALESCE(SUM(dd.amount),0) amount
                     ".dh_base_sql().$where, $params);
}
function dh_render_detail($db, $id) {
  $row = $db->fetch(dh_select_sql().dh_base_sql()." WHERE od.id=? LIMIT 1", array($id));
  if (!$row) { echo '<div class="alert alert-warning">Delivery tidak ditemukan.</div>'; return; }
  $items = $db->query("SELECT * FROM erp_outbound_delivery_detail WHERE delivery_id=? ORDER BY line_no,id", array($id));
  $pickings = $db->query("SELECT * FROM erp_picking WHERE delivery_id=? ORDER BY picking_date,id", array($id));
  $gis = $db->query("SELECT * FROM erp_goods_issue_delivery WHERE delivery_id=? ORDER BY posting_date,id", array($id));
  $sjs = $db->query("SELECT * FROM surat_jalan WHERE no_sales_order=? OR no_surat_jalan=? ORDER BY tgl_surat_jalan,id", array($row->no_sales_order,$row->reference_surat_jalan));
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=dh_h($row->delivery_no);?> <small>SO <?=dh_h($row->no_sales_order);?></small></h3><p><?=dh_status_label($row->status);?> <?=dh_process_label($row->picking_status);?> <?=dh_process_label($row->packing_status);?> <?=dh_process_label($row->gi_status);?></p></div><div class="col-md-4 text-right"><strong><?=dh_h($row->customer_code.' - '.$row->customer_name);?></strong><br><span class="text-muted"><?=dh_h($row->delivery_date);?></span></div></div>
  <div class="row">
    <div class="col-sm-3"><strong>Delivery Qty</strong><br><?=dh_qty($row->delivery_qty);?></div>
    <div class="col-sm-3"><strong>Picked / Packed</strong><br><?=dh_qty($row->picked_qty).' / '.dh_qty($row->packed_qty);?></div>
    <div class="col-sm-3"><strong>GI Qty</strong><br><?=dh_qty($row->gi_qty).' ('.number_format((float)$row->gi_percent,2,',','.').'%)';?></div>
    <div class="col-sm-3"><strong>Vehicle / Driver</strong><br><?=dh_h(trim($row->vehicle_no.' / '.$row->driver_name, ' /') ?: '-');?></div>
  </div>
  <hr>
  <h4>Document Flow</h4>
  <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Step</th><th>Document</th><th>'.sd_h('sales_date', 'Date').'</th><th>'.sd_h('common_status', 'Status').'</th><th>'.sd_h('sales_reference', 'Reference').'</th></tr></thead><tbody>
    <tr><td>1. Outbound Delivery</td><td><?=dh_h($row->delivery_no);?></td><td><?=dh_h($row->delivery_date);?></td><td><?=dh_status_label($row->status);?></td><td><?=dh_h($row->shipping_point ?: '-');?></td></tr>
    <tr><td>2. Picking</td><td><?=dh_h($row->picking_nos ?: '-');?></td><td><?=dh_h($row->picking_date ?: '-');?></td><td><?=dh_process_label($row->picking_status);?></td><td><?=intval($row->picking_docs);?> dokumen</td></tr>
    <tr><td>3. Packing List</td><td><?=dh_h($row->packing_nos ?: '-');?></td><td>-</td><td><?=dh_process_label($row->packing_status);?></td><td><?=intval($row->packing_docs);?> dokumen</td></tr>
    <tr><td>4. Surat Jalan</td><td><?=dh_h($row->no_surat_jalan ?: '-');?></td><td><?=dh_h($row->tgl_surat_jalan ?: '-');?></td><td><?=dh_sj_label($row->sj_status);?></td><td><?=intval($row->sj_docs);?> dokumen</td></tr>
    <tr><td>5. Goods Issue</td><td><?=dh_h($row->gi_nos ?: '-');?></td><td><?=dh_h($row->gi_posting_date ?: '-');?></td><td><?=dh_process_label($row->gi_status);?></td><td><?=dh_money($row->gi_amount);?></td></tr>
  </tbody></table></div>
  <h4>'.sd_h('sales_items', 'Items').'</h4>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('common_no', 'No').'</th><th>'.sd_h('sales_material', 'Material').'</th><th class="text-right">Delivery</th><th class="text-right">Picked</th><th class="text-right">Packed</th><th class="text-right">GI</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th><th>Remark</th></tr></thead><tbody>
  <?php foreach ($items as $item) { ?><tr><td><?=intval($item->line_no);?></td><td><strong><?=dh_h($item->material_code);?></strong><br><small><?=dh_h($item->material_name);?></small></td><td class="text-right"><?=dh_qty($item->delivery_qty);?></td><td class="text-right"><?=dh_qty($item->picked_qty);?></td><td class="text-right"><?=dh_qty($item->packed_qty);?></td><td class="text-right"><?=dh_qty($item->gi_qty);?></td><td><?=dh_h($item->uom);?></td><td class="text-right"><?=dh_money($item->amount);?></td><td><?=dh_h($item->remarks);?></td></tr><?php } ?>
  </tbody></table></div>
  <div class="row">
    <div class="col-md-6"><h4>Picking Documents</h4><ul class="list-unstyled"><?php foreach($pickings as $p){ ?><li><strong><?=dh_h($p->picking_no);?></strong> <?=dh_h($p->picking_date);?> - <?=dh_h($p->status);?> <span class="text-muted"><?=dh_h($p->picker);?></span></li><?php } ?></ul></div>
    <div class="col-md-6"><h4>Goods Issue Documents</h4><ul class="list-unstyled"><?php foreach($gis as $g){ ?><li><strong><?=dh_h($g->gi_no);?></strong> <?=dh_h($g->posting_date);?> - <?=dh_h($g->status);?> <span class="text-muted"><?=dh_money($g->total_amount);?></span></li><?php } ?></ul></div>
  </div>
  <h4>'.sd_h('sales_surat_jalan', 'Surat Jalan').'</h4><ul class="list-unstyled"><?php foreach($sjs as $sj){ ?><li><strong><?=dh_h($sj->no_surat_jalan);?></strong> <?=dh_h($sj->tgl_surat_jalan);?> - <?=dh_h(strtoupper($sj->status));?> <span class="text-muted"><?=dh_h($sj->sopir.' / '.$sj->no_kendaraan);?></span></li><?php } ?></ul>
  <?php
}
?>
