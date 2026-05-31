<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php"; 
session_check_json();
switch ($_GET["act"]) {
 
case "get_sales_order_detail":

$no_so = $_POST['no_sales_order'];

$q = $db->query("
    SELECT 
        so.kode_penerima,
        so.consignee,

        d.kd_barang,
        d.qty,
        b.nm_barang,
        b.satuan

    FROM sales_order so
    JOIN sales_order_detail d 
        ON so.id_sales_order = d.id_sales_order
    LEFT JOIN barang b 
        ON b.kd_barang = d.kd_barang
    WHERE so.no_sales_order = '$no_so'
");

$data = [];
$penerima = null;

foreach ($q as $k){

    $penerima = $k->kode_penerima;

    // 🔥 HITUNG STOCK GUDANG
    $stok = $db->query("
        SELECT IFNULL(SUM(qty_sisa),0) as stock
        FROM stock_layer
        WHERE kode = '".$k->kd_barang."'
        AND lokasi = 'GUDANG'
    ")->fetch(); 

    $data[] = [
        "kd_barang"     => $k->kd_barang, 
        "nm_barang"     => $k->nm_barang,
        "qty"           => $k->qty,
        "satuan"        => $k->satuan,
        "stock_gudang"  => (float)$stok->stock // 🔥 tambahan
    ];
}

echo json_encode([
    "detail"    => $data,
    "penerima"  => $penerima
]);

break;

  case "show_detail":
  $id = $_POST['id'];
  $qp = $db->query("select ifnull(flag,0) as flag from pengeluaran where id='$id' ");
  foreach ($qp as $kp) {
    $flag = $kp->flag;
  }
  ?>
  <div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
    <li class="active"><a href="#barang_jadi" data-toggle="tab" aria-expanded="true">Barang Jadi</a></li>
    <li><a href="#bahan_baku" data-toggle="tab" aria-expanded="false">Bahan Baku</a></li>
    <li><a href="#barang_set_jadi" data-toggle="tab" aria-expanded="false">Barang set/jadi</a></li>
    <li><a href="#barang_modal" data-toggle="tab" aria-expanded="false">Barang Modal/Perkantoran</a></li>
    <li class="pull-right"><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane active" id="barang_jadi">
         <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>No SJ</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Unit</th>
                <th>Valuta</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Nilai</th>
           <!--      <th>Berat</th>
                <th>Lokasi</th> -->
              </tr>
            </thead>
            <tbody>
          <?php
          $qty=0;
          $harga =0;
          $nilai=0;
          $berat =0;
          $q = $db->query("select b.valuta, d.*,ba.nm_barang from pengeluaran_detail d 
            join pengeluaran b on b.no_sj=d.no_sj
            join barang ba on ba.kd_barang=d.kode where b.id='$id' and ba.kd_kategori='K02' ");


          foreach ($q as $k) { 
            echo "<tr>
                    <td>$k->row_no</td>
                    <td>$k->no_sj</td>
                    <td>$k->tgl_sj</td>
                    <td>$k->kode , $k->nm_barang</td>
                    <td>$k->unit</td>
                    <td>$k->valuta</td>
                    <td style='text-align: right'>".number_format($k->jumlah,4)."</td>
                    <td style='text-align: right'>".number_format($k->harga,4)."</td>
                    <td style='text-align: right'>".number_format($k->nilai,4)."</td> 
                   
                  </tr>";
                  $qty = $qty +$k->jumlah; 
                  $harga = $harga + $k->harga; 
                  $nilai = $nilai + $k->nilai;
                
          }
          ?>
            <tr>
              <td colspan="6" style="text-align: center">Jumlah</td>
              <td style="text-align: right"><?= number_format($qty,4) ?></td>
              <td style="text-align: right"><?= number_format($harga,4)  ?></td>
              <td style="text-align: right"><?= number_format($nilai,4)  ?></td>
           
            </tr>
              </tbody>
          </table>
      </div>


     <div class="tab-pane" id="bahan_baku">

<table class="table">
<thead>
<tr>
    <th>No</th>
    <th>Kode</th>
    <th>Nama Barang</th>
    <th>Jenis Dokpab</th>
    <th>No Dokpab</th>
    <th>Tanggal Dokpab</th>
    <th>No Aju</th>
    <th>Tanggal Aju</th>
    <th>Qty</th>
    <th>Satuan</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
$total = 0;

$q = $db->query("
SELECT 
    d.id,
    d.kode as kode_barang,
    ba.kd_kategori,

    -- 🔥 qty
    d.jumlah as qty_keluar,
    bt.jumlah as qty_produksi,

    -- 🔥 bahan baku
    bb.kd_barang as kd_bahan,
    bb.nm_barang as nm_bahan,
    bh.jumlah as qty_bahan,
    bh.no_aju,
    bh.no_dokpab,

    ps.jenis_dokpab,
    ps.tgl_dokpab,
    ps.tgl_aju,

    bbb.satuan

FROM pengeluaran_detail d
JOIN pengeluaran b ON b.no_sj = d.no_sj
JOIN barang ba ON ba.kd_barang = d.kode

LEFT JOIN pengeluaran_detail_brg_jadi bj 
    ON bj.id_pengeluaran_detail = d.id

-- 🔥 FIX JOIN PRODUKSI
LEFT JOIN brgjadi_detail bt 
    ON bt.no_bpb = bj.no_bpb
    AND bt.kode = d.kode

LEFT JOIN bahanbaku_detail bh 
    ON bh.id_produksi_detail = bt.id_produksi_detail

LEFT JOIN barang bb 
    ON bb.kd_barang = bh.kode

LEFT JOIN pemasukan ps 
    ON ps.no_aju = bh.no_aju

LEFT JOIN barang bbb 
    ON bbb.kd_barang = bh.kode

WHERE b.id = '$id'
");

foreach ($q as $k){

    // ================= FG =================
    if (in_array($k->kd_kategori, ['K02','K07'])){

        if(empty($k->kd_bahan)) continue;

        // 🔥 HITUNG RATIO
        $qty_pakai = 0;
        if ($k->qty_produksi > 0) {
            $qty_pakai = $k->qty_bahan * ($k->qty_keluar / $k->qty_produksi);
        }

        $qty_pakai = round($qty_pakai, 4);

        echo "<tr>
            <td>$no</td>
            <td>$k->kd_bahan</td>
            <td>$k->nm_bahan</td>
            <td>".($k->jenis_dokpab ?? '-')."</td>
            <td>".($k->no_dokpab ?? '-')."</td>
            <td>".($k->tgl_dokpab ?? '-')."</td>
            <td>".($k->no_aju ?? '-')."</td>
            <td>".($k->tgl_aju ?? '-')."</td>
            <td style='text-align:right'>".number_format($qty_pakai,4)."</td>
            <td>$k->satuan</td>
        </tr>";

        $total += $qty_pakai;
        $no++;

    } else {

        // ================= NON FG =================
        $q2 = $db->query("
            SELECT 
                d.kode,
                bb.nm_barang,
                d.jumlah,
                ps.jenis_dokpab,
                ps.no_aju,
                ps.no_dokpab,
                ps.tgl_aju,
                ps.tgl_dokpab,
                bb.satuan
            FROM pengeluaran_detail d
            LEFT JOIN barang bb ON bb.kd_barang = d.kode
            LEFT JOIN pengeluaran_detail_brg_jadi bj 
                ON bj.id_pengeluaran_detail = d.id
            LEFT JOIN pemasukan ps 
                ON ps.no_aju = bj.no_aju
            WHERE d.id = '".$k->id."'
        ");

        foreach ($q2 as $x){

            echo "<tr>
                <td>$no</td>
                <td>$x->kode</td>
                <td>$x->nm_barang</td>
                <td>".($x->jenis_dokpab ?? '-')."</td>
                <td>".($x->no_dokpab ?? '-')."</td>
                <td>".($x->tgl_dokpab ?? '-')."</td>
                <td>".($x->no_aju ?? '-')."</td>
                <td>".($x->tgl_aju ?? '-')."</td>
                <td style='text-align:right'>".number_format($x->jumlah,4)."</td>
                <td>$x->satuan</td>
            </tr>";

            $total += $x->jumlah;
            $no++;
        }
    }
}
?>

<tr>
    <td colspan="8" style="text-align:center"><b>Total</b></td>
    <td style="text-align:right"><b><?= number_format($total,4) ?></b></td>
    <td></td>
</tr>

</tbody>
</table>

</div>
    


      <div class="tab-pane" id="barang_set_jadi">
        <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>No SJ</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Unit</th>
                <th>Valuta</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Nilai</th>
           <!--      <th>Berat</th>
                <th>Lokasi</th> -->
              </tr>
            </thead>
            <tbody>
          <?php
          $qty=0;
          $harga =0;
          $nilai=0;
          $berat =0;
          $q = $db->query("select b.valuta, d.*,ba.nm_barang from pengeluaran_detail d 
            join pengeluaran b on b.no_sj=d.no_sj
            join barang ba on ba.kd_barang=d.kode where b.id='$id' and ba.kd_kategori='K07' ");


          foreach ($q as $k) { 
            echo "<tr>
                    <td>$k->row_no</td>
                    <td>$k->no_sj</td>
                    <td>$k->tgl_sj</td>
                    <td>$k->kode , $k->nm_barang</td>
                    <td>$k->unit</td>
                    <td>$k->valuta</td>
                    <td style='text-align: right'>".number_format($k->jumlah,4)."</td>
                    <td style='text-align: right'>".number_format($k->harga,4)."</td>
                    <td style='text-align: right'>".number_format($k->nilai,4)."</td> 
                   
                  </tr>";
                  $qty = $qty +$k->jumlah; 
                  $harga = $harga + $k->harga; 
                  $nilai = $nilai + $k->nilai;
                
          }
          ?>
            <tr>
              <td colspan="6" style="text-align: center">Jumlah</td>
              <td style="text-align: right"><?= number_format($qty,4) ?></td>
              <td style="text-align: right"><?= number_format($harga,4)  ?></td>
              <td style="text-align: right"><?= number_format($nilai,4)  ?></td>
           
            </tr>
              </tbody>
          </table>
      </div>
       <div class="tab-pane" id="barang_modal">
        <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>No SJ</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Unit</th>
                <th>Valuta</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Nilai</th>
           <!--      <th>Berat</th>
                <th>Lokasi</th> -->
              </tr>
            </thead>
            <tbody>
          <?php
          $qty=0;
          $harga =0;
          $nilai=0;
          $berat =0;
          $q = $db->query("select b.valuta, d.*,ba.nm_barang from pengeluaran_detail d 
            join pengeluaran b on b.no_sj=d.no_sj
            join barang ba on ba.kd_barang=d.kode where b.id='$id' and ba.kd_kategori='K03' ");


          foreach ($q as $k) { 
            echo "<tr>
                    <td>$k->row_no</td>
                    <td>$k->no_sj</td>
                    <td>$k->tgl_sj</td>
                    <td>$k->kode , $k->nm_barang</td>
                    <td>$k->unit</td>
                    <td>$k->valuta</td>
                    <td style='text-align: right'>".number_format($k->jumlah,4)."</td>
                    <td style='text-align: right'>".number_format($k->harga,4)."</td>
                    <td style='text-align: right'>".number_format($k->nilai,4)."</td> 
                   
                  </tr>";
                  $qty = $qty +$k->jumlah; 
                  $harga = $harga + $k->harga; 
                  $nilai = $nilai + $k->nilai;
                
          }
          ?>
            <tr>
              <td colspan="6" style="text-align: center">Jumlah</td>
              <td style="text-align: right"><?= number_format($qty,4) ?></td>
              <td style="text-align: right"><?= number_format($harga,4)  ?></td>
              <td style="text-align: right"><?= number_format($nilai,4)  ?></td>
           
            </tr>
              </tbody>
          </table>
      </div>
    </div>

 </div>
 
  <?php
    break;

   case "upload_excel":
  // error_reporting(E_ALL);
   // print_r($_FILES);
   // die();
   move_uploaded_file($_FILES['fileupload']['tmp_name'], "../../upload/".$_FILES['fileupload']['name']);
   $Reader = new SpreadsheetReader("../../upload/".$_FILES['fileupload']['name']); 
  $Sheets = $Reader->Sheets();
  $data = array();
  $no=1;
  //echo "<pre>";
  $sukses=0;
  $duplikat=0;
  $val = array();
  $ada_data = 0;
  $db->query("truncate import_pemasukan_temp"); 
  $q = $db->query("show columns from import_pemasukan_temp");
  foreach ($q as $k) {
     $kol[] = $k->Field;
  }
  unset($kol[0]);
   unset($kol[35]);
  $datax = array();
  $kolom = implode(',', $kol); 
  $query_insert = "insert into import_pemasukan_temp ($kolom) values ";
  //echo $query_insert;
  // $db->fetch_custom("delete from tb_remun where bulan='".$_POST['bulan_upload']."' and tahun='".$_POST['tahun_upload']."' and untuk='blu' and ket_cair='".$_POST['ket_upload']."' and ket='".$_POST['ket_kategori']."' ");
  //echo "<pre>";
 // print_r($Sheets);
 // $dat  = array();
  $data_detail  = array();
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
      if ($i>0) {
        $dat = array();
        $x=0;
       foreach ($r as $kk => $vv) {
          $dat[$x] = "'".$vv."'";
          $x++;
       }
      // print_r($dat);
       $datax[] = "(".implode(",", $dat).")";
      // echo "$data <br>";
      // print_r($dat);
      }
      $i++;
    }
    $isi = implode(",", $datax);
  }
    
  }
  $query_insert .= $isi;
  $db->query($query_insert);
  //echo "$query_insert";
  $q = $db->query("select * from import_pemasukan_temp group by no_aju order by tgl_bpb ");
  $duplikat_no = array(); 
  $sukses_upload = array(); 
  $error = 0;
  $sukses = 0;
foreach ($q as $k) {
  
   $data_pemasukan = (array)$k; 
  $nomor = get_nomor('pengeluaran','id');
  $no_bpb = getNoSJ(date("Y",strtotime($data_pemasukan['tgl_dokpab'])));
 
  unset($data_pemasukan['tgl_bpb2']); 
  unset($data_pemasukan['tgl_bpb']); 
  unset($data_pemasukan['no_bpb']);
  unset($data_pemasukan['id']);
  unset($data_pemasukan['no_bpb2']); 
  unset($data_pemasukan['kode']);
  unset($data_pemasukan['jumlah']);  
  unset($data_pemasukan['harga']);
  unset($data_pemasukan['valuta2']);
  unset($data_pemasukan['nilai']);
  unset($data_pemasukan['berat']); 
  unset($data_pemasukan['satuan']);
  unset($data_pemasukan['kurs2']);
  unset($data_pemasukan['row_no']);
  unset($data_pemasukan['kd_kategori']); 
  unset($data_pemasukan['ket']);
  unset($data_pemasukan['kd_kategori']); 
   unset($data_pemasukan['uraian']); 
  //$data_pemasukan['no_bpb'] = $no_bpb;
  $data_pemasukan['nomor'] = $nomor;
  $data_pemasukan['migrasi'] = '21';
    $data_pemasukan['tgl_sj'] = $k->tgl_bpb;
    $data_pemasukan['no_sj'] = $no_bpb;
    $data_pemasukan['tgl_dokpab'] = $k->tgl_aju;
    $tgl_sj = $k->tgl_bpb;
  if ($k->tgl_bpb=='') {
    $data_pemasukan['tgl_sj'] = $k->tgl_aju; 
    $tgl_sj  = $k->tgl_aju;
  }

  $qc = $db->query("select id from pemasukan where no_dokpab='".$data_pemasukan['no_dokpab']."'  ");
  if ($qc->rowCount()==0) {
    $simpan_pemasukan = $db->insert("pengeluaran",$data_pemasukan);
    if ($simpan_pemasukan) {
      echo $db->getErrorMessage();
       $sukses_upload[] = $data_pemasukan['no_dokpab'];
       $sukses++;
    }
    
  }else{
     $duplikat_no[] = $data_pemasukan['no_dokpab'];
     $error++;
  }
  
   $no=1;
  $qq = $db->query("select kode,jumlah,harga,valuta,nilai,berat from import_pemasukan_temp where no_aju='$k->no_aju'
   ");
  foreach ($qq as $kk) {
    $data_pemasukan_detail  = array('tgl_sj' => $tgl_sj , 
                                    'no_sj' => $no_bpb ,
                                    'nomor' => $nomor ,
                                    'kode' => $kk->kode ,
                                    'jumlah' => $kk->jumlah ,
                                    'harga' => $kk->harga ,
                                    'valuta' => $kk->valuta,
                                    'nilai' => $kk->nilai,
                                    'berat' => $kk->berat,
                                    'migrasi'=> '21' ,
                                    'kurs' => $k->kurs,
                                    'row_no' => $no ,
                                    'kd_kategori' => $k->kd_kategori 
                                );  
     $data_stock = array( 
                   'kd_barang' => $kk->kode,
                   'no_aju' => $k->no_aju,
                   'tgl_aju' => $k->tgl_aju,
                   'tgl_masuk' => $k->tgl_bpb,
                   'jenis_dokpab' => $k->jenis_dokpab,
                   'tgl_dokpab' => $k->tgl_aju,
                   'jumlah' => $kk->jumlah,
                   'harga' => $kk->harga,
                   'nilai' => $kk->nilai,
                   'valuta' => $kk->valuta,
                   'status' => '90',
                   'no_urut' => $no,
                   'no_ref' => $no_bpb, 
                   'migrasi' => '21' 
                   );
     $db->insert("pengeluaran_detail",$data_pemasukan_detail);
     $db->insert("stock_incoming",$data_stock); 
     echo $db->getErrorMessage();
     $no++;
  }

}
$res['error'] = $error;
$res['sukses'] = $sukses;
$res['pesan_sukses'] = "No Dokumen yang sukses di upload<br>".implode(",", $sukses_upload)."<br>"; 
$res['pesan_gagal'] = "No Dokumen Sudah ada [Gagal di Upload]<br>".implode(",", $duplikat_no)."<br>"; 
echo json_encode($res);


  break;
  

  case "in":

$thn = date("Y",strtotime($_POST["tgl_sj"]));
$no_sj = getNoSJ($thn); 
$nomor = get_nomor("pengeluaran","id");

$data = array(
    "nomor" => $nomor,
    "no_sj" => $no_sj,
    //"flag" => $_POST['dari'],
    "tgl_sj" => $_POST["tgl_sj"],
    "penerima" => $_POST["penerima"],
    "no_invoice" => $_POST["no_invoice"],
    "tgl_invoice" => $_POST["tgl_invoice"],
    "no_do" => $_POST["no_do"],
    "kd_catdet" => $_POST["kd_catdet"],
    "jenis_dokpab" => $_POST["jenisbckeluar_jenis_dokpab"],  
    "no_dokpab" => $_POST["no_dokpab"],
    "tgl_dokpab" => $_POST["tgl_dokpab"],
    "catatan" => $_POST["catatan"],
    "no_aju" => $_POST["no_aju"],
    "tgl_aju" => $_POST["tgl_aju"],
    "efaktur" => $_POST["efaktur"],
    "tgl_efaktur" => $_POST["tgl_efaktur"],
);

$db->insert("pengeluaran",$data);

// reset detail
$db->query("DELETE FROM pengeluaran_detail WHERE no_sj='$no_sj'");

foreach ($_POST['kode'] as $key => $value) {

    $kode_barang = $_POST['kode_input'][$key];

    // 🔥 FIX TOTAL (ANTI ERROR)
    if(!isset($_POST['jumlah'][$key])) continue;

    $qty_keluar = (float)$_POST['jumlah'][$key];

    if($qty_keluar <= 0) continue;

    // ================= AMBIL DATA BARANG =================
    $barang = $db->query("
        SELECT id, kd_kategori 
        FROM barang 
        WHERE kd_barang = '$kode_barang'
    ")->fetch();

    $jenis_barang = in_array($barang->kd_kategori, ['K02','K07']) ? 'FG' : 'BB';

    // ================= SIMPAN DETAIL =================
    $data_detail = [
        'nomor'         => $nomor,
        'no_sj'         => $no_sj,
        'tgl_sj'        => $_POST["tgl_sj"],
        'kode'          => $kode_barang,
        'jumlah'        => $qty_keluar,
        'harga'         => $_POST['harga'][$key],
        'valuta'        => $_POST['valuta'],
        'nilai'         => $_POST['nilai'][$key],
        'unit'          => $_POST['unit'][$key],
        'row_no'        => $key+1,
        'jenis_barang'  => $jenis_barang
    ];

    $db->insert("pengeluaran_detail",$data_detail);
    $id_pengeluaran_detail = $db->last_insert_id();

    // ================= VALIDASI STOCK =================
    $cek = $db->query("
        SELECT IFNULL(SUM(qty_sisa),0) as sisa
        FROM stock_layer
        WHERE kode = '$kode_barang'
        AND lokasi = 'GUDANG'
    ")->fetch();

    if ($cek->sisa < $qty_keluar) {
        die(json_encode([
            "status"=>"error",
            "error_message"=>"Stock tidak cukup untuk ".$kode_barang
        ]));
    }

    // ================= FIFO =================
    $sisa = $qty_keluar;

    $q_layer = $db->query("
        SELECT *
        FROM stock_layer
        WHERE kode = '$kode_barang'
        AND lokasi = 'GUDANG'
        AND qty_sisa > 0
        ORDER BY tgl_masuk ASC, id ASC
    ");

    foreach ($q_layer as $layer){

        if ($sisa <= 0) break;

        $pakai = ($sisa > $layer->qty_sisa)
                 ? $layer->qty_sisa
                 : $sisa;

        $sisa -= $pakai;

        // ================= TRACE =================
        if($jenis_barang == 'FG'){

            $db->insert("pengeluaran_detail_brg_jadi", [
                'id_pengeluaran_detail' => $id_pengeluaran_detail,
                'id_produksi_detail'    => $layer->ref_id ?? null,
                'jumlah'                => $pakai,
                'no_bpb'                => $layer->no_bpb ?? null,
                'jenis_barang'          => 'FG',
                'date_created'          => date("Y-m-d H:i:s")
            ]);

        } else {

            $db->insert("pengeluaran_detail_brg_jadi", [
                'id_pengeluaran_detail' => $id_pengeluaran_detail,
                'id_incoming_detail'    => $layer->ref_id ?? null,
                'jumlah'                => $pakai,
                'no_aju'                => $layer->no_aju,
                'no_dokpab'             => $layer->no_dokpab,
                'jenis_barang'          => 'BB',
                'date_created'          => date("Y-m-d H:i:s")
            ]);
        }

        // ================= LEDGER =================
        $db->insert("detail_transaksi", [
            'kd_barang'     => $kode_barang,
            'qty'           => ($pakai * -1),
            'posisi'        => 'GUDANG',
            'move_code'     => '601',
            'no_ref'        => $no_sj,
            'no_aju'        => ($jenis_barang == 'BB') ? $layer->no_aju : null,
            'no_dokpab'     => ($jenis_barang == 'BB') ? $layer->no_dokpab : null,
            'no_bpb'        => ($jenis_barang == 'FG') ? $layer->no_bpb : null,
            'remark'        => 'Pengeluaran Barang',
            'posting_date'  => $_POST["tgl_sj"],
            'created_by'    => $_SESSION['username'],
            'date_created'  => date("Y-m-d H:i:s")
        ]);

        // ================= UPDATE STOCK =================
        $db->query("
            UPDATE stock_layer
            SET qty_sisa = qty_sisa - $pakai
            WHERE id = '".$layer->id."'
        ");
    }
}

action_response($db->getErrorMessage());
break;

   
  case "show_detailx":
  $id = $_POST['id'];
  ?>
  <table class="table">
    <thead>
      <tr>
        <th>No</th>
        <th>No SJ</th>
        <th>Tanggal</th>
        <th>Kode Barang</th>
        <th>Unit</th>
        <th>Valuta</th>
        <th>Qty</th>
        <th>Harga</th>
        <th>Nilai</th>
   <!--      <th>Berat</th>
        <th>Lokasi</th> -->
      </tr>
    </thead>
    <tbody>
  <?php
  $qty=0;
  $harga =0;
  $nilai=0;
  $berat =0;
  $q = $db->query("select b.valuta, d.*,ba.nm_barang from pengeluaran_detail d 
    join pengeluaran b on b.no_sj=d.no_sj
    join barang ba on ba.kd_barang=d.kode where b.id='$id' ");


  foreach ($q as $k) { 
    echo "<tr>
            <td>$k->row_no</td>
            <td>$k->no_sj</td>
            <td>$k->tgl_sj</td>
            <td>$k->kode , $k->nm_barang</td>
            <td>$k->unit</td>
            <td>$k->valuta</td>
            <td style='text-align: right'>".number_format($k->jumlah,4)."</td>
            <td style='text-align: right'>".number_format($k->harga,4)."</td>
            <td style='text-align: right'>".number_format($k->nilai,4)."</td> 
           
          </tr>";
          $qty = $qty +$k->jumlah; 
          $harga = $harga + $k->harga; 
          $nilai = $nilai + $k->nilai;
        
  }
  ?>
    <tr>
      <td colspan="6" style="text-align: center">Jumlah</td>
      <td style="text-align: right"><?= number_format($qty,4) ?></td>
      <td style="text-align: right"><?= number_format($harga,4)  ?></td>
      <td style="text-align: right"><?= number_format($nilai,4)  ?></td>
   
    </tr>
      </tbody>
  </table>
  <?php
    break;

  case "delete":
    
    
      
    // $db->query("delete pengeluaran_detail from  pengeluaran_detail join pengeluaran 
    //             on pengeluaran_detail.no_sj=pengeluaran.no_sj
    //              where pengeluaran.id='".$_GET["id"]."'  ");  
    $db->delete("pengeluaran","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("pengeluaran","no_sj",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
   $no_sj = $_POST["no_sj"];
   $nomor = get_nomor("pengeluaran","id");
   $data = array(
    //  "nomor" => $nomor,
      "tgl_sj" => $_POST["tgl_sj"],
      "penerima" => $_POST["penerima"],
      "no_invoice" => $_POST["no_invoice"],
      "tgl_invoice" => $_POST["tgl_invoice"],
      "no_do" => $_POST["no_do"],
      "kd_catdet" => $_POST["kd_catdet"],
       "jenis_dokpab" => $_POST["jenisbckeluar_jenis_dokpab"], 
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "valuta" => $_POST["valuta"],
      "catatan" => $_POST["catatan"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "efaktur" => $_POST["efaktur"],
      "tgl_efaktur" => $_POST["tgl_efaktur"],
   );

     
  $db->query("delete from pengeluaran_detail where no_sj='$no_sj' ");
   $no=1; 
   foreach ($_POST['kode'] as $key => $value) {
      $data_detail = array(
                    'nomor' => $_POST['nomor'] ,    
                    'no_sj' => $no_sj,
                    'tgl_sj' => $_POST["tgl_sj"],
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => $_POST['jumlah'][$key],
                    'harga' => $_POST['harga'][$key],
                    'valuta' => $_POST['valuta'],
                    'nilai' => $_POST['nilai'][$key],
                    'unit' => $_POST['unit'][$key],
                    'row_no' => $no,
                    // 'no_urut' => $no,
                    // 'no_aju' => $_POST['no_aju'],
                    // 'tgl_aju' => $_POST['tgl_aju'],
                    // 'tgl_masuk' => $_POST['tgl_aju'],
                    // 'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    // 'no_dokpab' => $_POST['no_dokpab'],
                    // 'tgl_dokpab' => $_POST['tgl_dokpab'],
                   
                  );
        $db->insert("pengeluaran_detail",$data_detail); 
       // print_r($data_detail);
      $no++;
   }
   
   
   

    
    
    $up = $db->update("pengeluaran",$data,"no_sj",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>