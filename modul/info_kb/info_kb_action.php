<?php
session_start();
include "../../inc/config.php";
session_check_json();

function info_kb_upload_logo($profile_code = '')
{
    if (empty($_FILES['logo_pt']) || !isset($_FILES['logo_pt']['error']) || $_FILES['logo_pt']['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($_FILES['logo_pt']['error'] !== UPLOAD_ERR_OK) {
        action_response(lang_text('info_kb_action_upload_failed', 'Company logo upload failed. Please try again.'));
    }

    $max_size = 2 * 1024 * 1024;
    if ($_FILES['logo_pt']['size'] > $max_size) {
        action_response(lang_text('info_kb_action_logo_max', 'Company logo maximum size is 2MB.'));
    }

    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $ext = strtolower(pathinfo($_FILES['logo_pt']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        action_response(lang_text('info_kb_action_logo_format', 'Company logo format must be JPG, PNG, GIF, or WebP.'));
    }

    if (!@getimagesize($_FILES['logo_pt']['tmp_name'])) {
        action_response(lang_text('info_kb_action_logo_invalid', 'Company logo file is not a valid image.'));
    }

    $upload_root = __DIR__ . '/../../upload';
    $upload_dir = $upload_root . '/infokb';
    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0777, true)) {
        action_response(lang_text('info_kb_action_upload_folder', 'Folder upload/infokb is not available or cannot be created by the server.'));
    }

    if (!is_writable($upload_dir)) {
        action_response(lang_text('info_kb_action_upload_permission', 'Folder upload/infokb is not writable by the server. Please check folder permission.'));
    }

    $safe_code = preg_replace('/[^a-zA-Z0-9_-]/', '_', $profile_code);
    if ($safe_code === '') {
        $safe_code = 'kb';
    }

    $filename = 'logo_pt_' . $safe_code . '_' . date('YmdHis') . '.' . $ext;
    $target = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($_FILES['logo_pt']['tmp_name'], $target)) {
        action_response(lang_text('info_kb_action_logo_save_failed', 'Company logo failed to save on the server.'));
    }

    return 'upload/infokb/' . $filename;
}

function info_kb_post($key, $default = '')
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function info_kb_form_data()
{
    return array(
        "kode" => info_kb_post("kode"),
        "company_code" => info_kb_post("company_code"),
        "business_area" => info_kb_post("business_area"),
        "default_plant_id" => info_kb_post("default_plant_id") !== '' ? info_kb_post("default_plant_id") : null,
        "purchasing_org_id" => info_kb_post("purchasing_org_id") !== '' ? info_kb_post("purchasing_org_id") : null,
        "sales_org_id" => info_kb_post("sales_org_id") !== '' ? info_kb_post("sales_org_id") : null,
        "fiscal_year_variant" => info_kb_post("fiscal_year_variant"),
        "local_currency" => info_kb_post("local_currency"),
        "nama" => info_kb_post("nama"),
        "alamat" => info_kb_post("alamat"),
        "alamat_kirim" => info_kb_post("alamat_kirim"),
        "prop" => info_kb_post("prop"),
        "kota" => info_kb_post("kota"),
        "postal_code" => info_kb_post("postal_code"),
        "country" => info_kb_post("country", "ID"),
        "npwp" => info_kb_post("npwp"),
        "tax_registration_no" => info_kb_post("tax_registration_no"),
        "nomor_nib" => info_kb_post("nomor_nib"),
        "nomor_api" => info_kb_post("nomor_api"),
        "telp" => info_kb_post("telp"),
        "fax" => info_kb_post("fax"),
        "email" => info_kb_post("email"),
        "website" => info_kb_post("website"),
        "kode_ceisa" => info_kb_post("kode_ceisa"),
        "kantor_pengawas" => info_kb_post("kantor_pengawas"),
        "jenis_fasilitas" => info_kb_post("jenis_fasilitas"),
        "skepkb" => info_kb_post("skepkb"),
        "tglskep" => info_kb_post("tglskep") !== '' ? info_kb_post("tglskep") : null,
        "pbob" => info_kb_post("pbob"),
        "cdob" => info_kb_post("cdob"),
        "bank" => info_kb_post("bank"),
        "bank_name" => info_kb_post("bank_name"),
        "bank_account_name" => info_kb_post("bank_account_name"),
        "swift_code" => info_kb_post("swift_code"),
        "bank_currency" => info_kb_post("bank_currency", "IDR"),
        "rek1" => info_kb_post("rek1"),
        "rek2" => info_kb_post("rek2"),
    );
}

switch ($_GET["act"]) {
  case "in":
  $logo_pt = info_kb_upload_logo(isset($_POST["kode"]) ? $_POST["kode"] : '');

  $data = info_kb_form_data();
  if ($logo_pt !== '') {
      $data["logo"] = $logo_pt;
  }
  
  
  
   
    $in = $db->insert("infokb",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("infokb","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("infokb","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
   $logo_pt = info_kb_upload_logo(isset($_POST["id"]) ? $_POST["id"] : '');
    
   $data = info_kb_form_data();
   if ($logo_pt !== '') {
      $data["logo"] = $logo_pt;
   }
   
   
   

    
    
    $up = $db->update("infokb",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
