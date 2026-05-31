<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../../inc/config.php";
session_check_json();
function get_seri_barang($idBarang,$kodeAsalBahanBaku){
  global $db;
  $q = $db->query("select count(id_detail_bahan_baku) as jml from ws_barang_bahanbaku where idBarang='$idBarang' and kodeAsalBahanBaku='$kodeAsalBahanBaku' "); 
  foreach ($q as $k) {
    return $k->jml+1;
  }
}

   if ($_POST['ket']=='add') {
     $idBarang = $_POST['id'];
     $seriBarang = get_seri_barang($idBarang,$_POST['kodeAsalBahanBaku']);
     $db->query("delete from ws_barang_bahanbaku where simpan='00' "); 
     $data_barang = array('idBarang' => $idBarang , 'simpan' => '00','kodeAsalBahanBaku' => $_POST['kodeAsalBahanBaku'],'seriBarang' => $seriBarang ); 
     $db->query("insert into ws_barang_bahanbaku (idBarang,simpan,kodeAsalBahanBaku,seriBarang) values ('$idBarang','00','".$_POST['kodeAsalBahanBaku']."','$seriBarang')");
   //  $db->insert("ws_barang_bahanbaku",$data_barang);
     $id_bahan_baku = $db->last_insert_id();
     if ($_POST['kodeAsalBahanBaku']=='1') {
         $db->query("insert into ws_bahan_baku_tarif (id_detail_bahan_baku,seriBahanBaku,kodeAsalBahanBaku,kodeJenisPungutan,kodeFasilitasTarif) values ('$id_bahan_baku','$seriBarang','".$_POST['kodeAsalBahanBaku']."','PPN','8')");
     }else{
        $db->query("insert into ws_bahan_baku_tarif (id_detail_bahan_baku,seriBahanBaku,kodeAsalBahanBaku,kodeJenisPungutan,kodeFasilitasTarif) values ('$id_bahan_baku','$seriBarang','".$_POST['kodeAsalBahanBaku']."','PPN','8'),('$id_bahan_baku','$seriBarang','".$_POST['kodeAsalBahanBaku']."','PPNBM','1'),('$id_bahan_baku','$seriBarang','".$_POST['kodeAsalBahanBaku']."','PPH','8'),('$id_bahan_baku','$seriBarang','".$_POST['kodeAsalBahanBaku']."','BM','8')"); 
     } 
   
    // $id_tarif_bahan_baku = $db->last_insert_id();
   }else{
     $id_bahan_baku = $_POST['id'];
     $qt = $db->query("select * from ws_bahan_baku_tarif where id_detail_bahan_baku='$id_bahan_baku' ");
     // foreach ($qt as $kt) {
     //   $id_tarif_bahan_baku = $kt->id_bahan_baku;
     // }
   }

   

  foreach ($db->query("select * from ws_barang_bahanbaku where id_detail_bahan_baku='$id_bahan_baku' ") as $k) {
    echo "$k->kodeAsalBahanBaku";
   
 
  ?>
  <div class="col-md-12" id="detail_barang2"> 
    <form role="form">

         <input type="hidden" id="id_detail_bahan_baku" value="<?= $id_bahan_baku ?>">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="box box-primary">
                            <div class="box-header with-border">
                              <h3 class="box-title">Dokumen Asal</h3> 
                            </div>
                            <div class="form-group">
                                <label for="nomor" >Kode Kantor </label>
                                <select style="width: 100%"  class="form-control form-kodeKantor" onchange="save_data(this.value,'kodeKantor',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                  <option value="">-Pilih Kantor-</option>
                                <?php
                                 foreach ($db->query("select * from ref_kantor") as $kn) {
                                  if ($k->kodeKantor==$kn->id_kantor) {
                                     echo "<option value='$kn->id_kantor' selected>$kn->id_kantor - $kn->nama_kantor</option>";
                                  }else{
                                     echo "<option value='$kn->id_kantor'>$kn->id_kantor - $kn->nama_kantor</option>";
                                  }
                                 } 
                                ?>
                                </select>
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Dokumen Asal </label>
                                  <select style="width: 100%" class="form-control form-kodeDokAsal" id="kodeDokAsal" name="kodeDokAsal" onchange="save_data(this.value,'kodeDokAsal',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                    <option value="">-Pilih Dokumen Asal-</option>
                                    <?php
                                     // $jenis_dok = array('23' => 'BC 2.3' , '27' => 'BC 2.7','522' => 'FTZ-02 PENGELUARAN ANTAR FREE TRADE ZONE DAN KAWASAN BERIKAT');

     
                                 if ($k->kodeAsalBahanBaku=='0') {
                                        $qdokbap = "select id_dokumen,nama_dokumen,nama_pendek from ref_dokumen where id_dokumen in('23','27','522','999')";
                                    }else{
                                      $qdokbap = "select id_dokumen,nama_dokumen,nama_pendek from ref_dokumen where id_dokumen in('27','40','999')";
                                    }  
                                
                                     foreach ($db->query($qdokbap) as $kn) {
                                      if ($k->kodeKantor==$kn->id_kantpr) {
                                         echo "<option value='$kn->id_dokumen' selected>$kn->id_dokumen - $kn->nama_pendek</option>";
                                      }else{
                                         echo "<option value='$kn->id_dokumen'>$kn->id_dokumen - $kn->nama_pendek</option>";
                                      }
                                     }
                                    ?>
                                  </select>                            
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Nomor Daftar </label>
                                  <input type="text" name="nomorDaftarDokAsal" id="nomorDaftarDokAsal" class="form-control" value="<?= $k->nomorDaftarDokAsal ?>" onkeyup="save_data(this.value,'nomorDaftarDokAsal',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Tanggal Daftar </label>
                                  <input type="text" name="tanggalDaftarDokAsal" id="tanggalDaftarDokAsal" class="form-control tanggalDaftarDokAsal" value="<?= $k->tanggalDaftarDokAsal ?>" onchange="save_data(this.value,'tanggalDaftarDokAsal',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" autocomplete="off">
                             </div>
                          
                             <div class="form-group">
                                <label for="nomor" >Nomor Pengajuan </label>
                                  <input type="text" name="nomorAjuAsal" id="nomorAjuAsal" class="form-control" value="<?= $k->nomorAjuAsal ?>" onkeyup="save_data(this.value,'nomorAjuAsal',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                             </div>
                         
                             <div class="form-group">
                                <label for="nomor" >Seri Barang Asal </label>
                                  <input type="text" name="seriBarangDokAsal" id="seriBarangDokAsal" class="form-control" value="<?= $k->seriBarangDokAsal ?>" onkeyup="save_data(this.value,'seriBarangDokAsal',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')"> 
                             </div>
                             
                          </div>
                    </div>
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
                                <label for="nomor" >Hs </label>
                                  <input type="text" style="width: 100%" class="form-control" id="hsCode" value="<?= $k->hsCode ?>" name="hsCode" onkeyup="save_data(this.value,'hsCode',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                                           
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Kode </label>
                                  <input type="text" style="width: 100%" class="orm-uraian form-control" value="<?= $k->kodeBarang ?>" id="kodeBarang" name="kodeBarang" onkeyup="save_data(this.value,'kodeBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                                           
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Uraian </label>
                                  <input type="text" style="width: 100%" class="form-uraian form-control" id="uraianBarang" name="uraian" onkeyup="save_data(this.value,'uraianBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                                       
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Merk </label>
                                  <input type="text" name="merkBarang" id="merkBarang" class="form-control" value="<?= $k->merkBarang ?>" onkeyup="save_data(this.value,'merkBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Tipe </label>
                                  <input type="text" name="tipeBarang" id="tipeBarang" class="form-control" value="<?= $k->tipeBarang ?>" onkeyup="save_data(this.value,'tipeBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Ukuran </label>
                                
                                  <input type="text" name="ukuranBarang" id="ukuranBarang" class="form-control" value="<?= $k->ukuranBarang ?>" onkeyup="save_data(this.value,'ukuranBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                                
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Spesifikasi Lain </label>
                                
                                  <input type="text" name="spesifikasiLainBarang" id="spesifikasiLainBarang" class="form-control" value="<?= $k->spesifikasiLainBarang ?>" onkeyup="save_data(this.value,'spesifikasiLainBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" >
                              
                             </div>
                          </div>
                    </div>
                    <div class="col-md-4">
                          
                    <div class="box box-primary">
                      <div class="box box-primary">
                            <div class="box-header with-border">
                              <h3 class="box-title">Jumlah & Berat</h3>
                            </div>
                            <div class="form-group">
                                <label for="nomor" >Satuan </label>
                                <div class="row">
                                  <div class="col-md-6">
                                    <input type="text" class="form-control" name="jumlahSatuan" id="jumlahSatuan" value="<?= $k->jumlahSatuan ?>" onkeyup="save_data(this.value,'jumlahSatuan',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" >
                                  </div> 
                                  <div class="col-md-6">
                                    <select style="width: 100%" class="form-control form-kategori-barang" onchange="save_data(this.value,'kodeSatuanBarang',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
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
                           

                           
                          </div>
                          <div class="box-header with-border">
                            <h3 class="box-title">Harga</h3>
                          </div>
                           
                             <div class="form-group">
                                <label for="nomor" >Nilai CIF </label>
                                
                                  <input type="text" name="cif" id="cif" class="form-control" value="<?= $k->cif ?>"   onkeyup="save_data(this.value,'cif',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')"  >
                            
                             </div>

                             <div class="form-group"> 
                                <label for="nomor" >Cif Rupiah</label>
                                  <input type="text" name="cifRupiah" id="cifRupiah" class="form-control"  value="<?= $k->cifRupiah ?>"  onkeyup="save_data(this.value,'cifRupiah',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" >
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Harga Penyerahan/Harga Jual </label>
                                  <input type="text" value="<?= $k->hargaPenyerahan ?>" name="hargaPenyerahan" id="hargaPenyerahan" class="form-control" onkeyup="save_data(this.value,'hargaPenyerahan',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')">
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Harga Perolehan </label>
                                  <input type="text" value="<?= $k->hargaPerolehan ?>" name="hargaPerolehan" id="hargaPerolehan" class="form-control" onkeyup="save_data(this.value,'hargaPerolehan',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" >
                             </div>
                             <div class="form-group">  
                                <label for="nomor" >Nilai Penggantian/Nilai Jasa </label>
                                  <input type="text" value="<?= $k->nilaiJasa ?>" name="nilaiJasa" id="nilaiJasa" class="form-control" onkeyup="save_data(this.value,'nilaiJasa',$('#id_detail_bahan_baku').val(),'ws_barang_bahanbaku','id_detail_bahan_baku')" >
                             </div>
                             
                          </div>
                             
                           
                        </div>
                        <div class="col-md-4">
                          
                         
               </div>
            </div> 
            <div class="row">
              <div class="col-md-12">
                <h3>Pungutan</h3>
              </div>
              <div class="col-md-6">  
                 <div class="box box-primary">
                           
                                  <div class="box-header with-border">
                                    <h3 class="box-title">BM</h3>
                                  </div>
                                  <div class="row">
                                    <div class="col-md-12">

                                    <?php
                                   foreach ($db->query("select * from ws_bahan_baku_tarif where id_detail_bahan_baku='$id_bahan_baku' and kodeJenisPungutan='BM' ") as $tr) {
                                ?>
                                  <div class="form-group" style="display: -webkit-inline-box;">  
                                     <select class="form-control" style="width:150px" name="kodeFasilitasTarif" id="kodeFasilitasTarif">
                                    <?php
                                    foreach ($db->query("select * from ref_jenis_pungutan where id_jenis_pungutan in ('BM','BMKITE')") as $kt) {
                                       if ($kt->id_jenis_pungutan==$tr->kodeJenisPungutan) {
                                         echo "<option value='$kt->id_jenis_pungutan' selected>$kt->id_jenis_pungutan</option>";
                                       }else{
                                        echo "<option value='$kt->id_jenis_pungutan'>$kt->id_jenis_pungutan</option>";
                                       }
                                    }
                                    ?>
                                    </select>
                                    <select class="form-control" style="width:150px" name="kodeFasilitasTarif" id="kodeFasilitasTarif">
                                       <option value="1">Advalorum</option>
                                       <option value="2">Spesifik</option>
                                    </select>
                                    <input type="text" name="tarif" placeholder="%" id="tarif" class="form-control" style="width: 100px" >
                                     <select class="form-control" style="width:150px" name="kodeFasilitasTarif" id="kodeFasilitasTarif">
                                    <?php
                                    foreach ($db->query("select * from ref_fasilitas_tarif where id_fasilitas_tarif in ('1','5','8')") as $kt) {
                                       if ($kt->id_fasilitas_tarif==$tr->kodeFasilitasTarif) {
                                         echo "<option value='$kt->id_fasilitas_tarif' selected>$kt->id_fasilitas_tarif - $kt->nama_fasilitas_tarif</option>";
                                       }else{
                                        echo "<option value='$kt->id_fasilitas_tarif'>$kt->id_fasilitas_tarif - $kt->nama_fasilitas_tarif</option>";
                                       }
                                    }
                                    ?>
                                    </select>
                                    <input type="text" name="tarifFasilitas" id="tarifFasilitas" class="form-control" placeholder="100%" style="width:100px" >
                                 </div>
                                 <?php
                                 }
                                  ?>
                                  </div>
                                </div> 
                    </div> 
                </div>
                <div class="col-md-6">
                  <div class="box box-primary">
                           
                                  <div class="box-header with-border">
                                    <h3 class="box-title">PDRI</h3>
                                  </div>
                                  <div class="row">
                                    <div class="col-md-12">

                                    <?php
                                   foreach ($db->query("select * from ws_bahan_baku_tarif where id_detail_bahan_baku='$id_bahan_baku' and kodeJenisPungutan!='BM' ") as $tr) {
                                ?>
                                  <div class="form-group" style="display: -webkit-inline-box;">  
                                    <label for="nomor" style="width: 100px" ><?= $tr->kodeJenisPungutan ?></label>
                                    <input type="text" name="tarif" placeholder="%" id="tarif" class="form-control" style="width: 100px" >
                                     <select class="form-control" style="width:150px" name="kodeFasilitasTarif" id="kodeFasilitasTarif">
                                    <?php
                                    foreach ($db->query("select * from ref_fasilitas_tarif where id_fasilitas_tarif in ('1','5','8')") as $kt) {
                                       if ($kt->id_fasilitas_tarif==$tr->kodeFasilitasTarif) {
                                         echo "<option value='$kt->id_fasilitas_tarif' selected>$kt->id_fasilitas_tarif - $kt->nama_fasilitas_tarif</option>";
                                       }else{
                                        echo "<option value='$kt->id_fasilitas_tarif'>$kt->id_fasilitas_tarif - $kt->nama_fasilitas_tarif</option>";
                                       }
                                    }
                                    ?>
                                    </select>
                                    <input type="text" name="tarifFasilitas" id="tarifFasilitas" class="form-control" placeholder="100%" style="width:100px" >
                                 </div>
                                 <?php
                                 }
                                  ?>
                                  </div>
                                </div> 
                    </div>
               
              </div>
            </div>  
             

        </form>
   </div>
  
   <script type="text/javascript">
      $('.form-kodeKantor').select2();
      $('.form-kodeDokAsal').select2();
        $(document).ready(function() {
          $(".tanggalDaftarDokAsal").datepicker({ 
            format: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true
          });
        });
   </script>
   
   <?php
     
   }