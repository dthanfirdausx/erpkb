  <?php
session_start();
include "inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  
  case "get_stock_outgoing":
   $kode    = $_POST['kode'];
   $jumlah = $_POST['jumlah'];
   $res= array();
   $wh = " and jenis_dokpab!='brg_jadi' ";
   if ($_POST['jenis']=='1') {
     $wh = " and jenis_dokpab='brg_jadi' ";
   }
   $q = $db->query("select ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where (b.kd_barang='$kode' or s.id_barang='$kode') and id_bagian='4' $wh ");
  // echo "select ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where (b.kd_barang='$kode' or s.id_barang='$kode') and id_bagian='4' $wh";
  // echo "select sum(stock) as stock from stock_barang where kd_barang='$kode'";
   foreach ($q as $k) {  
    if ($k->stock>0) {
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
    $res['stock'] = $k->stock;
  } 
        
  
   echo json_encode($res);
    break;

  case "get_stock_incoming":
   $kode    = $_POST['kode'];
   $jumlah = $_POST['jumlah'];
   $res= array();
   $q = $db->query("select ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where (b.kd_barang='$kode' or s.id_barang='$kode') and id_bagian='1' ");
  // echo "select sum(stock) as stock from stock_barang where kd_barang='$kode'";
   foreach ($q as $k) { 
    if ($k->stock>0) {
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
    $res['stock'] = $k->stock;
  }
        
  
   echo json_encode($res);
    break;

    case "get_stock_produksi":
       $kode = $_POST['kode'];
       $jumlah = $_POST['jumlah'];
       $res= array();
       $q = $db->query("select ifnull(sum(s.stock),0) as stock,
       (select ifnull(sum(jumlah),0) as jml from temp_lp_gabungan_detail
where kd_bahan_baku='$kode' ) as jml_tmp  from stock_barang s join barang b on b.id=s.id_barang where (b.kd_barang='$kode' or s.id_barang='$kode') and id_bagian='3' "); 
      // echo "select sum(stock) as stock from stock_barang where kd_barang='$kode'";
       foreach ($q as $k) { 
        $k->stock = ($k->stock - $k->jml_tmp);    
        if ($k->stock >0) {
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
        $res['stock'] = $k->stock;
      }
            
      
       echo json_encode($res);
    break;

    case 'get_stock_by_bom':
        $kode = $_POST['kode'];
        $jumlah = $_POST['jumlah'];

         $res= array();
         $q = $db->query("select d.jumlah,d.kodebb,ba.kd_barang,ba.nm_barang from bom b join bom_detail d on d.id_bom=b.id
          join barang ba on ba.kd_barang=d.kodebb where b.kodebj='$kode' ");
         $qty_bom = array();
         $qty_stock = array(); 
         $jml_produksi = array();
         foreach ($q as $k) {
            $qty_bom[] = $k->jumlah;
            // echo "select b.nm_barang,b.kd_barang, ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where b.kd_barang='$k->kodebb' and id_bagian='3'  <br>";
            $qq = $db->query("select b.nm_barang,b.kd_barang, ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where b.kd_barang='$k->kodebb' and id_bagian='3' ");
            if ($qq->rowCount()>0) {
              foreach ($qq as $kk) {
                if ($kk->stock>0) {
                  $qty_stock[] = $kk->stock;
                  $kode_barang[] = $kk->kd_barang;
                  $nama_barang[] = $kk->nm_barang;
                  $jml_produksi[] = $kk->stock/$k->jumlah;
                }else{
                  $qty_stock[] = 0;
                  //echo "$k->kd_barang";
                  $kode_barang[] = $k->kd_barang;
                  $nama_barang[] = $k->nm_barang;
                  $jml_produksi[] = 0;
                }
                
              }
            }else{ 
              $qty_stock[] = 0;
              //echo "$k->kd_barang";
              $kode_barang[] = $k->kd_barang;
              $nama_barang[] = $k->nm_barang;
              $jml_produksi[] = 0;
            }     

         }
          //print_r($kode_barang);
         if (count($jml_produksi)>0) { 
          sort($jml_produksi);
           $stock_tersedia = $jml_produksi[0];
           if ($stock_tersedia<=0) {
             $res['status'] = "0";
             $res['pesan'] = "Stock $kode = 0";
             $res['stock'] = "0";
           }else{
             $res['stock'] = $stock_tersedia;
             $res['status'] = "1";
             //$res['stock'] = 0;
           }
            $res['detail_bahan_baku'] = "<table class='table'>
                                       <thead>
                                         <tr>
                                          <th>Kode</th>
                                          <th>Nama Barang</th>
                                          <th>Stok</th>
                                       </thead>
                                       <tbody>";
             foreach ($qty_stock as $key => $value) {
                $res['detail_bahan_baku'] .="<tr>
                  <td>".$kode_barang[$key]."</td>
                  <td>".$nama_barang[$key]."</td>
                  <td>".$qty_stock[$key]."</td>
                </tr>";
             }  
             $res['detail_bahan_baku'] .="</tbody></table>";
         }else{
         
             $res['status'] = "0";
             $res['pesan'] = "Stock $kode = 0";
             $res['stock'] = "0";
         }
         
         //echo $stock_tersedia; 
      //    foreach ($q as $k) { 
      //     if ($k->stock>0) {
      //       if ($jumlah<=$k->stock) {
      //           $res['status'] = "1";
      //           $res['pesan'] = "Stock $kode = $k->stock";
      //         }else{
      //           $res['status'] = "0";
      //           $res['pesan'] = "Stock $kode yang tersedia = $k->stock";
      //         }
      //     }else{
      //       $res['status'] = "0";
      //       $res['pesan'] = "Stock $kode = 0";
      //     }
      //     $res['stock'] = $k->stock;
      //   }
        
         echo json_encode($res);
      break;
}