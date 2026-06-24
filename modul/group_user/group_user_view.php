<?php
$summary = $db->query(
  "select
     (select count(*) from sys_group_users) total_group,
     (select count(*) from sys_users where aktif='Y') active_user,
     (select count(*) from sys_users where aktif='N' or aktif is null) inactive_user,
     (select count(*) from sys_menu) total_menu"
)->fetch();
?>

<section class="content-header">
  <h1>Group User <small>Role dan kontrol akses ERP</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Group User</li>
  </ol>
</section>

<section class="content">
<?php
$mdtActionsHtml = '<a href="'.base_index().'group-user/tambah" class="btn btn-warning"><i class="fa fa-plus"></i> Tambah Role</a>';
include __DIR__ . "/../master_data_toolbar.php";
?>
  <div id="group_list_alert" class="alert" style="display:none"></div>
  <div class="row">
    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-aqua"><div class="inner"><h3><?=intval($summary->total_group);?></h3><p>Total Role</p></div><div class="icon"><i class="fa fa-users"></i></div></div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-green"><div class="inner"><h3><?=intval($summary->active_user);?></h3><p>User Aktif</p></div><div class="icon"><i class="fa fa-user-check"></i></div></div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-yellow"><div class="inner"><h3><?=intval($summary->inactive_user);?></h3><p>User Nonaktif</p></div><div class="icon"><i class="fa fa-user-times"></i></div></div>
    </div>
    <div class="col-lg-3 col-xs-6">
      <div class="small-box bg-purple"><div class="inner"><h3><?=intval($summary->total_menu);?></h3><p>Menu ERP</p></div><div class="icon"><i class="fa fa-sitemap"></i></div></div>
    </div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Daftar Role</h3>
    </div>
    <div class="box-body">
      <form action="" method="get" class="form-horizontal" style="margin-bottom:15px">
        <div class="form-group">
          <label class="control-label col-lg-2">Cari Role</label>
          <div class="col-lg-5">
            <div class="input-group">
              <input type="text" name="q" value="<?=htmlspecialchars(isset($_GET['q']) ? $_GET['q'] : '', ENT_QUOTES, 'UTF-8');?>" class="form-control" placeholder="Nama, kode, atau deskripsi role">
              <span class="input-group-btn"><button type="submit" class="btn btn-default"><i class="fa fa-search"></i> Cari</button></span>
            </div>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th style="width:40px">No</th>
              <th>Role</th>
              <th>Deskripsi</th>
              <th class="text-center">User</th>
              <th class="text-center">Menu Aktif</th>
              <th class="text-center">Akses Transaksi</th>
              <th style="width:225px">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $limit = 10;
          $search = '';
          if (isset($_GET['q']) && trim($_GET['q']) !== '') {
            $searchCondition = $db->getRawWhereFilterForColumns($_GET['q'], array('g.level', 'g.level_name', 'g.deskripsi'));
            $search = 'where '.$searchCondition;
          }
          $sql = "select g.*,
                    (select count(*) from sys_users u where u.group_level=g.id) user_count,
                    (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=g.level and r.read_act='Y') read_count,
                    (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=g.level and
                      (r.insert_act='Y' or r.update_act='Y' or r.delete_act='Y' or r.import_act='Y')) transaction_count
                  from sys_group_users g $search order by g.level_name asc";
          $groups = $pg->query($sql);
          $no = $pg->Num($limit);
          $count = $pg->Num($limit);
          if ($groups->rowCount() < 1) {
            echo '<tr><td colspan="7" class="text-center">Data role tidak ditemukan.</td></tr>';
          }
          foreach ($groups as $group) {
            $isAdmin = $group->level === 'admin';
          ?>
            <tr id="line_<?=$group->id;?>">
              <td class="text-center"><?=$no;?></td>
              <td>
                <strong><?=htmlspecialchars($group->level_name, ENT_QUOTES, 'UTF-8');?></strong><br>
                <code><?=htmlspecialchars($group->level, ENT_QUOTES, 'UTF-8');?></code>
                <?php if ($isAdmin) { ?><span class="label label-danger">SYSTEM</span><?php } ?>
              </td>
              <td><?=htmlspecialchars($group->deskripsi, ENT_QUOTES, 'UTF-8');?></td>
              <td class="text-center"><span class="badge bg-blue"><?=intval($group->user_count);?></span></td>
              <td class="text-center"><span class="badge bg-green"><?=intval($group->read_count);?></span> / <?=intval($summary->total_menu);?></td>
              <td class="text-center"><span class="badge bg-yellow"><?=intval($group->transaction_count);?></span></td>
              <td>
                <a href="<?=base_index();?>group-user/detail/<?=$group->id;?>" class="btn btn-success btn-sm" title="Detail"><i class="fa fa-eye"></i></a>
                <a href="<?=base_index();?>menu-management?user=<?=$group->id;?>" class="btn btn-info btn-sm" title="Kelola Hak Akses"><i class="fa fa-key"></i></a>
                <button type="button" class="btn btn-default btn-sm sync-group" data-id="<?=$group->id;?>" title="Sinkronkan Daftar Menu"><i class="fa fa-refresh"></i></button>
                <?php if (!$isAdmin) { ?>
                  <a href="<?=base_index();?>group-user/edit/<?=$group->id;?>" class="btn btn-primary btn-sm" title="Edit"><i class="fa fa-pencil"></i></a>
                  <button type="button" class="btn btn-danger btn-sm delete-group" data-id="<?=$group->id;?>" data-users="<?=intval($group->user_count);?>" title="Hapus"><i class="fa fa-trash"></i></button>
                <?php } ?>
              </td>
            </tr>
          <?php $no++; } ?>
          </tbody>
        </table>
      </div>

      <div class="row">
        <div class="col-sm-6">Menampilkan <?=$groups->rowCount() ? $count : 0;?> sampai <?=$groups->rowCount() ? $no - 1 : 0;?> dari <?=$pg->total_record;?> role</div>
        <div class="col-sm-6 text-right">
          <?php
          if (isset($_GET['q'])) {
            $pg->url = base_index().'group-user?q='.urlencode($_GET['q']).'&page=';
          }
          $pg->setParameter(array('range' => $limit));
          ?>
          <ul class="pagination" style="margin:0"><?=$pg->create();?></ul>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
$(function () {
  function showGroupMessage(type, message) {
    $('#group_list_alert').removeClass('alert-success alert-danger').addClass('alert-' + type).text(message).show();
  }

  $('.sync-group').on('click', function () {
    var button = $(this).prop('disabled', true);
    $.post('<?=base_admin();?>modul/group_user/group_user_action.php?act=sync', {id: button.data('id')}, function (response) {
      var result = response[0] || {};
      showGroupMessage(result.status === 'good' ? 'success' : 'danger', result.message || result.error_message);
      button.prop('disabled', false);
    }, 'json').fail(function () {
      showGroupMessage('danger', 'Sinkronisasi role gagal.');
      button.prop('disabled', false);
    });
  });

  $('.delete-group').on('click', function () {
    var button = $(this);
    if (parseInt(button.data('users'), 10) > 0) {
      showGroupMessage('danger', 'Role masih digunakan user dan tidak dapat dihapus.');
      return;
    }
    $('#ucing').modal({keyboard: false}).one('click', '#delete', function () {
      $.post('<?=base_admin();?>modul/group_user/group_user_action.php?act=delete&id=' + button.data('id'), function (response) {
        var result = response[0] || {};
        if (result.status === 'good') {
          $('#line_' + button.data('id')).fadeOut();
          showGroupMessage('success', result.message);
        } else {
          showGroupMessage('danger', result.error_message || 'Role gagal dihapus.');
        }
      }, 'json').fail(function () {
        showGroupMessage('danger', 'Respons server tidak valid.');
      });
      $('#ucing').modal('hide');
    });
  });
});
</script>
