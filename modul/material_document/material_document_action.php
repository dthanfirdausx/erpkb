<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function mdoc_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mdoc_row($label, $value) {
  return '<tr><th>'.mdoc_h($label).'</th><td>'.($value === '' || $value === null ? '-' : mdoc_h($value)).'</td></tr>';
}

function mdoc_movement_desc($moveCode) {
  $labels = array(
    '101' => 'Goods Receipt',
    '102' => 'Goods Receipt Reversal',
    '103' => 'Goods Receipt to Blocked Stock',
    '104' => 'Blocked Stock Reversal',
    '105' => 'Release GR Blocked Stock',
    '122' => 'Return to Vendor',
    '501' => 'Goods Receipt without Purchase Order'
  );
  return isset($labels[$moveCode]) ? $labels[$moveCode] : 'Material Movement';
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
if ($act === 'detail') {
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) {
    echo '<div class="alert alert-danger">Material document tidak valid.</div>';
    exit;
  }

  $row = $db->fetch(
    "SELECT dt.*,
            b.nm_barang,b.satuan AS material_uom,b.type AS material_type,
            p.no_bpb AS gr_no_bpb,p.tgl_bpb,p.nopo,p.pemasok,p.no_invoice,p.no_do,p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,
            p.jenis_dokpab,p.tgl_dokpab,p.kantor_pabean,p.negara_asal,p.stock_type AS header_stock_type,
            pemasok.nama AS vendor_name,
            po.purchase_order_no,po.po_type,po.payment_term,po.currency,
            loc.plant_id,loc.storage_location_id,loc.storage_bin_id,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            ref.no_ref AS original_doc_no,ref.move_code AS original_move_code,ref.posting_date AS original_posting_date
     FROM detail_transaksi dt
     LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
     LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,''))
     LEFT JOIN pemasok ON pemasok.kode_pemasok=p.pemasok
     LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.ref_id
     LEFT JOIN (
       SELECT no_bpb,kode,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
       FROM stock_layer
       GROUP BY no_bpb,kode
     ) loc ON loc.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,'')) AND loc.kode=dt.kd_barang
     LEFT JOIN erp_plant ep ON ep.id=loc.plant_id
     LEFT JOIN erp_storage_location es ON es.id=loc.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=loc.storage_bin_id
     LEFT JOIN detail_transaksi ref ON ref.id_detail=dt.ref_detail_id
     WHERE dt.id_detail=?
     LIMIT 1",
    array('id' => $id)
  );

  if (!$row) {
    echo '<div class="alert alert-danger">Material document tidak ditemukan.</div>';
    exit;
  }

  $direction = $row->direction ?: ((float)$row->qty < 0 ? 'OUT' : 'IN');
  $directionClass = $direction === 'OUT' ? 'danger' : 'success';
  $docNo = $row->no_ref ?: $row->no_bpb;
  $docYear = $row->posting_date ? date('Y', strtotime($row->posting_date)) : '';
  ?>
  <style>
    .mdoc-detail-head{border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:14px 16px;margin-bottom:14px}
    .mdoc-detail-head h3{margin:0 0 6px;font-size:20px}.mdoc-detail-head p{margin:0;color:#64748b}
    .mdoc-detail-table th{width:190px;background:#f8fafc}.mdoc-detail-table td,.mdoc-detail-table th{font-size:12px}
  </style>
  <div class="mdoc-detail-head">
    <div class="row">
      <div class="col-sm-8">
        <h3><?=mdoc_h($docNo);?> <small>Year <?=mdoc_h($docYear);?> / Item <?=mdoc_h($row->no_urut ?: $row->id_detail);?></small></h3>
        <p><?=mdoc_h(mdoc_movement_desc($row->move_code));?> | Movement Type <?=mdoc_h($row->move_code);?> | <span class="label label-<?=$directionClass;?>"><?=mdoc_h($direction);?></span></p>
      </div>
      <div class="col-sm-4 text-right">
        <h3><?=number_format((float)$row->qty,5,',','.');?> <?=mdoc_h($row->uom);?></h3>
        <p>Amount <?=number_format((float)$row->amount,2,',','.');?></p>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Document Header</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('Material Document', $docNo);?>
        <?=mdoc_row('Posting Date', $row->posting_date);?>
        <?=mdoc_row('Document Date', $row->document_date);?>
        <?=mdoc_row('Created By', $row->created_by ?: $row->user);?>
        <?=mdoc_row('Source GR / BPB', $row->gr_no_bpb ?: $row->no_bpb);?>
        <?=mdoc_row('Reference Replacement', $row->ref_pengganti);?>
        <?=mdoc_row('Reference Type', $row->ref_type);?>
        <?=mdoc_row('Reason', $row->reason);?>
        <?=mdoc_row('Remark', $row->remark);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Material & Quantity</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('Material', trim($row->kd_barang.' - '.$row->nm_barang, ' -'));?>
        <?=mdoc_row('Material Type', $row->material_type);?>
        <?=mdoc_row('Movement Type', $row->move_code.' - '.mdoc_movement_desc($row->move_code));?>
        <?=mdoc_row('Direction', $direction);?>
        <?=mdoc_row('Quantity', number_format((float)$row->qty,5,',','.'));?>
        <?=mdoc_row('UOM', $row->uom ?: $row->material_uom);?>
        <?=mdoc_row('Price', number_format((float)$row->price,2,',','.'));?>
        <?=mdoc_row('Amount', number_format((float)$row->amount,2,',','.'));?>
        <?=mdoc_row('Weight', $row->weight);?>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Organization & Location</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('Plant', trim($row->plant_code.' - '.$row->plant_name, ' -'));?>
        <?=mdoc_row('Storage Location', trim($row->storage_code.' - '.$row->storage_name, ' -'));?>
        <?=mdoc_row('Storage Bin', trim($row->bin_code.' - '.$row->bin_name, ' -'));?>
        <?=mdoc_row('Legacy Location', $row->lokasi);?>
        <?=mdoc_row('Position', $row->posisi);?>
        <?=mdoc_row('Stock Type', $row->header_stock_type);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Purchasing & Vendor</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('Purchase Order', $row->purchase_order_no ?: $row->nopo);?>
        <?=mdoc_row('PO Type', $row->po_type);?>
        <?=mdoc_row('Vendor', trim($row->pemasok.' - '.$row->vendor_name, ' -'));?>
        <?=mdoc_row('Invoice', $row->no_invoice);?>
        <?=mdoc_row('Delivery Order', $row->no_do);?>
        <?=mdoc_row('Payment Term', $row->payment_term);?>
        <?=mdoc_row('Currency', $row->currency);?>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Customs Information</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('No Aju', $row->header_no_aju ?: $row->no_aju);?>
        <?=mdoc_row('Jenis Dokumen Pabean', $row->jenis_dokpab);?>
        <?=mdoc_row('No Dokumen Pabean', $row->header_no_dokpab ?: $row->no_dokpab);?>
        <?=mdoc_row('Tanggal Dokumen Pabean', $row->tgl_dokpab);?>
        <?=mdoc_row('Kantor Pabean', $row->kantor_pabean);?>
        <?=mdoc_row('Negara Asal', $row->negara_asal);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Reversal / Reference</h4>
      <table class="table table-bordered table-condensed mdoc-detail-table">
        <?=mdoc_row('Is Reversal', ((int)$row->is_reversal === 1 ? 'Yes' : 'No'));?>
        <?=mdoc_row('Original Detail ID', $row->ref_detail_id);?>
        <?=mdoc_row('Original Document', $row->original_doc_no);?>
        <?=mdoc_row('Original Movement', $row->original_move_code);?>
        <?=mdoc_row('Original Posting Date', $row->original_posting_date);?>
        <?=mdoc_row('Reference ID', $row->ref_id);?>
        <?=mdoc_row('Incoming Detail ID', $row->id_incoming_detail);?>
      </table>
    </div>
  </div>
  <?php
  exit;
}

echo '<div class="alert alert-danger">Aksi tidak dikenal.</div>';
?>
