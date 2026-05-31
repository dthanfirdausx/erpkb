<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>COA</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>coa">COA</a></li>
                        <li class="active">Detail COA</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail COA</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="No Rek/COA" class="control-label col-lg-2">No Rek/COA <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_rek;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Induk " class="control-label col-lg-2">Induk  <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("rekening") as $isi) {
                  if ($data_edit->induk==$isi->no_rek) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama_rek'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="level" class="control-label col-lg-2">level </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->level;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="kat_coa" class="control-label col-lg-2">kat_coa </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("coa_kategori") as $isi) {
                  if ($data_edit->kat_coa==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->kategori'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="jenis" class="control-label col-lg-2">jenis </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jenis;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>coa" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
