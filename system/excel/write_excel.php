<?php




// Set document properties
$objPHPExcel->getProperties()->setCreator("Me")->setLastModifiedBy("Me")->setTitle("My Excel Sheet")->setSubject("My Excel Sheet")->setDescription("Excel Sheet")->setKeywords("Excel Sheet")->setCategory("Me");



// Set worksheet title
$objPHPExcel->getActiveSheet()->setTitle('Sample Data');

//save the file to the server (Excel2007)
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save( 'result/'.$service_name.'.xlsx');
?>