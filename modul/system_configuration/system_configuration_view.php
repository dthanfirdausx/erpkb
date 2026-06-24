<?php
function sc_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sc_count_table($db, $table) {
  $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
  $row = $db->fetch("SELECT COUNT(*) total FROM ".$safe);
  return $row ? (int)$row->total : 0;
}
function sc_config_value($row) {
  return $row->config_value !== null && $row->config_value !== '' ? $row->config_value : $row->default_value;
}

$canUpdate = isset($role_act['up_act']) && $role_act['up_act'] === 'Y';
$groupMeta = array(
  'COMPANY_KB' => array('label'=>'Company & KB', 'icon'=>'fa-building', 'color'=>'#0f766e', 'desc'=>'Identitas perusahaan, plant, currency, fiscal, dan fasilitas KB.'),
  'NUMBER_FORMAT' => array('label'=>'Number Format', 'icon'=>'fa-calculator', 'color'=>'#0891b2', 'desc'=>'Format angka global untuk ribuan, decimal, quantity, nilai uang, PDF, dan export Excel.'),
  'DOCUMENT_NUMBERING' => array('label'=>'Document Numbering', 'icon'=>'fa-sort-numeric-asc', 'color'=>'#1d4ed8', 'desc'=>'Format nomor dokumen lintas PR, PO, GR, GI, FI, dan produksi.'),
  'POSTING_RULES' => array('label'=>'Posting Rules', 'icon'=>'fa-balance-scale', 'color'=>'#7c3aed', 'desc'=>'Kontrol auto journal dan akun default.'),
  'INVENTORY_RULES' => array('label'=>'Inventory Rules', 'icon'=>'fa-cubes', 'color'=>'#b45309', 'desc'=>'Aturan stok minus, batch/lot, dokumen BC, dan stock type.'),
  'CUSTOMS_CEISA' => array('label'=>'Customs & CEISA', 'icon'=>'fa-cloud-upload', 'color'=>'#0369a1', 'desc'=>'Parameter CEISA 4.0 dan default dokumen pabean.'),
  'APPROVAL_WORKFLOW' => array('label'=>'Approval Workflow', 'icon'=>'fa-check-square-o', 'color'=>'#15803d', 'desc'=>'Aturan kebutuhan approval dan eskalasi.'),
  'INTEGRATION' => array('label'=>'Integration', 'icon'=>'fa-plug', 'color'=>'#334155', 'desc'=>'SMTP, attendance machine, FTP, dan integrasi teknis.'),
  'SECURITY_AUDIT' => array('label'=>'Security & Audit', 'icon'=>'fa-shield', 'color'=>'#be123c', 'desc'=>'Audit log, login-as, session, dan password policy.')
);

$configs = array();
foreach ($db->query("SELECT * FROM erp_system_config ORDER BY config_group, sort_order, config_key") as $row) {
  if (!isset($configs[$row->config_group])) $configs[$row->config_group] = array();
  $configs[$row->config_group][] = $row;
}
$totalConfig = 0;
foreach ($configs as $items) $totalConfig += count($items);
$updatedRows = $db->fetch("SELECT COUNT(*) total FROM erp_system_config WHERE updated_at IS NOT NULL");
$sensitiveRows = $db->fetch("SELECT COUNT(*) total FROM erp_system_config WHERE is_sensitive='Y'");
$readiness = array(
  array('label'=>'Company Profile', 'count'=>sc_count_table($db, 'infokb'), 'url'=>'info-kb', 'icon'=>'fa-building'),
  array('label'=>'Plants', 'count'=>sc_count_table($db, 'erp_plant'), 'url'=>'plant', 'icon'=>'fa-industry'),
  array('label'=>'COA', 'count'=>sc_count_table($db, 'rekening'), 'url'=>'coa', 'icon'=>'fa-list-alt'),
  array('label'=>'Fiscal Period', 'count'=>sc_count_table($db, 'erp_financial_period'), 'url'=>'fiscal-period', 'icon'=>'fa-calendar'),
  array('label'=>'BC Masuk', 'count'=>sc_count_table($db, 'jenisbcmasuk'), 'url'=>'bc-masuk', 'icon'=>'fa-sign-in'),
  array('label'=>'BC Keluar', 'count'=>sc_count_table($db, 'jenisbckeluar'), 'url'=>'bc-keluar', 'icon'=>'fa-sign-out')
);
?>
<style>
.sc-hero{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 12px 28px rgba(15,23,42,.2)}
.sc-hero h1{margin:0 0 7px;font-size:28px;font-weight:800}.sc-hero p{margin:0;color:#dbeafe}.sc-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.sc-actions .btn{border:0;border-radius:9px;font-weight:700;box-shadow:0 8px 18px rgba(15,23,42,.18)}
.sc-card{border:1px solid #e5edf5;border-radius:14px;background:#fff;box-shadow:0 7px 20px rgba(15,23,42,.055);margin-bottom:16px}.sc-card .box-header{border-bottom:1px solid #edf2f7;padding:14px 16px}.sc-card .box-title{font-weight:800;color:#0f172a}
.sc-kpi{border:1px solid #e5edf5;border-radius:14px;background:#fff;padding:15px;min-height:105px;margin-bottom:16px;box-shadow:0 6px 16px rgba(15,23,42,.045)}.sc-kpi i{width:38px;height:38px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;background:#0f766e;margin-bottom:9px}.sc-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase}.sc-kpi strong{display:block;font-size:23px;color:#0f172a;line-height:1.25}
.sc-tabs>li>a{font-weight:700;color:#334155}.sc-tab-pane{padding-top:15px}.sc-field{border:1px solid #e5edf5;border-radius:12px;padding:13px;margin-bottom:13px;background:#fff}.sc-field label{font-size:12px;text-transform:uppercase;letter-spacing:.03em;color:#475569}.sc-field small{display:block;color:#64748b;margin-top:6px}.sc-required{color:#dc2626}.sc-group-title{padding:13px 15px;border-radius:12px;color:#fff;margin-bottom:14px}.sc-group-title h3{margin:0 0 4px;font-size:18px}.sc-group-title p{margin:0;opacity:.9}.sc-readiness a{color:#0f172a}.sc-readiness .label{font-size:11px}.select2-container{width:100%!important}@media(max-width:991px){.sc-actions{justify-content:flex-start;margin-top:12px}}
.sc-number-preview{border:1px dashed #93c5fd;border-radius:12px;background:#eff6ff;padding:14px;margin-bottom:14px}.sc-number-preview h4{margin:0 0 8px;font-weight:800;color:#0f172a}.sc-number-preview code{display:inline-block;background:#fff;border:1px solid #bfdbfe;border-radius:8px;padding:8px 10px;font-size:15px;color:#0f172a}
</style>
<section class="content-header">
  <h1>System Configuration <small>ERP KB Control Panel</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li>Management System</li><li class="active">System Configuration</li></ol>
</section>
<section class="content">
  <div class="sc-hero">
    <div class="row">
      <div class="col-md-7">
        <h1><i class="fa fa-sliders"></i> System Configuration</h1>
        <p>Panel kontrol ERP KB untuk company profile, nomor dokumen, posting jurnal, inventory, CEISA, workflow approval, integrasi, dan security policy.</p>
      </div>
      <div class="col-md-5 sc-actions">
        <?php if ($canUpdate) { ?>
          <button type="button" id="btn_save_sc" class="btn btn-warning"><i class="fa fa-save"></i> Save Configuration</button>
          <button type="button" id="btn_reset_group_sc" class="btn btn-default"><i class="fa fa-refresh"></i> Reset Current Tab</button>
        <?php } ?>
        <a href="<?=base_index();?>log-aktifitas" class="btn btn-info"><i class="fa fa-history"></i> Audit Log</a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3"><div class="sc-kpi"><i class="fa fa-cogs"></i><span>Total Parameter</span><strong><?=number_format($totalConfig,0,',','.');?></strong><small>Aktif di control panel</small></div></div>
    <div class="col-md-3"><div class="sc-kpi"><i class="fa fa-check"></i><span>Updated</span><strong><?=number_format($updatedRows ? $updatedRows->total : 0,0,',','.');?></strong><small>Parameter sudah punya timestamp</small></div></div>
    <div class="col-md-3"><div class="sc-kpi"><i class="fa fa-lock"></i><span>Sensitive</span><strong><?=number_format($sensitiveRows ? $sensitiveRows->total : 0,0,',','.');?></strong><small>Password/API secret</small></div></div>
    <div class="col-md-3"><div class="sc-kpi"><i class="fa fa-user"></i><span>Role Access</span><strong><?=sc_h(isset($_SESSION['group_level']) ? $_SESSION['group_level'] : '-');?></strong><small><?=($canUpdate ? 'Update allowed' : 'Read only');?></small></div></div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="box sc-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-wrench"></i> Configuration Parameters</h3></div>
        <div class="box-body">
          <div id="sc_alert" class="alert" style="display:none"></div>
          <form id="form_system_configuration">
            <ul class="nav nav-tabs sc-tabs">
              <?php $first = true; foreach ($groupMeta as $group => $meta) { if (!isset($configs[$group])) continue; ?>
                <li class="<?=$first ? 'active' : '';?>"><a href="#tab_<?=sc_h($group);?>" data-toggle="tab" data-group="<?=sc_h($group);?>"><i class="fa <?=sc_h($meta['icon']);?>"></i> <?=sc_h($meta['label']);?></a></li>
              <?php $first = false; } ?>
            </ul>
            <div class="tab-content">
              <?php $first = true; foreach ($groupMeta as $group => $meta) { if (!isset($configs[$group])) continue; ?>
                <div class="tab-pane sc-tab-pane <?=$first ? 'active' : '';?>" id="tab_<?=sc_h($group);?>">
                  <div class="sc-group-title" style="background:linear-gradient(135deg,<?=sc_h($meta['color']);?>,#1f2937)">
                    <h3><i class="fa <?=sc_h($meta['icon']);?>"></i> <?=sc_h($meta['label']);?></h3>
                    <p><?=sc_h($meta['desc']);?></p>
                  </div>
                  <?php if ($group === 'NUMBER_FORMAT') { ?>
                    <div class="sc-number-preview">
                      <h4><i class="fa fa-eye"></i> Preview Format Angka</h4>
                      <div class="row">
                        <div class="col-sm-4"><small>Money / Amount</small><br><code id="sc_preview_money">1.250.000,50</code></div>
                        <div class="col-sm-4"><small>Quantity</small><br><code id="sc_preview_qty">12.345,67890</code></div>
                        <div class="col-sm-4"><small>Negative</small><br><code id="sc_preview_negative">-1.250.000,50</code></div>
                      </div>
                    </div>
                  <?php } ?>
                  <div class="row">
                    <?php foreach ($configs[$group] as $row) {
                      $value = sc_config_value($row);
                      $type = strtoupper((string)$row->value_type);
                      $disabled = $canUpdate ? '' : ' disabled';
                    ?>
                      <div class="col-md-6">
                        <div class="sc-field">
                          <label><?=sc_h($row->config_label);?> <?php if($row->is_sensitive==='Y'){ ?><span class="label label-danger">Sensitive</span><?php } ?></label>
                          <?php if ($type === 'BOOLEAN') { ?>
                            <select name="config[<?=sc_h($row->config_key);?>]" class="form-control select2-sc"<?=$disabled;?>>
                              <option value="Y" <?=$value==='Y'?'selected':'';?>>Y - Ya / Aktif</option>
                              <option value="N" <?=$value==='N'?'selected':'';?>>N - Tidak / Nonaktif</option>
                            </select>
                          <?php } elseif ($type === 'SELECT') {
                            $options = json_decode((string)$row->options_json, true);
                            if (!is_array($options)) $options = array();
                          ?>
                            <select name="config[<?=sc_h($row->config_key);?>]" class="form-control select2-sc sc-config-input" data-key="<?=sc_h($row->config_key);?>"<?=$disabled;?>>
                              <?php foreach ($options as $option) {
                                $optionLabel = $option;
                                if ($option === 'SPACE') $optionLabel = 'Space';
                                elseif ($option === 'NONE') $optionLabel = 'Tanpa Separator';
                                elseif ($option === 'MINUS') $optionLabel = '-1.000,00';
                                elseif ($option === 'PARENTHESES') $optionLabel = '(1.000,00)';
                              ?><option value="<?=sc_h($option);?>" <?=$value===$option?'selected':'';?>><?=sc_h($optionLabel);?></option><?php } ?>
                            </select>
                          <?php } else {
                            $inputType = 'text';
                            if ($type === 'NUMBER' || $type === 'DECIMAL') $inputType = 'number';
                            if ($type === 'PASSWORD') $inputType = 'password';
                            if ($type === 'DATE') $inputType = 'date';
                            if ($type === 'EMAIL') $inputType = 'email';
                            if ($type === 'URL') $inputType = 'url';
                            $step = $type === 'DECIMAL' ? ' step="0.0001"' : '';
                            $showValue = $row->is_sensitive === 'Y' ? '' : $value;
                          ?>
                            <input type="<?=$inputType;?>"<?=$step;?> name="config[<?=sc_h($row->config_key);?>]" class="form-control sc-config-input" data-key="<?=sc_h($row->config_key);?>" value="<?=sc_h($showValue);?>" placeholder="<?=$row->is_sensitive==='Y' ? 'Kosongkan jika tidak ingin mengubah nilai sensitif' : '';?>"<?=$disabled;?>>
                          <?php } ?>
                          <small><?=sc_h($row->description);?> <b>Key:</b> <code><?=sc_h($row->config_key);?></code></small>
                        </div>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              <?php $first = false; } ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="box sc-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-check-circle"></i> Master Readiness</h3></div>
        <div class="box-body sc-readiness">
          <div class="list-group">
            <?php foreach ($readiness as $item) { ?>
              <a class="list-group-item" href="<?=base_index().$item['url'];?>">
                <i class="fa <?=sc_h($item['icon']);?>"></i> <?=sc_h($item['label']);?>
                <span class="pull-right label label-<?=$item['count'] > 0 ? 'success' : 'danger';?>"><?=number_format($item['count'],0,',','.');?></span>
              </a>
            <?php } ?>
          </div>
          <div class="alert alert-info" style="margin-bottom:0">
            <b>Catatan:</b> konfigurasi hanya menyimpan parameter. Master detail tetap dikelola dari menu master terkait agar audit dan validasinya jelas.
          </div>
        </div>
      </div>

      <div class="box sc-card">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-external-link"></i> Quick Links</h3></div>
        <div class="box-body">
          <a class="btn btn-app" href="<?=base_index();?>info-kb"><i class="fa fa-building"></i> KB Profile</a>
          <a class="btn btn-app" href="<?=base_index();?>coa"><i class="fa fa-list-alt"></i> COA</a>
          <a class="btn btn-app" href="<?=base_index();?>financial-closing"><i class="fa fa-calendar-check-o"></i> Closing</a>
          <a class="btn btn-app" href="<?=base_index();?>dokumen-pabean"><i class="fa fa-cloud-upload"></i> CEISA</a>
          <a class="btn btn-app" href="<?=base_index();?>menu-management"><i class="fa fa-key"></i> Role</a>
          <a class="btn btn-app" href="<?=base_index();?>log-aktifitas"><i class="fa fa-history"></i> Log</a>
        </div>
      </div>
    </div>
  </div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function(){
  if($.fn.select2){$('.select2-sc').select2({width:'100%',allowClear:false});}
  function scCurrentGroup(){return $('.sc-tabs li.active a').data('group') || 'COMPANY_KB';}
  function scAlert(type,msg){$('#sc_alert').removeClass('alert-success alert-danger alert-info').addClass('alert-'+type).html(msg).show();$('html,body').animate({scrollTop:$('#sc_alert').offset().top-90},250);}
  function scCfg(key){
    var el=$('[data-key="'+key+'"]');
    return el.length ? el.val() : '';
  }
  function scSeparator(value){
    if(value==='SPACE') return ' ';
    if(value==='NONE') return '';
    return value || '';
  }
  function scFormatNumber(value, decimals){
    var thousand=scSeparator(scCfg('number_thousand_separator') || '.');
    var decimal=scSeparator(scCfg('number_decimal_separator') || ',') || ',';
    var negativeFormat=scCfg('number_negative_format') || 'MINUS';
    decimals=parseInt(decimals,10);
    if(isNaN(decimals) || decimals<0) decimals=2;
    var negative=value<0;
    var fixed=Math.abs(value).toFixed(decimals).split('.');
    var whole=fixed[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
    var result=whole+(decimals>0?decimal+fixed[1]:'');
    if(negative) result=negativeFormat==='PARENTHESES'?'('+result+')':'-'+result;
    return result;
  }
  function scRefreshNumberPreview(){
    if(!$('#sc_preview_money').length) return;
    var amountDec=parseInt(scCfg('number_decimal_precision') || '2',10);
    var qtyDec=parseInt(scCfg('number_qty_precision') || '5',10);
    $('#sc_preview_money').text(scFormatNumber(1250000.5, amountDec));
    $('#sc_preview_qty').text(scFormatNumber(12345.6789, qtyDec));
    $('#sc_preview_negative').text(scFormatNumber(-1250000.5, amountDec));
  }
  $(document).on('change keyup','.sc-config-input',scRefreshNumberPreview);
  scRefreshNumberPreview();
  $('#btn_save_sc').on('click',function(){
    var btn=$(this);btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    $.ajax({url:'<?=base_admin();?>modul/system_configuration/system_configuration_action.php?act=save',type:'POST',data:$('#form_system_configuration').serialize(),dataType:'json'})
      .done(function(res){ if(res.status==='good'){scAlert('success',res.message||'System Configuration tersimpan.');} else {scAlert('danger',res.error_message||'Gagal menyimpan konfigurasi.');} })
      .fail(function(xhr){scAlert('danger',xhr.responseText||'Gagal menyimpan konfigurasi.');})
      .always(function(){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Configuration');});
  });
  $('#btn_reset_group_sc').on('click',function(){
    var group=scCurrentGroup();
    Swal.fire({title:'Reset tab ini ke default?',text:'Group '+group+' akan dikembalikan ke nilai default.',icon:'warning',showCancelButton:true,confirmButtonText:'Reset'}).then(function(r){
      if(!r.isConfirmed)return;
      $.post('<?=base_admin();?>modul/system_configuration/system_configuration_action.php?act=reset_group',{group:group},function(res){
        if(res.status==='good'){Swal.fire('Berhasil',res.message,'success').then(function(){location.reload();});}
        else Swal.fire('Gagal',res.error_message||'Reset gagal.','error');
      },'json');
    });
  });
});
</script>
