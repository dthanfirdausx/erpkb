<style>
  #dtb_gr_without_po .gr-action-buttons {
    white-space: nowrap;
    min-width: 82px;
  }
  #dtb_gr_without_po .gr-action-buttons .btn {
    margin-right: 3px;
  }
  .gr-without-po-hero {
    border-radius: 10px;
    background: linear-gradient(135deg, #8a6d3b 0%, #f39c12 100%);
    color: #fff;
    padding: 18px 22px;
    margin-bottom: 15px;
    box-shadow: 0 8px 22px rgba(0,0,0,.12);
  }
  .gr-without-po-hero h3 { margin: 0 0 6px; font-weight: 600; }
  .gr-without-po-hero p { margin: 0; opacity: .92; }
</style>
<section class="content-header">
  <h1>GR Without Purchase Order <small>SAP MM Movement Type 501</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>gr-without-po">Goods Receipt</a></li>
    <li class="active">GR Without PO</li>
  </ol>
</section>

<section class="content">
  <?php
  $grSummary = $db->fetch("SELECT
      COUNT(*) AS total_doc,
      COALESCE(SUM(CASE WHEN COALESCE(status,'POSTED')='POSTED' THEN 1 ELSE 0 END),0) AS posted_doc,
      COALESCE(SUM(CASE WHEN COALESCE(status,'POSTED')='REVERSED' THEN 1 ELSE 0 END),0) AS reversed_doc
    FROM pemasukan
    WHERE nopo='GR_WITHOUT_PO'");
  $grItemSummary = $db->fetch("SELECT COALESCE(COUNT(d.id),0) AS total_item
    FROM pemasukan p
    JOIN pemasukan_detail d ON d.no_bpb=p.no_bpb
    WHERE p.nopo='GR_WITHOUT_PO'");
  ?>
  <div class="gr-without-po-hero">
    <h3><i class="fa fa-download"></i> GR Without Purchase Order Workbench</h3>
    <p>Penerimaan barang non-PO dikontrol dengan reason/reference, lokasi, dokumen pabean, material document, stock layer, dan jurnal otomatis.</p>
  </div>

  <div class="row">
    <div class="col-md-3 col-sm-6">
      <div class="small-box bg-yellow">
        <div class="inner"><h3><?=number_format((float)$grSummary->total_doc,0,',','.');?></h3><p>Total Dokumen</p></div>
        <div class="icon"><i class="fa fa-file-text-o"></i></div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="small-box bg-green">
        <div class="inner"><h3><?=number_format((float)$grSummary->posted_doc,0,',','.');?></h3><p>Posted</p></div>
        <div class="icon"><i class="fa fa-check-circle"></i></div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="small-box bg-red">
        <div class="inner"><h3><?=number_format((float)$grSummary->reversed_doc,0,',','.');?></h3><p>Reversed</p></div>
        <div class="icon"><i class="fa fa-undo"></i></div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="small-box bg-aqua">
        <div class="inner"><h3><?=number_format((float)$grItemSummary->total_item,0,',','.');?></h3><p>Total Item</p></div>
        <div class="icon"><i class="fa fa-cubes"></i></div>
      </div>
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <a class="btn btn-primary btn-sm" href="<?=base_index();?>gr-without-po/tambah"><i class="fa fa-plus"></i> Create GR Without PO</a>
      <a class="btn btn-default btn-sm" href="<?=base_index();?>pemasukan-hamparan"><i class="fa fa-shopping-cart"></i> GR for PO</a>
    </div>

    <div class="box-body">
      <form id="filter_gr_without_po" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" class="form-control" id="filter_tgl_awal" placeholder="tanggal awal" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" class="form-control" id="filter_tgl_akhir" placeholder="tanggal akhir" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-2">Vendor</label>
          <div class="col-lg-4">
            <select id="filter_vendor" class="form-control">
              <option value="">Semua Vendor</option>
              <?php foreach ($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $vendor) { ?>
                <option value="<?=htmlspecialchars($vendor->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($vendor->nama ?: $vendor->kode_pemasok,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2">
            <select id="filter_status" class="form-control">
              <option value="">Semua Status</option>
              <option value="POSTED">POSTED</option>
              <option value="REVERSED">REVERSED</option>
              <option value="REPLACED">REPLACED</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label>
          <div class="col-lg-4">
            <input type="text" id="filter_reference" class="form-control" placeholder="Cari reference / no dokumen / no aju">
          </div>
         
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"></label>
           <div class="col-lg-6">
            <button type="button" id="btn_filter_gr_without_po" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_gr_without_po" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
          </div>
        </div>
      </form>

      <hr>

      <div class="table-responsive">
        <table id="dtb_gr_without_po" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=wh_h(wh_t('common_action', 'Action'));?></th>
              <th>No BPB</th>
              <th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th>
              <th>Vendor</th>
              <th><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></th>
              <th>BC Document</th>
              <th>No Aju</th>
              <th>Items</th>
              <th>Valuta</th>
              <th><?=wh_h(wh_t('common_status', 'Status'));?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rows = $db->query("SELECT p.id,p.no_bpb,p.posting_date,p.tgl_bpb,p.pemasok,v.nama,p.ref_no,p.jenis_dokpab,p.no_dokpab,p.no_aju,p.valuta,p.status,p.is_reversal,
                                       (SELECT COUNT(*) FROM pemasukan_detail d WHERE d.no_bpb=p.no_bpb) AS item_count
                                FROM pemasukan p
                                LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
                                WHERE p.nopo='GR_WITHOUT_PO'
                                ORDER BY p.id DESC");
            foreach ($rows as $row) {
              $postingDate = $row->posting_date ?: $row->tgl_bpb;
              $postingFilter = substr((string) $postingDate, 0, 10);
              $vendorName = $row->nama ?: $row->pemasok;
              $status = $row->status ?: 'POSTED';
              $statusClass = ($status == 'REVERSED') ? 'danger' : (($status == 'REPLACED') ? 'warning' : 'success');
              $referenceFilter = trim($row->ref_no.' '.$row->jenis_dokpab.' '.$row->no_dokpab.' '.$row->no_aju);
              $itemDetails = array();
              $detailRows = $db->query("SELECT d.no_urut,d.kode,COALESCE(b.nm_barang,'') AS nm_barang,d.unit,d.jumlah,d.harga,d.nilai,d.valuta,d.lokasi,
                                               d.hs_code,d.customs_qty,d.customs_uom,d.customs_value,d.net_weight,d.gross_weight,
                                               d.package_type,d.package_qty,d.origin_country
                                        FROM pemasukan_detail d
                                        LEFT JOIN barang b ON b.kd_barang=d.kode
                                        WHERE d.no_bpb=?
                                        ORDER BY COALESCE(d.no_urut,d.id),d.id", array('no_bpb' => $row->no_bpb));
              if ($detailRows) {
                foreach ($detailRows as $detail) {
                  $itemDetails[] = array(
                    'line' => $detail->no_urut,
                    'kode' => $detail->kode,
                    'nama' => $detail->nm_barang,
                    'unit' => $detail->unit,
                    'qty' => number_format((float) $detail->jumlah, 5, ',', '.'),
                    'price' => number_format((float) $detail->harga, 5, ',', '.'),
                    'amount' => number_format((float) $detail->nilai, 5, ',', '.'),
                    'valuta' => $detail->valuta,
                    'lokasi' => $detail->lokasi,
                    'hs_code' => $detail->hs_code,
                    'customs_qty' => number_format((float) $detail->customs_qty, 5, ',', '.'),
                    'customs_uom' => $detail->customs_uom,
                    'customs_value' => number_format((float) $detail->customs_value, 5, ',', '.'),
                    'net_weight' => number_format((float) $detail->net_weight, 5, ',', '.'),
                    'gross_weight' => number_format((float) $detail->gross_weight, 5, ',', '.'),
                    'package_type' => $detail->package_type,
                    'package_qty' => number_format((float) $detail->package_qty, 3, ',', '.'),
                    'origin_country' => $detail->origin_country
                  );
                }
              }
              $itemDetailsJson = htmlspecialchars(json_encode($itemDetails), ENT_QUOTES, 'UTF-8');
            ?>
            <tr data-posting="<?=htmlspecialchars($postingFilter,ENT_QUOTES,'UTF-8');?>"
                data-vendor="<?=htmlspecialchars($row->pemasok,ENT_QUOTES,'UTF-8');?>"
                data-status="<?=htmlspecialchars($status,ENT_QUOTES,'UTF-8');?>"
                data-reference="<?=htmlspecialchars(strtolower($referenceFilter),ENT_QUOTES,'UTF-8');?>">
	              <td>
	                <div class="gr-action-buttons">
	                <button type="button" class="btn btn-primary btn-xs btn-toggle-items" data-items='<?=$itemDetailsJson;?>' data-toggle="tooltip" title="Show Item Detail">
	                  <i class="fa fa-plus"></i> <span class="badge"><?=number_format((float) $row->item_count, 0, ',', '.');?></span>
	                </button>
	                <?php if ($status === 'POSTED' && $row->is_reversal !== 'Y') { ?>
	                <button type="button" class="btn btn-warning btn-xs btn-reversal-gr-without-po" data-id="<?=htmlspecialchars($row->id,ENT_QUOTES,'UTF-8');?>" data-no-bpb="<?=htmlspecialchars($row->no_bpb,ENT_QUOTES,'UTF-8');?>" data-toggle="tooltip" title="<?=wh_h(wh_t('warehouse_reversal', 'Reversal'));?>">
	                  <i class="fa fa-undo"></i>
	                </button>
	                <?php } ?>
	                </div>
                <!--  <a href="<?=base_index();?>pemasukan-hamparan/detail/<?=htmlspecialchars($row->id,ENT_QUOTES,'UTF-8');?>" class="btn btn-success btn-xs" data-toggle="tooltip" title="<?=wh_h(wh_t('common_detail', 'Detail'));?>">
                  <i class="fa fa-eye"></i>
                </a> -->
              </td>
              <td><?=htmlspecialchars($row->no_bpb,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($postingDate,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($vendorName,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->ref_no,ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars(trim($row->jenis_dokpab.' '.$row->no_dokpab),ENT_QUOTES,'UTF-8');?></td>
              <td><?=htmlspecialchars($row->no_aju,ENT_QUOTES,'UTF-8');?></td>
              <td class="text-right"><?=number_format((float) $row->item_count, 0, ',', '.');?></td>
              <td><?=htmlspecialchars($row->valuta,ENT_QUOTES,'UTF-8');?></td>
              <td><span class="label label-<?=$statusClass;?>"><?=htmlspecialchars($status,ENT_QUOTES,'UTF-8');?></span></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
$(function() {
  if ($.fn.datepicker) {
    $('.filter-date').datepicker({
      autoclose: true,
      format: 'yyyy-mm-dd',
      todayHighlight: true
    });
  }

  if ($.fn.select2) {
    $('#filter_vendor, #filter_status').select2({
      width: '100%'
    });
  }

  var grWithoutPoTable = $('#dtb_gr_without_po').DataTable({
    pageLength: 25,
    order: [[2, 'desc']],
    responsive: true,
    dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
         "<'row'<'col-sm-6'l><'col-sm-6'p>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [
      {extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_export_excel', 'Export Excel'));?>', className: 'btn btn-success btn-sm', title: 'GR Without PO'},
      {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', title: 'GR Without PO'}
    ],
    columnDefs: [
      { targets: [0], orderable: false, searchable: false, className: 'text-center' },
      { targets: [7], className: 'text-right' }
    ]
  });

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatItemDetails(items) {
    if (!items || !items.length) {
      return '<div class="alert alert-info" style="margin:10px 0">Belum ada detail item untuk dokumen ini.</div>';
    }

    var html = '<div class="table-responsive" style="margin:10px 0">' +
      '<table class="table table-bordered table-condensed table-striped" style="margin-bottom:0;font-size:12px">' +
      '<thead>' +
      '<tr class="bg-gray">' +
      '<th style="width:40px"><?=wh_h(wh_t('table_no', 'No'));?></th>' +
      '<th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th>' +
      '<th class="text-right">GR Qty</th>' +
      '<th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th>' +
      '<th class="text-right">Price</th>' +
      '<th class="text-right"><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th>' +
      '<th>Valuta</th>' +
      '<th>Lokasi</th>' +
      '<th>HS Code</th>' +
      '<th class="text-right">Customs Qty</th>' +
      '<th>Customs UOM</th>' +
      '<th class="text-right">Customs Value</th>' +
      '<th class="text-right">Net Wgt</th>' +
      '<th class="text-right">Gross Wgt</th>' +
      '<th>Package</th>' +
      '<th>Origin</th>' +
      '</tr>' +
      '</thead><tbody>';

    $.each(items, function(index, item) {
      html += '<tr>' +
        '<td>' + escapeHtml(item.line || (index + 1)) + '</td>' +
        '<td><strong>' + escapeHtml(item.kode) + '</strong><br><span class="text-muted">' + escapeHtml(item.nama) + '</span></td>' +
        '<td class="text-right">' + escapeHtml(item.qty) + '</td>' +
        '<td>' + escapeHtml(item.unit) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.price) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.amount) + '</td>' +
        '<td>' + escapeHtml(item.valuta) + '</td>' +
        '<td>' + escapeHtml(item.lokasi) + '</td>' +
        '<td>' + escapeHtml(item.hs_code) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.customs_qty) + '</td>' +
        '<td>' + escapeHtml(item.customs_uom) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.customs_value) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.net_weight) + '</td>' +
        '<td class="text-right">' + escapeHtml(item.gross_weight) + '</td>' +
        '<td>' + escapeHtml(item.package_type) + ' / ' + escapeHtml(item.package_qty) + '</td>' +
        '<td>' + escapeHtml(item.origin_country) + '</td>' +
        '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
  }

  $('#dtb_gr_without_po tbody').on('click', '.btn-toggle-items', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var button = $(this);
    var tr = button.closest('tr');
    var row = grWithoutPoTable.row(tr);

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      button.removeClass('btn-warning').addClass('btn-primary').attr('title', 'Show Item Detail');
      button.find('i').removeClass('fa-minus').addClass('fa-plus');
      return;
    }

    var items = [];
    try {
      items = JSON.parse(button.attr('data-items') || '[]');
    } catch (err) {
      items = [];
    }

    row.child(formatItemDetails(items)).show();
    tr.addClass('shown');
    button.removeClass('btn-primary').addClass('btn-warning').attr('title', 'Hide Item Detail');
    button.find('i').removeClass('fa-plus').addClass('fa-minus');
  });

  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable.id !== 'dtb_gr_without_po') {
      return true;
    }

    var row = grWithoutPoTable.row(dataIndex).node();
    var posting = $(row).data('posting') || '';
    var vendor = String($(row).data('vendor') || '');
    var status = String($(row).data('status') || '');
    var reference = String($(row).data('reference') || '');
    var startDate = $('#filter_tgl_awal').val();
    var endDate = $('#filter_tgl_akhir').val();
    var selectedVendor = $('#filter_vendor').val();
    var selectedStatus = $('#filter_status').val();
    var referenceKeyword = $('#filter_reference').val().toLowerCase();

    if (startDate && posting < startDate) {
      return false;
    }

    if (endDate && posting > endDate) {
      return false;
    }

    if (selectedVendor && vendor !== selectedVendor) {
      return false;
    }

    if (selectedStatus && status !== selectedStatus) {
      return false;
    }

    if (referenceKeyword && reference.indexOf(referenceKeyword) === -1) {
      return false;
    }

    return true;
  });

  $('#btn_filter_gr_without_po').on('click', function() {
    grWithoutPoTable.draw();
  });

  $('#filter_reference').on('keyup', function(e) {
    if (e.keyCode === 13) {
      grWithoutPoTable.draw();
    }
  });

  $('#btn_reset_gr_without_po').on('click', function() {
    $('#filter_tgl_awal, #filter_tgl_akhir, #filter_reference').val('');
    $('#filter_vendor, #filter_status').val('').trigger('change');
    grWithoutPoTable.search('').columns().search('').draw();
  });

  $('#dtb_gr_without_po tbody').on('click', '.btn-reversal-gr-without-po', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var id = $(this).data('id');
    var noBpb = $(this).data('no-bpb');

    Swal.fire({
      title: 'Reversal GR Without PO',
      html:
        '<div class="text-left">' +
        '<label>No BPB</label>' +
        '<input type="text" class="swal2-input" value="' + escapeHtml(noBpb) + '" readonly>' +
        '<label>Reversal Date</label>' +
        '<input type="date" id="reversal_date" class="swal2-input" value="<?=date("Y-m-d");?>">' +
        '<label>Reason</label>' +
        '<textarea id="reason" class="swal2-textarea" placeholder="Masukkan alasan reversal..." required></textarea>' +
        '</div>',
      showCancelButton: true,
      confirmButtonText: 'Reversal',
      preConfirm: function() {
        var reason = $('#reason').val();
        var reversalDate = $('#reversal_date').val();
        if (!reversalDate) {
          Swal.showValidationMessage('Reversal Date wajib diisi.');
          return false;
        }
        if (!reason) {
          Swal.showValidationMessage('Reason wajib diisi.');
          return false;
        }
        return { reason: reason, reversal_date: reversalDate };
      }
    }).then(function(result) {
      if (!result.isConfirmed) {
        return;
      }

      $.ajax({
        url: "<?=base_admin();?>modul/gr_without_po/gr_without_po_action.php?act=reversal",
        type: "POST",
        dataType: "json",
        data: {
          id: id,
          reason: result.value.reason,
          reversal_date: result.value.reversal_date
        },
        success: function(res) {
          if (res && res[0] && res[0].status === 'good') {
            Swal.fire('Berhasil!', 'Reversal sukses. Dokumen reversal: ' + (res[0].reversal_no_bpb || ''), 'success')
              .then(function() { window.location.reload(); });
            return;
          }
          Swal.fire('Error!', (res && res[0] && res[0].error_message) ? res[0].error_message : 'Reversal gagal.', 'error');
        },
        error: function() {
          Swal.fire('Error!', 'Server error.', 'error');
        }
      });
    });
  });
});
</script>
