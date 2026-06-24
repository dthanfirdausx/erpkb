<?php
include_once "management_dashboard_lib.php";
$kpi = md_kpis($db);
$alerts = md_critical_alerts($db);
$moduleStatus = md_module_status($db);
$trendChart = md_trend_chart($db);
$alertChart = md_alert_chart($alerts);
$recentActivity = md_recent_activity($db);
$criticalCount = 0;
$warningCount = 0;
foreach ($alerts as $alert) {
  if ($alert['severity'] === 'danger') $criticalCount++;
  if ($alert['severity'] === 'warning') $warningCount++;
}
$productionCompletion = $kpi['production_order_qty'] > 0 ? ($kpi['production_completed_qty'] / $kpi['production_order_qty']) * 100 : 0;
$scrapRate = ($kpi['production_completed_qty'] + $kpi['production_scrap_qty']) > 0 ? ($kpi['production_scrap_qty'] / ($kpi['production_completed_qty'] + $kpi['production_scrap_qty'])) * 100 : 0;
?>
<style>
.md-hero{background:linear-gradient(135deg,#0f172a,#1d4ed8 55%,#0f766e);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 12px 28px rgba(15,23,42,.18)}
.md-hero h1{margin:0 0 7px;font-size:28px;font-weight:800}.md-hero p{margin:0;color:#dbeafe}.md-hero .md-pill{display:inline-block;margin-top:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.16);font-weight:700}
.md-card{border:1px solid #e5edf5;border-radius:14px;background:#fff;box-shadow:0 7px 20px rgba(15,23,42,.055);margin-bottom:16px}.md-card .box-header{border-bottom:1px solid #edf2f7;padding:14px 16px}.md-card .box-title{font-weight:800;color:#0f172a}
.md-kpi{border:1px solid #e5edf5;border-radius:14px;background:#fff;padding:15px;min-height:128px;margin-bottom:16px;box-shadow:0 6px 16px rgba(15,23,42,.045);position:relative;overflow:hidden}.md-kpi:after{content:"";position:absolute;right:-28px;top:-28px;width:88px;height:88px;border-radius:50%;background:rgba(37,99,235,.08)}.md-kpi i{width:40px;height:40px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;background:#2563eb;margin-bottom:9px}.md-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase}.md-kpi strong{display:block;font-size:23px;color:#0f172a;line-height:1.25}.md-kpi small{display:block;color:#64748b;margin-top:4px}
.md-kpi.green i{background:#059669}.md-kpi.orange i{background:#f59e0b}.md-kpi.red i{background:#dc2626}.md-kpi.purple i{background:#7c3aed}.md-kpi.teal i{background:#0f766e}
.md-alert{border-radius:12px;border:1px solid #e5edf5;padding:13px 14px;margin-bottom:10px;background:#fff}.md-alert .md-alert-value{font-size:24px;font-weight:800;line-height:1}.md-alert.danger{border-left:5px solid #dc2626}.md-alert.warning{border-left:5px solid #f59e0b}.md-alert.info{border-left:5px solid #2563eb}.md-alert.success{border-left:5px solid #059669}.md-alert h4{margin:0 0 4px;font-size:14px;font-weight:800}.md-alert p{margin:0;color:#64748b}.md-alert a{font-weight:700}
.md-chart{min-height:310px}.md-table th,.md-table td{font-size:12px;vertical-align:middle}.md-table th{background:#f8fafc;color:#334155}.md-status{font-weight:700}.md-feed{list-style:none;padding:0;margin:0}.md-feed li{border-bottom:1px solid #edf2f7;padding:10px 0}.md-feed li:last-child{border-bottom:0}.md-feed b{color:#0f172a}.md-feed small{display:block;color:#64748b}.progress.md-progress{height:8px;border-radius:999px;margin:8px 0 0;background:#e5e7eb}.progress.md-progress .progress-bar{border-radius:999px}
@media(max-width:991px){.md-hero .text-right{text-align:left!important;margin-top:12px}}
</style>

<section class="content-header">
  <h1>Management Dashboard <small>ERP Analytics Cockpit</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li>Analytics</li>
    <li class="active">Management Dashboard</li>
  </ol>
</section>

<section class="content">
  <div class="md-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><i class="fa fa-line-chart"></i> ERP Management Cockpit</h1>
        <p>Ringkasan kesehatan ERP lintas finance, warehouse, purchasing, sales, production, HR, dan system control. Area critical ditampilkan paling atas agar cepat ditindaklanjuti.</p>
        <span class="md-pill">Periode MTD: <?=md_h($kpi['period_from']);?> s/d <?=md_h($kpi['period_to']);?></span>
      </div>
      <div class="col-md-4 text-right">
        <h2 style="margin:0;font-weight:800"><?=md_num($criticalCount);?> Critical</h2>
        <p style="margin-top:6px;color:#dbeafe"><?=md_num($warningCount);?> warning perlu monitoring</p>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 col-sm-6"><div class="md-kpi green"><i class="fa fa-shopping-bag"></i><span>Sales MTD</span><strong><?=md_money($kpi['sales_value']);?></strong><small><?=md_num($kpi['sales_order']);?> sales order</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi orange"><i class="fa fa-shopping-cart"></i><span>Purchase MTD</span><strong><?=md_money($kpi['purchase_value']);?></strong><small>Outstanding qty <?=md_num($kpi['purchase_open_qty'],2);?></small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi teal"><i class="fa fa-cubes"></i><span>Stock On Hand</span><strong><?=md_num($kpi['stock_onhand_qty'],2);?></strong><small><?=md_num($kpi['blocked_layers'] + $kpi['quality_layers']);?> blocked/QI layer</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi purple"><i class="fa fa-industry"></i><span>Production Active</span><strong><?=md_num($kpi['production_active']);?></strong><small><?=md_num($productionCompletion,2);?>% completion MTD</small><div class="progress md-progress"><div class="progress-bar progress-bar-purple" style="width:<?=min(100,max(0,$productionCompletion));?>%"></div></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-3 col-sm-6"><div class="md-kpi"><i class="fa fa-book"></i><span>Posted Journal MTD</span><strong><?=md_num($kpi['journal_count']);?></strong><small>FI documents posted</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi red"><i class="fa fa-warning"></i><span>Critical Exceptions</span><strong><?=md_num($criticalCount);?></strong><small>Wajib diselesaikan sebelum closing/go-live</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi orange"><i class="fa fa-recycle"></i><span>Scrap Rate</span><strong><?=md_num($scrapRate,2);?>%</strong><small>Scrap qty <?=md_num($kpi['production_scrap_qty'],2);?></small></div></div>
    <div class="col-md-3 col-sm-6"><div class="md-kpi"><i class="fa fa-users"></i><span>Active Employee</span><strong><?=md_num($kpi['active_employee']);?></strong><small>Total employee <?=md_num($kpi['total_employee']);?></small></div></div>
  </div>

  <div class="row">
    <div class="col-md-5">
      <div class="box md-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Critical Highlight</h3></div>
        <div class="box-body">
          <?php foreach ($alerts as $alert) { ?>
            <div class="md-alert <?=md_h($alert['severity']);?>">
              <div class="row">
                <div class="col-xs-3"><div class="md-alert-value"><?=md_num($alert['value'], is_float($alert['value']) ? 2 : 0);?></div></div>
                <div class="col-xs-9">
                  <h4><?=md_h($alert['title']);?></h4>
                  <p><?=md_h($alert['description']);?></p>
                  <a href="<?=base_index().md_h($alert['url']);?>">Open module <i class="fa fa-angle-right"></i></a>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="box md-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-line-chart"></i> Transaction Trend 14 Hari</h3></div>
        <div class="box-body"><div id="md_trend_chart" class="md-chart"></div></div>
      </div>
      <div class="box md-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-pie-chart"></i> Exception Distribution</h3></div>
        <div class="box-body"><div id="md_alert_chart" style="min-height:245px"></div></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="box md-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-heartbeat"></i> Module Health & KPI Detail</h3></div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped md-table">
            <thead><tr><th>Area</th><th>Metric</th><th class="text-right">Value</th><th>Status / Follow Up</th></tr></thead>
            <tbody>
              <?php foreach ($moduleStatus as $row) { ?>
                <tr>
                  <td><b><?=md_h($row['area']);?></b></td>
                  <td><?=md_h($row['metric']);?></td>
                  <td class="text-right"><?=md_num($row['value']);?></td>
                  <td class="md-status"><?=md_h($row['status']);?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box md-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-history"></i> Recent Activity</h3></div>
        <div class="box-body">
          <ul class="md-feed">
            <?php if (!$recentActivity) { ?>
              <li><span class="text-muted">Belum ada aktivitas terbaru.</span></li>
            <?php } ?>
            <?php foreach ($recentActivity as $activity) { ?>
              <li>
                <b><?=md_h($activity->user);?></b>
                <small><?=md_h($activity->tgl);?></small>
                <div><?=md_h($activity->deskripsi);?></div>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
var mdTrendChart = <?=json_encode($trendChart);?>;
var mdAlertChart = <?=json_encode($alertChart);?>;
$(function(){
  if (typeof Highcharts === 'undefined') return;
  Highcharts.setOptions({lang:{thousandsSep:','},colors:['#2563eb','#f59e0b','#059669','#7c3aed','#dc2626','#0f766e']});
  Highcharts.chart('md_trend_chart',{
    chart:{type:'spline'},
    title:{text:null},
    xAxis:{categories:mdTrendChart.categories||[]},
    yAxis:{title:{text:'Document Count'},allowDecimals:false},
    tooltip:{shared:true},
    series:[
      {name:'Sales Order',data:mdTrendChart.sales||[]},
      {name:'Purchase Order',data:mdTrendChart.purchasing||[]},
      {name:'Production Confirmation',data:mdTrendChart.production||[]},
      {name:'Posted Journal',data:mdTrendChart.journal||[]}
    ],
    credits:{enabled:false}
  });
  Highcharts.chart('md_alert_chart',{
    chart:{type:'pie'},
    title:{text:null},
    tooltip:{pointFormat:'<b>{point.y}</b> item'},
    plotOptions:{pie:{innerSize:'55%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},
    series:[{name:'Exception',colorByPoint:true,data:mdAlertChart||[]}],
    credits:{enabled:false}
  });
});
</script>
