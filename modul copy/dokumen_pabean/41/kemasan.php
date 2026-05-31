<div class="row" style="padding-top: 15px">
    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Kemasan</h3>
          <button id="btn_tambah" class="btn btn-primary" style="float:right" onclick="show_tambah_kemasan()"><i class="fa fa-plus"></i> Tambah</button>
        </div>
        
        <table class="table">
          <thead>
            <tr>
              <th>Seri</th>
              <th>Jumlah</th>
              <th>Jenis</th>
              <th>Merk</th>
              <th></th> 
            </tr>
          </thead>
          <tbody id="isi_kemasan">
          <?php
          $q = $db->query("select d.*,j.kemasan from ws_kemasan d join ws_header h on h.id_header=d.id_header 
             left join ref_jenis_kemasan j on j.id_kemasan=d.kodeJenisKemasan where d.id_header='$data_header->id_header'  ");
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$no</td>
                       <td>$k->jumlahKemasan</td>
                       <td>$k->kemasan</td>
                       <td>$k->merkKemasan</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_kemasan($k->id_kemasan)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_kemasan($k->id_kemasan)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
                $no++;
            }
          }
          ?>
            
          </tbody>
        </table>
      </div>
    </div>  

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Peti Kemas</h3>
          <button id="btn_tambah" class="btn btn-primary" style="float:right" onclick="show_tambah_kontainer()"><i class="fa fa-plus"></i> Tambah</button>
        </div>
        
        <table class="table">
          <thead>
            <tr>
              <th>Seri</th>
              <th>Nomor</th>
              <th>Ukuran</th>
              <th>Jenis</th>
              <th>Tipe</th>
              <th></th> 
            </tr>
          </thead>
          <tbody id="isi_kontainer">
          <?php

          $q = $db->query("select d.*,j.tipe_kontainer,u.ukuran_kontainer,rf.jenis_kontainer from ws_kontainer d join ws_header h on h.id_header=d.id_header 
             left join ref_tipe_kontainer j on j.id_tipe_kontainer=d.kodeTipeKontainer
             left join ref_ukuran_kontainer u on u.id_ukuran=d.kodeUkuranKontainer
             left join ref_jenis_kontainer rf on rf.id_jenis_kontainer=d.kodeJenisKontainer where d.id_header='$data_header->id_header'  ");
          if ($q->rowCount()==0) { 
          ?>
          <tr>
              <td colspan="8">Belum ada data</td>
            </tr>
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$no</td>
                       <td>$k->nomorKontainer</td>
                       <td>$k->ukuran_kontainer</td>
                       <td>$k->jenis_kontainer</td> 
                       <td>$k->tipe_kontainer</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_kontainer($k->id_kontainer)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_kontainer($k->id_kontainer)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
                $no++;
            }
          }
          ?>
            
          </tbody>
        </table>
      </div>
    </div>  
    
</div>
<div class="row"> 
  <div class="col-md-12">
    <a style="float: right" data-toggle="tab" class="btn btn-primary" style="left: 45px"  onclick="activaTab('tab_pengangkut')">Next >></a>
    <a style="float: right;margin-right: 10px" data-toggle="tab"  class="btn btn-warning" onclick="activaTab('tab_entitas')">Back <<</a>
  </div>
</div> 
<div id="modal_kemasan" class="modal fade" role="dialog">
                <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Kemasan</h4>
                    </div>
                    <div class="modal-body">
                      <form id="input_dokumen_kemasan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=in_dokumen">

                          <input type="text" name="id_kemasan" id="id_kemasan">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="SERI_KEMASAN" id="SERI_KEMASAN" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Jumlah </label>
                            <div class="col-lg-9">
                              <input type="text" name="JUMLAH_KEMASAN" onkeyup="save_data(this.value,'jumlahKemasan',$('#id_kemasan').val(),'ws_kemasan','id_kemasan')" id="JUMLAH_KEMASAN" class="form-control">
                            </div> 
                          </div>
                          
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Jenis Kemasan </label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeJenisKemasan',$('#id_kemasan').val(),'ws_kemasan','id_kemasan')" style="width:100%" name="KODE_JENIS_KEMASAN" id="KODE_JENIS_KEMASAN" class="form-control form-ref-kemasan" >    
                                <option value="">-Pilih Kemasan-</option>                             
                                 <?php
                                 $q = $db->query("select id_kemasan,kemasan from ref_jenis_kemasan");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_kemasan'>$k->id_kemasan - $k->kemasan</option>";
                                 }
                                 ?>
                              </select>
                            </div> 
                          </div>

                          

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Merk</label>
                            <div class="col-lg-9">
                              <input type="text" onkeyup="save_data(this.value,'merkKemasan',$('#id_kemasan').val(),'ws_kemasan','id_kemasan')" name="MERK_KEMASAN" id="MERK_KEMASAN" class="form-control">
                            </div>
                          </div> 
                          
                                  
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_dokumen_kemasan()" >Simpan</button>
                    </div>
                  </div>

                </div>
</div>

<div id="modal_kontainer" class="modal fade" role="dialog">
                <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Peti Kemas</h4>
                    </div>
                    <div class="modal-body">
                      <form id="input_dokumen_kontainer" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=in_dokumen">

                          <input type="text" name="id_kontainer" id="id_kontainer">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="SERI_KONTAINER" id="SERI_KONTAINER" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Nomor </label>
                            <div class="col-lg-9">
                              <input type="text" name="NOMOR_KONTAINER" onkeyup="save_data(this.value,'nomorKontainer',$('#id_kontainer').val(),'ws_kontainer','id_kontainer')" id="NOMOR_KONTAINER" class="form-control">
                            </div>
                          </div>
                          
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Ukuran</label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeUkuranKontainer',$('#id_kontainer').val(),'ws_kontainer','id_kontainer')" style="width:100%" name="KODE_UKURAN_KONTAINER" id="KODE_UKURAN_KONTAINER" class="form-control form-ukuran-kontainer" >    
                                <option value="">-Pilih Ukuran-</option>                             
                                 <?php
                                 $q = $db->query("select id_ukuran,ukuran_kontainer  from ref_ukuran_kontainer");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_ukuran'>$k->id_ukuran - $k->ukuran_kontainer</option>";
                                 }
                                 ?>
                              </select>
                            </div> 
                          </div>

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Jenis</label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeJenisKontainer',$('#id_kontainer').val(),'ws_kontainer','id_kontainer')" style="width:100%" name="KODE_TIPE_KONTAINER" id="KODE_TIPE_KONTAINER" class="form-control form-tipe-kontainer" >    
                                <option value="">-Pilih Ukuran-</option>                             
                                 <?php
                                 $q = $db->query("select id_jenis_kontainer,jenis_kontainer  from ref_jenis_kontainer");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_jenis_kontainer'>$k->id_jenis_kontainer - $k->jenis_kontainer</option>";
                                 }
                                 ?>
                              </select>
                            </div> 
                          </div>


                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Tipe</label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeTipeKontainer',$('#id_kontainer').val(),'ws_kontainer','id_kontainer')" style="width:100%" name="TIPE_KONTAINER" id="TIPE_KONTAINER" class="form-control form-tipe-ukuran-kontainer" >    
                                <option value="">-Pilih Ukuran-</option>                             
                                 <?php
                                 $q = $db->query("select id_tipe_kontainer,tipe_kontainer  from ref_tipe_kontainer");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_tipe_kontainer'>$k->id_tipe_kontainer - $k->tipe_kontainer</option>";
                                 }
                                 ?>
                              </select>
                            </div> 
                          </div>

 
                          

                          
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_dokumen_kontainer()" >Simpan</button>
                    </div>
                  </div>

                </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
 
  $(".form-ref-kemasan").select2();

 function modal_delete_kontainer(id){
    Swal.fire({ 
      title: 'Yakin akan di hapus ?',
      text: "data yang sudah terhapus tidak bisa di kembalikan",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) { 

        $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_kontainer",
           type : "POST",
         //  dataType : "JSON",
           data : {
             id : id
           },
          // dataTye : 'JSON',
           success : function(data){
              Swal.fire(
                'Deleted!',
                'Your data has been deleted.',
                'success'
              )
              $.ajax({
               url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_kontainer",
               type : "POST",
             //  dataType : "JSON",
               data : {
                 id : $("#ID").val()
               },
              // dataTye : 'JSON',
               success : function(data){
                $("#isi_kontainer").html(data);
               // $("#modal_lampiran").modal('hide');
               }
            });
             
           // $("#modal_lampiran").modal('hide');
           }
        });

       
      }
    });
  }


  function modal_delete_kemasan(id){
    Swal.fire({ 
      title: 'Yakin akan di hapus ?',
      text: "data yang sudah terhapus tidak bisa di kembalikan",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) { 

        $.ajax({
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_kemasan",
           type : "POST",
         //  dataType : "JSON",
           data : {
             id : id
           },
          // dataTye : 'JSON',
           success : function(data){
              Swal.fire(
                'Deleted!',
                'Your data has been deleted.',
                'success'
              )
              $.ajax({
               url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_kemasan",
               type : "POST",
             //  dataType : "JSON",
               data : {
                 id : $("#ID").val()
               },
              // dataTye : 'JSON',
               success : function(data){
                $("#isi_kemasan").html(data);
               // $("#modal_lampiran").modal('hide');
               }
            });
             
           // $("#modal_lampiran").modal('hide');
           }
        });

       
      }
    });
  }
  
  function simpan_dokumen_kemasan(){ 
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_kemasan",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
        $("#isi_kemasan").html(data);
        $("#modal_kemasan").modal('hide');
       }
    });
     
  }

  function simpan_dokumen_kontainer(){ 
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_kontainer",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){  
        $("#isi_kontainer").html(data);
        $("#modal_kontainer").modal('hide');
       }
    });
     
  }

  function edit_data_kemasan(id) {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_data_kemasan",
       type : "POST",
       dataType : "JSON",
       data : {
         id : id
       },
      // dataTye : 'JSON',
       success : function(data){
        var data_kemasan = {
           id: data.KODE_JENIS_KEMASAN,
           text: data.nama_dokumen
         };  
       //var option_dokumen = new Option(data_dokumen.text, data_dokumen.id, false, false);

        $('.form-ref-kemasan').val(data_kemasan.id).trigger('change');
        $("#SERI_KEMASAN").val(data.SERI_KEMASAN);
        $("#JUMLAH_KEMASAN").val(data.JUMLAH_KEMASAN);
        $("#MERK_KEMASAN").val(data.MERK_KEMASAN);
        $("#id_kemasan").val(data.ID);
        $("#modal_kemasan").modal('show');
       }
    });
    
  } 

  function edit_data_kontainer(id) {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_data_kontainer",
       type : "POST",
       dataType : "JSON",
       data : {
         id : id
       },
      // dataTye : 'JSON',
       success : function(data){
        // var data_kemasan = {
        //    id: data.KODE_JENIS_KEMASAN,
        //    text: data.nama_dokumen
        //  };  
       //var option_dokumen = new Option(data_dokumen.text, data_dokumen.id, false, false);
 // $res['TIPE_KONTAINER'] = $k->TIPE_KONTAINER;
 //    $res['ID'] = $k->ID; 
 //    $res['KODE_TIPE_KONTAINER'] = $k->KODE_TIPE_KONTAINER;
 //    $res['KODE_UKURAN_KONTAINER'] = $k->KODE_UKURAN_KONTAINER;
 //    $res['NOMOR_KONTAINER'] = $k->NOMOR_KONTAINER;
 //    $res['SERI_KONTAINER'] = $k->SERI_KONTAINER;
       // $('.form-ref-kemasan').val(data_kemasan.id).trigger('change');
        $("#TIPE_KONTAINER").val(data.TIPE_KONTAINER);
        $("#KODE_TIPE_KONTAINER").val(data.KODE_TIPE_KONTAINER);
        $("#KODE_UKURAN_KONTAINER").val(data.KODE_UKURAN_KONTAINER);
        $("#NOMOR_KONTAINER").val(data.NOMOR_KONTAINER);
        $("#SERI_KONTAINER").val(data.SERI_KONTAINER);
        $("#id_kontainer").val(data.ID);
        $("#modal_kontainer").modal('show'); 
       }
    });
    
  } 

  function show_tambah_kontainer() {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=add_kontainer",
       type : "POST",
       dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){

        $("#input_dokumen_kontainer")[0].reset();
         var data_ukuran_kontainer = {
           id: '',
           text: ''
         };  
        var option_ukuran_kontainer = new Option(data_ukuran_kontainer.text, data_ukuran_kontainer.id, false, false);

        $('.form-ukuran-kontainer').append(option_ukuran_kontainer).trigger('change');

         var data_tipe = {
           id: '',
           text: ''
         };  
        var option_tipe = new Option(data_tipe.text, data_tipe.id, false, false);
        $('.form-tipe-kontainer').append(option_tipe).trigger('change');

        var data_tipe_ukuran = {
           id: '',
           text: ''
         };  
        var option_tipe_ukuran = new Option(data_tipe_ukuran.text, data_tipe_ukuran.id, false, false);
        $('.form-tipe-ukuran-kontainer').append(option_tipe_ukuran).trigger('change');


        $("#SERI_KONTAINER").val(data.seri);
        $("#id_kontainer").val(data.id); 
        $("#modal_kontainer").modal('show');
       }
    });
    
  }

  function show_tambah_kemasan() {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=add_kemasan",
       type : "POST",
       dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){

        $("#input_dokumen_kemasan")[0].reset();
         var data_kemasan = {
           id: '',
           text: ''
         };  
        var option_kemasan = new Option(data_kemasan.text, data_kemasan.id, false, false);

        $('.form-ref-kemasan').append(option_kemasan).trigger('change');
        $("#SERI_KEMASAN").val(data.seri);
        $("#id_kemasan").val(data.id);
        $("#modal_kemasan").modal('show');
       }
    });
    
  }

  $(document).ready(function() {
    $(".tgl").datepicker({ 
      format: "yyyy-mm-dd",
      autoclose: true, 
      todayHighlight: true
    });
  });
</script>