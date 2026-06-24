<div class="row" style="padding-top: 15px">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Harga</h3>
        </div>
        <form role="form">
          <div class="box-body">
            <div class="form-group">
              <label for="exampleInputEmail1">Valuta</label>
              
              <select onchange="save_data(this.value,'kodeValuta',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="KODE_VALUTA" id="KODE_VALUTA" class="form-control f-valuta" >    
                                <option value="">-Pilih Valuta-</option>                             
                                 <?php
                                 $q = $db->query("select kode_valuta,nama_valuta  from ref_valuta");
                                 foreach ($q as $k) {
                                  if ($k->kode_valuta==$data_header->kodeValuta) {
                                     echo "<option value='$k->kode_valuta' selected>$k->kode_valuta - $k->nama_valuta</option>";
                                  }else{
                                     echo "<option value='$k->kode_valuta'>$k->kode_valuta - $k->nama_valuta</option>";
                                  }
                                 
                                 }
                                 ?>
                              </select>
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">NDPBM</label>
              <label for="exampleInputEmail1" style="float: right;"><a style="cursor: pointer;" class="btn btn-primary" onclick="cek_valuta()">Sesuai Valuta Terbaru</a></label>
               <input type="text" class="form-control" name="NDPBM" id="NDPBM" onchange="save_data(this.value,'ndpbm',$('#ID').val(),'ws_header','id_header')" onkeyup="save_data(this.value,'ndpbm',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->ndpbm ?>" >
              
            </div> 
            <div class="form-group">
              <label for="exampleInputEmail1">Harga Barang</label>
              <div class="row">
                <div class="col-md-6">
                  <select onchange="save_data(this.value,'kodeIncoterm',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="KODE_HARGA" id="KODE_HARGA" class="form-control f-harga" >    
                                <option value="">-Pilih Valuta-</option>                             
                                 <?php
                                 $q = $db->query("select *  from ref_incoterm");
                                 foreach ($q as $k) {
                                  if ($k->id_incoterm==$data_header->KODE_HARGA) {
                                    echo "<option value='$k->id_incoterm' selected>$k->id_incoterm - $k->nama_incoterm</option>";
                                  }else{
                                    echo "<option value='$k->id_incoterm'>$k->id_incoterm - $k->nama_incoterm</option>";
                                  }
                                  
                                 }
                                 ?>
                              </select>
                </div>
                <div class="col-md-6">
                  <input type="number" class="form-control"  name="NILAI_INCOTERM" id="NILAI_INCOTERM"   onkeyup="sum_pabean(this.value)" value="<?= $data_header->cif ?>"> 
                </div>
              </div>
              
            </div>

            <div class="form-group">
              <label for="exampleInputEmail1">NILAI CIF</label>
              
               <input type="text" class="form-control" value="<?= $data_header->cif ?>" name="CIF" id="CIF" onchange="save_data(this.value,'cif',$('#ID').val(),'ws_header','id_header')" onkeyup="save_data(this.value,'cif',$('#ID').val(),'ws_header','id_header')"  >
              
            </div> 
            <div class="form-group">
              <label for="exampleInputEmail1">Nilai Pabean</label>
              
               <input type="text" class="form-control" name="cifRupiah" value="<?= $data_header->cifRupiah ?>" id="CIF_RUPIAH" onchange="save_data(this.value,'cifRupiah',$('#ID').val(),'ws_header','id_header')" onkeyup="save_data(this.value,'cifRupiah',$('#ID').val(),'ws_header','id_header')"  >
              
            </div> 
          </div>
        </form>
      </div>
    </div>

    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Harga Lainnya</h3>
        </div>
        <form role="form">
          <div class="box-body">
           
            <div class="form-group">
              <label for="kantor">Biaya Penambahan</label>
              <input type="text" class="form-control" name="BIAYA_TAMBAHAN" id="BIAYA_TAMBAHAN" onkeyup="save_data(this.value,'biayaTambahan',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->biayaTambahan ?>">
            </div>
             <div class="form-group">
              <label for="kantor">Biaya Pengurang</label>
              <input type="text" class="form-control" name="DISKON" id="DISKON" onkeyup="save_data(this.value,'biayaPengurang',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->biayaPengurang ?>">
            </div>
            <div class="form-group">
              <label for="kantor">CNF</label>
              <input type="text" class="form-control" name="DISKON" id="DISKON" onkeyup="save_data(this.value,'DISKON',$('#ID').val(),'ws_header','id_header')" >
            </div>
            <div class="form-group">
              <label for="kantor">Freight</label>
              <input type="text" class="form-control" name="FREIGHT" id="FREIGHT" onkeyup="save_data(this.value,'freight',$('#ID').val(),'ws_header','id_header')"  value="<?= $data_header->freight ?>">
            </div>
            <div class="form-group">
              <label for="exampleInputEmail1">Asuransi</label>
              <div class="row">
                <div class="col-md-6">
                  <select onchange="save_data(this.value,'kodeAsuransi',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="KODE_ASURANSI" id="KODE_ASURANSI" class="form-control f-asuransi" >    
                               <!--  <option value="">-Pilih Valuta-</option>  -->                            
                                 <?php
                                 $q = $db->query("select * from ref_asuransi");
                                 foreach ($q as $k) { 
                                  if ($k->kode_valuta==$data_header->kodeAsuransi) {
                                     echo "<option value='$k->kode_asuransi' selected>$k->kode_asuransi - $k->nama_asuransi</option>";
                                  }else{
                                     echo "<option value='$k->kode_asuransi'>$k->kode_asuransi - $k->nama_asuransi</option>";
                                  }
                                 
                                 }
                                 ?> 
                              </select> 
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="ASURANSI" id="ASURANSI" onkeyup="save_data(this.value,'asuransi',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->asuransi ?>"> 
                </div>
              </div>
              
            </div>
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-4">
      <div class="row">
        <div class="col-md-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Berat</h3>
            </div>
            <form role="form">
              <div class="box-body">
                 <div class="form-group">
                  <label for="kantor">Berat Kotor (KGM)</label>
                  <input type="text" class="form-control" name="BRUTO" id="BRUTO" onkeyup="save_data(this.value,'bruto',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->bruto ?>">
                </div>
                 <div class="form-group">
                  <label for="kantor">Berat Bersih (KGM)</label>
                  <input type="text" class="form-control" name="NETTO" id="NETTO" onkeyup="save_data(this.value,'netto',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->netto ?>">
                </div>
              </div>
            </form> 
        
          </div>

          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title"><?=customs_h('tax_information','Keterangan Pajak');?></h3>
            </div>
            <form role="form">
              <div class="box-body">
                 <div class="form-group">
                  <label for="kantor">Jasa Kena Pajak</label>
                   <select onchange="save_data(this.value,'kodeKenaPajak',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="JASA_KENA_PAJAK" id="JASA_KENA_PAJAK" class="form-control f-pajak" >    
                                <option value="">-Pilih Valuta-</option>                             
                                 <?php
                                 $q = $db->query("select kode_pajak,nama_pajak  from ref_kena_pajak");
                                 foreach ($q as $k) {
                                  if ($k->kode_pajak==$data_header->kodeKenaPajak) {
                                     echo "<option value='$k->kode_pajak' selected>$k->kode_pajak - $k->nama_pajak</option>";
                                  }else{
                                      echo "<option value='$k->kode_pajak'>$k->kode_pajak - $k->nama_pajak</option>";
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
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" onclick="activaTab('tab_entitas')">Next >></a>
  </div>
</div>
<script type="text/javascript">  


   $(document).ready(function() {
     $(".f-valuta").select2();
     $(".f-harga").select2();
     $(".f-pajak").select2();
   });

  function activaTab(tab){
      $('.nav-tabs a[href="#' + tab + '"]').tab('show');
  }

  function sum_pabean(val){
    var ndpmb = parseFloat($("#NDPBM").val());
    var cif = parseFloat(val);
    var cif_rupiah = 0;
    cif_rupiah = cif * ndpmb;
    $("#CIF_RUPIAH").val(cif_rupiah); 
    $("#CIF").val(cif);
    save_data(cif,'cif',$('#ID').val(),'ws_header','id_header');
    save_data(val,'nilaiIncoterm',$('#ID').val(),'ws_header','id_header');
    save_data(cif_rupiah,'cifRupiah',$('#ID').val(),'ws_header','id_header');
  }



  function cek_valuta(){
    var kode = $("#KODE_VALUTA").val();
    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_currency",
       type : "POST",
       data : {
         kode : kode, 
         //d_header : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
         $("#NDPBM").val(data);
         save_data(data,'NDPBM',$('#ID').val(),'ws_header','id_header');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });
  }
 






</script>
