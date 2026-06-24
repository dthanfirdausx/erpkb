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
include "sales_order_approval_lib.php";

$act=isset($_GET['act'])?$_GET['act']:'';
function soa_json($payload){header('Content-Type: application/json; charset=utf-8');echo json_encode($payload);exit;}

function soa_get_order($id){
  global $db;
  return $db->fetch("SELECT so.*,p.nama AS customer_name,a.id_approval,a.approver,a.approver_group,a.status AS approval_line_status
                     FROM sales_order so
                     LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima
                     LEFT JOIN sales_order_approval a ON a.id_sales_order=so.id_sales_order AND a.approval_level=1
                     WHERE so.id_sales_order=? LIMIT 1",array((int)$id));
}

function soa_can_act($row){
  if(!$row) return false;
  if(soa_is_admin()) return true;
  return $row->approver==='' || $row->approver===null || $row->approver===soa_username() || $row->approver_group===soa_group_level();
}

function soa_render_items($id){
  global $db;
  $items=$db->query("SELECT d.*,b.nm_barang,b.satuan FROM sales_order_detail d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.id_sales_order=? ORDER BY d.id_detail",array((int)$id));
  ?>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('common_no', 'No').'</th><th>'.sd_h('sales_material', 'Material').'</th><th>Store</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">Value</th><th>Remark</th></tr></thead><tbody>
  <?php $no=1;$tq=0;$tv=0;foreach($items as $it){$tq+=(float)$it->qty;$tv+=(float)$it->nilai; ?>
    <tr><td class="text-center"><?=$no++;?></td><td><strong><?=soa_h($it->kd_barang);?></strong><br><small><?=soa_h($it->nm_barang);?></small></td><td><?=soa_h($it->store);?></td><td class="text-right"><?=number_format((float)$it->qty,5,',','.');?></td><td><?=soa_h($it->satuan);?></td><td class="text-right"><?=number_format((float)$it->price,2,',','.');?></td><td class="text-right"><?=number_format((float)$it->nilai,2,',','.');?></td><td><?=soa_h($it->ket);?></td></tr>
  <?php } ?>
  </tbody><tfoot><tr class="bg-gray"><th colspan="3" class="text-right">'.sd_h('sales_total', 'Total').'</th><th class="text-right"><?=number_format($tq,5,',','.');?></th><th colspan="2"></th><th class="text-right"><?=number_format($tv,2,',','.');?></th><th></th></tr></tfoot></table></div>
  <?php
}

function soa_render_history($id){
  global $db;
  $rows=$db->query("SELECT * FROM sales_order_approval_history WHERE id_sales_order=? ORDER BY changed_at DESC,id DESC",array((int)$id));
  ?>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('sales_date', 'Date').'</th><th>Old Status</th><th>New Status</th><th>'.sd_h('common_remarks', 'Remarks').'</th><th>User</th></tr></thead><tbody>
  <?php $count=0;foreach($rows as $row){$count++; ?>
    <tr><td><?=soa_h($row->changed_at);?></td><td><?=soa_h($row->status_lama);?></td><td><?=soa_h($row->status_baru);?></td><td><?=soa_h($row->remarks);?></td><td><?=soa_h($row->changed_by);?></td></tr>
  <?php } if($count===0){ ?><tr><td colspan="5" class="text-center text-muted">Belum ada history approval.</td></tr><?php } ?>
  </tbody></table></div>
  <?php
}

if($act==='detail' || $act==='history'){
  session_check_json();
  $id=(int)soa_input('id');
  $row=soa_get_order($id);
  if(!$row){echo '<div class="alert alert-danger">Sales Order tidak ditemukan.</div>';exit;}
  if($act==='history'){
    echo '<h4>'.soa_h($row->no_sales_order).' <small>'.soa_h($row->customer_name).'</small></h4>';
    soa_render_history($id);
    exit;
  }
  ?>
  <div class="row">
    <div class="col-md-8"><h3 style="margin-top:0"><?=soa_h($row->no_sales_order);?> <small><?=soa_h($row->no_po);?></small></h3><p><?=soa_status_label($row->approval_status);?> <span class="text-muted">SO Date <?=soa_h($row->so_date);?> | Delivery <?=soa_h($row->delivery_date);?></span></p></div>
    <div class="col-md-4 text-right"><a href="<?=base_index();?>sales-order/detail/<?=intval($row->id_sales_order);?>" class="btn btn-info btn-sm" target="_blank"><i class="fa fa-eye"></i> Open SO</a></div>
  </div>
  <div class="row">
    <div class="col-md-6"><table class="table table-bordered table-condensed soa-detail-table"><tr><th>'.sd_h('sales_customer', 'Customer').'</th><td><?=soa_h(trim((string)$row->kode_penerima.' - '.(string)$row->customer_name,' -'));?></td></tr><tr><th>'.sd_h('sales_currency', 'Currency').'</th><td><?=soa_h($row->currency);?></td></tr><tr><th>Sales</th><td><?=soa_h($row->sales_id ?: $row->user);?></td></tr><tr><th>Shipping Address</th><td><?=soa_h($row->shipping_address);?></td></tr></table></div>
    <div class="col-md-6"><table class="table table-bordered table-condensed soa-detail-table"><tr><th>Approver</th><td><?=soa_h($row->approver_group ?: $row->approver ?: 'Open approver');?></td></tr><tr><th>Submitted</th><td><?=soa_h(trim((string)$row->submitted_by.' '.$row->submitted_at));?></td></tr><tr><th>'.sd_h('sales_approved', 'Approved').'</th><td><?=soa_h(trim((string)$row->approved_by.' '.$row->approved_at));?></td></tr><tr><th>'.sd_h('sales_rejected', 'Rejected').'</th><td><?=soa_h(trim((string)$row->rejected_by.' '.$row->rejected_at.' '.$row->rejection_reason));?></td></tr></table></div>
  </div>
  <h4>Sales Order Items</h4>
  <?php soa_render_items($id); ?>
  <h4>Approval History</h4>
  <?php soa_render_history($id); ?>
  <?php
  exit;
}

if($act==='approve' || $act==='reject'){
  session_check_json();
  $username=soa_username();
  $id=(int)soa_input('id');
  $note=soa_input('note');
  $row=soa_get_order($id);
  if(!$row) soa_json(array('status'=>'error','error_message'=>'Sales Order tidak ditemukan.'));
  if(!soa_can_act($row)) soa_json(array('status'=>'error','error_message'=>'User tidak memiliki akses approval untuk Sales Order ini.'));
  if(!in_array($row->approval_status,array('PENDING','SUBMITTED'))) soa_json(array('status'=>'error','error_message'=>'Sales Order tidak dalam status pending approval.'));
  $old=$row->approval_status;
  $new=$act==='approve'?'APPROVED':'REJECTED';
  $lineStatus=$act==='approve'?'APPROVED':'REJECTED';
  $db->update('sales_order_approval',array('status'=>$lineStatus,'approval_date'=>date('Y-m-d H:i:s'),'note'=>$note,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id_approval',$row->id_approval);
  $update=array('approval_status'=>$new);
  if($act==='approve'){
    $update['approved_by']=$username;$update['approved_at']=date('Y-m-d H:i:s');$update['status']='APPROVED';
  }else{
    $update['rejected_by']=$username;$update['rejected_at']=date('Y-m-d H:i:s');$update['rejection_reason']=$note;$update['status']='REJECTED';
  }
  $db->update('sales_order',$update,'id_sales_order',$id);
  $db->insert('sales_order_approval_history',array('id_sales_order'=>$id,'id_approval'=>$row->id_approval,'status_lama'=>$old,'status_baru'=>$new,'remarks'=>$note,'changed_by'=>$username));
  if(function_exists('simpan_log')) simpan_log('User '.$username.' '.($act==='approve'?'approve':'reject').' Sales Order '.$row->no_sales_order.' pada '.date('Y-m-d H:i:s'),$username);
  soa_json(array('status'=>'good'));
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level();ob_start();
  ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=soa_filters();$rows=soa_load_rows($db,$input);$from=soa_valid_date($input['tgl_awal'],date('Y-m-01'));$to=soa_valid_date($input['tgl_akhir'],date('Y-m-d'));
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('SO Approval'));
  $headers=array(erp_export_label("No"),erp_export_label("Sales Order"),erp_export_label("SO Date"),erp_export_label("Customer"),erp_export_label("Customer PO"),erp_export_label("Approval Status"),erp_export_label("Approver"),erp_export_label("Approval Date"),erp_export_label("Items"),erp_export_label("Qty"),erp_export_label("Value"),erp_export_label("Currency"),erp_export_label("Sales"),erp_export_label("Note"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;foreach($rows as $row){$values=array($n++,$row->no_sales_order,$row->so_date,$row->customer_name,$row->no_po,$row->approval_status,$row->approver_group ?: $row->approver,$row->approval_date,(float)$row->item_count,(float)$row->total_qty,(float)$row->total_amount,$row->currency,$row->sales_id ?: $row->user,$row->alasan ?: $row->catatan);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('SALES ORDER APPROVAL REPORT - SAP SD'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>14,'numeric_columns'=>array('J'),'money_columns'=>array('K'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer']?:erp_export_all_text(),'Approval Status'=>$input['approval_status']?:erp_export_all_text(),'Sales'=>$input['sales_person']?:erp_export_all_text(),'My Worklist'=>$input['my_worklist']?'Ya':'Tidak','Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>22,'C'=>14,'D'=>28,'E'=>18,'F'=>16,'G'=>18,'H'=>20,'I'=>10,'J'=>14,'K'=>16,'L'=>10,'M'=>18,'N'=>40)));
  $tmp=erpkb_excel_temp_file('sales_order_approval_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="sales_order_approval_'.$from.'_sd_'.$to.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}

soa_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
