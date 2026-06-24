<?php
if(session_status()===PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "dokumen_pabean_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0,(int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if($length<=0 || $length>500) $length = 25;

$input = dpb_filter_input();
if(isset($_POST['search']['value']) && trim($_POST['search']['value'])!=='') $input['keyword'] = trim($_POST['search']['value']);

$rows = iterator_to_array(dpb_load_rows($db,$input));
$pageRows = array_slice($rows,$start,$length);
$data = array();
$no = $start + 1;

foreach($pageRows as $row){
  $noAju = str_replace('-', '', (string)$row->nomorAju);
  $jenis = trim((string)$row->nama_pendek) ?: 'BC '.$row->kodeDokumen;
  $data[] = array(
    $no++,
    '<strong>'.dpb_h($jenis).'</strong><br><small>'.dpb_h($row->nama_dokumen).'</small>',
    '<strong>'.dpb_h($noAju).'</strong><br><small>Kode: '.dpb_h($row->kodeDokumen).'</small>',
    dpb_h($row->nomorDokpab ?: '-'),
    dpb_h($row->tanggalDokumen ? date('Y-m-d',strtotime($row->tanggalDokumen)) : '-'),
    dpb_h($row->tanggalTtd ? date('Y-m-d',strtotime($row->tanggalTtd)) : '-'),
    '<strong>'.number_format((float)$row->total_barang,0,',','.').'</strong><br><small>Qty '.number_format((float)$row->total_qty,5,',','.').'</small>',
    dpb_status_badge($row->statusDokumen),
    dpb_h($row->uuid)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw'=>$draw,
  'recordsTotal'=>count($rows),
  'recordsFiltered'=>count($rows),
  'data'=>$data
));
?>
