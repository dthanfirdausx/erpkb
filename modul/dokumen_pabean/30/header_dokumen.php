<div class="row" style="padding-top: 15px">
 


 
     <div class="col-md-12">
      <div class="box box-primary">
      <!--   <div class="box-header with-border">
          <h3 class="box-title">Keterangan Lain</h3>
        </div> -->
      <div class="box-body">
        <form class="form-horizontal" >
          <div class="form-group"> 
              <label  class="control-label col-sm-2"  for="exampleInputEmail1">Nomor Aju</label>
              <div class="col-sm-10">
                    <input type="text" class="form-control" id="no_aju" value="<?= $data_header->nomorAju ?>" placeholder="Nomor Aju" readonly="">
              </div>
          
            </div> 

             <div class="form-group"> 
              <label  class="control-label col-sm-2"  for="exampleInputEmail1">Kantor Pabean Muat Asal</label>
              <div class="col-sm-10">
                     <select class="form-kantor form-control" name="kodeKantorBongkar" id="kodeKantorBongkar" onchange="save_data(this.value,'kodeKantorBongkar',<?= $data_header->id_header ?>,'ws_header','id_header')" > 
                     </select>
              </div>
          
            </div>
          
        
            <div class="form-group"> 
              <label  class="control-label col-sm-2"  for="exampleInputEmail1">Pelabuhan Muat Exspor</label>
              <div class="col-sm-10">
                     <select class="form-pelabuhan form-control" name="pelabuhan_bongkar" id="pelabuhan_bongkar" onchange="get_pelabuhan(this.value)" >
                     </select>
              </div>
           
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Kantor Pabean Bongkar</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" name="kantor_bongkar" id="kantor_bongkar" readonly="">
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Jenis Ekspor</label>
              <div class="col-sm-10">
                <select class="form-jenis-exsport form-control" name="jenisEkspor" id="jenisEkspor" onchange="save_data(this.value,'jenisEkspor',<?= $data_header->id_header ?>,'ws_header','id_header')" >
                  <option value="">-Pilih Jenis Ekspor-</option>
                <?php
                foreach ($db->query("select * from ref_jenis_exsport") as $ref_jenis_exsport) {
                  if ($data_header->jenisEkspor==$ref_jenis_exsport->id_jenis_exsport) {
                     echo "<option value='$ref_jenis_exsport->id_jenis_exsport' selected>$ref_jenis_exsport->jenis_exsport</option>";
                  }else{
                     echo "<option value='$ref_jenis_exsport->id_jenis_exsport'>$ref_jenis_exsport->jenis_exsport</option>";
                  }
                  
                }
                ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Kategori Ekspor</label>
              <div class="col-sm-10">
                <select class="form-kategori-exsport form-control" name="kategoriEkspor" id="kategoriEkspor" onchange="save_data(this.value,'kategoriEkspor',<?= $data_header->id_header ?>,'ws_header','id_header')"  >
                  <option value="">-Pilih Kategori Ekspor-</option>
                <?php
                foreach ($db->query("select * from ref_kategori_ekspor") as $ref_kategori_ekspor) {
                  if ($data_header->kategoriEkspor==$ref_kategori_ekspor->id_kategori_ekspor) {
                   echo "<option value='$ref_kategori_ekspor->id_kategori_ekspor' selected>$ref_kategori_ekspor->kategori_ekspor</option>";
                  }else{
                    echo "<option value='$ref_kategori_ekspor->id_kategori_ekspor'>$ref_kategori_ekspor->kategori_ekspor</option>";
                   
                }
              }
                ?>
                </select>
                  
              </div>
            </div>
         
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Cara Dagang</label>
              <div class="col-sm-10">
                <select class="form-cara-dagang form-control" name="caraDagang" id="caraDagang" onchange="save_data(this.value,'caraDagang',<?= $data_header->id_header ?>,'ws_header','id_header')" >
                <option value="">-Pilih Cara Dagang-</option>
                <?php
                foreach ($db->query("select * from ref_cara_dagang") as $ref_cara_dagang) {

                    if ($data_header->caraDagang==$ref_cara_dagang->id_cara_dagang) {
                       echo "<option value='$ref_cara_dagang->id_cara_dagang' selected>$ref_cara_dagang->cara_dagang</option>";
                    }else{
                        echo "<option value='$ref_cara_dagang->id_cara_dagang'>$ref_cara_dagang->cara_dagang</option>";
                    }

                 
                }
                ?>
                </select>
              </div>
            </div>
             <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Cara Bayar</label>
              <div class="col-sm-10">
                <select class="form-cara-bayar form-control" name="caraBayar" id="caraBayar" onchange="save_data(this.value,'caraBayar',<?= $data_header->id_header ?>,'ws_header','id_header')" >
                  <option value="">-Pilih Cara Bayar-</option>
                <?php
                foreach ($db->query("select * from ref_cara_bayar") as $ref_cara_bayar) {
                  if ($data_header->caraBayar==$ref_cara_bayar->id_cara_bayar) {
                   echo "<option value='$ref_cara_bayar->id_cara_bayar' selected>$ref_cara_bayar->cara_bayar</option>";
                  } else{

                    echo "<option value='$ref_cara_bayar->id_cara_bayar'>$ref_cara_bayar->cara_bayar</option>";
                }
                 }
                ?>
                </select>
                 
              </div>
            </div> 
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Komoditi</label>
              <div class="col-sm-10">
                <select class="form-komoditi form-control" name="komoditi" id="komoditi"  onchange="save_data(this.value,'komoditi',<?= $data_header->id_header ?>,'ws_header','id_header')">
                <option value="">-Pilih Komoditi-</option>
                <?php
                foreach ($db->query("select * from ref_komoditi") as $ref_komoditi) {
                if ($data_header->komoditi==$ref_komoditi->id_komoditi) {
                  echo "<option value='$ref_komoditi->id_komoditi' selected>$ref_komoditi->nama_komoditi</option>";
                }else{
                   echo "<option value='$ref_komoditi->id_komoditi'>$ref_komoditi->nama_komoditi</option>";
                }
              }
                ?>
                </select>
                
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-2" for="kantor">Curah</label>
              <div class="col-sm-10">
                <select class="form-curah form-control" name="curah" id="curah" onchange="save_data(this.value,'curah',<?= $data_header->id_header ?>,'ws_header','id_header')"  >
                <option value="">-Pilih Curah-</option> 
                <?php
                foreach ($db->query("select * from ref_curah") as $ref_curah) {
                 if ($data_header->curah==$ref_curah->id_curah) {
                   echo "<option value='$ref_curah->id_curah' selected>$ref_curah->nama_curah</option>";
                 }  else {
                    echo "<option value='$ref_curah->id_curah'>$ref_curah->nama_curah</option>";
                }
              }
                ?>
                </select>
                 
              </div>
            </div>
          
        </form> 
      </div>
      </div>
    </div>
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" onclick="activaTab('tab_entitas')">Next >></a>
  </div>
</div>
<script type="text/javascript">  


 

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  } 
 
 
  function simpan_tujuan(val){  
      save_data(val,'kodeTujuanTpb',$("#ID").val(),'ws_header','id_header'); 
  }  
 

  function get_pelabuhan(val) {     
  //  alert("pelabuhan");

    save_data(val,'kodePelBongkar',$("#ID").val(),'ws_header','id_header'); 

    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_pelabuhan",
       type : "POST",
       data : {
         id : val,
         id_header : $("#ID").val() 
       },
      // dataTye : 'JSON',
       success : function(datas){ 
         $("#kantor_bongkar").val(datas); 
         var delimiter = " - ";
         var key = datas.split(delimiter);  
       //  save_data(key[0],'kodeKantorBongkar',$("#ID").val(),'ws_header','id_header'); 
         $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_tps",
           type : "POST",
           dataType : "JSON",
           data : {
             val : key,  
             //id_header : $("#ID").val()
           },
          // dataTye : 'JSON',
           success : function(datap){ 
             $(".form-ref-tps").select2({
               data: datap
             });
           }
        });
        
       }
    });

  }




</script>
