

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <?php
                    foreach ($db->fetch_all("sys_menu") as $isi) {
                        if (uri_segment(1)==$isi->url) {
                            if ($role_act["insert_act"]=="Y") {
                    ?>
                    <a href="<?=base_index();?>surat-jalan/tambah" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Tambah Surat Jalan
                    </a>
                    <?php
                            }
                        }
                    }
                    ?>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12" style="text-align: right;margin-bottom: 10px">
                            <button id="select_all" class="btn btn-primary btn-xs"><i class="fa fa-check-square-o"></i> Pilih Semua</button>
                            <button id="deselect_all" class="btn btn-primary btn-xs"><i class="fa fa-remove"></i> Batal Pilih</button>
                            <button id="bulk_delete" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Hapus Terpilih</button> 
                            <span class="selected-data"></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning fade in error_data_delete" style="display:none">
                        <button type="button" class="close hide_alert_notif">&times;</button>
                        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
                    </div>
                    
                    <table id="dtb_surat_jalan" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. Surat Jalan</th>
                                <th>Tanggal</th>
                                <th>No. Sales Order</th>
                                <th>No. PO</th>
                                <th>Penerima</th>
                                <th>Sopir</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan diisi via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail -->
    <div id="modal_detail" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Detail Surat Jalan</h4>
                </div>
                <div class="modal-body" id="isi_detail">
                    <!-- Detail akan diisi via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
<?php
// Set tombol edit dan delete berdasarkan permission
$edit = "";
$del = "";
foreach ($db->fetch_all("sys_menu") as $isi) {
    if (uri_segment(1)==$isi->url) {
        if ($role_act["up_act"]=="Y") {
            $edit = "<a href='".base_index()."surat-jalan/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data\" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
        }
        if ($role_act['del_act']=='Y') {
            // PERBAIKAN DI SINI - Escape quote dengan benar
            $del = "<button data-id='+aData[indek]+' data-uri=\"".base_admin()."modul/surat_jalan/surat_jalan_action.php\" class=\"btn btn-danger hapus_dtb_notif btn-sm\" data-toggle=\"tooltip\" title=\"Hapus\" data-variable=\"dtb_surat_jalan\"><i class=\"fa fa-trash\"></i></button>";
        }
    }
}
?>

var dtb_surat_jalan = $("#dtb_surat_jalan").DataTable({
    "fnCreatedRow": function(nRow, aData, iDataIndex) {
        var indek = aData.length - 1;
        var kolom = aData.length - 9;
        
        // Format status dengan badge
        var statusText = aData[7]; // Kolom status (index 7)
        var badge_class = '';
        switch(statusText.toLowerCase()) {
            case 'draft': badge_class = 'bg-gray'; break;
            case 'dikirim': badge_class = 'bg-blue'; break;
            case 'diterima': badge_class = 'bg-green'; break;
            case 'dibatalkan': badge_class = 'bg-red'; break;
            default: badge_class = 'bg-gray';
        }
        
        // Format tanggal (index 2)
        var tanggal = aData[2];
        
        // Tombol aksi
        var buttons = '<div class="btn-group">' +
            '<button onclick="show_detail(\'' + aData[indek] + '\')" class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></button>' +
            '<a href="<?=base_url();?>modul/surat_jalan/surat_jalan_print.php?id=' + aData[indek] + '" class="btn btn-primary btn-sm" target="_blank" data-toggle="tooltip" title="Print"><i class="fa fa-print"></i></a>';
        
        // Tambah tombol edit jika status draft
        if (statusText.toLowerCase() == 'draft') {
            buttons += ' <a href="<?=base_index();?>surat-jalan/edit/' + aData[indek] + '" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Edit"><i class="fa fa-edit"></i></a>';
        }
        
        // Tambah tombol delete jika status draft
        if (statusText.toLowerCase() == 'draft') {
            buttons += ' <button onclick="delete_surat_jalan(\'' + aData[indek] + '\')" class="btn btn-danger btn-sm" data-toggle="tooltip" title="Hapus"><i class="fa fa-trash"></i></button>';
        }
        
        buttons += '</div>';
        
        // Set isi kolom
        $('td:eq(2)', nRow).html(tanggal); // Kolom Tanggal (index 2)
        $('td:eq(7)', nRow).html('<span class="badge ' + badge_class + '">' + statusText.toUpperCase() + '</span>'); // Kolom Status (index 7)
        $('td:eq(8)', nRow).html(buttons); // Kolom Action (index 8)
        
        $(nRow).attr('id', 'line_' + aData[indek]);
    },
    "dom": "<'row'<'col-sm-12'B>>" + 
           "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    "buttons": [
        {
            extend: 'collection',
            text: 'Export Data',
            buttons: ['pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5'],
        }
    ],
    "bProcessing": true,
    "bServerSide": true,
    "columnDefs": [
        {
            "width": "5%",
            "targets": 0,
            "searchable": false,
            "orderable": false,
            "className": "dt-center"
        },
        {
            "targets": [8], // Kolom Action
            "orderable": false,
            "searchable": false,
            "className": "dt-center",
            "width": "15%"
        }
    ],
    "ajax": {
        url: '<?=base_admin();?>modul/surat_jalan/surat_jalan_data.php',
        type: 'post',
        error: function(xhr, error, thrown) {
            console.log('Error loading data:', error);
            console.log('Response:', xhr.responseText);
        }
    },
    "order": [[1, "desc"]], // Order by kolom 1 (No. Surat Jalan) descending
    "language": {
        "processing": "Memproses...",
        "lengthMenu": "Tampilkan _MENU_ data per halaman",
        "zeroRecords": "Tidak ada data ditemukan",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(difilter dari _MAX_ total data)",
        "search": "Cari:",
        "paginate": {
            "first": "Pertama",
            "last": "Terakhir",
            "next": "Selanjutnya",
            "previous": "Sebelumnya"
        }
    }
});

// Fungsi untuk menampilkan detail
function show_detail(id) {
    $('#loadnya').show();
    $.ajax({
        type: 'POST',
        url: '<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=show_detail',
        data: {id: id},
        success: function(data) {
            $('#loadnya').hide();
            $("#isi_detail").html(data);
            $("#modal_detail").modal("show");
        },
        error: function(xhr, status, error) {
            $('#loadnya').hide();
            alert('Error loading detail: ' + error);
        }
    });
} 

// Fungsi untuk delete single
function delete_surat_jalan(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        $('#loadnya').show();
        $.ajax({
            type: 'GET',
            url: '<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=delete&id=' + id,
            dataType: 'json',
            success: function(responseText) {
                $('#loadnya').hide();
                $.each(responseText, function(index) {
                    if (responseText[index].status == 'die') {
                        $('#informasi').modal('show');
                    } else if(responseText[index].status == 'error') {
                        alert('Error: ' + responseText[index].error_message);
                    } else if(responseText[index].status == 'good') {
                        dtb_surat_jalan.ajax.reload();
                        init_selected();
                    }
                });
            },
            error: function(xhr, status, error) {
                $('#loadnya').hide();
                alert('Error deleting data: ' + error);
            }
        });
    }
}

// Fungsi untuk select/deselect semua
$('#select_all').on('click', function() {
    $('#dtb_surat_jalan tbody tr').addClass('selected');
    init_selected();
});

$('#deselect_all').on('click', function() {
    $('#dtb_surat_jalan tbody tr').removeClass('selected');
    init_selected();
});

// Fungsi untuk check selected rows
function check_selected() {
    var table_select = $('#dtb_surat_jalan tbody tr.selected');
    var array_data_delete = [];
    table_select.each(function() {
        var row_id = $(this).attr('id');
        if (row_id) {
            var id = row_id.replace('line_', '');
            array_data_delete.push(id);
        }
    });
    $('.selected-data').text(array_data_delete.length + ' data terpilih');
    return array_data_delete;
}

// Fungsi untuk init selected
function init_selected() {
    var selected = check_selected();
    var btn_hide = $('#select_all, #deselect_all, #bulk_delete, .selected-data');
    if (selected.length > 0) {
        btn_hide.show();
    } else {
        btn_hide.hide();
    }
}

// Event untuk select row
$(document).on('click', '#dtb_surat_jalan tbody tr td', function(event) {
    // Jangan select jika klik pada tombol
    if (!$(event.target).is('button') && !$(event.target).is('a') && !$(event.target).closest('button').length && !$(event.target).closest('a').length) {
        $(this).parent().toggleClass('selected');
        init_selected();
    }
});

// Bulk delete
$('#bulk_delete').click(function() {
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    
    if (all_ids === '') {
        alert('Pilih data yang akan dihapus terlebih dahulu!');
        return;
    }
    
    if (confirm('Apakah Anda yakin ingin menghapus ' + data_array_id.length + ' data terpilih?')) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=del_massal',
            data: {data_ids: all_ids},
            success: function(responseText) {
                $('#loadnya').hide();
                if (responseText && responseText.length > 0) {
                    $.each(responseText, function(index) {
                        if (responseText[index].status == 'die') {
                            $('#informasi').modal('show');
                        } else if(responseText[index].status == 'error') {
                            $('.isi_warning_delete').text(responseText[index].error_message);
                            $('.error_data_delete').fadeIn();
                            $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                        } else if(responseText[index].status == 'good') {
                            $('.error_data_delete').hide();
                            dtb_surat_jalan.ajax.reload();
                            init_selected();
                        } else {
                            $('.isi_warning_delete').text(responseText[index].error_message || 'Unknown error');
                            $('.error_data_delete').fadeIn();
                            $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                        }
                    });
                } else {
                    // Jika response kosong, anggap berhasil
                    dtb_surat_jalan.ajax.reload();
                    init_selected();
                }
            },
            error: function(xhr, status, error) {
                $('#loadnya').hide();
                alert('Error deleting data: ' + error);
            }
        });
    }
});

// Init selected saat halaman load
$(document).ready(function() {
    init_selected();
});

// Reload table setiap kali draw
$('#dtb_surat_jalan').on('draw.dt', function() {
    init_selected();
});
</script>