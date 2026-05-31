<?php
$q = $db->query("select * from ws_entitas where id_header='$data_header->id_header'  ");
//echo "select * from ws_entitas where id_header='$data_header->id_header'";
$ada_pengusaha = 0;
foreach ($q as $k) {
   if ($k->kodeEntitas=='3') {
     $data_pengusaha = $k;
   }elseif ($k->kodeEntitas=='5') {
     $data_pemasok = $k;
   }elseif ($k->kodeEntitas=='7') {
     $data_pemilik = $k;
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
    save_data('5','kodeJenisIdentitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas');
    <?php
  }
  ?>
  
</script>
<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Importir/Pengusaha TPB</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control" id="npwp" value="<?= $info->npwp ?>" placeholder="NPWP" readonly="">
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control" id="npwp" value="<?= $info->nama ?>" placeholder="NPWP" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control" id="alamat"  placeholder="NPWP" ><?= $info->alamat ?></textarea>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nomor Izin TPB</label>
              <div class="row">
                 <div class="col-md-6">
                   <input type="text" class="form-control" id="npwp" value="<?= $info->skepkb ?>" placeholder="NPWP" >
                 </div>
                 <div class="col-md-6">
                   <input type="text" class="form-control" id="tglskep" value="<?= $info->tglskep ?>" placeholder="NPWP" >
                 </div>
              </div>        
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">NIB</label>
              <input type="text" class="form-control" id="nib" value="<?= $data_pengusaha->nibEntitas ?>"  placeholder="NIB" onkeyup="save_data(this.value,'nibEntitas',<?= $data_pengusaha->id_entitas ?>,'ws_entitas','id_entitas')" > 
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pemasok</h3>
        </div>
        <form role="form"> 
          <div class="box-body">
            <div class="form-group"> 
              <label for="kantor">Nama</label>
              <select style="width: 100%" class="form-pemasok form-control" name="pemasok" id="pemasok" onchange="get_pemasok(this.value)">
              </select> 
            </div>
            <div class="form-group">
              <label for="kantor">Alamat</label>
              <textarea class="form-control" name="alamat_pemasok" id="alamat_pemasok"></textarea>
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label>
              <select class="form-negara form-control" name="negara" id="negara" style="width: 100%" onchange="save_data(this.value,'KODE_NEGARA_PEMASOK',$('#ID').val(),'tpb_header')" >
              </select>
            </div> 
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pemilik Barang</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control" name="ID_PEMILIK"  id="ID_PEMILIK" value="<?= $data_pemilik->nomorIdentitas ?>" placeholder="NPWP" onkeyup="save_data(this.value,'nomorIdentitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control" name="NAMA_PEMILIK"  id="NAMA_PEMILIK" value="<?= $data_pemilik->namaEntitas ?>" placeholder="Nama Pemilik" onkeyup="save_data(this.value,'namaEntitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" >
            </div>
            <div class="form-group"> 
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control" id="alamatEntitas" onkeyup="save_data(this.value,'alamatEntitas',<?= $data_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" name="alamatEntitas"  placeholder="Alamat Pemilik" ><?= $data_pemilik->alamatEntitas ?></textarea>
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
      id: "<?= $data_pemasok->kodeRefEntitas ?>",
      text: "<?= $data_pemasok->kodeRefEntitas." - ".$data_pemasok->namaEntitas ?>"
  };


  var newOptionMuat = new Option(data2.text, data2.id, false, false);
  $('.form-pemasok').append(newOptionMuat).trigger('change'); 
   

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }

 
 

  function get_pemasok(val) { 

    //let text = "How are you doing today?";
    const pem = val.split(" - "); 

    save_data(pem[0],'kodeRefEntitas',<?= $data_pemasok->id_entitas ?>,'ws_entitas','id_entitas');
    save_data(pem[1],'namaEntitas',<?= $data_pemasok->id_entitas ?>,'ws_entitas','id_entitas');

    $.ajax({ 
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_pemasok",
       type : "POST",
       data : {
         id : pem[0]
       },
       dataType : "JSON",
      // dataTye : 'JSON',
       success : function(data){
         // $("#npwp").val(data.npwp);
         // $("#nama").val(data.nama);
         $("#alamat_pemasok").val(data.alamat);
          save_data(data.alamat,'alamatEntitas',<?= $data_pemasok->id_entitas ?>,'ws_entitas','id_entitas');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });

  }




</script>
