<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {


case "reversal":

$no_spb = $_POST['id'];  
$no_ref_rev = $no_spb.'_REV';

// 🔥 ambil no_urut terakhir 
$cek_urut = $db->query("
    SELECT IFNULL(MAX(no_urut),0) as urut
    FROM detail_transaksi
    WHERE no_ref = '$no_ref_rev'
")->fetch();  

$no_urut = $cek_urut->urut + 1;

// ================= GUDANG =================
$q = $db->query("
    SELECT * FROM detail_transaksi
    WHERE no_ref = '$no_spb'
    AND posisi = 'PRODUKSI'
");
// echo "SELECT * FROM detail_transaksi
//     WHERE no_ref = '$no_spb'
//     AND posisi = 'PRODUKSI'";

foreach ($q as $k){ 

    $qty = abs($k->qty);

    // ================= LEDGER REVERSAL =================
    $db->insert("detail_transaksi", [
        'kd_barang'     => $k->kd_barang,
        'qty'           => $qty,
        'posisi'        => 'PRODUKSI',
        'no_ref'        => $no_ref_rev,
        'no_urut'       => $no_urut++, // 🔥 TAMBAHAN
        'move_code'     => '262',
        'no_dokpab'     => $k->no_dokpab,
        'no_aju'        => $k->no_aju,
        'remark'        => 'Reversal transfer '.$no_spb,
        'posting_date'  => date("Y-m-d"),
        'created_by'    => $_SESSION['username'],
        'date_created'  => date("Y-m-d H:i:s")
    ]);

    // ================= RESTORE STOCK_LAYER =================
    $sisa = $qty;

    $q_layer = $db->query("
        SELECT * FROM stock_layer
        WHERE kode = '".$k->kd_barang."'
        AND no_aju = '".$k->no_aju."'
        AND no_dokpab = '".$k->no_dokpab."'
        ORDER BY id DESC
    ");
 
    foreach ($q_layer as $layer){

        if ($sisa <= 0) break;

        $max_restore = $layer->qty_masuk - $layer->qty_sisa;

        if ($max_restore <= 0) continue;

        $restore = min($sisa, $max_restore);

        $db->query("
            UPDATE stock_layer
            SET qty_sisa = qty_sisa + $restore
            WHERE id = '".$layer->id."'
        ");

        $sisa -= $restore;
    }
}

// ================= PRODUKSI =================
$q2 = $db->query("
    SELECT * FROM detail_transaksi
    WHERE no_ref = '$no_spb'
    AND posisi = 'PRODUKSI'
");

foreach ($q2 as $k){ 

    $db->insert("detail_transaksi", [
        'kd_barang'     => $k->kd_barang,
        'qty'           => ($k->qty * -1),
        'posisi'        => 'GUDANG',
        'no_ref'        => $no_ref_rev,
        'no_urut'       => $no_urut++, // 🔥 TAMBAHAN
        'move_code'     => '102',
        'no_dokpab'     => $k->no_dokpab,
        'no_aju'        => $k->no_aju,
        'remark'        => 'Reversal transfer '.$no_spb,
        'posting_date'  => date("Y-m-d"),
        'created_by'    => $_SESSION['username'],
        'date_created'  => date("Y-m-d H:i:s")
    ]);
}

// 🔥 update status
$db->query("UPDATE transfer SET status='9' WHERE no_transfer='$no_spb'");

echo json_encode([
    "status" => "good"
]);

break;

  case "get_stock_produksi_multi":

$kode_list = $_POST['kode']; // array

$kode_in = "'" . implode("','", $kode_list) . "'";

$q = $db->query("
    SELECT 
        dt.kd_barang,
        b.nm_barang,
        b.satuan,
        IFNULL(SUM(dt.qty),0) as stock
    FROM detail_transaksi dt
    LEFT JOIN barang b ON b.kd_barang = dt.kd_barang
    WHERE dt.kd_barang IN ($kode_in)
      AND dt.posisi = 'PRODUKSI'
    GROUP BY dt.kd_barang
");

$data = [];

foreach ($q as $k) {
    $data[] = [
        "kode"   => $k->kd_barang,
        "nama"   => $k->nm_barang,
        "satuan" => $k->satuan,
        "stock"  => round((float)$k->stock, 4)
    ];
}

echo json_encode($data);
break;

   case "down_excel":
  header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=data_transfer_detail.xls");  //File name extension was wrong
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
   $awal = $_GET['tgl_awal'];
   $akhir = $_GET['tgl_akhir'];
   ?>
   <h4 style="text-align: center;">Detail Transfer Barang dari Produksi</h4>
   <p style="text-align: center;">Tanggal <?= $awal ?> s/d <?= $akhir ?></p>
   <table border="1">
     <thead>
       <tr>
         <th>No</th>
         <th>No Transfer</th>
         <th>Tgl transfer</th>
         <th>Kode Barang</th>
         <th>Nama Barang</th>
         <th>Jumlah</th>
         <th>Satuan</th>
         <th>Tujuan</th>
       </tr>
     </thead>
     <tbody>
     <?php
      $no=1;
      $q = $db->query("select * from v_transfer_detail where tgl_transfer between '$awal' and '$akhir' and dari='3'  ");
      foreach ($q as $k) {
       echo "<tr>
        <td>$no</td>
        <td>$k->no_transfer</td>
        <td>".tgl_indo($k->tgl_transfer)."</td>
        <td>$k->kd_barang</td>
        <td>$k->nm_barang</td>
        <td>$k->jml</td>
        <td>$k->satuan</td>
        <td>$k->nm_bagian</td>
       
       </tr>";
       $no++;
      }
     ?>
     </tbody>
   </table>
   <?php
  

    break;

  case "show_detail":
  $no_spb = $_POST['no_spb'];
  ?>
  <table class="table">
    <thead>
      <tr>
        <th>No</th>
      <!--   <th>SPB</th>
        <th>Tanggal SPB</th> -->
        <th>Kode Barang</th>
       <!--  <th>Jenis Dokpab</th> -->
       <!--  <th>No Dokpab</th>
        <th>No Aju</th>
        <th>Qty RO</th> -->
        <th>Qty</th>
        <th>Satuan</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $q = $db->query("select d.no, pd.jumlah as jml_produksi,pd.id_produksi_detail, pm.no_dokpab,pm.no_aju,b.nm_barang, t.no_transfer as no_spb,t.tgl_transfer as tgl_spb,b.kd_barang as kode,pm.jenis_dokpab,ifnull(rd.jumlah,0) as qtyro,
d.jml as jumlah,
b.satuan,t.ket
from transfer t join transfer_detail d on d.id_transfer=t.id_transfer
left join pemasukan_detail dt on dt.id=d.id_incoming_detail
left join brgjadi_detail pd on pd.id_produksi_detail=d.id_produksi_detail
left join barang b on b.id=d.id_barang
left join pemasukan pm on pm.no_bpb=dt.no_bpb
left join ro on ro.no_ro=t.no_ro
left join ro_detail rd on (rd.no_ro=ro.no_ro and rd.kode=b.kd_barang)
where t.no_transfer='$no_spb'

 ");
    $no = 0;
    foreach ($q as $k) {  
      $jumlah = $k->jumlah;
      if ($k->jml_produksi!='') {
           $jumlah = $k->jml_produksi;
         }   
      if ($no!=$k->no) {
         $no = $k->no;
          echo "<tr>
             <td>$k->no</td>
           
             <td>$k->kode , $k->nm_barang</td>
            
             <td>".number_format($jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
      }else{
         echo "<tr>
             <td></td>
           
             <td></td>
             
             <td>".number_format($jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
      }
     
    }
    ?>
    </tbody>
  </table>
  <?php
    break;

   case "in":

$data = [
    "tgl_transfer" => $_POST["tgl_spb"],
    "user"         => $_SESSION['username'],
    "dept"         => $_POST["dept"],
    "ket"          => $_POST["catatan"],
    "dari"         => '3',
    "ke"           => '1',
    "date_created" => date("Y-m-d H:i:s")
];

$db->insert("transfer",$data);
$id = $db->last_insert_id();

$no_transfer = GetNoTransfer($id,5);
$db->query("UPDATE transfer SET no_transfer='$no_transfer' WHERE id_transfer='$id'");

$no = 1;

foreach($_POST['kode_input'] as $key => $kode){

    $qty = (float)$_POST['qty'][$key];
    if($qty <= 0) continue;

    // ambil id barang + kategori
    $barang = $db->query("
        SELECT id, kd_kategori 
        FROM barang 
        WHERE kd_barang = '$kode'
    ")->fetch();

    $is_barang_jadi = in_array($barang->kd_kategori, ['K02','K07']);

    // 🔥 SIMPAN DETAIL TRANSFER
    $db->insert("transfer_detail", [
        'id_transfer'  => $id,
        'id_barang'    => $barang->id,
        'jml'          => $qty,
        'no'           => $no,
        'date_created' => date("Y-m-d H:i:s")
    ]);

    // ================= VALIDASI STOCK =================
    $cek = $db->query("
        SELECT SUM(qty_sisa) as sisa
        FROM stock_layer
        WHERE kode = '".$kode."'
        AND lokasi = 'PRODUKSI'
    ")->fetch();

    if ($cek->sisa < $qty) {
        die(json_encode([
            "status"=>"error",
            "error_message"=>"Stock produksi ".$kode." tidak cukup"
        ]));
    }

    // ================= FIFO =================
    $sisa = $qty;

    $q_layer = $db->query(" 
        SELECT *
        FROM stock_layer
        WHERE kode = '".$kode."'
        AND lokasi = 'PRODUKSI'
        AND qty_sisa > 0
        ORDER BY tgl_masuk ASC, id ASC
    ");

    foreach ($q_layer as $layer){

        if ($sisa <= 0) break;

        $pakai = ($sisa > $layer->qty_sisa) 
                 ? $layer->qty_sisa 
                 : $sisa;

        $sisa -= $pakai;

        // 🔥 ambil asal produksi (kalau barang jadi)
        $no_bpb = $is_barang_jadi ? ($layer->no_bpb ?? null) : null;

        // 🔻 PRODUKSI OUT
        $db->insert("detail_transaksi", [
            'kd_barang'     => $kode,
            'qty'           => ($pakai * -1),
            'posisi'        => 'PRODUKSI',
            'move_code'     => '311',
            'no_ref'        => $no_transfer,
            'no_aju'        => $layer->no_aju,
            'no_dokpab'     => $layer->no_dokpab,
            'no_bpb'        => $no_bpb,
            'remark'        => 'Transfer ke Gudang',
            'posting_date'  => $_POST["tgl_spb"],
            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // 🔺 GUDANG IN
        $db->insert("detail_transaksi", [ 
            'kd_barang'     => $kode,
            'qty'           => $pakai,
            'posisi'        => 'TRANSIT',
            'move_code'     => '311',
            'no_ref'        => $no_transfer,
            'no_aju'        => $layer->no_aju,
            'no_dokpab'     => $layer->no_dokpab,
            'no_bpb'        => $no_bpb,
            'remark'        => 'Terima dari Produksi',
            'posting_date'  => $_POST["tgl_spb"],
            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // 🔻 UPDATE STOCK_LAYER PRODUKSI
        $db->query("
            UPDATE stock_layer
            SET qty_sisa = qty_sisa - $pakai
            WHERE id = '".$layer->id."'
        ");

        // 🔺 INSERT STOCK_LAYER GUDANG
        $db->insert("stock_layer", [
            'kode'        => $kode,
            'qty_masuk'   => $pakai,
            'qty_sisa'    => $pakai,
            'lokasi'      => 'GUDANG',
            'no_aju'      => $layer->no_aju,
            'no_dokpab'   => $layer->no_dokpab,
            'no_bpb'      => $no_bpb,
            'ref_table'   => 'transfer',
            'ref_id'      => $id,
            'tgl_masuk'   => $_POST["tgl_spb"],
            'created_at'  => date("Y-m-d H:i:s")
        ]);
    }

    $no++;
}

action_response($db->getErrorMessage());
break;

case "get_stock_produksi":

$kode = $_POST['kode'];

// 🔥 HANDLE ARRAY ATAU STRING
if(is_array($kode)){
    $kode_in = "'" . implode("','", $kode) . "'";
    $where = "dt.kd_barang IN ($kode_in)";
} else {
    $where = "dt.kd_barang = '$kode'";
}

// 🔥 QUERY
$q = $db->query("
    SELECT 
        dt.kd_barang,
        b.nm_barang,
        b.satuan,
        IFNULL(SUM(dt.qty),0) as stock
    FROM detail_transaksi dt
    LEFT JOIN barang b ON b.kd_barang = dt.kd_barang
    WHERE $where
      AND dt.posisi = 'PRODUKSI'
    GROUP BY dt.kd_barang
");

// 🔥 OUTPUT
$data = [];

foreach ($q as $k) {
    $data[] = [
        "kode"   => $k->kd_barang,
        "nama"   => $k->nm_barang,
        "satuan" => $k->satuan,
        "stock"  => (float)$k->stock
    ];
}

// fallback
if(empty($data)){
    $data[] = [
        "kode"   => is_array($kode) ? '' : $kode,
        "nama"   => "",
        "satuan" => "",
        "stock"  => 0
    ];
}

echo json_encode($data);
break;
 //  case "in":
 // //   print_r($_POST);
 //    $dari             = '3';
 //    $ke               = $_POST['tujuan_transfer'];
 //    if ($ke == '2') {
 //      $tujuan = "Pra Produksi";
 //    }elseif ($ke == '1') {
 //      $tujuan = "Incoming"; 
 //    }else{
 //       $tujuan = "Outgoing";
 //    }
 //    $data = array(  
 //      "tgl_transfer"  => $_POST["tgl_spb"],
 //      // "no_ro"         => $_POST["no_request"],
 //      // "tgl_ro"        => $_POST["tgl_request"],
 //      "user"          => $_SESSION['username'],
 //      "ket"           => $_POST["catatan"],
 //      "kd_dept"       => $_POST["dept"],
 //      "status"        => "0",
 //      "dari"          => $dari,
 //      "ke"            => $ke,
 //      "date_created"  => date("Y-m-d H:i:s")
 //    );
 //    $in               = $db->insert("transfer",$data);
 //    $id               = $db->last_insert_id(); 
 //    $no_transfer      = GetNoTransfer($id,5);
 //    $db->query("update transfer set no_transfer='$no_transfer' where id_transfer='$id' ");
 //    $log_transfer = "Transfer dari Produksi tujuan $tujuan dengan No Transfer $no_transfer";
 //    //untuk hasil produksi
 //    if ($_POST['jenis_barang']=='1') { 
 //        $no=1; 
 //          foreach ($_POST['kode_input'] as $key => $value) {    
 //              $jumlah = $_POST['qty'][$key];
 //              $qp = $db->query("select sisa as jumlah,id_produksi_detail,no_bpb,tgl_bpb from v_stock_produksi_tersedia where id_barang='".$_POST['id_input'][$key]."' order by tgl_bpb asc");
 //                foreach ($qp as $kp) {
 //                  if ($jumlah>0){
 //                      if ($jumlah>$kp->jumlah) {
 //                          $jml_terpakai  = $kp->jumlah;
 //                          $jumlah        = $jumlah - $kp->jumlah;
 //                      }else{
 //                          $jml_terpakai  = $jumlah;
 //                          $jumlah        = $jumlah - $jml_terpakai;
 //                      }     
 //                       $data_detail = array(
 //                                           'id_transfer'        => $id , 
 //                                           'id_barang'          => $_POST['id_input'][$key],
 //                                           'id_produksi_detail' => $kp->id_produksi_detail,
 //                                           'no'                 => $no,
 //                                           'jml'                => $jml_terpakai,
 //                                           'date_created'       => date("Y-m-d H:i:s")
 //                                           ); 
 //                      $db->insert("transfer_detail",$data_detail);  
 //                       update_stock($jml_terpakai,'minus','brg_jadi','3',$_POST['id_input'][$key],$_SESSION['username']);     
 //                  }
 //                }
 //          }
 //    }else{
 //          $no=1; 
 //          foreach ($_POST['kode_input'] as $key => $value) {    
 //              $jumlah = $_POST['qty'][$key];
 //              $qc = $db->query("select no_dokpab,no_aju,kd_barang,nm_barang, id_incoming_detail,(masuk-keluar) as jumlah ,jenis_dokpab from v_rekap_stok_produksi 
 //                where ((masuk)-keluar)>0 and id_barang='".$_POST['id_input'][$key]."' order by tgl_bpb asc");
 //              $temp = 0;  
 //           //   if ($qc->rowCount()>0) {
 //                foreach ($qc as $kc){  
 //                  if ($jumlah>0){
 //                     // $log_transfer .="
 //                     //  <tr>
 //                     //    <td>$no</td>
 //                     //    <td>$kc->kd_barang</td>
 //                     //    <td>$kc->nm_barang</td>
 //                     //    <td>$jumlah</td>
 //                     //    <td>$kc->jenis_dokpab</td>
 //                     //    <td>$kc->no_aju</td>
 //                     //    <td>$kc->no_dokpab</td>
 //                     //  </tr>
 //                     //  ";
 //                      if ($jumlah>$kc->jumlah) {
 //                          $jml_terpakai  = $kc->jumlah;
 //                          $jumlah        = $jumlah - $kc->jumlah;
 //                      }else{
 //                          $jml_terpakai  = $jumlah;
 //                          $jumlah        = $jumlah - $jml_terpakai;
 //                      }      
 //                      $data_detail = array(
 //                                           'id_transfer'        => $id , 
 //                                           'id_barang'          => $_POST['id_input'][$key],
 //                                           'id_incoming_detail' => $kc->id_incoming_detail,
 //                                           'no'                 => $no,
 //                                           'jml'                => $jml_terpakai,
 //                                           'date_created'       => date("Y-m-d H:i:s")
 //                                           ); 
 //                      $db->insert("transfer_detail",$data_detail);   
 //                       update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$_POST['id_input'][$key],$_SESSION['username']);       
 //                  }    

 //                }
        
 //              $no++; 
 //          }  
 //    }
    
 //    // $log_transfer .="<br>
 //    // <table class='table'>
 //    // <thead>
 //    //  <tr>
 //    //    <th>No</th>
 //    //    <th>Kode</th>
 //    //    <th>Nama Barang</th>
 //    //    <th>Jumlah</th>
 //    //    <th>Jenis Dokpab</th>
 //    //    <th>No Aju Asal<th>
 //    //    <th>No Dokpab</th>
 //    //  </tr>
 //    //  </thead>
 //    //  <tbody>";
     
   
 //  // $log_transfer .="</tbody></table>";
 //    simpan_log($log_transfer,$_SESSION['username']);
 //    action_response($db->getErrorMessage());
 //  break;
  case "delete":
    
    $q = $db->query("select t.dari, d.jml,d.id_transfer_detail,t.is_produksi,pd.id as id_incoming_detail , bj.jumlah as jml_prod,
b.id as id_barang,pm.jenis_dokpab,bj.id_produksi_detail
from transfer_detail d 
join transfer t on t.id_transfer=d.id_transfer
left join pemasukan_detail pd on pd.id=d.id_incoming_detail
left join pemasukan pm on pm.no_bpb=pd.no_bpb
left join brgjadi_detail bj on bj.id_produksi_detail=d.id_produksi_detail
left join barang b on b.kd_barang=bj.kode
 where d.id_transfer='".$_GET["id"]."'  ");
   if ($q->rowCount()>0) {
     foreach ($q as $k) {
      if ($k->is_produksi!='1') {
        update_stock($k->jml,'plus','brg_jadi','3',$k->id_barang,$_SESSION['username']);
      }else{
        update_stock($k->jml,'plus',$k->jenis_dokpab,$k->dari,$k->id_barang,$_SESSION['username']);
      }     
     }
   } 
    
    
    $db->delete("transfer","id_transfer",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("produksi_outgoing","no_spb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "no_bpb" => $_POST["no_bpb"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"=>$_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("produksi_outgoing",$data,"no_spb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>