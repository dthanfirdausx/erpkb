<?php
$q = $db->query("select * from ws_pengangkut left join ref_negara on ref_negara.kode_negara=ws_pengangkut.kodeBendera where ws_pengangkut.id_header='$data_header->id_header' ");
if ($q->rowCount()==0) {
   $data_angkutp = array('id_header' => $data_header->id_header,
                        'kodeBendera' => NULL,
                        'namaPengangkut' => NULL,
                        'nomorPengangkut' => NULL,
                        'kodeCaraAngkut' => NULL,
                       // 'negara' = 
                        'seriPengangkut' => '1', );
   $db->insert("ws_pengangkut",$data_angkutp);
   $data_angkutp['negara'] = '';
   $data_angkut = (object)$data_angkutp;

}else{
   foreach ($q as $k) {
     $data_angkut = $k; 
   }
}
?>
<div class="row" style="padding-top: 15px">
   

    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pengangkutan</h3>
        </div>
        <form role="form"> 
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Nama Sarana Angkut</label>
              <input type="text" class="form-control" name="NAMA_PENGANGKUT"  id="NAMA_PENGANGKUT" value="<?= $data_angkut->namaPengangkut ?>" placeholder="Sarana Angkut" onkeyup="save_data(this.value,'namaPengangkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nomor Sarana Pengangkut</label>
              <input type="text" class="form-control" name="NOMOR_POLISI"  id="NOMOR_POLISI" value="<?= $data_angkut->nomorPengangkut ?>" placeholder="Nomor Sarana Pengangkut" onkeyup="save_data(this.value,'nomorPengangkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
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

