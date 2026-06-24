<!-- Content Header (Page header) -->
<style>
  #dtb_pemasukan_hamparan .gr-action-buttons {
    white-space: nowrap;
    min-width: 82px;
  }
  #dtb_pemasukan_hamparan .gr-action-buttons .btn {
    margin-right: 3px;
  }
  .gr-po-hero {
    border-radius: 10px;
    background: linear-gradient(135deg, #1f4f8f 0%, #00a65a 100%);
    color: #fff;
    padding: 18px 22px;
    margin-bottom: 15px;
    box-shadow: 0 8px 22px rgba(0,0,0,.12);
  }
  .gr-po-hero h3 { margin: 0 0 6px; font-weight: 600; }
  .gr-po-hero p { margin: 0; opacity: .92; }
</style>
                <section class="content-header">
                    <h1>
                        Pemasukan 
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>pemasukan-hamparan">Pemasukan </a></li>
                        <li class="active">Pemasukan List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                  <div class="gr-po-hero">
                    <h3><i class="fa fa-download"></i> GR for Purchase Order Workbench</h3>
                    <p>Penerimaan barang berdasarkan PO outstanding dengan kontrol plant, storage location/bin, dokumen pabean, stock layer, dan jurnal otomatis.</p>
                  </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                <a style="font-size: 14px" class="btn btn-primary  btn-sm" title="" data-caption="Add" href="<?=base_index();?>pemasukan-hamparan/tambah" data-original-title="Add"><span class="glyphicon glyphicon-plus ewIcon"></span>Tambah Data</a>
                                <div class="btn-group ewButtonGroup">
                                  <a class="btn btn-default ewAddEdit ewAdd btn-sm" title="Import From CEISA" data-caption="Import From CEISA" href="<?=base_url();?>index.php/pemasukan-hamparan/upload_tpb"><span class="glyphicon glyphicon-link" style="font-size:16px;"></span>Import</a></div>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
	                              <form id="filter_gr_for_po" class="form-horizontal" onsubmit="return false;">
	                                <div class="form-group">
	                                  <label class="control-label col-lg-2">Posting Date</label>
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
	                                  <label class="control-label col-lg-2">Status</label>
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
	                                  <label class="control-label col-lg-2">Reference</label>
	                                  <div class="col-lg-4">
	                                    <input type="text" id="filter_reference" class="form-control" placeholder="Cari no BPB / PO / invoice / dokumen / no aju">
	                                  </div>
	                                </div>

	                                <div class="form-group">
	                                  <label class="control-label col-lg-2"></label>
	                                  <div class="col-lg-6">
	                                    <button type="button" id="btn_filter_gr_for_po" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
	                                    <button type="button" id="btn_reset_gr_for_po" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
	                                  </div>
	                                </div>
	                              </form>

	                              <hr>
                                <div class="row">
                                    <div class="col-sm-12" style="text-align: right;margin-bottom: 10px">
                                    <button id="select_all" class="btn btn-primary btn-xs"><i class="fa fa-check-square-o"></i> <?php echo $lang["select_all"];?></button>
                                    <button id="deselect_all" class="btn btn-primary btn-xs"><i class="fa fa-remove"></i> <?php echo $lang["deselect_all"];?></button>
                                    <button id="bulk_delete" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> <?php echo $lang["delete_selected"];?></button> <span class="selected-data"></span>
                            </div>
                            </div>
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
                        <table id="dtb_pemasukan_hamparan" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Action</th>
                                  <th>No BPB</th>
                                  <th>Tgl BPB</th>
                                  <th>No PO</th>
                                  <th>Pemasok</th>
                                  <th>No Invoice</th>
                                  <th>Jenis Dokumen</th>
                                  <th>Nomor Dokumen</th>
                                  <th>Nomor Aju</th>
                                  <th>E-Faktur</th>
                                  <th>Tanggal E-Faktur</th>
                                  <th>Valuta</th>
                                  <th>Keterangan</th>
                                 <!--  <th>Action</th> -->
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                    </div><!-- /.box-body -->
                  </div><!-- /.box -->
                </div>
              </div>
                <div id="modal_detail" class="modal fade" role="dialog">
              <div class="modal-dialog modal-lg" style="width: 90%">

                <!-- Modal content-->
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Detail Pemasukan</h4>
                  </div>
                  <div class="modal-body" id="isi_detail">
                    
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                  </div>
                </div>

              </div>
            </div>
        <?php

            foreach ($db->fetch_all("sys_menu") as $isi) {

            //jika url = url dari table menu
            if (uri_segment(1)==$isi->url) {
              //check edit permission
              if ($role_act["up_act"]=="Y") {
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."pemasukan-hamparan/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
                $reversal = "<button data-id='+aData[indek]+' class=\"btn btn-warning btn-sm btn-reversal\" title=\"Reversal\"><i class=\"fa fa-undo\"></i> Reversal</button>";
              } else {
                  $edit ="";
                  $reversal="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/pemasukan_hamparan/pemasukan_hamparan_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_pemasukan_hamparan"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->
        <script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
        <script type="text/javascript">
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
     });

     $(document).on('click', '.btn-reversal', function(){

    var id = $(this).data('id');

    Swal.fire({
        title: 'Reversal Transaksi',
        html: `
            <input id="reversal_date" type="date" class="swal2-input" value="<?=date("Y-m-d");?>" required>
            <textarea id="reason" class="swal2-textarea" placeholder="Masukkan alasan reversal..." required></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Reversal',
        preConfirm: () => {
            const reversalDate = document.getElementById('reversal_date').value;
            const reason = document.getElementById('reason').value;
            if (!reversalDate) {
                Swal.showValidationMessage('Tanggal reversal wajib diisi!');
                return false;
            }
            if (!reason) {
                Swal.showValidationMessage('Reason wajib diisi!');
                return false;
            }
            return { reason: reason, reversal_date: reversalDate };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            $("#loadnya").show();

            $.ajax({
                url: "<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=reversal",
                type: "POST",
                dataType: "json",
                data: {
                    id: id,
                    reason: result.value.reason,
                    reversal_date: result.value.reversal_date
                },

                success: function(res){

                    $("#loadnya").hide();

                    if(res[0].status == 'good'){
                        Swal.fire('Berhasil!', 'Reversal sukses', 'success');
                        $("#dtb_pemasukan_hamparan").DataTable().ajax.reload();
                    }else{
                        Swal.fire('Error!', res[0].error_message, 'error');
                    }

                },
                error: function(){
                    $("#loadnya").hide();
                    Swal.fire('Error!', 'Server error', 'error');
                }
            });

        }

    });

});
      
    var dtb_pemasukan_hamparan = $("#dtb_pemasukan_hamparan").DataTable({
          "fnCreatedRow": function( nRow, aData, iDataIndex ) {

    var indek = aData.length-1;
    $(nRow).attr('id', 'line_'+aData[indek]);

    // 🔥 ambil kolom keterangan (index ke-13)
    var keterangan = aData[13];

    if (keterangan && keterangan.toString().toUpperCase().includes('REVERSED')) {
        $(nRow).css({
            "background-color": "#f2dede",  // merah soft
            "color": "#a94442"
        });
    }

}, 
              "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",

              buttons: [
              {
                 extend: 'collection',
                 text: 'Export Data',
                 buttons: [ 'pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5' ],

              }
              ], 
           'bProcessing': true,
            'bServerSide': true,
            
           'columnDefs': [
                {
            'width': '45px',
            'targets': 0,
            "searchable": false, "orderable": false,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          },
          {
            'width': '95px',
            'targets': 1,
            "searchable": false,
            'orderable': false,
            'className': 'dt-center'
          },
           {
        "targets": [10,11,12], // 🔥 kolom EFaktur & Tanggal EFaktur
        "visible": false
    }
             ],

    
	            'ajax':{
	              url :'<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_data.php',
	                 data:   function ( d ) {
	                    d.tgl_awal = $("#filter_tgl_awal").val();
	                    d.tgl_akhir = $("#filter_tgl_akhir").val();
	                    d.vendor = $("#filter_vendor").val();
	                    d.status = $("#filter_status").val();
	                    d.reference = $("#filter_reference").val();
	                  },
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_pemasukan_hamparan').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });

	function filter() {
	      dtb_pemasukan_hamparan.draw();
	  }

  $('#btn_filter_gr_for_po').on('click', function() {
      filter();
  });

  $('#filter_reference').on('keyup', function(e) {
      if (e.keyCode === 13) {
          filter();
      }
  });

  $('#btn_reset_gr_for_po').on('click', function() {
      $('#filter_tgl_awal, #filter_tgl_akhir, #filter_reference').val('');
      $('#filter_vendor, #filter_status').val('').trigger('change');
      dtb_pemasukan_hamparan.search('').columns().search('').draw();
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
      '<th style="width:40px">No</th>' +
      '<th>Material</th>' +
      '<th class="text-right">GR Qty</th>' +
      '<th>UOM</th>' +
      '<th class="text-right">Price</th>' +
      '<th class="text-right">Amount</th>' +
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

  $('#dtb_pemasukan_hamparan tbody').on('click', '.btn-toggle-items', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var button = $(this);
    var tr = button.closest('tr');
    var row = dtb_pemasukan_hamparan.row(tr);

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

  // $(document).on('click', '#dtb_pemasukan_hamparan tbody tr td', function(event) {
  //     var btn = $(this).find('button');
  //     if (btn.length == 0) {
  //         $(this).parents('tr').toggleClass('DTTT_selected selected');
  //         var selected = check_selected();
  //         init_selected();

  //     }
  // });

  function init_selected() {
      var selected = check_selected();
      var btn_hide = $('#select_all, #deselect_all, #bulk_delete, .selected-data');
      if (selected.length > 0) {
          btn_hide.show()
      } else {
          btn_hide.hide()
      }
  }


  function check_selected() {
      var table_select = $('#dtb_pemasukan_hamparan tbody tr.selected');
      var array_data_delete = [];
      table_select.each(function() {
          var check_data = $(this).find('.hapus_dtb_notif').attr('data-id');
          if (typeof check_data != 'undefined') {
              array_data_delete.push(check_data)
          }
      });
      $('.selected-data').text(array_data_delete.length + ' <?=$lang["selected_data"];?>');
      return array_data_delete
  }


  function select_deselect(type) {
      if (type == 'select') {
          $('#dtb_pemasukan_hamparan tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_pemasukan_hamparan tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }

 


/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_pemasukan_hamparan );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=del_massal',
            data: {data_ids:all_ids},
               success: function(responseText) {
                  $('#loadnya').hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=='die') {
                            $('#informasi').modal('show');
                          } else if(responseText[index].status=='error') {
                             $('.isi_warning_delete').text(responseText[index].error_message);
                             $('.error_data_delete').fadeIn();
                             $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                          } else if(responseText[index].status=='good') {
                            $('.error_data_delete').hide();
                               $('#loadnya').hide();
                               $(anSelected).remove();
                               dtb_pemasukan_hamparan.draw();
                          } else {
                             $('.isi_warning_delete').text(responseText[index].error_message);
                             $('.error_data_delete').fadeIn();
                             $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                          }
                    });
                }
            //async:false
        });

        $('#ucing').modal('hide');

    });

  });

  /* Get the rows which are currently selected */
  function fnGetSelected( oTableLocal )
  {
      return oTableLocal.$('tr.selected');
  }
</script>
