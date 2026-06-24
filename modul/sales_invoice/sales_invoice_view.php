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
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$invoiceKpi = $db->fetch("
  SELECT
    COUNT(*) invoice_count,
    SUM(CASE WHEN billing_status='POSTED' THEN 1 ELSE 0 END) posted_count,
    SUM(CASE WHEN billing_status='CANCELLED' THEN 1 ELSE 0 END) cancelled_count,
    COALESCE(SUM(gross_amount),0) gross_total
  FROM sales_invoice
  WHERE invoice_date BETWEEN ? AND ?
", array($defaultFrom, $defaultTo));

$edit = '';
$del = '';
foreach ($db->fetch_all("sys_menu") as $isi) {
  if (uri_segment(1) == $isi->url) {
    if ($role_act["up_act"] == "Y") {
      $edit = "<a data-id='+aData[indek]+' href=".base_index()."sales-invoice/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data\" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
    }
    if ($role_act['del_act'] == 'Y') {
      $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/sales_invoice/sales_invoice_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_sales_invoice"><i class="fa fa-trash"></i></button>';
    }
  }
}
?>

<style>
  .si-page .box {
    border-radius: 10px;
    border-top: 0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
  }
  .si-hero {
    background: linear-gradient(135deg, #1f4e79 0%, #2f80ed 100%);
    color: #fff;
    border-radius: 12px;
    padding: 22px 24px;
    margin-bottom: 18px;
    box-shadow: 0 10px 24px rgba(31, 78, 121, .22);
  }
  .si-hero h3 {
    margin: 0 0 6px;
    font-weight: 700;
  }
  .si-hero p {
    margin: 0;
    opacity: .92;
  }
  .si-hero .btn {
    margin-top: 8px;
    border-radius: 20px;
    font-weight: 600;
  }
  .si-kpi {
    border-radius: 10px;
    background: #fff;
    padding: 15px 16px;
    margin-bottom: 15px;
    border: 1px solid #e8edf3;
  }
  .si-kpi small {
    color: #73808c;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 700;
  }
  .si-kpi strong {
    display: block;
    margin-top: 7px;
    font-size: 22px;
    color: #1f2d3d;
  }
  .si-filter .form-group {
    margin-bottom: 10px;
  }
  .si-help {
    background: #f7fbff;
    border-left: 4px solid #2f80ed;
    color: #3d4b5c;
    padding: 10px 12px;
    border-radius: 6px;
    margin-bottom: 14px;
  }
  #dtb_sales_invoice {
    font-size: 12px;
  }
  #dtb_sales_invoice .btn {
    margin-right: 3px;
  }
  .si-table-title {
    font-weight: 700;
    color: #1f2d3d;
    margin: 0;
  }
</style>

<section class="content-header">
  <h1><?=sd_h('sales_invoice', 'Sales Invoice');?> <small>SAP SD Billing</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>sales-invoice"><?=sd_h('sales_invoice', 'Sales Invoice');?></a></li>
    <li class="active">Sales Invoice List</li>
  </ol>
</section>

<section class="content si-page">
  <div class="si-hero">
    <div class="row">
      <div class="col-sm-8">
        <h3>Sales Invoice Cockpit</h3>
        <p>Monitor billing document dari Surat Jalan, posting invoice, status cancel, dan export data billing dalam satu layar.</p>
      </div>
      <div class="col-sm-4 text-right">
        <?php foreach ($db->fetch_all("sys_menu") as $isi) {
          if (uri_segment(1)==$isi->url && $role_act["insert_act"]=="Y") { ?>
            <a href="<?=base_index();?>sales-invoice/tambah" class="btn btn-default">
              <i class="fa fa-plus"></i> <?php echo $lang["add_button"];?>
            </a>
        <?php }} ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">
      <div class="si-kpi">
        <small>Total Invoice</small>
        <strong><?=number_format((float)$invoiceKpi->invoice_count, 0, ',', '.');?></strong>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="si-kpi">
        <small><?=sd_h('sales_posted', 'Posted');?></small>
        <strong><?=number_format((float)$invoiceKpi->posted_count, 0, ',', '.');?></strong>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="si-kpi">
        <small><?=sd_h('sales_cancelled', 'Cancelled');?></small>
        <strong><?=number_format((float)$invoiceKpi->cancelled_count, 0, ',', '.');?></strong>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="si-kpi">
        <small>Gross Billing</small>
        <strong><?=number_format((float)$invoiceKpi->gross_total, 2, ',', '.');?></strong>
      </div>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="si-help">
        <i class="fa fa-info-circle"></i>
        Invoice dibuat dari Billing Due List. Dokumen berstatus POSTED tidak bisa diedit atau dihapus; gunakan Cancel Billing agar jejak akuntansi tetap rapi.
      </div>

      <form id="form_filter_invoice" class="form-horizontal si-filter">
        <div class="row">
          <div class="col-md-5">
            <div class="form-group">
              <label class="control-label col-sm-4">Invoice Date</label>
              <div class="col-sm-8">
                <div class="row">
                  <div class="col-xs-6">
                    <div class="input-group date" id="tgl1">
                      <input type="text" class="form-control" id="tgl_awal" value="<?=$defaultFrom;?>" autocomplete="off">
                      <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                    </div>
                  </div>
                  <div class="col-xs-6">
                    <div class="input-group date" id="tgl2">
                      <input type="text" class="form-control" id="tgl_akhir" value="<?=$defaultTo;?>" autocomplete="off">
                      <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label class="control-label col-sm-3"><?=sd_h('sales_customer', 'Customer');?></label>
              <div class="col-sm-9">
                <select id="customer" class="form-control chzn-select">
                  <option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option>
                  <?php foreach ($db->fetch_all("penerima") as $isi) {
                    echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                  } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-primary" id="btn_filter"><i class="fa fa-search"></i> <?=sd_h('common_filter', 'Filter');?></button>
              <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button>
              <button type="button" class="btn btn-success" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_excel', 'Excel');?></button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title si-table-title">Billing Documents</h3>
      <div class="box-tools pull-right">
        <button id="select_all" class="btn btn-primary btn-xs"><i class="fa fa-check-square-o"></i> <?php echo $lang["select_all"];?></button>
        <button id="deselect_all" class="btn btn-primary btn-xs"><i class="fa fa-remove"></i> <?php echo $lang["deselect_all"];?></button>
        <button id="bulk_delete" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> <?php echo $lang["delete_selected"];?></button>
        <span class="selected-data text-muted"></span>
      </div>
    </div>
    <div class="box-body table-responsive">
      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>
      <table id="dtb_sales_invoice" class="table table-bordered table-striped table-hover" style="width:100%">
        <thead>
          <tr>
            <th><?=sd_h('common_no', 'No');?></th>
            <th>Bill To</th>
            <th>Ship To</th>
            <th>Invoice Date</th>
            <th>Sales Invoice No</th>
            <th>PO No</th>
            <th>Term</th>
            <th><?=sd_h('sales_currency', 'Currency');?></th>
            <th>Ship Date</th>
            <th>No Do</th>
            <th><?=sd_h('common_status', 'Status');?></th>
            <th><?=sd_h('common_action', 'Action');?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript">
  function sinc_acc(){
    $("#loadnya").show();
    $.ajax({
      url : '<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=sync_acc',
      type : 'POST',
      dataType : 'JSON',
      success : function(data){
        $("#loadnya").hide();
        if (data.s) {
          Swal.fire({title:'Berhasil!',text:'Data berhasil disimpan!',icon:'success',confirmButtonText:'OK'});
        } else {
          var pesanx = "";
          data.d.forEach(function(item){
            item.d.forEach(function(pesan){ pesanx = pesan; });
          });
          Swal.fire({icon:"error",title:"Oops...",text:pesanx});
        }
      }
    });
  }

  $(function(){
    if ($.fn.select2) {
      $("#customer").select2({width:'100%'});
    } else if ($.fn.chosen) {
      $(".chzn-select").chosen({width:'100%'});
    }

    $("#tgl1,#tgl2").datepicker({
      format: "yyyy-mm-dd",
      autoclose: true,
      todayHighlight: true
    });

    var defaultFrom = "<?=$defaultFrom;?>";
    var defaultTo = "<?=$defaultTo;?>";

    var dtb_sales_invoice = $("#dtb_sales_invoice").DataTable({
      "fnCreatedRow": function(nRow, aData) {
        var indek = aData.length - 1;
        var statusHtml = String(aData[indek-1] || '');
        var buttons = '<a href="<?=base_index();?>sales-invoice/detail/'+aData[indek]+'" class="btn btn-info btn-sm detail_action" data-toggle="tooltip" title="<?=sd_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></a> ';
        buttons += '<a target="_BLANK" href="<?=base_url();?>modul/sales_invoice/print.php?id='+aData[indek]+'" class="btn btn-success btn-sm" data-toggle="tooltip" title="<?=sd_h('common_print', 'Print');?>"><i class="fa fa-print"></i></a> ';
        if (statusHtml.indexOf('POSTED') >= 0) {
          buttons += '<button type="button" class="btn btn-warning btn-sm btn-cancel-invoice" data-id="'+aData[indek]+'" title="Cancel Billing"><i class="fa fa-undo"></i></button>';
        } else {
          buttons += '<?=$edit;?> <?=$del;?>';
        }
        $('td:eq('+indek+')', nRow).html(buttons);
        $(nRow).attr('id', 'line_'+aData[indek]);
      },
      "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [{
        extend: 'collection',
        text:<?=sd_js('common_export_data', 'Export Data');?>,
        buttons: ['pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5']
      }],
      'bProcessing': true,
      'bServerSide': true,
      'columnDefs': [{
        'targets': [11],
        'orderable': false,
        'searchable': false
      }, {
        'width': '45px',
        'targets': 0,
        'orderable': false,
        'searchable': false,
        'className': 'dt-center'
      }, {
        'width': '125px',
        'targets': 11,
        'orderable': false,
        'searchable': false
      }],
      'ajax': {
        url: '<?=base_admin();?>modul/sales_invoice/sales_invoice_data.php',
        type: 'post',
        data: function(d){
          d.tgl_awal = $('#tgl_awal').val();
          d.tgl_akhir = $('#tgl_akhir').val();
          d.customer = $('#customer').val();
        },
        error: function(xhr){ console.log(xhr); }
      }
    });

    $('#btn_filter').click(function(){
      dtb_sales_invoice.draw();
    });

    $('#btn_reset').click(function(){
      $('#tgl_awal').val(defaultFrom);
      $('#tgl_akhir').val(defaultTo);
      $("#customer").val("all");
      if ($.fn.select2) {
        $("#customer").trigger("change");
      } else {
        $("#customer").trigger("chosen:updated");
      }
      dtb_sales_invoice.draw();
    });

    $('#btn_excel').click(function(){
      window.open(
        "<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=excel"+
        "&tgl_awal="+encodeURIComponent($('#tgl_awal').val())+
        "&tgl_akhir="+encodeURIComponent($('#tgl_akhir').val())+
        "&customer="+encodeURIComponent($('#customer').val())
      );
    });

    $(document).on('click','.btn-cancel-invoice',function(e){
      e.stopPropagation();
      var id = $(this).data('id');
      Swal.fire({
        title:'Cancel Billing Document?',
        input:'text',
        inputLabel:'Alasan cancel',
        inputPlaceholder:'Masukkan alasan cancel invoice',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Cancel Invoice',
        inputValidator:function(value){ if(!value) return 'Alasan wajib diisi'; }
      }).then(function(result){
        if(!result.isConfirmed) return;
        $.ajax({
          url:'<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=cancel',
          type:'POST',
          dataType:'json',
          data:{id:id,reason:result.value},
          success:function(responseText){
            var ok=false,msg='';
            $.each(responseText||[],function(_,r){ if(r.status==='good') ok=true; if(r.status==='error') msg=r.error_message; });
            if(ok){ Swal.fire('Success','Invoice berhasil dicancel.','success'); dtb_sales_invoice.draw(false); }
            else{ Swal.fire('Error',msg||'Cancel invoice gagal.','error'); }
          },
          error:function(xhr){ console.log(xhr.responseText); Swal.fire('Error','Cancel invoice gagal.','error'); }
        });
      });
    });

    $('#dtb_sales_invoice').on('draw.dt', function() {
      init_selected();
    });

    $('#select_all').on('click', function(){ select_deselect('select'); });
    $('#deselect_all').on('click', function(){ select_deselect('unselect'); });

    $(document).on('click', '#dtb_sales_invoice tbody tr td', function() {
      var btn = $(this).find('button,a');
      if (btn.length == 0) {
        $(this).parents('tr').toggleClass('DTTT_selected selected');
        init_selected();
      }
    });

    function init_selected() {
      var selected = check_selected();
      var btn_hide = $('#select_all, #deselect_all, #bulk_delete, .selected-data');
      if (selected.length > 0) btn_hide.show(); else btn_hide.hide();
    }

    function check_selected() {
      var table_select = $('#dtb_sales_invoice tbody tr.selected');
      var array_data_delete = [];
      table_select.each(function() {
        var check_data = $(this).find('.hapus_dtb_notif').attr('data-id');
        if (typeof check_data != 'undefined') array_data_delete.push(check_data);
      });
      $('.selected-data').text(array_data_delete.length + ' <?=$lang["selected_data"];?>');
      return array_data_delete;
    }

    function select_deselect(type) {
      if (type == 'select') $('#dtb_sales_invoice tbody tr').addClass('DTTT_selected selected');
      else $('#dtb_sales_invoice tbody tr').removeClass('DTTT_selected selected');
      init_selected();
    }

    $('#bulk_delete').click(function() {
      var anSelected = fnGetSelected(dtb_sales_invoice);
      var all_ids = check_selected().toString();
      $('#ucing').modal({ keyboard: false }).one('click', '#delete', function() {
        $('#loadnya').show();
        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: '<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=del_massal',
          data: {data_ids:all_ids},
          success: function(responseText) {
            $('#loadnya').hide();
            $.each(responseText, function(index) {
              if (responseText[index].status == 'die') {
                $('#informasi').modal('show');
              } else if (responseText[index].status == 'error') {
                $('.isi_warning_delete').text(responseText[index].error_message);
                $('.error_data_delete').fadeIn();
                $('html, body').animate({scrollTop: ($('.error_data_delete').first().offset().top)}, 500);
              } else if (responseText[index].status == 'good') {
                $('.error_data_delete').hide();
                $(anSelected).remove();
                dtb_sales_invoice.draw();
              } else {
                $('.isi_warning_delete').text(responseText[index].error_message);
                $('.error_data_delete').fadeIn();
                $('html, body').animate({scrollTop: ($('.error_data_delete').first().offset().top)}, 500);
              }
            });
          }
        });
        $('#ucing').modal('hide');
      });
    });

    function fnGetSelected(oTableLocal) {
      return oTableLocal.$('tr.selected');
    }

    init_selected();
  });
</script>
