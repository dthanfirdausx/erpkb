<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=erp_h('master_packing_unit','Packing Unit');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>satuan-packing"><?=erp_h('master_packing_unit','Packing Unit');?></a></li>
                        <li class="active"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_packing_unit','Packing Unit');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=erp_h('common_detail','Detail');?> <?=erp_h('master_packing_unit','Packing Unit');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="<?=erp_h('master_term_satuan_packing','Packing Unit');?>" class="control-label col-lg-2"><?=erp_h('master_term_satuan_packing','Packing Unit');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->satuan_packing;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>satuan-packing" class="btn btn-success "><i class="fa fa-step-backward"></i> <?=erp_h('common_back','Back');?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
