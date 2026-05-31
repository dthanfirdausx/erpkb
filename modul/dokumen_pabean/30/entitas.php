<?php
$q = $db->query("select * from ws_entitas where id_header='$data_header->id_header'  ");
//echo "select * from ws_entitas where id_header='$data_header->id_header'";
$ada_pengusaha = 0;
foreach ($q as $k) {
   if ($k->kodeEntitas=='2') {
     $data_pengusaha = $k;
   }elseif ($k->kodeEntitas=='8') {
     $data_penerima = $k;
   }elseif ($k->kodeEntitas=='7') {
     $data_pemilik = $k;
   }elseif ($k->kodeEntitas=='6') {
     $data_pembeli = $k;
   }elseif ($k->kodeEntitas=='4') {
     $data_pembeli = $k;
   }
   $ada_pengusaha = 1;
} 
?>
<script type="text/javascript">
  <?php
  if ($ada_pengusaha=='1') {
    ?>
    save_data('<?= $info->npwp ?>','nomorIdentitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    save_data('<?= $info->nama ?>','namaEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    save_data('<?= $info->alamat ?>','alamatEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    save_data('<?= $info->skepkb ?>','nomorIjinEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    save_data('<?= $info->tglskep ?>','tanggalIjinEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    // save_data('5','kodeJenisIdentitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    <?php
  }
  ?>
  
</script>
<div class="row" style="padding-top: 15px">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Eksportir</h3>
        </div>
         <div class="box-body">
         <form class="form-horizontal" >
         
            <div class="form-group">
             <!--  <label for="exampleInputEmail1">NPWP</label> -->
              <label  class="control-label col-sm-3"  for="exampleInputEmail1">Nomor Identitas</label>
              <div class="col-sm-4">
               <select id="kodeEntitas" class="form-control" id="kodeEntitas">
               <?php
               foreach ($db->query("select * from ref_jenis_identitas") as $rf) {
                 echo "<option value='$rf->id_jenis_identitas'>$rf->jenis_identitas</option>";
               }
               ?>
               </select> 
              </div>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="npwp" value="<?= $info->npwp ?>" placeholder="NPWP" >
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-3"  for="exampleInputEmail1">Nama</label>
              <div class="col-sm-9">
                <input type="text" class="form-control" id="npwp" value="<?= $info->nama ?>" placeholder="NPWP" >
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-3" for="exampleInputEmail1">Alamat</label>
              <div class="col-sm-9">
                <textarea class="form-control" id="alamat"  placeholder="Alamat" onchange="save_data(this.value,'alamatEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas')"><?= $info->alamat ?></textarea> 
              </div>
            </div>
             <div class="form-group">
              <label class="control-label col-sm-3" for="exampleInputEmail1">Status</label>
              <div class="col-sm-9">
               <select id="kodeEntitas" class="form-control" id="kodeStatus" onchange="save_data(this.value,'kodeStatus',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas')">
               <?php  
               foreach ($db->query("select * from ref_status_pengusaha") as $rf) {
                if ($rf->id_status_pengusaha==$data_pengusaha->kodeStatus) {
                   echo "<option value='$rf->id_status_pengusaha' selected>$rf->status_pengusaha</option>";
                 } else {
                   echo "<option value='$rf->id_status_pengusaha'>$rf->status_pengusaha</option>";
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

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Penerima</h3>
        </div>
        <form role="form"> 
          <div class="box-body">
            <div class="form-group"> 
              <label for="kantor">Nama</label>
              <select style="width: 100%" class="form-penerima form-control" name="nama_penerima" id="nama_penerima" onchange="get_pemasok(this.value)">
              </select> 
            </div>
            <div class="form-group">
              <label for="kantor">Alamat</label>
              <textarea class="form-control" name="alamat_penerima" id="alamat_penerima" onkeyup="save_data(this.value,'alamatEntitas',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas')"><?= $data_penerima->alamatEntitas  ?></textarea> 
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label>
              <select class="form-negara form-control" name="negara_penerima" id="negara_penerima" style="width: 100%" onchange="save_data(this.value,'kodeNegara',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas')" >
              </select>
            </div>  
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pembeli</h3>
          <a onclick='salin_penerima()' class="btn btn-success" style="float: right;" >Salin data penerima</a>
       
        </div>
         <form role="form"> 
          <div class="box-body">
            <div class="form-group">  
              <label for="kantor">Nama</label>
              <select style="width: 100%" class="form-pembeli form-control" name="nama_pembeli" id="nama_pembeli" onchange="get_pembeli(this.value)"> 
              </select> 
            </div>
            <div class="form-group">
              <label for="kantor">Alamat</label>
              <textarea class="form-control" name="alamat_pembeli" id="alamat_pembeli"   onkeyup="save_data(this.value,'alamatEntitas',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas')"><?= $data_pembeli->alamatEntitas  ?></textarea>
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label> 
              <select class="form-negara-pembeli form-control" name="negara_pembeli" id="negara_pembeli" style="width: 100%" onchange="save_data(this.value,'kodeNegara',<?= $data_pembeli->id_entitas ?>,'ws_entitas','id_entitas')" >
              </select>
            </div>  
          </div>
        </form>
    
      </div>
    </div>

     <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pemilik Barang</h3>
        </div>
        <form role="form" class="form-horizontal">
          <div class="box-body">
            <div class="form-group">
             <!--  <label for="exampleInputEmail1">NPWP</label> -->
              <label  class="control-label col-sm-3"  for="exampleInputEmail1">Nomor Identitas</label>
              <div class="col-sm-4">
                   <select id="kodeJenisEntitas" class="form-control" id="kodeJenisEntitas" onchange="save_data(this.value,'kodeJenisEntitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')">
                   <?php
                   foreach ($db->query("select * from ref_jenis_identitas") as $rf) {
                     echo "<option value='$rf->id_jenis_identitas'>$rf->jenis_identitas</option>";
                   }
                   ?> 
                   </select> 
              </div>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="npwp" onchange="save_data(this.value,'nomorIdentitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" value="<?= $info->npwp ?>" placeholder="NPWP" >
              </div>
            </div>
            
            <div class="form-group">
              <label  class="control-label col-sm-3"  for="exampleInputEmail1">Nama</label>
               <div class="col-sm-9">
              <input type="text" class="form-control" name="NAMA_PEMILIK"  id="NAMA_PEMILIK" value="<?= $data_pemilik->namaEntitas ?>" placeholder="Nama Pemilik" onkeyup="save_data(this.value,'namaEntitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" >
            </div>
            </div>
            <div class="form-group"> 
               <label  class="control-label col-sm-3"  for="exampleInputEmail1">Alamat</label>
               <div class="col-sm-9">
              <textarea class="form-control" id="alamatEntitas" onkeyup="save_data(this.value,'alamatEntitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" name="alamatEntitas"  placeholder="Alamat Pemilik" ><?= $data_pemilik->alamatEntitas ?></textarea>
            </div>
            </div>
          </div>
        </form> 
    
      </div>
    </div>
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_dokumen')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_header')">Back <<</a>
  </div>
</div> 
<script type="text/javascript">   

  var data2 = {
      id: "<?= $data_penerima->kodeRefEntitas ?>",
      text: "<?= $data_penerima->kodeRefEntitas." - ".$data_penerima->namaEntitas ?>"
  };

  var data_pembeli = {
      id: "<?= $data_pembeli->kodeRefEntitas ?>",
      text: "<?= $data_pembeli->kodeRefEntitas." - ".$data_pembeli->namaEntitas ?>"
  };

   var data_negara = {
      id: "<?= $data_penerima->kodeNegara ?>",
      text: "<?= $data_penerima->kodeNegara ?>"
  };

   var data_negara_pembeli = {
      id: "<?= $data_pembeli->kodeNegara ?>",
      text: "<?= $data_pembeli->kodeNegara ?>" 
  };  

  var newOptionMuat = new Option(data2.text, data2.id, false, false);
  $('.form-penerima').append(newOptionMuat).trigger('change'); 

  var opsiPembeli = new Option(data_pembeli.text, data_pembeli.id, false, false);
  $('.form-pembeli').append(opsiPembeli).trigger('change');  

  var opsiNegara = new Option(data_negara.text, data_negara.id, false, false);
  $('.form-negara').append(opsiNegara).trigger('change'); 

   var opsiNegaraPembeli = new Option(data_negara_pembeli.text, data_negara_pembeli.id, false, false);
  $('.form-negara-pembeli').append(opsiNegaraPembeli).trigger('change'); 
   

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }

  function get_pemasok(val) {    

          //let text = "How are you doing today?";
          const pem = val.split(" - ");  

          save_data(pem[0],'kodeRefEntitas',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas');
          save_data(pem[1],'namaEntitas',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas');

          $.ajax({ 
             url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_penerima",
             type : "POST",
             data : { 
               id : pem[0]
             }, 
             dataType : "JSON",
            // dataTye : 'JSON',
             success : function(data){
               // $("#npwp").val(data.npwp);
               // $("#nama").val(data.nama);
               $("#alamat_penerima").val(data.alamat);   
                save_data(data.alamat,'alamatEntitas',<?= $data_penerima->id_entitas ?>,'ws_entitas','id_entitas');
              // $("#kantor_pabean_pengawas").val(data);
             }
          });

        }
    function get_pembeli(val) {     

          //let text = "How are you doing today?";
          const pem = val.split(" - ");  

          save_data(pem[0],'kodeRefEntitas',<?= $data_pembeli->id_entitas ?>,'ws_entitas','id_entitas');
          save_data(pem[1],'namaEntitas',<?= $data_pembeli->id_entitas ?>,'ws_entitas','id_entitas');

          $.ajax({ 
             url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_penerima",
             type : "POST",
             data : { 
               id : pem[0]
             }, 
             dataType : "JSON", 
            // dataTye : 'JSON',
             success : function(data){
               // $("#npwp").val(data.npwp);
               // $("#nama").val(data.nama);
               $("#alamat_pembeli").val(data.alamat);  
                save_data(data.alamat,'alamatEntitas',<?= $data_pembeli->id_entitas ?>,'ws_entitas','id_entitas');
              // $("#kantor_pabean_pengawas").val(data);
             }
          });

        }

 
   function salin_penerima(){ 
      $("#alamat_pembeli").val($("#alamat_penerima").val());

    }

  




</script>
