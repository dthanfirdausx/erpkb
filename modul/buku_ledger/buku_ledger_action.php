<?php
session_start();
include "../../inc/config.php";

session_check_json();
switch ($_GET["act"]) {

  case "excel":

   require '../../inc/lib/PHPExcel.php'; 

    $start_date = $_GET['start_date'];
    $end_date   = $_GET['end_date'];
    $no_rek     = $_GET['no_rek'];

    $excel = new PHPExcel();

    $excel->setActiveSheetIndex(0);

    $sheet = $excel->getActiveSheet();

    $sheet->setTitle('Buku Besar');

    // ======================
    // JUDUL
    // ======================

    $sheet->mergeCells('A1:J1');

    $sheet->setCellValue(
        'A1',
        'LAPORAN BUKU BESAR'
    );

    $sheet->mergeCells('A2:J2');

    $sheet->setCellValue(
        'A2',
        'Periode : '.$start_date.' s/d '.$end_date
    );

    $sheet->mergeCells('A3:J3');

    $sheet->setCellValue(
        'A3',
        'COA : '.$no_rek
    );

    $style_judul = array(

        'font' => array(
            'bold' => true,
            'size' => 14
        ),

        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        )

    );

    $sheet->getStyle('A1:A3')->applyFromArray($style_judul);

    // ======================
    // HEADER TABLE
    // ======================

    $sheet->setCellValue('A5', 'NO');
    $sheet->setCellValue('B5', 'TANGGAL');
    $sheet->setCellValue('C5', 'NO JURNAL');
    $sheet->setCellValue('D5', 'NO BUKTI');
    $sheet->setCellValue('E5', 'KETERANGAN');
    $sheet->setCellValue('F5', 'COA');
    $sheet->setCellValue('G5', 'NAMA REKENING');
    $sheet->setCellValue('H5', 'DEBET');
    $sheet->setCellValue('I5', 'KREDIT');
    $sheet->setCellValue('J5', 'SALDO AKHIR');

    $style_header = array(

        'font' => array(
            'bold' => true
        ),

        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        )

    );

    $sheet->getStyle('A5:J5')->applyFromArray($style_header);

    // ======================
    // QUERY
    // ======================

    $sql = "

        SELECT

            a.tgl_jurnal,
            a.no_jurnal,
            a.no_bukti,
            a.ket,

            b.no_rek,
            r.nama_rek,

            b.debet,
            b.kredit,

            @saldo := @saldo +
            (
                CASE

                    WHEN k.saldo_normal = 'debet'
                    THEN (b.debet - b.kredit)

                    ELSE (b.kredit - b.debet)

                END
            ) AS saldo_akhir

        FROM jurnal_header a

        INNER JOIN jurnal_detail b
            ON a.id = b.id_header

        LEFT JOIN rekening r
            ON b.no_rek = r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa = k.id

        CROSS JOIN (
            SELECT @saldo := 0
        ) s

        WHERE b.no_rek = '$no_rek'

        AND a.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date'

        ORDER BY a.tgl_jurnal ASC, a.id ASC

    ";

    $query = $db->query($sql);

    $row = 6;
    $no  = 1;

    $total_debet  = 0;
    $total_kredit = 0;

    foreach($query as $data){

        $sheet->setCellValue('A'.$row, $no);
        $sheet->setCellValue('B'.$row, $data->tgl_jurnal);
        $sheet->setCellValue('C'.$row, $data->no_jurnal);
        $sheet->setCellValue('D'.$row, $data->no_bukti);
        $sheet->setCellValue('E'.$row, $data->ket);
        $sheet->setCellValue('F'.$row, $data->no_rek);
        $sheet->setCellValue('G'.$row, $data->nama_rek);
        $sheet->setCellValue('H'.$row, $data->debet);
        $sheet->setCellValue('I'.$row, $data->kredit);
        $sheet->setCellValue('J'.$row, $data->saldo_akhir);

        $total_debet  += $data->debet;
        $total_kredit += $data->kredit;

        $row++;
        $no++;

    }

    // ======================
    // TOTAL
    // ======================

    $sheet->setCellValue('G'.$row, 'TOTAL');
    $sheet->setCellValue('H'.$row, $total_debet);
    $sheet->setCellValue('I'.$row, $total_kredit);

    $sheet->getStyle('G'.$row.':I'.$row)
          ->applyFromArray($style_header);

    // ======================
    // FORMAT NUMBER
    // ======================

    $sheet->getStyle('H6:J'.$row)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');

    // ======================
    // AUTO SIZE
    // ======================

    foreach(range('A','J') as $col){

        $sheet->getColumnDimension($col)
              ->setAutoSize(true);

    }

    // ======================
    // OUTPUT
    // ======================

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    header('Content-Disposition: attachment;filename="BUKU_BESAR_'.date('YmdHis').'.xlsx"');

    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');

    $writer->save('php://output');

    exit;

break;

  case "filter":

    header('Content-Type: application/json');

    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $no_rek     = $_POST['no_rek'];

    $html = "";

    $sql = "

        SELECT

            a.tgl_jurnal,
            a.no_jurnal,
            a.no_bukti,
            a.ket,

            b.no_rek,
            r.nama_rek,

            b.debet,
            b.kredit,

            @saldo := @saldo +
            (
                CASE

                    WHEN k.saldo_normal = 'debet'
                    THEN (b.debet - b.kredit)

                    ELSE (b.kredit - b.debet)

                END
            ) AS saldo_akhir

        FROM jurnal_header a

        INNER JOIN jurnal_detail b
            ON a.id = b.id_header

        LEFT JOIN rekening r
            ON b.no_rek = r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa = k.id

        CROSS JOIN (
            SELECT @saldo := 0
        ) s

        WHERE b.no_rek = '$no_rek'

        AND a.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date'

        ORDER BY a.tgl_jurnal ASC, a.id ASC

    ";

    $query = $db->query($sql);

    $no = 1;

    $total_debet  = 0;
    $total_kredit = 0;

    foreach($query as $row){

        $total_debet  += $row->debet;
        $total_kredit += $row->kredit;

        $html .= "

            <tr>

                <td>".$no."</td>

                <td>".$row->tgl_jurnal."</td>

                <td>".$row->no_jurnal."</td>

                <td>".$row->no_bukti."</td>

                <td>".$row->ket."</td>

                <td>".$row->no_rek."</td>

                <td>".$row->nama_rek."</td>

                <td align='right'>".number_format($row->debet,2)."</td>

                <td align='right'>".number_format($row->kredit,2)."</td>

                <td align='right'>".number_format($row->saldo_akhir,2)."</td>

            </tr>

        ";

        $no++;

    }

    $html .= "

        <tr style='font-weight:bold;background:#f5f5f5;'>

            <td colspan='7' align='center'>
                TOTAL
            </td>

            <td align='right'>
                ".number_format($total_debet,2)."
            </td>

            <td align='right'>
                ".number_format($total_kredit,2)."
            </td>

            <td></td>

        </tr>

    ";

    echo json_encode(array(

        "html" => $html

    ));

break;
  case "in":
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "no_jurnal" => $_POST["no_jurnal"],
  );
  
  
  
   
    $in = $db->insert("jurnal_header",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("jurnal_header","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("jurnal_header","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "no_jurnal" => $_POST["no_jurnal"],
   );
   
   
   

    
    
    $up = $db->update("jurnal_header",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>