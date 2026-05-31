<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Info KB</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>info-kb">Info KB</a></li>
                        <li class="active">Detail Info KB</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Info KB</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode" class="control-label col-lg-2">Kode </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama" class="control-label col-lg-2">Nama </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2">Alamat </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->alamat;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Provinsi" class="control-label col-lg-2">Provinsi </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->prop;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kota" class="control-label col-lg-2">Kota </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kota;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2">NPWP </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Telp" class="control-label col-lg-2">Telp </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->telp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Fax" class="control-label col-lg-2">Fax </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->fax;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Skep KB" class="control-label col-lg-2">Skep KB </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->skepkb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tgl Skep KB" class="control-label col-lg-2">Tgl Skep KB </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tglskep);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>info-kb" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
