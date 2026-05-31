<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  if (!is_dir("../../../upload/data_user")) {
              mkdir("../../../upload/data_user");
            }
  
  $data = array(
      "first_name" => $_POST["first_name"],
      "last_name" => $_POST["last_name"],
      "username" => $_POST["username"],
      "password" => $_POST["password"],
      "email" => $_POST["email"],
      "group_level" => $_POST["group_level"],
  );
  
  
                    if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto_user"]["name"]) ) {

              echo "pastikan file yang anda pilih png|jpg|jpeg|gif";
              exit();

            } else {
$db->compressImage($_FILES["foto_user"]["type"],$_FILES["foto_user"]["tmp_name"],"../../../upload/data_user/",$_FILES["foto_user"]["name"],300,);
            $foto_user = array("foto_user"=>$_FILES["foto_user"]["name"]);
              $data = array_merge($data,$foto_user);
            }
  
   
          if(isset($_POST["aktif"])=="on")
          {
            $aktif = array("aktif"=>"1");
            $data=array_merge($data,$aktif);
          } else {
            $aktif = array("aktif"=>"0");
            $data=array_merge($data,$aktif);
          }
    $in = $db->insert("sys_users",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    $db->deleteDirectory("../../../upload/data_user/".$db->fetch_single_row("sys_users","id",$_POST["id"])->foto_user);
    
    $db->delete("sys_users","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sys_users","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "first_name" => $_POST["first_name"],
      "last_name" => $_POST["last_name"],
      "username" => $_POST["username"],
      "password" => $_POST["password"],
      "email" => $_POST["email"],
      "group_level" => $_POST["group_level"],
   );
   
   
                         if(isset($_FILES["foto_user"]["name"])) {
                        if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["foto_user"]["name"]) ) {

              echo "pastikan file yang anda pilih gambar";
              exit();

            } else {
$db->compressImage($_FILES["foto_user"]["type"],$_FILES["foto_user"]["tmp_name"],"../../../upload/data_user/",$_FILES["foto_user"]["name"],300,);
              $db->deleteDirectory("../../../upload/data_user/".$db->fetch_single_row("sys_users","id",$_POST["id"])->foto_user);
              $foto_user = array("foto_user"=>$_FILES["foto_user"]["name"]);
              $data = array_merge($data,$foto_user);
            }

                         }
   

    
          if(isset($_POST["aktif"])=="on")
          {
            $aktif = array("aktif"=>"1");
            $data=array_merge($data,$aktif);
          } else {
            $aktif = array("aktif"=>"0");
            $data=array_merge($data,$aktif);
          }
    
    $up = $db->update("sys_users",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>