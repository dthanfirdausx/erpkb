<?php
require_once __DIR__.'/bootstrap.php';
$record = isset($erpCrudRecord) ? $erpCrudRecord : null;
$formMode = $record ? 'update' : 'insert';
?>
<form class="form-horizontal erp-crud-form" method="post" action="<?=base_admin();?>modul/<?=$erpCrudFolder;?>/<?=$erpCrudFolder;?>_action.php">
  <div class="alert alert-danger erp-crud-form-error" style="display:none"></div>
  <input type="hidden" name="record_id" value="<?=$record ? htmlspecialchars($record->{$erpCrudConfig['primary']}, ENT_QUOTES, 'UTF-8') : '';?>">
  <?php foreach ($erpCrudConfig['fields'] as $field => $settings) { $value = $record ? $record->{$field} : ''; ?>
    <div class="form-group">
      <label class="control-label col-md-3"><?=htmlspecialchars(erp_master_field_label($settings), ENT_QUOTES, 'UTF-8');?><?=!empty($settings['required']) ? ' <span class="text-red">*</span>' : '';?></label>
      <div class="col-md-9">
        <?php if (isset($settings['type']) && in_array($settings['type'], array('select', 'db_select'), true)) { $options = erp_crud_options($db, $settings); ?>
          <select name="<?=$field;?>" class="form-control" <?=!empty($settings['required']) ? 'required' : '';?>>
            <option value=""><?=htmlspecialchars(erp_t('select2_placeholder', 'Select data'), ENT_QUOTES, 'UTF-8');?></option>
            <?php foreach ($options as $optionValue => $optionLabel) { ?>
              <option value="<?=htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8');?>" <?=(string) $value === (string) $optionValue ? 'selected' : '';?>><?=htmlspecialchars(isset($settings['type']) && $settings['type'] === 'select' ? erp_master_text($optionLabel) : $optionLabel, ENT_QUOTES, 'UTF-8');?></option>
            <?php } ?>
          </select>
        <?php } else { ?>
          <input name="<?=$field;?>" type="<?=isset($settings['type']) ? $settings['type'] : 'text';?>" value="<?=htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');?>" class="form-control" <?=isset($settings['maxlength']) ? 'maxlength="'.intval($settings['maxlength']).'"' : '';?> <?=isset($settings['step']) ? 'step="'.htmlspecialchars($settings['step'], ENT_QUOTES, 'UTF-8').'"' : '';?> <?=!empty($settings['required']) ? 'required' : '';?>>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
  <div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?=htmlspecialchars(erp_t('common_cancel', 'Cancel'), ENT_QUOTES, 'UTF-8');?></button>
    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=htmlspecialchars(erp_t('common_save', 'Save'), ENT_QUOTES, 'UTF-8');?></button>
  </div>
</form>
<script>
$('.erp-crud-form').on('submit', function (event) {
  event.preventDefault();
  var form = $(this), button = form.find('button[type=submit]').prop('disabled', true);
  $.post(form.attr('action'), form.serialize(), function (response) {
    var result = response[0] || {};
    if (result.status === 'good') { location.reload(); return; }
    form.find('.erp-crud-form-error').text(result.error_message || ((window.ERPKB_LANG && ERPKB_LANG.common_process_failed) || 'Process failed.')).show();
    button.prop('disabled', false);
  }, 'json').fail(function () {
    form.find('.erp-crud-form-error').text((window.ERPKB_LANG && ERPKB_LANG.validation_remote) || 'Please fix this field.').show();
    button.prop('disabled', false);
  });
});
</script>
