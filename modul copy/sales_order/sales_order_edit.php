

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Sales Order</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_sales_order" method="post" class="form-horizontal" action="<?=base_admin();?>modul/sales_order/sales_order_action.php?act=up">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="Quotation" class="control-label col-lg-2">Quotation </label>
                        <div class="col-lg-10">
              <select  id="id_quotation" name="id_quotation" data-placeholder="Pilih Quotation..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("sales_quotation") as $isi) {

                  if ($data_edit->id_quotation==$isi->id_quotation) {
                    echo "<option value='$isi->id_quotation' selected>$isi->id_quotation</option>";
                  } else {
                  echo "<option value='$isi->id_quotation'>$isi->id_quotation</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
              <label for="Saler Order Date" class="control-label col-lg-2">Saler Order Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->so_date;?>" name="so_date" required=""   />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
            <div class="form-group"  style="display: none;">
              <label for="Currency" class="control-label col-lg-2">Store </label>
           <div class="col-lg-10">
            <select  id="no_store"  name="no_store" data-placeholder="Pilih Store ..." class="form-control chzn-select" tabindex="2" required=""  >
               <option value=""></option>
               <?php foreach ($db->query("select id_store,nama_store from store_location ") as $isi) {
                if ($data_edit->no_store==$isi->id_store) {
                   echo "<option value='$isi->id_store' selected>$isi->nama_store</option>";
                }else{
                   echo "<option value='$isi->id_store'>$isi->nama_store</option>";
                 
               } 
                }
                ?>
              </select>
            </div>
          </div>
          <div class="form-group">
                        <label for="Currency" class="control-label col-lg-2">Currency </label>
                        <div class="col-lg-10">
              <select  id="currency" name="currency" data-placeholder="Pilih Currency..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {

                  if ($data_edit->currency==$isi->jenis_valas) {
                    echo "<option value='$isi->jenis_valas' selected>$isi->jenis_valas</option>";
                  } else {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Rupiah Rate" class="control-label col-lg-2">Rupiah Rate </label>
                <div class="col-lg-10">
                  <input type="text" name="rupiah_rate" value="<?=$data_edit->rupiah_rate;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kur TT Counter Sale" class="control-label col-lg-2">Kur TT Counter Sale </label>
                <div class="col-lg-10">
                  <input type="text" name="rupiah_rate_sale" value="<?=$data_edit->rupiah_rate_sale;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Company Name" class="control-label col-lg-2">Company Name </label>
                        <div class="col-lg-10">
              <select  id="kode_penerima" name="kode_penerima" data-placeholder="Pilih Company Name..." class="form-control chzn-select" tabindex="2" required=""  >
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->kode_penerima==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
             <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Consignee </label>
                <div class="col-lg-10">
                  <input type="text" name="consignee" id="consignee" value="<?=$data_edit->consignee;?>"  placeholder="Consignee" class="form-control" >
                </div>
              </div><!-- /.form-group -->
             <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Notify Party </label>
                <div class="col-lg-10">
                  <input type="text" name="notify_party" id="notify_party" value="<?= $data_edit->notify_party ?>"  placeholder="Notify Party" class="form-control" >
                </div>
              </div><!-- /.form-group -->

               <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Other Reference </label>
                <div class="col-lg-10">
                  <input type="text" name="other_reference" id="other_reference" value="<?= $data_edit->other_reference ?>" placeholder="Other Reference" class="form-control" >
                </div>
              </div>
                  <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Note </label>
                <div class="col-lg-10">
                  <textarea class="form-control" name="catatan" placeholder="Note"><?= $data_edit->catatan ?></textarea>
                </div>
              </div>
                    </div>
                    <div class="col-md-6">
                       <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2">Tax </label>
                      <div class="col-lg-10">
                        
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio1" value="include" <?=($data_edit->tax=="include")?"checked":"";?>  onchange="set_panel_pajak()">
                    <label for="radio1" style="padding-left: 5px;" >
                      Include
                    </label>
                </div>
                
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio2" value="exclude" <?=($data_edit->tax=="exclude")?"checked":"";?>  onchange="set_panel_pajak()" >
                    <label for="radio2" style="padding-left: 5px;">
                      Exclude
                    </label>
                </div>
                
                      </div>
                </div><!-- /.form-group -->
                
              <div class="form-group">
                <label for="Sales ID" class="control-label col-lg-2">Sales ID </label>
                <div class="col-lg-10">
                  <input type="text" name="sales_id" value="<?=$data_edit->sales_id;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->


               <div class="form-group">
                <label for="Purchase Ref" class="control-label col-lg-2">No PO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_po" placeholder="No PO" class="form-control" value="<?=$data_edit->no_po;?>">
                </div>
              </div>
              
              <div class="form-group">
                <label for="Purchase Ref" class="control-label col-lg-2">Purchase Ref </label>
                <div class="col-lg-10">
                  <input type="text" name="purchase_ref" value="<?=$data_edit->purchase_ref;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Entry User </label>
                <div class="col-lg-10">
                  <input type="text" name="user" value="<?=$data_edit->user;?>" class="form-control" readonly >
                </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label for="delivery_term" class="control-label col-lg-2">Delivery Term </label>
                <div class="col-lg-10">
                  <input type="text" name="delivery_term" id="delivery_term" value="<?= $data_edit->delivery_term ?>" placeholder="Delivery Term" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Term (Days)" class="control-label col-lg-2">Term (Days) </label>
                <div class="col-lg-10">
                  <input type="text" name="term" value="<?=$data_edit->term;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Discount" class="control-label col-lg-2">Discount </label>
                <div class="col-lg-10">
                  <input type="text" name="discount" value="<?=$data_edit->discount;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Delivery Date" class="control-label col-lg-2">Delivery Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->delivery_date;?>" name="delivery_date" required=""   />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Shipping Address" class="control-label col-lg-2">Shipping Address </label>
                <div class="col-lg-10">
                  <input type="text" name="shipping_address" value="<?=$data_edit->shipping_address;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->


           <div class="form-group">
                <label for="Discount" class="control-label col-lg-2">Transport</label>
                <div class="col-lg-10">
                  <input type="text" name="vessel" class="form-control" value="<?= $data_edit->vessel ?>">
                   From
                    <input type="text" name="dari" class="form-control" placeholder="From" value="<?= infokb()->nama ?>" ><br>
                    to <br>
                    <input type="text" name="ke" class="form-control" placeholder="To" value="<?= $data_edit->ke ?>" >
                </div>

              </div><!-- /.form-group -->
              
                    </div>
                  </div>
                

               

               <div class="col-lg-12">
                <table class="table">
                 <thead> 
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
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
                       <td><input type="text" value="<?= $k->kd_barang." ".$k->nm_barang ?>" id="form_kode_<?= $no ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                        <input type="hidden" value="<?= $k->kd_barang ?>" name="kode_input[]" id="kode_input_<?= $no ?>">  
                       </td> 
                       <td><input type="text" value="<?= $k->satuan ?>" id="form_unit_<?= $no ?>" class="form-control" name="unit[]"  readonly=""></td>
                       <td><input type="text" value="<?= $k->qty ?>" onkeyup="sum_nilai(this.value,<?= $no ?>)" id="form_qty_<?= $no ?>" class="form-control" name="qty[]" style='text-align: right;'   required></td>
                       <td><input type="text" value="<?= $k->price ?>" onkeyup="sum_nilai(this.value,<?= $no ?>)" id="form_harga_<?= $no ?>" class="form-control" name="harga[]"  style='text-align: right;'  required></td>
                        <td><input type="text" id="form_nilai_<?= $no ?>" class="form-control" name="nilai[]" style='text-align: right;' value="<?= $nilai ?>" readonly></td>
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
                      $j=1;
                  // foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                  //    if (count($pajak)>0) {
                  //       for ($i=0; $i <count($pajak) ; $i++) { 
                  //        if ($pajak[$i]==$p->jenis_pajak) {
                  //          echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak' checked> $p->jenis_pajak [$p->jumlah%]</label><br>"; 
                  //        }else{
                  //         // echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                  //        }
                  //       }
                  //    }else{
                  //      echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                  //    }
                  //    $j++;
                  // }
                  ?>
                   </td>

                    <td style="text-align: right;">
                      <?php
                      $total_pajak = 0;
                      $j=1;
                  // foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                    
                  //     if (count($pajak)>0) {
                  //       for ($i=0; $i <count($pajak) ; $i++) { 
                  //        if ($pajak[$i]==$p->jenis_pajak) {
                  //          echo "<input id='nilai_".$j."' type='text' class='form-control' value='".(($p->jumlah/100)*$tot_nilai)."' readonly style='text-align: right;'>  ";
                  //          $total_pajak = $total_pajak + (($p->jumlah/100)*$tot_nilai);
                  //        }else{
                  //          // echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                  //        }
                  //        echo "<input type='hidden' id='nilai_pajak_$j' value='$p->jumlah' >";
                  //       }
                  //    }else{
                  //      echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                  //    }
                  //    $j++;
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
               <input type="hidden" id="jml" value="<?= ($no-1) ?>">
                <input type="hidden" id="jml2" value="<?= ($j) ?>">
              
                            <input type="hidden" name="id" value="<?=$data_edit->id_sales_order;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>sales-order" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">




   function set_panel_pajak(){
   if($('#radio1').is(':checked')) { 
     
         $("#baris_pajak").show();
   
  }else{
      $("#baris_pajak").hide();
  }
 }
   function set_pajak(jenis,jumlah){
                  var total_tmp = 0;
                   var nilai_total = parseFloat($("#tmp_total").val());
                  if ($("#tmp_total").val()=='') {
                      var nilai_total = parseFloat($("#nilai_total").val());
                  }
                 
                  if ($('#pajak_' + jenis).is(":checked")) {
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum;
                    nilai_total = nilai_total + nilai;

                   // alert(nilai);
                     $("#nilai_"+jenis).val(nilai.toFixed(3));
                  }else{
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum; 
                     nilai_total = nilai_total - nilai; 
                    $("#nilai_"+jenis).val("");
                  }
                  $("#tmp_total").val(nilai_total.toFixed(0));
                  $("#grand_total").val(nilai_total.toFixed(3));
                }
    function hapus_baris(id){ 
                    $("#baris_"+id).remove();
  }
  
   function sum_nilai(val,id){
    var jml = parseFloat($("#jml").val());
    var jml2 = parseFloat($("#jml2").val());
    var total = 0;
    var total_qty = 0;
    var total_harga = 0;
    var grand_total = 0;
    for (var i = 1; i <= jml; i++) {
       total = total + (parseFloat($("#form_qty_"+i).val()) * parseFloat($("#form_harga_"+i).val()));
       total_qty = total_qty + parseFloat($("#form_qty_"+i).val()) ;
       total_harga = total_harga + parseFloat($("#form_harga_"+i).val()) ;

    }
    var total_pajak = 0;
    for (var i = 1; i <= jml2; i++) {
      if ($('#pajak_'+i).is(":checked")) { 
        tmp_pajak = parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
        $("#nilai_"+i).val(tmp_pajak.toFixed(3));
        total_pajak = total_pajak + parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
      }
    }
    $("#tmp_total_pajak").val(total_pajak); 
    grand_total = total+total_pajak;
    $("#nilai_total").val(total); 
    $("#tmp_total").val(); 
    $("#total_qty").val(total_qty);
    $("#grand_total").val(grand_total.toFixed(3));
    $("#total_harga").val(total_harga);
    var qty = parseFloat($("#form_qty_"+id).val());
    var harga = parseFloat($("#form_harga_"+id).val());
    nilai = qty * harga;
    $("#form_nilai_"+id).val(nilai); 
    
  } 

   function set_panel_pajak(){
   if($('#radio1').is(':checked')) { 
     
         $("#baris_pajak").show();
      
       
      
   
  }else{
      $("#baris_pajak").hide();
  }
 }

   function set_panel_pajak2(){
   if($('#radio2').is(':checked')) { 
     
         $("#baris_pajak").hide();
      
       
      
   
  }else{
      $("#baris_pajak").show();
  }
 }

 function get_pemasok(val){
   $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pemasok",
          data: { kode_penerima: val },
          type : 'POST',
          success: function (data) {
            $("#shipping_address").val(data);
           // $("#satuan").val(data.satuan);
          } 
       });

   
 }

 



   function pilih_pr(no_pr){
       $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pr",
          data: { no_pr: no_pr },
          type : 'POST',
          success: function (data) {
            $("#form_pr").html(data);
           // $("#satuan").val(data.satuan);
          } 
       });

 

       $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pr2",
          data: { no_pr: no_pr },
          type : 'POST',
          dataType : 'JSON',
          success: function (data) {
           // $('.chzn-select').select2(); 
             $('#kode_penerima').val(data.kode_penerima).trigger('chosen:updated');
              $('#currency').val(data.currency).trigger('chosen:updated');
             $('#term').val(data.term);
             $('#sales_id').val(data.sales_id);
             $('#rupiah_rate').val(data.rupiah_rate);
              $('#rupiah_rate_sale').val(data.rupiah_rate_sale);
             $('#discount').val(data.sales_id);
             if (data.tax=='include') {
               $("#radio1").prop('checked', true);
               $("#radio2").prop('checked', false);
             }else{
               $("#radio2").prop('checked', true);
               $("#radio1").prop('checked', false);
             }
              $.ajax({
              url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pemasok",
              data: { kode_penerima: data.kode_penerima },
              type : 'POST',
              success: function (data) {
                $("#shipping_address").val(data);
               // $("#satuan").val(data.satuan);
              } 
           });
           // $("#satuan").val(data.satuan);
          } 
       });
   }

   function get_detail_barang(kd_barang){
    $.ajax({
                            url: "<?= base_url() ?>inc/get_barang_detail.php",
                            data: { kd_barang: kd_barang },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {
                              $("#nm_barang").val(data.nm_barang);
                              $("#satuan").val(data.satuan);

                            } 
                          });
  }

   function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_'+id_baris+'"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="text"  id="form_qty_'+id_baris+'" onkeyup="sum_nilai(this.value,'+id_baris+')" class="form-control" name="qty[]" style="text-align:right" required></td><td><input type="text" onkeyup="sum_nilai(this.value,'+id_baris+')"  id="form_harga_'+id_baris+'" class="form-control" name="harga[]" style="text-align:right"  required></td> <td><input type="text" id="form_nilai_'+id_baris+'" class="form-control" name="nilai[]"  readonly="" style="text-align:right"></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
    }

     function cari_kode(id) {   
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>cari_kode.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json", 
                            success: function (data) {
                               if(!data.length){
                                  alert("data tidak ditemukan"); 
                                  $("#form_kode_"+id).val(''); 
                                  // var result = [
                                  //     {
                                  //         kd_barang: 'No matches found', 
                                  //         nm_barang: response.term
                                  //     }
                                  // ];
                                  // response(result);
                              }else{
                                response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang,
                                  packing_size: item.packing_size
                                };
                              }))

                              }

                              
                            }
                          })
                        },
                        select: function (event, ui) {
                          // if (!ui.content.length) {
                          //    alert("data tidak ditemukan");
                          // }
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>cari_kode.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_"+id).val(data);
                              }
                            });
                             return false;
                         }
                         // , 
                         // response: function(event, ui) { 
                         //        if (!ui.content.length) {
                         //            var noResult = { value:"",label:"No results found" };
                         //            ui.content.push(noResult);
                         //        }
                         //    }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+' '+item.packing_size+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       }; 
  }
    $(document).ready(function() {
    
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    
    $("#edit_sales_order").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        

        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
