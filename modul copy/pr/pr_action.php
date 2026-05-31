<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "verifikasi":
    $no_ro = $_POST['id'];
  // echo "<pre>";
  // print_r($_POST);
  $q = $db->query("select id from roin_detail where no_ro='$no_ro' ");
  foreach ($q as $k) {
     $db->query("update roin_detail_compare set acc='0' where id_detail='$k->id' ");
     $qq = $db->query("select * from roin_detail_compare where id_detail='$k->id' "); 
     foreach ($qq as $kk) {
        $id_detail = $_POST['barang_'.$k->id];
        $db->query("update roin_detail_compare set acc='1' where id='$id_detail' "); 
     }
     
  }
  $db->query("update roin set status='2' where no_ro='$no_ro' "); 
   action_response("");
    break;

  case "cari_vendor":
    $kode = $_POST['term'];
    $q = $db->query("select kode_pemasok,nama from pemasok where kode_pemasok like '%$kode%' or nama like '%$kode%' ");
    $res = array();
   foreach ($q as $k) {
      $h['kode_pemasok'] = $k->kode_pemasok;
      $h['nama'] = $k->nama;
      $res[] = $h;
   }
   echo json_encode($res); 
    break;
   
  case "compare":
  $no_ro = $_POST['id'];
  // echo "<pre>";
  // print_r($_POST);
  $q = $db->query("select id from roin_detail where no_ro='$no_ro' ");
  foreach ($q as $k) {
     $db->query("delete from roin_detail_compare where id_detail='$k->id' "); 
     foreach ($_POST['vendor'][$k->id] as $key => $value) {
        $pem = explode("-", $_POST['vendor'][$k->id][$key]);
        $pemasok = $pem[0];
        $data = array('id_detail' => $k->id, 
                     'pemasok'    => $pemasok,
                     'harga'      => $_POST['harga'][$k->id][$key],
                     'ket'        => $_POST['ket'][$k->id][$key],
                     'userid'     => $_SESSION["username"]);
       // print_r($data);
        $db->insert("roin_detail_compare",$data);
     }
  }
   action_response("");
    
  break;

  case "in":
  $data = array(  
      "tgl_ro"   => $_POST["tgl_ro"],
      "dept"     => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"  => $_POST["catatan"],
  );
  $in     = $db->insert("roin",$data);
  //echo $db->getErrorMessage();
  $id     = $db->last_insert_id(); 
  $no_ro  = GetNextPRNo($id,5);
  $nomor  = getUjung($id,9);
  $data   = array( 
      "no_ro"    => $no_ro,
      "nomor"    => $nomor,
      "tgl_ro"   => $_POST["tgl_ro"],
      "dept"     => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"  => $_POST["catatan"],
      "userid"   => $_SESSION["username"]
  ); 
//  print_r($data);
  $db->update("roin",$data,"id",$id); 
  $db->query("delete from roin_detail where no_ro='$no_ro' "); 
  foreach ($_POST['kode_input'] as $key => $value) {
     $data_detail = array('nomor'   => $nomor , 
                          'no_ro'   => $no_ro,
                         // 'id_ro'   => $id,
                          'tgl_ro'  => $_POST["tgl_ro"],
                          'kode'    => $_POST['kode_input'][$key],
                          'jumlah'  => $_POST['jumlah'][$key],
                          'ket'     => $_POST['ket'][$key]);
    // print_r($data_detail);
     $db->insert("roin_detail",$data_detail);  
  }
 
    action_response($db->getErrorMessage());
    break;

   case "show_detail": 
  $id = $_POST['id'];
  ?>
  <table class="table">
    <thead>
      <tr>
        <th>No</th>
        <th>No PR</th>
        <th>Tanggal PR</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Qty</th>
        <th>Ket</th>
      </tr>
    </thead>
    <tbody>
  <?php
  $qty=0;
  $harga =0;
  $nilai=0;
  $berat =0;
  $no=1;
  $q = $db->query("select rd.*,b.nm_barang from roin_detail rd left join barang b on b.kd_barang=rd.kode
  where rd.no_ro='$id' ");
  foreach ($q as $k) { 
    echo "<tr>
            <td>$no</td>
            <td>$k->no_ro</td>
            <td>$k->tgl_ro</td>
            <td>$k->kode</td>
            <td>$k->nm_barang</td>
            <td style='text-align: right'>$k->jumlah</td>
            <td style='text-align: right'>$k->ket</td>
       
          </tr>";
          $qty = $qty +$k->jumlah;
          // $harga = $harga + $k->harga;
          // $nilai = $nilai + $k->nilai;
          // $berat = $berat + $k->berat;
          $no++;
  }
  ?>
    <tr>
      <td colspan="4" style="text-align: center">Jumlah</td>
      <td colspan="2" style="text-align: right"><?= number_format($qty,2) ?></td>
    
    </tr>
      </tbody>
  </table>
  <?php
    break;

  case "delete":
    
    
    
    $db->delete("roin","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("roin","no_ro",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_ro" => $_POST["no_ro"],
      "tgl_ro" => $_POST["tgl_ro"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"=>$_POST["catatan"],
   );

    $db->query("delete from roin_detail where no_ro='".$_POST["no_ro"]."' "); 
  foreach ($_POST['kode_input'] as $key => $value) {
     $data_detail = array('nomor'   => $_POST["nomor"],
                          'no_ro'   => $_POST["no_ro"],
                          'tgl_ro'  => $_POST["tgl_ro"],
                          'kode'    => $_POST['kode_input'][$key],
                          'jumlah'  => $_POST['jumlah'][$key],
                          'ket'     => $_POST['ket'][$key]);
     $db->insert("roin_detail",$data_detail); 

  }
   
   
   

    
    
    $up = $db->update("roin",$data,"no_ro",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>