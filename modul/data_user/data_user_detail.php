<?php $data_user_photo_url = erpkb_user_photo_url($data_edit->foto_user, 'data_user'); ?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Data User</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>data-user">Data User</a></li>
                        <li class="active">Detail Data User</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Data User</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="first name" class="control-label col-lg-2">first name </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->first_name;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="last name" class="control-label col-lg-2">last name </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->last_name;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="username" class="control-label col-lg-2">username </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->username;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="password" class="control-label col-lg-2">password </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->password;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="email" class="control-label col-lg-2">email </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->email;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="foto user" class="control-label col-lg-2">foto user </label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                    <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 200px; height: 150px;">
                    <img src="<?=$data_user_photo_url;?>" style="max-width:100%;max-height:140px;"></div>
                  </div>
                  </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="group level" class="control-label col-lg-2">group level </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("sys_group_users") as $isi) {
                  if ($data_edit->group_level==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->level'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="aktif" class="control-label col-lg-2">aktif </label>
                <div class="col-lg-10">
                <?php if ($data_edit->aktif=="1") {
                  ?>
                  <input name="aktif" class="make-switch" disabled type="checkbox" checked>
                  <?php
                } else {
                  ?>
                  <input name="aktif" class="make-switch" disabled type="checkbox">
                  <?php
                }?>
                </div>
            </div><!-- /.form-group -->
            
                        
                      </form>
                      <a href="<?=base_index();?>data-user" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
