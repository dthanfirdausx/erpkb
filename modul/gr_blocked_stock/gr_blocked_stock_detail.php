<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>GR Blocked Stock Hamparan</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>gr-blocked-stock">GR Blocked Stock Hamparan</a></li>
                        <li class="active">Detail GR Blocked Stock Hamparan</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail GR Blocked Stock Hamparan</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="No BPB" class="control-label col-lg-2">No BPB </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_bpb;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal BPB" class="control-label col-lg-2">Tanggal BPB </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_bpb);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="No PO" class="control-label col-lg-2">No PO </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("po") as $isi) {
                  if ($data_edit->nopo==$isi->nopo) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nopo'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Pemasok" class="control-label col-lg-2">Pemasok </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("pemasok") as $isi) {
                  if ($data_edit->pemasok==$isi->kode_pemasok) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice" class="control-label col-lg-2">No Invoice </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_invoice;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_invoice);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No DO" class="control-label col-lg-2">No DO </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_do;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Dokpab" class="control-label col-lg-2">No Dokpab </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_dokpab;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Dokpab" class="control-label col-lg-2">Tanggal Dokpab </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_dokpab);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="catatan" class="control-label col-lg-2">catatan </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("catatan") as $isi) {
                  if ($data_edit->catatan==$isi->nm_catatan) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_catatan'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="catatan" class="control-label col-lg-2">catatan </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("catatan") as $isi) {
                  if ($data_edit->catatan==$isi->nm_catatan) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_catatan'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Aju" class="control-label col-lg-2">No Aju </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_aju;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Aju" class="control-label col-lg-2">Tanggal Aju </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_aju);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No E-faktur" class="control-label col-lg-2">No E-faktur </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->efaktur;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal E-Faktur" class="control-label col-lg-2">Tanggal E-Faktur </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->tgl_efaktur);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("matauang") as $isi) {
                  if ($data_edit->valuta==$isi->jenis_valas) {

                    echo "<input disabled class='form-control' type='text' value='$isi->jenis_valas'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kurs;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->kurs;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                        
                      </form>
                      <a href="<?=base_index();?>gr-blocked-stock" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
