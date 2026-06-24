<!-- Content Header (Page header) -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">


    <section class="content-header">
        <h1><?=customs_h('legacy_laporan_scrap','Laporan Scrap');?></h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a>
            </li>
            <li>
              <a href="<?=base_index();?>laporan-scrap"><?=customs_h('scrap_report','Laporan Scrap');?></a>
            </li>
            <li class="active"><?=customs_h('legacy_add_laporan_scrap','Add Laporan Scrap');?></li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title"><?=customs_h('legacy_add_laporan_scrap','Add Laporan Scrap');?></h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_laporan_scrap" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/laporan_scrap/laporan_scrap_action.php?act=in">
                      
              <div class="form-group" style="display: none">
                <label for="Nomor" class="control-label col-lg-2"><?=customs_h('number','Nomor');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" value="<?= generate_no_scrap(date("Y"),date("m")) ?>"  placeholder="<?=customs_h('number','Nomor');?>" class="form-control" >
                </div>
              </div><!-- /.form-group --> 
              
              <div class="form-group">
                <label for="No Scrap" class="control-label col-lg-2">No Scrap </label>
                <div class="col-lg-10">
                  <input type="text" name="no_scrap" placeholder="No Scrap" value="<?= generate_no_scrap(date("Y"),date("m")) ?>" class="form-control" readonly >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Tanggal Scrap" class="control-label col-lg-2"><?=customs_h('scrap_date','Tanggal Scrap');?> </label>
                <div class="col-lg-3">
                 
                  <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_scrap" required="" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div> 
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                 <textarea 
    name="keterangan" 
    placeholder="<?=customs_h('remarks','Keterangan');?>" 
    class="form-control"
    rows="4"></textarea>
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Status" class="control-label col-lg-2"><?=customs_h('status','Status');?> </label>
                  <div class="col-lg-10">
                    
                <div class="radio radio-success ">
                  <input type="radio" name="status"  id="radio1" value="DRAFT" >
                    <label for="radio1" style="padding-left: 5px;">
                      DRAFT
                    </label>
                </div>
                
                <div class="radio radio-success ">
                  <input type="radio" name="status"  id="radio2" value="APPROVED" >
                    <label for="radio2" style="padding-left: 5px;">
                      APPROVED
                    </label>
                </div>
                
                <div class="radio radio-success ">
                  <input type="radio" name="status"  id="radio3" value="REJECTED" >
                    <label for="radio3" style="padding-left: 5px;">
                      REJECTED
                    </label>
                </div>
                
                  </div>
                </div><!-- /.form-group -->

                <hr>

<h4><i class="fa fa-recycle"></i> <?=customs_h('scrap_item_detail','Detail Item Scrap');?></h4>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="table_detail_scrap">
    <thead>
        <tr>
            <th width="5%"><?=customs_h('no','No');?></th>
            <th width="15%">No Laporan Produksi</th>
            <th width="15%"><?=customs_h('material_code','Kode Barang');?></th>
            <th width="25%"><?=customs_h('material_name','Nama Barang');?></th>
            <th width="10%">Qty Scrap</th>
            <th width="10%">Satuan</th>
            <th width="15%">Jenis Scrap</th>
            <th width="5%">#</th>
        </tr>
    </thead>

    <tbody>

    </tbody>
</table>
</div>

<button type="button" class="btn btn-success" id="tambah_detail">
    <i class="fa fa-plus"></i> Tambah Detail
</button>

<hr>

                
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>laporan-scrap" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script type="text/javascript">

var no = 0;

$("#tambah_detail").click(function(){

    no++;

    var html = '';

    html += '<tr>';

    html += '<td class="text-center">'+no+'</td>';

    // LAPORAN PRODUKSI
    html += '<td>';
    html += '<select name="no_laporan_produksi[]" ';
    html += 'class="form-control no_laporan_produksi">';
    html += '<option value="">Pilih LP</option>';
    html += '</select>';
    html += '</td>';

    // BARANG
    html += '<td>';
    html += '<select name="kode_barang_select[]" ';
    html += 'class="form-control kode_barang">';
    html += '<option value="">Pilih Barang</option>';
    html += '</select>';

   // html += '<input type="hidden" name="kode_barang[]">';

    html += '</td>';

    // NAMA BARANG
    html += '<td>';
    html += '<input type="text" ';
    html += 'name="nm_barang[]" ';
    html += 'class="form-control nm_barang" readonly>';
    html += '</td>';

    // QTY SCRAP
    html += '<td>';
    html += '<input type="number" ';
    html += 'step="0.00001" ';
    html += 'name="qty_scrap[]" ';
    html += 'class="form-control text-right">';
    html += '</td>';

    // SATUAN
    html += '<td>';
    html += '<input type="text" ';
    html += 'name="satuan[]" ';
    html += 'class="form-control" readonly>';
    html += '</td>';

    // JENIS SCRAP
    html += '<td>';

    html += '<select name="jenis_scrap[]" ';
    html += 'class="form-control">';

    html += '<option value="REJECT">REJECT</option>';
    html += '<option value="SETTING">SETTING</option>';
    html += '<option value="SISA">SISA</option>';
    html += '<option value="RUSAK">RUSAK</option>';

    html += '</select>';

    html += '</td>';

    // BUTTON HAPUS
    html += '<td class="text-center">';

    html += '<button type="button" ';
    html += 'class="btn btn-danger btn_remove">';

    html += '<i class="fa fa-trash"></i>';

    html += '</button>';

    html += '</td>';

    html += '</tr>';

    $("#table_detail_scrap tbody").append(html);

    // AMBIL ROW TERAKHIR
    var lastRow = $("#table_detail_scrap tbody tr:last");

    // SELECT2 LP
    lastRow.find(".no_laporan_produksi").select2({

        width : '300px',

        placeholder : 'Pilih Laporan Produksi',

        ajax : {

            url : '<?=base_admin();?>modul/laporan_scrap/laporan_scrap_action.php?act=get_laporan_produksi',

            dataType : 'json',

            delay : 250,

            processResults : function(data){

                return {
                    results : data
                };

            },

            cache : true

        }

    });

});


// HAPUS ROW
$(document).on("click",".btn_remove",function(){

    $(this).closest("tr").remove();

});


// CHAINING LP -> BARANG
$(document).on("change",".no_laporan_produksi",function(){

    var no_bpb = $(this).val();

    var row = $(this).closest("tr");

    $.ajax({

        url : '<?=base_admin();?>modul/laporan_scrap/laporan_scrap_action.php?act=get_barang_scrap',

        type : 'POST',

        data : {
            no_bpb : no_bpb
        },

        dataType : 'json',

        success : function(response){

          var html = '';

          html += '<option value="">Pilih Barang</option>';

          $.each(response,function(key,val){

              html += '<option ';
              html += 'value="'+val.kode+'" ';
              html += 'data-kode="'+val.kode+'" ';
              html += 'data-nama="'+val.nm_barang+'" ';
              html += 'data-satuan="'+val.satuan+'" ';
              html += '>';

              html += val.kode+' - '+val.nm_barang;

              html += '</option>';

          });

          row.find(".kode_barang").html(html);

      }

    });

});


// PILIH BARANG
$(document).on("change",".kode_barang",function(){

    var selected = $(this).find(':selected');

    var row = $(this).closest("tr");

    row.find(".nm_barang").val(
        selected.data('nama')
    );

    // row.find("input[name='kode_barang[]']").val(
    //     selected.data('kode')
    // );

    row.find("input[name='satuan[]']").val(
        selected.data('satuan')
    );

    row.find("input[name='qty_scrap[]']").val(
        selected.data('qty')
    );

    row.find("input[name='qty_scrap[]']")
        .attr('max',selected.data('qty'));

});


</script>

<script type="text/javascript">
    $(document).ready(function() {
     
    
    
    $("#input_laporan_scrap").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        
        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
