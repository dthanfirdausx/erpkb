<!-- Content Header (Page header) -->

    <section class="content-header">
        <h1>LP Produksi</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>lp-barang-jadi">LP Produksi</a>
            </li>
            <li class="active">Add LP Produksi</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add LP Barang Jadi</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_lp_barang_jadi" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/lp_barang_jadi/lp_barang_jadi_action.php?act=in">
             <!-- ===== SAP HEADER ===== -->



<!-- ===== PRODUCTION ORDER MODE ===== -->
<div class="form-group">
  <label class="control-label col-lg-2">Mode</label>
  <div class="col-lg-10">

    <label>
      <input type="radio" name="mode_produksi" value="manual" checked onclick="mode_manual()"> Manual
    </label>

    <label style="margin-left:20px;">
      <input type="radio" name="mode_produksi" value="auto" onclick="mode_auto()"> Auto (BOM)
    </label>

  </div>
</div>

<div class="form-group" id="form_bom" style="display:none;">
  <label class="control-label col-lg-2">Pilih BOM</label>
  <div class="col-lg-10">

    <select id="kode_bom" class="form-control" onchange="pilih_bom_select(this.value)">
      <option value="">-- Pilih Barang Jadi --</option>

      <?php
      $q = $db->query("SELECT kd_barang, nm_barang FROM barang where kd_kategori in ('K02','K03') ORDER BY nm_barang ASC");
      foreach ($q as $k) {
          echo "<option value='$k->kd_barang'>$k->kd_barang - $k->nm_barang</option>";
      }
      ?>

    </select>

  </div>
</div>

<div class="form-group">
  <label class="control-label col-lg-2">Sales Order</label>
  <div class="col-lg-10">

   <select name="no_sales_order" id="no_sales_order" class="form-control chzn-select" onchange="pilih_sales_order(this.value)">
      <option value="">-- Pilih Sales Order --</option>

      <?php
      $qso = $db->query("
        SELECT no_sales_order, so_date, consignee 
        FROM sales_order 
        WHERE status IS NULL OR status != 'cancel'
        ORDER BY so_date DESC
      ");

      foreach ($qso as $so) {
          echo "<option value='$so->no_sales_order'>
                  $so->no_sales_order | $so->so_date | $so->consignee
                </option>";
      }
      ?>

    </select>

  </div>
</div>
              <div class="form-group" style="display: none">
                        <label for="Departemen" class="control-label col-lg-2">Jenis Produksi <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
                      <select  id="jenis_produksi" name="jenis_produksi" data-placeholder="Pilih Jenis Produksi ..." class="form-control chzn-select" tabindex="2" >
                         <option value=""></option>
                         <option value="1">Barang Setengah Jadi</option>
                         <option value="2">Barang Jadi</option>
                        </select>
                      </div>
                </div><!-- /.form-group -->
                      
              <div class="form-group" style="display: none">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" placeholder="Nomor" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group" style="display: none">
                <label for="No LP" class="control-label col-lg-2">No LP <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_bpb" placeholder="No LP" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal LP" class="control-label col-lg-2">Tanggal Produksi <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_bpb" required autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Project" class="control-label col-lg-2">Project <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="project" placeholder="Project" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
            <div class="form-group">
    <label class="control-label col-lg-2">
        Proses Produksi <span style="color:#FF0000">*</span>
    </label>
    <div class="col-lg-10">
        <?php foreach ($db->fetch_all("dept") as $isi) { ?>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="dept[]" value="<?php echo $isi->nm_dept; ?>">
                    <?php echo $isi->nm_dept; ?>
                </label>
            </div>
        <?php } ?>
    </div>
</div>

              <div class="form-group">
                <label for="Nama PPC" class="control-label col-lg-2">Nama PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="Nama PPC" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Catatan" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                 <textarea name="catatan" class="form-control" placeholder="Catatan" rows="4" style="resize:vertical;"></textarea>
                </div>
              </div><!-- /.form-group -->
               <!-- ===== BAHAN BAKU ===== -->
<h4>🔻 Konsumsi Bahan Baku</h4>

<div class="form-group">
  <div class="col-lg-12">
    <table class="table">
      <thead>
        <tr>
          <th style="width:50px;text-align:center">
            <a style="cursor:pointer;" onclick="add_baris()"><i class="fa fa-plus"></i></a>
          </th>
          <th>Kode Barang</th>
          <th>Unit</th>
          <th>Stock</th>
          <th>Qty</th>
          <th>Ket</th>
        </tr>
      </thead>
      <tbody id="isi_tabel">
        <tr id="baris_1">
          <td style="text-align:center">
            <a onclick="hapus_baris('1')" style="cursor:pointer">
              <i class="fa fa-trash"></i>
            </a>
          </td>

          <td>
            <input type="text" id="form_kode_1" class="form-control" onclick="cari_kode('1')">
            <input type="hidden" name="kode_input[]" id="kode_input_1">
          </td>

          <td>
            <input type="text" id="form_unit_1" name="unit[]" class="form-control" readonly>
          </td>

          <td>
            <input type="text" id="stock_1" class="form-control" readonly>
          </td>

          <td>
           <input type="number" 
                   id="qty_1" 
                   name="qty[]" 
                   class="form-control qty"
                   onkeyup="cek_stok_row(1)"> 
          </td>

          <td>
            <input type="text" name="ket[]" class="form-control">
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== HASIL PRODUKSI ===== -->
<h4>🔺 Hasil Produksi</h4>

<table class="table">
  <thead>
    <tr>
      <th style="width:50px;text-align:center">
        <a onclick="add_hasil()" style="cursor:pointer"><i class="fa fa-plus"></i></a>
      </th>
      <th>Kode Barang Jadi</th>
      <th>Qty OK</th>
      <th>Qty NG</th>
      <th>Lot Number</th>
      <th>Satuan</th>
    </tr>
  </thead>

  <tbody id="isi_hasil">
    <tr id="hasil_1">
      <td align="center">
        <a onclick="hapus_hasil(1)"><i class="fa fa-trash"></i></a>
      </td>

      <td>
        <input type="text" id="kode_jadi_1" class="form-control" onclick="cari_kode_jadi(1)">
        <input type="hidden" name="kode_jadi[]" id="kode_jadi_input_1">
      </td>

      <td>
       <input type="number" 
           name="qty_jadi[]" 
           id="qty_jadi_1" 
           class="form-control"
           placeholder="Qty OK">
      </td>

      <td>
          <input type="number" 
                 name="qty_ng[]" 
                 id="qty_ng_1" 
                 class="form-control"
                 value="0"
                 placeholder="Qty NG">
      </td>
      <td>
          <input type="text" 
       name="lot_no[]" 
       id="lot_no_1" 
       class="form-control lot_no"
       required
       autocomplete="off"
       placeholder="Lot Number">
      </td>

      <td>
          <input type="text" 
                 name="unit_jadi[]" 
                 id="unit_jadi_1" 
                 class="form-control">
      </td>
    </tr>
  </tbody>
</table>

<input type="hidden" id="jml_hasil" value="1">
                 </div>
               <input type="hidden" id="jml" value="1">
              
              </div>
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>lp-barang-jadi" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" id="btn_simpan" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>
    <div id="modal_bahan_baku" class="modal fade" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Detail Stok Bahan Baku</h4>
        </div>
        <div class="modal-body" id="detail_modal">
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>

    </section><!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script type="text/javascript">

  function hapus_hasil(id){
    $("#hasil_" + id).remove();
}
  function add_hasil(){

    var id = parseInt($("#jml_hasil").val()) + 1;

    var row = `
    <tr id="hasil_${id}">
        <td align="center">
            <a onclick="hapus_hasil(${id})"><i class="fa fa-trash"></i></a>
        </td>

        <td>
            <input type="text" id="kode_jadi_${id}" class="form-control" onclick="cari_kode_jadi(${id})">
            <input type="hidden" name="kode_jadi[]" id="kode_jadi_input_${id}">
        </td>

        <td>
         <input type="number" 
           name="qty_jadi[]" 
           class="form-control"
           placeholder="Qty OK">
          </td>

            <td>
                <input type="number" 
                       name="qty_ng[]" 
                       class="form-control"
                       value="0"
                       placeholder="Qty NG">
            </td>
             <td>
               <input type="text" 
       name="lot_no[]" 
       id="lot_no_${id}" 
       class="form-control lot_no"
       required
       autocomplete="off"
       placeholder="Lot Number">
            </td>


            <td>
                <input type="text" 
                       name="unit_jadi[]" 
                       id="unit_jadi_${id}" 
                       class="form-control">
            </td>
    </tr>
    `;

    $("#isi_hasil").append(row);
    $("#jml_hasil").val(id);
}

function pilih_sales_order(no_so){
    if(no_so == '') return;

    $.ajax({
        url: "<?= base_url() ?>modul/lp_barang_jadi/lp_barang_jadi_action.php?act=get_sales_order",
        type: "POST",
        data: { no_sales_order: no_so },
        dataType: "json",
        success: function(res){

            // 🔥 RESET TABLE HASIL
            $("#isi_hasil").html("");
            $("#jml_hasil").val(0);

            var no = 1;

            $.each(res, function(i, item){

                var row = `
                <tr id="hasil_${no}">
                    <td align="center">
                        <a onclick="hapus_hasil(${no})">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>

                    <td>
                        <input type="text" 
                               id="kode_jadi_${no}" 
                               class="form-control"
                               value="${item.kd_barang} - ${item.nm_barang}">
                        
                        <input type="hidden" 
                               name="kode_jadi[]" 
                               id="kode_jadi_input_${no}" 
                               value="${item.kd_barang}">
                    </td>

                    <td>
                        <input type="number" 
                               name="qty_jadi[]" 
                               id="qty_jadi_${no}" 
                               class="form-control"
                               value="${item.qty}">
                    </td>
                       <td>
                <input type="number" 
                       name="qty_ng[]" 
                       class="form-control"
                       value="0"
                       placeholder="Qty NG">
                      </td>
                       <td>
                          <input type="text" 
                                 name="lot_no[]" 
                                 id="lot_no_${no}" 
                                 class="form-control"                
                                 placeholder="Lot Number">
                      </td>

                    <td>
                        <input type="text" 
                               name="unit_jadi[]" 
                               id="unit_jadi_${no}" 
                               class="form-control"
                               value="${item.satuan}">
                    </td>
                </tr>
                `;

                $("#isi_hasil").append(row);

                no++;
            });

            $("#jml_hasil").val(no - 1);
        }
    });
}

  $(document).ready(function(){

 

    $("#kode_bom").chosen({
    width: "100%"
});

});

  function auto_hitung_bom(no){

    // 🔥 hanya jalan jika mode auto BOM
    if($("input[name='mode_produksi']:checked").val() != 'auto'){
        return;
    }

    var qty_jadi = parseFloat($("#qty_jadi_" + no).val()) || 0;

    $("#isi_tabel tr").each(function(index){

        var row = index + 1;

        // qty standard BOM
        var qty_std = parseFloat($("#qty_" + row).attr("data-std")) || 0;

        // hasil qty baru
        var qty_baru = qty_std * qty_jadi;

        $("#qty_" + row).val(qty_baru.toFixed(4));

        // cek stock ulang
        cek_stok_row(row);

    });

}

function pilih_bom_select(kode){

    if(kode == '') return;

    // 🔥 RESET HASIL PRODUKSI
    $("#isi_hasil").html("");
    $("#jml_hasil").val(0);

    var no = 1;

    // 🔥 INSERT 1 ROW HASIL (default dari BOM)
    var row = `
    <tr id="hasil_${no}">
        <td align="center">
            <a onclick="hapus_hasil(${no})">
                <i class="fa fa-trash"></i>
            </a>
        </td>
 
        <td>
            <input type="text" 
                   id="kode_jadi_${no}" 
                   class="form-control"
                   value="${kode}">
            
            <input type="hidden" 
                   name="kode_jadi[]" 
                   id="kode_jadi_input_${no}" 
                   value="${kode}">
        </td>

        <td>
           <input type="number" 
           name="qty_jadi[]" 
           id="qty_jadi_${no}" 
           class="form-control"
           value="1"
           onkeyup="auto_hitung_bom(${no})">
        </td>
        <td>
           <input type="number" 
           name="qty_ng[]" 
           id="qty_ng_${no}" 
           class="form-control"
           value="0">
        </td>

        <td>
            <input type="text" 
                   name="unit_jadi[]" 
                   id="unit_jadi_${no}" 
                   class="form-control">
        </td>
    </tr>
    `;

    $("#isi_hasil").append(row);
    $("#jml_hasil").val(1);

    // 🔥 AMBIL SATUAN
    $.ajax({
        type : 'POST',
        url  : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
        data : { kd_barang : kode },
        success:function(data){
            $("#unit_jadi_1").val(data);
        }
    });

    // 🔥 LOAD BOM → BAHAN BAKU
    load_bom(kode);
}

  function mode_manual(){
    $("#form_bom").hide();
}

function mode_auto(){
    $("#form_bom").show();
}
   function cek_stok(id,jumlah){
    var kode  = $("#kode_input_"+id).val();
    var jml   = parseFloat(jumlah);
    var stock = parseFloat($("#form_stock_"+id).val());
    if (jml>stock) {
        // alert("Inputan melebihi stock");
        $("#error_stock_"+id).html("Inputan melebihi stock");
        $("#error_stock_"+id).show();
        $("#form_qty_"+id).val('');
        $("#form_qty_"+id).focus();
    }else{
      $("#error_stock_"+id).hide();
    }
 
  }

  function cek_semua_stok(){

    var disable = false;

    $(".qty").each(function(index){

        var id = $(this).attr("id").split("_")[1];

        var stock = parseFloat($("#stock_" + id).val()) || 0;
        var qty   = parseFloat($(this).val()) || 0;

        if(qty > stock){
            disable = true;
        }

    });

    $("#btn_simpan").prop("disabled", disable);

}


function cek_stok_row(id){

    var stock = parseFloat($("#stock_" + id).val()) || 0;
    var qty   = parseFloat($("#qty_" + id).val()) || 0;

    if(qty > stock){
        $("#baris_" + id).css("background","#f2dede");
        $("#warning_" + id).html("Stock kurang");
    }else{
        $("#baris_" + id).css("background","");
        $("#warning_" + id).html("");
    }

    cek_semua_stok();
}


 function load_bom(kode_barang){ 

    $.ajax({
        url: "<?= base_url() ?>modul/lp_barang_jadi/lp_barang_jadi_action.php?act=get_bom",
        type: "POST",
        data: {kode_barang: kode_barang},
        dataType: "json",
        success: function(res){

            $("#isi_tabel").html("");

            var no = 1;

            $.each(res, function(i, item){

                var row = `
                <tr id="baris_${no}">
                    <td align="center">
                        <a onclick="hapus_baris('${no}'); cek_semua_stok();">
                            <i class="fa fa-trash-o"></i>
                        </a>
                    </td>

                    <td>
                        <input type="text" value="${item.kode} ${item.nama}" class="form-control">
                        <input type="hidden" name="kode_input[]" value="${item.kode}">
                    </td>

                    <td>
                        <input type="text" value="${item.satuan}" class="form-control" readonly>
                    </td>

                    <td>
                        <input type="text" id="stock_${no}" value="${item.stock}" class="form-control stock" readonly>
                        <span id="warning_${no}" style="color:red;font-size:12px;"></span>
                    </td>

                    <td>
                       <input type="number" 
                       name="qty[]" 
                       id="qty_${no}" 
                       value="${item.qty}" 
                       data-std="${item.qty}"
                       class="form-control qty"
                       onkeyup="cek_stok_row(${no})">
                    </td>

                    <td>
                        <input type="text" name="ket[]" class="form-control">
                    </td>
                </tr>
                `;

                $("#isi_tabel").append(row);

                // cek awal
                setTimeout(() => cek_stok_row(no), 100);

                no++;
            });

        }
    });
}


function pilih_bom(kode, nama){
    $("#kode_bom").val(kode + " - " + nama);
    $("#kode_bom_input").val(kode);

    // auto isi bahan baku
    load_bom(kode);

    // isi barang jadi otomatis
    $("#kode_jadi").val(kode);
}

  function get_detail_ro(no_ro){
   $.ajax({
          url: "<?= base_url() ?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=get_detail_ro",
          data: { 
            no_ro: no_ro
          },
          type : 'POST',
          success: function (data) {
             $("#form_ro").html(data);
          }
        })
  }

  function hapus_baris(id){
    $("#baris_" + id).remove();
    cek_semua_stok();
}
   function add_baris() {

    var id_baris = parseInt($("#jml").val()) + 1;

    var baris = `
    <tr id="baris_${id_baris}">
        <td style="text-align:center">
            <a onclick="hapus_baris('${id_baris}')" style="cursor:pointer">
                <i class="fa fa-trash"></i>
            </a>
        </td> 

        <td>
            <input type="text" id="form_kode_${id_baris}" class="form-control" onclick="cari_kode('${id_baris}')">
            <input type="hidden" name="kode_input[]" id="kode_input_${id_baris}">
        </td>
        <td>
            <input type="text" id="form_unit_${id_baris}" name="unit[]" class="form-control" readonly="">
          </td> 

  

        <td>
            <input type="text" id="stock_${id_baris}" class="form-control" readonly>
        </td>

        <td>
           
            <input type="number" 
             id="qty_${id_baris}" 
             name="qty[]" 
             class="form-control qty"
             onkeyup="cek_stok_row(${id_baris})"> 
        </td>

        <td>
            <input type="text" name="ket[]" class="form-control">
        </td>
    </tr>
    `;

    $("#isi_tabel").append(baris);
    $("#jml").val(id_baris);
}

function cari_kode_jadi(id){ 

    $('#kode_jadi_'+id).autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                type: "POST",
                dataType: "json",
                data: { term: request.term },
                success: function (data) {

                    response($.map(data, function (item) {
                        return {
                            label: item.kd_barang + " - " + item.nm_barang,
                            value: item.kd_barang,
                            kd_barang: item.kd_barang,
                            nm_barang: item.nm_barang
                        };
                    }));

                }
            });
        },

        select: function (event, ui) {

            $('#kode_jadi_'+id).val(ui.item.label);
            $('#kode_jadi_input_'+id).val(ui.item.kd_barang);

            // ambil satuan
            $.ajax({
                type: 'POST',
                url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                data: { kd_barang: ui.item.kd_barang },
                success: function (data) {
                    $("#unit_jadi_"+id).val(data);
                }
            });

            return false;
        }

    });
}

     function cari_kode(id) {   
    
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
                                  nm_barang: item.nm_barang,
                                  id_barang : item.id_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                             $("#id_input_"+id).val(ui.item.id_barang);

                             $.ajax({ 
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_produksi_by_barang",
                                type: 'POST',
                                data: { kd_barang: ui.item.kd_barang }, // ✅ FIX PARAM
                                dataType: 'JSON',
                                success: function (data) {

                                    var stock = parseFloat(data.stock) || 0;

                                    // isi ke input stock
                                    $("#stock_"+id).val(stock);

                                    // 🔥 warna indikator
                                    if(stock <= 0){
                                        $("#stock_"+id).css("background","#f2dede");
                                    }else{
                                        $("#stock_"+id).css("background","");
                                    }
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

  function detail_bahan_baku(){
    $("#modal_bahan_baku").modal("show");
  }
    $(document).ready(function() {
     
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
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
      
    $("#input_lp_barang_jadi").validate({
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

            tgl_bpb: {
                required: true
            },

            project: {
                required: true
            },

            'dept[]': {
                required: true
            },

            'lot_no[]': {
                required: true
            }

        },
         messages: {
            
          no_bpb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          tgl_bpb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          project: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dept: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
        },
    
        submitHandler: function(form) {

          Swal.fire({
          title: 'Yakin akan disimpan ?',
          showDenyButton: false,
          showCancelButton: true,
          confirmButtonText: 'Save',
          denyButtonText: `Don't save`,
        }).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
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
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
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
            } else if (result.isDenied) {
             // Swal.fire('Changes are not saved', '', 'info')
            }
          })
            
        }
    });
});
</script>
