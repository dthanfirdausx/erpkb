<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
   case "in":
    
 // echo "<pre>";
  $thn = date("Y",strtotime($_POST["tgl_bpb"]));  
  $no_bpb = getNoBPB($thn);
 // echo "$no_bpb";
  $nomor = get_nomor("pemasukan","id"); 
  $data = array(
      "no_bpb" => $no_bpb,
      "nomor" => $nomor,
      "tgl_bpb" => $_POST["tgl_bpb"],
      "nopo" => $_POST["nopo"],
      "pemasok" => $_POST["pemasok"],
      "no_invoice" => $_POST["no_invoice"],
      "tgl_invoice" => $_POST["tgl_invoice"],
      "no_do" => $_POST["no_do"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "catatan" => $_POST["catatan"],
      "jenis_dokpab" => $_POST["jenisbcmasuk_jenis_dokumen"],
      "kd_catdet" => $_POST["kd_catdet"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "efaktur" => $_POST["efaktur"],
      "tgl_efaktur" => $_POST["tgl_efaktur"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
       'userid' => $_SESSION['username'], 
  );
   if ($_POST["tgl_efaktur"]=='') {
     unset($data['tgl_efaktur']);
   }
$db->insert("pemasukan",$data);  
//echo $db->getErrorMessage();
 // print_r($_SESSION); 
 // echo $_SESSION['username'];
// print_r($data)
 simpan_log("Input Dokumen ".$_POST["jenisbcmasuk_jenis_dokumen"]." dengan No Dokpab ".$_POST["no_dokpab"]." No Aju ".$_POST["no_aju"],$_SESSION['username']);
  
 $db->query("delete from pemasukan_detail where no_bpb='$no_bpb' ");
   $no=1;
   foreach ($_POST['kode'] as $key => $value) {
       $barang = att_barang($_POST['kode_input'][$key]);
      $data_detail = array('nomor' => $nomor , 
                    'no_bpb' => $no_bpb,
                    'tgl_bpb' => $_POST["tgl_bpb"],
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => $_POST['jumlah'][$key],
                    'harga' => $_POST['harga'][$key],
                    'valuta' => $_POST['valuta'],
                    'nilai' => $_POST['nilai'][$key],
                    'unit' => $_POST['unit'][$key],
                    'berat' => $_POST['berat'][$key], 
                    'no_urut' => $no,                    
                    'no_aju' => $_POST['no_aju'],
                    'tgl_aju' => $_POST['tgl_aju'],
                    'tgl_masuk' => $_POST['tgl_aju'],
                    'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    'no_dokpab' => $_POST['no_dokpab'],
                    'tgl_dokpab' => $_POST['tgl_dokpab'],
                    'lokasi' => $_POST['lokasi'][$key]
                  );
     //  update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);
        $db->insert("pemasukan_detail",$data_detail); 
       // $data_stock_incoming = array('kd_barang' => $_POST['kode_input'][$key], 
       //               'no_aju'    => $_POST['no_aju'],
       //               'tgl_aju'   => $_POST['tgl_aju'],
       //               'tgl_masuk' => $_POST['tgl_aju'],
       //               'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
       //               'tgl_dokpab' => $_POST['tgl_dokpab'],
       //               'no_dokpab'  => $_POST['no_dokpab'],
       //               'jumlah'     => $_POST['jumlah'][$key],
       //               'harga'      => $_POST['harga'][$key],
       //               'valuta'     => $_POST['valuta'],
       //               'nilai'      => $_POST['nilai'][$key],
       //               'no_urut'    => $no,
       //               'status'     => '10',
       //               'no_ref'     => $no_bpb,
       //               'date_created' => date("Y-m-d H:i:s"));
       
       // $db->insert("stock_incoming",$data_stock_incoming); 
      // print_r($data_detail);
      // print_r($data_stock_incoming);

      $no++;
   }

  
  
   
  //  $in = $db->insert("pemasukan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("tmp_pemasukan1","no_bpb",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("tmp_pemasukan1","no_bpb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
 case "up":
   $thn = date("Y",strtotime($_POST["tgl_bpb"]));  
  $no_bpb = getNoBPB($thn);
 // echo "$no_bpb";
  $nomor = get_nomor("pemasukan","id"); 
   $data = array(
     "nomor" => $nomor,
      "no_bpb" => $no_bpb,
      "tgl_bpb" => $_POST["tgl_bpb"],
      "nopo" => $_POST["nopo"],
      "pemasok" => $_POST["pemasok"],
      "no_invoice" => $_POST["no_invoice"],
      "tgl_invoice" => $_POST["tgl_invoice"],
      "no_do" => $_POST["no_do"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "catatan" => $_POST["catatan"],
      "jenis_dokpab" => $_POST["jenisbcmasuk_jenis_dokumen"],
      "kd_catdet" => $_POST["kd_catdet"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "efaktur" => $_POST["efaktur"],
      "tgl_efaktur" => $_POST["tgl_efaktur"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
      'userid' => $_SESSION['username'],
   );
   if ($_POST["tgl_efaktur"]=='') {
     unset($data['tgl_efaktur']);
   }

   simpan_log("Update Dokumen ".$_POST["jenisbcmasuk_jenis_dokumen"]." dengan No Dokpab ".$_POST["no_dokpab"]." No Aju ".$_POST["no_aju"],$_SESSION['username']);

  // $nomor = get_nomor("pemasukan","id");
   $up = $db->insert("pemasukan",$data); 
   $db->query("delete from pemasukan_detail where no_bpb='".$no_bpb."' ");
 //  $db->query("delete from stock_incoming where no_ref='".$no_bpb."' "); 
   $no=1;
   foreach ($_POST['kode'] as $key => $value) { 
      $barang = att_barang($_POST['kode_input'][$key]);
      // if (isset($_POST['jumlah_lama'][$key])) {
      //   update_stock($_POST['jumlah_lama'][$key],'minus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']); 
      // }
      update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);  
      $data_detail = array(
                    'nomor' => $nomor,
                    'no_bpb' => $no_bpb, 
                    'tgl_bpb' => $_POST["tgl_bpb"],
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => $_POST['jumlah'][$key],
                    'harga' => $_POST['harga'][$key],
                    'valuta' => $_POST['valuta'],
                    'nilai' => $_POST['nilai'][$key],
                    'unit' => $_POST['unit'][$key],
                    'berat' => $_POST['berat'][$key],
                    'no_urut' => $no,
                    'no_aju' => $_POST['no_aju'],
                    'tgl_aju' => $_POST['tgl_aju'],
                    'tgl_masuk' => $_POST['tgl_aju'],
                    'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    'no_dokpab' => $_POST['no_dokpab'],
                    'tgl_dokpab' => $_POST['tgl_dokpab'],
                    'lokasi' => $_POST['lokasi'][$key]
                   );
       // $data_stock_incoming = array('kd_barang' => $_POST['kode_input'][$key], 
       //               'no_aju'    => $_POST['no_aju'],
       //               'tgl_aju'   => $_POST['tgl_aju'],
       //               'tgl_masuk' => $_POST['tgl_aju'],
       //               'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
       //               'no_dokpab'  => $_POST['no_dokpab'],
       //               'jumlah'     => $_POST['jumlah'][$key],
       //               'harga'      => $_POST['harga'][$key],
       //               'valuta'     => $_POST['valuta'],
       //               'nilai'      => $_POST['nilai'][$key],
       //               'no_urut'    => $no,
       //               'no_ref'     => $_POST["no_bpb"],
       //               'date_created' => date("Y-m-d H:i:s"));
        $db->insert("pemasukan_detail",$data_detail); 
       // $db->insert("stock_incoming",$data_stock_incoming);  
       // print_r($data_detail);
      $no++;
   }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>