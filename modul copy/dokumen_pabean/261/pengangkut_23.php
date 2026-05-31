<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">BC 1.1</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Nomor BC 1.1</label>
               <div class="row">
                 <div class="col-md-6">
                   <input type="text" class="form-control col-md-6" id="NOMOR_BC11" value="<?= $data_header->NOMOR_BC11 ?>" placeholder="Nomor BC 1.1" onkeyup="save_data(this.value,'NOMOR_BC11',$('#ID').val(),'tpb_header')" >
                 </div>
                 <div class="col-md-6">
                   <input type="text"  class="form-control col-md-6 tgl" id="TANGGAL_BC11" value="<?= date("Y-m-d", strtotime($data_header->TANGGAL_BC11)) ?>" placeholder="Tanggal BC 1.1"  onchange="save_data(this.value,'TANGGAL_BC11',$('#ID').val(),'tpb_header')" autocomplete="off" >
                 </div>
               </div>  
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nomor Pos</label>
               <div class="row">
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="POS_BC11" value="<?= $data_header->POS_BC11 ?>" placeholder="Pos BC 1.1" onkeyup="save_data(this.value,'POS_BC11',$('#ID').val(),'tpb_header')">
                 </div>
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="SUBPOS_BC11" value="<?= $data_header->SUBPOS_BC11 ?>" placeholder="Sub Pos BC 1.1" onkeyup="save_data(this.value,'SUBPOS_BC11',$('#ID').val(),'tpb_header')">
                 </div>
                 <div class="col-md-4">
                   <input type="text" class="form-control" id="SUBSUBPOS_BC11" value="<?= $data_header->SUBSUBPOS_BC11 ?>" placeholder="Sub Sub Pos BC 1.1" onkeyup="save_data(this.value,'SUBSUBPOS_BC11',$('#ID').val(),'tpb_header')">
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
                    <select onchange="save_data(this.value,'KODE_CARA_ANGKUT',$('#ID').val(),'tpb_header')" style="width:100%" name="KODE_CARA_ANGKUT" id="KODE_CARA_ANGKUT" class="form-control form-ref-angkut" > 
                                
                                 <?php
                                 $q = $db->query("select * from ref_cara_angkut");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_cara_angkut'>$k->id_cara_angkut - $k->cara_angkut</option>";
                                 }
                                 ?>
                   </select>               
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nama Sarana Angkut</label>
              <input type="text" class="form-control" name="NAMA_PENGANGKUT"  id="NAMA_PENGANGKUT" value="<?= $data_header->NAMA_PENGANGKUT ?>" placeholder="Sarana Angkut" onkeyup="save_data(this.value,'NAMA_PENGANGKUT',$('#ID').val(),'tpb_header')" >
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Nomor Voy/Flight/No.Pol</label>
              <input type="text" class="form-control" name="NOMOR_POLISI"  id="NOMOR_POLISI" value="<?= $data_header->NOMOR_POLISI ?>" placeholder="Nomor Voy/Flight/No.Pol" onkeyup="save_data(this.value,'NOMOR_POLISI',$('#ID').val(),'tpb_header')" >
            </div>
            <div class="form-group">
              <label for="kantor">Negara</label>
              <select class="form-negara-angkut form-control" name="KODE_BENDERA" id="KODE_BENDERA" style="width: 100%" onchange="save_data(this.value,'KODE_BENDERA',$('#ID').val(),'tpb_header')" >
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
              <select style="width: 100%"  class="form-pelabuhan-muat form-control" name="KODE_PEL_MUAT" id="KODE_PEL_MUAT" onchange="save_data(this.value,'KODE_PEL_MUAT',$('#ID').val(),'tpb_header')">
              </select>
            </div>
             <div class="form-group">  
              <label for="kantor">Pelabuhan Transit</label>
              <select style="width: 100%" class="form-pelabuhan-transit form-control" name="KODE_PEL_TRANSIT" id="KODE_PEL_TRANSIT" onchange="save_data(this.value,'KODE_PEL_TRANSIT',$('#ID').val(),'tpb_header')">
              </select>
            </div>
            <div class="form-group"> 
              <label for="kantor">Pelabuhan Bongkar</label>
              <select style="width: 100%" class="form-pelabuhan form-control" name="pelabuhan_bongkar2" id="pelabuhan_bongkar2" onchange="get_pelabuhan(this.value)" readonly>
              </select> 
            </div>
          
            <div class="form-group">
                    <label for="exampleInputEmail1">Cara Angkut</label> 
                    <select onchange="save_data(this.value,'KODE_TPS',$('#ID').val(),'tpb_header')" style="width:100%" name="KODE_TPS" id="KODE_TPS" class="form-control form-ref-tps" > 
                                
                                 <?php
                                 $q = $db->query("select * from referensi_tps where KD_KANTOR='$data_header->KODE_KANTOR_BONGKAR' ");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->KODE_TPS'>$k->KODE_TPS - $k->URAIAN_TPS</option>";
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
<script type="text/javascript">   

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }


</script>
