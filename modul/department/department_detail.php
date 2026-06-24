<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$code = isset($data_edit->kd_dept) ? $data_edit->kd_dept : uri_segment(3);
$detail = $db->fetch("SELECT d.*,pd.nm_dept parent_name,cs.structure_code,cs.structure_name,cs.structure_type,cc.cost_center_name,pc.profit_center_name,u.username manager_username,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) manager_name
  FROM dept d
  LEFT JOIN dept pd ON pd.kd_dept=d.parent_dept_code
  LEFT JOIN erp_company_structure cs ON cs.id=d.company_structure_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=d.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=d.profit_center_code
  LEFT JOIN sys_users u ON u.id=d.manager_user_id
  WHERE d.kd_dept=? LIMIT 1", array($code));
function dd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<section class="content-header">
  <h1>Department Detail</h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li><a href="<?=base_index();?>hrd-department"><?=hr_h('hr_department', 'Department');?></a></li><li class="active"><?=hr_h('common_detail', 'Detail');?></li></ol>
</section>
<section class="content">
<?php if(!$detail){ ?>
  <div class="alert alert-warning">Department tidak ditemukan.</div>
<?php }else{ ?>
  <div class="box">
    <div class="box-header"><h3 class="box-title"><?=dd_h($detail->kd_dept);?> - <?=dd_h($detail->nm_dept);?></h3></div>
    <div class="box-body">
      <div class="row">
        <div class="col-md-3"><strong>Type</strong><br><?=dd_h($detail->dept_type);?></div>
        <div class="col-md-3"><strong><?=hr_h('common_status', 'Status');?></strong><br><?=dd_h($detail->status);?></div>
        <div class="col-md-3"><strong>Parent</strong><br><?=dd_h($detail->parent_dept_code?($detail->parent_dept_code.' - '.$detail->parent_name):'Root');?></div>
        <div class="col-md-3"><strong>Validity</strong><br><?=dd_h($detail->valid_from.' s/d '.$detail->valid_to);?></div>
      </div><hr>
      <div class="row">
        <div class="col-md-4"><strong>Org Assignment</strong><br><?=dd_h($detail->structure_code?($detail->structure_code.' - '.$detail->structure_name.' ['.$detail->structure_type.']'):'-');?></div>
        <div class="col-md-4"><strong>Cost Center</strong><br><?=dd_h($detail->cost_center_code?:'-');?> <small><?=dd_h($detail->cost_center_name?:'');?></small></div>
        <div class="col-md-4"><strong>Profit Center</strong><br><?=dd_h($detail->profit_center_code?:'-');?> <small><?=dd_h($detail->profit_center_name?:'');?></small></div>
      </div><hr>
      <div class="row">
        <div class="col-md-4"><strong><?=hr_h('hr_manager', 'Manager');?></strong><br><?=dd_h($detail->manager_username?:'-');?> <small><?=dd_h(trim($detail->manager_name)?:'');?></small></div>
        <div class="col-md-4"><strong>Functional Area</strong><br><?=dd_h($detail->functional_area?:'-');?></div>
        <div class="col-md-4"><strong>SAP Reference</strong><br><?=dd_h($detail->sap_reference?:'-');?></div>
      </div><hr>
      <strong><?=hr_h('common_remarks', 'Remarks');?></strong><p><?=nl2br(dd_h($detail->remarks?:'-'));?></p>
      <a href="<?=base_index();?>hrd-department" class="btn btn-success"><i class="fa fa-step-backward"></i> Back</a>
    </div>
  </div>
<?php } ?>
</section>
