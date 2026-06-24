<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
function lpwa_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function lpwa_num($value, $dec = 2) { return number_format((float)$value, $dec, ',', '.'); }
function lpwa_input($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }

function lpwa_rows($db, $tanggal, $keyword = '') {
  $where = "";
  $filter = array();
  if ($keyword !== '') {
    $where .= " AND (ipd.material_code LIKE ? OR ipd.material_name LIKE ? OR ip.production_no LIKE ? OR ipt.no_aju LIKE ? OR ipt.no_bpb LIKE ? OR ipt.no_dokpab LIKE ?) ";
    $kw = '%'.$keyword.'%';
    for ($i=0; $i<6; $i++) $filter[] = $kw;
  }
  $sql = "
    SELECT w.material_code,w.material_name,w.uom,
           SUM(w.wip_qty) AS jumlah,
           COUNT(DISTINCT w.production_no) AS po_count,
           COUNT(DISTINCT w.process_label) AS process_count,
           GROUP_CONCAT(DISTINCT w.process_label ORDER BY w.process_label SEPARATOR ', ') AS process_list
    FROM (
      SELECT ipt.id issue_trace_id,ip.production_no,ipd.material_code,ipd.material_name,ipd.uom,ipt.no_bpb,ipt.no_aju,ipt.no_dokpab,
             CASE WHEN COALESCE(gr.consumed_qty,0)>0 THEN 'Partial GR Production' ELSE 'Issued to Production' END process_label,
             GREATEST(COALESCE(ipt.qty,0)-COALESCE(gr.consumed_qty,0)-COALESCE(scr.scrap_qty,0),0) wip_qty
      FROM erp_issue_production_trace ipt
      JOIN erp_issue_production ip ON ip.id=ipt.issue_id AND ip.status='POSTED' AND ip.posting_date<=?
      JOIN erp_issue_production_detail ipd ON ipd.id=ipt.issue_detail_id
      LEFT JOIN (SELECT gt.source_issue_trace_id,SUM(gt.qty) consumed_qty FROM erp_gr_production_trace gt JOIN erp_gr_production gr ON gr.id=gt.gr_id AND gr.status='POSTED' AND gr.posting_date<=? GROUP BY gt.source_issue_trace_id) gr ON gr.source_issue_trace_id=ipt.id
      LEFT JOIN (SELECT pst.source_issue_trace_id,SUM(pst.qty) scrap_qty FROM erp_production_scrap_trace pst JOIN production_order_confirmation pc ON pc.id_confirmation=pst.confirmation_id AND pc.status='POSTED' AND pc.posting_date<=? GROUP BY pst.source_issue_trace_id) scr ON scr.source_issue_trace_id=ipt.id
      WHERE 1=1 $where
    ) w
    WHERE w.wip_qty > 0
    GROUP BY w.material_code,w.material_name,w.uom
    ORDER BY w.material_code";
  return $db->query($sql, array_merge(array($tanggal,$tanggal,$tanggal), $filter));
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act) {
  case 'detail':
    $tanggal = lpwa_input('tanggal', date('Y-m-d'));
    $material = lpwa_input('material_code');
    $q = $db->query("
      SELECT ip.issue_no,ip.production_no,ip.document_date,ip.posting_date,ip.reference_no,ip.status,
             po.material_code output_material_code,po.material_name output_material_name,po.status po_status,po.order_qty,po.uom output_uom,
             pc.confirmation_no,pc.operation_no,pc.work_center,pc.operation_name,pc.posting_date confirmation_posting_date,
             ipd.material_code,ipd.material_name,ipd.uom,ipt.id issue_trace_id,ipt.stock_layer_id,ipt.qty issued_qty,ipt.no_bpb,ipt.no_aju,ipt.no_dokpab,ipt.jenis_dokpab,ipt.hs_code,ipt.lot_no,
             b.kd_kategori,kat.nm_kategori,
             ep.plant_code,es.storage_code,eb.bin_code,
             COALESCE(gr.consumed_qty,0) consumed_qty,COALESCE(scr.scrap_qty,0) scrap_qty,
             GREATEST(COALESCE(ipt.qty,0)-COALESCE(gr.consumed_qty,0)-COALESCE(scr.scrap_qty,0),0) wip_qty
      FROM erp_issue_production_trace ipt
      JOIN erp_issue_production ip ON ip.id=ipt.issue_id AND ip.status='POSTED' AND ip.posting_date<=?
      JOIN erp_issue_production_detail ipd ON ipd.id=ipt.issue_detail_id
      LEFT JOIN production_order po ON po.id_production_order=ip.production_id
      LEFT JOIN (
        SELECT id_production_order,MAX(id_confirmation) latest_confirmation_id
        FROM production_order_confirmation
        WHERE status='POSTED' AND posting_date<=?
        GROUP BY id_production_order
      ) lpc ON lpc.id_production_order=ip.production_id
      LEFT JOIN production_order_confirmation pc ON pc.id_confirmation=lpc.latest_confirmation_id
      LEFT JOIN barang b ON b.kd_barang=ipd.material_code
      LEFT JOIN kategori kat ON kat.kd_kategori=b.kd_kategori
      LEFT JOIN erp_plant ep ON ep.id=ipt.plant_id
      LEFT JOIN erp_storage_location es ON es.id=ipt.storage_location_id
      LEFT JOIN erp_storage_bin eb ON eb.id=ipt.storage_bin_id
      LEFT JOIN (SELECT gt.source_issue_trace_id,SUM(gt.qty) consumed_qty FROM erp_gr_production_trace gt JOIN erp_gr_production gr ON gr.id=gt.gr_id AND gr.status='POSTED' AND gr.posting_date<=? GROUP BY gt.source_issue_trace_id) gr ON gr.source_issue_trace_id=ipt.id
      LEFT JOIN (SELECT pst.source_issue_trace_id,SUM(pst.qty) scrap_qty FROM erp_production_scrap_trace pst JOIN production_order_confirmation pc2 ON pc2.id_confirmation=pst.confirmation_id AND pc2.status='POSTED' AND pc2.posting_date<=? GROUP BY pst.source_issue_trace_id) scr ON scr.source_issue_trace_id=ipt.id
      WHERE ipd.material_code=?
      HAVING wip_qty>0
      ORDER BY ip.production_no,ip.issue_no,ipt.id", array($tanggal,$tanggal,$tanggal,$tanggal,$material));
    ?>
    <style>.lpw-detail-table th,.lpw-detail-table td{font-size:12px;vertical-align:middle!important}.lpw-detail-table th{background:#f8fafc}.lpw-detail-head{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:12px}.lpw-muted{color:#64748b;font-size:11px}.lpw-expanded{display:none;background:#f8fafc}.lpw-trace-box{border:1px solid #e5e7eb;border-radius:10px;background:#fff;padding:10px}</style>
    <div class="lpw-detail-head"><h4 style="margin:0"><?=customs_h('wip_position_detail','Detail Posisi WIP');?> - <?=lpwa_h($material);?></h4><small><?=customs_h('wip_position_detail_desc','Posisi sampai tanggal <?=lpwa_h($tanggal);?>. Jumlah = issued ke produksi - GR production - scrap.');?></small></div>
    <div class="table-responsive"><table class="table table-bordered table-condensed lpw-detail-table"><thead><tr><th><?=customs_h('no','No');?></th><th><?=customs_h('production_order','Production Order');?></th><th><?=customs_h('current_process','Proses Saat Ini');?></th><th><?=customs_h('issue_doc','Issue Doc');?></th><th><?=customs_h('wip_material','Material WIP');?></th><th><?=customs_h('output_order','Output Order');?></th><th><?=customs_h('origin_bc_document','Dokumen BC Asal');?></th><th><?=customs_h('origin_location','Lokasi Asal');?></th><th class="text-right"><?=customs_h('issued','Issued');?></th><th class="text-right"><?=customs_h('gr_scrap','GR/Scrap');?></th><th class="text-right"><?=customs_h('wip','WIP');?></th><th><?=customs_h('remarks','Keterangan');?></th></tr></thead><tbody>
    <?php $no=1;$total=0;foreach($q as $r){$total+=(float)$r->wip_qty;$proc=$r->work_center?trim((string)$r->work_center.' / '.(string)$r->operation_name,' /'):'Issued to Production';$loc=trim((string)$r->plant_code.' / '.(string)$r->storage_code.' / '.(string)$r->bin_code,' /');$bc=trim((string)$r->jenis_dokpab.' '.$r->no_dokpab.' / '.(string)$r->no_aju,' /');$isOutputTrace=in_array($r->kd_kategori,array('K02','K07')); ?>
      <tr><td><?=intval($no++);?></td><td><strong><?=lpwa_h($r->production_no);?></strong><br><small class="lpw-muted"><?=lpwa_h($r->po_status ?: '-');?></small></td><td><?=lpwa_h($proc);?><br><small class="lpw-muted"><?=lpwa_h($r->confirmation_no ?: 'Belum confirmation');?></small></td><td><strong><?=lpwa_h($r->issue_no);?></strong><br><small class="lpw-muted"><?=lpwa_h($r->posting_date);?></small></td><td><strong><?=lpwa_h($r->material_code);?></strong><br><small class="lpw-muted"><?=lpwa_h($r->material_name);?> | <?=lpwa_h($r->nm_kategori);?></small></td><td><?=lpwa_h($r->output_material_code ?: '-');?><br><small class="lpw-muted"><?=lpwa_h($r->output_material_name ?: '');?></small></td><td><?=lpwa_h($bc ?: '-');?><br><small class="lpw-muted"><?=lpwa_h($r->no_bpb ?: '');?><?=($r->lot_no?' | Lot '.$r->lot_no:'');?></small></td><td><?=lpwa_h($loc ?: '-');?></td><td class="text-right"><?=lpwa_num($r->issued_qty,5);?></td><td class="text-right"><?=lpwa_num((float)$r->consumed_qty+(float)$r->scrap_qty,5);?></td><td class="text-right"><?php if($isOutputTrace){ ?><a href="javascript:void(0)" onclick="$(this).closest('tr').next('.lpw-expanded').toggle();return false;" style="font-weight:700;text-decoration:underline"><?=lpwa_num($r->wip_qty,5);?></a><br><small class="lpw-muted">klik dokumen BC</small><?php } else { echo lpwa_num($r->wip_qty,5); } ?></td><td><?=lpwa_h($r->reference_no ?: '');?></td></tr>
      <?php if($isOutputTrace){ ?>
      <tr class="lpw-expanded"><td colspan="12"><div class="lpw-trace-box"><h5 style="margin-top:0;font-weight:700"><?=customs_h('inherited_bc_detail_sfg_fg','Detail Dokumen BC Inherited untuk SFG/FG');?></h5><div class="table-responsive"><table class="table table-bordered table-condensed" style="margin-bottom:0"><thead><tr><th><?=customs_h('no','No');?></th><th><?=customs_h('source_raw_material','Bahan Baku Asal');?></th><th class="text-right"><?=customs_h('trace_qty','Qty Trace');?></th><th>UOM</th><th><?=customs_h('bc_document','Dokumen BC');?></th><th><?=customs_h('aju_no_short','No AJU');?></th><th><?=customs_h('bpb_no','No BPB');?></th><th><?=customs_h('lot_batch','Lot/Batch');?></th><th><?=customs_h('hs_code','HS Code');?></th><th><?=customs_h('trace','Trace');?></th></tr></thead><tbody>
      <?php $tr=$db->query("SELECT gt.* FROM erp_gr_production_trace gt WHERE gt.output_stock_layer_id=? ORDER BY gt.raw_material_code,gt.no_aju,gt.id",array($r->stock_layer_id));$tn=1;$has=false;foreach($tr as $t){$has=true; ?>
        <tr><td><?=intval($tn++);?></td><td><strong><?=lpwa_h($t->raw_material_code ?: $t->source_material_code);?></strong><br><small class="lpw-muted"><?=lpwa_h($t->raw_material_name ?: $t->source_material_name);?></small></td><td class="text-right"><?=lpwa_num($t->qty,5);?></td><td><?=lpwa_h($t->uom);?></td><td><?=lpwa_h(trim((string)$t->jenis_dokpab.' '.(string)$t->no_dokpab) ?: '-');?></td><td><?=lpwa_h($t->no_aju ?: '-');?></td><td><?=lpwa_h($t->no_bpb ?: '-');?></td><td><?=lpwa_h($t->lot_no ?: '-');?></td><td><?=lpwa_h($t->hs_code ?: '-');?></td><td><span class="label label-<?=($t->trace_source==='INHERITED'?'primary':'info');?>"><?=lpwa_h($t->trace_source);?></span></td></tr>
      <?php } if(!$has){ ?><tr><td colspan="10" class="text-center text-muted"><?=customs_h('inherited_bc_trace_not_available','Trace dokumen BC inherited belum tersedia untuk layer ini.');?></td></tr><?php } ?>
      </tbody></table></div></div></td></tr>
      <?php } ?>
    <?php } if($no===1){ ?><tr><td colspan="12" class="text-center text-muted">Tidak ada posisi WIP untuk material ini.</td></tr><?php } ?>
    </tbody><tfoot><tr><th colspan="10" class="text-right">Total WIP</th><th class="text-right"><?=lpwa_num($total,5);?></th><th></th></tr></tfoot></table></div>
    <?php break;
  case 'excel':
    $initial=ob_get_level();ob_start();ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require "../../inc/lib/PHPExcel.php"; require_once "../../inc/excel_style_helper.php"; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $tanggal=lpwa_input('tanggal',date('Y-m-d'));$keyword=lpwa_input('keyword');$rows=lpwa_rows($db,$tanggal,$keyword);$company=defined('namaPT')?namaPT:(defined('shortTittle')?shortTittle:'NAMA_PT');
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Posisi WIP'));$headers=array(erp_export_label("NO"),erp_export_label("KODE\\nBARANG"),erp_export_label("NAMA\\nBARANG"),erp_export_label("SAT"),erp_export_label("JUMLAH"),erp_export_label("KETERANGAN"));$nums=array('..(3)..','..(4)..','..(5)..','..(6)..','..(7)..','..(8)..');$headerStart=5;$headerEnd=6;$firstData=7;
    $sh->mergeCells('A1:F1')->setCellValue('A1','KAWASAN BERIKAT '.$company);$sh->mergeCells('A2:F2')->setCellValue('A2','LAPORAN POSISI BARANG DALAM PROSES (WIP)');$sh->mergeCells('A3:F3')->setCellValue('A3','PERIODE: S.D. '.$tanggal);
    foreach($headers as $i=>$h)$sh->setCellValueByColumnAndRow($i,$headerStart,$h);foreach($nums as $i=>$h)$sh->setCellValueByColumnAndRow($i,$headerEnd,$h);
    $r=$firstData;$n=1;$total=0;foreach($rows as $row){$ket=array();if((int)$row->po_count>0)$ket[]=(int)$row->po_count.' production order';if((int)$row->process_count>0)$ket[]=(int)$row->process_count.' posisi proses';if($row->process_list)$ket[]=$row->process_list;$vals=array($n++,$row->material_code,$row->material_name,$row->uom,(float)$row->jumlah,implode(' | ',$ket));foreach($vals as $i=>$v)$sh->setCellValueByColumnAndRow($i,$r,$v);$total+=(float)$row->jumlah;$r++;}
    $last=max($firstData,$r-1);$sum=$r+1;$sh->mergeCells('A'.$sum.':D'.$sum);$sh->setCellValue('A'.$sum, erp_export_label('TOTAL'));$sh->setCellValue('E'.$sum,$total);
    $sh->getStyle('A1:F3')->getFont()->setBold(true)->setSize(12);$sh->getStyle('A1:F3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);$sh->getStyle('A'.$headerStart.':F'.$headerEnd)->getFont()->setBold(true);$sh->getStyle('A'.$headerStart.':F'.$headerEnd)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);$sh->getStyle('A'.$headerStart.':F'.$last)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('111827');$sh->getStyle('A'.$firstData.':F'.$last)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setWrapText(true);$sh->getStyle('A'.$firstData.':A'.$last)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);$sh->getStyle('E'.$firstData.':E'.$sum)->getNumberFormat()->setFormatCode('#,##0.00');$sh->getStyle('E'.$firstData.':E'.$sum)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);$sh->getStyle('A'.$sum.':F'.$sum)->getFont()->setBold(true);$sh->getStyle('A'.$sum.':F'.$sum)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');$sh->getStyle('A'.$sum.':F'.$sum)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)->getColor()->setRGB('111827');
    foreach(array('A'=>8,'B'=>18,'C'=>34,'D'=>10,'E'=>16,'F'=>42) as $col=>$w)$sh->getColumnDimension($col)->setWidth($w);$sh->getRowDimension($headerStart)->setRowHeight(35);$sh->freezePane('A'.$firstData);$sh->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);$sh->getPageSetup()->setFitToWidth(1);$sh->getPageSetup()->setFitToHeight(0);
    $tmp=erpkb_excel_temp_file('laporan_posisi_wip_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="laporan_posisi_wip_sd_'.$tanggal.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
