<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<div class="row" style="padding-top: 15px">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><?=customs_h('supporting_documents','Dokumen Lampiran');?></h3>
          <button id="btn_tambah" class="btn btn-primary" style="float:right" onclick="show_tambah()"><i class="fa fa-plus"></i> Tambah</button>
        </div>
        
        <table class="table display" style="width:100%" id="example">
          <thead>
            <tr>
              <th>Seri</th>
              <th style="width: 200px">Jenis</th>
              <th><?=customs_h('number','Nomor');?></th>
              <th><?=customs_h('date','Tanggal');?></th>
              <th>Fasilitas</th>
              <th>Izin</th>
              <th>Kantor</th>
              <th>File</th>
              <th></th> 
            </tr>
          </thead>
          <tbody id="isi_dokumen">
          <?php
          $q = $db->query("select d.*,r.nama_dokumen from ws_dokumen d join ref_dokumen r on r.id_dokumen=d.kodeDokumen where d.id_header='$data_header->id_header'  ");
          if ($q->rowCount()==0) {
          ?>
          
          <?php
          }else{
            $no=1;
            foreach ($q as $k) {
               echo "<tr>
                       <td>$k->seriDokumen</td>
                       <td>$k->nama_dokumen</td>
                       <td>$k->nomorDokumen</td>
                       <td>".tgl_indo($k->tanggalDokumen)."</td>
                       <td>-</td>
                       <td>-<td>
                       <td></td>
                       <td>
                         <button class='btn btn-primary' onclick='edit_data($k->idDokumen)'><i class='fa fa-pencil'></i></button>
                         <button class='btn btn-danger' onclick='modal_delete($k->idDokumen)'><i class='fa fa-trash'></i></button>
                       </td>
                     </tr>";
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
<div id="modal_lampiran" class="modal fade" role="dialog">
                <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title"><?=customs_h('document_type','Jenis Dokumen');?></h4>
                    </div>
                    <div class="modal-body">
                      <form id="input_dokumen_pabean" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/dokumen_pabean/dokumen_pabean_action.php?act=in_dokumen">

                          <input type="hidden" name="id_dokumen" id="id_dokumen">
                      
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3">Seri </label>
                            <div class="col-lg-9">
                              <input type="text" name="SERI_DOKUMEN" id="SERI_DOKUMEN" class="form-control" readonly="">
                            </div>
                          </div><!-- /.form-group -->
                          
                          <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3"><?=customs_h('document_type','Jenis Dokumen');?> </label>
                            <div class="col-lg-9">
                              <select onchange="save_data(this.value,'kodeDokumen',$('#id_dokumen').val(),'ws_dokumen','idDokumen')" style="width:100%" name="KODE_JENIS_DOKUMEN" id="KODE_JENIS_DOKUMEN" class="form-control form-ref-dokumen" > 
                                 
                                 <?php
                                 $q = $db->query("select id_dokumen,nama_dokumen from ref_dokumen");
                                 foreach ($q as $k) {
                                  echo "<option value='$k->id_dokumen'>$k->id_dokumen - $k->nama_dokumen</option>";
                                 }
                                 ?>
                              </select>
                            </div>
                          </div>

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3"><?=customs_h('document_number','Nomor Dokumen');?> </label>
                            <div class="col-lg-9">
                              <input type="text" name="NOMOR_DOKUMEN" onkeyup="save_data(this.value,'nomorDokumen',$('#id_dokumen').val(),'ws_dokumen','idDokumen')" id="NOMOR_DOKUMEN" class="form-control">
                            </div>
                          </div>

                           <div class="form-group">
                            <label for="nomor" class="control-label col-lg-3"><?=customs_h('date','Tanggal');?></label>
                            <div class="col-lg-9">
                              <input type="text" onchange="save_data(this.value,'tanggalDokumen',$('#id_dokumen').val(),'ws_dokumen','idDokumen')" name="TANGGAL_DOKUMEN" id="TANGGAL_DOKUMEN" class="form-control tgl" autocomplete="off">
                            </div>
                          </div> 
                          
                                  
                       

                        </form>
                    </div>
                    <div class="modal-footer">
                       <button type="button" class="btn btn-danger" data-dismiss="modal">batal</button>
                       <button type="button" class="btn btn-success" onclick="simpan_dokumen()" >Simpan</button>
                    </div>
                  </div>

                </div>
              </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script> -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


<script type="text/javascript">

    new DataTable("#example");

  function modal_delete(id){
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
           url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=hapus_dokumen",
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
               url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_dokumen",
               type : "POST",
             //  dataType : "JSON",
               data : {
                 id : $("#ID").val()
               },
              // dataTye : 'JSON',
               success : function(data){
                $("#isi_dokumen").html(data);
               // $("#modal_lampiran").modal('hide');
               }
            });
             
           // $("#modal_lampiran").modal('hide');
           }
        });

       
      }
    });
  }
  
  function simpan_dokumen(){
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=reload_dokumen",
       type : "POST",
     //  dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){
        $("#isi_dokumen").html(data);
        $("#modal_lampiran").modal('hide');
       }
    });
     
  } 

  function edit_data(id) {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=edit_data",
       type : "POST",
       dataType : "JSON",
       data : {
         id : id
       },
      // dataTye : 'JSON',

       success : function(data){
        $("#id_dokumen").val(data.ID);
        var data_dokumen = {
           id: data.KODE_JENIS_DOKUMEN,
           text: data.nama_dokumen
         };  
       var option_dokumen = new Option(data_dokumen.text, data_dokumen.id, false, false);

        $('.form-ref-dokumen').val(data_dokumen.id).trigger('change');
        $("#SERI_DOKUMEN").val(data.SERI_DOKUMEN);
        $("#NOMOR_DOKUMEN").val(data.NOMOR_DOKUMEN);
        $("#TANGGAL_DOKUMEN").val(data.TANGGAL_DOKUMEN);
        
        $("#modal_lampiran").modal('show');
       }
    });
    
  }

  function show_tambah() {
     $.ajax({
       url : "<?= base_url() ?>modul/dokumen_pabean/dokumen_pabean_action.php?act=add_dokumen",
       type : "POST",
       dataType : "JSON",
       data : {
         id : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){

        $("#input_dokumen_pabean")[0].reset();
         var data_dokumen = {
           id: '',
           text: ''
         };  
        var option_dokumen = new Option(data_dokumen.text, data_dokumen.id, false, false);

        $('.form-ref-dokumen').val(data_dokumen.id).trigger('change');
        $("#SERI_DOKUMEN").val(data.seri);
        $("#id_dokumen").val(data.id);
        $("#modal_lampiran").modal('show');
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