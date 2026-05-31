<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Info IKB</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>info-ikb">Info IKB</a></li>
                        <li class="active">Detail Info IKB</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Info IKB</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="kode" class="control-label col-lg-2">kode </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nama" class="control-label col-lg-2">nama </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="alamat" class="control-label col-lg-2">alamat </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->alamat;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="prop" class="control-label col-lg-2">prop </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->prop;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kota" class="control-label col-lg-2">kota </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kota;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="npwp" class="control-label col-lg-2">npwp </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="telp" class="control-label col-lg-2">telp </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->telp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="fax" class="control-label col-lg-2">fax </label>
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
              <label for="Tanggal Skep KB" class="control-label col-lg-2">Tanggal Skep KB </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tglskep);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>info-ikb" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
