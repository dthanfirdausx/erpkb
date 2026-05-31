<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Penerima</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>penerima">Penerima</a></li>
                        <li class="active">Detail Penerima</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Penerima</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="kode_penerima" class="control-label col-lg-2">kode_penerima </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode_penerima;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="npwp" class="control-label col-lg-2">npwp </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
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
                <label for="kota" class="control-label col-lg-2">kota </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kota;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="negara" class="control-label col-lg-2">negara </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->negara;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="notelp" class="control-label col-lg-2">notelp </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->notelp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nofax" class="control-label col-lg-2">nofax </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nofax;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="email" class="control-label col-lg-2">email </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->email;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="status" class="control-label col-lg-2">status </label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->status;?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                
              <div class="form-group">
                <label for="skep" class="control-label col-lg-2">skep </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->skep;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>penerima" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
