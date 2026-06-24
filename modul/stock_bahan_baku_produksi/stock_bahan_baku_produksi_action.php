<?php
session_start();
include "../../inc/config.php";
session_check_json();

function sbp_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sbp_locked_action()
{
  action_response('Aksi legacy stock produksi dikunci. Stock produksi sekarang dibaca langsung dari stock_layer.');
}

function sbp_export_stock($db, $title, $fgOnly = false)
{
  header("Content-Type: application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=".($fgOnly ? "rekap_stock_brg_jadi.xls" : "rekap_stock_bahan_baku_produksi.xls"));
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: private", false);

  $categoryFilter = $fgOnly ? " AND b.kd_kategori IN ('K02','K07') " : "";
  $q = $db->query("
    SELECT sl.jenis_dokpab,sl.kode kd_barang,b.nm_barang,sl.no_dokpab,pd.tgl_dokpab,sl.no_aju,pd.tgl_aju,
           sl.tgl_masuk tgl_bpb,sl.lokasi,b.satuan,sl.qty_sisa stock,k.nm_kategori
    FROM stock_layer sl
    LEFT JOIN barang b ON b.kd_barang=sl.kode
    LEFT JOIN kategori k ON k.kd_kategori=b.kd_kategori
    LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
    WHERE sl.qty_sisa>0 AND sl.lokasi='PRODUKSI' $categoryFilter
    ORDER BY sl.kode ASC, sl.tgl_masuk ASC, sl.jenis_dokpab ASC
  ");
  ?>
  <h3 style="text-align:center"><?=sbp_h($title);?></h3>
  <table border="1">
    <thead>
      <tr>
        <th>Jenis Dokpab</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Kategori</th>
        <th>No Dokpab</th>
        <th>Tanggal Dokpab</th>
        <th>No Aju</th>
        <th>Tanggal Aju</th>
        <th>Tanggal Masuk</th>
        <th>Lokasi</th>
        <th>Stock</th>
        <th>Satuan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($q as $kk) { ?>
        <tr>
          <td><?=sbp_h($kk->jenis_dokpab);?></td>
          <td><?=sbp_h($kk->kd_barang);?></td>
          <td><?=sbp_h($kk->nm_barang);?></td>
          <td><?=sbp_h($kk->nm_kategori);?></td>
          <td>'<?=sbp_h($kk->no_dokpab);?></td>
          <td><?=sbp_h($kk->tgl_dokpab);?></td>
          <td>'<?=sbp_h($kk->no_aju);?></td>
          <td><?=sbp_h($kk->tgl_aju);?></td>
          <td><?=sbp_h($kk->tgl_bpb);?></td>
          <td><?=sbp_h($kk->lokasi);?></td>
          <td style="text-align:right"><?=number_format((float)$kk->stock,5,",",".");?></td>
          <td><?=sbp_h($kk->satuan);?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <?php
}

switch ($_GET["act"]) {
  case "download_excel_brg_jadi":
    sbp_export_stock($db, 'Rekap Stock Barang Jadi / Setengah Jadi Produksi', true);
    break;

  case "download_excel":
    sbp_export_stock($db, 'Rekap Stock Bahan Baku Produksi', false);
    break;

  case "sinkron_stock":
    sbp_locked_action();
    break;

  case "show_detail_stock":
    $kd_barang = isset($_POST['kd_barang']) ? $_POST['kd_barang'] : '';
    $q = $db->query("
      SELECT sl.*,b.nm_barang,b.satuan,p.plant_code,s.storage_code,s.storage_name,bin.bin_code,bin.bin_name
      FROM stock_layer sl
      LEFT JOIN barang b ON b.kd_barang=sl.kode
      LEFT JOIN erp_plant p ON p.id=sl.plant_id
      LEFT JOIN erp_storage_location s ON s.id=sl.storage_location_id
      LEFT JOIN erp_storage_bin bin ON bin.id=sl.storage_bin_id
      WHERE sl.kode=? AND sl.lokasi='PRODUKSI' AND sl.qty_sisa>0
      ORDER BY sl.tgl_masuk ASC,sl.id ASC
    ", array($kd_barang));
    ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>No</th>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Jenis Dokpab</th>
            <th>No Aju</th>
            <th>No Dokpab</th>
            <th>Plant</th>
            <th>Storage Location</th>
            <th>Storage Bin</th>
            <th>Tgl Masuk</th>
            <th>Qty Masuk</th>
            <th>Sisa</th>
            <th>Satuan</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $total_masuk = 0;
          $total_sisa = 0;
          foreach ($q as $k) {
            $total_masuk += (float)$k->qty_masuk;
            $total_sisa += (float)$k->qty_sisa;
          ?>
            <tr>
              <td><?=intval($no++);?></td>
              <td><?=sbp_h($k->kode);?></td>
              <td><?=sbp_h($k->nm_barang);?></td>
              <td><?=sbp_h($k->jenis_dokpab);?></td>
              <td><?=sbp_h($k->no_aju);?></td>
              <td><?=sbp_h($k->no_dokpab);?></td>
              <td><?=sbp_h($k->plant_code);?></td>
              <td><?=sbp_h(trim($k->storage_code.' - '.$k->storage_name, ' -'));?></td>
              <td><?=sbp_h(trim($k->bin_code.' - '.$k->bin_name, ' -'));?></td>
              <td><?=sbp_h($k->tgl_masuk);?></td>
              <td style="text-align:right"><?=number_format((float)$k->qty_masuk,4,",",".");?></td>
              <td style="text-align:right"><?=number_format((float)$k->qty_sisa,4,",",".");?></td>
              <td><?=sbp_h($k->satuan);?></td>
            </tr>
          <?php } ?>
          <tr style="font-weight:bold;background:#f1f5f9">
            <td colspan="10" class="text-center">TOTAL</td>
            <td style="text-align:right"><?=number_format($total_masuk,4,",",".");?></td>
            <td style="text-align:right"><?=number_format($total_sisa,4,",",".");?></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php
    break;

  case "in":
  case "delete":
  case "del_massal":
  case "up":
    sbp_locked_action();
    break;

  default:
    break;
}
?>
