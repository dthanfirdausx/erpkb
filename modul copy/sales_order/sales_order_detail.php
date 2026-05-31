<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Sales Order</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>sales-order">Sales Order</a></li>
                        <li class="active">Detail Sales Order</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Sales Order</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        <div class="form-group">
                        <label for="Quotation" class="control-label col-lg-2">Quotation </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("sales_quotation") as $isi) {
                  if ($data_edit->id_quotation==$isi->id_quotation) {

                    echo "<input disabled class='form-control' type='text' value='$isi->id_quotation'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Saler Order Date" class="control-label col-lg-2">Saler Order Date </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->so_date);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          <div class="form-group"> 
                        <label for="Currency" class="control-label col-lg-2">Currency </label>
                        <div class="col-lg-10">
              <?php foreach ($db->query("select * from matauang group by jenis_valas") as $isi) {
                  if ($data_edit->currency==$isi->jenis_valas) {

                    echo "<input disabled class='form-control' type='text' value='$isi->jenis_valas'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Rupiah Rate" class="control-label col-lg-2">Rupiah Rate </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->rupiah_rate;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kur TT Counter Sale" class="control-label col-lg-2">Kur TT Counter Sale </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->rupiah_rate_sale;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Company Name" class="control-label col-lg-2">Company Name </label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("penerima") as $isi) {
                  if ($data_edit->kode_penerima==$isi->kode_penerima) {

                    echo "<input disabled class='form-control' type='text' value='$isi->nama'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->

                <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2">Tax </label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->tax;?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                
              <div class="form-group">
                <label for="Sales ID" class="control-label col-lg-2">Sales ID </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->sales_id;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Purchase Ref" class="control-label col-lg-2">Purchase Ref </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->purchase_ref;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Entry User </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->user;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Term (Days)" class="control-label col-lg-2">Term (Days) </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->term;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Discount" class="control-label col-lg-2">Discount </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->discount;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Delivery Date" class="control-label col-lg-2">Delivery Date </label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->delivery_date);?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Shipping Address" class="control-label col-lg-2">Shipping Address </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->shipping_address;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->

                <div class="col-lg-12">
                <table class="table">
                 <thead> 
                   <tr>
                     <th style="width:50px;text-align: center">
                       
                     </th>
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>Qty</th>    
                     <th>Harga</th> 
                     <th>Nilai</th>                
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $no=1; 
                 $total_qty =0;
                 $total_harga = 0;
                 $total_nilai = 0;
                 $tot_nilai=0;
                  $q = $db->query("select d.*,b.nm_barang,b.satuan from sales_order_detail d 
join barang b on b.kd_barang=d.kd_barang  where d.id_sales_order='$data_edit->id_sales_order'  ");   
                    foreach ($q as $k) {
                      $nilai = ($k->price * $k->qty);
                     ?>
                      <tr id="baris_<?= $no ?>">
                       <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                       <td><input type="text" value="<?= $k->kd_barang." ".$k->nm_barang ?>" id="form_kode_<?= $no ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]" readonly  >
                        <input type="hidden" value="<?= $k->kd_barang ?>" name="kode_input[]" id="kode_input_<?= $no ?>">  
                       </td> 
                       <td><input type="text" value="<?= $k->satuan ?>" id="form_unit_<?= $no ?>" class="form-control" name="unit[]"  readonly=""></td>
                       <td><input type="number" value="<?= $k->qty ?>" onkeyup="sum_nilai(this.value,<?= $no ?>)" id="form_qty_<?= $no ?>" class="form-control" name="qty[]" style='text-align: right;' readonly  required></td>
                       <td><input type="text" value="<?= $k->price ?>" onkeyup="sum_nilai(this.value,<?= $no ?>)" id="form_harga_<?= $no ?>" class="form-control" name="harga[]"  style='text-align: right;' readonly required></td>
                        <td><input type="number" id="form_nilai_<?= $no ?>" class="form-control" name="nilai[]" style='text-align: right;' value="<?= $nilai ?>" readonly></td>
                       <td><input type="text" id="form_ket_<?= $no ?>" value="<?= $k->ket ?>" class="form-control" name="ket[]" ></td> 
                     </tr>
                     <?php
                     $no++; 
                     $total_qty = $total_qty + $k->qty;
                     $total_harga = $total_harga + $k->price;
                     $total_nilai = $total_nilai + $k->nilai;
                      $tot_nilai = $tot_nilai + ($k->price * $k->qty);
                    }
                 ?>
               <tfoot>
                <tr id="baris_pajak"> 
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                   <td>
                      <?php
                  // foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                  //    if (count($pajak)>0) {
                  //       for ($i=0; $i <count($pajak) ; $i++) { 
                  //        if ($pajak[$i]==$p->jenis_pajak) {
                  //          echo "<label style='margin-top:5px'><input id='pajak_".$p->id_pajak."' onchange='set_pajak(\"$p->id_pajak\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak' checked> $p->jenis_pajak [$p->jumlah%]</label><br>"; 
                  //        }else{
                  //         echo "<label style='margin-top:5px'><input id='pajak_".$p->id_pajak."' onchange='set_pajak(\"$p->id_pajak\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                  //        }
                  //       }
                  //    }else{
                  //      echo "<label style='margin-top:5px'><input id='pajak_".$p->id_pajak."' onchange='set_pajak(\"$p->id_pajak\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                  //    }
                     
                  // }
                  ?>
                   </td>

                    <td style="text-align: right;">
                      <?php
                      $total_pajak = 0;
                  // foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                    
                  //     if (count($pajak)>0) {
                  //       for ($i=0; $i <count($pajak) ; $i++) { 
                  //        if ($pajak[$i]==$p->jenis_pajak) {
                  //          echo "<input id='nilai_".$p->id_pajak."' type='text' class='form-control' value='".(($p->jumlah/100)*$tot_nilai)."' readonly style='text-align: right;'>  ";
                  //          $total_pajak = $total_pajak + (($p->jumlah/100)*$tot_nilai);
                  //        }else{
                  //          echo "<input id='nilai_".$p->id_pajak."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                  //        }
                  //       }
                  //    }else{
                  //      echo "<input id='nilai_".$p->id_pajak."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                  //    }
                  // }
                  ?>
                   </td>

                   
                 </tr>
                  <tr>
                   <td colspan="5" style="text-align: center;">Grand Total</td>
                   <td>
                     <input type="hidden" id="tmp_total" value="<?= ($total_pajak+$tot_nilai) ?>"> 
                     <input type="hidden" id="tmp_total_pajak" value="<?= $total_pajak ?>"> 
                     <input type="text" id="grand_total" class="form-control" style="text-align: right;" value="<?= ($total_pajak+$tot_nilai) ?>" readonly="">
                   </td>

                 </tr>
                </tfoot>
                 </tbody>
               </table>
                </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
              
                        
                      </form>
                      <a href="<?=base_index();?>sales-order" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
