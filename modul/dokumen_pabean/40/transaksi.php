<div class="row" style="padding-top: 15px">
    

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Data Untuk Keperluan Pajak</h3>
        </div>
        <form role="form">
          <div class="box-body">

             <div class="form-group">
              <label for="kantor">Harga Penyerahan/Harga Jual</label>
              <input type="number" class="form-control" name="hargaPenyerahan" id="hargaPenyerahan" onkeyup="save_data(this.value,'hargaPenyerahan',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->hargaPenyerahan ?>">
            </div>
           
            <div class="form-group">
              <label for="kantor">Nilai Penggantian/Nilai Jasa</label>
              <input type="number" class="form-control" name="nilaiJasa" id="nilaiJasa" onkeyup="save_data(this.value,'nilaiJasa',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->nilaiJasa ?>">
            </div>
             <div class="form-group">
              <label for="kantor">Uang Muka</label>
              <input type="number" class="form-control" name="uangMuka" id="uangMuka" onkeyup="save_data(this.value,'uangMuka',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->uangMuka ?>">
            </div>
            <div class="form-group">
              <label for="kantor">Diskon</label>
              <input type="number" class="form-control" name="diskon" id="diskon" onkeyup="save_data(this.value,'diskon',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->diskon ?>">
            </div>
        
         
          </div>
        </form>
      </div> 
    </div> 
 
     <div class="col-md-6">
      <div class="row">
        <div class="col-md-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Berat</h3>
            </div>
            <form role="form">
              <div class="box-body">
                 <div class="form-group">
                  <label for="kantor">Volume</label>
                  <input type="text" class="form-control" name="Volume" id="Volume" readonly="">
                </div>
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
