<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Management Shift</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>management-shift">Management Shift</a></li>
                        <li class="active">Detail Management Shift</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Management Shift</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Nama Shift" class="control-label col-lg-2">Nama Shift </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->namaShift;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Jam Masuk" class="control-label col-lg-2">Jam Masuk </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->masuk);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
          <div class="form-group">
              <label for="Jam Keluar" class="control-label col-lg-2">Jam Keluar </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->keluar);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>management-shift" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
