<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Purchase Request</h1>
                   
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Purchase Request</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_pr" method="post" class="form-horizontal" action="<?=base_admin();?>modul/pr/pr_action.php?act=compare">
                            
              <div class="form-group">
                <label for="No RO" class="control-label col-lg-2">No RO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_ro" value="<?=$data_edit->no_ro;?>" class="form-control" readonly>
                  <input type="hidden" name="nomor" value="<?=$data_edit->nomor;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2">Tanggal RO <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_ro;?>" name="tgl_ro"  readonly />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen/Bagian" class="control-label col-lg-2">Departemen/Bagian <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="dept" name="dept" data-placeholder="Pilih Departemen/Bagian..." class="form-control chzn-select" tabindex="2" readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("dept") as $isi) {

                  if ($data_edit->dept==$isi->kd_dept) {
                    echo "<option value='$isi->kd_dept' selected>$isi->nm_dept</option>";
                  } else {
                  echo "<option value='$isi->kd_dept'>$isi->nm_dept</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" value="<?=$data_edit->name_ppc;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="catatan" class="control-label col-lg-2">catatan </label>
              <div class="col-lg-10">
              <textarea class="form-control col-xs-12" rows="5" name="catatan" readonly><?=$data_edit->catatan;?> </textarea>
              </div>
          </div>
          <div class="form-group">
               
                 <div class="col-lg-12">
                   <table class="table">
                   <thead>
                   <tr> 
                     
                     <th style="width: 30%" class="text-center">Nama Barang</th>
                   
                     <th class="text-center">Price Comparison</th>
                   </tr>
                <!--    <tr>
                     <th class="text-center">Vendor</th>
                     <th class="text-center">Harga</th>
                   </tr> -->
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $q = $db->query("select d.*,ba.nm_barang, ba.satuan as unit
                              from roin_detail d                              
                              left join barang ba on ba.kd_barang=d.kode where d.no_ro='$data_edit->no_ro'  "); 
                 $no=1;
                 foreach ($q as $k) {
                  ?>
                  <tr>
                    <td><b><?= $k->kode." ".$k->nm_barang."</b><br>Jumlah : ".$k->jumlah."<br>ket : ".$k->ket ?></td>
                    <td>
                      <table class="table" id="tabel_<?= $k->id ?>" >
                        <thead>
                          <tr>
                            <th></th>
                            <th>Pemasok</th>
                            <th>Harga Penawaran</th>
                            <th>Catatan</th>
                          </tr>
                        </thead>
                        <tbody>
                      <?php
                      $qc = $db->query("select c.*,p.nama from roin_detail_compare c left join pemasok p on 
                                    p.kode_pemasok=c.pemasok where c.id_detail='$k->id' ");
                      if ($qc->rowCount()>0) {
                         foreach ($qc as $kc) { 
                           ?>
                           <tr id="baris_<?= $no ?>"> 
                            <td style="width: 100px">
                              <a class="btn btn-primary" onclick="add_row('<?= $k->id ?>','<?= $no ?>')"><i class="fa fa-plus"></i></a>
                              <a class="btn btn-danger"  onclick="min_row('<?= $k->id ?>','<?= $no ?>')"> <i class="fa fa-minus"></i> </a>
                            </td>
                            <td><input value="<?= $kc->pemasok."-".$kc->nama  ?>" type="text" id="form_kode_<?= $no ?>" onclick="cari_vendor('<?= $no ?>')" name="vendor[<?= $k->id ?>][]" class="form-control" placeholder="input pemasok"></td>
                            <td><input value="<?= $kc->harga ?>" type="text" name="harga[<?= $k->id ?>][]" class="form-control" placeholder="input harga"></td>
                            <td><input value="<?= $kc->ket ?>" type="text" name="ket[<?= $k->id ?>][]" class="form-control" placeholder="input keterangan"></td>
                          </tr> 
                           <?php
                           $no++;
                         }
                      }else{
                        ?>
                        <tr id="baris_<?= $no ?>"> 
                          <td style="width: 100px">
                            <a class="btn btn-primary" onclick="add_row('<?= $k->id ?>','<?= $no ?>')"><i class="fa fa-plus"></i></a>
                            <a class="btn btn-danger"  onclick="min_row('<?= $k->id ?>','<?= $no ?>')"> <i class="fa fa-minus"></i> </a>
                          </td>
                          <td><input type="text" id="form_kode_<?= $no ?>" onclick="cari_vendor('<?= $no ?>')" name="vendor[<?= $k->id ?>][]" class="form-control" placeholder="input pemasok"></td>
                          <td><input type="text" name="harga[<?= $k->id ?>][]" class="form-control" placeholder="input harga"></td>
                          <td><input type="text" name="ket[<?= $k->id ?>][]" class="form-control" placeholder="input keterangan"></td>
                        </tr>
                        <?php
                      }
                      ?>
                        </tbody>
                      </table>
                    </td>                  
                  </tr>
                <!--   <tr>
                    <td><input placeholder="input pemasok" type="" name="" class="form-control"></td>
                    <td><input placeholder="input harga" type="" name="" class="form-control"></td>
                  </tr> 
                  <tr>
                    <td><input placeholder="input pemasok" type="" name="" class="form-control"></td>
                    <td><input placeholder="input harga" type="" name="" class="form-control"></td>
                  </tr>  -->
                  <?php
                  $no++;
                 } 
                 ?>
                   
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="<?= ($no-1) ?>">
              
              </div>
          
                            <input type="hidden" name="id" value="<?=$data_edit->no_ro;?>">
                            <div class="form-group">
                              
                                <div class="col-lg-12">
                                <a href="<?=base_index();?>pr" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">
   function min_row(id,baris) {
      $("#baris_"+baris).remove();
    }

    function add_row(id,no) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="width: 100px"><a class="btn btn-primary" onclick="add_row(\''+id+'\',\''+id_baris+'\')"><i class="fa fa-plus"></i></a> <a class="btn btn-danger"  onclick="min_row(\''+id+'\',\''+id_baris+'\')"> <i class="fa fa-minus"></i> </a></td><td><input id="form_kode_'+id_baris+'" onkeyup="cari_vendor(\''+id_baris+'\')" type="text" name="vendor['+id+'][]" class="form-control" placeholder="input pemasok"></td><td><input type="text" name="harga['+id+'][]" class="form-control" placeholder="input harga"></td><td><input  type="text" name="ket['+id+'][]" class="form-control" placeholder="input keterangan"></td></tr>'; 
      $("#tabel_"+id).append(baris);
        $("#jml").val(id_baris);
   }

    function cari_vendor(id) {    
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) { 
                          $.ajax({
                            url: "<?= base_url() ?>modul/pr/pr_action.php?act=cari_vendor",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kode_pemasok: item.kode_pemasok,
                                  nama: item.nama
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kode_pemasok+"-"+ui.item.nama); 
                          
                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kode_pemasok + ' - ' +item.nama+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
  }
    $(document).ready(function() {
    
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
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_pr").validate({
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
            
          tgl_ro: {
          required: true,
          //minlength: 2
          },
        
          dept: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          tgl_ro: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dept: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
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
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
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
