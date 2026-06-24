<?php
$reports = finrep_reports();
$reportGroups = array(
  'report' => array('title'=>'Laporan Keuangan', 'items'=>array()),
  'chart' => array('title'=>'Grafik & Proyeksi', 'items'=>array())
);
foreach ($reports as $report) {
  $type = isset($report['type']) ? $report['type'] : '';
  $isChart = $type === 'Finance Chart' || $type === 'Cash Projection' || strpos($report['slug'], 'grafik-') === 0 || strpos($report['slug'], 'proyeksi-') === 0;
  $reportGroups[$isChart ? 'chart' : 'report']['items'][] = $report;
}
?>
<style>
.finrep-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin-bottom:18px}
.finrep-section{margin-bottom:22px}.finrep-section:last-child{margin-bottom:0}
.finrep-section-title{display:flex;align-items:center;gap:8px;margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:16px;font-weight:700}
.finrep-section-title i{color:#3c8dbc}
.finrep-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.finrep-item{display:flex;align-items:flex-start;gap:11px;min-height:112px;padding:12px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;color:#111827;text-decoration:none;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
.finrep-item:hover{border-color:#3c8dbc;box-shadow:0 4px 12px rgba(60,141,188,.14);color:#111827;transform:translateY(-1px)}
.finrep-icon{flex:0 0 34px;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:#3c8dbc;font-size:16px;line-height:1}
.finrep-icon.orange{background:#f39c12}
.finrep-title{font-size:14px;line-height:1.3;margin:0 0 5px;color:#111827;font-weight:700}
.finrep-desc{font-size:12px;line-height:1.35;color:#6b7280;margin:0}
@media(max-width:1199px){.finrep-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:991px){.finrep-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.finrep-grid{grid-template-columns:1fr}.finrep-item{min-height:auto}}
</style>

<section class="content-header">
  <h1>Finance Reports <small>Report Center</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li>Finance</li>
    <li class="active">Finance Reports</li>
  </ol>
</section>

<section class="content">
  <div class="finrep-wrap">
    <?php foreach ($reportGroups as $groupKey => $group) {
      if (!count($group['items'])) continue;
      $groupIcon = $groupKey === 'chart' ? 'fa-line-chart' : 'fa-file-text-o';
    ?>
    <div class="finrep-section finrep-section-<?=finrep_h($groupKey);?>">
      <h3 class="finrep-section-title"><i class="fa <?=finrep_h($groupIcon);?>"></i> <?=finrep_h($group['title']);?></h3>
      <div class="finrep-grid">
        <?php foreach ($group['items'] as $report) {
          $accent = isset($report['accent']) ? $report['accent'] : '';
        ?>
        <a href="<?=base_index().finrep_h(!empty($report['external_url']) ? $report['external_url'] : 'finance-report/'.$report['slug']);?>" class="finrep-item">
          <div class="finrep-icon <?=finrep_h($accent);?>"><i class="fa <?=finrep_h($report['icon']);?>"></i></div>
          <div>
            <h3 class="finrep-title"><?=finrep_h($report['title']);?></h3>
            <p class="finrep-desc"><?=finrep_h($report['description']);?></p>
          </div>
        </a>
        <?php } ?>
      </div>
    </div>
      <?php } ?>
  </div>
</section>
