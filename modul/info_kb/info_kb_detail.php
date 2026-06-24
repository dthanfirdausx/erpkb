<?php
if (!function_exists('info_kb_t')) {
    function info_kb_t($key, $fallback = '')
    {
        return lang_text('info_kb_' . $key, $fallback);
    }
}
if (!function_exists('info_kb_h')) {
    function info_kb_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=info_kb_h(info_kb_t('title', 'KB Profile'));?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>info-kb"><?=info_kb_h(info_kb_t('title', 'KB Profile'));?></a></li>
                        <li class="active"><?=info_kb_h(info_kb_t('detail_title', 'KB Profile Detail'));?></li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title"><?=info_kb_h(info_kb_t('detail_title', 'KB Profile Detail'));?></h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Kode" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('internal_code', 'Internal Code'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kode;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('company_name', 'Company Name'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('address', 'Address'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->alamat;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Provinsi" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('province', 'Province'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->prop;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kota" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('city', 'City'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kota;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('npwp', 'Tax ID'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Telp" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('phone', 'Phone'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->telp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Fax" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('fax', 'Fax'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->fax;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Skep KB" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('skep_kb', 'SKEP KB'));?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->skepkb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tgl Skep KB" class="control-label col-lg-2"><?=info_kb_h(info_kb_t('skep_date', 'SKEP Date'));?> </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tglskep);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
                        
                      </form>
                      <a href="<?=base_index();?>info-kb" class="btn btn-success "><i class="fa fa-step-backward"></i> <?=info_kb_h(lang_text('back_button', 'Back'));?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
