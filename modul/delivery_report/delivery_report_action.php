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
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include "delivery_report_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $id=(int)dr_input('id');
    $h=$db->fetch(dr_select_sql().dr_base_sql()." WHERE sj.id=? LIMIT 1",array($id));
    if(!$h){echo '<div class="alert alert-warning">Delivery tidak ditemukan.</div>';break;}
    $items=$db->query("SELECT d.*,b.nm_barang FROM surat_jalan_detail d LEFT JOIN barang b ON b.kd_barang=COALESCE(d.material_code,d.kode_barang) WHERE d.surat_jalan_id=? ORDER BY d.line_no,d.row_no,d.id",array($id));
    $gis=$db->query("SELECT * FROM erp_goods_issue_delivery WHERE reference_surat_jalan=? OR id=? ORDER BY posting_date,id",array($h->no_surat_jalan,(int)$h->gi_id));
    $pls=$db->query("SELECT * FROM packing_list WHERE no_sj=? OR id=? ORDER BY date_created,id",array($h->no_surat_jalan,(int)$h->packing_list_id));
    echo '<h3 style="margin-top:0">'.dr_h($h->no_surat_jalan).' <small>'.sd_h('sales_delivery_report', 'Delivery Report').'</small></h3><div class="row"><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.dr_h($h->customer_name).'<br><small>'.dr_h($h->customer_address).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_order', 'Sales Order').'</strong><br>'.dr_h($h->no_sales_order?:'-').'<br><small>PO '.dr_h($h->no_po?:'-').'</small></div><div class="col-sm-3"><strong>Dates</strong><br>Doc '.dr_h($h->document_date?:$h->tgl_surat_jalan).'<br><small>Post '.dr_h($h->posting_date?:'-').' / Ship '.dr_h($h->tgl_kirim?:'-').'</small></div><div class="col-sm-3"><strong>'.sd_h('common_status', 'Status').'</strong><br>'.dr_status_badge($h->status).' '.dr_gi_badge($h->gi_status).'<br><small>Movement '.dr_h($h->movement_type?:'601').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><div class="well well-sm"><strong>Shipped Qty</strong><br>'.dr_num($h->shipped_qty,4).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>GI Qty</strong><br>'.dr_num($h->gi_qty,4).' ('.dr_num($h->gi_percent).'%)</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Gross Weight</strong><br>'.dr_num($h->gross_weight,5).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Customs Docs</strong><br>'.(int)$h->customs_doc_count.'</div></div></div>';
    echo '<h4>Delivery Items & Customs Trace</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Line</th><th>'.sd_h('sales_material', 'Material').'</th><th>Batch/Lot</th><th class="text-right">Order Qty</th><th class="text-right">Ship Qty</th><th>'.sd_h('sales_uom', 'UOM').'</th><th>BC Type</th><th>BC No/Date</th><th>HS Code</th><th class="text-right">Gross</th><th>Storage</th></tr></thead><tbody>';
    foreach($items as $it)echo '<tr><td>'.(int)$it->line_no.'</td><td><strong>'.dr_h($it->material_code?:$it->kode_barang).'</strong><br><small>'.dr_h($it->material_name?:$it->nama_barang?:$it->nm_barang).'</small></td><td>'.dr_h(trim($it->batch_no.' / '.$it->lot_no,' /')).'</td><td class="text-right">'.dr_num($it->qty_order,4).'</td><td class="text-right">'.dr_num($it->qty_kirim,4).'</td><td>'.dr_h($it->satuan).'</td><td>'.dr_h($it->bc_document_type?:'-').'</td><td>'.dr_h($it->bc_document_no?:'-').'<br><small>'.dr_h($it->bc_document_date?:'-').'</small></td><td>'.dr_h($it->hs_code?:'-').'</td><td class="text-right">'.dr_num($it->gross_weight,5).'</td><td>'.dr_h($it->plant_id.'/'.$it->storage_location_id.'/'.$it->storage_bin_id).'</td></tr>';
    echo '</tbody></table><div class="row"><div class="col-md-6"><h4>'.sd_h('sales_packing_list', 'Packing List').'</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_packing_list', 'Packing List').'</th><th>'.sd_h('sales_date', 'Date').'</th><th>'.sd_h('common_status', 'Status').'</th><th>'.sd_h('sales_vehicle', 'Vehicle').'</th></tr></thead><tbody>';
    foreach($pls as $pl)echo '<tr><td>'.dr_h($pl->no_packing_list).'</td><td>'.dr_h($pl->date_created).'</td><td>'.dr_h($pl->status).'</td><td>'.dr_h($pl->vehicle_no).'</td></tr>';
    echo '</tbody></table></div><div class="col-md-6"><h4>Goods Issue / PGI</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>GI No</th><th>Posting</th><th>'.sd_h('common_status', 'Status').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>BC Out</th></tr></thead><tbody>';
    foreach($gis as $g)echo '<tr><td>'.dr_h($g->gi_no).'</td><td>'.dr_h($g->posting_date).'</td><td>'.dr_h($g->status).'</td><td class="text-right">'.dr_num($g->total_qty,4).'</td><td>'.dr_h($g->outbound_bc_type.' '.$g->outbound_no_daftar).'<br><small>'.dr_h($g->outbound_bc_purpose).'</small></td></tr>';
    echo '</tbody></table></div></div>';
    if($h->keterangan)echo '<div class="alert alert-info"><strong>Keterangan:</strong> '.dr_h($h->keterangan).'</div>';
    break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $f=dr_filters();$rows=dr_load_rows($db,$f,0,0);$excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Delivery Report'));
    $heads=array(erp_export_label("No"),erp_export_label("Surat Jalan"),erp_export_label("Document Date"),erp_export_label("Posting Date"),erp_export_label("Sales Order"),erp_export_label("Customer"),erp_export_label("Customer PO"),erp_export_label("Status"),erp_export_label("GI Status"),erp_export_label("Movement"),erp_export_label("Packing List"),erp_export_label("GI No"),erp_export_label("Ship Qty"),erp_export_label("GI Qty"),erp_export_label("GI %"),erp_export_label("Items"),erp_export_label("Gross Weight"),erp_export_label("Net Weight"),erp_export_label("BC Type"),erp_export_label("BC Docs"),erp_export_label("Shipping Point"),erp_export_label("Vehicle"),erp_export_label("Driver"),erp_export_label("Ship To"),erp_export_label("Remarks"));
    foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;
    foreach($rows as $row){$vals=array($n++,$row->no_surat_jalan,$row->document_date?:$row->tgl_surat_jalan,$row->posting_date,$row->no_sales_order,$row->customer_name,$row->no_po,strtoupper($row->status),$row->gi_status?:'NOT POSTED',$row->movement_type,$row->packing_docs,$row->gi_no,(float)$row->shipped_qty,(float)$row->gi_qty,(float)$row->gi_percent,(int)$row->item_count,(float)$row->gross_weight,(float)$row->net_weight,$row->outbound_bc_type?:$row->bc_types,$row->outbound_no_daftar?:$row->bc_docs,$row->shipping_point,$row->no_kendaraan?:$row->no_polisi,$row->sopir,$row->alamat_pengiriman,$row->keterangan);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('DELIVERY REPORT - SAP SD SHIPPING'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>25,'numeric_columns'=>array('M','N','O','P','Q','R'),'filters'=>array('Posting Date'=>$f['tgl_awal'].' s/d '.$f['tgl_akhir'],'Customer'=>$f['customer']?:erp_export_all_text(),'Status'=>$f['status']?:erp_export_all_text(),'GI Status'=>$f['gi_status']?:erp_export_all_text(),'BC Type'=>$f['bc_type']?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('delivery_report_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="delivery_report_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
