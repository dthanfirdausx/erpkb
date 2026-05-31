<?php
header("Access-Control-Allow-Origin: *");
session_start(); 
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) { 

  case 'simpan_detail_bahan_baku':
   $id_detail_bahan_baku = $_POST['id_detail_bahan_baku'];
   $idBarang = $_POST['idBarang'];
   $db->query("update ws_barang_bahanbaku set simpan='11' where id_detail_bahan_baku='$id_detail_bahan_baku' "); 
   ?>
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
   <?php
    break;

  case 'hapus_barang':
   $db->query("delete from ws_barang where idBarang=? ",array($_POST['id']));
  break;

  case 'reload_barang':
   $q = $db->query("select * from ws_barang  where id_header='".$_POST['id']."'  ");
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php  
          }else{  
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$k->seriBarang</td>
                       <td>$k->hsCode</td>
                       <td>$k->uraian</td>
                       <td>$k->hargaPenyerahan</td>
                       <td>$k->jumlahSatuan</td>
                       <td>$k->kodeSatuanBarang<td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_barang($k->idBarang)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_barang($k->idBarang)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
            }
          }
  break;

  case 'hapus_bank':
  // 59.045.000

  $db->query("delete from ws_bank_devisa where id_detail_bank='".$_POST['id']."' ");   
    $qh = $db->query("select wb.*,b.nama_bank from ws_bank_devisa wb  left join ref_bank b on b.id_bank=wb.kode_bank where wb.id_header='".$_POST['id_header']."' and simpan='1'"); 
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
    break;
  
  case 'hapus_pengangkut':
  $db->query("delete from ws_pengangkut where id_pengangkut='".$_POST['id']."' ");  
    $q = $db->query("select * from v_ws_angkut where id_header='".$_POST['id_header']."' and simpan='1' ");
   foreach ($q as $k) { 
      echo "<tr>
              <td>$k->seriPengangkut</td>
              <td>$k->namaPengangkut</td>
              <td>$k->nomorPengangkut</td>
              <td>$k->cara_angkut</td>
              <td>$k->kodeBendera</td>
              <td>
                             <button class='btn btn-primary' onclick='edit_pengangkut($k->id_pengangkut)'><i class='fa fa-pencil'></i></button>
                             <button class='btn btn-danger' onclick='modal_delete_pengangkut($k->id_pengangkut)'><i class='fa fa-trash'></i></button>
                           </td>
           </tr>"; 
   }  
    break;

   case 'edit_bank':
  $q = $db->query("select wb.*,b.nama_bank from ws_bank_devisa wb  left join ref_bank b on b.id_bank=wb.kode_bank where wb.id_detail_bank='".$_POST['id']."' limit 1 ");
  foreach ($q as $k) {
    ?> 
   <form id="input_dokumen_pabean" method="post" class="form-horizontal foto_banyak" action="#">

                          <input type="hidden" name="id_detail_bank" id="id_detail_bank" value="<?= $k->id_detail_bank ?>">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="seriBank" value="<?= $k->seriBank ?>" id="seriBank" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->
                           
                         
                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nama Bank</label>
                            <div class="col-lg-9">
                             <select class="form-bank form-control" name="kodeBank" id="kodeBank" style="width: 100%" onchange="save_data(this.value,'kode_bank',$('#id_detail_bank').val(),'ws_bank_devisa','id_detail_bank')" >
                               <?php 
                               $qb = $db->query("select id_bank,kode_bank,nama_bank from ref_bank");
                               foreach ($qb as $kb) {
                                if ($kb->kode_bank==$k->kode_bank) {
                                   echo "<option value='$kb->id_bank' selected>$kb->nama_bank</option>";
                                }else{
                                   echo "<option value='$kb->id_bank'>$kb->nama_bank</option>";
                                 
                               }
                             }
                               ?>
                               </select>
                                
                            </div>
                          </div> 
                        </form>
                         <script type="text/javascript">
                     $('.form-bank').select2();
                      </script>
  <?php
  }  
  
  break;

  case 'edit_pengangkut':
  $q = $db->query("select p.*,n.negara from ws_pengangkut p left join ref_negara n on n.kode_negara=p.kodeBendera  where p.id_pengangkut='".$_POST['id']."' ");
  foreach ($q as $k) {
  
  ?>
   <form id="input_dokumen_pabean" method="post" class="form-horizontal foto_banyak" action="#">

                          <input type="hidden" name="id_pengangkut" id="id_pengangkut" value="<?= $_POST['id'] ?>"> 
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="seriPengangkut" id="seriPengangkut" class="form-control" readonly="" value="<?= $k->seriPengangkut ?>">
                            </div>
                          </div><!-- /.form-group -->
                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nama Sarana Angkut </label>
                            <div class="col-lg-9">
                              <input type="text" name="namaPengangkut" onkeyup="save_data(this.value,'namaPengangkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')"  class="form-control" value="<?= $k->namaPengangkut ?>">
                            </div>
                          </div>
                          
                          

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Cara Pengangkutan </label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeCaraAngkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" style="width:100%" name="kodeCaraAngkut" id="kodeCaraAngkut" class="form-control form-ref-dokumen" > 
                                  <option value=''>-Pilih Cara Angkut-</option>
                                 <?php
                                 $qq = $db->query("select id_cara_angkut,cara_angkut from ref_cara_angkut");
                                 foreach ($qq as $kk) {
                                  if ($kk->id_cara_angkut==$k->kodeCaraAngkut) {
                                    echo "<option value='$kk->id_cara_angkut' selected>$kk->id_cara_angkut - $kk->cara_angkut</option>";
                                  }else{
                                    echo "<option value='$kk->id_cara_angkut'>$kk->id_cara_angkut - $kk->cara_angkut</option>";
                                  }
                                  
                                 }
                                 ?>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nomor Voy/Flight/No.Pol </label>
                            <div class="col-lg-9">
                              <input type="text" name="nomorPengangkut" onkeyup="save_data(this.value,'nomorPengangkut',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" id="nomorPengangkut" class="form-control" value="<?= $k->nomorPengangkut ?>">
                            </div>
                          </div>

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Bendera</label>
                            <div class="col-lg-9">
                             <select class="form-negara2 form-control" name="kodeBendera" id="kodeBendera" style="width: 100%" onchange="save_data(this.value,'kodeBendera',$('#id_pengangkut').val(),'ws_pengangkut','id_pengangkut')" >
                               </select>
                            </div>
                          </div> 
        </form>
        <script type="text/javascript">
          var data_negara = {
              id: "<?= $k->kodeBendera ?>",
              text: "<?= $k->kodeBendera." - ".$k->negara ?>"
          };  
          var newOptionNegaraPengangkut = new Option(data_negara.text, data_negara.id, false, false);
          $('.form-negara2').append(newOptionNegaraPengangkut).trigger('change'); 

           $('.form-negara2').select2({  
                minimumInputLength: 2,
                tags: [],
                ajax: {
                    url: '<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=get_negara',
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
        </script>
  <?php 
  }
  break;

   case 'simpan_bank':
   $id        = $_POST['id'];
   $id_header = $_POST['id_header'];
   $db->query("update ws_bank_devisa set simpan='1' where id_detail_bank='$id' ");
   $q = $db->query("select wb.*,b.nama_bank from ws_bank_devisa wb  left join ref_bank b on b.id_bank=wb.kode_bank where wb.id_header='$id_header' and simpan='1' ");
   foreach ($q as $k) {
      echo "<tr>
              <td>$k->seriBank</td>
              <td>$k->kode_bank</td>
              <td>$k->nama_bank</td>
              <td>
                             <button class='btn btn-primary' onclick='edit_pengangkut($k->id_detail_bank)'><i class='fa fa-pencil'></i></button>
                             <button class='btn btn-danger' onclick='modal_delete_pengangkut($k->id_detail_bank)'><i class='fa fa-trash'></i></button>
                           </td>
           </tr>"; 
   }  
 
  break;  

  case 'simpan_pengangkut':
   $id        = $_POST['id'];
   $id_header = $_POST['id_header'];
   $db->query("update ws_pengangkut set simpan='1' where id_pengangkut='$id' ");
   $q = $db->query("select * from v_ws_angkut where id_header='$id_header' and simpan='1' ");
   foreach ($q as $k) {
      echo "<tr>
              <td>$k->seriPengangkut</td>
              <td>$k->namaPengangkut</td>
              <td>$k->nomorPengangkut</td>
              <td>$k->cara_angkut</td>
              <td>$k->kodeBendera</td>
              <td>
                             <button class='btn btn-primary' onclick='edit_pengangkut($k->id_pengangkut)'><i class='fa fa-pencil'></i></button>
                             <button class='btn btn-danger' onclick='modal_delete_pengangkut($k->id_pengangkut)'><i class='fa fa-trash'></i></button>
                           </td>
           </tr>"; 
   }  

  break;  

  case 'get_id_bank_devisa':
  $db->query("delete from ws_bank_devisa where  simpan!='1' or simpan is null "); 
   $q = $db->query("select * from ws_bank_devisa where id_header='".$_POST['id']."' ");
  if ($q->rowCount()==0) {
    $seri = 1;
  }else{
    $seri = $q->rowCount() + 1;
  }
  
  $data = array('id_header' => $_POST['id'],'seriBank' => $seri,'date_created' => date("Y-m-d H:i:s") );  
  $db->insert("ws_bank_devisa",$data);
  $id = $db->last_insert_id();
  $res = array('id' => $id , 
               'seri' => $seri);  
  echo json_encode($res);

 // echo $id;
    break;

  case 'get_id_pengangkut':
  $db->query("delete from ws_pengangkut where  simpan!='1' or simpan is null");
   $q = $db->query("select * from ws_pengangkut where id_header='".$_POST['id']."' ");
  if ($q->rowCount()==0) {
    $seri = 1;
  }else{
    $seri = $q->rowCount() + 1;
  }
  
  $data = array('id_header' => $_POST['id'],'seriPengangkut' => $seri );  
  $db->insert("ws_pengangkut",$data);
  $id = $db->last_insert_id();
  $res = array('id' => $id , 
               'seri' => $seri);
  echo json_encode($res);

 // echo $id;
    break;


  case 'kirim_dokumen_40':  
   $info = get_info_kb(); 
  $q = $db->query("select d.nama_dokumen, h.diskon, h.kodeTujuanTpb,h.nilaiJasa,h.uangMuka, h.kodeKantorTujuan, `h`.`freight` AS `freight`,`p`.`nama_pelabuhan` AS `pel_bongkar`,`tps`.`URAIAN_TPS` AS `nama_tps`,
`pm`.`nama_pelabuhan` AS `pel_muat`,`pt`.`nama_pelabuhan` AS `pel_transit`,`d`.`nama_pendek` AS `nama_pendek`,
`d`.`id_dokumen` AS `id_dokumen`,`k`.`nama_kantor` AS `nama_kantor`,`tp`.`jenis_tpb` AS `jenis_tpb`,
`h`.`id_header` AS `id_header`,`h`.`uuid` AS `uuid`,`h`.`asalData` AS `asalData`,`h`.`asuransi` AS `asuransi`,
`h`.`bruto` AS `bruto`,`h`.`cif` AS `cif`,`h`.`fob` AS `fob`,`h`.`hargaPenyerahan` AS `hargaPenyerahan`,
`h`.`jabatanTtd` AS `jabatanTtd`,`h`.`jumlahKontainer` AS `jumlahKontainer`,`h`.`kodeAsuransi` AS `kodeAsuransi`,
`h`.`kodeDokumen` AS `kodeDokumen`,`h`.`tanggalDokumen` AS `tanggalDokumen`,`h`.`kodeIncoterm` AS `kodeIncoterm`,
`h`.`kodeKantor` AS `kodeKantor`,`h`.`kodeKantorBongkar` AS `kodeKantorBongkar`,`h`.`kodePelBongkar` AS `kodePelBongkar`,
`h`.`kodePelMuat` AS `kodePelMuat`,`h`.`kodePelTransit` AS `kodePelTransit`,`h`.`kodeTps` AS `kodeTps`,
`h`.`kodeTujuanTpb` AS `kodeTujuanTpb`,`h`.`kodeTutupPu` AS `kodeTutupPu`,`h`.`kodeValuta` AS `kodeValuta`,
`h`.`kotaTtd` AS `kotaTtd`,`h`.`namaTtd` AS `namaTtd`,`h`.`ndpbm` AS `ndpbm`,`h`.`netto` AS `netto`,
`h`.`nik` AS `nik`,`h`.`nilaiBarang` AS `nilaiBarang`,`h`.`nomorAju` AS `nomorAju`,
`h`.`tanggalAju` AS `tanggalAju`,`h`.`nomorBc11` AS `nomorBc11`,`h`.`posBc11` AS `posBc11`,`h`.`seri` AS `seri`,
`h`.`subposBc11` AS `subposBc11`,`h`.`tanggalBc11` AS `tanggalBc11`,`h`.`tanggalTiba` AS `tanggalTiba`,
`h`.`tanggalTtd` AS `tanggalTtd`,`h`.`biayaTambahan` AS `biayaTambahan`,`h`.`biayaPengurang` AS `biayaPengurang`,
`h`.`kodeKenaPajak` AS `kodeKenaPajak`,`h`.`dateCreated` AS `dateCreated`,`h`.`nomorDokpab` AS `nomorDokpab`,
`h`.`subsubposBc11` AS `subsubposBc11`,`h`.`cifRupiah` AS `cifRupiah` 
from (((((((`ws_header` `h` left join `ref_dokumen` `d` on(`d`.`id_dokumen` = `h`.`kodeDokumen`)) 
left join `ref_pelabuhan` `p` on(`p`.`kode_pelabuhan` = `h`.`kodePelBongkar`)) 
left join `ref_kantor` `k` on(`k`.`id_kantor` = `p`.`kode_kantor`)) 
left join `ref_jenis_tpb` `tp` on(`tp`.`id_jenis_tpb` = `h`.`kodeTujuanTpb`)) 
left join `ref_pelabuhan` `pm` on(`pm`.`kode_pelabuhan` = `h`.`kodePelMuat`)) 
left join `ref_pelabuhan` `pt` on(`pt`.`kode_pelabuhan` = `h`.`kodePelTransit`)) 
left join `referensi_tps` `tps` on(`tps`.`KODE_TPS` = `h`.`kodeTps` and `tps`.`KD_KANTOR` = `h`.`kodeKantorBongkar`))
where   h.id_header='".$_POST['id_header']."' "); 
  foreach ($q as $k) {
     $jenis_dokpab  = $k->id_dokumen;
        $nama_pendek   = $k->nama_pendek; 
        $nama_dokumen  = $k->nama_dokumen;
        $data_header   = $k;   
    $qa = $db->query("select id_entitas from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'asal');
       // $db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_asal = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where ket='asal' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_asal = $ka;
        }
      }

      $qa = $db->query("select id_entitas from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'tujuan');
        //$db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $data_tujuan = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where ket='tujuan' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $data_tujuan = $ka;
        }
      }

      $qa = $db->query("select id_entitas from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'pemilik');
       // $db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_pemilik = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where ket='pemilik' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_pemilik = $ka;
        }
}

$qda = $db->query("select * from ws_pengangkut left join ref_negara on ref_negara.kode_negara=ws_pengangkut.kodeBendera where ws_pengangkut.id_header='$data_header->id_header' ");

   foreach ($qda as $kda) {
     $data_angkut = $kda; 
   }
 $qh = $db->query("select d.*,r.nama_dokumen from ws_dokumen d join ref_dokumen r on r.id_dokumen=d.kodeDokumen where d.id_header='$k->id_header'  ");
 $dok = array();
 foreach ($qh as $kh) {
 
   $dok[] = '{
          "kodeDokumen":"'.$kh->kodeDokumen.'",
          "nomorDokumen":"'.$kh->nomorDokumen.'",
          "seriDokumen":'.$kh->seriDokumen.',
          "tanggalDokumen":"'.date("Y-m-d",strtotime($k->tanggalDokumen)).'"
        }
    ';
 }
 $dokumen = '['.implode(",", $dok).']';
  $kema = array();
  $qkm = $db->query("select d.*,j.kemasan from ws_kemasan d join ws_header h on h.id_header=d.id_header left join ref_jenis_kemasan j on j.id_kemasan=d.kodeJenisKemasan where d.id_header='$data_header->id_header'  ");
  foreach ($qkm as $kem) {  
    //print_r($kem);
    $kema[] = '{
          "jumlahKemasan":'.$kem->jumlahKemasan.',
          "kodeJenisKemasan":"'.$kem->kodeJenisKemasan.'",
          "merkKemasan":"'.$kem->merkKemasan.'",
          "seriKemasan":'.$kem->seriKemasan.'
       }';

  } 
  $kemasan = '['.implode(",", $kema).']';
  $bar = array();
  $qb = $db->query("select * from ws_barang  where id_header='$data_header->id_header'  ");
  foreach ($qb as $kb) { 

      $qbd = $db->query("select * from ws_barang_tarif where idBarang='$kb->idBarang' ");
      foreach ($qbd as $kbd) {
        $id_tarif_barang = $kbd->id_tarif_barang;
        $data_barang_tarif = $kbd;
        $tarif_barang ='{
                "kodeJenisTarif":"'.$data_barang_tarif->kodeJenisTarif.'",
                "jumlahSatuan":'.$kb->jumlahSatuan.', 
                "kodeFasilitasTarif":"3",
                "kodeSatuanBarang":"PCE",
                "nilaiBayar":0.00,
                "nilaiFasilitas":100.00,
                "nilaiSudahDilunasi":0.00,
                "seriBarang":1,
                "tarif":11.00,
                "tarifFasilitas":100.00,
                "kodeJenisPungutan":"PPN"
             }';

            
      
    }
    $kdb = explode(" - ", $kb->uraian);
   $bar[] = ' {
          "asuransi":0.00,
          "bruto":123.4500,
          "cif":0.00,
          "diskon":0.00,
          "hargaEkspor":0.00,
          "hargaPenyerahan":'.$kb->hargaPenyerahan.',
          "hargaSatuan":0.00,
          "isiPerKemasan":0,
          "jumlahKemasan":0.00,
          "jumlahRealisasi":0.00,
          "jumlahSatuan":'.$kb->jumlahSatuan.',
          "kodeBarang":"'.$kdb[0].'",
          "kodeDokumen":"40",
          "kodeJenisKemasan":"'.$kb->kodeJenisKemasan.'",
          "kodeSatuanBarang":"'.$kb->kodeSatuanBarang.'",
          "merk":"-",
          "netto":'.$kb->netto.',
          "nilaiBarang":0.00,
          "posTarif":"48191000",
          "seriBarang":1,
          "spesifikasiLain":"-",
          "tipe":"TIPE BARANG",
          "ukuran":"",
          "uraian":"'.$kb->uraian.'",
          "volume":'.$kb->volume.',
          "cifRupiah":0.00,
          "hargaPerolehan":0.00,
          "kodeAsalBahanBaku":"1",
          "ndpbm":0.00,
          "uangMuka":0.00,
          "nilaiJasa":0,
          "barangTarif":[
             '. $tarif_barang.'
          ]
       }';
  }
   $barang = '['.implode(",", $bar).']';
   $kn = explode(" - ", $info->kantor_pengawas);
    $data = '{
    "asalData":"S",
    "asuransi":0.00,
    "bruto":123.4500,
    "cif":0.00,
    "kodeJenisTpb":"1",
    "freight": 0 ,
    "hargaPenyerahan":'.$k->hargaPenyerahan.',
    "idPengguna":"3213142008477",
    "jabatanTtd":"'.$k->jabatanTtd.'",
    "jumlahKontainer":0,
    "kodeDokumen":"40",
    "kodeKantor":"'.$kn[0].'",
    "kodeTujuanPengiriman":"1",
    "kotaTtd":"'.$k->kotaTtd.'",
    "namaTtd":"'.$k->namaTtd.'",
    "netto":'.$k->netto.',
    "nik":"3213432353444",
    "nomorAju":"'.str_replace("-", "", $k->nomorAju).'",
    "seri":0,
    "tanggalAju":"'.date("Y-m-d",strtotime($k->tanggalAju)).'",
    "tanggalTtd":"'.date("Y-m-d",strtotime($k->tanggalTtd)).'",
    "volume":2,
    "biayaTambahan":0.00,
    "biayaPengurang":0.00,
    "vd":0.00,
    "uangMuka":0.00,
    "nilaiJasa":0.00,
    "entitas":[
       {
          "alamatEntitas": "'.$info->alamat.'",
          "kodeEntitas": "3",
          "kodeJenisIdentitas": "3",
          "namaEntitas": "'.$info->nama.'",
          "nibEntitas": "'.$entitas_asal->nibEntitas.'",
          "nomorIdentitas": "'.clean($info->npwp).'",
          "seriEntitas": 1,
          "nomorIjinEntitas": "'.$info->skepkb.'",
          "tanggalIjinEntitas": "'.$info->tglskep.'"
       },
       {
          "alamatEntitas":"'.$data_tujuan->alamatEntitas.'",
          "kodeEntitas":"7",
          "kodeJenisApi":"2",
          "kodeJenisIdentitas":"5",
          "kodeStatus":"5",
          "namaEntitas":"'.$data_tujuan->namaEntitas.'",
          "nibEntitas":"'.$data_tujuan->nomorIjinEntitas.'",
          "nomorIdentitas":"'.clean($data_tujuan->nomorIdentitas).'",
          "seriEntitas":2
       },
       {
          "alamatEntitas":"'.$entitas_pemilik->alamatEntitas.'",
          "kodeEntitas":"9",
          "kodeJenisApi":"2",
          "kodeJenisIdentitas":"5",
          "kodeStatus":"5",
          "namaEntitas":"'.$entitas_pemilik->namaEntitas.'",
          "nibEntitas":"1234567890456",
          "nomorIdentitas":"'.clean($entitas_pemilik->nomorIdentitas).'",
          "seriEntitas":3
       }
    ],
    "dokumen": '.$dokumen.',
    "pengangkut":[
       {
          "namaPengangkut":"'.$data_angkut->namaPengangkut.'",
          "nomorPengangkut":"'.$data_angkut->nomorPengangkut.'",
          "seriPengangkut":1
       }
    ], 
    "kontainer":[
       
    ],
    "kemasan":'.$kemasan.',
    "pungutan":[
       {
          "kodeFasilitasTarif":"3",
          "kodeJenisPungutan":"PPN",
          "nilaiPungutan":123456.00
       }
    ],
    "barang": '.$barang.'
 }';
 //echo $data;
 error_reporting(0); 
 $res = kirim_dokumen($data);
   echo json_encode($res);
  } 
    
  break;
 
 case 'kirim_dokumen_23':
    include "23/kirim_dokumen.php";
  
 break; 

 case 'kirim_dokumen_30': 
   include "30/kirim_dokumen.php";
  
 break;
   case 'hapus_detail_entitas':
   $db->query("delete from ws_entitas_barang where id_entitas_barang='".$_POST['id']."' ");
    break;

  case 'hapus_detail_dokumen':
   $db->query("delete from ws_barang_dokumen where id='".$_POST['id']."' ");
    break;

  case 'set_dokumen':
    $data = array('seri_dokumen' => $_POST['seri_dokumen'], 
                'id_header'    => $_POST['id_header'],
                'id_barang'    => $_POST['id_barang']);
    $qc = $db->query("select * from ws_barang_dokumen where seri_dokumen='".$_POST['seri_dokumen']."' and id_header='".$_POST['id_header']."' and id_barang='".$_POST['id_barang']."' ");
    if ($qc->rowCount()==0) {
      $db->insert("ws_barang_dokumen",$data);
    }
    $q = $db->query("select wd.id as id_detail, d.*,r.nama_dokumen from ws_barang_dokumen wd left join ws_dokumen d on (d.seriDokumen=wd.seri_dokumen and wd.id_barang='".$_POST['id_barang']."' and wd.id_header='".$_POST['id_header']."')  left join ref_dokumen r on r.id_dokumen=d.kodeDokumen  "); 

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
    break;



  case 'get_detail_entitas2':
   $q = $db->query("select ws.id_entitas_barang, ws.seriEntitas,e.nomorIdentitas,e.namaEntitas,e.alamatEntitas from ws_entitas_barang ws left join ws_barang b on b.idBarang=ws.idBarang left join ws_entitas e on (e.id_header=ws.id_header and ws.seriEntitas=e.seriEntitas) where ws.idBarang='".$_POST['id_barang']."'  ");

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
    break;

  case 'get_detail_dokumen2':
   $q = $db->query("select wd.id as id_detail, d.*,r.nama_dokumen from ws_barang_dokumen wd left join ws_dokumen d on (d.seriDokumen=wd.seri_dokumen and wd.id_barang='".$_POST['id_barang']."' and wd.id_header='".$_POST['id_header']."')  left join ref_dokumen r on r.id_dokumen=d.kodeDokumen  "); 

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
    break;

  case 'get_detail_dokumen':
   $id_barang = $_POST['id_barang'];
   $id        = $_POST['id'];
   $q = $db->query("select d.*,r.nama_dokumen from ws_dokumen d left join ref_dokumen r on r.id_dokumen=d.kodeDokumen where id_header='$id' ");

   foreach ($q as $k) {  
      echo "<tr>
              <td>
                <input type='checkbox' id='detail_".$id_barang."_".$id."_".$k->seriDokumen."' onclick='set_dokumen($id_barang,$id,$k->seriDokumen)' />
              </td>
              <td>$k->seriDokumen</td>
              <td>$k->nama_dokumen</td>
              <td>$k->nomorDokumen</td>
              <td>$k->tanggalDokumen</td>
            </tr>";
   } 

  break;

  case 'set_entitas_barang':
    $data = array('seriEntitas' => $_POST['seri_dokumen'], 
                'id_header'    => $_POST['id_header'],
                'idBarang'    => $_POST['id_barang']);
    $qc = $db->query("select * from ws_entitas_barang where seriEntitas='".$_POST['seri_dokumen']."' and id_header='".$_POST['id_header']."' and idBarang='".$_POST['id_barang']."' ");
    if ($qc->rowCount()==0) {
      $db->insert("ws_entitas_barang",$data);  
    }
       $q = $db->query("select ws.id_entitas_barang, ws.seriEntitas,e.nomorIdentitas,e.namaEntitas,e.alamatEntitas from ws_entitas_barang ws left join ws_barang b on b.idBarang=ws.idBarang left join ws_entitas e on (e.id_header=ws.id_header and ws.seriEntitas=e.seriEntitas) where ws.idBarang='".$_POST['id_barang']."'  ");

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
    break;

  case 'update_detail_entitas':
   $id_barang = $_POST['id_barang'];
   $id        = $_POST['id'];
   $q = $db->query("select ws.id_entitas_barang, ws.seriEntitas,e.nomorIdentitas,e.namaEntitas,e.alamatEntitas from ws_entitas_barang ws left join ws_barang b on b.idBarang=ws.idBarang left join ws_entitas e on (e.id_header=ws.id_header and ws.seriEntitas=e.seriEntitas) where ws.idBarang='$idBarang'  ");

     foreach ($q as $kd) { 
                              echo "<tr>                                
                                      <td>$kd->seriEntitas</td>
                                      <td>$kd->nomorIdentitas</td>
                                      <td>$kd->namaEntitas</td>
                                      <td>$kd->alamatEntitas</td>
                                      <td>
                                        <a onclick='hapus_detail_dokumen($kd->id_entitas_barang)' class='btn btn-danger'><i class='fa fa-trash'></i></a>
                                      </td>
                                    </tr>";
                           }

   break;

  case 'get_detail_entitas':
   $id_barang = $_POST['id_barang'];
   $id        = $_POST['id'];
   $q = $db->query("select d.* from ws_entitas d  where id_header='$id' ");

   foreach ($q as $k) {  
      echo "<tr>
              <td>
                <input type='checkbox' id='detail_entitas_".$id_barang."_".$id."_".$k->seriEntitas."' onclick='set_entitas_barang($id_barang,$id,$k->seriEntitas)' />
              </td> 
              <td>$k->seriEntitas</td> 
              <td>$k->nomorIdentitas</td> 
              <td>$k->namaEntitas</td>
              <td>$k->alamatEntitas</td>
            </tr>";
   } 

  break;

  case 'get_detail_barang':
  $idBarang = $_POST['id'];
  // echo "select b.*,s.satuan from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang where b.idBarang='$idBarang'";
  foreach ($db->query("select b.*,s.satuan,h.kodeDokumen from ws_barang b left join ref_satuan s on s.kode_satuan=b.kodeSatuanBarang left join ws_header h on h.id_header=b.id_header where b.idBarang='$idBarang' ") as $k) {
    $uraian = $k->uraian;
 
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
                      <h3 class="box-title">Keterangan Lainnya</h3>
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
                                
                                  <input type="text" name="hargaEkspor" id="hargaEkspor" class="form-control" >
                         
                             </div>
                             <div class="form-group">
                                <label for="nomor" >Biaya Tambauan </label>
                                
                                  <input type="text" name="nilaiTambah" id="nilaiTambah" class="form-control" readonly="">
                                
                             </div>

                             <div class="form-group">
                                <label for="nomor" >FOB</label>
                                
                                  <input type="text" name="fob" id="fob" class="form-control" readonly="">
                                
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Harga Satuan </label>
                                
                                  <input type="text" name="hargaSatuan" id="hargaSatuan" class="form-control" readonly="">
                          
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Freight </label>
                                
                                  <input type="text" name="freight" id="freight" class="form-control" readonly="">
                             
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Asuransi </label>
                                
                                  <input type="text" name="asuransi" id="asuransi" class="form-control" readonly="">
                               
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Nilai CIF </label>
                                
                                  <input type="text" name="cif" id="cif" class="form-control" readonly="">
                            
                             </div>

                             <div class="form-group">
                                <label for="nomor" >Nilai Pabean </label>
                                
                                  <input type="text" name="hargaPenyerahan" id="hargaPenyerahan" class="form-control" readonly="">
                           
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
                          <th>Fasilitas</th>
                          <th>Izin</th>
                          <th>File</th>
                        </tr>
                      </thead>
                    </table>
                            
                          </div>
                        </div>
                      </div>     

        </form>
   </div>
   <script type="text/javascript">

     function show_modal_fasilitas() {
       // body...
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
    break;

  case 'get_tps':
  // $par = $_GET['term'];
   // print_r($key);
 //   $key = $par['term'];
    // $val = explode(" - ", $key);
    $tp = get_tps($_POST['val'][0]);
    $res = array();
    foreach ($tp->data as $key => $value) {

       $data['id'] = $value->kodeGudang;
       $data['text'] =  $value->kodeGudang." - ".$value->namaGudang;
       $res[] = $data;
    }
    echo json_encode($res);   
    break;
 
  case 'get_currency': 
    $data = get_valuta($_POST['kode']);
   // print_r($data);
    $valuta = $data->data;
    if (!empty($valuta)) {
       echo $valuta[0]->nilaiKurs;
    }
    break;

  case 'hapus_dokumen': 
  $db->query("delete from ws_dokumen where idDokumen='".$_POST['id']."' "); 
    break;

   case 'hapus_kemasan':
  $db->query("delete from ws_kemasan where id_kemasan='".$_POST['id']."' "); 
    break;

   case 'hapus_kontainer':
  $db->query("delete from ws_kontainer where id_kontainer='".$_POST['id']."' "); 
    break;

  case 'reload_dokumen':
    $q = $db->query("select d.*,r.nama_dokumen from ws_dokumen d join ref_dokumen r on r.id_dokumen=d.kodeDokumen where d.id_header='".$_POST['id']."'  "); 
          if ($q->rowCount()==0) {
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$k->seriDokumen</td>
                       <td>$k->nama_dokumen</td>
                       <td>$k->nomorDokumen</td>
                       <td>".tgl_indo($k->tanggalDokumen)."</td>
                       <td>-</td>
                       <td>-<td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data($k->idDokumen)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete($k->idDokumen)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
            }
          }
  break;


  case 'reload_kontainer':
 $q = $db->query("select d.*,j.tipe_kontainer,u.ukuran_kontainer,rf.jenis_kontainer from ws_kontainer d join ws_header h on h.id_header=d.id_header 
             left join ref_tipe_kontainer j on j.id_tipe_kontainer=d.kodeTipeKontainer
             left join ref_ukuran_kontainer u on u.id_ukuran=d.kodeUkuranKontainer
             left join ref_jenis_kontainer rf on rf.id_jenis_kontainer=d.kodeJenisKontainer where d.id_header='".$_POST['id']."'  ");
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$no</td>
                       <td>$k->nomorKontainer</td>
                       <td>$k->ukuran_kontainer</td>
                       <td>$k->jenis_kontainer</td> 
                       <td>$k->tipe_kontainer</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_kontainer($k->id_kontainer)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_kontainer($k->id_kontainer)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
                $no++;
            }
          }
  break;

  case 'reload_kemasan':

   $q = $db->query("select d.*,j.kemasan from ws_kemasan d join ws_header h on h.id_header=d.id_header 
             left join ref_jenis_kemasan j on j.id_kemasan=d.kodeJenisKemasan where d.id_header='".$_POST['id']."'  ");
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$no</td>
                       <td>$k->jumlahKemasan</td>
                       <td>$k->kemasan</td>
                       <td>$k->merkKemasan</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_kemasan($k->id_kemasan)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_kemasan($k->id_kemasan)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
                $no++;
            }
          }


  break;

  case 'reload_jaminan':

  $db->query("update ws_jaminan set simpan='11' where idJaminan='".$_POST['idJaminan']."' "); 

  $q = $db->query("select j.idJaminan,j.id_header,j.nomorBpj,j.tanggalBpj,j.kodeJenisJaminan,j.nomorJaminan,j.tanggalJaminan,j.tanggalJatuhTempo,j.penjamin,j.nilaiJaminan ,jj.jenis_jaminan from ws_jaminan j left join ws_header h on h.id_header=j.id_header left join ref_jenis_jaminan jj on jj.id_jenis_jaminan=j.kodeJenisJaminan where j.id_header='".$_POST['id']."'  "); 
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$no</td>
                       <td>$k->jenis_jaminan</td>
                       <td>$k->nomorJaminan</td>
                       <td>$k->tanggalJaminan</td>
                       <td>$k->nilaiJaminan</td>
                       <td>$k->tanggalJatuhTempo</td>
                       <td>$k->penjamin</td>
                       <td>$k->nomorBpj</td>
                       <td>$k->tanggalBpj</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_jaminan($k->idJaminan)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_jaminan($k->idJaminan)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
                $no++;
            }
          }


  break;

  case 'edit_data_kontainer': 
  $id = $_POST['id'];
  $res = array();
  $q = $db->query("select * from ws_kontainer where id_kontainer='$id'  ");
  foreach ($q as $k) { 
    // $data = array('ID_HEADER' => $id,
    //              'SERI_DOKUMEN' => $k->jml+1 ); 
    // $db->insert("tpb_dokumen",$data);
    // $ID = $db->last_insert_id();
    $res['TIPE_KONTAINER'] = $k->kodeTipeKontainer;
    $res['ID'] = $k->id_kontainer; 
    $res['KODE_TIPE_KONTAINER'] = $k->kodeTipeKontainer;
    $res['KODE_JENIS_KONTAINAR'] = $k->kodeJenisKontainer;
    $res['KODE_UKURAN_KONTAINER'] = $k->kodeUkuranKontainer;
    $res['NOMOR_KONTAINER'] = $k->nomorKontainer;
    $res['SERI_KONTAINER'] = $k->seriKontainer;

 
  }
  echo json_encode($res);
  break;

  case 'edit_data_jaminan': 
  $id = $_POST['id'];
  $res = array();
  $q = $db->query("select * from ws_jaminan where idJaminan='$id' ");
  foreach ($q as $k) {
    $res = (array)$k; 
  }
  echo json_encode($res);
  break;

  case 'edit_data_kemasan': 
  $id = $_POST['id'];
  $res = array();
  $q = $db->query("select d.*,r.kemasan from ws_kemasan d join ref_jenis_kemasan r on r.id_kemasan=d.kodeJenisKemasan where d.id_kemasan='$id' ");
  foreach ($q as $k) {
    // $data = array('ID_HEADER' => $id,
    //              'SERI_DOKUMEN' => $k->jml+1 );
    // $db->insert("tpb_dokumen",$data);
    // $ID = $db->last_insert_id();
    $res['nama_kemasan'] = $k->kemasan;
    $res['ID'] = $k->id_kemasan;
    $res['KODE_JENIS_KEMASAN'] = $k->kodeJenisKemasan;
    $res['SERI_KEMASAN'] = $k->seriKemasan;  
    $res['MERK_KEMASAN'] = $k->merkKemasan;
    $res['JUMLAH_KEMASAN'] = $k->jumlahKemasan;

 
  }
  echo json_encode($res);
  break;

  case 'edit_data':
  $id = $_POST['id'];
  $res = array();
  $q = $db->query("select d.*,r.nama_dokumen from ws_dokumen d join ref_dokumen r on r.id_dokumen=d.kodeDokumen  where d.idDokumen='$id' ");
  foreach ($q as $k) {
    // $data = array('ID_HEADER' => $id,
    //              'SERI_DOKUMEN' => $k->jml+1 );
    // $db->insert("tpb_dokumen",$data);
    // $ID = $db->last_insert_id();
    $res['nama_dokumen'] = $k->nama_dokumen;
    $res['ID'] = $k->idDokumen;
    $res['KODE_JENIS_DOKUMEN'] = $k->kodeDokumen;
    $res['SERI_DOKUMEN'] = $k->seriDokumen;
    $res['NOMOR_DOKUMEN'] = $k->nomorDokumen;
    $res['TANGGAL_DOKUMEN'] = date("Y-m-d",strtotime($k->tanggalDokumen));

 
  }
  echo json_encode($res);

  break;

  case 'add_kontainer': 
  $id = $_POST['id']; 
  $res = array();
  $q = $db->query("select ifnull(count(id_kontainer),0) as jml from ws_kontainer where id_header='$id' ");
  foreach ($q as $k) {
    $data = array('id_header' => $id,
                 'seriKontainer' => $k->jml+1 );
    $db->insert("ws_kontainer",$data);
    $ID = $db->last_insert_id(); 
    $res['id'] = $ID;
    $res['seri'] = $k->jml+1;  
  }
  echo json_encode($res);

  break;

  case 'hapus_detail_barang':
    $id = $_POST['id'];
    $db->query("delete from ws_barang where idBarang=?",array($id));
  break;

   case 'add_jaminan':  
      $id  = $_POST['id'];  
      $res = array();
      $db->query("delete from ws_jaminan where simpan is null or simpan='00' ");
      $q   = $db->query("select ifnull(count(idJaminan),0) as jml from ws_jaminan where id_header='$id' ");
      foreach ($q as $k) {
        $data = array('id_header'   => $id,
                      'simpan'      => '00',
                      'date_created' => date("Y-m-d H:i:s"),
                      'seriJaminan' => $k->jml+1 );
        $db->insert("ws_jaminan",$data);
        $ID = $db->last_insert_id(); 
        $res['id'] = $ID; 
        $res['seri'] = $k->jml+1;
      }
  echo json_encode($res);

  break;


  case 'add_kemasan':  
  $id = $_POST['id']; 
  $res = array();
  $q = $db->query("select ifnull(count(id_kemasan),0) as jml from ws_kemasan where id_header='$id' ");
  foreach ($q as $k) {
    $data = array('id_header' => $id,
                 'seriKemasan' => $k->jml+1 );
    $db->insert("ws_kemasan",$data);
    $ID = $db->last_insert_id(); 
    $res['id'] = $ID;
    $res['seri'] = $k->jml+1;
  }
  echo json_encode($res);

  break;


  case 'add_barang':
    $id = $_POST['id'];
    $res = array();
    $q = $db->query("select ifnull(count(idBarang),0) as jml from ws_barang where id_header='$id' ");
    foreach ($q as $k) {
       $data = array('id_header'   => $id,
                     'seriBarang' => $k->jml+1
                   );
    $db->insert("ws_barang",$data);  
    $ID = $db->last_insert_id(); 
    $res['id'] = $ID; 
    $res['seri'] = $k->jml+1;
  }
  echo json_encode($res);
  break;

  case 'add_dokumen': 
  $id = $_POST['id'];
  $res = array();
  $db->query("delete from ws_dokumen where id_header='$id' and kodeDokumen is null "); 
   $q = $db->query("select ifnull(count(idDokumen),0) as jml from ws_dokumen where id_header='$id' ");
  foreach ($q as $k) {
    $data = array('id_header' => $id,
                 'seriDokumen' => $k->jml+1 );
    $db->insert("ws_dokumen",$data); 
    $ID = $db->last_insert_id();
    $res['id'] = $ID; 
    $res['seri'] = $k->jml+1;
  }
  // $res['seri'] = 4;
  echo json_encode($res);
  break;

  case 'save_data':
    $tabel = $_POST['tabel'];
    $kolom = $_POST['kolom'];
    $nilai = $_POST['nilai'];
    $kol_id = $_POST['kol_id']; 
    $id    = $_POST['id'];

    $db->query("update $tabel set $kolom='$nilai' where $kol_id='$id' ");  
  break; 

  case 'buat_dokumen':
    $jenis = $_POST['jenis'];
    $no_aju = buat_no_aju($jenis);
    $uuid = get_uuid(); 
    $data = array('nomorAju'           => $no_aju, 
                  'tanggalAju'         => date("Y-m-d"),
                  'kodeDokumen'        => $jenis,
                  'kodeTutupPu'        => '11',
                  'tanggalDokumen'     => date("Y-m-d"),
                  'uuid'               => $uuid, 
                  'asalData'           => 'S',
                  'statusDokumen'      => 'Draft'
                );
    $db->insert("ws_header",$data); 
    echo $uuid;
    break; 

  case 'get_detail_pelabuhan':
    $id = $_POST['id'];
    $q = $db->query("select r.* from ref_kantor r join ref_pelabuhan p on p.kode_kantor=r.id_kantor
     where p.kode_pelabuhan = '$id' ");
    foreach ($q as $k) {
      echo "$k->id_kantor - $k->nama_kantor";
      $db->query("update tpb_header set KODE_KANTOR_BONGKAR='$k->id_kantor' where ID='".$_POST['id_header']."' ");
    }

  break;

   case 'get_detail_penerima':
    $id = $_POST['id'];
    $q = $db->query("select npwp,nama,alamat from penerima where kode_penerima='$id' ");
    foreach ($q as $k) {
      $data['npwp'] = $k->npwp; 
      $data['nama'] = $k->nama;
      $data['alamat'] = $k->alamat;
    }
    echo json_encode($data);
    break;


  case 'get_detail_pemasok':
    $id = $_POST['id'];
    $q = $db->query("select npwp,nama,alamat from pemasok where kode_pemasok='$id' ");
    foreach ($q as $k) {
      $data['npwp'] = $k->npwp;
      $data['nama'] = $k->nama;
      $data['alamat'] = $k->alamat;
    }
    echo json_encode($data);
    break;

  case "get_negara":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from ref_negara where negara like '%$key%' or kode_negara like '%$key%' ");
    foreach ($q as $k) {
       $data['id']       = $k->kode_negara; 
       $data['text']     = $k->kode_negara." - ".$k->negara; 
       $res['results'][] = $data;
    }
    echo json_encode($res);
  break;

   case "get_bank":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from ref_bank where kode_bank like '%$key%' or nama_bank like '%$key%' ");
    foreach ($q as $k) {
       $data['id']       = $k->kode_bank; 
       $data['text']     = $k->nama_bank; 
       $res['results'][] = $data;
    }
    echo json_encode($res);
  break;

  case "get_pemasok":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from pemasok where nama like '%$key%' or kode_pemasok like '%$key%'
    or npwp like '%$key%' ");
    foreach ($q as $k) {
       $data['id']       = $k->kode_pemasok." - ".$k->nama; 
       $data['text']     = $k->kode_pemasok." - ".$k->nama; 
       $res['results'][] = $data;
    }
    echo json_encode($res);
  break;

  case "get_uraian":
    $q = $db->query("select nm_barang from barang where kd_barang=?",array($_POST['id']));
    foreach ($q as $k) {
       echo $k->nm_barang;
    }
  break; 

  case "get_penerima":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from penerima where nama like '%$key%' or kode_penerima like '%$key%'
    or npwp like '%$key%' ");
    foreach ($q as $k) { 
       $data['id']       = $k->kode_penerima." - ".$k->nama; 
       $data['text']     = $k->kode_penerima." - ".$k->nama; 
       $res['results'][] = $data;
    }
    echo json_encode($res);
  break;

  case "get_barang":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from barang where kd_barang like '%$key%' or nm_barang like '%$key%' ");
    foreach ($q as $k) {
       $data['id'] = $k->kd_barang;    
       $data['text'] = $k->kd_barang." - ".$k->nm_barang;
       $res['results'][] = $data;
    }
    echo json_encode($res);
    break;

   case "get_kantor":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from ref_kantor where nama_kantor like '%$key%' or id_kantor like '%$key%'
    ");
    foreach ($q as $k) {
       $data['id'] = $k->id_kantor; 
       $data['text'] = $k->id_kantor." - ".$k->nama_kantor;
       $res['results'][] = $data;
    }
    echo json_encode($res);
    break;
 
  case "get_pelabuhan":
    $par = $_GET['term'];
   // print_r($key);
    $key = $par['term'];
    $res = array();
    $q = $db->query("select * from ref_pelabuhan where kode_kantor like '%$key%' or nama_pelabuhan like '%$key%'
    or kode_kantor like '%$key%' ");
    foreach ($q as $k) {
       $data['id'] = $k->kode_pelabuhan; 
       $data['text'] = $k->kode_pelabuhan." - ".$k->nama_pelabuhan;
       $res['results'][] = $data;
    }
    echo json_encode($res);
    break;

  case "get_dokpab":
    $jenis = $_POST['jenis']; 
   // if ($jenis=='tpb') {
      $q = $db->query("select uuid, nama_pendek as jenis_dokpab,id_dokumen from ref_dokumen where tpb='$jenis'"); 
       ?>
       <option value=''>Pilih Dokumen</option>
       <?php
       foreach ($q as $k) {
         echo "<option value='$k->id_dokumen'>$k->jenis_dokpab</option>";
       } 
   // }
  break;


  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
      "no_lap" => $_POST["no_lap"],
  );
  
  
  
   
    $in = $db->insert("bahan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("ws_header","uuid",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("bahan","no_lap",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_lap" => $_POST["no_lap"],
   );
   
   
   

    
    
    $up = $db->update("bahan",$data,"no_lap",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>