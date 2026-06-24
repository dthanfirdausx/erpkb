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
function sj_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-m-d');
$canInsert = isset($role_act["insert_act"]) && $role_act["insert_act"] == "Y";
$canUpdate = isset($role_act["up_act"]) && $role_act["up_act"] == "Y";
$canDelete = isset($role_act["del_act"]) && $role_act["del_act"] == "Y";

$customers = $db->query("SELECT kode_penerima, nama FROM penerima ORDER BY nama ASC");
$summary = $db->fetch("
    SELECT
        COUNT(*) AS total_docs,
        SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft_docs,
        SUM(CASE WHEN status='dikirim' THEN 1 ELSE 0 END) AS sent_docs,
        COALESCE(SUM(print_count),0) AS print_count,
        COALESCE(SUM(total_qty),0) AS total_qty
    FROM surat_jalan
    WHERE tgl_surat_jalan BETWEEN ? AND ?
", array($defaultFrom, $defaultTo));
?>
<style>
.sj-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.sj-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.sj-hero p{margin:0;opacity:.92}
.sj-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.sj-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.sj-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.sj-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.sj-filter .form-group{margin-bottom:12px}
#dtb_surat_jalan th,#dtb_surat_jalan td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.sj-action .btn{margin-right:3px}.sj-action .btn:last-child{margin-right:0}.sj-detail-table th,.sj-detail-table td{font-size:12px;vertical-align:middle}
</style>

<section class="content-header">
    <h1><?=sd_h('sales_surat_jalan', 'Surat Jalan');?> <small>Delivery Document</small></h1>
    <ol class="breadcrumb">
        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
        <li><a href="#">Sales & Distribution</a></li>
        <li class="active"><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></li>
    </ol>
</section>

<section class="content">
    <div class="sj-hero">
        <div class="row">
            <div class="col-md-8">
                <h1><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></h1>
                <p>Memonitor dokumen pengiriman barang dari Packing List, status pengiriman, penerimaan, dan cetak dokumen operasional.</p>
            </div>
            <div class="col-md-4 text-right">
                <?php if ($canInsert) { ?>
                    <a href="<?=base_index();?>surat-jalan/tambah" class="btn btn-success"><i class="fa fa-plus"></i> Tambah Surat Jalan</a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3"><div class="sj-kpi"><i class="fa fa-file-text-o"></i><span>Total Surat Jalan</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
        <div class="col-sm-3"><div class="sj-kpi"><i class="fa fa-pencil-square-o"></i><span><?=sd_h('sales_draft', 'Draft');?></span><strong><?=number_format((float)$summary->draft_docs,0,',','.');?></strong></div></div>
        <div class="col-sm-3"><div class="sj-kpi"><i class="fa fa-truck"></i><span>Dikirim</span><strong><?=number_format((float)$summary->sent_docs,0,',','.');?></strong></div></div>
        <div class="col-sm-3"><div class="sj-kpi"><i class="fa fa-print"></i><span>Total Print</span><strong><?=number_format((float)$summary->print_count,0,',','.');?></strong></div></div>
    </div>

    <div class="box">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Surat Jalan</h3></div>
        <div class="box-body">
            <form class="form-horizontal sj-filter" onsubmit="return false;">
                <div class="form-group">
                    <label class="control-label col-lg-2">Tanggal SJ</label>
                    <div class="col-lg-2">
                        <div class="input-group date sj-date">
                            <input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>">
                            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="input-group date sj-date">
                            <input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>">
                            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                        </div>
                    </div>
                    <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label>
                    <div class="col-lg-5">
                        <select id="filter_customer" class="form-control">
                            <option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option>
                            <?php foreach ($customers as $c) { ?>
                                <option value="<?=sj_h($c->kode_penerima);?>"><?=sj_h($c->kode_penerima.' - '.$c->nama);?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-2"><?=sd_h('common_status', 'Status');?></label>
                    <div class="col-lg-2">
                        <select id="filter_status" class="form-control">
                            <option value="all"><?=sd_h('sales_all_status', 'All Status');?></option>
                            <option value="draft"><?=sd_h('sales_draft', 'Draft');?></option>
                            <option value="dikirim">Dikirim</option>
                            <option value="diterima">Diterima</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    <label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label>
                    <div class="col-lg-5">
                        <input id="filter_keyword" class="form-control" placeholder="No SJ / PL / OD / GI / SO / PO / customer / kendaraan">
                    </div>
                    <div class="col-lg-2">
                        <button id="btn_filter_sj" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <button id="btn_reset_sj" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button>
                        <button id="btn_excel_sj" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <div class="alert alert-warning error_data_delete" style="display:none">
                <button type="button" class="close hide_alert_notif">&times;</button>
                <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
            </div>
            <div class="table-responsive">
                <table id="dtb_surat_jalan" class="table table-bordered table-striped table-condensed" style="width:100%">
                    <thead>
                        <tr>
                            <th><?=sd_h('common_no', 'No');?></th>
                            <th><?=sd_h('common_action', 'Action');?></th>
                            <th>No. Surat Jalan</th>
                            <th>Tgl SJ</th>
                            <th>Doc Date</th>
                            <th>Posting</th>
                            <th>PL / OD / GI</th>
                            <th><?=sd_h('sales_order', 'Sales Order');?></th>
                            <th>No. PO</th>
                            <th><?=sd_h('sales_customer', 'Customer');?></th>
                            <th>Driver / Vehicle</th>
                            <th><?=sd_h('common_print', 'Print');?></th>
                            <th><?=sd_h('common_status', 'Status');?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div id="modal_detail" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" style="width:94%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detail Surat Jalan</h4>
            </div>
            <div class="modal-body" id="isi_detail"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=sd_h('common_close', 'Close');?></button>
            </div>
        </div>
    </div>
</div>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script type="text/javascript">
var sjCanUpdate = <?=$canUpdate ? 'true' : 'false';?>;
var sjCanDelete = <?=$canDelete ? 'true' : 'false';?>;

function sjFilters() {
    return {
        tgl_awal: $('#filter_tgl_awal').val(),
        tgl_akhir: $('#filter_tgl_akhir').val(),
        customer: $('#filter_customer').val(),
        status: $('#filter_status').val(),
        keyword: $('#filter_keyword').val()
    };
}

function sjStatusBadge(status) {
    status = String(status || '').toLowerCase();
    var cls = 'bg-gray';
    if (status === 'dikirim') cls = 'bg-blue';
    if (status === 'diterima') cls = 'bg-green';
    if (status === 'dibatalkan') cls = 'bg-red';
    return '<span class="badge '+cls+'">'+(status ? status.toUpperCase() : '-')+'</span>';
}

function sjError(message) {
    $('.isi_warning_delete').text(message || <?=sd_js('sales_surat_jalan_data_process_failed', 'Surat Jalan data failed to process.');?>);
    $('.error_data_delete').fadeIn();
}

$(function() {
    if ($.fn.datepicker) {
        $('.sj-date').datepicker({format:'yyyy-mm-dd', autoclose:true, todayHighlight:true});
    }
    if ($.fn.select2) {
        $('#filter_customer,#filter_status').select2({width:'100%'});
    }

    var dtb_surat_jalan = $("#dtb_surat_jalan").DataTable({
        bProcessing: true,
        bServerSide: true,
        pageLength: 25,
        ordering: false,
        dom: "<'row'<'col-sm-12'B>>" +
             "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [{extend:'collection', text:<?=sd_js('common_export_data', 'Export Data');?>, buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
        columnDefs: [
            {targets:[0,1,11,12], orderable:false, searchable:false},
            {targets:[0,1,11,12], className:'text-center'},
            {width:'42px', targets:0},
            {width:'128px', targets:1},
            {width:'180px', targets:6},
            {width:'64px', targets:11},
            {width:'92px', targets:12}
        ],
        fnCreatedRow: function(nRow, aData) {
            var id = aData[aData.length - 1];
            var status = String(aData[12] || '').toLowerCase();
            var buttons = '<div class="sj-action">';
            buttons += '<button type="button" class="btn btn-info btn-xs btn-sj-detail" data-id="'+id+'" title="<?=sd_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></button>';
            buttons += '<a href="<?=base_url();?>modul/surat_jalan/surat_jalan_print.php?id='+id+'" target="_blank" class="btn btn-primary btn-xs" title="<?=sd_h('common_print', 'Print');?>"><i class="fa fa-print"></i></a>';
            if (status === 'draft' && sjCanUpdate) {
                buttons += '<a href="<?=base_index();?>surat-jalan/edit/'+id+'" class="btn btn-warning btn-xs" title="<?=sd_h('common_edit', 'Edit');?>"><i class="fa fa-pencil"></i></a>';
            }
            if (status === 'draft' && sjCanDelete) {
                buttons += '<button type="button" class="btn btn-danger btn-xs btn-sj-delete" data-id="'+id+'" title="Hapus"><i class="fa fa-trash"></i></button>';
            }
            buttons += '</div>';
            $('td:eq(1)', nRow).html(buttons);
            $('td:eq(12)', nRow).html(sjStatusBadge(status));
            $(nRow).attr('id', 'line_' + id);
        },
        ajax: {
            url: '<?=base_admin();?>modul/surat_jalan/surat_jalan_data.php',
            type: 'post',
            data: function(d) { $.extend(d, sjFilters()); },
            error: function(xhr) {
                console.log(xhr.responseText);
                sjError(<?=sd_js('sales_surat_jalan_load_failed', 'Surat Jalan data failed to load.');?>);
            }
        },
        language: {
            processing: "Memproses...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            search: "Cari cepat:",
            paginate: {first:"Pertama", last:"Terakhir", next:"Selanjutnya", previous:"Sebelumnya"}
        }
    });

    $('#btn_filter_sj').on('click', function() { dtb_surat_jalan.draw(); });
    $('#filter_keyword').on('keyup', function(e) { if (e.keyCode === 13) dtb_surat_jalan.draw(); });
    $('#btn_reset_sj').on('click', function() {
        $('#filter_tgl_awal').val('<?=$defaultFrom;?>');
        $('#filter_tgl_akhir').val('<?=$defaultTo;?>');
        $('#filter_keyword').val('');
        $('#filter_customer,#filter_status').val('all').trigger('change');
        dtb_surat_jalan.draw();
    });
    $('#btn_excel_sj').on('click', function() {
        window.location = '<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=excel&' + $.param(sjFilters());
    });
    $(document).on('click', '.btn-sj-detail', function() {
        $('#loadnya').show();
        $.post('<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=show_detail', {id:$(this).data('id')}, function(html) {
            $('#loadnya').hide();
            $('#isi_detail').html(html);
            $('#modal_detail').modal('show');
        }).fail(function() {
            $('#loadnya').hide();
            sjError('Detail Surat Jalan gagal dibuka.');
        });
    });
    $(document).on('click', '.btn-sj-delete', function() {
        var id = $(this).data('id');
        if (!confirm('Apakah Anda yakin ingin menghapus Surat Jalan ini?')) return;
        $('#loadnya').show();
        $.getJSON('<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=delete&id=' + id, function(responseText) {
            $('#loadnya').hide();
            var ok = false;
            $.each(responseText || [], function(_, item) {
                if (item.status === 'good') ok = true;
                if (item.status === 'die') $('#informasi').modal('show');
                if (item.status === 'error') sjError(item.error_message || 'Surat Jalan gagal dihapus.');
            });
            if (ok) dtb_surat_jalan.draw(false);
        }).fail(function(xhr) {
            $('#loadnya').hide();
            console.log(xhr.responseText);
            sjError('Surat Jalan gagal dihapus.');
        });
    });
    $(document).on('click', '.hide_alert_notif', function() { $('.error_data_delete').hide(); });
});
</script>
