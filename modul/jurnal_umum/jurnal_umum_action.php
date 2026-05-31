<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php";
require '../../inc/lib/PHPExcel.php'; 

session_check_json();

switch ($_GET["act"]) {

    case "update":

    header('Content-Type: application/json');

    $data = array(

        "tgl_jurnal" => $_POST['tgl_jurnal'],
        "no_bukti"   => $_POST['no_bukti'],
        "ket"        => $_POST['ket']

    );

    $update = $db->update(
        "jurnal_header",
        $data,
        "id",
        $_POST['id']
    );

    if($update){

        echo json_encode(array(

            "status"  => "success",
            "message" => "Data berhasil diupdate"

        ));

    }else{

        echo json_encode(array(

            "status"  => "error",
            "message" => "Gagal update data"

        ));

    }

break;


    case "excel":

    $where = "";

    if (!empty($_GET['tgl_awal']) && !empty($_GET['tgl_akhir'])) {

        $where .= "
            AND a.tgl_jurnal
            BETWEEN '".$_GET['tgl_awal']."'
            AND '".$_GET['tgl_akhir']."'
        ";

    }

    if (!empty($_GET['no_jurnal'])) {

        $where .= "
            AND a.no_jurnal LIKE '%".$_GET['no_jurnal']."%'
        ";

    }

   // require '../../inc/PHPExcel/PHPExcel.php';

    $excel = new PHPExcel();

    $excel->setActiveSheetIndex(0);

    $sheet = $excel->getActiveSheet();

    $sheet->setTitle('Jurnal Umum');

    // =========================
    // JUDUL
    // =========================

    $sheet->mergeCells('A1:J1');

    $sheet->setCellValue(
        'A1',
        'LAPORAN JURNAL UMUM'
    );

    $sheet->mergeCells('A2:J2');

    $sheet->setCellValue(
        'A2',
        'Periode : '.$_GET['tgl_awal'].' s/d '.$_GET['tgl_akhir']
    );

    $sheet->getStyle('A1:A2')->applyFromArray(array(

        'font' => array(
            'bold' => true,
            'size' => 14
        ),

        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        )

    ));

    // =========================
    // HEADER TABLE
    // =========================

    $sheet->setCellValue('A4', 'NO');
    $sheet->setCellValue('B4', 'NO JURNAL');
    $sheet->setCellValue('C4', 'TANGGAL');
    $sheet->setCellValue('D4', 'NO BUKTI');
    $sheet->setCellValue('E4', 'KETERANGAN');
    $sheet->setCellValue('F4', 'COA');
    $sheet->setCellValue('G4', 'NAMA REKENING');
    $sheet->setCellValue('H4', 'DEBET');
    $sheet->setCellValue('I4', 'KREDIT');
    $sheet->setCellValue('J4', 'VALUTA');

    $styleHeader = array(

        'font' => array(
            'bold' => true
        ),

        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        )

    );

    $sheet->getStyle('A4:J4')->applyFromArray($styleHeader);

    // =========================
    // QUERY
    // =========================

    $sql = "

        SELECT

            a.no_jurnal,
            a.tgl_jurnal,
            a.no_bukti,
            a.ket,

            b.no_rek,
            b.debet,
            b.kredit,
            b.valuta,

            c.nama_rek

        FROM jurnal_header a

        INNER JOIN jurnal_detail b
            ON a.id = b.id_header

        LEFT JOIN rekening c
            ON b.no_rek = c.no_rek

        WHERE 1=1
        $where

        ORDER BY a.tgl_jurnal ASC

    ";

    $q = $db->query($sql);

    // =========================
    // DATA
    // =========================

    $row = 5;
    $no  = 1;

    $total_debet  = 0;
    $total_kredit = 0;

    foreach ($q as $data) {

        $sheet->setCellValue('A'.$row, $no);
        $sheet->setCellValue('B'.$row, $data->no_jurnal);
        $sheet->setCellValue('C'.$row, $data->tgl_jurnal);
        $sheet->setCellValue('D'.$row, $data->no_bukti);
        $sheet->setCellValue('E'.$row, $data->ket);
        $sheet->setCellValue('F'.$row, $data->no_rek);
        $sheet->setCellValue('G'.$row, $data->nama_rek);
        $sheet->setCellValue('H'.$row, $data->debet);
        $sheet->setCellValue('I'.$row, $data->kredit);
        $sheet->setCellValue('J'.$row, $data->valuta);

        $total_debet  += $data->debet;
        $total_kredit += $data->kredit;

        $row++;
        $no++;

    }

    // =========================
    // TOTAL
    // =========================

    $sheet->setCellValue('G'.$row, 'TOTAL');
    $sheet->setCellValue('H'.$row, $total_debet);
    $sheet->setCellValue('I'.$row, $total_kredit);

    $sheet->getStyle('G'.$row.':I'.$row)->applyFromArray($styleHeader);

    // =========================
    // FORMAT NUMBER
    // =========================

    $sheet->getStyle('H5:I'.$row)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');

    // =========================
    // AUTO SIZE
    // =========================

    foreach(range('A','J') as $col) {

        $sheet->getColumnDimension($col)
              ->setAutoSize(true);

    }

    // =========================
    // OUTPUT
    // =========================

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    header('Content-Disposition: attachment;filename="DATA_JURNAL_'.date('YmdHis').'.xlsx"');

    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');

    $writer->save('php://output');

    exit;

break;



    case "import": 

    header('Content-Type: application/json');

    try {

        move_uploaded_file(
            $_FILES['file_excel']['tmp_name'],
            "../../upload/import_data/" . $_FILES['file_excel']['name']
        );

        $Reader = new SpreadsheetReader(
            "../../upload/import_data/" . $_FILES['file_excel']['name']
        );

        $Sheets = $Reader->Sheets();

        $sukses = 0;
        $gagal  = 0;

        foreach ($Sheets as $Index => $Name) {

            $Reader->ChangeSheet($Index);

            $i = 0;

            foreach ($Reader as $r) {

                // skip header excel
                if ($i > 0) {

                    /*
                    FORMAT EXCEL

                    A = No Jurnal
                    B = Tanggal
                    C = No Bukti
                    D = Keterangan
                    E = COA
                    F = Debet
                    G = Kredit
                    H = Valuta
                    */

                    $no_jurnal = trim($r[0]);
                    $tgl       = trim($r[1]);
                    $no_bukti  = trim($r[2]);
                    $ket       = trim($r[3]);
                    $no_rek    = trim($r[4]);

                    $debet = ($r[5] != '')
                                ? str_replace(",", "", $r[5])
                                : 0;

                    $kredit = ($r[6] != '')
                                ? str_replace(",", "", $r[6])
                                : 0;

                    $valuta = trim($r[7]);

                    if ($no_jurnal == '') {
                        continue;
                    }

                    // cek header jurnal
                    $cek = $db->query("
                        SELECT id
                        FROM jurnal_header
                        WHERE no_jurnal = '$no_jurnal'
                    ");

                    if ($cek->rowCount() == 0) {

                        $header = array(

                            "no_jurnal"  => $no_jurnal,
                            "tgl_jurnal" => $tgl,
                            "no_bukti"   => $no_bukti,
                            "ket"        => $ket,
                            "username"   => $_SESSION['username'],
                            "tgl_insert" => date("Y-m-d H:i:s")

                        );

                        $db->insert("jurnal_header", $header);

                        $id_header = $db->last_insert_id();

                    } else {

                        $h = $cek->fetch();

                        $id_header = $h->id;

                    }

                    // insert detail
                    $detail = array(

                        "id_header" => $id_header,
                        "no_rek"    => $no_rek,
                        "debet"     => $debet,
                        "kredit"    => $kredit,
                        "valuta"    => $valuta,
                        "kurs"      => 1

                    );

                    $insert = $db->insert("jurnal_detail", $detail);

                    if ($insert) {

                        $sukses++;

                    } else {

                        $gagal++;

                    }

                }

                $i++;

            }

        }

        echo json_encode([

            "status"  => "success",
            "message" => "Import selesai. Sukses: ".$sukses." , Gagal: ".$gagal

        ]);

    } catch (Exception $e) { 

        echo json_encode([

            "status"  => "error",
            "message" => $e->getMessage()

        ]);

    }

break;

   case "in":

    header('Content-Type: application/json');

    $db->query("START TRANSACTION");

    try {

        $header = array(
            "no_jurnal"  => $_POST["no_jurnal"],
            "tgl_jurnal" => $_POST["tgl_jurnal"],
            "ket"        => $_POST["ket"],
            "no_bukti"   => $_POST["no_bukti"],
            "username"   => $_SESSION['username'],
            "tgl_insert" => date("Y-m-d H:i:s")
        );

        $insert_header = $db->insert("jurnal_header", $header);

        if (!$insert_header) {

            throw new Exception("Gagal insert header jurnal");

        }

        $id_header = $db->last_insert_id();

        $total_debet  = 0;
        $total_kredit = 0;

        foreach ($_POST['no_rek'] as $key => $value) {

           $debet  = (!empty($_POST['debet'][$key])) 
            ? str_replace(",", "", $_POST['debet'][$key]) 
            : 0;

$kredit = (!empty($_POST['kredit'][$key])) 
            ? str_replace(",", "", $_POST['kredit'][$key]) 
            : 0;

            $total_debet  += $debet;
            $total_kredit += $kredit;

            $detail = array(

                "id_header" => $id_header,
                "no_rek"    => $_POST['no_rek'][$key],
                "debet"     => $debet,
                "kredit"    => $kredit,
                "valuta"    => $_POST['valuta'][$key],
                "kurs"      => 1

            );

            $insert_detail = $db->insert("jurnal_detail", $detail);

            if (!$insert_detail) {

                throw new Exception("Gagal insert detail jurnal");

            }

        }

        if ($total_debet != $total_kredit) {

            throw new Exception("Total Debet dan Kredit tidak balance");

        }

        $db->query("COMMIT");

        echo json_encode([
            "status"  => "success",
            "message" => "Jurnal berhasil disimpan"
        ]);

    } catch (Exception $e) {

        $db->query("ROLLBACK");

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);

    }

break;






    case "up":

        $header = array(

            "no_jurnal"  => $_POST["no_jurnal"],
            "tgl_jurnal" => $_POST["tgl_jurnal"],
            "ket"        => $_POST["ket"],
            "no_bukti"   => $_POST["no_bukti"]

        );

        $db->update(
            "jurnal_header",
            $header,
            "id",
            $_POST["id"]
        );



        $db->delete(
            "jurnal_detail",
            "id_header",
            $_POST["id"]
        );



        $total_debet  = 0;
        $total_kredit = 0;

        foreach ($_POST['no_rek'] as $key => $value) {

            $debet  = str_replace(",", "", $_POST['debet'][$key]);
            $kredit = str_replace(",", "", $_POST['kredit'][$key]);

            $total_debet  += $debet;
            $total_kredit += $kredit;

            $detail = array(

                "id_header" => $_POST["id"],
                "no_rek"    => $_POST['no_rek'][$key],
                "debet"     => $debet,
                "kredit"    => $kredit,
                "valuta"    => $_POST['valuta'][$key],
                "kurs"      => 1

            );

            $db->insert("jurnal_detail", $detail);

        }


        if ($total_debet != $total_kredit) {

            echo json_encode(array(
                "status" => "error",
                "message" => "Total Debet dan Kredit tidak balance"
            ));

            exit;

        }

        action_response($db->getErrorMessage());

    break;






    case "delete":

        $db->delete(
            "jurnal_detail",
            "id_header",
            $_GET["id"]
        );

        $db->delete(
            "jurnal_header",
            "id",
            $_GET["id"]
        );

        action_response($db->getErrorMessage());

    break;






    case "del_massal":

        $data_ids = $_REQUEST["data_ids"];

        $data_id_array = explode(",", $data_ids);

        if (!empty($data_id_array)) {

            foreach ($data_id_array as $id) {

                $db->delete(
                    "jurnal_detail",
                    "id_header",
                    $id
                );

                $db->delete(
                    "jurnal_header",
                    "id",
                    $id
                );

            }

        }

        action_response($db->getErrorMessage());

    break;






    default:
    break;
}
?>