<!-- Content Header (Page header) -->
<!--     <section class="content-header">
        <h1>Pemasukan Hamparan</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>pemasukan-hamparan">Pemasukan Hamparan</a>
            </li>
            <li class="active">Add Pemasukan Hamparan</li>
        </ol>
    </section> -->
      <link rel="stylesheet" href="<?= base_url() ?>assets/css/jquery-ui.css"> 
<style type="text/css">
     .ui-autocomplete { 
  z-index:2147483647;
}
</style>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Pemasukan</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_pemasukan_hamparan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=in">
                      
              <div class="form-group" style="display: none">
                <label for="No BPB" class="control-label col-lg-2">No BPB </label>
                <div class="col-lg-10">
                  <input type="text" name="no_bpb" placeholder="No BPB" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal BPB" class="control-label col-lg-2">Tanggal BPB </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_bpb" autocomplete="off" required="" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div> 
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="No PO" class="control-label col-lg-2">No PO </label>
                        <div class="col-lg-10">
            <select  id="nopo" name="nopo" data-placeholder="Pilih No PO ..." class="form-control chzn-select" tabindex="2" onchange="pilih_po(this.value)" >
               <option value=""></option>
               <?php 
               foreach ($db->query("
    SELECT po.purchase_order_no AS no_po
    FROM purchase_order po
    LEFT JOIN pemasukan p 
        ON p.nopo = po.purchase_order_no 
        AND p.status != 'REVERSED'
    WHERE po.status != 'CLOSE'
    AND p.nopo IS NULL
") as $isi) { 
    echo "<option value='$isi->no_po'>$isi->no_po</option>";
}
               ?>
              </select>
            </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Pemasok" class="control-label col-lg-2">Pemasok </label>
                        <div class="col-lg-10">
            <select required=""  id="pemasok" name="pemasok" data-placeholder="Pilih Pemasok ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("pemasok") as $isi) {
                  echo "<option value='$isi->kode_pemasok'>$isi->nama</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice" class="control-label col-lg-2">No Invoice </label>
                <div class="col-lg-10">
                  <input required="" type="text" name="no_invoice" placeholder="No Invoice" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input required="" type="text" class="form-control" name="tgl_invoice" autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No DO" class="control-label col-lg-2">No DO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_do" placeholder="No DO" class="form-control" required="" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Dokpab" class="control-label col-lg-2">No Dokpab </label>
                <div class="col-lg-10">
                  <input type="text" name="no_dokpab" placeholder="No Dokpab" class="form-control" required="">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Dokpab" class="control-label col-lg-2">Tanggal Dokpab </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl3">
                    <input type="text" class="form-control" name="tgl_dokpab" autocomplete="off" required=""/>
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="catatan" class="control-label col-lg-2">catatan <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="catatan" name="catatan" data-placeholder="Pilih catatan ..." class="form-control chzn-select" tabindex="2" required="">
               <option value=""></option>
               <?php foreach ($db->fetch_all("catatan") as $isi) {
                  echo "<option value='$isi->nm_catatan'>$isi->nm_catatan</option>";
               } ?>
              </select> 
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                  <label for="Jenis Dokumen" class="control-label col-lg-2">Jenis Dokumen <span style="color:#FF0000">*</span></label>
                  <div class="col-lg-10">
                      <select name="jenisbcmasuk_jenis_dokumen" id="jenisbcmasuk_jenis_dokumen" data-placeholder="Pilih Jenis Dokumen ..." class="form-control chzn-select" tabindex="2" required>
                        <option value="">Pilih Jenis Dokumen</option>
                        <?php foreach ($db->fetch_all("jenisbcmasuk") as $isi) {
                        echo "<option value='$isi->jenis'>$isi->jenis</option>";
                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->
                      
            <div class="form-group">
                <label for="kd_catdet" class="control-label col-lg-2">Tujuan Detail </label>
                <div class="col-lg-10">
                  <select name="kd_catdet" id="detail_catatan_kd_catdet" data-placeholder="Pilih Tujuan Detail ..." class="form-control chzn-select" tabindex="2" > 
                  </select>
                </div>
            </div><!-- /.form-group -->
            
              <div class="form-group">
                <label for="No Aju" class="control-label col-lg-2">No Aju </label>
                <div class="col-lg-10">
                  <input type="text" name="no_aju" placeholder="No Aju" class="form-control" required="">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Aju" class="control-label col-lg-2">Tanggal Aju </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl4">
                    <input type="text" class="form-control" name="tgl_aju" autocomplete="off"  required=""/>
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No E-faktur" class="control-label col-lg-2">No E-faktur </label>
                <div class="col-lg-10">
                  <input type="text" name="efaktur" placeholder="No E-faktur" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal E-Faktur" class="control-label col-lg-2">Tanggal E-Faktur </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl5">
                    <input type="text" class="form-control" name="tgl_efaktur" autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div> 
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
            <select  id="valuta" name="valuta" data-placeholder="Pilih Valuta ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" name="kurs" placeholder="Kurs" class="form-control" >
                </div>
              </div><!-- /.form-group -->
          

              <div class="form-group" id="detail_pemasukan">              
               <div class="col-lg-12">
                <table class="table" >
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                     <th style="width: 70px">Unit</th>
                     <th>Qty</th>
                     <th>Harga</th>
                     <th>Nilai</th>
                     <th>Berat</th>
                     <th>Lot Number</th>
                     <th>Lokasi</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="number" onkeyup="sum_nilai(this.value,'1')" id="form_qty_1" class="form-control" name="jumlah[]" ></td>
                     <td><input type="number" onkeyup="sum_nilai(this.value,'1')" id="form_harga_1" class="form-control" name="harga[]" ></td>
                     <td><input type="text" id="form_nilai_1" class="form-control" name="nilai[]" readonly=""></td>
                     <td><input type="number" id="form_berat_1" class="form-control" name="berat[]" ></td>
                     <td><input type="text" id="form_lot_1" class="form-control" name="lot_no[]" ></td>
                     <td><input type="text" id="form_lokasi_1" class="form-control" name="lokasi[]" ></td>
                   </tr>
                 </tbody>
                 </table>
               </div>
               <input type="hidden" id="jml" value="1">
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>pemasukan-hamparan" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>
 
          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->
      <script src="<?= base_url() ?>assets/js/jquery-ui.js"></script> 

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* =========================
   🔥 SWEETALERT HELPER
========================= */
function swalWarning(msg){
    Swal.fire({ icon:'warning', title:'Warning', text:msg });
}
function swalError(msg){
    Swal.fire({ icon:'error', title:'Error', text:msg });
}
function swalSuccess(msg, redirect=null){
    Swal.fire({
        icon:'success',
        title:'Berhasil',
        text:msg,
        timer:1500,
        showConfirmButton:false
    }).then(()=>{
        if(redirect) window.location.href = redirect;
    });
}
function swalLoading(){
    Swal.fire({
        title:'Processing...',
        text:'Mohon tunggu',
        allowOutsideClick:false,
        didOpen:()=>Swal.showLoading()
    });
}

/* =========================
   🔥 FUNCTION PO
========================= */
function pilih_po(no_po){

    $.post("<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_po",{no_po:no_po},function(data){
        $("#detail_pemasukan").html(data);
    });

    $.post("<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_po_header",{no_po:no_po},function(res){
        $("#pemasok").val(res.seller_code).trigger("chosen:updated");
        $("#valuta").val(res.currency).trigger("chosen:updated");
    },"json");
}

/* =========================
   🔥 TABLE CONTROL
========================= */
function hapus_baris(id){
    $("#baris_"+id).remove();
}

function add_baris(){
    let id = parseInt($("#jml").val())+1;

    let html = `
    <tr id="baris_${id}">
        <td class="text-center">
            <a onclick="hapus_baris('${id}')" style="cursor:pointer">
                <i class="fa fa-trash-o" style="font-size:25px;"></i>
            </a>
        </td>
        <td>
            <input type="text" class="form-control" onclick="cari_kode('${id}')" id="form_kode_${id}" name="kode[]">
            <input type="hidden" id="kode_input_${id}" name="kode_input[]">
        </td>
        <td><input type="text" class="form-control" id="form_unit_${id}" name="unit[]" readonly></td>
        <td><input type="number" class="form-control" id="form_qty_${id}" name="jumlah[]" onkeyup="sum_nilai('${id}')"></td>
        <td><input type="number" class="form-control" id="form_harga_${id}" name="harga[]" onkeyup="sum_nilai('${id}')"></td>
        <td><input type="text" class="form-control" id="form_nilai_${id}" name="nilai[]" readonly></td>
        <td><input type="number" class="form-control" id="form_berat_${id}" name="berat[]"></td>
        <td><input type="number" class="form-control" id="form_lot_${id}" name="lot_no[]"></td>
        <td><input type="text" class="form-control" id="form_lokasi_${id}" name="lokasi[]"></td>
    </tr>`;

    $("#isi_tabel").append(html);
    $("#jml").val(id);
}

function sum_nilai(id){
    let qty = parseFloat($("#form_qty_"+id).val()) || 0;
    let harga = parseFloat($("#form_harga_"+id).val()) || 0;
    $("#form_nilai_"+id).val(qty * harga);
}

/* =========================
   🔥 AUTOCOMPLETE BARANG
========================= */
function cari_kode(id){
    $('#form_kode_'+id).autocomplete({
        source:function(request,response){
            $.post("<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
            {term:request.term},function(data){
                response($.map(data,function(item){
                    return {
                        label:item.kd_barang+" - "+item.nm_barang,
                        value:item.kd_barang,
                        kd:item.kd_barang
                    };
                }));
            },"json");
        },
        select:function(event,ui){
            $('#form_kode_'+id).val(ui.item.label);
            $("#kode_input_"+id).val(ui.item.kd);

            $.post("<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
            {kd_barang:ui.item.kd},function(data){
                $("#form_unit_"+id).val(data);
            });

            return false;
        }
    });
}

/* =========================
   🔥 DOCUMENT READY
========================= */
$(function(){

    $(".chzn-select").chosen();

    $(".date").datepicker({
        format:"yyyy-mm-dd",
        autoclose:true
    });

    $.validator.setDefaults({ ignore:":hidden:not(select)" });

    $("#input_pemasukan_hamparan").validate({

        submitHandler:function(form){ 

            /* 🔴 VALIDASI */
           
            if(!$("#pemasok").val()) return swalWarning("Pemasok harus dipilih!");
         

            if($("input[name='kode_input[]']").length == 0)
                return swalWarning("Item tidak boleh kosong!");

            let valid=true;
            $("input[name='jumlah[]']").each(function(){
                if(!$(this).val() || parseFloat($(this).val())<=0){
                    swalWarning("Qty tidak boleh kosong atau 0!");
                    valid=false;
                    return false;
                }
            });
            if(!valid) return false;

            /* 🔵 CONFIRM */
            Swal.fire({
                title:'Simpan data?',
                icon:'question',
                showCancelButton:true,
                confirmButtonText:'Ya'
            }).then((result)=>{

                if(!result.isConfirmed) return;

                $("button[type='submit']").prop("disabled",true);
                swalLoading();

                let formData = new FormData(form);

                $.ajax({
                    url: $(form).attr("action"),
                    type:"POST",
                    data:formData,
                    contentType:false,
                    processData:false,
                    dataType:"json",

                    success:function(res){
                        Swal.close();
                        $("button[type='submit']").prop("disabled",false);

                        if(!res || res.length==0)
                            return swalError("Response kosong");

                        let r = res[0];

                        if(r.status=="good"){
                            swalSuccess("Data berhasil disimpan","<?=base_index();?>pemasukan-hamparan");
                        }
                        else if(r.status=="error"){
                            swalError(r.error_message);
                        }
                        else{
                            swalError("Terjadi error");
                        }
                    },

                    error:function(xhr){
                        Swal.close();
                        $("button[type='submit']").prop("disabled",false);
                        console.log(xhr.responseText);
                        swalError("Server error");
                    }

                });

            });

            return false;
        }

    });

});
</script>

                  <script type="text/javascript">
                  $("#jenisbcmasuk_jenis_dokumen").change(function(){

                        $.ajax({
                        type : "post",
                        url : "<?=base_admin();?>modul/pemasukan_hamparan/get_kd_catdet.php",
                        data : {jenis_dokumen:this.value},
                        success : function(data) {
                            $("#detail_catatan_kd_catdet").html(data);
                            $("#detail_catatan_kd_catdet").trigger("chosen:updated");

                        }
                    });

                  });

                  
                  </script>