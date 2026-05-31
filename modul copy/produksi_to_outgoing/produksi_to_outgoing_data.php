<?php
include "../../inc/config.php";

$columns = array(
    //'v_produksi.nomor',
    'v_produksi.no_transfer',
    'v_produksi.tgl_transfer',
    'v_produksi.no_ro',
    'v_produksi.tgl_ro',
  //  'v_produksi.dept',
    'v_produksi.user',
    'v_produksi.ket',
    'v_produksi.id_transfer',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('name_ppc','v_produksi.no_spb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_produksi.tgl_transfer");

  //set order by type
  $datatable->set_order_type("desc"); 

  //set group by column
  //$new_table->group_by = "group by v_produksi.no_spb";
     $wh = "";
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and v_produksi.tgl_transfer between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and v_produksi.tgl_transfer between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  }  


  $query = $datatable->get_custom("select status, nm_ke,tgl_transfer, v_produksi.id_transfer as id,v_produksi.jml, v_produksi.no_transfer as no_spb,v_produksi.tgl_transfer as tgl_spb,v_produksi.no_ro as no_request,v_produksi.tgl_ro as tgl_request,v_produksi.user as name_ppc,v_produksi.ket as catatan from v_transfer_produksi v_produksi where 1=1 $wh ",$columns); 

  //buat inisialisasi array data
  $data = array(); 

  $i=1;
  foreach ($query as $value) {
   // $btn_edit = "";
   $btn_edit = ' <a data-id="'.$value->id.'" href="'.base_url().'index.php/produksi-to-outgoing/edit/'.$value->id.'" class="btn btn-primary btn-sm edit_data " data-toggle="tooltip" title="" data-original-title="Edit"><i class="fa fa-pencil"></i></a> <button data-id="'.$value->id.'" data-uri="'.base_url().'/modul/produksi_to_outgoing/produksi_to_outgoing_action.php" class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_transfer_produksi"><i class="fa fa-trash"></i></button>';
    $detail = "<button class='btn btn-primary' onclick='show_detail(\"$value->no_spb\")'>Detail Barang <i class='badge'>$value->jml</i></button>";

    if ($value->status=='0') {
      $status = "<label class='label label-danger'>Belum diterima</label>";
    }elseif ($value->status=='1') {
      $status = "<label class='label label-success'>Diterima</label>";
    }else{
      $status = "<label class='label label-warning'>Dibatalkan</label>"; 
    }
    //array data
    $ResultData = array();
    $ResultData[] = $btn_edit;
    $ResultData[] = $detail;
  
   // $ResultData[] = $value->nomor;
    $ResultData[] = $value->no_spb;
    $ResultData[] = tgl_indo($value->tgl_transfer);
    $ResultData[] = $value->nm_ke;
  //  $ResultData[] = $value->tgl_request;
   // $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $status;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>