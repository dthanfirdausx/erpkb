<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../../inc/config.php";
session_check_json();
$idBarang = $_POST['id'];
  // echo "select b.*,s.satuan from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang where b.idBarang='$idBarang'";
  foreach ($db->query("select b.*,s.satuan,h.kodeDokumen,h.id_header from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang left join ws_header h on h.id_header=b.id_header where b.idBarang='$idBarang' ") as $k) {
    $uraian = $k->uraian;
 
  ?>
  <div class="col-md-12" id="detail_barang1"> 
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
                                <label for="nomor" >Hs Code </label>
                                  <input type="text" name="hsCode" id="hsCode" class="form-control" value="<?= $k->hsCode ?>" onkeyup="save_data(this.value,'hsCode',$('#id_barang').val(),'ws_barang','idBarang')">
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Uraian </label>
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
                        <div class="col-md-4">
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Keterangan Lainnya</h3>
                    </div>
                            <div class="form-group">
                                <label for="nomor" >Negara Asal </label>
                                
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
                                    <option value="">-Pilih Asal Barang-</option>
                                  <?php
                                  foreach ($db->query("select * from ref_asal_barang ") as $kn) {
                                    if ($kn->id_asal_barang==$k->kodeDaerahAsal) {
                                      echo "<option value='$kn->id_asal_barang' selected>$kn->id_asal_barang $kn->asal_barang</option>";
                                    }else{
                                       echo "<option value='$kn->id_asal_barang'>$kn->id_asal_barang $kn->asal_barang</option>";
                                    }
                                    
                                  }
                                  ?>
                                  </select>
                                
                            </div> 
                          </div>
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Harga</h3>
                    </div>
                           
                             <div class="form-group">
                                <label for="nomor" >Nilai CIF </label>
                                
                                  <input type="text" name="cif" id="cif" class="form-control" >
                            
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Nilai Pabean </label>
                                
                                  <input type="text" name="hargaPenyerahan" id="hargaPenyerahan" class="form-control" >
                           
                             </div>
                             
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Jumlah & Berat</h3>
                    </div>
                            <div class="form-group">
                                <label for="nomor" >Satuan </label>
                                <div class="row">
                                  <div class="col-md-6">
                                    <input type="text" class="form-control" name="jumlahSatuan" id="jumlahSatuan" value="<?= $k->jumlahSatuan ?>" onkeyup="save_data(this.value,'jumlahSatuan',$('#id_barang').val(),'ws_barang','idBarang')" >
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
                                <label for="nomor" >Berat Bersih (Kg) </label>
                                
                                  <input type="text" name="netto" value="<?= $k->netto ?>" id="netto" class="form-control" onkeyup="save_data(this.value,'netto',$('#id_barang').val(),'ws_barang','idBarang')">
                                
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
            </div>   
            <div class="row" id="detail_bahan_baku_form">
              <div class="col-md-6">
                  <div class="box box-primary">
                    <div class="box-header with-border">
                         <h3 class="text-center" style="font-size: 18px">Bahan Baku Impor</h3>
                        <div class="dropdown">
                          <button class="btn btn-primary dropdown-toggle"  type="button" data-toggle="dropdown">Aksi
                          <span class="caret"></span></button>
                          <ul class="dropdown-menu">
                            <li><a href="#" onclick="show_detail_modal_barang('Bahan Baku Impor','0')"><i class="fa fa-plus"></i> Tambah</a></li>
                            <li><a href="#"><i class="fa fa-upload"></i> Ambil Barang Asal</a></li>
                          </ul>
                        </div> 
                    </div>
                    
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Seri</th>
                          <th>HS</th>
                          <th>Uraian</th>
                          <th>Nilai Barang</th>
                          <th>Satuan</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php
                      // echo "select id_detail_bahan_baku, seriBarang, hsCode,uraianBarang,nilaiJasa,jumlahSatuan,kodeSatuanBarang from kodeAsalBahanBaku where idBarang='$idBarang' and kodeAsalBahanBaku='0' ";
                      foreach ($db->query("select hargaPenyerahan, id_detail_bahan_baku, seriBarang, kodeAsalBahanBaku, hsCode,uraianBarang,nilaiJasa,jumlahSatuan,kodeSatuanBarang from ws_barang_bahanbaku where idBarang='$idBarang' and kodeAsalBahanBaku='0' ") as $kb) {
                       echo "<tr> 
                               <td>$kb->seriBarang</td>
                               <td>$kb->hsCode</td>
                               <td>$kb->uraianBarang</td>
                               <td>$kb->hargaPenyerahan</td>
                               <td>$kb->kodeSatuanBarang</td>
                                <td>
                                 <a class='btn btn-primary' onclick='edit_data_bahan_baku($kb->id_detail_bahan_baku)'><i class='fa fa-pencil'></i></a>
                                 <a class='btn btn-danger' onclick='modal_delete_bahan_baku($kb->id_detail_bahan_baku)'><i class='fa fa-trash'></i></a>
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
                        <h3 class="text-center" style="font-size: 18px">Bahan Baku Lokal</h3>
                         <div class="dropdown">
                          <button class="btn btn-primary dropdown-toggle"  type="button" data-toggle="dropdown">Aksi
                          <span class="caret"></span></button>
                          <ul class="dropdown-menu">
                            <li><a href="#" onclick="show_detail_modal_barang('Bahan Baku Lokal','1')"><i class="fa fa-plus"></i> Tambah</a></li>
                            <li><a href="#"><i class="fa fa-upload"></i> Ambil Barang Asal</a></li>
                          </ul>
                        </div>  
                    </div>
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Seri</th>
                          <th>HS</th>
                          <th>Uraian</th>
                          <th>Nilai Barang</th>
                          <th>Satuan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                      // echo "select id_detail_bahan_baku, seriBarang, hsCode,uraianBarang,nilaiJasa,jumlahSatuan,kodeSatuanBarang from kodeAsalBahanBaku where idBarang='$idBarang' and kodeAsalBahanBaku='0' ";
                      foreach ($db->query("select hargaPenyerahan, id_detail_bahan_baku, seriBarang, kodeAsalBahanBaku, hsCode,uraianBarang,nilaiJasa,jumlahSatuan,kodeSatuanBarang from ws_barang_bahanbaku where idBarang='$idBarang' and kodeAsalBahanBaku='1' ") as $kb) {
                       echo "<tr> 
                               <td>$kb->seriBarang</td>
                               <td>$kb->hsCode</td>
                               <td>$kb->uraianBarang</td>
                               <td>$kb->hargaPenyerahan</td>
                               <td>$kb->kodeSatuanBarang</td>
                                <td>
                                 <a class='btn btn-primary' onclick='edit_data_bahan_baku($kb->id_detail_bahan_baku)'><i class='fa fa-pencil'></i></a>
                                 <a class='btn btn-danger' onclick='modal_delete_bahan_baku($kb->id_detail_bahan_baku)'><i class='fa fa-trash'></i></a>
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

    function show_detail_modal_barang(judul,kodeAsalBahanBaku){
       $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/262/get_detail_bahan_baku.php",
           type : "POST", 
           //dataType : "JSON",
           data : {
             id : $("#id_barang").val(),
             ket : 'add',
             kodeAsalBahanBaku : kodeAsalBahanBaku
           },
           success : function(data){ 
             $(".modal-body-barang-detail").html(data);
             $(".modal-body-barang").hide();
             $(".modal-body-barang-detail").show();
             $(".modal_barang").modal('show'); 
             $(".btn_modal_1").hide();
             $(".btn_modal_2").show();
           }
        });
       
    }
 
    function edit_data_bahan_baku(id_detail_bahan_baku){
       $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/262/get_detail_bahan_baku.php",
           type : "POST", 
           //dataType : "JSON",
           data : {
             id : id_detail_bahan_baku,
             ket : 'edit'
            // kodeAsalBahanBaku : kodeAsalBahanBaku
           },
           success : function(data){ 
             $(".modal-body-barang-detail").html(data);
             $(".modal-body-barang").hide();
             $(".modal-body-barang-detail").show();
             $(".modal_barang").modal('show'); 
             $(".btn_modal_1").hide();
             $(".btn_modal_2").show();
           }
        });
       
    }

     function hide_modal2(){ 
        var id_detail_bahan_baku = $("#id_detail_bahan_baku").val();
        $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=simpan_detail_bahan_baku",
           type : "POST", 
           //dataType : "JSON",
           data : {
             id_detail_bahan_baku : id_detail_bahan_baku,
             idBarang : '<?= $idBarang ?>'
           },
           success : function(data){ 
             $("#detail_bahan_baku_form").html(data);
             $(".modal-body-barang").show();
             $(".modal-body-barang-detail").hide();
             $(".btn_modal_1").show();
             $(".btn_modal_2").hide();
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