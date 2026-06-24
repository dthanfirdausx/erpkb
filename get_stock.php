<?php
session_start();
include "inc/config.php";
session_check_json();

function gs_input($key, $default = '')
{
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function gs_stock_layer_qty($db, $kode, $lokasi, $jenisDokpab = '', $fgOnly = null)
{
  $where = " WHERE sl.qty_sisa>0 AND sl.lokasi=? AND (b.kd_barang=? OR b.id=?) ";
  $params = array($lokasi, $kode, $kode);

  if ($jenisDokpab !== '') {
    $where .= " AND sl.jenis_dokpab=? ";
    $params[] = $jenisDokpab;
  }

  if ($fgOnly === true) {
    $where .= " AND b.kd_kategori IN ('K02','K07') ";
  } elseif ($fgOnly === false) {
    $where .= " AND b.kd_kategori NOT IN ('K02','K07') ";
  }

  $row = $db->fetch(
    "SELECT COALESCE(SUM(sl.qty_sisa),0) stock
     FROM stock_layer sl
     INNER JOIN barang b ON b.kd_barang=sl.kode
     $where",
    $params
  );

  return $row ? (float)$row->stock : 0;
}

function gs_response($kode, $stock, $jumlah)
{
  $res = array();
  if ($stock > 0) {
    if ((float)$jumlah <= $stock) {
      $res['status'] = "1";
      $res['pesan'] = "Stock $kode = $stock";
    } else {
      $res['status'] = "0";
      $res['pesan'] = "Stock $kode yang tersedia = $stock";
    }
  } else {
    $res['status'] = "0";
    $res['pesan'] = "Stock $kode = 0";
  }
  $res['stock'] = $stock;
  return $res;
}

function gs_bom_components($db, $kode)
{
  return $db->query(
    "SELECT d.jumlah,d.kodebb,ba.kd_barang,ba.nm_barang
     FROM bom b
     JOIN bom_detail d ON d.id_bom=b.id
     JOIN barang ba ON ba.kd_barang=d.kodebb
     WHERE b.kodebj=?",
    array($kode)
  );
}

$act = isset($_GET["act"]) ? $_GET["act"] : '';

switch ($act) {
  case "get_stock_outgoing":
    $kode = gs_input('kode');
    $jumlah = gs_input('jumlah', '0');
    $jenis = gs_input('jenis');
    $fgOnly = null;
    if ($jenis === '1') {
      $fgOnly = true;
    } elseif ($jenis !== '') {
      $fgOnly = false;
    }
    echo json_encode(gs_response($kode, gs_stock_layer_qty($db, $kode, 'OUTGOING', '', $fgOnly), $jumlah));
    break;

  case "get_stock_gudang":
  case "get_stock_incoming":
    $kode = gs_input('kode');
    $jumlah = gs_input('jumlah', '0');
    echo json_encode(gs_response($kode, gs_stock_layer_qty($db, $kode, 'GUDANG'), $jumlah));
    break;

  case "get_stock_layer_filtered":
  case "get_stock_incoming2":
    $kode = gs_input('kode');
    $jumlah = gs_input('jumlah', '0');
    $jenisDokpab = gs_input('jenis_dokpab');
    $jenis = gs_input('jenis');
    $lokasi = strtoupper($jenis) === 'PRODUKSI' ? 'PRODUKSI' : 'GUDANG';
    echo json_encode(gs_response($kode, gs_stock_layer_qty($db, $kode, $lokasi, $jenisDokpab), $jumlah));
    break;

  case "get_stock_produksi":
    $kode = gs_input('kode');
    $jumlah = gs_input('jumlah', '0');
    echo json_encode(gs_response($kode, gs_stock_layer_qty($db, $kode, 'PRODUKSI'), $jumlah));
    break;

  case 'get_stock_by_bom':
    $kode = gs_input('kode');
    $qtyStock = array();
    $kodeBarang = array();
    $namaBarang = array();
    $jmlProduksi = array();

    foreach (gs_bom_components($db, $kode) as $component) {
      $stock = gs_stock_layer_qty($db, $component->kodebb, 'PRODUKSI');
      $qtyStock[] = $stock;
      $kodeBarang[] = $component->kd_barang;
      $namaBarang[] = $component->nm_barang;
      $jmlProduksi[] = ((float)$component->jumlah > 0) ? ($stock / (float)$component->jumlah) : 0;
    }

    $res = array();
    if (count($jmlProduksi) > 0) {
      sort($jmlProduksi);
      $stockTersedia = (float)$jmlProduksi[0];
      $res['status'] = $stockTersedia > 0 ? "1" : "0";
      $res['pesan'] = $stockTersedia > 0 ? "Stock $kode = $stockTersedia" : "Stock $kode = 0";
      $res['stock'] = $stockTersedia > 0 ? $stockTersedia : "0";
      $res['detail_bahan_baku'] = "<table class='table'><thead><tr><th>Kode</th><th>Nama Barang</th><th>Stok</th></tr></thead><tbody>";
      foreach ($qtyStock as $key => $value) {
        $res['detail_bahan_baku'] .= "<tr><td>".htmlspecialchars($kodeBarang[$key], ENT_QUOTES, 'UTF-8')."</td><td>".htmlspecialchars($namaBarang[$key], ENT_QUOTES, 'UTF-8')."</td><td>".number_format((float)$value, 5, ',', '.')."</td></tr>";
      }
      $res['detail_bahan_baku'] .= "</tbody></table>";
    } else {
      $res['status'] = "0";
      $res['pesan'] = "Stock $kode = 0";
      $res['stock'] = "0";
    }
    echo json_encode($res);
    break;

  default:
    echo json_encode(array('status'=>'0','pesan'=>'Aksi stock tidak dikenal.','stock'=>0));
    break;
}
?>
