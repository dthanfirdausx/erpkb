<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=customs_h('legacy_laporan_pemasukan_per_jenis_dokumen_pabean','Laporan Pemasukan Per Jenis Dokumen Pabean');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=customs_h('home','Home');?></a></li>
                        <li><a href="<?=base_index();?>laporan-pemasukan-per-jenis-dokumen-pabean"><?=customs_h('incoming_report_by_doc_type','Laporan Pemasukan Per Jenis Dokumen Pabean');?></a></li>
                        <li class="active"><?=customs_h('legacy_detail_laporan_pemasukan_per_jenis_dokumen_pabean','Detail Laporan Pemasukan Per Jenis Dokumen Pabean');?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=customs_h('legacy_detail_laporan_pemasukan_per_jenis_dokumen_pabean','Detail Laporan Pemasukan Per Jenis Dokumen Pabean');?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="kategori" class="control-label col-lg-2">kategori </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kategori;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kd_sub_kategori" class="control-label col-lg-2">kd_sub_kategori </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kd_sub_kategori;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>laporan-pemasukan-per-jenis-dokumen-pabean" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
