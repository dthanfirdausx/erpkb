<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Stock Pemasukan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>stock-pemasukan">Stock Pemasukan</a></li>
                        <li class="active">Detail Stock Pemasukan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Stock Pemasukan</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="kd_barang" class="control-label col-lg-2">kd_barang </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kd_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nm_barang" class="control-label col-lg-2">nm_barang </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nm_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="stock" class="control-label col-lg-2">stock </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->stock;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="satuan" class="control-label col-lg-2">satuan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->satuan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nm_kategori" class="control-label col-lg-2">nm_kategori </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nm_kategori;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>stock-pemasukan" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
