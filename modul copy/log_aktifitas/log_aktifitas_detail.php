<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Log Aktifitas</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>log-aktifitas">Log Aktifitas</a></li>
                        <li class="active">Detail Log Aktifitas</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Log Aktifitas</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
          <div class="form-group">
              <label for="deskripsi" class="control-label col-lg-2">deskripsi </label>
              <div class="col-lg-10">
                <textarea id="editbox" name="deskripsi" disabled="" class="editbox"><?=$data_edit->deskripsi;?> </textarea>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="user" class="control-label col-lg-2">user </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->user;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl" class="control-label col-lg-2">tgl </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>log-aktifitas" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
