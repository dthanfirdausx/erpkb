<?php
$reportNotFound = !$report;
if ($reportNotFound) {
  $report = array(
    'title' => 'Finance Report',
    'description' => 'Report tidak ditemukan.',
    'icon' => 'fa-file-text-o',
    'type' => 'Finance'
  );
}
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$year = date('Y');
?>
<style>
.frd-hero{background:#fff;border:1px solid #e7edf5;border-radius:8px;padding:22px;margin-bottom:16px;box-shadow:0 5px 16px rgba(15,23,42,.04)}
.frd-hero-inner{display:flex;align-items:flex-start;gap:20px}.frd-icon{width:74px;height:74px;flex:0 0 74px;border:4px solid #1e73be;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#1e73be;font-size:38px}
.frd-icon.orange{border-color:#f27a45;color:#f27a45}.frd-title{margin:0 0 8px;font-size:28px;font-weight:600;color:#111827}.frd-desc{margin:0;color:#7c6f6f;font-size:16px;line-height:1.45}.frd-tag{display:inline-block;margin-top:12px;border-radius:999px;background:#eef6ff;color:#1e73be;padding:6px 10px;font-size:12px;font-weight:700}
.frd-card{border:1px solid #e7edf5;border-radius:8px;background:#fff;box-shadow:0 5px 16px rgba(15,23,42,.04);margin-bottom:16px}.frd-card .box-header{border-bottom:1px solid #edf2f7}.frd-card .box-title{font-weight:700;color:#111827}
.frd-kpi{border:1px solid #e7edf5;border-radius:8px;background:#f8fafc;padding:14px;margin-bottom:14px;min-height:92px}.frd-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:700}.frd-kpi strong{display:block;margin-top:8px;font-size:22px;color:#111827}.frd-kpi small{color:#94a3b8}
.frd-table th{background:#f8fafc;color:#334155}.frd-table th,.frd-table td{font-size:12px;vertical-align:middle!important}.frd-placeholder{padding:22px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;color:#64748b}
@media(max-width:767px){.frd-hero-inner{display:block}.frd-icon{margin-bottom:14px}.frd-title{font-size:24px}}
</style>

<section class="content-header">
  <h1><?=finrep_h($report['title']);?> <small>Dummy Preview</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>finance-report">Finance Reports</a></li>
    <li class="active"><?=finrep_h($report['title']);?></li>
  </ol>
</section>

<section class="content">
  <?php if ($reportNotFound) { ?>
    <div class="alert alert-warning">Report yang diminta belum terdaftar.</div>
  <?php } ?>

  <div class="frd-hero">
    <div class="frd-hero-inner">
      <div class="frd-icon <?=finrep_h(isset($report['accent']) ? $report['accent'] : '');?>"><i class="fa <?=finrep_h($report['icon']);?>"></i></div>
      <div>
        <h2 class="frd-title"><?=finrep_h($report['title']);?></h2>
        <p class="frd-desc"><?=finrep_h($report['description']);?></p>
        <span class="frd-tag"><?=finrep_h(isset($report['type']) ? $report['type'] : 'Finance');?> · Dummy Layout</span>
      </div>
    </div>
  </div>

  <div class="box frd-card">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-filter"></i> Filter Laporan</h3>
      <div class="box-tools">
        <a href="<?=base_index();?>finance-report" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Kembali</a>
        <?php if (!empty($report['external_url'])) { ?>
          <a href="<?=base_index().finrep_h($report['external_url']);?>" class="btn btn-primary btn-sm"><i class="fa fa-external-link"></i> Buka Report Aktif</a>
        <?php } ?>
      </div>
    </div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-md-2">Periode</label>
          <div class="col-md-2"><input type="text" class="form-control" value="<?=finrep_h($monthStart);?>" disabled></div>
          <div class="col-md-2"><input type="text" class="form-control" value="<?=finrep_h($today);?>" disabled></div>
          <label class="control-label col-md-1">Tahun</label>
          <div class="col-md-2"><input type="text" class="form-control" value="<?=finrep_h($year);?>" disabled></div>
          <div class="col-md-3">
            <button class="btn btn-primary" disabled><i class="fa fa-search"></i> Tampilkan</button>
            <button class="btn btn-success" disabled><i class="fa fa-file-excel-o"></i> Export</button>
            <button class="btn btn-default" disabled><i class="fa fa-print"></i> Print</button>
          </div>
        </div>
      </form>
      <div class="frd-placeholder">
        <strong>Dummy screen.</strong> Struktur filter, KPI, tabel, export, dan print sudah disiapkan sebagai rumah awal. Query, agregasi, dan format report final akan diisi pada tahap develop.
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="frd-kpi"><span>Periode Aktif</span><strong><?=finrep_h(date('M Y'));?></strong><small>Dummy value</small></div></div>
    <div class="col-sm-3"><div class="frd-kpi"><span>Total Baris</span><strong>0</strong><small>Belum dihitung</small></div></div>
    <div class="col-sm-3"><div class="frd-kpi"><span>Total Nilai</span><strong>0.00</strong><small>Belum dihitung</small></div></div>
    <div class="col-sm-3"><div class="frd-kpi"><span>Status</span><strong>Planned</strong><small>Siap dikembangkan</small></div></div>
  </div>

  <div class="box frd-card">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-table"></i> Preview Output</h3></div>
    <div class="box-body table-responsive">
      <table class="table table-bordered table-striped frd-table">
        <thead>
          <tr>
            <th style="width:60px">No</th>
            <th>Kelompok</th>
            <th>Deskripsi</th>
            <th class="text-right">Periode Ini</th>
            <th class="text-right">Pembanding</th>
            <th class="text-right">Selisih</th>
            <th class="text-right">%</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="text-center">1</td>
            <td><?=finrep_h(isset($report['type']) ? $report['type'] : 'Finance');?></td>
            <td><?=finrep_h($report['title']);?> - placeholder row</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00%</td>
          </tr>
          <tr>
            <td class="text-center">2</td>
            <td>Subtotal</td>
            <td>Baris contoh untuk layout tabel</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00</td>
            <td class="text-right">0.00%</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>
