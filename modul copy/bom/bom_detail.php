<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>BOM</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>bom">BOM</a></li>
                        <li class="active">Detail BOM</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail BOM</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        <div class="form-group">
                        <label for="Kode Barang" class="control-label col-lg-2">Kode Barang </label>
                        <div class="col-lg-10">
              <select readonly  id="kodebj" name="kodebj" onchange="get_detail_barang(this.value)" data-placeholder="Pilih Kode Barang..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->query("select * from barang where kd_kategori='K02'") as $isi) {

                  if ($data_edit->kodebj==$isi->kd_barang) {
                    echo "<option value='$isi->kd_barang' selected>$isi->kd_barang</option>";
                  } else {
                  echo "<option value='$isi->kd_barang'>$isi->kd_barang</option>";
                    }
               } ?>
              </select>
          </div>
                      </div>

              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2">Nama Barang </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nm_barang;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="satuan" class="control-label col-lg-2">satuan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->satuan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="jumlah" class="control-label col-lg-2">jumlah </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->jumlah;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
               <div class="form-group" id="form_ro">
                
                <div class="col-lg-12">
                <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                      
                     </th>
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>Qty</th>                     
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                  <?php
                  $no=1;
                  $qq = $db->query("select b.satuan,d.kodebb,d.status,b.nm_barang,b.kd_barang, d.jumlah from bom_detail d join barang b on b.kd_barang=d.kodebb where id_bom=?",array(uri_segment(3)));
                  foreach ($qq as $kk) {
                  ?>
                  <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" readonly value="<?= $kk->kodebb ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" value="<?= $kk->kodebb ?>"  id="kode_input_<?= $no ?>"> 
                     </td> 
                     <td><input type="text" id="form_unit_<?= $no ?>" readonly value="<?= $kk->satuan ?>"  class="form-control" name="unit[]"  readonly=""></td> 
                    
                     <td><input type="number" id="form_qty_<?= $no ?>"  readonly value="<?= $kk->jumlah ?>"  class="form-control" name="qty[]" onkeyup="cek_stok('1',this.value)" required></td>
                     <td><input type="text" id="form_ket_<?= $no ?>"  readonly value="<?= $kk->status ?>"  class="form-control" name="ket[]" ></td>
                   </tr>
                  <?php
                  $no++;
                  }
                  ?>
                   
                 </tbody> 
                
               </table>
                Total Bomlist : <?= ($no-1) ?>
                 </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
              
              </div>
              
                        
                      </form>
                      <a href="<?=base_index();?>bom" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
