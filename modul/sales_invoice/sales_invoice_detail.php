<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1><?=sd_h('sales_invoice', 'Sales Invoice');?></h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
                        <li><a href="<?=base_index();?>sales-invoice"><?=sd_h('sales_invoice', 'Sales Invoice');?></a></li>
                        <li class="active">Detail Sales Invoice</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Sales Invoice</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        <div class="form-group">
                        <label for="Bill To" class="control-label col-lg-2">Bill To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("penerima") as $isi) {
                  if ($data_edit->bill_to==$isi->kode_penerima) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Ship To" class="control-label col-lg-2">Ship To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("penerima") as $isi) {
                  if ($data_edit->ship_to==$isi->kode_penerima) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Invoice Date" class="control-label col-lg-2">Invoice Date </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->invoice_date);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Invooice No" class="control-label col-lg-2">Invooice No </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->invoice_no;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="PO NO" class="control-label col-lg-2">PO NO </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nopo;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Term" class="control-label col-lg-2">Term </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->term;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Currency" class="control-label col-lg-2"><?=sd_h('sales_currency', 'Currency');?> </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("matauang") as $isi) {
                  if ($data_edit->valuta==$isi->jenis_valas) {

                    echo "<input disabled class='form-control' type='text' value='$isi->jenis_valas'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Ship Date" class="control-label col-lg-2">Ship Date </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->ship_date);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">DO No </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_do;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Bank Detail" class="control-label col-lg-2">Bank Detail </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->bank_detail;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2"><?=sd_h('sales_tax', 'Tax');?> </label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->tax;?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                
                        
                      </form>
                      <a href="<?=base_index();?>sales-invoice" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
