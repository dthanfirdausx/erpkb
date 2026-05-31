<?php
session_start();
include "../../inc/config.php";
      $start_date = "1970-01-01";
      $end_date = date("Y-m-d");
      if ($_POST['tgl_awal']!='') {
        $start_date = $_POST['tgl_awal'];
      }
      if ($_POST['tgl_akhir']!='') {
         $end_date   = $_POST['tgl_akhir'];
      }
     
      $month = date('m',strtotime($start_date));
      $year = date('Y',strtotime($start_date));
      $cek = $month-1;
      if($cek==0){
        $bln = 12;
        $thn = $year-1;
      }else{  
        $bln = $cek;
        $thn = $year;
      }
      if ((int)date("d",strtotime($start_date))=='1') {
        $start_date2='1900-00-00';
        $end_date2='1900-00-00';
      }else{
        $tg = explode("-", $start_date);
        $start_date2 = $tg[0]."-".$tg[1]."-01";
        if ((int)date("d",strtotime($start_date))<10) {
          $akhir = "0".((int)date("d",strtotime($start_date))-1);
        }else{
          $akhir = date("d",strtotime($start_date))-1; 
        }
        $end_date2 = $tg[0]."-".$tg[1]."-".$akhir;
      }
      if (!isset($_SESSION['IKB4_status_UserID'])) {
        $user = "admin";
      }else{
         $user= $_SESSION['IKB4_status_UserID'];       
       }  
     
//      echo "create view vmutasiscrap as 
//  SELECT a.kd_barang, a.nm_barang, a.type as tipe, a.satuan, 
//       ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0) as saldo_awal,
//       ifnull(c.jumlah,0)+ifnull(g.jumlah,0) as pemasukan, 
//       ifnull(d.jumlah,0)+ifnull(h.jumlah,0) as pengeluaran, 
//       ifnull(e.jumlah,0) as penyesuaian, 
//       (ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0))+(ifnull(c.jumlah,0)+ifnull(g.jumlah,0))-(ifnull(d.jumlah,0)+ifnull(h.jumlah,0))+ifnull(e.jumlah,0) as saldo_akhir, 
//       ifnull(f.jumlah,0) as stock_opname, 
//       '0' as selisih, 
//       'Sesuai' as ket ,'$user' as userid,'$start_date' as dari,'$end_date' as sampai
//   FROM barang as a
//   LEFT JOIN (SELECT tgl_closing as tgl_closing,@max := tgl_closing,stock, kd_barang from closing a
// WHERE tgl_closing=(select max(tgl_closing) from closing where kd_barang=a.kd_barang and tgl_closing<'$start_date')) as b
//         ON b.kd_barang = a.kd_barang
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
//         where dari='PRODUKSI' and tgl_lpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) as b1
//         ON b1.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
//         where tgl_bpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b2
//         ON b2.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
//         where tgl_sj between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b3 
//         ON b3.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
//         where tgl_bpb between '$start_date' and '$end_date' group by kode) AS c
//         ON c.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
//         where tgl_sj between '$start_date' and '$end_date' group by kode) AS d 
//         ON d.kode=a.kd_barang         
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from adjusment_detail
//         where tgl_adj between '$start_date' and '$end_date' group by kode) AS e
//         ON e.kode=a.kd_barang
//   LEFT JOIN  (SELECT kode_brg,max(tglstock),stockopname as jumlah  FROM stockopname_scrap
//         WHERE tglstock between '$start_date' and '$end_date' group by kode_brg) as f
//         ON f.kode_brg=a.kd_barang     
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
//         where tgl_lpb  between '$start_date' and '$end_date' group by kode) as g
//         ON g.kode=a.kd_barang        
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from produksi_terima_detail
//         where tgl_lpb between '$start_date' and '$end_date' group by kode) as h
//         ON h.kode=a.kd_barang           
//   WHERE a.kd_kategori='K04' ORDER BY a.kd_barang ASC"; 
//        $db->query("drop view vmutasiscrap"); 
//        $db->query("create view vmutasiscrap as 
//  SELECT a.kd_barang, a.nm_barang, a.type as tipe, a.satuan, 
//       ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0) as saldo_awal,
//       ifnull(c.jumlah,0)+ifnull(g.jumlah,0) as pemasukan, 
//       ifnull(d.jumlah,0)+ifnull(h.jumlah,0) as pengeluaran, 
//       ifnull(e.jumlah,0) as penyesuaian, 
//       (ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0))+(ifnull(c.jumlah,0)+ifnull(g.jumlah,0))-(ifnull(d.jumlah,0)+ifnull(h.jumlah,0))+ifnull(e.jumlah,0) as saldo_akhir, 
//       ifnull(f.jumlah,0) as stock_opname, 
//       '0' as selisih, 
//       'Sesuai' as ket ,'$user' as userid,'$start_date' as dari,'$end_date' as sampai
//   FROM barang as a
//   LEFT JOIN (SELECT tgl_closing as tgl_closing,@max := tgl_closing,stock, kd_barang from closing a
// WHERE tgl_closing=(select max(tgl_closing) from closing where kd_barang=a.kd_barang and tgl_closing<'$start_date')) as b
//         ON b.kd_barang = a.kd_barang
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
//         where dari='PRODUKSI' and tgl_lpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) as b1
//         ON b1.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
//         where tgl_bpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b2
//         ON b2.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
//         where tgl_sj between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b3 
//         ON b3.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
//         where tgl_bpb between '$start_date' and '$end_date' group by kode) AS c
//         ON c.kode=a.kd_barang
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
//         where tgl_sj between '$start_date' and '$end_date' group by kode) AS d 
//         ON d.kode=a.kd_barang         
//   LEFT JOIN  (select kode, sum(jumlah) as jumlah from adjusment_detail
//         where tgl_adj between '$start_date' and '$end_date' group by kode) AS e
//         ON e.kode=a.kd_barang
//   LEFT JOIN  (SELECT kode_brg,max(tglstock),stockopname as jumlah  FROM stockopname_scrap
//         WHERE tglstock between '$start_date' and '$end_date' group by kode_brg) as f
//         ON f.kode_brg=a.kd_barang     
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
//         where tgl_lpb  between '$start_date' and '$end_date' group by kode) as g
//         ON g.kode=a.kd_barang        
//   LEFT JOIN (select kode, sum(jumlah) as jumlah from produksi_terima_detail
//         where tgl_lpb between '$start_date' and '$end_date' group by kode) as h
//         ON h.kode=a.kd_barang           
//   WHERE a.kd_kategori='K04' ORDER BY a.kd_barang ASC");
//        echo $db->getErrorMessage();  
        //echo "CALL spmutasibb('$user','$start_date','$end_date','$start_date2','$end_date2','$bln','$thn')  <br>";  
      //  $db->query("CALL spmutasiscrap('$user','$start_date','$end_date')");  

$columns = array(
    'mutasi_scrap.kd_barang',
    'mutasi_scrap.nm_barang', 
    'mutasi_scrap.type',
    'mutasi_scrap.satuan',
    'mutasi_scrap.saldo_awal',
    'mutasi_scrap.pemasukan',
    'mutasi_scrap.pengeluaran',
    'mutasi_scrap.penyesuaian',
    'mutasi_scrap.saldo_akhir',
    'mutasi_scrap.stock_opname',
    'mutasi_scrap.selisih',
    'mutasi_scrap.ket',
    'mutasi_scrap.userid',
    'mutasi_scrap.kd_barang',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('type','mutasi_scrap.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("mutasi_scrap.kd_barang");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by mutasi_scrap.id"; 

  $query = $datatable->get_custom("select mutasi_scrap.kd_barang,mutasi_scrap.nm_barang,mutasi_scrap.type,mutasi_scrap.satuan,mutasi_scrap.saldo_awal,mutasi_scrap.pemasukan,mutasi_scrap.pengeluaran,mutasi_scrap.penyesuaian,mutasi_scrap.saldo_akhir,mutasi_scrap.stock_opname,mutasi_scrap.selisih,mutasi_scrap.ket,mutasi_scrap.userid,mutasi_scrap.kd_barang from (SELECT a.kd_barang, a.nm_barang, a.type, a.satuan, 
      ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0) as saldo_awal,
      ifnull(c.jumlah,0)+ifnull(g.jumlah,0) as pemasukan, 
      ifnull(d.jumlah,0)+ifnull(h.jumlah,0) as pengeluaran, 
      ifnull(e.jumlah,0) as penyesuaian, 
      (ifnull(b.stock,0)+ifnull(b1.jumlah,0)+ifnull(b2.jumlah,0)-ifnull(b3.jumlah,0))+(ifnull(c.jumlah,0)+ifnull(g.jumlah,0))-(ifnull(d.jumlah,0)+ifnull(h.jumlah,0))+ifnull(e.jumlah,0) as saldo_akhir, 
      ifnull(f.jumlah,0) as stock_opname, 
      '0' as selisih, 
      'Sesuai' as ket ,'$user' as userid,'$start_date' as dari,'$end_date' as sampai
  FROM barang as a
  LEFT JOIN (SELECT tgl_closing as tgl_closing,@max := tgl_closing,stock, kd_barang from closing a
WHERE tgl_closing=(select max(tgl_closing) from closing where kd_barang=a.kd_barang and tgl_closing<'$start_date')) as b
        ON b.kd_barang = a.kd_barang
  LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
        where dari='PRODUKSI' and tgl_lpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) as b1
        ON b1.kode=a.kd_barang
  LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
        where tgl_bpb between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b2
        ON b2.kode=a.kd_barang
  LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
        where tgl_sj between date_sub(@max,interval -1 day) and date_sub('$start_date',interval 1 day) group by kode) AS b3 
        ON b3.kode=a.kd_barang
  LEFT JOIN  (select kode, sum(jumlah) as jumlah from pemasukan_detail
        where tgl_bpb between '$start_date' and '$end_date' group by kode) AS c
        ON c.kode=a.kd_barang
  LEFT JOIN  (select kode, sum(jumlah) as jumlah from pengeluaran_detail
        where tgl_sj between '$start_date' and '$end_date' group by kode) AS d 
        ON d.kode=a.kd_barang         
  LEFT JOIN  (select kode, sum(jumlah) as jumlah from adjusment_detail
        where tgl_adj between '$start_date' and '$end_date' group by kode) AS e
        ON e.kode=a.kd_barang
  LEFT JOIN  (SELECT kode_brg,max(tglstock),stockopname as jumlah  FROM stockopname_scrap
        WHERE tglstock between '$start_date' and '$end_date' group by kode_brg) as f
        ON f.kode_brg=a.kd_barang     
  LEFT JOIN (select kode, sum(jumlah) as jumlah from outgoing_terima_detail
        where tgl_lpb  between '$start_date' and '$end_date' group by kode) as g
        ON g.kode=a.kd_barang        
  LEFT JOIN (select kode, sum(jumlah) as jumlah from produksi_terima_detail
        where tgl_lpb between '$start_date' and '$end_date' group by kode) as h
        ON h.kode=a.kd_barang           
  WHERE a.kd_kategori='K04' ORDER BY a.kd_barang ASC) mutasi_scrap where pemasukan!=0 or pengeluaran!=0 ",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->type;
    $ResultData[] = $value->satuan;
    $ResultData[] = number_format($value->saldo_awal,2,",",".");
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",1)' >".number_format($value->pemasukan,2,",",".")."</a>";
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",2)' >".number_format($value->pengeluaran,2,",",".")."</a>";
    $ResultData[] = number_format($value->penyesuaian,2,",",".");
    $ResultData[] = number_format($value->saldo_akhir,2,",",".");
    $ResultData[] = number_format($value->stock_opname,2,",",".");
    $ResultData[] = number_format($value->selisih,2,",",".");
    $ResultData[] = $value->ket;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->kd_barang;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>