<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Laporan Transfer Pemasukan</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>laporan-transfer-pemasukan">Laporan Transfer Pemasukan</a>
            </li>
            <li class="active">Add Laporan Transfer Pemasukan</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Laporan Transfer Pemasukan</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_laporan_transfer_pemasukan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/laporan_transfer_pemasukan/laporan_transfer_pemasukan_action.php?act=in">
                      
              <div class="form-group">
                <label for="no_spb" class="control-label col-lg-2">no_spb </label>
                <div class="col-lg-10">
                  <input type="text" name="no_spb" placeholder="no_spb" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_spb" class="control-label col-lg-2">tgl_spb </label>
                <div class="col-lg-10">
                  <input type="text" name="tgl_spb" placeholder="tgl_spb" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="name_ppc" class="control-label col-lg-2">name_ppc </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="name_ppc" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kode" class="control-label col-lg-2">kode </label>
                <div class="col-lg-10">
                  <input type="text" name="kode" placeholder="kode" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nm_barang" class="control-label col-lg-2">nm_barang </label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" placeholder="nm_barang" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="satuan" class="control-label col-lg-2">satuan </label>
                <div class="col-lg-10">
                  <input type="text" name="satuan" placeholder="satuan" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="jumlah" class="control-label col-lg-2">jumlah </label>
                <div class="col-lg-10">
                  <input type="text" name="jumlah" placeholder="jumlah" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>laporan-transfer-pemasukan" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
    $(document).ready(function() {
     
    
    
    $("#input_laporan_transfer_pemasukan").validate({
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
