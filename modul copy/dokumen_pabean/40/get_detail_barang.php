<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../../inc/config.php";
session_check_json();
$idBarang = $_POST['id'];
  // echo "select b.*,s.satuan from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang where b.idBarang='$idBarang'";
  foreach ($db->query("select b.*,s.satuan,h.kodeDokumen,h.id_header from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang left join ws_header h on h.id_header=b.id_header where b.idBarang='$idBarang' ") as $k) {
    $uraian = $k->uraian;
    $qb = $db->query("select * from ws_barang_tarif where idBarang='$idBarang' ");
    if ($qb->rowCount()==0) {
       $data_barang_tarif = array('idBarang' => $idBarang,
                                  'kodeJenisTarif' => '1',
                                  'kodeSatuanBarang' => $k->kodeSatuanBarang,
                                  'kodeJenisPungutan' => 'PPN',
                                  'kodeFasilitasTarif' => '3' );
       $db->insert("ws_barang_tarif",$data_barang_tarif);
       $id_tarif_barang = $db->last_insert_id();
    }else{
        $qb = $db->query("select * from ws_barang_tarif where idBarang='$idBarang' ");
      foreach ($qb as $kb) {
        $id_tarif_barang = $kb->id_tarif_barang;
        $data_barang_tarif = $kb;
      }
    }
 
  ?>
  <div class="col-md-12"> 
    <form role="form">

         
                      <div class="row">
                        <div class="col-md-4">
                          <div class="box box-primary">
                            <div class="box-header with-border">
                              <h3 class="box-title">Jenis</h3> 
                            </div>
                            <div class="form-group">
                                <label for="nomor" >Seri </label>
                                  
                                  <input type="text" name="seriBarang" value="<?= $k->seriBarang ?>" id="seriBarang" class="form-control" readonly="">
                                
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Uraian </label>
                                
                                 <!--  <textarea class="form-control" name="uraian" id="uraian" value="<?= $k->uraian ?>" onkeyup="save_data(this.value,'uraian',$('#id_barang').val(),'ws_barang','idBarang')"></textarea> -->
                                  <select style="width: 100%" class="form-uraian form-control" id="uraian" name="uraian" onchange="save_data(this.value,'uraian',$('#id_barang').val(),'ws_barang','idBarang')">
                                   
                                  </select>
                               
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Merk </label>
                                
                                  <input type="text" name="merk" id="merk" class="form-control" value="<?= $k->merk ?>" onkeyup="save_data(this.value,'merk',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Tipe </label>
                                
                                  <input type="text" name="tipe" id="tipe" class="form-control" value="<?= $k->tipe ?>" onkeyup="save_data(this.value,'tipe',$('#id_barang').val(),'ws_barang','idBarang')">
                            
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Ukuran </label>
                                
                                  <input type="text" name="ukuran" id="ukuran" class="form-control" value="<?= $k->ukuran ?>" onkeyup="save_data(this.value,'ukuran',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Spesifikasi Lain </label>
                                
                                  <input type="text" name="spesifikasiLain" id="spesifikasiLain" class="form-control" value="<?= $k->spesifikasiLain ?>" onkeyup="save_data(this.value,'spesifikasiLain',$('#id_barang').val(),'ws_barang','idBarang')" >
                              
                             </div>
                          </div>
                       </div>
                       <div class="col-md-8">
                         <div class="col-md-6">
                        <div class="box box-primary">
                          <div class="box-header with-border">
                            <h3 class="box-title">Jumlah & Berat</h3>
                          </div>
                            <div class="form-group">
                                <label for="nomor" >Satuan </label>
                                <div class="row">
                                  <div class="col-md-6">
                                    <input type="jumlah" class="form-control" name="jumlahSatuan" id="jumlahSatuan" value="<?= $k->jumlahSatuan ?>" onkeyup="save_data(this.value,'jumlahSatuan',$('#id_barang').val(),'ws_barang','idBarang')" >
                                  </div>
                                  <div class="col-md-6">
                                    <select style="width: 100%" class="form-control form-kategori-barang" onchange="save_data(this.value,'kodeSatuanBarang',$('#id_barang').val(),'ws_barang','idBarang')">
                                      <option value="">-Kode Satuan-</option>
                                      <?php
                                      foreach ($db->query("select kode_satuan,satuan from ref_satuan group by kode_satuan ") as $kk) {
                                        if ($kk->kode_satuan==$k->kodeSatuanBarang) {
                                          echo "<option value='$kk->kode_satuan' selected>$kk->kode_satuan $kk->satuan</option>";
                                        }else{
                                          echo "<option value='$kk->kode_satuan'>$kk->kode_satuan $kk->satuan</option>";
                                        }
                                        
                                      } 
                                      ?>
                                      </select>
                                  </div>
                                </div>
                            </div> 
                            <div class="form-group">
                                <label for="nomor" >Kemasan </label>
                                <div class="row">
                                  <div class="col-md-6">
                                    <input type="jumlah" class="form-control" name="jumlahKemasan" id="jumlahKemasan"  value="<?= $k->jumlahKemasan ?>" onkeyup="save_data(this.value,'jumlahKemasan',$('#id_barang').val(),'ws_barang','idBarang')">
                                  </div>
                                  <div class="col-md-6">
                                    <select style="width: 100%" id="kodeJenisKemasan" class="form-control form-kategori-barang" onchange="save_data(this.value,'kodeJenisKemasan',$('#id_barang').val(),'ws_barang','idBarang')">
                                      <option value="">-Kode Kemasan-</option>
                                      <?php
                                      foreach ($db->query("select id_kemasan,kemasan from ref_jenis_kemasan  ") as $km) {
                                        if ($km->id_kemasan==$k->kodeJenisKemasan) {
                                          echo "<option value='$km->id_kemasan' selected>$km->id_kemasan $km->kemasan</option>";
                                        }else{
                                           echo "<option value='$km->id_kemasan'>$km->id_kemasan $km->kemasan</option>";
                                        }
                                       
                                      }
                                      ?>
                                      </select>
                                  </div>
                                </div>
                            </div> 
                           

                           

                              <div class="form-group">
                                <label for="nomor" >Volume (M3) </label>
                                
                                  <input type="text" name="volume" value="<?= $k->volume ?>" id="netto" class="form-control" onkeyup="save_data(this.value,'volume',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>

                            <div class="form-group">
                                <label for="nomor" >Berat Bersih (Kg) </label>
                                
                                  <input type="text" name="netto" value="<?= $k->netto ?>" id="netto" class="form-control" onkeyup="save_data(this.value,'netto',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>

                          </div>
                        </div>
                        <div class="col-md-6">
                        
                          <div class="box box-primary">
                          <div class="box-header with-border">
                            <h3 class="box-title">Harga</h3>
                          </div>
                            
                             
                            

                             <div class="form-group">
                                <label for="nomor" >Harga Penyerahan/Harga Jual </label>
                                
                                  <input type="number" name="hargaPenyerahan" id="hargaPenyerahan" class="form-control" onkeyup="save_data(this.value,'hargaPenyerahan',$('#id_barang').val(),'ws_barang','idBarang')" value="<?= $k->hargaPenyerahan ?>"> 
                          
                             </div>

                            

                             <div class="form-group">
                                <label for="nomor" >Nilai Penggantian/Nilai Jasa </label>
                                
                                  <input type="number" name="nilaiJasa" id="nilaiJasa" class="form-control" onkeyup="save_data(this.value,'nilaiJasa',$('#id_barang').val(),'ws_barang','idBarang')" value="<?= $k->nilaiJasa ?>"> 
                          
                             </div>
                            
                              <div class="form-group">
                                <label for="nomor" >Diskon </label>
                                
                                  <input type="number" name="diskon" id="diskon" class="form-control" onkeyup="save_data(this.value,'diskon',$('#id_barang').val(),'ws_barang','idBarang')" value="<?= $k->diskon ?>"> 
                          
                             </div>

                           
                             
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-12">
                              <div class="box box-primary">
                                <div class="box-header with-border">
                                  <h3 class="box-title">Pungutan</h3>
                                </div>
                                <div class="row">
                                   <div class="col-md-5">
                                     <h5>PPN</h5>
                                    
                                   </div>
                                   <div class="col-md-7">
                                     <div class="row">
                                        <div class="col-md-4">
                                           <input type="number" name="tarif" id="tarif" class="form-control" onkeyup="save_data(this.value,'tarif',<?= $data_barang_tarif->id_tarif_barang ?>,'ws_barang_tarif','id_tarif_barang')" value="<?= $data_barang_tarif->tarif ?>"> 
                                        </div>
                                        <div class="col-md-4">
                                           <select class="form-control" id="kodeFasilitasTarif" name="kodeFasilitasTarif">
                                             <?php
                                             foreach ($db->query("select * from ref_fasilitas_tarif where id_fasilitas_tarif in ('3','5','6','7')") as $tr) {
                                              echo "<option value='$tr->id_fasilitas_tarif'>$tr->id_fasilitas_tarif - $tr->nama_fasilitas_tarif</option>";
                                             }
                                             ?>
                                           </select>
                                        </div>
                                        <div class="col-md-4">
                                           <input type="number" name="nilaiFasilitas" id="nilaiFasilitas" class="form-control" onkeyup="save_data(this.value,'nilaiFasilitas',<?= $data_barang_tarif->id_tarif_barang ?>,'ws_barang_tarif','id_tarif_barang')" value="<?= $data_barang_tarif->nilaiFasilitas ?>"> 
                                        </div>
                                     </div>
                                   </div>
                                </div>
                              </div>
                          </div>
                        </div>
                       </div>
                       
                        
                      </div>     

        </form>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script> -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

   <script type="text/javascript">

    function hapus_detail_dokumen(id){
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
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_detail_dokumen",
           type : "POST",
         //  dataType : "JSON",
           data : {
             id : id
           },
          // dataTye : 'JSON',
           success : function(data){
              Swal.fire(
                'Deleted!',
                'Your data has been deleted.',
                'success'
              )
              $.ajax({
             url  : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_dokumen2",
             type : "POST",
             data : { 
               id_header : "<?= $k->id_header ?>",
               id_barang : $('#id_barang').val()
             },
             success : function(data){
               $("#detail_dokumen_barang2").html(data); 
             }
        });
             
           // $("#modal_lampiran").modal('hide');
           }
        });

       
      }
    });
  }

     function set_dokumen(id_barang,id_header,seri_dokumen) {
      var ket = '';
      if ($('#detail_' + id_barang+"_"+id_header+"_"+seri_dokumen).is(":checked")) {
          $.ajax({
             url  : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=set_dokumen",
             type : "POST",
             data : { 
               seri_dokumen : seri_dokumen,
               id_header    : id_header,
               id_barang    : id_barang
             },
             success : function(data){
              
             }
        });
      }
       
    }

     $("#modal_dokuman_fasilitas").on('hide.bs.modal', function(){
        $.ajax({
             url  : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_dokumen2",
             type : "POST",
             data : { 
               id_header : "<?= $k->id_header ?>",
               id_barang : $('#id_barang').val()
             },
             success : function(data){
               $("#detail_dokumen_barang2").html(data); 
             }
        }); 
      });

     function show_modal_fasilitas() {
       $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_dokumen",
       type : "POST",
       //dataType : "JSON",
       data : { 
         id : "<?= $k->id_header ?>",
         id_barang : $('#id_barang').val()
       },
      // dataTye : 'JSON',

       success : function(data){
         $("#detail_dokumen_barang").html(data);
         $("#modal_dokuman_fasilitas").modal('show');
       }
      });
      
     }
     $(document).ready(function() {
          $(".form-kategori-barang").select2();
          $(".form-negara-barang").select2();
          $('.form-uraian').select2({ 
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_barang',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term
                        };
                    },
                    results: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.completeName,
                                    slug: item.slug,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });
    });
     var data_uraian = {
        id: "<?= $uraian ?>",
        text: "<?= $uraian ?>",
    };  
    var newOptionUraian = new Option(data_uraian.text, data_uraian.id, false, false);

    $('.form-uraian').append(newOptionUraian).trigger('change');
   </script>
   
   <?php
   }