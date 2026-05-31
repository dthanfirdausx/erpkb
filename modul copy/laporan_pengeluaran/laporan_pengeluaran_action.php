<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
    case "detail_bahan_baku":
   $tgl_akhir = $_POST['tgl_sj'];
   $tgl_awal = date_create($tgl_akhir)->modify('-120 days')->format('Y-m-d');
   $kode_bj = $_POST['kode_bj'];
   $wh_tgl = "and (p.tgl_bpb between '$tgl_awal' and '$tgl_akhir') ";
  // $wh_tgl = " and p.tgl_bpb < '$tgl_akhir' ";
    echo "<table style='width:100%' border='1'>
                              <thead>
                               <tr>
                                <th class='text-center'>No</th>
                                <th class='text-center'>Kode / Nama Barang</th>
                                <th class='text-center'>No Dokpab</th>
                                <th class='text-center'>No Aju</th>
                                <th class='text-center'>Jenis Dokpab</th>
                                <th class='text-center'>Tgl Dokpab</th>
                                <th class='text-center'>Qty</th>
                               </tr>
                              </thead>
                             ";
                             $no2=1;
   $q = $db->query("select d.kodebb,d.jumlah from bom_detail d join bom b on b.id=d.id_bom where b.kodebj='$kode_bj' ");
   if ($q->rowCount()>0) {
     $no2=1;
     foreach ($q as $k) {
     $qq = $db->query("select b.nm_barang,p.tgl_dokpab,d.kode,d.jumlah, p.no_dokpab,p.jenis_dokpab,p.no_aju from pemasukan_detail d join pemasukan p on p.no_bpb=d.no_bpb join barang b on b.kd_barang=d.kode where d.kode='$k->kodebb' $wh_tgl and p.jenis_dokpab!='Saldo Awal' limit 1 ");
     foreach ($qq as $kk) { 
       echo "<tr>
              <td style='padding:3px'>$no2</td>
              <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
              <td style='padding:3px'>$kk->no_dokpab</td>
              <td style='padding:3px'>$kk->no_aju</td>
              <td style='padding:3px'>$kk->jenis_dokpab</td>
              <td style='padding:3px'>$kk->tgl_dokpab</td>
              <td style='padding:3px'>$kk->jumlah</td>
           </tr>";
     }
     
      $no2++; 
     }
   }else{
      $qq = $db->query("select b.nm_barang,p.tgl_dokpab,d.kode,d.jumlah, p.no_dokpab,p.jenis_dokpab,p.no_aju from pemasukan_detail d join pemasukan p on p.no_bpb=d.no_bpb join barang b on b.kd_barang=d.kode where d.kode='$kode_bj' $wh_tgl and p.jenis_dokpab!='Saldo Awal'  limit 1 ");
       $no2=1;
     foreach ($qq as $kk) {
       echo "<tr>
              <td style='padding:3px'>$no2</td>
              <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
              <td style='padding:3px'>$kk->no_dokpab</td>
              <td style='padding:3px'>$kk->no_aju</td>
              <td style='padding:3px'>$kk->jenis_dokpab</td>
              <td style='padding:3px'>$kk->tgl_dokpab</td>
              <td style='padding:3px'>$kk->jumlah</td>
           </tr>";
     }
   }
   
//echo date_create($date)->modify('-30 days')->format('Y-m-d');
  
                             // $qq = $db->query("select * from v_detail_bahan_baku_produksi where id_produksi_detail='".$_POST['id_produksi_detail']."' ");
                             // foreach ($qq as $kk) {
                             //    echo "<tr>
                             //      <td style='padding:3px'>$no2</td>
                             //      <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
                             //      <td style='padding:3px'>$kk->no_dokpab</td>
                             //      <td style='padding:3px'>$kk->no_aju</td>
                             //      <td style='padding:3px'>$kk->jenis_dokpab</td>
                             //      <td style='padding:3px'>$kk->tgl_dokpab</td>
                             //      <td style='padding:3px'>$kk->jumlah</td>
                             //    </tr>";
                             //    $no2++; 
                             // }
                             echo"</table>";
  break;
  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
  );
  
  
  
   
    $in = $db->insert("bahan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("bahan","no_lap",$_GET["id"]);
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
   );
   
   
   

    
    
    $up = $db->update("bahan",$data,"no_lap",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>