<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>RO</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>ro">RO</a></li>
                        <li class="active">Detail RO</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail RO</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
          <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2">Tanggal RO <span style="color:#FF0000">*</span></label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_ro);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("dept") as $isi) {
                  if ($data_edit->dept==$isi->kd_dept) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_dept'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->name_ppc;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Material" class="control-label col-lg-2">Material </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("bom") as $isi) {
                  if ($data_edit->id_bom==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_barang'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Jumlah Barang" class="control-label col-lg-2">Jumlah Barang </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jml_brg_jadi;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
            <div class="form-group">
                <label for="tujuan" class="control-label col-lg-2">tujuan </label>
                <div class="col-lg-10">
                <?php
                  $option = array(
'1' => 'Praproduksi',

'2' => 'Produksi',
);
                  foreach ($option as $isi => $val) {
                  if ($data_edit->tujuan==$isi) {

                    echo "<input disabled class='form-control' type='text' value='$val'>";
                  }
               } ?>
                  </div>
            </div><!-- /.form-group -->
            
          <div class="form-group">
              <label for="catatan" class="control-label col-lg-2">catatan </label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" disabled="" ><?=$data_edit->catatan;?> </textarea>
              </div>
          </div><!-- /.form-group -->
          <hr>

<h4>Detail Barang RO</h4>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th class="text-right">Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no=1;
            // echo "SELECT a.*, b.nm_barang, b.satuan
            //     FROM ro_detail a
            //     LEFT JOIN barang b ON a.kode_barang=b.kd_barang
            //     WHERE a.no_ro='$data_edit->no_ro'";

            $detail = $db->query("
                 SELECT
                    d.*,
                    b.nm_barang,
                    b.satuan
                FROM ro_detail d
                LEFT JOIN barang b
                    ON b.kd_barang = d.kode
                WHERE d.no_ro = '".$data_edit->no_ro."'
                ORDER BY d.row_no ASC
            ");

            foreach($detail as $d){
            ?>
            <tr>
                <td><?=$no++;?></td>
                <td><?=$d->kode;?></td>
                <td><?=$d->nm_barang;?></td>
                <td><?=$d->satuan;?></td>
                <td align="right"><?=number_format($d->jumlah,2);?></td>
            </tr>
            <?php } ?>

            <?php if($detail->rowCount()==0){ ?>
            <tr>
                <td colspan="5" align="center">
                    Tidak ada detail barang
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
          
                        
                      </form>
                      <a href="<?=base_index();?>ro" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
