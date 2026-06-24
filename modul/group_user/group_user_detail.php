<?php
$roleStats = $db->query(
  "select
     (select count(*) from sys_users where group_level=?) user_count,
     (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=? and r.read_act='Y') read_count,
     (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=? and r.insert_act='Y') insert_count,
     (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=? and r.update_act='Y') update_count,
     (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=? and r.delete_act='Y') delete_count,
     (select count(distinct r.id_menu) from sys_menu_role r inner join sys_menu m on m.id=r.id_menu where r.group_level=? and r.import_act='Y') import_count",
  array(
    'group_id' => $data_edit->id,
    'read_level' => $data_edit->level,
    'insert_level' => $data_edit->level,
    'update_level' => $data_edit->level,
    'delete_level' => $data_edit->level,
    'import_level' => $data_edit->level,
  )
)->fetch();
$roleUsers = $db->query(
  "select first_name, last_name, username, email, aktif
   from sys_users where group_level=? order by first_name, username limit 20",
  array('group_level' => $data_edit->id)
);
?>

<section class="content-header">
  <h1>Detail Role ERP</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>group-user">Group User</a></li>
    <li class="active">Detail Role</li>
  </ol>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Identitas Role</h3></div>
        <div class="box-body box-profile">
          <h3 class="profile-username text-center"><?=htmlspecialchars($data_edit->level_name, ENT_QUOTES, 'UTF-8');?></h3>
          <p class="text-muted text-center"><code><?=htmlspecialchars($data_edit->level, ENT_QUOTES, 'UTF-8');?></code></p>
          <p><?=nl2br(htmlspecialchars($data_edit->deskripsi, ENT_QUOTES, 'UTF-8'));?></p>
          <ul class="list-group list-group-unbordered">
            <li class="list-group-item"><b>Jumlah User</b><span class="pull-right badge bg-blue"><?=intval($roleStats->user_count);?></span></li>
            <li class="list-group-item"><b>Menu Dapat Dilihat</b><span class="pull-right badge bg-green"><?=intval($roleStats->read_count);?></span></li>
          </ul>
          <a href="<?=base_index();?>menu-management?user=<?=$data_edit->id;?>" class="btn btn-info btn-block"><i class="fa fa-key"></i> Kelola Hak Akses</a>
          <?php if ($data_edit->level !== 'admin') { ?><a href="<?=base_index();?>group-user/edit/<?=$data_edit->id;?>" class="btn btn-primary btn-block"><i class="fa fa-pencil"></i> Edit Role</a><?php } ?>
          <a href="<?=base_index();?>group-user" class="btn btn-default btn-block"><i class="fa fa-arrow-left"></i> Kembali</a>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="row">
        <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-plus"></i></span><div class="info-box-content"><span class="info-box-text">Input</span><span class="info-box-number"><?=intval($roleStats->insert_count);?></span></div></div></div>
        <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-pencil"></i></span><div class="info-box-content"><span class="info-box-text">Edit</span><span class="info-box-number"><?=intval($roleStats->update_count);?></span></div></div></div>
        <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-trash"></i></span><div class="info-box-content"><span class="info-box-text">Hapus</span><span class="info-box-number"><?=intval($roleStats->delete_count);?></span></div></div></div>
        <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-upload"></i></span><div class="info-box-content"><span class="info-box-text">Import</span><span class="info-box-number"><?=intval($roleStats->import_count);?></span></div></div></div>
      </div>
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">User dalam Role Ini</h3></div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped">
            <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($roleUsers->rowCount() < 1) { ?>
              <tr><td colspan="4" class="text-center">Belum ada user pada role ini.</td></tr>
            <?php } foreach ($roleUsers as $user) { ?>
              <tr>
                <td><?=htmlspecialchars(trim($user->first_name.' '.$user->last_name), ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');?></td>
                <td><?php if ($user->aktif === 'Y' || $user->aktif === '1') { ?><span class="label label-success">Aktif</span><?php } else { ?><span class="label label-default">Nonaktif</span><?php } ?></td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
