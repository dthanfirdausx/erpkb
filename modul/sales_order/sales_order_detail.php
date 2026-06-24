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
function so_detail_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function so_detail_num($value, $dec = 4) { return number_format((float)$value, $dec, ',', '.'); }
function so_detail_money($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function so_detail_date($value) {
    if (!$value || $value === '0000-00-00') return '-';
    return function_exists('tgl_indo') ? tgl_indo($value) : date('d-m-Y', strtotime($value));
}
function so_detail_status_label($status) {
    $status = (string)$status;
    $class = 'default';
    if ($status === 'PRODUKSI BELUM FULL') $class = 'warning';
    if ($status === 'PROSES PRODUKSI') $class = 'primary';
    if ($status === 'DIKIRIM SEBAGIAN') $class = 'info';
    if ($status === 'SUDAH DIKIRIM') $class = 'success';
    return '<span class="label label-'.$class.'">'.so_detail_h($status ?: 'OPEN').'</span>';
}
function so_detail_approval_label($status) {
    $status = strtoupper((string)$status);
    $map = array('DRAFT'=>'default','SUBMITTED'=>'info','PENDING'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','CANCELLED'=>'danger');
    $class = isset($map[$status]) ? $map[$status] : 'default';
    return '<span class="label label-'.$class.'">'.so_detail_h($status ?: '-').'</span>';
}

$status_so = $db->fetch("SELECT * FROM v_sales_status WHERE id_sales_order=? LIMIT 1", array($data_edit->id_sales_order));
$customer = $db->fetch("SELECT * FROM penerima WHERE kode_penerima=? LIMIT 1", array($data_edit->kode_penerima));
$salesArea = $db->fetch("
    SELECT sorg.org_code,sorg.org_name,dc.channel_code,dc.channel_name
    FROM sales_order so
    LEFT JOIN erp_sales_organization sorg ON sorg.id=so.sales_org_id
    LEFT JOIN erp_distribution_channel dc ON dc.id=so.distribution_channel_id
    WHERE so.id_sales_order=?
    LIMIT 1
", array($data_edit->id_sales_order));
$subtotal = $db->fetch("SELECT COALESCE(SUM(nilai),0) total_amount,COALESCE(SUM(qty),0) total_qty,COUNT(*) item_count FROM sales_order_detail WHERE id_sales_order=?", array($data_edit->id_sales_order));

$items = $db->query("
    SELECT
        sod.*,
        br.nm_barang,
        br.satuan,
        COALESCE(prod.qty_ok,0) AS qty_produksi,
        COALESCE(prod.qty_ng,0) AS qty_ng,
        COALESCE(sj.qty_sj,0) AS qty_surat_jalan,
        COALESCE(od.delivery_qty,0) AS qty_delivery,
        COALESCE(od.picked_qty,0) AS qty_picked,
        COALESCE(od.packed_qty,0) AS qty_packed,
        COALESCE(od.gi_qty,0) AS qty_gi
    FROM sales_order_detail sod
    LEFT JOIN barang br ON br.kd_barang=sod.kd_barang
    LEFT JOIN (
        SELECT kode,COALESCE(SUM(qty_ok),0) qty_ok,COALESCE(SUM(qty_ng),0) qty_ng
        FROM (
            SELECT grd.material_code AS kode,COALESCE(SUM(grd.qty),0) qty_ok,0 qty_ng
            FROM production_order po
            INNER JOIN erp_gr_production gr ON gr.id_production_order=po.id_production_order AND gr.status='POSTED'
            INNER JOIN erp_gr_production_detail grd ON grd.gr_id=gr.id
            WHERE po.no_sales_order=?
            GROUP BY grd.material_code
        ) x
        GROUP BY kode
    ) prod ON prod.kode=sod.kd_barang
    LEFT JOIN (
        SELECT d.kode_barang,COALESCE(SUM(d.qty_kirim),0) qty_sj
        FROM surat_jalan sj
        INNER JOIN surat_jalan_detail d ON d.surat_jalan_id=sj.id
        WHERE sj.no_sales_order=? AND sj.status<>'dibatalkan'
        GROUP BY d.kode_barang
    ) sj ON sj.kode_barang=sod.kd_barang
    LEFT JOIN (
        SELECT d.material_code,COALESCE(SUM(d.delivery_qty),0) delivery_qty,
               COALESCE(SUM(d.picked_qty),0) picked_qty,
               COALESCE(SUM(d.packed_qty),0) packed_qty,
               COALESCE(SUM(d.gi_qty),0) gi_qty
        FROM erp_outbound_delivery od
        INNER JOIN erp_outbound_delivery_detail d ON d.delivery_id=od.id
        WHERE od.no_sales_order=? AND od.status<>'CANCELLED'
        GROUP BY d.material_code
    ) od ON od.material_code=sod.kd_barang
    WHERE sod.id_sales_order=?
    ORDER BY sod.id_detail ASC
", array($data_edit->no_sales_order, $data_edit->no_sales_order, $data_edit->no_sales_order, $data_edit->id_sales_order));

$productions = $db->query("
    SELECT gr.id AS id_produksi,gr.gr_no AS no_bpb,gr.posting_date AS tgl_bpb,grd.material_code AS kode,
           grd.material_name AS nm_barang,grd.qty AS jumlah,0 AS qty_ng,po.no_production_order,'GR Production' AS source_doc
    FROM production_order po
    INNER JOIN erp_gr_production gr ON gr.id_production_order=po.id_production_order AND gr.status='POSTED'
    INNER JOIN erp_gr_production_detail grd ON grd.gr_id=gr.id
    WHERE po.no_sales_order=?
    ORDER BY gr.posting_date ASC,gr.gr_no ASC
", array($data_edit->no_sales_order));

$deliveries = $db->query("
    SELECT id,delivery_no,delivery_date,status,picking_status,packing_status,gi_status,
           reference_packing_list,reference_surat_jalan,reference_gi
    FROM erp_outbound_delivery
    WHERE no_sales_order=?
    ORDER BY delivery_date ASC,id ASC
", array($data_edit->no_sales_order));

$suratJalan = $db->query("
    SELECT id,no_surat_jalan,tgl_surat_jalan,status,packing_list_no,delivery_no,total_qty
    FROM surat_jalan
    WHERE no_sales_order=? AND status<>'dibatalkan'
    ORDER BY tgl_surat_jalan ASC,id ASC
", array($data_edit->no_sales_order));

$goodsIssues = $db->query("
    SELECT id,gi_no,delivery_no,posting_date,status,total_qty,total_amount,outbound_bc_type,outbound_no_daftar
    FROM erp_goods_issue_delivery
    WHERE no_sales_order=?
    ORDER BY posting_date ASC,id ASC
", array($data_edit->no_sales_order));

$qtySo = $status_so ? (float)$status_so->qty_so : (float)$subtotal->total_qty;
$qtyProduksi = $status_so ? (float)$status_so->qty_produksi : 0;
$qtyKirim = $status_so ? (float)$status_so->qty_kirim : 0;
$progressProd = $qtySo > 0 ? min(100, ($qtyProduksi / $qtySo) * 100) : 0;
$progressShip = $qtySo > 0 ? min(100, ($qtyKirim / $qtySo) * 100) : 0;
$customerName = $customer ? $customer->nama : ($status_so ? $status_so->nama : '');
?>
<style>
.so-detail-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.so-detail-hero h1{margin:0 0 6px;font-size:25px;font-weight:700}.so-detail-hero p{margin:0;opacity:.92}
.so-detail-card{border-radius:14px;background:#fff;border:1px solid #e5edf5;margin-bottom:16px;box-shadow:0 6px 18px rgba(15,23,42,.06)}
.so-detail-card .card-head{padding:14px 16px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.so-detail-card .card-head h4{margin:0;font-size:15px;font-weight:700}.so-detail-card .card-body{padding:16px}
.so-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:14px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.so-kpi span{display:block;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.04em}.so-kpi strong{display:block;font-size:22px;margin-top:6px;color:#111827}
.so-kpi i{float:right;font-size:24px;color:#0f766e;opacity:.55}.so-info-table th{width:160px;background:#f8fafc;color:#475569}
.so-detail-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc!important;color:#475569;vertical-align:middle}.so-detail-table td{font-size:12px;vertical-align:middle}
.so-progress{height:7px;margin:5px 0 0;background:#e5edf5}.so-muted{color:#64748b}.so-action-bar .btn{margin-left:4px;margin-bottom:4px}
</style>

<section class="content-header">
  <h1><?=sd_h('sales_order', 'Sales Order');?> <small><?=sd_h('common_detail', 'Detail');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>sales-order"><?=sd_h('sales_order', 'Sales Order');?></a></li>
    <li class="active"><?=sd_h('common_detail', 'Detail');?></li>
  </ol>
</section>

<section class="content">
  <div class="so-detail-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=so_detail_h($data_edit->no_sales_order);?></h1>
        <p><?=so_detail_h(($customerName ?: '-').' | Customer PO: '.($data_edit->no_po ?: '-'));?></p>
      </div>
      <div class="col-md-4 text-right so-action-bar">
        <a href="<?=base_index();?>sales-order" class="btn btn-default"><i class="fa fa-step-backward"></i> Back</a>
        <a href="<?=base_index();?>sales-order/edit/<?=intval($data_edit->id_sales_order);?>" class="btn btn-warning"><i class="fa fa-pencil"></i> <?=sd_h('common_edit', 'Edit');?></a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-cubes"></i><span>Qty Sales Order</span><strong><?=so_detail_num($qtySo);?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-industry"></i><span>Qty Produksi</span><strong><?=so_detail_num($qtyProduksi);?></strong><div class="progress so-progress"><div class="progress-bar progress-bar-primary" style="width:<?=round($progressProd,2);?>%"></div></div></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-truck"></i><span>Qty Delivery</span><strong><?=so_detail_num($qtyKirim);?></strong><div class="progress so-progress"><div class="progress-bar progress-bar-success" style="width:<?=round($progressShip,2);?>%"></div></div></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-money"></i><span>Total Amount</span><strong><?=so_detail_h($data_edit->currency ?: '-');?> <?=so_detail_money($subtotal->total_amount);?></strong></div></div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-file-text-o"></i> Sales Order Information</h4><?=so_detail_status_label($status_so ? $status_so->status_so : 'OPEN');?></div>
        <div class="card-body">
          <table class="table table-bordered table-condensed so-info-table">
            <tr><th>SO Number</th><td><?=so_detail_h($data_edit->no_sales_order);?></td></tr>
            <tr><th>Order Type</th><td><?=so_detail_h($data_edit->order_type ?: 'OR');?></td></tr>
            <tr><th>Sales Area</th><td><?=so_detail_h(trim(($salesArea ? $salesArea->org_code.' - '.$salesArea->org_name : '-').' / '.($salesArea ? $salesArea->channel_code.' - '.$salesArea->channel_name : '-').' / '.($data_edit->division_code ?: '00'), ' /'));?></td></tr>
            <tr><th>SO Date</th><td><?=so_detail_date($data_edit->so_date);?></td></tr>
            <tr><th><?=sd_h('sales_customer_po', 'Customer PO');?></th><td><?=so_detail_h($data_edit->no_po ?: '-');?></td></tr>
            <tr><th>Invoice Ref</th><td><?=so_detail_h($data_edit->no_sales_invoice ?: '-');?></td></tr>
            <tr><th><?=sd_h('sales_currency', 'Currency');?></th><td><?=so_detail_h($data_edit->currency ?: '-');?></td></tr>
            <tr><th>Incoterm</th><td><?=so_detail_h($data_edit->incoterm ?: '-');?></td></tr>
            <tr><th>Delivery Term</th><td><?=so_detail_h($data_edit->delivery_term ?: '-');?></td></tr>
            <tr><th><?=sd_h('sales_payment_term', 'Payment Term');?></th><td><?=so_detail_h($data_edit->payment_term ?: $data_edit->term ?: '-');?></td></tr>
            <tr><th>Delivery / Billing Block</th><td><?=so_detail_h(($data_edit->delivery_block ?: '-').' / '.($data_edit->billing_block ?: '-'));?></td></tr>
            <tr><th>Sales / Entry User</th><td><?=so_detail_h(trim(($data_edit->sales_id ?: '-').' / '.($data_edit->user ?: '-'), ' /'));?></td></tr>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-user"></i> Customer & Approval</h4><?=so_detail_approval_label($data_edit->approval_status);?></div>
        <div class="card-body">
          <table class="table table-bordered table-condensed so-info-table">
            <tr><th><?=sd_h('sales_customer', 'Customer');?></th><td><?=so_detail_h(($data_edit->kode_penerima ?: '-').' - '.$customerName);?></td></tr>
            <tr><th>Sold-to Party</th><td><?=so_detail_h($data_edit->sold_to_party ?: $data_edit->kode_penerima ?: '-');?></td></tr>
            <tr><th>Ship-to Party</th><td><?=so_detail_h($data_edit->ship_to_party ?: $data_edit->kode_penerima ?: '-');?></td></tr>
            <tr><th>Bill-to Party</th><td><?=so_detail_h($data_edit->bill_to_party ?: $data_edit->kode_penerima ?: '-');?></td></tr>
            <tr><th>Payer</th><td><?=so_detail_h($data_edit->payer ?: $data_edit->kode_penerima ?: '-');?></td></tr>
            <tr><th>NPWP</th><td><?=so_detail_h($customer ? $customer->npwp : '-');?></td></tr>
            <tr><th><?=sd_h('sales_address', 'Address');?></th><td><?=so_detail_h($customer && $customer->alamat ? $customer->alamat : ($data_edit->shipping_address ?: '-'));?></td></tr>
            <tr><th>Ship To</th><td><?=so_detail_h($data_edit->shipping_address ?: '-');?></td></tr>
            <tr><th>Submitted</th><td><?=so_detail_h(trim(($data_edit->submitted_by ?: '-').' / '.($data_edit->submitted_at ?: '-'), ' /'));?></td></tr>
            <tr><th><?=sd_h('sales_approved', 'Approved');?></th><td><?=so_detail_h(trim(($data_edit->approved_by ?: '-').' / '.($data_edit->approved_at ?: '-'), ' /'));?></td></tr>
            <tr><th>Notes</th><td><?=so_detail_h($data_edit->alasan ?: $data_edit->catatan ?: '-');?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="so-detail-card">
    <div class="card-head">
      <h4><i class="fa fa-list"></i> Sales Order Item Detail</h4>
      <span class="so-muted"><?=number_format((float)$subtotal->item_count,0,',','.');?> item</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed so-detail-table">
          <thead>
            <tr>
              <th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('sales_material', 'Material');?></th><th>Description</th><th>Store</th><th><?=sd_h('sales_uom', 'UOM');?></th>
              <th class="text-right">Order Qty</th><th class="text-right">Production</th><th class="text-right">Delivery</th>
              <th class="text-right">Picked</th><th class="text-right">Packed</th><th class="text-right">GI</th>
              <th class="text-right"><?=sd_h('sales_price', 'Price');?></th><th class="text-right"><?=sd_h('sales_amount', 'Amount');?></th><th>Remark</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach ($items as $item) { ?>
              <tr>
                <td class="text-center"><?=intval($no);?></td>
                <td><strong><?=so_detail_h($item->kd_barang);?></strong></td>
                <td><?=so_detail_h($item->nm_barang ?: '-');?></td>
                <td><?=so_detail_h($item->store ?: '-');?></td>
                <td><?=so_detail_h($item->satuan ?: '-');?></td>
                <td class="text-right"><?=so_detail_num($item->qty);?></td>
                <td class="text-right"><?=so_detail_num($item->qty_produksi);?><br><small class="text-muted">NG <?=so_detail_num($item->qty_ng);?></small></td>
                <td class="text-right"><?=so_detail_num($item->qty_delivery ?: $item->qty_surat_jalan);?></td>
                <td class="text-right"><?=so_detail_num($item->qty_picked);?></td>
                <td class="text-right"><?=so_detail_num($item->qty_packed);?></td>
                <td class="text-right"><?=so_detail_num($item->qty_gi);?></td>
                <td class="text-right"><?=so_detail_money($item->price);?></td>
                <td class="text-right"><?=so_detail_money($item->nilai);?></td>
                <td><?=so_detail_h($item->ket ?: '-');?></td>
              </tr>
            <?php $no++; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-truck"></i> <?=sd_h('sales_outbound_delivery', 'Outbound Delivery');?></h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-condensed so-detail-table">
              <thead><tr><th>Delivery</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('common_status', 'Status');?></th><th><?=sd_h('sales_picking', 'Picking');?></th><th>Packing</th><th>GI</th><th>References</th></tr></thead>
              <tbody>
                <?php $hasDelivery=false; foreach ($deliveries as $d) { $hasDelivery=true; ?>
                  <tr>
                    <td><?=so_detail_h($d->delivery_no);?></td><td><?=so_detail_date($d->delivery_date);?></td><td><?=so_detail_h($d->status);?></td>
                    <td><?=so_detail_h($d->picking_status);?></td><td><?=so_detail_h($d->packing_status);?></td><td><?=so_detail_h($d->gi_status);?></td>
                    <td><?=so_detail_h(trim($d->reference_packing_list.' '.$d->reference_surat_jalan.' '.$d->reference_gi));?></td>
                  </tr>
                <?php } if (!$hasDelivery) { ?><tr><td colspan="7" class="text-center text-muted">Belum ada Outbound Delivery.</td></tr><?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-file-text-o"></i> <?=sd_h('sales_surat_jalan', 'Surat Jalan');?></h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-condensed so-detail-table">
              <thead><tr><th>No SJ</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('common_status', 'Status');?></th><th><?=sd_h('sales_packing_list', 'Packing List');?></th><th>Delivery</th><th class="text-right"><?=sd_h('sales_qty', 'Qty');?></th></tr></thead>
              <tbody>
                <?php $hasSj=false; foreach ($suratJalan as $sj) { $hasSj=true; ?>
                  <tr><td><?=so_detail_h($sj->no_surat_jalan);?></td><td><?=so_detail_date($sj->tgl_surat_jalan);?></td><td><?=so_detail_h(strtoupper($sj->status));?></td><td><?=so_detail_h($sj->packing_list_no ?: '-');?></td><td><?=so_detail_h($sj->delivery_no ?: '-');?></td><td class="text-right"><?=so_detail_num($sj->total_qty);?></td></tr>
                <?php } if (!$hasSj) { ?><tr><td colspan="6" class="text-center text-muted">Belum ada Surat Jalan.</td></tr><?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-industry"></i> Production Receipt</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-condensed so-detail-table">
              <thead><tr><th>No Produksi</th><th>Production Order</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_material', 'Material');?></th><th>Description</th><th>Source</th><th class="text-right">OK</th><th class="text-right">NG</th></tr></thead>
              <tbody>
                <?php $hasProd=false; foreach ($productions as $prd) { $hasProd=true; ?>
                  <tr><td><?=so_detail_h($prd->no_bpb);?></td><td><?=so_detail_h($prd->no_production_order ?: '-');?></td><td><?=so_detail_date($prd->tgl_bpb);?></td><td><?=so_detail_h($prd->kode);?></td><td><?=so_detail_h($prd->nm_barang);?></td><td><?=so_detail_h($prd->source_doc);?></td><td class="text-right"><?=so_detail_num($prd->jumlah);?></td><td class="text-right"><?=so_detail_num($prd->qty_ng);?></td></tr>
                <?php } if (!$hasProd) { ?><tr><td colspan="8" class="text-center text-muted">Belum ada penerimaan produksi.</td></tr><?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="so-detail-card">
        <div class="card-head"><h4><i class="fa fa-sign-out"></i> <?=sd_h('sales_goods_issue_delivery', 'Goods Issue for Delivery');?></h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-condensed so-detail-table">
              <thead><tr><th>GI No</th><th>Delivery</th><th>Posting</th><th><?=sd_h('common_status', 'Status');?></th><th>BC</th><th class="text-right"><?=sd_h('sales_qty', 'Qty');?></th><th class="text-right"><?=sd_h('sales_amount', 'Amount');?></th></tr></thead>
              <tbody>
                <?php $hasGi=false; foreach ($goodsIssues as $gi) { $hasGi=true; ?>
                  <tr><td><?=so_detail_h($gi->gi_no);?></td><td><?=so_detail_h($gi->delivery_no);?></td><td><?=so_detail_date($gi->posting_date);?></td><td><?=so_detail_h($gi->status);?></td><td><?=so_detail_h(trim($gi->outbound_bc_type.' '.$gi->outbound_no_daftar));?></td><td class="text-right"><?=so_detail_num($gi->total_qty);?></td><td class="text-right"><?=so_detail_money($gi->total_amount);?></td></tr>
                <?php } if (!$hasGi) { ?><tr><td colspan="7" class="text-center text-muted">Belum ada Goods Issue for Delivery.</td></tr><?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
