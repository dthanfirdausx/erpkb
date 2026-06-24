<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=erp_h('password_change_title', 'Change Password');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=erp_h('common_home', 'Home');?></a></li>
                        <li><a href="<?=base_index();?>ubah-password"><?=erp_h('password_change_title', 'Change Password');?></a></li>
                        <li class="active"><?=erp_h('password_detail_title', 'Change Password Detail');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=erp_h('password_detail_title', 'Change Password Detail');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="email" class="control-label col-lg-2">email </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->email;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>ubah-password" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
