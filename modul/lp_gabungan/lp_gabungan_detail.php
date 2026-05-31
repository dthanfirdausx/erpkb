<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>LP Gabungan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>lp-gabungan">LP Gabungan</a></li>
                        <li class="active">Detail LP Gabungan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail LP Gabungan</h3>
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
                <label for="No LP" class="control-label col-lg-2">No LP <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal LP" class="control-label col-lg-2">Tanggal LP <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_bpb);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Project" class="control-label col-lg-2">Project <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->project;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("dept") as $isi) {
                  if ($data_edit->dept==$isi->nm_dept) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_dept'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->name_ppc;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Catatan" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->catatan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>lp-gabungan" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
