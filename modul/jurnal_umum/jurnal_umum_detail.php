<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Jurnal Umum</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>jurnal-umum">Jurnal Umum</a></li>
                        <li class="active">Detail Jurnal Umum</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Jurnal Umum</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="No Jurnal" class="control-label col-lg-2">No Jurnal </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_jurnal;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Jurnal" class="control-label col-lg-2">Tanggal Jurnal </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_jurnal);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Ket" class="control-label col-lg-2">Ket </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->ket;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Bukti" class="control-label col-lg-2">No Bukti </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bukti;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="COA" class="control-label col-lg-2">COA </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("rekening") as $isi) {
                  if ($data_edit->no_rek==$isi->no_rek) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama_rek'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="debet" class="control-label col-lg-2">debet </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->debet;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kredit" class="control-label col-lg-2">kredit </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kredit;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("matauang") as $isi) {
                  if ($data_edit->valuta==$isi->jenis_valas) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama_valas'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

                        
                      </form>
                      <a href="<?=base_index();?>jurnal-umum" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
