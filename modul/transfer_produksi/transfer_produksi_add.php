<!-- Content Header (Page header) -->
<style type="text/css">
  .box-body {
    overflow-x: auto;
}
</style>
     <section class="content-header">
                  <h1>Transfer Posting</h1> 
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>transfer-produksi">Transfer Posting</a>
                        </li>
                        <li class="active">Edit Transfer Posting</li>
                    </ol>
     </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Transfer Posting</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_transfer_produksi" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=in">  
                
                       
          <div class="form-group">
              <label for="Tanggal SPB" class="control-label col-lg-2">Tanggal SPB <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_spb" required autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="No Request" class="control-label col-lg-2">No Request </label>
                        <div class="col-lg-10">
            <select  id="no_request" name="no_request" data-placeholder="Pilih No Request ..." class="form-control chzn-select" tabindex="2" onchange="get_detail_ro(this.value)" >
               <option value=""></option>
               <?php foreach ($db->query("select * from ro ") as $isi) {
                  echo "<option value='$isi->no_ro'>$isi->catatan $isi->no_ro</option>";  
               } ?>
              </select>
            </div>
           </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Tanggal Request" class="control-label col-lg-2">Tanggal Request </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" id="tgl_request" name="tgl_request"  autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group" style="display: none">
                        <label for="Departemen" class="control-label col-lg-2">Tujuan <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="dept" name="dept" data-placeholder="Pilih Proses Produksi ..." class="form-control chzn-select" tabindex="2" > 
               <option value=""></option> 
               <?php foreach ($db->fetch_all("dept") as $isi) {
                  echo "<option value='$isi->kd_dept'>$isi->nm_dept</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Nama PPC" class="control-label col-lg-2">Nama PPC <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" readonly="" class="form-control col-xs-12" rows="5" value="<?= $_SESSION['username'] ?>" name="name_ppc" >
              </div>
          </div>
           <div class="form-group">
              <label for="Nama PPC" class="control-label col-lg-2">Catatan <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" ></textarea>
              </div>
          </div>
           <div class="form-group">
              <label for="Receipt" class="control-label col-lg-2">Upload Detail Barang<br>
              <i class="label label-warning">jika menggunakan import </i> </label>
              <div class="col-lg-3">
                <div class="input-group date">
                    <input type="file" name="file" id="file" onchange="upload_file(this.value)">
                     <a href="<?= base_url() ?>upload/template/template_transfer_incoming.xls"><i class="fa fa-download"></i>Download Template</a>
                </div> 
              </div>
          </div> 
           <div class="form-group" id="form_ro">
                <div class="row"> 
                 <div class="col-lg-12">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                   <!--   <th style="width: 150px">Jenis Dokpab</th> -->
                     <th style="width: 100px">Unit</th>
                     <th>Qty RO</th>
                     <th>Stock</th>
                     <th>Qty</th>                     
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                      <input type="hidden" name="id_input[]" id="id_input_1"> 
                     </td> 
                    <!--  <td>
                       <select id="jenis_dokpab_1" class="form-control" name="jenis_dokpab[]">
                        <option value="">Jenis Dokpab</option>
                       // <?php
                          //$qj = $db->query("select jenis from jenisbcmasuk");
                          //foreach ($qj as $kj) { 
                           //  echo "<option value='$kj->jenis'>$kj->jenis</option>";
                         // }
                      // ?> 
                       </select>
                     </td> -->
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="text" id="form_qty_ro_1" class="form-control" name="qty_ro[]" readonly="" ></td> 
                     <td><input type="text" id="form_stock_1" class="form-control" name="stock[]" readonly="" ></td> 
                     <td><input type="text" id="form_qty_1" class="form-control" name="qty[]" onkeyup="cek_stok('1',this.value)" required>
                     <i id="error_stock_1" style="color: red"></i> </td>
                     <td><input type="text" id="form_ket_1" class="form-control" name="ket[]" ></td>
                   </tr>
                 </tbody>
               </table>
               <input type="hidden" id="jml" value="1">
                 </div>
               
              </div>
              </div>
          
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>transfer-produksi" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                <button type="submit" id="btn_simpan" class="btn btn-primary">
                    <i class="fa fa-save"></i> Submit
                </button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">

  function cek_semua_stock() {
    var valid = true;

    $("input[id^='form_qty_']").each(function(){
        var id = $(this).attr("id").split("_")[2];
        var stock = parseFloat($("#form_stock_" + id).val()) || 0;
        var qty   = parseFloat($(this).val()) || 0;

        if (qty > stock || qty <= 0 || isNaN(qty)) {
            valid = false;
        }
    });

    // if (!valid) {
    //     $("#btn_simpan").prop("disabled", true);
    // } else {
    //     $("#btn_simpan").prop("disabled", false);
    // }
}
  function upload_file(file){
       //var form = $('#input_mrp')[0];    
       if ($("#order").val()=='') {
          $("#error_order").show();
          $("#file").val(null);
          $("#order").focus();
       }else{ 
           $("#isi_mrp").html("Uploading Data...");
           $("#error_order").hide();
           var data = new FormData();
           data.append( 'file', $( '#file' )[0].files[0] );
           data.append( 'order', $( '#order' ).val() );
           $.ajax({
             url : "<?= base_url() ?>modul/transfer_produksi/transfer_produksi_action.php?act=upload_file",
             type : "POST",
             processData: false,
             contentType: false, 
             cache: false,
             enctype: 'multipart/form-data',
             data : data,
           //  dataType : 'JSON',
             success : function(data){
               //  $("#detail_mrp").show();
                 $("#isi_tabel").html(data);
             }
           });
       }       
    } 
  

  function cek_stok(id,jumlah){
    var jml   = parseFloat(jumlah) || 0;
    var stock = parseFloat($("#form_stock_"+id).val()) || 0;

    if (jml > stock) {
        $("#error_stock_"+id).html("Qty melebihi stock ("+stock+")");
        $("#error_stock_"+id).show();
        $("#form_qty_"+id).val(stock);
        $("#form_qty_"+id).css("border","1px solid red");
    } 
    else if (jml <= 0) {
        $("#error_stock_"+id).html("Qty harus > 0");
        $("#error_stock_"+id).show();
        $("#form_qty_"+id).css("border","1px solid red");
    } 
    else {
        $("#error_stock_"+id).hide();
        $("#form_qty_"+id).css("border","");
    }

    // 🔥 VALIDASI GLOBAL
    cek_semua_stock();
}

 function get_detail_ro(no_ro){
   $.ajax({
      url: "<?= base_url() ?>modul/transfer_produksi/transfer_produksi_action.php?act=get_detail_ro",
      data: { no_ro: no_ro },
      type : 'POST',
      success: function (data) {
         $("#form_ro").html(data);

         // 🔥 langsung cek setelah render
         setTimeout(function(){
            cek_semua_stock();
         }, 100);
      }
   });

   $.ajax({
      url: "<?= base_url() ?>modul/transfer_produksi/transfer_produksi_action.php?act=get_tgl_ro",
      data: { no_ro: no_ro },
      type : 'POST',
      success: function (data) {
         $("#tgl_request").val(data);
      }
   });
}

  function hapus_baris(id) {
     
      $("#baris_"+id).remove();
        cek_semua_stock();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    } 

    function add_baris(id) {
      var jenis_bc = '<td><select id="jenis_dokpab_'+id_baris+'" class="form-control" name="jenis_dokpab[]"><option value="">Jenis Dokpab</option><option value="BC 2.3">BC 2.3</option><option value="BC 2.7">BC 2.7</option><option value="BC 4.0">BC 4.0</option><option value="BC 2.6.2">BC 2.6.2</option><option value="NON">NON</option></select></td>';
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" > <input type="hidden" id="id_input_'+id_baris+'" name="id_input[]" > </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="text"  id="form_qty_ro_'+id_baris+'" class="form-control" name="qty_ro[]" readonly=""></td><td><input type="text" id="form_stock_'+id_baris+'" class="form-control" name="stock[]" readonly="" ></td> <td><input type="text"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]"  onkeyup="cek_stok(\''+id_baris+'\',this.value)" required><i id="error_stock_'+id_baris+'"  style="color: red"></i></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
        cek_semua_stock();
    }

     function cari_kode(id) {   
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang,
                                  id_barang : item.id_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                             $("#id_input_"+id).val(ui.item.id_barang);

                              $.ajax({
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_incoming",
                                data: { 
                                  kode   :  ui.item.kd_barang,
                                  jumlah : '0'
                                },
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                   $("#form_stock_"+id).val(data.stock);
                                }
                              });
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_"+id).val(data);
                              }
                            });

                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
  }
    $(document).ready(function() {
     
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
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
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#input_transfer_produksi").validate({
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
        
        rules: {
            
          tgl_spb: {
          required: true,
          //minlength: 2
          },
        
         
        
         
        
          name_ppc: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          tgl_spb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
      
        
          dept: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          name_ppc: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
        },
    
        submitHandler: function(form) {
        Swal.fire({
          title: 'Yakin akan disimpan ?',
          showDenyButton: false,
          showCancelButton: true,
          confirmButtonText: 'Save',
          denyButtonText: `Don't save`,
        }).then((result) => {
          /* Read more about isConfirmed, isDenied below */
          if (result.isConfirmed) {
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
          } else if (result.isDenied) {
            Swal.fire('Changes are not saved', '', 'info')
          }
        })   
            
        }
    });
});
</script>
