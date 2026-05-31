<?php
session_start();
include "../../inc/config.php";
session_check_json();

function ambil_bahan_baku($kodebj,$jumlah_produksi,$id_produksi_detail,$id_transfer=NULL)
{
  global $db;
   $no=1;
   $q = $db->query("select id from barang where kd_barang='$kodebj' ");
   foreach ($q as $k) {
     $id_barang = $k->id;
   }
  $q = $db->query("select d.kodebb,d.jumlah from bom_detail d join bom b on d.id_bom=b.id where b.kodebj='$kodebj' ");
  foreach ($q as $k) {
    $qc = $db->query("select id_barang,id_incoming_detail,(masuk-keluar) as jumlah,jenis_dokpab from v_rekap_stok_produksi 
      where (masuk-keluar)>0 and kd_barang='$k->kodebb'");
   
    $jumlah = $jumlah_produksi * $k->jumlah;
    foreach ($qc as $kc) {  
     //  echo "$jumlah, ";
     //  print_r($kc);
       if ($jumlah>0){
          if ($jumlah>$kc->jumlah) {
            $jml_terpakai  = $kc->jumlah;
            $jumlah        = $jumlah - $kc->jumlah;
          }else{
            $jml_terpakai  = $jumlah;
            $jumlah        = $jumlah - $jml_terpakai;
          }    
          $data_detail = array(
             'id_produksi_detail' => $id_produksi_detail,
             'id_incoming_detail' => $kc->id_incoming_detail,
             'kode'               => $k->kodebb,
             'jumlah'             => $jml_terpakai,
             'row_no'             => $no
          ); 
         
          $db->insert("bahanbaku_detail",$data_detail);    
         //  update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);  
       //   update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);
      }
      $no++;
    }
  }
   $data_transfer_detail = array('id_transfer' => $id_transfer , 
                                 'id_barang' => $id_barang,
                                  'id_produksi_detail' => $id_produksi_detail,
                                  'jml' => $jumlah_produksi );
   $db->insert("transfer_detail",$data_transfer_detail); 
    //update_stock($jumlah_produksi,'plus','brg_jadi','3',$id_barang,$_SESSION['username']); 
}
switch ($_GET["act"]) {

  case "add_temp_barang_jadi":
    $kd_barang = $_POST['kd_barang'];
    $jumlah    = $_POST['jumlah'];
    $no        = $_POST['no'];
    $data = array('kd_barang' => $kd_barang , 
                  'jumlah'    => $jumlah,
                  'no'        => $no, 
                  'user'      => $_SESSION['username'],
                  'date_created' => date("Y-m-d H:i:s"));
    $q = $db->query("select id from temp_lp_gabungan where kd_barang='$kd_barang' and user='".$_SESSION['username']."' and no='$no' ");
    if ($q->rowCount()==0) {
       $db->insert("temp_lp_gabungan",$data); 
    }else{
      foreach ($q as $k) {
       $db->update("temp_lp_gabungan",$data,"id",$k->id); 
      }
      
    }
  break;

  case "hapus_temp_barang_jadi":
    $kd_barang = $_POST['kd_barang'];
    $jumlah    = $_POST['jumlah'];
    $no        = $_POST['no'];
    $data = array('kd_barang' => $kd_barang , 
                  'jumlah'    => $jumlah,
                  'no'        => $no, 
                  'user'      => $_SESSION['username'],
                  'date_created' => date("Y-m-d H:i:s"));
    $q = $db->query("delete from temp_lp_gabungan where kd_barang='$kd_barang' and user='".$_SESSION['username']."' and no='$no' ");
  break;


  case 'add_ket':
    $kd_bahan_baku = $_POST['kode_bahan_baku'];
    $kd_barang_jadi = $_POST['kd_barang_jadi'];
    $baris = $_POST['baris'];
    if ($_POST['jenis']=='scrap') { 
      $db->query("update temp_lp_gabungan_detail set ket = '".$_POST['ket']."',jumlah='".$_POST['jumlah']."'
      where kd_bahan_baku='$kd_bahan_baku' and kd_barang_jadi='$kd_barang_jadi' and baris='$baris' "); 
      echo "update temp_lp_gabungan_detail set ket = '".$_POST['ket']."',jumlah='".$_POST['jumlah']."'
      where kd_bahan_baku='$kd_bahan_baku' and kd_barang_jadi='$kd_barang_jadi' and baris='$baris'";
    }else{
       $db->query("update temp_lp_gabungan_detail set ket = '".$_POST['ket']."'
    where kd_bahan_baku='$kd_bahan_baku' and kd_barang_jadi='$kd_barang_jadi' and baris='$baris' "); 
    }
   
    break;
  

  case "add_detail_bahan_baku":
  $kd_barang_jadi = $_POST['kd_barang']; 
  ?>
  <style type="text/css">
    .ui-autocomplete { z-index:2147483647; }
  </style>
  <table style="font-size: 16px;margin-bottom: 10px">
    <tr>
      <td style="width: 200px">Kode/Nama Barang Jadi</td>
      <td>: &nbsp;<?= $_POST['kode'] ?></td>
    </tr>
     <tr>
      <td>Jumlah</td>
      <td>: &nbsp;<?= $_POST['jumlah'] ?></td>
    </tr>
  </table>
  <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#bahan_baku">Bahan Baku/Setengah Jadi</a></li>
                <li><a data-toggle="tab" href="#scrap">Scrap</a></li>
              </ul>

              <div class="tab-content">
                <div id="bahan_baku" class="tab-pane fade in active">
                 <table class="table">
                   <thead>
                     <tr>
                       <th style="width:50px;text-align: center">
                         <a style="cursor: pointer;" onclick="add_baris_bahan_baku()" ><i class="fa fa-plus"></i> </a>
                       </th>
                       <th style="width: 200px">Jenis</th>
                       <th style="width: 300px">Kode/Nama Barang</th>
                       <th style="width: 100px">Unit</th>
                         <th>Stock</th>     
                       <th>Qty</th>                     
                       <th>Ket</th>
                     </tr>
                   </thead>
                   <tbody id="isi_tabel_bahan_baku">
                   <?php
                   $qb = $db->query("select d.*,b.id as id_barang,b.satuan from temp_lp_gabungan_detail d join barang b on b.kd_barang=d.kd_bahan_baku  where d.kd_barang_jadi='$kd_barang_jadi' and d.user='".$_SESSION['username']."' and jenis_produksi!='3' ");
                   if ($qb->rowCount()==0) {
                    $baris = 1;
                    ?>
                     <tr id="baris_bahan_baku_1">
                       <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_bahan_baku('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                       <td>
                         <select onchange="show_cari(1)" class="form-control" id="ket_jenis_bahan_baku_1" name="ket_jenis_bahan_baku[]" >
                           <option value="">Pilih Jenis</option>
                           <option value="1">Bahan Baku</option>
                           <option value="2">Barang Setengah Jadi</option>
                         </select>
                         <i id="error_jenis_bahan_baku_1" style="display:none;color: red">Pilih Jenis Bahan Baku</i>
                       </td>
                       <td><input readonly type="text" id="form_kode_bahan_baku_1" placeholder="Kode Barang"  onpaste="cari_kode_bahan_baku('1')" onchange="cari_kode_bahan_baku('1')" onkeyup="cari_kode_bahan_baku('1')" class="form-control" name="kode_bahan_baku[]"  >
                        <input type="hidden" name="kode_input_bahan_baku[]" id="kode_input_bahan_baku_1"> 
                        <input type="hidden" name="id_input_bahan_baku[]" id="id_input_bahan_baku_1"> 
                       </td> 
                       <td><input type="text" id="form_unit_bahan_baku_1" class="form-control" name="unitbahan_baku[]"  readonly=""></td> 
                       <td><input type="text" id="form_stock_1" class="form-control" name="stock[]" readonly="" ></td>
                       <td><input type="number" id="form_qty_bahan_baku_1" class="form-control" name="qty_bahan_baku[]" onkeyup="cek_stok('1',this.value)" required>
                       <i id="error_stock_bahan_baku_1" style="color: red"></i> </td>
                       <td><input type="text" onkeyup="save_ket_bahan_baku('1')" id="form_ket_bahan_baku_1" class="form-control" name="ket_bahan_baku[]" ></td>    
                     </tr>
                    <?php
                   }else{
                     $baris = 1;
                     foreach ($qb as $kb) {
                       $qs = $db->query("select sum(ifnull(stock,0)) as stock from stock_barang where id_barang='$kb->id_barang' and id_bagian='3' ");
                       if ($qs->rowCount()>0) {
                         foreach ($qs as $ks) {
                           $stock = $ks->stock;
                         }
                       }else{
                          $stock = 0;
                       }
                       ?>
                        <tr id="baris_bahan_baku_<?= $baris ?>">
                       <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_bahan_baku('<?= $baris ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                       <td>
                         <select onchange="show_cari(<?= $baris ?>)" class="form-control" id="ket_jenis_bahan_baku_<?= $baris ?>" name="ket_jenis_bahan_baku[]" >
                           <option value="">Pilih Jenis</option>
                           <option value="1" <?= ($kb->jenis_produksi=='1' ?  "selected" :  "") ?>>Bahan Baku</option>
                           <option value="2" <?= ($kb->jenis_produksi=='2' ?  "selected" :  "") ?>>Barang Setengah Jadi</option>
                         </select>
                         <i id="error_jenis_bahan_baku_<?= $baris ?>" style="display:none;color: red">Pilih Jenis Bahan Baku</i>
                       </td>
                       <td><input  type="text" id="form_kode_bahan_baku_<?= $baris ?>" placeholder="Kode Barang"  onpaste="cari_kode_bahan_baku('<?= $baris ?>')" onchange="cari_kode_bahan_baku('<?= $baris ?>')" onkeyup="cari_kode_bahan_baku('<?= $baris ?>')" class="form-control" name="kode_bahan_baku[]" value="<?= $kb->kd_bahan_baku ?>"  >
                        <input type="hidden" name="kode_input_bahan_baku[]" id="kode_input_bahan_baku_<?= $baris ?>" value="<?= $kb->kd_bahan_baku ?>"> 
                        <input type="hidden" name="id_input_bahan_baku[]" id="id_input_bahan_baku_<?= $baris ?>"> 
                       </td> 
                       <td><input type="text" value="<?= $kb->satuan ?>" id="form_unit_bahan_baku_<?= $baris ?>" class="form-control" name="unitbahan_baku[]"  readonly=""></td> 
                       <td><input type="text" value="<?= $stock ?>" id="form_stock_<?= $baris ?>" class="form-control" name="stock[]" readonly="" ></td>
                       <td><input type="number" value="<?= $kb->jumlah ?>" id="form_qty_bahan_baku_<?= $baris ?>" class="form-control" name="qty_bahan_baku[]" onkeyup="cek_stok('<?= $baris ?>',this.value)" required>
                       <i id="error_stock_bahan_baku_<?= $baris ?>" style="color: red"></i> </td>
                       <td><input type="text" onkeyup="save_ket_bahan_baku('<?= $baris ?>')" id="form_ket_bahan_baku_<?= $baris ?>" class="form-control" name="ket_bahan_baku[]" value="<?= $kb->ket ?>"></td>    
                     </tr>
                       <?php
                       $baris++;
                     }
                   }
                   ?>
                    
                   </tbody>
                   <input type="hidden" id="jml_bahan_baku" name="jml_bahan_baku" value="<?= $baris ?>">
                 </table>
                </div>
                <div id="scrap" class="tab-pane fade">
                  <table class="table">
                   <thead>
                     <tr>
                       <th style="width:50px;text-align: center">
                         <a style="cursor: pointer;" onclick="add_baris_scrap()" ><i class="fa fa-plus"></i> </a>
                       </th>
                       <th style="width: 300px">Kode /Nama Scrap</th>
                       <th style="width: 100px">Unit</th>
                       <th>Qty</th>      
                               
                       <th>Ket</th>
                     </tr>
                   </thead>
                   <tbody id="isi_tabel_scrap">
                   <?php
                   $nos=1;
                   // echo "select b.nm_barang,b.satuan, d.id_detail,d.kd_barang_jadi,d.baris,d.kd_bahan_baku,d.jumlah from 
                   //  temp_lp_gabungan_detail d join barang b on b.kd_barang=d.kd_bahan_baku where kd_barang_jadi='".$_POST['kd_barang']."' and jenis_produksi='3'";
                   $qs = $db->query("select d.ket, b.nm_barang,b.satuan, d.id_detail,d.kd_barang_jadi,d.baris,d.kd_bahan_baku,d.jumlah from 
                    temp_lp_gabungan_detail d join barang b on b.kd_barang=d.kd_bahan_baku where kd_barang_jadi='".$_POST['kd_barang']."' and jenis_produksi='3' order by baris asc ");
                   if ($qs->rowCount()>0) {
                    //$nos=1;
                    foreach ($qs as $ks) {
                      $nos = $ks->baris;
                     ?>
                      <tr id="baris_scrap_<?= $nos ?>">
                       <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_scrap('<?= $nos ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                       <td><input type="text" id="form_kode_scrap_<?= $nos ?>" value="<?= $ks->kd_bahan_baku." ".$ks->nm_barang ?>" placeholder="Kode Barang" onkeyup="cari_kode_scrap('<?= $nos ?>')" class="form-control" name="kode_scrap_[]"  >
                        <input type="hidden" name="kode_input_scrap_[]" value="<?= $ks->kd_bahan_baku ?>" id="kode_input_scrap_<?= $nos ?>"> 
                        <input type="hidden" name="id_input_scrap[]" id="id_input_scrap_<?= $nos ?>"> 
                       </td> 
                       <td><input type="text" id="form_unit_scrap_<?= $nos ?>" class="form-control" name="unit_scrap[]"  readonly="" value="<?= $ks->satuan ?>"></td> 
                       
                       <td><input type="number" id="form_qty_scrap_<?= $nos ?>" value="<?= $ks->jumlah ?>" class="form-control" name="qty_scrap[]" onkeyup="save_ket_scrap('<?= $nos ?>')" required>
                       </td>
                       <td><input type="text" onkeyup="save_ket_scrap('<?= $nos ?>')" id="form_ket_scrap_<?= $nos ?>" class="form-control" name="ket_scrap[]" value="<?= $ks->ket ?>" ></td>    
                     </tr>
                     <?php
                   // $nos++;
                    }
                   }else{
                    ?>
                     <tr id="baris_scrap_1">
                       <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_scrap('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                       <td><input type="text" id="form_kode_scrap_1" placeholder="Kode Barang" onkeyup="cari_kode_scrap('1')" class="form-control" name="kode_scrap_[]"  >
                        <input type="hidden" name="kode_input_scrap_[]" id="kode_input_scrap_1"> 
                        <input type="hidden" name="id_input_scrap[]" id="id_input_scrap_1"> 
                       </td> 
                       <td><input type="text" id="form_unit_scrap_1" class="form-control" name="unit_scrap[]"  readonly=""></td> 
                       
                       <td><input type="number" id="form_qty_scrap_1" class="form-control" name="qty_scrap[]" onkeyup="save_ket_scrap('1')" required>
                       </td>
                       <td><input type="text" onkeyup="save_ket_scrap('1')" id="form_ket_scrap_1" class="form-control" name="ket_scrap[]" ></td>    
                     </tr>
                    <?php
                   }
                   ?>
                    
                   </tbody> 
                 </table>
                 <input type="hidden" id="jml_scrap" name="jml_scrap" value="<?= $nos ?>">
                </div>
              </div>
              <script type="text/javascript">

                function save_ket_bahan_baku(id){
                  var kode  = $("#kode_input_bahan_baku_"+id).val();
                
                   // $("#error_stock_bahan_baku_"+id).hide();
                    $.ajax({
                              type : 'POST', 
                              data : {
                                 jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                                 baris : id,
                                 jenis : 'bahan_baku',
                                 ket : $("#form_ket_bahan_baku_"+id).val(),
                                 kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                                 kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                               //  jumlah : $("#form_qty_bahan_baku_"+id).val()
                              },
                             url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_ket", 
                              success:function(data){
                                  // $("#form_unit_bahan_baku_"+id).val(data);
                              }
                            });
                  
               
                }

                function save_ket_scrap(id){
                  var kode  = $("#kode_input_bahan_baku_"+id).val();
                
                   // $("#error_stock_bahan_baku_"+id).hide();
                    $.ajax({
                              type : 'POST', 
                              data : {
                                 jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                                 baris : id,
                                 jenis : 'scrap',
                                 jumlah : $("#form_qty_scrap_"+id).val(),
                                 ket : $("#form_ket_scrap_"+id).val(),
                                 kode_bahan_baku : $("#kode_input_scrap_"+id).val(),
                                 kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                                // jumlah : $("#form_qty_bahan_baku_"+id).val()
                              },
                             url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_ket", 
                              success:function(data){
                                  // $("#form_unit_bahan_baku_"+id).val(data);
                              }
                            });
                  
               
                }

                // function cek_stok_scrap(id,jumlah){
                //   var kode  = $("#kode_input_bahan_baku_"+id).val();
                //   var jml   = parseFloat(jumlah);
                
                //     $("#error_stock_bahan_baku_"+id).hide();
                //     $.ajax({
                //               type : 'POST', 
                //               data : {
                //                  jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                //                  baris : id,
                //                  kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                //                  kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                //                  jumlah : $("#form_qty_bahan_baku_"+id).val()
                //               },
                //              url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_list_bahan_baku", 
                //               success:function(data){
                //                   // $("#form_unit_bahan_baku_"+id).val(data);
                //               }
                //             });
                  
               
                // }

                function cek_stok(id,jumlah){
                  var kode  = $("#kode_input_bahan_baku_"+id).val();
                  var jml   = parseFloat(jumlah);
                  var stock = parseFloat($("#form_stock_"+id).val());
                  if (jml>stock) {
                      // alert("Inputan melebihi stock");
                      $("#error_stock_bahan_baku_"+id).html("Inputan melebihi stock");
                      $("#error_stock_bahan_baku_"+id).show();
                      $("#form_qty_bahan_baku_"+id).val('');
                      $("#form_qty_bahan_baku_"+id).focus();
                  }else{
                    $("#error_stock_bahan_baku_"+id).hide();
                    $.ajax({
                              type : 'POST', 
                              data : {
                                 jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                                 baris : id,
                                 kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                                 kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                                 jumlah : $("#form_qty_bahan_baku_"+id).val()
                              },
                             url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_list_bahan_baku", 
                              success:function(data){
                                  // $("#form_unit_bahan_baku_"+id).val(data);
                              }
                            });
                  }
               
                }


                function add_baris_bahan_baku() {
                  
                  var id_baris =  parseInt($("#jml_bahan_baku").val())+1;
                  var baris = '<tr id="baris_bahan_baku_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_bahan_baku(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><select onchange="show_cari('+id_baris+')" class="form-control" id="ket_jenis_bahan_baku_'+id_baris+'" name="ket_jenis_bahan_baku[]" ><option value="">Pilih Jenis</option><option value="1">Bahan Baku</option><option value="2">Barang Setengah Jadi</option></select><i id="error_jenis_bahan_baku_'+id_baris+'" style="display:none;color:red"></i></td><td><input type="text" class="form-control" placeholder="Kode Barang" onchange="cari_kode_bahan_baku('+id_baris+')" onpaste="cari_kode_bahan_baku('+id_baris+')"  onkeyup="cari_kode_bahan_baku(\''+id_baris+'\')" id="form_kode_bahan_baku_'+id_baris+'" name="kodebahan_baku[]"  readonly> <input type="hidden" id="kode_input_bahan_baku_'+id_baris+'" name="kode_input_bahan_baku[]" id="kode_input_bahan_baku_'+id_baris+'"> </td><td><input type="text" class="form-control" id="form_unit_bahan_baku_'+id_baris+'" name="unit_bahan_baku[]" readonly=""></td><td><input type="text" id="form_stock_'+id_baris+'" class="form-control" name="stock[]" readonly="" ></td><td><input type="number"  id="form_qty_bahan_baku_'+id_baris+'" class="form-control" name="qty_bahan_baku[]"  onkeyup="cek_stok(\''+id_baris+'\',this.value)" required><i id="error_stock_bahan_baku_'+id_baris+'" style="color: red"></i></td><td><input type="text" class="form-control" name="ket_bahan_baku[]" onkeyup="save_ket_bahan_baku(\''+id_baris+'\')" id="form_ket_bahan_baku_'+id_baris+'"></td></tr>';

                    $("#isi_tabel_bahan_baku").append(baris);
                    $("#jml_bahan_baku").val(id_baris);
              }

              function add_baris_scrap() {
                  var id_baris =  parseInt($("#jml_scrap").val())+1; 
                  // var baris = '<tr id="baris_scrap_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_scrap(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onkeyup="cari_kode_scrap(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" readonly=""></td><td><input type="number"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]"  onkeyup="cek_stok(\''+id_baris+'\',this.value)" required><i id="error_stock_'+id_baris+'" style="color: red"></i></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

                   var baris = '<tr id="baris_scrap_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris_scrap(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a></td><td><input type="text" id="form_kode_scrap_'+id_baris+'" placeholder="Kode Barang" onkeyup="cari_kode_scrap(\''+id_baris+'\')" class="form-control" name="kode_scrap_[]"  ><input type="hidden" name="kode_input_scrap_[]" id="kode_input_scrap_'+id_baris+'"><input type="hidden" name="id_input_scrap[]" id="id_input_scrap_'+id_baris+'"></td><td><input type="text" id="form_unit_scrap_'+id_baris+'" class="form-control" name="unit_scrap[]"  readonly=""></td><td><input type="number" id="form_qty_scrap_'+id_baris+'" class="form-control" name="qty_scrap[]" onkeyup="save_ket_scrap(\''+id_baris+'\')"required></td><td><input type="text" onkeyup="save_ket_scrap(\''+id_baris+'\')" id="form_ket_scrap_'+id_baris+'" class="form-control" name="ket_scrap[]" ></td></tr>';

                    $("#isi_tabel_scrap").append(baris);
                    $("#jml_scrap").val(id_baris);
              }
                function hapus_baris_bahan_baku(id) { 
                 
                   $.ajax({
                              type : 'POST', 
                              data : {
                                 jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                                 baris : id,
                                 kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                                 kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                                 jumlah : $("#form_qty_bahan_baku_"+id).val()
                              },
                             url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=hapus_list_bahan_baku", 
                              success:function(data){
                                  $("#baris_bahan_baku_"+id).remove();
                              }
                            });
                }
                function hapus_baris_scrap(id) {
                  $("#baris_scrap_"+id).remove();
                }
                function show_cari(id){
                  $("#form_kode_bahan_baku_"+id).val('');
                  var ket = 'add';
                  if ($("#ket_jenis_bahan_baku_"+id).val()!='') {
                    $("#form_kode_bahan_baku_"+id).removeAttr('readonly');
                  }else{
                    ket = 'remove';
                    $("#form_kode_bahan_baku_"+id).attr("readonly", "true");
                  }
                  $.ajax({
                            url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_list_bahan_baku",
                            data: { 
                              jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                              baris : id,
                              ket: ket,
                              kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                              jumlah : '0',
                              kd_barang_jadi : "<?= $kd_barang_jadi ?>"
                             },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                            }
                          });
                  
                };
                function cari_kode_bahan_baku(id) {   
                 if ($('#form_kode_bahan_baku_'+id).val()!='') {
                     $("#error_jenis_bahan_baku_"+id).hide();
                     if ($("#ket_jenis_bahan_baku_"+id).val()=='1') {
                       var url_cek_stock = "get_stock_produksi";
                     }else{
                       var url_cek_stock = "get_stock_set_jadi";
                     }
                     $('#form_kode_bahan_baku_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang,
                                  id_barang : item.id_barang
                                };
                              }))
                            }
                          });
                        },
                        select: function (event, ui) {
                             $('#form_kode_bahan_baku_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_bahan_baku_"+id).val(ui.item.kd_barang);
                             $("#id_input_bahan_baku"+id).val(ui.item.id_barang);

                              $.ajax({
                                url: "<?= base_url() ?>get_stock.php?act="+url_cek_stock,
                                data: { 
                                  kode   :  ui.item.kd_barang,
                                  jumlah : '0'
                                },
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                   $("#form_stock_"+id).val(data.stock);
                                }
                              });
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_bahan_baku_"+id).val(data);
                              }
                            });
                            $.ajax({
                              type : 'POST',
                              data : {
                                 jenis_produksi : $("#ket_jenis_bahan_baku_"+id).val(),
                                 baris : id,
                                 kode_bahan_baku : $("#kode_input_bahan_baku_"+id).val(),
                                 kd_barang_jadi : "<?= $kd_barang_jadi ?>",
                                 jumlah : $("#form_qty_bahan_baku_"+id).val(),

                              },
                             url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_list_bahan_baku", 
                              success:function(data){
                                  // $("#form_unit_bahan_baku_"+id).val(data);
                              }
                            });
 
                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
                 }else{
                    $("#error_jenis_bahan_baku_"+id).show();
                 }
                     
              }
              function cari_kode_scrap(id) {   
    
                      $('#form_kode_scrap_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang,
                                  id_barang : item.id_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_scrap_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_scrap_"+id).val(ui.item.kd_barang);
                             $("#id_input_scrap"+id).val(ui.item.id_barang);

                               $.ajax({
                                url: "<?= base_url() ?>modul/lp_gabungan/lp_gabungan_action.php?act=add_scrap_detail",
                                data: { 
                                  kode           : ui.item.kd_barang,
                                  baris          : id,                                
                                  kd_barang_jadi : "<?= $kd_barang_jadi ?>"
                                },
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                 //  $("#form_stock_"+id).val(data.stock);
                                }
                              });

                              $.ajax({
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_by_bom",
                                data: { 
                                  kode   :  ui.item.kd_barang,
                                  jumlah : '0'
                                },
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                   $("#form_stock_"+id).val(data.stock);
                                }
                              });
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_scrap_"+id).val(data);
                              }
                            });

                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
              }
              </script>
  <?php
    break;

  case "hapus_list_bahan_baku":
    $data = array('baris'          => $_POST['baris'] , 
                 'jenis_produksi' => $_POST['jenis_produksi'],
                 'kd_barang_jadi' => $_POST['kd_barang_jadi'], 
                 'kd_bahan_baku' => $_POST['kode_bahan_baku'], 
                 'jumlah'         => $_POST['jumlah']=='' ? '0' : $_POST['jumlah'], 
                 'user'           => $_SESSION['username']);
    $db->query("delete from temp_lp_gabungan_detail where kd_barang_jadi='".$_POST['kd_barang_jadi']."'
    and kd_bahan_baku='".$_POST['kode_bahan_baku']."' and baris='".$_POST['baris']."' "); 
    break;

  case "add_scrap_detail":
    $data = array('baris'          => $_POST['baris'] , 
                // 'jenis_produksi'  => $_POST['jenis_produksi'],
                 'kd_barang_jadi'  => $_POST['kd_barang_jadi'], 
                 'kd_bahan_baku'   => $_POST['kode'], 
                 'jenis_produksi'  => '3',  
                 'user'            => $_SESSION['username']); 
    // echo "select id_detail from temp_lp_gabungan_detail where baris='".$_POST['baris']."' and kd_barang_jadi='".$_POST['kd_barang_jadi']."' and user='".$_SESSION['username']."'";
   $q = $db->query("select id_detail from temp_lp_gabungan_detail where baris='".$_POST['baris']."' and kd_barang_jadi='".$_POST['kd_barang_jadi']."' and user='".$_SESSION['username']."' and jenis_produksi='3' ");
   if ($q->rowCount()==0) {
      $db->insert("temp_lp_gabungan_detail",$data);
   }else{
      foreach ($q as $k) {
         $db->update("temp_lp_gabungan_detail",$data,"id_detail",$k->id_detail);
      }
   } 
  break;

  case "add_list_bahan_baku":
   $data = array('baris'          => $_POST['baris'] , 
                 'jenis_produksi' => $_POST['jenis_produksi'],
                 'kd_barang_jadi' => $_POST['kd_barang_jadi'], 
                 'kd_bahan_baku' => $_POST['kode_bahan_baku'], 
                 'jumlah'         => $_POST['jumlah']=='' ? '0' : $_POST['jumlah'], 
                 'user'           => $_SESSION['username']);
   $q = $db->query("select id_detail from temp_lp_gabungan_detail where baris='".$_POST['baris']."' and kd_barang_jadi='".$_POST['kd_barang_jadi']."' and user='".$_SESSION['username']."' and jenis_produksi!='3' ");
   if ($q->rowCount()==0) {
      $db->insert("temp_lp_gabungan_detail",$data);
   }else{
      foreach ($q as $k) {
         $db->update("temp_lp_gabungan_detail",$data,"id_detail",$k->id_detail);
      }
   }
    break;

  case "in":
 
   $data = array(
      "nomor"    => $_POST["nomor"],
      "userid"   => $_SESSION['username'],
      "tgl_bpb"  => $_POST["tgl_bpb"],
      "project"  => $_POST["project"],
      "dept"     => $_POST["dept"],
      "jenis_produksi" => $_POST["jenis_produksi"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"  => $_POST["catatan"],
  );
  $db->insert("brgjadi",$data);
  $id          = $db->last_insert_id(); 
  $no_bpb      = GetNoLpbProduksi($id);
  $db->query("update brgjadi set no_bpb='$no_bpb' where id_produksi='$id' ");
  $no          = 1;
  foreach ($_POST['kode'] as $key => $value) {
    if ($_POST['kode_input'][$key]!='') {
        $barang      = att_barang($_POST['kode_input'][$key]);
        $data_detail = array(
                    'id_produksi'   => $id , 
                    'no_bpb'        => $no_bpb,
                    'tgl_bpb'       => $_POST["tgl_bpb"],
                    'kode'          => $_POST['kode_input'][$key],
                    'jumlah'        => $_POST['qty'][$key],
                    'row_no'        => $no 
                  );
        $data_transfer = array(
                        'no_transfer'  => $no_bpb, 
                        'dari'         => '5',
                        'ke'         => '3',
                        'id_produksi' => $id,
                        'tgl_transfer' => $_POST["tgl_bpb"],
                        'user'         => $_SESSION['username'],
                        'is_produksi'  => '1',
                        'date_created' => date("Y-m-d H:i:s")
                      ); 
        $db->insert("transfer",$data_transfer);
        $id_transfer = $db->last_insert_id();
        $db->insert("brgjadi_detail",$data_detail); 
        $id_produksi_detail = $db->last_insert_id();
        $qd = $db->query("select d.baris, d.jumlah,d.kd_bahan_baku,d.jenis_produksi
          from temp_lp_gabungan_detail d join barang b on b.kd_barang=d.kd_bahan_baku 
           where d.kd_barang_jadi='".$_POST['kode_input'][$key]."' and user='".$_SESSION['username']."' ");
        foreach ($qd as $kd) {
          //jika bahan baku
         if ($kd->jenis_produksi=='1') {
           $qc = $db->query("select id_barang,id_incoming_detail,(masuk-keluar) as jumlah,jenis_dokpab from v_rekap_stok_produksi where (masuk-keluar)>0 and kd_barang='$kd->kd_bahan_baku' ");
         
          $jumlah = $kd->jumlah;
          foreach ($qc as $kc) {  
           //  echo "$jumlah, ";
           //  print_r($kc);
             if ($jumlah>0){  
                if ($jumlah>$kc->jumlah) {
                  $jml_terpakai  = $kc->jumlah;
                  $jumlah        = $jumlah - $kc->jumlah;
                }else{
                  $jml_terpakai  = $jumlah;
                  $jumlah        = $jumlah - $jml_terpakai;
                }    
                $data_detail = array(
                   'id_produksi_detail' => $id_produksi_detail,
                   'id_incoming_detail' => $kc->id_incoming_detail,
                   'kode'               => $kd->kd_bahan_baku,
                   'jumlah'             => $jml_terpakai,
                   'row_no'             => $kd->baris
                ); 
               
                $db->insert("bahanbaku_detail",$data_detail);    
               //  update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);  
             //   update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);
            }
           // $no++;
          }
         }
         //jika scrap
         else{

         }
        }
       // ambil_bahan_baku($_POST['kode_input'][$key],$_POST['qty'][$key],$id_produksi_detail,$id_transfer); 
       // update_stock($_POST['qty'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);     
        $no++;

    }   
  }
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vbrgjadi","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vbrgjadi","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_bpb" => $_POST["no_bpb"],
      "tgl_bpb" => $_POST["tgl_bpb"],
      "project" => $_POST["project"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("vbrgjadi",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>