<?php
session_start();
include "../../inc/config.php";
//session_check_json();
switch ($_GET["act"]) {

  case "buat_view":

    $tgl_awal  = $_POST['tgl_awal'];
    $tgl_akhir = $_POST['tgl_akhir'];

    $db->query("DROP VIEW IF EXISTS v_mutasi_bahan_baku");

    $sql = "
    CREATE VIEW v_mutasi_bahan_baku AS

    SELECT
        b.id,
        b.kd_barang,
        b.nm_barang,
        b.satuan,

        0 AS closing,

        /* SALDO AWAL */
        COALESCE(SUM(
            CASE
                WHEN (dt.document_date < '$tgl_awal' and dt.posisi='GUDANG')
                THEN dt.qty
                ELSE 0
            END
        ),0) AS saldo_awal,

        /* PEMASUKAN */
        COALESCE(SUM(
            CASE
                WHEN dt.document_date BETWEEN '$tgl_awal' AND '$tgl_akhir'
                     AND dt.qty > 0 and dt.posisi='GUDANG'
                THEN dt.qty
                ELSE 0
            END
        ),0) AS pemasukan,

        /* PENGELUARAN */
        COALESCE(SUM(
            CASE
                WHEN dt.document_date BETWEEN '$tgl_awal' AND '$tgl_akhir'
                     AND dt.qty < 0 and dt.posisi='GUDANG'
                THEN ABS(dt.qty)
                ELSE 0
            END
        ),0) AS pengeluaran,

        /* SALDO AKHIR */
        COALESCE(SUM(
            CASE
                WHEN (dt.document_date <= '$tgl_akhir' and dt.posisi='GUDANG')
                THEN dt.qty
                ELSE 0
            END
        ),0) AS saldo_akhir,

        0 AS penyesuaian,
        0 AS stock_opname,
        0 AS selisih,
        '' AS ket,

        '".$_SESSION['username']."' AS userid

    FROM barang b

    LEFT JOIN detail_transaksi dt
        ON dt.kd_barang = b.kd_barang
        AND dt.document_date <= '$tgl_akhir'
        and dt.posisi='GUDANG'

    WHERE b.kd_kategori = 'K01'

    GROUP BY
        b.id,
        b.kd_barang,
        b.nm_barang,
        b.satuan

    ORDER BY b.kd_barang
    ";
    //echo $sql;

    $db->query($sql);

break;
   
 case "show_detail_pemasukan":

    $tgl_awal  = "1970-01-01";
    $tgl_akhir = date("Y-m-d");

    if ($_POST['tgl_awal'] != '') {
        $tgl_awal = $_POST['tgl_awal'];
    }

    if ($_POST['tgl_akhir'] != '') {
        $tgl_akhir = $_POST['tgl_akhir'];
    }

    $kd_barang = $_POST['kd_barang'];
    $tabel     = $_POST['tabel'];

    // Kondisi qty
    if ($tabel == 'vmutasipemasukanbbdetails') {
        $where_qty = "AND qty > 0";
    } else {
        $where_qty = "AND qty < 0";
    }
   $query =" SELECT 
    dt.move_code,
    dt.remark,
    dt.kd_barang AS kode,
    b.nm_barang,
    b.satuan,

    p.no_dokpab AS no_dokumen,
    p.tgl_dokpab AS tgl_dokumen,

    p.jenis_dokpab,
    p.no_dokpab,
    p.tgl_dokpab,
    p.no_aju,
    p.tgl_aju,

    sum(dt.qty) AS jumlah

FROM detail_transaksi dt

LEFT JOIN barang b 
    ON b.kd_barang = dt.kd_barang

LEFT JOIN pemasukan p 
    ON p.no_aju = dt.no_aju

 WHERE  dt.document_date BETWEEN '$tgl_awal' AND '$tgl_akhir'
    AND dt.kd_barang = '$kd_barang'
$where_qty and dt.posisi='GUDANG' $where_qty

GROUP BY  
    dt.no_aju,
    dt.no_ref,dt.kd_barang

ORDER BY 
    dt.posting_date ASC,
    dt.no_ref ASC";
   $q = $db->query($query);

  // echo $query;
 


?>

<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode/Nama Barang</th>
            <th>Satuan</th>
            <th>No Dokumen</th>
            <th>Tanggal Dokumen</th>
            <th>Jenis Dokpab</th>
            <th>No Dokpab</th>
            <th>No Aju</th>   
            <th>Jumlah</th>
            <th>Keterangan</th>

        </tr>
    </thead>

    <tbody>
        <?php

        $no  = 1;
        $jml = 0;

        foreach ($q as $k) {

            echo "
            <tr>
                <td>$no</td>
                <td>$k->kode / $k->nm_barang</td>
                <td>$k->satuan</td>
                <td>$k->no_dokumen</td>
                <td>$k->tgl_dokumen</td>
                <td>$k->jenis_dokpab</td>
                <td>$k->no_dokpab</td>
              
                <td>$k->no_aju</td>
               
                <td>" . number_format(abs($k->jumlah), 2, ",", ".") . "</td>
                <td>$k->remark</td>
            </tr>
            ";

            $jml += abs($k->jumlah);
            $no++;
        }

        ?>

        <tr>
            <td colspan="8"><b>Total</b></td>
            <td><b><?= number_format($jml, 2, ",", ".") ?></b></td>
        </tr>

    </tbody>
</table>

<?php

break;

  case "in": 
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
  );
  
  
  
   
    $in = $db->insert("mutasi_bahanbaku",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("mutasi_bahanbaku","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("mutasi_bahanbaku","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
   );
   
   
   

    
    
    $up = $db->update("mutasi_bahanbaku",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>