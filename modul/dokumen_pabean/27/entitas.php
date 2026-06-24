<?php
$qa = $db->query("select id_entitas from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
if ($qa->rowCount()==0) {
  $data_asal = array('id_header' => $data_header->id_header , 
                     'ket' => 'asal');
  $db->insert("ws_entitas",$data_asal);
  $qa = $db->query("select * from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $entitas_asal = $ka;
  }
}else{
  $qa = $db->query("select * from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $entitas_asal = $ka;
  }
}

$qa = $db->query("select id_entitas from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
if ($qa->rowCount()==0) {
  $data_asal = array('id_header' => $data_header->id_header , 
                     'ket' => 'tujuan');
  $db->insert("ws_entitas",$data_asal);
  $qa = $db->query("select * from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $data_tujuan = $ka;
  }
}else{
  $qa = $db->query("select * from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $data_tujuan = $ka;
  }
}

$qa = $db->query("select id_entitas from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
if ($qa->rowCount()==0) {
  $data_asal = array('id_header' => $data_header->id_header , 
                     'ket' => 'pemilik');
  $db->insert("ws_entitas",$data_asal);
  $qa = $db->query("select * from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $entitas_pemilik = $ka;
  }
}else{
  $qa = $db->query("select * from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
  foreach ($qa as $ka) {
     $entitas_pemilik = $ka;
  }
}

?>

<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">TPB Asal Barang / Pengusaha Kena Pajak</h3>
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
              <label for="exampleInputEmail1"><?=customs_h('tpb_license_number','Nomor Izin TPB');?></label>
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
              <input type="text" class="form-control" id="nib" value="<?= $entitas_asal->nibEntitas ?>"  placeholder="NIB" onkeyup="save_data(this.value,'nibEntitas',<?= $entitas_asal->id_entitas ?>,'ws_entitas','id_entitas')" > 
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">TPB Tujuan Barang / Pembeli Barang Kena Pajak / Penerima Jasa Kena Pajak / Penerima Barang</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control"  value="<?= $data_tujuan->nomorIdentitas ?>" placeholder="NPWP" onkeyup="save_data(this.value,'nomorIdentitas',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')">
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control"  value="<?= $data_tujuan->namaEntitas ?>"  onkeyup="save_data(this.value,'namaEntitas',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control"  onkeyup="save_data(this.value,'alamatEntitas ',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')"><?= $data_tujuan->alamatEntitas  ?></textarea>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('tpb_license_number','Nomor Izin TPB');?></label>
              <div class="row">
                 <div class="col-md-6">
                   <input type="text" class="form-control" value="<?= $data_tujuan->nomorIjinEntitas ?>" placeholder="<?=customs_h('tpb_license_number','Nomor Izin TPB');?>" onkeyup="save_data(this.value,'nomorIjinEntitas ',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')" >
                 </div>
                 <div class="col-md-6">
                   <input type="text" class="form-control tgl" id="tglskep" value="<?= $data_tujuan->tanggalIjinEntitas ?>" placeholder="<?=customs_h('date','Tanggal');?>" onkeyup="save_data(this.value,'tanggalIjinEntitas ',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')" onchange="save_data(this.value,'tanggalIjinEntitas ',<?= $data_tujuan->id_entitas ?>,'ws_entitas','id_entitas')" >
                 </div>
              </div>         
            </div>
           
          </div>
        </form>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">TPB Tujuan Barang / Pembeli Barang Kena Pajak / Penerima Jasa Kena Pajak / Penerima Barang</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">NPWP</label>
              <input type="text" class="form-control"  value="<?= $entitas_pemilik->nomorIdentitas ?>" placeholder="NPWP" onkeyup="save_data(this.value,'nomorIdentitas',<?= $entitas_pemilik->id_entitas ?>,'ws_entitas','id_entitas')">
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama</label>
              <input type="text" class="form-control"  value="<?= $entitas_pemilik->namaEntitas ?>"  onkeyup="save_data(this.value,'namaEntitas',<?= $entitas_pemilik->id_entitas ?>,'ws_entitas','id_entitas')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Alamat</label>
              <textarea class="form-control"  onkeyup="save_data(this.value,'alamatEntitas ',<?= $entitas_pemilik->id_entitas ?>,'ws_entitas','id_entitas')"><?= $entitas_pemilik->alamatEntitas  ?></textarea>
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

