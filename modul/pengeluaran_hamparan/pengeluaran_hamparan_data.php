<?php
include "../../inc/config.php";

$columns = array(
    'pengeluaran.id',
    'pengeluaran.no_sj',
    'pengeluaran.tgl_sj',
    'penerima.nama',
    'pengeluaran.no_invoice',
    'pengeluaran.no_do',
    'pengeluaran.jenis_dokpab',
    'pengeluaran.no_dokpab',
    'pengeluaran.no_aju',
    'pengeluaran.efaktur',
    'pengeluaran.tgl_efaktur',
    'pengeluaran.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tgl_efaktur','pengeluaran.no_sj');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("pengeluaran.no_sj");

  //set order by type
  $datatable->set_order_type("desc");
 
  //set group by column
  //$new_table->group_by = "group by pengeluaran.no_sj";

  $where = " where 1=1 ";
  $params = array();
  if (!empty($_POST['tgl_awal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_awal'])) {
    $where .= " and pengeluaran.tgl_sj >= ? ";
    $params[] = $_POST['tgl_awal'];
  }
  if (!empty($_POST['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_akhir'])) {
    $where .= " and pengeluaran.tgl_sj <= ? ";
    $params[] = $_POST['tgl_akhir'];
  }
  if (!empty($_POST['jenis_dokpab'])) {
    $where .= " and pengeluaran.jenis_dokpab = ? ";
    $params[] = $_POST['jenis_dokpab'];
  }

  $query = $datatable->get_custom("select pengeluaran.id, pengeluaran.no_sj,pengeluaran.tgl_sj,penerima.nama,pengeluaran.no_invoice,pengeluaran.no_do,pengeluaran.jenis_dokpab,pengeluaran.no_dokpab,pengeluaran.no_aju,pengeluaran.efaktur,pengeluaran.tgl_efaktur from pengeluaran inner join penerima on pengeluaran.penerima=penerima.kode_penerima $where",$columns,$params);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = "";
    $ResultData[] = htmlspecialchars($value->no_sj, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->tgl_sj, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->nama, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->no_invoice, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->no_do, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->jenis_dokpab, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->no_dokpab, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->no_aju, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->efaktur, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->tgl_efaktur, ENT_QUOTES, 'UTF-8');
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
