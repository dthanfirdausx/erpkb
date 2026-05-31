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
              <label for="exampleInputEmail1">Nama Sarana Angkut</label><br>
             
              <select style="width: 100%" class="form-control kodeCaraAngkut" onchange="save_data(this.value,'kodeCaraAngkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
               <option>Pilih Cara Angkut</option>
               <?php
               foreach ($db->query("select * from ref_cara_angkut") as $k) {
                if ($k->id_cara_angkut==$data_angkut->kodeCaraAngkut) {
                   echo "<option value='$k->id_cara_angkut' selected>$k->id_cara_angkut - $k->cara_angkut</option>";
                }else{
                   echo "<option value='$k->id_cara_angkut'>$k->id_cara_angkut - $k->cara_angkut</option>";
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
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_dokumen')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_header')">Back <<</a>
  </div>
</div> 

