<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>User Group</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>user-group">User Group</a></li>
                        <li class="active">Detail User Group</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail User Group</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="level" class="control-label col-lg-2">level </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->level;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="level name" class="control-label col-lg-2">level name </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->level_name;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="deskripsi" class="control-label col-lg-2">deskripsi </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->deskripsi;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>user-group" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
