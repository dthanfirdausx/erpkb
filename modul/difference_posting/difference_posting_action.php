<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if(session_status()===PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
include "difference_posting_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';

function dp_json($status,$message='',$extra=array()){ header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status,'error_message'=>$message),$extra)); exit; }
function dp_layer_price($layer){
  if(isset($layer->purchase_price) && (float)$layer->purchase_price>0) return (float)$layer->purchase_price;
  if(isset($layer->dt_price) && (float)$layer->dt_price>0) return (float)$layer->dt_price;
  if(isset($layer->dt_amount) && isset($layer->dt_qty) && abs((float)$layer->dt_qty)>0) return abs((float)$layer->dt_amount)/abs((float)$layer->dt_qty);
  return 0;
}
function dp_current_price($materialCode){
  global $db;
  $row=$db->fetch("SELECT COALESCE(NULLIF(pd.harga,0),NULLIF(dt.price,0),CASE WHEN ABS(COALESCE(dt.qty,0))>0 THEN ABS(COALESCE(dt.amount,0))/ABS(dt.qty) ELSE 0 END,0) price
                   FROM stock_layer sl
                   LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
                   LEFT JOIN detail_transaksi dt ON dt.id=sl.ref_id AND sl.ref_table='detail_transaksi'
                   WHERE sl.kode=? AND (sl.qty_sisa>0 OR sl.qty_masuk>0)
                   ORDER BY sl.tgl_masuk DESC,sl.id DESC LIMIT 1",array($materialCode));
  return $row?(float)$row->price:0;
}

if($act==='material_search'){
  session_check_json(); $term=isset($_POST['term'])?trim($_POST['term']):''; $params=array(); $where=" WHERE b.status=1 ";
  if($term!==''){ $where.=" AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows=$db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30",$params);
  $results=array(); foreach($rows as $row)$results[]=array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

function dp_posting_date(){ return date('Y-m-d'); }

if($act==='post'){
  session_check_json();
  $docType=dp_input('doc_type'); $itemId=(int)dp_input('item_id');
  if(!in_array($docType,array('CYCLE_COUNT','STOCK_OPNAME'))||$itemId<=0) dp_json('error','Item difference posting tidak valid.');
  $item=dp_get_item($db,$docType,$itemId);
  if(!$item) dp_json('error','Item tidak ditemukan.');
  if($item->posting_no) dp_json('error','Item sudah pernah diposting: '.$item->posting_no);
  if($item->item_status!=='COUNTED') dp_json('error','Item harus status COUNTED sebelum difference posting.');
  $diff=round((float)$item->difference_qty,5);
  if(abs($diff)<0.000001) dp_json('error','Difference qty nol, tidak perlu posting.');
  $username=isset($_SESSION['username'])?$_SESSION['username']:'system';
  $postingNo=dp_next_no($db); $postingDate=dp_posting_date(); $moveCode=$diff>0?'701':'702'; $qty=abs($diff);
  $itemTable=$docType==='CYCLE_COUNT'?'cycle_count_document_items':'stock_opname_document_items';
  $docTable=$docType==='CYCLE_COUNT'?'cycle_count_documents':'stock_opname_documents';
  $db->query('START TRANSACTION');
  $dup=$db->fetch("SELECT id FROM physical_inventory_postings WHERE doc_type=? AND item_id=? LIMIT 1", array($docType,$itemId));
  if($dup){ $db->query('ROLLBACK'); dp_json('error','Item sudah pernah diposting.'); }
  $price=0; $amount=0; $layerRefId=null; $noBpb=$postingNo; $noAju=''; $noDokpab=''; $jenisDokpab='NON';
  if($diff<0){
    $available=$db->fetch("SELECT COALESCE(SUM(qty_sisa),0) qty FROM stock_layer WHERE kode=? AND qty_sisa>0 AND IFNULL(plant_id,0)=IFNULL(?,0) AND IFNULL(storage_location_id,0)=IFNULL(?,0) AND IFNULL(storage_bin_id,0)=IFNULL(?,0) AND stock_type=?", array($item->material_code,$item->plant_id,$item->storage_location_id,$item->storage_bin_id,$item->stock_type));
    if(!$available || (float)$available->qty + 0.00001 < $qty){ $db->query('ROLLBACK'); dp_json('error','Stock layer tidak cukup untuk posting selisih negatif.'); }
    $remaining=$qty;
    $layers=$db->query("SELECT sl.*,pd.harga purchase_price,dt.price dt_price,dt.amount dt_amount,dt.qty dt_qty FROM stock_layer sl LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail' LEFT JOIN detail_transaksi dt ON dt.id=sl.ref_id AND sl.ref_table='detail_transaksi' WHERE sl.kode=? AND sl.qty_sisa>0 AND IFNULL(sl.plant_id,0)=IFNULL(?,0) AND IFNULL(sl.storage_location_id,0)=IFNULL(?,0) AND IFNULL(sl.storage_bin_id,0)=IFNULL(?,0) AND sl.stock_type=? ORDER BY COALESCE(sl.tgl_masuk,DATE(sl.created_at)),sl.id FOR UPDATE", array($item->material_code,$item->plant_id,$item->storage_location_id,$item->storage_bin_id,$item->stock_type));
    foreach($layers as $layer){ if($remaining<=0) break; $take=min($remaining,(float)$layer->qty_sisa); if($take<=0) continue; $layerPrice=dp_layer_price($layer); if($layerPrice<=0){$db->query('ROLLBACK'); dp_json('error','Valuation price stock layer #'.$layer->id.' material '.$item->material_code.' belum tersedia.');} $amount+=round($take*$layerPrice,2); $db->query("UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?", array($take,$layer->id,$take)); $remaining-=$take; $layerRefId=$layer->id; $noBpb=$layer->no_bpb; $noAju=$layer->no_aju; $noDokpab=$layer->no_dokpab; $jenisDokpab=$layer->jenis_dokpab; }
    if($remaining>0.00001){ $db->query('ROLLBACK'); dp_json('error','Stock layer FIFO tidak cukup.'); }
    $price=$qty>0?$amount/$qty:0;
  }else{
    $price=dp_current_price($item->material_code);
    if($price<=0){$db->query('ROLLBACK'); dp_json('error','Valuation price material '.$item->material_code.' belum tersedia untuk selisih positif.');}
    $amount=round($qty*$price,2);
  }
  $dt=array('no_ref'=>$postingNo,'ref_pengganti'=>$item->doc_no,'no_aju'=>$noAju,'no_dokpab'=>$noDokpab,'move_code'=>$moveCode,'posisi'=>'GUDANG','no_urut'=>$item->line_no,'qty'=>$diff>0?$qty:$qty*-1,'kd_barang'=>$item->material_code,'lokasi'=>'GUDANG','document_date'=>$postingDate,'posting_date'=>$postingDate,'user'=>$username,'direction'=>$diff>0?'IN':'OUT','ref_type'=>'PI_DIFF','ref_id'=>$itemId,'uom'=>$item->uom,'price'=>$price,'amount'=>$amount,'reason'=>'Physical inventory difference '.$item->doc_no,'created_by'=>$username,'no_bpb'=>$noBpb,'plant_id'=>$item->plant_id,'storage_location_id'=>$item->storage_location_id,'storage_bin_id'=>$item->storage_bin_id,'stock_type'=>$item->stock_type,'destination_storage_location_id'=>$item->storage_location_id,'destination_storage_bin_id'=>$item->storage_bin_id,'destination_stock_type'=>$item->stock_type,'destination_material_code'=>$item->material_code,'remark'=>'Physical Inventory Difference '.$moveCode.' '.$item->doc_no);
  if(!$db->insert('detail_transaksi',$dt)){ $err=$db->getErrorMessage(); $db->query('ROLLBACK'); dp_json('error',$err?:'Material document gagal dibuat.'); }
  $matDocId=$db->last_insert_id();
  if($diff>0){
    if(!$db->insert('stock_layer', array('kode'=>$item->material_code,'qty_masuk'=>$qty,'qty_sisa'=>$qty,'no_aju'=>'','no_dokpab'=>'','lokasi'=>'GUDANG','stock_type'=>$item->stock_type,'plant_id'=>$item->plant_id,'storage_location_id'=>$item->storage_location_id,'storage_bin_id'=>$item->storage_bin_id,'jenis_dokpab'=>'NON','ref_table'=>'detail_transaksi','ref_id'=>$matDocId,'tgl_masuk'=>$postingDate,'no_bpb'=>$postingNo))){ $err=$db->getErrorMessage(); $db->query('ROLLBACK'); dp_json('error',$err?:'Stock layer selisih positif gagal dibuat.'); }
  }
  if(!$db->insert('physical_inventory_postings', array('posting_no'=>$postingNo,'doc_type'=>$docType,'document_id'=>$item->document_id,'item_id'=>$itemId,'material_doc_id'=>$matDocId,'movement_type'=>$moveCode,'difference_qty'=>$diff,'posted_by'=>$username,'remarks'=>'Difference posting from '.$item->doc_no))){ $err=$db->getErrorMessage(); $db->query('ROLLBACK'); dp_json('error',$err?:'Mapping posting gagal disimpan.'); }
  $journalResult=accounting_post_auto_journal($diff>0?'pi_diff_increase':'pi_diff_decrease','',array(array('kode'=>$item->material_code,'amount'=>$amount,'valuta'=>'IDR','kurs'=>1)),array('no_bukti'=>$postingNo,'tgl_jurnal'=>$postingDate,'ket'=>'Physical Inventory Difference '.$postingNo.' '.$item->doc_no,'valuta'=>'IDR','kurs'=>1,'source_module'=>'PHYSICAL_INVENTORY_DIFF'));
  if($journalResult!==true){$db->query('ROLLBACK'); dp_json('error',$journalResult);}
  $db->update($itemTable,array('status'=>'POSTED'),'id',$itemId);
  $open=$db->fetch("SELECT COUNT(*) total_open FROM $itemTable WHERE document_id=? AND status<>'POSTED'", array((int)$item->document_id));
  if($open && (int)$open->total_open===0) $db->update($docTable,array('status'=>'POSTED','updated_at'=>date('Y-m-d H:i:s')),'id',(int)$item->document_id);
  if(function_exists('simpan_log')) simpan_log('User '.$username.' posting physical inventory difference '.$postingNo.' dari '.$item->doc_no.' pada '.date('Y-m-d H:i:s'),$username);
  $db->query('COMMIT'); dp_json('good','',array('posting_no'=>$postingNo,'material_doc_id'=>$matDocId,'movement_type'=>$moveCode));
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level(); ob_start(); ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=array('tgl_awal'=>dp_input('tgl_awal',date('Y-m-01')),'tgl_akhir'=>dp_input('tgl_akhir',date('Y-m-d')),'doc_type'=>dp_input('doc_type'),'doc_no'=>dp_input('doc_no'),'material_code'=>dp_input('material_code'),'plant_id'=>dp_input('plant_id'),'storage_location_id'=>dp_input('storage_location_id'),'storage_bin_id'=>dp_input('storage_bin_id'),'stock_type'=>dp_input('stock_type'),'posting_status'=>dp_input('posting_status'),'variance_type'=>dp_input('variance_type'),'keyword'=>dp_input('keyword'));
  $rows=dp_load_rows($db,$input); $excel=new PHPExcel(); $sheet=$excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Difference Posting'));
  $headers=array(erp_export_label("No"),erp_export_label("Doc Type"),erp_export_label("Doc No"),erp_export_label("Count Date"),erp_export_label("Item Status"),erp_export_label("Posting No"),erp_export_label("Movement"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("System Qty"),erp_export_label("Counted Qty"),erp_export_label("Difference"),erp_export_label("UOM"),erp_export_label("Posted By"),erp_export_label("Posted At"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;$totSys=0;$totCnt=0;$totDiff=0; foreach($rows as $row){$diff=(float)$row->difference_qty;$totSys+=(float)$row->system_qty;$totCnt+=(float)$row->counted_qty;$totDiff+=$diff;$values=array($n++,dp_doc_type_label($row->doc_type),$row->doc_no,$row->count_date,$row->item_status,$row->posting_no,$diff>=0?'701':'702',$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,dp_stock_type_label($row->stock_type),(float)$row->system_qty,(float)$row->counted_qty,$diff,$row->uom,$row->posted_by,$row->posted_at);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  $summaryRow=$r+1;$sheet->mergeCells('A'.$summaryRow.':M'.$summaryRow);$sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));$sheet->setCellValue('N'.$summaryRow,$totSys);$sheet->setCellValue('O'.$summaryRow,$totCnt);$sheet->setCellValue('P'.$summaryRow,$totDiff);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('DIFFERENCE POSTING - SAP PHYSICAL INVENTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('N','O','P'),'filters'=>array('Periode'=>dp_valid_date($input['tgl_awal'],date('Y-m-01')).' s/d '.dp_valid_date($input['tgl_akhir'],date('Y-m-d')),'Doc Type'=>$input['doc_type'],'Doc No'=>$input['doc_no'],'Posting Status'=>$input['posting_status'],'Variance'=>$input['variance_type'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>16,'C'=>18,'D'=>14,'E'=>14,'F'=>18,'G'=>10,'H'=>16,'I'=>36,'J'=>12,'K'=>16,'L'=>14,'M'=>18,'N'=>14,'O'=>14,'P'=>14,'Q'=>10,'R'=>16,'S'=>20)));
  $sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getFont()->setBold(true);$sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');$sheet->getStyle('A'.$summaryRow.':S'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);$sheet->getStyle('N'.$summaryRow.':P'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $tmp=erpkb_excel_temp_file('difference_posting_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="difference_posting_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}
header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
