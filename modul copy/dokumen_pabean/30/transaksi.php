<div class="row" style="padding-top: 15px">
    <div class="col-md-6">
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
              <label for="exampleInputEmail1">Cara Penyerahan</label>
              <div class="row">
                <div class="col-md-12">
                  <select onchange="save_data(this.value,'kodeIncoterm',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="kodeIncoterm" id="kodeIncoterm" class="form-control f-harga" >    
                                <option value="">-Pilih Cara Penyerahan-</option>                             
                                 <?php
                                 $q = $db->query("select *  from ref_incoterm");
                                 foreach ($q as $k) {
                                  if ($k->id_incoterm==$data_header->kodeIncoterm) {
                                    echo "<option value='$k->id_incoterm' selected>$k->id_incoterm - $k->nama_incoterm</option>";
                                  }else{
                                    echo "<option value='$k->id_incoterm'>$k->id_incoterm - $k->nama_incoterm</option>";
                                  }
                                  
                                 }
                                 ?>
                              </select>
                </div>
                <!-- <div class="col-md-6">
                  <input type="number" class="form-control"  name="NILAI_INCOTERM" id="NILAI_INCOTERM"   onkeyup="sum_pabean(this.value)" value="<?= $data_header->cif ?>"> 
                </div> -->
              </div>
              
            </div>

            <div class="form-group">
              <label for="exampleInputEmail1">Nilai Ekspor (Incoterm FOB)</label>
              
               <input type="text" class="form-control" value="<?= $data_header->cif ?>" name="CIF" id="CIF" onchange="save_data(this.value,'cif',$('#ID').val(),'ws_header','id_header')" onkeyup="save_data(this.value,'cif',$('#ID').val(),'ws_header','id_header')"  >
              
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
            <div class="form-group">
              <label for="exampleInputEmail1">Nilai Pabean</label>
              
               <input type="text" class="form-control" name="cifRupiah" value="<?= $data_header->cifRupiah ?>" id="CIF_RUPIAH" onchange="save_data(this.value,'cifRupiah',$('#ID').val(),'ws_header','id_header')" onkeyup="save_data(this.value,'cifRupiah',$('#ID').val(),'ws_header','id_header')"  >
              
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

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
         
        </div>
        <form role="form">
          <div class="box-body">
            
            <div class="form-group">
              <label for="kantor">Nilai Maklon</label>
              <input type="text" class="form-control" name="nilaiMaklon" id="nilaiMaklon" onkeyup="save_data(this.value,'nilaiMaklon',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->biayaTambahan ?>">
            </div>
            <div class="form-group">
              <label for="kantor">Nilai Bea Keluar</label>
              
            </div>
            <div class="form-group">
              <label for="kantor">PPH</label>
              <input type="checkbox" name="">
            </div>
             <div class="form-group">
              <label for="kantor">Nilai Pungutan Sawit</label>
              <input type="text" class="form-control" name="DISKON" id="DISKON"  readonly="">
            </div>
         <!--    <div class="form-group">
              <label for="kantor">CNF</label>
              <input type="text" class="form-control" name="DISKON" id="DISKON" onkeyup="save_data(this.value,'DISKON',$('#ID').val(),'ws_header','id_header')" >
            </div> -->
            
           
          </div>
        </form>
      </div> 
    </div> 
 

    
</div>
<div class="row">
  <div class="col-md-12">
    <div class="box-body">
           <div class="box-header with-border">
           <h3>Bank Devisa</h3>
           <button style="float: right;" class="btn btn-primary" onclick="tambah_bank_devisa()">Tambah</button>
           <table class="table">
             <thead>
               <tr>
                 <th>Seri</th>
                 <th>Kode Bank</th>
                 <th>Nama Bank</th>
                 <th></th>
               </tr>
             </thead>
             <tbody id="detail_bank">
              <?php
              $qh = $db->query("select wb.*,b.nama_bank from ws_bank_devisa wb  left join ref_bank b on b.id_bank=wb.kode_bank where wb.id_header='$data_header->id_header' and simpan='1'"); 
                 foreach ($qh as $kh) {
                    echo "<tr>
                            <td>$kh->seriBank</td>
                            <td>$kh->kode_bank</td>
                            <td>$kh->nama_bank</td>
                            
                             <td>
                             <button class='btn btn-primary' onclick='edit_bank($kh->id_detail_bank)'><i class='fa fa-pencil'></i></button>
                             <button class='btn btn-danger' onclick='modal_delete_bank($kh->id_detail_bank)'><i class='fa fa-trash'></i></button>
                           </td>
                         </tr>"; 
                 } 
              ?> 
             </tbody> 
           </table>
          </div>
         </div>
  </div>
</div>

<div class="row"> 
  <div class="col-md-12">

    <a style="float: right" data-toggle="tab" class="btn btn-primary" onclick="activaTab('tab_barang')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_kemasan')">Back <<</a>
  </div>
</div>


<div id="modal_bank" class="modal fade" role="dialog">
                <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Pengangkut</h4>
                    </div>
                    <div class="modal-body" id="isi_form_bank"> 
                      <form id="input_dokumen_pabean" method="post" class="form-horizontal foto_banyak" action="#">

                          <input type="hidden" name="id_detail_bank" id="id_detail_bank">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="seriBank" id="seriBank" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->
                          
 
                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nama Bank</label>
                            <div class="col-lg-9">
                             <select class="form-bank form-control" name="kodeBank" id="kodeBank" style="width: 100%" onchange="save_data(this.value,'kode_bank',$('#id_detail_bank').val(),'ws_bank_devisa','id_detail_bank')" >
                               <?php
                               $qb = $db->query("select id_bank,kode_bank,nama_bank from ref_bank");
                               foreach ($qb as $kb) {
                                  echo "<option value='$kb->id_bank'>$kb->nama_bank</option>";
                               }
                               ?>
                               </select>
                            </div>
                          </div>  
                          
                                  
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_bank()" >Simpan</button>
                    </div>
                  </div>

                </div>
              </div>
<script type="text/javascript">  
function tambah_bank_devisa(){
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_id_bank_devisa",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
       dataType : 'JSON',
       success : function(data){
        $("#id_detail_bank").val(data.id);
        $("#seriBank").val(data.seri);
        $("#modal_bank").modal('show');
       }
    });
    
}

function modal_delete_bank(id){
    Swal.fire({ 
      title: 'Yakin akan di hapus ?',
      text: "data yang sudah terhapus tidak bisa di kembalikan",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) { 

        $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_bank",
           type : "POST",
         //  dataType : "JSON",
           data : {
             id : id,
             id_header : $("#ID").val()
           },
          // dataTye : 'JSON',
           success : function(data){
             $("#detail_bank").html(data);
              Swal.fire(
                'Deleted!',
                'Your data has been deleted.',
                'success'
              );
           
           }
        });

       
      }
    });
  }

function edit_bank(id){
    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_bank",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : id
        // id_header : $("#ID").val()
       },
      // dataType : 'JSON',
       success : function(data){ 
         $("#isi_form_bank").html(data);
        // $("#seriPengangkut").val(data.seri);
         $("#modal_bank").modal('show');
       }
    });
}

function simpan_bank(){
   $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=simpan_bank",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#id_detail_bank").val(),
         id_header : $("#ID").val()
       },
      // dataType : 'JSON',
       success : function(data){ 
         $("#detail_bank").html(data);
        // $("#seriPengangkut").val(data.seri);
        $("#modal_bank").modal('hide');
       }
    });
}

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
