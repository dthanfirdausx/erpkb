<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../../inc/config.php";
session_check_json();
$idBarang = $_POST['id'];
  // echo "select b.*,s.satuan from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang where b.idBarang='$idBarang'";
  foreach ($db->query("select b.*,s.satuan,h.kodeDokumen,h.id_header from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang left join ws_header h on h.id_header=b.id_header where b.idBarang='$idBarang' ") as $k) {
    $uraian = $k->uraian;
    $seriBarang = $_POST['seriBarang'];
    if ($_POST['ket']=='add') {
     $db->query("insert into ws_barang_tarif (kodeJenisTarif ,idBarang,seriBarang,kodeJenisPungutan,kodeFasilitasTarif) values ('1',$idBarang','$seriBarang','PPN','8'),('1','$idBarang','$seriBarang','PPH','8'),('1','$idBarang','$seriBarang','BM','8')"); 
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
                        <div class="col-md-4">
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title"><?=customs_h('other_information','Keterangan Lainnya');?></h3>
                    </div>
                            <div class="form-group">
                                <label for="nomor" >Kategori Barang </label>
                                
                                  <select style="width: 100%" class="form-control form-kategori-barang" onchange="save_data(this.value,'kodeKategoriBarang',$('#id_barang').val(),'ws_barang','idBarang')">
                                      <option value="">-Pilih Kategori-</option>
                                  <?php
                                  foreach ($db->query("select kategori_barang,nama_kategori from ref_kategori_barang where kode_dokumen='$k->kodeDokumen' ") as $kb) {
                                    if ($kb->kategori_barang==$k->kodeKategoriBarang) {
                                      echo "<option value='$kb->kategori_barang' selected>$kb->kategori_barang $kb->nama_kategori</option>";
                                    }else{
                                      echo "<option value='$kb->kategori_barang'>$kb->kategori_barang $kb->nama_kategori</option>";
                                    }
                                    
                                  }
                                  ?>
                                  </select>
                            
                            </div> 
                            <br><br>

                            <div class="form-group">
                                <label for="nomor" >Negara </label>
                                
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
                          </div>
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Harga</h3>
                    </div>
                     <div class="form-group">
                                <label for="nomor" >Harga </label>
                                
                                  <input type="text" name="hargaPenyerahan" id="hargaPenyerahan" class="form-control" value="<?= $k->hargaPenyerahan ?>" onkeyup="save_data(this.value,'hargaPenyerahan',$('#id_barang').val(),'ws_barang','idBarang')" >
                         
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Biaya Tambauan </label>
                                
                                  <input type="text"  id="nilaiTambah" name="nilaiTambah" value="<?= $k->nilaiTambah ?>" onkeyup="save_data(this.value,'nilaiTambah',$('#id_barang').val(),'ws_barang','idBarang')" id="nilaiTambah" class="form-control" >
                                
                             </div>

                             <div class="form-group">
                                <label for="nomor" >FOB</label>
                                
                                  <input type="text" name="fob" id="fob" class="form-control" value="<?= $k->fob ?>" onkeyup="save_data(this.value,'fob',$('#id_barang').val(),'ws_barang','idBarang')" >
                                
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Harga Satuan </label>
                                
                                  <input type="text" name="hargaSatuan" id="hargaSatuan" class="form-control" value="<?= $k->hargaSatuan ?>" onkeyup="save_data(this.value,'hargaSatuan',$('#id_barang').val(),'ws_barang','idBarang')" >
                          
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Freight </label>
                                
                                  <input type="text" name="freight" id="freight" class="form-control" value="<?= $k->freight ?>" onkeyup="save_data(this.value,'freight',$('#id_barang').val(),'ws_barang','idBarang')">
                             
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Asuransi </label>
                                
                                  <input type="text" name="asuransi" id="asuransi" class="form-control" value="<?= $k->asuransi ?>" onkeyup="save_data(this.value,'asuransi',$('#id_barang').val(),'ws_barang','idBarang')" >
                               
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Nilai CIF </label>
                                
                                  <input type="text" name="cif" id="cif" class="form-control"  value="<?= $k->cif ?>" onkeyup="save_data(this.value,'cif',$('#id_barang').val(),'ws_barang','idBarang')">
                            
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Nilai Pabean </label>
                                
                                  <input type="text" name="hargaPerolehan" id="hargaPerolehan" class="form-control" value="<?= $k->hargaPerolehan ?>" onkeyup="save_data(this.value,'hargaPerolehan',$('#id_barang').val(),'ws_barang','idBarang')" >
                           
                             </div>
                             
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title"><?=customs_h('quantity_weight','Jumlah & Berat');?></h3>
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
                      <h3 class="box-title"><?=customs_h('facility_lartas_document','Dokumen Fasilitas/Lartas');?></h3>
                      <a class="btn btn-primary" onclick="show_modal_fasilitas()" style="float: right;cursor: pointer;"><i class="fa fa-plus"></i>Tambah</a>
                    </div>
                    <table class="table">
                      <thead>
                        <tr>
                         
                          <th>Seri</th>
                          <th>Jenis</th>
                          <th><?=customs_h('number','Nomor');?></th>
                          <th><?=customs_h('date','Tanggal');?></th>
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