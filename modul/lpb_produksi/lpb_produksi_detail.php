<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>LPB Produksi</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>lpb-produksi">LPB Produksi</a></li>
                        <li class="active">Detail LPB Produksi</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail LPB Produksi</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="nomor" class="control-label col-lg-2">nomor </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nomor;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_lpb" class="control-label col-lg-2">no_lpb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_lpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_lpb" class="control-label col-lg-2">tgl_lpb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_lpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="dari" class="control-label col-lg-2">dari </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->dari;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_spb" class="control-label col-lg-2">no_spb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_spb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_spb" class="control-label col-lg-2">tgl_spb </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_spb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="dept" class="control-label col-lg-2">dept </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->dept;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="name_ppc" class="control-label col-lg-2">name_ppc </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->name_ppc;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="catatan" class="control-label col-lg-2">catatan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->catatan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="user_trt" class="control-label col-lg-2">user_trt </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->user_trt;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="userid" class="control-label col-lg-2">userid </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->userid;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>lpb-produksi" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
