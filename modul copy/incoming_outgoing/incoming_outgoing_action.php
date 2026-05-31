<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {


  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
  );
    $data_produksi = array(  
      "tgl_spb"     => $_POST["tgl_spb"],
      "tgl_lpb"     => $_POST["tgl_spb"],
      "dept"        => $_POST["dept"],
      "dari"        => "INCOMING",
      "name_ppc"    => $_POST["name_ppc"],
      "catatan"     => $_POST["catatan"],
      "userid"      => $_SESSION['username'],
      "user_trt"    => $_SESSION['username'],
      "terima"      => '0'
    );
 
    $in     = $db->insert("incoming_outgoing",$data);
 //   $db->getErrorMessage();
    $id     = $db->last_insert_id(); 
  //  echo "$id";
    $in     = $db->insert("outgoing_terima",$data_produksi);
   // echo $db->getErrorMessage();;
    $id_produksi_terima = $db->last_insert_id();   
    $no_spb = GetNoSpbOutgoing($id,5); 
    $no_lpb = GetNoLpbOutgoing($id_produksi_terima,5); 
    $nomor  = getUjung($id,10); 
    $nomor_produksi  = getUjung($id_produksi_terima,10);
    $db->query("update incoming_outgoing set no_spb='$no_spb',nomor='$nomor' where id='$id' ");
    $db->query("update outgoing_terima set no_spb='$no_spb',nomor='$nomor_produksi',
                no_lpb='$no_lpb',terima='0' where id='$id_produksi_terima' ");
    $no=1; 
    foreach ($_POST['kode_input'] as $key => $value) {
        $jumlah = $_POST['qty'][$key];
        $data_pem = $db->query("SELECT * FROM vpickingready where kd_barang='".$_POST['kode_input'][$key]."'  order by tgl_masuk,no_dokpab");
        foreach ($data_pem as $kk) {
            $kode        = $_POST['kode_input'][$key]; 
            $noaju       = $kk->no_aju;
            $tglaju      = $kk->tgl_aju;
            $tglmasuk    = $kk->tgl_masuk;
            $jenisdokpab = $kk->jenis_dokpab;
            $nodokpab    = $kk->no_dokpab;
            $tgldokpab   = $kk->tgl_dokpab;
            $stok        = $kk->stock;
            $status      = 20;
            $nourut      = $kk->no_urut;
            $noref       = $no_spb;
            $nourutref   = $no;            
            if($jumlah > 0) { 
              $temp     = $jumlah;
              $jumlah   = $jumlah - $stok;
              if ($jumlah<0) {
                $stok=$temp;
              }
              $db->query("INSERT INTO stock_incoming (kd_barang,no_aju,tgl_aju,tgl_masuk,jenis_dokpab,
                no_dokpab,tgl_dokpab,jumlah,status,no_urut,no_ref,no_urutref) VALUES 
                ('".$kode."','".$noaju."','".$tglaju."','".$tglmasuk."','".$jenisdokpab."','".$nodokpab."',
                '".$tgldokpab."','".$stok."','".$status."','".$nourut."','".$noref."','".$nourutref."')");
              update_stock($stok,"minus",$jenisdokpab,'incoming',$kode,$_SESSION['username']);         
            }
        }
        $data_detail_produksi = array(
                          'nomor'    => $nomor_produksi ,  
                          'no_lpb'   => $no_lpb,
                          'dari'     => 'INCOMING',
                          'tgl_lpb'  => $_POST["tgl_spb"],
                          'kode'     => $_POST['kode_input'][$key],
                          'jumlah'   => $_POST['qty'][$key],
                          'row_no'   => $no,
                          'ket'      => $_POST['ket'][$key]);

        $data_detail  = array('nomor'    => $nomor ,  
                          'no_spb'       => $no_spb,
                          'jenis_dokpab' => $jenisdokpab,
                          'tgl_spb'      => $_POST["tgl_spb"],
                          'kode'         => $_POST['kode_input'][$key],
                          'jumlah'       => $_POST['qty'][$key],
                          'row_no'       => $no,
                          'ket'          => $_POST['ket'][$key]);

        $db->insert("incoming_outgoing_detail",$data_detail);
        $db->insert("outgoing_terima_detail",$data_detail_produksi);
        $no++;
    }
     action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("incoming_outgoing","no_spb",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("incoming_outgoing","no_spb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("incoming_outgoing",$data,"no_spb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>