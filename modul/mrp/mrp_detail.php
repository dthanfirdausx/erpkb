<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=prod_h('production_mrp', 'MRP');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a></li>
                        <li><a href="<?=base_index();?>mrp"><?=prod_h('production_mrp', 'MRP');?></a></li>
                        <li class="active">Detail MRP</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail MRP</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Order" class="control-label col-lg-2">Order <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->order;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Style" class="control-label col-lg-2">Style <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->style;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Qty" class="control-label col-lg-2"><?=prod_h('production_qty', 'Qty');?> </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->order_qty;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="term" class="control-label col-lg-2">term </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->term;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Delivery" class="control-label col-lg-2">Delivery </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->delivery);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
          <div class="form-group">
              <label for="Receipt" class="control-label col-lg-2">Receipt </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->receipt);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="PO" class="control-label col-lg-2">PO </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->po;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Buyer" class="control-label col-lg-2">Buyer <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("penerima") as $isi) {
                  if ($data_edit->buyer==$isi->kode_penerima) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

                        
                      </form>
                      <a href="<?=base_index();?>mrp" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
