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
$periodResult = $db->query(
    'select * from erp_financial_period where id=? limit 1',
    array('id' => $periodId)
);
$period = $periodResult ? $periodResult->fetch() : false;
if (!$period) {
    echo '<section class="content"><div class="alert alert-danger">Periode keuangan tidak ditemukan.</div></section>';
    return;
}
$metrics = financial_closing_metrics($db, $period->start_date, $period->end_date);
$checklists = $db->query(
    'select * from erp_financial_closing_checklist where period_id=? order by sequence_no,id',
    array('period_id' => $period->id)
);
$summary = $db->query(
    "select count(*) total,
            sum(case when is_required='Y' then 1 else 0 end) required_count,
            sum(case when is_required='Y' and is_completed='Y' then 1 else 0 end) completed_required
     from erp_financial_closing_checklist where period_id=?",
    array('period_id' => $period->id)
)->fetch();
$canUpdate = isset($role_act['up_act']) && $role_act['up_act'] === 'Y';
$allRequiredDone = intval($summary->required_count) > 0 && intval($summary->required_count) === intval($summary->completed_required);
?>
<section class="content-header">
  <h1><?=fin_h('finance_financial_closing', 'Financial Closing');?> <?=$period->period_code;?> <small><?=$period->status;?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>financial-closing"><?=fin_h('finance_financial_closing', 'Financial Closing');?></a></li><li class="active"><?=$period->period_code;?></li></ol>
</section>
<section class="content">
  <div id="closing_detail_alert" class="alert" style="display:none"></div>
  <div class="row">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-book"></i></span><div class="info-box-content"><span class="info-box-text">Jurnal</span><span class="info-box-number"><?=$metrics['journal_count'];?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon <?=$metrics['unbalanced_count']?'bg-red':'bg-green';?>"><i class="fa fa-balance-scale"></i></span><div class="info-box-content"><span class="info-box-text">Tidak Seimbang</span><span class="info-box-number"><?=$metrics['unbalanced_count'];?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-pencil-square-o"></i></span><div class="info-box-content"><span class="info-box-text">Jurnal Penyesuaian</span><span class="info-box-number"><?=$metrics['adjustment_count'];?></span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fa fa-cubes"></i></span><div class="info-box-content"><span class="info-box-text">Snapshot Stok</span><span class="info-box-number"><?=number_format($metrics['inventory_rows'],0,',','.');?></span></div></div></div>
  </div>

  <div class="row">
    <div class="col-md-8"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Closing Checklist</h3></div><div class="box-body table-responsive">
      <table class="table table-bordered table-striped"><thead><tr><th style="width:50px">Selesai</th><th>Aktivitas</th><th>Wajib</th><th>Diselesaikan Oleh</th><th>Catatan</th></tr></thead><tbody>
      <?php foreach ($checklists as $item) { ?>
        <tr><td class="text-center"><input type="checkbox" class="closing-checklist" data-id="<?=$item->id;?>" <?=$item->is_completed==='Y'?'checked':'';?> <?=(!$canUpdate || $period->status==='CLOSED')?'disabled':'';?>></td><td><?=htmlspecialchars($item->checklist_name, ENT_QUOTES, 'UTF-8');?></td><td><?=$item->is_required==='Y'?'<span class="label label-danger">Wajib</span>':'<span class="label label-default">Opsional</span>';?></td><td><?=htmlspecialchars($item->completed_by, ENT_QUOTES, 'UTF-8');?><br><small><?=$item->completed_at;?></small></td><td><input type="text" class="form-control input-sm checklist-note" data-id="<?=$item->id;?>" value="<?=htmlspecialchars($item->notes, ENT_QUOTES, 'UTF-8');?>" <?=(!$canUpdate || $period->status==='CLOSED')?'disabled':'';?>></td></tr>
      <?php } ?>
      </tbody></table>
    </div></div></div>
    <div class="col-md-4"><div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Kontrol Periode</h3></div><div class="box-body">
      <dl><dt>Periode</dt><dd><?=$period->start_date;?> s.d. <?=$period->end_date;?></dd><dt><?=fin_h('common_status', 'Status');?></dt><dd><span class="label label-<?=$period->status==='CLOSED'?'danger':($period->status==='CLOSING'?'warning':'success');?>"><?=$period->status;?></span></dd><dt>Checklist Wajib</dt><dd><?=intval($summary->completed_required);?> / <?=intval($summary->required_count);?></dd><dt>Total Debet</dt><dd>Rp <?=number_format($metrics['total_debet'],2,',','.');?></dd><dt>Total Kredit</dt><dd>Rp <?=number_format($metrics['total_kredit'],2,',','.');?></dd><dt>Catatan</dt><dd><?=nl2br(htmlspecialchars($period->notes, ENT_QUOTES, 'UTF-8'));?></dd></dl>
      <?php if ($canUpdate && $period->status !== 'CLOSED') { ?><button class="btn btn-warning btn-block closing-action" data-act="start_closing" <?=$period->status==='CLOSING'?'disabled':'';?>><i class="fa fa-hourglass-half"></i> Mulai Closing</button><button class="btn btn-danger btn-block closing-action" data-act="close" <?=$period->status==='CLOSING' && $allRequiredDone && !$metrics['unbalanced_count']?'':'disabled';?>><i class="fa fa-lock"></i> Tutup Periode</button><?php } ?>
      <?php if ($canUpdate && $period->status === 'CLOSED') { ?><button class="btn btn-success btn-block closing-action" data-act="reopen"><i class="fa fa-unlock"></i> Buka Kembali Periode</button><?php } ?>
      <a href="<?=base_index();?>financial-closing" class="btn btn-default btn-block"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div></div></div>
  </div>
</section>
<script>
$(function(){
  function send(data){$.post('<?=base_admin();?>modul/financial_closing/financial_closing_action.php',data,function(response){var result=response[0]||{};if(result.status==='good'){location.reload();return;}$('#closing_detail_alert').removeClass('alert-success').addClass('alert-danger').text(result.error_message).show();},'json').fail(function(){$('#closing_detail_alert').addClass('alert-danger').text('Respons server tidak valid.').show();});}
  $('.closing-checklist').on('change',function(){send({act:'checklist',id:$(this).data('id'),completed:this.checked?'Y':'N',notes:$('.checklist-note[data-id='+$(this).data('id')+']').val()});});
  $('.checklist-note').on('change',function(){var checkbox=$('.closing-checklist[data-id='+$(this).data('id')+']');send({act:'checklist',id:$(this).data('id'),completed:checkbox.is(':checked')?'Y':'N',notes:$(this).val()});});
  $('.closing-action').on('click',function(){var action=$(this).data('act'),reason='';if(action==='reopen'){reason=window.prompt('Masukkan alasan membuka kembali periode:','');if(!reason){return;}}send({act:action,period_id:<?=$period->id;?>,reason:reason});});
});
</script>
