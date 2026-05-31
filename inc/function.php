<?php

function session_check()
{
  if (empty($_SESSION['login'])) {
    echo "die";
    exit();
  }
}

function session_check_end() {
    if (empty($_SESSION['login'])) {
    echo "<script>alert('Sessio Anda Telah Habis'); window.location = '".base_url()."';</script>";
    exit();
  }
}

function infokb(){
  global $db;
  $q = $db->query("select * from infokb");
  foreach ($q as $k) {
    return $k;
  }
}  

function formatNumber($number){ 
  $number = str_replace(".", "#", $number);
   $number = str_replace(",", ".", $number); 
   $number = str_replace("#", "", $number);
   return $number;
} 

function formatAngka($angka,$belakang=NULL){ 
   return number_format($angka,2,",",".");
}  

function get_nomor_transaksi($ket){

   global $db;

   if ($ket=='sq') {

     $q = $db->query("select count(id_quotation)+1 as jml from sales_quotation where year(tgl)='".date("Y")."' ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     }

     $depan = "QID";

   }elseif ($ket=='so') {

      $q = $db->query("select count(id_sales_order)+1 as jml from sales_order where year(so_date)='".date("Y")."' ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     } 

     $depan = "SLS";

   }elseif ($ket=='do') {

      $q = $db->query("select count(id_sales_order)+1 as jml from sales_order where year(so_date)='".date("Y")."' and (no_do='' or no_do is null)  ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     } 

     $depan = "DO"; 

   }elseif ($ket=='ri') {

      $q = $db->query("select count(id)+1 as jml from return_barang where year(tgl_return)='".date("Y")."' and status='in'  ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     }  

     $depan = "RTI"; 

   }elseif ($ket=='ro') {

      $q = $db->query("select count(id)+1 as jml from return_barang where year(tgl_return)='".date("Y")."' and status='out'  ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     } 

     $depan = "RTO"; 

   }





   if ($jml<10) { 

      return "$depan/00000".$jml."/".date("dmY");

   }elseif ($jml>=10 && $jml<100) {

       return "$depan/0000".$jml."/".date("dmY");

   }elseif ($jml>=100 && $jml<1000) {

       return "$depan/000".$jml."/".date("dmY");

   }elseif ($jml>=1000 && $jml<10000) {

       return "$depan/00".$jml."/".date("dmY");

   }elseif ($jml>=10000 && $jml<100000) {

       return "$depan/0".$jml."/".date("dmY");

   }else{

      return "$depan/".$jml."/".date("dmY");

   }

}

function info_pt()

{

  global $db;

  $q = $db->query("select * from infokb");

  foreach ($q as $k) {

    return $k;

  }

}

function toRomawi($number) {

    $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);

    $returnValue = '';

    while ($number > 0) {

        foreach ($map as $roman => $int) {

            if($number >= $int) {

                $number -= $int;

                $returnValue .= $roman;

                break;

            }

        }

    }

    return $returnValue; 

} 


function generate_no_packing_list($tahun,$bulan){ 

   global $db;

    // $tahun = date('Y');
    // $bulan = date('m');

    // ambil nomor terakhir di bulan & tahun yang sama
   // echo " SELECT MAX(no_invoice) as max_no
   //      FROM sales_invoice
   //      WHERE no_invoice LIKE 'SI/$tahun/$bulan/%'";
    $q = $db->query("
         SELECT MAX(no_packing_list) as max_no       
 FROM packing_list        WHERE no_packing_list LIKE 'PL/$tahun/$bulan/%'
    ");
    foreach ($q as $k) {
      $row = $k;
    }

  //  $row = $q->fetch(PDO::FETCH_OBJ);

    if($row->max_no){

        // pecah nomor terakhir
        $exp = explode('/', $row->max_no);

        // ambil urutan terakhir
        $last_no = (int)$exp[3];

        // increment
        $no_urut = $last_no + 1;

    } else {

        // reset ke 1 kalau belum ada
        $no_urut = 1;
    }

    // format jadi 4 digit
    $urut = str_pad($no_urut, 4, "0", STR_PAD_LEFT);

    return "PL/$tahun/$bulan/$urut";
}


function generate_no_sales_infoice($tahun,$bulan){ 

   global $db;

    // $tahun = date('Y');
    // $bulan = date('m');

    // ambil nomor terakhir di bulan & tahun yang sama
   // echo " SELECT MAX(no_invoice) as max_no
   //      FROM sales_invoice
   //      WHERE no_invoice LIKE 'SI/$tahun/$bulan/%'";
    $q = $db->query("
         SELECT MAX(no_sales_invoice) as max_no       
 FROM sales_invoice        WHERE no_sales_invoice LIKE'SI/$tahun/$bulan/%'
    ");
    foreach ($q as $k) {
      $row = $k;
    }

  //  $row = $q->fetch(PDO::FETCH_OBJ);

    if($row->max_no){

        // pecah nomor terakhir
        $exp = explode('/', $row->max_no);

        // ambil urutan terakhir
        $last_no = (int)$exp[3];

        // increment
        $no_urut = $last_no + 1;

    } else {

        // reset ke 1 kalau belum ada
        $no_urut = 1;
    }

    // format jadi 4 digit
    $urut = str_pad($no_urut, 4, "0", STR_PAD_LEFT);

    return "SI/$tahun/$bulan/$urut";
}

function generate_no_scrap($tahun,$bulan){ 

   global $db;

    // ambil nomor terakhir
    $q = $db->query("
        SELECT MAX(no_scrap) as max_no
        FROM scrap
        WHERE no_scrap LIKE 'SCRAP/$tahun/$bulan/%'
    ");

    foreach ($q as $k) {
        $row = $k;
    }

    // kalau sudah ada nomor
    if($row->max_no){

        // pecah format
        // contoh : SCRAP/2026/05/0001
        $exp = explode('/', $row->max_no);

        // ambil urutan terakhir
        $last_no = (int)$exp[3];

        // increment
        $no_urut = $last_no + 1;

    } else {

        // mulai dari 1
        $no_urut = 1;
    }

    // format 4 digit
    $urut = str_pad($no_urut, 4, "0", STR_PAD_LEFT);

    // hasil akhir
    return "SCRAP/$tahun/$bulan/$urut";
}





function get_no_si(){

  //nomerxxx/FP-AVB/bulan/tahun contoh 295/FP-AVB/XI/22

  global $db;

   $q = $db->query("select count(id_sales_order)+1 as jml from sales_order where year(so_date)='".date("Y")."' and (no_do='' or no_do is null)  ");

     $jml = 0;

     foreach ($q as $k) {

        $jml = $k->jml;

     }

     if ($jml<10) { 

        $jml = "00".$jml;

     }elseif ($jml>=10 && $jml<100) {

         $jml = "0".$jml;

     }else{

         $jml = $jml;

     }

  $no_do = $jml."/FP-AVB/".toRomawi(date("m"))."/".date("y");

  return $no_do;

}

function generate_po_no($tahun=NULL,$bulan=NULL) {
    // Ambil bulan & tahun sekarang
  error_reporting(0);

    global $db;
    if ($bulan==NULL) {
      $bulan = date("m");
    } 
    if ($tahun==NULL) {
       $tahun = date("Y");
    }
   

    // Query ambil nomor terakhir di bulan & tahun ini
    $query = $db->query("
        SELECT purchase_order_no 
        FROM purchase_order 
        WHERE YEAR(po_date) = '$tahun' AND MONTH(po_date) = '$bulan'
        ORDER BY purchase_order_no DESC 
        LIMIT 1
    ");
   // $last_po = $query->fetch(PDO::FETCH_ASSOC); 
    foreach ($query as $l) {
      $last_po= (array)$l;
    }

    if ($last_po) {
        // Ambil nomor urut terakhir
        $last_number = (int)substr($last_po['purchase_order_no'], -5);
        $new_number = str_pad($last_number + 1, 5, "0", STR_PAD_LEFT);
    } else {
        // Kalau belum ada, mulai dari 00001
        $new_number = "00001";
    }

    // Format PO: PO/MM/YYYY/XXXXX
    return "PO/{$bulan}/{$tahun}/{$new_number}";
}

function getLangUser($username){
  global $db;
  $language = '';
  $q = $db->query("select lang from sys_users where username=?",array($username));
  foreach ($q as $k) {
   $language = $k->lang;
  
  }
  if ($language == '') {
     $language = 'en';
  }
  return $language;
}

function GetNextPemasokNo() {
  global $db;
  $sNextNo = ""; 
  $sLastNo = "";
  $q = $db->query("SELECT kode_pemasok FROM pemasok ORDER BY kode_pemasok DESC limit 1");
  if ($q->rowCount()>0) { 
    foreach ($q as $k) {
      $value = $k->kode_pemasok;
    }
    $sLastNo = intval(substr($value, 1, 7));
    $sLastNo = intval($sLastNo) + 1;
    $sNextNo = "S" . sprintf('%07s', $sLastNo); 
    if (strlen($sNextNo) > 8) {
      $sNextNo = "S9999999";
    }  
  } else { // jika belum ada, gunakan kode yang pertama
    $sNextNo = "S0000001";
  }
  return $sNextNo;
} 

function GetNextPenerimaNo() { 
  global $db;
  $sNextNo = "";
  $sLastNo = "";
  $q = $db->query("SELECT kode_penerima FROM penerima ORDER BY kode_penerima DESC limit 1");
  if ($q->rowCount()>0) { 
    foreach ($q as $k) {
      $value = $k->kode_penerima;
    }
    $sLastNo = intval(substr($value, 1, 7));
    $sLastNo = intval($sLastNo) + 1;
    $sNextNo = "P" . sprintf('%07s', $sLastNo); 
    if (strlen($sNextNo) > 8) {
      $sNextNo = "P9999999";
    }
  } else { // jika belum ada, gunakan kode yang pertama
    $sNextNo = "P0000001";
  }
  return $sNextNo;
}

function GetNoTransfer($id){
   $year=date("Y");
  $sNextNo = "TRF-".$year . getUjung($id,6);  
  return $sNextNo;
}

function GetNoTerima($id) {  
  $year=date("Y");
  $sNextNo = "LPB-".$year . getUjung($id,6);  
  return $sNextNo;
} 
 
function GetNextRONo($id) { 
  $year=date("Y");
  $sNextNo = "RO-".$year . getUjung($id,6)."/PROD-INC";  
  return $sNextNo;
} 

function GetNextPRNo($id) { 
 
  $year=date("Y");
  $sNextNo = $year . getUjung($id,6);  
  return $sNextNo;
} 

function GetNoSpbProduksi($id) { 
 
  $year=date("Y");
  $sNextNo = "SPB-".$year . getUjung($id,6)."/INC-PROD";  
  return $sNextNo;
} 

function GetNoSpbOutgoing($id) { 
 
  $year=date("Y");
  $sNextNo = "SPB-".$year . getUjung($id,6)."/INC-OUT";  
  return $sNextNo;
} 

function GetNoLpbProduksi($id) { 
 
  $year=date("Y");
  $sNextNo = "LP-".$year . getUjung($id,6)."/PRODUKSI";  
  return $sNextNo;
} 

function GetNoLpbOutgoing($id) { 
 
  $year=date("Y");
  $sNextNo = "LPB-".$year . getUjung($id,6)."/OUTGOING";  
  return $sNextNo;
}

function GetNo($id,$kode_depan,$kode_belakang,$digit_nomor) 
{
  $year=date("Y");
  $sNextNo = $kode_depan."-".$year . getUjung($id,$digit_nomor)."/".$kode_belakang;  
  return $sNextNo;
}

function getUjung($jml,$panjang)
{
   $length = strlen($jml);
   $awal = $panjang - $length;
   $kode_awal = "";
   for ($i=1; $i <=$awal ; $i++) { 
     $kode_awal .="0";
   }
   $kode_akhir = $kode_awal.($jml+1);
   return $kode_akhir;
}

function att_barang($kd_barang){
  global $db;
  $q = $db->query("select * from barang where kd_barang='$kd_barang' ");
  foreach ($q as $k) {
     return $k;
  }
}

function update_stock($jml,$status,$jenis_dokpab,$posisi,$kd_barang,$user,$saldo_awal=NULL,$baru=NULL){
  global $db;
  $q = $db->query("select ifnull(stock,0) as stock,id from stock_barang where jenis_dokpab='$jenis_dokpab' and id_barang='$kd_barang' and id_bagian='$posisi'  ");
  if ($q->rowCount()>0) { 
    foreach ($q as $k) {  
      $stock = $k->stock;
      if ($status=='plus') {
        $stock = $stock + $jml;
      }else{
        $stock = $stock - $jml;
      }
      $db->query("update stock_barang set stock='$stock' where id='$k->id' ");
     // echo "update stock_barang set stock='$stock' where id='$k->id' <br>";
    } 
  }else{ 

    $data = array('id_barang' => $kd_barang , 
                  'stock' => $jml,
                  'jenis_dokpab' => $jenis_dokpab,
                  'id_bagian' => $posisi,
                  'date_created' => date("Y-m-d H:i:s"),
                  'date_updated' => date("Y-m-d H:i:s"),
                  'user' => $user);
    if ($baru!='') {
      $data['baru'] = '1';   
    }  
    //print_r($data);
    if ($saldo_awal!='') {
       $data['saldo_awal'] = '1';
    }
    if ($status=='plus') { 
       $db->insert("stock_barang",$data);
    }
   // echo $db->getErrorMessage();
  }
}

function simpan_log($desksipsi,$user){
  global $db;
  $data = array('deskripsi' => $desksipsi ,
                'user' => $user,
                'tgl' => date("Y-m-d H:i:s"));
  $db->insert("log_aktifitas",$data);
}

function rekap_stock($posisi,$kd_barang=NULL){ 
     global $db;
     $q = $db->query("select id_barang, jenis_dokpab,kd_barang,sum(ifnull(jumlah,0)) as jumlah,sum(ifnull(masuk,0)) as masuk, sum(ifnull(keluar,0)) as keluar,((sum(ifnull(jumlah,0))+sum(ifnull(masuk,0)))-sum(ifnull(keluar,0))) as stock from v_stock_pemasukan where  id_barang='$kd_barang' group by jenis_dokpab order by tgl_bpb asc");
     if ($q->rowCount()>0) {
       foreach ($q as $k){ 
        $db->query("update stock_barang set stock='$k->stock' where id_barang='$k->id_barang' 
                    and jenis_dokpab='$k->jenis_dokpab' and id_bagian='$posisi' "); 
        // echo "update stock_barang set stock='$k->stock' where id_barang='$k->id_barang' 
        //             and jenis_dokpab='$k->jenis_dokpab' and id_bagian='$posisi' <br>";
      }
     }else{
        $db->query("update stock_barang set stock='0' where id_barang='$kd_barang'  and id_bagian='$posisi' "); 
     }
} 

function rekap_stock_produksi($posisi,$kd_barang=NULL){ 
     global $db;
     $q = $db->query("select id_barang, jenis_dokpab,kd_barang,sum(ifnull(jumlah,0)) as jumlah,sum(ifnull(masuk,0)) as masuk, sum(ifnull(keluar,0)) as keluar,((sum(ifnull(masuk,0)))-sum(ifnull(keluar,0))) as stock from v_rekap_stok_produksi where ((masuk)-keluar)>0 and id_barang='$kd_barang' group by jenis_dokpab order by tgl_bpb asc"); 
     if ($q->rowCount()>0) {
       foreach ($q as $k){ 
        $db->query("update stock_barang set stock='$k->stock' where id_barang='$k->id_barang' 
                    and jenis_dokpab='$k->jenis_dokpab' and id_bagian='$posisi' "); 
      }
     }else{
        $db->query("update stock_barang set stock='0' where id_barang='$kd_barang'  and id_bagian='$posisi' "); 
     } 
     
}  
  
function rekap_stock_outgoing($posisi,$kd_barang=NULL){ 
     global $db;
     $q = $db->query("select id_barang, jenis_dokpab,kd_barang,sum(ifnull(jumlah,0)) as jumlah,sum(ifnull(masuk,0)) as masuk, sum(ifnull(keluar,0)) as keluar,((sum(ifnull(masuk,0)))-sum(ifnull(keluar,0))) as stock from v_rekap_stok_outgoing2 where ((masuk)-keluar)>0 and id_barang='$kd_barang' group by jenis_dokpab order by tgl_bpb asc");   
    if ($q->rowCount()>0) {
       foreach ($q as $k){ 
        $db->query("update stock_barang set stock='$k->stock' where id_barang='$k->id_barang' 
                    and jenis_dokpab='$k->jenis_dokpab' and id_bagian='$posisi' "); 
      }
     }else{
        $db->query("update stock_barang set stock='0' where id_barang='$kd_barang'  and id_bagian='$posisi' "); 
     }
}  


function get_nomor($table,$kolom)
{
   global $db;
   $q = $db->query("select count($kolom) as jml from $table ");
   foreach ($q as $k) {
      $jml = $k->jml+1;
   }
   if ($jml<10) {
      return "000".$jml;
   }elseif ($jml>=10 && $jml<100) {
      return "00".$jml;
   }elseif ($jml>=100 && $jml<1000) {
      return "0".$jml;
   }else {
      return $jml;
   }
}

function getNoRO($year) {
  global $db;
  $sNextNo = "";
  $sLastNo = "";
  $qq=$db->query("SELECT kode FROM infokb LIMIT 1");
  foreach ($qq as $k) {
    $kode = $k->kode;
  }
 // $kode = $kode->kode;
  $q = $db->query("SELECT no_ro FROM ro WHERE year(tgl_ro)='".$year."' ORDER BY id DESC LIMIT 1");
  $value="";
  foreach ($q as $k) {
    $value = $k->no_ro; 
   // print_r($k);
  }

  
  if ($value != "") { 
    $sLastNo = intval(substr($value, 9, 6));
    $sLastNo = intval($sLastNo) + 1;
    $sNextNo = "RO-" . $year . sprintf('%06s', $sLastNo) . "/" . $kode; 
    if (strlen($sNextNo) > 23) {
      $sNextNo = "RO-" . $year . "999999" . "/" . $kode;
    }
  } else { // jika belum ada, gunakan kode yang pertama
    $sNextNo = "RO-" . $year . "000001" . "/" . $kode;
  }
  return $sNextNo;
}

function getNoBPB($year) {
  global $db;
  $sNextNo = "";
  $sLastNo = "";
  $qq=$db->query("SELECT kode FROM infokb LIMIT 1");
  foreach ($qq as $k) {
    $kode = $k->kode; 
  }
 // $kode = $kode->kode;
  $q = $db->query("SELECT no_bpb FROM pemasukan WHERE year(tgl_bpb)='".$year."' ORDER BY no_bpb DESC LIMIT 1");
  $value="";
  foreach ($q as $k) { 
    $value = $k->no_bpb; 
   // print_r($k);
  }

  
  if ($value != "") { 
    $sLastNo = intval(substr($value, 9, 6));
    $sLastNo = intval($sLastNo) + 1;
    $sNextNo = "BPB-" . $year . sprintf('%06s', $sLastNo) . "/" . $kode; 
    if (strlen($sNextNo) > 23) {
      $sNextNo = "BPB-" . $year . "999999" . "/" . $kode;
    }
  } else { // jika belum ada, gunakan kode yang pertama
    $sNextNo = "BPB-" . $year . "000001" . "/" . $kode;
  }
  return $sNextNo;
}

function getNoSJ($year) {
  global $db;
  $sNextNo = "";
  $sLastNo = "";
  $qq=$db->query("SELECT kode FROM infokb LIMIT 1");
  foreach ($qq as $k) {
    $kode = $k->kode;
  }
 // $kode = $kode->kode;
  $q = $db->query("SELECT no_sj FROM pengeluaran WHERE year(tgl_sj)='".$year."' ORDER BY id DESC LIMIT 1");
  $value="";
  foreach ($q as $k) {
       
    $value = $k->no_sj;   
  }
  
  if ($value != "") { 
   // echo substr($value, 9, 6);
    $sLastNo = intval(substr($value, 9, 6));
    $sLastNo = intval($sLastNo) + 1;
    $sNextNo = "SJ-" . $year . sprintf('%06s', $sLastNo) . "/" . $kode; 
    if (strlen($sNextNo) > 23) {
      $sNextNo = "SJ-" . $year . "999999" . "/" . $kode;
    }
  } else { // jika belum ada, gunakan kode yang pertama
    $sNextNo = "SJ-" . $year . "000001" . "/" . $kode;
  }
  return $sNextNo;
}

function session_check_json()
{
 if (empty($_SESSION['login'])) {
    $json_response = array();
    $status['status'] = "die";
    array_push($json_response, $status);
    echo json_encode($json_response);
    exit();
  }
}


//submit form action json response
function action_response($error_message,$custom_response=array()) {
    $json_response = array();
    if ($error_message=='') {
        $status['status'] = "good";
        if (!empty($custom_response)) {
       foreach ($custom_response as $key => $value) {
          $status[$key] = $value;
       }

      }

     } else {
        $status['status'] = "error";
        $status['error_message'] = $error_message;
     }
    array_push($json_response, $status);
    echo json_encode($json_response);
    exit();
}

function get_akses_prodi() {
  global $db;
  $data_prodi = array();
  $where = "";
  $get_akses_prodi = $db->fetch_single_row("sys_group_users","level",$_SESSION['group_level']);
  if ($get_akses_prodi->akses_prodi!="") {
    $decode_prodi = json_decode($get_akses_prodi->akses_prodi);
    $where = "where kode_jur in(".$decode_prodi->akses.")";
  }
  return $where;
}

//uang
function rupiah($angka){

  $hasil_rupiah = number_format($angka,0,',','.');
  return $hasil_rupiah;
}

//looping prodi berdasarkan akses prodi sesuai group users
function looping_prodi() {
  global $db;
  $akses_prodi = get_akses_prodi();
  $jurusan = $db->query("select * from view_prodi_jenjang $akses_prodi");
      if ($jurusan->rowCount()<1) {
        echo "<option value='' selected>Group User Ini Belum Punya Akses Prodi</option>";
    } else if ($jurusan->rowCount()==1) {
      foreach ($jurusan as $dt) {
        echo "<option value='$dt->kode_jur' selected>$dt->jurusan</option>";
      }
    } else {
      echo "<option value='all'>Semua</option>";
      foreach ($jurusan as $dt) {
       echo "<option value='$dt->kode_jur'>$dt->jurusan</option>";
      }
    }
}

//looping prodi berdasarkan akses prodi sesuai group users
function looping_prodi_enc() {
  global $db;
  require_once ('encrypt.php');
  $enc = new Encrypt();
  $akses_prodi = get_akses_prodi();
  $jurusan = $db->query("select * from view_prodi_jenjang $akses_prodi");
      if ($jurusan->rowCount()<1) {
        echo "<option value='' selected>Group User Ini Belum Punya Akses Prodi</option>";
    } else if ($jurusan->rowCount()==1) {
      foreach ($jurusan as $dt) {
        echo "<option value='".en($dt->kode_jur)."' selected>$dt->jurusan</option>";
      }
    } else {
      echo "<option value='all'>Semua</option>";
      foreach ($jurusan as $dt) {
        if (array_key_exists("jur", $_GET) && $_GET['jur']==en($dt->kode_jur)) {
        echo "<option value='".en($dt->kode_jur)."' selected>$dt->jurusan</option>";
      }else{
         echo "<option value='".en($dt->kode_jur)."'>$dt->jurusan</option>";
      }

      }
    }
}

//looping prodi berdasarkan akses prodi sesuai group users
function looping_matkul_kelas() {
  global $db;
  $akses_prodi = get_akses_prodi();
  $jurusan = $db->query("select * from view_prodi_jenjang $akses_prodi");
    if ($jurusan->rowCount()==1) {
      $akses_jur = $db->fetch_custom_single("select group_concat(kode_jur) as kode_jur from view_prodi_jenjang $akses_prodi");
      if ($akses_jur) {
        $jur_filter = "where vnk.kode_jur in(".$akses_jur->kode_jur.")";
      } else {
        //jika tidak group tidak punya akses prodi, set in 0
        $jur_filter = "where vnk.kode_jur in(0)";
      }
      //default semester aktif
      $sem_filter = "and vnk.sem_id='".get_sem_aktif()."'";
      $data = $db->query("select vnk.nm_matkul,vnk.id_matkul from view_nama_kelas vnk
        $jur_filter $sem_filter
group by vnk.id_matkul");
       echo "<option value='all'>Semua</option>";
      foreach ($data as $dt) {
        echo "<option value='$dt->id_matkul'>$dt->nm_matkul</option>";
      }
  } else {
    echo "<option value='all'>Semua</option>";
  }

}

//looping prodi berdasarkan akses prodi sesuai group users
function looping_kurikulum_kelas() {
  global $db;
  $akses_prodi = get_akses_prodi();
  $jurusan = $db->query("select * from view_prodi_jenjang $akses_prodi");
    //jika hanya punya satu akses prodi
    if ($jurusan->rowCount()==1) {
      $akses_jur = $db->fetch_custom_single("select group_concat(kode_jur) as kode_jur from view_prodi_jenjang $akses_prodi");
      if ($akses_jur) {
        $jur_filter = "where kode_jur in(".$akses_jur->kode_jur.")";
      } else {
        //jika tidak group tidak punya akses prodi, set in 0
        $jur_filter = "where kode_jur in(0)";
      }

      $data = $db->query("select kurikulum.kur_id,kurikulum.nama_kurikulum,view_semester.tahun_akademik from kurikulum
inner join view_semester on kurikulum.sem_id=view_semester.id_semester
        $jur_filter order by kurikulum.sem_id desc");
       echo "<option value=''>Pilih Kurikulum</option>";
      foreach ($data as $dt) {
        echo "<option value='$dt->kur_id'>$dt->nama_kurikulum $dt->tahun_akademik</option>";
      }
  } else {
    echo "<option value=''>Pilih Program Studi Dulu</option>";
  }

}

function get_tahun_akademik($sem) {
    global $db;
    $semester = $db->fetch_single_row('view_semester','id_semester',$sem);
    return $semester->tahun_akademik;
}

function get_sem_aktif() {
  global $db;
    $semester = $db->fetch_single_row('semester_ref','aktif',1);
    return $semester->id_semester;
}
function looping_semester() {
  global $db;
     foreach ($db->fetch_all("view_semester") as $isi) {
      if ($isi->aktif==1) {
          echo "<option value='$isi->id_semester' selected>$isi->tahun_akademik</option>";
          $aktif = $isi->tahun_akademik;
      } else {
          echo "<option value='$isi->id_semester'>$isi->tahun_akademik</option>";
      }

   }
}

//check if current date is periode krs or input nilai
/**
 * check current aktif per prodi
 * @param  string $type     pilihanya check krs atau lainya check periode input nilai
 * @param  int $periode  periode misal 20171
 * @param  int $kode_jur contoh 705
 * @return boolean
 */
function check_current_periode($type,$periode,$kode_jur) {
  global $db;
  if ($type=='krs') {
      $check = $db->query("select * from semester s where ((now() between s.tgl_mulai_krs and s.tgl_selesai_krs)
or (now() between s.tgl_mulai_pkrs and s.tgl_selesai_pkrs))
and id_semester='$periode'");
  } else {
    $check = $db->query("select * from semester s where now() between s.tgl_mulai_input_nilai and s.tgl_selesai_input_nilai and id_semester='$periode'");
  }
  if ($check->rowCount()>0) {
    return true;
  } else {
    return false;
  }

}


//for admin only
function session_check_adm()
{
  if ($_SESSION['group_level']!='admin') {
  exit();
  }
}
//redirection
function redirect($var)
{
  header("location:".$var);
}


//root directory web
function base_url()
{
  $root='';
  $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
  $root = $protocol.$_SERVER['HTTP_HOST'];
  //$root .= dirname($_SERVER['SCRIPT_NAME']);
  $root .= "/".DIR_MAIN;
  return $root;
}

//base admin is url until admin dir, ex:https://localhost/backend/admina
function base_admin()
{
  $root='';
  $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
  $root = $protocol.$_SERVER['HTTP_HOST'];
  $root .= "/".DIR_ADMIN."/";
  return $root;
}

//base admin is url until index.php, ex:https://localhost/backend/admina/index.php
function base_index()
{
  $root='';
   $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
  $root = $protocol.$_SERVER['HTTP_HOST'];
  $root .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
  $root .='index.php/';
  return $root;
}

//base admin is url until index.php, ex:https://localhost/backend/admina/index.php
function base_index_end()
{
  $root='';
   $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
  $root = $protocol.$_SERVER['HTTP_HOST'];
  $root .= SITE_ROOT;
  $root .='index.php/';
  return $root;
}


function tgl_indo($date) { // fungsi atau method untuk mengubah tanggal ke format indonesia
    if ($date!="") {
         // variabel BulanIndo merupakan variabel array yang menyimpan nama-nama bulan
    $BulanIndo = array("Januari", "Februari", "Maret",
               "April", "Mei", "Juni",
               "Juli", "Agustus", "September",
               "Oktober", "November", "Desember");

    $tahun = substr($date, 0, 4); // memisahkan format tahun menggunakan substring
    $bulan = substr($date, 5, 2); // memisahkan format bulan menggunakan substring
    $tgl   = substr($date, 8, 2); // memisahkan format tanggal menggunakan substring

    $result = $tgl . " " . $BulanIndo[(int)$bulan-1] . " ". $tahun;
    } else {
      $result = "";
    }

    return($result);
}

  function get_atribut_mhs($nim)
  {
    global $db;
    $q=$db->query("select * from mahasiswa m
      join jurusan j on m.jur_kode=j.kode_jur
    join fakultas f on j.fak_kode=f.kode_fak  where nim='$nim'
     ");
    foreach ($q as $k) {
      return $k;
    }
  }

  function get_jatah_sks($sem_id,$nim){
    global $db;
    $q=$db->query("select fungsi_get_jatah_sks('".$nim."','".$sem_id."') as jatah ");
    foreach ($q as $k) {
       return $k->jatah;
    }
  }

  function cek_kelas_penuh($id_kelas){
   global $db;
   $q=$db->query("select count(k.id_krs_detail) as terisi,
                  kl.peserta_max as max from krs_detail k
                  join kelas kl on k.id_kelas=kl.kelas_id
                  where k.id_kelas='$id_kelas' group by k.id_kelas ");
   foreach ($q as $k) {
    if (((int)$k->terisi) < ((int)$k->max)) {
      return false;
    }
    else{
      return true;
    }
   }
}

  function cek_penuh_permatkul($id_matkul,$sem_id){
   global $db;
   $status = array();
   $q=$db->query("select count(k.id_krs_detail) as terisi,
                  kl.peserta_max as max from krs_detail k
                  right join kelas kl on k.id_kelas=kl.kelas_id
                  where kl.id_matkul in('$id_matkul') and kl.sem_id='$sem_id'   group by k.id_kelas ");
   foreach ($q as $k) {
    if (((int)$k->terisi) < ((int)$k->max)) {
      $status[] = 'kosong';
    }
   }
   if (empty($status)) {
     return true;
   } else {
     return false;
   }
}

//Fungsi cek prasyarat MK
function cek_prasyarat_mhs($id_mk,$mhs_id){
 // echo "<pre>";
  global $db;
  $ket = "";
  $ket2 ="";
  $ket3 ="";
  $q=$db->query("select * from prasyarat_mk where id_mk='".$id_mk."'");
 // return $q->rowCount();
  //jika tidak ada prasyarat
  if ($q->rowCount()=="0") {
     return "0";
  }
  else{

      $blm_lulus=0;
      foreach ($q as $k) {
      $matkul = $k->id_mk;
      $matkul_prasyarat = $k->id_mk_prasyarat;
      $nama_mk = $db->fetch_single_row("matkul","id_matkul",$matkul)->nama_mk;
      $nama_mk_prasyarat = $db->fetch_single_row("matkul","id_matkul",$matkul_prasyarat)->nama_mk;
      $q2=$db->query("select k.*,m.bobot_minimal_lulus from krs_detail k
                      join matkul m on m.id_matkul=k.kode_mk
                      where k.kode_mk='$matkul_prasyarat' and k.nim='$mhs_id'");

      if ($q2->rowCount()==0) {
        $q3=$db->query("select m.`*`, mm.nama_mk from matkul_setara m join matkul mm on mm.id_matkul=m.id_matkul_baru where m.id_matkul_lama='$matkul_prasyarat'");
         $setara_lulus=0;
         $blm_ngambil_setara=0;
        foreach ($q3 as $k_s) {
          $qs=$db->query("select k.*,m.bobot_minimal_lulus from krs_detail k
                            join matkul m on m.id_matkul=k.kode_mk
                           where k.kode_mk='$k_s->id_matkul_baru' and k.nim='$mhs_id'");
           if ($qs->rowCount()>0) {
              foreach ($qs as $kk) {
                if ($kk->bobot < $kk->bobot_minimal_lulus) {
                  //return "$nama_mk $nama_mk_prasyarat";
                  $ket2 .= "- $nama_mk_prasyarat<br>";
                  $blm_lulus++;
                  $setara_lulus++;
                }
              }
            }else{
                $blm_ngambil_setara++;
            }

        }
      if ($blm_ngambil_setara==$q3->rowCount()) {
          $nama_mk_prasyarat = $db->fetch_single_row("matkul","id_matkul",$matkul_prasyarat)->nama_mk;
          $ket2 .= "- $nama_mk_prasyarat<br>";
          $blm_lulus++;
      }
      }else{
        $setara_lulus=0;
          foreach ($q2 as $kk) {
             if ($kk->bobot < $kk->bobot_minimal_lulus) {
                    //return "$nama_mk $nama_mk_prasyarat";
                  $ket2 .= "- $nama_mk_prasyarat<br>";
                  $blm_lulus++;
                }
          }
      }

    }

    // $ket2 .="- $ket3<br>";
    if ($blm_lulus>0) {
      $ket = "Tidak dapat mengambil mata kuliah $nama_mk karena belum lulus mata kuliah :<br>".$ket2;
    }else{
      $ket="0";
    }
    return $ket;
   // return $ket." Anda tidak dapat ambil mata kuliah $nama_mk karena belum lulus mata kuliah $nama_mk_prasyarat<br>";

  }
}

//Fungsi cek prasyarat MK
function cek_prasyarat($id_mk,$mhs_id){
 // echo "<pre>";
  global $db;
  $ket = "";
  $ket2 ="";
  $ket3 ="";
  $q=$db->query("select * from prasyarat_mk where id_mk='".$id_mk."'");
 // return $q->rowCount();
  //jika tidak ada prasyarat
  if ($q->rowCount()=="0") {
     return "0";
  }
  else{

      $blm_lulus=0;
      foreach ($q as $k) {
      $matkul = $k->id_mk;
      $matkul_prasyarat = $k->id_mk_prasyarat;
      $nama_mk = $db->fetch_single_row("matkul","id_matkul",$matkul)->nama_mk;
      $nama_mk_prasyarat = $db->fetch_single_row("matkul","id_matkul",$matkul_prasyarat)->nama_mk;
      $q2=$db->query("select k.*,m.bobot_minimal_lulus from krs_detail k join krs kr on kr.krs_id=k.id_krs
                      join matkul m on m.id_matkul=k.kode_mk
                      where k.kode_mk='$matkul_prasyarat' and kr.mhs_id='$mhs_id'");

      if ($q2->rowCount()==0) {
        $q3=$db->query("select m.`*`, mm.nama_mk from matkul_setara m join matkul mm on mm.id_matkul=m.id_matkul_baru where m.id_matkul_lama='$matkul_prasyarat'");
         $setara_lulus=0;
         $blm_ngambil_setara=0;
        foreach ($q3 as $k_s) {
          $qs=$db->query("select k.*,m.bobot_minimal_lulus from krs_detail k join krs kr on kr.krs_id=k.id_krs
                            join matkul m on m.id_matkul=k.kode_mk
                           where k.kode_mk='$k_s->id_matkul_baru' and kr.mhs_id='$mhs_id'");
           if ($qs->rowCount()>0) {
              foreach ($qs as $kk) {
                if ($kk->bobot < $kk->bobot_minimal_lulus) {
                  //return "$nama_mk $nama_mk_prasyarat";
                  $ket2 .= "- $nama_mk_prasyarat<br>";
                  $blm_lulus++;
                  $setara_lulus++;
                }
              }
            }else{
                $blm_ngambil_setara++;
            }

        }
      if ($blm_ngambil_setara==$q3->rowCount()) {
          $nama_mk_prasyarat = $db->fetch_single_row("matkul","id_matkul",$matkul_prasyarat)->nama_mk;
          $ket2 .= "- $nama_mk_prasyarat<br>";
          $blm_lulus++;
      }
      }else{
        $setara_lulus=0;
          foreach ($q2 as $kk) {
             if ($kk->bobot < $kk->bobot_minimal_lulus) {
                    //return "$nama_mk $nama_mk_prasyarat";
                  $ket2 .= "- $nama_mk_prasyarat<br>";
                  $blm_lulus++;
                }
          }
      }

    }

    // $ket2 .="- $ket3<br>";
    if ($blm_lulus>0) {
      $ket = "Tidak dapat mengambil mata kuliah $nama_mk karena belum lulus mata kuliah :<br>".$ket2;
    }else{
      $ket="0";
    }
    return $ket;
   // return $ket." Anda tidak dapat ambil mata kuliah $nama_mk karena belum lulus mata kuliah $nama_mk_prasyarat<br>";

  }
}

function get_semester_aktif($kode_jur){
    global $db;
   $q= $db->query("select id_semester,sem_id from semester where is_aktif='1' and kode_jur='$kode_jur' ");
   foreach ($q as $k) {
      return $k;
   }
}

function get_semester_aktif_mhs($sem_id,$nim){
    global $db;
   $q= $db->query("select akm_id from akm where mhs_nim='$nim' and sem_id='$sem_id' ");
   foreach ($q as $k) {
      return $k->akm_id;
   }
}

function get_avaliable_tanggal($jenis,$sem_id){
   global $db;
   if ($jenis=='krs') {
       $q = "select * from semester s where now() between
               s.tgl_mulai_krs and s.tgl_selesai_krs and sem_id='$sem_id'";
   }else if ($jenis=='pkrs') {
      $q = "select * from semester s where now() between
               s.tgl_mulai_pkrs and s.tgl_selesai_pkrs and sem_id='$sem_id'";
   }else if ($jenis=='input_nilai') {
       $q = "select * from semester s where now() between
               s.tgl_mulai_input_nilai and s.tgl_selesai_input_nilai and sem_id='$sem_id'";
   }
   $qu = $db->query($q);
   if ($qu->rowCount()>0) {
     return true;
   }else{
    return false;
   }
}

function cek_status_registrasi($mhs_id,$sem_id){
   global $db;
   $q=$db->query("select * from mhs_registrasi m where m.sem_id='$sem_id' and m.nim='$mhs_id'");
   if ($q->rowCount()>0) {
     return true;
   }else{
    return false;
   }
}

function tampil_periode($sem_aktif)
{
    $sem = substr($sem_aktif, 0,4);
    $sem2 = $sem+1;
    if (substr($sem_aktif, 0,4)=='1') {
        $periode = "Ganjil";
    }else{
        $periode = "Genap";
    }
    return $periode." $sem/$sem2";
}

function get_kode_jur_by_nim($nim)
{
   global $db;
   $q=$db->query("select jur_kode from mahasiswa where nim='$nim'");
   foreach ($q as $k) {
     return $k->jur_kode;
   }
}

function clean($string) {
    return preg_replace('/[^\da-z ]/i', '', $string);// Removes special chars.
}

function buat_akm($data_akm)
{
   global $db;
   $q=$db->query("select * from akm where mhs_nim='".$data_akm['mhs_nim']."'
    and sem_id='".$data_akm['sem_id']."' ");
   if ($q->rowCount()==0) {
     // $data = array('mhs_nim' => $nim, 'sem_id' => $sem);
      $db->insert("akm",$data_akm);
   }
}



function update_akm($nim){
   error_reporting(0);
   global $db;

   $q=$db->query("select s.id_semester from krs_detail k join semester s on k.id_semester=s.id_semester
                  join mahasiswa m on m.nim=k.nim where k.nim='$nim' and s.kode_jur=m.jur_kode group by
                  s.id_semester order by s.id_semester asc ");
   $ipk=0;
   $bobot_ipk=0;
   $sks_ipk=0;
   foreach ($q as $k) {
     $qq = $db->query("select akm_id from akm where mhs_nim='$nim' and sem_id='$k->id_semester' ");
     if ($qq->rowCount()==0) {
       $datax = array('sem_id' => $k->id_semester ,
                      'mhs_nim' => $nim);
       $db->insert("akm",$datax);
     }
      $ip=0;
      $ipk=0;
      foreach ($db->query("select sum(k.sks) as jml_sks, sum(k.bobot * k.sks) as jml_bobot from krs_detail k
                           where k.nim='$nim' and k.id_semester='$k->id_semester' and k.batal='0' 
                           group by k.id_semester ")
              as $kk) {
         $bobot_ipk = $bobot_ipk + $kk->jml_bobot;
         $sks_ipk   = $sks_ipk + $kk->jml_sks;
         $ipk       = $bobot_ipk/$sks_ipk;
         $ip        = $kk->jml_bobot/$kk->jml_sks; 
         $db->query("update akm set ip='".number_format($ip,2)."',ipk='".number_format($ipk,2)."',
         sks_diambil='$kk->jml_sks' where sem_id='$k->id_semester' and mhs_nim='$nim' ");
      //   echo "update akm set ip='".number_format($ip,2)."',ipk='".number_format($ipk,2)."' where sem_id='$k->id_semester' and mhs_nim='$nim'  <br>";
      }
   }
}

function waktu_import($waktu) {

  $hours = floor($waktu / 3600);
  $minutes = floor(($waktu / 60) % 60);
  $seconds = $waktu % 60;

  return ($hours < 1?'':$hours.' Jam') . ($minutes < 1 ? '':$minutes.' Menit') . $seconds.' Detik';
}

/**
 * get data jurusan lokal from input jurusan dikti
 * @return [type] [description]
 */
function get_prodi_lokal() {
    global $db;
  $array_jur_lokal = array();
  $array_jur_query = $db->query("select kode_dikti, kode_jur from jurusan");
  foreach ($array_jur_query as $jur_lokal) {
    $array_jur_lokal[$jur_lokal->kode_dikti] = $jur_lokal->kode_jur;
  }
  return $array_jur_lokal;
}

/**
 * get data jurusan lokal from input jurusan dikti
 * @return [type] [description]
 */
function get_prodi_dikti() {
    global $db;
  $array_jur_lokal = array();
  $array_jur_query = $db->query("select kode_dikti, kode_jur from jurusan");
  foreach ($array_jur_query as $jur_lokal) {
    $array_jur_lokal[$jur_lokal->kode_jur] = $jur_lokal->kode_dikti;
  }
  return $array_jur_lokal;
}
function get_label_kelas() {
      global $db;
  $array_jur_lokal = array();
  $array_jur_query = $db->query("select kode_paralel, nm_paralel from paralel_kelas_ref");
  foreach ($array_jur_query as $jur_lokal) {
    $array_jur_lokal[$jur_lokal->kode_paralel] = $jur_lokal->nm_paralel;
  }
  return $array_jur_lokal;
}

/**
 * get token briva
 * @return [string] [description]
 */

function get_token_briva()
{
  global $db;
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "https://developer.bri.co.id/v1/api/token");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);

  $param = array(
    'grant_type'  => 'authorization_code',
    'client_id'   => '82382c89e8c9167b3e1a5963866aa5c99acf',
    'client_secret' => 'f600fea596c8b85817100742857638c6b0b2',
    'code'    => 'c331603d6a4a17039371a105ca7252ddbdbb1ccf'
  );

  curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "X-BRI-KEY: 4de865b0e67c2bce68118d128f20511496b25413",
    "Content-Type : application/json"
  ));

  $r= json_decode(curl_exec($ch));
}

/*function generate_tagihan_sks()
{
  global $db;
  $q=$db->query("select * from keu_jenis_pembayaran where kode_pembayaran='SKS' ");
  if ($q->rowCount()==0) {
    $db->query("insert into keu_jenis_pembayaran values ('SKS','SKS')");
  }
  $qq=$db->query("select * from keu_jenis_tagihan t where t.kode_tagihan='SKS_REG'");
  if ($qq->rowCount()==0) {
    $db->query("insert into keu_jenis_tagihan values ('SKS_REGULER','SKS MAHASISWA REGULER','SKS','N')");
  }
  $qn=$db->query("select * from keu_jenis_tagihan t where t.kode_tagihan='SKS_NON'");
  if ($qn->rowCount()==0) {
    $db->query("insert into keu_jenis_tagihan values ('SKS_NON','SKS MAHASISWA NON REGULER','SKS','N')");
  }
  $qj=$db->query("select kode_jur from jurusan");
  foreach ($qj as $k) {
     $qa=$db->query("select m.mulai_smt from mahasiswa m group by m.mulai_smt");
     foreach ($qa as $ka) {
         $qt=$db->query("select * from keu_tagihan t where t.kode_prodi='$k->kode_jur'
         and t.kode_tagihan='SKS_REGULER' and t.berlaku_angkatan='$ka->mulai_smt' ");
         if ($qt->rowCount()==0) {
            $data = array('kode_prodi'       => $k->kode_jur ,
                          'kode_tagihan'     => 'SKS_REGULER',
                          'nominal_tagihan'  => get_tarif_sks('reguler') ,
                          'berlaku_angkatan' => $ka->mulai_smt,
                          'ket' => 'sks');
            $db->insert("keu_tagihan",$data);
         }else{
           foreach ($qt as $kt) {
             $id_keu = $kt->id;
           }
           $data = array('kode_prodi'       => $k->kode_jur ,
                         'kode_tagihan'     => 'SKS_REGULER',
                         'nominal_tagihan'  => get_tarif_sks('reguler') ,
                         'berlaku_angkatan' => $ka->mulai_smt,
                         'ket' => 'sks');
           $db->update("keu_tagihan",$data,"id",$id_keu);
         }
         $qtt=$db->query("select * from keu_tagihan t where t.kode_prodi='$k->kode_jur'
         and t.kode_tagihan='SKS_NON' and t.berlaku_angkatan='$ka->mulai_smt' ");
         if ($qtt->rowCount()==0) {
            $dataxx = array('kode_prodi'       => $k->kode_jur ,
                           'kode_tagihan'     => 'SKS_NON',
                           'nominal_tagihan'  => get_tarif_sks('non'),
                           'berlaku_angkatan' => $ka->mulai_smt,
                           'ket' => 'sks');
            // print_r($dataxx);
            // echo "<br>";
            $db->insert("keu_tagihan",$dataxx);
         }else{
           foreach ($qtt as $ktt) {
             $id_keu = $ktt->id;
           }
           $dataxx = array('kode_prodi'       => $k->kode_jur ,
                          'kode_tagihan'     => 'SKS_NON',
                          'nominal_tagihan'  => get_tarif_sks('non'),
                          'berlaku_angkatan' => $ka->mulai_smt,
                          'ket' => 'sks');
           $db->update("keu_tagihan",$dataxx,"id",$id_keu);
         }
     }
  }
}*/

function get_tarif_sks($kode_prodi,$ket,$kode_tagihan,$berlaku_angkatan)
{
  global $db;
 // $q=$db->query("select nominal from tarif_sks where ket='$ket' ");
  $q=$db->query("select nominal_tagihan from keu_tagihan k
    where k.kode_prodi='$kode_prodi'
    and k.ket='$ket'
    and k.kode_tagihan='$kode_tagihan'
    and k.berlaku_angkatan='$berlaku_angkatan'");
  foreach ($q as $k) {
    return $k->nominal_tagihan;
  }
}

function get_id_tagihan($kode_prodi,$kode_tagihan,$angkatan)
{
  global $db;
  $q=$db->query("select id from keu_tagihan t where t.kode_prodi='$kode_prodi'
        and t.berlaku_angkatan='$angkatan' and t.kode_tagihan='$kode_tagihan' ");
   foreach ($q as $k) {
      return $k->id;
   }
}

function buat_tagihan($nim,$id_tagihan_prodi,$periode)
{
  global $db;
  $q= $db->query("select * from keu_tagihan_mahasiswa WHERE
       nim='$nim' and id_tagihan_prodi='$id_tagihan_prodi'
       and periode='$periode' ");
  if ($q->rowCount()==0) {
    $data = array("nim"              => $nim,
                  "id_tagihan_prodi" => $id_tagihan_prodi,
                  "periode"          => $periode);
    $db->insert("keu_tagihan_mahasiswa",$data);
  }

}

function get_kajur($kode_jur)
{
  global $db;
  $q=$db->query("select d.nama_dosen, d.nip from jurusan j left join dosen d on d.id_dosen=j.ketua_jurusan
where j.kode_jur='$kode_jur'");
$data=array();
foreach ($q as $k) {
  $data['nip'] = $k->nip;
  $data['nama'] = $k->nama_dosen;
}
return $data;
}

function get_foto($username)
{
  global $db;
  $q=$db->query("select s.foto_user from sys_users s where s.username='$username' ");
  $data=array();
  foreach ($q as $k) {
    return $k->foto_user;
  }
//return $data;
}

function get_jenjang($kode_jur)
{
  global $db;
  $q=$db->query("select jp.jenjang from jurusan j join jenjang_pendidikan jp
on jp.id_jenjang=j.id_jenjang where j.kode_jur='$kode_jur'");
  $data=array();
  foreach ($q as $k) {
    return $k->jenjang;
  }
//return $data;
}

function get_pejabat($id)
{
  global $db;
  $q = $db->query("select p.jabatan,p.id_pejabat,d.nama_dosen from pejabat p join dosen d
on d.nip=p.nama_pejabat where p.id_pejabat='$id' ");
  foreach ($q as $k) {
    return $k;
  }
}

//looping prodi berdasarkan akses prodi sesuai group users
/**
 * get periode pendaftaran seminar/sidang
 * @param  varchar $kode_pendaftaran kode pendaftaran dari table jenis_pendaftaran
 * @return string                  dropdown select
 */
function looping_periode_pendaftaran($kode_pendaftaran=null) {
  global $db;
  $akses_prodi = get_akses_prodi();
  $jurusan = $db->query("select * from view_prodi_jenjang $akses_prodi");
    if ($jurusan->rowCount()==1) {
      $akses_jur = $db->fetch_custom_single("select group_concat(kode_jur) as kode_jur from view_prodi_jenjang $akses_prodi");
      if ($akses_jur) {
        $jur_filter = "where tdj.kode_jur in(".$akses_jur->kode_jur.")";
      } else {
        //jika tidak group tidak punya akses prodi, set in 0
        $jur_filter = "where tdj.kode_jur in(0)";
      }

      $kode_filter;
      if ($kode_pendaftaran!=null) {
        $kode_filter = "and tj.kode='".$kode_pendaftaran."'";
      }
      //default semester aktif
      $sem_filter = "and tdj.semester='".get_sem_aktif()."'";
      $data = $db->query("select tdj.id, tdj.periode_bulan,status_aktif from tb_data_jadwal_pendaftaran tdj inner join tb_jenis_pendaftaran tj on tdj.id_pendaftaran=tj.id $jur_filter $sem_filter $kode_filter");
       echo "<option value='all'>Semua</option>";
      foreach ($data as $dt) {
        if ($dt->status_aktif=='Y') {
            echo "<option value='$dt->id' selected>".bulan_tahun($dt->periode_bulan)."</option>";
        } else {
                  echo "<option value='$dt->id'>".bulan_tahun($dt->periode_bulan)."</option>";
        }

      }
  } else {
    echo "<option value='all'>Semua</option>";
  }
}

function get_jumlah_sks_matkul($id_matkul) {
  global $db;
  $jml_matkul = $db->fetch_custom_single("select (sks_tm+sks_prak+sks_sim+sks_prak_lap) as jml_sks from matkul where id_matkul=?",array('id_matkul' => $id_matkul));
  return $jml_matkul->jml_sks;
}
function hari($tgl)
{
  if ($tgl!="") {
     $hari = date('l',strtotime($tgl));
     $hari = getHari($hari);
  } else {
    $hari = "";
  }
   
    return $hari;
  
}

function getHari($hari)
{
  switch ($hari) {
    case 'Sunday':
      return "Minggu";
      break;
    case 'Monday':
      return "Senin"; 
      break;
    case 'Tuesday':
      return "Selasa";
      break;
    case 'Wednesday':
      return "Rabu";
      break;
    case 'Thursday':
      return "Kamis";
      break;
    case 'Friday':
      return "Jumat";
      break;
    case 'Saturday':
      return "Sabtu";
      break;
  }
}
/**
 * [trimmer trim for import excel
 *
 * @param  [type] $excel column value
 * @return [type]  trimmed value
 */
function trimmer($value)
{
    $result = preg_replace('/[^[:print:]]/', '', filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH));
    return addslashes(trim($result));
}

function cek_barang($kd_barang){
  global $db;
  $res  = array();
  $q = $db->query("select * from barang where kd_barang='$kd_barang' ");
  if ($q->rowCount()>0) {
     foreach ($q as $k) {
       $res= (array) $k; 
     }
     $res['status'] = '1';
  }else{
     $res['status'] = '0';
     $res['kd_kategori'] = '0';
  }
}
function reset_lp_gabungan($user){
  global $db;
  $db->query("delete from temp_lp_gabungan where user=? ",array($user));
  $db->query("delete from temp_lp_gabungan_detail where user=? ",array($user));
}


function login_ws(){
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => URL_API.'/nle-oauth/v1/user/login',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{"username":"'.user_ws.'","password":"'.pass_ws.'"}',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Cookie: Customs_Cookie=!Vc8rTwQE+/axtNiR2rCAps9hQJZe3krqYv2FBrzsAe6ErBhPAnCiW/n+RWr7s5AJE8l0pE69qbLqbzA='
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);
}

function get_valuta($kode){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => URL_API.'/openapi/kurs/'.$kode,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.login_ws()->item->access_token,
        'Cookie: Customs_Cookie=!Vc8rTwQE+/axtNiR2rCAps9hQJZe3krqYv2FBrzsAe6ErBhPAnCiW/n+RWr7s5AJE8l0pE69qbLqbzA='
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);

}

function buat_entitas($id_header,$jenis_dokpab){ 
   global $db;
   $q = $db->query("select id_entitas from ref_entitas where dokumen like '%$jenis_dokpab%' ");
   $seri =1;
   foreach ($q as $k) {
      $qc = $db->query("select id_entitas from ws_entitas where id_header='$id_header' and kodeEntitas='$k->id_entitas' ");
      if ($qc->rowCount()==0) {
         $data = array('id_header' => $id_header ,  
                       'seriEntitas' => $seri,
                      // 'statusDokumen' => 'Draft',
                       'kodeEntitas' => $k->id_entitas);
         // if ($k->id_entitas=='7' && $jenis_dokpab=='23') {
         //   $data['kodeJenisIdentitas'] = ''
         // }
         
         $db->insert("ws_entitas",$data); 
      }
      $seri++;
   }
}

function get_tps($kode_kantor)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => URL_API.'/openapi/gudangTPS/kodeKantor/'.$kode_kantor,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.login_ws()->item->access_token,
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);

}

function kirim_dokumen($data)
{

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => URL_API.'/openapi/document',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>$data,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer '.login_ws()->item->access_token,
    'Cookie: Customs_Cookie=!OrkeULNXQI/BdRjw9ywOZRzcqyz6pY8sG0lEXc8IJBf9cPgGO+Mg6LCEYktfwfuyCFzLtddSOIh1vTs='
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return json_decode($response);

}

function get_status_dokumen($no_aju)
{
      $curl = curl_init();
      curl_setopt_array($curl, array(
      CURLOPT_URL => URL_API.'/openapi/status/'.$no_aju,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
         'Authorization: Bearer '.login_ws()->item->access_token,
         'Cookie: Customs_Cookie=!dX8fXeYvXlHO597w9ywOZRzcqyz6pdI5Kz5GoxPxSKId+YZDQZmQxcFBglhxOi5iArG+1mSfV7rUavs='
        ),
      ));
      $response = curl_exec($curl);
      curl_close($curl); 
      return json_decode($response);
}

function get_uuid()
{
  $data = random_bytes(16);
  assert(strlen($data) == 16);

  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_last_no(){
   global $db;
   $tgl = date("Y-m");
   $q = $db->query("select ifnull(count(id_header),0) as jml from ws_header where tanggalDokumen like '".$tgl."%' ");
   foreach ($q as $k) {
      $jml = $k->jml + 1;
   }
   if ($jml<10) {
      $nomor = "0000".$jml;
   }elseif ($jml>=10 && $jml<100) {  
       $nomor = "000".$jml;
   }elseif ($jml>=100 && $jml<1000) {
       $nomor = "00".$jml;
   }elseif ($jml>=1000 && $jml<10000) {
       $nomor = "0".$jml;
   }elseif ($jml>=10000 && $jml<100000) {
       $nomor = "".$jml;
   }elseif ($jml>=100000 && $jml<1000000) {
       $nomor = $jml;
   }
   return $nomor;
}

function buat_no_aju($jenis_dokpab){
  global $db;
  $q = $db->query("select kode_ceisa,kantor_pengawas from infokb ");
  foreach ($q as $k) {
     $kode_ceisa = $k->kode_ceisa;
     $kantor = $k->kantor_pengawas;
  }
  $kp = explode(" - ", $kantor);
  $id_kantor = substr($kp[0], 0,4);
  if (strlen($jenis_dokpab)==2) {
    $jenis_dokpab = "0".$jenis_dokpab;
  }

  $no_aju = $id_kantor.$jenis_dokpab."".$kode_ceisa."".date("Ymd")."".get_last_no();
  return $no_aju;

}

function get_info_kb(){
  global $db;
  $q = $db->query("select * from infokb");
  foreach ($q as $k) {
    return $k;
  }
}

function terbilang_id($x) {
    $x = abs($x);

    $angka = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

    if ($x < 12)
        return " " . $angka[$x];
    elseif ($x < 20)
        return terbilang_id($x - 10) . " Belas";
    elseif ($x < 100)
        return terbilang_id(floor($x / 10)) . " Puluh" . terbilang_id($x % 10);
    elseif ($x < 200)
        return " Seratus" . terbilang_id($x - 100);
    elseif ($x < 1000)
        return terbilang_id(floor($x / 100)) . " Ratus" . terbilang_id($x % 100);
    elseif ($x < 2000)
        return " Seribu" . terbilang_id($x - 1000);
    elseif ($x < 1000000)
        return terbilang_id(floor($x / 1000)) . " Ribu" . terbilang_id($x % 1000);
    elseif ($x < 1000000000)
        return terbilang_id(floor($x / 1000000)) . " Juta" . terbilang_id($x % 1000000);
    elseif ($x < 1000000000000)
        return terbilang_id(floor($x / 1000000000)) . " Miliar" . terbilang_id($x % 1000000000);
    else
        return "Angka terlalu besar";
}

function penyebut_id($nilai) {

    $nilai = number_format($nilai, 2, '.', '');

    $explode = explode('.', $nilai);

    $depan = (int)$explode[0];
    $belakang = (int)$explode[1];

    $hasil = trim(terbilang_id($depan)) . " Rupiah";

    if ($belakang > 0) {
        $hasil .= " Koma";

        $digit = str_split($explode[1]);

        foreach ($digit as $d) {
            $hasil .= " " . trim(terbilang_id($d));
        }
    }

    return $hasil;
}

function terbilang_en($number) {

    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';

    $dictionary  = array(
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
        1000000 => 'million',
        1000000000 => 'billion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if ($number < 0) {
        return $negative . terbilang_en(abs($number));
    }

    $number = (int)$number;

    $string = '';

    switch (true) {

        case $number < 21:
            $string = $dictionary[$number];
            break;

        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;

            $string = $dictionary[$tens];

            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }

            break;

        case $number < 1000:

            $hundreds  = floor($number / 100);
            $remainder = $number % 100;

            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];

            if ($remainder) {
                $string .= $conjunction . terbilang_en($remainder);
            }

            break;

        default:

            foreach ([1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand'] as $base => $label) {

                if ($number >= $base) {

                    $numBaseUnits = floor($number / $base);
                    $remainder = $number % $base;

                    $string = terbilang_en($numBaseUnits) . ' ' . $label;

                    if ($remainder) {
                        $string .= $separator . terbilang_en($remainder);
                    }

                    break;
                }
            }

            break;
    }

    return ucfirst($string);
}

function penyebut_en($nilai){

    $nilai = number_format($nilai, 2, '.', '');

    $explode = explode('.', $nilai);

    $depan = (int)$explode[0];
    $belakang = $explode[1];

    $hasil = terbilang_en($depan) . " dollars";

    if ((int)$belakang > 0) {

        $hasil .= " point";

        $angka_en = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine'
        ];

        $digit = str_split($belakang);

        foreach ($digit as $d) {
            $hasil .= " " . $angka_en[$d];
        }
    }

    return ucfirst($hasil); 
}

function generate_no_jurnal()
{

  global $db;
    
    $prefix = "JU";
    
    $tanggal = date("ym");

   $cek = $db->fetch("
    SELECT *
    FROM jurnal_header
    WHERE DATE_FORMAT(tgl_insert,'%y%m')='$tanggal'
    ORDER BY id DESC
    LIMIT 1
");

    if ($cek) {

        $last_no = substr($cek->no_jurnal, -4);

        $urut = (int)$last_no + 1;

    } else {

        $urut = 1;

    }

    $nomor = str_pad($urut, 4, "0", STR_PAD_LEFT);

    return $prefix . $tanggal . $nomor;

}


?>
