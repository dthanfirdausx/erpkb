<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Mata Uang</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>mata-uang">Mata Uang</a></li>
                        <li class="active">Detail Mata Uang</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Mata Uang</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode Valas" class="control-label col-lg-2">Kode Valas </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kd_valas;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Jenis Valas" class="control-label col-lg-2">Jenis Valas </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jenis_valas;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Valas" class="control-label col-lg-2">Nama Valas </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama_valas;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Negara" class="control-label col-lg-2">Negara </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->negara_valas;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>mata-uang" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
