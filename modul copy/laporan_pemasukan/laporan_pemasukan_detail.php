<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Laporan Pemasukan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>laporan-pemasukan">Laporan Pemasukan</a></li>
                        <li class="active">Detail Laporan Pemasukan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Laporan Pemasukan</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="id" class="control-label col-lg-2">id </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->id;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_bpb" class="control-label col-lg-2">no_bpb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_bpb" class="control-label col-lg-2">tgl_bpb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_bpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>laporan-pemasukan" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
