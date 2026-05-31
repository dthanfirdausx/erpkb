<?php
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
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and pemasukan.tgl_bpb between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and pemasukan.tgl_bpb between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  }  

  $query = $datatable->get_custom("select pemasukan.is_reversal, pemasukan.status as keterangan, pemasukan.id, pemasukan.nomor, pemasukan.no_bpb,pemasukan.tgl_bpb,pemasukan.nopo,pemasok.nama,pemasukan.no_invoice,pemasukan.jenis_dokpab,pemasukan.no_dokpab,pemasukan.no_aju,pemasukan.efaktur,pemasukan.tgl_efaktur,pemasukan.valuta from pemasukan left join pemasok on pemasukan.pemasok=pemasok.kode_pemasok 
    where 1=1 $wh ",$columns);

  //buat inisialisasi array data
  $data = array(); 

  $i=1;
  foreach ($query as $value) {
   $qq = $db->query("select count(no_bpb) as jml from pemasukan_detail where no_bpb='$value->no_bpb'  ");
   foreach ($qq as $kk) {
     $jml = $kk->jml;
   }

   $cek_pengganti = $db->fetch("
        SELECT COUNT(*) as jml 
        FROM pemasukan 
        WHERE ref_reversal = '".$value->no_bpb."'
    ");

    $sudah_ada_pengganti = ($cek_pengganti->jml > 0);

   $status_reversal = $value->keterangan; // dari DB

    $tombol = '';

   $tombol = ' 
<div class="btn-group">
  <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
    Action <span class="caret"></span>
  </button>
  <ul class="dropdown-menu dropdown-menu-right">
';

// ✅ jika SUDAH reversal → hanya EDIT
if($status_reversal == 'REVERSED'){ 
    if(!$sudah_ada_pengganti){
        $tombol .= '
            <li>
                <a href="'.base_url().'index.php/pemasukan-hamparan/edit/'.$value->id.'">
                    <i class="fa fa-pencil"></i> Buat Ulang
                </a>
            </li>
        ';
    }
}
// ✅ jika BELUM reversal → hanya REVERSAL
else{
    $tombol .= '
        <li>
            <a href="javascript:void(0)" data-id="'.$value->id.'" class="btn-reversal">
                <i class="fa fa-undo"></i> Reversal
            </a>
        </li>
    ';
}

// ✅ TAMBAHAN: tombol DETAIL masuk dropdown
$tombol .= '
    <li>
        <a href="javascript:void(0)" onclick="show_detail(\''.$value->id.'\')">
            <i class="fa fa-eye"></i> Detail <span class="badge">'.$jml.'</span>
        </a>
    </li>
';

$tombol .= '
  </ul>
</div>
';
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