<!-- Content Header (Page header) -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <section class="content-header">
        <h1>LP Sparepart</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>lp-sparepart">LP Sparepart</a>
            </li>
            <li class="active">Add LP Sparepart</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add LP Sparepart</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_lp_sparepart" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/lp_sparepart/lp_sparepart_action.php?act=in">
                      
              <div class="form-group">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" placeholder="Nomor" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No LP" class="control-label col-lg-2">No LP </label>
                <div class="col-lg-10">
                  <input type="text" name="no_lap" placeholder="No LP" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal LP" class="control-label col-lg-2">Tanggal LP </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_lap"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="PPC" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Catatan" class="control-label col-lg-2">Catatan </label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" ></textarea>
              </div>
          </div><!-- /.form-group -->
          <hr>

<h4>Detail Barang</h4>

<table class="table table-bordered" id="table_barang">
<thead>
<tr>
    <th width="5%">No</th>
    <th>Kode Barang</th>
    <th>Nama Barang</th>
    <th width="120">Qty</th>
    <th width="120">Satuan</th>
    <th width="50">Aksi</th>
</tr>
</thead>
<tbody id="isi_barang"></tbody>
</table>

<button type="button" class="btn btn-success btn-sm" onclick="add_barang()">
    <i class="fa fa-plus"></i> Tambah Barang
</button>
          
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>lp-sparepart" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

<script type="text/javascript">

  var no_barang = 0;

function add_barang(){

    no_barang++;

    var html = `
    <tr id="row_${no_barang}">
        <td>${no_barang}</td>

        <td>
            <input type="text" id="form_kode_${no_barang}" class="form-control">
            <input type="hidden" name="kode_barang[]" id="kode_barang_${no_barang}">
        </td>

        <td>
            <input type="text" name="nama_barang[]" 
                   id="nama_barang_${no_barang}"
                   class="form-control">
        </td>

        <td>
            <input type="number" name="qty[]" class="form-control">
        </td>

        <td>
            <input type="text" name="satuan[]" 
                   id="satuan_${no_barang}"
                   class="form-control">
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                onclick="hapus_barang(${no_barang})">
                X
            </button>
        </td>
    </tr>
    `;

    $("#isi_barang").append(html);

    // 🔥 aktifkan autocomplete
    init_autocomplete(no_barang);
}

function init_autocomplete(id){

    $("#form_kode_"+id).autocomplete({
        source: function(request, response){
            $.ajax({
                url: "<?=base_admin();?>modul/lp_sparepart/lp_sparepart_action.php?act=get_barang",
                type: "POST",
                dataType: "json",
                data: { term: request.term },
                success: function(data){
                    response(data);
                }
            });
        },
        
        minLength: 2,
        select: function(event, ui){

    // tampil di input utama
    $("#form_kode_"+id).val(ui.item.label);

    // simpan kode
    $("#kode_barang_"+id).val(ui.item.value);

    // 🔥 AUTO ISI NAMA BARANG
    $("#nama_barang_"+id).val(ui.item.nama);

    // 🔥 AUTO ISI SATUAN
    $("#satuan_"+id).val(ui.item.satuan);

    return false;
}
    });
}

function hapus_barang(id){
    $("#row_"+id).remove();
}
    $(document).ready(function() {
 
      add_barang();
     
    
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    
    $("#input_lp_sparepart").validate({
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
