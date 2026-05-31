<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Packing List</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>packing-list">Packing List</a>
                        </li>
                        <li class="active">Detail Packing List</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Detail Packing List</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_packing_list" method="post" class="form-horizontal" action="<?=base_admin();?>modul/packing_list/packing_list_action.php?act=up">
                            
              <div class="form-group">
                <label for="Packing List Number" class="control-label col-lg-2">Packing List Number <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_packing_list" value="<?=$data_edit->no_packing_list;?>" class="form-control" required readonly>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Delivery Order No" class="control-label col-lg-2">Delivery Order No <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="no_sj" name="no_sj" onchange="pilih_do(this.value)"  data-placeholder="Pilih Delivery Order No..." class="form-control chzn-select" tabindex="2" required readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("pengeluaran") as $isi) {

                  if ($data_edit->no_sj==$isi->no_sj) {
                    echo "<option value='$isi->no_sj' selected>$isi->no_sj</option>";
                  } else {
                  echo "<option value='$isi->no_sj'>$isi->no_sj</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
              <label for="Tanggal DO" class="control-label col-lg-2">Tanggal DO </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_sj;?>" name="tgl_sj"  readonly/>
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Penerima" class="control-label col-lg-2">Penerima <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="penerima" name="penerima" data-placeholder="Pilih Penerima..." class="form-control chzn-select" tabindex="2" required readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->penerima==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Pemilik" class="control-label col-lg-2">Pemilik <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="pemilik" name="pemilik" data-placeholder="Pilih Pemilik..." class="form-control chzn-select" tabindex="2" required readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("pemilik") as $isi) {

                  if ($data_edit->pemilik==$isi->kode_pemilik) {
                    echo "<option value='$isi->kode_pemilik' selected>$isi->nama_pemilik</option>";
                  } else {
                  echo "<option value='$isi->kode_pemilik'>$isi->nama_pemilik</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Invoice No" class="control-label col-lg-2">Invoice No </label>
                <div class="col-lg-10">
                  <input type="text" name="no_invoice" value="<?=$data_edit->no_invoice;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No PO" class="control-label col-lg-2">No PO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_po" value="<?=$data_edit->no_po;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
              <select  id="valuta" name="valuta" data-placeholder="Pilih Valuta..." class="form-control chzn-select" tabindex="2" readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {

                  if ($data_edit->valuta==$isi->jenis_valas) {
                    echo "<option value='$isi->jenis_valas' selected>$isi->jenis_valas</option>";
                  } else {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" id="kurs" name="kurs" value="<?=$data_edit->kurs;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Vehicle No" class="control-label col-lg-2">Vehicle No </label>
                <div class="col-lg-10">
                  <input type="text" name="vehicle_no" value="<?=$data_edit->vehicle_no;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group" id="panel_barang">
                <div class="col-lg-12" style="overflow: scroll;">
                  <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" > </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                     <th style="width: 200px">Jenis Dokpab Asal</th>
                     <th style="width: 200px">Unit</th>
                     <th style="width: 200px">Stock</th>
                     <th style="width: 200px">Qty Barang</th>
                     <th style="width: 200px">Harga</th>
                     <th style="width: 200px">Nilai</th>
                     <th style="width: 200px">N'Weight (KG)</th>
                     <th style="width: 200px">Bruto Weight (KG)</th>
                     <th style="width: 200px">G'Weight (KG)</th>
                     <th style="width: 200px">Lot NO</th>
                     <th style="width: 200px">Prod Date</th>
                     <th style="width: 200px">Exp Date</th>
                     <th style="width: 200px">Packing</th>
                     <th style="width: 200px">Qty (P'Kgs)</th>
                     <th style="width: 200px">Remark</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $no = 1;
                 $q = $db->query("select d.*,b.nm_barang from packing_list_detail d join barang b on b.kd_barang=d.kode  where d.no_sj='".$data_edit->no_sj."' ");
                 foreach ($q as $k) {
                  ?>
                   <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]" style="width: 300px" value="<?= $k->kode." - ".$k->nm_barang ?>" readonly>
                      <input type="hidden" name="kode_input[]" id="kode_input_<?= $no ?>" value="<?= $k->kode ?>"> 
                     </td> 
                    <td style="width: 150px">
                      <select  onchange="check_stock(this.value,1)" name="jenis_dokpab_<?= $no ?>" id="jenis_dokpab_<?= $no ?>" class="form-control" style="min-width: 150px" readonly>
                        <option value="">-Pilih Jenis Dokumen</option>
                        <?php
                        foreach ($db->query("select jenis from jenisbcmasuk") as $kk) {
                          if ($kk->jenis==$k->jenis_dokpab) {
                            echo "<option value='$kk->jenis' selected>$kk->jenis</option>";
                          }else{
                            echo "<option value='$kk->jenis'>$kk->jenis</option>";
                          }
                          
                        }
                        ?>
                      </select>
                    </td>
                     <td><input value="<?= $k->unit ?>" type="text" id="form_unit_<?= $no ?>" class="form-control" name="unit[]" style="width: 150px" readonly="" readonly></td> 
                     <td><input type="text" id="form_stock_<?= $no ?>" class="form-control" name="stock[]" style="width: 150px" readonly="">  </td>   
                     <td><input value="<?= $k->jumlah ?>" type="text" onkeyup="sum_nilai_cek(this.value,'1')"  id="form_qty_<?= $no ?>" class="form-control" name="jumlah_<?= $no ?>" required="" style="width: 150px" readonly><i id="error_stock_<?= $no ?>" style="color: red"></i></td>
                     <td><input value="<?= formatAngka($k->harga) ?>" type="text" onkeyup="sum_nilai(this.value,'1')" id="form_harga_<?= $no ?>" class="form-control" name="harga[]" style="width: 150px" readonly></td>
                     <td><input value="<?= formatAngka($k->nilai) ?>" type="text" id="form_nilai_<?= $no ?>" class="form-control" name="nilai[]" readonly="" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->berat ?>" type="text" id="form_berat_<?= $no ?>" class="form-control" name="berat[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->bruto ?>" type="text" id="form_bruto_<?= $no ?>" class="form-control" name="bruto[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->berat2 ?>" type="text" id="form_berat2_<?= $no ?>" class="form-control" name="berat2[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->lot_no ?>" type="text" id="form_lot_<?= $no ?>" class="form-control" name="lot_no[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->prod_date ?>" type="date" id="form_prod_date_<?= $no ?>" class="form-control" name="prod_date[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->exp_date ?>" type="date" id="form_exp_date_<?= $no ?>" class="form-control" name="exp_date[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->packing ?>" type="text" id="form_packing_<?= $no ?>" class="form-control" name="packing[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->qty_packing ?>" type="text" id="form_qty_packing_<?= $no ?>" class="form-control" name="qty_packing[]" style="width: 150px" readonly></td>
                     <td><input value="<?= $k->remark ?>" type="text" id="form_remark_<?= $no ?>" class="form-control" name="remark[]" style="width: 150px" readonly></td>
                   </tr>
                   <?php
                    $no++;
                    }
                   ?>
                 </tbody>
                 
               </table>
              </div>
               <input type="hidden" id="jml" value="<?= ($no) ?>">
              </div>
              
                            <input type="hidden" name="id" value="<?=$data_edit->id;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>packing-list" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                               
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">

   function pilih_do(no_sj) {
    if (no_sj === "") return;

    $.ajax({
        url: "<?= base_url() ?>modul/packing_list/packing_list_action.php?act=get_detail",
        type: "POST",
        dataType: "json",
        data: { no_sj: no_sj },
        success: function (res) {

            // Pastikan respon valid
            if (!res) {
                alert("Data DO tidak ditemukan!");
                return;
            }

            // Isi field tanggal DO
            $("input[name='tgl_sj']").val(res.tgl_sj);

            // Isi field penerima
            $("#penerima").val(res.penerima).trigger("chosen:updated");

            // Isi field pemilik
            $("#pemilik").val(res.pemilik).trigger("chosen:updated");

            // Isi Invoice No
            $("input[name='no_invoice']").val(res.no_invoice);

            // Isi No PO
            $("input[name='no_po']").val(res.no_po);

            // Isi Valuta
            $("#valuta").val(res.valuta).trigger("chosen:updated");

            // Isi Kurs (jika ada field)
            if ($("#kurs").length > 0) {
                $("#kurs").val(res.kurs);
            }
        },
        error: function (xhr, status, error) {
            alert("Gagal mengambil data DO. Error: " + error);
        }
    });
    $.ajax({
        url: "<?= base_url() ?>modul/packing_list/packing_list_action.php?act=get_detail_barang",
        type: "POST",
     //   dataType: "json",
        data: { no_sj: no_sj }, 
        success: function (res) {
          $("#panel_barang").html(res);
        },
        error: function (xhr, status, error) {
            alert("Gagal mengambil data DO. Error: " + error);
        }
    });
}




  function check_stock(val,no){
    $.ajax({
       url  : "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=cek_stock",
       type : "POST",
       data : {
         kode : $("#kode_input_"+no).val(), 
         jenis_dokpab : val
       },
       success : function(data){ 
         $("#form_stock_"+no).val(data);
       }
    });
  }

  function cek_valuta(kode){ 
  //  var kode = $("#KODE_VALUTA").val();
  $("#kurs").attr('readonly',true);
  $("#kurs").val('get data ...');
    $.ajax({
       url : "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_currency",
       type : "POST",
       data : {
         kode : kode, 
         //d_header : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
          $("#kurs").val(data);
          $("#kurs").attr('readonly',false);
        // save_data(data,'NDPBM',$('#ID').val(),'ws_header','id_header');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });
  }

    function pilih_po(no_po){
       $.ajax({
          url: "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_so",
          data: { no_po: no_po },
          type : 'POST',
          success: function (data) {
            $("#panel_barang").html(data);
           // $("#satuan").val(data.satuan); 
          } 
       });
        $.ajax({
          url: "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_pemasok",
          data: { no_po: no_po },
          type : 'POST',
          dataTye : 'JSON',
          success: function (data) { 
            $("#penerima").val(data.id_customer).trigger('chosen:updated');
            $('#penerima').val(data.id_customer).trigger('change');
            $("#valuta").val(data.valuta).trigger('chosen:updated');
            $('#valuta').val(data.valuta).trigger('change');
            $("#nopo").val(data.no_po);  
          } 
       });
    }

    function show_panel(val){
      // alert(val);
       $("#panel_barang").show();
    }
    function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

   function sum_nilai_cek(val,id) { 
      var kode  = $("#kode_input_"+id).val();
      var jml   = parseFloat(formatNumber(val));
      var stock = parseFloat(formatNumber($("#form_stock_"+id).val()));
      if (jml>stock) {
          // alert("Inputan melebihi stock");
          $("#error_stock_"+id).html("Inputan melebihi stock");
          $("#error_stock_"+id).show();
          $("#form_qty_"+id).val('');
          $("#form_qty_"+id).focus();
      }else{ 
        var a = $("#form_qty_"+id).val();
         var b = $("#form_harga_"+id).val();
         if (a=='') {
          a=0;
         }else{
          a = parseFloat(a);
         }
         if (b=='') {
          b=0;
         }else{
          b=parseFloat(b);
         }
         c = a*b;
         $("#form_nilai_"+id).val(formatAngka(c));
        $("#error_stock_"+id).hide();
      }
       

    }

    function add_baris() {
    var id_baris = parseInt($("#jml").val()) + 1;

    var baris = `
    <tr id="baris_${id_baris}">
        <td style="text-align: center">
            <a style="cursor: pointer;" onclick="hapus_baris('${id_baris}')">
                <i class="fa fa-trash-o" style="font-size: 25px;"></i>
            </a>
        </td>

        <td>
            <input type="text" id="form_kode_${id_baris}" placeholder="Kode Barang"
                onclick="cari_kode('${id_baris}')"
                class="form-control" name="kode[]" style="width: 300px">
            <input type="hidden" name="kode_input[]" id="kode_input_${id_baris}">
        </td>

        <td>
            <select onchange="check_stock(this.value,${id_baris})"
                name="jenis_dokpab_${id_baris}" id="jenis_dokpab_${id_baris}"
                class="form-control">
                <option value="">-Pilih Jenis Dokumen</option>
                <?php foreach ($db->query("select jenis from jenisbcmasuk") as $k) { 
                    echo "<option value='$k->jenis'>$k->jenis</option>";
                } ?>
            </select>
        </td>

        <td><input type="text" id="form_unit_${id_baris}" class="form-control" name="unit[]" style="width: 150px" readonly=""></td>
        <td><input type="text" id="form_stock_${id_baris}" class="form-control" name="stock[]" style="width: 150px" readonly=""></td>

        <td>
            <input type="text" onkeyup="sum_nilai_cek(this.value,'${id_baris}')"
                id="form_qty_${id_baris}" class="form-control" name="jumlah_${id_baris}"
                required="" style="width: 150px">
            <i id="error_stock_${id_baris}" style="color: red"></i>
        </td>

        <td><input type="text" onkeyup="sum_nilai(this.value,'${id_baris}')" id="form_harga_${id_baris}" class="form-control" name="harga[]" style="width: 150px"></td>

        <td><input type="text" id="form_nilai_${id_baris}" class="form-control" name="nilai[]" readonly="" style="width: 150px"></td>

        <td><input type="text" id="form_berat_${id_baris}" class="form-control" name="berat[]" style="width: 150px"></td>

        <td><input type="text" id="form_bruto_${id_baris}" class="form-control" name="bruto[]" style="width: 150px"></td>

        <td><input type="text" id="form_berat2_${id_baris}" class="form-control" name="berat2[]" style="width: 150px"></td>

        <td><input type="text" id="form_lot_${id_baris}" class="form-control" name="lot_no[]" style="width: 150px"></td>

        <td><input type="date" id="form_prod_date_${id_baris}" class="form-control" name="prod_date[]" style="width: 150px"></td>

        <td><input type="date" id="form_exp_date_${id_baris}" class="form-control" name="exp_date[]" style="width: 150px"></td>

        <td><input type="text" id="form_packing_${id_baris}" class="form-control" name="packing[]" style="width: 150px"></td>

        <td><input type="text" id="form_qty_packing_${id_baris}" class="form-control" name="qty_packing[]" style="width: 150px"></td>

        <td><input type="text" id="form_remark_${id_baris}" class="form-control" name="remark[]" style="width: 150px"></td>
    </tr>
    `;

    $("#isi_tabel").append(baris);
    $("#jml").val(id_baris);

    // Refresh chosen-select jika digunakan
    if ($(".chzn-select").length > 0) {
        $(".chzn-select").chosen();
    }
}


    function sum_nilai(val,id) {
       var a = formatNumber($("#form_qty_"+id).val());
       var b = formatNumber($("#form_harga_"+id).val());
       if (a=='') {
        a=0;
       }else{
        a = parseFloat(a);
       }
       if (b=='') {
        b=0;
       }else{
        b=parseFloat(b);
       }
       c = a*b;
       $("#form_nilai_"+id).val(formatAngka(c));

    }

      function cari_kode(id) {   
       if ($("#jenisbckeluar_jenis_dokpab").val()=='') {
         alert("Jenis Dokumen Pabean Belum Dipilih");
       }else{
            $('#form_kode_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);

                              $.ajax({
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_incoming2",
                                data: { 
                                  kode   :  ui.item.kd_barang, 
                                  jumlah : '0',
                                  jenis_dokpab : $("#jenisbckeluar_jenis_dokpab").val(),
                                  jenis : $('input[name="dari"]:checked').val()
                                }, 
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                   $("#form_stock_"+id).val(data.stock); 
                                }
                              });
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_"+id).val(data);
                              }
                            });

                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
       }
                      
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
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_packing_list").validate({
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
        
        rules: {
            
          no_packing_list: {
          required: true,
          //minlength: 2
          },
        
          no_sj: {
          required: true,
          //minlength: 2
          },
        
          penerima: {
          required: true,
          //minlength: 2
          },
        
          pemilik: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          no_packing_list: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          no_sj: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          penerima: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          pemilik: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
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
