<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Picking</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>picking">Picking</a></li>
                        <li class="active">Detail Picking</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Picking</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="no_aju" class="control-label col-lg-2">no_aju </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_aju;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_aju" class="control-label col-lg-2">tgl_aju </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_aju;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="jenis_dokpab" class="control-label col-lg-2">jenis_dokpab </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jenis_dokpab;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_dokpab" class="control-label col-lg-2">no_dokpab </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_dokpab;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_dokpab" class="control-label col-lg-2">tgl_dokpab </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_dokpab;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>picking" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
