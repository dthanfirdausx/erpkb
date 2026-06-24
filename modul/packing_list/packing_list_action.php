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
session_start();
include "../../inc/config.php";
include "packing_list_lib.php";
session_check_json();
switch ($_GET["act"]) {

 case "delivery_search":
      $term = pl_input('term');
      $kw = '%'.$term.'%';
      $rows = $db->query(
        "SELECT od.*,COALESCE(SUM(d.picked_qty-d.packed_qty),0) open_qty,pk.id AS picking_id,pk.picking_no
         FROM erp_outbound_delivery od
         JOIN erp_outbound_delivery_detail d ON d.delivery_id=od.id
         LEFT JOIN erp_picking pk ON pk.delivery_id=od.id AND pk.status='PICKED'
         WHERE od.status NOT IN ('CANCELLED','PGI','COMPLETED')
           AND od.picking_status='COMPLETE'
           AND od.packing_status <> 'COMPLETE'
           AND (?='' OR od.delivery_no LIKE ? OR od.no_sales_order LIKE ? OR od.customer_name LIKE ? OR od.customer_code LIKE ?)
         GROUP BY od.id
         HAVING open_qty>0
         ORDER BY od.delivery_date DESC,od.id DESC
         LIMIT 30",
        array($term,$kw,$kw,$kw,$kw)
      );
      $results = array();
      foreach ($rows as $row) {
        $results[] = array(
          'id' => $row->id,
          'text' => $row->delivery_no.' - '.$row->customer_name.' - Open Pack '.pl_num($row->open_qty),
          'delivery_no' => $row->delivery_no,
          'picking_id' => $row->picking_id,
          'picking_no' => $row->picking_no,
          'customer_code' => $row->customer_code,
          'customer_name' => $row->customer_name,
          'delivery_date' => $row->delivery_date,
          'no_sales_order' => $row->no_sales_order,
          'no_po' => '',
          'vehicle_no' => $row->vehicle_no,
          'reference_surat_jalan' => $row->reference_surat_jalan
        );
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(array('results'=>$results));
 break;

 case "delivery_items":
      $deliveryId = (int)pl_input('delivery_id');
      $rows = $db->query(
        "SELECT d.*
         FROM erp_outbound_delivery_detail d
         WHERE d.delivery_id=?
         ORDER BY d.line_no,d.id",
        array($deliveryId)
      );
      $no = 1; $count = 0;
      foreach ($rows as $row) {
        $openQty = max(0, (float)$row->picked_qty - (float)$row->packed_qty);
        if ($openQty <= 0) continue;
        $count++;
        ?>
        <tr>
          <td class="text-center"><?=intval($no++);?><input type="hidden" name="delivery_detail_id[]" value="<?=intval($row->id);?>"><input type="hidden" name="kode_input[]" value="<?=pl_h($row->material_code);?>"><input type="hidden" name="material_name[]" value="<?=pl_h($row->material_name);?>"><input type="hidden" name="line_no[]" value="<?=intval($row->line_no);?>"><input type="hidden" name="delivery_qty[]" value="<?=pl_h($row->delivery_qty);?>"><input type="hidden" name="picked_qty[]" value="<?=pl_h($row->picked_qty);?>"></td>
          <td><strong><?=pl_h($row->material_code);?></strong><br><small><?=pl_h($row->material_name);?></small></td>
          <td class="text-right"><?=pl_num($row->delivery_qty);?></td>
          <td class="text-right"><?=pl_num($row->picked_qty);?></td>
          <td class="text-right"><?=pl_num($row->packed_qty);?></td>
          <td><input name="jumlah[]" class="form-control input-sm text-right pl-pack-qty" value="<?=pl_h(number_format($openQty,5,'.',''));?>" data-max="<?=pl_h($openQty);?>"></td>
          <td><?=pl_h($row->uom);?><input type="hidden" name="unit[]" value="<?=pl_h($row->uom);?>"></td>
          <td><input name="packing[]" class="form-control input-sm" value="<?=pl_h($row->store);?>" placeholder="Carton / pallet / roll"></td>
          <td><input name="qty_packing[]" class="form-control input-sm" placeholder="Jumlah kemasan"></td>
          <td><input name="remark[]" class="form-control input-sm" value="<?=pl_h($row->remarks);?>"></td>
        </tr>
        <?php
      }
      if ($count === 0) echo '<tr><td colspan="10" class="text-center text-muted">Tidak ada open picked qty untuk dipacking.</td></tr>';
      exit;
 break;
 
 case "get_surat_jalan":
      $search = isset($_POST['search']) ? $_POST['search'] : '';

        $q = $db->query("
            SELECT *
            FROM v_surat_jalan_packing_status
            WHERE status_packing != 'CLOSE'
            AND (
                no_surat_jalan LIKE '%$search%'
                OR nama_customer LIKE '%$search%'
            )
            ORDER BY tgl_surat_jalan DESC
            LIMIT 50
        ");

        $data = array();

        foreach ($q as $row) {

           $text = 
                $row->no_surat_jalan . " | " .
                date('d-m-Y', strtotime($row->tgl_surat_jalan)) . ' | ' . $row->nama_customer . "\n " .

                'TOTAL   : ' . number_format($row->total_qty_sj,2) . "\n" .

             //   'PACKING : ' . number_format($row->total_qty_packing,2) . "\n" .

                'SISA    : ' . number_format($row->sisa_qty,2) . "\n";

              //  'STATUS  : ' . $row->status_packing;

            $data[] = array(
                'id'   => $row->no_surat_jalan,
                'text' => $text
            );
        }

        echo json_encode($data);
 break;

 case "get_detail":

    $q = $db->query("

        SELECT 

            sj.*,

            sj.no_surat_jalan AS no_sj,

            sj.no_po,

            sj.tgl_surat_jalan AS tgl_sj,

            sj.kode_penerima AS penerima,

            sj.no_kendaraan AS vehicle_no,

            p.nama

        FROM surat_jalan sj

        LEFT JOIN penerima p
            ON p.kode_penerima = sj.kode_penerima

        WHERE sj.no_surat_jalan = '".$_POST['no_sj']."'

        LIMIT 1

    ");

    foreach ($q as $k) {

        $res = array(
            "id"           => $k->id,
            "no_sj"        => $k->no_sj,
            "tgl_sj"       => $k->tgl_sj,
            "penerima"     => $k->penerima,
            "nama"         => $k->nama,
            "no_invoice"   => $k->no_invoice,
            "no_po"        => $k->no_po,
            "vehicle_no"   => $k->vehicle_no,
            "alamat"       => $k->alamat_pengiriman,
            "keterangan"   => $k->keterangan
        );

    }

    echo json_encode($res);

break;
 
 case "get_detail_barang":
?>

<div class="col-lg-12" style="overflow: scroll;">

<table class="table">

<thead>
<tr>

    <th style="width:50px;text-align: center">
        <a style="cursor: pointer;" onclick="add_baris()">
            <i class="fa fa-plus"></i>
        </a>
    </th>

    <th style="width: 300px">Kode Barang</th>
    <th style="width: 200px">Unit</th>
    <th style="width: 200px">Qty Barang</th>
  
    <th style="width: 200px">Packing</th>
    <th style="width: 200px">Remark</th>

</tr>
</thead>

<tbody id="isi_tabel">

<?php

$no = 1;

$q = $db->query("

    SELECT 
        d.*,

        sj.no_surat_jalan,
        sj.tgl_surat_jalan,

        b.nm_barang,
        b.satuan

    FROM surat_jalan_detail d

    LEFT JOIN surat_jalan sj
        ON sj.id = d.surat_jalan_id

    LEFT JOIN barang b
        ON b.kd_barang = d.kode_barang

    WHERE sj.no_surat_jalan = '".$_POST['no_sj']."'

    ORDER BY d.row_no ASC

");

foreach ($q as $k) {

   // $nilai = $k->qty_kirim * $k->harga_jual;

?>

<tr id="baris_<?= $no ?>">

    <td style="text-align: center">
        <a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')">
            <i class="fa fa-trash" style="font-size: 25px;"></i>
        </a>
    </td>

    <td>
        <input
            type="text"
            id="form_kode_<?= $no ?>"
            class="form-control"
            name="kode[]"
            style="width: 300px"
            value="<?= $k->kode_barang ?> - <?= $k->nama_barang ?>"
            readonly
        >

        <input
            type="hidden"
            name="kode_input[]"
            id="kode_input_<?= $no ?>"
            value="<?= $k->kode_barang ?>"
        >
    </td>

    <td>
        <input
            value="<?= $k->satuan ?>"
            type="text"
            id="form_unit_<?= $no ?>"
            class="form-control"
            name="unit[]"
            style="width: 150px"
            readonly
        >
    </td>

    <td>
        <input
            value="<?= formatAngka($k->qty_kirim) ?>"
            type="text"
            id="form_qty_<?= $no ?>"
            class="form-control"
            name="jumlah_<?= $no ?>"
            style="width: 150px"
        >
    </td>

   

    <td>
        <input
            value="<?= $k->packing ?> <?= $k->satuan_packing ?>"
            type="text"
            id="form_packing_<?= $no ?>"
            class="form-control"
            name="packing[]"
            style="width: 150px"
        >
    </td>

    <td>
        <input
            value="<?= $k->keterangan ?>"
            type="text"
            id="form_remark_<?= $no ?>"
            class="form-control"
            name="remark[]"
            style="width: 150px"
        >
    </td>

</tr>

<?php
$no++;
}
?>

</tbody>

</table>

</div>

<input type="hidden" id="jml" value="<?= ($no-1) ?>">

<?php
break;



  case "in":
    if (!empty($_POST['delivery_id'])) {
      $deliveryId = (int)$_POST['delivery_id'];
      $delivery = $db->fetch("SELECT * FROM erp_outbound_delivery WHERE id=? LIMIT 1", array($deliveryId));
      if (!$delivery) { action_response('Outbound Delivery tidak ditemukan.'); break; }
      if ($delivery->picking_status !== 'COMPLETE') { action_response('Packing List hanya bisa dibuat dari delivery yang sudah picked complete.'); break; }
      if (empty($_POST['delivery_detail_id']) || !is_array($_POST['delivery_detail_id'])) { action_response('Item packing wajib diisi.'); break; }

      $username = pl_user();
      $pickingId = isset($_POST['picking_id']) ? (int)$_POST['picking_id'] : 0;
      $pickingNo = pl_input('picking_no');
      $noSj = pl_input('no_sj') ?: $delivery->reference_surat_jalan;
      $tglSj = pl_date(pl_input('tgl_sj'), $delivery->delivery_date);

      $db->query('START TRANSACTION');
      $header = array(
        'delivery_id' => $delivery->id,
        'delivery_no' => $delivery->delivery_no,
        'picking_id' => $pickingId > 0 ? $pickingId : null,
        'picking_no' => $pickingNo,
        'no_packing_list' => $_POST['no_packing_list'],
        'no_sj' => $noSj ?: $delivery->delivery_no,
        'tgl_sj' => $tglSj,
        'penerima' => $delivery->customer_code,
        'no_invoice' => pl_input('no_invoice'),
        'no_po' => pl_input('no_po'),
        'valuta' => pl_input('valuta') ?: 'IDR',
        'kurs' => (float)(pl_input('kurs') ?: 1),
        'vehicle_no' => $delivery->vehicle_no,
        'status' => 'PACKED',
        'packed_by' => $username,
        'packed_at' => date('Y-m-d H:i:s'),
        'remarks' => pl_input('remarks')
      );
      if (!$db->insert('packing_list', $header)) {
        $err = $db->getErrorMessage(); $db->query('ROLLBACK'); action_response($err ?: sd_t('sales_packing_list_header_save_failed', 'Packing List header failed to save.')); break;
      }
      $packingListId = $db->last_insert_id();
      $totalPacked = 0;
      foreach ($_POST['delivery_detail_id'] as $i => $detailId) {
        $detailId = (int)$detailId;
        $packQty = isset($_POST['jumlah'][$i]) ? (float)str_replace(',', '.', $_POST['jumlah'][$i]) : 0;
        if ($packQty <= 0) continue;
        $d = $db->fetch("SELECT * FROM erp_outbound_delivery_detail WHERE id=? AND delivery_id=? LIMIT 1", array($detailId,$deliveryId));
        if (!$d) { $db->query('ROLLBACK'); action_response('Detail delivery tidak valid.'); break 2; }
        $openQty = (float)$d->picked_qty - (float)$d->packed_qty;
        if ($packQty > $openQty + 0.00001) { $db->query('ROLLBACK'); action_response('Packed qty '.$d->material_code.' melebihi open picked qty.'); break 2; }
        if (!$db->insert('packing_list_detail', array(
          'packing_list_id' => $packingListId,
          'delivery_detail_id' => $detailId,
          'line_no' => $d->line_no,
          'no_sj' => $noSj ?: $delivery->delivery_no,
          'tgl_sj' => $tglSj,
          'kode_pemilik' => $delivery->customer_code,
          'kode' => $d->material_code,
          'material_name' => $d->material_name,
          'delivery_qty' => $d->delivery_qty,
          'picked_qty' => $d->picked_qty,
          'packed_qty' => $packQty,
          'jumlah' => $packQty,
          'harga' => $d->price,
          'nilai' => round($packQty * (float)$d->price, 2),
          'valuta' => pl_input('valuta') ?: 'IDR',
          'unit' => $d->uom,
          'kurs' => (float)(pl_input('kurs') ?: 1),
          'packing' => isset($_POST['packing'][$i]) ? $_POST['packing'][$i] : '',
          'qty_packing' => isset($_POST['qty_packing'][$i]) ? $_POST['qty_packing'][$i] : '',
          'remark' => isset($_POST['remark'][$i]) ? $_POST['remark'][$i] : '',
          'row_no' => ($i + 1),
          'kd_kategori' => ''
        ))) {
          $err = $db->getErrorMessage(); $db->query('ROLLBACK'); action_response($err ?: sd_t('sales_packing_list_detail_save_failed', 'Packing List detail failed to save.')); break 2;
        }
        $db->query("UPDATE erp_outbound_delivery_detail SET packed_qty=packed_qty+? WHERE id=?", array($packQty,$detailId));
        $totalPacked += $packQty;
      }
      if ($totalPacked <= 0) { $db->query('ROLLBACK'); action_response('Minimal satu packed qty harus lebih dari nol.'); break; }
      $sum = $db->fetch("SELECT COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(packed_qty),0) packed_qty FROM erp_outbound_delivery_detail WHERE delivery_id=?", array($deliveryId));
      $packingStatus = ((float)$sum->packed_qty + 0.00001 >= (float)$sum->delivery_qty) ? 'COMPLETE' : 'PARTIAL';
      $deliveryStatus = $packingStatus === 'COMPLETE' ? 'PACKED' : $delivery->status;
      $db->query("UPDATE erp_outbound_delivery SET packing_status=?,status=?,reference_packing_list=?,updated_by=?,updated_at=? WHERE id=?", array($packingStatus,$deliveryStatus,$_POST['no_packing_list'],$username,date('Y-m-d H:i:s'),$deliveryId));
      if (function_exists('simpan_log')) simpan_log('User '.$username.' membuat Packing List '.$_POST['no_packing_list'].' dari delivery '.$delivery->delivery_no.' pada '.date('Y-m-d H:i:s'), $username);
      $db->query('COMMIT');
      action_response('');
      break;
    }
    
  
  
  
  $data = array(
      "no_packing_list" => $_POST["no_packing_list"],
      "no_sj" => $_POST["no_sj"],
      "tgl_sj" => $_POST["tgl_sj"],
      "penerima" => $_POST["penerima"],
      // "pemilik" => $_POST["pemilik"],
      "no_invoice" => $_POST["no_invoice"],
      "no_po" => $_POST["no_po"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
      "vehicle_no" => $_POST["vehicle_no"],
  );
  
  
  
   
    $in = $db->insert("packing_list",$data);

     $no_sj     = $_POST['no_sj'];
    $tgl_sj    = $_POST['tgl_sj'];
  //  $pemilik   = $_POST['pemilik'];
    $valuta    = $_POST['valuta'];
    $kurs      = $_POST['kurs'];
    $no_invoice = $_POST['no_invoice'];
    $no_po      = $_POST['no_po'];

    // Hapus data lama untuk no_sj ini agar tidak duplikat
    $db->query("DELETE FROM packing_list_detail WHERE no_sj='$no_sj' ");

    // hitung banyak baris data
    $rows = count($_POST['kode_input']);

    for ($i = 0; $i < $rows; $i++) {

        $realIndex = $i + 1; // untuk field jumlah_1, jenis_dokpab_1, dst

        $kode            = $_POST['kode_input'][$i];
      //  $jenis_dokpab    = $_POST['jenis_dokpab_' . $realIndex];
        $jumlah          = formatNumber($_POST['jumlah_' . $realIndex]);

        // $harga           = formatNumber($_POST['harga'][$i]);
        // $nilai           = formatNumber($_POST['jumlah_' . $realIndex]) * formatNumber($_POST['harga'][$i]);
       // $berat           = formatNumber($_POST['berat'][$i]);
       // $bruto           = formatNumber($_POST['bruto'][$i]);
       // $berat2          = formatNumber($_POST['berat2'][$i]);
      //  $lot_no          = $_POST['lot_no'][$i];
        // $prod_date       = $_POST['prod_date'][$i];
        // $exp_date        = $_POST['exp_date'][$i];
        $packing         = $_POST['packing'][$i];
       // $qty_packing     = formatNumber($_POST['qty_packing'][$i]);
        $remark          = $_POST['remark'][$i];
        $unit            = $_POST['unit'][$i];

        // Ambil hs_code dari tabel barang (jika diperlukan)
        // $brg = $db->fetch_custom_single("SELECT hs_code FROM barang WHERE kd_barang='$kode' ");
        // $hs_code = $brg ? $brg->hs_code : ''; 

        // INSERT
        $db->query("
            INSERT INTO packing_list_detail
            SET 
                no_sj          = '$no_sj',
                tgl_sj         = '$tgl_sj',
              
                kode           = '$kode',
                jumlah         = '$jumlah',
               
              
              
           
             
                packing        = '$packing',
               
                remark         = '$remark',
                unit           = '$unit'
             
        ");
    }
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    $header = $db->fetch("SELECT * FROM packing_list WHERE id=? LIMIT 1", array($id));
    if ($header && !empty($header->delivery_id)) {
      $details = $db->query("SELECT * FROM packing_list_detail WHERE packing_list_id=?", array($id));
      $db->query('START TRANSACTION');
      foreach ($details as $detail) {
        if (!empty($detail->delivery_detail_id)) {
          $db->query("UPDATE erp_outbound_delivery_detail SET packed_qty=GREATEST(packed_qty-?,0) WHERE id=?", array((float)$detail->jumlah, (int)$detail->delivery_detail_id));
        }
      }
      $sum = $db->fetch("SELECT COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(packed_qty),0) packed_qty FROM erp_outbound_delivery_detail WHERE delivery_id=?", array((int)$header->delivery_id));
      $packingStatus = ((float)$sum->packed_qty <= 0.00001) ? 'NOT_STARTED' : (((float)$sum->packed_qty + 0.00001 >= (float)$sum->delivery_qty) ? 'COMPLETE' : 'PARTIAL');
      $deliveryStatus = $packingStatus === 'COMPLETE' ? 'PACKED' : 'PICKED';
      $db->query("UPDATE erp_outbound_delivery SET packing_status=?,status=?,reference_packing_list=NULL,updated_by=?,updated_at=? WHERE id=?", array($packingStatus,$deliveryStatus,pl_user(),date('Y-m-d H:i:s'),(int)$header->delivery_id));
      $db->delete("packing_list_detail","packing_list_id",$id);
      $db->delete("packing_list","id",$id);
      $db->query('COMMIT');
    } else {
      $db->delete("packing_list","id",$id);
    }
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("packing_list","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    if (!empty($_POST['delivery_id'])) {
      $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
      $deliveryId = (int)$_POST['delivery_id'];
      $header = $db->fetch("SELECT * FROM packing_list WHERE id=? LIMIT 1", array($id));
      $delivery = $db->fetch("SELECT * FROM erp_outbound_delivery WHERE id=? LIMIT 1", array($deliveryId));
      if (!$header) { action_response('Packing List tidak ditemukan.'); break; }
      if (!$delivery) { action_response('Outbound Delivery tidak ditemukan.'); break; }
      if (empty($_POST['delivery_detail_id']) || !is_array($_POST['delivery_detail_id'])) { action_response('Item packing wajib diisi.'); break; }

      $username = pl_user();
      $noSj = pl_input('no_sj') ?: $delivery->reference_surat_jalan;
      $tglSj = pl_date(pl_input('tgl_sj'), $delivery->delivery_date);
      $db->query('START TRANSACTION');

      $oldDetails = $db->query("SELECT * FROM packing_list_detail WHERE packing_list_id=?", array($id));
      foreach ($oldDetails as $old) {
        if (!empty($old->delivery_detail_id)) {
          $db->query(
            "UPDATE erp_outbound_delivery_detail SET packed_qty=GREATEST(packed_qty-?,0) WHERE id=?",
            array((float)$old->jumlah, (int)$old->delivery_detail_id)
          );
        }
      }
      $db->delete("packing_list_detail", "packing_list_id", $id);

      $data = array(
        'delivery_id' => $delivery->id,
        'delivery_no' => $delivery->delivery_no,
        'picking_id' => isset($_POST['picking_id']) && (int)$_POST['picking_id'] > 0 ? (int)$_POST['picking_id'] : null,
        'picking_no' => pl_input('picking_no'),
        'no_packing_list' => pl_input('no_packing_list'),
        'no_sj' => $noSj ?: $delivery->delivery_no,
        'tgl_sj' => $tglSj,
        'penerima' => $delivery->customer_code,
        'no_invoice' => pl_input('no_invoice'),
        'no_po' => pl_input('no_po'),
        'valuta' => pl_input('valuta') ?: 'IDR',
        'kurs' => (float)(pl_input('kurs') ?: 1),
        'vehicle_no' => pl_input('vehicle_no') ?: $delivery->vehicle_no,
        'status' => 'PACKED',
        'packed_by' => $username,
        'packed_at' => date('Y-m-d H:i:s'),
        'remarks' => pl_input('remarks')
      );
      $db->update("packing_list", $data, "id", $id);
      $error = $db->getErrorMessage();
      $totalPacked = 0;

      if ($error === '') {
        foreach ($_POST['delivery_detail_id'] as $i => $detailId) {
          $detailId = (int)$detailId;
          $packQty = isset($_POST['jumlah'][$i]) ? (float)str_replace(',', '.', $_POST['jumlah'][$i]) : 0;
          if ($packQty <= 0) continue;
          $d = $db->fetch("SELECT * FROM erp_outbound_delivery_detail WHERE id=? AND delivery_id=? LIMIT 1", array($detailId, $deliveryId));
          if (!$d) { $error = 'Detail delivery tidak valid.'; break; }
          $openQty = (float)$d->picked_qty - (float)$d->packed_qty;
          if ($packQty > $openQty + 0.00001) { $error = 'Packed qty '.$d->material_code.' melebihi open picked qty.'; break; }
          if (!$db->insert('packing_list_detail', array(
            'packing_list_id' => $id,
            'delivery_detail_id' => $detailId,
            'line_no' => $d->line_no,
            'no_sj' => $noSj ?: $delivery->delivery_no,
            'tgl_sj' => $tglSj,
            'kode_pemilik' => $delivery->customer_code,
            'kode' => $d->material_code,
            'material_name' => $d->material_name,
            'delivery_qty' => $d->delivery_qty,
            'picked_qty' => $d->picked_qty,
            'packed_qty' => $packQty,
            'jumlah' => $packQty,
            'harga' => $d->price,
            'nilai' => round($packQty * (float)$d->price, 2),
            'valuta' => pl_input('valuta') ?: 'IDR',
            'unit' => $d->uom,
            'kurs' => (float)(pl_input('kurs') ?: 1),
            'packing' => isset($_POST['packing'][$i]) ? $_POST['packing'][$i] : '',
            'qty_packing' => isset($_POST['qty_packing'][$i]) ? $_POST['qty_packing'][$i] : '',
            'remark' => isset($_POST['remark'][$i]) ? $_POST['remark'][$i] : '',
            'row_no' => ($i + 1),
            'kd_kategori' => ''
          ))) {
            $error = $db->getErrorMessage() ?: sd_t('sales_packing_list_detail_save_failed', 'Packing List detail failed to save.');
            break;
          }
          $db->query("UPDATE erp_outbound_delivery_detail SET packed_qty=packed_qty+? WHERE id=?", array($packQty, $detailId));
          $totalPacked += $packQty;
        }
      }

      if ($error === '' && $totalPacked <= 0) $error = 'Minimal satu packed qty harus lebih dari nol.';
      if ($error !== '') {
        $db->query('ROLLBACK');
        action_response($error);
        break;
      }

      $sum = $db->fetch("SELECT COALESCE(SUM(delivery_qty),0) delivery_qty,COALESCE(SUM(packed_qty),0) packed_qty FROM erp_outbound_delivery_detail WHERE delivery_id=?", array($deliveryId));
      $packingStatus = ((float)$sum->packed_qty <= 0.00001) ? 'NOT_STARTED' : (((float)$sum->packed_qty + 0.00001 >= (float)$sum->delivery_qty) ? 'COMPLETE' : 'PARTIAL');
      $deliveryStatus = $packingStatus === 'COMPLETE' ? 'PACKED' : 'PICKED';
      $db->query(
        "UPDATE erp_outbound_delivery SET packing_status=?,status=?,reference_packing_list=?,updated_by=?,updated_at=? WHERE id=?",
        array($packingStatus, $deliveryStatus, pl_input('no_packing_list'), $username, date('Y-m-d H:i:s'), $deliveryId)
      );
      if (function_exists('simpan_log')) simpan_log('User '.$username.' mengubah Packing List '.pl_input('no_packing_list').' dari delivery '.$delivery->delivery_no.' pada '.date('Y-m-d H:i:s'), $username);
      $db->query('COMMIT');
      action_response('');
      break;
    }

   $data = array(
      "no_packing_list" => $_POST["no_packing_list"],
      "no_sj" => $_POST["no_sj"],
      "tgl_sj" => $_POST["tgl_sj"],
      // "penerima" => $_POST["penerima"],
      // "pemilik" => $_POST["pemilik"],
      "no_invoice" => $_POST["no_invoice"],
      "no_po" => $_POST["no_po"],
      // "valuta" => $_POST["valuta"],
      // "kurs" => $_POST["kurs"],
      "vehicle_no" => $_POST["vehicle_no"],
   );
   
   
   

    
    
    $up = $db->update("packing_list",$data,"id",$_POST["id"]);

     $no_sj     = $_POST['no_sj'];
    $tgl_sj    = $_POST['tgl_sj'];
   // $pemilik   = $_POST['pemilik'];
    // $valuta    = $_POST['valuta'];
    // $kurs      = $_POST['kurs'];
    $no_invoice = $_POST['no_invoice'];
    $no_po      = $_POST['no_po'];

    // Hapus data lama untuk no_sj ini agar tidak duplikat
    $db->query("DELETE FROM packing_list_detail WHERE no_sj='$no_sj' ");

     $rows = count($_POST['kode_input']);

    for ($i = 0; $i < $rows; $i++) {

        $realIndex = $i + 1; // untuk field jumlah_1, jenis_dokpab_1, dst

        $kode            = $_POST['kode_input'][$i];
      //  $jenis_dokpab    = $_POST['jenis_dokpab_' . $realIndex];
        $jumlah          = isset($_POST['jumlah'][$i]) ? formatNumber($_POST['jumlah'][$i]) : formatNumber($_POST['jumlah_' . $realIndex]);

        // $harga           = formatNumber($_POST['harga'][$i]);
        // $nilai           = formatNumber($_POST['jumlah_' . $realIndex]) * formatNumber($_POST['harga'][$i]);
       // $berat           = formatNumber($_POST['berat'][$i]);
       // $bruto           = formatNumber($_POST['bruto'][$i]);
       // $berat2          = formatNumber($_POST['berat2'][$i]);
      //  $lot_no          = $_POST['lot_no'][$i];
        // $prod_date       = $_POST['prod_date'][$i];
        // $exp_date        = $_POST['exp_date'][$i];
        $packing         = $_POST['packing'][$i];
        $qty_packing     = isset($_POST['qty_packing'][$i]) ? $_POST['qty_packing'][$i] : '';
        $remark          = $_POST['remark'][$i];
        $unit            = $_POST['unit'][$i];

        // Ambil hs_code dari tabel barang (jika diperlukan)
        // $brg = $db->fetch_custom_single("SELECT hs_code FROM barang WHERE kd_barang='$kode' ");
        // $hs_code = $brg ? $brg->hs_code : ''; 

        // INSERT
        $db->query("
            INSERT INTO packing_list_detail
            SET 
                no_sj          = '$no_sj',
                tgl_sj         = '$tgl_sj',
              
                kode           = '$kode',
                jumlah         = '$jumlah',
               
              
              
           
             
                packing        = '$packing',
                qty_packing    = '$qty_packing',
                remark         = '$remark',
                unit           = '$unit'
             
        ");
    }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
