<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php"; 
session_check_json(); 
switch ($_GET["act"]) {

 case "reversal":

$no_spb = $_POST['no_spb'];
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
    AND posisi = 'GUDANG'
");

foreach ($q as $k){

    $qty = abs($k->qty);

    // ================= LEDGER REVERSAL =================
    $db->insert("detail_transaksi", [
        'kd_barang'     => $k->kd_barang,
        'qty'           => $qty,
        'posisi'        => 'GUDANG',
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
        'posisi'        => 'PRODUKSI',
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
      $no=1;
   // $dat = array();
    foreach ($Reader as $r)  
    {
      if ($i!=0) {
       // print_r($r); 
        $satuan = "";
        $nm_barang = "";
        $stock = 0;
        $id_barang = "";

        $qb = $db->query("select nm_barang,satuan,id from barang where kd_barang='".$r[0]."'  ");
        foreach ($qb as $kb) {
          $satuan = $kb->satuan; 
          $nm_barang  = $kb->nm_barang;
          $id_barang = $kb->id;
        }
          $qs = $db->query("select ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where (b.kd_barang='".$r[0]."' or s.id_barang='$id_barang') and id_bagian='1' ");
          foreach ($qs as $ks) {
            $stock = $ks->stock;
          }
          $barang_tidak_ada = "";
          $qty              = $r['2']; 
          if ($id_barang=='') {
             $barang_tidak_ada = "<i class='label label-danger'>Tidak ada di master barang</i>";
             $qty = NULL; 
          } 
        ?>
        <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" value="<?= $r[0]." ".$nm_barang ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" value="<?= $r[0] ?>" id="kode_input_<?= $no ?>"> 
                      <input type="hidden" name="id_input[]" id="id_input_<?= $no ?>" value="<?= $id_barang ?>" > 
                      <?php
                      if ($id_barang=='') {
                        echo $barang_tidak_ada;
                      }
                      ?>
                     </td>  
                    <!--  <td>
                       <select id="jenis_dokpab_1" class="form-control" name="jenis_dokpab[]">
                        <option value="">Jenis Dokpab</option>
                       // <?php
                          //$qj = $db->query("select jenis from jenisbcmasuk");
                          //foreach ($qj as $kj) { 
                           //  echo "<option value='$kj->jenis'>$kj->jenis</option>";
                         // }
                      // ?> 
                       </select>
                     </td> -->
                     <td><input type="text" id="form_unit_<?= $no ?>" value="<?= $satuan ?>" class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="text" id="form_qty_ro_<?= $no ?>" class="form-control" name="qty_ro[]" readonly="" ></td> 
                     <td><input type="text" id="form_stock_<?= $no ?>" class="form-control" value="<?= $stock ?>" name="stock[]" readonly="" ></td> 
                     <td><input type="text" id="form_qty_<?= $no ?>" value="<?= $qty ?>" class="form-control" name="qty[]" onkeyup="cek_stok('<?= $no ?>',this.value)" required="">
                     <i id="error_stock_<?= $no ?>" style="color: red"></i> </td>
                     <td><input type="text" id="form_ket_<?= $no ?>" class="form-control"  value="<?= $r['3'] ?>" name="ket[]" ></td>
                   </tr>
        <?php
      }
     
      // if ($i==0) {
      //   foreach ($r as $key => $value) {
      //      $kol[$key] = $value;
      //   }
      // }else{
    //     foreach ($r as $k => $v) {
        
    //     print_r($v);
      
    //   $i++;
    // }
    //$isi = implode(",", $datax);
      $i++;
      $no++;
    }
  }
    
  }
 // echo json_encode($res);
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
        <th>Jenis Dokpab</th>
        <th>No Dokpab</th>
        <th>No Aju</th>
      
        <th>Qty</th>
        <th>Satuan</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $q = $db->query("
SELECT 
    d.no,
    dt.no_dokpab,
    dt.no_aju,
    b.nm_barang,
    t.no_transfer as no_spb,
    t.tgl_transfer as tgl_spb,
    b.kd_barang as kode,

    MAX(p.jenis_dokpab) as jenis_dokpab,
    IFNULL(MAX(rd.jumlah),0) as qtyro,

    abs(dt.qty) as jumlah, -- 🔥 FIX DI SINI

    b.satuan,
    t.ket

FROM transfer t

JOIN transfer_detail d 
    ON d.id_transfer = t.id_transfer

JOIN barang b 
    ON b.id = d.id_barang

JOIN detail_transaksi dt 
    ON  dt.no_ref = t.no_transfer
    AND dt.kd_barang = b.kd_barang
    -- and d.no = dt.no_urut
    AND dt.posisi = 'GUDANG'
    AND dt.is_reversal='0'

LEFT JOIN pemasukan p 
    ON p.no_aju = dt.no_aju

LEFT JOIN ro 
    ON ro.no_ro = t.no_ro

LEFT JOIN ro_detail rd 
    ON rd.no_ro = ro.no_ro 
    AND rd.kode = b.kd_barang

WHERE t.no_transfer = '$no_spb'

GROUP BY 
    d.no,
    b.kd_barang,
    dt.no_aju,
    dt.no_dokpab

ORDER BY d.no, dt.no_aju
");
    $no = 0;
    foreach ($q as $k) {     
      if ($no!=$k->no) {
         $no = $k->no;
          echo "<tr>
             <td>$k->no</td>
           
             <td>$k->kode , $k->nm_barang</td>
             <td>$k->jenis_dokpab</td>
             <td>$k->no_dokpab</td>
             <td>$k->no_aju</td>
            
             <td>".number_format($k->jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
      }else{
         echo "<tr>
             <td></td>
           
             <td></td>
             <td>$k->jenis_dokpab</td>
             <td>$k->no_dokpab</td>
             <td>$k->no_aju</td>
            
             <td>".number_format($k->jumlah,2)."</td>
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

  case "get_stock":
   $kode = $_POST['kode'];
   $jumlah = $_POST['jumlah'];
   $res= array();
   $q = $db->query("select sum(stock) as stock from stock_barang where kd_barang='$kode' and posisi='incoming' ");
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
  

 case "get_tgl_ro":
  $no_ro = $_POST['no_ro']; 
  $q = $db->query("select tgl_ro from ro where no_ro='$no_ro' ");
  foreach ($q as $k) {
    echo "$k->tgl_ro";
  }
   break;

 case "get_detail_ro":
$no_ro = $_POST['no_ro'];
?>
<div class="form-group" id="form_ro">
  <div class="col-lg-12">
    <table class="table">
      <thead>
        <tr>
          <th style="width:50px;text-align: center">
            <a style="cursor: pointer;" onclick="add_baris()"><i class="fa fa-plus"></i></a>
          </th>
          <th style="width: 400px">Kode Barang</th>
          <th style="width: 100px">Unit</th>
          <th>Qty RO</th>
          <th>Stock</th>
          <th>Qty</th>                     
          <th>Ket</th>
        </tr>
      </thead>
      <tbody id="isi_tabel">
      <?php
      $no = 1;
      $q = $db->query("
          SELECT r.*, b.nm_barang, b.satuan, b.id as id_barang 
          FROM ro_detail r 
          JOIN barang b ON b.kd_barang = r.kode 
          WHERE r.no_ro = '$no_ro'
      ");

      foreach ($q as $k) {

        // 🔥 STOCK = SUM(qty) (sudah minus/plus)
        $qc = $db->query("
            SELECT IFNULL(SUM(qty),0) as stock
            FROM detail_transaksi 
            WHERE kd_barang = '$k->kode' 
              AND posisi = 'GUDANG'
        ");

        $stock = 0;
        foreach ($qc as $kc) {
          $stock = $kc->stock;
        }

        $jml_ro = $k->jumlah;
        $error_stock = "";

        // 🔴 VALIDASI
        if ($stock <= 0) {
            $jml_ro = "";
            $error_stock = "Stock Kosong";
        } elseif ($k->jumlah > $stock) {
            $error_stock = "Stock tidak cukup (Tersedia: $stock)";
            $jml_ro = $stock;
        }
      ?>
      <tr id="baris_<?= $no ?>">
        <td style="text-align: center">
          <a onclick="hapus_baris('<?= $no ?>')">
            <i class="fa fa-trash-o" style="font-size: 25px;"></i>
          </a>
        </td>

        <td>
          <input type="text" 
            value="<?= $k->kode." ".$k->nm_barang ?>" 
            id="form_kode_<?= $no ?>" 
            class="form-control" 
            name="kode[]">

          <input type="hidden" value="<?= $k->kode ?>" name="kode_input[]" id="kode_input_<?= $no ?>"> 
          <input type="hidden" value="<?= $k->id_barang ?>" name="id_input[]" id="id_input_<?= $no ?>"> 
        </td>  

        <td>
          <input type="text" value="<?= $k->satuan ?>" 
            class="form-control" readonly>
        </td> 

        <td>
          <input type="text" value="<?= $k->jumlah ?>" 
            class="form-control" readonly>
        </td> 

        <td>
          <input type="text" id="form_stock_<?= $no ?>" 
            value="<?= $stock ?>" 
            class="form-control" readonly>
        </td> 

        <td>
          <input type="number" 
            value="<?= $jml_ro ?>" 
            id="form_qty_<?= $no ?>" 
            class="form-control" 
            name="qty[]" 
            onkeyup="cek_stok('<?= $no ?>',this.value)" required>

          <i id="error_stock_<?= $no ?>" style="color:red">
            <?= $error_stock ?>
          </i>
        </td>

        <td>
          <input type="text" class="form-control" name="ket[]">
        </td>
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
  // break;

 case "in": 

$dari = '1';
$ke   = '3';

if ($ke == '2') {
    $tujuan = "Pra Produksi";
} elseif ($ke == '3') {
    $tujuan = "Produksi";
} else {
    $tujuan = "Outgoing";
}

// ================= HEADER =================
$data = array(  
    "tgl_transfer"  => $_POST["tgl_spb"],
    "no_ro"         => $_POST["no_request"],
    "tgl_ro"        => $_POST["tgl_request"],
    "user"          => $_SESSION['username'],
    "ket"           => $_POST["catatan"],
    "kd_dept"       => $_POST["dept"],
    "status"        => "0",
    "dari"          => $dari,
    "ke"            => $ke,
    "date_created"  => date("Y-m-d H:i:s")
);

$db->insert("transfer",$data);
$id          = $db->last_insert_id(); 
$no_transfer = GetNoTransfer($id,5);

$db->query("UPDATE transfer 
            SET no_transfer='$no_transfer' 
            WHERE id_transfer='$id'");

// 🔥 INIT NO_URUT
$no_urut = 1;

$log_transfer = "Transfer dari Incoming tujuan $tujuan dengan No Transfer $no_transfer";

$no = 1;

// ================= DETAIL =================
foreach ($_POST['kode_input'] as $key => $value) {    

    $kode_barang = $_POST['kode_input'][$key];
    $jumlah      = $_POST['qty'][$key];

    // 🔥 VALIDASI STOCK
    $cek = $db->query("
        SELECT SUM(qty_sisa) as sisa
        FROM stock_layer
        WHERE kode = '".$kode_barang."'
    ")->fetch();

    if ($cek->sisa < $jumlah) {
        die(json_encode([
            "status"=>"error",
            "error_message"=>"Stock tidak cukup untuk barang $kode_barang"
        ]));
    }

    $q_layer = $db->query("
        SELECT *
        FROM stock_layer
        WHERE kode = '".$kode_barang."'
        AND qty_sisa > 0
        ORDER BY tgl_masuk ASC, id ASC 
    ");

    foreach ($q_layer as $layer){ 

        if ($jumlah <= 0) break;

        if ($jumlah > $layer->qty_sisa) {
            $jml_terpakai = $layer->qty_sisa;
        } else {
            $jml_terpakai = $jumlah;
        }

        $jumlah -= $jml_terpakai;

        // ================= TRANSFER DETAIL =================
        $db->insert("transfer_detail",[
            'id_transfer'  => $id,
            'id_barang'    => $_POST['id_input'][$key],
            'no'           => $no,
            'jml'          => $jml_terpakai,
            'date_created' => date("Y-m-d H:i:s")
        ]); 

        // ================= LEDGER =================
        $db->insert("detail_transaksi", [
            'kd_barang'     => $kode_barang,
            'qty'           => ($jml_terpakai * -1),
            'posisi'        => 'GUDANG',
            'move_code'     => '102',
            'no_ref'        => $no_transfer,
            'no_urut'       => $no_urut++, // 🔥 TAMBAHAN
            'no_dokpab'     => $layer->no_dokpab,
            'no_aju'        => $layer->no_aju,
            'remark'        => 'Transfer ke '.$tujuan,
            'posting_date'  => $_POST["tgl_spb"],
            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // ================= UPDATE LAYER =================
        $db->query("
            UPDATE stock_layer
            SET qty_sisa = qty_sisa - $jml_terpakai
            WHERE id = '".$layer->id."'
        ");
    }

    $no++;
}

// ================= LOG =================
simpan_log($log_transfer,$_SESSION['username']);
action_response($db->getErrorMessage());

break;

  case "delete":
    $q = $db->query("select id_barang,jml,p.jenis_dokpab,tr.dari,tr.ke,tr.no_transfer,b.nm_bagian as dari,bb.nm_bagian as tujuan from transfer_detail t join pemasukan_detail d 
    on t.id_incoming_detail=d.id
    join pemasukan p on p.no_bpb=d.no_bpb
join transfer tr on tr.id_transfer=t.id_transfer
join bagian b on b.id_bagian=tr.dari
join bagian bb on bb.id_bagian=tr.ke where t.id_transfer='".$_GET["id"]."' ");
    $no_transfer = "";
    $dari = "";
    $tujuan = "";
    foreach ($q as $k) {
       $no_transfer = $k->no_transfer; 
       $dari = $k->dari;
       $tujuan = $k->tujuan;
       update_stock($k->jml,'plus',$k->jenis_dokpab,$k->dari,$k->id_barang,$_SESSION['username']);
       update_stock($k->jml,'minus',$k->jenis_dokpab,$k->ke,$k->id_barang,$_SESSION['username']);   
    }  
     simpan_log("Delete Data transfer dari $dari tujuan $tujuan dengan No Transfer $no_transfer",$_SESSION['username']);
    $db->delete("transfer","id_transfer",$_GET["id"]);
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
     // "nomor" => $_POST["nomor"],
      "no_transfer" => $_POST["no_spb"],
      "tgl_transfer" => $_POST["tgl_spb"],
      "no_ro" => $_POST["no_request"],
   //   "tgl_request" => $_POST["tgl_request"],
      "kd_dept" => $_POST["dept"],
      "user"=>$_POST["name_ppc"], 
   );

    
    $up = $db->update("transfer",$data,"id_transfer",$_POST["id"]);
    $q = $db->query("select id_barang,jml,p.jenis_dokpab,tr.dari,tr.ke,tr.no_transfer,b.nm_bagian as dari,bb.nm_bagian as tujuan from transfer_detail t join pemasukan_detail d 
    on t.id_incoming_detail=d.id
    join pemasukan p on p.no_bpb=d.no_bpb
join transfer tr on tr.id_transfer=t.id_transfer
join bagian b on b.id_bagian=tr.dari
join bagian bb on bb.id_bagian=tr.ke where t.id_transfer='".$_POST["id"]."'      ");
    $i=0;
    foreach ($q as $k) {
       $jml_lama = $k->jml;
       $jml_baru = $_POST['qty'][$i];
       // echo "$jml_lama ".$_POST['qty'][$i]." == ";
       if ($jml_baru>$jml_lama) {
         $stock = $jml_baru - $jml_lama;
         $ket = "plus";
       }else{
          $stock = $jml_lama - $jml_baru;
          $ket = "minus";
       } 

       update_stock($stock,$ket,$k->jenis_dokpab,$k->dari,$k->id_barang,$_SESSION['username']);
       update_stock($stock,$ket,$k->jenis_dokpab,$k->ke,$k->id_barang,$_SESSION['username']);
       $i++;
    }
   // echo "string";
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>