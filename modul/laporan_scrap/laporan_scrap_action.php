<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

 case "get_laporan_produksi":

    $query = $db->query("

        SELECT
            a.no_bpb,
            a.tgl_bpb,
            bj.project,
            GROUP_CONCAT(
                DISTINCT b.nm_barang
                SEPARATOR ', '
            ) AS detail_barang

        FROM brgjadi_detail a

        LEFT JOIN barang b
        ON a.kode = b.kd_barang
        left join brgjadi bj on bj.no_bpb=a.no_bpb

        WHERE a.no_bpb IS NOT NULL

        GROUP BY
            a.no_bpb,
            a.tgl_bpb

        ORDER BY a.tgl_bpb DESC

    ");

    $data = array();

    foreach ($query as $row) {

        $text = $row->no_bpb." ".$row->project."";

        $text .= ' | ';
        $text .= date('d/m/Y',strtotime($row->tgl_bpb));

        if(!empty($row->detail_barang)){

            $text .= ' | ';
            $text .= $row->detail_barang;

        }

        $data[] = (object) [

            "id"   => $row->no_bpb,

            "text" => $text

        ];

    }

    echo json_encode($data);

break;

case "get_barang_scrap":

    $query = $db->query("

        SELECT
            kd_barang,
            nm_barang,
            satuan

        FROM barang

        WHERE kd_kategori='K04'

        ORDER BY nm_barang ASC

    ");

    $data = array();

   foreach ($query as $row) {

         $data[] = array(

            "kode"      => $row->kd_barang,
            "nm_barang" => $row->nm_barang,
            "satuan"    => $row->satuan

        );

    }

    echo json_encode($data);

break;

case "get_barang_produksi":

    $no_bpb = $_POST['no_bpb'];

    $query = $db->query("
        SELECT
            a.id_produksi_detail,
            a.kode,
            a.jumlah,
            a.qty_ng,

            b.nm_barang,
            b.satuan

        FROM brgjadi_detail a

        LEFT JOIN barang b
        ON a.kode=b.kd_barang

        WHERE a.no_bpb='$no_bpb'
    ");

    $data = array();

     foreach ($query as $row) {

        $data[] = array(

            "id_produksi_detail" => $row->id_produksi_detail,
            "kode"               => $row->kode,
            "nm_barang"          => $row->nm_barang,
            "satuan"             => $row->satuan,
            "qty_ng"             => $row->qty_ng,
            "jumlah"             => $row->jumlah

        );

    }

    echo json_encode($data);

break;

 case "in":

$db->begin_transaction();

try { 

    // =========================
    // HEADER
    // =========================
    $data = array(
        "nomor"      => $_POST["nomor"],
        "no_scrap"   => $_POST["no_scrap"],
        "tgl_scrap"  => $_POST["tgl_scrap"],
        "keterangan" => $_POST["keterangan"],
        "status"     => $_POST["status"],
    );

    // CEK HEADER
    $cek = $db->query(
        "SELECT nomor FROM scrap WHERE nomor = ?",
        array($_POST["nomor"])
    );

    if($cek->rowCount()>0){

        // UPDATE HEADER
        $db->update(
            "scrap",
            $data,
            "nomor = ?",
            array($_POST["nomor"])
        );

        // HAPUS DETAIL LAMA
        $db->delete(
            "scrap_detail",
            "nomor = ?",
            array($_POST["nomor"])
        );

    } else {

        // INSERT HEADER
        $db->insert("scrap",$data);

    }

    // =========================
    // DETAIL
    // =========================
    if(isset($_POST['kode_barang_select'])){

        foreach($_POST['kode_barang_select'] as $key => $kode){

            if(empty($kode)){
                continue;
            }

            $detail = array(

              //  "nomor" => $_POST["nomor"],
                 "no_scrap"   => $_POST["no_scrap"],

                "no_laporan_produksi" =>
                    $_POST["no_laporan_produksi"][$key],

                "kode_barang" =>
                    $_POST["kode_barang_select"][$key],

                "nm_barang" =>
                    $_POST["nm_barang"][$key],

                "qty_scrap" =>
                    $_POST["qty_scrap"][$key],

                "satuan" =>
                    $_POST["satuan"][$key],

                "jenis_scrap" =>
                    $_POST["jenis_scrap"][$key],

            );

            $db->insert("scrap_detail",$detail);

        }

    }

    $db->commit();

    echo json_encode(array(
        array(
            "status" => "good"
        )
    ));

} catch(Exception $e){ 

    $db->rollback();

    echo json_encode(array(
        array(
            "status" => "error",
            "error_message" => $e->getMessage()
        )
    ));

}

break;
  case "delete":
    
    
    
    $db->delete("scrap","id_scrap",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("scrap","id_scrap",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
 case "up":

$db->begin_transaction();

try{

    // =========================
    // UPDATE HEADER
    // =========================
    $data = array(

        "nomor"      => $_POST["nomor"],
        "no_scrap"   => $_POST["no_scrap"],
        "tgl_scrap"  => $_POST["tgl_scrap"],
        "keterangan" => $_POST["keterangan"],
        "status"     => $_POST["status"],
 
    ); 

    $db->update(
        "scrap",
        $data,
        "id_scrap",
        $_POST["id"]
    ); 

    // =========================
    // HAPUS DETAIL LAMA
    // =========================
    $db->query(
        "delete from scrap_detail where no_scrap=?",
       // "nomor = ?",
        array($_POST["no_scrap"])
    );

    // =========================
    // INSERT DETAIL BARU
    // =========================
    if(isset($_POST['kode_barang_select'])){

        foreach($_POST['kode_barang_select'] as $key => $kode){

            if(empty($kode)){
                continue;
            }

            $detail = array(

                 "no_scrap"   => $_POST["no_scrap"],

                "no_laporan_produksi" =>
                    $_POST["no_laporan_produksi"][$key],

                "kode_barang" =>
                    $_POST["kode_barang_select"][$key],

                "nm_barang" =>
                    $_POST["nm_barang"][$key],

                "qty_scrap" =>
                    $_POST["qty_scrap"][$key],

                "satuan" =>
                    $_POST["satuan"][$key],

                "jenis_scrap" =>
                    $_POST["jenis_scrap"][$key],

            );

            $db->insert("scrap_detail",$detail);

        }

    }

    $db->commit();

    echo json_encode(array(
        array(
            "status" => "good"
        )
    ));

}catch(Exception $e){

    $db->rollback();

    echo json_encode(array(
        array(
            "status" => "error",
            "error_message" => $e->getMessage()
        )
    ));

}

break;
  default:
    # code...
    break;
}

?>