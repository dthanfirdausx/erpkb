<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=erp_h('master_vendor_master','Vendor Master');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=erp_h('common_home','Home');?></a></li>
                        <li><a href="<?=base_index();?>pemasok"><?=erp_h('master_vendor_master','Vendor Master');?></a></li>
                        <li class="active"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_vendor_master','Vendor Master');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_vendor_master','Vendor Master');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode Pemasok" class="control-label col-lg-2"><?=erp_h('master_term_kode_pemasok','Vendor Code');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode_pemasok;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2"><?=erp_h('master_term_npwp','Tax ID');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Pemasok" class="control-label col-lg-2"><?=erp_h('master_term_nama_pemasok','Vendor Name');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2"><?=erp_h('master_term_alamat','Address');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->alamat;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kota" class="control-label col-lg-2"><?=erp_h('master_term_kota','City');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kota;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Negara" class="control-label col-lg-2"><?=erp_h('master_term_negara','Country');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->negara;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Telp" class="control-label col-lg-2"><?=erp_h('master_term_telepon','Phone');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->notelp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Fax" class="control-label col-lg-2"><?=erp_h('master_term_fax','Fax');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nofax;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Email" class="control-label col-lg-2"><?=erp_h('master_term_email','Email');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->email;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
            <div class="form-group">
                <label for="Status" class="control-label col-lg-2"><?=erp_h('common_status','Status');?> </label>
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
                      <a href="<?=base_index();?>pemasok" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
