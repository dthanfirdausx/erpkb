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
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">BC 1.1</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('bc11_number','Nomor BC 1.1');?></label>
               <div class="row">
                 <div class="col-md-6">
                   <input type="text" class="form-control col-md-6" id="NOMOR_BC11" value="<?= $data_header->nomorBc11 ?>" placeholder="<?=customs_h('bc11_number','Nomor BC 1.1');?>" onkeyup="save_data(this.value,'nomorBc11',$('#ID').val(),'ws_header','id_header')" >
                 </div>
                 <div class="col-md-6">
                   <input type="text"  class="form-control col-md-6 tgl" id="TANGGAL_BC11" value="<?= date("Y-m-d", strtotime($data_header->tanggalBc11)) ?>" placeholder="Tanggal BC 1.1"  onchange="save_data(this.value,'tanggalBc11',$('#ID').val(),'ws_header','id_header')" autocomplete="off" >
                 </div>
               </div>  
            </div>
            <div class="form-group"> 
              <label for="exampleInputEmail1"><?=customs_h('post_number','Nomor Pos');?></label>
               <div class="row">
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="POS_BC11" value="<?= $data_header->posBc11 ?>" placeholder="Pos BC 1.1" onkeyup="save_data(this.value,'posBc11',$('#ID').val(),'ws_header','id_header')">
                 </div>
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="SUBPOS_BC11" value="<?= $data_header->subposBc11 ?>" placeholder="Sub Pos BC 1.1" onkeyup="save_data(this.value,'subposBc11',$('#ID').val(),'ws_header','id_header')">
                 </div>
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="SUBSUBPOS_BC11" value="<?= $data_header->subsubposBc11 ?>" placeholder="Sub Sub Pos BC 1.1" onkeyup="save_data(this.value,'subsubposBc11',$('#ID').val(),'ws_header','id_header')">
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
          <h3 class="box-title">Pengangkutan</h3>
        </div>
        <form role="form"> 
          <div class="box-body">
            <div class="form-group">
                    <label for="exampleInputEmail1">Cara Angkut</label> 
                    <select onchange="save_data(this.value,'kodeCaraAngkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" style="width:100%" name="KODE_CARA_ANGKUT" id="KODE_CARA_ANGKUT" class="form-control form-ref-angkut" > 
                               <option value=""></option>
                                
                                 <?php
                                 $q = $db->query("select * from ref_cara_angkut");
                                 foreach ($q as $k) {
                                   if ($k->id_cara_angkut==$data_angkut->kodeCaraAngkut) {
                                     echo "<option value='$k->id_cara_angkut' selected>$k->id_cara_angkut - $k->cara_angkut</option>";
                                   }else{
                                    echo "<option value='$k->id_cara_angkut'>$k->id_cara_angkut - $k->cara_angkut</option>";
                                   }
                                  
                                 }
                                 ?>
                   </select>               
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama Sarana Angkut</label>
              <input type="text" class="form-control" name="NAMA_PENGANGKUT"  id="NAMA_PENGANGKUT" value="<?= $data_angkut->namaPengangkut ?>" placeholder="Sarana Angkut" onkeyup="save_data(this.value,'namaPengangkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1"><?=customs_h('voy_flight_police_no','Nomor Voy/Flight/No.Pol');?></label>
              <input type="text" class="form-control" name="NOMOR_POLISI"  id="NOMOR_POLISI" value="<?= $data_angkut->nomorPengangkut ?>" placeholder="<?=customs_h('voy_flight_police_no','Nomor Voy/Flight/No.Pol');?>" onkeyup="save_data(this.value,'nomorPengangkut',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label>
              <select class="form-negara-angkut form-control" name="KODE_BENDERA" id="KODE_BENDERA" style="width: 100%" onchange="save_data(this.value,'kodeBendera',<?= $data_angkut->id_pengangkut ?>,'ws_pengangkut','id_pengangkut')" >
              </select>
            </div> 
          </div>
        </form>
      </div> 
     </div> 
     <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Pelabuhan & Tempat Penimbunan</h3>
        </div>
        <form role="form">
          <div class="box-body">
             <div class="form-group"> 
              <label for="kantor">Pelabuhan Muat</label>
              <select style="width: 100%"  class="form-pelabuhan-muat form-control" name="KODE_PEL_MUAT" id="KODE_PEL_MUAT" onchange="save_data(this.value,'kodePelMuat',$('#ID').val(),'ws_header','id_header')">
              </select>
            </div>
             <div class="form-group">  
              <label for="kantor">Pelabuhan Transit</label>
              <select style="width: 100%" class="form-pelabuhan-transit form-control" name="KODE_PEL_TRANSIT" id="KODE_PEL_TRANSIT" onchange="save_data(this.value,'kodePelTransit',$('#ID').val(),'ws_header','id_header')">
              </select> 
            </div> 
            <div class="form-group"> 
              <label for="kantor">Pelabuhan Bongkar</label>
              <select style="width: 100%" class="form-pelabuhan form-control" name="pelabuhan_bongkar2" id="pelabuhan_bongkar2" onchange="get_pelabuhan(this.value)" readonly>
              </select> 
            </div>
          
            <div class="form-group">
                    <label for="exampleInputEmail1">Tempat Penimbunan</label> 
                    <select onchange="save_data(this.value,'kodeTps',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="KODE_TPS" id="KODE_TPS" class="form-control form-ref-tps" > 
                             
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
<script type="text/javascript">   

  var data_angkut = {
    id: "<?= $data_angkut->kodeBendera ?>",
    text: "<?= $data_angkut->kodeBendera." - ".$data_angkut->negara ?>"
};


var newOptionBendera = new Option(data_angkut.text, data_angkut.id, false, false);
$('.form-negara-angkut').append(newOptionBendera).trigger('change');

 var data_pel_muat = {
    id: "<?= $data_header->kodePelMuat ?>",
    text: "<?= $data_header->kodePelMuat." - ".$data_header->pel_muat ?>"
};

var optionPelMuat = new Option(data_pel_muat.text, data_pel_muat.id, false, false);
$('.form-pelabuhan-muat').append(optionPelMuat).trigger('change');

 var data_pel_transit = {
    id: "<?= $data_header->kodePelTransit ?>",
    text: "<?= $data_header->kodePelTransit." - ".$data_header->pel_transit ?>"
};

var optionPelTransit = new Option(data_pel_transit.text, data_pel_transit.id, false, false);
$('.form-pelabuhan-transit').append(optionPelTransit).trigger('change'); 

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }


</script>
