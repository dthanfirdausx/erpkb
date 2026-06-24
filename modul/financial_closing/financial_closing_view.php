<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
require_once __DIR__.'/financial_closing_helper.php';
$periods = $db->query(
    "select p.*,
            (select count(*) from erp_financial_closing_checklist c where c.period_id=p.id and c.is_required='Y') required_count,
            (select count(*) from erp_financial_closing_checklist c where c.period_id=p.id and c.is_required='Y' and c.is_completed='Y') completed_count
     from erp_financial_period p order by p.start_date desc"
);
$canInsert = isset($role_act['insert_act']) && $role_act['insert_act'] === 'Y';
?>
<section class="content-header">
  <h1><?=fin_h('finance_financial_closing', 'Financial Closing');?> <small>SAP FI Period-End Closing</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li class="active"><?=fin_h('finance_financial_closing', 'Financial Closing');?></li></ol>
</section>

<section class="content">
  <div id="financial_closing_alert" class="alert" style="display:none"></div>
  <div class="row">
    <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-unlock"></i></span><div class="info-box-content"><span class="info-box-text">Open</span><span class="info-box-number"><?=$db->query("select count(*) total from erp_financial_period where status='OPEN'")->fetch()->total;?></span></div></div></div>
    <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">Closing</span><span class="info-box-number"><?=$db->query("select count(*) total from erp_financial_period where status='CLOSING'")->fetch()->total;?></span></div></div></div>
    <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-lock"></i></span><div class="info-box-content"><span class="info-box-text"><?=fin_h('finance_closed', 'Closed');?></span><span class="info-box-number"><?=$db->query("select count(*) total from erp_financial_period where status='CLOSED'")->fetch()->total;?></span></div></div></div>
  </div>
  <div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Periode Keuangan</h3><?php if ($canInsert) { ?><div class="box-tools"><button id="add_financial_period" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Tambah Periode</button></div><?php } ?></div>
    <div class="box-body table-responsive">
      <table id="financial_period_table" class="table table-bordered table-striped">
        <thead><tr><th>Periode</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th><?=fin_h('common_status', 'Status');?></th><th>Checklist</th><th>Risk</th><th>Closed By</th><th>Aksi</th></tr></thead>
        <tbody><?php foreach ($periods as $period) { ?>
          <?php $m = financial_closing_metrics($db, $period->start_date, $period->end_date); $pct = intval($period->required_count) > 0 ? round((intval($period->completed_count) / intval($period->required_count)) * 100) : 0; ?>
          <tr>
            <td><strong><?=htmlspecialchars($period->period_code, ENT_QUOTES, 'UTF-8');?></strong><br><small class="text-muted"><?=intval($m['journal_count']);?> journal</small></td>
            <td><?=$period->start_date;?></td>
            <td><?=$period->end_date;?></td>
            <td><span class="label label-<?=$period->status==='CLOSED'?'danger':($period->status==='CLOSING'?'warning':'success');?>"><?=$period->status;?></span></td>
            <td><div class="progress progress-xs"><div class="progress-bar progress-bar-primary" style="width:<?=$pct;?>%"></div></div><small><?=intval($period->completed_count);?> / <?=intval($period->required_count);?> selesai</small></td>
            <td><?php if ($m['open_document_count'] > 0 || $m['unbalanced_count'] > 0) { ?><span class="label label-danger"><?=intval($m['open_document_count']);?> draft / <?=intval($m['unbalanced_count']);?> unbalanced</span><?php } else { ?><span class="label label-success">Clear</span><?php } ?></td>
            <td><?=htmlspecialchars($period->closed_by, ENT_QUOTES, 'UTF-8');?></td>
            <td><a href="<?=base_index();?>financial-closing/detail/<?=$period->id;?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> <?=fin_h('common_detail', 'Detail');?></a></td>
          </tr>
        <?php } ?></tbody>
      </table>
    </div>
  </div>
</section>

<?php if ($canInsert) { ?>
<div class="modal fade" id="financial_period_modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form id="financial_period_form" action="<?=base_admin();?>modul/financial_closing/financial_closing_action.php?act=create" method="post">
  <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Tambah Periode Keuangan</h4></div>
  <div class="modal-body"><div class="form-group"><label>Periode</label><input type="month" id="period_month" name="period_month" value="<?=date('Y-m');?>" class="form-control" required></div><div class="form-group"><label>Catatan</label><textarea name="notes" class="form-control" maxlength="255" rows="3"></textarea></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button></div>
</form></div></div></div>
<?php } ?>
<script>
$(function(){
  $('#financial_period_table').DataTable({order:[[1,'desc']]});
  $('#add_financial_period').on('click',function(){$('#financial_period_modal').modal('show');});
  $('#financial_period_form').on('submit',function(e){e.preventDefault();var form=$(this),button=form.find('button[type=submit]').prop('disabled',true);$.post(form.attr('action'),form.serialize(),function(response){var result=response[0]||{};if(result.status==='good'){window.location='<?=base_index();?>financial-closing/detail/'+result.id;return;}$('#financial_closing_alert').addClass('alert-danger').text(result.error_message).show();button.prop('disabled',false);$('#financial_period_modal').modal('hide');},'json').fail(function(){button.prop('disabled',false);});});
});
</script>
