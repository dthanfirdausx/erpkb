<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php"; 

session_check_json();
switch ($_GET["act"]) {

   case "upload_file": 
 // error_reporting(0);
   move_uploaded_file($_FILES['file']['tmp_name'], "../../upload/".$_FILES['file']['name']);
   $Reader = new SpreadsheetReader("../../upload/".$_FILES['file']['name']); 
   $Sheets = $Reader->Sheets(); 
  // $db->query("delete from mrpmaterial where no_order =? ",array($_POST['order']));
   $res = array();
   $res['tabel'] = "";
   foreach ($Sheets as $Index => $Name)
  {
    //echo "$Index,";
    $Reader->ChangeSheet($Index);
    if ($Index==0) {
      $mulai = false;
      $i=0;
   // $dat = array();
    foreach ($Reader as $r)  
    {
      // if ($i==0) {
      //   foreach ($r as $key => $value) {
      //      $kol[$key] = $value;
      //   }
      // }else{
        foreach ($r as $k => $v) {
        
        print_r($v);
      
      $i++;
    }
    //$isi = implode(",", $datax);
  }
    
  }
  echo json_encode($res);
    break;

  case "show_detail":
  $no_spb = $_POST['no_spb'];
  ?>
  <table class="table">
    <thead>
      <tr>
        <th>No</th>
        <th>SPB</th>
        <th>Tanggal SPB</th>
        <th>Kode Barang</th>
        <th>Jenis Dokpab</th>
        <th>Qty RO</th>
        <th>Qty</th>
        <th>Satuan</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $q = $db->query("select p.*,b.nm_barang,b.satuan from produksi_detail p 
      join barang b on b.kd_barang=p.kode where p.no_spb='$no_spb' order by row_no asc ");
    foreach ($q as $k) {
      echo "<tr>
             <td>$k->row_no</td>
             <td>$k->no_spb</td>
             <td>$k->tgl_spb</td>
             <td>$k->kode</td>
             <td>$k->jenis_dokpab</td>
             <td>$k->qtyro</td>
             <td>$k->jumlah</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
    }
    ?>
    </tbody>
  </table>
  <?php
    break;

  case "get_stock":
   $kode = $_POST['kode'];
   $jumlah = $_POST['jumlah'];
   $res= array();
   $q = $db->query("select sum(stock) as stock from stock_barang where kd_barang='$kode' ");
  // echo "select sum(stock) as stock from stock_barang where kd_barang='$kode'";
   foreach ($q as $k) {
    if ($k->stock!=NULL) {
      if ($jumlah<=$k->stock) {
          $res['status'] = "1";
          $res['pesan'] = "Stock $kode = $k->stock";
        }else{
          $res['status'] = "0";
          $res['pesan'] = "Stock $kode yang tersedia = $k->stock";
        }
    }else{
      $res['status'] = "0";
      $res['pesan'] = "Stock $kode = 0";
    }
  }
        
  
   echo json_encode($res);
    break;
  
  case "get_detail_ro":
    $no_ro = $_POST['no_ro'];
  ?>
   <div class="form-group" id="form_ro">
                 <label for="Kurs" class="control-label col-lg-2"> </label>
                 <div class="col-lg-10">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                     <th>Qty RO</th>
                     <th>Qty</th>                     
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                  <?php
                  $no=1;
                  $q = $db->query("select r.*,b.nm_barang,b.satuan from ro_detail r join barang b on b.kd_barang=r.kode where r.no_ro='$no_ro' ");
                  foreach ($q as $k) {
                  ?>
                  <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" value="<?= $k->kode." ".$k->nm_barang ?>" id="form_kode_<?= $no ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                      <input type="hidden" value="<?= $k->kode ?>" name="kode_input[]" id="kode_input_<?= $no ?>"> 
                     </td> 
                     <td><input type="text" value="<?= $k->satuan ?>" id="form_unit_<?= $no ?>" class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="text" value="<?= $k->jumlah ?>" id="form_qty_ro_<?= $no ?>" class="form-control" name="qty_ro[]" readonly ></td> 
                     <td><input type="text" id="form_qty_<?= $no ?>" class="form-control" name="qty[]" onkeyup="cek_stok('<?= $no ?>',this.value)" required></td>
                     <td><input type="text" id="form_ket_<?= $no ?>" class="form-control" name="ket[]" ></td>
                   </tr>
                  <?php
                  $no++;
                  }
                  ?>
                   
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
              
              </div>
  <?php
  break;

  case "in":  
 
    $data = array(  
      "tgl_spb"     => $_POST["tgl_spb"],
      "no_request"  => $_POST["no_request"],
      "tgl_request" => $_POST["tgl_request"],
      "dept"        => $_POST["dept"],
      "name_ppc"    => $_POST["name_ppc"],
      "catatan"     => $_POST["catatan"],
      "userid"      => $_SESSION['username'],
    );

    $data_produksi = array(  
      "tgl_spb"     => $_POST["tgl_spb"],
      "tgl_lpb"     => $_POST["tgl_spb"],
      "dept"        => $_POST["dept"],
      "dari"        => "INCOMING",
      "name_ppc"    => $_POST["name_ppc"],
      "catatan"     => $_POST["catatan"],
      "userid"      => $_SESSION['username'],
      "user_trt"    => $_SESSION['username'],
      "terima"      => '0'
    );
 
   // $in     = $db->insert("produksi",$data);
    $id     = $db->last_insert_id(); 
    $in     = $db->insert("produksi_terima",$data_produksi);
    $id_produksi_terima = $db->last_insert_id();   
    $no_spb = GetNoSpbProduksi($id,5);
    $no_lpb = GetNoLpbProduksi($id_produksi_terima,5);
    $nomor  = getUjung($id,9);
    $nomor_produksi  = getUjung($id_produksi_terima,9);
  //  $db->query("update produksi set no_spb='$no_spb',nomor='$nomor' where id='$id' ");
    $db->query("update produksi_terima set no_spb='$no_spb',nomor='$nomor_produksi',
                no_lpb='$no_lpb' where id='$id_produksi_terima' ");
    $no=1; 
    foreach ($_POST['kode_input'] as $key => $value) {
        $jumlah = $_POST['qty'][$key];
        
        $data_pem = $db->query("select p.no_aju, p.no_dokpab,p.jenis_dokpab, b.kd_barang,  p.tgl_dokpab, ifnull(sum(pd.jumlah),0) as jml_masuk,ifnull(sum(pt.jumlah),0) as jml_produksi,
ifnull(sum(pr.jumlah),0) as jml_pra_produksi,ifnull(sum(ot.jumlah),0) as jml_outgoing,
(ifnull(sum(pd.jumlah),0) - (ifnull(sum(pt.jumlah),0)+ifnull(sum(pr.jumlah),0)+ifnull(sum(ot.jumlah),0))) as saldo
from 
barang b 
left join pemasukan_detail pd on pd.kode=b.kd_barang
join pemasukan p on p.no_bpb=pd.no_bpb
left join produksi_terima_detail pt on (pt.kode=b.kd_barang and pt.no_dokpab=p.no_dokpab)
left join praproduksi_terima_detail pr on pr.kode=b.kd_barang
left join outgoing_terima_detail ot on ot.kode=b.kd_barang
where b.kd_barang='".$_POST['kode_input'][$key]."' 
group by b.kd_barang,p.no_dokpab 
having (ifnull(sum(pd.jumlah),0) - (ifnull(sum(pt.jumlah),0)+ifnull(sum(pr.jumlah),0)+ifnull(sum(ot.jumlah),0)))>0");

        foreach ($data_pem as $kk) {
            $stok = $kk->saldo;
            if($jumlah > 0) { 
              $temp     = $jumlah;
              $jumlah   = $jumlah - $kk->saldo;
              if ($jumlah<0) {
                $stok=$temp;
              } 
              $data_detail_produksi = array(
                                    'nomor'    => $nomor_produksi ,  
                                    'no_lpb'   => $no_lpb,
                                    'dari'     => 'INCOMING',
                                    'no_dokpab' => $kk->no_dokpab,
                                    'tgl_lpb'  => $_POST["tgl_spb"],
                                    'kode'     => $_POST['kode_input'][$key],
                                    'jumlah'   => $stok,
                                    'row_no'   => $no,
                                    'ket'      => $_POST['ket'][$key]);

                  $data_detail  = array('nomor'    => $nomor ,  
                                    'no_spb'       => $no_spb,
                                    'jenis_dokpab' => $kk->jenis_dokpab,
                                    'no_dokpab' => $kk->no_dokpab,
                                    'tgl_spb'      => $_POST["tgl_spb"],
                                    'kode'         => $_POST['kode_input'][$key],
                                    'jumlah'       => $stok,
                                    'row_no'       => $no,
                                    'ket'          => $_POST['ket'][$key]); 

               //   $db->insert("produksi_detail",$data_detail);
                  $db->insert("produksi_terima_detail",$data_detail_produksi); 
              }    
           }
          $no++;
       
  }
    action_response($db->getErrorMessage());
    break;

  // case "in":  
 
  //   $data = array(  
  //     "tgl_spb"     => $_POST["tgl_spb"],
  //     "no_request"  => $_POST["no_request"],
  //     "tgl_request" => $_POST["tgl_request"],
  //     "dept"        => $_POST["dept"],
  //     "name_ppc"    => $_POST["name_ppc"],
  //     "catatan"     => $_POST["catatan"],
  //     "userid"      => $_SESSION['username'],
  //   );

  //   $data_produksi = array(  
  //     "tgl_spb"     => $_POST["tgl_spb"],
  //     "tgl_lpb"     => $_POST["tgl_spb"],
  //     "dept"        => $_POST["dept"],
  //     "dari"        => "INCOMING",
  //     "name_ppc"    => $_POST["name_ppc"],
  //     "catatan"     => $_POST["catatan"],
  //     "userid"      => $_SESSION['username'],
  //     "user_trt"    => $_SESSION['username'],
  //     "terima"      => '0'
  //   );
 
  //   $in     = $db->insert("produksi",$data);
  //   $id     = $db->last_insert_id(); 
  //   $in     = $db->insert("produksi_terima",$data_produksi);
  //   $id_produksi_terima = $db->last_insert_id();   
  //   $no_spb = GetNoSpbProduksi($id,5);
  //   $no_lpb = GetNoLpbProduksi($id_produksi_terima,5);
  //   $nomor  = getUjung($id,9);
  //   $nomor_produksi  = getUjung($id_produksi_terima,9);
  //   $db->query("update produksi set no_spb='$no_spb',nomor='$nomor' where id='$id' ");
  //   $db->query("update produksi_terima set no_spb='$no_spb',nomor='$nomor_produksi',
  //               no_lpb='$no_lpb' where id='$id_produksi_terima' ");
  //   $no=1; 
  //   foreach ($_POST['kode_input'] as $key => $value) {
  //       $jumlah = $_POST['qty'][$key];
  //       $data_pem = $db->query("SELECT * FROM vpickingready where kd_barang='".$_POST['kode_input'][$key]."'  order by tgl_masuk,no_dokpab");
  //       foreach ($data_pem as $kk) {
  //           $kode        = $_POST['kode_input'][$key];
  //           $noaju       = $kk->no_aju;
  //           $tglaju      = $kk->tgl_aju;
  //           $tglmasuk    = $kk->tgl_masuk;
  //           $jenisdokpab = $kk->jenis_dokpab;
  //           $nodokpab    = $kk->no_dokpab;
  //           $tgldokpab   = $kk->tgl_dokpab;
  //           $stok        = $kk->stock;
  //           $status      = 20;
  //           $nourut      = $kk->no_urut;
  //           $noref       = $no_spb;
  //           $nourutref   = $no;            
  //           if($jumlah > 0) { 
  //             $temp     = $jumlah;
  //             $jumlah   = $jumlah - $stok;
  //             if ($jumlah<0) {
  //               $stok=$temp;
  //             }
  //             $db->query("INSERT INTO stock_incoming (kd_barang,no_aju,tgl_aju,tgl_masuk,jenis_dokpab,
  //               no_dokpab,tgl_dokpab,jumlah,status,no_urut,no_ref,no_urutref) VALUES 
  //               ('".$kode."','".$noaju."','".$tglaju."','".$tglmasuk."','".$jenisdokpab."','".$nodokpab."',
  //               '".$tgldokpab."','".$stok."','".$status."','".$nourut."','".$noref."','".$nourutref."')");
  //             update_stock($stok,"minus",$jenisdokpab,'incoming',$kode,$_SESSION['username']);         
  //           }
  //       }
  //       $data_detail_produksi = array(
  //                         'nomor'    => $nomor_produksi ,  
  //                         'no_lpb'   => $no_lpb,
  //                         'dari'     => 'INCOMING',
  //                         'tgl_lpb'  => $_POST["tgl_spb"],
  //                         'kode'     => $_POST['kode_input'][$key],
  //                         'jumlah'   => $_POST['qty'][$key],
  //                         'row_no'   => $no,
  //                         'ket'      => $_POST['ket'][$key]);

  //       $data_detail  = array('nomor'    => $nomor ,  
  //                         'no_spb'       => $no_spb,
  //                         'jenis_dokpab' => $jenisdokpab,
  //                         'tgl_spb'      => $_POST["tgl_spb"],
  //                         'kode'         => $_POST['kode_input'][$key],
  //                         'jumlah'       => $_POST['qty'][$key],
  //                         'row_no'       => $no,
  //                         'ket'          => $_POST['ket'][$key]);

  //       $db->insert("produksi_detail",$data_detail);
  //       $db->insert("produksi_terima_detail",$data_detail_produksi);
  //       $no++;
  // }
  //   action_response($db->getErrorMessage());
  //   break;
  case "delete":
    $db->delete("produksi","no_spb",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("produksi","no_spb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "no_request" => $_POST["no_request"],
      "tgl_request" => $_POST["tgl_request"],
      "dept" => $_POST["dept"],
      "name_ppc"=>$_POST["name_ppc"],
   );
   
   
   

    
    
    $up = $db->update("produksi",$data,"no_spb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>