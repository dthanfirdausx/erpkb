<?php
if (!function_exists('pr_detail_t')) {
  function pr_detail_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('pr_detail_h')) {
  function pr_detail_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
?>
<section class="content-header">
  <h1><?=pr_detail_h(pr_detail_t('purchase_requisition_detail_title','Purchase Requisition Detail'));?></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=pr_detail_h(pr_detail_t('common_home','Home'));?></a></li>
    <li><a href="<?=base_index();?>pr"><?=pr_detail_h(pr_detail_t('purchase_requisition_title','Purchase Requisition'));?></a></li>
    <li class="active"><?=pr_detail_h(pr_detail_t('common_detail','Detail'));?></li>
  </ol>
</section>

<section class="content">
  <?php if (!$data_edit) { ?>
    <div class="alert alert-warning"><?=pr_detail_h(pr_detail_t('purchase_requisition_not_found','Purchase Requisition tidak ditemukan.'));?></div>
  <?php } else { ?>
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title"><?=htmlspecialchars($data_edit->no_pr,ENT_QUOTES,'UTF-8');?></h3>
      </div>
      <div class="box-body">
        <div id="detail_pr_inline"></div>
        <a href="<?=base_index();?>pr" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=pr_detail_h(pr_detail_t('common_back','Kembali'));?></a>
      </div>
    </div>
  <?php } ?>
</section>

<?php if ($data_edit) { ?>
<script>
$(function(){
  $.post('<?=base_admin();?>modul/pr/pr_action.php?act=show_detail', {id:'<?=intval($data_edit->id_pr);?>'}, function(html){
    $('#detail_pr_inline').html(html);
  });
});
</script>
<?php } ?>
