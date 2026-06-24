<section class="content-header">
  <h1>Tambah Role ERP</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>group-user">Group User</a></li>
    <li class="active">Tambah Role</li>
  </ol>
</section>

<section class="content">
  <div class="row">
    <div class="col-lg-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Identitas dan Hak Akses Awal</h3>
        </div>
        <div class="box-body">
          <div id="group_form_alert" class="alert alert-danger" style="display:none"></div>
          <form id="group_user_form" method="post" class="form-horizontal" action="<?=base_admin();?>modul/group_user/group_user_action.php?act=in">
            <div class="form-group">
              <label for="level_name" class="control-label col-lg-2">Nama Role <span class="text-red">*</span></label>
              <div class="col-lg-6">
                <input type="text" id="level_name" name="level_name" maxlength="50" placeholder="Contoh: Supervisor Gudang" class="form-control" required>
                <p class="help-block">Nama yang tampil pada pilihan user dan halaman pengaturan.</p>
              </div>
            </div>
            <div class="form-group">
              <label for="level" class="control-label col-lg-2">Kode Role <span class="text-red">*</span></label>
              <div class="col-lg-4">
                <input type="text" id="level" name="level" maxlength="50" placeholder="supervisor_gudang" class="form-control" required>
                <p class="help-block">Huruf kecil, angka, dan garis bawah. Digunakan oleh sistem permission.</p>
              </div>
            </div>
            <div class="form-group">
              <label for="deskripsi" class="control-label col-lg-2">Deskripsi</label>
              <div class="col-lg-8">
                <textarea id="deskripsi" name="deskripsi" maxlength="150" rows="3" placeholder="Jelaskan fungsi dan tanggung jawab role" class="form-control"></textarea>
              </div>
            </div>
            <div class="form-group">
              <label for="copy_from_group" class="control-label col-lg-2">Hak Akses Awal</label>
              <div class="col-lg-6">
                <select id="copy_from_group" name="copy_from_group" class="form-control chzn-select" data-placeholder="Pilih role sumber">
                  <option value="">Tanpa akses, atur manual setelah role dibuat</option>
                  <?php foreach ($db->query('select id, level_name from sys_group_users order by level_name') as $group) { ?>
                    <option value="<?=$group->id;?>"><?=htmlspecialchars($group->level_name, ENT_QUOTES, 'UTF-8');?></option>
                  <?php } ?>
                </select>
                <p class="help-block">Salin permission dari role yang paling mendekati, lalu sesuaikan di Kelola Hak Akses.</p>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-lg-2">&nbsp;</label>
              <div class="col-lg-10">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Role</button>
                <a href="<?=base_index();?>group-user" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
$(function () {
  $('#level_name').on('input', function () {
    if (!$('#level').data('manual')) {
      $('#level').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, ''));
    }
  });
  $('#level').on('input', function () {
    $(this).data('manual', true).val($(this).val().toLowerCase().replace(/[^a-z0-9_]+/g, '_'));
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
      $('#group_form_alert').text(result.error_message || 'Role gagal disimpan.').show();
      button.prop('disabled', false);
    }, 'json').fail(function () {
      $('#group_form_alert').text('Respons server tidak valid.').show();
      button.prop('disabled', false);
    });
  });
});
</script>
