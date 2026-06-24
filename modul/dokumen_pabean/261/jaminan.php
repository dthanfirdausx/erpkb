<div class="row" style="padding-top: 15px">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Jaminan</h3>
          <button id="btn_tambah" class="btn btn-primary" style="float:right" onclick="show_tambah_jaminan()"><i class="fa fa-plus"></i> Tambah</button>
        </div>
    
        <table class="table">
          <thead>
            <tr>
              <th><?=customs_h('no','No');?></th>
              <th>Jenis</th>
              <th><?=customs_h('number','Nomor');?></th>
              <th><?=customs_h('date','Tanggal');?></th>
              <th>Nilai</th>
              <th>Jatuh Tempo</th>
              <th>Penjamin</th>
              <th><?=customs_h('bpj_number','Nomor BPJ');?></th>
              <th><?=customs_h('bpj_date','Tanggal BPJ');?></th>
              <th></th> 
            </tr>
          </thead>
          <tbody id="isi_jaminan">
          <?php
          $q = $db->query("select j.idJaminan,j.id_header,j.nomorBpj,j.tanggalBpj,j.kodeJenisJaminan,j.nomorJaminan,j.tanggalJaminan,j.tanggalJatuhTempo,j.penjamin,j.nilaiJaminan ,jj.jenis_jaminan from ws_jaminan j left join ws_header h on h.id_header=j.id_header left join ref_jenis_jaminan jj on jj.id_jenis_jaminan=j.kodeJenisJaminan where j.id_header='$data_header->id_header'  "); 
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
                       <td>$k->jenis_jaminan</td>
                       <td>$k->nomorJaminan</td>
                       <td>$k->tanggalJaminan</td>
                       <td>$k->nilaiJaminan</td>
                       <td>$k->tanggalJatuhTempo</td>
                       <td>$k->penjamin</td>
                       <td>$k->nomorBpj</td>
                       <td>$k->tanggalBpj</td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data_jaminan($k->idJaminan)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete_jaminan($k->idJaminan)'><i class='fa fa-trash'></i></button>
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
<div id="modal_jaminan" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg" style="width: 80%">
                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">DetailJaminan</h4>
                    </div>
                    <div class="modal-body">
                      <form id="input_dokumen_kemasan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=in_dokumen">
                      
                          <input type="hidden" name="idJaminan" id="idJaminan">
                    <div class="col-md-4">
                      <div class="box box-primary">
                        <div class="box-header with-border">
                          <h3 class="box-title">Jaminan</h3>
                          
                        </div>
                        <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4">Jenis Jaminan</label>
                            <div class="col-lg-8">
                              <select class="form-control" id="kodeJenisJaminan" onchange="save_data(this.value,'kodeJenisJaminan',$('#idJaminan').val(),'ws_jaminan','idJaminan')">
                                <option value="">-Jenis Jaminan-</option>
                                <?php
                                foreach ($db->query("select * from ref_jenis_jaminan") as $kj) {
                                  echo "<option value='$kj->id_jenis_jaminan'>$kj->jenis_jaminan</option>";
                                }
                                ?>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4"><?=customs_h('guarantee_number','Nomor Jaminan');?> </label>
                            <div class="col-lg-8">
                              <input type="text" name="nomorJaminan" id="nomorJaminan" class="form-control" onkeyup="save_data(this.value,'nomorJaminan',$('#idJaminan').val(),'ws_jaminan','idJaminan')" >
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4"><?=customs_h('guarantee_date','Tanggal Jaminan');?> </label>
                            <div class="col-lg-8">
                              <input type="text" name="tanggalJaminan" id="tanggalJaminan" class="form-control" onchange="save_data(this.value,'tanggalJaminan',$('#idJaminan').val(),'ws_jaminan','idJaminan')" autocomplete="off" >
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4">Nilai Jaminan</label>
                            <div class="col-lg-8">
                              <input type="text" name="nilaiJaminan" id="nilaiJaminan" class="form-control" onkeyup="save_data(this.value,'nilaiJaminan',$('#idJaminan').val(),'ws_jaminan','idJaminan')" >
                            </div>
                          </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <div class="box box-primary">
                        <div class="box-header with-border">
                          <h3 class="box-title">Jatuh Tempo & Penjamin</h3>
                          
                        </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4"><?=customs_h('due_date','Tanggal Jatuh Tempo');?></label>
                            <div class="col-lg-8">
                              <input type="text" name="tanggalJatuhTempo" id="tanggalJatuhTempo" class="form-control" onchange="save_data(this.value,'tanggalJatuhTempo',$('#idJaminan').val(),'ws_jaminan','idJaminan')" autocomplete="off">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4">Penjamin</label>
                            <div class="col-lg-8">
                              <input type="text" name="penjamin" id="penjamin" class="form-control" onkeyup="save_data(this.value,'penjamin',$('#idJaminan').val(),'ws_jaminan','idJaminan')" >
                            </div>
                          </div>
                        
                      </div>
                  </div>
 
                   <div class="col-md-4">
                      <div class="box box-primary">
                        <div class="box-header with-border">
                          <h3 class="box-title">Bukti Penerimaan Jaminan</h3>
                          
                        </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4"><?=customs_h('guarantee_receipt_number','Nomor Bukti Penerimaan Jaminan');?></label>
                            <div class="col-lg-8">
                              <input type="text" name="nomorBpj" id="nomorBpj" class="form-control" onkeyup="save_data(this.value,'nomorBpj',$('#idJaminan').val(),'ws_jaminan','idJaminan')" >
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-4"><?=customs_h('guarantee_receipt_date','Tanggal Bukti Penerimaan Jaminan');?></label>
                            <div class="col-lg-8">
                              <input type="text" name="tanggalBpj" id="tanggalBpj" class="form-control" onchange="save_data(this.value,'tanggalBpj',$('#idJaminan').val(),'ws_jaminan','idJaminan')" autocomplete="off">
                            </div>
                          </div>
                         
                      </div>
                  </div>
                      

                         
                          
                                  
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_dokumen_jaminan()" >Simpan</button>
                    </div>
                  </div>

                </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">


  $(document).ready(function() {
    $("#tanggalJaminan").datepicker({ 
      format: "yyyy-mm-dd",
      autoclose: true, 
      todayHighlight: true
    });
    $("#tanggalJatuhTempo").datepicker({ 
      format: "yyyy-mm-dd",
      autoclose: true, 
      todayHighlight: true
    });
    $("#tanggalBpj").datepicker({ 
      format: "yyyy-mm-dd",
      autoclose: true, 
      todayHighlight: true
    });
  });
 
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
  
  function simpan_dokumen_jaminan(){ 
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_jaminan",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val(),
         idJaminan : $("#idJaminan").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
        $("#isi_jaminan").html(data);
        $("#modal_jaminan").modal('hide');
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

  function edit_data_jaminan(id) {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_data_jaminan",
       type : "POST",
       dataType : "JSON",
       data : {
         id : id
       },
      // dataTye : 'JSON',
       success : function(data){
        $("#kodeJenisJaminan").val(data.kodeJenisJaminan);
        $("#idJaminan").val(data.idJaminan);
        $("#nomorJaminan").val(data.nomorJaminan);
        $("#tanggalJaminan").val(data.tanggalJaminan);
        $("#nilaiJaminan").val(data.nilaiJaminan);
        $("#tanggalJatuhTempo").val(data.tanggalJatuhTempo);
        $("#penjamin").val(data.penjamin);
        $("#nomorBpj").val(data.nomorBpj);
        $("#tanggalBpj").val(data.tanggalBpj);
        $("#modal_jaminan").modal('show');
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

  function show_tambah_jaminan() {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=add_jaminan",
       type : "POST",
       dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){
        $("#idJaminan").val(data.id);
         $("#kodeJenisJaminan").val('');
        //$("#idJaminan").val(data.idJaminan);
        $("#nomorJaminan").val('');
        $("#tanggalJaminan").val('');
        $("#nilaiJaminan").val('');
        $("#tanggalJatuhTempo").val('');
        $("#penjamin").val('');
        $("#nomorBpj").val(''); 
        $("#tanggalBpj").val('');
        $("#modal_jaminan").modal('show');
        $("#modal_jaminan").modal('show');
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