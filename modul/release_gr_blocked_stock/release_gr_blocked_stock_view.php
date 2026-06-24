<style>
  #dtb_release_candidates .release-action-buttons,
  #dtb_release_history .release-action-buttons {
    white-space: nowrap;
  }
  .release-summary {
    margin-bottom: 15px;
  }
  .release-filter .form-group {
    margin-bottom: 10px;
  }
  .release-filter .control-label {
    text-align: left;
  }
</style>

<section class="content-header">
  <h1>Release GR Blocked Stock <small>SAP MM Movement Type 105</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>release-gr-blocked-stock">Goods Receipt</a></li>
    <li class="active">Release GR Blocked Stock</li>
  </ol>
</section>

<section class="content">
  <div class="row release-summary">
    <div class="col-md-4">
      <div class="small-box bg-yellow">
        <div class="inner">
          <?php
          $summary = $db->fetch("SELECT COUNT(*) AS layer_count, COALESCE(SUM(qty_sisa),0) AS total_qty FROM stock_layer WHERE stock_type='BLOCKED' AND qty_sisa>0");
          ?>
          <h3><?=number_format((float) $summary->total_qty, 2, ',', '.');?></h3>
          <p>Qty GR Blocked Outstanding</p>
        </div>
        <div class="icon"><i class="fa fa-lock"></i></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="small-box bg-green">
        <div class="inner">
          <?php $released = $db->fetch("SELECT COUNT(*) AS doc_count FROM detail_transaksi WHERE move_code='105'"); ?>
          <h3><?=number_format((float) $released->doc_count, 0, ',', '.');?></h3>
          <p>Material Document 105</p>
        </div>
        <div class="icon"><i class="fa fa-unlock"></i></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="small-box bg-aqua">
        <div class="inner">
          <h3>105</h3>
          <p>Release dari blocked ke unrestricted</p>
        </div>
        <div class="icon"><i class="fa fa-exchange"></i></div>
      </div>
    </div>
  </div>

  <div class="box box-warning">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-lock"></i> Outstanding GR Blocked Stock</h3>
      <div class="box-tools">
        <a class="btn btn-primary btn-sm" href="<?=base_index();?>release-gr-blocked-stock/tambah"><i class="fa fa-plus"></i> Create Release 105</a>
      </div>
    </div>
    <div class="box-body release-filter" style="border-bottom:1px solid #f4f4f4">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label class="col-sm-4 control-label"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label>
              <div class="col-sm-8">
                <input type="text" id="release_material_filter" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_code_material', 'Kode / nama material'));?>">
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="col-sm-4 control-label"><?=wh_h(wh_t('common_plant', 'Plant'));?></label>
              <div class="col-sm-8">
                <select id="release_plant_filter" class="form-control select2">
                  <option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option>
                  <?php
                  $plants = $db->query("SELECT plant_code,plant_name FROM erp_plant ORDER BY plant_code");
                  foreach ($plants as $plant) {
                  ?>
                    <option value="<?=htmlspecialchars($plant->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($plant->plant_code.' - '.$plant->plant_name,ENT_QUOTES,'UTF-8');?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <button type="button" id="release_reset_filter" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
          </div>
        </div>
      </form>
    </div>
    <div class="box-body">
      <div class="table-responsive">
        <table id="dtb_release_candidates" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=wh_h(wh_t('common_action', 'Action'));?></th>
              <th>Original BPB</th>
              <th>Posting 103</th>
              <th>PO</th>
              <th>Vendor</th>
              <th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th>
              <th><?=wh_h(wh_t('common_plant', 'Plant'));?></th>
              <th><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></th>
              <th><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></th>
              <th class="text-right">Blocked Qty</th>
              <th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th>
              <th>No Aju</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $candidates = $db->query("SELECT sl.id AS layer_id,sl.qty_sisa,sl.no_bpb,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,
                                             p.posting_date,p.tgl_bpb,p.nopo,p.pemasok,COALESCE(v.nama,p.pemasok) AS vendor_name,p.no_aju,
                                             d.no_urut,d.kode,d.unit,COALESCE(b.nm_barang,'') AS nm_barang,
                                             ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
                                      FROM stock_layer sl
                                      JOIN pemasukan_detail d ON d.id=sl.ref_id
                                      JOIN pemasukan p ON p.no_bpb=sl.no_bpb
                                      LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
                                      LEFT JOIN barang b ON b.kd_barang=d.kode
                                      LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
                                      LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
                                      LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
                                      WHERE sl.stock_type='BLOCKED' AND sl.qty_sisa>0
                                      ORDER BY p.posting_date DESC,sl.id DESC");
            foreach ($candidates as $row) {
              $postingDate = $row->posting_date ?: $row->tgl_bpb;
            ?>
            <tr>
              <td>
                <div class="release-action-buttons">
                  <button type="button" class="btn btn-info btn-xs btn-release-detail"
                    data-bpb="<?=htmlspecialchars($row->no_bpb,ENT_QUOTES,'UTF-8');?>"
                    data-posting="<?=htmlspecialchars($postingDate,ENT_QUOTES,'UTF-8');?>"
                    data-po="<?=htmlspecialchars($row->nopo,ENT_QUOTES,'UTF-8');?>"
                    data-vendor="<?=htmlspecialchars($row->vendor_name,ENT_QUOTES,'UTF-8');?>"
                    data-material="<?=htmlspecialchars($row->kode.' - '.$row->nm_barang,ENT_QUOTES,'UTF-8');?>"
                    data-plant="<?=htmlspecialchars($row->plant_code,ENT_QUOTES,'UTF-8');?>"
                    data-sloc="<?=htmlspecialchars(trim($row->storage_code.' - '.$row->storage_name),ENT_QUOTES,'UTF-8');?>"
                    data-bin="<?=htmlspecialchars(trim($row->bin_code.' - '.$row->bin_name),ENT_QUOTES,'UTF-8');?>"
                    data-qty="<?=htmlspecialchars(number_format((float) $row->qty_sisa, 5, ',', '.'),ENT_QUOTES,'UTF-8');?>"
                    data-uom="<?=htmlspecialchars($row->unit,ENT_QUOTES,'UTF-8');?>"
                    data-aju="<?=htmlspecialchars($row->no_aju,ENT_QUOTES,'UTF-8');?>"
                    title="<?=wh_h(wh_t('common_detail', 'Detail'));?>"><i class="fa fa-eye"></i></button>
                  <a class="btn btn-success btn-xs" href="<?=base_index();?>release-gr-blocked-stock/tambah/<?=htmlspecialchars($row->layer_id,ENT_QUOTES,'UTF-8');?>" title="<?=wh_h(wh_t('warehouse_release_stock', 'Release Stock'));?>">
                    <i class="fa fa-unlock"></i> Release
                  </a>
                </div>
              </td>
              <td><?=htmlspecialchars($row->no_bpb,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($postingDate,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->nopo,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->vendor_name,ENT_QUOTES,'UTF-8');?></td>
              <td><strong><?=htmlspecialchars($row->kode,ENT_QUOTES,'UTF-8');?></strong><br><span class="text-muted"><?=htmlspecialchars($row->nm_barang,ENT_QUOTES,'UTF-8');?></span></td>
              <td><?=htmlspecialchars($row->plant_code,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars(trim($row->storage_code.' - '.$row->storage_name),ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars(trim($row->bin_code.' - '.$row->bin_name),ENT_QUOTES,'UTF-8');?></td>
              <td class="text-right"><?=number_format((float) $row->qty_sisa, 5, ',', '.');?></td>
              <td><?=htmlspecialchars($row->unit,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->no_aju,ENT_QUOTES,'UTF-8');?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="box box-success">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-history"></i> Release History</h3>
    </div>
    <div class="box-body">
      <form class="form-horizontal release-filter" onsubmit="return false;">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label class="col-sm-4 control-label">History Search</label>
              <div class="col-sm-8">
                <input type="text" id="release_history_filter" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_material_doc_remark', 'Material doc / material / remark'));?>">
              </div>
            </div>
          </div>
        </div>
      </form>
      <div class="table-responsive">
        <table id="dtb_release_history" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>Material Doc</th>
              <th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th>
              <th>Original BPB</th>
              <th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th>
              <th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th>
              <th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th>
              <th>Created By</th>
              <th>Remark</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $history = $db->query("SELECT dt.no_ref,dt.posting_date,dt.ref_pengganti,dt.kd_barang,COALESCE(b.nm_barang,'') AS nm_barang,
                                          dt.qty,dt.uom,dt.created_by,dt.remark
                                   FROM detail_transaksi dt
                                   LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
                                   WHERE dt.move_code='105'
                                   ORDER BY dt.id_detail DESC");
            foreach ($history as $row) {
            ?>
            <tr>
              <td><?=htmlspecialchars($row->no_ref,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->posting_date,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->ref_pengganti,ENT_QUOTES,'UTF-8');?></td>
              <td><strong><?=htmlspecialchars($row->kd_barang,ENT_QUOTES,'UTF-8');?></strong><br><span class="text-muted"><?=htmlspecialchars($row->nm_barang,ENT_QUOTES,'UTF-8');?></span></td>
              <td class="text-right"><?=number_format((float) $row->qty, 5, ',', '.');?></td>
              <td><?=htmlspecialchars($row->uom,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->created_by,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->remark,ENT_QUOTES,'UTF-8');?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="release_detail_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Release GR Blocked Detail</h4></div>
    <div class="modal-body"><table class="table table-bordered table-condensed"><tbody id="release_detail_body"></tbody></table></div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<script>
$(function() {
  var candidateTable = $('#dtb_release_candidates').DataTable({
    pageLength: 25,
    order: [[2, 'desc']],
    dom: 'Bfrtip',
    buttons: [
      {extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm', title: 'Release GR Blocked Stock Outstanding'},
      {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', title: 'Release GR Blocked Stock Outstanding'}
    ],
    columnDefs: [
      { targets: [0], orderable: false, searchable: false, className: 'text-center' },
      { targets: [9], className: 'text-right' }
    ]
  });
  var historyTable = $('#dtb_release_history').DataTable({
    pageLength: 25,
    order: [[1, 'desc']],
    dom: 'Bfrtip',
    buttons: [
      {extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm', title: 'Release GR Blocked Stock History'},
      {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', title: 'Release GR Blocked Stock History'}
    ],
    columnDefs: [
      { targets: [4], className: 'text-right' }
    ]
  });
  if ($.fn.select2) {
    $('.select2').select2({width: '100%'});
  }
  $('#release_material_filter').on('keyup change', function() {
    candidateTable.search(this.value).draw();
  });
  $('#release_plant_filter').on('change', function() {
    candidateTable.column(6).search(this.value).draw();
  });
  $('#release_reset_filter').on('click', function() {
    $('#release_material_filter').val('');
    $('#release_plant_filter').val('').trigger('change');
    candidateTable.search('').columns().search('').draw();
  });
  $('#release_history_filter').on('keyup change', function() {
    historyTable.search(this.value).draw();
  });
  $(document).on('click', '.btn-release-detail', function() {
    var b=$(this),fields=[['Original BPB',b.data('bpb')],['Posting 103',b.data('posting')],['PO',b.data('po')],['Vendor',b.data('vendor')],['Material',b.data('material')],['Plant',b.data('plant')],['Storage Location',b.data('sloc')],['Storage Bin',b.data('bin')],['Blocked Qty',b.data('qty')],['UOM',b.data('uom')],['No Aju',b.data('aju')]],html='';
    $.each(fields,function(_,r){html+='<tr><th style="width:34%;background:#f7f9fb">'+$('<div>').text(r[0]).html()+'</th><td>'+$('<div>').text(r[1]||'-').html()+'</td></tr>';});
    $('#release_detail_body').html(html);$('#release_detail_modal').modal('show');
  });
});
</script>
