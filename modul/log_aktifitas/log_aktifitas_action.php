<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();

session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "excel":
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
    $filterUser = isset($_GET['filter_user']) ? trim($_GET['filter_user']) : '';

    $where = " where lower(trim(coalesce(user, ''))) != 'guest'
                 and trim(coalesce(user, '')) != ''";
    $params = array();

    if ($startDate !== '') {
      $where .= " and date(tgl) >= ?";
      $params[] = $startDate;
    }

    if ($endDate !== '') {
      $where .= " and date(tgl) <= ?";
      $params[] = $endDate;
    }

    if ($filterUser !== '') {
      $where .= " and user = ?";
      $params[] = $filterUser;
    }

    $logs = $db->query(
      "select deskripsi, user, tgl from log_aktifitas $where order by id desc",
      $params
    );

    if ($logs === false) {
      throw new Exception('Data log aktivitas gagal diambil: '.$db->getErrorMessage());
    }

    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);

    $excel = new PHPExcel();
    $excel->getProperties()
          ->setCreator(namaPT)
          ->setTitle(erp_export_sheet_title('Log Aktivitas'))
          ->setSubject('Log Aktivitas Pengguna');

    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Log Aktivitas'));
    $sheet->mergeCells('A1:D1');
    $sheet->mergeCells('A2:D2');
    $sheet->setCellValue('A1', namaPT);
    $sheet->setCellValue('A2', 'LOG AKTIVITAS PENGGUNA');

    $periode = 'Periode: '.($startDate !== '' ? $startDate : erp_export_all_text()).
      ' s.d. '.($endDate !== '' ? $endDate : erp_export_all_text());
    if ($filterUser !== '') {
      $periode .= ' | User: '.$filterUser;
    }
    $sheet->mergeCells('A3:D3');
    $sheet->setCellValue('A3', $periode);

    $sheet->setCellValue('A5', 'No');
    $sheet->setCellValue('B5', 'Deskripsi');
    $sheet->setCellValue('C5', 'User');
    $sheet->setCellValue('D5', 'Tanggal');

    $titleStyle = array(
      'font' => array('bold' => true, 'size' => 14),
      'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
    );
    $headerStyle = array(
      'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
      'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => '3C8DBC')
      ),
      'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
      'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN))
    );
    $bodyBorder = array(
      'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),
      'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP)
    );

    $sheet->getStyle('A1:D3')->applyFromArray($titleStyle);
    $sheet->getStyle('A2')->getFont()->setSize(16);
    $sheet->getStyle('A5:D5')->applyFromArray($headerStyle);

    $rowNumber = 6;
    $number = 1;
    foreach ($logs as $log) {
      $sheet->setCellValueExplicit(
        'A'.$rowNumber,
        $number,
        PHPExcel_Cell_DataType::TYPE_NUMERIC
      );
      $sheet->setCellValue('B'.$rowNumber, $log->deskripsi);
      $sheet->setCellValueExplicit('C'.$rowNumber, $log->user, PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValue('D'.$rowNumber, $log->tgl);
      $sheet->getStyle('A'.$rowNumber.':D'.$rowNumber)->applyFromArray($bodyBorder);
      $sheet->getStyle('B'.$rowNumber)->getAlignment()->setWrapText(true);
      $rowNumber++;
      $number++;
    }

    if ($number === 1) {
      $sheet->mergeCells('A6:D6');
      $sheet->setCellValue('A6', 'Tidak ada data untuk filter yang dipilih.');
      $sheet->getStyle('A6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
      $rowNumber = 7;
    }

    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(90);
    $sheet->getColumnDimension('C')->setWidth(22);
    $sheet->getColumnDimension('D')->setWidth(22);
    $sheet->freezePane('A6');
    $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageMargins()->setTop(0.5)->setRight(0.4)->setLeft(0.4)->setBottom(0.5);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('LOG AKTIVITAS PENGGUNA'),'header_row'=>5,'first_data_row'=>6,'last_data_row'=>max(6,$rowNumber-1),'column_count'=>4,'filters'=>array('Periode'=>($startDate !== '' ? $startDate : erp_export_all_text()).' s/d '.($endDate !== '' ? $endDate : erp_export_all_text()),'User'=>$filterUser),'widths'=>array('A'=>8,'B'=>90,'C'=>22,'D'=>22)));

    $tempDirectory = ini_get('upload_tmp_dir');
    if (!$tempDirectory || !is_dir($tempDirectory) || !is_writable($tempDirectory)) {
      $tempDirectory = sys_get_temp_dir();
    }

    $tempFile = tempnam($tempDirectory, 'log_aktifitas_');
    if ($tempFile === false) {
      throw new Exception('File sementara untuk export Excel tidak dapat dibuat.');
    }

    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    try {
      $writer->save($tempFile);
    } catch (Exception $writerException) {
      @unlink($tempFile);
      throw $writerException;
    }

    $fileSize = @filesize($tempFile);
    $signature = @file_get_contents($tempFile, false, null, 0, 2);
    if (!$fileSize || $signature !== 'PK') {
      @unlink($tempFile);
      throw new Exception('File Excel gagal dibuat dengan benar.');
    }

    while (ob_get_level() > $initialOutputBufferLevel) {
      ob_end_clean();
    }

    $filename = 'log_aktifitas_'.date('Ymd_His').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.$fileSize);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tempFile);
    @unlink($tempFile);
    exit;

  case "in":
    
  
  
  
  $data = array(
      "deskripsi" => $_POST["deskripsi"],
      "user" => $_POST["user"],
      "tgl" => $_POST["tgl"],
  );
  
  
  
   
    $in = $db->insert("log_aktifitas",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("log_aktifitas","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("log_aktifitas","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "deskripsi" => $_POST["deskripsi"],
      "user" => $_POST["user"],
      "tgl" => $_POST["tgl"],
   );
   
   
   

    
    
    $up = $db->update("log_aktifitas",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
