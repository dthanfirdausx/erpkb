<?php
session_start();
include "../../inc/config.php";
session_check_json();

function lp_traceability_locked_response() {
  echo json_encode(array(
    'status' => 'error',
    'error_message' => 'LP Produksi legacy dikunci. Penerimaan barang jadi/setengah jadi wajib melalui Production Order -> Production Confirmation -> GR from Production Order supaya trace bahan baku dan dokumen BC tetap utuh.'
  ));
  exit;
}


function ambil_bahan_baku($kodebj,$jumlah_produksi,$id_produksi_detail,$id_transfer=NULL)
{
  global $db;
   $no=1;
   $q = $db->query("select id from barang where kd_barang='$kodebj' ");
   foreach ($q as $k) {
     $id_barang = $k->id;
   }
  $q = $db->query("select d.kodebb,d.jumlah from bom_detail d join bom b on d.id_bom=b.id where b.kodebj='$kodebj' ");
  foreach ($q as $k) {
    $qc = $db->query("select id_barang,id_incoming_detail,(masuk-keluar) as jumlah,jenis_dokpab from v_rekap_stok_produksi 
      where (masuk-keluar)>0 and kd_barang='$k->kodebb'");
   
    $jumlah = $jumlah_produksi * $k->jumlah;
    foreach ($qc as $kc) {  
     //  echo "$jumlah, ";
     //  print_r($kc);
       if ($jumlah>0){
          if ($jumlah>$kc->jumlah) {
            $jml_terpakai  = $kc->jumlah;
            $jumlah        = $jumlah - $kc->jumlah;
          }else{
            $jml_terpakai  = $jumlah;
            $jumlah        = $jumlah - $jml_terpakai;
          }    
          $data_detail = array(
             'id_produksi_detail' => $id_produksi_detail,
             'id_incoming_detail' => $kc->id_incoming_detail,
             'kode'               => $k->kodebb,
             'jumlah'             => $jml_terpakai,
             'row_no'             => $no
          ); 
         
          $db->insert("bahanbaku_detail",$data_detail);    
           update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);  
       //   update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'3',$kc->id_barang,$_SESSION['username']);
      }
      $no++;
    }
  }
   $data_transfer_detail = array('id_transfer' => $id_transfer , 
                                 'id_barang' => $id_barang,
                                  'id_produksi_detail' => $id_produksi_detail,
                                  'jml' => $jumlah_produksi );
   $db->insert("transfer_detail",$data_transfer_detail); 
    update_stock($jumlah_produksi,'plus','brg_jadi','3',$id_barang,$_SESSION['username']); 
}

switch ($_GET["act"]) {

  case "get_sales_order":

    $no_so = $_POST['no_sales_order'];

    $q = $db->query("
        SELECT 
            d.kd_barang,
            b.nm_barang,
            d.qty,
            b.satuan
        FROM sales_order so
        INNER JOIN sales_order_detail d 
            ON so.id_sales_order = d.id_sales_order
        LEFT JOIN barang b 
            ON b.kd_barang = d.kd_barang
        WHERE so.no_sales_order = '$no_so'
    ");

    $result = [];

    foreach ($q as $row){
        $result[] = [
            "kd_barang" => $row->kd_barang,
            "nm_barang" => $row->nm_barang,
            "qty"       => (float)$row->qty,
            "satuan"    => $row->satuan
        ];
    }

    echo json_encode($result);

break;

  case "reversal":

$id = $_POST['id'];

// update status jadi reversal
$db->query("
    UPDATE brgjadi 
    SET status='9' 
    WHERE id_produksi='$id'
");

// ambil transaksi lama
$q = $db->query("
    SELECT * 
    FROM detail_transaksi 
    WHERE no_ref IN (
        SELECT no_bpb 
        FROM brgjadi 
        WHERE id_produksi='$id'
    )
");

foreach($q as $k){

    // insert reversal transaksi
    $db->insert("detail_transaksi", [
        'kd_barang'      => $k->kd_barang,
        'qty'            => ($k->qty * -1),
        'posisi'         => $k->posisi,
        'move_code'      => 'REV',
        'no_ref'         => $k->no_ref,
        'remark'         => 'Reversal LP',
        'document_date'  => date("Y-m-d"),
        'posting_date'   => date("Y-m-d"),
        'created_by'     => $_SESSION['username'],
        'date_created'   => date("Y-m-d H:i:s")
    ]);

    /*
    |--------------------------------------------------------------------------
    | UPDATE STOCK LAYER
    |--------------------------------------------------------------------------
    | Jika transaksi lama qty negatif
    | berarti dulu mengurangi stock layer
    | saat reversal stock layer dikembalikan lagi
    */

    if($k->qty < 0){ 

        $qty_kembali = abs($k->qty);

        // ambil layer FIFO yang pernah dipakai
        $layer = $db->query("
            SELECT *
            FROM stock_layer
            WHERE kode = '$k->kd_barang'
            AND lokasi = '$k->posisi'
            ORDER BY tgl_masuk ASC, id ASC
        ");

        foreach($layer as $l){

            if($qty_kembali <= 0){
                break;
            }

            $maksimum_bisa_kembali =
                $l->qty_masuk - $l->qty_sisa;

            if($maksimum_bisa_kembali <= 0){
                continue;
            }

            $qty_return = min(
                $qty_kembali,
                $maksimum_bisa_kembali
            );

            // kembalikan qty_sisa
            $db->query("
                UPDATE stock_layer
                SET qty_sisa = qty_sisa + $qty_return
                WHERE id = '$l->id'
            ");

            $qty_kembali -= $qty_return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Jika transaksi lama qty positif
    | berarti dulu menambah stock layer
    | saat reversal stock layer dikurangi kembali
    |--------------------------------------------------------------------------
    */

    if($k->qty > 0){

        $qty_kurang = abs($k->qty);

        $layer = $db->query("
            SELECT *
            FROM stock_layer
            WHERE kode = '$k->kd_barang'
            AND lokasi = '$k->posisi'
            ORDER BY id DESC
        ");

        foreach($layer as $l){

            if($qty_kurang <= 0){
                break;
            }

            if($l->qty_sisa <= 0){
                continue;
            }

            $ambil = min(
                $qty_kurang,
                $l->qty_sisa
            );

            $db->query("
                UPDATE stock_layer
                SET qty_sisa = qty_sisa - $ambil
                WHERE id = '$l->id'
            ");

            $qty_kurang -= $ambil;
        }
    }
}

echo json_encode([
    "status" => "ok"
]);

break;


  case "get_bom":

$kode = $_POST['kode_barang'];

// 🔥 ambil BOM header dulu
$qb = $db->query("
    SELECT id 
    FROM bom 
    WHERE kodebj = '$kode'
")->fetch();

$data = [];

if($qb){

    // 🔥 ambil detail bahan baku
   $q = $db->query("
    SELECT 
        bd.kodebb as kode,
        b.nm_barang as nama,
        b.satuan,
        bd.jumlah as qty
    FROM bom_detail bd
    LEFT JOIN barang b ON b.kd_barang = bd.kodebb
    WHERE bd.id_bom = '".$qb->id."'
");

    foreach ($q as $k){

        // 🔥 ambil stock dari ledger
        $qs = $db->query("
            SELECT IFNULL(SUM(qty),0) as stock
            FROM detail_transaksi
            WHERE kd_barang = '".$k->kode."'
            AND posisi = 'PRODUKSI'
        ")->fetch();

        $stock = $qs ? $qs->stock : 0;
        //$stock = $qs ? number_format($qs->stock, 5, '.', '') : '0.00000';

        $data[] = [
            "kode"   => $k->kode,
            "nama"   => $k->nama,
            "satuan" => $k->satuan,
            "qty"    => $k->qty,
            "stock"  => number_format($stock, 5, '.', '')
        ];
    }
}

echo json_encode($data);

break;
  
  case "detail_bahan_baku":
   echo "<table style='width:100%' border='1'>
                              <thead>
                               <tr>
                                <th class='text-center'>No</th>
                                <th class='text-center'>Kode / Nama Barang</th>
                                <th class='text-center'>No Dokpab</th>
                                <th class='text-center'>No Aju</th>
                                <th class='text-center'>Jenis Dokpab</th>
                                <th class='text-center'>Tgl Dokpab</th>
                                <th class='text-center'>Qty</th>
                               </tr>
                              </thead>
                             ";
                             $no2=1;
                             $qq = $db->query("select * from v_detail_bahan_baku_produksi where id_produksi_detail='".$_POST['id_produksi_detail']."' ");
                             foreach ($qq as $kk) {
                                echo "<tr>
                                  <td style='padding:3px'>$no2</td>
                                  <td style='padding:3px'>$kk->kode /$kk->nm_barang</td>
                                  <td style='padding:3px'>$kk->no_dokpab</td>
                                  <td style='padding:3px'>$kk->no_aju</td>
                                  <td style='padding:3px'>$kk->jenis_dokpab</td>
                                  <td style='padding:3px'>$kk->tgl_dokpab</td>
                                  <td style='padding:3px'>$kk->jumlah</td>
                                </tr>";
                                $no2++; 
                             }
                             echo"</table>";
  break;
case "in":
lp_traceability_locked_response();

$data = array(
    "nomor"    => $_POST["nomor"],
    "userid"   => $_SESSION['username'],
    "tgl_bpb"  => $_POST["tgl_bpb"],
    "no_sales_order"  => $_POST["no_sales_order"],
    "project"  => $_POST["project"],
    "dept"     => json_encode($_POST["dept"]),
  //  "jenis_produksi" => $_POST["jenis_produksi"],
    "name_ppc" => $_POST["name_ppc"],
    "catatan"  => $_POST["catatan"],
);

$db->insert("brgjadi",$data);
$id = $db->last_insert_id();

$no_bpb = GetNoLpbProduksi($id);

$db->query("UPDATE brgjadi SET no_bpb='$no_bpb' WHERE id_produksi='$id'");

// ================= HASIL PRODUKSI =================
$kode_jadi = $_POST['kode_jadi'];
$qty_jadi  = $_POST['qty_jadi']; 
$qty_ng    = $_POST['qty_ng'];

$no = 1;
$id_detail_list = [];
$total_qty_jadi = 0;

foreach ($kode_jadi as $i => $kode){

    $qty_ok = (float)$qty_jadi[$i];
    $ng     = (float)$qty_ng[$i];

    if ($kode == '' || ($qty_ok <= 0 && $ng <= 0)) {
        continue;
    } 

    $barang = $db->query("
        SELECT satuan
        FROM barang
        WHERE kd_barang = '$kode'
    ")->fetch();

    $uom = $barang ? $barang->satuan : '';

    // =========================
    // HASIL OK
    // =========================
    if($qty_ok > 0){

        $db->insert("detail_transaksi", [

            'kd_barang'     => $kode,
            'qty'           => $qty_ok,
            'direction'     => 'IN',

            'move_code'     => '101',
            'posisi'        => 'PRODUKSI',

            'document_date' => $_POST["tgl_bpb"],
            'posting_date'  => $_POST["tgl_bpb"],
            
            'no_ref'        => $no_bpb,
            'no_bpb'        => $no_bpb,

            'ref_type'      => 'PRODUKSI',
            'ref_id'        => $id,

            'uom'           => $uom,

            'remark'        => 'Hasil Produksi OK',

            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // DETAIL PRODUKSI
        $db->insert("brgjadi_detail", [

            'id_produksi' => $id,
            'no_bpb'      => $no_bpb,
            'tgl_bpb'     => $_POST["tgl_bpb"],

            'kode'        => $kode, 
            'lot_no'        => $_POST["lot_no"][$i],
            'jumlah'      => $qty_ok,
            'qty_ng'      => $ng,
          //  'lot_no'      => 

            'row_no'      => $no++
        ]);

        $id_detail = $db->last_insert_id();

        $id_detail_list[] = $id_detail;

        // STOCK LAYER OK
        $db->insert("stock_layer", [

            'kode'       => $kode,

            'qty_masuk'  => $qty_ok,
            'qty_sisa'   => $qty_ok,

            'lokasi'     => 'PRODUKSI',

            'ref_table'  => 'brgjadi_detail',
            'ref_id'     => $id_detail,

            'tgl_masuk'  => $_POST["tgl_bpb"],

            'no_bpb'     => $no_bpb,

            'created_at' => date("Y-m-d H:i:s")
        ]);
    }

    // =========================
    // HASIL NG
    // =========================
    if($ng > 0){

        $db->insert("detail_transaksi", [ 

            'kd_barang'     => $kode,
            'qty'           => $ng,
            'direction'     => 'IN',

            'move_code'     => '102',
            'posisi'        => 'NG',

            'document_date' => $_POST["tgl_bpb"],
            'posting_date'  => $_POST["tgl_bpb"],

            'no_ref'        => $no_bpb,
            'no_bpb'        => $no_bpb,

            'ref_type'      => 'PRODUKSI_NG',
            'ref_id'        => $id,

            'uom'           => $uom,

            'remark'        => 'Hasil Produksi NG',

            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // STOCK LAYER NG
        $db->insert("stock_layer", [

            'kode'       => $kode,

            'qty_masuk'  => $ng,
            'qty_sisa'   => $ng,

            'lokasi'     => 'NG',

            'ref_table'  => 'brgjadi_detail',
            'ref_id'     => $id,

            'tgl_masuk'  => $_POST["tgl_bpb"],

            'no_bpb'     => $no_bpb,

            'created_at' => date("Y-m-d H:i:s")
        ]);
    }
}


// ================= BAHAN BAKU =================
foreach ($_POST['kode_input'] as $key => $kode_barang) {

    if ($kode_barang == '') continue;

    $qty_input = (float)$_POST["qty"][$key];

    // 🔥 VALIDASI STOCK
    $cek = $db->query("
        SELECT SUM(qty_sisa) as sisa
        FROM stock_layer
        WHERE kode = '".$kode_barang."'
        AND lokasi = 'PRODUKSI'
    ")->fetch();

    if ($cek->sisa < $qty_input) {
        die(json_encode([
            "status"=>"error",
            "error_message"=>"Stock bahan ".$kode_barang." tidak cukup"
        ]));
    }

    // ================= FIFO =================
    $sisa = $qty_input;

    $q_layer = $db->query("
        SELECT *
        FROM stock_layer
        WHERE kode = '".$kode_barang."'
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

        // 🔥 ledger bahan baku (EXBC)
        $db->insert("detail_transaksi", [
            'kd_barang'     => $kode_barang,
            'qty'           => ($pakai * -1),
            'posisi'        => 'PRODUKSI',
            'move_code'     => '261',
            'no_ref'        => $no_bpb,
            'no_aju'        => $layer->no_aju,
            'no_dokpab'     => $layer->no_dokpab,
            'remark'        => 'Pemakaian Bahan Baku (FIFO)',
            'document_date'  => $_POST["tgl_bpb"], 
            'posting_date'  => $_POST["tgl_bpb"],
            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // 🔥 bahanbaku_detail (EXBC split)
        foreach ($id_detail_list as $id_detail){
            $db->insert("bahanbaku_detail", [
                'id_produksi_detail' => $id_detail,
                'kode'               => $kode_barang,
                'jumlah'             => $pakai,
                'no_aju'             => $layer->no_aju,
                'no_dokpab'          => $layer->no_dokpab
            ]);
        }

        // 🔥 update stock_layer bahan
        $db->query("
            UPDATE stock_layer
            SET qty_sisa = qty_sisa - $pakai
            WHERE id = '".$layer->id."'
        ");
    }
}

action_response($db->getErrorMessage());
break;



  // case "in":

  // $data = array(
  //     "nomor"    => $_POST["nomor"],
  //     "userid"   => $_SESSION['username'],
  //     "tgl_bpb"  => $_POST["tgl_bpb"],
  //     "project"  => $_POST["project"],
  //     "dept"     => $_POST["dept"],
  //     "jenis_produksi" => $_POST["jenis_produksi"],
  //     "name_ppc" => $_POST["name_ppc"],
  //     "catatan"  => $_POST["catatan"],
  // );
  // $db->insert("brgjadi",$data);
  // $id          = $db->last_insert_id();
  // $no_bpb      = GetNoLpbProduksi($id);
  // $db->query("update brgjadi set no_bpb='$no_bpb' where id_produksi='$id' ");
  // $no          = 1;
  // foreach ($_POST['kode'] as $key => $value) {
  //   if ($_POST['kode_input'][$key]!='') {
  //       $barang      = att_barang($_POST['kode_input'][$key]);
  //       $data_detail = array(
  //                   'id_produksi'   => $id , 
  //                   'no_bpb'        => $no_bpb,
  //                   'tgl_bpb'       => $_POST["tgl_bpb"],
  //                   'kode'          => $_POST['kode_input'][$key],
  //                   'jumlah'        => $_POST['qty'][$key],
  //                   'row_no'        => $no 
  //                 );
  //       $data_transfer = array(
  //                       'no_transfer'  => $no_bpb, 
  //                       'dari'         => '5',
  //                       'ke'         => '3',
  //                       'id_produksi' => $id,
  //                       'tgl_transfer' => $_POST["tgl_bpb"],
  //                       'user'         => $_SESSION['username'],
  //                       'is_produksi'  => '1',
  //                       'date_created' => date("Y-m-d H:i:s")
  //                     ); 
  //       $db->insert("transfer",$data_transfer);
  //       $id_transfer = $db->last_insert_id();
  //       $db->insert("brgjadi_detail",$data_detail); 
  //       $id_produksi_detail = $db->last_insert_id();
  //       ambil_bahan_baku($_POST['kode_input'][$key],$_POST['qty'][$key],$id_produksi_detail,$id_transfer); 
  //      // update_stock($_POST['qty'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);     
  //       $no++;

  //   }   
  // }
  
  // //echo "string"; 
  
   
  // //  $in = $db->insert("brgjadi",$data);
    
    
  //   action_response($db->getErrorMessage());
  //   break;
  case "delete":
    
    $id = $_GET['id'];
    $q = $db->query("select d.kode,d.jumlah,b.id as id_barang from brgjadi_detail d join barang b on b.kd_barang=d.kode where d.id_produksi='$id'  ");
    foreach ($q as $k) {
      update_stock($k->jumlah,'minus',"brg_jadi",'3',$k->id_barang,$_SESSION['username']); 
    }
    
    $db->delete("brgjadi","id_produksi",$_GET["id"]);
    action_response($db->getErrorMessage()); 
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("brgjadi","no_bpb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_bpb" => $_POST["no_bpb"],
      "tgl_bpb" => $_POST["tgl_bpb"],
      "project" => $_POST["project"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("brgjadi",$data,"no_bpb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
