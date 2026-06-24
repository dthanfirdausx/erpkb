<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();

function barang_import_json($status, $message = "", $extra = array()) {
  header("Content-Type: application/json");
  $row = array("status" => $status);
  if ($message !== "") {
    $row["error_message"] = $message;
    $row["message"] = $message;
  }
  foreach ((array) $extra as $key => $value) {
    $row[$key] = $value;
  }
  echo json_encode(array($row));
  exit();
}

function barang_import_value($sheet, $col, $row) {
  $value = $sheet->getCell($col.$row)->getValue();
  if ($value instanceof PHPExcel_RichText) {
    $value = $value->getPlainText();
  }
  return trim((string) $value);
}

function barang_import_upper($value) {
  return strtoupper(trim((string) $value));
}

function barang_import_status($value) {
  $value = strtoupper(trim((string) $value));
  if ($value === "") {
    return 1;
  }
  return in_array($value, array("1", "Y", "YA", "YES", "TRUE", "AKTIF", "ACTIVE"), true) ? 1 : 0;
}

function barang_import_lookup_id($db, $sql, $params) {
  $row = $db->fetch($sql, $params);
  return $row ? (int) $row->id : null;
}

function barang_import_uom($db, $value) {
  $value = trim((string) $value);
  if ($value === "") {
    return "";
  }
  $row = $db->fetch("SELECT jenis FROM satuan WHERE jenis=? OR kode=? OR nama=? LIMIT 1", array($value, $value, $value));
  return $row ? $row->jenis : "";
}

switch ($_GET["act"]) {
  case "template_import":
    $initial = ob_get_level();
    ob_start();
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";
    require_once "../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);

    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_t('common_import', 'Import').' '.erp_t('master_material_master', 'Material Master'));
    $headers = array(
      erp_t(erp_export_label("master_term_kode_barang"), erp_export_label("Material Code")).erp_export_label("*"),
      erp_t(erp_export_label("master_term_nama_barang"), erp_export_label("Material Name")).erp_export_label("*"),
      erp_export_label("Legacy Type*"),
      erp_export_label("Material Type Code"),
      erp_export_label("Material Group Code"),
      erp_export_label("Plant Code"),
      erp_export_label("Storage Location Code"),
      erp_t(erp_export_label("common_spec"), erp_export_label("Spec")),
      erp_t(erp_export_label("master_term_satuan"), erp_export_label("Unit")).erp_export_label("*"),
      erp_t(erp_export_label("master_term_kategori"), erp_export_label("Category")).erp_export_label(" Code*"),
      erp_t(erp_export_label("master_term_keterangan"), erp_export_label("Remarks")),
      erp_t(erp_export_label("common_status"), erp_export_label("Status"))
    );
    foreach ($headers as $i => $label) {
      $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i)."4", $label);
    }
    $sheet->setCellValue("A3", erp_t('master_import_required_help', 'Red header columns are mandatory.').' '.erp_t('master_import_code_help', 'Fill master codes according to active ERP data. Material code will automatically be converted to uppercase.'));
    $sample = array("BB-IMPORT-001", "Contoh Material Import", "ROH", "ROH", "K01", "PL01", "RM01", "Spesifikasi contoh", "ROL", "K01", "Contoh import material", "Aktif");
    foreach ($sample as $i => $value) {
      $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i)."5", $value);
    }

    erpkb_excel_apply_standard_style($excel, array(
      "sheet" => $sheet,
      "title" => strtoupper(erp_t('master_import_template', 'Import Template').' '.erp_t('master_material_master', 'Material Master')),
      "header_row" => 4,
      "first_data_row" => 5,
      "last_data_row" => 5,
      "column_count" => count($headers),
      "filters" => array(erp_t('master_term_catatan', 'Notes') => erp_t('master_import_sample_note', 'Delete or replace the sample row before importing real data.')),
      "widths" => array("A" => 18, "B" => 30, "C" => 16, "D" => 20, "E" => 22, "F" => 15, "G" => 24, "H" => 24, "I" => 12, "J" => 16, "K" => 30, "L" => 12)
    ));
    foreach (array("A", "B", "C", "I", "J") as $col) {
      $sheet->getStyle($col."4")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("DC2626");
      $sheet->getStyle($col."4")->getFont()->setBold(true)->getColor()->setRGB("FFFFFF");
      $sheet->getStyle($col."4")->getBorders()->getOutline()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM)->getColor()->setRGB("7F1D1D");
    }
    $sheet->getStyle("A3:L3")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("FEE2E2");
    $sheet->getStyle("A3:L3")->getFont()->setBold(true)->getColor()->setRGB("991B1B");

    $ref = $excel->createSheet(1);
    $ref->setTitle(erp_t('master_reference', 'Reference'));
    $ref->setCellValue("A1", erp_t('master_code_reference', 'Master Code Reference'));
    $ref->getStyle("A1")->getFont()->setBold(true)->setSize(14);
    $ref->fromArray(array(erp_t('master_term_kategori', 'Category').' Code', erp_t('master_term_kategori', 'Category').' Name'), null, "A3");
    $r = 4;
    foreach ($db->query("SELECT kd_kategori,nm_kategori FROM kategori ORDER BY kd_kategori") as $row) {
      $ref->setCellValue("A".$r, $row->kd_kategori)->setCellValue("B".$r, $row->nm_kategori);
      $r++;
    }
    $ref->fromArray(array(erp_t('master_term_satuan', 'Unit'), erp_t('master_term_nama', 'Name')), null, "D3");
    $r = 4;
    foreach ($db->query("SELECT jenis,nama FROM satuan ORDER BY jenis LIMIT 200") as $row) {
      $ref->setCellValue("D".$r, $row->jenis)->setCellValue("E".$r, $row->nama);
      $r++;
    }
    $ref->fromArray(array(erp_t('master_term_material_type', 'Material Type'), erp_t('master_term_nama', 'Name')), null, "G3");
    $r = 4;
    foreach ($db->query("SELECT type_code,type_name FROM erp_material_type WHERE status='Aktif' ORDER BY type_code") as $row) {
      $ref->setCellValue("G".$r, $row->type_code)->setCellValue("H".$r, $row->type_name);
      $r++;
    }
    $ref->fromArray(array(erp_t('master_term_material_group', 'Material Group'), erp_t('master_term_nama', 'Name')), null, "J3");
    $r = 4;
    foreach ($db->query("SELECT group_code,group_name FROM erp_material_group WHERE status='Aktif' ORDER BY group_code") as $row) {
      $ref->setCellValue("J".$r, $row->group_code)->setCellValue("K".$r, $row->group_name);
      $r++;
    }
    $ref->fromArray(array(erp_t('master_term_plant', 'Plant'), erp_t('master_term_nama', 'Name')), null, "M3");
    $r = 4;
    foreach ($db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $row) {
      $ref->setCellValue("M".$r, $row->plant_code)->setCellValue("N".$r, $row->plant_name);
      $r++;
    }
    $ref->fromArray(array(erp_t('master_term_storage_location', 'Storage Location'), erp_t('master_term_nama', 'Name')), null, "P3");
    $r = 4;
    foreach ($db->query("SELECT storage_code,storage_name FROM erp_storage_location WHERE status='Aktif' ORDER BY storage_code") as $row) {
      $ref->setCellValue("P".$r, $row->storage_code)->setCellValue("Q".$r, $row->storage_name);
      $r++;
    }
    foreach (array("A", "B", "D", "E", "G", "H", "J", "K", "M", "N", "P", "Q") as $col) {
      $ref->getColumnDimension($col)->setAutoSize(true);
    }
    $ref->getStyle("A3:Q3")->getFont()->setBold(true);
    $ref->getStyle("A3:Q3")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("DBEAFE");
    $excel->setActiveSheetIndex(0);

    $tmp = erpkb_excel_temp_file("template_import_barang_");
    try {
      PHPExcel_IOFactory::createWriter($excel, "Excel2007")->save($tmp);
    } catch (Exception $e) {
      @unlink($tmp);
      while (ob_get_level() > $initial) ob_end_clean();
      header("Content-Type:text/plain; charset=utf-8");
      echo erp_t('master_template_failed', 'Excel template failed to create: ').$e->getMessage();
      exit;
    }
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== "PK") {
      @unlink($tmp);
      while (ob_get_level() > $initial) ob_end_clean();
      header("Content-Type:text/plain; charset=utf-8");
      echo "Template Excel gagal dibuat dengan benar.";
      exit;
    }
    while (ob_get_level() > $initial) ob_end_clean();
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"template_import_barang.xlsx\"");
    header("Content-Length: ".$size);
    header("Cache-Control: max-age=0");
    header("Pragma: public");
    readfile($tmp);
    @unlink($tmp);
    exit;

  case "import_excel":
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    if (empty($_FILES["file_excel"]["tmp_name"])) {
      barang_import_json("error", erp_t('master_excel_required', 'Excel file is required.'));
    }
    $ext = strtolower(pathinfo($_FILES["file_excel"]["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, array("xls", "xlsx"), true)) {
      barang_import_json("error", erp_t('master_excel_format_invalid', 'File format must be .xls or .xlsx.'));
    }
    require_once "../../inc/lib/PHPExcel.php";
    try {
      $reader = PHPExcel_IOFactory::createReaderForFile($_FILES["file_excel"]["tmp_name"]);
      $reader->setReadDataOnly(true);
      $excel = $reader->load($_FILES["file_excel"]["tmp_name"]);
    } catch (Exception $e) {
      barang_import_json("error", erp_t('master_excel_read_failed', 'Excel file cannot be read: ').$e->getMessage());
    }

    $sheet = $excel->getSheet(0);
    $highest = $sheet->getHighestRow();
    $rows = array();
    $seen = array();
    $errors = array();

    for ($r = 5; $r <= $highest; $r++) {
      $code = barang_import_upper(barang_import_value($sheet, "A", $r));
      $name = barang_import_value($sheet, "B", $r);
      $legacyType = barang_import_upper(barang_import_value($sheet, "C", $r));
      $materialTypeCode = barang_import_upper(barang_import_value($sheet, "D", $r));
      $materialGroupCode = barang_import_upper(barang_import_value($sheet, "E", $r));
      $plantCode = barang_import_upper(barang_import_value($sheet, "F", $r));
      $storageCode = barang_import_upper(barang_import_value($sheet, "G", $r));
      $spec = barang_import_value($sheet, "H", $r);
      $uomInput = barang_import_upper(barang_import_value($sheet, "I", $r));
      $categoryCode = barang_import_upper(barang_import_value($sheet, "J", $r));
      $remark = barang_import_value($sheet, "K", $r);
      $status = barang_import_status(barang_import_value($sheet, "L", $r));

      if ($code === "" && $name === "" && $legacyType === "" && $categoryCode === "") {
        continue;
      }
      if ($code === "BB-IMPORT-001") {
        continue;
      }

      if ($code === "") $errors[] = sprintf(erp_t('master_import_row_required', 'Row %s: %s is required.'), $r, erp_t('master_term_kode_barang', 'Material Code'));
      if ($name === "") $errors[] = sprintf(erp_t('master_import_row_required', 'Row %s: %s is required.'), $r, erp_t('master_term_nama_barang', 'Material Name'));
      if ($legacyType === "") $errors[] = sprintf(erp_t('master_import_row_required', 'Row %s: %s is required.'), $r, 'Legacy Type');
      if ($uomInput === "") $errors[] = sprintf(erp_t('master_import_row_required', 'Row %s: %s is required.'), $r, erp_t('master_term_satuan', 'Unit'));
      if ($categoryCode === "") $errors[] = sprintf(erp_t('master_import_row_required', 'Row %s: %s is required.'), $r, erp_t('master_term_kategori', 'Category').' Code');
      if ($code !== "" && isset($seen[$code])) $errors[] = sprintf(erp_t('master_import_row_duplicate_file', 'Row %s: %s %s is duplicated in the Excel file.'), $r, erp_t('master_term_kode_barang', 'Material Code'), $code);
      $seen[$code] = 1;
      if ($code !== "" && mdg_exists($db, "barang", "kd_barang", $code)) {
        $errors[] = sprintf(erp_t('master_import_row_exists', 'Row %s: %s %s already exists in master.'), $r, erp_t('master_term_kode_barang', 'Material Code'), $code);
      }

      $uom = barang_import_uom($db, $uomInput);
      if ($uomInput !== "" && $uom === "") {
        $errors[] = sprintf(erp_t('master_import_row_not_found', 'Row %s: %s %s was not found.'), $r, erp_t('master_term_satuan', 'Unit'), $uomInput);
      }
      if ($categoryCode !== "" && !mdg_exists($db, "kategori", "kd_kategori", $categoryCode)) {
        $errors[] = sprintf(erp_t('master_import_row_not_found', 'Row %s: %s %s was not found.'), $r, erp_t('master_term_kategori', 'Category'), $categoryCode);
      }

      $materialTypeId = null;
      if ($materialTypeCode !== "") {
        $materialTypeId = barang_import_lookup_id($db, "SELECT id FROM erp_material_type WHERE type_code=? AND status='Aktif' LIMIT 1", array($materialTypeCode));
        if (!$materialTypeId) $errors[] = sprintf(erp_t('master_import_row_not_found_inactive', 'Row %s: %s %s was not found or inactive.'), $r, erp_t('master_term_material_type', 'Material Type'), $materialTypeCode);
      }
      $materialGroupId = null;
      if ($materialGroupCode !== "") {
        $materialGroupId = barang_import_lookup_id($db, "SELECT id FROM erp_material_group WHERE group_code=? AND status='Aktif' LIMIT 1", array($materialGroupCode));
        if (!$materialGroupId) $errors[] = sprintf(erp_t('master_import_row_not_found_inactive', 'Row %s: %s %s was not found or inactive.'), $r, erp_t('master_term_material_group', 'Material Group'), $materialGroupCode);
      }
      $plantId = null;
      if ($plantCode !== "") {
        $plantId = barang_import_lookup_id($db, "SELECT id FROM erp_plant WHERE plant_code=? AND status='Aktif' LIMIT 1", array($plantCode));
        if (!$plantId) $errors[] = sprintf(erp_t('master_import_row_not_found_inactive', 'Row %s: %s %s was not found or inactive.'), $r, erp_t('master_term_plant', 'Plant'), $plantCode);
      }
      $storageId = null;
      if ($storageCode !== "") {
        $params = array($storageCode);
        $wherePlant = "";
        if ($plantId) {
          $wherePlant = " AND plant_id=? ";
          $params[] = $plantId;
        }
        $storageId = barang_import_lookup_id($db, "SELECT id FROM erp_storage_location WHERE storage_code=? ".$wherePlant." AND status='Aktif' LIMIT 1", $params);
        if (!$storageId) $errors[] = sprintf(erp_t('master_import_row_not_found_inactive', 'Row %s: %s %s was not found or inactive.'), $r, erp_t('master_term_storage_location', 'Storage Location'), $storageCode);
      }

      if (count($errors) >= 30) {
        break;
      }
      $rows[] = array(
        "kd_barang" => $code,
        "nm_barang" => $name,
        "type" => $legacyType,
        "material_type_id" => $materialTypeId,
        "material_group_id" => $materialGroupId,
        "plant_id" => $plantId,
        "default_storage_location_id" => $storageId,
        "spec" => $spec,
        "satuan" => $uom,
        "ket" => $remark,
        "kd_kategori" => $categoryCode,
        "status" => $status
      );
    }

    if (empty($rows)) {
      $errors[] = erp_t('master_import_no_rows', 'No material data can be imported. Make sure data starts from row 5 and the sample row has been replaced.');
    }
    if (!empty($errors)) {
      barang_import_json("error", implode("<br>", array_slice($errors, 0, 30)));
    }

    $db->query("START TRANSACTION");
    $inserted = 0;
    foreach ($rows as $data) {
      $ok = $db->query(
        "INSERT INTO barang (kd_barang,nm_barang,type,material_type_id,material_group_id,plant_id,default_storage_location_id,spec,satuan,ket,kd_kategori,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
        array($data["kd_barang"], $data["nm_barang"], $data["type"], $data["material_type_id"], $data["material_group_id"], $data["plant_id"], $data["default_storage_location_id"], $data["spec"], $data["satuan"], $data["ket"], $data["kd_kategori"], $data["status"])
      );
      if (!$ok) {
        $err = $db->getErrorMessage();
        $db->query("ROLLBACK");
        barang_import_json("error", $err ? $err : erp_t('master_import_failed', 'Import failed.'));
      }
      $inserted++;
    }
    $username = isset($_SESSION["username"]) ? $_SESSION["username"] : "system";
    if (function_exists("simpan_log")) {
      simpan_log("User ".$username." import ".$inserted." master barang dari Excel pada ".date("Y-m-d H:i:s"), $username);
    }
    $db->query("COMMIT");
    barang_import_json("good", sprintf(erp_t('master_import_success_count', 'Import successful. %s new rows processed.'), $inserted), array("inserted" => $inserted));
    break;

  case "in":
    mdg_required(array(
      "kd_barang" => erp_t('master_term_kode_barang', 'Material Code'),
      "nm_barang" => erp_t('master_term_nama_barang', 'Material Name'),
      "type" => "Material type legacy",
      "satuan" => erp_t('master_term_satuan', 'Unit'),
      "kd_kategori" => erp_t('master_term_kategori', 'Category')
    ));
    $_POST["kd_barang"] = strtoupper(mdg_trim("kd_barang"));
    if (mdg_exists($db, "barang", "kd_barang", $_POST["kd_barang"])) {
      mdg_error(erp_t('master_term_kode_barang', 'Material Code').' '.$_POST["kd_barang"].' '.erp_t('master_already_exists', 'already exists.'));
    }
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => mdg_trim("nm_barang"),
      "type" => mdg_trim("type"),
      "material_type_id" => mdg_trim("material_type_id") !== "" ? (int) mdg_trim("material_type_id") : null,
      "material_group_id" => mdg_trim("material_group_id") !== "" ? (int) mdg_trim("material_group_id") : null,
      "plant_id" => mdg_trim("plant_id") !== "" ? (int) mdg_trim("plant_id") : null,
      "default_storage_location_id" => mdg_trim("default_storage_location_id") !== "" ? (int) mdg_trim("default_storage_location_id") : null,
      "spec" => mdg_trim("spec"),
      "satuan" => mdg_trim("satuan"),
      "ket" => mdg_trim("ket"),
      "kd_kategori" => mdg_trim("kd_kategori"),
  );
  
  
  
   
          if(isset($_POST["status"]) && $_POST["status"]=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    $in = $db->insert("barang",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    $material = $db->fetch("SELECT kd_barang FROM barang WHERE id=? LIMIT 1", array($_GET["id"]));
    $materialCode = $material ? $material->kd_barang : $_GET["id"];
    mdg_block_delete_if_used($db, "Material ".$materialCode, $materialCode, array(
      array("stock_layer", "kode"),
      array("detail_transaksi", "kd_barang"),
      array("pemasukan_detail", "kode"),
      array("erp_goods_issue_delivery_detail", "material_code"),
      array("erp_issue_production_detail", "material_code"),
      array("erp_gr_production_detail", "material_code"),
      array("production_order_material", "material_code")
    ));
    $db->delete("barang","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $material = $db->fetch("SELECT kd_barang FROM barang WHERE id=? LIMIT 1", array($id));
          $materialCode = $material ? $material->kd_barang : $id;
          mdg_block_delete_if_used($db, "Material ".$materialCode, $materialCode, array(
            array("stock_layer", "kode"),
            array("detail_transaksi", "kd_barang"),
            array("pemasukan_detail", "kode"),
            array("erp_goods_issue_delivery_detail", "material_code"),
            array("erp_issue_production_detail", "material_code"),
            array("erp_gr_production_detail", "material_code"),
            array("production_order_material", "material_code")
          ));
          $db->delete("barang","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    mdg_required(array(
      "id" => 'ID '.erp_t('master_material_master', 'Material Master'),
      "kd_barang" => erp_t('master_term_kode_barang', 'Material Code'),
      "nm_barang" => erp_t('master_term_nama_barang', 'Material Name'),
      "type" => "Material type legacy",
      "satuan" => erp_t('master_term_satuan', 'Unit'),
      "kd_kategori" => erp_t('master_term_kategori', 'Category')
    ));
    $_POST["kd_barang"] = strtoupper(mdg_trim("kd_barang"));
    if (mdg_exists($db, "barang", "kd_barang", $_POST["kd_barang"], "id", $_POST["id"])) {
      mdg_error(erp_t('master_term_kode_barang', 'Material Code').' '.$_POST["kd_barang"].' '.erp_t('master_already_exists', 'already exists.'));
    }
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => mdg_trim("nm_barang"),
      "type" => mdg_trim("type"),
      "material_type_id" => mdg_trim("material_type_id") !== "" ? (int) mdg_trim("material_type_id") : null,
      "material_group_id" => mdg_trim("material_group_id") !== "" ? (int) mdg_trim("material_group_id") : null,
      "plant_id" => mdg_trim("plant_id") !== "" ? (int) mdg_trim("plant_id") : null,
      "default_storage_location_id" => mdg_trim("default_storage_location_id") !== "" ? (int) mdg_trim("default_storage_location_id") : null,
      "spec" => mdg_trim("spec"),
      "satuan" => mdg_trim("satuan"),
      "ket" => mdg_trim("ket"),
      "kd_kategori" => mdg_trim("kd_kategori"),
   );
   
   
   

    
          if(isset($_POST["status"]) && $_POST["status"]=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    
    $up = $db->update("barang",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
