<?php
error_reporting(0);

// ================= KPI =================

// PEMASUKAN
$pemasukan = $db->fetch("
SELECT COUNT(*) as total 
FROM pemasukan  
WHERE MONTH(tgl_bpb)=MONTH(CURDATE()) 
AND YEAR(tgl_bpb)=YEAR(CURDATE())
");

// PENGELUARAN
$pengeluaran = $db->fetch("
SELECT COUNT(*) as total 
FROM pengeluaran 
WHERE MONTH(tgl_keluar)=MONTH(CURDATE()) 
AND YEAR(tgl_keluar)=YEAR(CURDATE())
");

// PRODUKSI
$produksi = $db->fetch("
SELECT COUNT(*) as total 
FROM detail_transaksi 
WHERE is_produksi='1'
");

// STOCK
$barang = $db->fetch("
SELECT SUM(qty) as total 
FROM detail_transaksi
");


// ================= CHART PEMASUKAN =================
$q = $db->query("
SELECT jenis_dokpab, COUNT(*) as jml
FROM pemasukan
GROUP BY jenis_dokpab
");

$kategori=[]; $data=[];
foreach($q as $r){
    $kategori[] = $r->jenis_dokpab;
    $data[] = (int)$r->jml;
}


// ================= CHART PENGELUARAN =================
$q2 = $db->query("
SELECT jenis_dokpab, COUNT(*) as jml
FROM pengeluaran
GROUP BY jenis_dokpab
");

$kat2=[]; $data2=[];
foreach($q2 as $r){
    $kat2[] = $r->jenis_dokpab;
    $data2[] = (int)$r->jml;
}


// ================= PIE =================
$q = $db->query("SELECT jenis_dokpab, sum(jml) as jml from v_rekap_pie group by jenis_dokpab");
$pie=[];
foreach($q as $r){
    $pie[] = [$r->jenis_dokpab,(int)$r->jml];
}


// ================= TREND =================
$q4 = $db->query("
SELECT MONTH(tgl_bpb) as bln, COUNT(*) as jml
FROM pemasukan
WHERE YEAR(tgl_bpb)=YEAR(CURDATE())
GROUP BY MONTH(tgl_bpb)
");

$bulan=[]; $trend=[];
foreach($q4 as $r){
    $bulan[] = date('M', mktime(0,0,0,$r->bln,1));
    $trend[] = (int)$r->jml;
}
?>

<section class="content-header">
<h1>Dashboard ERP <small>Kawasan Berikat</small></h1>
</section>

<section class="content">

<!-- KPI -->
<div class="row">

<div class="col-md-3">
<div class="small-box bg-aqua">
<div class="inner">
<h3><?= number_format($pemasukan->total) ?></h3>
<p>Pemasukan Bulan Ini</p>
</div>
<div class="icon"><i class="fa fa-download"></i></div>
</div>
</div>

<div class="col-md-3">
<div class="small-box bg-green">
<div class="inner">
<h3><?= number_format($pengeluaran->total) ?></h3>
<p>Pengeluaran Bulan Ini</p>
</div>
<div class="icon"><i class="fa fa-upload"></i></div>
</div>
</div>

<div class="col-md-3">
<div class="small-box bg-yellow">
<div class="inner">
<h3><?= number_format($produksi->total) ?></h3>
<p>Produksi</p>
</div>
<div class="icon"><i class="fa fa-industry"></i></div>
</div>
</div>

<div class="col-md-3">
<div class="small-box bg-red">
<div class="inner">
<h3><?= number_format($barang->total) ?></h3>
<p>Total Stock</p>
</div>
<div class="icon"><i class="fa fa-cubes"></i></div>
</div>
</div>

</div>


<!-- CHART -->
<div class="row">

<div class="col-md-6">
<div class="box">
<div class="box-header"><h3 class="box-title">Dokumen Pemasukan</h3></div>
<div class="box-body"><div id="chart_pemasukan"></div></div>
</div>
</div>

<div class="col-md-6">
<div class="box">
<div class="box-header"><h3 class="box-title">Dokumen Pengeluaran</h3></div>
<div class="box-body"><div id="chart_pengeluaran"></div></div>
</div>
</div>

</div>


<div class="row">

<div class="col-md-6">
<div class="box">
<div class="box-header"><h3 class="box-title">Distribusi Dokumen</h3></div>
<div class="box-body"><div id="chart_pie"></div></div>
</div>
</div>

<div class="col-md-6">
<div class="box">
<div class="box-header"><h3 class="box-title">Trend Transaksi</h3></div>
<div class="box-body"><div id="chart_trend"></div></div>
</div>
</div>

</div>


<!-- TOP BARANG -->
<div class="box">
<div class="box-header"><h3 class="box-title">Top Barang Masuk</h3></div>
<div class="box-body">

<table class="table table-bordered">
<thead>
<tr>
<th>No</th>
<th>Kode Barang</th>
<th>Nama Barang</th>
<th>Total Masuk</th>
</tr>
</thead>

<tbody>
<?php
$q5 = $db->query("
SELECT kd_barang, SUM(qty) as total
FROM detail_transaksi
WHERE qty > 0
GROUP BY kd_barang
ORDER BY total DESC
LIMIT 5
");

$no=1;
foreach($q5 as $r){
$b = $db->fetch("SELECT nm_barang FROM barang WHERE kd_barang='$r->kd_barang'");
?>
<tr>
<td><?= $no++ ?></td>
<td><?= $r->kd_barang ?></td>
<td><?= $b->nm_barang ?></td>
<td><?= number_format($r->total) ?></td>
</tr>
<?php } ?>
</tbody>

</table>

</div>
</div>

</section>

<script src="<?= base_url() ?>assets/js/highcharts.js"></script>

<script>
// PEMASUKAN
Highcharts.chart('chart_pemasukan', {
    chart:{type:'column'},
    title:{text:'Grafik Pemasukan Barang'},
    xAxis:{categories: <?= json_encode($kategori) ?>},
    series:[{
        name:'Jumlah',
        data: <?= json_encode($data) ?>
    }]
});

// PENGELUARAN
Highcharts.chart('chart_pengeluaran', {
    chart:{type:'column'},
    title:{text:'Grafik Pengeluaran Barang'},
    xAxis:{categories: <?= json_encode($kat2) ?>},
    series:[{
        name:'Jumlah',
        data: <?= json_encode($data2) ?>
    }]
});

// PIE
Highcharts.chart('chart_pie', {
    chart:{type:'pie'},
    title:{text:'Komposisi Dokumen'},
    series:[{
        name:'Dokumen',
        data: <?= json_encode($pie) ?>
    }]
});

// TREND
Highcharts.chart('chart_trend', {
    chart:{type:'line'},
    title:{text:'Trend Transaksi per Bulan'},
    xAxis:{categories: <?= json_encode($bulan) ?>},
    series:[{
        name:'Transaksi',
        data: <?= json_encode($trend) ?>
    }]
});

</script>