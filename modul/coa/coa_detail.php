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
                <label for="Nama Coa" class="control-label col-lg-2">Nama Coa </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_rek;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Induk" class="control-label col-lg-2">Induk </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("rekening") as $isi) {
                  if ($data_edit->induk==$isi->no_rek) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama_rek'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="Level" class="control-label col-lg-2">Level </label>
                <div class="col-lg-10">
                <?php
                  $option = array(
'1' => '1',

'2' => '2',

'3' => '3',

'4' => '4',
);
                  foreach ($option as $isi => $val) {
                  if ($data_edit->level==$isi) {

                    echo "<input disabled class='form-control' type='text' value='$val'>";
                  }
               } ?>
                  </div>
            </div><!-- /.form-group -->
            
              <div class="form-group">
                <label for="Nama COA/Rekening" class="control-label col-lg-2">Nama COA/Rekening </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama_rek;?>" class="form-control">
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

                        
                      </form>
                      <a href="<?=base_index();?>coa" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
