<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "excel":

    require '../../inc/lib/PHPExcel.php';  


    $start_date = $_GET['start_date'];
    $end_date   = $_GET['end_date'];

    $excel = new PHPExcel();

    $excel->setActiveSheetIndex(0);

    $sheet = $excel->getActiveSheet();

    $sheet->setTitle('Neraca');

    // =====================================
    // JUDUL
    // =====================================

    $sheet->mergeCells('A1:F1');

    $sheet->setCellValue(
        'A1',
        'LAPORAN NERACA'
    );

    $sheet->mergeCells('A2:F2');

    $sheet->setCellValue(
        'A2',
        'Periode : '.$start_date.' s/d '.$end_date
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

    $sheet->getStyle('A1:F2')
          ->applyFromArray($style_judul);

    // =====================================
    // HEADER
    // =====================================

    $sheet->mergeCells('A4:C4');
    $sheet->mergeCells('D4:F4');

    $sheet->setCellValue('A4', 'AKTIVA');
    $sheet->setCellValue('D4', 'PASSIVA');

    $style_header = array(

        'font' => array(
            'bold' => true
        ),

        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array(
                'rgb' => '3C8DBC'
            )
        ),

        'font' => array(
            'bold' => true,
            'color' => array(
                'rgb' => 'FFFFFF'
            )
        ),

        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        )

    );

    $sheet->getStyle('A4:F4')
          ->applyFromArray($style_header);

    // =====================================
    // QUERY AKTIVA
    // =====================================

    $aset = $db->query("

        SELECT

            k.id,
            k.kategori,

            r.no_rek,
            r.nama_rek,

            CASE

                WHEN k.saldo_normal='debet'

                THEN SUM(d.debet)-SUM(d.kredit)

                ELSE SUM(d.kredit)-SUM(d.debet)

            END AS saldo

        FROM jurnal_detail d

        INNER JOIN jurnal_header h
            ON d.id_header=h.id

        LEFT JOIN rekening r
            ON d.no_rek=r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa=k.id

        WHERE h.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date'

        AND k.kategori_akun='aset'

        GROUP BY

            k.id,
            r.no_rek

        HAVING saldo != 0

        ORDER BY

            k.id,
            r.no_rek ASC

    ");

    // =====================================
    // QUERY PASSIVA
    // =====================================

    $passiva = $db->query("

        SELECT

            k.id,
            k.kategori,

            r.no_rek,
            r.nama_rek,

            CASE

                WHEN k.saldo_normal='debet'

                THEN SUM(d.debet)-SUM(d.kredit)

                ELSE SUM(d.kredit)-SUM(d.debet)

            END AS saldo

        FROM jurnal_detail d

        INNER JOIN jurnal_header h
            ON d.id_header=h.id

        LEFT JOIN rekening r
            ON d.no_rek=r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa=k.id

        WHERE h.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date'

        AND k.kategori_akun IN (

            'kewajiban',
            'modal'

        )

        GROUP BY

            k.id,
            r.no_rek

        HAVING saldo != 0

        ORDER BY

            k.id,
            r.no_rek ASC

    ");

    // =====================================
    // AKTIVA
    // =====================================

    $row_aset = 5;

    $kategori_aset = '';

    $total_aset = 0;

    foreach($aset as $a){

        if($kategori_aset != $a->kategori){

            $kategori_aset = $a->kategori;

            $sheet->mergeCells('A'.$row_aset.':C'.$row_aset);

            $sheet->setCellValue(
                'A'.$row_aset,
                $a->kategori
            );

            $sheet->getStyle('A'.$row_aset)
                  ->getFont()
                  ->setBold(true);

            $row_aset++;

        }

        $sheet->setCellValue(
            'A'.$row_aset,
            $a->no_rek
        );

        $sheet->setCellValue(
            'B'.$row_aset,
            $a->nama_rek
        );

        $sheet->setCellValue(
            'C'.$row_aset,
            $a->saldo
        );

        $total_aset += $a->saldo;

        $row_aset++;

    }

    $sheet->setCellValue(
        'B'.$row_aset,
        'TOTAL AKTIVA'
    );

    $sheet->setCellValue(
        'C'.$row_aset,
        $total_aset
    );

    // =====================================
    // PASSIVA
    // =====================================

    $row_passiva = 5;

    $kategori_passiva = '';

    $total_passiva = 0;

    foreach($passiva as $p){

        if($kategori_passiva != $p->kategori){

            $kategori_passiva = $p->kategori;

            $sheet->mergeCells('D'.$row_passiva.':F'.$row_passiva);

            $sheet->setCellValue(
                'D'.$row_passiva,
                $p->kategori
            );

            $sheet->getStyle('D'.$row_passiva)
                  ->getFont()
                  ->setBold(true);

            $row_passiva++;

        }

        $sheet->setCellValue(
            'D'.$row_passiva,
            $p->no_rek
        );

        $sheet->setCellValue(
            'E'.$row_passiva,
            $p->nama_rek
        );

        $sheet->setCellValue(
            'F'.$row_passiva,
            $p->saldo
        );

        $total_passiva += $p->saldo;

        $row_passiva++;

    }

    $sheet->setCellValue(
        'E'.$row_passiva,
        'TOTAL PASSIVA'
    );

    $sheet->setCellValue(
        'F'.$row_passiva,
        $total_passiva
    );

    // =====================================
    // FORMAT NUMBER
    // =====================================

    $sheet->getStyle('C5:C'.$row_aset)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');

    $sheet->getStyle('F5:F'.$row_passiva)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');

    // =====================================
    // AUTO SIZE
    // =====================================

    foreach(range('A','F') as $col){

        $sheet->getColumnDimension($col)
              ->setAutoSize(true);

    }

    // =====================================
    // OUTPUT
    // =====================================

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    header('Content-Disposition: attachment;filename="NERACA_'.date('YmdHis').'.xlsx"');

    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter( 
        $excel,
        'Excel2007'
    );

    $writer->save('php://output');

    exit;

break;

  case "filter":

    header('Content-Type: application/json');

    $end_date = $_POST['start_date'];

    $year = date('Y', strtotime($end_date));

    $start_date = $year . '-01-01';

    // =========================================
    // QUERY AKTIVA
    // =========================================

    $aset = $db->query("

        SELECT

            k.id,
            k.kategori,
            k.kategori_akun,

            r.no_rek,
            r.nama_rek,

            CASE

                WHEN k.saldo_normal='debet'

                THEN SUM(d.debet)-SUM(d.kredit)

                ELSE SUM(d.kredit)-SUM(d.debet)

            END AS saldo

        FROM jurnal_detail d

        INNER JOIN jurnal_header h
            ON d.id_header=h.id

        LEFT JOIN rekening r
            ON d.no_rek=r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa=k.id

        WHERE h.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date' 

        AND k.kategori_akun='aset'

        GROUP BY

            k.id,
            r.no_rek

        HAVING saldo != 0

        ORDER BY

            k.id,
            r.no_rek ASC

    ");

    // =========================================
    // QUERY PASSIVA
    // =========================================

    $passiva = $db->query("

        SELECT

            k.id,
            k.kategori,
            k.kategori_akun,

            r.no_rek,
            r.nama_rek,

            CASE

                WHEN k.saldo_normal='debet'

                THEN SUM(d.debet)-SUM(d.kredit)

                ELSE SUM(d.kredit)-SUM(d.debet)

            END AS saldo

        FROM jurnal_detail d

        INNER JOIN jurnal_header h
            ON d.id_header=h.id

        LEFT JOIN rekening r
            ON d.no_rek=r.no_rek

        LEFT JOIN coa_kategori k
            ON r.kat_coa=k.id

        WHERE h.tgl_jurnal
        BETWEEN '$start_date'
        AND '$end_date'

        AND k.kategori_akun IN (

            'kewajiban',
            'modal'

        )

        GROUP BY

            k.id,
            r.no_rek

        HAVING saldo != 0

        ORDER BY

            k.id,
            r.no_rek ASC

    ");

    // =========================================
    // HTML
    // =========================================

    $html = "

    <table class='table table-bordered'>

        <tr style='background:#3c8dbc;color:white;font-weight:bold;'>

            <td width='50%' align='center'>

                AKTIVA

            </td>

            <td width='50%' align='center'>

                PASSIVA

            </td>

        </tr>

        <tr>

            <td valign='top'>

                <table class='table table-bordered table-striped'>

    ";

    // =========================================
    // AKTIVA
    // =========================================

    $total_aset = 0;

    $kategori_aset = '';

    foreach($aset as $a){

        if($kategori_aset != $a->kategori){

            $kategori_aset = $a->kategori;

            $html .= "

                <tr style='background:#d9edf7;font-weight:bold;'>

                    <td colspan='2'>

                        ".$a->kategori."

                    </td>

                </tr>

            ";

        }

        $total_aset += $a->saldo;

        $html .= "

            <tr>

                <td>

                    ".$a->no_rek." - ".$a->nama_rek."

                </td>

                <td align='right' width='35%'>

                    ".number_format($a->saldo,2)."

                </td>

            </tr>

        ";

    }

    $html .= "

        <tr style='background:#f5f5f5;font-weight:bold;'>

            <td>

                TOTAL AKTIVA

            </td>

            <td align='right'>

                ".number_format($total_aset,2)."

            </td>

        </tr>

        </table>

    </td>

    <td valign='top'>

        <table class='table table-bordered table-striped'>

    ";

    // =========================================
    // PASSIVA
    // =========================================

    $total_passiva = 0;

    $kategori_passiva = '';

    foreach($passiva as $p){

        if($kategori_passiva != $p->kategori){

            $kategori_passiva = $p->kategori;

            $html .= "

                <tr style='background:#d9edf7;font-weight:bold;'>

                    <td colspan='2'>

                        ".$p->kategori."

                    </td>

                </tr>

            ";

        }

        $total_passiva += $p->saldo;

        $html .= "

            <tr>

                <td>

                    ".$p->no_rek." - ".$p->nama_rek."

                </td>

                <td align='right' width='35%'>

                    ".number_format($p->saldo,2)."

                </td>

            </tr>

        ";

    }

    $html .= "

        <tr style='background:#f5f5f5;font-weight:bold;'>

            <td>

                TOTAL PASSIVA

            </td>

            <td align='right'>

                ".number_format($total_passiva,2)."

            </td>

        </tr>

        </table>

    </td>

    </tr>

    </table>

    ";

    echo json_encode(array(

        'html' => $html

    ));

break;


  case "in":
    
  
  
  
  $data = array(
      "kategori_akun" => $_POST["kategori_akun"],
      "kategori" => $_POST["kategori"],
      "no_rek" => $_POST["no_rek"],
      "nama_rek" => $_POST["nama_rek"],
  );
  
  
  
   
    $in = $db->insert("v_neraca",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("v_neraca","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("v_neraca","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kategori_akun" => $_POST["kategori_akun"],
      "kategori" => $_POST["kategori"],
      "no_rek" => $_POST["no_rek"],
      "nama_rek" => $_POST["nama_rek"],
   );
   
   
   

    
    
    $up = $db->update("v_neraca",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>