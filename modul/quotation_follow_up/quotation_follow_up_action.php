<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "quotation_follow_up_lib.php";

$act=isset($_GET['act'])?$_GET['act']:'';
function qfu_json($payload){header('Content-Type: application/json; charset=utf-8');echo json_encode($payload);exit;}

if($act==='customer_search'){
  session_check_json();
  $term=qfu_input('term');$params=array();$where=" WHERE 1=1 ";
  if($term!==''){$where.=" AND (nama LIKE ? OR kode_pemasok LIKE ? OR email LIKE ? OR notelp LIKE ?) ";for($i=0;$i<4;$i++)$params[]='%'.$term.'%';}
  $rows=$db->query("SELECT id_customer,kode_pemasok,nama FROM customer $where ORDER BY nama LIMIT 30",$params);
  $results=array();foreach($rows as $row)$results[]=array('id'=>$row->id_customer,'text'=>trim((string)$row->kode_pemasok.' - '.(string)$row->nama,' -'));
  qfu_json(array('results'=>$results));
}

if($act==='save'){
  session_check_json();
  $username=qfu_username();
  $quotationId=(int)qfu_input('quotation_id');
  $quote=$db->fetch_single_row('sales_quotation','id_quotation',$quotationId);
  if(!$quote) qfu_json(array('status'=>'error','error_message'=>'Quotation tidak ditemukan.'));
  $summary=qfu_input('discussion_summary');
  $nextAction=qfu_input('next_action');
  if($summary==='') qfu_json(array('status'=>'error','error_message'=>'Discussion summary wajib diisi.'));
  if($nextAction==='') qfu_json(array('status'=>'error','error_message'=>'Next action wajib diisi.'));
  $followupDate=qfu_valid_datetime(qfu_input('followup_date'),date('Y-m-d H:i:s'));
  $nextFollow=qfu_input('next_followup_date')!==''?qfu_valid_datetime(qfu_input('next_followup_date'),''):null;
  $status=qfu_input('result_status','OPEN');
  $prob=(float)str_replace(',','.',qfu_input('probability_percent','0'));
  if($prob<0)$prob=0;if($prob>100)$prob=100;
  $data=array(
    'quotation_id'=>$quotationId,
    'followup_date'=>$followupDate,
    'contact_method'=>qfu_input('contact_method','PHONE'),
    'contact_person'=>qfu_input('contact_person') ?: $quote->contact_person,
    'sales_person'=>qfu_input('sales_person') ?: ($quote->sales_id ?: $username),
    'activity_type'=>qfu_input('activity_type','REMINDER'),
    'result_status'=>$status,
    'probability_percent'=>$prob,
    'discussion_summary'=>$summary,
    'next_action'=>$nextAction,
    'next_followup_date'=>$nextFollow,
    'lost_reason'=>qfu_input('lost_reason'),
    'created_by'=>$username,
    'updated_by'=>$username,
    'updated_at'=>date('Y-m-d H:i:s')
  );
  if(!$db->insert('sales_quotation_followup',$data)) qfu_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
  $quoteUpdate=array('updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
  if($status==='WON'){$quoteUpdate['status']='ACCEPTED';$quoteUpdate['accepted_at']=date('Y-m-d H:i:s');}
  if($status==='LOST'){$quoteUpdate['status']='REJECTED';$quoteUpdate['rejected_reason']=qfu_input('lost_reason');}
  if($status==='CANCELLED')$quoteUpdate['status']='CANCELLED';
  if($quote->status==='OPEN' && !isset($quoteUpdate['status']))$quoteUpdate['status']='SENT';
  $db->update('sales_quotation',$quoteUpdate,'id_quotation',$quotationId);
  if(function_exists('simpan_log')) simpan_log('User '.$username.' mencatat follow-up Sales Quotation '.$quote->no_sales_quotation.' dengan hasil '.$status.' pada '.date('Y-m-d H:i:s'),$username);
  qfu_json(array('status'=>'good'));
}

if($act==='history'){
  session_check_json();
  $quotationId=(int)qfu_input('quotation_id');
  $quote=$db->fetch_single_row('sales_quotation','id_quotation',$quotationId);
  if(!$quote){echo '<div class="alert alert-danger">Quotation tidak ditemukan.</div>';exit;}
  $rows=$db->query("SELECT * FROM sales_quotation_followup WHERE quotation_id=? ORDER BY followup_date DESC,id DESC",array($quotationId));
  ?>
  <h4><?=qfu_h($quote->no_sales_quotation);?> <small><?=qfu_h($quote->customer_name);?></small></h4>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('sales_date', 'Date').'</th><th>Method</th><th>Activity</th><th>'.sd_h('common_status', 'Status').'</th><th>Probability</th><th>Summary</th><th>Next Action</th><th>Next Date</th><th>User</th></tr></thead><tbody>
  <?php $count=0; foreach($rows as $row){$count++; ?>
    <tr><td><?=qfu_h($row->followup_date);?></td><td><?=qfu_h($row->contact_method);?></td><td><?=qfu_h($row->activity_type);?></td><td><?=qfu_status_label($row->result_status);?></td><td class="text-right"><?=number_format((float)$row->probability_percent,2,',','.');?>%</td><td><?=qfu_h($row->discussion_summary);?></td><td><?=qfu_h($row->next_action);?></td><td><?=qfu_h($row->next_followup_date);?></td><td><?=qfu_h($row->created_by);?></td></tr>
  <?php } if($count===0){ ?><tr><td colspan="9" class="text-center text-muted">Belum ada follow-up.</td></tr><?php } ?>
  </tbody></table></div>
  <?php
  exit;
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level();ob_start();
  ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=qfu_filters();$rows=qfu_load_rows($db,$input);$from=qfu_valid_date($input['tgl_awal'],date('Y-m-01'));$to=qfu_valid_date($input['tgl_akhir'],date('Y-m-d'));
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Quotation Follow Up'));
  $headers=array(erp_export_label("No"),erp_export_label("Quotation No"),erp_export_label("Quotation Date"),erp_export_label("Valid Until"),erp_export_label("Customer"),erp_export_label("Subject"),erp_export_label("Quote Status"),erp_export_label("Last Follow Up"),erp_export_label("Method"),erp_export_label("Activity"),erp_export_label("Follow Up Status"),erp_export_label("Probability %"),erp_export_label("Next Follow Up"),erp_export_label("Next Action"),erp_export_label("Sales"),erp_export_label("Summary"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;foreach($rows as $row){$values=array($n++,$row->no_sales_quotation,$row->tgl,$row->valid_date,$row->customer_display,$row->subject,$row->quote_status,$row->followup_date,$row->contact_method,$row->activity_type,$row->result_status,(float)$row->probability_percent,$row->next_followup_date,$row->next_action,$row->sales_person ?: $row->sales_id,$row->discussion_summary);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('QUOTATION FOLLOW UP REPORT - SAP SD'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>16,'decimal_columns'=>array('L'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer_id']?:erp_export_all_text(),'Quote Status'=>$input['quote_status']?:erp_export_all_text(),'Follow Up Status'=>$input['followup_status']?:erp_export_all_text(),'Sales'=>$input['sales_person']?:erp_export_all_text(),'Due Only'=>$input['due_only']?'Ya':'Tidak','Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>28,'F'=>35,'G'=>14,'H'=>20,'I'=>14,'J'=>24,'K'=>18,'L'=>12,'M'=>20,'N'=>35,'O'=>18,'P'=>45)));
  $tmp=erpkb_excel_temp_file('quotation_follow_up_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="quotation_follow_up_'.$from.'_sd_'.$to.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}

qfu_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
