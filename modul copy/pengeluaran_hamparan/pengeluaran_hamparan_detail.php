<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Pengeluaran Hamparan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>pengeluaran-hamparan">Pengeluaran Hamparan</a></li>
                        <li class="active">Detail Pengeluaran Hamparan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Pengeluaran Hamparan</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
          <div class="form-group">
              <label for="Tanggal Pengeluaran" class="control-label col-lg-2">Tanggal Pengeluaran <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_sj);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Penerima" class="control-label col-lg-2">Penerima <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("penerima") as $isi) {
                  if ($data_edit->penerima==$isi->kode_penerima) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice/Kontrak" class="control-label col-lg-2">No Invoice/Kontrak <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_invoice;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_invoice);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Po" class="control-label col-lg-2">No Po </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_do;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Po" class="control-label col-lg-2">No Po </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_do;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Dokpab" class="control-label col-lg-2">No Dokpab </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_dokpab;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Dokpab" class="control-label col-lg-2">Tanggal Dokpab </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_dokpab);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Tujuan Pengiriman" class="control-label col-lg-2">Tujuan Pengiriman </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("catatan") as $isi) {
                  if ($data_edit->catatan==$isi->kd_catatan) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_catatan'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Aju" class="control-label col-lg-2">No Aju </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_aju;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Aju" class="control-label col-lg-2">Tanggal Aju </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_aju);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Efaktur" class="control-label col-lg-2">No Efaktur </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->efaktur;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tgl Efaktur" class="control-label col-lg-2">Tgl Efaktur </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_efaktur);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>pengeluaran-hamparan" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
