<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Data Pegawai</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>data-pegawai">Data Pegawai</a></li>
                        <li class="active">Detail Data Pegawai</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Data Pegawai</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="NIK" class="control-label col-lg-2">NIK </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nik;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2">NPWP </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->npwp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Pegawai" class="control-label col-lg-2">Nama Pegawai </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->namaPegwai;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Jenis Kelamin" class="control-label col-lg-2">Jenis Kelamin </label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->kelamin;?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                <div class="form-group">
                        <label for="Agama" class="control-label col-lg-2">Agama </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("h_agama") as $isi) {
                  if ($data_edit->agama==$isi->idAgama) {

                    echo "<input disabled class='form-control' type='text' value='$isi->namaAgama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No HP" class="control-label col-lg-2">No HP </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->noHp;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Email" class="control-label col-lg-2">Email </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->email;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2">Alamat </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->alamat;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Rekening" class="control-label col-lg-2">No Rekening </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->noRek;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Bank" class="control-label col-lg-2">Bank </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->bank;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Provinsi" class="control-label col-lg-2">Provinsi </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {
                  if ($data_edit->idProvinsi==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_wil'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Kota" class="control-label col-lg-2">Kota </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {
                  if ($data_edit->idKota==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_wil'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Kecamatan" class="control-label col-lg-2">Kecamatan </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {
                  if ($data_edit->idKecamatan==$isi->id) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nm_wil'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="foto" class="control-label col-lg-2">foto </label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                    <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 200px; height: 150px;">
                    <img src="../../../upload/data_pegawai/<?=$data_edit->foto?>"></div>
                  </div>
                  </div>
                      </div><!-- /.form-group -->

                        
                      </form>
                      <a href="<?=base_index();?>data-pegawai" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
