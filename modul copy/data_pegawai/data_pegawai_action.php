<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  if (!is_dir("../../upload/data_pegawai")) {
              mkdir("../../upload/data_pegawai"); 
            }
  $data = array(
      "nik" => $_POST["nik"],
      "npwp" => $_POST["npwp"],
      "namaPegwai" => $_POST["namaPegwai"],
      "kelamin" => $_POST["kelamin"],
      "agama" => $_POST["agama"], 
      "noHp" => $_POST["noHp"],
      "email" => $_POST["email"],
      "alamat" => $_POST["alamat"],
      "noRek" => $_POST["noRek"],
      "bank" => $_POST["bank"],
      "idProvinsi" => $_POST["idProvinsi"],
      "idKota" => $_POST["idKota"],
      "idKecamatan" => $_POST["idKecamatan"],
  );
  
  
  
                    if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto"]["name"]) ) {

              echo "pastikan file yang anda pilih png|jpg|jpeg|gif";
              exit();

            } else {
      move_uploaded_file($_FILES["foto"]["tmp_name"], "../../upload/data_pegawai/".$_FILES['foto']['name']);

            $foto = array("foto"=>$_FILES["foto"]["name"]);
              $data = array_merge($data,$foto);
            }
   
    $in = $db->insert("h_pegawai",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    $db->deleteDirectory("../../upload/data_pegawai/".$db->fetch_single_row("h_pegawai","idPegawai",$_POST["id"])->foto);
    
    
    $db->delete("h_pegawai","idPegawai",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("h_pegawai","idPegawai",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nik" => $_POST["nik"],
      "npwp" => $_POST["npwp"],
      "namaPegwai" => $_POST["namaPegwai"],
      "kelamin" => $_POST["kelamin"],
      "agama" => $_POST["agama"],
      "noHp" => $_POST["noHp"],
      "email" => $_POST["email"],
      "alamat" => $_POST["alamat"],
      "noRek" => $_POST["noRek"],
      "bank" => $_POST["bank"],
      "idProvinsi" => $_POST["idProvinsi"],
      "idKota" => $_POST["idKota"],
      "idKecamatan" => $_POST["idKecamatan"],
   );
   
   
   
                         if(isset($_FILES["foto"]["name"])) {
                        if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto"]["name"]) ) {

              echo "pastikan file yang anda pilih gambar";
              exit();
 
            } else {
      move_uploaded_file($_FILES["foto"]["tmp_name"], "../../upload/data_pegawai/".$_FILES['foto']['name']);

              $db->deleteDirectory("../../upload/data_pegawai/".$db->fetch_single_row("h_pegawai","idPegawai",$_POST["id"])->foto);
              $foto = array("foto"=>$_FILES["foto"]["name"]);
              $data = array_merge($data,$foto);
            }

                         }

    
    
    $up = $db->update("h_pegawai",$data,"idPegawai",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>