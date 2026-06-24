<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=erp_h('master_material_master', 'Material Master');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=erp_h('common_home', 'Home');?></a></li>
                        <li><a href="<?=base_index();?>barang"><?=erp_h('master_material_master', 'Material Master');?></a></li>
                        <li class="active"><?=erp_h('common_detail', 'Detail');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=erp_h('common_detail', 'Detail');?> <?=erp_h('master_material_master', 'Material Master');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode Barang" class="control-label col-lg-2"><?=erp_h('master_term_kode_barang', 'Material Code');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kd_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2"><?=erp_h('master_term_nama_barang', 'Material Name');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nm_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Type" class="control-label col-lg-2"><?=erp_h('common_type', 'Type');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->type;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Spesipikasi" class="control-label col-lg-2"><?=erp_h('common_spec', 'Spec');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->spec;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Satuan" class="control-label col-lg-2"><?=erp_h('master_term_satuan', 'Unit');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->satuan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2"><?=erp_h('master_term_keterangan', 'Remarks');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->ket;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Kategori" class="control-label col-lg-2"><?=erp_h('master_term_kategori', 'Category');?> <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("kategori") as $isi) {
                  if ($data_edit->kd_kategori==$isi->kd_kategori) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_kategori'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="status" class="control-label col-lg-2"><?=erp_h('common_status', 'Status');?> </label>
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
