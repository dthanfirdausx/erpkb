<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include "../../inc/config.php";

$columns = array(
    'pemasukan.no_bpb',
    'pemasukan.no_bpb',
    'pemasukan.tgl_bpb',
    'pemasukan.nopo',
    'pemasok.nama',
    'pemasukan.no_invoice',
    'pemasukan.jenis_dokpab',
    'pemasukan.no_dokpab',
    'pemasukan.no_aju',
    'pemasukan.efaktur',
    'pemasukan.tgl_efaktur',
    'pemasukan.valuta',
    'pemasukan.no_bpb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('jenis_dokpab','pemasukan.no_bpb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("pemasukan.no_bpb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$datatable->set_group_by(" group by pemasukan.no_bpb");  

   $wh = "";
  $params = array();

  if (isset($_POST['tgl_awal']) && $_POST['tgl_awal']!='' && (!isset($_POST['tgl_akhir']) || $_POST['tgl_akhir']=='')) {
    $wh .= " and COALESCE(pemasukan.posting_date,pemasukan.tgl_bpb) between ? and ? ";
    $params[] = $_POST['tgl_awal'];
    $params[] = date("Y-m-d");
  } else if (isset($_POST['tgl_awal']) && $_POST['tgl_awal']!='' && isset($_POST['tgl_akhir']) && $_POST['tgl_akhir']!='') {
    $wh .= " and COALESCE(pemasukan.posting_date,pemasukan.tgl_bpb) between ? and ? ";
    $params[] = $_POST['tgl_awal'];
    $params[] = $_POST['tgl_akhir'];
  }

  if (isset($_POST['vendor']) && $_POST['vendor']!='') {
    $wh .= " and pemasukan.pemasok = ? ";
    $params[] = $_POST['vendor'];
  }

  if (isset($_POST['status']) && $_POST['status']!='') {
    $wh .= " and pemasukan.status = ? ";
    $params[] = $_POST['status'];
  }

  if (isset($_POST['reference']) && trim($_POST['reference'])!='') {
    $wh .= " and (
      pemasukan.no_bpb like ?
      or pemasukan.nopo like ?
      or pemasukan.no_invoice like ?
      or pemasukan.ref_no like ?
      or pemasukan.jenis_dokpab like ?
      or pemasukan.no_dokpab like ?
      or pemasukan.no_aju like ?
    ) ";
    $keyword = '%'.trim($_POST['reference']).'%';
    for ($i = 0; $i < 7; $i++) {
      $params[] = $keyword;
    }
  }

  $query = $datatable->get_custom("select pemasukan.is_reversal, pemasukan.status as keterangan, pemasukan.id, pemasukan.nomor, pemasukan.no_bpb,pemasukan.tgl_bpb,pemasukan.nopo,pemasok.nama,pemasukan.no_invoice,pemasukan.jenis_dokpab,pemasukan.no_dokpab,pemasukan.no_aju,pemasukan.efaktur,pemasukan.tgl_efaktur,pemasukan.valuta from pemasukan left join pemasok on pemasukan.pemasok=pemasok.kode_pemasok
    where pemasukan.stock_type='BLOCKED' $wh ",$columns,$params);

  //buat inisialisasi array data
  $data = array(); 

  $i=1;
  foreach ($query as $value) {
   $detailRows = $db->query("SELECT d.no_urut,d.kode,COALESCE(b.nm_barang,'') AS nm_barang,d.unit,d.jumlah,d.harga,d.nilai,d.valuta,d.lokasi,
       d.hs_code,d.customs_qty,d.customs_uom,d.customs_value,d.net_weight,d.gross_weight,
       d.package_type,d.package_qty,d.origin_country
     FROM pemasukan_detail d
     LEFT JOIN barang b ON b.kd_barang=d.kode
     WHERE d.no_bpb=?
     ORDER BY COALESCE(d.no_urut,d.id),d.id", array('no_bpb' => $value->no_bpb));

   $itemDetails = array();
   if ($detailRows) {
     foreach ($detailRows as $detail) {
       $itemDetails[] = array(
         'line' => $detail->no_urut,
         'kode' => $detail->kode,
         'nama' => $detail->nm_barang,
         'unit' => $detail->unit,
         'qty' => number_format((float) $detail->jumlah, 5, ',', '.'),
         'price' => number_format((float) $detail->harga, 5, ',', '.'),
         'amount' => number_format((float) $detail->nilai, 5, ',', '.'),
         'valuta' => $detail->valuta,
         'lokasi' => $detail->lokasi,
         'hs_code' => $detail->hs_code,
         'customs_qty' => number_format((float) $detail->customs_qty, 5, ',', '.'),
         'customs_uom' => $detail->customs_uom,
         'customs_value' => number_format((float) $detail->customs_value, 5, ',', '.'),
         'net_weight' => number_format((float) $detail->net_weight, 5, ',', '.'),
         'gross_weight' => number_format((float) $detail->gross_weight, 5, ',', '.'),
         'package_type' => $detail->package_type,
         'package_qty' => number_format((float) $detail->package_qty, 3, ',', '.'),
         'origin_country' => $detail->origin_country
       );
     }
   }
   $jml = count($itemDetails);
   $itemDetailsJson = htmlspecialchars(json_encode($itemDetails), ENT_QUOTES, 'UTF-8');

   $cek_pengganti = $db->fetch("
        SELECT COUNT(*) as jml 
        FROM pemasukan 
        WHERE ref_reversal = '".$value->no_bpb."'
    ");

    $sudah_ada_pengganti = ($cek_pengganti->jml > 0);

   $status_reversal = $value->keterangan; // dari DB

    $tombol = '<div class="gr-action-buttons">
      <button type="button" class="btn btn-primary btn-xs btn-toggle-items" data-items="'.$itemDetailsJson.'" data-toggle="tooltip" title="Show Item Detail">
        <i class="fa fa-plus"></i> <span class="badge">'.$jml.'</span>
      </button>
    ';

    if ($status_reversal == 'REVERSED') {
        if (!$sudah_ada_pengganti) {
            $tombol .= '
              <a href="'.base_url().'index.php/gr-blocked-stock/edit/'.$value->id.'" class="btn btn-success btn-xs" data-toggle="tooltip" title="Buat Ulang">
                <i class="fa fa-pencil"></i>
              </a>
            ';
        }
    } else {
        $tombol .= '
          <button type="button" data-id="'.$value->id.'" class="btn btn-warning btn-xs btn-reversal" data-toggle="tooltip" title="'.wh_h(wh_t('warehouse_reversal', 'Reversal')).' ">
            <i class="fa fa-undo"></i>
          </button>
        ';
    }
    $tombol .= '</div>';
    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $tombol;
    $ResultData[] = $value->no_bpb;
    $ResultData[] = $value->tgl_bpb;
    $ResultData[] = $value->nopo;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->no_invoice;
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->no_aju;
    $ResultData[] = $value->efaktur;
    $ResultData[] = $value->tgl_efaktur;
    $ResultData[] = $value->valuta;
    $ResultData[] = $value->keterangan;
    $ResultData[] = $value->is_reversal;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
