<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add RO</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_ro" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/ro/ro_action.php?act=in"> 
                      
          <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2">Tanggal RO <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" autocomplete="off" class="form-control" name="tgl_ro" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group" style="display: none">
          <label class="control-label col-lg-2">Sales Order</label>
          <div class="col-lg-10">
            <select name="no_so" id="no_so" class="form-control select2">
              <option value="">Pilih Sales Order</option>
              <?php
              $so = $db->query("SELECT no_so, tgl_so, customer FROM sales_order ORDER BY tgl_so DESC");
              foreach ($so as $s) {
                echo "<option value='$s->no_so'>$s->no_so - $s->customer</option>";
              }
              ?>
            </select>
          </div>
</div>
   <div class="form-group">
              <label for="catatan" class="control-label col-lg-2">catatan </label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" ></textarea>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group" style="display: none">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="dept" name="dept" data-placeholder="Pilih Departemen ..." class="form-control chzn-select" tabindex="2">
               <option value=""></option>
               <?php foreach ($db->fetch_all("dept") as $isi) {
                  echo "<option value='$isi->kd_dept'>$isi->nm_dept</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="PPC" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">

    <label class="control-label col-lg-2">
        Metode RO
    </label>

    <div class="col-lg-2">

        <select
            name="jenis_ro"
            id="jenis_ro"
            class="form-control">

            <option value="bom">
                By BOM
            </option>

            <option value="manual">
                Manual
            </option>

        </select>

    </div>

</div>
            <div class="form-group" id="area_bom">

<label class="control-label col-lg-2">
    BOM Produksi
</label>

<div class="col-lg-10">

<table class="table table-bordered">

<thead>

<tr>

<th width="5%">
    <a onclick="add_bom_row()">
        <i class="fa fa-plus"></i>
    </a>
</th>

<th>
    BOM
</th>

<th width="15%">
    Qty Order
</th>

</tr>

</thead>

<tbody id="bom_area">

</tbody>

</table>

</div>

</div>
<div
    class="form-group"
    id="area_manual"
    style="display:none;">

<label class="control-label col-lg-2">
    Detail Material
</label>

<div class="col-lg-10">

<table class="table table-bordered">

    <thead>

        <tr>

            <th width="5%">

                <a
                    onclick="add_manual_row()">

                    <i class="fa fa-plus"></i>

                </a>

            </th>

            <th>Kode Barang</th>

            <th>Qty</th>

            <th>Keterangan</th>

        </tr>

    </thead>

    <tbody id="manual_area">

    </tbody>

</table>

</div>

</div>

              <div class="form-group" style="display: none">
                <label for="Jumlah Barang" class="control-label col-lg-2">Jumlah Barang Jadi</label>
                <div class="col-lg-10">
                  <input type="text" name="jml_brg_jadi" id="jml_brg_jadi" onkeyup="update_detail_bom()" placeholder="Jumlah Barang" class="form-control" required="" > 
                </div>
              </div><!-- /.form-group -->
              
            <div class="form-group" style="display: none">
                <label for="tujuan" class="control-label col-lg-2">tujuan </label>
                <div class="col-lg-10">
                  <select name="tujuan" id="tujuan" data-placeholder="Pilih tujuan ..." class="form-control chzn-select" tabindex="2" >
                    
<option value='1'>Praproduksi</option>

<option value='2'>Produksi</option>

                  </select>
                </div>
            </div><!-- /.form-group -->
            
       
          
                      
             
              <div class="form-group" id="detail_bom" style="display: none">
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
                      $qq = $db->query("select b.id,b.kodebj,v.jml,v.nm_barang from v_ro_barang v join bom b on b.kodebj=v.kd_barang where barang_temp='1' and user='".$_SESSION['username']."' ");
                      foreach ($qq as $kk) {
                        $jml = 1;
                        if ($kk->jml!='') { 
                         $jml = $kk->jml;
                        }
                    //  echo "select * from bom_detail where id_hd='$kk->id'";
                      $q = $db->query("select d.*,b.nm_barang from bom_detail d left join barang b on b.kd_barang=d.kodebb where d.id_bom='$kk->id' ");  
                      foreach ($q as $k) {
                     
                      ?>
                       <tr id="baris_<?= $no ?>">
                         <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                         <td><input type="text" value="<?= $k->kodebb." ".$k->nm_barang ?>" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                          <input type="hidden" value="<?= $k->kodebb ?>" name="kode_input[]" id="kode_input_1"> 
                         </td> 
                        
                         <td><input type="text"  id="form_qty_1" value="<?= ($k->jumlah * $jml) ?>" class="form-control" name="jumlah[]" ></td>
                        
                         <td><input type="text" id="form_ket_1"  class="form-control" name="ket[]" ></td>
                       </tr>
                       <?php
                       $no++;
                     }
                   }
                       ?>
                     </tbody>
                   </table>
                 </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
              
              </div>

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
    </div>

    </section><!-- /.content -->

  </pre>
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
         get_detail_bom(1); 
     }
    });
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
            get_detail_bom(1); 
         }
        }); 
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

  function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

    function pilih_material() {
      $("#modal_barang").modal('show');
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

    function get_detail_bom(id) {
      //alert(id);
       $.ajax({
                              type : 'POST',
                              data : {
                                id  :id, 
                                jml : $("#jml_brg_jadi").val()
                                
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



    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]" ></td><td><input type="text" class="form-control" id="form_ket_'+id_baris+'" name="ket[]" ></td></tr>';

      

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
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
     
          //chosen select
         // $(".chzn-select").select2();
          // $(".chzn-select-deselect").chosen({
          //     allow_single_deselect: true
          // });

         // $('.js-example-basic-multiple').select2();
        
    
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
      
    $("#input_ro").validate({
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
 $('#modal_barang').on('hide.bs.modal', function () { 
    $.ajax({
     type : "POST",
     url: "<?= base_url() ?>modul/ro/ro_action.php?act=get_temp_barang",
     success : function(data){
       $("#barang_pilih").html(data); 
       get_detail_bom(1);
     }
    }); 
  });

 function add_bom_row(){

var no = $(".bom-row").length + 1;

var html = '';

html += '<tr class="bom-row" id="bom_'+no+'">';

html += '<td>';
html += '<a onclick="hapus_bom('+no+')">';
html += '<i class="fa fa-trash"></i>';
html += '</a>';
html += '</td>';

html += '<td>';

html += '<input type="text" ';
html += 'class="form-control bom_autocomplete" ';
html += 'id="bom_nama_'+no+'" ';
html += 'placeholder="Cari BOM">';
html += '<input type="hidden" ';
html += 'name="id_bom[]" ';
html += 'id="id_bom_'+no+'">';

html += '<div id="detail_bom_'+no+'"></div>';

html += '</td>';

html += '<td>';

html += '<input type="number" ';
html += 'value="1" ';
html += 'class="form-control" ';
html += 'onkeyup="reload_bom('+no+')">';

html += '</td>';

html += '</tr>';

$("#bom_area").append(html);

autocomplete_bom(no);

}

function autocomplete_bom(id){

$('#bom_nama_'+id).autocomplete({

source:function(request,response){

$.ajax({

url:
'<?=base_url()?>modul/ro/ro_action.php?act=cari_bom',

type:'POST',

data:{
term:request.term
},

dataType:'json',

success:function(data){

response($.map(data,function(item){

return{

id:item.id,

label:item.kodebj+' - '+item.nm_barang,

value:item.kodebj+' - '+item.nm_barang

};

}));

}

});

},

select:function(event,ui){

$("#id_bom_"+id).val(ui.item.id);

reload_bom(id);

}

});

}

function reload_bom(id){

$.ajax({

type:'POST',

url:
'<?=base_url()?>modul/ro/ro_action.php?act=load_bom',

data:{

id_bom:$("#id_bom_"+id).val(),

qty:$("#bom_"+id)
.find("input[type=number]")
.val()

},

success:function(data){

$("#detail_bom_"+id).html(data);

}

});

}

$("#jenis_ro").change(function(){

    if($(this).val()=="bom"){

        $("#area_bom").show();

        $("#area_manual").hide();

    }else{

        $("#area_bom").hide();

        $("#area_manual").show();

    }

});

function add_manual_row(){

    var no =
        $("#manual_area tr").length + 1;

    var html='';

    html += '<tr id="manual_'+no+'">';

    html += '<td>';

    html += '<a onclick="hapus_manual('+no+')">';

    html += '<i class="fa fa-trash"></i>';

    html += '</a>';

    html += '</td>';

    html += '<td>';

    html += '<input type="text" ';
    html += 'class="form-control" ';
    html += 'id="barang_'+no+'" ';
    html += 'name="barang_manual[]">';

    html += '<input type="hidden" ';
    html += 'id="kode_'+no+'" ';
    html += 'name="kode_manual[]">';

    html += '</td>';

    html += '<td>';

    html += '<input type="number" ';
    html += 'step="0.00001" ';
    html += 'class="form-control" ';
    html += 'name="qty_manual[]">';

    html += '</td>';

    html += '<td>';

    html += '<input type="text" ';
    html += 'class="form-control" ';
    html += 'name="ket_manual[]">';

    html += '</td>';

    html += '</tr>';

    $("#manual_area").append(html);

    autocomplete_barang(no);

}

function autocomplete_barang(id){

$('#barang_'+id).autocomplete({

source:function(request,response){

$.ajax({

url:
'<?=base_url()?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode',

type:'POST',

data:{
term:request.term
},

dataType:'json',

success:function(data){

response($.map(data,function(item){

return{

kd_barang:item.kd_barang,

nm_barang:item.nm_barang

};

}));

}

});

},

select:function(event,ui){

$("#barang_"+id)
.val(ui.item.kd_barang+' - '+ui.item.nm_barang);

$("#kode_"+id)
.val(ui.item.kd_barang);

return false;

}

});

}
function hapus_manual(id){

    $("#manual_"+id).remove();

}
</script>
