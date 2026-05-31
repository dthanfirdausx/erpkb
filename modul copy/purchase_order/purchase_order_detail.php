<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Purchase Order</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>purchase-order">Purchase Order</a></li>
                        <li class="active">Detail Purchase Order</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Purchase Order</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="No PO" class="control-label col-lg-2">No PO </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->po_no;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Season" class="control-label col-lg-2">Season </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->season;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Po Date" class="control-label col-lg-2">Po Date </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->po_date);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Supplier" class="control-label col-lg-2">Supplier </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("pemasok") as $isi) {
                  if ($data_edit->supplier==$isi->kode_pemasok) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Supplier Address" class="control-label col-lg-2">Supplier Address </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->supplier_address;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Issue By" class="control-label col-lg-2">Issue By </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->issue_by;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Trade Term" class="control-label col-lg-2">Trade Term </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->trade_terms;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Payment" class="control-label col-lg-2">Payment </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->payment;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>purchase-order" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
