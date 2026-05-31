<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Satuan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>satuan">Satuan</a></li>
                        <li class="active">Detail Satuan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Satuan</h3>
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
                <label for="jenis" class="control-label col-lg-2">jenis </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jenis;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nama" class="control-label col-lg-2">nama </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>satuan" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
