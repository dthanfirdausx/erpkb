<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();
function lpk_trace_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function lpk_trace_num($value, $dec = 5) { return number_format((float)$value, $dec, ',', '.'); }
function lpk_trace_money($value) { return number_format((float)$value, 2, ',', '.'); }
switch ($_GET["act"]) {
  case "trace_nilai":
    $sourceType = isset($_POST['source_type']) ? trim((string)$_POST['source_type']) : '';
    $sourceId = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
    $sourceDetailId = isset($_POST['source_detail_id']) ? (int)$_POST['source_detail_id'] : 0;
    $materialCode = isset($_POST['material_code']) ? trim((string)$_POST['material_code']) : '';
    $docNo = isset($_POST['doc_no']) ? trim((string)$_POST['doc_no']) : '';
    $rows = array();
    $header = null;

    if ($sourceType === 'gid' && $sourceDetailId > 0) {
      $header = $db->fetch("SELECT gi.gi_no doc_no,gi.posting_date doc_date,gi.customer_name partner,d.material_code,d.material_name,d.qty,d.amount,d.uom
                            FROM erp_goods_issue_delivery_detail d
                            JOIN erp_goods_issue_delivery gi ON gi.id=d.gi_id
                            WHERE d.id=? LIMIT 1", array($sourceDetailId));
      $rows = $db->query("SELECT t.*,ep.plant_code,es.storage_code,eb.bin_code
                          FROM erp_goods_issue_delivery_trace t
                          LEFT JOIN erp_plant ep ON ep.id=t.plant_id
                          LEFT JOIN erp_storage_location es ON es.id=t.storage_location_id
                          LEFT JOIN erp_storage_bin eb ON eb.id=t.storage_bin_id
                          WHERE t.gi_detail_id=?
                          ORDER BY t.id", array($sourceDetailId));
      $rawRows = $db->query("SELECT gt.*,t.stock_layer_id output_stock_layer_id,t.material_doc_id output_material_doc_id
                             FROM erp_goods_issue_delivery_trace t
                             JOIN erp_gr_production_trace gt ON gt.output_stock_layer_id=t.stock_layer_id
                             WHERE t.gi_detail_id=?
                             ORDER BY gt.trace_source,gt.raw_material_code,gt.id", array($sourceDetailId));
    } else {
      $header = $db->fetch("SELECT v.no_sj doc_no,v.tgl_sj doc_date,v.nama partner,v.kode material_code,v.nm_barang material_name,v.jumlah qty,v.nilai amount,v.satuan uom
                            FROM vpengeluaranbyjenisdokpab v
                            WHERE v.id=? LIMIT 1", array($sourceDetailId));
      $params = array();
      $where = " WHERE (dt.direction='OUT' OR dt.qty<0) ";
      if ($docNo !== '') {
        $where .= " AND (dt.no_ref=? OR dt.ref_pengganti=? OR dt.no_bpb=? OR dt.remark LIKE ?) ";
        $params[] = $docNo; $params[] = $docNo; $params[] = $docNo; $params[] = '%'.$docNo.'%';
      }
      if ($materialCode !== '') {
        $where .= " AND (dt.kd_barang=? OR dt.destination_material_code=?) ";
        $params[] = $materialCode; $params[] = $materialCode;
      }
      $rows = $db->query("SELECT dt.id_detail AS material_doc_id,ABS(dt.qty) qty,dt.price,dt.amount,dt.stock_type,dt.plant_id,dt.storage_location_id,dt.storage_bin_id,
                                 dt.no_bpb,dt.no_aju,dt.no_dokpab,'' AS jenis_dokpab,'' AS lot_no,ep.plant_code,es.storage_code,eb.bin_code
                          FROM detail_transaksi dt
                          LEFT JOIN erp_plant ep ON ep.id=dt.plant_id
                          LEFT JOIN erp_storage_location es ON es.id=dt.storage_location_id
                          LEFT JOIN erp_storage_bin eb ON eb.id=dt.storage_bin_id
                          $where
                          ORDER BY dt.posting_date DESC,dt.id_detail DESC
                          LIMIT 100", $params);

      if (!$rows || $rows->rowCount() === 0) {
        $fallbackParams = array($materialCode);
        $rows = $db->query("SELECT sl.id AS material_doc_id,sl.qty_masuk qty,0 price,0 amount,sl.stock_type,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,
                                   sl.no_bpb,sl.no_aju,sl.no_dokpab,sl.jenis_dokpab,'' AS lot_no,ep.plant_code,es.storage_code,eb.bin_code
                            FROM stock_layer sl
                            LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
                            LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
                            LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
                            WHERE sl.kode=? AND (COALESCE(sl.no_bpb,'')<>'' OR COALESCE(sl.no_aju,'')<>'' OR COALESCE(sl.no_dokpab,'')<>'')
                            ORDER BY sl.tgl_masuk ASC,sl.id ASC
                            LIMIT 20", $fallbackParams);
      }
    }

    ?>
    <style>.lpk-trace-head{border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px;padding:14px;margin-bottom:14px}.lpk-trace-table th,.lpk-trace-table td{font-size:12px;vertical-align:middle!important}.lpk-trace-table th{background:#f8fafc}</style>
    <?php if ($header) { ?>
      <div class="lpk-trace-head">
        <div class="row">
          <div class="col-sm-8"><h3 style="margin-top:0"><?=lpk_trace_h($header->doc_no);?> <small><?=lpk_trace_h($header->doc_date);?></small></h3><p><?=lpk_trace_h($header->partner);?> | <?=lpk_trace_h($header->material_code.' - '.$header->material_name);?></p></div>
          <div class="col-sm-4 text-right"><h3 style="margin-top:0"><?=lpk_trace_num($header->qty,2).' '.lpk_trace_h($header->uom);?></h3><p>Nilai <?=lpk_trace_money($header->amount);?></p></div>
        </div>
      </div>
    <?php } ?>
    <div class="alert alert-info"><strong><?=customs_h('origin_document_trace','Trace dokumen asal.');?></strong> <?=customs_h('origin_document_trace_desc','Data di bawah menunjukkan layer/transaksi asal yang membawa No BPB, No Aju, dan Dokumen BC sumber. Untuk data legacy yang belum punya trace FIFO eksplisit, sistem menampilkan referensi terbaik dari material document/stock layer.');?></div>
    <div class="table-responsive"><table class="table table-bordered table-condensed lpk-trace-table">
      <thead><tr><th><?=customs_h('layer_material_doc','Layer / Material Doc');?></th><th class="text-right"><?=customs_h('qty','Qty');?></th><th class="text-right"><?=customs_h('price','Price');?></th><th class="text-right"><?=customs_h('amount','Amount');?></th><th><?=customs_h('bpb_no','No BPB');?></th><th><?=customs_h('aju_no_short','No Aju');?></th><th><?=customs_h('origin_bc_document','Dokumen BC Asal');?></th><th><?=customs_h('lot','Lot');?></th><th>Location</th><th><?=customs_h('stock_type','Stock Type');?></th></tr></thead><tbody>
      <?php $count=0; foreach ($rows as $r) { $count++; ?>
        <tr>
          <td>#<?=lpk_trace_h($r->stock_layer_id ?? $r->material_doc_id);?><br><small>MatDoc <?=lpk_trace_h($r->material_doc_id);?></small></td>
          <td class="text-right"><?=lpk_trace_num($r->qty);?></td>
          <td class="text-right"><?=lpk_trace_num($r->price);?></td>
          <td class="text-right"><?=lpk_trace_money($r->amount);?></td>
          <td><?=lpk_trace_h($r->no_bpb ?: '-');?></td>
          <td><?=lpk_trace_h($r->no_aju ?: '-');?></td>
          <td><?=lpk_trace_h(trim(($r->jenis_dokpab ?: '').' '.($r->no_dokpab ?: '')) ?: '-');?></td>
          <td><?=lpk_trace_h($r->lot_no ?: '-');?></td>
          <td><?=lpk_trace_h(trim(($r->plant_code ?: '').' / '.($r->storage_code ?: '').' / '.($r->bin_code ?: ''), ' /') ?: '-');?></td>
          <td><?=lpk_trace_h($r->stock_type ?: '-');?></td>
        </tr>
      <?php } if ($count===0) { ?>
        <tr><td colspan="10" class="text-center text-muted"><?=customs_h('origin_trace_not_found','Trace dokumen asal belum ditemukan untuk baris ini.');?></td></tr>
      <?php } ?>
      </tbody></table></div>
    <?php if (isset($rawRows) && $rawRows && $rawRows->rowCount() > 0) { ?>
      <h4 style="margin-top:18px"><?=customs_h('output_raw_sfg_trace','Trace Bahan Baku / Barang Setengah Jadi Pembentuk Output');?></h4>
      <div class="table-responsive"><table class="table table-bordered table-condensed lpk-trace-table">
        <thead><tr><th><?=customs_h('output_layer','Output Layer');?></th><th><?=customs_h('source_material','Source Material');?></th><th><?=customs_h('raw_sfg_material','Raw/SFG Material');?></th><th class="text-right"><?=customs_h('qty','Qty');?></th><th>UOM</th><th><?=customs_h('bpb_no','No BPB');?></th><th><?=customs_h('aju_no_short','No Aju');?></th><th><?=customs_h('origin_bc_document','Dokumen BC Asal');?></th><th><?=customs_h('lot','Lot');?></th><th><?=customs_h('trace_source','Trace Source');?></th></tr></thead><tbody>
        <?php foreach ($rawRows as $rr) { ?>
          <tr>
            <td>#<?=lpk_trace_h($rr->output_stock_layer_id);?></td>
            <td><strong><?=lpk_trace_h($rr->source_material_code);?></strong><br><small><?=lpk_trace_h($rr->source_material_name);?></small></td>
            <td><strong><?=lpk_trace_h($rr->raw_material_code);?></strong><br><small><?=lpk_trace_h($rr->raw_material_name);?></small></td>
            <td class="text-right"><?=lpk_trace_num($rr->qty);?></td>
            <td><?=lpk_trace_h($rr->uom);?></td>
            <td><?=lpk_trace_h($rr->no_bpb ?: '-');?></td>
            <td><?=lpk_trace_h($rr->no_aju ?: '-');?></td>
            <td><?=lpk_trace_h(trim(($rr->jenis_dokpab ?: '').' '.($rr->no_dokpab ?: '')) ?: '-');?></td>
            <td><?=lpk_trace_h($rr->lot_no ?: '-');?></td>
            <td><?=lpk_trace_h($rr->trace_source);?></td>
          </tr>
        <?php } ?>
        </tbody></table></div>
    <?php } ?>
    <?php
    break;
    case "detail_bahan_baku":
   $tgl_akhir = $_POST['tgl_sj'];
   $tgl_awal = date_create($tgl_akhir)->modify('-120 days')->format('Y-m-d');
   $kode_bj = $_POST['kode_bj'];
   $wh_tgl = "and (p.tgl_bpb between '$tgl_awal' and '$tgl_akhir') ";
  // $wh_tgl = " and p.tgl_bpb < '$tgl_akhir' ";
    echo "<table style='width:100%' border='1'>
                              <thead>
                               <tr>
                                <th class='text-center'><?=customs_h('no','No');?></th>
                                <th class='text-center'>Kode / Nama Barang</th>
                                <th class='text-center'>No Dokpab</th>
                                <th class='text-center'>No Aju</th>
                                <th class='text-center'>Jenis Dokpab</th>
                                <th class='text-center'>Tgl Dokpab</th>
                                <th class='text-center'>Qty</th>
                               </tr>
                              </thead>
                             ";
                             $no2=1;
   $q = $db->query("select d.kodebb,d.jumlah from bom_detail d join bom b on b.id=d.id_bom where b.kodebj='$kode_bj' ");
   if ($q->rowCount()>0) {
     $no2=1;
     foreach ($q as $k) {
     $qq = $db->query("select b.nm_barang,p.tgl_dokpab,d.kode,d.jumlah, p.no_dokpab,p.jenis_dokpab,p.no_aju from pemasukan_detail d join pemasukan p on p.no_bpb=d.no_bpb join barang b on b.kd_barang=d.kode where d.kode='$k->kodebb' $wh_tgl and p.jenis_dokpab!='Saldo Awal' limit 1 ");
     foreach ($qq as $kk) { 
       echo "<tr>
              <td style='padding:3px'>$no2</td>
              <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
              <td style='padding:3px'>$kk->no_dokpab</td>
              <td style='padding:3px'>$kk->no_aju</td>
              <td style='padding:3px'>$kk->jenis_dokpab</td>
              <td style='padding:3px'>$kk->tgl_dokpab</td>
              <td style='padding:3px'>$kk->jumlah</td>
           </tr>";
     }
     
      $no2++; 
     }
   }else{
      $qq = $db->query("select b.nm_barang,p.tgl_dokpab,d.kode,d.jumlah, p.no_dokpab,p.jenis_dokpab,p.no_aju from pemasukan_detail d join pemasukan p on p.no_bpb=d.no_bpb join barang b on b.kd_barang=d.kode where d.kode='$kode_bj' $wh_tgl and p.jenis_dokpab!='Saldo Awal'  limit 1 ");
       $no2=1;
     foreach ($qq as $kk) {
       echo "<tr>
              <td style='padding:3px'>$no2</td>
              <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
              <td style='padding:3px'>$kk->no_dokpab</td>
              <td style='padding:3px'>$kk->no_aju</td>
              <td style='padding:3px'>$kk->jenis_dokpab</td>
              <td style='padding:3px'>$kk->tgl_dokpab</td>
              <td style='padding:3px'>$kk->jumlah</td>
           </tr>";
     }
   }
   
//echo date_create($date)->modify('-30 days')->format('Y-m-d');
  
                             // $qq = $db->query("select * from v_detail_bahan_baku_produksi where id_produksi_detail='".$_POST['id_produksi_detail']."' ");
                             // foreach ($qq as $kk) {
                             //    echo "<tr>
                             //      <td style='padding:3px'>$no2</td>
                             //      <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
                             //      <td style='padding:3px'>$kk->no_dokpab</td>
                             //      <td style='padding:3px'>$kk->no_aju</td>
                             //      <td style='padding:3px'>$kk->jenis_dokpab</td>
                             //      <td style='padding:3px'>$kk->tgl_dokpab</td>
                             //      <td style='padding:3px'>$kk->jumlah</td>
                             //    </tr>";
                             //    $no2++; 
                             // }
                             echo"</table>";
  break;
  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
  );
  
  
  
   
    $in = $db->insert("bahan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("bahan","no_lap",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("bahan","no_lap",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
   );
   
   
   

    
    
    $up = $db->update("bahan",$data,"no_lap",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
