<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=fin_h('finance_balance_sheet', 'Balance Sheet');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
                        <li><a href="<?=base_index();?>neraca"><?=fin_h('finance_balance_sheet', 'Balance Sheet');?></a></li>
                        <li class="active">Detail Neraca</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Neraca</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="kategori_akun" class="control-label col-lg-2">kategori_akun </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kategori_akun;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kategori" class="control-label col-lg-2">kategori </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kategori;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_rek" class="control-label col-lg-2">no_rek </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_rek;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nama_rek" class="control-label col-lg-2">nama_rek </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nama_rek;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>neraca" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
