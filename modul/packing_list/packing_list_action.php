<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
 
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
    
    
    
    $db->delete("packing_list","id",$_GET["id"]);
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
  default:
    # code...
    break;
}

?>