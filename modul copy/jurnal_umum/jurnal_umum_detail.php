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
                <label for="no_jurnal" class="control-label col-lg-2">no_jurnal </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_jurnal;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_jurnal" class="control-label col-lg-2">tgl_jurnal </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_jurnal;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="ket" class="control-label col-lg-2">ket </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->ket;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_bukti" class="control-label col-lg-2">no_bukti </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bukti;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="no_rek" class="control-label col-lg-2">no_rek </label>
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
                <label for="debet_usd" class="control-label col-lg-2">debet_usd </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->debet_usd;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kredit" class="control-label col-lg-2">kredit </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kredit;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kredit_usd" class="control-label col-lg-2">kredit_usd </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kredit_usd;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="username" class="control-label col-lg-2">username </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->username;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_insert" class="control-label col-lg-2">tgl_insert </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_insert;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="valuta" class="control-label col-lg-2">valuta </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->valuta;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kurs" class="control-label col-lg-2">kurs </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kurs;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>jurnal-umum" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
