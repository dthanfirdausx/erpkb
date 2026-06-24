<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=erp_h('master_inbound_customs_type','Inbound Customs Type');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
                        <li><a href="<?=base_index();?>bc-masuk"><?=erp_h('master_inbound_customs_type','Inbound Customs Type');?></a></li>
                        <li class="active"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_inbound_customs_type','Inbound Customs Type');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_inbound_customs_type','Inbound Customs Type');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="<?=erp_h('master_term_kode_bc','BC Code');?> " class="control-label col-lg-2"><?=erp_h('master_term_kode_bc','BC Code');?>  </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="<?=erp_h('master_term_jenis_dokumen','Document Type');?>" class="control-label col-lg-2"><?=erp_h('master_term_jenis_dokumen','Document Type');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jenis;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="<?=erp_h('master_term_nama','Name');?>" class="control-label col-lg-2"><?=erp_h('master_term_nama','Name');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>bc-masuk" class="btn btn-success "><i class="fa fa-step-backward"></i> <?=erp_h('common_back','Back');?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
