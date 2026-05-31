<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Transfer Posting</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>transfer-produksi">Transfer Posting</a></li>
                        <li class="active">Detail Transfer Posting</li>
                    </ol>
                </section>

                <!-- Main content --> 
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Transfer Posting</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nomor;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No SPB" class="control-label col-lg-2">No SPB </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_spb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal SPB" class="control-label col-lg-2">Tanggal SPB <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_spb);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="No Request" class="control-label col-lg-2">No Request <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("ro") as $isi) {
                  if ($data_edit->no_request==$isi->no_ro) {

                    echo "<input disabled class='form-control' type='text' value='$isi->no_ro'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Tanggal Request" class="control-label col-lg-2">Tanggal Request </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_request);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("dept") as $isi) {
                  if ($data_edit->dept==$isi->kd_dept) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_dept'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Nama PPC" class="control-label col-lg-2">Nama PPC <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="name_ppc" disabled="" required><?=$data_edit->name_ppc;?> </textarea>
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>transfer-produksi" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
