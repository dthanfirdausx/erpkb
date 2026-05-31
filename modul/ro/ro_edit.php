<!-- Content Header (Page header) -->
             <!--  <section class="content-header">
                  <h1>RO</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>ro">RO</a>
                        </li>
                        <li class="active">Edit RO</li>
                    </ol>
              </section> -->

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit RO</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_ro" method="post" class="form-horizontal" action="<?=base_admin();?>modul/ro/ro_action.php?act=up">
                            
              <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2">Tanggal RO <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_ro;?>" name="tgl_ro" required />
                    <input type="hidden" class="form-control" value="<?=$data_edit->no_ro;?>" name="no_ro" required />
                    <input type="hidden" class="form-control" value="<?=$data_edit->nomor;?>" name="nomor" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group" style="display: none">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="dept" name="dept" data-placeholder="Pilih Departemen..." class="form-control chzn-select" tabindex="2" >
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
                  <input type="text" name="name_ppc" value="<?=$data_edit->name_ppc;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
               <div class="form-group">
                        <label for="Material" class="control-label col-lg-2">Material </label>
                        <div class="col-lg-10">
           <!--  <select  id="id_bom" onchange="get_detail_bom(this.value)" data-placeholder="Pilih Material ..." class="js-example-basic-multiple" name="id_bom[]" multiple="multiple" style="width: 100%" >
               <option value="">Pilih Material</option>
               <?php foreach ($db->fetch_all("bom") as $isi) {
                  echo "<option value='$isi->id'>$isi->kodebj $isi->nm_barang</option>";
               } ?>
              </select> -->
              <a style="cursor:pointer" class="btn btn-success" onclick="pilih_material()"><i class="fa fa-plus"></i> Pilih Material</a>
               <h3 class="text-center">Material yang dipilih</h3>
            <table class="table">
              <thead> 
                <tr>
                  <th></th>
                  <th>Kode barang</th>
                  <th>Nama Barang</th>
                  <th>Satuan</th>
                  <th>Jumlah Order</th>
                </tr> 
              </thead>
              <tbody id="barang_pilih">
                <?php
                $q = $db->query("select b.*,b.id as id_barang,rb.jml_brg as jml,b.kodebj as kd_barang
from ro_bom rb join ro r on r.no_ro=rb.no_ro
join bom b on (b.id=rb.id_bom and rb.no_ro=r.no_ro)
where r.id='".uri_segment(3)."' group by b.kodebj");
  foreach ($q as $k) {
    $jml_order = 1;
    if ($k->jml!='') {
      $jml_order = $k->jml;
    }
    echo "<tr>
    <td><a style='cursor:pointer' class='btn btn-danger' onclick='hapus_material($k->id_barang)'><i class='fa fa-minus'></i></a></td>
    <td>$k->kd_barang</td>
    <td>$k->nm_barang</td>
    <td>$k->satuan</td>
    <td><input type='text' class='form-control' value='$jml_order' onkeyup='update_jml_order($k->id_barang,this.value)' ></td> 
    </tr>";
  }
                ?>
              </tbody> 
            </table>
            </div>
           
             </div><!-- /.form-group --> 

             
              
            <div class="form-group" style="display: none">
                <label for="tujuan" class="control-label col-lg-2">tujuan </label>
                <div class="col-lg-10">
                    <select id="tujuan" name="tujuan" data-placeholder="Pilih tujuan..." class="form-control chzn-select" tabindex="2" >
                      <option value=""></option>
                     <?php
                     $option = array(
                      '1' => 'Praproduksi',

                      '2' => 'Produksi',
                      );
                     foreach ($option as $isi => $val) {

                        if ($data_edit->tujuan==$isi) {
                          echo "<option value='$data_edit->tujuan' selected>$val</option>";
                        } else {
                       echo "<option value='$isi'>$val</option>";
                          }
                     } ?>
                    </select>
                  </div>
            </div><!-- /.form-group -->
            
          <div class="form-group">
              <label for="catatan" class="control-label col-lg-2">catatan </label>
              <div class="col-lg-10">
              <textarea class="form-control col-xs-12" rows="5" name="catatan"><?=$data_edit->catatan;?> </textarea>
              </div>
          </div><!-- /.form-group -->
           <div class="form-group" id="detail_bom">
                 <label for="Kurs" class="control-label col-lg-2"> </label>
                 <div class="col-lg-10">
                   <table class="table">
                     <thead>
                       <tr>
                         <th style="width:50px;text-align: center">
                           <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                         </th>
                         <th>Kode Bahan Baku</th>                     
                         <th>Qty</th>                
                         <th>Lokasi</th>
                       </tr>
                     </thead>
                     <tbody id="isi_tabel">
                    <?php
                    $no=1;
                    $q = $db->query("select d.* from ro r join ro_detail d on r.no_ro=d.no_ro
                      where r.id='".uri_segment(3)."' ");
                    foreach ($q as $k) {
                   
                    ?>
                       <tr id="baris_<?= $no ?>">
                         <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                         <td><input type="text" value="<?= $k->kode ?>" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                          <input type="hidden" value="<?= $k->kode ?>" name="kode_input[]" id="kode_input_1"> 
                         </td> 
                        
                         <td><input type="text" value="<?= $k->jumlah ?>"  id="form_qty_1" class="form-control" name="jumlah[]" ></td>
                        
                         <td><input type="text" value="<?= $k->ket ?>" id="form_ket_1" class="form-control" name="ket[]" ></td>
                       </tr>
                    <?php
                  }
                    ?>
                     </tbody>
                   </table> 
                 </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
              
              </div>
          
                            <input type="hidden" name="id" value="<?=$data_edit->id;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>ro" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->
                <div id="modal_barang" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg" style="width: 90%">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Data Material</h4>
      </div>
      <div class="modal-body">
     
        <table class="table" id="data_barang">
          <thead>
            <tr>
              <th></th>
              <th>Kode Barang</th>
              <th>Nama Barang</th>
              <th>Satuan</th>
             <!--  <th>Jml Barang Order</th> -->
            </tr>
          </thead>
         
          </table>
 
      
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
  
</div>

<script type="text/javascript">


  function update_jml_order(id,jml) {
     $.ajax({
     type : "POST",
     url: "<?= base_url() ?>modul/ro/ro_action.php?act=update_jml_order",
     data : {
       id : id,
       jml : jml
     },
     success : function(data){
         get_detail_bom(1,jml); 
     }
    });
  }

  function cek_barang(id) {
  // $("#id_barang_"+id)
   var cek = 0;
   if ($("#id_barang_"+id).is(':checked')){ 
     cek = 1; 
   }
   $.ajax({
     type : "POST",
     data : { 
       id : id,
       cek : cek
     },
     url: "<?= base_url() ?>modul/ro/ro_action.php?act=temp_barang",
     success : function(data){
      $('#data_barang').DataTable().destroy();
      $("#data_barang").DataTable({
         
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
            'width': '5%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          } 
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/ro/ro_temp_data.php',
            type: 'POST',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });
     }
   })
 }

  function hapus_material(id) {
     $.ajax({
     type : "POST",
     url: "<?= base_url() ?>modul/ro/ro_action.php?act=hapus_material",
     data : {
       id : id
     },
     success : function(data){
      $.ajax({ 
         type : "POST",
         url: "<?= base_url() ?>modul/ro/ro_action.php?act=get_temp_barang",
         success : function(data){
           $("#barang_pilih").html(data); 
            get_detail_bom(1,null); 
         }
        }); 
     }
    }); 
  }
  function pilih_material() {
      $("#modal_barang").modal('show');
    }

  function hapus_baris(id) {
 
      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

     function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]" ></td><td><input type="text" class="form-control" id="form_ket_'+id_baris+'" name="ket[]" ></td></tr>';

      

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
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
                                  nm_barang: item.nm_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
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

    function get_detail_bom(id,jml) {
       $.ajax({
                              type : 'POST',
                              data : {
                                id  :id, 
                                jml : jml
                                
                              },
                              url : "<?= base_url() ?>modul/ro/ro_action.php?act=get_detail_bom",
                              success:function(data){
                                   $("#detail_bom").html(data);
                              }
                            });
    }

     function update_detail_bom() {
       $.ajax({
                              type : 'POST',
                              data : {
                                id  :  $("#id_bom").val(),  
                                jml : $("#jml_brg_jadi").val()
                                
                              },
                              url : "<?= base_url() ?>modul/ro/ro_action.php?act=get_detail_bom",
                              success:function(data){
                                   $("#detail_bom").html(data);
                              }
                            });
    }


    $(document).ready(function() {

         $("#data_barang").DataTable({
         
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
            'width': '5%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          } 
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/ro/ro_temp_data.php',
            type: 'POST',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
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
      
    $("#edit_ro").validate({
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
          }
        
      
        
        },
         messages: {
            
          tgl_ro: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
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
