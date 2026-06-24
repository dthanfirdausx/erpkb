<section class="content-header">
  <h1>Edit Role ERP</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>group-user">Group User</a></li>
    <li class="active">Edit Role</li>
  </ol>
</section>

<section class="content">
  <div class="row">
    <div class="col-lg-12">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Identitas Role</h3></div>
        <div class="box-body">
          <?php if (!$data_edit) { ?>
            <div class="alert alert-danger">Role tidak ditemukan.</div>
          <?php } elseif ($data_edit->level === 'admin') { ?>
            <div class="alert alert-warning">Role administrator sistem tidak dapat diubah.</div>
          <?php } else { ?>
            <div id="group_form_alert" class="alert alert-danger" style="display:none"></div>
            <form id="group_user_form" method="post" class="form-horizontal" action="<?=base_admin();?>modul/group_user/group_user_action.php?act=up">
              <input type="hidden" name="id" value="<?=$data_edit->id;?>">
              <div class="form-group">
                <label for="level_name" class="control-label col-lg-2">Nama Role <span class="text-red">*</span></label>
                <div class="col-lg-6">
                  <input type="text" id="level_name" name="level_name" maxlength="50" value="<?=htmlspecialchars($data_edit->level_name, ENT_QUOTES, 'UTF-8');?>" class="form-control" required>
                </div>
              </div>
              <div class="form-group">
                <label for="level" class="control-label col-lg-2">Kode Role <span class="text-red">*</span></label>
                <div class="col-lg-4">
                  <input type="text" id="level" name="level" maxlength="50" value="<?=htmlspecialchars($data_edit->level, ENT_QUOTES, 'UTF-8');?>" class="form-control" required>
                  <p class="help-block">Perubahan kode akan diterapkan juga ke seluruh permission role.</p>
                </div>
              </div>
              <div class="form-group">
                <label for="deskripsi" class="control-label col-lg-2">Deskripsi</label>
                <div class="col-lg-8">
                  <textarea id="deskripsi" name="deskripsi" maxlength="150" rows="3" class="form-control"><?=htmlspecialchars($data_edit->deskripsi, ENT_QUOTES, 'UTF-8');?></textarea>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
                  <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
                  <a href="<?=base_index();?>menu-management?user=<?=$data_edit->id;?>" class="btn btn-info"><i class="fa fa-key"></i> Kelola Hak Akses</a>
                  <a href="<?=base_index();?>group-user" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
              </div>
            </form>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
$(function () {
  $('#level').on('input', function () {
    $(this).val($(this).val().toLowerCase().replace(/[^a-z0-9_]+/g, '_'));
  });
  $('#group_user_form').on('submit', function (event) {
    event.preventDefault();
    var form = $(this);
    var button = form.find('button[type=submit]').prop('disabled', true);
    $('#group_form_alert').hide();
    $.post(form.attr('action'), form.serialize(), function (response) {
      var result = response[0] || {};
      if (result.status === 'good') {
        window.location = '<?=base_index();?>group-user';
        return;
      }
      $('#group_form_alert').text(result.error_message || 'Role gagal diperbarui.').show();
      button.prop('disabled', false);
    }, 'json').fail(function () {
      $('#group_form_alert').text('Respons server tidak valid.').show();
      button.prop('disabled', false);
    });
  });
});
</script>
