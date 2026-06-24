<?php
if (!function_exists('mdt_h')) {
  function mdt_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('mdt_t')) {
  function mdt_t($key, $fallback) { return function_exists('erp_t') ? erp_t($key, $fallback) : $fallback; }
}
$mdtUrl = function_exists('uri_segment') ? uri_segment(1) : '';
$mdtMap = array(
  'barang' => array('title_key'=>'master_material_master','title'=>'Material Master','desc_key'=>'master_material_master_desc','desc'=>'Manage material code, material type, material group, UOM, plant, and active material status.','icon'=>'fa-cubes','color'=>'#0f766e'),
  'pemasok' => array('title_key'=>'master_vendor_master','title'=>'Vendor Master','desc_key'=>'master_vendor_master_desc','desc'=>'Manage supplier/vendor, tax ID, address, country, contact, and active purchasing partner status.','icon'=>'fa-truck','color'=>'#1d4ed8'),
  'satuan' => array('title_key'=>'master_uom','title'=>'Unit of Measure','desc_key'=>'master_uom_desc','desc'=>'Manage material base units so GR, GI, production, and reports use consistent references.','icon'=>'fa-balance-scale','color'=>'#7c3aed'),
  'satuan-packing' => array('title_key'=>'master_packing_unit','title'=>'Packing Unit','desc_key'=>'master_packing_unit_desc','desc'=>'Manage packing units for customs documents, goods receipt, and goods issue.','icon'=>'fa-archive','color'=>'#b45309'),
  'kategori-barang' => array('title_key'=>'master_material_category','title'=>'Material Category','desc_key'=>'master_material_category_desc','desc'=>'Manage material categories for inventory reports, accounting mapping, and customs reporting.','icon'=>'fa-tags','color'=>'#0f766e'),
  'bc-masuk' => array('title_key'=>'master_inbound_customs_type','title'=>'Inbound Customs Type','desc_key'=>'master_inbound_customs_type_desc','desc'=>'Manage inbound BC document types used in goods receipt and bonded zone reports.','icon'=>'fa-sign-in','color'=>'#0891b2'),
  'bc-keluar' => array('title_key'=>'master_outbound_customs_type','title'=>'Outbound Customs Type','desc_key'=>'master_outbound_customs_type_desc','desc'=>'Manage outbound BC document types used in goods issue and bonded zone reports.','icon'=>'fa-sign-out','color'=>'#dc2626'),
  'data-user' => array('title_key'=>'master_user_master','title'=>'User Master','desc_key'=>'master_user_master_desc','desc'=>'Manage login users, user groups, active status, and operational access.','icon'=>'fa-user','color'=>'#2563eb'),
  'group-user' => array('title_key'=>'master_role_master','title'=>'Role Master','desc_key'=>'master_role_master_desc','desc'=>'Manage ERP roles, permission templates, and menu access scope for every user group.','icon'=>'fa-users','color'=>'#7c3aed'),
  'menu-management' => array('title_key'=>'master_role_authorization','title'=>'Role Authorization','desc_key'=>'master_role_authorization_desc','desc'=>'Manage menu authorization per role: read, input, edit, delete, and import.','icon'=>'fa-key','color'=>'#334155')
);
$mdt = isset($mdtMap[$mdtUrl]) ? $mdtMap[$mdtUrl] : array('title_key'=>'module_master_data','title'=>'Master Data','desc_key'=>'master_default_desc','desc'=>'Manage ERP master data references.','icon'=>'fa-database','color'=>'#334155');
$mdtActionsHtml = isset($mdtActionsHtml) ? trim((string)$mdtActionsHtml) : '';
$mdtTitle = mdt_t($mdt['title_key'], $mdt['title']);
$mdtDesc = mdt_t($mdt['desc_key'], $mdt['desc']);
?>
<style>
.mdt-hero{background:linear-gradient(135deg,<?=$mdt['color'];?>,#1f2937);color:#fff;border-radius:14px;padding:19px 22px;margin-bottom:16px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.mdt-hero h1{margin:0 0 6px;font-size:25px;font-weight:800}.mdt-hero p{margin:0;opacity:.9}
.mdt-actions{display:flex;gap:8px;justify-content:flex-end;align-items:center;flex-wrap:wrap}
.mdt-actions .btn{border:0;border-radius:8px;font-weight:700;box-shadow:0 8px 18px rgba(15,23,42,.12)}
.mdt-actions .btn-default{background:rgba(255,255,255,.92);color:#334155}
.mdt-actions .label{align-self:center;margin-top:7px}
.mdt-filter{border-radius:12px;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.mdt-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.select2-container{width:100%!important}
@media(max-width:991px){.mdt-actions{justify-content:flex-start;margin-top:12px}}
</style>
<div class="mdt-hero">
  <div class="row">
    <div class="<?=$mdtActionsHtml !== '' ? 'col-md-7' : 'col-md-9';?>">
      <h1><i class="fa <?=$mdt['icon'];?>"></i> <?=mdt_h($mdtTitle);?></h1>
      <p><?=mdt_h($mdtDesc);?></p>
    </div>
    <div class="<?=$mdtActionsHtml !== '' ? 'col-md-5' : 'col-md-3 text-right';?>">
      <?php if ($mdtActionsHtml !== '') { ?>
        <div class="mdt-actions"><?=$mdtActionsHtml;?></div>
      <?php } else { ?>
        <span class="label label-primary"><?=mdt_h(mdt_t('module_master_data', 'Master Data'));?></span>
      <?php } ?>
    </div>
  </div>
</div>
<div class="box mdt-filter">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=mdt_h(mdt_t('common_filter', 'Filter'));?> <?=mdt_h(mdt_t('module_master_data', 'Master Data'));?></h3></div>
  <div class="box-body">
    <form class="form-horizontal" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-md-2"><?=mdt_h(mdt_t('common_search', 'Search'));?></label>
        <div class="col-md-5"><input type="text" class="form-control master-data-search" placeholder="<?=mdt_h(mdt_t('master_search_placeholder', 'Search code, name, status, or master data'));?>"></div>
        <label class="control-label col-md-2"><?=mdt_h(mdt_t('master_scope', 'Scope'));?></label>
        <div class="col-md-2"><select class="form-control master-data-scope select2"><option value=""><?=mdt_h(mdt_t('master_all_data', 'All Data'));?></option><option value="aktif"><?=mdt_h(mdt_t('master_term_aktif', 'Active'));?></option><option value="nonaktif"><?=mdt_h(mdt_t('master_term_nonaktif', 'Inactive'));?></option></select></div>
        <div class="col-md-1"><button type="button" class="btn btn-default master-data-reset"><i class="fa fa-refresh"></i></button></div>
      </div>
    </form>
  </div>
</div>
<script>
$(function(){
  if($.fn.select2){$('.master-data-scope').select2({width:'100%',allowClear:true});}
  function mdtTable(){var t=$('table[id^="dtb_"]').first();return t.length&&$.fn.DataTable&&$.fn.DataTable.isDataTable(t)?t.DataTable():null;}
  $(document).on('keyup','.master-data-search',function(){var dt=mdtTable();if(dt)dt.search($(this).val()).draw();});
  $(document).on('click','.master-data-reset',function(){$('.master-data-search').val('');$('.master-data-scope').val('').trigger('change');var dt=mdtTable();if(dt)dt.search('').draw();});
});
</script>
