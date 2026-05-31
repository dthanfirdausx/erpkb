<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Barang</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>barang">Barang</a></li>
                        <li class="active">Detail Barang</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Barang</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode Barang" class="control-label col-lg-2">Kode Barang <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kd_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2">Nama Barang <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nm_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Type" class="control-label col-lg-2">Type </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->type;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Spesipikasi" class="control-label col-lg-2">Spesipikasi </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->spec;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Satuan" class="control-label col-lg-2">Satuan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->satuan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2">Keterangan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->ket;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Kategori" class="control-label col-lg-2">Kategori <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("kategori") as $isi) {
                  if ($data_edit->kd_kategori==$isi->kd_kategori) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_kategori'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="status" class="control-label col-lg-2">status </label>
                <div class="col-lg-10">
                <?php if ($data_edit->status=="1") {
                  ?>
                  <input name="status" class="make-switch" disabled type="checkbox" checked>
                  <?php
                } else {
                  ?>
                  <input name="status" class="make-switch" disabled type="checkbox">
                  <?php
                }?>
                </div>
            </div><!-- /.form-group -->
            
                        
                      </form>
                      <a href="<?=base_index();?>barang" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
