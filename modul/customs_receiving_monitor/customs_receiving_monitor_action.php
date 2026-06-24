<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function crm_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function crm_row($label, $value) {
  return '<tr><th>'.crm_h($label).'</th><td>'.($value === '' || $value === null ? '-' : crm_h($value)).'</td></tr>';
}

function crm_num($value, $dec = 5) {
  return number_format((float)$value, $dec, ',', '.');
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
if ($act === 'detail') {
  $noBpb = isset($_POST['no_bpb']) ? trim($_POST['no_bpb']) : '';
  if ($noBpb === '') {
    echo '<div class="alert alert-danger">No BPB tidak valid.</div>';
    exit;
  }

  $header = $db->fetch(
    "SELECT p.*,COALESCE(v.nama,p.pemasok) AS vendor_name,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name
     FROM pemasukan p
     LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
     LEFT JOIN erp_plant ep ON ep.id=p.plant_id
     LEFT JOIN erp_storage_location es ON es.id=p.storage_location_id
     WHERE p.no_bpb=?
     LIMIT 1",
    array('no_bpb' => $noBpb)
  );
  if (!$header) {
    echo '<div class="alert alert-danger">Goods Receipt tidak ditemukan.</div>';
    exit;
  }

  $items = $db->query(
    "SELECT d.*,b.nm_barang,im.import_rows,im.import_qty,im.import_value,im.import_weight
     FROM pemasukan_detail d
     LEFT JOIN barang b ON b.kd_barang=d.kode
     LEFT JOIN (
       SELECT no_bpb,kode,COUNT(*) AS import_rows,
              SUM(CAST(REPLACE(COALESCE(jumlah,'0'),',','') AS DECIMAL(20,5))) AS import_qty,
              SUM(CAST(REPLACE(COALESCE(nilai,'0'),',','') AS DECIMAL(20,5))) AS import_value,
              SUM(CAST(REPLACE(COALESCE(berat,'0'),',','') AS DECIMAL(20,5))) AS import_weight
       FROM import_pemasukan_temp
       GROUP BY no_bpb,kode
     ) im ON im.no_bpb COLLATE latin1_general_ci=d.no_bpb AND im.kode=d.kode
     WHERE d.no_bpb=?
     ORDER BY COALESCE(d.customs_item_no,d.no_urut,d.id),d.id",
    array('no_bpb' => $noBpb)
  );

  $summary = $db->fetch(
    "SELECT COUNT(*) AS item_count,SUM(jumlah) AS total_qty,SUM(COALESCE(customs_qty,jumlah)) AS customs_qty,
            SUM(nilai) AS total_value,SUM(COALESCE(customs_value,nilai)) AS customs_value,
            SUM(COALESCE(net_weight,berat,0)) AS net_weight,SUM(COALESCE(gross_weight,berat,0)) AS gross_weight,
            SUM(CASE WHEN COALESCE(hs_code,'')='' OR COALESCE(customs_qty,jumlah) IS NULL OR COALESCE(customs_uom,unit,'')='' THEN 1 ELSE 0 END) AS missing_item_customs
     FROM pemasukan_detail
     WHERE no_bpb=?",
    array('no_bpb' => $noBpb)
  );

  $import = $db->fetch(
    "SELECT COUNT(*) AS import_rows,COUNT(DISTINCT kode) AS import_items,
            SUM(CAST(REPLACE(COALESCE(jumlah,'0'),',','') AS DECIMAL(20,5))) AS import_qty,
            SUM(CAST(REPLACE(COALESCE(nilai,'0'),',','') AS DECIMAL(20,5))) AS import_value,
            SUM(CAST(REPLACE(COALESCE(berat,'0'),',','') AS DECIMAL(20,5))) AS import_weight
     FROM import_pemasukan_temp
     WHERE no_bpb=? OR no_aju=?",
    array('no_bpb' => $noBpb, 'no_aju' => $header->no_aju)
  );

  $missingHeader = trim((string)$header->no_aju) === '' || trim((string)$header->no_dokpab) === '' || trim((string)$header->jenis_dokpab) === '';
  $status = ($header->status === 'REVERSED' || $header->is_reversal === 'Y') ? 'REVERSED' : (($missingHeader || (int)$summary->missing_item_customs > 0) ? 'INCOMPLETE' : (((int)$import->import_rows <= 0) ? 'ERP_ONLY' : ((abs((float)$summary->customs_qty-(float)$import->import_qty)>0.00001 || abs((float)$summary->customs_value-(float)$import->import_value)>0.01) ? 'MISMATCH' : 'COMPLETE')));
  $statusClass = $status === 'COMPLETE' ? 'success' : ($status === 'ERP_ONLY' ? 'info' : ($status === 'INCOMPLETE' ? 'warning' : 'danger'));
  ?>
  <style>
    .crm-detail-head{border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:14px 16px;margin-bottom:14px}
    .crm-detail-head h3{margin:0 0 6px;font-size:20px}.crm-detail-head p{margin:0;color:#64748b}
    .crm-detail-table th{width:185px;background:#f8fafc}.crm-detail-table td,.crm-detail-table th{font-size:12px}
    .crm-items td,.crm-items th{font-size:12px;vertical-align:middle!important}
  </style>
  <div class="crm-detail-head">
    <div class="row">
      <div class="col-sm-8">
        <h3><?=crm_h($header->no_bpb);?> <small><?=crm_h(trim($header->jenis_dokpab.' '.$header->no_dokpab));?></small></h3>
        <p>No Aju <?=crm_h($header->no_aju);?> | Posting <?=crm_h($header->posting_date ?: $header->tgl_bpb);?> | <span class="label label-<?=$statusClass;?>"><?=crm_h($status);?></span></p>
      </div>
      <div class="col-sm-4 text-right">
        <h3><?=crm_num($summary->customs_qty);?></h3>
        <p>Customs Qty / Import Qty <?=crm_num($import->import_qty);?></p>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Goods Receipt Header</h4>
      <table class="table table-bordered table-condensed crm-detail-table">
        <?=crm_row('No BPB', $header->no_bpb);?>
        <?=crm_row('Posting Date', $header->posting_date ?: $header->tgl_bpb);?>
        <?=crm_row('Vendor', trim($header->pemasok.' - '.$header->vendor_name, ' -'));?>
        <?=crm_row('Purchase Order', $header->nopo);?>
        <?=crm_row('Invoice', $header->no_invoice);?>
        <?=crm_row('Delivery Order', $header->no_do);?>
        <?=crm_row('Plant', trim($header->plant_code.' - '.$header->plant_name, ' -'));?>
        <?=crm_row('Storage Location', trim($header->storage_code.' - '.$header->storage_name, ' -'));?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Customs Header</h4>
      <table class="table table-bordered table-condensed crm-detail-table">
        <?=crm_row('No Aju', $header->no_aju);?>
        <?=crm_row('Tanggal Aju', $header->tgl_aju);?>
        <?=crm_row('Jenis Dokumen', $header->jenis_dokpab);?>
        <?=crm_row('No Dokumen', $header->no_dokpab);?>
        <?=crm_row('Tanggal Dokumen', $header->tgl_dokpab);?>
        <?=crm_row('Kantor Pabean', $header->kantor_pabean);?>
        <?=crm_row('Negara Asal', $header->negara_asal);?>
        <?=crm_row('Customs Status', $header->customs_status);?>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>ERP Summary</h4>
      <table class="table table-bordered table-condensed crm-detail-table">
        <?=crm_row('Item Count', crm_num($summary->item_count,0));?>
        <?=crm_row('GR Qty', crm_num($summary->total_qty));?>
        <?=crm_row('Customs Qty', crm_num($summary->customs_qty));?>
        <?=crm_row('Customs Value', crm_num($summary->customs_value,2));?>
        <?=crm_row('Net Weight', crm_num($summary->net_weight));?>
        <?=crm_row('Gross Weight', crm_num($summary->gross_weight));?>
        <?=crm_row('Item Need Completion', crm_num($summary->missing_item_customs,0));?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Import / CEISA Temp Summary</h4>
      <table class="table table-bordered table-condensed crm-detail-table">
        <?=crm_row('Import Rows', crm_num($import->import_rows,0));?>
        <?=crm_row('Import Items', crm_num($import->import_items,0));?>
        <?=crm_row('Import Qty', crm_num($import->import_qty));?>
        <?=crm_row('Import Value', crm_num($import->import_value,2));?>
        <?=crm_row('Import Weight', crm_num($import->import_weight));?>
        <?=crm_row('Qty Variance', crm_num((float)$summary->customs_qty-(float)$import->import_qty));?>
        <?=crm_row('Value Variance', crm_num((float)$summary->customs_value-(float)$import->import_value,2));?>
      </table>
    </div>
  </div>
  <h4>Material & Customs Items</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-striped table-condensed crm-items">
      <thead>
        <tr>
          <th>Item</th><th><?=customs_h('material','Material');?></th><th>HS Code</th><th class="text-right">GR Qty</th><th>UOM</th><th class="text-right">Customs Qty</th><th>Customs UOM</th><th class="text-right">Value</th><th class="text-right">Net Weight</th><th class="text-right">Gross Weight</th><th>Package</th><th>Origin</th><th class="text-right">Import Qty</th><th class="text-right">Variance</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $item){
          $customsQty = $item->customs_qty !== null ? $item->customs_qty : $item->jumlah;
          $importQty = $item->import_qty !== null ? $item->import_qty : 0;
          $variance = (float)$customsQty - (float)$importQty;
          $rowClass = (trim((string)$item->hs_code)==='' || abs($variance)>0.00001) ? 'warning' : '';
        ?>
        <tr class="<?=$rowClass;?>">
          <td><?=crm_h($item->customs_item_no ?: $item->no_urut);?></td>
          <td><strong><?=crm_h($item->kode);?></strong><br><small><?=crm_h($item->nm_barang);?></small></td>
          <td><?=crm_h($item->hs_code);?></td>
          <td class="text-right"><?=crm_num($item->jumlah);?></td>
          <td><?=crm_h($item->unit);?></td>
          <td class="text-right"><?=crm_num($customsQty);?></td>
          <td><?=crm_h($item->customs_uom ?: $item->unit);?></td>
          <td class="text-right"><?=crm_num($item->customs_value !== null ? $item->customs_value : $item->nilai,2);?></td>
          <td class="text-right"><?=crm_num($item->net_weight);?></td>
          <td class="text-right"><?=crm_num($item->gross_weight);?></td>
          <td><?=crm_h(trim($item->package_type.' / '.$item->package_qty, ' /'));?></td>
          <td><?=crm_h($item->origin_country);?></td>
          <td class="text-right"><?=crm_num($importQty);?></td>
          <td class="text-right"><?=crm_num($variance);?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php
  exit;
}

echo '<div class="alert alert-danger">Aksi tidak dikenal.</div>';
?>
