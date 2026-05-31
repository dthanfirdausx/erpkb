<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php";
session_check_json();
switch ($_GET["act"]) {
 
  case "import_bom": 
  error_reporting(0);
   unlink("../../upload/import_data/".$_FILES['fileupload']['name']);
   move_uploaded_file($_FILES['fileupload']['tmp_name'], "../../upload/import_data/".$_FILES['fileupload']['name']);
   $Reader = new SpreadsheetReader("../../upload/import_data/".$_FILES['fileupload']['name']); 
  $Sheets = $Reader->Sheets(); 
  $data = array();
  $no=1;
  //echo "<pre>"; 
  $sukses=0; 
  $duplikat=0; 
  $val = array(); 
  $ada_data = 0;
  $db->query("truncate bom_upload_tmp"); 
  $q = $db->query("show columns from bom_upload_tmp");
  foreach ($q as $k) {
     $kol[] = $k->Field;
  }
  // unset($kol[0]);
  // unset($kol[35]);
  $datax = array(); 
  $kolom = implode(',', $kol);
  $query_insert = "insert into bom_upload_tmp ($kolom) values ";
 
 // $data_detail  = array();
   foreach ($Sheets as $Index => $Name)
  {
    //echo "$Index,";
    $Reader->ChangeSheet($Index);
    if ($Index==0) {
      $mulai = false;
      
      $i=0; 
     // $dat = array();
     // print_r($Reader);
      foreach ($Reader as $r)  
      {
        // $dat = array();
       // print_r($r);

        if (count($r)>0 && $r[1]!='' && $i>0) {
          $x=0; 
           foreach ($r as $kk => $vv) {    
            if ($kk<=4) {
               $dat[$x] = "'".addslashes($vv)."'"; 
               $x++;
            }
            
          }
         // print_r($r);
        }
        //unset($dat); 
        $i++;
         // if ($r[0]!='') {
          
         // }
        //print_r($dat);
        if (isset($dat)) {
         $datax[] = "(".implode(",", $dat).")"; 
        }
        unset($dat); 
      } 
    //  if (count($dat)>1) {  
        
    //   }
      
        //$isi = implode(",", $datax);
  }
}
 $isi = implode(",", $datax);
 $query_insert .= $isi;
 $db->query($query_insert);
// echo "$query_insert";
 $db->query("update bom_upload_tmp set qty=replace(qty,',','.')");
 // $q = $db->query("select bb.brg_jadi,b.kodebj from bom_upload_tmp bb right join bom b on b.kodebj=bb.brg_jadi group by bb.brg_jadi")
  $q = $db->query("select bb.kode_brg_jadi,b.kodebj,b.id as id_bom,ba.nm_barang,ba.kd_kategori,ba.satuan from bom_upload_tmp bb 
                   left join bom b on b.kodebj=bb.kode_brg_jadi join barang ba on ba.kd_barang=bb.kode_brg_jadi 
                   group by bb.kode_brg_jadi "); 
    foreach ($q as $k) {
      if ($k->kodebj!='') {
         $db->query("delete from bom_detail where id_bom='$k->id_bom'  "); 
         $qq = $db->query("select b.kode_bahan_baku,b.qty,ba.nm_barang,ba.satuan 
                       from bom_upload_tmp b join barang ba on ba.kd_barang=b.kode_bahan_baku  
                       where b.kode_brg_jadi='$k->kode_brg_jadi' ");
         foreach ($qq as $kk) {
         //  $qb = $db->query("select * from bom_detail id_bom='$k->id_bom' and and kodebb='$kk->kd_bahan_baku'  ")
         $data_detail = array('id_bom' => $k->id_bom ,  
                          'kodebb' => $kk->kode_bahan_baku,
                          'nm_barang' => $kk->nm_barang,
                          'satuan' => $kk->satuan,
                          'jumlah' => $kk->qty,
                          'baru'   => '1'
                    ); 
         $db->insert("bom_detail",$data_detail); 
       }
      }else{ 
        //kodebj  nm_barang satuan  jumlah  status  tgl_input
        $data_bom = array('kodebj' => $k->kode_brg_jadi ,
                          'nm_barang' => $k->nm_barang,
                          'satuan' => $k->satuan,
                          'jumlah' => '1',
                          'status' => '1',
                          'tgl_input' => date("Y-m-d H:i:s") );
        $db->insert("bom",$data_bom);
        $id_bom = $db->last_insert_id();
        $db->query("delete from bom_detail where id_bom='$id_bom'  "); 
        $qq = $db->query("select b.kode_bahan_baku,b.qty,ba.nm_barang,ba.satuan 
                       from bom_upload_tmp b join barang ba on ba.kd_barang=b.kode_bahan_baku  
                       where b.kode_brg_jadi='$k->kode_brg_jadi' ");
        foreach ($qq as $kk) {
           $data_detail = array('id_bom' => $id_bom ,  
                          'kodebb' => $kk->kode_bahan_baku,
                          'nm_barang' => $kk->nm_barang,
                          'satuan' => $kk->satuan,
                          'jumlah' => $kk->qty,
                          'baru'   => '1'
                    ); 
           $db->insert("bom_detail",$data_detail);
        }
        
      }    
    }
  
  // else{
  //   $q = $db->query("select kd_barang,nm_barang from barang where kd_barang='$' ")
  // }
  

 $res['pesan'] = "Data Sukses Di Import";
 echo json_encode($res);
    break;

  case "in":
    
  
  
  
  $data = array(
      "kodebj" => $_POST["kodebj"],
      "nm_barang" => $_POST["nm_barang"],
      "satuan" => $_POST["satuan"],
      "jumlah" => $_POST["jumlah"],
      'tgl_input' => date("Y-m-d H:i:s"),
      'user_id' => $_SESSION['username']
  );
  $in = $db->insert("bom",$data);
  $id_bom = $db->last_insert_id();
  $db->query("delete from bom_detail where id_bom='$id_bom' ");
  $no=1;
  foreach ($_POST['kode'] as $key => $value) {
     $data_detail = array('id_bom' => $id_bom , 
                          'kodebb' => $_POST['kode_input'][$key],
                          'jumlah' => $_POST['qty'][$key],
                          'status' => $_POST['ket'][$key],
                          'tgl_input' => date("Y-m-d H:i:s"),
                          'user_id' => $_SESSION['username']);
     $db->insert("bom_detail",$data_detail);
  }
 
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("bom","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("bom","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kodebj" => $_POST["kodebj"],
      "nm_barang" => $_POST["nm_barang"],
      "satuan" => $_POST["satuan"], 
      "jumlah" => $_POST["jumlah"],
   );
    $up = $db->update("bom",$data,"id",$_POST["id"]);
    $id_bom = $_POST["id"];
    $db->query("delete from bom_detail where id_bom='$id_bom' ");
    $no=1; 
    foreach ($_POST['kode'] as $key => $value) {
       $data_detail = array('id_bom' => $id_bom , 
                            'kodebb' => $_POST['kode_input'][$key],
                            'jumlah' => $_POST['qty'][$key],
                            'status' => $_POST['ket'][$key],
                            'tgl_input' => date("Y-m-d H:i:s"),
                            'user_id' => $_SESSION['username']);
       $db->insert("bom_detail",$data_detail);
       //print_r($data_detail); 
     //  echo $db->last_insert_id();
    }

    
  
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>