<?php
$kode = urldecode(uri_segment(3));
$summary = $db->fetch(
  "SELECT sl.kode AS kd_barang,COALESCE(b.nm_barang,sl.kode) AS nm_barang,COALESCE(b.satuan,'') AS satuan,
          COALESCE(b.kategori,b.kd_kategori,'') AS kategori,COALESCE(SUM(sl.qty_sisa),0) AS stock_qty,
          COUNT(sl.id) AS layer_count
   FROM stock_layer sl
   LEFT JOIN barang b ON b.kd_barang=sl.kode
   WHERE sl.ref_table='erp_gr_production' AND sl.kode=? AND sl.kode LIKE 'BJ%'
   GROUP BY sl.kode,COALESCE(b.nm_barang,sl.kode),COALESCE(b.satuan,''),COALESCE(b.kategori,b.kd_kategori,'')",
  array($kode)
);
$layers = $db->query(
  "SELECT sl.*,gp.gr_no,gp.no_production_order,gp.confirmation_no,gp.posting_date,gp.status AS gr_status,
          ep.plant_code,es.storage_code,eb.bin_code,vi.trace_status,vi.raw_material_count,vi.missing_document_rows
   FROM stock_layer sl
   LEFT JOIN erp_gr_production gp ON gp.id=sl.ref_id
   LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
   LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
   LEFT JOIN vw_production_output_trace_integrity vi ON vi.stock_layer_id=sl.id
   WHERE sl.ref_table='erp_gr_production' AND sl.kode=? AND sl.kode LIKE 'BJ%'
   ORDER BY sl.tgl_masuk DESC,sl.id DESC",
  array($kode)
);
$traces = $db->query(
  "SELECT gt.*,gp.gr_no,gp.no_production_order
   FROM erp_gr_production_trace gt
   JOIN stock_layer sl ON sl.id=gt.output_stock_layer_id
   LEFT JOIN erp_gr_production gp ON gp.id=gt.gr_id
   WHERE sl.kode=? AND sl.ref_table='erp_gr_production' AND sl.kode LIKE 'BJ%'
   ORDER BY gp.posting_date DESC,gt.raw_material_code,gt.no_aju,gt.no_dokpab,gt.id",
  array($kode)
);
?>
<section class="content-header">
  <h1>Detail Stock Barang Jadi Produksi</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>stock-barang-jadi-produksi">Stock Barang Jadi Produksi</a></li>
    <li class="active">Detail Trace</li>
  </ol>
</section>

<section class="content">
  <?php if (!$summary) { ?>
    <div class="alert alert-warning">Stock barang jadi produksi tidak ditemukan dari GR Production.</div>
    <a href="<?=base_index();?>stock-barang-jadi-produksi" class="btn btn-success"><i class="fa fa-step-backward"></i> <?=$lang["back_button"];?></a>
  <?php } else { ?>
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title"><?=htmlspecialchars($summary->kd_barang.' - '.$summary->nm_barang, ENT_QUOTES, 'UTF-8');?></h3>
      </div>
      <div class="box-body">
        <div class="row">
          <div class="col-sm-3"><div class="small-box bg-aqua"><div class="inner"><h3><?=number_format((float)$summary->stock_qty,5,',','.');?></h3><p>Stock <?=htmlspecialchars($summary->satuan, ENT_QUOTES, 'UTF-8');?></p></div></div></div>
          <div class="col-sm-3"><div class="small-box bg-green"><div class="inner"><h3><?=intval($summary->layer_count);?></h3><p>Stock Layer GR Production</p></div></div></div>
          <div class="col-sm-3"><div class="small-box bg-yellow"><div class="inner"><h3><?=htmlspecialchars($summary->kategori ?: '-', ENT_QUOTES, 'UTF-8');?></h3><p>Kategori</p></div></div></div>
          <div class="col-sm-3"><div class="small-box bg-purple"><div class="inner"><h3>Trace</h3><p>Raw Material & Dokumen BC</p></div></div></div>
        </div>

        <h4><i class="fa fa-cubes"></i> Stock Layer Hasil GR Production</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr class="bg-gray">
                <th>Layer</th><th>GR No</th><th>Production Order</th><th>Posting</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Sisa</th><th>Lokasi</th><th>Stock Type</th><th>Trace</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($layers as $layer) { ?>
              <tr>
                <td>#<?=intval($layer->id);?></td>
                <td><?=htmlspecialchars($layer->gr_no ?: $layer->no_bpb, ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($layer->no_production_order ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($layer->posting_date ?: $layer->tgl_masuk, ENT_QUOTES, 'UTF-8');?></td>
                <td class="text-right"><?=number_format((float)$layer->qty_masuk,5,',','.');?></td>
                <td class="text-right"><?=number_format((float)$layer->qty_sisa,5,',','.');?></td>
                <td><?=htmlspecialchars(trim($layer->plant_code.' / '.$layer->storage_code.' / '.$layer->bin_code, ' /') ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($layer->stock_type, ENT_QUOTES, 'UTF-8');?></td>
                <td><span class="label label-<?=($layer->trace_status==='OK'?'success':'danger');?>"><?=htmlspecialchars($layer->trace_status ?: 'BROKEN', ENT_QUOTES, 'UTF-8');?></span> <small><?=intval($layer->raw_material_count);?> raw</small></td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>

        <h4><i class="fa fa-random"></i> Trace Bahan Baku Asal & Dokumen BC</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr class="bg-gray">
                <th>GR</th><th>Raw Material</th><th>Source Material</th><th class="text-right">Qty Trace</th><th>UOM</th><th>Lot</th><th>Dok Pabean</th><th>No Aju</th><th>No BPB</th><th>HS Code</th><th>Trace</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$traces || $traces->rowCount() === 0) { ?>
              <tr><td colspan="11" class="text-center text-muted">Trace belum tersedia.</td></tr>
            <?php } else { foreach ($traces as $trace) { ?>
              <tr>
                <td><?=htmlspecialchars($trace->gr_no ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><strong><?=htmlspecialchars($trace->raw_material_code, ENT_QUOTES, 'UTF-8');?></strong><br><small><?=htmlspecialchars($trace->raw_material_name, ENT_QUOTES, 'UTF-8');?></small></td>
                <td><?=htmlspecialchars($trace->source_material_code ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td class="text-right"><?=number_format((float)$trace->qty,5,',','.');?></td>
                <td><?=htmlspecialchars($trace->uom ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($trace->lot_no ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars(trim($trace->jenis_dokpab.' '.$trace->no_dokpab) ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($trace->no_aju ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($trace->no_bpb ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($trace->hs_code ?: '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><span class="label label-info"><?=htmlspecialchars($trace->trace_source, ENT_QUOTES, 'UTF-8');?></span></td>
              </tr>
            <?php }} ?>
            </tbody>
          </table>
        </div>
        <a href="<?=base_index();?>stock-barang-jadi-produksi" class="btn btn-success"><i class="fa fa-step-backward"></i> <?=$lang["back_button"];?></a>
      </div>
    </div>
  <?php } ?>
</section>
