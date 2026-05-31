<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Mapping COA</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>mapping-coa">Mapping COA</a></li>
                        <li class="active">Detail Mapping COA</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Mapping COA</h3>
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
                <label for="transaksi" class="control-label col-lg-2">transaksi </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->transaksi;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="bc" class="control-label col-lg-2">bc </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->bc;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>mapping-coa" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
