<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../../inc/config.php";
session_check_json();
$idBarang = $_POST['id'];
  foreach ($db->query("select b.*,s.satuan,h.kodeDokumen,h.id_header from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang left join ws_header h on h.id_header=b.id_header where b.idBarang='$idBarang' group by b.idBarang ") as $k) {
    $uraian = $k->uraian;
 
  ?>
  <div class="col-md-12">  
    <form role="form">

         
            <div class="row">
              <div class="col-md-6">
                  <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Jenis</h3> 
                    </div>
                    <div class="form-group">
                                <label for="nomor" >Seri </label>
                                  
                                  <input type="text" name="seriBarang" value="<?= $k->seriBarang ?>" id="seriBarang" class="form-control" readonly="">
                                
                             </div>
                              <div class="form-group">
                                <label for="nomor" >HS Code </label>
                                
                                  <input type="text" name="hsCode" id="hsCode" class="form-control" value="<?= $k->hsCode ?>" onkeyup="save_data(this.value,'hsCode',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Kode </label>
                                
                                 <!--  <textarea class="form-control" name="uraian" id="uraian" value="<?= $k->uraian ?>" onkeyup="save_data(this.value,'uraian',$('#id_barang').val(),'ws_barang','idBarang')"></textarea> -->
                                  <select style="width: 100%" class="form-uraian form-control" id="kodeBarang" name="kodeBarang" onchange="save_data_barang(this.value,'kodeBarang',$('#id_barang').val(),'ws_barang','idBarang')">
                                   
                                  </select> 
                               
                             </div>
                              <div class="form-group">
                                <label for="nomor" >Uraian </label>
                                  <textarea name="uraian" id="uraian" class="form-control" value="<?= $k->kodeBarang ?>" onkeyup="save_data(this.value,'kodeBarang',$('#id_barang').val(),'ws_barang','idBarang')" onchange="save_data(this.value,'uraian',$('#id_barang').val(),'ws_barang','idBarang')"><?= $k->uraian ?></textarea>
                                  <!-- <input type="text" name="kodeBarang" id="kodeBarang" class="form-control" value="<?= $k->kodeBarang ?>" onkeyup="save_data(this.value,'kodeBarang',$('#id_barang').val(),'ws_barang','idBarang')">  -->
                                
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
                                <label for="nomor" >Negara Asal Barang </label>
                                
                                  <select style="width: 100%" class="form-control form-negara-barang" onchange="save_data(this.value,'kodeNegaraAsal',$('#id_barang').val(),'ws_barang','idBarang')">
                                    <option value="">-Pilih Negara-</option>
                                  <?php
                                  foreach ($db->query("select kode_negara,negara from ref_negara ") as $kn) {
                                    if ($kn->kode_negara==$k->kodeNegaraAsal) {
                                      echo "<option value='$kn->kode_negara' selected>$kn->kode_negara $kn->negara</option>";
                                    }else{
                                      echo "<option value='$kn->kode_negara'>$kn->kode_negara $kn->negara</option>";
                                    }
                                    
                                  }
                                  ?>
                                  </select>
                                
                            </div> 

                             <div class="form-group">
                                <label for="nomor" >Daerah Asal Barang </label>
                                
                                  <select style="width: 100%" class="form-control form-negara-barang" onchange="save_data(this.value,'kodeDaerahAsal',$('#id_barang').val(),'ws_barang','idBarang')">
                                    <option value="">-Pilih Daerah-</option>
                                  <?php
                                  foreach ($db->query("select id_asal_daerah,asal_daerah from ref_asal_daerah ") as $kn) {
                                    if ($kn->id_asal_daerah==$k->kodeDaerahAsal) {
                                      echo "<option value='$kn->kodeDaerahAsal' selected>$kn->asal_daerah</option>";
                                    }else{
                                      echo "<option value='$kn->kodeDaerahAsal'>$kn->asal_daerah</option>";
                                    }
                                    
                                  }
                                  ?>
                                  </select>
                                
                            </div> 
                           
                          </div>
                    </div>
                    <div class="col-md-6">
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Jumlah & Berat</h3>
                    </div>
                            <div class="form-group">
                                <label for="nomor" >Satuan </label>
                                <div class="row">
                                  <div class="col-md-6">
                                    <input type="text" class="form-control" name="jumlahSatuan" id="jumlahSatuan" value="<?= $k->jumlahSatuan ?>" onkeyup="save_data_hitung(this.value,'jumlahSatuan',$('#id_barang').val(),'ws_barang','idBarang')" >
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
                                    <input type="text" class="form-control" name="jumlahKemasan" id="jumlahKemasan"  value="<?= $k->jumlahKemasan ?>" onkeyup="save_data(this.value,'jumlahKemasan',$('#id_barang').val(),'ws_barang','idBarang')">
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
                                <label for="nomor" >FOB</label><input value="<?= $k->fob ?>"  type="text" name="fob" id="fob" class="form-control" onkeyup="save_data_hitung(this.value,'fob',$('#id_barang').val(),'ws_barang','idBarang')" />
                                
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Volume </label><input value="<?= $k->volume ?>"  type="text" name="fob" id="fob" class="form-control" onkeyup="save_data(this.value,'volume',$('#id_barang').val(),'ws_barang','idBarang')" />
                                 
                             </div>

                            <div class="form-group">
                                <label for="nomor" >Berat Bersih (Kg) </label>
                                
                                  <input type="text" name="netto" value="<?= $k->netto ?>" id="netto" class="form-control" onkeyup="save_data(this.value,'netto',$('#id_barang').val(),'ws_barang','idBarang')">
                                
                             </div>

                              <div class="form-group">
                                <label for="nomor" >Harga Satuan</label>
                                
                                  <input type="text" name="hargaSatuan" value="<?= $k->hargaSatuan ?>" id="hargaSatuan" class="form-control" onkeyup="save_data(this.value,'hargaSatuan',$('#id_barang').val(),'ws_barang','idBarang')" onchange="save_data(this.value,'hargaSatuan',$('#id_barang').val(),'ws_barang','idBarang')" readonly>
                                
                             </div>
                          </div>
                  <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Dokumen Fasilitas/Lartas</h3>
                      <a class="btn btn-primary" onclick="show_modal_fasilitas()" style="float: right;cursor: pointer;"><i class="fa fa-plus"></i>Tambah</a>
                    </div>
                    <table class="table">
                      <thead>
                        <tr>
                         
                          <th>Seri</th>
                          <th>Jenis</th>
                          <th>Nomor</th>
                          <th>Tanggal</th>
                          <th></th>
                         <!--  <th>Fasilitas</th>
                          <th>Izin</th>
                          <th>File</th> -->
                        </tr> 
                      </thead>
                      <tbody id="detail_dokumen_barang2">
                      <?php
                      $q = $db->query("select wd.id as id_detail, d.*,r.nama_dokumen from ws_barang_dokumen wd left join ws_dokumen d on (d.seriDokumen=wd.seri_dokumen and wd.id_barang='$idBarang' and wd.id_header='$k->id_header')  left join ref_dokumen r on r.id_dokumen=d.kodeDokumen  "); 

                           foreach ($q as $kd) { 
                              echo "<tr>                                
                                      <td>$kd->seriDokumen</td>
                                      <td>$kd->nama_dokumen</td>
                                      <td>$kd->nomorDokumen</td>
                                      <td>$kd->tanggalDokumen</td>
                                      <td>
                                        <a onclick='hapus_detail_dokumen($kd->id_detail)' class='btn btn-danger'><i class='fa fa-trash'></i></a>
                                      </td>
                                    </tr>";
                           } 
                      ?>
                      </tbody>
                    </table>  
                  </div>
                  </div>
                  <div class="col-md-6">
                    
                 <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Entitas Barang</h3>
                      <a class="btn btn-primary" onclick="show_modal_entitas_barang()" style="float: right;cursor: pointer;"><i class="fa fa-plus"></i>Tambah</a>
                    </div>
                    <table class="table">
                      <thead>
                        <tr>
                         
                          <th>Seri</th>
                          <th>No Identitas</th>
                          <th>Nama</th>
                          <th>Alamat</th>
                          <th></th>
                         <!--  <th>Fasilitas</th>
                          <th>Izin</th>
                          <th>File</th> -->
                        </tr> 
                      </thead>
                      <tbody id="detail_entitas_barang">
                      <?php
                      $q = $db->query("select ws.id_entitas_barang, ws.seriEntitas,e.nomorIdentitas,e.namaEntitas,e.alamatEntitas from ws_entitas_barang ws left join ws_barang b on b.idBarang=ws.idBarang left join ws_entitas e on (e.id_header=ws.id_header and ws.seriEntitas=e.seriEntitas) where ws.idBarang='$idBarang' ");  

                           foreach ($q as $kd) { 
                              echo "<tr>                                
                                      <td>$kd->seriEntitas</td>
                                      <td>$kd->nomorIdentitas</td>
                                      <td>$kd->namaEntitas</td>
                                      <td>$kd->alamatEntitas</td>
                                      <td>
                                        <a onclick='hapus_detail_entitas($kd->id_entitas_barang)' class='btn btn-danger'><i class='fa fa-trash'></i></a>
                                      </td>
                                    </tr>";
                           } 
                      ?>
                      </tbody>
                    </table>  
                  </div>
                  </div>
                  
            </div>     

        </form>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script> -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

   <script type="text/javascript">

    $(function() {
      var extra = 0;
      var $input = $("#fob"); 

      $input.on("keyup", function(event) {

        // When user select text in the document, also abort.
        var selection = window.getSelection().toString();
        if (selection !== '') {
          return;
        }

        // When the arrow keys are pressed, abort.
        if ($.inArray(event.keyCode, [38, 40, 37, 39]) !== -1) {
          if (event.keyCode == 38) {
            extra = 1000;
          } else if (event.keyCode == 40) {
            extra = -1000;
          } else {
            return;
          }

        }

        var $this = $(this);
        // Get the value.
        var input = $this.val();
        var input = input.replace(/[\D\s\._\-]+/g, "");
        input = input ? parseInt(input, 10) : 0;
        input += extra;
        extra = 0;
        $this.val(function() {
          return (input === 0) ? "" : input.toLocaleString("en-US");
        });
      });
    }); 
  

  function hapus_detail_entitas(id){
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
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_detail_entitas",
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
             url  : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_entitas2",
             type : "POST",
             data : { 
               id_header : "<?= $k->id_header ?>",
               id_barang : $('#id_barang').val()
             },
             success : function(data){
               $("#detail_entitas_barang").html(data); 
             }
        });
             
           // $("#modal_lampiran").modal('hide');
           }
        });

       
      }
    });
  } 

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
                 $("#detail_dokumen_barang2").html(data);
             }
        });
      } 
       
    }
     function set_entitas_barang(id_barang,id_header,seri_dokumen) {
      var ket = '';
      if ($('#detail_entitas_' + id_barang+"_"+id_header+"_"+seri_dokumen).is(":checked")) {
          $.ajax({
             url  : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=set_entitas_barang",
             type : "POST",
             data : { 
               seri_dokumen : seri_dokumen,
               id_header    : id_header,
               id_barang    : id_barang
             },
             success : function(data){
                 $("#detail_entitas_barang").html(data);
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

     function show_modal_entitas_barang() {
       $.ajax({
         url    : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_detail_entitas",
         type   : "POST",
         data   : { 
              id        : "<?= $k->id_header ?>",
              id_barang : $('#id_barang').val()
         },
         success : function(data){
            $("#detail_entitas_barang_modal").html(data);
            $("#modal_dokuman_entitas").modal('show');
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