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
   <!--  <div class="col-md-4">
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
                   <input type="text" class="form-control col-md-6" id="NOMOR_BC11" value="<?= $data_header->nomorBc11 ?>" placeholder="Nomor BC 1.1" onkeyup="save_data(this.value,'nomorBc11',$('#ID').val(),'ws_header','id_header')" >
                 </div>
                 <div class="col-md-6">
                   <input type="text"  class="form-control col-md-6 tgl" id="TANGGAL_BC11" value="<?= date("Y-m-d", strtotime($data_header->tanggalBc11)) ?>" placeholder="Tanggal BC 1.1"  onchange="save_data(this.value,'tanggalBc11',$('#ID').val(),'ws_header','id_header')" autocomplete="off" >
                 </div>
               </div>  
            </div>
            <div class="form-group"> 
              <label for="exampleInputEmail1">Nomor Pos</label>
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
    </div> -->

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
        
        </div>
        <form role="form"> 
          <div class="box-body">
             <div class="form-group">
                    <label for="exampleInputEmail1">Tempat Penimbunan</label> 
                    <select onchange="save_data(this.value,'kodeTps',$('#ID').val(),'ws_header','id_header')" style="width:100%" name="KODE_TPS" id="KODE_TPS" class="form-control form-ref-tps" > 
                             
                   </select>                
            </div>
             <div class="form-group"> 
              <label for="kantor">Pelabuhan Muat Asal</label>
              <select style="width: 100%"  class="form-pelabuhan-muat form-control" name="KODE_PEL_MUAT" id="KODE_PEL_MUAT" onchange="save_data(this.value,'kodePelMuat',$('#ID').val(),'ws_header','id_header')">
              </select>
            </div>
             <div class="form-group"> 
              <label for="kantor">Pelabuhan Bongkar</label>
              <select style="width: 100%" class="form-pelabuhan form-control" name="pelabuhan_bongkar2" id="pelabuhan_bongkar2" onchange="save_data(this.value,'kodePelBongkar',$('#ID').val(),'ws_header','id_header')" onchange="get_pelabuhan(this.value)" readonly> 
              </select> 
            </div> 
             <div class="form-group">  
              <label for="kantor">Pelabuhan Tujuan</label>
              <select style="width: 100%" class="form-pelabuhan-transit form-control" name="KODE_PEL_TRANSIT" id="KODE_PEL_TRANSIT" onchange="save_data(this.value,'kodePelTransit',$('#ID').val(),'ws_header','id_header')">
              </select> 
            </div> 
             <div class="form-group">
              <label for="kantor">Negara Tujuan Ekspor</label>
              <select class="form-negara-angkut form-control" name="kodeNegaraTujuan" id="kodeNegaraTujuan" style="width: 100%" onchange="save_data(this.value,'kodeNegaraTujuan',$('#ID').val(),'ws_header','id_header')" > 
              </select>
            </div>
             <div class="form-group">
              <label for="kantor">Tanggal Perkiraan Ekspor</label>
              <input type="text" onchange="save_data(this.value,'tanggalPerkiraanEkspor',$('#ID').val(),'ws_header','id_header')" value="<?= $data_header->tanggalPerkiraanEkspor ?>" name="tanggalPerkiraanEkspor" id="tanggalPerkiraanEkspor" class="form-control tgl" autocomplete="off"> 
    
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
              <label for="kantor">Lokasi Pemeriksaan</label>
              <select style="width: 100%" class="form-lokasi-periksa form-control" name="lokasiPeriksa" id="lokasiPeriksa" onchange="save_data(this.value,'lokasiPeriksa',$('#ID').val(),'ws_header','id_header')" > 
              <?php
              $ql = $db->query("select id_lokasi,nama_lokasi from ref_lokasi_periksa");
              foreach ($ql as $kl) {
                if ($data_header->lokasiPeriksa==$kl->id_lokasi) {
                   echo "<option value='$kl->id_lokasi' selected>$kl->id_lokasi - $kl->nama_lokasi</option>";
                }else{ 
                   echo "<option value='$kl->id_lokasi'>$kl->id_lokasi - $kl->nama_lokasi</option>";
                }
              } 
              ?>
              </select> 
            </div> 
             <div class="form-group">
              <label for="kantor">Tanggal Periksa</label>
              <input type="text" onchange="save_data(this.value,'tanggalPeriksa',$('#ID').val(),'ws_header','id_header')" name="tanggalPeriksa" value="<?= $data_header->tanggalPeriksa ?>" id="tanggalPeriksa" class="form-control tgl" autocomplete="off"> 
     
            </div>
            <div class="form-group"> 
              <label for="kantor">Kantor Periksa</label>
              <select style="width: 100%" class="form-kantor-periksa form-control" name="kantorPeriksa" id="kantorPeriksa" onchange="save_data(this.value,'kantorPeriksa',$('#ID').val(),'ws_header','id_header')" > 
              </select> 
            </div>  
           
          
           
          </div>

        </form> 

      </div>
    </div>
    
</div>
<div class="row">
  <div class="col-md-12">
    <div class="box-body">
           <div class="box-header with-border">
           <h3>Sarana Angkut</h3>
           <button style="float: right;" class="btn btn-primary" onclick="tambah_pengangkut()">Tambah</button>
           <table class="table">
             <thead>
               <tr>
                 <th>Seri</th>
                 <th>Nama Sarana Angkut</th>
                 <th>Nomor Pengangkut</th>
                 <th>Cara Pengangkutan</th>
                 <th>Kode Bendera</th>
               </tr>
             </thead>
             <tbody id="detail_pengangkut">
              <?php
              $qh = $db->query("select * from v_ws_angkut where id_header='$data_header->id_header' and simpan='1' "); 
                 foreach ($qh as $kh) {
                    echo "<tr>
                            <td>$kh->seriPengangkut</td>
                            <td>$kh->namaPengangkut</td>
                            <td>$kh->nomorPengangkut</td>
                            <td>$kh->cara_angkut</td>
                            <td>$kh->kodeBendera</td>
                             <td>
                             <button class='btn btn-primary' onclick='edit_pengangkut($kh->id_pengangkut)'><i class='fa fa-pencil'></i></button>
                             <button class='btn btn-danger' onclick='modal_delete_pengangkut($kh->id_pengangkut)'><i class='fa fa-trash'></i></button>
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
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_kemasan')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_header')">Back <<</a>
  </div>
</div> 


<div id="modal_pengangkut" class="modal fade" role="dialog">
                <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Pengangkut</h4>
                    </div>
                    <div class="modal-body" id="isi_form_pengangkut">
                      <form id="input_dokumen_pabean" method="post" class="form-horizontal foto_banyak" action="#">

                          <input type="hidden" name="id_pengangkut" id="id_pengangkut">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="seriPengangkut" id="seriPengangkut" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->
                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nama Sarana Angkut </label>
                            <div class="col-lg-9">
                              <input type="text" name="namaPengangkut" onkeyup="save_data(this.value,'namaPengangkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')"  class="form-control">
                            </div>
                          </div>
                          
                          

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Cara Pengangkutan </label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeCaraAngkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" style="width:100%" name="kodeCaraAngkut" id="kodeCaraAngkut" class="form-control form-ref-dokumen" > 
                                  <option value=''>-Pilih Cara Angkut-</option>
                                 <?php
                                 $q = $db->query("select id_cara_angkut,cara_angkut from ref_cara_angkut");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_cara_angkut'>$k->id_cara_angkut - $k->cara_angkut</option>";
                                 }
                                 ?>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nomor Voy/Flight/No.Pol </label>
                            <div class="col-lg-9">
                              <input type="text" name="nomorPengangkut" onkeyup="save_data(this.value,'nomorPengangkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" id="nomorPengangkut" class="form-control">
                            </div>
                          </div>

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Bendera</label>
                            <div class="col-lg-9">
                             <select class="form-negara form-control" name="kodeBendera" id="kodeBendera" style="width: 100%" onchange="save_data(this.value,'kodeBendera',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" >
                               </select>
                            </div>
                          </div> 
                          
                                  
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_pengangkut()" >Simpan</button>
                    </div>
                  </div>

                </div>
              </div>
<script type="text/javascript">   

  var data_angkut = {
    id: "<?= $data_header->kodeNegaraTujuan ?>",
    text: "<?= $data_header->kodeNegaraTujuan." - ".$data_header->negaraTujuan ?>"
};
 
 
var newOptionBendera = new Option(data_angkut.text, data_angkut.id, false, false);
$('.form-negara-angkut').append(newOptionBendera).trigger('change');



 var data_kantor_periksa = {
    id: "<?= $data_header->kantorPeriksa ?>",
    text: "<?= $data_header->kantorPeriksa." - ".$data_header->namaKantorPeriksa ?>"
};
 

var newOptionKantorPeriksa= new Option(data_kantor_periksa.text, data_kantor_periksa.id, false, false);
$('.form-kantor-periksa').append(newOptionKantorPeriksa).trigger('change');


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

function tambah_pengangkut(){
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_id_pengangkut",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
       dataType : 'JSON',
       success : function(data){
        $("#id_pengangkut").val(data.id);
        $("#seriPengangkut").val(data.seri);
        $("#modal_pengangkut").modal('show');
       }
    });
    
}

function edit_pengangkut(id){
    $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_pengangkut",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : id
        // id_header : $("#ID").val()
       },
      // dataType : 'JSON',
       success : function(data){ 
         $("#isi_form_pengangkut").html(data);
        // $("#seriPengangkut").val(data.seri);
         $("#modal_pengangkut").modal('show');
       }
    });
}

function simpan_pengangkut(){
   $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=simpan_pengangkut",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#id_pengangkut").val(),
         id_header : $("#ID").val()
       },
      // dataType : 'JSON',
       success : function(data){ 
         $("#detail_pengangkut").html(data);
        // $("#seriPengangkut").val(data.seri);
        $("#modal_pengangkut").modal('hide');
       }
    });
}

function modal_delete_pengangkut(id){
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
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_pengangkut",
           type : "POST",
         //  dataType : "JSON",
           data : {
             id : id,
             id_header : $("#ID").val()
           },
          // dataTye : 'JSON',
           success : function(data){
              Swal.fire(
                'Deleted!',
                'Your data has been deleted.',
                'success'
              );
               $("#detail_pengangkut").html(data);
         
           }
        });

       
      }
    });
  }


</script>
